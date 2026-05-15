<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Contracts\AuditLogServiceInterface;
use App\Contracts\DeployServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Contracts\SftpClientInterface;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\AiAgentService;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class SiteController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites,
        private HtmlGeneratorInterface $generator,
        private DeployServiceInterface $deploy,
        private SftpClientInterface $sftp,
        private AuditLogServiceInterface $audit,
        private AiAgentService $aiAgentService,
        private AiAgentConfigRepositoryInterface $aiConfigs,
        private ImportService $importService
    ) {}

    public function index(): JsonResponse
    {
        $sites = $this->sites->getAll();
        return response()->json($sites);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:sites,domain',
            'template_set' => 'required|string|max:100',
            'output_path' => 'required|string|max:500',
            'status' => 'nullable|in:active,inactive,draft',
            'locale' => 'nullable|string|max:10',
            'default_locale' => 'nullable|string|max:10',
            'sftp_host' => 'nullable|string',
            'sftp_port' => 'nullable|integer',
            'sftp_username' => 'nullable|string',
            'sftp_password' => 'nullable|string',
            'sftp_private_key' => 'nullable|string',
            'sftp_auth_method' => 'nullable|in:password,key',
            'sftp_remote_path' => 'nullable|string',
            'ai_clone_templates' => 'nullable|boolean',
            'ai_source_domain' => 'nullable|string|max:255',
            'ai_field_prompts' => 'nullable|array',
            'ai_field_prompts.*.file' => 'required_with:ai_field_prompts|string|max:255',
            'ai_field_prompts.*.path' => 'required_with:ai_field_prompts|string|max:1000',
            'ai_field_prompts.*.prompt' => 'required_with:ai_field_prompts|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        if (!empty($data['sftp_password'])) {
            $data['sftp_password'] = encrypt($data['sftp_password']);
        }
        
        if (!empty($data['sftp_private_key'])) {
            $data['sftp_private_key'] = encrypt($data['sftp_private_key']);
        }

        $aiCloneTemplates = (bool) ($data['ai_clone_templates'] ?? false);
        $aiSourceDomain = trim((string) ($data['ai_source_domain'] ?? 'test.com'));
        $aiFieldPrompts = $data['ai_field_prompts'] ?? [];

        unset($data['ai_clone_templates'], $data['ai_source_domain'], $data['ai_field_prompts']);

        $site = $this->sites->create($data);
        $aiGeneration = [
            'enabled' => $aiCloneTemplates,
            'updated_fields' => 0,
            'updated_files' => 0,
            'updated_paths' => [],
        ];

        try {
            if ($aiCloneTemplates) {
                $aiGeneration = $this->processAiTemplateGeneration(
                    site: $site,
                    userId: (int) (auth()->id() ?? 0),
                    sourceDomain: $aiSourceDomain !== '' ? $aiSourceDomain : 'test.com',
                    prompts: is_array($aiFieldPrompts) ? $aiFieldPrompts : []
                );
            }
        } catch (\Throwable $e) {
            try {
                $templatesRoot = (string) config(
                    'services.ai_agent.templates_root',
                    storage_path('import-deploy/md/test/raw_html')
                );
                $targetDir = rtrim($templatesRoot, '/') . '/' . $site->domain;
                if (is_dir($targetDir)) {
                    File::deleteDirectory($targetDir);
                }
            } catch (\Throwable) {
                // best effort cleanup
            }

            $this->sites->delete($site);

            return response()->json([
                'error' => 'AI template generation failed',
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->audit->log('site.created', Site::class, $site->id, null, $site->toArray());

        $payload = $site->toArray();
        $payload['ai_generation'] = $aiGeneration;

        return response()->json($payload, 201);
    }

    public function show(int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        return response()->json($site);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|unique:sites,domain,' . $id,
            'template_set' => 'sometimes|string|max:100',
            'output_path' => 'sometimes|string|max:500',
            'status' => 'sometimes|in:active,inactive,draft',
            'locale' => 'sometimes|string|max:10',
            'default_locale' => 'sometimes|string|max:10',
            'sftp_host' => 'nullable|string',
            'sftp_port' => 'nullable|integer',
            'sftp_username' => 'nullable|string',
            'sftp_password' => 'nullable|string',
            'sftp_private_key' => 'nullable|string',
            'sftp_auth_method' => 'nullable|in:password,key',
            'sftp_remote_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $site->toArray();
        $data = $validator->validated();
        
        if (!empty($data['sftp_password'])) {
            $data['sftp_password'] = encrypt($data['sftp_password']);
        }
        
        if (!empty($data['sftp_private_key'])) {
            $data['sftp_private_key'] = encrypt($data['sftp_private_key']);
        }

        $site = $this->sites->update($site, $data);
        
        $this->audit->log('site.updated', Site::class, $site->id, $oldValues, $site->toArray());

        return response()->json($site);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $this->audit->log('site.deleted', Site::class, $site->id, $site->toArray(), null);

        $deleted = $this->sites->delete($site);
        if (!$deleted) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => 'Site could not be deleted from local database/storage.',
            ], 500);
        }

        return response()->json(['message' => 'Site deleted successfully']);
    }

    public function clone(Request $request, int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:sites,domain',
            'output_path' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newSite = $this->sites->clone($site, $validator->validated());
        
        $this->audit->log('site.cloned', Site::class, $newSite->id, null, $newSite->toArray());

        return response()->json($newSite, 201);
    }

    public function generate(int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $activePagesCount = app(\App\Contracts\PageRepositoryInterface::class)->getActiveBySite($site)->count();

        if ($activePagesCount > 100) {
            \App\Jobs\GenerateSiteJob::dispatch($site, auth()->id());
            return response()->json([
                'message' => 'Site generation dispatched to queue',
                'job_id' => "site_generation_progress_{$site->id}"
            ], 202);
        }

        $result = $this->generator->generateSite($site);
        
        $this->audit->log('site.generated', Site::class, $site->id);

        return response()->json($result);
    }

    public function deploy(Request $request, int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        if ($request->input('background', false)) {
            \App\Jobs\DeploySiteJob::dispatch($site, auth()->id());
            return response()->json([
                'message' => 'Site deployment dispatched to queue'
            ], 202);
        }

        try {
            // 1. Generate full static site to prepare staging files for deployment.
            $generation = $this->generator->generateSite($site);
            if (($generation['success'] ?? false) !== true) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation failed',
                    'generation_errors' => $generation['errors'] ?? [],
                    'message' => 'HTML generation failed, deployment skipped.',
                ], 422);
            }

            // 2. Test SFTP connection.
            $sftpConnected = $this->sftp->testConnection($site);
            if (!$sftpConnected) {
                return response()->json([
                    'success' => false,
                    'error' => 'SFTP connection failed',
                    'message' => 'Could not connect to SFTP server. Please check SFTP settings.'
                ], 400);
            }

            // 3. Deploy generated files and run post-deploy SSH commands by default.
            $runPostDeployCommands = (bool) $request->boolean('run_post_deploy_commands', true);
            $deployment = $this->deploy->deploy($site, $runPostDeployCommands);
            if ($deployment->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => $deployment->error_message ?: 'Deployment failed',
                    'message' => 'Deployment finished with failure.',
                    'site_id' => $site->id,
                    'sftp_host' => $site->sftp_host,
                    'remote_path' => $site->sftp_remote_path,
                    'deployment' => $deployment,
                ], 422);
            }

            $this->audit->log('site.deployed', Site::class, $site->id);

            return response()->json($deployment);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Deploy failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importAndDeploy(Request $request, int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        try {
            $importRelativePath = trim((string) $request->input('import_path', 'import-deploy/md/test/contact-us.md'));
            if ($importRelativePath === '' || str_contains($importRelativePath, '..')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid import_path',
                    'message' => 'import_path must be a storage-relative path without ".."',
                ], 422);
            }

            $importFile = storage_path($importRelativePath);
            if (!file_exists($importFile)) {
                return response()->json(['error' => 'Import file not found: ' . $importFile], 404);
            }

            // 1. Import pages and SFTP config from file.
            $importService = app(\App\Services\ImportService::class);
            $result = $importService->importFromMdFile($importFile, $site->id);
            $site = ($result['site'] ?? $site)->fresh() ?? $site->fresh() ?? $site;
            $pagesCount = (int) ($result['pages_count'] ?? 0);

            // 2. Generate full static site to prepare staging files for deployment.
            $generation = $this->generator->generateSite($site);
            if (($generation['success'] ?? false) !== true) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation failed',
                    'generation_errors' => $generation['errors'] ?? [],
                    'message' => 'HTML generation failed, deployment skipped.',
                ], 422);
            }

            // 3. Test SFTP connection.
            $sftpConnected = $this->sftp->testConnection($site);
            if (!$sftpConnected) {
                return response()->json([
                    'success' => false,
                    'error' => 'SFTP connection failed',
                    'message' => 'Could not connect to SFTP server. Please check SFTP settings.'
                ], 400);
            }

            // 4. Deploy generated files.
            $deployment = $this->deploy->deploy($site, true);
            if ($deployment->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => $deployment->error_message ?: 'Deployment failed',
                    'message' => 'Import & Deploy finished with deployment failure.',
                    'site_id' => $site->id,
                    'sftp_host' => $site->sftp_host,
                    'remote_path' => $site->sftp_remote_path,
                    'pages_count' => $pagesCount,
                    'deployment' => $deployment,
                ], 422);
            }

            $this->audit->log('site.import_and_deployed', Site::class, $site->id);

            return response()->json([
                'success' => true,
                'message' => 'Import & Deploy completed successfully!',
                'site_id' => $site->id,
                'sftp_host' => $site->sftp_host,
                'remote_path' => $site->sftp_remote_path,
                'pages_count' => $pagesCount,
                'deployment' => $deployment
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Import & Deploy failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testSftp(int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $success = $this->sftp->testConnection($site);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful' : 'Connection failed'
        ]);
    }

    /**
     * @param  array<int, array{file?:string,path?:string,prompt?:string}>  $prompts
     */
    private function processAiTemplateGeneration(
        Site $site,
        int $userId,
        string $sourceDomain,
        array $prompts
    ): array {
        if ($userId <= 0) {
            throw new RuntimeException('Authenticated user is required for AI template generation.');
        }

        $this->aiAgentService->cloneDomainTemplates($sourceDomain, $site->domain);
        $this->audit->log('ai.templates.cloned', Site::class, $site->id, null, [
            'source_domain' => $sourceDomain,
            'target_domain' => $site->domain,
        ]);

        if ($prompts === []) {
            $importStats = $this->importClonedTemplatesIntoSite($site);
            $this->audit->log('ai.templates.imported', Site::class, $site->id, null, $importStats);
            return [
                'enabled' => true,
                'updated_fields' => 0,
                'updated_files' => 0,
                'updated_paths' => [],
            ];
        }

        $config = $this->aiConfigs->findForUser($userId);
        $result = $this->aiAgentService->applyPromptsToDomain(
            targetDomain: $site->domain,
            fieldPrompts: $prompts,
            config: $config,
            // During initial site creation, the site is new and cannot be pre-listed
            // in allowed_sites ahead of time. Path access rules still apply.
            siteId: null
        );

        $updatedPaths = [];
        foreach (($result['details'] ?? []) as $detail) {
            if (!is_array($detail)) {
                continue;
            }

            foreach (($detail['updated_paths'] ?? []) as $path) {
                if (is_string($path) && $path !== '') {
                    $updatedPaths[] = $path;
                }
            }
        }
        $updatedPaths = array_values(array_unique($updatedPaths));

        $this->audit->log('ai.templates.generated', Site::class, $site->id, null, [
            'updated_fields' => $result['updated_fields'],
            'updated_files' => $result['updated_files'],
            'updated_paths' => $updatedPaths,
        ]);

        $importStats = $this->importClonedTemplatesIntoSite($site);
        $this->audit->log('ai.templates.imported', Site::class, $site->id, null, $importStats);

        return [
            'enabled' => true,
            'updated_fields' => (int) ($result['updated_fields'] ?? 0),
            'updated_files' => (int) ($result['updated_files'] ?? 0),
            'updated_paths' => $updatedPaths,
        ];
    }

    /**
     * Import cloned per-page md templates into pages/sections of the created site.
     *
     * @return array{files_count:int,pages_count:int}
     */
    private function importClonedTemplatesIntoSite(Site $site): array
    {
        $templatesRoot = (string) config(
            'services.ai_agent.templates_root',
            storage_path('import-deploy/md/test/raw_html')
        );
        $domainDir = rtrim($templatesRoot, '/') . '/' . $site->domain;

        if (!is_dir($domainDir)) {
            throw new RuntimeException("Cloned template directory not found: {$domainDir}");
        }

        $files = glob($domainDir . '/*-raw_html.md') ?: [];
        sort($files);

        if ($files === []) {
            throw new RuntimeException("No md templates found in cloned directory: {$domainDir}");
        }

        $pagesCount = 0;

        foreach ($files as $filePath) {
            $result = $this->importService->importFromMdFile(
                $filePath,
                $site->id,
                false // keep SFTP values from create form, do not override from template file
            );

            $pagesCount += (int) ($result['pages_count'] ?? 0);
        }

        return [
            'files_count' => count($files),
            'pages_count' => $pagesCount,
        ];
    }

}

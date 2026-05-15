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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
        // TODO(PROD): remove temporary site-create deep debug tracing (debug_id, detailed step logs, debug headers/body fields).
        $debugId = $this->resolveSiteCreateDebugId($request);
        $requestStartedAt = microtime(true);
        $userId = (int) (auth()->id() ?? 0);

        Log::withContext([
            'site_create_debug_id' => $debugId,
            'site_create_user_id' => $userId,
        ]);

        Log::info('site.create.request_received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_keys' => array_keys($request->all()),
        ]);

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
            'ai_field_edits' => 'nullable|array',
            'ai_field_edits.*.file' => 'required_with:ai_field_edits|string|max:255',
            'ai_field_edits.*.path' => 'required_with:ai_field_edits|string|max:1000',
            'ai_field_edits.*.value' => 'present|string|max:100000',
        ]);

        if ($validator->fails()) {
            Log::warning('site.create.validation_failed', [
                'errors' => $validator->errors()->toArray(),
                'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
            ]);

            return response()
                ->json([
                    'errors' => $validator->errors(),
                    'debug_id' => $debugId,
                ], 422)
                ->header('X-Site-Create-Debug-Id', $debugId);
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
        $aiFieldEdits = $data['ai_field_edits'] ?? [];

        Log::info('site.create.validated', [
            'name' => $data['name'] ?? null,
            'domain' => $data['domain'] ?? null,
            'template_set' => $data['template_set'] ?? null,
            'output_path' => $data['output_path'] ?? null,
            'ai_clone_templates' => $aiCloneTemplates,
            'ai_source_domain' => $aiSourceDomain,
            'ai_prompt_count' => is_array($aiFieldPrompts) ? count($aiFieldPrompts) : 0,
            'ai_edit_count' => is_array($aiFieldEdits) ? count($aiFieldEdits) : 0,
            'ai_prompt_summary' => $this->summarizeFieldEntries(
                is_array($aiFieldPrompts) ? $aiFieldPrompts : [],
                'prompt'
            ),
            'ai_edit_summary' => $this->summarizeFieldEntries(
                is_array($aiFieldEdits) ? $aiFieldEdits : [],
                'value'
            ),
        ]);

        unset($data['ai_clone_templates'], $data['ai_source_domain'], $data['ai_field_prompts'], $data['ai_field_edits']);

        $site = $this->sites->create($data);
        Log::info('site.create.site_row_created', [
            'site_id' => $site->id,
            'domain' => $site->domain,
        ]);

        $aiGeneration = [
            'enabled' => $aiCloneTemplates,
            'updated_fields' => 0,
            'updated_files' => 0,
            'updated_paths' => [],
            'manual_updated_fields' => 0,
            'manual_updated_files' => 0,
            'manual_updated_paths' => [],
        ];

        $aiPipelineStartedAt = microtime(true);
        try {
            if ($aiCloneTemplates) {
                Log::info('site.create.ai_pipeline.start', [
                    'site_id' => $site->id,
                    'source_domain' => $aiSourceDomain !== '' ? $aiSourceDomain : 'test.com',
                ]);

                $aiGeneration = $this->processAiTemplateGeneration(
                    site: $site,
                    userId: $userId,
                    sourceDomain: $aiSourceDomain !== '' ? $aiSourceDomain : 'test.com',
                    prompts: is_array($aiFieldPrompts) ? $aiFieldPrompts : [],
                    fieldEdits: is_array($aiFieldEdits) ? $aiFieldEdits : [],
                    debugId: $debugId
                );

                Log::info('site.create.ai_pipeline.completed', [
                    'site_id' => $site->id,
                    'duration_ms' => $this->elapsedMilliseconds($aiPipelineStartedAt),
                    'updated_fields' => $aiGeneration['updated_fields'] ?? 0,
                    'updated_files' => $aiGeneration['updated_files'] ?? 0,
                    'manual_updated_fields' => $aiGeneration['manual_updated_fields'] ?? 0,
                    'manual_updated_files' => $aiGeneration['manual_updated_files'] ?? 0,
                ]);
            } else {
                Log::info('site.create.ai_pipeline.skipped', [
                    'site_id' => $site->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('site.create.ai_pipeline.failed', [
                'site_id' => $site->id,
                'duration_ms' => $this->elapsedMilliseconds($aiPipelineStartedAt),
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $templatesDeleted = false;
            try {
                $templatesRoot = (string) config(
                    'services.ai_agent.templates_root',
                    storage_path('import-deploy/md/test/raw_html')
                );
                $targetDir = rtrim($templatesRoot, '/') . '/' . $site->domain;
                if (is_dir($targetDir)) {
                    $templatesDeleted = File::deleteDirectory($targetDir);
                }
            } catch (\Throwable $cleanupError) {
                Log::error('site.create.rollback.templates_cleanup_failed', [
                    'site_id' => $site->id,
                    'exception_class' => $cleanupError::class,
                    'message' => $cleanupError->getMessage(),
                ]);
            }

            $siteDeleted = false;
            try {
                $siteDeleted = $this->sites->delete($site);
            } catch (\Throwable $deleteError) {
                Log::error('site.create.rollback.site_delete_failed', [
                    'site_id' => $site->id,
                    'exception_class' => $deleteError::class,
                    'message' => $deleteError->getMessage(),
                ]);
            }

            Log::warning('site.create.rollback.completed', [
                'site_id' => $site->id,
                'templates_deleted' => $templatesDeleted,
                'site_deleted' => $siteDeleted,
                'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
            ]);

            return response()
                ->json([
                    'error' => 'AI template generation failed',
                    'message' => $e->getMessage(),
                    'debug_id' => $debugId,
                ], 422)
                ->header('X-Site-Create-Debug-Id', $debugId);
        }

        $this->audit->log('site.created', Site::class, $site->id, null, $site->toArray());

        $payload = $site->toArray();
        $payload['ai_generation'] = $aiGeneration;
        $payload['debug_id'] = $debugId;

        Log::info('site.create.completed', [
            'site_id' => $site->id,
            'domain' => $site->domain,
            'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
        ]);

        return response()
            ->json($payload, 201)
            ->header('X-Site-Create-Debug-Id', $debugId);
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
        array $prompts,
        array $fieldEdits,
        ?string $debugId = null
    ): array {
        if ($userId <= 0) {
            throw new RuntimeException('Authenticated user is required for AI template generation.');
        }

        $stepStartedAt = microtime(true);
        Log::info('site.create.ai.clone.start', [
            'site_id' => $site->id,
            'source_domain' => $sourceDomain,
            'target_domain' => $site->domain,
            'debug_id' => $debugId,
        ]);
        $this->aiAgentService->cloneDomainTemplates($sourceDomain, $site->domain);
        Log::info('site.create.ai.clone.completed', [
            'site_id' => $site->id,
            'duration_ms' => $this->elapsedMilliseconds($stepStartedAt),
        ]);

        $this->audit->log('ai.templates.cloned', Site::class, $site->id, null, [
            'source_domain' => $sourceDomain,
            'target_domain' => $site->domain,
        ]);

        $stepStartedAt = microtime(true);
        Log::info('site.create.ai.manual_edits.start', [
            'site_id' => $site->id,
            'rows' => count($fieldEdits),
        ]);
        $manualResult = $this->aiAgentService->applyFieldEditsToDomain($site->domain, $fieldEdits);
        $manualUpdatedPaths = [];
        foreach (($manualResult['details'] ?? []) as $detail) {
            if (!is_array($detail)) {
                continue;
            }

            foreach (($detail['updated_paths'] ?? []) as $path) {
                if (is_string($path) && $path !== '') {
                    $manualUpdatedPaths[] = $path;
                }
            }
        }
        $manualUpdatedPaths = array_values(array_unique($manualUpdatedPaths));
        Log::info('site.create.ai.manual_edits.completed', [
            'site_id' => $site->id,
            'duration_ms' => $this->elapsedMilliseconds($stepStartedAt),
            'updated_fields' => $manualResult['updated_fields'] ?? 0,
            'updated_files' => $manualResult['updated_files'] ?? 0,
        ]);

        if (($manualResult['updated_fields'] ?? 0) > 0) {
            $this->audit->log('ai.templates.manual_updated', Site::class, $site->id, null, [
                'updated_fields' => $manualResult['updated_fields'],
                'updated_files' => $manualResult['updated_files'],
                'updated_paths' => $manualUpdatedPaths,
            ]);
        }

        if ($prompts === []) {
            Log::info('site.create.ai.prompts.skipped', [
                'site_id' => $site->id,
            ]);

            $stepStartedAt = microtime(true);
            Log::info('site.create.ai.import.start', [
                'site_id' => $site->id,
            ]);
            $importStats = $this->importClonedTemplatesIntoSite($site);
            Log::info('site.create.ai.import.completed', [
                'site_id' => $site->id,
                'duration_ms' => $this->elapsedMilliseconds($stepStartedAt),
                'files_count' => $importStats['files_count'] ?? 0,
                'pages_count' => $importStats['pages_count'] ?? 0,
            ]);
            $this->audit->log('ai.templates.imported', Site::class, $site->id, null, $importStats);
            return [
                'enabled' => true,
                'updated_fields' => 0,
                'updated_files' => 0,
                'updated_paths' => [],
                'manual_updated_fields' => (int) ($manualResult['updated_fields'] ?? 0),
                'manual_updated_files' => (int) ($manualResult['updated_files'] ?? 0),
                'manual_updated_paths' => $manualUpdatedPaths,
            ];
        }

        $stepStartedAt = microtime(true);
        Log::info('site.create.ai.prompts.start', [
            'site_id' => $site->id,
            'rows' => count($prompts),
            'summary' => $this->summarizeFieldEntries($prompts, 'prompt'),
        ]);

        $config = $this->aiConfigs->findForUser($userId);
        Log::info('site.create.ai.config.loaded', [
            'site_id' => $site->id,
            'provider' => $config?->provider,
            'model_name' => $config?->model_name,
            'is_active' => (bool) ($config?->is_active ?? false),
            'has_api_key' => trim((string) ($config?->api_key ?? '')) !== '',
        ]);

        $result = $this->aiAgentService->applyPromptsToDomain(
            targetDomain: $site->domain,
            fieldPrompts: $prompts,
            config: $config,
            // During initial site creation, the site is new and cannot be pre-listed
            // in allowed_sites ahead of time. Path access rules still apply.
            siteId: null
        );
        Log::info('site.create.ai.prompts.completed', [
            'site_id' => $site->id,
            'duration_ms' => $this->elapsedMilliseconds($stepStartedAt),
            'updated_fields' => $result['updated_fields'] ?? 0,
            'updated_files' => $result['updated_files'] ?? 0,
        ]);

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

        $stepStartedAt = microtime(true);
        Log::info('site.create.ai.import.start', [
            'site_id' => $site->id,
        ]);
        $importStats = $this->importClonedTemplatesIntoSite($site);
        Log::info('site.create.ai.import.completed', [
            'site_id' => $site->id,
            'duration_ms' => $this->elapsedMilliseconds($stepStartedAt),
            'files_count' => $importStats['files_count'] ?? 0,
            'pages_count' => $importStats['pages_count'] ?? 0,
        ]);
        $this->audit->log('ai.templates.imported', Site::class, $site->id, null, $importStats);

        return [
            'enabled' => true,
            'updated_fields' => (int) ($result['updated_fields'] ?? 0),
            'updated_files' => (int) ($result['updated_files'] ?? 0),
            'updated_paths' => $updatedPaths,
            'manual_updated_fields' => (int) ($manualResult['updated_fields'] ?? 0),
            'manual_updated_files' => (int) ($manualResult['updated_files'] ?? 0),
            'manual_updated_paths' => $manualUpdatedPaths,
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

    private function resolveSiteCreateDebugId(Request $request): string
    {
        $headerId = trim((string) $request->header('X-Site-Create-Debug-Id', ''));
        if ($headerId !== '') {
            return Str::limit($headerId, 80, '');
        }

        $bodyId = trim((string) $request->input('debug_request_id', ''));
        if ($bodyId !== '') {
            return Str::limit($bodyId, 80, '');
        }

        return 'site-create-' . Str::uuid();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function summarizeFieldEntries(array $items, string $textField): array
    {
        $summary = [];

        foreach ($items as $index => $item) {
            if ($index >= 25) {
                $summary[] = ['truncated' => true, 'remaining' => count($items) - 25];
                break;
            }

            $file = (string) ($item['file'] ?? '');
            $path = (string) ($item['path'] ?? '');
            $text = (string) ($item[$textField] ?? '');

            $summary[] = [
                'file' => $file,
                'path' => $path,
                'text_len' => mb_strlen($text),
                'text_preview' => mb_substr(preg_replace('/\s+/', ' ', trim($text)) ?? '', 0, 180),
            ];
        }

        return $summary;
    }

    private function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

}

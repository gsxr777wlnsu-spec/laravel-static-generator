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
use App\Models\SiteSharedBlock;
use App\Services\AiAgentService;
use App\Services\ImportService;
use App\Services\LanguageService;
use App\Support\SiteLayoutContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        private ImportService $importService,
        private SiteLayoutContent $layoutContent,
        private LanguageService $languageService
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

        $input = $this->normalizeAiFieldEditValues($request->all());

        $validator = Validator::make($input, [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:sites,domain',
            'template_set' => 'required|string|max:100',
            'output_path' => 'required|string|max:500',
            'status' => 'nullable|in:active,inactive,draft',
            'locale' => 'nullable|string|max:10',
            'default_locale' => 'nullable|string|max:10',
            'alternate_locales' => 'nullable|array',
            'alternate_locales.*' => 'nullable|string|max:10',
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
            'ai_field_prompts.*.send_current_value' => 'nullable|boolean',
            'ai_field_prompts.*.model_key' => 'nullable|string|max:100',
            'ai_field_prompts.*.context_mode' => 'nullable|in:none,previous,next,adjacent,all,selected',
            'ai_field_prompts.*.context_section_paths' => 'nullable|array',
            'ai_field_prompts.*.context_section_paths.*' => 'nullable|string|max:1000',
            'ai_field_edits' => 'nullable|array',
            'ai_field_edits.*.file' => 'required_with:ai_field_edits|string|max:255',
            'ai_field_edits.*.path' => 'required_with:ai_field_edits|string|max:1000',
            'ai_field_edits.*.value' => 'present|string|max:100000',
            'ai_block_operations' => 'nullable|array',
            'ai_block_operations.*.file' => 'required_with:ai_block_operations|string|max:255',
            'ai_block_operations.*.section_path' => 'required_with:ai_block_operations|string|max:1000',
            'ai_block_operations.*.action' => 'required_with:ai_block_operations|string|max:100',
            'ai_block_operations.*.queue_id' => 'nullable|string|max:64',
            'ai_block_operations.*.target_key' => 'nullable|string|max:64',
            'ai_block_operations.*.tag' => 'nullable|string|max:10',
            'ai_block_operations.*.value' => 'nullable|string|max:100000',
            'ai_block_operations.*.value_prompt' => 'nullable|string|max:10000',
            'ai_block_operations.*.class' => 'nullable|string|max:255',
            'ai_block_operations.*.list_tag' => 'nullable|string|max:10',
            'ai_block_operations.*.items' => 'nullable|array',
            'ai_block_operations.*.items.*' => 'nullable|string|max:10000',
            'ai_block_operations.*.item_prompts' => 'nullable|array',
            'ai_block_operations.*.item_prompts.*' => 'nullable|string|max:10000',
            'ai_block_operations.*.item_class' => 'nullable|string|max:255',
            'ai_block_operations.*.aria_label' => 'nullable|string|max:255',
            'ai_block_operations.*.headers' => 'nullable|array',
            'ai_block_operations.*.headers.*' => 'nullable|string|max:10000',
            'ai_block_operations.*.header_prompts' => 'nullable|array',
            'ai_block_operations.*.header_prompts.*' => 'nullable|string|max:10000',
            'ai_block_operations.*.rows' => 'nullable|array',
            'ai_block_operations.*.rows.*' => 'nullable|array',
            'ai_block_operations.*.rows.*.*' => 'nullable|string|max:10000',
            'ai_block_operations.*.row_prompts' => 'nullable|array',
            'ai_block_operations.*.row_prompts.*' => 'nullable|array',
            'ai_block_operations.*.row_prompts.*.*' => 'nullable|string|max:10000',
            'ai_block_operations.*.container_key' => 'nullable|string|max:64',
            'ai_block_operations.*.text' => 'nullable|string|max:100000',
            'ai_block_operations.*.text_prompt' => 'nullable|string|max:10000',
            'ai_block_operations.*.icon_src' => 'nullable|string|max:1000',
            'ai_block_operations.*.icon_alt' => 'nullable|string|max:1000',
            'ai_block_operations.*.icon_class' => 'nullable|string|max:255',
            'ai_block_operations.*.text_class' => 'nullable|string|max:255',
            'ai_block_operations.*.col1' => 'nullable|string|max:100000',
            'ai_block_operations.*.col2' => 'nullable|string|max:100000',
            'ai_block_operations.*.row_class' => 'nullable|string|max:255',
            'ai_block_operations.*.cell_class' => 'nullable|string|max:255',
            'ai_block_operations.*.col1_prompt' => 'nullable|string|max:10000',
            'ai_block_operations.*.col2_prompt' => 'nullable|string|max:10000',
            'ai_block_operations.*.anchor_key' => 'nullable|string|max:64',
            'ai_block_operations.*.anchor_position' => 'nullable|in:before,after',
            'ai_block_operations.*.module' => 'nullable|string|max:255',
            'ai_image_replacements' => 'nullable|array',
            'ai_image_replacements.*.file' => 'required_with:ai_image_replacements|string|max:255',
            'ai_image_replacements.*.path' => 'required_with:ai_image_replacements|string|max:1000',
            'ai_image_replacements.*.src' => 'required_with:ai_image_replacements|string|max:1000',
            'ai_image_replacements.*.filename' => 'required_with:ai_image_replacements|string|max:255',
            'ai_image_replacements.*.data_url' => 'required_with:ai_image_replacements|string|max:15000000',
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
        $aiBlockOperations = $data['ai_block_operations'] ?? [];
        $aiImageReplacements = $data['ai_image_replacements'] ?? [];

        Log::info('site.create.validated', [
            'name' => $data['name'] ?? null,
            'domain' => $data['domain'] ?? null,
            'template_set' => $data['template_set'] ?? null,
            'output_path' => $data['output_path'] ?? null,
            'ai_clone_templates' => $aiCloneTemplates,
            'ai_source_domain' => $aiSourceDomain,
            'ai_prompt_count' => is_array($aiFieldPrompts) ? count($aiFieldPrompts) : 0,
            'ai_edit_count' => is_array($aiFieldEdits) ? count($aiFieldEdits) : 0,
            'ai_block_operation_count' => is_array($aiBlockOperations) ? count($aiBlockOperations) : 0,
            'ai_image_replacement_count' => is_array($aiImageReplacements) ? count($aiImageReplacements) : 0,
            'ai_prompt_summary' => $this->summarizeFieldEntries(
                is_array($aiFieldPrompts) ? $aiFieldPrompts : [],
                'prompt'
            ),
            'ai_edit_summary' => $this->summarizeFieldEntries(
                is_array($aiFieldEdits) ? $aiFieldEdits : [],
                'value'
            ),
            'ai_block_operation_summary' => $this->summarizeBlockOperations(
                is_array($aiBlockOperations) ? $aiBlockOperations : []
            ),
        ]);

        unset(
            $data['ai_clone_templates'],
            $data['ai_source_domain'],
            $data['ai_field_prompts'],
            $data['ai_field_edits'],
            $data['ai_block_operations'],
            $data['ai_image_replacements']
        );

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
            'block_updated_fields' => 0,
            'block_updated_files' => 0,
            'block_updated_paths' => [],
            'received_block_operations' => $this->summarizeBlockOperations(
                is_array($aiBlockOperations) ? $aiBlockOperations : []
            ),
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
                    blockOperations: is_array($aiBlockOperations) ? $aiBlockOperations : [],
                    imageReplacements: is_array($aiImageReplacements) ? $aiImageReplacements : [],
                    debugId: $debugId
                );
                $aiGeneration['received_block_operations'] = $this->summarizeBlockOperations(
                    is_array($aiBlockOperations) ? $aiBlockOperations : []
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

        $this->languageService->prepareSiteLanguages($site, is_array($data['alternate_locales'] ?? null) ? $data['alternate_locales'] : []);
        $site = $site->fresh() ?? $site;

        $this->audit->log('site.created', Site::class, $site->id, null, $site->toArray());

        $payload = $site->toArray();
        $payload['ai_generation'] = $aiGeneration;
        $payload['debug_id'] = $debugId;
        $payload['create_report'] = $this->storeSiteCreateReport($site, $aiGeneration, $debugId);

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
            'alternate_locales' => 'nullable|array',
            'alternate_locales.*' => 'nullable|string|max:10',
            'menu_html' => 'nullable|string',
            'mobile_menu_html' => 'nullable|string',
            'footer_html' => 'nullable|string',
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

        if (array_key_exists('menu_html', $data)) {
            $data['menu_html'] = $this->layoutContent->normalizeMenuInner($data['menu_html']);
        }

        if (array_key_exists('mobile_menu_html', $data)) {
            $data['mobile_menu_html'] = $this->layoutContent->normalizeMobileMenuHtml($data['mobile_menu_html']);
        }

        if (array_key_exists('footer_html', $data)) {
            $data['footer_html'] = $this->layoutContent->normalizeFooterInner($data['footer_html']);
        }
        
        if (!empty($data['sftp_password'])) {
            $data['sftp_password'] = encrypt($data['sftp_password']);
        }
        
        if (!empty($data['sftp_private_key'])) {
            $data['sftp_private_key'] = encrypt($data['sftp_private_key']);
        }

        $site = $this->sites->update($site, $data);
        if (array_key_exists('locale', $data) || array_key_exists('default_locale', $data) || array_key_exists('alternate_locales', $data)) {
            $this->languageService->prepareSiteLanguages($site, is_array($data['alternate_locales'] ?? null) ? $data['alternate_locales'] : []);
            $site = $site->fresh() ?? $site;
        }
        
        $this->audit->log('site.updated', Site::class, $site->id, $oldValues, $site->toArray());

        return response()->json($site);
    }

    public function updateSharedBlock(Request $request, int $id, string $locale): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $normalizedLocale = $this->languageService->normalizeLocale($locale);
        if ($normalizedLocale === '') {
            return response()->json(['error' => 'Invalid locale'], 422);
        }

        $validator = Validator::make($request->all(), [
            'menu_html' => 'nullable|string',
            'mobile_menu_html' => 'nullable|string',
            'footer_html' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $updates = [];

        if (array_key_exists('menu_html', $data)) {
            $updates['menu_html'] = $this->layoutContent->normalizeMenuInner($data['menu_html']);
        }

        if (array_key_exists('mobile_menu_html', $data)) {
            $updates['mobile_menu_html'] = $this->layoutContent->normalizeMobileMenuHtml($data['mobile_menu_html']);
        }

        if (array_key_exists('footer_html', $data)) {
            $updates['footer_html'] = $this->layoutContent->normalizeFooterInner($data['footer_html']);
        }

        $block = SiteSharedBlock::updateOrCreate(
            ['site_id' => $site->id, 'locale' => $normalizedLocale],
            $updates
        );

        return response()->json($block);
    }

    public function addLanguage(Request $request, int $id): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $site = $this->languageService->addSiteLanguage($site, (string) $validator->validated()['locale']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($site);
    }

    public function removeLanguage(int $id, string $locale): JsonResponse
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        try {
            $site = $this->languageService->removeSiteLanguage($site, $locale);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

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

        $cleanupIssues = $this->sites->getLastCleanupIssues();
        if ($cleanupIssues !== []) {
            return response()->json([
                'message' => 'Site deleted successfully, but some local artifacts could not be cleaned up.',
                'cleanup_warnings' => $cleanupIssues,
            ]);
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
        array $blockOperations,
        array $imageReplacements = [],
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

        $imageReplacementPaths = $this->storeAiImageReplacements($site, $imageReplacements);
        foreach ($imageReplacementPaths as $replacement) {
            $fieldEdits[] = [
                'file' => $replacement['file'],
                'path' => $replacement['path'],
                'value' => $replacement['src'],
            ];
        }

        $config = $this->aiConfigs->findForUser($userId);
        Log::info('site.create.ai.config.loaded', [
            'site_id' => $site->id,
            'provider' => $config?->provider,
            'model_name' => $config?->model_name,
            'is_active' => (bool) ($config?->is_active ?? false),
            'has_api_key' => trim((string) ($config?->api_key ?? '')) !== '',
        ]);

        $stepStartedAt = microtime(true);
        Log::info('site.create.ai.block_ops.start', [
            'site_id' => $site->id,
            'rows' => count($blockOperations),
        ]);
        $blockResult = $this->aiAgentService->applyBlockOperationsToDomain($site->domain, $blockOperations, $config);
        $blockUpdatedPaths = [];
        foreach (($blockResult['details'] ?? []) as $detail) {
            if (!is_array($detail)) {
                continue;
            }

            foreach (($detail['updated_paths'] ?? []) as $path) {
                if (is_string($path) && $path !== '') {
                    $blockUpdatedPaths[] = $path;
                }
            }
        }
        $blockUpdatedPaths = array_values(array_unique($blockUpdatedPaths));
        Log::info('site.create.ai.block_ops.completed', [
            'site_id' => $site->id,
            'duration_ms' => $this->elapsedMilliseconds($stepStartedAt),
            'updated_fields' => $blockResult['updated_fields'] ?? 0,
            'updated_files' => $blockResult['updated_files'] ?? 0,
        ]);

        if (($blockResult['updated_fields'] ?? 0) > 0) {
            $this->audit->log('ai.templates.blocks_updated', Site::class, $site->id, null, [
                'updated_fields' => $blockResult['updated_fields'],
                'updated_files' => $blockResult['updated_files'],
                'updated_paths' => $blockUpdatedPaths,
            ]);
        }

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
                'block_updated_fields' => (int) ($blockResult['updated_fields'] ?? 0),
                'block_updated_files' => (int) ($blockResult['updated_files'] ?? 0),
                'block_updated_paths' => $blockUpdatedPaths,
            ];
        }

        $stepStartedAt = microtime(true);
        Log::info('site.create.ai.prompts.start', [
            'site_id' => $site->id,
            'rows' => count($prompts),
            'summary' => $this->summarizeFieldEntries($prompts, 'prompt'),
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
            'block_updated_fields' => (int) ($blockResult['updated_fields'] ?? 0),
            'block_updated_files' => (int) ($blockResult['updated_files'] ?? 0),
            'block_updated_paths' => $blockUpdatedPaths,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $imageReplacements
     * @return array<int, array{file:string,path:string,src:string,stored_path:string}>
     */
    private function storeAiImageReplacements(Site $site, array $imageReplacements): array
    {
        if ($imageReplacements === []) {
            return [];
        }

        $templatesRoot = (string) config(
            'services.ai_agent.templates_root',
            storage_path('import-deploy/md/test/raw_html')
        );
        $domainDir = rtrim($templatesRoot, '/') . '/' . $site->domain;
        if (!is_dir($domainDir)) {
            throw new RuntimeException("Cloned template directory not found: {$domainDir}");
        }

        $stored = [];
        foreach ($imageReplacements as $replacement) {
            if (!is_array($replacement)) {
                continue;
            }

            $file = basename((string) ($replacement['file'] ?? ''));
            $path = trim((string) ($replacement['path'] ?? ''));
            $src = $this->normalizeTemplateAssetPath((string) ($replacement['src'] ?? ''));
            $dataUrl = (string) ($replacement['data_url'] ?? '');

            if ($file === '' || $path === '' || $src === '') {
                continue;
            }

            if (!preg_match('/^data:(image\/[A-Za-z0-9.+-]+);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
                continue;
            }

            $binary = base64_decode($matches[2], true);
            if ($binary === false || $binary === '') {
                continue;
            }

            $targetPath = $domainDir . '/' . $src;
            File::ensureDirectoryExists(dirname($targetPath));
            File::put($targetPath, $binary);

            $stored[] = [
                'file' => $file,
                'path' => $path,
                'src' => str_starts_with((string) ($replacement['src'] ?? ''), '/') ? '/' . $src : $src,
                'stored_path' => $targetPath,
            ];
        }

        return $stored;
    }

    private function normalizeTemplateAssetPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? '';

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '~')) {
            return '';
        }

        return $path;
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

        $this->copyClonedTemplateAssetsToGeneratedSite($domainDir, $site->id);

        return [
            'files_count' => count($files),
            'pages_count' => $pagesCount,
        ];
    }

    private function copyClonedTemplateAssetsToGeneratedSite(string $domainDir, int $siteId): void
    {
        $sourceAssetsDir = rtrim($domainDir, '/') . '/assets';
        if (!is_dir($sourceAssetsDir)) {
            return;
        }

        $targetAssetsPath = "site{$siteId}/assets";
        Storage::disk('generated')->makeDirectory($targetAssetsPath);

        foreach (File::allFiles($sourceAssetsDir) as $file) {
            $relativePath = ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');
            Storage::disk('generated')->put(
                "{$targetAssetsPath}/{$relativePath}",
                File::get($file->getPathname())
            );
        }
    }

    /**
     * @param  array<string, mixed>  $aiGeneration
     * @return array{text:string,stored_path:string,view_url:string}
     */
    private function storeSiteCreateReport(Site $site, array $aiGeneration, string $debugId): array
    {
        $reportText = $this->buildSiteCreateReportText($site, $aiGeneration, $debugId);
        $reportDirectory = $this->siteCreateReportDirectory($site);

        File::ensureDirectoryExists($reportDirectory);

        $reportPath = $reportDirectory . '/site-create-report.txt';
        File::put($reportPath, $reportText);

        return [
            'text' => $reportText,
            'stored_path' => $reportPath,
            'view_url' => route('admin.sites.creation-log', ['id' => $site->id]),
        ];
    }

    private function normalizeAiFieldEditValues(array $input): array
    {
        if (!isset($input['ai_field_edits']) || !is_array($input['ai_field_edits'])) {
            return $input;
        }

        foreach ($input['ai_field_edits'] as $index => $edit) {
            if (!is_array($edit) || !array_key_exists('value', $edit) || $edit['value'] !== null) {
                continue;
            }

            $input['ai_field_edits'][$index]['value'] = '';
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $aiGeneration
     */
    private function buildSiteCreateReportText(Site $site, array $aiGeneration, string $debugId): string
    {
        $updatedPaths = array_values(array_filter((array) ($aiGeneration['updated_paths'] ?? []), fn ($path) => is_string($path) && $path !== ''));
        $manualUpdatedPaths = array_values(array_filter((array) ($aiGeneration['manual_updated_paths'] ?? []), fn ($path) => is_string($path) && $path !== ''));
        $blockUpdatedPaths = array_values(array_filter((array) ($aiGeneration['block_updated_paths'] ?? []), fn ($path) => is_string($path) && $path !== ''));
        $receivedBlockOperations = array_values(array_filter((array) ($aiGeneration['received_block_operations'] ?? []), fn ($line) => is_string($line) && $line !== ''));

        $lines = [
            'Site created successfully.',
            '',
            'Site ID: ' . $site->id,
            'Name: ' . $site->name,
            'Domain: ' . $site->domain,
            'Template set: ' . $site->template_set,
            'Output path: ' . $site->output_path,
            'Status: ' . $site->status,
            'Locale: ' . $site->locale,
            'AI generation enabled: ' . (($aiGeneration['enabled'] ?? false) ? 'yes' : 'no'),
            'AI updated fields: ' . (int) ($aiGeneration['updated_fields'] ?? 0),
            'Manual updated fields: ' . (int) ($aiGeneration['manual_updated_fields'] ?? 0),
            'Block updated fields: ' . (int) ($aiGeneration['block_updated_fields'] ?? 0),
            'Debug ID: ' . $debugId,
        ];

        if ($blockUpdatedPaths !== []) {
            $lines[] = '';
            $lines[] = 'Block operations:';
            foreach ($blockUpdatedPaths as $path) {
                $lines[] = '- ' . $path;
            }
        }

        if ($receivedBlockOperations !== []) {
            $lines[] = '';
            $lines[] = 'Received block operations:';
            foreach ($receivedBlockOperations as $line) {
                $lines[] = '- ' . $line;
            }
        }

        if ($manualUpdatedPaths !== []) {
            $lines[] = '';
            $lines[] = 'Manual field updates:';
            foreach ($manualUpdatedPaths as $path) {
                $lines[] = '- ' . $path;
            }
        }

        if ($updatedPaths !== []) {
            $lines[] = '';
            $lines[] = 'AI updated fields:';
            foreach ($updatedPaths as $path) {
                $lines[] = '- ' . $path;
            }
        } elseif (($aiGeneration['enabled'] ?? false) === true && $manualUpdatedPaths === []) {
            $lines[] = '';
            $lines[] = 'AI completed without field rewrites.';
        }

        return implode("\n", $lines) . "\n";
    }

    private function siteCreateReportDirectory(Site $site): string
    {
        $templatesRoot = (string) config(
            'services.ai_agent.templates_root',
            storage_path('import-deploy/md/test/raw_html')
        );

        return rtrim($templatesRoot, '/') . '/' . $site->domain;
    }

    /**
     * @param  array<int, array<string, mixed>>  $operations
     * @return array<int, string>
     */
    private function summarizeBlockOperations(array $operations): array
    {
        $lines = [];

        foreach (array_values($operations) as $index => $operation) {
            if (!is_array($operation)) {
                continue;
            }

            $action = trim((string) ($operation['action'] ?? ''));
            $sectionPath = trim((string) ($operation['section_path'] ?? ''));
            $anchorKey = trim((string) ($operation['anchor_key'] ?? ''));
            $valuePreview = '';

            if (isset($operation['value']) && is_string($operation['value']) && trim($operation['value']) !== '') {
                $valuePreview = Str::limit(trim(preg_replace('/\s+/u', ' ', $operation['value']) ?? ''), 50, '...');
            } elseif (($operation['action'] ?? null) === 'add_table_block') {
                $headers = is_array($operation['headers'] ?? null) ? count($operation['headers']) : 0;
                $rows = is_array($operation['rows'] ?? null) ? count($operation['rows']) : 0;
                $valuePreview = "headers={$headers}, rows={$rows}";
            }

            $summary = '#' . ($index + 1)
                . ' ' . ($action !== '' ? $action : 'unknown')
                . ($sectionPath !== '' ? " @ {$sectionPath}" : '')
                . ($anchorKey !== '' ? " anchor={$anchorKey}" : '');

            if ($valuePreview !== '') {
                $summary .= " value={$valuePreview}";
            }

            $lines[] = $summary;
        }

        return $lines;
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

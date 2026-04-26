<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\DeployServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Contracts\SftpClientInterface;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites,
        private HtmlGeneratorInterface $generator,
        private DeployServiceInterface $deploy,
        private SftpClientInterface $sftp,
        private AuditLogServiceInterface $audit
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

        $site = $this->sites->create($data);
        
        $this->audit->log('site.created', Site::class, $site->id, null, $site->toArray());

        return response()->json($site, 201);
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
        
        $this->sites->delete($site);

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

        $deployment = $this->deploy->deploy($site);
        
        $this->audit->log('site.deployed', Site::class, $site->id);

        return response()->json($deployment);
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
}

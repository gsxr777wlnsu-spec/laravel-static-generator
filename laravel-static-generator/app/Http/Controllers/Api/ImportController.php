<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeployServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\SftpClientInterface;
use App\Http\Controllers\Controller;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private DeployServiceInterface $deployService,
        private HtmlGeneratorInterface $htmlGenerator,
        private SftpClientInterface $sftp
    ) {}

    public function import(Request $request): JsonResponse
    {
        try {
            [$site, $pagesCount] = $this->importUploadedFile($request);

            return response()->json([
                'success' => true,
                'message' => "Imported {$pagesCount} page(s) for site '{$site->domain}'",
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'domain' => $site->domain,
                ],
                'pages_count' => $pagesCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function importAndDeploy(Request $request): JsonResponse
    {
        try {
            [$site, $pagesCount] = $this->importUploadedFile($request);

            $generation = $this->htmlGenerator->generateSite($site);
            if (($generation['success'] ?? false) !== true) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation failed',
                    'generation_errors' => $generation['errors'] ?? [],
                ], 422);
            }

            $sftpConnected = $this->sftp->testConnection($site);
            if (!$sftpConnected) {
                return response()->json([
                    'success' => false,
                    'error' => 'SFTP connection failed',
                    'message' => 'Could not connect to SFTP server. Please check SFTP settings.',
                ], 400);
            }

            $deployment = $this->deployService->deploy($site, true);
            if ($deployment->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'error' => $deployment->error_message ?: 'Deployment failed',
                    'deployment' => [
                        'id' => $deployment->id,
                        'status' => $deployment->status,
                        'sftp_host' => $deployment->sftp_host,
                        'remote_path' => $deployment->remote_path,
                    ],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$pagesCount} page(s) for site '{$site->domain}'",
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'domain' => $site->domain,
                ],
                'pages_count' => $pagesCount,
                'deployed' => true,
                'deployment' => [
                    'id' => $deployment->id,
                    'status' => $deployment->status,
                    'sftp_host' => $deployment->sftp_host,
                    'remote_path' => $deployment->remote_path,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function templates(): JsonResponse
    {
        $templates = $this->importService->listImportTemplates();

        return response()->json([
            'templates' => $templates,
        ]);
    }

    private function importUploadedFile(Request $request): array
    {
        $request->validate([
            'file' => 'required|file|mimes:md,yaml,yml,txt',
        ]);

        $file = $request->file('file');
        $tempPath = $file->getRealPath();

        $result = $this->importService->importFromMdFile($tempPath);
        $site = $result['site'];
        $pagesCount = (int) ($result['pages_count'] ?? 0);

        return [$site, $pagesCount];
    }
}

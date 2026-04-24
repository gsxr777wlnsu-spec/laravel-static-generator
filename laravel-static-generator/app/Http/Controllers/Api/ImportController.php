<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeployServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Http\Controllers\Controller;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private DeployServiceInterface $deployService,
        private HtmlGeneratorInterface $htmlGenerator
    ) {}

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:md,yaml,yml,txt',
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->getRealPath();

            $result = $this->importService->importFromMdFile($tempPath);
            $site = $result['site'];
            $pagesCount = $result['pages_count'];

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
        $request->validate([
            'file' => 'required|file|mimes:md,yaml,yml,txt',
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->getRealPath();

            $result = $this->importService->importFromMdFile($tempPath);
            $site = $result['site'];
            $pagesCount = $result['pages_count'];

            $this->htmlGenerator->generateSite($site);
            $deployment = $this->deployService->deploy($site);
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
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeployService;
use App\Services\HtmlGeneratorService;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private DeployService $deployService,
        private HtmlGeneratorService $htmlGenerator
    ) {}

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:md,yaml,txt',
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function importAndDeploy(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:md,yaml,txt',
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->getRealPath();

            $result = $this->importService->importFromMdFile($tempPath);
            $site = $result['site'];
            $pagesCount = $result['pages_count'];

            $this->htmlGenerator->generateSite($site);

            $freshDeployService = new \App\Services\DeployService(
                $this->htmlGenerator,
                app(\App\Contracts\DeploymentRepositoryInterface::class)
            );
            $deployment = $freshDeployService->deploy($site);

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
        } catch (\Exception $e) {
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
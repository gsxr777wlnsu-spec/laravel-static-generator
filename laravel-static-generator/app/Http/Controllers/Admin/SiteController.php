<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Services\AiAgentService;
use App\Services\PageTemplatePresetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SiteController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites,
        private AiAgentService $aiAgentService,
        private AiAgentConfigRepositoryInterface $aiConfigs,
        private PageTemplatePresetService $templatePresets
    ) {}

    public function index()
    {
        $sites = $this->sites->getAll();
        return view('admin.sites.index', compact('sites'));
    }

    public function create(Request $request)
    {
        $sourceDomain = 'test.com';
        $templateFieldCatalog = [];
        $templateFileOptions = [];
        $selectedTemplateFile = basename((string) $request->query('template_file', 'index-raw_html.md'));

        try {
            $templateFileOptions = $this->aiAgentService->listTemplateFileNames($sourceDomain);
            if (!in_array($selectedTemplateFile, $templateFileOptions, true)) {
                $selectedTemplateFile = in_array('index-raw_html.md', $templateFileOptions, true)
                    ? 'index-raw_html.md'
                    : (string) ($templateFileOptions[0] ?? '');
            }
            $templateFieldCatalog = $selectedTemplateFile !== ''
                ? $this->aiAgentService->listTemplateFields($sourceDomain, $selectedTemplateFile)
                : [];
        } catch (\Throwable) {
            $templateFieldCatalog = [];
            $templateFileOptions = [];
        }

        $user = auth()->user();
        $aiConfig = $user ? $this->aiConfigs->findForUser((int) $user->id) : null;

        return view('admin.sites.create', [
            'aiSourceDomain' => $sourceDomain,
            'templateFieldCatalog' => $templateFieldCatalog,
            'templateFileOptions' => $templateFileOptions,
            'selectedTemplateFile' => $selectedTemplateFile,
            'hasActiveAiConfig' => (bool) ($aiConfig?->is_active ?? false) && !empty($aiConfig?->api_key),
            'aiModelOptions' => $this->aiAgentService->modelOptions($aiConfig),
            'moduleCatalog' => $this->templatePresets->listModulesForUi(),
        ]);
    }

    public function edit(int $id)
    {
        $site = $this->sites->findById($id);
        
        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        return view('admin.sites.edit', [
            'site' => $site,
            'creationLogUrl' => route('admin.sites.creation-log', ['id' => $site->id]),
        ]);
    }

    public function creationLog(int $id)
    {
        $site = $this->sites->findById($id);

        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        $reportPath = rtrim((string) config(
            'services.ai_agent.templates_root',
            storage_path('import-deploy/md/test/raw_html')
        ), '/') . '/' . $site->domain . '/site-create-report.txt';

        $reportText = File::exists($reportPath)
            ? (string) File::get($reportPath)
            : 'Site creation report was not found.';

        return view('admin.sites.creation-log', [
            'site' => $site,
            'reportPath' => $reportPath,
            'reportText' => $reportText,
        ]);
    }
}

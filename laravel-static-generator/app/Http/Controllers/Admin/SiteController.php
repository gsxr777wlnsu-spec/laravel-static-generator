<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Services\AiAgentService;

class SiteController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites,
        private AiAgentService $aiAgentService,
        private AiAgentConfigRepositoryInterface $aiConfigs
    ) {}

    public function index()
    {
        $sites = $this->sites->getAll();
        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        $sourceDomain = 'test.com';
        $templateFieldCatalog = [];

        try {
            $templateFieldCatalog = $this->aiAgentService->listTemplateFields($sourceDomain);
        } catch (\Throwable) {
            $templateFieldCatalog = [];
        }

        $user = auth()->user();
        $aiConfig = $user ? $this->aiConfigs->findForUser((int) $user->id) : null;

        return view('admin.sites.create', [
            'aiSourceDomain' => $sourceDomain,
            'templateFieldCatalog' => $templateFieldCatalog,
            'hasActiveAiConfig' => (bool) ($aiConfig?->is_active ?? false) && !empty($aiConfig?->api_key),
        ]);
    }

    public function edit(int $id)
    {
        $site = $this->sites->findById($id);
        
        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        return view('admin.sites.edit', compact('site'));
    }
}

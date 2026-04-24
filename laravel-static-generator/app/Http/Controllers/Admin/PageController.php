<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PageRepositoryInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageTemplatePresetService;

class PageController extends Controller
{
    public function __construct(
        private PageRepositoryInterface $pages,
        private SiteRepositoryInterface $sites,
        private PageTemplatePresetService $templatePresets
    ) {}

    public function index(int $siteId)
    {
        $site = $this->sites->findById($siteId);
        
        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        $pages = Page::where('site_id', $siteId)->with('sections')->get();
        
        return view('admin.pages.index', compact('site', 'pages'));
    }

    public function create(int $siteId)
    {
        $site = $this->sites->findById($siteId);
        
        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        $pageTemplates = $this->templatePresets->listForUi();

        return view('admin.pages.create', compact('site', 'pageTemplates'));
    }

    public function edit(int $siteId, int $id)
    {
        $site = $this->sites->findById($siteId);
        $page = Page::with('sections')
            ->where('site_id', $siteId)
            ->find($id);
        
        if (!$site || !$page) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site or Page not found');
        }

        $pageTemplates = $this->templatePresets->listForUi();
        $moduleCatalog = $this->templatePresets->listModulesForUi();
        $moduleDefaults = $this->templatePresets->getModuleDefaults();

        return view('admin.pages.edit', compact('site', 'page', 'pageTemplates', 'moduleCatalog', 'moduleDefaults'));
    }
}

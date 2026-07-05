<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PageRepositoryInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\LanguageService;
use App\Services\PageTemplatePresetService;
use App\Services\AiAgentService;
use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Support\SiteLayoutContent;

class PageController extends Controller
{
    public function __construct(
        private PageRepositoryInterface $pages,
        private SiteRepositoryInterface $sites,
        private PageTemplatePresetService $templatePresets,
        private SiteLayoutContent $layoutContent,
        private LanguageService $languageService,
        private AiAgentService $aiAgentService,
        private AiAgentConfigRepositoryInterface $aiAgentConfigs
    ) {}

    public function index(int $siteId)
    {
        $site = $this->sites->findById($siteId);
        
        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        $pages = Page::where('site_id', $siteId)->with('sections')->orderBy('locale')->orderBy('slug')->get();
        $locales = $this->languageService->buildLocaleSet((string) ($site->locale ?? 'en'), is_array($site->alternate_locales) ? $site->alternate_locales : []);
        $defaultLocale = $this->languageService->normalizeLocale((string) ($site->locale ?? 'en')) ?: 'en';
        $pagesByLocale = $pages->groupBy(fn (Page $page) => $this->languageService->normalizeLocale((string) ($page->locale ?? $site->locale ?? 'en')) ?: 'en');
        $languageOptions = $this->languageService->languageOptions();
        
        return view('admin.pages.index', compact('site', 'pages', 'pagesByLocale', 'locales', 'defaultLocale', 'languageOptions'));
    }

    public function editShared(int $siteId, string $part, ?string $locale = null)
    {
        $site = $this->sites->findById($siteId);

        if (!$site) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Site not found');
        }

        $part = strtolower(trim($part));
        if (!in_array($part, ['menu', 'mobile-menu', 'footer'], true)) {
            return redirect()->route('admin.pages.index', $siteId)
                ->with('error', 'Shared block not found');
        }

        $locale = $this->languageService->normalizeLocale($locale ?: (string) ($site->locale ?? 'en')) ?: 'en';

        $html = match ($part) {
            'menu' => $this->layoutContent->resolveMenuInner($site, $locale),
            'mobile-menu' => $this->layoutContent->resolveMobileMenuHtml($site, $locale),
            default => $this->layoutContent->resolveFooterInner($site, $locale),
        };

        return view('admin.pages.edit-shared', compact('site', 'part', 'html', 'locale'));
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

        $sanitizedSections = $page->sections
            ->reject(function ($section) {
                $content = is_array($section->content ?? null)
                    ? $section->content
                    : (is_string($section->content ?? null) ? json_decode($section->content, true) ?: [] : []);

                $moduleKey = strtolower(trim((string) ($section->module ?? $content['module'] ?? $content['module_key'] ?? '')));

                return in_array($moduleKey, ['header', 'footer', 'menu', 'mobile-menu'], true);
            })
            ->map(function ($section) {
                $content = is_array($section->content ?? null)
                    ? $section->content
                    : (is_string($section->content ?? null) ? json_decode($section->content, true) ?: [] : []);

                if (isset($content['raw_html']) && is_string($content['raw_html'])) {
                    $content['raw_html'] = $this->layoutContent->sanitizeSectionHtml($content['raw_html']);
                }

                $section->content = $content;
                if (is_string($section->raw_html) && trim($section->raw_html) !== '') {
                    $section->raw_html = $this->layoutContent->sanitizeSectionHtml($section->raw_html);
                }

                return $section;
            })
            ->values();

        $page->setRelation('sections', $sanitizedSections);

        $pageTemplates = $this->templatePresets->listForUi();
        $moduleCatalog = $this->templatePresets->listModulesForUi();
        $moduleDefaults = $this->templatePresets->getModuleDefaults();
        $aiConfig = auth()->user() ? $this->aiAgentConfigs->findForUser((int) auth()->id()) : null;
        $aiModelOptions = $this->aiAgentService->modelOptions($aiConfig);

        return view('admin.pages.edit', compact('site', 'page', 'pageTemplates', 'moduleCatalog', 'moduleDefaults', 'aiModelOptions'));
    }
}

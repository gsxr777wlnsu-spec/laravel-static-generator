<?php

namespace App\Services;

use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\PageRepositoryInterface;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class HtmlGeneratorService implements HtmlGeneratorInterface
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private LanguageService $languageService
    ) {}

    public function generatePage(Page $page): string
    {
        $page->load(['sections' => function ($query) {
            $query->orderBy('order');
        }, 'site']);

        $languageVersions = Page::where('parent_page_id', $page->id)
            ->orWhere('id', $page->parent_page_id)
            ->where('id', '!=', $page->id)
            ->get();

        $data = [
            'page' => $page,
            'site' => $page->site,
            'languageVersions' => $languageVersions,
        ];

        if ($this->isSitemapPage($page)) {
            $data['sitemapLinks'] = $this->buildSitemapLinks($page->site, $page);
        }

        $templatePath = $this->resolvePageTemplatePath($page);

        $html = View::make($templatePath, $data)->render();
        $html = $this->languageService->applyLanguageSwitcherToHtml($html, $page, $page->site);

        $result = $this->normalizeGoogleMapEmbeds($html);

        return $result;
    }

    public function generateSite(Site $site, ?callable $onProgress = null): array
    {
        $pages = $this->pageRepository->getActiveBySite($site)->values();
        $regularPages = $pages->reject(fn (Page $page) => $this->isSitemapPage($page))->values();
        $sitemapPages = $pages->filter(fn (Page $page) => $this->isSitemapPage($page))->values();
        $generatedFiles = [];
        $errors = [];

        $totalSteps = $pages->count() + 2; // +1 sitemap.xml, +1 robots.txt
        $currentStep = 0;

        foreach ($regularPages as $page) {
            try {
                $html = $this->generatePage($page);
                
                $path = "site{$site->id}/{$this->pageFilename($page)}";
                
                Storage::disk('generated')->put($path, $html);
                
                $generatedFiles[] = $path;
            } catch (\Exception $e) {
                $errors[] = [
                    'page_id' => $page->id,
                    'slug' => $page->slug,
                    'error' => $e->getMessage()
                ];
            }
            
            $currentStep++;
            if ($onProgress) {
                $onProgress($currentStep, $totalSteps);
            }
        }


        $sitemapPath = "site{$site->id}/sitemap.xml";
        Storage::disk('generated')->put($sitemapPath, $this->generateSitemap($site));
        $generatedFiles[] = $sitemapPath;
        $currentStep++;
        if ($onProgress) {
            $onProgress($currentStep, $totalSteps);
        }

        foreach ($sitemapPages as $page) {
            try {
                $html = $this->generatePage($page);

                $path = "site{$site->id}/{$this->pageFilename($page)}";

                Storage::disk('generated')->put($path, $html);

                $generatedFiles[] = $path;
            } catch (\Exception $e) {
                $errors[] = [
                    'page_id' => $page->id,
                    'slug' => $page->slug,
                    'error' => $e->getMessage()
                ];
            }

            $currentStep++;
            if ($onProgress) {
                $onProgress($currentStep, $totalSteps);
            }
        }

        $robotsPath = "site{$site->id}/robots.txt";
        Storage::disk('generated')->put($robotsPath, $this->generateRobotsTxt($site));
        $generatedFiles[] = $robotsPath;
        $currentStep++;
        if ($onProgress) {
            $onProgress($currentStep, $totalSteps);
        }

        // Copy assets to the site's generated directory
        $this->copyAssetsToSite($site->id);

        // Auto-commit to staging git repo
        try {
            $gitService = app(\App\Services\GitService::class);
            $gitService->setRepositoryPath(Storage::disk('generated')->path(''));
            $gitService->commit("Auto-commit: update site {$site->id}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Git commit failed for site {$site->id}: " . $e->getMessage());
        }

        return [
            'success' => empty($errors),
            'files_count' => count($generatedFiles),
            'generated_files' => $generatedFiles,
            'errors' => $errors,
        ];
    }

    public function generateSitemap(Site $site): string
    {
        $pages = $this->pageRepository->getActiveBySite($site);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        foreach ($pages as $page) {
            $url = $page->canonical ?: "https://{$site->domain}" . $this->languageService->hrefForPage(
                $page,
                (string) ($page->locale ?? $site->locale ?? 'en'),
                (string) ($site->locale ?? $site->default_locale ?? 'en')
            );
            
            $xml .= '  <url>' . PHP_EOL;
            $xml .= "    <loc>{$url}</loc>" . PHP_EOL;
            
            if ($page->updated_at) {
                $xml .= "    <lastmod>{$page->updated_at->toW3cString()}</lastmod>" . PHP_EOL;
            }
            
            $xml .= '  </url>' . PHP_EOL;
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }

    public function generateRobotsTxt(Site $site): string
    {
        $content = "User-agent: *" . PHP_EOL;
        $content .= "Allow: /" . PHP_EOL;
        $content .= PHP_EOL;
        $content .= "Sitemap: https://{$site->domain}/sitemap.xml" . PHP_EOL;
        
        return $content;
    }

    private function isSitemapPage(Page $page): bool
    {
        $slug = trim((string) $page->slug, '/');
        return in_array($slug, ['sitemap', 'sitemap.html'], true);
    }

    /**
     * @return array<int, array{href:string,label:string}>
     */
    private function buildSitemapLinks(Site $site, ?Page $currentPage = null): array
    {
        $links = [];
        $lookup = [];
        $currentLocale = $this->languageService->normalizeLocale(
            (string) ($currentPage?->locale ?? $site->locale ?? $site->default_locale ?? 'en')
        ) ?: 'en';

        $activePages = $this->pageRepository->getActiveBySite($site);
        foreach ($activePages as $page) {
            $pageLocale = $this->languageService->normalizeLocale(
                (string) ($page->locale ?? $site->locale ?? $site->default_locale ?? 'en')
            ) ?: 'en';

            if ($pageLocale !== $currentLocale) {
                continue;
            }

            $href = $this->languageService->hrefForPage(
                $page,
                (string) ($page->locale ?? $site->locale ?? 'en'),
                (string) ($site->locale ?? $site->default_locale ?? 'en')
            );
            if ($href === null) {
                continue;
            }

            $label = trim((string) $page->title);
            if ($label === '') {
                $label = $this->labelFromHref($href);
            }

            $this->pushSitemapLink($links, $lookup, $href, $label);
        }

        if (count($links) === 0) {
            foreach ($this->sitemapHrefsFromXml($site) as $href) {
                $this->pushSitemapLink($links, $lookup, $href, $this->labelFromHref($href));
            }
        }

        if (count($links) === 0) {
            foreach ($this->sitemapHrefsFromGeneratedFiles($site) as $href) {
                $this->pushSitemapLink($links, $lookup, $href, $this->labelFromHref($href));
            }
        }

        if (count($links) === 0) {
            return [
                ['href' => '/', 'label' => 'Home'],
            ];
        }

        $homeIndex = null;
        foreach ($links as $index => $link) {
            if (($link['href'] ?? '') === '/') {
                $homeIndex = $index;
                break;
            }
        }

        if ($homeIndex !== null && $homeIndex > 0) {
            $homeLink = $links[$homeIndex];
            array_splice($links, $homeIndex, 1);
            array_unshift($links, $homeLink);
        }

        return $links;
    }

    private function pushSitemapLink(array &$links, array &$lookup, string $href, string $label): void
    {
        $normalizedHref = trim($href);
        if ($normalizedHref === '') {
            return;
        }

        $key = Str::lower($normalizedHref);
        if (array_key_exists($key, $lookup)) {
            return;
        }

        $normalizedLabel = trim($label);
        if ($normalizedLabel === '') {
            $normalizedLabel = $this->labelFromHref($normalizedHref);
        }

        $lookup[$key] = count($links);
        $links[] = [
            'href' => $normalizedHref,
            'label' => $normalizedLabel,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function sitemapHrefsFromXml(Site $site): array
    {
        $sitemapPath = "site{$site->id}/sitemap.xml";
        if (!Storage::disk('generated')->exists($sitemapPath)) {
            return [];
        }

        $xmlContent = Storage::disk('generated')->get($sitemapPath);
        if (!is_string($xmlContent) || trim($xmlContent) === '') {
            return [];
        }

        $xml = @simplexml_load_string($xmlContent);
        if ($xml === false) {
            return [];
        }

        $urls = [];
        if (!isset($xml->url)) {
            return $urls;
        }

        foreach ($xml->url as $urlNode) {
            $loc = trim((string) ($urlNode->loc ?? ''));
            if ($loc === '') {
                continue;
            }

            $href = $this->hrefFromUrl($loc, (string) $site->domain);
            if ($href === null) {
                continue;
            }

            $urls[] = $href;
        }

        return $urls;
    }

    /**
     * @return array<int, string>
     */
    private function sitemapHrefsFromGeneratedFiles(Site $site): array
    {
        $sitePath = "site{$site->id}";
        if (!Storage::disk('generated')->exists($sitePath)) {
            return [];
        }

        $hrefs = [];
        foreach (Storage::disk('generated')->allFiles($sitePath) as $filePath) {
            $relativePath = ltrim((string) Str::of($filePath)->after("{$sitePath}/"), '/');
            if ($relativePath === '') {
                continue;
            }

            if (!Str::endsWith(Str::lower($relativePath), '.html')) {
                continue;
            }

            if ($relativePath === 'index.html') {
                $hrefs[] = '/';
                continue;
            }

            $hrefs[] = $relativePath;
        }

        return $hrefs;
    }

    private function hrefFromSlug(string $slug): ?string
    {
        $normalizedSlug = trim($slug);
        if ($normalizedSlug === '') {
            return '/';
        }

        $normalizedSlug = trim($normalizedSlug, '/');
        if ($normalizedSlug === '' || Str::lower($normalizedSlug) === 'index' || Str::lower($normalizedSlug) === 'index.html') {
            return '/';
        }

        if (!Str::endsWith(Str::lower($normalizedSlug), '.html')) {
            $normalizedSlug .= '.html';
        }

        return $normalizedSlug;
    }

    private function hrefFromUrl(string $url, string $siteDomain): ?string
    {
        $normalizedUrl = trim($url);
        if ($normalizedUrl === '') {
            return null;
        }

        $parts = parse_url($normalizedUrl);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            $urlHost = Str::lower((string) ($parts['host'] ?? ''));
            $domainUrl = Str::startsWith($siteDomain, ['http://', 'https://']) ? $siteDomain : ('https://' . $siteDomain);
            $domainHost = Str::lower((string) parse_url($domainUrl, PHP_URL_HOST));

            if ($urlHost !== '' && $domainHost !== '' && $urlHost !== $domainHost) {
                return $normalizedUrl;
            }
        }

        $path = trim((string) ($parts['path'] ?? ''));
        if ($path === '' || $path === '/') {
            return '/';
        }

        $path = trim($path, '/');
        if ($path === '' || Str::lower($path) === 'index' || Str::lower($path) === 'index.html') {
            return '/';
        }

        if (!Str::endsWith(Str::lower($path), '.html')) {
            $path .= '.html';
        }

        return $path;
    }

    private function labelFromHref(string $href): string
    {
        $trimmed = trim($href);
        if ($trimmed === '' || $trimmed === '/') {
            return 'Home';
        }

        if (Str::startsWith($trimmed, ['http://', 'https://'])) {
            $path = (string) parse_url($trimmed, PHP_URL_PATH);
            $trimmed = trim($path, '/');
        }

        if ($trimmed === '' || $trimmed === '/') {
            return 'Home';
        }

        $segment = Str::of($trimmed)
            ->trim('/')
            ->replaceMatches('/\.html$/i', '')
            ->afterLast('/')
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->value();

        return $segment !== '' ? $segment : 'Page';
    }

    public function generatePreview(Page $page): array
    {
        $previewToken = Str::random(32);
        $previewDir = "preview/{$previewToken}";

        Storage::disk('generated')->put("{$previewDir}/.site.json", json_encode([
            'site_id' => (int) $page->site_id,
            'page_id' => (int) $page->id,
            'created_at' => now()->toDateTimeString(),
        ], JSON_THROW_ON_ERROR));

        $previewPages = $this->pageRepository->getActiveBySite($page->site)->keyBy('id');
        $previewPages->put($page->id, $page);

        foreach ($previewPages->values() as $previewPage) {
            $html = $this->generatePage($previewPage);
            $html = $this->rewriteAssetPathsForPreview($html, $previewToken);

            Storage::disk('generated')->put(
                "{$previewDir}/{$this->pageFilename($previewPage)}",
                $html
            );
        }
        
        // Copy assets from the site's generated directory to preview directory
        $this->copyAssetsToPreview($page->site_id, $previewToken);

        try {
            $this->cleanupExpiredPreviews();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Preview cleanup failed', [
                'token' => $previewToken,
                'error' => $e->getMessage(),
            ]);
        }
        
        return [
            'token' => $previewToken,
            'url' => "/api/preview/{$previewToken}/{$this->pageFilename($page)}",
            'expires_at' => now()->addMinutes(30)->toDateTimeString(),
        ];
    }

    private function rewriteAssetPathsForPreview(string $html, string $previewToken): string
    {
        $html = preg_replace('/(href|src)=(["\'])\/assets\/([^"\']*)\2/', '$1=$2/api/preview/' . $previewToken . '/assets/$3$2', $html);
        $html = preg_replace('/(href|src)=(["\'])\/js\/([^"\']*)\2/', '$1=$2/api/preview/' . $previewToken . '/js/$3$2', $html);
        $html = preg_replace_callback('/href=(["\'])\/([^"\']*)\1/i', function (array $matches) use ($previewToken): string {
            $quote = $matches[1];
            $path = $matches[2] ?? '';

            if (str_starts_with($path, 'api/preview/') || str_starts_with($path, 'assets/') || str_starts_with($path, 'js/')) {
                return $matches[0];
            }

            $previewPath = $this->previewHrefPath($path);

            return 'href=' . $quote . '/api/preview/' . $previewToken . '/' . $previewPath . $quote;
        }, $html);
        
        return $html;
    }

    private function previewHrefPath(string $path): string
    {
        $fragment = '';
        $query = '';
        $normalized = $path;

        $fragmentPos = strpos($normalized, '#');
        if ($fragmentPos !== false) {
            $fragment = substr($normalized, $fragmentPos);
            $normalized = substr($normalized, 0, $fragmentPos);
        }

        $queryPos = strpos($normalized, '?');
        if ($queryPos !== false) {
            $query = substr($normalized, $queryPos);
            $normalized = substr($normalized, 0, $queryPos);
        }

        $normalized = trim($normalized, '/');
        if ($normalized === '') {
            return 'index.html' . $query . $fragment;
        }

        if (!str_ends_with(strtolower($normalized), '.html')) {
            $normalized .= '/index.html';
        }

        return $normalized . $query . $fragment;
    }

    private function pageFilename(Page $page): string
    {
        return $this->languageService->pathForPage(
            $page,
            (string) ($page->locale ?? $page->site?->locale ?? 'en'),
            (string) ($page->site?->locale ?? $page->site?->default_locale ?? 'en')
        );
    }

    private function copyAssetsToPreview(int $siteId, string $previewToken): void
    {
        $sourceAssetPath = $this->resolveGeneratedAssetsSourcePath($siteId, $previewToken);

        if ($sourceAssetPath === null) {
            \Illuminate\Support\Facades\Log::warning('Preview assets source was not found', [
                'site_id' => $siteId,
                'preview_token' => $previewToken,
            ]);
            return;
        }

        $previewAssetsPath = "preview/{$previewToken}/assets";
        try {
            $this->copyStorageDirectory('generated', $sourceAssetPath, 'generated', $previewAssetsPath);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Preview assets copy failed', [
                'site_id' => $siteId,
                'preview_token' => $previewToken,
                'source_asset_path' => $sourceAssetPath,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $siteAssetsPath = "site{$siteId}/assets";
        if ($sourceAssetPath !== $siteAssetsPath) {
            $this->copyGeneratedAssetOverlay($siteAssetsPath, $previewAssetsPath);
        }
        $this->ensureStyleSheetAlias('generated', $previewAssetsPath);
        $this->ensureMainScriptAlias('generated', $previewAssetsPath);
        $this->rewritePreviewCssAssetPaths($previewAssetsPath, $previewToken);
    }

    private function rewritePreviewCssAssetPaths(string $previewAssetsPath, string $previewToken): void
    {
        $disk = Storage::disk('generated');
        $cssDir = trim($previewAssetsPath, '/').'/css';

        if (!$disk->exists($cssDir)) {
            return;
        }

        foreach ($disk->allFiles($cssDir) as $cssFile) {
            if (!Str::endsWith($cssFile, '.css')) {
                continue;
            }

            $css = $disk->get($cssFile);
            $rewritten = preg_replace(
                '/url\(\s*(["\']?)\/assets\/([^)"\']+)\1\s*\)/',
                "url(\$1/api/preview/{$previewToken}/assets/\$2\$1)",
                $css
            );
            if ($rewritten !== null) {
                $rewritten = preg_replace(
                    '/url\(\s*(["\']?)\/api\/preview\/[^\/)"\']+\/assets\/([^)"\']+)\1\s*\)/',
                    "url(\$1/api/preview/{$previewToken}/assets/\$2\$1)",
                    $rewritten
                );
            }

            if ($rewritten !== null && $rewritten !== $css) {
                $disk->put($cssFile, $rewritten);
            }
        }
    }

    private function copyAssetsToSite(int $siteId): void
    {
        $targetPath = "site{$siteId}/assets";

        $sourceFromSitesDisk = "{$siteId}/assets";
        if ($this->hasCompleteAssets('sites', $sourceFromSitesDisk)) {
            Storage::disk('generated')->deleteDirectory($targetPath);
            $this->copyStorageDirectory('sites', $sourceFromSitesDisk, 'generated', $targetPath);
            $this->ensureStyleSheetAlias('generated', $targetPath);
            $this->ensureMainScriptAlias('generated', $targetPath);
            return;
        }

        $fallbackPath = $this->resolveGeneratedAssetsSourcePath($siteId);
        if ($fallbackPath === null) {
            \Illuminate\Support\Facades\Log::warning('Generated site assets source was not found', [
                'site_id' => $siteId,
            ]);
            return;
        }

        if ($fallbackPath === $targetPath) {
            $this->ensureStyleSheetAlias('generated', $targetPath);
            $this->ensureMainScriptAlias('generated', $targetPath);
            return;
        }

        $assetOverlay = $this->readGeneratedAssetOverlay($targetPath);

        Storage::disk('generated')->deleteDirectory($targetPath);
        $this->copyStorageDirectory('generated', $fallbackPath, 'generated', $targetPath);
        $this->writeGeneratedAssetOverlay($targetPath, $assetOverlay);
        $this->ensureStyleSheetAlias('generated', $targetPath);
        $this->ensureMainScriptAlias('generated', $targetPath);
    }

    /**
     * @return array<string, string>
     */
    private function readGeneratedAssetOverlay(string $assetsPath): array
    {
        $disk = Storage::disk('generated');
        $normalizedAssetsPath = trim($assetsPath, '/');
        $overlay = [];

        if (!$disk->exists($normalizedAssetsPath)) {
            return $overlay;
        }

        foreach ($disk->allFiles($normalizedAssetsPath) as $file) {
            $relativePath = ltrim((string) Str::of($file)->after($normalizedAssetsPath), '/');
            if ($relativePath === '' || Str::startsWith($relativePath, ['css/', 'js/'])) {
                continue;
            }

            $overlay[$relativePath] = $disk->get($file);
        }

        return $overlay;
    }

    /**
     * @param  array<string, string>  $overlay
     */
    private function writeGeneratedAssetOverlay(string $assetsPath, array $overlay): void
    {
        $disk = Storage::disk('generated');
        $normalizedAssetsPath = trim($assetsPath, '/');

        foreach ($overlay as $relativePath => $contents) {
            $disk->put("{$normalizedAssetsPath}/{$relativePath}", $contents);
        }
    }

    private function copyGeneratedAssetOverlay(string $sourceAssetsPath, string $targetAssetsPath): void
    {
        $this->writeGeneratedAssetOverlay(
            $targetAssetsPath,
            $this->readGeneratedAssetOverlay($sourceAssetsPath)
        );
    }

    private function resolveGeneratedAssetsSourcePath(int $siteId, ?string $excludePreviewToken = null): ?string
    {
        $siteAssetsPath = "site{$siteId}/assets";
        if ($this->hasCompleteAssets('generated', $siteAssetsPath)) {
            return $siteAssetsPath;
        }

        $etalonPath = 'site1/assets';
        if ($etalonPath !== $siteAssetsPath && $this->hasCompleteAssets('generated', $etalonPath)) {
            return $etalonPath;
        }

        if ($this->hasAnyAssetFiles('generated', $siteAssetsPath)) {
            return $siteAssetsPath;
        }

        if ($etalonPath !== $siteAssetsPath && $this->hasAnyAssetFiles('generated', $etalonPath)) {
            return $etalonPath;
        }

        return $this->findLatestPreviewAssetsPath($excludePreviewToken);
    }

    private function hasCompleteAssets(string $diskName, string $assetsPath): bool
    {
        $disk = Storage::disk($diskName);
        $normalizedPath = trim($assetsPath, '/');

        try {
            if (!$disk->exists("{$normalizedPath}/css/style.css") && !$disk->exists("{$normalizedPath}/css/main.css")) {
                return false;
            }

            if (!$disk->exists("{$normalizedPath}/js/main.js") && !$disk->exists("{$normalizedPath}/js/app.js")) {
                return false;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Generated assets existence check failed', [
                'disk' => $diskName,
                'assets_path' => $normalizedPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        return $this->hasAnyAssetFiles($diskName, $normalizedPath);
    }

    private function hasAnyAssetFiles(string $diskName, string $assetsPath): bool
    {
        $disk = Storage::disk($diskName);
        $normalizedPath = trim($assetsPath, '/');

        try {
            if (!$disk->exists($normalizedPath)) {
                return false;
            }

            return $disk->allFiles($normalizedPath) !== [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Generated assets listing failed', [
                'disk' => $diskName,
                'assets_path' => $normalizedPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function ensureStyleSheetAlias(string $diskName, string $assetsPath): void
    {
        $disk = Storage::disk($diskName);
        $normalizedAssetsPath = trim($assetsPath, '/');

        $stylePath = "{$normalizedAssetsPath}/css/style.css";
        if ($disk->exists($stylePath)) {
            return;
        }

        $mainPath = "{$normalizedAssetsPath}/css/main.css";
        if (!$disk->exists($mainPath)) {
            return;
        }

        $disk->put($stylePath, $disk->get($mainPath));
    }

    private function findLatestPreviewAssetsPath(?string $excludeToken = null): ?string
    {
        $generatedDisk = Storage::disk('generated');
        $candidates = [];

        foreach ($generatedDisk->directories('preview') as $previewDir) {
            $token = (string) Str::of($previewDir)->after('preview/');
            if ($excludeToken !== null && $token === $excludeToken) {
                continue;
            }

            $assetsPath = "{$previewDir}/assets";
            if (!$generatedDisk->exists("{$assetsPath}/css/style.css")) {
                continue;
            }

            $modifiedAt = 0;
            try {
                $modifiedAt = $generatedDisk->lastModified("{$assetsPath}/css/style.css");
            } catch (\Throwable) {
                // Keep the candidate with a neutral timestamp.
            }

            $candidates[] = [
                'path' => $assetsPath,
                'modified_at' => $modifiedAt,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            fn (array $left, array $right): int => $right['modified_at'] <=> $left['modified_at']
        );

        return (string) $candidates[0]['path'];
    }

    private function ensureMainScriptAlias(string $diskName, string $assetsPath): void
    {
        $disk = Storage::disk($diskName);
        $normalizedAssetsPath = trim($assetsPath, '/');

        $mainScriptPath = "{$normalizedAssetsPath}/js/main.js";
        if ($disk->exists($mainScriptPath)) {
            return;
        }

        $appScriptPath = "{$normalizedAssetsPath}/js/app.js";
        if (!$disk->exists($appScriptPath)) {
            return;
        }

        $disk->put($mainScriptPath, $disk->get($appScriptPath));
    }

    public function cleanupExpiredPreviews(): int
    {
        $generatedDisk = Storage::disk('generated');
        $allDirs = $generatedDisk->directories('preview');

        $cleaned = 0;
        foreach ($allDirs as $dir) {
            $files = $generatedDisk->allFiles($dir);

            if (empty($files)) {
                $generatedDisk->deleteDirectory($dir);
                $cleaned++;
                continue;
            }

            $latestModified = null;
            foreach ($files as $file) {
                try {
                    $modifiedAt = $generatedDisk->lastModified($file);
                } catch (\Throwable) {
                    continue;
                }

                if ($latestModified === null || $modifiedAt > $latestModified) {
                    $latestModified = $modifiedAt;
                }
            }

            if ($latestModified === null) {
                continue;
            }

            if (now()->diffInMinutes(\Illuminate\Support\Carbon::createFromTimestamp($latestModified)) > 30) {
                $generatedDisk->deleteDirectory($dir);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    private function copyStorageDirectory(string $sourceDisk, string $sourcePath, string $targetDisk, string $targetPath): void
    {
        $source = Storage::disk($sourceDisk);
        $target = Storage::disk($targetDisk);

        if (!$source->exists($sourcePath)) {
            return;
        }

        if (!$target->exists($targetPath)) {
            $target->makeDirectory($targetPath);
        }

        $directories = $source->allDirectories($sourcePath);
        foreach ($directories as $directory) {
            $relativePath = ltrim((string) Str::of($directory)->after($sourcePath), '/');
            $targetDirectory = $relativePath === '' ? $targetPath : "{$targetPath}/{$relativePath}";
            if (!$target->exists($targetDirectory)) {
                $target->makeDirectory($targetDirectory);
            }
        }

        $files = $source->allFiles($sourcePath);
        foreach ($files as $file) {
            $relativePath = ltrim((string) Str::of($file)->after($sourcePath), '/');
            $targetFilePath = $relativePath === '' ? $targetPath : "{$targetPath}/{$relativePath}";
            $target->put($targetFilePath, $source->get($file));
        }
    }

    private function resolvePageTemplatePath(Page $page): string
    {
        $templateSet = $page->site->template_set ?: 'base';
        $slugViewPath = $this->slugToViewPath($page->slug);

        $candidates = [
            "templates.{$templateSet}.pages.{$slugViewPath}",
            "templates.{$templateSet}.pages.default",
            "templates.base.pages.{$slugViewPath}",
            'templates.base.pages.default',
        ];

        foreach ($candidates as $candidate) {
            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        return 'templates.base.pages.default';
    }

    private function slugToViewPath(string $slug): string
    {
        $normalized = trim($slug, '/');

        if ($normalized === '' || $normalized === 'index') {
            return 'index';
        }

        $normalized = str_replace('/', '.', $normalized);

        return (string) Str::of($normalized)->replaceMatches('/[^A-Za-z0-9._-]/', '-');
    }

    private function normalizeGoogleMapEmbeds(string $html): string
    {
        // Legacy imported markup may contain <embed class="google-map__iframe">.
        $html = preg_replace_callback(
            '/<embed\b([^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1] ?? '';

                $hasMapClass = preg_match('/\bclass\s*=\s*["\'][^"\']*\bgoogle-map__iframe\b[^"\']*["\']/i', $attributes) === 1;
                $hasGoogleMapsSrc = preg_match('/\bsrc\s*=\s*["\'][^"\']*(maps\.google\.com\/maps|www\.google\.com\/maps\/embed)[^"\']*["\']/i', $attributes) === 1;

                if (!$hasMapClass || !$hasGoogleMapsSrc) {
                    return $matches[0];
                }

                $cleanAttributes = trim($attributes);
                return '<iframe ' . $cleanAttributes . '></iframe>';
            },
            $html
        ) ?? $html;

        // sandbox="" blocks Google Maps scripts inside iframe.
        $html = preg_replace_callback(
            '/<iframe\b([^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1] ?? '';

                $hasGoogleMapsSrc = preg_match('/\bsrc\s*=\s*["\'][^"\']*(maps\.google\.com\/maps|www\.google\.com\/maps\/embed)[^"\']*["\']/i', $attributes) === 1;
                if (!$hasGoogleMapsSrc) {
                    return $matches[0];
                }

                $cleanAttributes = preg_replace('/\s+sandbox(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $attributes) ?? $attributes;

                return '<iframe' . $cleanAttributes . '>';
            },
            $html
        ) ?? $html;

        return $html;
    }
}

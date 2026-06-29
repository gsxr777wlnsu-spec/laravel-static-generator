<?php

namespace App\Services;

use App\Contracts\SiteRepositoryInterface;
use App\Models\Site;
use App\Models\Page;
use App\Models\Section;
use App\Support\SiteLayoutContent;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class ImportService
{
    private const ETALON_SITE_ID = 1;

    public function __construct(
        private SiteRepositoryInterface $sites,
        private SiteLayoutContent $layoutContent
    ) {}

    public function importFromMdFile(string $filePath, ?int $siteId = null, bool $allowSftpUpdates = true): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Could not read file: {$filePath}");
        }

        $data = Yaml::parse($content);
        if ($data === null) {
            throw new \RuntimeException('Could not parse YAML content');
        }

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid YAML structure: root value must be a map/object');
        }

        return $this->importSite($data, $siteId, $allowSftpUpdates);
    }

    public function importSite(array $data, ?int $siteId = null, bool $allowSftpUpdates = true): array
    {
        $domain = $data['domain'] ?? null;
        if (!$domain) {
            throw new \RuntimeException("Missing 'domain' in import data");
        }

        // If siteId provided, use that site instead of searching by domain
        if ($siteId) {
            $site = $this->sites->findById($siteId);
            if (!$site) {
                throw new \RuntimeException("Site not found with ID: {$siteId}");
            }
        } else {
            $site = $this->sites->findByDomain($domain);
        }

        $template = $data['template'] ?? 'base';
        $pages = $data['pages'] ?? [];

        $isNewSite = false;

        if (!$site) {
            $site = $this->sites->create([
                'name' => $domain,
                'domain' => $domain,
                'status' => 'active',
                'template_set' => $template,
                'output_path' => "generated/{$domain}",
                'locale' => 'en',
                'default_locale' => 'en',
            ]);
            $isNewSite = true;
            
            try {
                if ($isNewSite) {
                    $this->copyAssetsFromEtalon($site->id);
                    $this->copyTemplatesToSite($site->id);
                }
            } catch (\Exception $e) {
                $site->delete();
                throw new \RuntimeException("Failed to initialize site: " . $e->getMessage());
            }
        }

        if (is_array($pages) && $pages !== []) {
            $this->syncSharedLayoutContent($site, $pages);
        }

        $importedPages = [];

        $hasSftpUpdates = false;
        if ($allowSftpUpdates) {
            foreach ([
                'sftp_host',
                'sftp_port',
                'sftp_username',
                'sftp_password',
                'sftp_private_key',
                'sftp_auth_method',
                'sftp_remote_path',
            ] as $sftpKey) {
                if (array_key_exists($sftpKey, $data)) {
                    $hasSftpUpdates = true;
                    break;
                }
            }
        }

        if ($allowSftpUpdates && $hasSftpUpdates) {
            $authMethod = $site->sftp_auth_method;
            if (isset($data['sftp_auth_method'])) {
                $candidate = strtolower((string) $data['sftp_auth_method']);
                if (in_array($candidate, ['password', 'key'], true)) {
                    $authMethod = $candidate;
                }
            }

            $site->update([
                'sftp_host' => $data['sftp_host'] ?? $site->sftp_host,
                'sftp_port' => $data['sftp_port'] ?? $site->sftp_port,
                'sftp_username' => $data['sftp_username'] ?? $site->sftp_username,
                'sftp_password' => $this->resolveEncryptedSecret($data, 'sftp_password', $site->sftp_password),
                'sftp_private_key' => $this->resolveEncryptedSecret($data, 'sftp_private_key', $site->sftp_private_key),
                'sftp_auth_method' => $authMethod,
                'sftp_remote_path' => $data['sftp_remote_path'] ?? $site->sftp_remote_path,
            ]);
        }

        foreach ($pages as $pageData) {
            $page = $this->importPage($site, $pageData, $isNewSite);
            $importedPages[] = $page;
        }

        return [
            'site' => $site,
            'pages' => $importedPages,
            'pages_count' => count($importedPages),
        ];
    }

    private function syncSharedLayoutContent(Site $site, array $pages): void
    {
        $menuHtml = null;
        $mobileMenuHtml = null;
        $footerHtml = null;

        foreach ($pages as $page) {
            if (!is_array($page)) {
                continue;
            }

            $sections = $page['sections'] ?? null;
            if (!is_array($sections)) {
                continue;
            }

            foreach ($sections as $section) {
                if (!is_array($section)) {
                    continue;
                }

                $rawHtml = trim((string) ($section['raw_html'] ?? ''));
                $module = strtolower(trim((string) ($section['module'] ?? $section['module_key'] ?? '')));

                if ($rawHtml !== '') {
                    if (
                        $menuHtml === null
                        && (str_contains($rawHtml, 'header__inner') || str_contains($rawHtml, '<header'))
                    ) {
                        $candidate = $this->layoutContent->normalizeMenuInner($rawHtml);
                        if ($candidate !== '') {
                            $menuHtml = $candidate;
                        }
                    }

                    if (
                        $mobileMenuHtml === null
                        && ($module === 'mobile-menu' || str_contains($rawHtml, 'mobile-menu'))
                    ) {
                        $candidate = $this->layoutContent->normalizeMobileMenuHtml($rawHtml);
                        if ($candidate !== '') {
                            $mobileMenuHtml = $candidate;
                        }
                    }

                    if (
                        $footerHtml === null
                        && (
                            $module === 'footer'
                            || str_contains($rawHtml, '<footer')
                            || str_contains($rawHtml, 'class="footer')
                            || str_contains($rawHtml, "class='footer")
                        )
                    ) {
                        $candidate = $this->layoutContent->normalizeFooterInner($rawHtml);
                        if ($candidate !== '') {
                            $footerHtml = $candidate;
                        }
                    }
                }
            }
        }

        $updates = [];

        if ($menuHtml !== null) {
            $updates['menu_html'] = $menuHtml;
        }

        if ($mobileMenuHtml !== null) {
            $updates['mobile_menu_html'] = $mobileMenuHtml;
        }

        if ($footerHtml !== null) {
            $updates['footer_html'] = $footerHtml;
        }

        if ($updates !== []) {
            $site->update($updates);
            $site->refresh();
        }
    }

    private function copyAssetsFromEtalon(int $newSiteId): void
    {
        $sourcePath = $this->resolveImportAssetsSourcePath();
        if ($sourcePath === null) {
            return;
        }

        $targetPath = "{$newSiteId}/assets";
        $this->copyStorageDirectory('generated', $sourcePath, 'sites', $targetPath);
        $this->ensureMainScriptInAssets(Storage::disk('sites')->path($targetPath));
    }

    private function ensureMainScriptInAssets(string $assetsPath): void
    {
        $appScriptPath = $assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'app.js';
        $mainScriptPath = $assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'main.js';

        if (!is_file($appScriptPath) || is_file($mainScriptPath)) {
            return;
        }

        copy($appScriptPath, $mainScriptPath);
    }

    private function resolveImportAssetsSourcePath(): ?string
    {
        $generatedDisk = Storage::disk('generated');
        $etalonPath = 'site' . self::ETALON_SITE_ID . '/assets';

        if ($this->hasCompleteAssetSet($generatedDisk, $etalonPath)) {
            return $etalonPath;
        }

        $candidates = [];
        foreach ($generatedDisk->directories('preview') as $previewDir) {
            $assetsPath = "{$previewDir}/assets";
            if (!$this->hasCompleteAssetSet($generatedDisk, $assetsPath)) {
                continue;
            }

            $modifiedAt = 0;
            try {
                $modifiedAt = $generatedDisk->lastModified("{$assetsPath}/css/style.css");
            } catch (\Throwable) {
                // Keep zero timestamp and still allow the candidate.
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

        return $candidates[0]['path'];
    }

    private function hasCompleteAssetSet($disk, string $assetsPath): bool
    {
        $normalizedPath = trim($assetsPath, '/');

        if (!$disk->exists("{$normalizedPath}/css/style.css")) {
            return false;
        }

        return $disk->exists("{$normalizedPath}/js/main.js")
            || $disk->exists("{$normalizedPath}/js/app.js");
    }

    private function copyTemplatesToSite(int $siteId): void
    {
        $baseTemplatesPath = resource_path('views/templates/base');
        $targetPath = resource_path("views/templates/site{$siteId}");

        if (!is_dir($baseTemplatesPath)) {
            return;
        }

        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir) || !is_writable($parentDir)) {
            return;
        }

        try {
            $this->copyDirectory($baseTemplatesPath, $targetPath);
        } catch (\Throwable) {
            // Template copy is best-effort and should not fail the whole import.
        }
    }

    private function importPage(Site $site, array $data, bool $bootstrapSections = false): Page
    {
        $slug = $data['slug'] ?? '';
        $templateKey = $data['template_key'] ?? null;
        $hasSections = isset($data['sections']) && is_array($data['sections']) && count($data['sections']) > 0;

        $page = Page::where('site_id', $site->id)
            ->where('slug', $slug)
            ->first();

        if ($page) {
            $page->update([
                'title' => $data['title'] ?? $page->title,
                'template_key' => $templateKey ?? $page->template_key,
                'status' => $data['status'] ?? $page->status,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'canonical' => $data['canonical'] ?? null,
                'og_data' => $this->pageOgData($data),
                'json_ld' => isset($data['json_ld']) && is_array($data['json_ld']) ? $data['json_ld'] : null,
                'locale' => $data['locale'] ?? 'en',
            ]);
        } else {
            $page = Page::create([
                'site_id' => $site->id,
                'slug' => $slug,
                'title' => $data['title'] ?? '',
                'template_key' => $templateKey ?? 'blank',
                'status' => $data['status'] ?? 'draft',
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'canonical' => $data['canonical'] ?? null,
                'og_data' => $this->pageOgData($data),
                'json_ld' => isset($data['json_ld']) && is_array($data['json_ld']) ? $data['json_ld'] : null,
                'locale' => $data['locale'] ?? 'en',
            ]);
        }

        if ($hasSections) {
            $this->importSections($site, $page, $data['sections']);
        } elseif ($bootstrapSections && $templateKey && $templateKey !== 'blank') {
            $this->bootstrapSectionsFromEtalon($page, $templateKey);
        }

        return $page;
    }

    private function pageOgData(array $data): ?array
    {
        $ogData = isset($data['og_data']) && is_array($data['og_data']) ? $data['og_data'] : [];

        foreach (['head_meta', 'head_links', 'head_extra', 'head_custom', 'body_extra'] as $key) {
            if (array_key_exists($key, $data)) {
                $ogData[$key] = $data[$key];
            }
        }

        return count($ogData) > 0 ? $ogData : null;
    }

    private function bootstrapSectionsFromEtalon(Page $page, string $templateKey): void
    {
        $sourceFile = $this->getSourceFileForTemplate($templateKey);
        if (!$sourceFile) {
            return;
        }

        $fullPath = storage_path("generated/site1/{$sourceFile}");
        if (!is_file($fullPath)) {
            return;
        }

        $html = file_get_contents($fullPath);
        if ($html === false || trim($html) === '') {
            return;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//section[not(ancestor::footer) and not(contains(@id, "footer")) and not(contains(@class, "footer"))] | //div[@id="text"]');

        if ($nodes === false || $nodes->length === 0) {
            return;
        }

        Section::where('page_id', $page->id)->delete();

        $order = 0;
        foreach ($nodes as $node) {
            $tagName = strtolower((string) $node->tagName);
            $classAttr = trim((string) $node->getAttribute('class'));
            $idAttr = trim((string) $node->getAttribute('id'));

            $moduleKey = $this->resolveModuleKey($classAttr, $idAttr, $templateKey);

            if ($moduleKey === null) {
                continue;
            }

            $heading = $this->extractHeading($node);
            $rawHtml = $this->innerHtml($node);

            Section::create([
                'page_id' => $page->id,
                'order' => $order++,
                'type' => 'module',
                'module' => $moduleKey,
                'module_key' => $moduleKey,
                'heading' => $heading,
                'raw_html' => $rawHtml,
                'class' => $classAttr ?: $moduleKey,
                'identifier' => $idAttr ?: null,
                'content' => [
                    'module' => $moduleKey,
                    'module_key' => $moduleKey,
                    'heading' => $heading,
                    'class' => $classAttr ?: $moduleKey,
                    'id' => $idAttr ?: null,
                    'raw_html' => $rawHtml,
                ],
            ]);
        }
    }

    private function getSourceFileForTemplate(string $templateKey): ?string
    {
        $map = [
            '1win' => '1win.html',
            'app' => 'app.html',
            'app-copy' => 'app.html',
            'authors' => 'authors.html',
            'bonuses' => 'bonuses.html',
            'comparison' => 'comparison.html',
            'contact-us' => 'contact-us.html',
            'cookie-policy' => 'cookie-policy.html',
            'demo' => 'demo.html',
            'index' => 'index.html',
            'privacy-policy' => 'privacy-policy.html',
            'reviews' => 'reviews.html',
            'sitemap' => 'sitemap.html',
            'terms-and-conditions' => 'terms-and-conditions.html',
            'tips' => 'tips.html',
        ];

        return $map[$templateKey] ?? null;
    }

    private function resolveModuleKey(string $classAttr, string $idAttr, ?string $templateKey = null): ?string
    {
        $tokens = preg_split('/\s+/', $classAttr) ?: [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $base = preg_replace('/(--|__).*$/', '', $token);

            if (
                $base === 'authors'
                && $templateKey === 'terms-and-conditions'
                && $idAttr === 'cookies'
            ) {
                return 'authors-cookies';
            }

            if (
                $base === 'authors'
                && $templateKey === 'cookie-policy'
                && $idAttr === 'cookies'
            ) {
                return 'authors-cookie-policy';
            }

            if (
                $base === 'authors'
                && $templateKey === 'privacy-policy'
                && $idAttr === 'cookies'
            ) {
                return 'authors-privacy-policy';
            }

            if (
                $base === 'casino'
                && $templateKey === '1win'
                && $idAttr === 'bonuses'
            ) {
                return 'casino-1win';
            }

            if (
                $base === 'casino'
                && $templateKey === 'bonuses'
                && $idAttr === 'casino'
            ) {
                return 'casino-bonuses';
            }

            if (
                $base === 'casino'
                && $templateKey === 'bonuses'
                && $idAttr === 'casino-2'
            ) {
                return 'casino-bonuses-2';
            }

            if (
                $base === 'casino'
                && $templateKey === 'comparison'
                && $idAttr === 'casino'
            ) {
                return 'casino-comparison';
            }

            if (
                $base === 'casino'
                && $templateKey === 'reviews'
                && $idAttr === 'casino'
            ) {
                return 'casino-reviews';
            }

            if (
                $base === 'casino'
                && $templateKey === 'reviews'
                && $idAttr === 'text'
            ) {
                return 'text-reviews';
            }

            if (
                $base === 'casino'
                && $templateKey === 'demo'
                && $idAttr === 'casino'
            ) {
                return 'casino-demo';
            }

            if (
                $base === 'casino'
                && $templateKey === 'tips'
                && $idAttr === 'casino'
            ) {
                return 'casino-tips';
            }

            if (
                $base === 'casino'
                && $templateKey === 'tips'
                && $idAttr === 'casino-2'
            ) {
                return 'casino-tips-2';
            }

            if (
                $base === 'casino'
                && $templateKey === 'demo'
                && $idAttr === 'casino-2'
            ) {
                return 'casino-demo-2';
            }

            if (
                $base === 'casino'
                && in_array((string) $templateKey, ['app', 'app-copy'], true)
                && $idAttr === 'casino'
            ) {
                return 'casino-review-app';
            }

            if (
                $base === 'casino'
                && in_array((string) $templateKey, ['app', 'app-copy'], true)
                && $idAttr === 'where-to-play'
            ) {
                return 'casino-where-to-play-app';
            }

            if (
                $base === 'download'
                && in_array((string) $templateKey, ['app', 'app-copy'], true)
                && $idAttr === 'download'
            ) {
                return 'download-app';
            }

            if (
                $base === 'form'
                && $templateKey === 'reviews'
                && $idAttr === 'form'
            ) {
                return 'form-reviews';
            }

            if (
                $base === 'level'
                && $templateKey === 'contact-us'
                && $idAttr === 'level'
            ) {
                return 'level-map';
            }

            if (
                $base === 'steps'
                && $templateKey === 'demo'
                && $idAttr === 'steps'
            ) {
                return 'steps-demo';
            }

            if (
                $base === 'steps'
                && $templateKey === 'bonuses'
                && $idAttr === 'steps'
            ) {
                return 'steps-bonuses';
            }

            if (
                $base === 'steps'
                && $templateKey === 'tips'
                && $idAttr === 'steps'
            ) {
                return 'steps-tips';
            }

            if (
                $base === 'benefits'
                && $templateKey === 'demo'
                && $idAttr === 'benefits'
            ) {
                return 'benefits-demo';
            }

            if (
                $base === 'symbols'
                && $templateKey === '1win'
                && $idAttr === 'details'
            ) {
                return 'symbols-1win';
            }

            if (
                $base === 'steps'
                && $templateKey === '1win'
                && $idAttr === 'steps'
            ) {
                return 'steps-1win';
            }

            if (
                $base === 'review'
                && $templateKey === '1win'
                && $idAttr === 'mobile-app'
            ) {
                return 'review-1win';
            }

            if (
                $base === 'review'
                && $templateKey === '1win'
                && $idAttr === 'demo'
            ) {
                return 'review-demo-1win';
            }

            if (
                $base === 'review'
                && $templateKey === '1win'
                && $idAttr === 'support'
            ) {
                return 'review-support-1win';
            }

            if (
                $base === 'characteristics'
                && $templateKey === '1win'
                && $idAttr === 'characteristics'
            ) {
                return 'characteristics-1win';
            }

            if (
                $base === 'characteristics'
                && $templateKey === 'comparison'
                && $idAttr === 'characteristics'
            ) {
                return 'characteristics-comparison';
            }

            if (
                $base === 'review'
                && $templateKey === 'comparison'
                && $idAttr === 'review'
            ) {
                return 'review-comparison';
            }

            if (
                $base === 'symbols'
                && $templateKey === 'comparison'
                && $idAttr === 'symbols'
            ) {
                return 'symbols-comparison';
            }

            if (
                $base === 'rtp'
                && $templateKey === 'comparison'
                && $idAttr === 'rtp'
            ) {
                return 'rtp-comparison';
            }

            if (
                $base === 'steps'
                && $templateKey === 'comparison'
                && $idAttr === 'steps'
            ) {
                return 'steps-comparison';
            }

            if ($base === 'hero') {
                if ($templateKey === 'index') {
                    return 'hero-main';
                }

                if ($templateKey === 'demo') {
                    return 'hero-demo';
                }

                if ($templateKey === 'authors') {
                    return 'hero-authors';
                }

                if (in_array($templateKey, ['1win', 'app', 'app-copy', 'bonuses', 'comparison', 'reviews', 'tips'], true)) {
                    return 'hero-breadcrumbs';
                }

                if (in_array($templateKey, ['contact-us', 'sitemap'], true)) {
                    return 'hero';
                }
            }

            if ($base === 'footer') {
                return null;
            }

            $normalized = $this->normalizeModuleKey($base);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        $fromId = $this->normalizeModuleKey($idAttr);
        if ($fromId !== '' && $fromId !== 'footer') {
            return $fromId;
        }

        return 'module';
    }

    private function normalizeModuleKey(string $value): string
    {
        return \Illuminate\Support\Str::of($value)
            ->lower()
            ->trim()
            ->replace(' ', '-')
            ->replaceMatches('/[^a-z0-9_-]+/', '-')
            ->trim('-')
            ->value();
    }

    private function extractHeading(\DOMElement $section): ?string
    {
        foreach (['h1', 'h2', 'h3'] as $tagName) {
            $nodes = $section->getElementsByTagName($tagName);
            if ($nodes->length === 0) {
                continue;
            }

            $text = trim((string) $nodes->item(0)?->textContent);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function innerHtml(\DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $fragment = $element->ownerDocument?->saveHTML($child);
            if ($fragment !== false) {
                $html .= $fragment;
            }
        }

        return trim($html);
    }

    private function importSections(Site $site, Page $page, array $sectionsData): void
    {
        Section::where('page_id', $page->id)->delete();

        $order = 0;
        foreach ($sectionsData as $sectionData) {
            if (isset($sectionData['raw_html']) && is_string($sectionData['raw_html'])) {
                $sectionData['raw_html'] = $this->replaceRawHtmlPlaceholders($sectionData['raw_html'], $sectionData);
            }

            $module = $sectionData['module'] ?? ($sectionData['module_key'] ?? 'module');
            
            // Save ALL fields from YAML in content JSON (not just standard fields)
            $contentFields = array_filter($sectionData, fn($v) => $v !== null);
            $isDynamicSitemapModule = trim((string) $module) === 'sitemap';
            if (
                !$isDynamicSitemapModule
                && (!isset($contentFields['raw_html']) || !is_string($contentFields['raw_html']) || trim($contentFields['raw_html']) === '')
            ) {
                $generatedRawHtml = $this->generateRawHtmlFromModuleView($site, $page, $sectionData);
                if ($generatedRawHtml !== null) {
                    $contentFields['raw_html'] = $generatedRawHtml;
                }
            }

            Section::create([
                'page_id' => $page->id,
                'order' => $order++,
                'type' => $sectionData['type'] ?? 'module',
                'module' => $module,
                'module_key' => $sectionData['module_key'] ?? $module,
                'heading' => $sectionData['heading'] ?? null,
                'subheading' => $sectionData['subheading'] ?? null,
                'description' => $sectionData['description'] ?? null,
                'raw_html' => $sectionData['raw_html'] ?? ($contentFields['raw_html'] ?? null),
                'class' => $sectionData['class'] ?? null,
                'identifier' => $sectionData['id'] ?? null,
                'settings' => isset($sectionData['settings']) && is_array($sectionData['settings'])
                    ? $sectionData['settings']
                    : null,
                'content' => $contentFields,
            ]);
        }
    }

    private function replaceRawHtmlPlaceholders(string $rawHtml, array $sectionData): string
    {
        return preg_replace_callback(
            '/\[\[([A-Za-z0-9_.-]+)\]\]/',
            function (array $matches) use ($sectionData): string {
                $key = $matches[1];
                $value = $sectionData[$key] ?? null;

                if (is_scalar($value)) {
                    return (string) $value;
                }

                return $matches[0];
            },
            $rawHtml
        ) ?? $rawHtml;
    }

    private function generateRawHtmlFromModuleView(Site $site, Page $page, array $sectionData): ?string
    {
        $module = trim((string) ($sectionData['module'] ?? ($sectionData['module_key'] ?? '')));
        if ($module === '') {
            return null;
        }

        $templateSet = trim((string) ($site->template_set ?? 'base'));
        if ($templateSet === '') {
            $templateSet = 'base';
        }

        $viewName = "templates.{$templateSet}.modules.{$module}";

        if (!view()->exists($viewName)) {
            return null;
        }

        try {
            $section = (object) array_merge($sectionData, [
                'content' => $sectionData,
                'raw_html' => $sectionData['raw_html'] ?? null,
            ]);

            $rendered = trim((string) view($viewName, array_merge($sectionData, [
                'section' => $section,
                'page' => $page,
                'site' => $site,
            ]))->render());

        } catch (\Throwable) {
            return null;
        }

        return $rendered !== '' ? $rendered : null;
    }

    public function listImportTemplates(): array
    {
        $templatesPath = storage_path('import/templates');
        if (!is_dir($templatesPath)) {
            return [];
        }

        $templates = [];
        $directories = glob($templatesPath . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $name = basename($dir);
            $templates[] = [
                'name' => $name,
                'path' => $dir,
                'label' => ucfirst($name),
            ];
        }

        return $templates;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                throw new \RuntimeException("Could not create directory: {$destination}");
            }
        }

        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                if (!copy($sourcePath, $destPath)) {
                    throw new \RuntimeException("Could not copy file: {$sourcePath} to {$destPath}");
                }
            }
        }
    }

    private function resolveEncryptedSecret(array $data, string $key, ?string $existingValue): ?string
    {
        if (!array_key_exists($key, $data)) {
            return $existingValue;
        }

        $value = $data[$key];
        if ($value === null || $value === '') {
            return null;
        }

        return $this->encryptIfNeeded((string) $value);
    }

    private function encryptIfNeeded(string $value): string
    {
        try {
            decrypt($value);
            return $value;
        } catch (\Throwable) {
            return encrypt($value);
        }
    }

    private function copyStorageDirectory(string $sourceDisk, string $sourcePath, string $targetDisk, string $targetPath): void
    {
        if (!Storage::disk($targetDisk)->exists($targetPath)) {
            Storage::disk($targetDisk)->makeDirectory($targetPath);
        }

        $files = Storage::disk($sourceDisk)->allFiles($sourcePath);

        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . '/', '', $file);
            $targetFilePath = $targetPath . '/' . $relativePath;

            $content = Storage::disk($sourceDisk)->get($file);
            Storage::disk($targetDisk)->put($targetFilePath, $content);
        }

        $directories = Storage::disk($sourceDisk)->allDirectories($sourcePath);
        foreach ($directories as $dir) {
            $relativePath = str_replace($sourcePath . '/', '', $dir);
            $targetDirPath = $targetPath . '/' . $relativePath;

            if (!Storage::disk($targetDisk)->exists($targetDirPath)) {
                Storage::disk($targetDisk)->makeDirectory($targetDirPath);
            }
        }
    }
}

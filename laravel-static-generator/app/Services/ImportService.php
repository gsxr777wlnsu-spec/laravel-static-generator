<?php

namespace App\Services;

use App\Contracts\SiteRepositoryInterface;
use App\Models\Site;
use App\Models\Page;
use App\Models\Section;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class ImportService
{
    private const ETALON_SITE_ID = 1;

    public function __construct(
        private SiteRepositoryInterface $sites
    ) {}

    public function importFromMdFile(string $filePath, ?int $siteId = null): array
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

        return $this->importSite($data, $siteId);
    }

    public function importSite(array $data, ?int $siteId = null): array
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

        $importedPages = [];

        $hasSftpUpdates = false;
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

        if ($hasSftpUpdates) {
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

    private function copyAssetsFromEtalon(int $newSiteId): void
    {
        $sourcePath = storage_path("generated/site" . self::ETALON_SITE_ID . "/assets");
        $targetPath = storage_path("generated/site{$newSiteId}/assets");

        if (!is_dir($sourcePath)) {
            return;
        }

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $targetFile = $targetPath . DIRECTORY_SEPARATOR . $files->getSubPathname();
            
            if ($file->isDir()) {
                if (!is_dir($targetFile)) {
                    mkdir($targetFile, 0755, true);
                }
            } else {
                $dir = dirname($targetFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($file->getPathname(), $targetFile);
            }
        }

        $this->ensureMainScriptInAssets($targetPath);
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
                'locale' => $data['locale'] ?? 'en',
            ]);
        }

        if ($hasSections) {
            $this->importSections($page, $data['sections']);
        } elseif ($bootstrapSections && $templateKey && $templateKey !== 'blank') {
            $this->bootstrapSectionsFromEtalon($page, $templateKey);
        }

        return $page;
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
        $nodes = $xpath->query('//section[not(ancestor::footer) and not(contains(@id, "footer")) and not(contains(@class, "footer"))]');

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

            if ($base === 'hero' && $templateKey === 'sitemap') {
                return 'hero-sitemap';
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

    private function importSections(Page $page, array $sectionsData): void
    {
        Section::where('page_id', $page->id)->delete();

        $order = 0;
        foreach ($sectionsData as $sectionData) {
            $module = $sectionData['module'] ?? ($sectionData['module_key'] ?? 'module');
            
            // Save ALL fields from YAML in content JSON (not just standard fields)
            $contentFields = array_filter($sectionData, fn($v) => $v !== null);

            Section::create([
                'page_id' => $page->id,
                'order' => $order++,
                'type' => $sectionData['type'] ?? 'module',
                'module' => $module,
                'module_key' => $sectionData['module_key'] ?? $module,
                'heading' => $sectionData['heading'] ?? null,
                'subheading' => $sectionData['subheading'] ?? null,
                'description' => $sectionData['description'] ?? null,
                'raw_html' => $sectionData['raw_html'] ?? null,
                'class' => $sectionData['class'] ?? null,
                'identifier' => $sectionData['id'] ?? null,
                'settings' => isset($sectionData['settings']) && is_array($sectionData['settings'])
                    ? $sectionData['settings']
                    : null,
                'content' => $contentFields,
            ]);
        }
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

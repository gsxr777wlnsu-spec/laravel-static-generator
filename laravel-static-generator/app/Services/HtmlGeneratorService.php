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
        private PageRepositoryInterface $pageRepository
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

        $templatePath = $this->resolvePageTemplatePath($page);

        $html = View::make($templatePath, $data)->render();

        return $this->normalizeGoogleMapEmbeds($html);
    }

    public function generateSite(Site $site, ?callable $onProgress = null): array
    {
        $pages = $this->pageRepository->getActiveBySite($site);
        $generatedFiles = [];
        $errors = [];

        $totalSteps = $pages->count() + 2; // +1sitemap, +1robots
        $currentStep = 0;

        foreach ($pages as $page) {
            try {
                $html = $this->generatePage($page);
                
                $filename = $page->slug === 'index' || $page->slug === '' 
                    ? 'index.html' 
                    : $page->slug . '.html';
                
                $path = "site{$site->id}/{$filename}";
                
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

        $robotsPath = "site{$site->id}/robots.txt";
        Storage::disk('generated')->put($robotsPath, $this->generateRobotsTxt($site));
        $generatedFiles[] = $robotsPath;

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
            $url = $page->canonical ?? "https://{$site->domain}/{$page->slug}";
            
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

    public function generatePreview(Page $page): array
    {
        $html = $this->generatePage($page);
        
        // Rewrite asset paths to be relative for preview serving
        $html = $this->rewriteAssetPathsForPreview($html);
        
        $previewToken = Str::random(32);
        
        $previewDir = "preview/{$previewToken}";
        
        $filename = $page->slug === 'index' || $page->slug === '' 
            ? 'index.html' 
            : $page->slug . '.html';
        
        Storage::disk('generated')->put("{$previewDir}/{$filename}", $html);
        
        // Copy assets from the site's generated directory to preview directory
        $this->copyAssetsToPreview($page->site_id, $previewToken);
        
        return [
            'token' => $previewToken,
            'url' => "/api/preview/{$previewToken}/{$filename}",
            'expires_at' => now()->addMinutes(30)->toDateTimeString(),
        ];
    }

    private function rewriteAssetPathsForPreview(string $html): string
    {
        $html = preg_replace('/(href|src)="\/assets\/([^"]*)"/', '$1="assets/$2"', $html);
        $html = preg_replace('/(href|src)="\/js\/([^"]*)"/', '$1="js/$2"', $html);
        
        return $html;
    }

    private function copyAssetsToPreview(int $siteId, string $previewToken): void
    {
        $generatedDisk = Storage::disk('generated');
        $sourceAssetPath = "site{$siteId}/assets";

        if (!$generatedDisk->exists($sourceAssetPath)) {
            $sourceAssetPath = 'site1/assets';
        }

        if (!$generatedDisk->exists($sourceAssetPath)) {
            return;
        }

        $previewAssetsPath = "preview/{$previewToken}/assets";
        $this->copyStorageDirectory('generated', $sourceAssetPath, 'generated', $previewAssetsPath);
        $this->ensureMainScriptAlias('generated', $previewAssetsPath);
    }

    private function copyAssetsToSite(int $siteId): void
    {
        $targetPath = "site{$siteId}/assets";

        $sourceFromSitesDisk = "{$siteId}/assets";
        if (Storage::disk('sites')->exists($sourceFromSitesDisk)) {
            $this->copyStorageDirectory('sites', $sourceFromSitesDisk, 'generated', $targetPath);
            $this->ensureMainScriptAlias('generated', $targetPath);
            return;
        }

        $fallbackPath = 'site1/assets';
        if ($fallbackPath === $targetPath || !Storage::disk('generated')->exists($fallbackPath)) {
            return;
        }

        $this->copyStorageDirectory('generated', $fallbackPath, 'generated', $targetPath);
        $this->ensureMainScriptAlias('generated', $targetPath);
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

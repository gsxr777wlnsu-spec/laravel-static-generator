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

        return View::make($templatePath, $data)->render();
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
        $previewBaseDir = storage_path("generated/preview/{$previewToken}");
        
        $sourceAssetPath = storage_path("generated/site{$siteId}/assets");
        
        if (!is_dir($sourceAssetPath)) {
            $sourceAssetPath = storage_path("generated/site1/assets");
        }
        
        if (!is_dir($sourceAssetPath)) {
            return;
        }
        
        if (!is_dir($previewBaseDir)) {
            mkdir($previewBaseDir, 0755, true);
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceAssetPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $relativePath = 'assets/' . $files->getSubPathname();
            $targetFile = $previewBaseDir . DIRECTORY_SEPARATOR . $relativePath;
            
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
    }

    private function copyAssetsToSite(int $siteId): void
    {
        $targetBaseDir = storage_path("generated/site{$siteId}/assets");
        $sourceAssetPath = storage_path("generated/site1/assets");

        if (!is_dir($sourceAssetPath)) {
            return;
        }

        if (!is_dir($targetBaseDir)) {
            mkdir($targetBaseDir, 0755, true);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceAssetPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $targetFile = $targetBaseDir . DIRECTORY_SEPARATOR . $files->getSubPathname();

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
    }

    public function cleanupExpiredPreviews(): int
    {
        $previewDir = 'preview';
        $allDirs = Storage::disk('generated')->directories($previewDir);
        
        $cleaned = 0;
        foreach ($allDirs as $dir) {
            $dirName = basename($dir);
            $createdAt = Storage::disk('generated')->getMetadata($dir)['lastModified'] ?? null;
            
            if ($createdAt && now()->diffInMinutes(now()->createFromTimestamp($createdAt)) > 30) {
                Storage::disk('generated')->deleteDirectory($dir);
                $cleaned++;
            }
        }
        
        return $cleaned;
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
}

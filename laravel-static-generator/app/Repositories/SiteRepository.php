<?php

namespace App\Repositories;

use App\Contracts\SiteRepositoryInterface;
use App\Models\AuditLog;
use App\Models\Deployment;
use App\Models\Media;
use App\Models\Section;
use App\Models\Site;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class SiteRepository implements SiteRepositoryInterface
{
    public function create(array $data): Site
    {
        $site = Site::create($data);
        $this->initializeSiteDirectoryStructure($site);
        return $site;
    }

    public function update(Site $site, array $data): Site
    {
        $site->update($data);
        return $site->fresh();
    }

    public function delete(Site $site): bool
    {
        $siteId = (int) $site->id;
        $outputPath = (string) ($site->output_path ?? '');

        $pageIds = Page::where('site_id', $siteId)->pluck('id')->all();
        $mediaIds = Media::where('site_id', $siteId)->pluck('id')->all();
        $deploymentIds = Deployment::where('site_id', $siteId)->pluck('id')->all();
        $sectionIds = empty($pageIds)
            ? []
            : Section::whereIn('page_id', $pageIds)->pluck('id')->all();

        $deleted = DB::transaction(function () use ($site, $siteId, $pageIds, $sectionIds, $mediaIds, $deploymentIds): bool {
            $this->deleteAuditLogsForAuditable(Site::class, [$siteId]);
            $this->deleteAuditLogsForAuditable(Page::class, $pageIds);
            $this->deleteAuditLogsForAuditable(Section::class, $sectionIds);
            $this->deleteAuditLogsForAuditable(Media::class, $mediaIds);
            $this->deleteAuditLogsForAuditable(Deployment::class, $deploymentIds);

            return $site->delete();
        });

        if ($deleted) {
            $this->cleanupSiteFilesystemArtifacts($siteId, $outputPath, (string) $site->domain);
        }

        return $deleted;
    }

    public function findById(int $id): ?Site
    {
        return Site::find($id);
    }

    public function findByDomain(string $domain): ?Site
    {
        return Site::where('domain', $domain)->first();
    }

    public function getActive(): Collection
    {
        return Site::where('status', 'active')->get();
    }

    public function getAll(): Collection
    {
        return Site::all();
    }

    public function clone(Site $site, array $overrides): Site
    {
        $newSite = $this->create(array_merge(
            $site->only(['name', 'template_set', 'locale', 'default_locale']),
            $overrides
        ));

        // Note: create() already called initializeSiteDirectoryStructure

        foreach ($site->pages as $page) {
            $newPage = $newSite->pages()->create($page->only([
                'slug', 'title', 'meta_title', 'meta_description', 
                'meta_keywords', 'canonical', 'og_data', 'json_ld', 
                'status', 'locale'
            ]));

            foreach ($page->sections as $section) {
                $newPage->sections()->create($section->only(['type', 'content', 'order']));
            }
        }

        return $newSite;
    }

    private function initializeSiteDirectoryStructure(Site $site): void
    {
        $directories = [
            "{$site->id}/assets/images/logo",
            "{$site->id}/assets/images/upload",
            "{$site->id}/assets/css",
            "{$site->id}/assets/js",
        ];

        foreach ($directories as $dir) {
            if (!Storage::disk('sites')->exists($dir)) {
                Storage::disk('sites')->makeDirectory($dir);
            }
        }
    }

    private function deleteAuditLogsForAuditable(string $auditableType, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        AuditLog::where('auditable_type', $auditableType)
            ->whereIn('auditable_id', $ids)
            ->delete();
    }

    private function cleanupSiteFilesystemArtifacts(int $siteId, string $outputPath, string $siteDomain): void
    {
        $this->deleteDirectoryOnDisk('sites', (string) $siteId);
        $this->deleteDirectoryOnDisk('generated', 'site' . $siteId);
        $this->deleteDirectoryOnDisk('generated', (string) $siteId);
        $this->deleteDirectoryOnDisk('staging', 'site' . $siteId);
        $this->deleteDirectoryOnDisk('staging', (string) $siteId);

        $normalizedOutputPath = $this->normalizeOutputPathForGeneratedDisk($outputPath);
        if ($normalizedOutputPath !== null) {
            $this->deleteDirectoryOnDisk('generated', $normalizedOutputPath);
        }

        $this->cleanupAiTemplateDirectory($siteDomain);

        $templatePath = resource_path("views/templates/site{$siteId}");
        try {
            if (is_dir($templatePath) && !File::deleteDirectory($templatePath)) {
                \Log::warning('Failed to delete site template directory', [
                    'site_id' => $siteId,
                    'path' => $templatePath,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete site template directory', [
                'site_id' => $siteId,
                'path' => $templatePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteDirectoryOnDisk(string $disk, string $path): void
    {
        $normalized = trim($path, '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return;
        }

        try {
            if (Storage::disk($disk)->exists($normalized) && !Storage::disk($disk)->deleteDirectory($normalized)) {
                \Log::warning('Failed to delete site artifact directory', [
                    'disk' => $disk,
                    'path' => $normalized,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete site artifact directory', [
                'disk' => $disk,
                'path' => $normalized,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeOutputPathForGeneratedDisk(string $outputPath): ?string
    {
        $normalized = trim(str_replace('\\', '/', $outputPath));
        if ($normalized === '') {
            return null;
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'generated/')) {
            $normalized = substr($normalized, strlen('generated/'));
        }

        $normalized = trim($normalized, '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        return $normalized;
    }

    private function cleanupAiTemplateDirectory(string $siteDomain): void
    {
        $domain = strtolower(trim($siteDomain));
        if ($domain === '' || str_contains($domain, '..')) {
            return;
        }

        $templatesRoot = (string) config(
            'services.ai_agent.templates_root',
            storage_path('import-deploy/md/test/raw_html')
        );

        $templatesRoot = rtrim(str_replace('\\', '/', $templatesRoot), '/');
        if ($templatesRoot === '' || !is_dir($templatesRoot)) {
            return;
        }

        $targetDirectory = $templatesRoot . '/' . $domain;
        $normalizedTarget = str_replace('\\', '/', $targetDirectory);
        if (!is_dir($normalizedTarget)) {
            return;
        }

        if (!str_starts_with($normalizedTarget, $templatesRoot . '/')) {
            return;
        }

        if (!$this->isSafeToDeleteTemplateDirectory($normalizedTarget, $domain)) {
            return;
        }

        try {
            if (!File::deleteDirectory($normalizedTarget)) {
                \Log::warning('Failed to delete AI template directory', [
                    'domain' => $domain,
                    'path' => $normalizedTarget,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete AI template directory', [
                'domain' => $domain,
                'path' => $normalizedTarget,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isSafeToDeleteTemplateDirectory(string $directory, string $domain): bool
    {
        $files = glob($directory . '/*-raw_html.md') ?: [];
        if ($files === []) {
            return true;
        }

        foreach ($files as $file) {
            try {
                $data = Yaml::parseFile($file);
            } catch (\Throwable) {
                continue;
            }

            if (!is_array($data)) {
                continue;
            }

            $fileDomain = strtolower(trim((string) ($data['domain'] ?? '')));
            if ($fileDomain === $domain) {
                return true;
            }
        }

        return false;
    }

    public function cloneFromStaging(string $stagingPath, array $siteData): ?Site
    {
        if (!File::exists($stagingPath)) {
            throw new \Exception("Staging path does not exist: {$stagingPath}");
        }

        $files = Storage::disk('generated')->files($stagingPath);
        
        if (empty($files)) {
            throw new \Exception("No HTML files found in staging path: {$stagingPath}");
        }

        $newSite = $this->create([
            'name' => $siteData['name'],
            'domain' => $siteData['domain'],
            'template_set' => $siteData['template_set'] ?? 'base',
            'output_path' => $siteData['output_path'] ?? "generated/{$siteData['domain']}",
            'status' => 'draft',
            'locale' => $siteData['locale'] ?? 'en',
        ]);

        $htmlFiles = array_filter($files, function ($file) {
            return Str::endsWith($file, '.html');
        });

        foreach ($htmlFiles as $file) {
            $slug = basename($file, '.html');
            $slug = $slug === 'index' ? '' : $slug;

            $newSite->pages()->create([
                'slug' => $slug,
                'title' => Str::title(str_replace('-', ' ', $slug)),
                'status' => 'published',
            ]);
        }

        return $newSite;
    }
}

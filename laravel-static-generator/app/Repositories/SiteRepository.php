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
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $lastCleanupIssues = [];

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
        $this->lastCleanupIssues = [];

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
            $this->detectDatabaseResiduesAfterDelete($siteId, $pageIds, $sectionIds, $mediaIds, $deploymentIds);
        }

        return $deleted;
    }

    public function getLastCleanupIssues(): array
    {
        return $this->lastCleanupIssues;
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
                $this->recordCleanupIssue('filesystem', 'site-template', $templatePath, 'Failed to delete template directory');
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete site template directory', [
                'site_id' => $siteId,
                'path' => $templatePath,
                'error' => $e->getMessage(),
            ]);
            $this->recordCleanupIssue('filesystem', 'site-template', $templatePath, $e->getMessage());
        }

        if (is_dir($templatePath)) {
            $this->recordCleanupIssue('filesystem', 'site-template', $templatePath, 'Template directory still exists after cleanup');
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
                $this->recordCleanupIssue(
                    'filesystem',
                    'disk-directory',
                    "{$disk}:{$normalized}",
                    'Storage deleteDirectory returned false'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete site artifact directory', [
                'disk' => $disk,
                'path' => $normalized,
                'error' => $e->getMessage(),
            ]);
            $this->recordCleanupIssue(
                'filesystem',
                'disk-directory',
                "{$disk}:{$normalized}",
                $e->getMessage()
            );
            return;
        }

        try {
            if (Storage::disk($disk)->exists($normalized)) {
                $this->recordCleanupIssue(
                    'filesystem',
                    'disk-directory',
                    "{$disk}:{$normalized}",
                    'Directory still exists after cleanup'
                );
            }
        } catch (\Throwable $e) {
            $this->recordCleanupIssue(
                'filesystem',
                'disk-directory',
                "{$disk}:{$normalized}",
                "Exists check failed: {$e->getMessage()}"
            );
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
                $this->recordCleanupIssue('filesystem', 'ai-template', $normalizedTarget, 'Failed to delete AI template directory');
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete AI template directory', [
                'domain' => $domain,
                'path' => $normalizedTarget,
                'error' => $e->getMessage(),
            ]);
            $this->recordCleanupIssue('filesystem', 'ai-template', $normalizedTarget, $e->getMessage());
        }

        if (is_dir($normalizedTarget)) {
            $this->recordCleanupIssue('filesystem', 'ai-template', $normalizedTarget, 'AI template directory still exists after cleanup');
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

    /**
     * @param  array<int, int>  $pageIds
     * @param  array<int, int>  $sectionIds
     * @param  array<int, int>  $mediaIds
     * @param  array<int, int>  $deploymentIds
     */
    private function detectDatabaseResiduesAfterDelete(
        int $siteId,
        array $pageIds,
        array $sectionIds,
        array $mediaIds,
        array $deploymentIds
    ): void {
        if (Site::whereKey($siteId)->exists()) {
            $this->recordCleanupIssue('database', 'sites', "site_id={$siteId}", 'Site row still exists after delete');
        }

        if (Page::where('site_id', $siteId)->exists()) {
            $this->recordCleanupIssue('database', 'pages', "site_id={$siteId}", 'Page rows still exist after site delete');
        }

        if (Media::where('site_id', $siteId)->exists()) {
            $this->recordCleanupIssue('database', 'media', "site_id={$siteId}", 'Media rows still exist after site delete');
        }

        if (Deployment::where('site_id', $siteId)->exists()) {
            $this->recordCleanupIssue('database', 'deployments', "site_id={$siteId}", 'Deployment rows still exist after site delete');
        }

        if ($pageIds !== [] && Section::whereIn('page_id', $pageIds)->exists()) {
            $this->recordCleanupIssue('database', 'sections', "page_ids=" . implode(',', $pageIds), 'Section rows still exist after site delete');
        }

        if ($pageIds !== []) {
            $remainingPageAuditLogs = AuditLog::where('auditable_type', Page::class)->whereIn('auditable_id', $pageIds)->count();
            if ($remainingPageAuditLogs > 0) {
                $this->recordCleanupIssue('database', 'audit_logs', 'page_audit_logs', "Remaining page audit logs: {$remainingPageAuditLogs}");
            }
        }

        if ($sectionIds !== []) {
            $remainingSectionAuditLogs = AuditLog::where('auditable_type', Section::class)->whereIn('auditable_id', $sectionIds)->count();
            if ($remainingSectionAuditLogs > 0) {
                $this->recordCleanupIssue('database', 'audit_logs', 'section_audit_logs', "Remaining section audit logs: {$remainingSectionAuditLogs}");
            }
        }

        if ($mediaIds !== []) {
            $remainingMediaAuditLogs = AuditLog::where('auditable_type', Media::class)->whereIn('auditable_id', $mediaIds)->count();
            if ($remainingMediaAuditLogs > 0) {
                $this->recordCleanupIssue('database', 'audit_logs', 'media_audit_logs', "Remaining media audit logs: {$remainingMediaAuditLogs}");
            }
        }

        if ($deploymentIds !== []) {
            $remainingDeploymentAuditLogs = AuditLog::where('auditable_type', Deployment::class)->whereIn('auditable_id', $deploymentIds)->count();
            if ($remainingDeploymentAuditLogs > 0) {
                $this->recordCleanupIssue('database', 'audit_logs', 'deployment_audit_logs', "Remaining deployment audit logs: {$remainingDeploymentAuditLogs}");
            }
        }

        $remainingSiteAuditLogs = AuditLog::where('auditable_type', Site::class)->where('auditable_id', $siteId)->count();
        if ($remainingSiteAuditLogs > 0) {
            $this->recordCleanupIssue('database', 'audit_logs', "site_id={$siteId}", "Remaining site audit logs: {$remainingSiteAuditLogs}");
        }
    }

    private function recordCleanupIssue(string $scope, string $resource, string $path, string $message): void
    {
        $issue = [
            'scope' => $scope,
            'resource' => $resource,
            'path' => $path,
            'message' => $message,
        ];

        if (in_array($issue, $this->lastCleanupIssues, true)) {
            return;
        }

        $this->lastCleanupIssues[] = $issue;
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

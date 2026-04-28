<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\PageRepositoryInterface;
use App\Contracts\SeoServiceInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Contracts\SftpClientInterface;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Services\PageTemplatePresetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct(
        private PageRepositoryInterface $pages,
        private SiteRepositoryInterface $sites,
        private SeoServiceInterface $seo,
        private HtmlGeneratorInterface $generator,
        private AuditLogServiceInterface $audit,
        private PageTemplatePresetService $templatePresets,
        private SftpClientInterface $sftp
    ) {}

    public function index(Request $request): JsonResponse
    {
        $siteId = $request->query('site_id');
        
        if ($siteId) {
            $site = $this->sites->findById($siteId);
            if (!$site) {
                return response()->json(['error' => 'Site not found'], 404);
            }
            $pages = Page::where('site_id', $siteId)->with('sections')->get();
        } else {
            $pages = Page::with('sections')->get();
        }

        return response()->json($pages);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'slug' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'canonical' => 'nullable|string|max:500',
            'og_data' => 'nullable|array',
            'json_ld' => 'nullable|array',
            'status' => 'nullable|in:published,draft,archived',
            'locale' => 'nullable|string|max:10',
            'template_key' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $templateKey = $this->templatePresets->normalizeKey($data['template_key'] ?? null);
        $data['template_key'] = $templateKey;
        $site = $this->sites->findById($data['site_id']);

        if ($this->seo->checkDuplicateSlugs($site, $data['slug'])) {
            return response()->json(['error' => 'Slug already exists for this site'], 422);
        }

        $data = $this->applyAutoCanonicalOnStore($data, $site);

        $warnings = [];
        if (isset($data['meta_title'])) {
            $validation = $this->seo->validateMetaTitle($data['meta_title']);
            if (!$validation['valid']) {
                $warnings[] = $validation['message'];
            }
        }

        if (isset($data['meta_description'])) {
            $validation = $this->seo->validateMetaDescription($data['meta_description']);
            if (!$validation['valid']) {
                $warnings[] = $validation['message'];
            }
        }

        $page = DB::transaction(function () use ($data, $templateKey) {
            $page = $this->pages->create($data);
            $this->bootstrapPageSections($page, $templateKey, false);

            return $page;
        });

        $page = $page->fresh(['sections']);

        $sitemapSyncWarning = $this->syncSitemapArtifactsToRemote($site);
        if ($sitemapSyncWarning !== null) {
            $warnings[] = $sitemapSyncWarning;
        }

        $this->audit->log('page.created', Page::class, $page->id, null, $page->toArray());

        return response()->json([
            'page' => $page,
            'warnings' => $warnings,
            'sections_bootstrapped' => $page->sections->count(),
        ], 201);
    }

    public function pageTemplates(): JsonResponse
    {
        return response()->json([
            'templates' => $this->templatePresets->listForUi(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $page = Page::with('sections')->find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        return response()->json($page);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'slug' => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'canonical' => 'nullable|string|max:500',
            'og_data' => 'nullable|array',
            'json_ld' => 'nullable|array',
            'status' => 'sometimes|in:published,draft,archived',
            'template_key' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $site = $this->sites->findById($page->site_id);

        if (isset($data['template_key'])) {
            $data['template_key'] = $this->templatePresets->normalizeKey($data['template_key']);
        }
        
        if (isset($data['slug'])) {
            if ($this->seo->checkDuplicateSlugs($site, $data['slug'], $page->id)) {
                return response()->json(['error' => 'Slug already exists for this site'], 422);
            }
        }

        $data = $this->applyAutoCanonicalOnUpdate($data, $page, $site);

        $oldValues = $page->toArray();
        $page = $this->pages->update($page, $data);
        
        $this->audit->log('page.updated', Page::class, $page->id, $oldValues, $page->toArray());

        return response()->json($page);
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::with('site')->find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $site = $page->site;
        if (!$site) {
            return response()->json(['error' => 'Site not found for page'], 404);
        }

        $filename = $this->resolveGeneratedFilename($page);

        if ($this->hasSftpConfiguration($site)) {
            $remoteRoot = $this->resolveRemoteRootPath($site);
            $remoteFilePath = trim($remoteRoot, '/') . '/' . $filename;

            try {
                if (!$this->sftp->connect($site)) {
                    return response()->json([
                        'error' => 'Remote delete failed',
                        'message' => 'Could not connect to SFTP server for remote page deletion.',
                    ], 422);
                }

                if (!$this->sftp->deleteFile($site, $remoteFilePath)) {
                    return response()->json([
                        'error' => 'Remote delete failed',
                        'message' => "Could not delete remote page file: {$remoteFilePath}",
                    ], 422);
                }
            } finally {
                $this->sftp->disconnect();
            }
        }

        $sectionIds = Section::where('page_id', $page->id)->pluck('id')->all();
        $this->audit->log('page.deleted', Page::class, $page->id, $page->toArray(), null);

        DB::transaction(function () use ($page, $sectionIds): void {
            AuditLog::where('auditable_type', Page::class)
                ->where('auditable_id', $page->id)
                ->delete();

            if (!empty($sectionIds)) {
                AuditLog::where('auditable_type', Section::class)
                    ->whereIn('auditable_id', $sectionIds)
                    ->delete();
            }

            $this->pages->delete($page);
        });

        $this->deletePageArtifactsFromDisk($site, $filename);

        $warning = $this->syncSitemapArtifactsToRemote($site);
        if ($warning !== null) {
            return response()->json([
                'message' => 'Page deleted successfully',
                'warning' => $warning,
            ]);
        }

        return response()->json(['message' => 'Page deleted successfully']);
    }

    public function preview(int $id): JsonResponse
    {
        $page = Page::with('sections')->find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $html = $this->generator->generatePage($page);

        return response()->json(['html' => $html]);
    }

    public function generatePreviewToken(Request $request, int $id): JsonResponse
    {
        $page = Page::with('sections', 'site')->find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $result = $this->generator->generatePreview($page);

        return response()->json([
            'preview_url' => $result['url'],
            'expires_at' => $result['expires_at'],
        ]);
    }

    public function bootstrapSections(Request $request, int $id): JsonResponse
    {
        $page = Page::with('sections')->find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'template_key' => 'required|string|max:100',
            'replace_existing' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $templateKey = $this->templatePresets->normalizeKey($data['template_key']);
        $replaceExisting = (bool) ($data['replace_existing'] ?? true);

        DB::transaction(function () use ($page, $templateKey, $replaceExisting) {
            $this->bootstrapPageSections($page, $templateKey, $replaceExisting);
            $page->update(['template_key' => $templateKey]);
        });

        $page = $page->fresh(['sections']);

        return response()->json([
            'message' => 'Sections bootstrapped successfully',
            'page' => $page,
            'sections_bootstrapped' => $page->sections->count(),
        ]);
    }

    public function servePreview(string $token, ?string $path = null)
    {
        if (!$path) {
            $path = 'index.html';
        }

        $fullPath = "preview/{$token}/{$path}";
        
        if (!Storage::disk('generated')->exists($fullPath)) {
            abort(404);
        }

        $content = Storage::disk('generated')->get($fullPath);
        $mimeType = Storage::disk('generated')->mimeType($fullPath);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function bootstrapPageSections(Page $page, string $templateKey, bool $replaceExisting = false): void
    {
        if ($replaceExisting) {
            Section::where('page_id', $page->id)->delete();
        } elseif (Section::where('page_id', $page->id)->exists()) {
            return;
        }

        $presetSections = $this->templatePresets->buildPresetSections($templateKey, $page);

        foreach ($presetSections as $index => $sectionData) {
            Section::create([
                'page_id' => $page->id,
                'type' => $sectionData['type'],
                'content' => $sectionData['content'],
                'order' => $index,
            ]);
        }
    }

    private function applyAutoCanonicalOnStore(array $data, ?\App\Models\Site $site): array
    {
        if (!$site) {
            return $data;
        }

        $canonical = trim((string) ($data['canonical'] ?? ''));
        if ($canonical === '') {
            $data['canonical'] = $this->buildCanonicalUrl((string) $site->domain, (string) ($data['slug'] ?? ''));
        } else {
            $data['canonical'] = $canonical;
        }

        return $data;
    }

    private function applyAutoCanonicalOnUpdate(array $data, Page $page, ?\App\Models\Site $site): array
    {
        if (!$site) {
            return $data;
        }

        $currentSlug = (string) $page->slug;
        $nextSlug = array_key_exists('slug', $data) ? (string) $data['slug'] : $currentSlug;

        $currentAutoCanonical = $this->buildCanonicalUrl((string) $site->domain, $currentSlug);
        $currentLegacyAutoCanonical = $this->buildLegacyCanonicalUrl((string) $site->domain, $currentSlug);
        $nextAutoCanonical = $this->buildCanonicalUrl((string) $site->domain, $nextSlug);
        $currentCanonical = trim((string) ($page->canonical ?? ''));

        if (array_key_exists('canonical', $data)) {
            $requestedCanonical = trim((string) ($data['canonical'] ?? ''));
            if (
                $requestedCanonical === '' ||
                $requestedCanonical === $currentAutoCanonical ||
                $requestedCanonical === $currentLegacyAutoCanonical
            ) {
                $data['canonical'] = $nextAutoCanonical;
            } else {
                $data['canonical'] = $requestedCanonical;
            }

            return $data;
        }

        if (
            $currentCanonical === '' ||
            $currentCanonical === $currentAutoCanonical ||
            $currentCanonical === $currentLegacyAutoCanonical
        ) {
            $data['canonical'] = $nextAutoCanonical;
        }

        return $data;
    }

    private function buildCanonicalUrl(string $siteDomain, string $slug): string
    {
        $domain = $this->normalizeDomainForCanonical($siteDomain);
        $normalizedSlug = trim($slug);
        $normalizedSlug = trim($normalizedSlug, '/');

        if ($normalizedSlug === '' || Str::lower($normalizedSlug) === 'index' || Str::lower($normalizedSlug) === 'index.html') {
            return $domain . '/';
        }

        if (!Str::endsWith(Str::lower($normalizedSlug), '.html')) {
            $normalizedSlug .= '.html';
        }

        return $domain . '/' . $normalizedSlug;
    }

    private function buildLegacyCanonicalUrl(string $siteDomain, string $slug): string
    {
        $domain = $this->normalizeDomainForCanonical($siteDomain);
        $normalizedSlug = trim($slug);
        $normalizedSlug = trim($normalizedSlug, '/');

        if ($normalizedSlug === '' || Str::lower($normalizedSlug) === 'index' || Str::lower($normalizedSlug) === 'index.html') {
            return $domain . '/';
        }

        return $domain . '/' . preg_replace('/\.html$/i', '', $normalizedSlug);
    }

    private function normalizeDomainForCanonical(string $siteDomain): string
    {
        $domain = trim($siteDomain);
        if ($domain === '') {
            return 'https://';
        }

        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . $domain;
        }

        return rtrim($domain, '/');
    }

    private function hasSftpConfiguration(Site $site): bool
    {
        $credentials = $site->getSftpCredentials();

        return trim((string) ($credentials['host'] ?? '')) !== ''
            && trim((string) ($credentials['username'] ?? '')) !== '';
    }

    private function resolveRemoteRootPath(Site $site): string
    {
        $remotePath = trim((string) ($site->sftp_remote_path ?? ''), '/');
        if ($remotePath !== '') {
            return $remotePath;
        }

        return trim('/var/www/' . trim((string) $site->domain, '/'), '/');
    }

    private function resolveGeneratedFilename(Page $page): string
    {
        $slug = trim((string) $page->slug);
        if ($slug === '' || Str::lower($slug) === 'index' || Str::lower($slug) === 'index.html') {
            return 'index.html';
        }

        if (!Str::endsWith(Str::lower($slug), '.html')) {
            $slug .= '.html';
        }

        return ltrim($slug, '/');
    }

    private function deletePageArtifactsFromDisk(Site $site, string $filename): void
    {
        $this->deleteFileOnDisk('generated', "site{$site->id}/{$filename}");
        $this->deleteFileOnDisk('generated', "{$site->id}/{$filename}");
        $this->deleteFileOnDisk('staging', "site{$site->id}/{$filename}");
        $this->deleteFileOnDisk('staging', "{$site->id}/{$filename}");

        $outputPath = $this->normalizeOutputPathForGeneratedDisk((string) ($site->output_path ?? ''));
        if ($outputPath !== null) {
            $this->deleteFileOnDisk('generated', "{$outputPath}/{$filename}");
        }
    }

    private function deleteFileOnDisk(string $disk, string $path): void
    {
        $normalized = trim($path, '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return;
        }

        try {
            if (Storage::disk($disk)->exists($normalized)) {
                Storage::disk($disk)->delete($normalized);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete page artifact file', [
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

    private function syncSitemapArtifactsToRemote(Site $site): ?string
    {
        if (!$this->hasSftpConfiguration($site)) {
            return null;
        }

        $sitemapPage = Page::where('site_id', $site->id)
            ->where('status', 'published')
            ->whereIn('slug', ['sitemap', 'sitemap.html'])
            ->orderBy('id')
            ->first();

        if (!$sitemapPage) {
            return null;
        }

        try {
            $sitemapXmlPath = "site{$site->id}/sitemap.xml";
            Storage::disk('generated')->put($sitemapXmlPath, $this->generator->generateSitemap($site));

            $sitemapHtmlFilename = $this->resolveGeneratedFilename($sitemapPage);
            $sitemapHtmlPath = "site{$site->id}/{$sitemapHtmlFilename}";
            Storage::disk('generated')->put($sitemapHtmlPath, $this->generator->generatePage($sitemapPage));

            $remoteRoot = trim($this->resolveRemoteRootPath($site), '/');
            if ($remoteRoot === '') {
                return 'Automatic sitemap deploy skipped: remote path is empty.';
            }

            if (!$this->sftp->connect($site)) {
                return 'Automatic sitemap deploy failed: could not connect to SFTP.';
            }

            $remoteSitemapXmlPath = $remoteRoot . '/sitemap.xml';
            if (!$this->sftp->uploadFile($site, $sitemapXmlPath, $remoteSitemapXmlPath)) {
                return "Automatic sitemap deploy failed: could not upload {$remoteSitemapXmlPath}.";
            }

            $remoteSitemapHtmlPath = $remoteRoot . '/' . $sitemapHtmlFilename;
            if (!$this->sftp->uploadFile($site, $sitemapHtmlPath, $remoteSitemapHtmlPath)) {
                return "Automatic sitemap deploy failed: could not upload {$remoteSitemapHtmlPath}.";
            }

            return null;
        } catch (\Throwable $e) {
            \Log::warning('Automatic sitemap deploy failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return 'Automatic sitemap deploy failed: ' . $e->getMessage();
        } finally {
            try {
                $this->sftp->disconnect();
            } catch (\Throwable) {
                // Ignore disconnect issues.
            }
        }
    }
}

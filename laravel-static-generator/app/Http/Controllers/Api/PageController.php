<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\HtmlGeneratorInterface;
use App\Contracts\PageRepositoryInterface;
use App\Contracts\SeoServiceInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Section;
use App\Services\PageTemplatePresetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    public function __construct(
        private PageRepositoryInterface $pages,
        private SiteRepositoryInterface $sites,
        private SeoServiceInterface $seo,
        private HtmlGeneratorInterface $generator,
        private AuditLogServiceInterface $audit,
        private PageTemplatePresetService $templatePresets
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

        if (isset($data['template_key'])) {
            $data['template_key'] = $this->templatePresets->normalizeKey($data['template_key']);
        }
        
        if (isset($data['slug'])) {
            $site = $this->sites->findById($page->site_id);
            if ($this->seo->checkDuplicateSlugs($site, $data['slug'], $page->id)) {
                return response()->json(['error' => 'Slug already exists for this site'], 422);
            }
        }

        $oldValues = $page->toArray();
        $page = $this->pages->update($page, $data);
        
        $this->audit->log('page.updated', Page::class, $page->id, $oldValues, $page->toArray());

        return response()->json($page);
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $this->audit->log('page.deleted', Page::class, $page->id, $page->toArray(), null);
        
        $this->pages->delete($page);

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
}

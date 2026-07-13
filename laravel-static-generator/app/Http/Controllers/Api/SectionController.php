<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\SectionServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    public function __construct(
        private SectionServiceInterface $sections,
        private AuditLogServiceInterface $audit
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pageId = $request->query('page_id');
        
        if ($pageId) {
            $sections = Section::where('page_id', $pageId)->orderBy('order')->get();
        } else {
            $sections = Section::orderBy('page_id')->orderBy('order')->get();
        }

        return response()->json($sections);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:pages,id',
            'type' => 'required|string|in:module,faq,hero,text,list,table,gallery,cta',
            'content' => 'required|array',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        try {
            $section = $this->sections->add(
                $data['page_id'],
                $data['type'],
                $data['content'],
                $data['order'] ?? null
            );
            
            $this->audit->log('section.created', Section::class, $section->id, null, $section->toArray());

            return response()->json($section, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        return response()->json($section);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|in:module,faq,hero,text,list,table,gallery,cta',
            'content' => 'sometimes|array',
            'order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $oldValues = $section->toArray();
            if ($request->has('content')) {
                SectionHistory::create([
                    'section_id' => $section->id,
                    'page_id' => $section->page_id,
                    'type' => $section->type,
                    'content' => $section->content ?? [],
                    'order' => (int) $section->order,
                ]);
                $this->pruneSectionHistory($section->id);
            }

            $section = $this->sections->update($section, $validator->validated());
            
            $this->audit->log('section.updated', Section::class, $section->id, $oldValues, $section->toArray());

            return response()->json($section);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $this->audit->log('section.deleted', Section::class, $section->id, $section->toArray(), null);
        
        $this->sections->delete($section);

        return response()->json(['message' => 'Section deleted successfully']);
    }

    public function history(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $histories = SectionHistory::where('section_id', $section->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (SectionHistory $history) => [
                'id' => $history->id,
                'label' => 'Saved ' . ($history->created_at?->toDateTimeString() ?? ''),
                'created_at' => $history->created_at?->toDateTimeString(),
            ]);

        return response()->json(['histories' => $histories]);
    }

    public function restoreHistory(int $id, int $historyId): JsonResponse
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $history = SectionHistory::where('section_id', $section->id)->find($historyId);

        if (!$history) {
            return response()->json(['error' => 'History item not found'], 404);
        }

        $oldValues = $section->toArray();

        SectionHistory::create([
            'section_id' => $section->id,
            'page_id' => $section->page_id,
            'type' => $section->type,
            'content' => $section->content ?? [],
            'order' => (int) $section->order,
        ]);
        $this->pruneSectionHistory($section->id);

        $section = $this->sections->update($section, [
            'type' => $history->type,
            'content' => $history->content,
            'order' => (int) $history->order,
        ]);

        $this->audit->log('section.restored', Section::class, $section->id, $oldValues, $section->toArray());

        return response()->json($section);
    }

    public function storeGeneratedBackgroundOverride(Request $request, int $id): JsonResponse
    {
        $section = Section::with('page.site')->find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|max:10240',
            'source_path' => 'nullable|string|max:500',
            'target_path' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $siteId = (int) ($section->page?->site_id ?? 0);
        if ($siteId <= 0) {
            return response()->json(['error' => 'Section site was not found'], 422);
        }

        $targetPath = $this->normalizeGeneratedAssetPath((string) $request->input('target_path'));
        if ($targetPath === null) {
            return response()->json(['error' => 'Target path is invalid'], 422);
        }

        $expectedMimeType = $this->mimeTypeFromExtension($targetPath);
        if ($expectedMimeType === null) {
            return response()->json([
                'error' => 'Target path extension is not supported',
            ], 422);
        }

        $file = $request->file('file');
        $sourcePath = $this->normalizeGeneratedAssetPath((string) $request->input('source_path', ''));
        if (($file === null || !$file->isValid()) && $sourcePath === null) {
            return response()->json(['error' => 'Uploaded file or source image is required'], 422);
        }

        if ($file !== null && $file->isValid()) {
            $mimeType = strtolower(trim((string) $file->getMimeType()));
            if ($mimeType === 'image/x-webp') {
                $mimeType = 'image/webp';
            }

            $contents = file_get_contents($file->getRealPath());
            $contents = $this->normalizeImageContentsForTarget($contents, $mimeType, $expectedMimeType);
        } else {
            $source = $sourcePath === null
                ? null
                : $this->readSourceAssetContents($siteId, $sourcePath);

            if ($source === null) {
                return response()->json(['error' => 'Selected source image was not found'], 422);
            }

            $mimeType = $source['mime_type'];
            if ($mimeType === 'image/x-webp') {
                $mimeType = 'image/webp';
            }

            $contents = $source['contents'];
            $contents = $this->normalizeImageContentsForTarget($contents, $mimeType, $expectedMimeType);
        }

        $storagePath = "site{$siteId}/{$targetPath}";
        if ($contents === false || $contents === null) {
            return response()->json(['error' => 'Image file could not be read'], 422);
        }

        if (!Storage::disk('sites')->put("{$siteId}/{$targetPath}", $contents)) {
            return response()->json(['error' => 'Background override could not be persisted'], 500);
        }

        Storage::disk('generated')->put($storagePath, $contents);
        $this->syncGeneratedAssetOverrideToPreviews($siteId, $targetPath, $contents);

        return response()->json([
            'message' => 'Generated background override stored successfully',
            'site_id' => $siteId,
            'target_path' => $targetPath,
            'stored_path' => $storagePath,
            'asset_url' => '/' . $targetPath,
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:pages,id',
            'order' => 'required|array',
            'order.*' => 'required|exists:sections,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $requestedIds = array_map('intval', $data['order']);
        if (count($requestedIds) !== count(array_unique($requestedIds))) {
            return response()->json([
                'error' => 'Order payload contains duplicate section IDs',
            ], 422);
        }

        $currentIds = Section::where('page_id', $data['page_id'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($requestedIds);
        sort($currentIds);

        if ($requestedIds !== $currentIds) {
            return response()->json([
                'error' => 'Order payload must include exactly all sections of the target page',
            ], 422);
        }

        $this->sections->reorder($data['page_id'], $data['order']);

        return response()->json(['message' => 'Sections reordered successfully']);
    }

    private function normalizeGeneratedAssetPath(string $path): ?string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? '';

        if (
            $normalized === ''
            || str_contains($normalized, '..')
            || !str_starts_with($normalized, 'assets/')
        ) {
            return null;
        }

        return $normalized;
    }

    private function mimeTypeFromExtension(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            default => null,
        };
    }

    private function syncGeneratedAssetOverrideToPreviews(int $siteId, string $targetPath, string $contents): void
    {
        foreach (Storage::disk('generated')->directories('preview') as $previewDirectory) {
            $siteJsonPath = trim($previewDirectory, '/') . '/.site.json';
            if (!Storage::disk('generated')->exists($siteJsonPath)) {
                continue;
            }

            $previewSiteId = (int) (json_decode(Storage::disk('generated')->get($siteJsonPath), true)['site_id'] ?? 0);
            if ($previewSiteId !== $siteId) {
                continue;
            }

            Storage::disk('generated')->put(trim($previewDirectory, '/') . '/' . $targetPath, $contents);
        }
    }

    /**
     * @return array{contents:string,mime_type:string}|null
     */
    private function readSourceAssetContents(int $siteId, string $sourcePath): ?array
    {
        $candidates = [
            ['sites', "{$siteId}/{$sourcePath}"],
            ['generated', "site{$siteId}/{$sourcePath}"],
            ['generated', "{$siteId}/{$sourcePath}"],
            ['generated', "site1/{$sourcePath}"],
            ['generated', "1/{$sourcePath}"],
        ];

        foreach ($candidates as [$diskName, $path]) {
            $disk = Storage::disk($diskName);
            if (!$disk->exists($path)) {
                continue;
            }

            return [
                'contents' => $disk->get($path),
                'mime_type' => $disk->mimeType($path) ?: '',
            ];
        }

        foreach (Storage::disk('generated')->directories('preview') as $previewDirectory) {
            $previewDirectory = trim($previewDirectory, '/');
            $siteJsonPath = "{$previewDirectory}/.site.json";
            if (!Storage::disk('generated')->exists($siteJsonPath)) {
                continue;
            }

            $previewSiteId = (int) (json_decode(Storage::disk('generated')->get($siteJsonPath), true)['site_id'] ?? 0);
            if ($previewSiteId !== $siteId) {
                continue;
            }

            $path = "{$previewDirectory}/{$sourcePath}";
            if (!Storage::disk('generated')->exists($path)) {
                continue;
            }

            return [
                'contents' => Storage::disk('generated')->get($path),
                'mime_type' => Storage::disk('generated')->mimeType($path) ?: '',
            ];
        }

        return null;
    }

    private function normalizeImageContentsForTarget(string|false $contents, string $sourceMimeType, string $targetMimeType): ?string
    {
        if ($contents === false) {
            return null;
        }

        if ($sourceMimeType === $targetMimeType) {
            return $contents;
        }

        if (
            $targetMimeType === 'image/webp'
            && in_array($sourceMimeType, ['image/jpeg', 'image/png', 'image/gif'], true)
        ) {
            return $this->convertImageContentsToWebp($contents);
        }

        return null;
    }

    private function convertImageContentsToWebp(string $contents): ?string
    {
        $image = @imagecreatefromstring($contents);
        if (!$image) {
            return null;
        }

        ob_start();
        $encoded = imagewebp($image, null, 90);
        $webp = ob_get_clean();
        imagedestroy($image);

        if (!$encoded || !is_string($webp) || $webp === '') {
            return null;
        }

        return $webp;
    }

    private function pruneSectionHistory(int $sectionId): void
    {
        $keepIds = SectionHistory::where('section_id', $sectionId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->pluck('id');

        SectionHistory::where('section_id', $sectionId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}

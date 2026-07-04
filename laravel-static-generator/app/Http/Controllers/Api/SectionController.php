<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\SectionServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Section;
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

    public function storeGeneratedBackgroundOverride(Request $request, int $id): JsonResponse
    {
        $section = Section::with('page.site')->find($id);

        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
            'target_path' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $siteId = (int) ($section->page?->site_id ?? 0);
        if ($siteId <= 0) {
            return response()->json(['error' => 'Section site was not found'], 422);
        }

        $file = $request->file('file');
        if ($file === null || !$file->isValid()) {
            return response()->json(['error' => 'Uploaded file is invalid'], 422);
        }

        $targetPath = $this->normalizeGeneratedAssetPath((string) $request->input('target_path'));
        if ($targetPath === null) {
            return response()->json(['error' => 'Target path is invalid'], 422);
        }

        $mimeType = strtolower(trim((string) $file->getMimeType()));
        if ($mimeType === 'image/x-webp') {
            $mimeType = 'image/webp';
        }

        $expectedMimeType = $this->mimeTypeFromExtension($targetPath);
        if ($expectedMimeType === null || $mimeType !== $expectedMimeType) {
            return response()->json([
                'error' => 'Uploaded file type does not match target extension',
            ], 422);
        }

        $storagePath = "site{$siteId}/{$targetPath}";
        Storage::disk('generated')->put($storagePath, file_get_contents($file->getRealPath()));

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
}

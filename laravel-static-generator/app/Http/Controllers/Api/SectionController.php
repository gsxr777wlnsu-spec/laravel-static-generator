<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\SectionServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiPromptHistory;
use App\Services\AiPromptHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AiPromptHistoryController extends Controller
{
    public function __construct(private AiPromptHistoryService $history) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->validatedScope($request);
        if ($scope instanceof JsonResponse) return $scope;
        return response()->json($this->history->list($scope));
    }

    public function record(Request $request): JsonResponse
    {
        $scope = $this->validatedScope($request, true);
        if ($scope instanceof JsonResponse) return $scope;
        return response()->json($this->history->record($scope, (string) $request->input('prompt')), 201);
    }

    public function favorite(Request $request): JsonResponse
    {
        $scope = $this->validatedScope($request, true);
        if ($scope instanceof JsonResponse) return $scope;
        return response()->json($this->history->favorite($scope, (string) $request->input('prompt')), 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = AiPromptHistory::find($id);
        if (!$item) return response()->json(['error' => 'Prompt not found'], 404);
        $item->delete();
        return response()->json(['message' => 'Prompt deleted']);
    }

    private function validatedScope(Request $request, bool $withPrompt = false): array|JsonResponse
    {
        $rules = [
            'template_set' => 'required|string|max:100', 'page_key' => 'required|string|max:255',
            'module_key' => 'required|string|max:255', 'locale' => 'required|string|max:20',
            'field_key' => 'required|string|max:1000',
        ];
        if ($withPrompt) $rules['prompt'] = 'required|string|max:12000';
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);
        return collect($validator->validated())->only(['template_set', 'page_key', 'module_key', 'locale', 'field_key'])->map(fn ($value) => trim((string) $value))->all();
    }
}

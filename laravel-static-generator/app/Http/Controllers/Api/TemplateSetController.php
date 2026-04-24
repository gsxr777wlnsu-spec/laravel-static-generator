<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemplateSet;
use App\Services\TemplateSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TemplateSetController extends Controller
{
    public function __construct(
        private TemplateSetService $service
    ) {}

    public function index(): JsonResponse
    {
        $templates = $this->service->getAll();
        
        $templates = $templates->map(function ($template) {
            $validation = $this->service->validate($template);
            $template->validation = $validation;
            $template->components = $this->service->getComponents($template);
            return $template;
        });

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string',
            'source_template' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $template = $this->service->create($validator->validated());
            
            return response()->json([
                'message' => 'Template set created successfully',
                'template' => $template,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $template = $this->service->getById($id);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $validation = $this->service->validate($template);
        $template->validation = $validation;
        $template->components = $this->service->getComponents($template);

        return response()->json($template);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = $this->service->getById($id);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = $this->service->update($template, $validator->validated());

        return response()->json([
            'message' => 'Template set updated successfully',
            'template' => $template,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = $this->service->getById($id);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        try {
            $this->service->delete($template);
            
            return response()->json(['message' => 'Template set deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function clone(Request $request, int $id): JsonResponse
    {
        $template = $this->service->getById($id);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $newTemplate = $this->service->clone($template, $request->input('name'));
            
            return response()->json([
                'message' => 'Template set cloned successfully',
                'template' => $newTemplate,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function validate(int $id): JsonResponse
    {
        $template = $this->service->getById($id);
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $validation = $this->service->validate($template);
        
        return response()->json($validation);
    }

    public function builtIn(): JsonResponse
    {
        $templates = TemplateSetService::getBuiltInTemplates();
        
        return response()->json([
            'templates' => $templates,
        ]);
    }
}

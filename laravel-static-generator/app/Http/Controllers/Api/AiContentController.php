<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiContentService;
use App\Services\MarkdownParser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiContentController extends Controller
{
    public function __construct(
        protected AiContentService $aiService,
        protected MarkdownParser $markdownParser
    ) {}

    public function processMarkdown(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120', // 5MB max
        ]);

        $content = $request->file('file')->get();
        $structure = $this->markdownParser->extractStructure($content);

        return response()->json([
            'message' => 'Markdown parsed successfully',
            'structure' => $structure
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'type' => 'required|in:sections,metadata',
        ]);

        $text = $request->input('text');
        
        try {
            if ($request->input('type') === 'sections') {
                $result = $this->aiService->generateContent($text);
            } else {
                $result = $this->aiService->generateMetadata($text);
            }

            return response()->json([
                'message' => 'Generation successful',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'AI Generation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace Tests\Feature\Property;

use Tests\TestCase;
use App\Services\MarkdownParser;
use App\Services\AiContentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class AiContentTest extends TestCase
{
    /**
     * Property 12: Markdown parsing round trip.
     */
    public function test_markdown_parsing_extracts_headings_and_content()
    {
        $parser = new MarkdownParser();
        $markdown = "
# Main Title
Intro text here.

## Secondary Output
More text.
";
        $structure = $parser->extractStructure($markdown);

        $this->assertCount(2, $structure);
        $this->assertEquals('Main Title', $structure[0]['heading']);
        $this->assertStringContainsString('Intro text here.', $structure[0]['content']);
        $this->assertEquals('Secondary Output', $structure[1]['heading']);
        $this->assertStringContainsString('More text.', $structure[1]['content']);
    }

    /**
     * Property 13: AI section generation completeness.
     */
    public function test_ai_section_generation_completeness()
    {
        config(['services.ai.api_key' => 'fake-key']);
        
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                ['type' => 'text', 'content' => ['heading' => 'Test Heading', 'text' => 'Test']],
                                ['type' => 'faq', 'content' => ['items' => []]]
                            ])
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = new AiContentService();
        $sections = $service->generateContent("Test content");

        $this->assertIsArray($sections);
        $this->assertCount(2, $sections);
        $this->assertEquals('text', $sections[0]['type']);
        $this->assertEquals('faq', $sections[1]['type']);
    }

    public function test_ai_metadata_generation_completeness()
    {
        config(['services.ai.api_key' => 'fake-key']);
        
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'meta_title' => 'Test Title',
                                'meta_description' => 'Test Description',
                                'meta_keywords' => 'test, json',
                                'json_ld' => '{"@context":"https://schema.org"}'
                            ])
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = new AiContentService();
        $metadata = $service->generateMetadata("Test content");

        $this->assertArrayHasKey('meta_title', $metadata);
        $this->assertEquals('Test Title', $metadata['meta_title']);
    }
}

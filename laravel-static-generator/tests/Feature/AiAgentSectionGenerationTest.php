<?php

namespace Tests\Feature;

use App\Models\AiAgentConfig;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAgentSectionGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_generation_sanitizes_shared_header_before_and_after_ai_call(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '<section class="hero"><header class="header"><div class="header__inner">Menu</div></header><h1>Generated SEO H1</h1></section>',
                        ],
                    ],
                ],
            ]),
        ]);

        $site = Site::create([
            'name' => 'AI Site',
            'domain' => 'ai-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/ai-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home',
            'status' => 'draft',
            'locale' => 'en',
            'template_key' => 'index',
        ]);

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'order' => 0,
            'content' => [
                'module' => 'hero',
                'raw_html' => '<section class="hero"><header class="header"><div class="header__inner">Menu</div></header><h1>Old H1</h1></section>',
                'render_mode' => 'raw_html',
            ],
        ]);

        $config = new AiAgentConfig([
            'provider' => 'openai',
            'api_key' => 'test-key',
            'api_base_url' => 'https://api.openai.com/v1',
            'model_name' => 'gpt-test',
            'temperature' => 0.3,
            'is_active' => true,
        ]);

        $html = app(AiAgentService::class)->generateSectionHtml(
            section: $section,
            config: $config,
            prompt: 'Generate SEO text for h1',
        );

        $this->assertStringContainsString('Generated SEO H1', $html);
        $this->assertStringNotContainsString('<header', $html);
        $this->assertStringNotContainsString('header__inner', $html);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $userMessage = $body['messages'][1]['content'] ?? '';

            return str_contains($userMessage, 'Old H1')
                && !str_contains($userMessage, '<header')
                && !str_contains($userMessage, 'header__inner');
        });
    }
}

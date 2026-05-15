<?php

namespace Tests\Feature;

use App\Models\AiAgentConfig;
use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class SiteAiTemplateGenerationTest extends TestCase
{
    use RefreshDatabase;

    private string $templatesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templatesRoot = '/tmp/laravel-static-generator-tests/ai-templates-' . Str::uuid();
        File::ensureDirectoryExists($this->templatesRoot . '/test.com');

        config()->set('services.ai_agent.templates_root', $this->templatesRoot);

        $fixture = [
            'domain' => 'test.com',
            'name' => 'test.com',
            'template' => 'test',
            'output_path' => 'generated/test.com',
            'status' => 'active',
            'locale' => 'en',
            'sftp_host' => 'source-host.example',
            'sftp_port' => 22,
            'sftp_username' => 'source-user',
            'sftp_password' => 'source-password',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/source',
            'pages' => [[
                'slug' => 'index',
                'title' => 'Old Title',
                'template_key' => 'index',
                'status' => 'published',
                'meta_title' => 'Old Meta',
                'meta_description' => 'Old Description',
                'meta_keywords' => 'old,keywords',
                'canonical' => 'https://test.com/',
                'locale' => 'en',
                'sections' => [[
                    'module' => 'hero',
                    'module_key' => 'hero',
                    'heading' => 'Old Heading',
                    'raw_html' => '<section><h1>Old Heading</h1></section>',
                    'render_mode' => 'raw_html',
                ]],
            ]],
        ];

        $yaml = Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($this->templatesRoot . '/test.com/index-raw_html.md', "---\n" . $yaml);
    }

    public function test_site_creation_clones_md_template_folder(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Demo Site',
            'domain' => 'demo-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/demo-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => 'target-host.example',
            'sftp_port' => 22,
            'sftp_username' => 'target-user',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/target',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.updated_fields', 0);
        $response->assertJsonPath('ai_generation.updated_files', 0);
        $response->assertJsonCount(0, 'ai_generation.updated_paths');

        $targetFile = $this->templatesRoot . '/demo-site.example/index-raw_html.md';
        $this->assertFileExists($targetFile);

        $cloned = Yaml::parseFile($targetFile);
        $this->assertSame('demo-site.example', $cloned['domain']);
        $this->assertSame('generated/demo-site.example', $cloned['output_path']);
        $this->assertSame('https://demo-site.example/', $cloned['pages'][0]['canonical']);

        $site = Site::where('domain', 'demo-site.example')->firstOrFail();
        $this->assertSame('target-host.example', $site->sftp_host);
        $this->assertSame('target-user', $site->sftp_username);
        $this->assertSame('/var/www/target', $site->sftp_remote_path);
        $this->assertDatabaseCount('pages', 1);
        $this->assertDatabaseHas('pages', [
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Old Title',
        ]);
    }

    public function test_site_creation_applies_ai_prompts_to_md_fields(): void
    {
        $user = User::factory()->create();

        AiAgentConfig::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'api_key' => 'test-api-key',
            'model_name' => 'gpt-4o-mini',
            'allowed_paths' => [$this->templatesRoot],
            'is_active' => true,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'New Heading By AI'],
                ]],
            ], 200),
        ]);

        $payload = [
            'name' => 'Prompt Site',
            'domain' => 'prompt-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/prompt-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [[
                'file' => 'index-raw_html.md',
                'path' => 'pages.0.sections.0.heading',
                'prompt' => 'Rewrite heading for a betting audience.',
            ]],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.updated_fields', 1);
        $response->assertJsonPath('ai_generation.updated_files', 1);
        $response->assertJsonPath('ai_generation.updated_paths.0', 'pages.0.sections.0.heading');

        $targetFile = $this->templatesRoot . '/prompt-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);

        $this->assertSame('New Heading By AI', $updated['pages'][0]['sections'][0]['heading']);

        $site = Site::where('domain', 'prompt-site.example')->firstOrFail();
        $page = Page::where('site_id', $site->id)->where('slug', 'index')->firstOrFail();
        $section = Section::where('page_id', $page->id)->firstOrFail();

        $this->assertSame('New Heading By AI', $section->heading);
    }

    public function test_site_creation_strips_outer_quotes_for_text_fields(): void
    {
        $user = User::factory()->create();

        AiAgentConfig::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'api_key' => 'test-api-key',
            'model_name' => 'gpt-4o-mini',
            'allowed_paths' => [$this->templatesRoot],
            'is_active' => true,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => '"An engaging legal simulation offering high-stakes challenges and seamless user experience."',
                    ],
                ]],
            ], 200),
        ]);

        $payload = [
            'name' => 'Prompt Site 2',
            'domain' => 'prompt-site-2.example',
            'template_set' => 'base',
            'output_path' => 'generated/prompt-site-2.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [[
                'file' => 'index-raw_html.md',
                'path' => 'pages.0.meta_description',
                'prompt' => 'Rewrite meta description in 110 chars.',
            ]],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.updated_fields', 1);
        $response->assertJsonPath('ai_generation.updated_files', 1);
        $response->assertJsonPath('ai_generation.updated_paths.0', 'pages.0.meta_description');

        $targetFile = $this->templatesRoot . '/prompt-site-2.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $expected = 'An engaging legal simulation offering high-stakes challenges and seamless user experience.';

        $this->assertSame($expected, $updated['pages'][0]['meta_description']);

        $site = Site::where('domain', 'prompt-site-2.example')->firstOrFail();
        $page = Page::where('site_id', $site->id)->where('slug', 'index')->firstOrFail();

        $this->assertSame($expected, $page->meta_description);
    }

    public function test_site_creation_applies_multiple_page_field_prompts(): void
    {
        $user = User::factory()->create();

        AiAgentConfig::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'api_key' => 'test-api-key',
            'model_name' => 'gpt-4o-mini',
            'allowed_paths' => [$this->templatesRoot],
            'is_active' => true,
        ]);

        Http::fake([
            'api.openai.com/*' => function (\Illuminate\Http\Client\Request $request) {
                $body = $request->data();
                $userMessage = (string) ($body['messages'][1]['content'] ?? '');

                if (str_contains($userMessage, 'Field path: pages.0.meta_title')) {
                    return Http::response([
                        'choices' => [[
                            'message' => ['content' => '1WIN Aviator: Best Legal Betting Experience'],
                        ]],
                    ], 200);
                }

                if (str_contains($userMessage, 'Field path: pages.0.meta_description')) {
                    return Http::response([
                        'choices' => [[
                            'message' => ['content' => 'Play 1WIN Aviator online with legal access, fast gameplay, and high-payout excitement. Discover bonuses, strategy tips, and secure betting options today.'],
                        ]],
                    ], 200);
                }

                return Http::response([
                    'choices' => [[
                        'message' => ['content' => 'Unexpected prompt'],
                    ]],
                ], 200);
            },
        ]);

        $payload = [
            'name' => 'Prompt Site 3',
            'domain' => 'prompt-site-3.example',
            'template_set' => 'base',
            'output_path' => 'generated/prompt-site-3.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [
                [
                    'file' => 'index-raw_html.md',
                    'path' => 'pages.0.meta_title',
                    'prompt' => 'Generate SEO title up to 60 chars in English for betting topic.',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'path' => 'pages.0.meta_description',
                    'prompt' => 'Generate SEO description up to 160 chars in English for betting topic.',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.updated_fields', 2);
        $response->assertJsonPath('ai_generation.updated_files', 1);
        $response->assertJsonCount(2, 'ai_generation.updated_paths');
        $this->assertEqualsCanonicalizing(
            ['pages.0.meta_title', 'pages.0.meta_description'],
            $response->json('ai_generation.updated_paths')
        );

        $targetFile = $this->templatesRoot . '/prompt-site-3.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);

        $this->assertSame('Old Title', $updated['pages'][0]['title']);
        $this->assertSame('1WIN Aviator: Best Legal Betting Experience', $updated['pages'][0]['meta_title']);
        $this->assertSame(
            'Play 1WIN Aviator online with legal access, fast gameplay, and high-payout excitement. Discover bonuses, strategy tips, and secure betting options today.',
            $updated['pages'][0]['meta_description']
        );

        $site = Site::where('domain', 'prompt-site-3.example')->firstOrFail();
        $page = Page::where('site_id', $site->id)->where('slug', 'index')->firstOrFail();

        $this->assertSame('Old Title', $page->title);
        $this->assertSame('1WIN Aviator: Best Legal Betting Experience', $page->meta_title);
        $this->assertSame(
            'Play 1WIN Aviator online with legal access, fast gameplay, and high-payout excitement. Discover bonuses, strategy tips, and secure betting options today.',
            $page->meta_description
        );
    }
}

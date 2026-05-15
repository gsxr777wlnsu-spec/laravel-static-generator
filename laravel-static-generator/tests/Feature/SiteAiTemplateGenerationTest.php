<?php

namespace Tests\Feature;

use App\Models\AiAgentConfig;
use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use App\Models\Section;
use App\Services\AiAgentService;
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
                'sections' => [
                    [
                        'module' => 'hero',
                        'module_key' => 'hero',
                        'heading' => 'Old Heading',
                        'raw_html' => '<section class="hero"><header class="header"><nav class="header__nav menu"><a class="menu__link" href="app.html">App</a></nav></header><div class="hero__content"><h1>Old Heading</h1><p>Old Description</p></div></section>',
                        'render_mode' => 'raw_html',
                    ],
                    [
                        'module' => 'mobile-menu',
                        'module_key' => 'mobile-menu',
                        'heading' => 'Mobile Menu',
                        'raw_html' => '<div class="mobile-menu"><nav><a href="app.html">App</a></nav></div>',
                        'render_mode' => 'raw_html',
                    ],
                ],
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

    public function test_template_catalog_hides_main_menu_text_and_mobile_menu_section_fields(): void
    {
        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $this->assertIsArray($indexFile);

        $sectionFields = $indexFile['section_fields'] ?? [];
        $this->assertIsArray($sectionFields);

        $this->assertTrue(collect($sectionFields)->contains(
            fn ($field) => ($field['value'] ?? null) === 'Old Heading'
        ));

        $this->assertFalse(collect($sectionFields)->contains(
            fn ($field) => ($field['value'] ?? null) === 'App'
        ));

        $this->assertFalse(collect($sectionFields)->contains(
            fn ($field) => str_contains((string) ($field['path'] ?? ''), '.sections.1.')
        ));
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

    public function test_site_creation_applies_ai_prompt_to_index_raw_html_text_without_sending_markup(): void
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

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $sectionFields = $indexFile['section_fields'] ?? [];
        $rawHeadingField = collect($sectionFields)->first(
            fn ($field) => ($field['value'] ?? null) === 'Old Heading'
        );

        $this->assertIsArray($rawHeadingField, 'Expected extracted raw_html text field for heading.');
        $this->assertTrue(str_starts_with((string) ($rawHeadingField['path'] ?? ''), 'pages.0.sections.0.raw_html.__text__.'));

        Http::fake([
            'api.openai.com/*' => function (\Illuminate\Http\Client\Request $request) {
                $body = $request->data();
                $userMessage = (string) ($body['messages'][1]['content'] ?? '');

                $this->assertStringContainsString("Current value:\nOld Heading", $userMessage);
                $this->assertStringNotContainsString('<section>', $userMessage);
                $this->assertStringNotContainsString('<h1>', $userMessage);

                return Http::response([
                    'choices' => [[
                        'message' => ['content' => 'New Heading In Raw Html'],
                    ]],
                ], 200);
            },
        ]);

        $payload = [
            'name' => 'Prompt Raw Html Site',
            'domain' => 'prompt-raw-html.example',
            'template_set' => 'base',
            'output_path' => 'generated/prompt-raw-html.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [[
                'file' => 'index-raw_html.md',
                'path' => $rawHeadingField['path'],
                'prompt' => 'Rewrite heading for better engagement.',
            ]],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.updated_fields', 1);
        $response->assertJsonPath('ai_generation.updated_paths.0', $rawHeadingField['path']);

        $targetFile = $this->templatesRoot . '/prompt-raw-html.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $rawHtml = (string) ($updated['pages'][0]['sections'][0]['raw_html'] ?? '');

        $this->assertStringContainsString('<h1>New Heading In Raw Html</h1>', $rawHtml);
        $this->assertStringNotContainsString('<h1>Old Heading</h1>', $rawHtml);
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

    public function test_site_creation_applies_manual_page_field_edits_without_ai_prompt(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Manual Site',
            'domain' => 'manual-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/manual-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [[
                'file' => 'index-raw_html.md',
                'path' => 'pages.0.title',
                'value' => 'Manual Title From Form',
            ]],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.updated_fields', 0);
        $response->assertJsonPath('ai_generation.manual_updated_fields', 1);
        $response->assertJsonPath('ai_generation.manual_updated_files', 1);
        $response->assertJsonPath('ai_generation.manual_updated_paths.0', 'pages.0.title');

        $targetFile = $this->templatesRoot . '/manual-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $this->assertSame('Manual Title From Form', $updated['pages'][0]['title']);

        $site = Site::where('domain', 'manual-site.example')->firstOrFail();
        $page = Page::where('site_id', $site->id)->where('slug', 'index')->firstOrFail();
        $this->assertSame('Manual Title From Form', $page->title);
    }

    public function test_site_creation_persists_manual_edits_for_all_head_text_fields(): void
    {
        $user = User::factory()->create();

        $sourceDomain = 'head-source.test';
        $sourceDir = $this->templatesRoot . '/' . $sourceDomain;
        File::ensureDirectoryExists($sourceDir);

        $fixture = [
            'domain' => $sourceDomain,
            'name' => $sourceDomain,
            'template' => 'test',
            'output_path' => "generated/{$sourceDomain}",
            'status' => 'active',
            'locale' => 'en',
            'pages' => [[
                'slug' => 'index',
                'title' => 'Head Source Page',
                'template_key' => 'index',
                'status' => 'published',
                'meta_title' => 'Old Meta Title',
                'meta_description' => 'Old Meta Description',
                'meta_keywords' => 'old,keywords',
                'canonical' => "https://{$sourceDomain}/",
                'locale' => 'en',
                'og_data' => [
                    'head_meta' => [
                        ['name' => 'robots', 'content' => 'all'],
                        ['name' => 'telegram:channel', 'content' => '@old_channel'],
                        ['name' => 'telegram:bot', 'content' => '@old_bot'],
                        ['property' => 'vk:image', 'content' => '/assets/images/logo-old.png'],
                        ['property' => 'vk:app_id', 'content' => 'old-app-id'],
                        ['name' => 'og:type', 'property' => 'og:type', 'content' => 'article'],
                        ['property' => 'og:locale', 'content' => 'en_EN'],
                        ['name' => 'og:title', 'property' => 'og:title', 'content' => 'Old OG Title'],
                        ['name' => 'og:description', 'property' => 'og:description', 'content' => 'Old OG Description'],
                        ['property' => 'article:published_time', 'content' => '2016'],
                        ['property' => 'article:modified_time', 'content' => '2017-01-01T00:00:00+00:00'],
                        ['property' => 'article:author', 'content' => 'Old Author'],
                        ['name' => 'twitter:card', 'content' => 'summary'],
                        ['name' => 'twitter:title', 'content' => 'Old Twitter Title'],
                        ['name' => 'twitter:description', 'content' => 'Old Twitter Description'],
                        ['name' => 'twitter:site', 'content' => 'oldsite.com'],
                        ['name' => 'twitter:creator', 'content' => 'oldcreator.com'],
                        ['name' => 'twitter:image', 'content' => '/assets/images/old-twitter.jpg'],
                        ['property' => 'og:image', 'content' => '/assets/images/old-og.jpg'],
                        ['name' => 'geo.region', 'content' => 'RU'],
                        ['name' => 'geo.position', 'content' => '50.00000; 40.00000'],
                        ['name' => 'ICBM', 'content' => '50.00000, 40.00000'],
                        ['name' => 'contact', 'content' => 'support@oldsite.com'],
                        ['name' => 'copyright', 'content' => 'oldsite.com'],
                        ['name' => 'designer', 'content' => 'old-designer'],
                        ['name' => 'generator', 'content' => 'old CMS'],
                        ['name' => 'author', 'content' => 'old author'],
                        ['name' => 'rating', 'content' => 'pg-13'],
                    ],
                    'head_links' => [
                        ['rel' => 'publisher', 'href' => "https://{$sourceDomain}/"],
                    ],
                    'head_extra' => <<<'HTML'
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Old WebPage"}</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"Old Org"}</script>
HTML,
                    'head_custom' => "<style>.old{color:red;}</style>\n<script>window.oldCounter=1;</script>",
                ],
            ]],
        ];

        $yaml = Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($sourceDir . '/index-raw_html.md', "---\n" . $yaml);

        $headMetaUpdatedValues = [
            0 => 'deny',
            1 => '@WP_WooCom',
            2 => '@WP_WooCom_bot',
            3 => '/assets/images/logo/logo.png',
            4 => 'vk-app-123',
            5 => 'website',
            6 => 'en_US',
            7 => 'Aviator Game',
            8 => 'Play Aviator Game',
            9 => '2020-12-07T18:05:01+00:00',
            10 => '2026-04-20T10:43:59+00:00',
            11 => 'Aviator',
            12 => 'summary_large_image',
            13 => 'Aviator Game',
            14 => 'Play Aviator Game',
            15 => 'site.com',
            16 => 'site.com',
            17 => '/assets/images/aviator.jpg',
            18 => '/assets/images/aviator.jpg',
            19 => 'EN',
            20 => '55.71881; 37.555728',
            21 => '55.71881, 37.555728',
            22 => 'support@site.com',
            23 => 'site.com',
            24 => 'gsxr777',
            25 => 'site.com CMS',
            26 => 'site.com',
            27 => 'general',
        ];

        $fieldEdits = [
            ['file' => 'index-raw_html.md', 'path' => 'pages.0.meta_title', 'value' => 'Aviator Game - New Meta Title'],
            ['file' => 'index-raw_html.md', 'path' => 'pages.0.meta_description', 'value' => 'Play Aviator Game with legal access and high RTP.'],
            ['file' => 'index-raw_html.md', 'path' => 'pages.0.meta_keywords', 'value' => 'aviator, legal, betting'],
            ['file' => 'index-raw_html.md', 'path' => 'pages.0.canonical', 'value' => 'https://target-head.example/'],
        ];

        foreach ($headMetaUpdatedValues as $metaIndex => $value) {
            $fieldEdits[] = [
                'file' => 'index-raw_html.md',
                'path' => "pages.0.og_data.head_meta.{$metaIndex}.content",
                'value' => $value,
            ];
        }

        $updatedScriptBlockOne = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","@id":"https://target-head.example/#webpage","url":"https://target-head.example/"}</script>';
        $updatedScriptBlockTwo = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","@id":"https://target-head.example/#organization","name":"Aviator Game"}</script>';
        $updatedHeadCustom = "<style>.new{color:green;}</style>\n<script>window.analyticsCode='UA-TEST-1';</script>";

        $fieldEdits[] = ['file' => 'index-raw_html.md', 'path' => 'pages.0.og_data.head_links.0.href', 'value' => 'https://site.com/'];
        $fieldEdits[] = ['file' => 'index-raw_html.md', 'path' => 'pages.0.og_data.head_extra.__script__.0', 'value' => $updatedScriptBlockOne];
        $fieldEdits[] = ['file' => 'index-raw_html.md', 'path' => 'pages.0.og_data.head_extra.__script__.1', 'value' => $updatedScriptBlockTwo];
        $fieldEdits[] = ['file' => 'index-raw_html.md', 'path' => 'pages.0.og_data.head_custom', 'value' => $updatedHeadCustom];

        $payload = [
            'name' => 'Head Manual Site',
            'domain' => 'head-manual-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/head-manual-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => $sourceDomain,
            'ai_field_prompts' => [],
            'ai_field_edits' => $fieldEdits,
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.updated_fields', 0);
        $response->assertJsonPath('ai_generation.updated_files', 0);
        $response->assertJsonPath('ai_generation.manual_updated_fields', count($fieldEdits));
        $response->assertJsonPath('ai_generation.manual_updated_files', 1);
        $response->assertJsonCount(count($fieldEdits), 'ai_generation.manual_updated_paths');
        $this->assertEqualsCanonicalizing(
            array_map(static fn (array $item) => $item['path'], $fieldEdits),
            $response->json('ai_generation.manual_updated_paths')
        );

        $targetFile = $this->templatesRoot . '/head-manual-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);

        $this->assertSame('Aviator Game - New Meta Title', data_get($updated, 'pages.0.meta_title'));
        $this->assertSame('Play Aviator Game with legal access and high RTP.', data_get($updated, 'pages.0.meta_description'));
        $this->assertSame('aviator, legal, betting', data_get($updated, 'pages.0.meta_keywords'));
        $this->assertSame('https://target-head.example/', data_get($updated, 'pages.0.canonical'));
        $this->assertSame('https://site.com/', data_get($updated, 'pages.0.og_data.head_links.0.href'));
        $this->assertStringContainsString($updatedScriptBlockOne, (string) data_get($updated, 'pages.0.og_data.head_extra'));
        $this->assertStringContainsString($updatedScriptBlockTwo, (string) data_get($updated, 'pages.0.og_data.head_extra'));
        $this->assertSame($updatedHeadCustom, data_get($updated, 'pages.0.og_data.head_custom'));

        foreach ($headMetaUpdatedValues as $metaIndex => $value) {
            $this->assertSame($value, data_get($updated, "pages.0.og_data.head_meta.{$metaIndex}.content"));
        }

        $site = Site::where('domain', 'head-manual-site.example')->firstOrFail();
        $page = Page::where('site_id', $site->id)->where('slug', 'index')->firstOrFail();

        $this->assertSame('Aviator Game - New Meta Title', $page->meta_title);
        $this->assertSame('Play Aviator Game with legal access and high RTP.', $page->meta_description);
        $this->assertSame('aviator, legal, betting', $page->meta_keywords);
        $this->assertSame('https://target-head.example/', $page->canonical);
        $this->assertSame('https://site.com/', data_get($page->og_data, 'head_links.0.href'));
        $this->assertStringContainsString($updatedScriptBlockOne, (string) data_get($page->og_data, 'head_extra'));
        $this->assertStringContainsString($updatedScriptBlockTwo, (string) data_get($page->og_data, 'head_extra'));
        $this->assertSame($updatedHeadCustom, data_get($page->og_data, 'head_custom'));

        foreach ($headMetaUpdatedValues as $metaIndex => $value) {
            $this->assertSame($value, data_get($page->og_data, "head_meta.{$metaIndex}.content"));
        }
    }
}

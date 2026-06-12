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
        $response->assertJsonPath('create_report.view_url', route('admin.sites.creation-log', ['id' => 1]));

        $targetFile = $this->templatesRoot . '/demo-site.example/index-raw_html.md';
        $this->assertFileExists($targetFile);
        $reportFile = $this->templatesRoot . '/demo-site.example/site-create-report.txt';
        $this->assertFileExists($reportFile);
        $this->assertStringContainsString('Site created successfully.', (string) File::get($reportFile));
        $this->assertStringContainsString('Domain: demo-site.example', (string) File::get($reportFile));

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

    public function test_site_creation_can_edit_empty_raw_html_image_alt_attribute(): void
    {
        $user = User::factory()->create();

        $sourceFile = $this->templatesRoot . '/test.com/index-raw_html.md';
        $fixture = Yaml::parseFile($sourceFile);
        $fixture['pages'][0]['sections'][0]['raw_html'] = '<section class="hero"><img class="hero__image" src="/hero.webp" alt=""><h1>Old Heading</h1></section>';
        file_put_contents($sourceFile, "---\n" . Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $sectionFields = $indexFile['section_fields'] ?? [];
        $altField = collect(is_array($sectionFields) ? $sectionFields : [])->first(
            fn ($field) => is_array($field)
                && (($field['target_type'] ?? null) === 'attr')
                && (($field['tag'] ?? null) === 'img')
                && (($field['value'] ?? null) === '')
        );

        $this->assertIsArray($altField);
        $this->assertSame(0, $altField['length']);
        $this->assertStringContainsString('.raw_html.__attr__.', (string) $altField['path']);

        $payload = [
            'name' => 'Manual Alt Site',
            'domain' => 'manual-alt-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/manual-alt-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [[
                'file' => 'index-raw_html.md',
                'path' => $altField['path'],
                'value' => 'Aviator hero image',
            ]],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.manual_updated_fields', 1);
        $response->assertJsonPath('ai_generation.manual_updated_paths.0', $altField['path']);

        $targetFile = $this->templatesRoot . '/manual-alt-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $rawHtml = (string) data_get($updated, 'pages.0.sections.0.raw_html');

        $this->assertStringContainsString('alt="Aviator hero image"', $rawHtml);
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

    public function test_site_creation_can_skip_sending_current_value_to_ai_for_field_prompt(): void
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

                $this->assertStringContainsString('Field path: pages.0.meta_title', $userMessage);
                $this->assertStringContainsString('Instruction: Rewrite the title without using the existing value.', $userMessage);
                $this->assertStringNotContainsString('Current value:', $userMessage);
                $this->assertStringNotContainsString('Old Meta Title', $userMessage);

                return Http::response([
                    'choices' => [[
                        'message' => ['content' => 'Fresh Meta Title'],
                    ]],
                ], 200);
            },
        ]);

        $payload = [
            'name' => 'Prompt No Current Value Site',
            'domain' => 'prompt-no-current-value.example',
            'template_set' => 'base',
            'output_path' => 'generated/prompt-no-current-value.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [[
                'file' => 'index-raw_html.md',
                'path' => 'pages.0.meta_title',
                'prompt' => 'Rewrite the title without using the existing value.',
                'send_current_value' => false,
            ]],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.updated_fields', 1);
        $response->assertJsonPath('ai_generation.updated_paths.0', 'pages.0.meta_title');

        $targetFile = $this->templatesRoot . '/prompt-no-current-value.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);

        $this->assertSame('Fresh Meta Title', data_get($updated, 'pages.0.meta_title'));
    }

    public function test_faq_page_json_ld_syncs_from_visible_faq_after_prompt_generation(): void
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

        $sourceFile = $this->templatesRoot . '/test.com/index-raw_html.md';
        $source = Yaml::parseFile($sourceFile);
        $source['pages'][0]['og_data']['head_extra'] = implode("\n", [
            '<script type="application/ld+json">{"@type":"BreadcrumbList"}</script>',
            '<script type="application/ld+json">{"@type":"WebSite"}</script>',
            '<script type="application/ld+json">{"@type":"Organization"}</script>',
            '<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Old question?","acceptedAnswer":{"@type":"Answer","text":"Old answer."}}]}</script>',
        ]);
        $source['pages'][0]['sections'] = array_pad($source['pages'][0]['sections'], 18, [
            'module' => 'placeholder',
            'raw_html' => '<section></section>',
            'render_mode' => 'raw_html',
        ]);
        $source['pages'][0]['sections'][17] = [
            'module' => 'faq',
            'module_key' => 'faq',
            'raw_html' => '<section class="faq"><h2>FAQ</h2><p>Intro text.</p><div><span>Old question?</span><p>Old answer.</p></div></section>',
            'render_mode' => 'raw_html',
        ];
        file_put_contents($sourceFile, "---\n" . Yaml::dump($source, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $sectionFields = collect($indexFile['section_fields'] ?? []);
        $questionField = $sectionFields->first(fn ($field) => is_array($field) && (($field['value'] ?? null) === 'Old question?'));
        $answerField = $sectionFields->first(fn ($field) => is_array($field) && (($field['value'] ?? null) === 'Old answer.'));

        $this->assertIsArray($questionField);
        $this->assertIsArray($answerField);

        Http::fake([
            'api.openai.com/*' => function (\Illuminate\Http\Client\Request $request) {
                $userMessage = (string) ($request->data()['messages'][1]['content'] ?? '');

                return Http::response([
                    'choices' => [[
                        'message' => ['content' => str_contains($userMessage, 'Old question?')
                            ? 'New generated question?'
                            : 'New generated answer.'],
                    ]],
                ], 200);
            },
        ]);

        $payload = [
            'name' => 'FAQ Sync Site',
            'domain' => 'faq-sync.example',
            'template_set' => 'base',
            'output_path' => 'generated/faq-sync.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [
                [
                    'file' => 'index-raw_html.md',
                    'path' => (string) $questionField['path'],
                    'prompt' => 'Rewrite FAQ question.',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'path' => (string) $answerField['path'],
                    'prompt' => 'Rewrite FAQ answer.',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);

        $updated = Yaml::parseFile($this->templatesRoot . '/faq-sync.example/index-raw_html.md');
        $headExtra = (string) data_get($updated, 'pages.0.og_data.head_extra');

        preg_match_all('/<script\b[^>]*>.*?<\/script>/is', $headExtra, $matches);
        $faqScript = $matches[0][3] ?? '';
        $json = trim((string) preg_replace('/^<script\b[^>]*>|<\/script>$/i', '', $faqScript));
        $schema = json_decode($json, true);

        $this->assertSame('FAQPage', $schema['@type'] ?? null);
        $this->assertCount(1, $schema['mainEntity'] ?? []);
        $this->assertSame('New generated question?', $schema['mainEntity'][0]['name'] ?? null);
        $this->assertSame('New generated answer.', $schema['mainEntity'][0]['acceptedAnswer']['text'] ?? null);
    }

    public function test_how_to_json_ld_syncs_from_visible_steps_after_prompt_generation(): void
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

        $sourceFile = $this->templatesRoot . '/test.com/index-raw_html.md';
        $source = Yaml::parseFile($sourceFile);
        $source['pages'][0]['og_data']['head_extra'] = implode("\n", [
            '<script type="application/ld+json">{"@type":"BreadcrumbList"}</script>',
            '<script type="application/ld+json">{"@type":"WebSite"}</script>',
            '<script type="application/ld+json">{"@type":"Organization"}</script>',
            '<script type="application/ld+json">{"@type":"FAQPage","mainEntity":[]}</script>',
            '<script type="application/ld+json">{"@context":"https://schema.org","@type":"HowTo","name":"Old steps title","description":"Old steps description.","step":[{"@type":"HowToStep","position":1,"name":"Old step title","text":"Old step text.","image":"https://{site}/assets/images/steps/step.webp"}]}</script>',
        ]);
        $source['pages'][0]['sections'] = array_pad($source['pages'][0]['sections'], 11, [
            'module' => 'placeholder',
            'raw_html' => '<section></section>',
            'render_mode' => 'raw_html',
        ]);
        $source['pages'][0]['sections'][10] = [
            'module' => 'steps',
            'module_key' => 'steps',
            'raw_html' => '<section class="steps"><h2>Old steps title</h2><p>Old steps description.</p><div class="steps__list"><div class="steps__card"><span>Step 1</span><div>Old step title</div><p>Old step text.</p></div></div></section>',
            'render_mode' => 'raw_html',
        ];
        file_put_contents($sourceFile, "---\n" . Yaml::dump($source, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $sectionFields = collect($indexFile['section_fields'] ?? []);
        $titleField = $sectionFields->first(fn ($field) => is_array($field) && (($field['value'] ?? null) === 'Old steps title'));
        $descriptionField = $sectionFields->first(fn ($field) => is_array($field) && (($field['value'] ?? null) === 'Old steps description.'));
        $stepTitleField = $sectionFields->first(fn ($field) => is_array($field) && (($field['value'] ?? null) === 'Old step title'));
        $stepTextField = $sectionFields->first(fn ($field) => is_array($field) && (($field['value'] ?? null) === 'Old step text.'));

        $this->assertIsArray($titleField);
        $this->assertIsArray($descriptionField);
        $this->assertIsArray($stepTitleField);
        $this->assertIsArray($stepTextField);

        Http::fake([
            'api.openai.com/*' => function (\Illuminate\Http\Client\Request $request) {
                $userMessage = (string) ($request->data()['messages'][1]['content'] ?? '');

                $content = match (true) {
                    str_contains($userMessage, 'Old steps title') => 'New steps title',
                    str_contains($userMessage, 'Old steps description.') => 'New steps description.',
                    str_contains($userMessage, 'Old step title') => 'New step title',
                    default => 'New step text.',
                };

                return Http::response(['choices' => [['message' => ['content' => $content]]]], 200);
            },
        ]);

        $payload = [
            'name' => 'HowTo Sync Site',
            'domain' => 'how-to-sync.example',
            'template_set' => 'base',
            'output_path' => 'generated/how-to-sync.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [
                ['file' => 'index-raw_html.md', 'path' => (string) $titleField['path'], 'prompt' => 'Rewrite steps title.'],
                ['file' => 'index-raw_html.md', 'path' => (string) $descriptionField['path'], 'prompt' => 'Rewrite steps description.'],
                ['file' => 'index-raw_html.md', 'path' => (string) $stepTitleField['path'], 'prompt' => 'Rewrite step title.'],
                ['file' => 'index-raw_html.md', 'path' => (string) $stepTextField['path'], 'prompt' => 'Rewrite step text.'],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);

        $updated = Yaml::parseFile($this->templatesRoot . '/how-to-sync.example/index-raw_html.md');
        $headExtra = (string) data_get($updated, 'pages.0.og_data.head_extra');

        preg_match_all('/<script\b[^>]*>.*?<\/script>/is', $headExtra, $matches);
        $howToScript = $matches[0][4] ?? '';
        $json = trim((string) preg_replace('/^<script\b[^>]*>|<\/script>$/i', '', $howToScript));
        $schema = json_decode($json, true);

        $this->assertSame('HowTo', $schema['@type'] ?? null);
        $this->assertSame('New steps title', $schema['name'] ?? null);
        $this->assertSame('New steps description.', $schema['description'] ?? null);
        $this->assertCount(1, $schema['step'] ?? []);
        $this->assertSame('New step title', $schema['step'][0]['name'] ?? null);
        $this->assertSame('New step text.', $schema['step'][0]['text'] ?? null);
        $this->assertSame('https://{site}/assets/images/steps/step.webp', $schema['step'][0]['image'] ?? null);
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

    public function test_site_creation_applies_block_operations_for_index_raw_html(): void
    {
        $user = User::factory()->create();

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $controls = $indexFile['section_block_controls'] ?? [];
        $firstSectionControl = is_array($controls) && isset($controls[0]) && is_array($controls[0]) ? $controls[0] : null;

        $this->assertIsArray($firstSectionControl);
        $sectionPath = (string) ($firstSectionControl['section_path'] ?? '');
        $this->assertNotSame('', $sectionPath);

        $removableBlocks = $firstSectionControl['removable_blocks'] ?? [];
        $paragraphBlock = collect(is_array($removableBlocks) ? $removableBlocks : [])->first(
            fn ($block) => is_array($block) && (($block['tag'] ?? null) === 'p')
        );
        $this->assertIsArray($paragraphBlock);
        $paragraphKey = (string) ($paragraphBlock['key'] ?? '');
        $this->assertNotSame('', $paragraphKey);

        $payload = [
            'name' => 'Block Ops Site',
            'domain' => 'block-ops-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/block-ops-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [],
            'ai_block_operations' => [
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'Inserted paragraph from block operation',
                    'anchor_key' => $paragraphKey,
                    'anchor_position' => 'before',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_list_block',
                    'list_tag' => 'ul',
                    'class' => 'list list--bulleted',
                    'item_class' => 'list__item',
                    'aria_label' => 'Gameplay bullet list',
                    'items' => [
                        'We will explore the key features of the Aviator',
                        'Casino game, discuss its gameplay mechanics',
                        'User interface and overall experience',
                    ],
                    'anchor_key' => $paragraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_table_block',
                    'class' => 'payments__tables',
                    'aria_label' => 'Payment methods list',
                    'headers' => [
                        'Method',
                        'Withdrawal Availability',
                        'Min Deposit/Withdrawal',
                        'Withdrawal Time',
                        'Fees',
                    ],
                    'rows' => [
                        ['Visa', 'Yes (limited)', '€25/€50', '1-3 days', '3% on deposit'],
                        ['Skrill', 'Yes', '€10/€20', '24-48 hours', 'None'],
                    ],
                    'anchor_key' => $paragraphKey,
                    'anchor_position' => 'after',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.enabled', true);
        $response->assertJsonPath('ai_generation.block_updated_fields', 3);
        $response->assertJsonPath('ai_generation.block_updated_files', 1);

        $targetFile = $this->templatesRoot . '/block-ops-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $rawHtml = (string) data_get($updated, 'pages.0.sections.0.raw_html');

        $this->assertStringContainsString('Inserted paragraph from block operation', $rawHtml);
        $this->assertStringContainsString(
            '</h1><p>Inserted paragraph from block operation</p>',
            str_replace(["\r", "\n"], '', $rawHtml)
        );
        $this->assertStringContainsString('<p>Old Description</p>', str_replace(["\r", "\n"], '', $rawHtml));
        $this->assertStringContainsString('class="list list--bulleted"', $rawHtml);
        $this->assertStringContainsString('aria-label="Gameplay bullet list"', $rawHtml);
        $this->assertStringContainsString('We will explore the key features of the Aviator', $rawHtml);
        $this->assertStringContainsString('class="payments__tables"', $rawHtml);
        $this->assertStringContainsString('Payment methods table', $rawHtml);
        $this->assertStringContainsString('Withdrawal Availability', $rawHtml);
        $this->assertStringContainsString('Visa', $rawHtml);
    }

    public function test_site_creation_applies_last_item_and_section_block_operations(): void
    {
        $user = User::factory()->create();

        $sourceFile = $this->templatesRoot . '/test.com/index-raw_html.md';
        $fixture = Yaml::parseFile($sourceFile);
        $fixture['pages'][0]['sections'][0]['raw_html'] = '<section class="hero"><h1>Old Heading</h1><ul class="hero__list"><li>First item</li><li>Second item</li></ul></section>';
        file_put_contents($sourceFile, "---\n" . Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $controls = $indexFile['section_block_controls'] ?? [];
        $firstSectionControl = is_array($controls) && isset($controls[0]) && is_array($controls[0]) ? $controls[0] : null;

        $this->assertIsArray($firstSectionControl);
        $sectionPath = (string) ($firstSectionControl['section_path'] ?? '');
        $listContainers = $firstSectionControl['list_containers'] ?? [];
        $listContainer = collect(is_array($listContainers) ? $listContainers : [])->first(
            fn ($item) => is_array($item) && (($item['tag'] ?? null) === 'ul')
        );

        $this->assertIsArray($listContainer);
        $listKey = (string) ($listContainer['key'] ?? '');
        $this->assertNotSame('', $listKey);

        $payload = [
            'name' => 'Section Ops Site',
            'domain' => 'section-ops-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/section-ops-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [],
            'ai_block_operations' => [
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'remove_last_list_item',
                    'container_key' => $listKey,
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_section',
                    'module' => 'casino',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.block_updated_fields', 2);

        $targetFile = $this->templatesRoot . '/section-ops-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $sections = data_get($updated, 'pages.0.sections');

        $this->assertIsArray($sections);
        $this->assertStringContainsString('First item', (string) data_get($updated, 'pages.0.sections.0.raw_html'));
        $this->assertStringNotContainsString('Second item', (string) data_get($updated, 'pages.0.sections.0.raw_html'));
        $this->assertTrue(collect($sections)->contains(
            fn ($section) => is_array($section) && (($section['module'] ?? null) === 'casino')
        ));
    }

    public function test_site_creation_applies_ai_prompt_to_new_block_operation_fields(): void
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
                    'message' => ['content' => 'AI generated paragraph for queued block'],
                ]],
            ], 200),
        ]);

        $payload = [
            'name' => 'Queued Prompt Block Site',
            'domain' => 'queued-prompt-block.example',
            'template_set' => 'base',
            'output_path' => 'generated/queued-prompt-block.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [],
            'ai_block_operations' => [
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'Draft paragraph before AI',
                    'value_prompt' => 'Rewrite this queued paragraph.',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('ai_generation.block_updated_fields', 1);

        $targetFile = $this->templatesRoot . '/queued-prompt-block.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $rawHtml = (string) data_get($updated, 'pages.0.sections.0.raw_html');

        $this->assertStringContainsString('AI generated paragraph for queued block', $rawHtml);
        $this->assertStringNotContainsString('Draft paragraph before AI', $rawHtml);
    }

    public function test_site_creation_chains_multiple_inserted_blocks_after_same_anchor(): void
    {
        $user = User::factory()->create();

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $controls = $indexFile['section_block_controls'] ?? [];
        $targetControl = collect(is_array($controls) ? $controls : [])->first(function ($control) {
            if (!is_array($control)) {
                return false;
            }

            $blocks = $control['removable_blocks'] ?? [];
            return collect(is_array($blocks) ? $blocks : [])->contains(
                fn ($block) => is_array($block) && (($block['tag'] ?? null) === 'p')
            );
        });

        $this->assertIsArray($targetControl);
        $sectionPath = (string) ($targetControl['section_path'] ?? '');
        $removableBlocks = $targetControl['removable_blocks'] ?? [];
        $paragraphBlock = collect(is_array($removableBlocks) ? $removableBlocks : [])->first(
            fn ($block) => is_array($block) && (($block['tag'] ?? null) === 'p')
        );

        $this->assertIsArray($paragraphBlock);
        $paragraphKey = (string) ($paragraphBlock['key'] ?? '');
        $this->assertNotSame('', $paragraphKey);

        $payload = [
            'name' => 'Chained Insert Site',
            'domain' => 'chained-insert-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/chained-insert-site.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [],
            'ai_block_operations' => [
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'Inserted paragraph one',
                    'class' => '',
                    'anchor_key' => $paragraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'Inserted paragraph two',
                    'class' => '',
                    'anchor_key' => $paragraphKey,
                    'anchor_position' => 'after',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);

        $targetFile = $this->templatesRoot . '/chained-insert-site.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        preg_match('/pages\.0\.sections\.(\d+)/', $sectionPath, $matches);
        $sectionIndex = (int) ($matches[1] ?? 0);
        $rawHtml = str_replace(["\r", "\n"], '', (string) data_get($updated, "pages.0.sections.{$sectionIndex}.raw_html"));

        $this->assertStringContainsString('Inserted paragraph one', $rawHtml);
        $this->assertStringContainsString('Inserted paragraph two', $rawHtml);
        $this->assertStringContainsString(
            'Inserted paragraph one</p><p',
            $rawHtml
        );
        $this->assertStringContainsString(
            'Inserted paragraph two</p>',
            $rawHtml
        );
        $this->assertLessThan(
            strpos($rawHtml, 'Inserted paragraph two'),
            strpos($rawHtml, 'Inserted paragraph one')
        );
    }

    public function test_site_creation_keeps_later_anchor_stable_after_earlier_insertions(): void
    {
        $user = User::factory()->create();

        $sourceFile = $this->templatesRoot . '/test.com/index-raw_html.md';
        $fixture = Yaml::parseFile($sourceFile);
        $fixture['pages'][0]['sections'][0]['raw_html'] = '<section class="hero"><p class="hero__description">First original paragraph.</p><p class="hero__description">Last original paragraph.</p></section>';
        file_put_contents($sourceFile, "---\n" . Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $controls = $indexFile['section_block_controls'] ?? [];
        $firstSectionControl = is_array($controls) && isset($controls[0]) && is_array($controls[0]) ? $controls[0] : null;

        $this->assertIsArray($firstSectionControl);
        $sectionPath = (string) ($firstSectionControl['section_path'] ?? '');
        $this->assertNotSame('', $sectionPath);

        $removableBlocks = array_values(array_filter(
            $firstSectionControl['removable_blocks'] ?? [],
            fn ($block) => is_array($block) && (($block['tag'] ?? null) === 'p')
        ));

        $this->assertGreaterThanOrEqual(2, count($removableBlocks));

        $firstParagraphKey = (string) ($removableBlocks[0]['key'] ?? '');
        $lastParagraphKey = (string) ($removableBlocks[count($removableBlocks) - 1]['key'] ?? '');

        $this->assertNotSame('', $firstParagraphKey);
        $this->assertNotSame('', $lastParagraphKey);

        $payload = [
            'name' => 'Stable Later Anchor Site',
            'domain' => 'stable-later-anchor.example',
            'template_set' => 'base',
            'output_path' => 'generated/stable-later-anchor.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [],
            'ai_block_operations' => [
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'Inserted after first original.',
                    'class' => 'hero__description',
                    'anchor_key' => $firstParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => $sectionPath,
                    'action' => 'add_table_block',
                    'class' => 'payments__tables',
                    'aria_label' => 'Payment methods list',
                    'headers' => ['Method', 'Fees'],
                    'rows' => [
                        ['Visa', '3% on deposit'],
                    ],
                    'anchor_key' => $lastParagraphKey,
                    'anchor_position' => 'after',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);

        $targetFile = $this->templatesRoot . '/stable-later-anchor.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $rawHtml = str_replace(["\r", "\n"], '', (string) data_get($updated, 'pages.0.sections.0.raw_html'));

        $this->assertStringContainsString('First original paragraph.', $rawHtml);
        $this->assertStringContainsString('Inserted after first original.', $rawHtml);
        $this->assertStringContainsString('Last original paragraph.', $rawHtml);
        $this->assertStringContainsString('Payment methods table', $rawHtml);
        $this->assertStringContainsString(
            'First original paragraph.</p><p class="hero__description">Inserted after first original.</p><p class="hero__description">Last original paragraph.</p><div class="payments__tables"',
            $rawHtml
        );
        $this->assertStringNotContainsString('data-ai-anchor-key', $rawHtml);
    }

    public function test_site_creation_keeps_same_anchor_chain_separate_from_later_anchor_chain(): void
    {
        $user = User::factory()->create();

        $sourceFile = $this->templatesRoot . '/test.com/index-raw_html.md';
        $fixture = Yaml::parseFile($sourceFile);
        $fixture['pages'][0]['sections'][0]['raw_html'] = '<section class="hero"><p class="hero__description">First original paragraph.</p><p class="hero__description">Last original paragraph.</p></section>';
        file_put_contents($sourceFile, "---\n" . Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $catalog = app(AiAgentService::class)->listTemplateFields('test.com');
        $indexFile = collect($catalog)->firstWhere('file', 'index-raw_html.md');
        $controls = collect($indexFile['section_block_controls'] ?? []);
        $control = $controls->first(fn ($item) => is_array($item) && (($item['section_path'] ?? null) === 'pages.0.sections.0'));

        $this->assertIsArray($control);

        $paragraphBlocks = array_values(array_filter(
            $control['removable_blocks'] ?? [],
            fn ($block) => is_array($block) && (($block['tag'] ?? null) === 'p')
        ));

        $this->assertGreaterThanOrEqual(2, count($paragraphBlocks));

        $firstParagraphKey = (string) ($paragraphBlocks[0]['key'] ?? '');
        $lastParagraphKey = (string) ($paragraphBlocks[count($paragraphBlocks) - 1]['key'] ?? '');

        $this->assertNotSame('', $firstParagraphKey);
        $this->assertNotSame('', $lastParagraphKey);

        $payload = [
            'name' => 'Separate Chains Site',
            'domain' => 'separate-chains.example',
            'template_set' => 'base',
            'output_path' => 'generated/separate-chains.example',
            'status' => 'draft',
            'locale' => 'en',
            'default_locale' => 'en',
            'ai_clone_templates' => true,
            'ai_source_domain' => 'test.com',
            'ai_field_prompts' => [],
            'ai_field_edits' => [],
            'ai_block_operations' => [
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'тест',
                    'class' => 'hero__description',
                    'anchor_key' => $firstParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'тест2',
                    'class' => 'hero__description',
                    'anchor_key' => $firstParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'тест3',
                    'class' => 'hero__description',
                    'anchor_key' => $firstParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'тест4',
                    'class' => 'hero__description',
                    'anchor_key' => $firstParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_text',
                    'tag' => 'p',
                    'value' => 'тест5',
                    'class' => 'hero__description',
                    'anchor_key' => $firstParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_table_block',
                    'class' => 'payments__tables',
                    'aria_label' => 'Payment methods list',
                    'headers' => ['Method', 'Fees'],
                    'rows' => [
                        ['Visa', '3% on deposit'],
                    ],
                    'anchor_key' => $lastParagraphKey,
                    'anchor_position' => 'after',
                ],
                [
                    'file' => 'index-raw_html.md',
                    'section_path' => 'pages.0.sections.0',
                    'action' => 'add_table_block',
                    'class' => 'payments__tables',
                    'aria_label' => 'Payment methods list',
                    'headers' => ['Method', 'Fees'],
                    'rows' => [
                        ['Mastercard', '3% on deposit'],
                    ],
                    'anchor_key' => $lastParagraphKey,
                    'anchor_position' => 'after',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/sites', $payload);
        $response->assertStatus(201);

        $targetFile = $this->templatesRoot . '/separate-chains.example/index-raw_html.md';
        $updated = Yaml::parseFile($targetFile);
        $rawHtml = str_replace(["\r", "\n"], '', (string) data_get($updated, 'pages.0.sections.0.raw_html'));

        $this->assertMatchesRegularExpression(
            '~hero__description">First original paragraph\.</p><p class="hero__description">тест</p><p class="hero__description">тест2</p><p class="hero__description">тест3</p><p class="hero__description">тест4</p><p class="hero__description">тест5</p><p class="hero__description">Last original paragraph\.</p>~u',
            $rawHtml
        );
        $this->assertMatchesRegularExpression(
            '~hero__description">Last original paragraph\.</p><div class="payments__tables".*?<div class="payments__tables"~u',
            $rawHtml
        );
        $this->assertStringNotContainsString('data-ai-anchor-key', $rawHtml);
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

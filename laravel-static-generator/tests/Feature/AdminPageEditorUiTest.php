<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class AdminPageEditorUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewsPath = '/tmp/laravel-static-generator-tests/views-' . Str::uuid();
        File::ensureDirectoryExists($compiledViewsPath);
        config()->set('view.compiled', $compiledViewsPath);
        app()->forgetInstance('blade.compiler');
    }

    public function test_edit_page_contains_template_selector_and_module_catalog(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-page-ui@test.com',
            'password' => Hash::make('password'),
        ]);

        $site = Site::create([
            'name' => 'UI Site',
            'domain' => 'ui-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/ui-site',
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

        Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'order' => 0,
            'content' => [
                'module' => 'hero',
                'module_key' => 'hero',
                'raw_html' => '<section><header class="header"><div class="header__inner"><a href="/legacy.html">Legacy Menu</a></div></header><div class="mobile-menu"><a href="/legacy-mobile.html">Legacy Mobile</a></div><h1>Hero</h1><img src="/assets/images/hero/aviator.webp" alt="Hero"></section>',
                'render_mode' => 'raw_html',
            ],
        ]);

        $response = $this->actingAs($admin)->get("/admin/sites/{$site->id}/pages/{$page->id}/edit");

        $response->assertOk();
        $response->assertSee('name="template_key"', false);
        $response->assertSee('Apply Selected Template To Modules');
        $response->assertSee('Modules');
        $response->assertSee('Head JSON');
        $response->assertSee('Visual');
        $response->assertSee('Code');
        $response->assertSee('AI Prompt');
        $response->assertSee('Medium main');
        $response->assertSee('Previous and next modules');
        $response->assertSee('Generate');
        $response->assertDontSee('Legacy Menu');
        $response->assertDontSee('Legacy Mobile');
        $response->assertDontSee('Add Section');
        $response->assertDontSee('Section Type');
    }

    public function test_preview_button_does_not_silently_resave_all_sections(): void
    {
        $source = File::get(resource_path('views/admin/pages/edit.blade.php'));
        $this->assertIsString($source);

        preg_match('/async function openPreview\(\) \{(?P<body>.*?)async function applyTemplateToSections\(\)/s', $source, $matches);

        $this->assertArrayHasKey('body', $matches);
        $this->assertStringNotContainsString('saveAllSectionsSilently()', $matches['body']);
        $this->assertStringContainsString("fetch('/api/pages/{{ \$page->id }}/preview-token'", $matches['body']);
    }

    public function test_page_editor_preserves_complex_raw_html_when_editing_images(): void
    {
        $source = File::get(resource_path('js/page-editor.js'));
        $this->assertIsString($source);

        $this->assertStringContainsString('function isComplexRawHtml(rawHtml)', $source);
        $this->assertStringContainsString('function shouldPreserveRawHtml(container, rawHtml)', $source);
        $this->assertStringContainsString('data-raw-image-index', $source);
        $this->assertStringContainsString('function patchRawImageAttributes(state, rawImageIndex, attrs)', $source);
        $this->assertStringContainsString('function generatedBackgroundOverrideConfig(target)', $source);
        $this->assertStringContainsString('function uploadGeneratedBackgroundOverride(sectionId, targetPath, file)', $source);
        $this->assertStringContainsString('function syntheticBackgroundTargets(moduleKey)', $source);
        $this->assertStringContainsString('function ensureBackgroundStyleOverride(rawHtml, target, nextUrl)', $source);
        $this->assertStringContainsString('function patchRawTextNodesFromEditor(state)', $source);
        $this->assertStringContainsString('function normalizePatchText(text)', $source);
        $this->assertStringContainsString('function findRawTextNodeSpan(rawTextNodes, searchOffset, text)', $source);
        $this->assertStringContainsString('function splitTextAcrossRawParts(text, parts)', $source);
        $this->assertStringContainsString('function buildRawTextNodeMap(rawHtml, editorTexts)', $source);
        $this->assertStringContainsString('normalizePatchText(node.textContent) === normalizedText', $source);
        $this->assertStringContainsString('mappedEntry = findRawTextNodeSpan(rawTextNodes, searchOffset, text) || -1;', $source);
        $this->assertStringContainsString('rawTextNodes[nodeIndex].textContent = nextParts[partIndex] || \'\';', $source);
        $this->assertStringContainsString('state.rawTextNodeMap = buildRawTextNodeMap(state.originalRawHtml, nextTexts)', $source);
        $this->assertStringContainsString('rawTextNodes[rawIndex].textContent = text', $source);
        $this->assertStringContainsString('state.preserveRawHtml ? state.originalRawHtml : getEditorHtml(state.editor)', $source);
    }

    public function test_page_editor_visual_mode_covers_text_and_image_saves_for_complex_sections(): void
    {
        $source = File::get(resource_path('js/page-editor.js'));
        $this->assertIsString($source);

        $coveredModules = collect(File::files(resource_path('views/defaults/modules')))
            ->mapWithKeys(fn ($file) => [$file->getFilenameWithoutExtension() => File::get($file->getPathname())])
            ->filter(fn ($html) => str_contains($html, '<img') && preg_match('/>\s*[^<\s][^<]*\s*</u', $html))
            ->keys()
            ->all();

        $this->assertContains('casino', $coveredModules);
        $this->assertContains('hero-main', $coveredModules);
        $this->assertStringContainsString('<span class="casino__title-top">Where', File::get(resource_path('views/defaults/modules/casino-where-to-play-app.html')));
        $this->assertGreaterThan(20, count($coveredModules), 'Default module fixtures should cover text and image editing across sections.');

        $this->assertStringContainsString("return node.textContent === text || normalizePatchText(node.textContent) === normalizedText;", $source);
        $this->assertStringContainsString('patchRawImageAttributes(state, imageAttrs.rawImageIndex, {', $source);
        $this->assertStringContainsString("if (refreshedState.activeTab === 'code')", $source);
        $this->assertStringContainsString('syncEditorFromCode(container);', $source);
        $this->assertStringContainsString('syncJsonFromEditor(container);', $source);
    }

    public function test_shared_block_editor_forces_raw_html_preservation(): void
    {
        $source = File::get(resource_path('views/admin/pages/edit-shared.blade.php'));
        $this->assertIsString($source);

        $this->assertStringContainsString('class="section-item" data-preserve-raw-html="true"', $source);
        $this->assertStringContainsString('setSharedButtonBusy(button, true)', $source);
        $this->assertStringContainsString('{{ strtoupper($part) }} saved at', $source);
    }

    public function test_save_changes_saves_page_modules_and_reports_status(): void
    {
        $source = File::get(resource_path('views/admin/pages/edit.blade.php'));
        $this->assertIsString($source);

        preg_match('/async function handlePageSave\(options = \{\}\) \{(?P<body>.*?)document\.getElementById\(\'page-form\'\)/s', $source, $matches);

        $this->assertArrayHasKey('body', $matches);
        $this->assertStringContainsString('const sectionsSaved = await saveAllSectionsSilently();', $matches['body']);
        $this->assertStringContainsString('Page and modules saved at', $matches['body']);
        $this->assertStringContainsString('setInlineButtonBusy(button, true)', $source);
        $this->assertStringContainsString('Saving module #${sectionId}', $source);
        $this->assertStringContainsString('window.savePageSection = saveSection;', $source);
    }

    public function test_create_site_index_raw_html_fields_are_grouped_by_sections(): void
    {
        $admin = User::create([
            'name' => 'Admin Create Site',
            'email' => 'admin-create-site-ui@test.com',
            'password' => Hash::make('password'),
        ]);

        $templatesRoot = '/tmp/laravel-static-generator-tests/create-site-ui-' . Str::uuid();
        $sourceDir = $templatesRoot . '/test.com';
        File::ensureDirectoryExists($sourceDir);
        config()->set('services.ai_agent.templates_root', $templatesRoot);

        $fixture = [
            'domain' => 'test.com',
            'name' => 'test.com',
            'template' => 'test',
            'output_path' => 'generated/test.com',
            'status' => 'active',
            'locale' => 'en',
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
                'og_data' => [
                    'head_extra' => implode("\n", [
                        '<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Old Meta","description":"Old Description"}</script>',
                        '<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"Hidden Organization"}</script>',
                        '<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","name":"Hidden FAQ"}</script>',
                        '<script type="application/ld+json">{"@context":"https://schema.org","@type":"HowTo","name":"Hidden HowTo"}</script>',
                        '<script type="application/ld+json">{"@context":"https://schema.org","@type":"BreadcrumbList","name":"Hidden Breadcrumb"}</script>',
                    ]),
                ],
                'sections' => [[
                    'module' => 'hero',
                    'module_key' => 'hero',
                    'raw_html' => '<section class="hero"><h1>Hero Title</h1><p>Hero description</p><ul class="hero__list"><li>First item</li></ul></section>',
                    'render_mode' => 'raw_html',
                ]],
            ]],
        ];

        File::put($sourceDir . '/index-raw_html.md', "---\n" . Yaml::dump($fixture, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        $response = $this->actingAs($admin)->get('/admin/sites/create');

        $response->assertOk();
        $response->assertSee('create-site-import-file', false);
        $response->assertSee('site-import-status', false);
        $response->assertSee('site-import-status-summary', false);
        $response->assertSee('site-import-status-warnings', false);
        $response->assertSee('site-create-report', false);
        $response->assertSee('site-create-report-copy', false);
        $response->assertSee('site-create-report-body', false);
        $response->assertSee('Import');
        $response->assertSee('SECTION HEAD');
        $response->assertSee('SECTION HERO');
        $response->assertSee('HEAD META');
        $response->assertSee('Head JSON-LD script block');
        $response->assertDontSee('Head JSON-LD script block #1');
        $response->assertDontSee('Head JSON-LD script block #2');
        $response->assertDontSee('Head JSON-LD script block #3');
        $response->assertDontSee('Head JSON-LD script block #4');
        $response->assertDontSee('Head JSON-LD script block #5');
        $response->assertSee('Queue Add LI');
        $response->assertSee('Add standard block');
        $response->assertSee('Bulleted list');
        $response->assertSee('Payment table');
        $response->assertDontSee('Queue structural changes for blocks');
    }

    public function test_create_site_syncs_published_and_modified_time_fields(): void
    {
        $source = File::get(resource_path('views/admin/sites/create.blade.php'));
        $this->assertIsString($source);

        $this->assertStringContainsString('function primaryPublishedTimeMetaPath()', $source);
        $this->assertStringContainsString("return 'pages.0.og_data.head_meta.5.content';", $source);
        $this->assertStringContainsString('function extractJsonLdPublishedTime(value)', $source);
        $this->assertStringContainsString('function applyImportedPublishedTime(preferredSource = null)', $source);
        $this->assertStringContainsString('let importedPublishedTimeSource = null;', $source);
        $this->assertStringContainsString('applyImportedPublishedTime(importedPublishedTimeSource);', $source);
        $this->assertStringContainsString('syncPublishedTimeFromJsonLd({ autoManaged: false });', $source);
        $this->assertStringContainsString('if (rowPath === primaryPublishedTimeMetaPath())', $source);
        $this->assertStringContainsString('function refreshAutoManagedDates()', $source);
        $this->assertStringContainsString('const timestamp = currentUtcIsoTimestamp();', $source);
        $this->assertStringContainsString('refreshAutoManagedDates();', $source);
    }

    public function test_edit_site_page_contains_creation_log_button(): void
    {
        $admin = User::create([
            'name' => 'Admin Edit Site',
            'email' => 'admin-edit-site-ui@test.com',
            'password' => Hash::make('password'),
        ]);

        $site = Site::create([
            'name' => 'UI Site',
            'domain' => 'ui-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/ui-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $response = $this->actingAs($admin)->get("/admin/sites/{$site->id}/edit");

        $response->assertOk();
        $response->assertSee('View Creation Log');
        $response->assertSee("/admin/sites/{$site->id}/creation-log", false);
        $response->assertSee('site-edit-status', false);
        $response->assertSee('site-edit-status-text', false);
    }

    public function test_creation_log_page_contains_inline_status_block(): void
    {
        $admin = User::create([
            'name' => 'Admin Creation Log',
            'email' => 'admin-creation-log-ui@test.com',
            'password' => Hash::make('password'),
        ]);

        $templatesRoot = '/tmp/laravel-static-generator-tests/creation-log-ui-' . Str::uuid();
        $siteDir = $templatesRoot . '/ui-site.example';
        File::ensureDirectoryExists($siteDir);
        File::put($siteDir . '/site-create-report.txt', "Site created successfully.\n");
        config()->set('services.ai_agent.templates_root', $templatesRoot);

        $site = Site::create([
            'name' => 'UI Site',
            'domain' => 'ui-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/ui-site',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $response = $this->actingAs($admin)->get("/admin/sites/{$site->id}/creation-log");

        $response->assertOk();
        $response->assertSee('Copy Report');
        $response->assertSee('site-create-log-status', false);
        $response->assertSee('site-create-log-status-text', false);
    }
}

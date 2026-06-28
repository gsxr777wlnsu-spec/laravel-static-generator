<?php

namespace Tests\Feature;

use App\Models\Page;
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

        $response = $this->actingAs($admin)->get("/admin/sites/{$site->id}/pages/{$page->id}/edit");

        $response->assertOk();
        $response->assertSee('name="template_key"', false);
        $response->assertSee('Apply Selected Template To Modules');
        $response->assertSee('Modules');
        $response->assertDontSee('Add Section');
        $response->assertDontSee('Section Type');
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

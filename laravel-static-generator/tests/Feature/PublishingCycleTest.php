<?php

namespace Tests\Feature;

use App\Contracts\SftpClientInterface;
use App\Contracts\DeployServiceInterface;
use App\Models\Deployment;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Services\GitService;
use App\Services\LanguageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublishingCycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-cycle@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_generate_then_preview_cycle_works(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $site = Site::create([
            'name' => 'Cycle Site',
            'domain' => 'cycle.example',
            'template_set' => 'base',
            'output_path' => 'generated/cycle',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home Page',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Page::create([
            'site_id' => $site->id,
            'slug' => 'terms-and-conditions',
            'title' => 'Terms And Conditions',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'heading' => 'Welcome',
                'content' => '<p>Generated content</p>',
                'module' => 'hero-main',
                'id' => 'hero-main',
                'class' => 'hero-section reusable-block',
            ],
        ]);

        Storage::disk('sites')->put("{$site->id}/assets/js/app.js", 'console.log("from app.js");');
        Storage::disk('sites')->put("{$site->id}/assets/css/style.css", '.hero{background-image:url("/assets/images/hero/hero-background.webp")}');
        Storage::disk('sites')->put("{$site->id}/assets/images/hero/hero-background.webp", 'preview-image');
        Storage::disk('sites')->put("{$site->id}/assets/images/favicon/site.webmanifest", '{"name":"Preview Site"}');

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $generateResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $generateResponse->assertOk();
        $this->assertTrue(
            (bool) $generateResponse->json('success'),
            'Generation failed: '.json_encode($generateResponse->json(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        Storage::disk('generated')->assertExists("site{$site->id}/index.html");
        Storage::disk('generated')->assertExists("site{$site->id}/sitemap.xml");
        Storage::disk('generated')->assertExists("site{$site->id}/robots.txt");
        Storage::disk('generated')->assertExists("site{$site->id}/assets/js/main.js");
        $this->assertSame(
            'console.log("from app.js");',
            Storage::disk('generated')->get("site{$site->id}/assets/js/main.js")
        );

        $previewTokenResponse = $this->actingAs($this->admin)->postJson("/api/pages/{$page->id}/preview-token");
        $previewTokenResponse->assertOk();
        $previewTokenResponse->assertJsonStructure(['preview_url', 'expires_at']);

        $previewUrl = $previewTokenResponse->json('preview_url');
        $this->assertIsString($previewUrl);
        $this->assertStringStartsWith('/api/preview/', $previewUrl);

        preg_match('#^/api/preview/([^/]+)/#', (string) $previewUrl, $matches);
        $this->assertArrayHasKey(1, $matches);
        $previewToken = $matches[1];
        Storage::disk('generated')->assertExists("preview/{$previewToken}/terms-and-conditions.html");
        Storage::disk('generated')->assertExists("preview/{$previewToken}/assets/js/main.js");
        Storage::disk('generated')->assertExists("preview/{$previewToken}/assets/css/style.css");
        $previewHtml = Storage::disk('generated')->get("preview/{$previewToken}/index.html");
        $this->assertStringContainsString("href=\"/api/preview/{$previewToken}/index.html#where-to-play\"", $previewHtml);
        $this->assertStringContainsString('href="terms-and-conditions.html"', $previewHtml);
        $previewCss = Storage::disk('generated')->get("preview/{$previewToken}/assets/css/style.css");
        $this->assertStringContainsString("/api/preview/{$previewToken}/assets/images/hero/hero-background.webp", $previewCss);
        $this->assertStringNotContainsString('url("/assets/', $previewCss);

        $manifestResponse = $this->get("/api/preview/{$previewToken}/assets/images/favicon/site.webmanifest");
        $manifestResponse->assertOk();
        $manifestResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $previewResponse = $this->get($previewUrl);

        $previewResponse->assertOk();
        $previewResponse->assertSee('Home Page');
        $previewResponse->assertSee('Welcome');
    }

    public function test_base_site_generation_upload_preview_and_delete_removes_all_local_artifacts(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $siteResponse = $this->actingAs($this->admin)->postJson('/api/sites', [
            'name' => 'Delete Base Cycle',
            'domain' => 'delete-base-cycle.example',
            'template_set' => 'base',
            'output_path' => 'generated/delete-base-cycle',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);
        $siteResponse->assertCreated();

        $siteId = (int) $siteResponse->json('id');
        $site = Site::findOrFail($siteId);

        $page = Page::create([
            'site_id' => $siteId,
            'slug' => 'index',
            'title' => 'Base Delete Cycle',
            'status' => 'published',
            'locale' => 'en',
        ]);

        $section = Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'heading' => 'Delete Cycle',
                'content' => '<p>Generated base content</p>',
                'module' => 'hero-main',
                'id' => 'hero-main',
                'class' => 'hero-section reusable-block',
            ],
        ]);

        Storage::disk('sites')->put("{$siteId}/assets/js/app.js", 'console.log("delete cycle");');
        Storage::disk('sites')->put("{$siteId}/assets/css/style.css", '.hero{background-image:url("/assets/images/upload/photo.webp")}');

        $uploadResponse = $this->actingAs($this->admin)->postJson('/api/media', [
            'site_id' => $siteId,
            'file' => UploadedFile::fake()->create('photo.webp', 16, 'image/webp'),
            'alt' => 'Uploaded photo',
            'target_directory' => 'assets/images/upload',
        ]);
        $uploadResponse->assertCreated();

        $mediaId = (int) $uploadResponse->json('id');
        $mediaPath = (string) $uploadResponse->json('path');
        Storage::disk('sites')->assertExists($mediaPath);

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $generateResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$siteId}/generate");
        $generateResponse->assertOk();
        $this->assertTrue((bool) $generateResponse->json('success'));
        Storage::disk('generated')->assertExists("site{$siteId}/index.html");
        Storage::disk('generated')->assertExists("site{$siteId}/assets/images/upload/" . basename($mediaPath));

        $previewTokenResponse = $this->actingAs($this->admin)->postJson("/api/pages/{$page->id}/preview-token");
        $previewTokenResponse->assertOk();
        preg_match('#^/api/preview/([^/]+)/#', (string) $previewTokenResponse->json('preview_url'), $matches);
        $this->assertArrayHasKey(1, $matches);
        $previewToken = $matches[1];

        Storage::disk('generated')->assertExists("preview/{$previewToken}/.site.json");
        Storage::disk('generated')->assertExists("preview/{$previewToken}/assets/images/upload/" . basename($mediaPath));
        $this->assertCount(1, Storage::disk('generated')->directories('preview'));

        $deleteResponse = $this->actingAs($this->admin)->deleteJson("/api/sites/{$siteId}");
        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('sites', ['id' => $siteId]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('media', ['id' => $mediaId]);

        $this->assertFalse(Storage::disk('sites')->exists((string) $siteId));
        $this->assertFalse(Storage::disk('generated')->exists("site{$siteId}"));
        $this->assertFalse(Storage::disk('generated')->exists("preview/{$previewToken}"));
        $this->assertSame([], Storage::disk('generated')->directories('preview'));
    }

    public function test_multilingual_site_generates_language_folders_shared_blocks_and_preview_assets(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $site = Site::create([
            'name' => 'Multilingual Site',
            'domain' => 'multilingual.example',
            'template_set' => 'base',
            'output_path' => 'generated/multilingual',
            'status' => 'active',
            'locale' => 'en_US',
            'default_locale' => 'en_US',
            'alternate_locales' => ['en', 'es', 'de'],
            'menu_html' => '<div class="header__inner"><div class="header__logo"><a class="header__logo-wrapper" href="/">Logo</a></div><nav class="header__nav menu"><ul class="menu__list"><li class="menu__item"><a class="menu__link" href="/">App</a></li></ul></nav></div>',
            'footer_html' => '<div class="footer__inner"><div class="footer__logo"><a class="footer__logo-wrapper" href="/">Logo</a></div><a href="/privacy-policy.html">Privacy Policy</a></div>',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home Page',
            'status' => 'published',
            'locale' => 'en_US',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'module' => 'hero-main',
                'raw_html' => '<section class="hero"><h1>Home</h1></section>',
            ],
        ]);

        $privacy = Page::create([
            'site_id' => $site->id,
            'slug' => 'privacy-policy',
            'title' => 'Privacy Policy',
            'status' => 'published',
            'locale' => 'en_US',
        ]);

        Section::create([
            'page_id' => $privacy->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'module' => 'content',
                'raw_html' => '<section><h1>Privacy Policy</h1></section>',
            ],
        ]);

        $sitemap = Page::create([
            'site_id' => $site->id,
            'slug' => 'sitemap',
            'title' => 'Sitemap',
            'status' => 'published',
            'locale' => 'en_US',
        ]);

        Section::create([
            'page_id' => $sitemap->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'module' => 'sitemap',
            ],
        ]);

        Storage::disk('sites')->put("{$site->id}/assets/js/main.js", 'console.log("main");');
        Storage::disk('sites')->put("{$site->id}/assets/css/style.css", 'body{background:url("/assets/images/bg.webp")}');
        Storage::disk('sites')->put("{$site->id}/assets/images/bg.webp", 'image');

        app(LanguageService::class)->prepareSiteLanguages($site, ['en', 'es', 'de']);
        $site = $site->fresh();

        $this->assertDatabaseHas('pages', ['site_id' => $site->id, 'slug' => 'index', 'locale' => 'en_US']);
        $this->assertDatabaseHas('pages', ['site_id' => $site->id, 'slug' => 'index', 'locale' => 'es']);
        $this->assertDatabaseHas('pages', ['site_id' => $site->id, 'slug' => 'privacy-policy', 'locale' => 'de']);
        $this->assertDatabaseHas('site_shared_blocks', ['site_id' => $site->id, 'locale' => 'es']);

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $generateResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $generateResponse->assertOk();

        Storage::disk('generated')->assertExists("site{$site->id}/index.html");
        Storage::disk('generated')->assertExists("site{$site->id}/es/index.html");
        Storage::disk('generated')->assertExists("site{$site->id}/de/privacy-policy.html");
        Storage::disk('generated')->assertExists("site{$site->id}/es/sitemap.html");

        $spanishHtml = Storage::disk('generated')->get("site{$site->id}/es/index.html");
        $this->assertStringContainsString('lang="es"', $spanishHtml);
        $this->assertStringContainsString('menu__item--lang', $spanishHtml);
        $this->assertStringContainsString('/de/', $spanishHtml);
        $this->assertStringContainsString('Aplicación', $spanishHtml);
        $this->assertStringContainsString('class="header__logo-wrapper" href="/es/"', $spanishHtml);
        $this->assertStringContainsString('class="footer__logo-wrapper" href="/es/"', $spanishHtml);
        $defaultHtml = Storage::disk('generated')->get("site{$site->id}/index.html");
        $this->assertStringContainsString('lang="en_US"', $defaultHtml);

        $spanishSitemapHtml = Storage::disk('generated')->get("site{$site->id}/es/sitemap.html");
        $this->assertStringContainsString('class="sitemap__link" href="/es/privacy-policy.html"', $spanishSitemapHtml);
        $this->assertStringNotContainsString('class="sitemap__link" href="/privacy-policy.html"', $spanishSitemapHtml);
        $this->assertStringNotContainsString('class="sitemap__link" href="/de/privacy-policy.html"', $spanishSitemapHtml);

        $spanishPage = Page::where('site_id', $site->id)->where('slug', 'index')->where('locale', 'es')->firstOrFail();
        $previewTokenResponse = $this->actingAs($this->admin)->postJson("/api/pages/{$spanishPage->id}/preview-token");
        $previewTokenResponse->assertOk();

        $previewUrl = (string) $previewTokenResponse->json('preview_url');
        $this->assertStringContainsString('/es/index.html', $previewUrl);
        preg_match('#^/api/preview/([^/]+)/#', $previewUrl, $matches);
        $previewToken = $matches[1] ?? '';

        Storage::disk('generated')->assertExists("preview/{$previewToken}/es/index.html");
        $previewHtml = Storage::disk('generated')->get("preview/{$previewToken}/es/index.html");
        $this->assertStringContainsString("/api/preview/{$previewToken}/assets/css/style.css", $previewHtml);
        $this->assertStringContainsString("/api/preview/{$previewToken}/de/index.html", $previewHtml);
        $this->assertStringContainsString("class=\"header__logo-wrapper\" href=\"/api/preview/{$previewToken}/es/index.html\"", $previewHtml);
        $this->assertStringContainsString("class=\"footer__logo-wrapper\" href=\"/api/preview/{$previewToken}/es/index.html\"", $previewHtml);
        $this->assertStringNotContainsString('href="assets/css/style.css"', $previewHtml);
    }

    public function test_generate_falls_back_to_complete_generated_assets_when_site_assets_are_incomplete(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $site = Site::create([
            'name' => 'Fallback Asset Site',
            'domain' => 'fallback-assets.example',
            'template_set' => 'base',
            'output_path' => 'generated/fallback-assets',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Fallback Assets',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'heading' => 'Fallback',
                'content' => '<p>Fallback asset content</p>',
                'module' => 'hero-main',
                'id' => 'hero-main',
                'class' => 'hero-section reusable-block',
            ],
        ]);

        Storage::disk('sites')->put("{$site->id}/assets/images/upload/placeholder.txt", 'incomplete');
        Storage::disk('generated')->put('site1/assets/css/style.css', '.fallback{color:#222;}');
        Storage::disk('generated')->put('site1/assets/js/app.js', 'console.log("fallback source");');
        Storage::disk('generated')->put('site1/assets/images/favicon/site.webmanifest', '{"name":"Fallback Assets"}');

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $generateResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $generateResponse->assertOk();
        $this->assertTrue((bool) $generateResponse->json('success'));

        Storage::disk('generated')->assertExists("site{$site->id}/assets/css/style.css");
        Storage::disk('generated')->assertExists("site{$site->id}/assets/js/main.js");
        Storage::disk('generated')->assertExists("site{$site->id}/assets/images/favicon/site.webmanifest");
        $this->assertSame(
            '.fallback{color:#222;}',
            Storage::disk('generated')->get("site{$site->id}/assets/css/style.css")
        );
        $this->assertSame(
            'console.log("fallback source");',
            Storage::disk('generated')->get("site{$site->id}/assets/js/main.js")
        );
    }

    public function test_generate_aliases_fallback_main_css_to_style_css(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $site = Site::create([
            'name' => 'Main Css Fallback Site',
            'domain' => 'main-css-fallback.example',
            'template_set' => 'base',
            'output_path' => 'generated/main-css-fallback',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Main Css Fallback',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Storage::disk('generated')->put('site1/assets/css/main.css', '.main-css{color:#123;}');
        Storage::disk('generated')->put('site1/assets/js/main.js', 'console.log("main css fallback");');

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $generateResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $generateResponse->assertOk();
        $this->assertTrue((bool) $generateResponse->json('success'));

        Storage::disk('generated')->assertExists("site{$site->id}/assets/css/main.css");
        Storage::disk('generated')->assertExists("site{$site->id}/assets/css/style.css");
        $this->assertSame(
            '.main-css{color:#123;}',
            Storage::disk('generated')->get("site{$site->id}/assets/css/style.css")
        );
    }

    public function test_preview_copies_partial_generated_site_assets_when_no_complete_asset_set_exists(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $site = Site::create([
            'name' => 'Partial Asset Preview Site',
            'domain' => 'partial-preview.example',
            'template_set' => 'base',
            'output_path' => 'generated/partial-preview',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Partial Preview',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'text',
            'order' => 1,
            'content' => [
                'heading' => 'Partial Preview',
                'content' => '<p>Preview content</p>',
                'module' => 'hero-main',
                'id' => 'hero-main',
                'class' => 'hero-section reusable-block',
            ],
        ]);

        Storage::disk('generated')->put("site{$site->id}/assets/images/hero/hero.webp", 'hero-image');

        $previewTokenResponse = $this->actingAs($this->admin)->postJson("/api/pages/{$page->id}/preview-token");
        $previewTokenResponse->assertOk();

        preg_match('#^/api/preview/([^/]+)/#', (string) $previewTokenResponse->json('preview_url'), $matches);
        $this->assertArrayHasKey(1, $matches);

        Storage::disk('generated')->assertExists("preview/{$matches[1]}/assets/images/hero/hero.webp");
        $this->assertSame(
            'hero-image',
            Storage::disk('generated')->get("preview/{$matches[1]}/assets/images/hero/hero.webp")
        );
    }

    public function test_sitemap_module_is_dynamic_and_uses_latest_pages(): void
    {
        $this->useTemporaryGeneratedDisk();
        $this->useTemporarySitesDisk();

        $site = Site::create([
            'name' => 'Sitemap Dynamic Site',
            'domain' => 'sitemap-dynamic.example',
            'template_set' => 'test',
            'output_path' => 'generated/sitemap-dynamic',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home Page',
            'template_key' => 'index',
            'status' => 'published',
            'locale' => 'en',
        ]);

        $appPage = Page::create([
            'site_id' => $site->id,
            'slug' => 'app',
            'title' => 'Download App',
            'template_key' => 'app',
            'status' => 'published',
            'locale' => 'en',
        ]);

        $sitemapPage = Page::create([
            'site_id' => $site->id,
            'slug' => 'sitemap',
            'title' => 'Sitemap',
            'template_key' => 'sitemap',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $sitemapPage->id,
            'type' => 'module',
            'module' => 'sitemap',
            'module_key' => 'sitemap',
            'order' => 1,
            'content' => [
                'module' => 'sitemap',
                'module_key' => 'sitemap',
                'class' => 'sitemap',
                'id' => 'sitemap',
                'heading' => 'Sitemap',
            ],
        ]);

        $mockGitService = \Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('setRepositoryPath')->andReturnSelf();
        $mockGitService->shouldReceive('commit')->andReturnNull();
        $this->app->instance(GitService::class, $mockGitService);

        $firstGenerate = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $firstGenerate->assertOk();
        $this->assertTrue((bool) $firstGenerate->json('success'));

        Storage::disk('generated')->assertExists("site{$site->id}/sitemap.xml");
        Storage::disk('generated')->assertExists("site{$site->id}/sitemap.html");

        $firstGeneratedFiles = $firstGenerate->json('generated_files', []);
        $xmlIndex = array_search("site{$site->id}/sitemap.xml", $firstGeneratedFiles, true);
        $sitemapHtmlIndex = array_search("site{$site->id}/sitemap.html", $firstGeneratedFiles, true);
        $this->assertNotFalse($xmlIndex);
        $this->assertNotFalse($sitemapHtmlIndex);
        $this->assertTrue($xmlIndex < $sitemapHtmlIndex, 'sitemap.html must be generated after sitemap.xml');

        $firstSitemapHtml = Storage::disk('generated')->get("site{$site->id}/sitemap.html");
        $this->assertStringContainsString('href="/"', $firstSitemapHtml);
        $this->assertStringContainsString('href="app.html"', $firstSitemapHtml);
        $this->assertStringContainsString('Download App', $firstSitemapHtml);

        $appPage->delete();

        Page::create([
            'site_id' => $site->id,
            'slug' => 'tips',
            'title' => 'Tips And Tricks',
            'template_key' => 'tips',
            'status' => 'published',
            'locale' => 'en',
        ]);

        $secondGenerate = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/generate");
        $secondGenerate->assertOk();
        $this->assertTrue((bool) $secondGenerate->json('success'));

        $secondSitemapHtml = Storage::disk('generated')->get("site{$site->id}/sitemap.html");
        $this->assertStringNotContainsString('href="app.html"', $secondSitemapHtml);
        $this->assertStringContainsString('href="tips.html"', $secondSitemapHtml);
        $this->assertStringContainsString('Tips And Tricks', $secondSitemapHtml);
    }

    public function test_deploy_uses_generated_directory_and_completes(): void
    {
        $this->useTemporaryGeneratedDisk();

        $site = Site::create([
            'name' => 'Deploy Site',
            'domain' => 'deploy.example',
            'template_set' => 'base',
            'output_path' => 'generated/deploy',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => 'sftp.example.com',
            'sftp_username' => 'deploy-user',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/deploy.example',
        ]);

        Storage::disk('generated')->put("site{$site->id}/index.html", '<html>ok</html>');

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('testConnection')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('connect')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('backupDirectory')->once()->withArgs(function (Site $argSite, string $remotePath, string $backupPath) use ($site) {
            return $argSite->id === $site->id
                && $remotePath === '/var/www/deploy.example'
                && str_starts_with($backupPath, '/var/www/deploy.example.backup-');
        })->andReturn(true);
        $mockSftp->shouldReceive('uploadDirectory')->once()->withArgs(function (Site $argSite, string $localPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $localPath === "site{$site->id}"
                && $remotePath === '/var/www/deploy.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('verifyUploadedFiles')->once()->withArgs(function (Site $argSite, string $localPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $localPath === "site{$site->id}"
                && $remotePath === '/var/www/deploy.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $deployResponse = $this->actingAs($this->admin)->postJson("/api/sites/{$site->id}/deploy", [
            'run_post_deploy_commands' => false,
        ]);
        $deployResponse->assertOk();
        $deployResponse->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('deployments', [
            'site_id' => $site->id,
            'status' => 'completed',
        ]);
    }

    public function test_deploy_with_post_commands_runs_remote_commands(): void
    {
        $this->useTemporaryGeneratedDisk();

        $site = Site::create([
            'name' => 'Deploy Import Site',
            'domain' => 'import.example',
            'template_set' => 'base',
            'output_path' => 'generated/import',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => '37.1.217.183',
            'sftp_username' => 'root',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/import.example',
        ]);

        Storage::disk('generated')->put("site{$site->id}/index.html", '<html>ok</html>');

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->withArgs(function (Site $argSite) use ($site) {
            return $argSite->id === $site->id;
        })->andReturn(true);
        $mockSftp->shouldReceive('backupDirectory')->once()->withArgs(function (Site $argSite, string $remotePath, string $backupPath) use ($site) {
            return $argSite->id === $site->id
                && $remotePath === '/var/www/import.example'
                && str_starts_with($backupPath, '/var/www/import.example.backup-');
        })->andReturn(true);
        $mockSftp->shouldReceive('uploadDirectory')->once()->withArgs(function (Site $argSite, string $localPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $localPath === "site{$site->id}"
                && $remotePath === '/var/www/import.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('verifyUploadedFiles')->once()->withArgs(function (Site $argSite, string $localPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $localPath === "site{$site->id}"
                && $remotePath === '/var/www/import.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('runPostDeployCommands')->once()->withArgs(function (Site $argSite, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $remotePath === '/var/www/import.example';
        });
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $deployment = $this->app->make(DeployServiceInterface::class)->deploy($site, true);

        $this->assertSame('completed', $deployment->status);
        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'status' => 'completed',
            'remote_path' => '/var/www/import.example',
        ]);
    }

    public function test_manual_rollback_restores_remote_backup(): void
    {
        $site = Site::create([
            'name' => 'Rollback Site',
            'domain' => 'rollback.example',
            'template_set' => 'base',
            'output_path' => 'generated/rollback',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => 'sftp.example.com',
            'sftp_username' => 'deploy-user',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/rollback.example',
        ]);

        $deployment = Deployment::create([
            'site_id' => $site->id,
            'status' => 'completed',
            'remote_path' => '/var/www/rollback.example',
            'backup_path' => '/var/www/rollback.example.backup-20260509000000-1',
            'log' => 'Deployment completed successfully',
        ]);

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->withArgs(fn (Site $argSite) => $argSite->id === $site->id)->andReturn(true);
        $mockSftp->shouldReceive('restoreDirectory')->once()->withArgs(function (Site $argSite, string $backupPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && $backupPath === '/var/www/rollback.example.backup-20260509000000-1'
                && $remotePath === '/var/www/rollback.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $this->assertTrue($this->app->make(DeployServiceInterface::class)->rollback($deployment));

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'status' => 'rolled_back',
        ]);
    }

    public function test_failed_deploy_restores_backup(): void
    {
        $this->useTemporaryGeneratedDisk();

        $site = Site::create([
            'name' => 'Failed Deploy Site',
            'domain' => 'failed-deploy.example',
            'template_set' => 'base',
            'output_path' => 'generated/failed-deploy',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'sftp_host' => 'sftp.example.com',
            'sftp_username' => 'deploy-user',
            'sftp_auth_method' => 'password',
            'sftp_remote_path' => '/var/www/failed-deploy.example',
        ]);

        Storage::disk('generated')->put("site{$site->id}/index.html", '<html>ok</html>');

        $mockSftp = \Mockery::mock(SftpClientInterface::class);
        $mockSftp->shouldReceive('connect')->once()->withArgs(fn (Site $argSite) => $argSite->id === $site->id)->andReturn(true);
        $mockSftp->shouldReceive('backupDirectory')->once()->andReturn(true);
        $mockSftp->shouldReceive('uploadDirectory')->once()->andReturn(false);
        $mockSftp->shouldReceive('restoreDirectory')->once()->withArgs(function (Site $argSite, string $backupPath, string $remotePath) use ($site) {
            return $argSite->id === $site->id
                && str_starts_with($backupPath, '/var/www/failed-deploy.example.backup-')
                && $remotePath === '/var/www/failed-deploy.example';
        })->andReturn(true);
        $mockSftp->shouldReceive('disconnect')->once();

        $this->app->instance(SftpClientInterface::class, $mockSftp);

        $deployment = $this->app->make(DeployServiceInterface::class)->deploy($site);

        $this->assertSame('failed', $deployment->status);
        $this->assertStringContainsString('Rollback restored from backup', (string) $deployment->log);
    }

    private function useTemporaryGeneratedDisk(): void
    {
        $root = '/tmp/laravel-static-generator-tests/generated-' . Str::uuid();
        File::ensureDirectoryExists($root);

        config()->set('filesystems.disks.generated.root', $root);
        Storage::forgetDisk('generated');

        $compiledViewsPath = '/tmp/laravel-static-generator-tests/views-' . Str::uuid();
        File::ensureDirectoryExists($compiledViewsPath);
        config()->set('view.compiled', $compiledViewsPath);
        app()->forgetInstance('blade.compiler');
    }

    private function useTemporarySitesDisk(): void
    {
        $root = '/tmp/laravel-static-generator-tests/sites-' . Str::uuid();
        File::ensureDirectoryExists($root);

        config()->set('filesystems.disks.sites.root', $root);
        Storage::forgetDisk('sites');
    }
}

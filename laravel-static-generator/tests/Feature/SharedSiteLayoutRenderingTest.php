<?php

namespace Tests\Feature;

use App\Contracts\HtmlGeneratorInterface;
use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedSiteLayoutRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_menu_and_footer_render_for_all_pages_and_old_header_inner_is_removed(): void
    {
        $site = Site::create([
            'name' => 'Shared Layout Site',
            'domain' => 'shared-layout.example',
            'template_set' => 'base',
            'output_path' => 'generated/shared-layout',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'menu_html' => '<div class="header__inner"><div class="header__logo"><a href="/"><img src="/assets/images/logo/logo.webp" alt="Shared Logo"></a></div><nav><a href="/tips.html">Tips</a></nav></div>',
            'mobile_menu_html' => '<div class="mobile-menu" data-mobile-menu><nav><a href="/mobile-shared.html">Shared Mobile</a></nav></div>',
            'footer_html' => '<div class="footer__inner"><p>Shared Footer</p></div>',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'tips',
            'title' => 'Tips Page',
            'template_key' => 'tips',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'content' => [
                'module' => 'hero',
                'render_mode' => 'raw_html',
                'raw_html' => '<section class="hero hero--has-breadcrumbs"><header class="header"><div class="header__inner"><nav><a href="/legacy.html">Legacy Menu</a></nav></div></header><div class="mobile-menu"><a href="/legacy-mobile.html">Legacy Mobile</a></div><nav class="breadcrumbs-container" aria-label="Breadcrumb">Breadcrumbs</nav><div class="hero__content"><h1>Tips Page</h1></div></section>',
            ],
            'order' => 0,
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site', 'sections']));

        $this->assertStringContainsString('<header class="header" id="header">', $html);
        $this->assertStringContainsString('Shared Logo', $html);
        $this->assertStringContainsString('Shared Mobile', $html);
        $this->assertStringContainsString('Shared Footer', $html);
        $this->assertStringNotContainsString('Legacy Menu', $html);
        $this->assertStringNotContainsString('Legacy Mobile', $html);
        $this->assertMatchesRegularExpression('/<section class="hero hero--has-breadcrumbs">\s*<header class="header" id="header">.*<nav class="breadcrumbs-container"/s', $html);
        $this->assertSame(1, substr_count($html, 'header__inner'));
        $this->assertSame(1, substr_count($html, 'data-mobile-menu'));
    }

    public function test_test_template_hero_module_uses_shared_menu_instead_of_local_header_partial(): void
    {
        $site = Site::create([
            'name' => 'Shared Test Layout Site',
            'domain' => 'shared-test-layout.example',
            'template_set' => 'test',
            'output_path' => 'generated/shared-test-layout',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'menu_html' => '<div class="header__inner"><nav><a href="/shared-menu.html">Shared Menu</a></nav></div>',
            'mobile_menu_html' => '<div class="mobile-menu" data-mobile-menu><nav><a href="/shared-mobile.html">Shared Mobile</a></nav></div>',
            'footer_html' => '<div class="footer__inner"><p>Shared Footer</p></div>',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home',
            'template_key' => 'index',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'module' => 'hero-main',
            'content' => [
                'module' => 'hero-main',
                'module_key' => 'hero-main',
                'heroTitle' => 'Shared Hero',
            ],
            'order' => 0,
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site', 'sections']));

        $this->assertStringContainsString('Shared Menu', $html);
        $this->assertStringContainsString('Shared Mobile', $html);
        $this->assertStringNotContainsString('data-desktop-submenu-trigger', $html);
        $this->assertSame(1, substr_count($html, '<header class="header" id="header">'));
        $this->assertSame(1, substr_count($html, '<div class="mobile-menu" data-mobile-menu>'));
    }

    public function test_shared_header_is_injected_into_first_rendered_hero_after_skipped_layout_sections(): void
    {
        $site = Site::create([
            'name' => 'Skipped Layout Sections Site',
            'domain' => 'skipped-layout.example',
            'template_set' => 'base',
            'output_path' => 'generated/skipped-layout',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
            'menu_html' => '<div class="header__inner"><nav><a href="/shared-menu.html">Shared Menu</a></nav></div>',
            'mobile_menu_html' => '<div class="mobile-menu" data-mobile-menu><nav><a href="/shared-mobile.html">Shared Mobile</a></nav></div>',
            'footer_html' => '<div class="footer__inner"><p>Shared Footer</p></div>',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Home',
            'template_key' => 'index',
            'status' => 'published',
            'locale' => 'en',
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'module' => 'mobile-menu',
            'content' => [
                'module' => 'mobile-menu',
                'module_key' => 'mobile-menu',
                'raw_html' => '<div class="mobile-menu" data-mobile-menu>Old Mobile</div>',
                'render_mode' => 'raw_html',
            ],
            'order' => 0,
        ]);

        Section::create([
            'page_id' => $page->id,
            'type' => 'module',
            'module' => 'hero',
            'content' => [
                'module' => 'hero',
                'module_key' => 'hero',
                'raw_html' => '<section class="hero" id="hero"><header class="header"><div class="header__inner">Old Menu</div></header><div class="hero__inner">Hero</div></section>',
                'render_mode' => 'raw_html',
            ],
            'order' => 1,
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site', 'sections']));

        $this->assertMatchesRegularExpression('/<section class="hero" id="hero">\s*<header class="header" id="header">/', $html);
        $this->assertStringContainsString('Shared Menu', $html);
        $this->assertStringNotContainsString('Old Menu', $html);
        $this->assertStringNotContainsString('Old Mobile', $html);
        $this->assertSame(1, substr_count($html, 'header__inner'));
    }
}

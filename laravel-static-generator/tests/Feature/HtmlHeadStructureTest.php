<?php

namespace Tests\Feature;

use App\Contracts\HtmlGeneratorInterface;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HtmlHeadStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_head_matches_expected_structure_and_order(): void
    {
        $site = Site::create([
            'name' => 'Cleopatra Slot',
            'domain' => 'cleopatraslot.ca',
            'template_set' => 'base',
            'output_path' => 'generated/cleopatraslot.ca',
            'status' => 'active',
            'locale' => 'en-CA',
            'default_locale' => 'en-CA',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Any Title',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Any Meta Title',
            'meta_description' => 'Any Meta Description',
            'canonical' => 'https://cleopatraslot.ca/',
            'locale' => 'en-CA',
            'og_data' => [
                'head_meta' => [
                    ['name' => 'robots', 'content' => 'robots-value'],
                    ['property' => 'og:locale', 'content' => 'en_CA'],
                    ['property' => 'og:locale:alternate', 'content' => 'en_US'],
                    ['property' => 'og:type', 'content' => 'website'],
                    ['property' => 'og:title', 'content' => 'og title'],
                    ['property' => 'og:description', 'content' => 'og description'],
                    ['property' => 'og:url', 'content' => 'https://cleopatraslot.ca/'],
                    ['property' => 'og:site_name', 'content' => 'cleopatraslot.ca'],
                    ['property' => 'article:published_time', 'content' => '2020-12-07T18:05:01+00:00'],
                    ['property' => 'article:modified_time', 'content' => '2026-04-20T10:43:59+00:00'],
                    ['name' => 'twitter:card', 'content' => 'summary_large_image'],
                ],
                'head_links' => [
                    ['rel' => 'alternate', 'href' => 'https://site.com/', 'hreflang' => 'en'],
                    ['rel' => 'alternate', 'href' => 'https://site.en/es/', 'hreflang' => 'es'],
                ],
                'head_extra' => <<<'HTML'
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Page"}</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","name":"FAQ"}</script>
HTML,
                'head_custom' => <<<'HTML'
<meta name="tail-marker" content="tail-marker">
HTML,
            ],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));
        $head = $this->extractHead($html);

        $expectedSequence = [
            '<title>',
            '<meta name="description"',
            '<link rel="canonical"',
            '<meta name="robots"',
            '<meta property="og:locale"',
            '<meta property="og:locale:alternate"',
            '<meta property="og:type"',
            '<meta property="og:title"',
            '<meta property="og:description"',
            '<meta property="og:url"',
            '<meta property="og:site_name"',
            '<meta property="article:published_time"',
            '<meta property="article:modified_time"',
            '<meta name="twitter:card"',
            '<link rel="alternate"',
            '<link rel="alternate"',
            '<link rel="stylesheet" href="/assets/css/style.css">',
            '<link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">',
            '<link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">',
            '<link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">',
            '<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">',
            '<link rel="manifest" href="/assets/images/favicon/site.webmanifest">',
            '<script type="application/ld+json">',
        ];

        $offset = -1;
        foreach ($expectedSequence as $needle) {
            $position = strpos($head, $needle, $offset + 1);
            $this->assertNotFalse($position, "Head is missing expected fragment: {$needle}");
            $this->assertGreaterThan($offset, $position, "Head fragment is out of order: {$needle}");
            $offset = $position;
        }

        $this->assertSame(2, substr_count($head, '<link rel="alternate"'));
        $this->assertSame(1, substr_count($head, '<script type="application/ld+json">'));
        $this->assertStringNotContainsString('<meta charset=', $head);
        $this->assertStringNotContainsString('X-UA-Compatible', $head);
        $this->assertStringNotContainsString('viewport', $head);
        $this->assertStringNotContainsString('<meta name="keywords"', $head);
        $this->assertSame(1, substr_count($head, '<link rel="stylesheet" href="/assets/css/style.css">'));
        $this->assertSame(1, substr_count($head, '<link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">'));
        $this->assertSame(1, substr_count($head, '<link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">'));
        $this->assertSame(1, substr_count($head, '<link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">'));
        $this->assertSame(1, substr_count($head, '<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">'));
        $this->assertSame(1, substr_count($head, '<link rel="manifest" href="/assets/images/favicon/site.webmanifest">'));
        $this->assertStringContainsString('"@graph"', $head);
        $this->assertMatchesRegularExpression('/<link rel="stylesheet" href="\/assets\/css\/style\.css">\s*<link rel="icon" type="image\/png" href="\/assets\/images\/favicon\/favicon-96x96\.png" sizes="96x96">\s*<link rel="icon" type="image\/svg\+xml" href="\/assets\/images\/favicon\/favicon\.svg">\s*<link rel="shortcut icon" href="\/assets\/images\/favicon\/favicon\.ico">\s*<link rel="apple-touch-icon" sizes="180x180" href="\/assets\/images\/favicon\/apple-touch-icon\.png">\s*<link rel="manifest" href="\/assets\/images\/favicon\/site\.webmanifest">\s*<script type="application\/ld\+json">[\s\S]*<\/script>\s*<meta name="tail-marker" content="tail-marker">\s*$/', $head);
    }

    public function test_page_head_omits_alternates_when_no_alternates_exist(): void
    {
        $site = Site::create([
            'name' => 'Default Alternate',
            'domain' => 'default-alternate.example',
            'template_set' => 'base',
            'output_path' => 'generated/default-alternate.example',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Default Alternate',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Default Alternate',
            'meta_description' => 'Default alternate description',
            'canonical' => 'https://default-alternate.example/',
            'locale' => 'en',
            'og_data' => [],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));

        $this->assertSame(0, substr_count($html, '<link rel="alternate"'));
    }

    public function test_page_head_skips_alternate_links_without_hreflang(): void
    {
        $site = Site::create([
            'name' => 'Incomplete Alternate',
            'domain' => 'incomplete-alternate.example',
            'template_set' => 'base',
            'output_path' => 'generated/incomplete-alternate.example',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Incomplete Alternate',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Incomplete Alternate',
            'meta_description' => 'Incomplete alternate description',
            'canonical' => 'https://incomplete-alternate.example/',
            'locale' => 'en',
            'og_data' => [
                'head_links' => [
                    ['rel' => 'alternate', 'href' => 'https://incomplete-alternate.example/es/', 'hreflang' => ''],
                    ['rel' => 'alternate', 'href' => '', 'hreflang' => 'de'],
                ],
            ],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));

        $this->assertStringNotContainsString('<link rel="alternate" href="https://incomplete-alternate.example/es/"', $html);
        $this->assertStringNotContainsString('hreflang=""', $html);
        $this->assertSame(0, substr_count($html, '<link rel="alternate"'));
    }

    private function extractHead(string $html): string
    {
        if (preg_match('/<head>(.*?)<\/head>/is', $html, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }
}

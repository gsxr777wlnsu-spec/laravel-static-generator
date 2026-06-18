<?php

namespace Tests\Feature;

use App\Contracts\HtmlGeneratorInterface;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HtmlHeadRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_head_renders_custom_meta_links_and_scripts_without_forced_defaults(): void
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
            'title' => 'Cleopatra Slot in Canada - 150 Free Spins, Free Demo & Casino Review',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Cleopatra Slot in Canada - 150 Free Spins, Free Demo & Casino Review',
            'meta_description' => 'Claim 150 Free Spins 👑 & up to 640 CAD bonus on Cleopatra slot! Free demo, no download needed, instant play for Canadian players. Start now!',
            'meta_keywords' => '',
            'canonical' => 'https://cleopatraslot.ca/',
            'locale' => 'en-CA',
            'og_data' => [
                'head_meta' => [
                    ['name' => 'robots', 'content' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'],
                    ['property' => 'og:locale', 'content' => 'en_CA'],
                    ['property' => 'og:locale:alternate', 'content' => 'en_US'],
                    ['name' => 'og:type', 'property' => 'og:type', 'content' => 'website'],
                    ['name' => 'og:title', 'property' => 'og:title', 'content' => 'Cleopatra Slot in Canada - 150 Free Spins, Free Demo & Casino Review'],
                    ['name' => 'og:description', 'property' => 'og:description', 'content' => 'Claim 150 Free Spins 👑 & up to 640 CAD bonus on Cleopatra slot! Free demo, no download needed, instant play for Canadian players. Start now!'],
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
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebPage",
      "url": "https://cleopatraslot.ca/",
      "name": "Cleopatra Slot in Canada - 150 Free Spins, Free Demo & Casino Review"
    }
  ]
}
</script>
HTML,
                'head_custom' => '',
            ],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));

        $this->assertStringContainsString('<html lang="en-CA">', $html);
        $this->assertStringContainsString('<title>Cleopatra Slot in Canada - 150 Free Spins, Free Demo &amp; Casino Review</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Claim 150 Free Spins 👑 &amp; up to 640 CAD bonus on Cleopatra slot! Free demo, no download needed, instant play for Canadian players. Start now!">', $html);
        $this->assertStringNotContainsString('<meta name="keywords"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://cleopatraslot.ca/">', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="en_CA">', $html);
        $this->assertStringContainsString('<meta property="og:locale:alternate" content="en_US">', $html);
        $this->assertStringContainsString('<meta property="og:site_name" content="cleopatraslot.ca">', $html);
        $this->assertStringContainsString('<link rel="alternate" href="https://site.com/" hreflang="en">', $html);
        $this->assertStringContainsString('<link rel="alternate" href="https://site.en/es/" hreflang="es">', $html);
        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/css/style.css">', $html);
        $this->assertStringContainsString('<link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">', $html);
        $this->assertStringContainsString('<link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">', $html);
        $this->assertStringContainsString('<link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">', $html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">', $html);
        $this->assertStringContainsString('<link rel="manifest" href="/assets/images/favicon/site.webmanifest">', $html);
        $this->assertStringNotContainsString('hreflang="x-default"', $html);
        $this->assertStringContainsString('"@graph"', $html);
        $this->assertSame(1, substr_count($html, '<meta property="og:type" content="website">'));
        $this->assertSame(1, preg_match_all('/<meta property="og:title" content="[^"]*">/', $html));
        $this->assertSame(1, preg_match_all('/<meta property="og:description" content="[^"]*">/', $html));
        $this->assertStringNotContainsString('<meta name="og:type" property="og:type"', $html);
        $this->assertStringNotContainsString('<meta name="og:title" property="og:title"', $html);
        $this->assertStringNotContainsString('<meta name="og:description" property="og:description"', $html);
        $this->assertStringNotContainsString('twitter:title', $html);
        $this->assertStringNotContainsString('article:author', $html);
    }
}

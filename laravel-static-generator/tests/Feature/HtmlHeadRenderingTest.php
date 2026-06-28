<?php

namespace Tests\Feature;

use App\Contracts\HtmlGeneratorInterface;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        $this->assertStringNotContainsString('<style>', $html);
        $this->assertStringNotContainsString('img[width][height]', $html);
        $this->assertStringContainsString('<link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">', $html);
        $this->assertStringContainsString('<link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">', $html);
        $this->assertStringContainsString('<link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">', $html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">', $html);
        $this->assertStringContainsString('<link rel="manifest" href="/assets/images/favicon/site.webmanifest">', $html);
        $this->assertStringContainsString('<meta property="article:modified_time" content="2026-04-20T10:43:59+00:00">', $html);
        $this->assertStringNotContainsString('hreflang="x-default"', $html);
        $this->assertStringContainsString('"@graph"', $html);
        $this->assertStringContainsString('"dateModified": "2026-04-20T10:43:59+00:00"', $html);
        $this->assertSame(1, substr_count($html, '<meta property="og:type" content="website">'));
        $this->assertSame(1, preg_match_all('/<meta property="og:title" content="[^"]*">/', $html));
        $this->assertSame(1, preg_match_all('/<meta property="og:description" content="[^"]*">/', $html));
        $this->assertStringNotContainsString('<meta name="og:type" property="og:type"', $html);
        $this->assertStringNotContainsString('<meta name="og:title" property="og:title"', $html);
        $this->assertStringNotContainsString('<meta name="og:description" property="og:description"', $html);
        $this->assertStringNotContainsString('twitter:title', $html);
        $this->assertStringNotContainsString('article:author', $html);
    }

    public function test_page_head_keeps_latest_faq_and_how_to_json_ld_nodes(): void
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
            'title' => 'Cleopatra Slot',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Cleopatra Slot',
            'meta_description' => 'Demo description',
            'canonical' => 'https://cleopatraslot.ca/',
            'locale' => 'en-CA',
            'og_data' => [
                'head_extra' => <<<'HTML'
<script type="application/ld+json">
{"@context":"https://schema.org","@graph":[{"@type":"HowTo","name":"Old HowTo"},{"@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Old FAQ"}]}]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Fresh FAQ"}]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"HowTo","name":"Fresh HowTo","step":[{"@type":"HowToStep","name":"Fresh step"}]}
</script>
HTML,
            ],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));
        $this->assertSame(1, preg_match('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches));

        $payload = json_decode($matches[1], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());

        $graph = $payload['@graph'] ?? [];
        $types = array_map(static fn (array $node): string => (string) ($node['@type'] ?? ''), $graph);

        $this->assertSame(1, count(array_filter($types, static fn (string $type): bool => $type === 'FAQPage')));
        $this->assertSame(1, count(array_filter($types, static fn (string $type): bool => $type === 'HowTo')));
        $this->assertStringContainsString('Fresh FAQ', $matches[1]);
        $this->assertStringContainsString('Fresh HowTo', $matches[1]);
        $this->assertStringNotContainsString('Old FAQ', $matches[1]);
        $this->assertStringNotContainsString('Old HowTo', $matches[1]);
    }

    public function test_page_head_fills_json_ld_and_modified_time_from_page_data(): void
    {
        Carbon::setTestNow('2026-06-27 12:00:00');

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
            'title' => 'Cleopatra Slot in Canada - 150 Free Spins',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Cleopatra Slot in Canada - 150 Free Spins',
            'meta_description' => 'Fresh imported Cleopatra slot description.',
            'canonical' => 'https://cleopatraslot.ca/',
            'locale' => 'en-CA',
            'og_data' => [
                'head_extra' => <<<'HTML'
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebPage",
      "@id": "https://old.example/#webpage",
      "url": "https://old.example/",
      "name": "Old page name",
      "description": "Old page description",
      "inLanguage": "en",
      "datePublished": "2017-01-01T00:00:00+00:00",
      "dateModified": "2017-01-01T00:00:00+00:00",
      "mainEntity": {
        "@type": "VideoGame",
        "name": "Cleopatra Slot",
        "url": "https://old.example/",
        "image": "https://old.example/assets/images/aviator.jpg",
        "description": "Old game description"
      }
    },
    {
      "@type": "VideoGame",
      "@id": "https://old.example/#aviator-game",
      "name": "Aviator",
      "url": "https://old.example/",
      "image": "https://old.example/assets/images/aviator.jpg",
      "description": "Old standalone game description"
    },
    {
      "@type": "Organization",
      "@id": "https://old.example/#organization",
      "name": "Old Organization",
      "url": "https://old.example/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://old.example/assets/images/favicon/apple-touch-icon.png"
      }
    }
  ]
}
</script>
HTML,
            ],
        ]);
        $page->forceFill([
            'created_at' => Carbon::parse('2026-01-02 03:04:05', 'UTC'),
            'updated_at' => Carbon::parse('2026-06-20 10:11:12', 'UTC'),
        ])->saveQuietly();

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));

        $this->assertStringContainsString(
            '<meta property="article:modified_time" content="2026-06-20T10:11:12+00:00">',
            $html
        );

        $this->assertSame(1, preg_match('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches));
        $schema = json_decode($matches[1], true);
        $this->assertIsArray($schema);
        $graph = $schema['@graph'] ?? [];

        $webPage = collect($graph)->firstWhere('@type', 'WebPage');
        $videoGame = collect($graph)->firstWhere('@type', 'VideoGame');
        $organization = collect($graph)->firstWhere('@type', 'Organization');

        $this->assertSame('https://cleopatraslot.ca/#webpage', $webPage['@id'] ?? null);
        $this->assertSame('https://cleopatraslot.ca/', $webPage['url'] ?? null);
        $this->assertSame('Cleopatra Slot in Canada - 150 Free Spins', $webPage['name'] ?? null);
        $this->assertSame('Fresh imported Cleopatra slot description.', $webPage['description'] ?? null);
        $this->assertSame('en-CA', $webPage['inLanguage'] ?? null);
        $this->assertSame('2026-01-02T03:04:05+00:00', $webPage['datePublished'] ?? null);
        $this->assertSame('2026-06-20T10:11:12+00:00', $webPage['dateModified'] ?? null);

        $this->assertSame('https://cleopatraslot.ca/', $webPage['mainEntity']['url'] ?? null);
        $this->assertSame('Cleopatra Slot', $webPage['mainEntity']['name'] ?? null);
        $this->assertSame('https://cleopatraslot.ca/assets/images/aviator.jpg', $webPage['mainEntity']['image'] ?? null);
        $this->assertSame('Fresh imported Cleopatra slot description.', $webPage['mainEntity']['description'] ?? null);

        $this->assertSame('https://cleopatraslot.ca/#aviator-game', $videoGame['@id'] ?? null);
        $this->assertSame('https://cleopatraslot.ca/', $videoGame['url'] ?? null);
        $this->assertSame('Cleopatra Slot', $videoGame['name'] ?? null);
        $this->assertSame('Fresh imported Cleopatra slot description.', $videoGame['description'] ?? null);
        $this->assertSame('https://cleopatraslot.ca/assets/images/aviator.jpg', $videoGame['image'] ?? null);

        $this->assertSame('https://cleopatraslot.ca/#organization', $organization['@id'] ?? null);
        $this->assertSame('Cleopatra Slot', $organization['name'] ?? null);
        $this->assertSame('https://cleopatraslot.ca/', $organization['url'] ?? null);
        $this->assertSame('https://cleopatraslot.ca/assets/images/favicon/apple-touch-icon.png', $organization['logo']['url'] ?? null);

        Carbon::setTestNow();
    }

    public function test_dynamic_head_logic_renders_in_test_template_set(): void
    {
        $site = Site::create([
            'name' => 'Test Template Site',
            'domain' => 'test-template.example',
            'template_set' => 'test',
            'output_path' => 'generated/test-template.example',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'index',
            'title' => 'Test Template Site',
            'template_key' => 'index',
            'status' => 'published',
            'meta_title' => 'Test Template Site',
            'meta_description' => 'Test template description.',
            'canonical' => 'https://test-template.example/',
            'locale' => 'en',
            'og_data' => [
                'head_extra' => '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"WebPage","url":"https://old.example/"}]}</script>',
            ],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));

        $this->assertStringContainsString('<meta property="article:modified_time"', $html);
        $this->assertStringContainsString('"url": "https://test-template.example/"', $html);
    }

    public function test_non_home_page_keeps_video_game_url_path_while_web_page_uses_canonical(): void
    {
        $site = Site::create([
            'name' => 'Review Site',
            'domain' => 'review-site.example',
            'template_set' => 'base',
            'output_path' => 'generated/review-site.example',
            'status' => 'active',
            'locale' => 'en',
            'default_locale' => 'en',
        ]);

        $page = Page::create([
            'site_id' => $site->id,
            'slug' => 'review',
            'title' => 'Review Page',
            'template_key' => 'default',
            'status' => 'published',
            'meta_title' => 'Review Page',
            'meta_description' => 'Review page description.',
            'canonical' => 'https://review-site.example/review/',
            'locale' => 'en',
            'og_data' => [
                'head_extra' => <<<'HTML'
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {"@type": "WebPage", "url": "https://old.example/old-review/"},
    {"@type": "VideoGame", "url": "https://old.example/game-home/"}
  ]
}
</script>
HTML,
            ],
        ]);

        $html = app(HtmlGeneratorInterface::class)->generatePage($page->fresh(['site']));

        $this->assertSame(1, preg_match('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches));
        $schema = json_decode($matches[1], true);
        $graph = $schema['@graph'] ?? [];

        $webPage = collect($graph)->firstWhere('@type', 'WebPage');
        $videoGame = collect($graph)->firstWhere('@type', 'VideoGame');

        $this->assertSame('https://review-site.example/review/', $webPage['url'] ?? null);
        $this->assertSame('https://review-site.example/game-home/', $videoGame['url'] ?? null);
    }
}

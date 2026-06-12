<!DOCTYPE html>
<html lang="{{ $page->locale ?? 'en' }}">
<head>
@php
            $pageOgData = is_array($page->og_data ?? null) ? $page->og_data : [];
            $headMetaItems = isset($pageOgData['head_meta']) && is_array($pageOgData['head_meta']) ? $pageOgData['head_meta'] : [];
            $headLinkItems = isset($pageOgData['head_links']) && is_array($pageOgData['head_links']) ? $pageOgData['head_links'] : [];
            $domain = (string) ($site->domain ?? 'site.com');

            $metaKey = static function (array $meta): ?string {
                $name = strtolower(trim((string) ($meta['name'] ?? '')));
                if ($name !== '') {
                    return 'name:' . $name;
                }

                $property = strtolower(trim((string) ($meta['property'] ?? '')));
                if ($property !== '') {
                    return 'property:' . $property;
                }

                $httpEquiv = strtolower(trim((string) ($meta['http_equiv'] ?? '')));
                if ($httpEquiv !== '') {
                    return 'http-equiv:' . $httpEquiv;
                }

                return null;
            };
            $blockedHeadMetaKeys = [
                'name:geo.region',
                'name:geo.position',
                'name:icbm',
                'name:contact',
                'name:copyright',
                'name:designer',
                'name:generator',
                'name:author',
                'name:rating',
                'name:telegram:channel',
                'name:telegram:bot',
                'property:vk:image',
                'property:vk:app_id',
                'name:twitter:title',
                'name:twitter:description',
                'name:twitter:site',
                'name:twitter:creator',
                'name:twitter:image',
                'property:og:image',
            ];

            $defaultHeadMeta = [
                ['name' => 'robots', 'content' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'],
                ['name' => 'og:type', 'property' => 'og:type', 'content' => 'website'],
                ['property' => 'og:locale', 'content' => ($page->locale ?? 'en') . '_' . strtoupper($page->locale ?? 'en')],
                ['name' => 'og:title', 'property' => 'og:title', 'content' => (string) ($page->meta_title ?? $page->title ?? '')],
                ['name' => 'og:description', 'property' => 'og:description', 'content' => (string) ($page->meta_description ?? '')],
                ['property' => 'article:published_time', 'content' => '2020-12-07T18:05:01+00:00'],
                ['property' => 'article:modified_time', 'content' => '2026-04-20T10:43:59+00:00'],
                ['property' => 'article:author', 'content' => $domain],
                ['name' => 'twitter:card', 'content' => 'summary_large_image'],
            ];

            $mergedMetaMap = [];
            foreach ($defaultHeadMeta as $meta) {
                $key = $metaKey($meta);
                if ($key === null || in_array($key, $blockedHeadMetaKeys, true)) {
                    continue;
                }
                $mergedMetaMap[$key] = $meta;
            }

            foreach ($headMetaItems as $meta) {
                if (!is_array($meta) || !array_key_exists('content', $meta)) {
                    continue;
                }

                $key = $metaKey($meta);
                if ($key === null || in_array($key, $blockedHeadMetaKeys, true)) {
                    continue;
                }

                $mergedMetaMap[$key] = [
                    'name' => isset($meta['name']) ? (string) $meta['name'] : null,
                    'property' => isset($meta['property']) ? (string) $meta['property'] : null,
                    'http_equiv' => isset($meta['http_equiv']) ? (string) $meta['http_equiv'] : null,
                    'content' => (string) $meta['content'],
                ];
            }

            $mergedHeadMeta = array_values($mergedMetaMap);
            $publishedIndex = null;
            $modifiedIndex = null;
            foreach ($mergedHeadMeta as $idx => $meta) {
                $property = strtolower(trim((string) ($meta['property'] ?? '')));
                if ($property === 'article:published_time') {
                    $publishedIndex = $idx;
                }
                if ($property === 'article:modified_time') {
                    $modifiedIndex = $idx;
                }
            }

            if ($publishedIndex !== null && $modifiedIndex !== null && $modifiedIndex !== $publishedIndex + 1) {
                $modifiedMeta = $mergedHeadMeta[$modifiedIndex];
                array_splice($mergedHeadMeta, $modifiedIndex, 1);

                if ($modifiedIndex < $publishedIndex) {
                    $publishedIndex--;
                }

                array_splice($mergedHeadMeta, $publishedIndex + 1, 0, [$modifiedMeta]);
            }
@endphp
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $page->meta_title ?? $page->title }}</title>
        <meta name="description" content="{{ $page->meta_description ?? '' }}">
        <meta name="keywords" content="{{ $page->meta_keywords ?? 'game, play, bet, aviator' }}">
        <link rel="canonical" href="{{ $page->canonical ?? url($page->slug) }}">
@php
foreach ($mergedHeadMeta as $meta) {
    if (!is_array($meta) || !array_key_exists('content', $meta)) {
        continue;
    }

    $metaAttributes = [];
    if (!empty($meta['name'])) {
        $metaAttributes[] = 'name="' . e((string) $meta['name']) . '"';
    }
    if (!empty($meta['property'])) {
        $metaAttributes[] = 'property="' . e((string) $meta['property']) . '"';
    }
    if (!empty($meta['http_equiv'])) {
        $metaAttributes[] = 'http-equiv="' . e((string) $meta['http_equiv']) . '"';
    }
    $metaAttributes[] = 'content="' . e((string) $meta['content']) . '"';

    echo '        <meta ' . implode(' ', $metaAttributes) . '>' . PHP_EOL;
}
@endphp
@php
foreach ($headLinkItems as $link) {
    if (!is_array($link) || !isset($link['href'])) {
        continue;
    }

    $rel = strtolower(trim((string) ($link['rel'] ?? '')));
    if (in_array($rel, ['stylesheet', 'publisher', 'icon', 'shortcut icon', 'apple-touch-icon', 'manifest'], true)) {
        continue;
    }

    $linkAttributes = [];
    if (isset($link['rel'])) {
        $linkAttributes[] = 'rel="' . e((string) $link['rel']) . '"';
    }
    $linkAttributes[] = 'href="' . e((string) $link['href']) . '"';
    if (isset($link['type'])) {
        $linkAttributes[] = 'type="' . e((string) $link['type']) . '"';
    }
    if (isset($link['sizes'])) {
        $linkAttributes[] = 'sizes="' . e((string) $link['sizes']) . '"';
    }
    if (isset($link['hreflang'])) {
        $linkAttributes[] = 'hreflang="' . e((string) $link['hreflang']) . '"';
    }

    echo '        <link ' . implode(' ', $linkAttributes) . '>' . PHP_EOL;
}
@endphp
        <link href="/assets/css/style.css" rel="stylesheet">
        <link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">
        <link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">
        <link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">
        <link rel="manifest" href="/assets/images/favicon/site.webmanifest">
@if(isset($pageOgData['head_extra']) && is_string($pageOgData['head_extra']) && trim($pageOgData['head_extra']) !== '')
@php echo preg_replace('/^/m', '        ', trim($pageOgData['head_extra'])) . PHP_EOL; @endphp
@else
            <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "@@id": "https://{{ $domain }}/#webpage",
    "url": "https://{{ $domain }}/"
}
            </script>
            <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "VideoGame",
    "additionalType": "https://schema.org/WebApplication",
    "@@id": "https://{{ $domain }}/#aviator-game",
    "name": "Aviator",
    "description": "Play Aviator Game — a thrilling legal online game with a maximum win of 1000x your bet. Created by Spribe, this crash game has a high chance for players to win with 97% RTP.",
    "url": "https://{{ $domain }}/",
    "image": "https://{{ $domain }}/assets/images/aviator.jpg",
    "author": {
        "@@type": "Organization",
        "name": "Spribe"
    },
    "gamePlatform": ["Web Browser", "Mobile", "Desktop"],
    "genre": "Crash Game",
    "applicationCategory": "GameApplication",
    "operatingSystem": "Web Browser",
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.7",
        "bestRating": "5",
        "worstRating": "1",
        "ratingCount": "44"
    }
}
            </script>
            <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "@@id": "https://{{ $domain }}/#organization",
    "name": "Aviator Game",
    "url": "https://{{ $domain }}/",
    "logo": {
        "@@type": "ImageObject",
        "url": "https://{{ $domain }}/assets/images/favicon/apple-touch-icon.png",
        "width": 180,
        "height": 180
    }
}
            </script>
@endif
@if(isset($pageOgData['head_custom']) && is_string($pageOgData['head_custom']) && trim($pageOgData['head_custom']) !== '')
@php echo preg_replace('/^/m', '        ', trim($pageOgData['head_custom'])) . PHP_EOL; @endphp
@endif

        @if(isset($languageVersions) && count($languageVersions) > 0)
            @foreach($languageVersions as $version)
            <link rel="alternate" hreflang="{{ $version->locale }}" href="{{ $version->canonical ?? url($version->slug) }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ $page->canonical ?? url($page->slug) }}">
@endif
</head>
<body class="body" id="body">
    <main class="main">
        @yield('content')
    </main>

    @if(($page->template_key ?? '') !== 'blank')
    <footer class="footer" id="footer">
        <div class="footer__inner">
            <div class="footer__main" aria-label="Footer navigation">
                <div class="footer__col footer__col--brand">
                    <div class="footer__logo">
                        <a class="footer__logo-wrapper" href="/" aria-label="To the main page">
                            <img src="/assets/images/logo/logo.webp" width="141" height="41" loading="lazy" alt="Aviator">
                        </a>
                    </div>
                    <a class="btn__cta" href="#play-now">Play now!</a>
                </div>

                <nav class="footer__col footer__col--links" aria-label="Footer column 1">
                    <ul class="footer__links">
                        <li class="footer__item"><a class="footer__link" href="/#where-to-play">Where to play</a></li>
                        <li class="footer__item"><a class="footer__link" href="/#characteristics">Characteristics</a></li>
                        <li class="footer__item"><a class="footer__link" href="/#review">Review</a></li>
                        <li class="footer__item"><a class="footer__link" href="/#symbols">Symbols</a></li>
                    </ul>
                </nav>

                <nav class="footer__col footer__col--links" aria-label="Footer column 2">
                    <ul class="footer__links">
                        <li class="footer__item"><a class="footer__link" href="/#gameplay">Gameplay</a></li>
                        <li class="footer__item"><a class="footer__link" href="/#rtp">RTP</a></li>
                        <li class="footer__item"><a class="footer__link" href="/#bonuses">Bonuses</a></li>
                        <li class="footer__item"><a class="footer__link" href="/#conclusion">Conclusion</a></li>
                    </ul>
                </nav>

                <nav class="footer__col footer__col--links" aria-label="Footer column 3">
                    <ul class="footer__links">
                        <li class="footer__item"><a class="footer__link" href="terms-and-conditions.html">Terms and Conditions</a></li>
                        <li class="footer__item"><a class="footer__link" href="cookie-policy.html">Cookie Policy</a></li>
                        <li class="footer__item"><a class="footer__link" href="privacy-policy.html">Privacy Policy</a></li>
                        <li class="footer__item"><a class="footer__link" href="sitemap.html">Sitemap</a></li>
                    </ul>
                </nav>
            </div>

            <div class="footer__info" aria-label="Footer disclaimer">
                <svg class="footer__age-icon" width="63" height="63" viewBox="0 0 34 34" role="img" aria-label="18+">
                    <circle cx="17" cy="17" r="16" fill="none" stroke="var(--color-white)" stroke-width="2"></circle>
                    <text x="17" y="22" text-anchor="middle" font-size="14" font-family="Inter, Arial, sans-serif" font-weight="700" fill="var(--color-white)">18+</text>
                </svg>

                <div class="footer__info-text">
                    <span class="footer__info-copy">{{ $site->domain ?? 'site.com' }} is one of Spribe's independent affiliates. We are experts in presenting accurate, objective information about cutting-edge casino games and iGaming products. Please go over our terms and conditions and privacy policy. Please be aware that the activities of users on third-party sites are not under the control of our organization.</span>
                </div>

                <div class="footer__payments" aria-label="Payment systems">
                    <img class="footer__payment" src="/assets/images/payment-systems/visa.webp" width="80" height="40" loading="lazy" alt="Visa">
                    <img class="footer__payment" src="/assets/images/payment-systems/mc.webp" width="80" height="40" loading="lazy" alt="Mastercard">
                    <img class="footer__payment" src="/assets/images/payment-systems/ae.webp" width="80" height="40" loading="lazy" alt="American Express">
                    <img class="footer__payment" src="/assets/images/payment-systems/paypal.webp" width="80" height="40" loading="lazy" alt="PayPal">
                </div>
            </div>

            <div class="footer__copyright" aria-label="Copyright">© Copyright 2024-{{ date('Y') }}</div>
        </div>
    </footer>
@endif

    @php
        $slugOrTemplate = (string) ($page->template_key ?? $page->slug ?? '');

        $scriptMap = [
            'app' => ['/assets/js/main.js', '/assets/js/sliders.js', '/assets/js/lightbox.js', '/assets/js/faq.js'],
            'authors' => ['/assets/js/main.js', '/assets/js/faq.js'],
            'bonuses' => ['/assets/js/main.js', '/assets/js/faq.js'],
            'comparison' => ['/assets/js/main.js', '/assets/js/lightbox.js', '/assets/js/faq.js'],
            'contact-us' => ['/assets/js/main.js', '/assets/js/form.js'],
            'cookie-policy' => ['/assets/js/main.js'],
            'demo' => ['/assets/js/main.js', '/assets/js/faq.js'],
            'index' => ['/assets/js/main.js', '/assets/js/lightbox.js', '/assets/js/faq.js'],
            'privacy-policy' => ['/assets/js/main.js'],
            'reviews' => ['/assets/js/main.js', '/assets/js/faq.js', '/assets/js/form.js'],
            'sitemap' => ['/assets/js/main.js'],
            'terms-and-conditions' => ['/assets/js/main.js'],
            'tips' => ['/assets/js/main.js', '/assets/js/faq.js'],
        ];

        $scripts = $scriptMap[$slugOrTemplate] ?? ['/assets/js/main.js'];
@endphp

    @foreach($scripts as $script)
        <script defer src="{{ $script }}"></script>
    @endforeach
    @if(isset($pageOgData['body_extra']) && is_string($pageOgData['body_extra']) && trim($pageOgData['body_extra']) !== '')
        {!! $pageOgData['body_extra'] !!}
@endif
</body>
</html>

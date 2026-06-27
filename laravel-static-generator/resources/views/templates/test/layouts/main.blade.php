<!DOCTYPE html>
<html lang="{{ $page->locale ?? 'en' }}">
<head>
@php
            $pageOgData = is_array($page->og_data ?? null) ? $page->og_data : [];
            $headMetaItems = isset($pageOgData['head_meta']) && is_array($pageOgData['head_meta']) ? $pageOgData['head_meta'] : [];
            $headLinkItems = isset($pageOgData['head_links']) && is_array($pageOgData['head_links']) ? $pageOgData['head_links'] : [];
            $headCustomRaw = isset($pageOgData['head_custom']) && is_string($pageOgData['head_custom']) ? trim($pageOgData['head_custom']) : '';
            $headExtra = isset($pageOgData['head_extra']) && is_string($pageOgData['head_extra']) ? trim($pageOgData['head_extra']) : '';
            $domain = (string) ($site->domain ?? 'site.com');
            $metaTitle = trim((string) ($page->meta_title ?? $page->title ?? ''));
            $metaDescription = trim((string) ($page->meta_description ?? ''));
            $canonical = trim((string) ($page->canonical ?? url($page->slug)));
            $defaultLocale = trim((string) ($page->locale ?? 'en'));
            $ogLocale = str_replace('-', '_', $defaultLocale);
            $metaKey = static function (array $meta): ?string {
                $property = strtolower(trim((string) ($meta['property'] ?? '')));
                if ($property !== '') {
                    return 'property:' . $property;
                }

                $name = strtolower(trim((string) ($meta['name'] ?? '')));
                if ($name !== '') {
                    return 'name:' . $name;
                }

                $httpEquiv = strtolower(trim((string) ($meta['http_equiv'] ?? '')));
                if ($httpEquiv !== '') {
                    return 'http-equiv:' . $httpEquiv;
                }

                return null;
            };
            $headCustomResidual = $headCustomRaw;
            $standardMeta = [
                'name:robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
                'property:og:locale' => $ogLocale,
                'property:og:locale:alternate' => '',
                'property:og:type' => 'website',
                'property:og:title' => $metaTitle,
                'property:og:description' => $metaDescription,
                'property:og:url' => $canonical,
                'property:og:site_name' => $domain,
                'property:article:published_time' => '2020-12-07T18:05:01+00:00',
                'property:article:modified_time' => '2026-04-20T10:43:59+00:00',
                'name:twitter:card' => 'summary_large_image',
            ];
            $extraHeadMeta = [];
            foreach ($headMetaItems as $meta) {
                if (!is_array($meta)) {
                    continue;
                }

                $key = $metaKey($meta);
                $content = trim((string) ($meta['content'] ?? ''));
                if ($key === null || $content === '') {
                    continue;
                }

                $normalizedMeta = [
                    'name' => isset($meta['name']) ? trim((string) $meta['name']) : null,
                    'property' => isset($meta['property']) ? trim((string) $meta['property']) : null,
                    'http_equiv' => isset($meta['http_equiv']) ? trim((string) $meta['http_equiv']) : null,
                    'content' => $content,
                ];

                if (array_key_exists($key, $standardMeta)) {
                    $standardMeta[$key] = $content;
                    continue;
                }

                $extraHeadMeta[] = $normalizedMeta;
            }
            $alternateLinks = [];
            $extraLinks = [];
            foreach ($headLinkItems as $link) {
                if (!is_array($link)) {
                    continue;
                }

                $href = trim((string) ($link['href'] ?? ''));
                if ($href === '') {
                    continue;
                }

                $normalizedLink = [
                    'rel' => isset($link['rel']) ? trim((string) $link['rel']) : null,
                    'href' => $href,
                    'hreflang' => isset($link['hreflang']) ? trim((string) $link['hreflang']) : null,
                    'type' => isset($link['type']) ? trim((string) $link['type']) : null,
                    'sizes' => isset($link['sizes']) ? trim((string) $link['sizes']) : null,
                ];

                if (strtolower((string) ($normalizedLink['rel'] ?? '')) === 'alternate') {
                    if ($normalizedLink['hreflang'] === null || $normalizedLink['hreflang'] === '') {
                        continue;
                    }

                    $alternateLinks[] = $normalizedLink;
                    continue;
                }

                $extraLinks[] = $normalizedLink;
            }

            if ($alternateLinks === [] && isset($languageVersions) && count($languageVersions) > 0) {
                foreach ($languageVersions as $version) {
                    $alternateLinks[] = [
                        'rel' => 'alternate',
                        'href' => $version->canonical ?? url($version->slug),
                        'hreflang' => $version->locale,
                        'type' => null,
                        'sizes' => null,
                    ];
                }
            }

            $appendGraphNode = static function (array &$graph, array $node): void {
                unset($node['@context']);
                if ($node !== []) {
                    $graph[] = $node;
                }
            };
            $jsonLdGraph = [];
            if ($headExtra !== '') {
                preg_match_all('/<script\b[^>]*type=(["\'])application\/ld\+json\1[^>]*>(.*?)<\/script>/is', $headExtra, $scriptMatches);
                foreach ($scriptMatches[2] ?? [] as $scriptBody) {
                    $decoded = json_decode(trim((string) $scriptBody), true);
                    if (!is_array($decoded)) {
                        continue;
                    }

                    if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                        foreach ($decoded['@graph'] as $graphNode) {
                            if (is_array($graphNode)) {
                                $appendGraphNode($jsonLdGraph, $graphNode);
                            }
                        }
                        continue;
                    }

                    $appendGraphNode($jsonLdGraph, $decoded);
                }
            }

            if ($jsonLdGraph === []) {
                $jsonLdGraph = [
                    [
                        '@type' => 'WebPage',
                        '@id' => "https://{$domain}/#webpage",
                        'url' => "https://{$domain}/",
                    ],
                    [
                        '@type' => 'VideoGame',
                        'additionalType' => 'https://schema.org/WebApplication',
                        '@id' => "https://{$domain}/#aviator-game",
                        'name' => 'Aviator',
                        'description' => 'Play Aviator Game — a thrilling legal online game with a maximum win of 1000x your bet. Created by Spribe, this crash game has a high chance for players to win with 97% RTP.',
                        'url' => "https://{$domain}/",
                        'image' => "https://{$domain}/assets/images/aviator.jpg",
                        'author' => [
                            '@type' => 'Organization',
                            'name' => 'Spribe',
                        ],
                        'gamePlatform' => ['Web Browser', 'Mobile', 'Desktop'],
                        'genre' => 'Crash Game',
                        'applicationCategory' => 'GameApplication',
                        'operatingSystem' => 'Web Browser',
                        'aggregateRating' => [
                            '@type' => 'AggregateRating',
                            'ratingValue' => '4.7',
                            'bestRating' => '5',
                            'worstRating' => '1',
                            'ratingCount' => '44',
                        ],
                    ],
                    [
                        '@type' => 'Organization',
                        '@id' => "https://{$domain}/#organization",
                        'name' => 'Aviator Game',
                        'url' => "https://{$domain}/",
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => "https://{$domain}/assets/images/favicon/apple-touch-icon.png",
                            'width' => 180,
                            'height' => 180,
                        ],
                    ],
                ];
            }

            $jsonLdPayload = json_encode([
                '@context' => 'https://schema.org',
                '@graph' => array_values($jsonLdGraph),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
@if($metaTitle !== '')
        <title>{{ $metaTitle }}</title>
@endif
@if($metaDescription !== '')
        <meta name="description" content="{{ $metaDescription }}">
@endif
@if($canonical !== '')
        <link rel="canonical" href="{{ $canonical }}">
@endif
        <meta name="robots" content="{{ e($standardMeta['name:robots']) }}">
        <meta property="og:locale" content="{{ e($standardMeta['property:og:locale']) }}">
@if($standardMeta['property:og:locale:alternate'] !== '')
        <meta property="og:locale:alternate" content="{{ e($standardMeta['property:og:locale:alternate']) }}">
@endif
        <meta property="og:type" content="{{ e($standardMeta['property:og:type']) }}">
        <meta property="og:title" content="{{ e($standardMeta['property:og:title']) }}">
        <meta property="og:description" content="{{ e($standardMeta['property:og:description']) }}">
        <meta property="og:url" content="{{ e($standardMeta['property:og:url']) }}">
        <meta property="og:site_name" content="{{ e($standardMeta['property:og:site_name']) }}">
        <meta property="article:published_time" content="{{ e($standardMeta['property:article:published_time']) }}">
        <meta property="article:modified_time" content="{{ e($standardMeta['property:article:modified_time']) }}">
        <meta name="twitter:card" content="{{ e($standardMeta['name:twitter:card']) }}">
@php
foreach ($extraHeadMeta as $meta) {
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

foreach ($alternateLinks as $link) {
    $linkAttributes = ['rel="alternate"', 'href="' . e((string) $link['href']) . '"'];
    if (!empty($link['hreflang'])) {
        $linkAttributes[] = 'hreflang="' . e((string) $link['hreflang']) . '"';
    }
    echo '        <link ' . implode(' ', $linkAttributes) . '>' . PHP_EOL;
}

foreach ($extraLinks as $link) {
    $linkAttributes = [];
    if (!empty($link['rel'])) {
        $linkAttributes[] = 'rel="' . e((string) $link['rel']) . '"';
    }
    $linkAttributes[] = 'href="' . e((string) $link['href']) . '"';
    if (!empty($link['type'])) {
        $linkAttributes[] = 'type="' . e((string) $link['type']) . '"';
    }
    if (!empty($link['sizes'])) {
        $linkAttributes[] = 'sizes="' . e((string) $link['sizes']) . '"';
    }
    if (!empty($link['hreflang'])) {
        $linkAttributes[] = 'hreflang="' . e((string) $link['hreflang']) . '"';
    }
    echo '        <link ' . implode(' ', $linkAttributes) . '>' . PHP_EOL;
}
@endphp
        <style>
            img[width][height] {
                max-width: 100%;
                object-fit: contain;
            }
        </style>
        <link rel="stylesheet" href="/assets/css/style.css">
        <link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">
        <link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">
        <link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">
        <link rel="manifest" href="/assets/images/favicon/site.webmanifest">
        <script type="application/ld+json">
{!! $jsonLdPayload !!}
        </script>
@if($headCustomResidual !== '')
@php echo preg_replace('/^/m', '        ', $headCustomResidual) . PHP_EOL; @endphp
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

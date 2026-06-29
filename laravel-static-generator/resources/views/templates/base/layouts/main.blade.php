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
            $isHomePage = in_array(trim((string) ($page->slug ?? ''), '/'), ['', 'index'], true);
            $defaultLocale = trim((string) ($page->locale ?? 'en'));
            $ogLocale = str_replace('-', '_', $defaultLocale);
            $pagePublishedTime = $page->created_at ? $page->created_at->toAtomString() : now()->toAtomString();
            $pageModifiedTime = $page->updated_at ? $page->updated_at->toAtomString() : now()->toAtomString();
            $canonicalParts = $canonical !== '' ? parse_url($canonical) : [];
            $canonicalOrigin = is_array($canonicalParts) && isset($canonicalParts['scheme'], $canonicalParts['host'])
                ? $canonicalParts['scheme'] . '://' . $canonicalParts['host'] . (isset($canonicalParts['port']) ? ':' . $canonicalParts['port'] : '')
                : 'https://' . $domain;
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
                'property:article:published_time' => $pagePublishedTime,
                'property:article:modified_time' => $pageModifiedTime,
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
            $schemaPublishedTime = trim((string) ($standardMeta['property:article:published_time'] ?? $pagePublishedTime));
            $schemaModifiedTime = trim((string) ($standardMeta['property:article:modified_time'] ?? $pageModifiedTime));
            $layoutContent = app(\App\Support\SiteLayoutContent::class);
            $siteMenuHtml = $layoutContent->resolveMenuInner($site);
            $siteMobileMenuHtml = $layoutContent->resolveMobileMenuHtml($site);
            $siteFooterHtml = $layoutContent->resolveFooterInner($site);
            $renderSharedHeaderInContent = $layoutContent->shouldRenderHeaderInsideFirstHero($page);
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

            $normalizeSchemaUrl = static function (?string $value) use ($canonicalOrigin): string {
                $value = trim((string) $value);
                if ($value === '') {
                    return $value;
                }

                $value = str_replace('https://{site}', $canonicalOrigin, $value);
                $value = str_replace('http://{site}', $canonicalOrigin, $value);

                if (str_starts_with($value, '//')) {
                    return 'https:' . $value;
                }

                if (str_starts_with($value, '/')) {
                    return rtrim($canonicalOrigin, '/') . $value;
                }

                $parts = parse_url($value);
                if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
                    return $value;
                }

                $path = $parts['path'] ?? '';
                $query = isset($parts['query']) ? '?' . $parts['query'] : '';
                $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

                return rtrim($canonicalOrigin, '/') . $path . $query . $fragment;
            };

            $normalizeSchemaUrls = static function ($value, ?string $key = null) use (&$normalizeSchemaUrls, $normalizeSchemaUrl) {
                $urlKeys = ['@id' => true, 'url' => true, 'image' => true, 'logo' => true, 'contentUrl' => true, 'thumbnailUrl' => true];

                if (is_string($value)) {
                    return isset($urlKeys[(string) $key]) ? $normalizeSchemaUrl($value) : $value;
                }

                if (!is_array($value)) {
                    return $value;
                }

                foreach ($value as $childKey => $childValue) {
                    $value[$childKey] = $normalizeSchemaUrls($childValue, is_string($childKey) ? $childKey : null);
                }

                return $value;
            };

            $schemaGameName = '';
            foreach ($jsonLdGraph as $node) {
                if (!is_array($node)) {
                    continue;
                }

                $mainEntity = $node['mainEntity'] ?? null;
                if (is_array($mainEntity) && (($mainEntity['@type'] ?? null) === 'VideoGame')) {
                    $schemaGameName = trim((string) ($mainEntity['name'] ?? ''));
                    if ($schemaGameName !== '') {
                        break;
                    }
                }
            }
            if ($schemaGameName === '') {
                $titleParts = preg_split('/\s[-–—|:]\s/u', $metaTitle, 2);
                $schemaGameName = trim((string) (($titleParts !== false ? $titleParts[0] : $metaTitle) ?? ''));
            }

            $applyDynamicSchemaFields = static function (array $node) use (&$applyDynamicSchemaFields, $normalizeSchemaUrls, $normalizeSchemaUrl, $canonical, $canonicalOrigin, $metaTitle, $metaDescription, $defaultLocale, $schemaPublishedTime, $schemaModifiedTime, $domain, $site, $schemaGameName, $isHomePage): array {
                $node = $normalizeSchemaUrls($node);
                $type = $node['@type'] ?? null;

                if (isset($node['@id']) && is_string($node['@id'])) {
                    $node['@id'] = $normalizeSchemaUrl($node['@id']);
                }

                if (isset($node['url']) && is_string($node['url'])) {
                    $node['url'] = $normalizeSchemaUrl($node['url']);
                }

                if (isset($node['image']) && is_string($node['image'])) {
                    $node['image'] = $normalizeSchemaUrl($node['image']);
                }

                if (isset($node['logo']) && is_array($node['logo']) && isset($node['logo']['url']) && is_string($node['logo']['url'])) {
                    $node['logo']['url'] = $normalizeSchemaUrl($node['logo']['url']);
                }

                if ($type === 'WebPage') {
                    $node['url'] = $canonical;
                    $node['name'] = $metaTitle;
                    $node['description'] = $metaDescription;
                    $node['inLanguage'] = $defaultLocale;
                    $node['datePublished'] = $schemaPublishedTime;
                    $node['dateModified'] = $schemaModifiedTime;
                }

                if ($type === 'VideoGame') {
                    if ($isHomePage) {
                        $node['url'] = $canonical;
                    }
                    if ($schemaGameName !== '') {
                        $node['name'] = $schemaGameName;
                    }
                    if ($metaDescription !== '') {
                        $node['description'] = $metaDescription;
                    }
                }

                if ($type === 'Organization') {
                    $node['url'] = $canonicalOrigin . '/';
                    $siteName = trim((string) ($site->name ?? ''));
                    if ($siteName !== '') {
                        $node['name'] = $siteName;
                    } elseif ($domain !== '') {
                        $node['name'] = $domain;
                    }
                }

                if (isset($node['mainEntity']) && is_array($node['mainEntity'])) {
                    $node['mainEntity'] = $applyDynamicSchemaFields($node['mainEntity']);
                }

                return $node;
            };

            $jsonLdGraph = array_map(static fn (array $node): array => $applyDynamicSchemaFields($node), $jsonLdGraph);

            $singleGraphTypes = ['FAQPage' => true, 'HowTo' => true];
            $seenGraphTypes = [];
            for ($i = count($jsonLdGraph) - 1; $i >= 0; $i--) {
                $type = $jsonLdGraph[$i]['@type'] ?? null;
                if (!is_string($type) || !isset($singleGraphTypes[$type])) {
                    continue;
                }

                if (isset($seenGraphTypes[$type])) {
                    unset($jsonLdGraph[$i]);
                    continue;
                }

                $seenGraphTypes[$type] = true;
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
    @if(!$renderSharedHeaderInContent)
        @include('templates.base.layouts.shared-header', ['siteMenuHtml' => $siteMenuHtml])
    @endif
    
    <main class="main">
        @yield('content')
    </main>

    @include('templates.base.layouts.shared-mobile-menu', ['siteMobileMenuHtml' => $siteMobileMenuHtml])
    
    @include('templates.base.layouts.shared-footer', ['siteFooterHtml' => $siteFooterHtml])

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
</body>
</html>

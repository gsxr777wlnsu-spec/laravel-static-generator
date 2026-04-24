<!DOCTYPE html>
<html lang="{{ $page->locale ?? 'en' }}">
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $page->meta_title ?? $page->title }}</title>
        <meta name="description" content="{{ $page->meta_description ?? '' }}">
        <meta name="keywords" content="{{ $page->meta_keywords ?? 'game, play, bet, aviator' }}">
        <link rel="canonical" href="{{ $page->canonical ?? url($page->slug) }}">
        <meta name="robots" content="all">
        <meta name="telegram:channel" content="@WP_WooCom">
        <meta name="telegram:bot" content="@WP_WooCom_bot">
        <meta property="vk:image" content="/assets/images/logo/logo.png">
        <meta property="vk:app_id" content="">
        <meta name="og:type" property="og:type" content="website">
        <meta property="og:locale" content="{{ $page->locale ?? 'en' }}_{{ strtoupper($page->locale ?? 'en') }}">
        <meta name="og:title" property="og:title" content="{{ $page->meta_title ?? $page->title }}">
        <meta name="og:description" property="og:description" content="{{ $page->meta_description ?? '' }}">
        <meta property="article:published_time" content="2016">
        <meta property="article:author" content="{{ $site->domain ?? 'site.com' }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $page->meta_title ?? $page->title }}">
        <meta name="twitter:description" content="{{ $page->meta_description ?? '' }}">
        <meta name="twitter:site" content="{{ $site->domain ?? 'site.com' }}">
        <meta name="twitter:creator" content="{{ $site->domain ?? 'site.com' }}">
        <meta name="twitter:image" content="{{ $page->og_image ?? '/assets/images/aviator.jpg' }}">
        <meta property="og:image" content="{{ $page->og_image ?? '/assets/images/aviator.jpg' }}">
        <meta name="geo.region" content="EN">
        <meta name="geo.position" content="55.71881; 37.555728">
        <meta name="ICBM" content="55.71881, 37.555728">
        <meta name="contact" content="support@{{ $site->domain ?? 'site.com' }}">
        <meta name="copyright" content="{{ $site->domain ?? 'site.com' }}">
        <meta name="designer" content="gsxr777">
        <meta name="generator" content="{{ $site->domain ?? 'site.com' }} CMS">
        <link rel="publisher" href="https://{{ $site->domain ?? 'site.com' }}/">
        <meta name="author" content="{{ $site->domain ?? 'site.com' }}">
        <meta name="rating" content="general">
        <link href="/assets/css/style.css" rel="stylesheet">
        <link rel="icon" type="image/png" href="/assets/images/favicon/favicon-96x96.png" sizes="96x96">
        <link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">
        <link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">
        <link rel="manifest" href="/assets/images/favicon/site.webmanifest">
        <script type="application/ld+json">
{
    "@{!! 'context' !!}": "https://schema.org",
    "@type": "WebPage",
    "@id": "https://{{ $site->domain ?? 'site.com' }}/#webpage",
    "url": "https://{{ $site->domain ?? 'site.com' }}/"
}
</script>
        <script type="application/ld+json">
{
    "@{!! 'context' !!}": "https://schema.org",
    "@type": "VideoGame",
    "additionalType": "https://schema.org/WebApplication",
    "@id": "https://{{ $site->domain ?? 'site.com' }}/#aviator-game",
    "name": "Aviator",
    "description": "Play Aviator Game — a thrilling legal online game with a maximum win of 1000x your bet. Created by Spribe, this crash game has a high chance for players to win with 97% RTP.",
    "url": "https://{{ $site->domain ?? 'site.com' }}/",
    "image": "https://{{ $site->domain ?? 'site.com' }}/assets/images/aviator.jpg",
    "author": {
        "@type": "Organization",
        "name": "Spribe"
    },
    "gamePlatform": ["Web Browser", "Mobile", "Desktop"],
    "genre": "Crash Game",
    "applicationCategory": "GameApplication",
    "operatingSystem": "Web Browser",
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.7",
        "bestRating": "5",
        "worstRating": "1",
        "ratingCount": "44"
    }
}
</script>
        <script type="application/ld+json">
{
    "@{!! 'context' !!}": "https://schema.org",
    "@type": "Organization",
    "@id": "https://{{ $site->domain ?? 'site.com' }}/#organization",
    "name": "Aviator Game",
    "url": "https://{{ $site->domain ?? 'site.com' }}/",
    "logo": {
        "@type": "ImageObject",
        "url": "https://{{ $site->domain ?? 'site.com' }}/assets/images/favicon/apple-touch-icon.png",
        "width": 180,
        "height": 180
    }
}
</script>

        @if(isset($languageVersions) && count($languageVersions) > 0)
            @foreach($languageVersions as $version)
            <link rel="alternate" hreflang="{{ $version->locale }}" href="{{ $version->canonical ?? url($version->slug) }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ $page->canonical ?? url($page->slug) }}">
        @endif
</head>
<body>
    @if(($page->template_key ?? '') !== 'blank')
    <header>
        <nav>
            <!-- Navigation -->
        </nav>
    </header>
    @endif
    
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
                            <img src="/assets/images/logo/logo.webp" width="141" height="41"
                                loading="lazy" alt="Aviator">
                        </a>
                    </div>

                    <a class="btn__cta" href="#play-now">Play now!</a>
                </div>

                <nav class="footer__col footer__col--links" aria-label="Footer column 1">
                    <ul class="footer__links">
                        <li class="footer__item"><a class="footer__link"
                                href="/#where-to-play">Where to
                                play</a></li>
                        <li class="footer__item"><a class="footer__link"
                                href="/#characteristics">Characteristics</a></li>
                        <li class="footer__item"><a class="footer__link"
                                href="/#review">Review</a></li>
                        <li class="footer__item"><a class="footer__link"
                                href="/#symbols">Symbols</a>
                        </li>
                    </ul>
                </nav>

                <nav class="footer__col footer__col--links" aria-label="Footer column 2">
                    <ul class="footer__links">
                        <li class="footer__item"><a class="footer__link"
                                href="/#gameplay">Gameplay</a>
                        </li>
                        <li class="footer__item"><a class="footer__link" href="/#rtp">RTP</a>
                        </li>
                        <li class="footer__item"><a class="footer__link"
                                href="/#bonuses">Bonuses</a>
                        </li>
                        <li class="footer__item"><a class="footer__link"
                                href="/#conclusion">Conclusion</a></li>
                    </ul>
                </nav>

                <nav class="footer__col footer__col--links" aria-label="Footer column 3">
                    <ul class="footer__links">
                        <li class="footer__item"><a class="footer__link"
                                href="terms-and-conditions.html">Terms and
                                Conditions</a></li>
                        <li class="footer__item"><a class="footer__link"
                                href="cookie-policy.html">Cookie
                                Policy</a></li>
                        <li class="footer__item"><a class="footer__link"
                                href="privacy-policy.html">Privacy
                                Policy</a></li>
                        <li class="footer__item"><a class="footer__link"
                                href="sitemap.html">Sitemap</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="footer__info" aria-label="Footer disclaimer">
                <svg class="footer__age-icon" width="63" height="63" viewBox="0 0 34 34" role="img"
                    aria-label="18+">
                    <circle cx="17" cy="17" r="16" fill="none" stroke="var(--color-white)"
                        stroke-width="2">
                    </circle>
                    <text x="17" y="22" text-anchor="middle" font-size="14"
                        font-family="Inter, Arial, sans-serif" font-weight="700"
                        fill="var(--color-white)">18+</text>
                </svg>

                <div class="footer__info-text">
                    <span class="footer__info-copy">{{ $site->domain ?? 'site.com' }} is one of Spribe’s independent
                        affiliates. We are experts in presenting accurate, objective information
                        about cutting-edge casino games and iGaming products. Please go over our
                        terms and conditions and privacy policy. Please be aware that the
                        activities of users on third-party sites are not under the control of
                        our organization.</span>
                </div>

                <div class="footer__payments" aria-label="Payment systems">
                    <img class="footer__payment" src="/assets/images/payment-systems/visa.webp"
                        width="80" height="40" loading="lazy" alt="Visa">
                    <img class="footer__payment" src="/assets/images/payment-systems/mc.webp"
                        width="80" height="40" loading="lazy" alt="Mastercard">
                    <img class="footer__payment" src="/assets/images/payment-systems/ae.webp"
                        width="80" height="40" loading="lazy" alt="American Express">
                    <img class="footer__payment" src="/assets/images/payment-systems/paypal.webp"
                        width="80" height="40" loading="lazy" alt="PayPal">
                </div>
            </div>

            <div class="footer__copyright" aria-label="Copyright">© Copyright 2024-{{ date('Y') }}</div>
        </div>
    </footer>
    
    <script defer src="/assets/js/app.js"></script>
    <script defer src="/assets/js/lightbox.js"></script>
    <script defer src="/assets/js/faq.js"></script>
    @endif
</body>
</html>

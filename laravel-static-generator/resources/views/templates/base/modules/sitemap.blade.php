@php
    $sectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'sitemap';

    $sectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'sitemap';

    $links = [];
    if (isset($sitemapLinks) && is_array($sitemapLinks)) {
        $links = $sitemapLinks;
    }
@endphp

<section class="{{ $sectionClass }}" id="{{ $sectionId }}">
    <div class="sitemap__inner">
        <div class="sitemap__list">
            @forelse($links as $link)
                @php
                    $href = isset($link['href']) ? trim((string) $link['href']) : '';
                    $label = isset($link['label']) ? trim((string) $link['label']) : '';
                @endphp

                @if($href !== '' && $label !== '')
                    <div class="sitemap__item">
                        <a class="sitemap__link" href="{{ $href }}">{{ $label }}</a>
                    </div>
                @endif
            @empty
                <div class="sitemap__item">
                    <a class="sitemap__link" href="/">Home</a>
                </div>
            @endforelse
        </div>
    </div>
</section>

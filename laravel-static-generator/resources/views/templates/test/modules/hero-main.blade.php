@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
@else
@php
    $heroTitleText = isset($heroTitle) && is_string($heroTitle) && trim($heroTitle) !== ''
        ? trim($heroTitle)
        : ($page->title ?? '');
    $heroDescriptionText = isset($description) && is_string($description) && trim($description) !== ''
        ? trim($description)
        : null;
    $heroCtaText = isset($ctaText) && is_string($ctaText) && trim($ctaText) !== ''
        ? trim($ctaText)
        : 'Play now!';
    $heroCtaHref = isset($ctaHref) && is_string($ctaHref) && trim($ctaHref) !== ''
        ? trim($ctaHref)
        : '#play-now';
    $heroImageSrc = isset($imageSrc) && is_string($imageSrc) && trim($imageSrc) !== ''
        ? trim($imageSrc)
        : '/assets/images/hero/aviator.webp';
    $heroImageAlt = isset($imageAlt) && is_string($imageAlt) && trim($imageAlt) !== ''
        ? trim($imageAlt)
        : 'Aviator';
@endphp
<section class="hero" id="hero">
    <div class="hero__inner">
        <div class="hero__content">
            <div class="hero__text">
                <h1 class="hero__title">{{ $heroTitleText }}</h1>
                @if($heroDescriptionText)
                    <p class="hero__description">{{ $heroDescriptionText }}</p>
                @endif
            </div>

            @if($heroCtaText !== '')
                <a class="btn__cta btn__cta--hero" href="{{ $heroCtaHref }}">{{ $heroCtaText }}</a>
            @endif
        </div>

        <div class="hero__media">
            <img class="hero__image" src="{{ $heroImageSrc }}" width="560" height="582" alt="{{ $heroImageAlt }}">
        </div>
    </div>
</section>
@endif

@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
@else
@php
    $reviewDescription = isset($description) && is_string($description) && trim($description) !== ''
        ? trim($description)
        : 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.';
    $reviewAccent = isset($reviewAccent) && is_string($reviewAccent) && trim($reviewAccent) !== ''
        ? trim($reviewAccent)
        : $reviewDescription;
    $reviewTitleTop = isset($reviewTitleTop) && is_string($reviewTitleTop) && trim($reviewTitleTop) !== ''
        ? trim($reviewTitleTop)
        : 'The Aviator';
    $reviewTitleBottom = isset($reviewTitleBottom) && is_string($reviewTitleBottom) && trim($reviewTitleBottom) !== ''
        ? trim($reviewTitleBottom)
        : 'Game Review';
@endphp
<section class="casino" id="casino">
    <nav class="casino__menu" aria-label="Section navigation" data-casino-menu>
        <ul class="casino__menu-list" data-casino-menu-list>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#casino">Where to play</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#download">Download</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#installation">Installation</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#benefits">Benefits</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#comparison">Comparison</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#game">Game</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#where-to-play">Where to play</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#other-reviews">Other reviews</a></li>
            <li class="casino__menu-item"><a class="casino__menu-link" href="#faq">FAQ</a></li>
        </ul>

        <div class="casino__menu-dropdown" data-casino-menu-dropdown hidden aria-hidden="true">
            <ul class="casino__menu-dropdown-list" data-casino-menu-overflow></ul>
        </div>
    </nav>

    <div class="review__inner">
        <div class="review__content">
            <h2 class="review__title">
                <span class="review__title-top">{{ $reviewTitleTop }}</span>
                <span class="review__title-bottom">{{ $reviewTitleBottom }}</span>
            </h2>

            <p class="review__description">{{ $reviewDescription }}</p>
            <p class="review__accent">{{ $reviewAccent }}</p>
        </div>

        <figure class="review__media" aria-hidden="true">
            <img class="review__image" src="/assets/images/phone-aviator.webp" width="443" height="428" loading="lazy" alt="">
            <figcaption class="level__caption">Description Level Image</figcaption>
        </figure>
    </div>
</section>
@endif

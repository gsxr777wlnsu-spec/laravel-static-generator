@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
@else
@php
    $pageTitleText = isset($pageTitle) && is_string($pageTitle) && trim($pageTitle) !== ''
        ? trim($pageTitle)
        : ($page->title ?? '');
    $heroTitleText = isset($heroTitle) && is_string($heroTitle) && trim($heroTitle) !== ''
        ? trim($heroTitle)
        : ($page->title ?? '');
@endphp
<section class="hero hero--simple hero--has-breadcrumbs" id="hero">
    <nav class="breadcrumbs-container" aria-label="Breadcrumb">
        <div class="breadcrumbs">
            <a class="breadcrumbs__item" href="/">Home page</a>
            <span class="breadcrumbs__separator" aria-hidden="true">→</span>
            <span class="breadcrumbs__item breadcrumbs__item--active" aria-current="page">{{ $pageTitleText }}</span>
        </div>
    </nav>

    <div class="hero__inner">
        <div class="hero__content">
            <div class="hero__text">
                <h1 class="hero__title">{{ $heroTitleText }}</h1>
            </div>
        </div>
    </div>
</section>
@endif

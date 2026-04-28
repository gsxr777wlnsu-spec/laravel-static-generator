@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
    @if(!str_contains($raw_html, 'data-mobile-menu'))
        @include('templates.test.modules._hero_mobile_menu')
    @endif
@else
    @php
        $heroSectionClass = isset($class) && is_string($class) && trim($class) !== ''
            ? trim($class)
            : 'hero hero--authors hero--has-breadcrumbs';
        $heroSectionId = isset($id) && is_string($id) && trim($id) !== ''
            ? trim($id)
            : 'hero';
    @endphp

    @include('templates.test.modules._generic_section', [
        'module_slug' => 'hero-authors',
        'class' => $heroSectionClass,
        'id' => $heroSectionId,
    ])

    @include('templates.test.modules._hero_mobile_menu')
@endif

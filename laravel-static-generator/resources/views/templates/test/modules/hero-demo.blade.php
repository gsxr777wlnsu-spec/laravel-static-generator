@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
@else
    @php
        $heroSectionClass = isset($class) && is_string($class) && trim($class) !== ''
            ? trim($class)
            : 'hero hero--demo hero--has-breadcrumbs';
        $heroSectionId = isset($id) && is_string($id) && trim($id) !== ''
            ? trim($id)
            : 'hero';
    @endphp

    @include('templates.test.modules._generic_section', [
        'module_slug' => 'hero-demo',
        'class' => $heroSectionClass,
        'id' => $heroSectionId,
    ])
@endif

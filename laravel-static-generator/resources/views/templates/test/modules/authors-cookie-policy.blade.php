@php
    $authorsSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'authors';
    $authorsSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'cookies';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'authors-cookie-policy',
    'class' => $authorsSectionClass,
    'id' => $authorsSectionId,
])

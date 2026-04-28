@php
    $downloadSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'download background--characteristics';
    $downloadSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'download';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'download',
    'class' => $downloadSectionClass,
    'id' => $downloadSectionId,
])

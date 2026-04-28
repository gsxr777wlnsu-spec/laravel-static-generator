@php
    $characteristicsSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'characteristics background--characteristics';
    $characteristicsSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'characteristics';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'characteristics-comparison',
    'class' => $characteristicsSectionClass,
    'id' => $characteristicsSectionId,
])

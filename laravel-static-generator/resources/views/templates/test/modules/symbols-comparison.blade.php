@php
    $symbolsSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'symbols';
    $symbolsSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'symbols';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'symbols-comparison',
    'class' => $symbolsSectionClass,
    'id' => $symbolsSectionId,
])

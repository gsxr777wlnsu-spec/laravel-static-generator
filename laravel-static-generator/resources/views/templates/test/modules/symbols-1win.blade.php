@php
    $symbolsSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'symbols symbols-mt0';
    $symbolsSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'details';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'symbols-1win',
    'class' => $symbolsSectionClass,
    'id' => $symbolsSectionId,
])

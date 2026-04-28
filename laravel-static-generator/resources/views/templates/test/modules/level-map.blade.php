@php
    $levelSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'level';
    $levelSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'level';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'level-map',
    'class' => $levelSectionClass,
    'id' => $levelSectionId,
])

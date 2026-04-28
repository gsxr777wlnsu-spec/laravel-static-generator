@php
    $stepsSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'steps steps--demo';
    $stepsSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'steps';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'steps-demo',
    'class' => $stepsSectionClass,
    'id' => $stepsSectionId,
])

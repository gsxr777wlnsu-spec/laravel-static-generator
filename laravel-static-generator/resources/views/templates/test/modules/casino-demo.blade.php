@php
    $casinoSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'casino casino--demo';
    $casinoSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'casino';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'casino-demo',
    'class' => $casinoSectionClass,
    'id' => $casinoSectionId,
])

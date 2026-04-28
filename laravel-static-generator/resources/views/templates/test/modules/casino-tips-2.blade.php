@php
    $casinoSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'casino casino--tips';
    $casinoSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'casino-2';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'casino-tips-2',
    'class' => $casinoSectionClass,
    'id' => $casinoSectionId,
])

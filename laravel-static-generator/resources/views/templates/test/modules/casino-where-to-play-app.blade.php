@php
    $casinoSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'casino casino--errors';
    $casinoSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'where-to-play';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'casino-where-to-play-app',
    'class' => $casinoSectionClass,
    'id' => $casinoSectionId,
])

@php
    $casinoSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'casino';
    $casinoSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'bonuses';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'casino-1win',
    'class' => $casinoSectionClass,
    'id' => $casinoSectionId,
])

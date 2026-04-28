@php
    $benefitsSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'benefits benefits--demo';
    $benefitsSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'benefits';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'benefits-demo',
    'class' => $benefitsSectionClass,
    'id' => $benefitsSectionId,
])

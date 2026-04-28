@php
    $formSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'form background--characteristics';
    $formSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'form';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'form-reviews',
    'class' => $formSectionClass,
    'id' => $formSectionId,
])

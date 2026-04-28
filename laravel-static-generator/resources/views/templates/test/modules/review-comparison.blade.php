@php
    $reviewSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'review';
    $reviewSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'review';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'review-comparison',
    'class' => $reviewSectionClass,
    'id' => $reviewSectionId,
])

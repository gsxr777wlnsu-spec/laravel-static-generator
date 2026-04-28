@php
    $reviewSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'review';
    $reviewSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'mobile-app';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'review-1win',
    'class' => $reviewSectionClass,
    'id' => $reviewSectionId,
])

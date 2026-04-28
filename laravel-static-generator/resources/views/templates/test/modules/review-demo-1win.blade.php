@php
    $reviewSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'review review--media-last-tablet';
    $reviewSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'demo';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'review-demo-1win',
    'class' => $reviewSectionClass,
    'id' => $reviewSectionId,
])

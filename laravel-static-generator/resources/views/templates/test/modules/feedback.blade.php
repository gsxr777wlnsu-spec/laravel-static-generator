@php
    $feedbackSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'feedback';
    $feedbackSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'feedback';
    $feedbackModuleSlug = str_contains($feedbackSectionClass, 'feedback--reviews')
        ? 'feedback-reviews'
        : 'feedback';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => $feedbackModuleSlug,
    'class' => $feedbackSectionClass,
    'id' => $feedbackSectionId,
])

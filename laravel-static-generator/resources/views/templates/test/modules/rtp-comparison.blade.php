@php
    $rtpSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'rtp';
    $rtpSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'rtp';
@endphp

@include('templates.test.modules._generic_section', [
    'module_slug' => 'rtp-comparison',
    'class' => $rtpSectionClass,
    'id' => $rtpSectionId,
])

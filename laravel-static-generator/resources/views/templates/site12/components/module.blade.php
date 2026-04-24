@php
    $rawContent = $section->content ?? null;
    $content = is_array($rawContent) ? $rawContent : (is_string($rawContent) ? json_decode($rawContent, true) ?: [] : []);
    
    $sectionId = isset($content['id']) && is_string($content['id']) && trim($content['id']) !== '' ? trim($content['id']) : null;
    $defaultClass = isset($content['module']) && is_string($content['module']) && trim($content['module']) !== '' ? trim($content['module']) : 'module';
    $sectionClass = isset($content['class']) && is_string($content['class']) && trim($content['class']) !== '' ? trim($content['class']) : $defaultClass;
    $rawHtml = isset($content['raw_html']) && is_string($content['raw_html']) ? $content['raw_html'] : '';
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    @if(trim($rawHtml) !== '')
        {!! $rawHtml !!}
    @elseif(isset($content['content']))
        {!! $content['content'] !!}
    @endif
</section>


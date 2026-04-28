@php
    $rawContent = $section->content ?? null;
    $content = is_array($rawContent) ? $rawContent : (is_string($rawContent) ? json_decode($rawContent, true) ?: [] : []);
    
    $sectionId = isset($content['id']) && is_string($content['id']) && trim($content['id']) !== '' ? trim($content['id']) : null;
    $defaultClass = isset($content['module']) && is_string($content['module']) && trim($content['module']) !== '' ? trim($content['module']) : 'module';
    $sectionClass = isset($content['class']) && is_string($content['class']) && trim($content['class']) !== '' ? trim($content['class']) : $defaultClass;
    $rawHtml = isset($content['raw_html']) && is_string($content['raw_html']) ? $content['raw_html'] : '';
    
    if (($content['module'] ?? '') === 'footer' || ($content['module'] ?? '') === 'header') {
        return;
    }
    
    $skipWrapper = false;
    if (trim($rawHtml) !== '' && preg_match('/^\s*<section[^>]*>/i', $rawHtml)) {
        $skipWrapper = true;
    }
@endphp

@if(!$skipWrapper)
<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
@endif
    @if(trim($rawHtml) !== '')
        {!! $rawHtml !!}
    @elseif(isset($content['content']))
        {!! $content['content'] !!}
    @endif
@if(!$skipWrapper)
</section>
@endif


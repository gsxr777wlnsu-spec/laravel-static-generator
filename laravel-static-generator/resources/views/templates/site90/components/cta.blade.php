@php
    $content = is_array($section->content ?? null) ? $section->content : [];
    $sectionId = $content['id'] ?? null;
    $sectionClass = trim('cta-section '.($content['class'] ?? ''));
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    @if(isset($content['heading']))
    <h2>{{ $content['heading'] }}</h2>
    @endif
    
    @if(isset($content['description']))
    <p>{{ $content['description'] }}</p>
    @endif
    
    <a href="{{ $content['link'] ?? '#' }}" class="cta-button">
        {{ $content['text'] ?? 'Learn more' }}
    </a>
</section>

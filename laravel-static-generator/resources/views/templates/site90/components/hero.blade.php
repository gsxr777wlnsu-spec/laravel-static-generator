@php
    $content = is_array($section->content ?? null) ? $section->content : [];
    $sectionId = $content['id'] ?? null;
    $sectionClass = trim('hero-section '.($content['class'] ?? ''));
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    <div class="hero-content">
        <h1>{{ $content['heading'] ?? '' }}</h1>
        
        @if(isset($content['subheading']))
        <p class="subheading">{{ $content['subheading'] }}</p>
        @endif
        
        @if(isset($content['cta_text']) && isset($content['cta_link']))
        <a href="{{ $content['cta_link'] }}" class="cta-button">
            {{ $content['cta_text'] }}
        </a>
        @endif
    </div>
    
    @if(isset($content['image']))
    <div class="hero-image">
        <img src="{{ $content['image'] }}" alt="{{ $content['image_alt'] ?? '' }}">
    </div>
    @endif
</section>

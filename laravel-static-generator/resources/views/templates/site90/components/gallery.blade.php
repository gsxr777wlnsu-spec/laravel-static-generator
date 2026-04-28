@php
    $content = is_array($section->content ?? null) ? $section->content : [];
    $sectionId = $content['id'] ?? null;
    $sectionClass = trim('gallery-section '.($content['class'] ?? ''));
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    @if(isset($content['heading']))
    <h2>{{ $content['heading'] }}</h2>
    @endif
    
    <div class="gallery-grid">
        @foreach(($content['images'] ?? []) as $image)
        <div class="gallery-item">
            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? '' }}">
            @if(isset($image['caption']))
            <p class="caption">{{ $image['caption'] }}</p>
            @endif
        </div>
        @endforeach
    </div>
</section>

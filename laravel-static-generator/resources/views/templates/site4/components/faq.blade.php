@php
    $content = is_array($section->content ?? null) ? $section->content : [];
    $sectionId = $content['id'] ?? null;
    $sectionClass = trim('faq-section '.($content['class'] ?? ''));
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    @if(isset($content['heading']))
    <h2>{{ $content['heading'] }}</h2>
    @endif
    
    <div class="faq-item">
        <h3 class="faq-question">{{ $content['question'] ?? '' }}</h3>
        <div class="faq-answer">
            {!! $content['answer'] ?? '' !!}
        </div>
    </div>
</section>

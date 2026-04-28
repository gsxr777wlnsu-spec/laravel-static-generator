@php
    $content = is_array($section->content ?? null) ? $section->content : [];
    $sectionId = $content['id'] ?? null;
    $sectionClass = trim('list-section '.($content['class'] ?? ''));
@endphp

<section @if($sectionId) id="{{ $sectionId }}" @endif class="{{ $sectionClass }}">
    @if(isset($content['heading']))
    <h2>{{ $content['heading'] }}</h2>
    @endif
    
    <ul>
        @foreach(($content['items'] ?? []) as $item)
        <li>{{ $item }}</li>
        @endforeach
    </ul>
</section>

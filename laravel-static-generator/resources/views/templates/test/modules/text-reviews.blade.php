@php
    $textSectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : 'casino';
    $textSectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : 'text';

    $defaultModuleHtml = '';
    $defaultModulePath = resource_path('views/defaults/modules/text-reviews.html');
    if (is_file($defaultModulePath)) {
        $loadedDefaultHtml = file_get_contents($defaultModulePath);
        if (is_string($loadedDefaultHtml) && trim($loadedDefaultHtml) !== '') {
            $defaultModuleHtml = $loadedDefaultHtml;
        }
    }
@endphp

@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
@else
    <div class="{{ $textSectionClass }}" id="{{ $textSectionId }}">
        @if($defaultModuleHtml !== '')
            {!! $defaultModuleHtml !!}
        @elseif(isset($description) && is_string($description) && trim($description) !== '')
            <p class="text text--left">{{ trim($description) }}</p>
        @endif
    </div>
@endif

@php
    $moduleSlug = isset($module_slug) && is_string($module_slug) && trim($module_slug) !== ''
        ? trim($module_slug)
        : 'module';

    $sectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : $moduleSlug;

    $sectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : $moduleSlug;

    $sectionTitle = null;
    foreach (['heading', 'title'] as $titleKey) {
        if (isset($$titleKey) && is_string($$titleKey) && trim($$titleKey) !== '') {
            $sectionTitle = trim($$titleKey);
            break;
        }
    }

    if ($sectionTitle === null) {
        $sectionTitle = ucwords(str_replace('-', ' ', $moduleSlug));
    }

    $sectionSubtitle = isset($subheading) && is_string($subheading) && trim($subheading) !== ''
        ? trim($subheading)
        : null;

    $sectionDescription = isset($description) && is_string($description) && trim($description) !== ''
        ? trim($description)
        : null;

    $sectionText = isset($text) && is_string($text) && trim($text) !== ''
        ? trim($text)
        : null;

    $unorderedItems = [];
    foreach (['items', 'listItems', 'addressItems'] as $itemsKey) {
        if (isset($$itemsKey) && is_array($$itemsKey) && count($$itemsKey) > 0) {
            $unorderedItems = $$itemsKey;
            break;
        }
    }

    $orderedItemsLocal = isset($orderedItems) && is_array($orderedItems) ? $orderedItems : [];

    $defaultModuleHtml = '';
    $defaultModulePath = resource_path("views/defaults/modules/{$moduleSlug}.html");
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
    @if($defaultModuleHtml !== '')
        <section class="{{ $sectionClass }}" id="{{ $sectionId }}">
            {!! $defaultModuleHtml !!}
        </section>
    @else
        <section class="{{ $sectionClass }}" id="{{ $sectionId }}">
            <div class="{{ $moduleSlug }}__inner">
                @if($sectionTitle !== '')
                    <h2 class="{{ $moduleSlug }}__title">{{ $sectionTitle }}</h2>
                @endif

                @if($sectionSubtitle)
                    <h3 class="{{ $moduleSlug }}__subtitle">{{ $sectionSubtitle }}</h3>
                @endif

                @if($sectionDescription)
                    <p class="{{ $moduleSlug }}__description">{{ $sectionDescription }}</p>
                @endif

                @if($sectionText)
                    <p class="{{ $moduleSlug }}__text">{!! nl2br(e($sectionText)) !!}</p>
                @endif

                @if(count($unorderedItems) > 0)
                    <ul class="{{ $moduleSlug }}__list" aria-label="{{ $moduleSlug }} list">
                        @foreach($unorderedItems as $item)
                            <li class="{{ $moduleSlug }}__list-item">{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(count($orderedItemsLocal) > 0)
                    <ol class="{{ $moduleSlug }}__ordered-list" aria-label="{{ $moduleSlug }} ordered list">
                        @foreach($orderedItemsLocal as $item)
                            <li class="{{ $moduleSlug }}__ordered-item">{{ $item }}</li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    @endif
@endif

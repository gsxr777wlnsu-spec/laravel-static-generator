@php
    $layoutContent = app(\App\Support\SiteLayoutContent::class);
    $renderSharedHeaderInContent = $renderSharedHeaderInContent ?? $layoutContent->shouldRenderHeaderInsideFirstHero($page);
    $siteMenuHtml = $siteMenuHtml ?? $layoutContent->resolveMenuInner($site ?? null);
    $sharedHeaderInjected = false;
@endphp
<div class="container">
    @foreach($page->sections as $section)
        @php
            $sectionContent = is_string($section->content) ? json_decode($section->content, true) : $section->content;
            $sectionContent = is_array($sectionContent) ? $sectionContent : [];

            $renderMode = $sectionContent['render_mode'] ?? null;
            $contentRawHtml = isset($sectionContent['raw_html']) && is_string($sectionContent['raw_html'])
                ? trim($sectionContent['raw_html'])
                : '';
            $dbRawHtml = is_string($section->raw_html) ? trim($section->raw_html) : '';
            $resolvedRawHtml = $contentRawHtml !== '' ? $contentRawHtml : $dbRawHtml;
            $resolvedRawHtml = preg_replace_callback('/\[\[([A-Za-z0-9_.-]+)\]\]/', function ($matches) use ($sectionContent) {
                $key = $matches[1];

                if (!array_key_exists($key, $sectionContent) || is_array($sectionContent[$key])) {
                    return $matches[0];
                }

                return e((string) $sectionContent[$key]);
            }, $resolvedRawHtml) ?? $resolvedRawHtml;
            $layoutContent = app(\App\Support\SiteLayoutContent::class);
            $resolvedRawHtml = $layoutContent->sanitizeSectionHtml($resolvedRawHtml);

            $sectionContent['raw_html'] = $resolvedRawHtml;
            $moduleKey = strtolower(trim((string) ($section->module ?? $sectionContent['module'] ?? $sectionContent['module_key'] ?? '')));
            $injectSharedHeader = ($renderSharedHeaderInContent ?? false)
                && !$sharedHeaderInjected
                && !in_array($moduleKey, ['header', 'footer', 'menu', 'mobile-menu'], true);

            if ($injectSharedHeader) {
                $sharedHeaderInjected = true;
            }
        @endphp

        @if(in_array($moduleKey, ['header', 'footer', 'menu', 'mobile-menu'], true))
            @continue
        @elseif($renderMode === 'raw_html' && $resolvedRawHtml !== '')
            {!! $injectSharedHeader ? $layoutContent->injectHeaderIntoFirstHero($resolvedRawHtml, $siteMenuHtml) : $resolvedRawHtml !!}
        @elseif($section->module && $section->module !== '')
            @php
                $moduleView = 'templates.test.modules.' . $section->module;
            @endphp

            @if(\Illuminate\Support\Facades\View::exists($moduleView))
                {!! view($moduleView, array_merge([
                    'section' => $section,
                    'page' => $page,
                    'site' => $site,
                ], $sectionContent))->render() !!}
            @elseif($resolvedRawHtml !== '')
                {!! $resolvedRawHtml !!}
            @endif
        @elseif($resolvedRawHtml !== '')
            {!! $resolvedRawHtml !!}
        @endif
    @endforeach
</div>

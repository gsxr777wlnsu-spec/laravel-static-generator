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
        @endphp

        @if($renderMode === 'raw_html' && $resolvedRawHtml !== '')
            {!! $resolvedRawHtml !!}
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

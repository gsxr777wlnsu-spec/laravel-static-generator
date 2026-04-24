<div class="container">
    @foreach($page->sections as $section)
        @php
            $sectionContent = is_string($section->content) ? json_decode($section->content, true) : $section->content;
            $sectionContent = is_array($sectionContent) ? $sectionContent : [];
        @endphp

        @if($section->raw_html)
            {!! $section->raw_html !!}
        @elseif($section->module && $section->module !== '')
            {!! view('templates.test.modules.' . $section->module, array_merge([
                'section' => $section,
                'page' => $page,
                'site' => $site,
            ], $sectionContent))->render() !!}
        @endif
    @endforeach
</div>
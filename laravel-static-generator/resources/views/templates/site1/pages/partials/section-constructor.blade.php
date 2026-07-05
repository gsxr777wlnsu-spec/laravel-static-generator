@php
    $layoutContent = app(\App\Support\SiteLayoutContent::class);
    $renderSharedHeaderInContent = $renderSharedHeaderInContent ?? $layoutContent->shouldRenderHeaderInsideFirstHero($page);
    $siteMenuHtml = $siteMenuHtml ?? $layoutContent->resolveMenuInner($site ?? null);
    $sharedHeaderInjected = false;
@endphp
<div class="container">
    @foreach($page->sections as $section)
        @php
            $sectionContent = is_array($section->content ?? null)
                ? $section->content
                : (is_string($section->content ?? null) ? json_decode($section->content, true) : []);
            $sectionContent = is_array($sectionContent) ? $sectionContent : [];
            $moduleKey = strtolower(trim((string) ($section->module ?? $sectionContent['module'] ?? $sectionContent['module_key'] ?? '')));
            $injectSharedHeader = ($renderSharedHeaderInContent ?? false)
                && !$sharedHeaderInjected
                && !in_array($moduleKey, ['header', 'footer', 'menu', 'mobile-menu'], true);

            if ($injectSharedHeader) {
                $sharedHeaderInjected = true;
            }
        @endphp
        @include('templates.base.components.render-section', [
            'section' => $section,
            'page' => $page,
            'site' => $site,
            'renderSharedHeaderInContent' => $injectSharedHeader,
            'siteMenuHtml' => $siteMenuHtml ?? null,
        ])
    @endforeach
</div>

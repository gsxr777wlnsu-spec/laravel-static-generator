<div class="container">
    @foreach($page->sections as $section)
        @include('templates.base.components.render-section', ['section' => $section, 'page' => $page, 'site' => $site])
    @endforeach
</div>

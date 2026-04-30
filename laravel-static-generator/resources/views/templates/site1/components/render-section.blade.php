@php
    $rawSectionContent = $section->content ?? null;
    $sectionContent = is_array($rawSectionContent) ? $rawSectionContent : (is_string($rawSectionContent) ? json_decode($rawSectionContent, true) ?: [] : []);

    $moduleKey = $sectionContent['module'] ?? $sectionContent['module_key'] ?? null;
    $sectionId = $sectionContent['id'] ?? null;
    $sectionClass = $sectionContent['class'] ?? $sectionContent['class_name'] ?? null;

    $normalize = static function (?string $value): ?string {
        if (!$value) {
            return null;
        }

        return \Illuminate\Support\Str::of($value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]+/', '-')
            ->trim('-')
            ->value();
    };

    $sectionType = $normalize($section->type ?? 'text');
    $moduleKey = $normalize($moduleKey);
    $sectionId = $normalize($sectionId);

    $classTokens = [];
    if (is_string($sectionClass) && trim($sectionClass) !== '') {
        foreach (preg_split('/\s+/', trim($sectionClass)) ?: [] as $classToken) {
            $normalized = $normalize($classToken);
            if ($normalized) {
                $classTokens[] = $normalized;
            }
        }
    }

    $templateSet = $site->template_set ?? 'base';
    $viewCandidates = [];

    if ($moduleKey) {
        $viewCandidates[] = "templates.{$templateSet}.modules.{$moduleKey}";
        $viewCandidates[] = "templates.base.modules.{$moduleKey}";
        $viewCandidates[] = "templates.{$templateSet}.modules.shared.{$moduleKey}";
        $viewCandidates[] = "templates.base.modules.shared.{$moduleKey}";
    }

    if ($sectionId) {
        $viewCandidates[] = "templates.{$templateSet}.modules.by-id.{$sectionId}";
        $viewCandidates[] = "templates.base.modules.by-id.{$sectionId}";
    }

    foreach ($classTokens as $classToken) {
        $viewCandidates[] = "templates.{$templateSet}.modules.by-class.{$classToken}";
        $viewCandidates[] = "templates.base.modules.by-class.{$classToken}";
    }

    $viewCandidates[] = "templates.{$templateSet}.components.{$sectionType}";
    $viewCandidates[] = "templates.base.components.{$sectionType}";
    $viewCandidates = array_values(array_unique($viewCandidates));

    $resolvedView = null;
    foreach ($viewCandidates as $candidate) {
        if (\Illuminate\Support\Facades\View::exists($candidate)) {
            $resolvedView = $candidate;
            break;
        }
    }
@endphp

@if($resolvedView)
    @include($resolvedView, array_merge([
        'section' => $section,
        'page' => $page,
        'site' => $site,
    ], $sectionContent))
@else
    {{-- Fallback if no module/component exists for this section --}}
    <section class="section section--missing-template" data-section-type="{{ $section->type }}">
        <p>Missing template for section type "{{ $section->type }}".</p>
    </section>
@endif

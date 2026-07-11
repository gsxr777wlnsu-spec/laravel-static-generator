<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PageHeadDefaultsService
{
    public function applyToData(array $data, Site $site): array
    {
        $defaults = $this->defaultsFor($data, $site);
        if ($defaults === []) {
            return $data;
        }

        foreach (['title', 'meta_title', 'meta_description', 'meta_keywords', 'canonical'] as $key) {
            if (!array_key_exists($key, $data) || $this->blank($data[$key] ?? null)) {
                $data[$key] = $defaults[$key] ?? ($data[$key] ?? null);
            }
        }

        $data['og_data'] = $this->mergeOgData($data['og_data'] ?? null, $defaults['og_data'] ?? []);
        if ((!array_key_exists('json_ld', $data) || $this->blank($data['json_ld'] ?? null)) && array_key_exists('json_ld', $defaults)) {
            $data['json_ld'] = $defaults['json_ld'];
        }

        return $data;
    }

    public function applyToPage(Page $page): bool
    {
        $page->loadMissing('site');
        if (!$page->site) {
            return false;
        }

        $data = $this->applyToData($page->only([
            'slug',
            'title',
            'template_key',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'canonical',
            'og_data',
            'json_ld',
        ]), $page->site);

        $updates = [];
        foreach (['title', 'meta_title', 'meta_description', 'meta_keywords', 'canonical', 'og_data', 'json_ld'] as $key) {
            if (($page->{$key} ?? null) !== ($data[$key] ?? null)) {
                $updates[$key] = $data[$key] ?? null;
            }
        }

        if ($updates === []) {
            return false;
        }

        $page->update($updates);
        return true;
    }

    private function defaultsFor(array $data, Site $site): array
    {
        $path = $this->templatePath($data);
        if ($path === null) {
            return [];
        }

        $templateText = File::get($path);
        $fields = $this->parseHeadFields($templateText);
        if ($fields === []) {
            return [];
        }

        $variables = $this->parseVariables($templateText);
        $values = [];
        foreach ($fields as $field) {
            $fieldPath = $field['path'] ?? '';
            $value = $this->replacePlaceholders((string) ($field['value'] ?? ''), $data, $site, $variables);

            if ($fieldPath === 'pages.0.title') {
                $values['title'] = $value;
                continue;
            }
            if (str_starts_with($fieldPath, 'pages.0.')) {
                $relativePath = substr($fieldPath, strlen('pages.0.'));
                if (str_starts_with($relativePath, 'og_data.head_extra.__script__.')) {
                    $index = (int) substr($relativePath, strlen('og_data.head_extra.__script__.'));
                    $values['og_data']['head_extra_scripts'][$index] = $value;
                    continue;
                }

                Arr::set($values, $relativePath, $value);
            }
        }

        if (isset($values['og_data']['head_extra_scripts']) && is_array($values['og_data']['head_extra_scripts'])) {
            ksort($values['og_data']['head_extra_scripts']);
            $values['og_data']['head_extra'] = implode("\n", array_map(
                fn (string $script): string => "<script type=\"application/ld+json\">\n{$script}\n</script>",
                array_filter($values['og_data']['head_extra_scripts'], fn ($script) => trim((string) $script) !== '')
            ));
            unset($values['og_data']['head_extra_scripts']);
        }

        if (isset($values['og_data']['head_meta']) && is_array($values['og_data']['head_meta'])) {
            $values['og_data']['head_meta'] = $this->normalizeHeadMetaRows($values['og_data']['head_meta'], $data, $site);
        }

        return $values;
    }

    private function templatePath(array $data): ?string
    {
        $candidates = array_filter([
            (string) ($data['template_key'] ?? ''),
            (string) ($data['slug'] ?? ''),
            $this->slugWithoutExtension((string) ($data['slug'] ?? '')),
        ]);

        foreach (array_unique($candidates) as $candidate) {
            $normalized = $this->slugWithoutExtension($candidate);
            if ($normalized === '') {
                continue;
            }

            $path = storage_path("import/txt/{$normalized}.txt");
            if (File::isFile($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{path:string,value:string}>
     */
    private function parseHeadFields(string $text): array
    {
        $blocks = preg_split('/^\[FIELD\]\s*$/m', $text) ?: [];
        $fields = [];

        foreach ($blocks as $block) {
            if (!preg_match('/^\s*section\s*=\s*HEAD\s*$/mi', $block)) {
                continue;
            }
            if (!preg_match('/^\s*path\s*=\s*(.+?)\s*$/mi', $block, $pathMatch)) {
                continue;
            }
            if (!preg_match('/value:\s*```(?:text|json|html)?\s*([\s\S]*?)```/i', $block, $valueMatch)) {
                continue;
            }

            $fields[] = [
                'path' => trim($pathMatch[1]),
                'value' => trim($valueMatch[1]),
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private function parseVariables(string $text): array
    {
        $variables = [];
        if (preg_match_all('/^\{([A-Za-z0-9_]+)\}\s*=\s*(.*?)\s*$/m', $text, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $variables[$match[1]] = trim($match[2]);
            }
        }

        return $variables;
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function replacePlaceholders(string $value, array $data, Site $site, array $variables): string
    {
        $slug = $this->slugWithoutExtension((string) ($data['slug'] ?? 'index'));
        $filename = $slug === 'index' ? '' : "{$slug}.html";
        $canonical = 'https://' . $site->domain . ($filename !== '' ? "/{$filename}" : '/');

        $replacements = [];
        foreach ($variables as $key => $variableValue) {
            $replacements['{' . $key . '}'] = $variableValue;
        }

        $replacements = array_merge($replacements, [
            '{site}' => $site->domain,
            '{site_name}' => (string) ($data['title'] ?? $site->name),
            '{page_description}' => (string) ($data['meta_description'] ?? ($variables['page_description'] ?? '')),
            '{html_lang}' => (string) ($data['locale'] ?? $site->locale ?? 'en'),
            '{canonical_url}' => $canonical,
            '{alternate_lang}' => (string) ($data['locale'] ?? $site->locale ?? 'en'),
            '{og_locale_alternate}' => (string) ($data['locale'] ?? $site->locale ?? 'en'),
        ]);

        $previous = null;
        $current = $value;
        for ($i = 0; $i < 5 && $previous !== $current; $i++) {
            $previous = $current;
            $current = strtr($current, $replacements);
        }

        return $current;
    }

    private function mergeOgData(mixed $current, array $defaults): ?array
    {
        $current = is_array($current) ? $current : [];
        $merged = $current;

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $merged) || $this->blank($merged[$key])) {
                $merged[$key] = $value;
            } elseif ($key === 'head_meta' && is_array($merged[$key]) && is_array($value)) {
                $merged[$key] = $this->mergeHeadMetaRows($merged[$key], $value);
            }
        }

        return $merged !== [] ? $merged : null;
    }

    private function mergeHeadMetaRows(array $current, array $defaults): array
    {
        foreach ($defaults as $index => $defaultRow) {
            if (!is_array($defaultRow)) {
                continue;
            }

            $currentRow = isset($current[$index]) && is_array($current[$index]) ? $current[$index] : [];
            foreach (['name', 'property', 'http_equiv', 'content'] as $key) {
                if ((!array_key_exists($key, $currentRow) || $this->blank($currentRow[$key])) && array_key_exists($key, $defaultRow)) {
                    $currentRow[$key] = $defaultRow[$key];
                }
            }
            $current[$index] = $currentRow;
        }

        ksort($current);
        return $current;
    }

    private function normalizeHeadMetaRows(array $rows, array $data, Site $site): array
    {
        $standard = [
            0 => ['name' => 'robots'],
            1 => ['name' => 'og:type', 'property' => 'og:type', 'content' => 'website'],
            2 => ['property' => 'og:locale'],
            3 => ['name' => 'og:title', 'property' => 'og:title'],
            4 => ['name' => 'og:description', 'property' => 'og:description'],
            5 => ['property' => 'article:published_time'],
            6 => ['property' => 'article:modified_time'],
            7 => ['property' => 'article:author'],
            8 => ['name' => 'twitter:card', 'content' => 'summary_large_image'],
        ];

        foreach ($standard as $index => $attributes) {
            $row = isset($rows[$index]) && is_array($rows[$index]) ? $rows[$index] : [];
            foreach ($attributes as $key => $value) {
                if (!array_key_exists($key, $row) || $this->blank($row[$key])) {
                    $row[$key] = $this->replacePlaceholders((string) $value, $data, $site, []);
                }
            }
            $rows[$index] = $row;
        }

        ksort($rows);
        return $rows;
    }

    private function slugWithoutExtension(string $slug): string
    {
        return trim(preg_replace('/\.html$/i', '', trim($slug, '/')) ?? '', '/');
    }

    private function blank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}

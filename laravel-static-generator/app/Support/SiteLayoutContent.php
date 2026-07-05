<?php

namespace App\Support;

use App\Models\Site;
use App\Models\Page;
use App\Models\SiteSharedBlock;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class SiteLayoutContent
{
    public function resolveMenuInner(?Site $site, ?string $locale = null): string
    {
        $block = $this->sharedBlockForLocale($site, $locale);
        $stored = is_string($block?->menu_html) ? $block->menu_html : (is_string($site?->menu_html) ? $site->menu_html : '');

        $menuHtml = $this->normalizeMenuInner($stored !== '' ? $stored : $this->defaultMenuInner());

        return $stored === '' ? $this->filterDefaultMenuLinks($menuHtml, $site) : $menuHtml;
    }

    public function resolveFooterInner(?Site $site, ?string $locale = null): string
    {
        $block = $this->sharedBlockForLocale($site, $locale);
        $stored = is_string($block?->footer_html) ? $block->footer_html : (is_string($site?->footer_html) ? $site->footer_html : '');
        $footerHtml = $this->normalizeFooterInner($stored !== '' ? $stored : $this->defaultFooterInner());
        $domain = trim((string) ($site?->domain ?? 'site.com'));

        if ($domain !== '') {
            $footerHtml = str_replace('site.com', $domain, $footerHtml);
        }

        return preg_replace('/© Copyright 2024-\d{4}/', '© Copyright 2024-' . date('Y'), $footerHtml) ?? $footerHtml;
    }

    public function resolveMobileMenuHtml(?Site $site, ?string $locale = null): string
    {
        $block = $this->sharedBlockForLocale($site, $locale);
        $stored = is_string($block?->mobile_menu_html) ? $block->mobile_menu_html : (is_string($site?->mobile_menu_html) ? $site->mobile_menu_html : '');

        $mobileMenuHtml = $this->normalizeMobileMenuHtml($stored !== '' ? $stored : $this->defaultMobileMenuHtml($site));

        return $stored === '' ? $this->filterDefaultMenuLinks($mobileMenuHtml, $site) : $mobileMenuHtml;
    }

    private function sharedBlockForLocale(?Site $site, ?string $locale): ?SiteSharedBlock
    {
        $normalizedLocale = strtolower(substr(str_replace('_', '-', trim((string) $locale)), 0, 2));
        if (!$site || $normalizedLocale === '') {
            return null;
        }

        return SiteSharedBlock::where('site_id', $site->id)
            ->where('locale', $normalizedLocale)
            ->first();
    }

    public function normalizeMenuInner(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return trim($this->defaultMenuInner());
        }

        $headerInner = $this->extractFirstNodeOuterHtmlByClass($html, 'header__inner');
        if ($headerInner !== null) {
            return trim($headerInner);
        }

        $headerContents = $this->extractFirstElementInnerHtmlByTag($html, 'header');
        if ($headerContents !== null) {
            return trim($headerContents);
        }

        return $html;
    }

    public function normalizeFooterInner(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return trim($this->defaultFooterInner());
        }

        $footerContents = $this->extractFirstElementInnerHtmlByTag($html, 'footer');
        if ($footerContents !== null) {
            return trim($footerContents);
        }

        return $html;
    }

    public function normalizeMobileMenuHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $mobileMenu = $this->extractFirstNodeOuterHtmlByClass($html, 'mobile-menu');
        if ($mobileMenu !== null) {
            return trim($mobileMenu);
        }

        return $html;
    }

    public function sanitizeSectionHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $document = $this->createFragmentDocument($html);
        if (!$document) {
            return $this->sanitizeSectionHtmlFallback($html);
        }

        $xpath = new DOMXPath($document);

        foreach ($this->queryNodes($xpath, '//header[.//*[contains(concat(" ", normalize-space(@class), " "), " header__inner ")]]') as $node) {
            $this->removeNode($node);
        }

        foreach ($this->queryNodes($xpath, '//*[contains(concat(" ", normalize-space(@class), " "), " header__inner ")]') as $node) {
            $this->removeNode($node);
        }

        foreach ($this->queryNodes($xpath, '//*[contains(concat(" ", normalize-space(@class), " "), " mobile-menu ")]') as $node) {
            $this->removeNode($node);
        }

        return $this->renderBodyInnerHtml($document);
    }

    public function shouldRenderHeaderInsideFirstHero(?Page $page): bool
    {
        if (!$page) {
            return false;
        }

        foreach ($page->sections as $section) {
            $content = is_array($section->content)
                ? $section->content
                : (is_string($section->content) ? json_decode($section->content, true) : []);
            $content = is_array($content) ? $content : [];
            $module = strtolower(trim((string) ($section->module ?? $content['module'] ?? $content['module_key'] ?? '')));

            if (in_array($module, ['header', 'footer', 'menu', 'mobile-menu'], true)) {
                continue;
            }

            $rawHtml = isset($content['raw_html']) && is_string($content['raw_html']) && trim($content['raw_html']) !== ''
                ? trim($content['raw_html'])
                : (is_string($section->raw_html) ? trim($section->raw_html) : '');

            return $this->isHeroWithSharedHeaderSource($rawHtml);
        }

        return false;
    }

    public function injectHeaderIntoFirstHero(string $html, string $menuInner): string
    {
        $html = trim($html);
        $menuInner = trim($menuInner);

        if ($html === '' || $menuInner === '') {
            return $html;
        }

        if (!preg_match('/^\s*<section\b(?=[^>]*class=(["\'])[^"\']*\bhero\b[^"\']*\1)[^>]*>/i', $html)) {
            return $html;
        }

        if (str_contains($html, 'header__inner')) {
            return $html;
        }

        $headerHtml = '<header class="header" id="header">' . PHP_EOL . $menuInner . PHP_EOL . '</header>';

        return preg_replace(
            '/^\s*(<section\b(?=[^>]*class=(["\'])[^"\']*\bhero\b[^"\']*\2)[^>]*>)/i',
            '$1' . PHP_EOL . $headerHtml,
            $html,
            1
        ) ?? $html;
    }

    public function defaultMenuInner(): string
    {
        return $this->readDefaultViewFragment('header');
    }

    public function defaultFooterInner(): string
    {
        return $this->readDefaultViewFragment('footer');
    }

    private function sanitizeSectionHtmlFallback(string $html): string
    {
        $withoutHeader = preg_replace('/<header\b[^>]*>[\s\S]*?<div\b[^>]*class=(["\'])[^"\']*\bheader__inner\b[^"\']*\1[\s\S]*?<\/div>[\s\S]*?<\/header>/i', '', $html);
        $withoutInner = preg_replace('/<div\b[^>]*class=(["\'])[^"\']*\bheader__inner\b[^"\']*\1[\s\S]*?<\/div>/i', '', $withoutHeader ?? $html);
        $withoutMobileMenu = preg_replace('/<([a-z0-9:_-]+)\b[^>]*class=(["\'])[^"\']*\bmobile-menu\b[^"\']*\2[\s\S]*?<\/\1>/i', '', $withoutInner ?? $html);

        return trim((string) ($withoutMobileMenu ?? $withoutInner ?? $html));
    }

    private function isHeroWithSharedHeaderSource(string $html): bool
    {
        return $html !== ''
            && preg_match('/^\s*<section\b(?=[^>]*class=(["\'])[^"\']*\bhero\b[^"\']*\1)[^>]*>/i', $html) === 1
            && str_contains($html, '<header')
            && str_contains($html, 'header__inner');
    }

    private function readDefaultViewFragment(string $name): string
    {
        $path = resource_path("views/defaults/modules/{$name}.html");
        if (!is_file($path)) {
            return '';
        }

        $contents = file_get_contents($path);

        return is_string($contents) ? trim($contents) : '';
    }

    private function defaultMobileMenuHtml(?Site $site): string
    {
        $templateSet = strtolower(trim((string) ($site?->template_set ?? '')));
        $candidates = $templateSet !== ''
            ? [resource_path("views/templates/{$templateSet}/modules/_hero_mobile_menu.blade.php")]
            : [];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $contents = file_get_contents($path);
            if (is_string($contents) && trim($contents) !== '') {
                return trim($contents);
            }
        }

        return '';
    }

    private function filterDefaultMenuLinks(string $html, ?Site $site): string
    {
        if (!$site) {
            return $html;
        }

        $publishedSlugs = Page::where('site_id', $site->id)
            ->where('status', 'published')
            ->pluck('slug')
            ->map(fn ($slug) => trim((string) $slug, '/'))
            ->all();

        if ($publishedSlugs === []) {
            return $html;
        }

        $allowed = array_fill_keys($publishedSlugs, true);
        $allowed[''] = true;
        $allowed['index'] = true;

        return preg_replace_callback('/<li\b[^>]*>\s*<a\b[^>]*href=(["\'])([^"\']+)\1[\s\S]*?<\/li>/i', function (array $matches) use ($allowed) {
            if (str_contains(strtolower($matches[0]), '<ul')) {
                return $matches[0];
            }

            $href = trim((string) ($matches[2] ?? ''));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '/') || preg_match('/^[a-z]+:/i', $href)) {
                return $matches[0];
            }

            $slug = preg_replace('/\.html$/i', '', trim($href, '/')) ?? '';

            return isset($allowed[$slug]) ? $matches[0] : '';
        }, $html) ?? $html;
    }

    private function extractFirstNodeOuterHtmlByClass(string $html, string $className): ?string
    {
        $document = $this->createFragmentDocument($html);
        if (!$document) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $nodes = $this->queryNodes(
            $xpath,
            '//*[contains(concat(" ", normalize-space(@class), " "), " ' . $className . ' ")]'
        );

        $node = $nodes[0] ?? null;

        return $node instanceof DOMNode ? trim($document->saveHTML($node) ?: '') : null;
    }

    private function extractFirstElementInnerHtmlByTag(string $html, string $tag): ?string
    {
        $document = $this->createFragmentDocument($html);
        if (!$document) {
            return null;
        }

        $elements = $document->getElementsByTagName($tag);
        $element = $elements->item(0);
        if (!$element instanceof DOMElement) {
            return null;
        }

        return trim($this->innerHtml($element));
    }

    private function createFragmentDocument(string $html): ?DOMDocument
    {
        if (!class_exists(DOMDocument::class)) {
            return null;
        }

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        return $loaded ? $document : null;
    }

    /**
     * @return array<int, DOMNode>
     */
    private function queryNodes(DOMXPath $xpath, string $query): array
    {
        $nodeList = $xpath->query($query);
        if ($nodeList === false) {
            return [];
        }

        $nodes = [];
        foreach ($nodeList as $node) {
            $nodes[] = $node;
        }

        return $nodes;
    }

    private function removeNode(DOMNode $node): void
    {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }

    private function renderBodyInnerHtml(DOMDocument $document): string
    {
        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement) {
            return '';
        }

        return trim($this->innerHtml($body));
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $fragment = $node->ownerDocument?->saveHTML($child);
            if (is_string($fragment)) {
                $html .= $fragment;
            }
        }

        return $html;
    }
}

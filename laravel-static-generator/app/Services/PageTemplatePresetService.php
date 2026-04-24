<?php

namespace App\Services;

use App\Models\Page;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

class PageTemplatePresetService
{
    /**
     * @var array<string, array{label:string, source_file:string, default_slug:string}>
     */
    private const TEMPLATES = [
        'blank' => [
            'label' => 'Empty Template',
            'source_file' => '',
            'default_slug' => '',
        ],
        '1win' => [
            'label' => '1win',
            'source_file' => '1win.html',
            'default_slug' => '1win',
        ],
        'app-copy' => [
            'label' => 'App Copy',
            'source_file' => 'app.html',
            'default_slug' => 'app-copy',
        ],
        'app' => [
            'label' => 'App',
            'source_file' => 'app.html',
            'default_slug' => 'app',
        ],
        'authors' => [
            'label' => 'Authors',
            'source_file' => 'authors.html',
            'default_slug' => 'authors',
        ],
        'bonuses' => [
            'label' => 'Bonuses',
            'source_file' => 'bonuses.html',
            'default_slug' => 'bonuses',
        ],
        'comparison' => [
            'label' => 'Comparison',
            'source_file' => 'comparison.html',
            'default_slug' => 'comparison',
        ],
        'contact-us' => [
            'label' => 'Contact Us',
            'source_file' => 'contact-us.html',
            'default_slug' => 'contact-us',
        ],
        'cookie-policy' => [
            'label' => 'Cookie Policy',
            'source_file' => 'cookie-policy.html',
            'default_slug' => 'cookie-policy',
        ],
        'demo' => [
            'label' => 'Demo',
            'source_file' => 'demo.html',
            'default_slug' => 'demo',
        ],
        'index' => [
            'label' => 'Index',
            'source_file' => 'index.html',
            'default_slug' => 'index',
        ],
        'privacy-policy' => [
            'label' => 'Privacy Policy',
            'source_file' => 'privacy-policy.html',
            'default_slug' => 'privacy-policy',
        ],
        'reviews' => [
            'label' => 'Reviews',
            'source_file' => 'reviews.html',
            'default_slug' => 'reviews',
        ],
        'sitemap' => [
            'label' => 'Sitemap',
            'source_file' => 'sitemap.html',
            'default_slug' => 'sitemap',
        ],
        'terms-and-conditions' => [
            'label' => 'Terms And Conditions',
            'source_file' => 'terms-and-conditions.html',
            'default_slug' => 'terms-and-conditions',
        ],
        'tips' => [
            'label' => 'Tips',
            'source_file' => 'tips.html',
            'default_slug' => 'tips',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const MODULE_CATALOG = [
        'authors',
        'background',
        'benefits',
        'bonuses',
        'breadcrumbs',
        'button',
        'card',
        'casino',
        'characteristics',
        'comparison',
        'conclusion',
        'demo',
        'download',
        'errors',
        'faq',
        'feature',
        'feedback',
        'authors',
        'benefits',
        'bonuses',
        'casino',
        'characteristics',
        'comparison',
        'conclusion',
        'download',
        'faq',
        'feature',
        'feedback',
        'footer',
        'form',
        'game',
        'gameplay',
        'header',
        'hero',
        'hero-sitemap',
        'installation',
        'level',
        'lightbox',
        'list',
        'logo',
        'menu',
        'other-reviews',
        'payments',
        'promo',
        'pros',
        'review',
        'rtp',
        'screenshots',
        'scrollbar',
        'sitemap',
        'steps',
        'strategies',
        'symbols',
        'table',
        'text',
        'tips',
    ];

    private const MODULE_DEFAULTS = [
        'authors' => ['class' => 'authors', 'id' => 'authors'],
        'benefits' => ['class' => 'benefits', 'id' => 'benefits'],
        'bonuses' => ['class' => 'bonuses', 'id' => 'bonuses'],
        'casino' => ['class' => 'casino', 'id' => 'casino'],
        'characteristics' => ['class' => 'characteristics background--characteristics', 'id' => 'characteristics'],
        'comparison' => ['class' => 'comparison', 'id' => 'comparison'],
        'conclusion' => ['class' => 'conclusion', 'id' => 'conclusion'],
        'download' => ['class' => 'download background--characteristics', 'id' => 'download'],
        'errors' => ['class' => 'errors', 'id' => 'errors'],
        'faq' => ['class' => 'faq', 'id' => 'faq'],
        'feature' => ['class' => 'feature', 'id' => 'feature'],
        'feedback' => ['class' => 'feedback', 'id' => 'feedback'],
        'footer' => ['class' => 'footer', 'id' => 'footer'],
        'form' => ['class' => 'form background--characteristics mb50', 'id' => 'form'],
        'game' => ['class' => 'game', 'id' => 'game'],
        'gameplay' => ['class' => 'gameplay', 'id' => 'gameplay'],
        'header' => ['class' => 'header', 'id' => 'header'],
        'hero' => ['class' => 'hero', 'id' => 'hero'],
        'hero-sitemap' => ['class' => 'hero hero--has-breadcrumbs hero--simple', 'id' => 'hero'],
        'installation' => ['class' => 'installation', 'id' => 'installation'],
        'level' => ['class' => 'level', 'id' => 'level'],
        'other-reviews' => ['class' => 'other-reviews', 'id' => 'other-reviews'],
        'promo' => ['class' => 'promo', 'id' => 'promo'],
        'pros' => ['class' => 'pros', 'id' => 'pros'],
        'review' => ['class' => 'review', 'id' => 'review'],
        'rtp' => ['class' => 'rtp', 'id' => 'rtp'],
        'screenshots' => ['class' => 'screenshots', 'id' => 'screenshots'],
        'sitemap' => ['class' => 'sitemap', 'id' => 'sitemap'],
        'steps' => ['class' => 'steps background--characteristics', 'id' => 'steps'],
        'strategies' => ['class' => 'strategies', 'id' => 'strategies'],
        'symbols' => ['class' => 'symbols', 'id' => 'symbols'],
    ];

    /**
     * @return array<int, array{key:string, label:string, source_file:string, default_slug:string}>
     */
    public function listForUi(): array
    {
        $items = [];

        foreach (self::TEMPLATES as $key => $template) {
            $items[] = [
                'key' => $key,
                'label' => $template['label'],
                'source_file' => $template['source_file'],
                'default_slug' => $template['default_slug'],
            ];
        }

        return $items;
    }

    public function normalizeKey(?string $templateKey): string
    {
        $normalized = Str::of((string) $templateKey)
            ->lower()
            ->trim()
            ->replace(' ', '-')
            ->replaceMatches('/[^a-z0-9-]+/', '')
            ->value();

        return array_key_exists($normalized, self::TEMPLATES) ? $normalized : 'blank';
    }

    /**
     * @return array<int, array{key:string, label:string}>
     */
    public function listModulesForUi(): array
    {
        $items = [];

        foreach (self::MODULE_CATALOG as $key) {
            $items[] = [
                'key' => $key,
                'label' => Str::of($key)->replace('-', ' ')->title()->value(),
            ];
        }

        return $items;
    }

    public function getModuleDefaults(): array
    {
        $defaults = [];
        foreach (self::MODULE_DEFAULTS as $key => $config) {
            $rawHtml = '';
            $path = resource_path("views/defaults/modules/{$key}.html");
            if (file_exists($path)) {
                $rawHtml = file_get_contents($path);
            }

            $defaults[$key] = array_merge([
                'module' => $key,
                'module_key' => $key,
                'heading' => Str::of($key)->replace('-', ' ')->title()->value(),
                'raw_html' => $rawHtml,
            ], $config);
        }
        return $defaults;
    }

    public function getDefaultSlug(string $templateKey): ?string
    {
        $key = $this->normalizeKey($templateKey);
        $slug = self::TEMPLATES[$key]['default_slug'] ?? '';

        return $slug !== '' ? $slug : null;
    }

    /**
     * @return array<int, array{type:string, content:array<string, mixed>}>
     */
    public function buildPresetSections(string $templateKey, Page $page): array
    {
        $key = $this->normalizeKey($templateKey);

        if ($key === 'blank') {
            return [];
        }

        $sections = $this->extractSectionsFromEtalon($key);
        if (!empty($sections)) {
            return $sections;
        }

        $heading = $page->title !== '' ? $page->title : Str::title(str_replace('-', ' ', $page->slug));
        $keySuffix = str_replace('-', '_', $key);

        return [
            [
                'type' => 'hero',
                'content' => [
                    'module' => 'hero',
                    'id' => "hero_{$keySuffix}",
                    'class' => "hero hero-{$key}",
                    'heading' => $heading,
                    'subheading' => "Template preset: {$key}",
                    'image' => '/assets/images/placeholder.webp',
                    'image_alt' => $heading,
                ],
            ],
            [
                'type' => 'text',
                'content' => [
                    'module' => 'text',
                    'id' => "content_{$keySuffix}",
                    'class' => "text content-{$key}",
                    'heading' => "{$heading} Content",
                    'content' => '<p>Edit this section in page builder and replace with final module content.</p>',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{type:string, content:array<string, mixed>}>
     */
    private function extractSectionsFromEtalon(string $templateKey): array
    {
        $sourceFile = self::TEMPLATES[$templateKey]['source_file'] ?? '';
        if ($sourceFile === '') {
            return [];
        }

        $fullPath = storage_path("generated/site1/{$sourceFile}");
        if (!is_file($fullPath)) {
            return [];
        }

        $html = file_get_contents($fullPath);
        if ($html === false || trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//section | //footer');

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $sections = [];

        /** @var DOMElement $node */
        foreach ($nodes as $node) {
            $tagName = strtolower((string) $node->tagName);
            $classAttr = trim((string) $node->getAttribute('class'));
            $idAttr = trim((string) $node->getAttribute('id'));
            
            if ($tagName === 'footer') {
                $moduleKey = 'footer';
            } else {
                $moduleKey = $this->resolveModuleKey($classAttr, $idAttr, $templateKey);
            }
            
            $heading = $this->extractHeading($node);
            $rawHtml = $this->innerHtml($node);

            $content = [
                'module' => $moduleKey,
                'module_key' => $moduleKey,
                'id' => $idAttr !== '' ? $idAttr : null,
                'class' => $classAttr !== '' ? $classAttr : $moduleKey,
                'heading' => $heading,
                'raw_html' => $rawHtml,
                'source_file' => $sourceFile,
            ];

            $sections[] = [
                'type' => 'module',
                'content' => $content,
            ];
        }

        return $sections;
    }

    private function resolveModuleKey(string $classAttr, string $idAttr, ?string $templateKey = null): string
    {
        $tokens = preg_split('/\s+/', $classAttr) ?: [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $base = preg_replace('/(--|__).*$/', '', $token);
            
            // Special mapping for hero-sitemap
            if ($base === 'hero' && $templateKey === 'sitemap') {
                return 'hero-sitemap';
            }

            $normalized = $this->normalizeModuleKey((string) $base);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        $fromId = $this->normalizeModuleKey($idAttr);
        if ($fromId !== '') {
            return $fromId;
        }

        return 'module';
    }

    private function normalizeModuleKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->trim()
            ->replace(' ', '-')
            ->replaceMatches('/[^a-z0-9_-]+/', '-')
            ->trim('-')
            ->value();
    }

    private function extractHeading(DOMElement $section): ?string
    {
        foreach (['h1', 'h2', 'h3'] as $tagName) {
            $nodes = $section->getElementsByTagName($tagName);
            if ($nodes->length === 0) {
                continue;
            }

            $text = trim((string) $nodes->item(0)?->textContent);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $fragment = $element->ownerDocument?->saveHTML($child);
            if ($fragment !== false) {
                $html .= $fragment;
            }
        }

        return trim($html);
    }
}

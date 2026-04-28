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
        'authors-cookies',
        'authors-cookie-policy',
        'authors-privacy-policy',
        'background',
        'benefits',
        'benefits-demo',
        'bonuses',
        'breadcrumbs',
        'button',
        'card',
        'casino',
        'casino-1win',
        'casino-bonuses',
        'casino-bonuses-2',
        'casino-comparison',
        'casino-reviews',
        'casino-tips',
        'casino-tips-2',
        'casino-demo',
        'casino-demo-2',
        'casino-review-app',
        'casino-where-to-play-app',
        'characteristics',
        'characteristics-1win',
        'characteristics-comparison',
        'comparison',
        'conclusion',
        'demo',
        'download',
        'download-app',
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
        'form-reviews',
        'game',
        'gameplay',
        'header',
        'hero-main',
        'hero-authors',
        'hero-breadcrumbs',
        'hero-demo',
        'hero',
        'hero-sitemap',
        'installation',
        'level',
        'level-map',
        'lightbox',
        'list',
        'logo',
        'menu',
        'other-reviews',
        'payments',
        'promo',
        'pros',
        'review',
        'review-1win',
        'review-demo-1win',
        'review-support-1win',
        'review-comparison',
        'rtp',
        'rtp-comparison',
        'screenshots',
        'scrollbar',
        'sitemap',
        'steps',
        'steps-1win',
        'steps-comparison',
        'steps-bonuses',
        'steps-demo',
        'steps-tips',
        'strategies',
        'symbols',
        'symbols-1win',
        'symbols-comparison',
        'table',
        'text',
        'text-reviews',
        'tips',
    ];

    private const MODULE_DEFAULTS = [
        'authors' => ['class' => 'authors', 'id' => 'authors'],
        'authors-cookies' => [
            'class' => 'authors',
            'id' => 'cookies',
            'heading' => 'Acceptance of Terms',
            'description' => 'Utilising this slot game platform signifies your acceptance of the terms and conditions outlined here, which include the reading and understanding of all components. Should these terms be unsatisfactory in any form, the services provided should be ceased immediately.',
        ],
        'authors-cookie-policy' => [
            'class' => 'authors',
            'id' => 'cookies',
            'heading' => 'What Are Cookies',
            'description' => 'The following sections detail the specific use of cookies for the site dedicated to all information and services regarding the Ganesha Fortune slot game.',
        ],
        'authors-privacy-policy' => [
            'class' => 'authors',
            'id' => 'cookies',
            'heading' => 'Offers',
            'description' => 'The site offers the necessary details that pertain to online slot gaming, given that such information can only be accessed if you agree with this privacy policy. This document highlights the extensive measures that we have put in place to ensure the protection of user data, promote responsible gambling behaviour, and comply with the local laws of India where our site is hosted. The online gambling industry is bound by the strict rules and regulations set in place, and we uphold these for the good of every user. You have to accept our terms in full in order to access the site. Use of our site without any restrictions will be regarded as your acceptance to the terms mentioned in this privacy statement.',
        ],
        'benefits' => ['class' => 'benefits', 'id' => 'benefits'],
        'benefits-demo' => ['class' => 'benefits benefits--demo', 'id' => 'benefits'],
        'bonuses' => ['class' => 'bonuses', 'id' => 'bonuses'],
        'casino' => ['class' => 'casino', 'id' => 'casino'],
        'casino-1win' => [
            'class' => 'casino',
            'id' => 'bonuses',
            'heading' => 'Bonuses andpromo codes',
            'description' => 'By making use of exclusive bonus codes, you may increase your initial betting budget for the Aviator game. Because of that, you will be able to take more risks and that might help you walk away with larger sums in the bank.',
        ],
        'casino-bonuses' => [
            'class' => 'casino',
            'id' => 'casino',
            'heading' => 'The Aviator Game Strategy',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'casino-bonuses-2' => [
            'class' => 'casino casino--tips',
            'id' => 'casino-2',
            'heading' => 'Casino with demo mode',
            'description' => 'Before diving into gambling for INR, be sure to test out the demo Aviator and see what works best for you. Here are the steps to begin created by our team.',
        ],
        'casino-comparison' => [
            'class' => 'casino',
            'id' => 'casino',
            'heading' => 'Where to play The Aviator Game?',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'casino-reviews' => [
            'class' => 'casino',
            'id' => 'casino',
            'heading' => 'Where to play The Aviator Game?',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'casino-tips' => [
            'class' => 'casino',
            'id' => 'casino',
            'heading' => 'The Aviator Game Strategy',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'casino-tips-2' => [
            'class' => 'casino casino--tips',
            'id' => 'casino-2',
            'heading' => 'Casino with demo mode',
            'description' => 'Before diving into gambling for INR, be sure to test out the demo Aviator and see what works best for you. Here are the steps to begin created by our team.',
        ],
        'casino-demo' => [
            'class' => 'casino casino--demo',
            'id' => 'casino',
            'heading' => 'The Aviator Game Review',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'casino-demo-2' => [
            'class' => 'casino casino__demo',
            'id' => 'casino-2',
            'heading' => 'Casino with demo mode',
            'description' => 'Before diving into gambling for INR, be sure to test out the demo Aviator and see what works best for you. Here are the steps to begin created by our team.',
        ],
        'casino-review-app' => [
            'class' => 'casino',
            'id' => 'casino',
            'heading' => 'The Aviator Game Review',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'casino-where-to-play-app' => [
            'class' => 'casino casino--errors',
            'id' => 'where-to-play',
            'heading' => 'Where to play The Aviator Game?',
            'description' => 'In this article, we will explore the key features of the Aviator casino game, discuss its gameplay mechanics, user interface, and overall experience.',
        ],
        'characteristics' => ['class' => 'characteristics background--characteristics', 'id' => 'characteristics'],
        'characteristics-1win' => ['class' => 'characteristics background--characteristics', 'id' => 'characteristics'],
        'characteristics-comparison' => ['class' => 'characteristics background--characteristics', 'id' => 'characteristics'],
        'comparison' => ['class' => 'comparison', 'id' => 'comparison'],
        'conclusion' => ['class' => 'conclusion', 'id' => 'conclusion'],
        'download' => ['class' => 'download background--characteristics', 'id' => 'download'],
        'download-app' => ['class' => 'download background--characteristics', 'id' => 'download'],
        'errors' => ['class' => 'errors', 'id' => 'errors'],
        'faq' => ['class' => 'faq', 'id' => 'faq'],
        'feature' => ['class' => 'feature', 'id' => 'feature'],
        'feedback' => ['class' => 'feedback', 'id' => 'feedback'],
        'footer' => ['class' => 'footer', 'id' => 'footer'],
        'form' => ['class' => 'form background--characteristics mb50', 'id' => 'form'],
        'form-reviews' => ['class' => 'form background--characteristics', 'id' => 'form'],
        'game' => ['class' => 'game', 'id' => 'game'],
        'gameplay' => ['class' => 'gameplay', 'id' => 'gameplay'],
        'header' => ['class' => 'header', 'id' => 'header'],
        'hero-main' => [
            'class' => 'hero',
            'id' => 'hero',
            'heroTitle' => 'Aviator Game — Play for Real Money',
            'description' => 'Play Aviator Game — a thrilling legal online game with a maximum win of 1000x your bet.',
            'ctaText' => 'Play now!',
            'ctaHref' => '#play-now',
            'imageSrc' => '/assets/images/hero/aviator.webp',
            'imageAlt' => 'Aviator',
        ],
        'hero-authors' => [
            'class' => 'hero hero--authors hero--has-breadcrumbs',
            'id' => 'hero',
            'heroTitle' => 'Rahul Kumar Gupta',
            'pageTitle' => "Author's",
            'description' => 'Kishor Singha is a renowned name in the world of online gambling, particularly renowned for his expertise in the Aviator crash game. Born in the vibrant city of Delhi, India, Kishor\'s passion for gaming.',
        ],
        'hero-breadcrumbs' => [
            'class' => 'hero hero--has-breadcrumbs',
            'id' => 'hero',
            'heroTitle' => 'Download Aviator Game App',
            'pageTitle' => 'Download App',
            'description' => 'Play Aviator Game — a thrilling legal online game with a maximum win of 1000x your bet.',
            'ctaText' => 'Play now!',
            'ctaHref' => '#play-now',
            'imageSrc' => '/assets/images/hero/aviator.webp',
            'imageAlt' => 'Aviator',
        ],
        'hero-demo' => [
            'class' => 'hero hero--demo hero--has-breadcrumbs',
            'id' => 'hero',
            'heroTitle' => 'Aviator Demo Play the Game Online for Free in 2025',
            'pageTitle' => 'Demo Play',
            'description' => 'Aviator demo is supplied by most casinos for Indian players who choose not to risk their money.',
            'imageSrc' => '/assets/images/demo.webp',
            'imageAlt' => 'Aviator demo gameplay',
        ],
        'hero' => [
            'class' => 'hero hero--simple hero--has-breadcrumbs',
            'id' => 'hero',
            'heroTitle' => 'Contact Us',
            'pageTitle' => 'Contact Us',
        ],
        'hero-sitemap' => ['class' => 'hero hero--has-breadcrumbs hero--simple', 'id' => 'hero'],
        'installation' => ['class' => 'installation', 'id' => 'installation'],
        'level' => ['class' => 'level', 'id' => 'level'],
        'level-map' => ['class' => 'level', 'id' => 'level'],
        'other-reviews' => ['class' => 'other-reviews', 'id' => 'other-reviews'],
        'promo' => ['class' => 'promo', 'id' => 'promo'],
        'pros' => ['class' => 'pros', 'id' => 'pros'],
        'review' => ['class' => 'review', 'id' => 'review'],
        'review-1win' => ['class' => 'review', 'id' => 'mobile-app'],
        'review-demo-1win' => ['class' => 'review review--media-last-tablet', 'id' => 'demo'],
        'review-support-1win' => ['class' => 'review', 'id' => 'support'],
        'review-comparison' => ['class' => 'review', 'id' => 'review'],
        'rtp' => ['class' => 'rtp', 'id' => 'rtp'],
        'rtp-comparison' => ['class' => 'rtp', 'id' => 'rtp'],
        'screenshots' => ['class' => 'screenshots', 'id' => 'screenshots'],
        'sitemap' => ['class' => 'sitemap', 'id' => 'sitemap'],
        'steps' => ['class' => 'steps background--characteristics', 'id' => 'steps'],
        'steps-1win' => ['class' => 'steps steps--1win background--characteristics', 'id' => 'steps'],
        'steps-comparison' => ['class' => 'steps background--characteristics', 'id' => 'steps'],
        'steps-bonuses' => ['class' => 'steps steps--demo', 'id' => 'steps'],
        'steps-demo' => ['class' => 'steps steps--demo', 'id' => 'steps'],
        'steps-tips' => ['class' => 'steps steps--demo', 'id' => 'steps'],
        'strategies' => ['class' => 'strategies', 'id' => 'strategies'],
        'symbols' => ['class' => 'symbols', 'id' => 'symbols'],
        'symbols-1win' => ['class' => 'symbols symbols-mt0', 'id' => 'details'],
        'symbols-comparison' => ['class' => 'symbols', 'id' => 'symbols'],
        'text-reviews' => ['class' => 'casino', 'id' => 'text'],
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
            if ($key !== 'sitemap' && file_exists($path)) {
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
        $nodes = $xpath->query('//section | //footer | //div[@id="text"]');

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

            if (
                $base === 'authors'
                && $templateKey === 'terms-and-conditions'
                && $idAttr === 'cookies'
            ) {
                return 'authors-cookies';
            }

            if (
                $base === 'authors'
                && $templateKey === 'cookie-policy'
                && $idAttr === 'cookies'
            ) {
                return 'authors-cookie-policy';
            }

            if (
                $base === 'authors'
                && $templateKey === 'privacy-policy'
                && $idAttr === 'cookies'
            ) {
                return 'authors-privacy-policy';
            }

            if (
                $base === 'casino'
                && $templateKey === '1win'
                && $idAttr === 'bonuses'
            ) {
                return 'casino-1win';
            }

            if (
                $base === 'casino'
                && $templateKey === 'bonuses'
                && $idAttr === 'casino'
            ) {
                return 'casino-bonuses';
            }

            if (
                $base === 'casino'
                && $templateKey === 'bonuses'
                && $idAttr === 'casino-2'
            ) {
                return 'casino-bonuses-2';
            }

            if (
                $base === 'casino'
                && $templateKey === 'comparison'
                && $idAttr === 'casino'
            ) {
                return 'casino-comparison';
            }

            if (
                $base === 'casino'
                && $templateKey === 'reviews'
                && $idAttr === 'casino'
            ) {
                return 'casino-reviews';
            }

            if (
                $base === 'casino'
                && $templateKey === 'reviews'
                && $idAttr === 'text'
            ) {
                return 'text-reviews';
            }

            if (
                $base === 'casino'
                && $templateKey === 'demo'
                && $idAttr === 'casino'
            ) {
                return 'casino-demo';
            }

            if (
                $base === 'casino'
                && $templateKey === 'tips'
                && $idAttr === 'casino'
            ) {
                return 'casino-tips';
            }

            if (
                $base === 'casino'
                && $templateKey === 'tips'
                && $idAttr === 'casino-2'
            ) {
                return 'casino-tips-2';
            }

            if (
                $base === 'casino'
                && $templateKey === 'demo'
                && $idAttr === 'casino-2'
            ) {
                return 'casino-demo-2';
            }

            if (
                $base === 'casino'
                && in_array((string) $templateKey, ['app', 'app-copy'], true)
                && $idAttr === 'casino'
            ) {
                return 'casino-review-app';
            }

            if (
                $base === 'casino'
                && in_array((string) $templateKey, ['app', 'app-copy'], true)
                && $idAttr === 'where-to-play'
            ) {
                return 'casino-where-to-play-app';
            }

            if (
                $base === 'download'
                && in_array((string) $templateKey, ['app', 'app-copy'], true)
                && $idAttr === 'download'
            ) {
                return 'download-app';
            }

            if (
                $base === 'form'
                && $templateKey === 'reviews'
                && $idAttr === 'form'
            ) {
                return 'form-reviews';
            }

            if (
                $base === 'level'
                && $templateKey === 'contact-us'
                && $idAttr === 'level'
            ) {
                return 'level-map';
            }

            if (
                $base === 'steps'
                && $templateKey === 'demo'
                && $idAttr === 'steps'
            ) {
                return 'steps-demo';
            }

            if (
                $base === 'steps'
                && $templateKey === 'bonuses'
                && $idAttr === 'steps'
            ) {
                return 'steps-bonuses';
            }

            if (
                $base === 'steps'
                && $templateKey === 'tips'
                && $idAttr === 'steps'
            ) {
                return 'steps-tips';
            }

            if (
                $base === 'benefits'
                && $templateKey === 'demo'
                && $idAttr === 'benefits'
            ) {
                return 'benefits-demo';
            }

            if (
                $base === 'symbols'
                && $templateKey === '1win'
                && $idAttr === 'details'
            ) {
                return 'symbols-1win';
            }

            if (
                $base === 'steps'
                && $templateKey === '1win'
                && $idAttr === 'steps'
            ) {
                return 'steps-1win';
            }

            if (
                $base === 'review'
                && $templateKey === '1win'
                && $idAttr === 'mobile-app'
            ) {
                return 'review-1win';
            }

            if (
                $base === 'review'
                && $templateKey === '1win'
                && $idAttr === 'demo'
            ) {
                return 'review-demo-1win';
            }

            if (
                $base === 'review'
                && $templateKey === '1win'
                && $idAttr === 'support'
            ) {
                return 'review-support-1win';
            }

            if (
                $base === 'characteristics'
                && $templateKey === '1win'
                && $idAttr === 'characteristics'
            ) {
                return 'characteristics-1win';
            }

            if (
                $base === 'characteristics'
                && $templateKey === 'comparison'
                && $idAttr === 'characteristics'
            ) {
                return 'characteristics-comparison';
            }

            if (
                $base === 'review'
                && $templateKey === 'comparison'
                && $idAttr === 'review'
            ) {
                return 'review-comparison';
            }

            if (
                $base === 'symbols'
                && $templateKey === 'comparison'
                && $idAttr === 'symbols'
            ) {
                return 'symbols-comparison';
            }

            if (
                $base === 'rtp'
                && $templateKey === 'comparison'
                && $idAttr === 'rtp'
            ) {
                return 'rtp-comparison';
            }

            if (
                $base === 'steps'
                && $templateKey === 'comparison'
                && $idAttr === 'steps'
            ) {
                return 'steps-comparison';
            }
            
            if ($base === 'hero') {
                if ($templateKey === 'index') {
                    return 'hero-main';
                }

                if ($templateKey === 'demo') {
                    return 'hero-demo';
                }

                if ($templateKey === 'authors') {
                    return 'hero-authors';
                }

                if (in_array($templateKey, ['1win', 'app', 'app-copy', 'bonuses', 'comparison', 'reviews', 'tips'], true)) {
                    return 'hero-breadcrumbs';
                }

                if (in_array($templateKey, ['contact-us', 'sitemap'], true)) {
                    return 'hero';
                }
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

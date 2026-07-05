<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Section;
use App\Models\Site;
use App\Models\SiteSharedBlock;
use App\Support\SiteLayoutContent;
use Illuminate\Support\Facades\DB;

class LanguageService
{
    /**
     * @var array<string, array{name:string, hreflang:string, flag:string}>
     */
    private array $languages = [
        'en' => [
            'name' => 'English',
            'hreflang' => 'en-US',
            'flag' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAIAAAD5gJpuAAAASklEQVR42mM4IaD3//9/RsVMOAJygYK4xBkwpfGLMCALQVSgaUCWAiIGrC7BZMCVgZyECeDqMAEDRI54RLoNJPuB5FAiOR5IjWkAJLMivBWX4NUAAAAASUVORK5CYII=',
        ],
        'es' => [
            'name' => 'Español',
            'hreflang' => 'es-ES',
            'flag' => 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%2716%27%20height%3D%2711%27%20fill%3D%27%23AA151B%27%2F%3E%3Crect%20y%3D%272.75%27%20width%3D%2716%27%20height%3D%275.5%27%20fill%3D%27%23F1BF00%27%2F%3E%3C%2Fsvg%3E',
        ],
        'de' => [
            'name' => 'Deutsch',
            'hreflang' => 'de-DE',
            'flag' => 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%2716%27%20height%3D%273.666%27%20fill%3D%27%23000%27%2F%3E%3Crect%20y%3D%273.666%27%20width%3D%2716%27%20height%3D%273.667%27%20fill%3D%27%23DD0000%27%2F%3E%3Crect%20y%3D%277.333%27%20width%3D%2716%27%20height%3D%273.667%27%20fill%3D%27%23FFCE00%27%2F%3E%3C%2Fsvg%3E',
        ],
        'fr' => [
            'name' => 'Français',
            'hreflang' => 'fr-FR',
            'flag' => 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%275.333%27%20height%3D%2711%27%20fill%3D%27%23002395%27%2F%3E%3Crect%20x%3D%275.333%27%20width%3D%275.334%27%20height%3D%2711%27%20fill%3D%27%23fff%27%2F%3E%3Crect%20x%3D%2710.667%27%20width%3D%275.333%27%20height%3D%2711%27%20fill%3D%27%23ED2939%27%2F%3E%3C%2Fsvg%3E',
        ],
        'it' => [
            'name' => 'Italiano',
            'hreflang' => 'it-IT',
            'flag' => 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%275.333%27%20height%3D%2711%27%20fill%3D%27%23009246%27%2F%3E%3Crect%20x%3D%275.333%27%20width%3D%275.334%27%20height%3D%2711%27%20fill%3D%27%23fff%27%2F%3E%3Crect%20x%3D%2710.667%27%20width%3D%275.333%27%20height%3D%2711%27%20fill%3D%27%23CE2B37%27%2F%3E%3C%2Fsvg%3E',
        ],
        'pt' => [
            'name' => 'Português',
            'hreflang' => 'pt-PT',
            'flag' => 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%276.4%27%20height%3D%2711%27%20fill%3D%27%23006000%27%2F%3E%3Crect%20x%3D%276.4%27%20width%3D%279.6%27%20height%3D%2711%27%20fill%3D%27%23FF0000%27%2F%3E%3Ccircle%20cx%3D%276.4%27%20cy%3D%275.5%27%20r%3D%272%27%20fill%3D%27%23FFD700%27%2F%3E%3C%2Fsvg%3E',
        ],
    ];

    /**
     * @var array<string, array{en:string,ru:string}>
     */
    private array $languageCatalog = [
        'en' => ['en' => 'English', 'ru' => 'Английский'],
        'es' => ['en' => 'Spanish', 'ru' => 'Испанский'],
        'de' => ['en' => 'German', 'ru' => 'Немецкий'],
        'fr' => ['en' => 'French', 'ru' => 'Французский'],
        'it' => ['en' => 'Italian', 'ru' => 'Итальянский'],
        'pt' => ['en' => 'Portuguese', 'ru' => 'Португальский'],
        'ru' => ['en' => 'Russian', 'ru' => 'Русский'],
        'ro' => ['en' => 'Romanian', 'ru' => 'Румынский'],
        'uk' => ['en' => 'Ukrainian', 'ru' => 'Украинский'],
        'be' => ['en' => 'Belarusian', 'ru' => 'Белорусский'],
        'pl' => ['en' => 'Polish', 'ru' => 'Польский'],
        'cs' => ['en' => 'Czech', 'ru' => 'Чешский'],
        'sk' => ['en' => 'Slovak', 'ru' => 'Словацкий'],
        'nl' => ['en' => 'Dutch', 'ru' => 'Нидерландский'],
        'sv' => ['en' => 'Swedish', 'ru' => 'Шведский'],
        'no' => ['en' => 'Norwegian', 'ru' => 'Норвежский'],
        'da' => ['en' => 'Danish', 'ru' => 'Датский'],
        'fi' => ['en' => 'Finnish', 'ru' => 'Финский'],
        'tr' => ['en' => 'Turkish', 'ru' => 'Турецкий'],
        'el' => ['en' => 'Greek', 'ru' => 'Греческий'],
        'ar' => ['en' => 'Arabic', 'ru' => 'Арабский'],
        'he' => ['en' => 'Hebrew', 'ru' => 'Иврит'],
        'zh' => ['en' => 'Chinese', 'ru' => 'Китайский'],
        'ja' => ['en' => 'Japanese', 'ru' => 'Японский'],
        'ko' => ['en' => 'Korean', 'ru' => 'Корейский'],
        'hi' => ['en' => 'Hindi', 'ru' => 'Хинди'],
        'id' => ['en' => 'Indonesian', 'ru' => 'Индонезийский'],
        'vi' => ['en' => 'Vietnamese', 'ru' => 'Вьетнамский'],
        'th' => ['en' => 'Thai', 'ru' => 'Тайский'],
    ];

    public function __construct(private SiteLayoutContent $layoutContent)
    {
    }

    public function normalizeLocale(?string $locale): string
    {
        $value = strtolower(trim((string) $locale));
        $value = str_replace('_', '-', $value);

        if (preg_match('/^[a-z]{2}/', $value, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    /**
     * @param  array<int, mixed>  $candidateLocales
     * @return array<int, string>
     */
    public function buildLocaleSet(string $defaultLocale, array $candidateLocales): array
    {
        $default = $this->normalizeLocale($defaultLocale) ?: 'en';
        $locales = [$default];

        foreach ($candidateLocales as $locale) {
            $normalized = $this->normalizeLocale(is_scalar($locale) ? (string) $locale : '');
            if ($normalized === '' || !$this->isKnownIsoLanguage($normalized)) {
                continue;
            }

            $locales[] = $normalized;
        }

        return array_values(array_unique($locales));
    }

    public function isKnownIsoLanguage(string $locale): bool
    {
        return preg_match('/^[a-z]{2}$/', $locale) === 1;
    }

    /**
     * @return array<int, array{code:string,name_en:string,name_ru:string}>
     */
    public function languageOptions(): array
    {
        $codes = array_values(array_unique(array_merge(array_keys($this->languageCatalog), array_keys($this->languages))));
        sort($codes);

        return array_map(function (string $code): array {
            $meta = $this->languageCatalog[$code] ?? ['en' => $this->languageMeta($code)['name'], 'ru' => $this->languageMeta($code)['name']];

            return [
                'code' => $code,
                'name_en' => $meta['en'],
                'name_ru' => $meta['ru'],
            ];
        }, $codes);
    }

    public function pathForPage(Page $page, string $locale, string $defaultLocale): string
    {
        $slug = trim((string) $page->slug, '/');
        $filename = $slug === '' || $slug === 'index' ? 'index.html' : $slug . '.html';
        $normalizedLocale = $this->normalizeLocale($locale);
        $normalizedDefault = $this->normalizeLocale($defaultLocale) ?: 'en';

        if ($normalizedLocale === '' || $normalizedLocale === $normalizedDefault) {
            return $filename;
        }

        return "{$normalizedLocale}/{$filename}";
    }

    public function hrefForPage(Page $page, string $locale, string $defaultLocale): string
    {
        $path = $this->pathForPage($page, $locale, $defaultLocale);

        if ($path === 'index.html') {
            return '/';
        }

        if (str_ends_with($path, '/index.html')) {
            return '/' . substr($path, 0, -strlen('index.html'));
        }

        return '/' . $path;
    }

    public function languageSwitcherHtml(Page $page, Site $site): string
    {
        $defaultLocale = $this->normalizeLocale((string) ($site->locale ?? $site->default_locale ?? 'en')) ?: 'en';
        $locales = $this->buildLocaleSet($defaultLocale, is_array($site->alternate_locales) ? $site->alternate_locales : []);
        $currentLocale = $this->normalizeLocale((string) ($page->locale ?? $defaultLocale)) ?: $defaultLocale;

        if (count($locales) < 2 || !in_array($currentLocale, $locales, true)) {
            return '';
        }

        $current = $this->languageMeta($currentLocale);
        $items = '';
        foreach ($locales as $locale) {
            if ($locale === $currentLocale) {
                continue;
            }

            $meta = $this->languageMeta($locale);
            $items .= '<li class="menu__submenu-item"><a class="menu__submenu-link" href="' . e($this->hrefForPage($page, $locale, $defaultLocale)) . '" hreflang="' . e($meta['hreflang']) . '" lang="' . e($meta['hreflang']) . '"><span class="menu__lang"><img class="menu__lang-flag" src="' . e($meta['flag']) . '" alt="" width="16" height="11" loading="lazy"><span class="menu__lang-text">' . e($meta['name']) . '</span></span></a></li>';
        }

        return '<li class="menu__item menu__item--has-submenu menu__item--lang lang-item lang-item-' . e($currentLocale) . '"><a class="menu__link menu__link--lang" href="#" aria-haspopup="true" aria-expanded="false" data-desktop-submenu-trigger><span class="menu__lang"><img class="menu__lang-flag" src="' . e($current['flag']) . '" alt="" width="16" height="11" loading="lazy"><span class="menu__lang-text">' . e($current['name']) . '</span></span></a><ul class="menu__submenu" aria-label="Language submenu">' . $items . '</ul></li>';
    }

    public function menuWithLanguageSwitcher(string $menuHtml, Page $page, Site $site): string
    {
        $menuHtml = trim($this->stripLanguageSwitcher($menuHtml));
        $switcher = $this->languageSwitcherHtml($page, $site);

        if ($switcher === '') {
            return $menuHtml;
        }

        $inserted = $this->insertIntoFirstListByClass($menuHtml, 'menu__list', $switcher);

        return $inserted !== $menuHtml ? $inserted : trim($menuHtml . PHP_EOL . $switcher);
    }

    public function applyLanguageSwitcherToHtml(string $html, Page $page, Site $site): string
    {
        $html = $this->stripLanguageSwitcher($html);
        $locale = $this->normalizeLocale((string) ($page->locale ?? $site->locale ?? 'en')) ?: 'en';
        $desktopSwitcher = $this->languageSwitcherHtml($page, $site);
        $mobileSwitcher = $this->mobileLanguageSwitcherHtml($page, $site);
        $menuHtml = $this->menuWithLanguageSwitcher($this->layoutContent->resolveMenuInner($site, $locale), $page, $site);
        $mobileMenuHtml = $this->stripLanguageSwitcher($this->layoutContent->resolveMobileMenuHtml($site, $locale));

        if (trim($menuHtml) !== '') {
            $html = $this->replaceFirstElementByClass($html, 'div', 'header__inner', $menuHtml);
        } elseif ($desktopSwitcher !== '') {
            $html = $this->insertIntoFirstListByClass($html, 'menu__list', $desktopSwitcher);
        }

        if (trim($mobileMenuHtml) !== '') {
            if ($mobileSwitcher !== '') {
                $mobileMenuHtml = $this->insertIntoFirstListByClass($mobileMenuHtml, 'mobile-menu__list', $mobileSwitcher);
            }
            $html = $this->replaceFirstElementByClass($html, 'div', 'mobile-menu', $mobileMenuHtml);
        } elseif ($mobileSwitcher !== '') {
            $html = $this->insertIntoFirstListByClass($html, 'mobile-menu__list', $mobileSwitcher);
        }

        return $this->localizeHomeLinks($html, $page, $site);
    }

    private function localizeHomeLinks(string $html, Page $page, Site $site): string
    {
        $currentLocale = $this->normalizeLocale((string) ($page->locale ?? $site->locale ?? 'en')) ?: 'en';
        $defaultLocale = $this->normalizeLocale((string) ($site->locale ?? $site->default_locale ?? 'en')) ?: 'en';

        if ($currentLocale === $defaultLocale) {
            return $html;
        }

        $homeHref = '/' . $currentLocale . '/';
        $targetClasses = [
            'header__logo-wrapper',
            'footer__logo-wrapper',
            'breadcrumbs__item',
        ];

        return preg_replace_callback('/<a\b[^>]*\bhref=(["\'])\/\1[^>]*>/i', function (array $matches) use ($targetClasses, $homeHref): string {
            $tag = $matches[0];
            foreach ($targetClasses as $className) {
                if ($this->tagHasClass($tag, $className)) {
                    return preg_replace_callback('/\bhref=(["\'])\/\1/i', fn (array $hrefMatches): string => 'href=' . $hrefMatches[1] . $homeHref . $hrefMatches[1], $tag, 1) ?? $tag;
                }
            }

            return $tag;
        }, $html) ?? $html;
    }

    private function replaceFirstElementByClass(string $html, string $tagName, string $className, string $replacementHtml): string
    {
        if (trim($replacementHtml) === '') {
            return $html;
        }

        if (preg_match_all('/<' . preg_quote($tagName, '/') . '\b[^>]*>/i', $html, $openMatches, PREG_OFFSET_CAPTURE) === false) {
            return $html;
        }

        foreach ($openMatches[0] as [$openTag, $openOffset]) {
            if (!$this->tagHasClass($openTag, $className)) {
                continue;
            }

            $openEnd = $openOffset + strlen($openTag);
            $tail = substr($html, $openEnd);
            if ($tail === false) {
                return $html;
            }

            if (preg_match_all('/<\/?' . preg_quote($tagName, '/') . '\b[^>]*>/i', $tail, $tagMatches, PREG_OFFSET_CAPTURE) === false) {
                return $html;
            }

            $depth = 1;
            foreach ($tagMatches[0] as [$tag, $relativeOffset]) {
                if (str_starts_with(strtolower($tag), '</' . strtolower($tagName))) {
                    $depth--;
                    if ($depth === 0) {
                        $closeEnd = $openEnd + $relativeOffset + strlen($tag);
                        return substr($html, 0, $openOffset) . $replacementHtml . substr($html, $closeEnd);
                    }
                    continue;
                }

                $depth++;
            }

            return $html;
        }

        return $html;
    }

    private function insertIntoFirstListByClass(string $html, string $className, string $insertHtml): string
    {
        if ($insertHtml === '') {
            return $html;
        }

        if (preg_match_all('/<ul\b[^>]*>/i', $html, $openMatches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($openMatches[0] as [$openTag, $openOffset]) {
                if (!$this->tagHasClass($openTag, $className)) {
                    continue;
                }

                $openEnd = $openOffset + strlen($openTag);
                $tail = substr($html, $openEnd);
                if ($tail === false) {
                    return $html;
                }

                if (preg_match_all('/<\/?ul\b[^>]*>/i', $tail, $tagMatches, PREG_OFFSET_CAPTURE) === false) {
                    return $html;
                }

                $depth = 1;
                foreach ($tagMatches[0] as [$tag, $relativeOffset]) {
                    if (str_starts_with(strtolower($tag), '</ul')) {
                        $depth--;
                        if ($depth === 0) {
                            $insertOffset = $openEnd + $relativeOffset;
                            return substr($html, 0, $insertOffset) . $insertHtml . substr($html, $insertOffset);
                        }
                        continue;
                    }

                    $depth++;
                }

                return $html;
            }
        }

        return $html;
    }

    private function tagHasClass(string $tag, string $className): bool
    {
        if (preg_match('/\sclass=(["\'])(.*?)\1/is', $tag, $matches) !== 1) {
            return false;
        }

        $classes = preg_split('/\s+/', trim($matches[2])) ?: [];

        return in_array($className, $classes, true);
    }

    private function mobileLanguageSwitcherHtml(Page $page, Site $site): string
    {
        $defaultLocale = $this->normalizeLocale((string) ($site->locale ?? $site->default_locale ?? 'en')) ?: 'en';
        $locales = $this->buildLocaleSet($defaultLocale, is_array($site->alternate_locales) ? $site->alternate_locales : []);
        $currentLocale = $this->normalizeLocale((string) ($page->locale ?? $defaultLocale)) ?: $defaultLocale;

        if (count($locales) < 2 || !in_array($currentLocale, $locales, true)) {
            return '';
        }

        $current = $this->languageMeta($currentLocale);
        $items = '';
        foreach ($locales as $locale) {
            if ($locale === $currentLocale) {
                continue;
            }

            $meta = $this->languageMeta($locale);
            $items .= '<li class="mobile-menu__submenu-item"><a class="mobile-menu__submenu-link" href="' . e($this->hrefForPage($page, $locale, $defaultLocale)) . '" hreflang="' . e($meta['hreflang']) . '" lang="' . e($meta['hreflang']) . '"><span class="mobile-menu__lang"><img class="mobile-menu__lang-flag" src="' . e($meta['flag']) . '" alt="" width="16" height="11" loading="lazy"><span class="mobile-menu__lang-text">' . e($meta['name']) . '</span></span></a></li>';
        }

        return '<li class="mobile-menu__item mobile-menu__item--has-submenu mobile-menu__item--lang lang-item lang-item-' . e($currentLocale) . '"><button class="mobile-menu__link mobile-menu__link--lang" type="button" aria-expanded="false" data-mobile-submenu-trigger><span class="mobile-menu__lang"><img class="mobile-menu__lang-flag" src="' . e($current['flag']) . '" alt="" width="16" height="11" loading="lazy"><span class="mobile-menu__lang-text">' . e($current['name']) . '</span></span></button><ul class="mobile-menu__submenu" aria-label="Language submenu" hidden>' . $items . '</ul></li>';
    }

    public function prepareSiteLanguages(Site $site, array $candidateLocales = []): void
    {
        $originalDefaultLocale = (string) ($site->locale ?? $site->default_locale ?? 'en');
        $defaultLocale = $this->normalizeLocale($originalDefaultLocale) ?: 'en';
        $htmlDefaultLocale = trim($originalDefaultLocale) !== '' ? trim($originalDefaultLocale) : $defaultLocale;
        $derivedLocales = array_merge($candidateLocales, $this->deriveLocalesFromHeadLinks($site));
        $locales = $this->buildLocaleSet($defaultLocale, $derivedLocales);

        $site->update([
            'locale' => $htmlDefaultLocale,
            'default_locale' => $htmlDefaultLocale,
            'alternate_locales' => array_values(array_diff($locales, [$defaultLocale])),
        ]);
        $site->refresh();

        if (count($locales) < 2) {
            return;
        }

        $this->createLocalizedPages($site, $locales, $defaultLocale, $originalDefaultLocale, $htmlDefaultLocale);
        $this->createLocalizedSharedBlocks($site, $locales, $defaultLocale);
    }

    public function addSiteLanguage(Site $site, string $locale): Site
    {
        $normalized = $this->normalizeLocale($locale);
        $defaultLocale = $this->normalizeLocale((string) ($site->locale ?? $site->default_locale ?? 'en')) ?: 'en';

        if ($normalized === '' || !$this->isKnownIsoLanguage($normalized)) {
            throw new \InvalidArgumentException('Invalid ISO 639-1 language code.');
        }

        if ($normalized === $defaultLocale) {
            return $site->fresh() ?? $site;
        }

        $alternates = is_array($site->alternate_locales) ? $site->alternate_locales : [];
        $alternates[] = $normalized;
        $locales = $this->buildLocaleSet((string) ($site->locale ?? 'en'), $alternates);

        $site->update(['alternate_locales' => array_values(array_diff($locales, [$defaultLocale]))]);
        $site->refresh();
        $this->createLocalizedPages($site, $locales, $defaultLocale, (string) ($site->locale ?? $defaultLocale), (string) ($site->locale ?? $defaultLocale));
        $this->createLocalizedSharedBlocks($site, $locales, $defaultLocale);

        return $site->fresh() ?? $site;
    }

    public function removeSiteLanguage(Site $site, string $locale): Site
    {
        $normalized = $this->normalizeLocale($locale);
        $defaultLocale = $this->normalizeLocale((string) ($site->locale ?? $site->default_locale ?? 'en')) ?: 'en';

        if ($normalized === '' || $normalized === $defaultLocale) {
            throw new \InvalidArgumentException('Default language cannot be removed.');
        }

        DB::transaction(function () use ($site, $normalized, $defaultLocale): void {
            $pageIds = Page::where('site_id', $site->id)
                ->where('locale', $normalized)
                ->pluck('id')
                ->all();

            if ($pageIds !== []) {
                Section::whereIn('page_id', $pageIds)->delete();
                Page::whereIn('id', $pageIds)->delete();
            }

            SiteSharedBlock::where('site_id', $site->id)
                ->where('locale', $normalized)
                ->delete();

            $alternates = array_values(array_filter(
                is_array($site->alternate_locales) ? $site->alternate_locales : [],
                fn ($candidate): bool => $this->normalizeLocale(is_scalar($candidate) ? (string) $candidate : '') !== $normalized
            ));

            $site->update([
                'alternate_locales' => array_values(array_diff($this->buildLocaleSet((string) ($site->locale ?? $defaultLocale), $alternates), [$defaultLocale])),
            ]);
        });

        return $site->fresh() ?? $site;
    }

    /**
     * @return array<int, string>
     */
    private function deriveLocalesFromHeadLinks(Site $site): array
    {
        $locales = [];
        $pages = Page::where('site_id', $site->id)->get(['og_data']);
        foreach ($pages as $page) {
            $ogData = is_array($page->og_data) ? $page->og_data : [];
            foreach (($ogData['head_links'] ?? []) as $link) {
                if (!is_array($link) || strtolower((string) ($link['rel'] ?? '')) !== 'alternate') {
                    continue;
                }

                $locales[] = (string) ($link['hreflang'] ?? '');
            }
        }

        return $locales;
    }

    /**
     * @param  array<int, string>  $locales
     */
    private function createLocalizedPages(Site $site, array $locales, string $defaultLocale, string $originalDefaultLocale, string $htmlDefaultLocale): void
    {
        $defaultPages = Page::with('sections')
            ->where('site_id', $site->id)
            ->where(function ($query) use ($defaultLocale, $originalDefaultLocale) {
                $query->whereIn('locale', array_values(array_unique([
                    $defaultLocale,
                    $originalDefaultLocale,
                    str_replace('-', '_', $originalDefaultLocale),
                    str_replace('_', '-', $originalDefaultLocale),
                ])))
                    ->orWhereNull('parent_page_id');
            })
            ->get()
            ->unique('slug')
            ->values();

        foreach ($defaultPages as $sourcePage) {
            $sourcePage->update(['locale' => $htmlDefaultLocale]);

            foreach ($locales as $locale) {
                if ($locale === $defaultLocale) {
                    continue;
                }

                $page = Page::updateOrCreate(
                    ['site_id' => $site->id, 'slug' => $sourcePage->slug, 'locale' => $locale],
                    [
                        'title' => $sourcePage->title,
                        'template_key' => $sourcePage->template_key,
                        'status' => $sourcePage->status,
                        'meta_title' => $sourcePage->meta_title,
                        'meta_description' => $sourcePage->meta_description,
                        'meta_keywords' => $sourcePage->meta_keywords,
                        'canonical' => $this->localizedCanonical($site, $sourcePage, $locale, $defaultLocale),
                        'og_data' => $sourcePage->og_data,
                        'json_ld' => $sourcePage->json_ld,
                        'parent_page_id' => $sourcePage->id,
                    ]
                );

                Section::where('page_id', $page->id)->delete();
                foreach ($sourcePage->sections as $section) {
                    $copy = $section->replicate();
                    $copy->page_id = $page->id;
                    $copy->save();
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $locales
     */
    private function createLocalizedSharedBlocks(Site $site, array $locales, string $defaultLocale): void
    {
        $menu = $this->stripLanguageSwitcher($this->layoutContent->resolveMenuInner($site));
        $mobileMenu = $this->layoutContent->resolveMobileMenuHtml($site);
        $footer = $this->layoutContent->resolveFooterInner($site);

        foreach ($locales as $locale) {
            SiteSharedBlock::updateOrCreate(
                ['site_id' => $site->id, 'locale' => $locale],
                [
                    'menu_html' => $this->translateSharedHtml($menu, $locale, $defaultLocale),
                    'mobile_menu_html' => $this->translateSharedHtml($mobileMenu, $locale, $defaultLocale),
                    'footer_html' => $this->translateSharedHtml($footer, $locale, $defaultLocale),
                ]
            );
        }
    }

    public function languageMeta(string $locale): array
    {
        if (isset($this->languages[$locale])) {
            return $this->languages[$locale];
        }

        $name = $this->languageCatalog[$locale]['en'] ?? strtoupper($locale);

        return [
            'name' => $name,
            'hreflang' => $locale,
            'flag' => 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20width%3D%2716%27%20height%3D%2711%27%20viewBox%3D%270%200%2016%2011%27%3E%3Crect%20width%3D%2716%27%20height%3D%2711%27%20rx%3D%271%27%20fill%3D%27%23e5e7eb%27%2F%3E%3Ctext%20x%3D%278%27%20y%3D%277.8%27%20font-size%3D%275%27%20text-anchor%3D%27middle%27%20font-family%3D%27Arial%27%20fill%3D%27%23111827%27%3E' . rawurlencode(strtoupper($locale)) . '%3C%2Ftext%3E%3C%2Fsvg%3E',
        ];
    }

    private function localizedCanonical(Site $site, Page $page, string $locale, string $defaultLocale): string
    {
        return 'https://' . $site->domain . $this->hrefForPage($page, $locale, $defaultLocale);
    }

    public function stripLanguageSwitcher(string $html): string
    {
        $html = preg_replace('/<li\b(?=[^>]*\blang-item\b)[\s\S]*?<\/ul>\s*<\/li>\s*/i', '', $html) ?? $html;
        $html = preg_replace('/<li\b(?=[^>]*\bmenu__item--lang\b)[\s\S]*?<\/li>\s*/i', '', $html) ?? $html;
        return preg_replace('/<li\b(?=[^>]*\bmobile-menu__item--lang\b)[\s\S]*?<\/li>\s*/i', '', $html) ?? $html;
    }

    private function translateSharedHtml(string $html, string $locale, string $defaultLocale): string
    {
        if ($locale === $defaultLocale || trim($html) === '') {
            return $html;
        }

        $translations = [
            'es' => ['App' => 'Aplicación', 'Demo' => 'Demo', 'Tips' => 'Consejos', 'Bonuses' => 'Bonos', 'Reviews' => 'Reseñas', 'Contact Us' => 'Contacto', 'New Versions' => 'Nuevas versiones', "Author's" => 'Autores', 'Comparison' => 'Comparación', 'Where to play' => 'Dónde jugar', 'Characteristics' => 'Características', 'Review' => 'Reseña', 'Symbols' => 'Símbolos', 'Gameplay' => 'Juego', 'Conclusion' => 'Conclusión', 'Terms and Conditions' => 'Términos y condiciones', 'Cookie Policy' => 'Política de cookies', 'Privacy Policy' => 'Política de privacidad', 'Sitemap' => 'Mapa del sitio', 'Play now!' => '¡Jugar ahora!'],
            'de' => ['App' => 'App', 'Demo' => 'Demo', 'Tips' => 'Tipps', 'Bonuses' => 'Boni', 'Reviews' => 'Bewertungen', 'Contact Us' => 'Kontakt', 'New Versions' => 'Neue Versionen', "Author's" => 'Autoren', 'Comparison' => 'Vergleich', 'Where to play' => 'Wo spielen', 'Characteristics' => 'Merkmale', 'Review' => 'Bewertung', 'Symbols' => 'Symbole', 'Gameplay' => 'Spielablauf', 'Conclusion' => 'Fazit', 'Terms and Conditions' => 'Allgemeine Geschäftsbedingungen', 'Cookie Policy' => 'Cookie-Richtlinie', 'Privacy Policy' => 'Datenschutzrichtlinie', 'Sitemap' => 'Sitemap', 'Play now!' => 'Jetzt spielen!'],
            'fr' => ['App' => 'Application', 'Demo' => 'Démo', 'Tips' => 'Conseils', 'Bonuses' => 'Bonus', 'Reviews' => 'Avis', 'Contact Us' => 'Contact', 'New Versions' => 'Nouvelles versions', "Author's" => 'Auteurs', 'Comparison' => 'Comparaison', 'Where to play' => 'Où jouer', 'Characteristics' => 'Caractéristiques', 'Review' => 'Avis', 'Symbols' => 'Symboles', 'Gameplay' => 'Gameplay', 'Conclusion' => 'Conclusion', 'Terms and Conditions' => 'Conditions générales', 'Cookie Policy' => 'Politique de cookies', 'Privacy Policy' => 'Politique de confidentialité', 'Sitemap' => 'Plan du site', 'Play now!' => 'Jouer maintenant !'],
            'it' => ['App' => 'App', 'Demo' => 'Demo', 'Tips' => 'Consigli', 'Bonuses' => 'Bonus', 'Reviews' => 'Recensioni', 'Contact Us' => 'Contatti', 'New Versions' => 'Nuove versioni', "Author's" => 'Autori', 'Comparison' => 'Confronto', 'Where to play' => 'Dove giocare', 'Characteristics' => 'Caratteristiche', 'Review' => 'Recensione', 'Symbols' => 'Simboli', 'Gameplay' => 'Gameplay', 'Conclusion' => 'Conclusione', 'Terms and Conditions' => 'Termini e condizioni', 'Cookie Policy' => 'Informativa sui cookie', 'Privacy Policy' => 'Informativa sulla privacy', 'Sitemap' => 'Mappa del sito', 'Play now!' => 'Gioca ora!'],
            'pt' => ['App' => 'Aplicação', 'Demo' => 'Demo', 'Tips' => 'Dicas', 'Bonuses' => 'Bónus', 'Reviews' => 'Avaliações', 'Contact Us' => 'Contacto', 'New Versions' => 'Novas versões', "Author's" => 'Autores', 'Comparison' => 'Comparação', 'Where to play' => 'Onde jogar', 'Characteristics' => 'Características', 'Review' => 'Avaliação', 'Symbols' => 'Símbolos', 'Gameplay' => 'Jogabilidade', 'Conclusion' => 'Conclusão', 'Terms and Conditions' => 'Termos e condições', 'Cookie Policy' => 'Política de cookies', 'Privacy Policy' => 'Política de privacidade', 'Sitemap' => 'Mapa do site', 'Play now!' => 'Jogar agora!'],
        ];

        $map = $translations[$locale] ?? [];
        if ($map === []) {
            return $html;
        }

        $footerDisclaimerTranslations = [
            'es' => '%s es uno de los afiliados independientes de Spribe. Somos expertos en presentar información precisa y objetiva sobre juegos de casino innovadores y productos de iGaming. Consulta nuestros términos y condiciones y nuestra política de privacidad. Ten en cuenta que las actividades de los usuarios en sitios de terceros no están bajo el control de nuestra organización.',
            'de' => '%s ist einer der unabhängigen Partner von Spribe. Wir sind Experten darin, präzise und objektive Informationen über moderne Casinospiele und iGaming-Produkte bereitzustellen. Bitte lies unsere Allgemeinen Geschäftsbedingungen und unsere Datenschutzrichtlinie. Bitte beachte, dass die Aktivitäten von Nutzern auf Websites Dritter nicht unter der Kontrolle unserer Organisation stehen.',
            'fr' => '%s est l’un des affiliés indépendants de Spribe. Nous sommes spécialisés dans la présentation d’informations précises et objectives sur les jeux de casino innovants et les produits iGaming. Veuillez consulter nos conditions générales et notre politique de confidentialité. Veuillez noter que les activités des utilisateurs sur des sites tiers ne sont pas sous le contrôle de notre organisation.',
            'it' => '%s è uno degli affiliati indipendenti di Spribe. Siamo esperti nel presentare informazioni accurate e obiettive sui giochi da casinò innovativi e sui prodotti iGaming. Consulta i nostri termini e condizioni e la nostra informativa sulla privacy. Tieni presente che le attività degli utenti su siti di terze parti non sono sotto il controllo della nostra organizzazione.',
            'pt' => '%s é um dos afiliados independentes da Spribe. Somos especialistas em apresentar informações precisas e objetivas sobre jogos de casino inovadores e produtos de iGaming. Consulta os nossos termos e condições e a nossa política de privacidade. Tem em atenção que as atividades dos utilizadores em sites de terceiros não estão sob o controlo da nossa organização.',
        ];

        return preg_replace_callback('/>([^<>]+)</', function (array $matches) use ($map, $footerDisclaimerTranslations, $locale): string {
            $text = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalized = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

            $translated = $map[$normalized] ?? null;
            if ($translated === null && isset($footerDisclaimerTranslations[$locale])) {
                $pattern = '/^(.+?) is one of Spribe[’\']s independent affiliates\. We are experts in presenting accurate, objective information about cutting-edge casino games and iGaming products\. Please go over our terms and conditions and privacy policy\. Please be aware that the activities of users on third-party sites are not under the control of our organization\.$/u';
                if (preg_match($pattern, $normalized, $disclaimerMatch) === 1) {
                    $translated = sprintf($footerDisclaimerTranslations[$locale], $disclaimerMatch[1]);
                }
            }

            if ($translated === null) {
                return $matches[0];
            }

            $leading = preg_match('/^\s*/', $matches[1], $leadingMatch) === 1 ? ($leadingMatch[0] ?? '') : '';
            $trailing = preg_match('/\s*$/', $matches[1], $trailingMatch) === 1 ? ($trailingMatch[0] ?? '') : '';

            return '>' . $leading . e($translated) . $trailing . '<';
        }, $html) ?? $html;
    }
}

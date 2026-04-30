@php
    $moduleSlug = isset($module_slug) && is_string($module_slug) && trim($module_slug) !== ''
        ? trim($module_slug)
        : 'module';

    $sectionId = isset($id) && is_string($id) && trim($id) !== ''
        ? trim($id)
        : $moduleSlug;

    $sectionClass = isset($class) && is_string($class) && trim($class) !== ''
        ? trim($class)
        : $moduleSlug;

    $sectionTitle = null;
    $hasSectionTitle = false;
    foreach (['heading', 'title'] as $titleKey) {
        if (isset($$titleKey) && is_string($$titleKey) && trim($$titleKey) !== '') {
            $sectionTitle = trim($$titleKey);
            $hasSectionTitle = true;
            break;
        }
    }

    if ($sectionTitle === null) {
        $sectionTitle = ucwords(str_replace('-', ' ', $moduleSlug));
    }

    $sectionSubtitle = isset($subheading) && is_string($subheading) && trim($subheading) !== ''
        ? trim($subheading)
        : null;

    $sectionDescription = isset($description) && is_string($description) && trim($description) !== ''
        ? trim($description)
        : null;

    $sectionText = isset($text) && is_string($text) && trim($text) !== ''
        ? trim($text)
        : null;

    $unorderedItems = [];
    foreach (['items', 'listItems', 'addressItems'] as $itemsKey) {
        if (isset($$itemsKey) && is_array($$itemsKey) && count($$itemsKey) > 0) {
            $unorderedItems = $$itemsKey;
            break;
        }
    }

    $orderedItemsLocal = isset($orderedItems) && is_array($orderedItems) ? $orderedItems : [];

    $contentBlockParagraphs = [];
    $contentBlockTables = [];
    $contentBlockHeadings = [];
    if (isset($contentBlocks) && is_array($contentBlocks)) {
        foreach ($contentBlocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $blockType = strtolower(trim((string) ($block['type'] ?? '')));
            if (
                in_array($blockType, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)
                && isset($block['text'])
                && !is_array($block['text'])
                && trim((string) $block['text']) !== ''
            ) {
                $contentBlockHeadings[] = trim((string) $block['text']);
            }

            if (
                in_array($blockType, ['paragraph', 'p'], true)
                && isset($block['text'])
                && !is_array($block['text'])
                && trim((string) $block['text']) !== ''
            ) {
                $contentBlockParagraphs[] = trim((string) $block['text']);
            }

            if ($blockType === 'table' && isset($block['rows']) && is_array($block['rows'])) {
                $contentBlockTables[] = $block['rows'];
            }
        }
    }

    if (count($contentBlockParagraphs) > 0) {
        $sectionDescription = $contentBlockParagraphs[0];
    }

    if (count($contentBlockHeadings) > 0) {
        $sectionTitle = $contentBlockHeadings[0];
        $hasSectionTitle = true;
    }

    $defaultModuleHtml = '';
    $defaultModulePath = resource_path("views/defaults/modules/{$moduleSlug}.html");
    if (is_file($defaultModulePath)) {
        $loadedDefaultHtml = file_get_contents($defaultModulePath);
        if (is_string($loadedDefaultHtml) && trim($loadedDefaultHtml) !== '') {
            $defaultModuleHtml = $loadedDefaultHtml;
        }
    }

    $replaceFirstElementByClass = function (string $html, string $classFragment, string $value, array $tags): string {
        $escapedValue = e($value);
        $tagPattern = implode('|', array_map('preg_quote', $tags));
        $classPattern = preg_quote($classFragment, '/');
        $pattern = '/<(' . $tagPattern . ')(\s[^>]*class=(["\'])(?=[^"\']*\b' . $classPattern . '\b)[^"\']*\3[^>]*)>.*?<\/\1>/is';

        return preg_replace($pattern, '<$1$2>' . $escapedValue . '</$1>', $html, 1) ?? $html;
    };

    $replaceFirstElementByClassContains = function (string $html, string $classFragment, string $value, array $tags): string {
        $escapedValue = e($value);
        $tagPattern = implode('|', array_map('preg_quote', $tags));
        $classPattern = preg_quote($classFragment, '/');
        $pattern = '/<(' . $tagPattern . ')(\s[^>]*class=(["\'])(?=[^"\']*' . $classPattern . ')[^"\']*\3[^>]*)>.*?<\/\1>/is';

        return preg_replace($pattern, '<$1$2>' . $escapedValue . '</$1>', $html, 1) ?? $html;
    };

    $setNodeText = function (\DOMNode $node, string $value): void {
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }

        $node->appendChild($node->ownerDocument->createTextNode($value));
    };

    $replaceSequentialParagraphs = function (string $html, array $paragraphs) use ($setNodeText): string {
        if (count($paragraphs) === 0 || !class_exists(\DOMDocument::class)) {
            return $html;
        }

        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        if (!$loaded) {
            return $html;
        }

        $xpath = new \DOMXPath($document);
        $targets = $xpath->query(
            '//p[contains(@class, "description") or contains(@class, "accent") or contains(@class, "text") or contains(@class, "subtitle")]'
        );

        if (!$targets) {
            return $html;
        }

        $paragraphIndex = 0;
        foreach ($targets as $target) {
            if (!isset($paragraphs[$paragraphIndex])) {
                break;
            }

            $setNodeText($target, $paragraphs[$paragraphIndex]);
            $paragraphIndex++;
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        $rendered = '';
        foreach ($body->childNodes as $child) {
            $fragment = $document->saveHTML($child);
            if (is_string($fragment)) {
                $rendered .= $fragment;
            }
        }

        return trim($rendered);
    };

    $replaceSequentialHeadings = function (string $html, array $headings) use ($setNodeText): string {
        if (count($headings) === 0 || !class_exists(\DOMDocument::class)) {
            return $html;
        }

        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        if (!$loaded) {
            return $html;
        }

        $xpath = new \DOMXPath($document);
        $targets = $xpath->query(
            '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6][contains(@class, "title") or contains(@class, "subtitle") or contains(@class, "subheading")]'
        );

        if (!$targets) {
            return $html;
        }

        $headingIndex = 0;
        foreach ($targets as $target) {
            if (!isset($headings[$headingIndex])) {
                break;
            }

            $setNodeText($target, $headings[$headingIndex]);
            $headingIndex++;
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        $rendered = '';
        foreach ($body->childNodes as $child) {
            $fragment = $document->saveHTML($child);
            if (is_string($fragment)) {
                $rendered .= $fragment;
            }
        }

        return trim($rendered);
    };

    $replaceModuleTables = function (string $html, string $moduleSlug, array $tables) use ($setNodeText): string {
        if (count($tables) === 0 || !class_exists(\DOMDocument::class)) {
            return $html;
        }

        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        if (!$loaded) {
            return $html;
        }

        $xpath = new \DOMXPath($document);
        $moduleLabelClass = "{$moduleSlug}__label";
        $moduleValueClass = "{$moduleSlug}__value";
        $htmlTables = $xpath->query('//table');

        if (!$htmlTables) {
            return $html;
        }

        foreach ($tables as $tableIndex => $tableRows) {
            $htmlTable = $htmlTables->item($tableIndex);
            if (!$htmlTable || !is_array($tableRows)) {
                continue;
            }

            $htmlRows = $xpath->query('.//tbody/tr', $htmlTable);
            if (!$htmlRows || $htmlRows->length === 0) {
                $htmlRows = $xpath->query('.//tr', $htmlTable);
            }
            if (!$htmlRows) {
                continue;
            }

            foreach (array_values($tableRows) as $rowIndex => $row) {
                $htmlRow = $htmlRows->item($rowIndex);
                if (!$htmlRow || !is_array($row)) {
                    if (!is_array($row) || !$htmlRows->item($htmlRows->length - 1)) {
                        continue;
                    }

                    $htmlRow = $htmlRows->item($htmlRows->length - 1)->cloneNode(true);
                    $tbodyNodes = $xpath->query('.//tbody', $htmlTable);
                    $rowParent = $tbodyNodes && $tbodyNodes->item(0) ? $tbodyNodes->item(0) : $htmlTable;
                    $rowParent->appendChild($htmlRow);
                }

                $label = isset($row[0]) && !is_array($row[0]) ? trim((string) $row[0]) : '';
                $value = isset($row[1]) && !is_array($row[1]) ? trim((string) $row[1]) : '';
                $cells = $xpath->query('./th|./td', $htmlRow);

                if ($label !== '') {
                    $labelNodes = $xpath->query(
                        './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $moduleLabelClass . ' ")]',
                        $htmlRow
                    );
                    if (!$labelNodes || !$labelNodes->item(0)) {
                        $labelNodes = $xpath->query(
                            './/*[contains(concat(" ", normalize-space(@class), " "), " table__label ")] | .//*[contains(concat(" ", normalize-space(@class), " "), " symbols__label ")]',
                            $htmlRow
                        );
                    }
                    if ($labelNodes && $labelNodes->item(0)) {
                        $setNodeText($labelNodes->item(0), $label);
                    } else {
                        if ($cells && $cells->item(0)) {
                            $setNodeText($cells->item(0), $label);
                        }
                    }
                }

                if ($value !== '') {
                    $valueNodes = $xpath->query(
                        './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $moduleValueClass . ' ")]',
                        $htmlRow
                    );
                    if (!$valueNodes || !$valueNodes->item(0)) {
                        $valueNodes = $xpath->query(
                            './/*[contains(concat(" ", normalize-space(@class), " "), " table__value ")] | .//*[contains(concat(" ", normalize-space(@class), " "), " symbols__value ")]',
                            $htmlRow
                        );
                    }
                    if ($valueNodes && $valueNodes->item(0)) {
                        $setNodeText($valueNodes->item(0), $value);
                    } else {
                        if ($cells && $cells->item(1)) {
                            $setNodeText($cells->item(1), $value);
                        }
                    }
                }

                if ($cells) {
                    foreach (array_values($row) as $cellIndex => $cellValue) {
                        if ($cellIndex < 2 || is_array($cellValue) || trim((string) $cellValue) === '') {
                            continue;
                        }

                        if ($cells->item($cellIndex)) {
                            $setNodeText($cells->item($cellIndex), trim((string) $cellValue));
                        }
                    }
                }
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        $rendered = '';
        foreach ($body->childNodes as $child) {
            $fragment = $document->saveHTML($child);
            if (is_string($fragment)) {
                $rendered .= $fragment;
            }
        }

        return trim($rendered);
    };

    if ($defaultModuleHtml !== '') {
        if ($hasSectionTitle && $sectionTitle !== null) {
            $updatedModuleHtml = $replaceFirstElementByClass($defaultModuleHtml, "{$moduleSlug}__title", $sectionTitle, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
            $defaultModuleHtml = $updatedModuleHtml !== $defaultModuleHtml
                ? $updatedModuleHtml
                : $replaceFirstElementByClassContains($defaultModuleHtml, '__title', $sectionTitle, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
        }

        if ($sectionSubtitle !== null) {
            $defaultModuleHtml = $replaceFirstElementByClass($defaultModuleHtml, "{$moduleSlug}__subtitle", $sectionSubtitle, ['h2', 'h3', 'h4', 'h5', 'h6', 'p']);
        }

        if ($sectionDescription !== null) {
            $defaultModuleHtml = $replaceFirstElementByClass($defaultModuleHtml, "{$moduleSlug}__description", $sectionDescription, ['p', 'div']);
        }

        if ($sectionText !== null) {
            $defaultModuleHtml = $replaceFirstElementByClass($defaultModuleHtml, "{$moduleSlug}__text", $sectionText, ['p', 'div']);
        }

        if (isset($contentBlockParagraphs[1])) {
            $defaultModuleHtml = $replaceFirstElementByClass($defaultModuleHtml, 'text--limited', $contentBlockParagraphs[1], ['p', 'div']);
        }

        if (count($contentBlockParagraphs) > 0) {
            $defaultModuleHtml = $replaceSequentialParagraphs($defaultModuleHtml, $contentBlockParagraphs);
        }

        if (count($contentBlockHeadings) > 0) {
            $defaultModuleHtml = $replaceSequentialHeadings($defaultModuleHtml, $contentBlockHeadings);
        }

        if (count($contentBlockTables) > 0) {
            $defaultModuleHtml = $replaceModuleTables($defaultModuleHtml, $moduleSlug, $contentBlockTables);
        }
    }
@endphp

@if(($render_mode ?? null) === 'raw_html' && isset($raw_html) && is_string($raw_html) && trim($raw_html) !== '')
    {!! $raw_html !!}
@else
    @if($defaultModuleHtml !== '')
        <section class="{{ $sectionClass }}" id="{{ $sectionId }}">
            {!! $defaultModuleHtml !!}
        </section>
    @else
        <section class="{{ $sectionClass }}" id="{{ $sectionId }}">
            <div class="{{ $moduleSlug }}__inner">
                @if($sectionTitle !== '')
                    <h2 class="{{ $moduleSlug }}__title">{{ $sectionTitle }}</h2>
                @endif

                @if($sectionSubtitle)
                    <h3 class="{{ $moduleSlug }}__subtitle">{{ $sectionSubtitle }}</h3>
                @endif

                @if($sectionDescription)
                    <p class="{{ $moduleSlug }}__description">{{ $sectionDescription }}</p>
                @endif

                @if($sectionText)
                    <p class="{{ $moduleSlug }}__text">{!! nl2br(e($sectionText)) !!}</p>
                @endif

                @if(count($unorderedItems) > 0)
                    <ul class="{{ $moduleSlug }}__list" aria-label="{{ $moduleSlug }} list">
                        @foreach($unorderedItems as $item)
                            <li class="{{ $moduleSlug }}__list-item">{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(count($orderedItemsLocal) > 0)
                    <ol class="{{ $moduleSlug }}__ordered-list" aria-label="{{ $moduleSlug }} ordered list">
                        @foreach($orderedItemsLocal as $item)
                            <li class="{{ $moduleSlug }}__ordered-item">{{ $item }}</li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    @endif
@endif

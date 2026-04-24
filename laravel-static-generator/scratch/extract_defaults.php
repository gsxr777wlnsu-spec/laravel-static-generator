<?php

function extractSections($htmlFile) {
    if (!file_exists($htmlFile)) return [];

    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($htmlFile), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $results = [];
    $sections = $xpath->query('//section | //footer | //header');

    foreach ($sections as $section) {
        $tagName = strtolower($section->tagName);
        $class = $section->getAttribute('class');
        $id = $section->getAttribute('id');
        
        $moduleKey = '';
        if ($tagName === 'footer') {
            $moduleKey = 'footer';
        } elseif ($tagName === 'header') {
            $moduleKey = 'header';
        } else {
            $tokens = preg_split('/\s+/', $class);
            foreach ($tokens as $token) {
                $base = preg_replace('/(--|__).*$/', '', $token);
                if ($base) {
                    $moduleKey = $base;
                    break;
                }
            }
        }
        
        if (!$moduleKey) continue;
        
        // Skip if already found (prefer the one from index.html)
        if (isset($results[$moduleKey]) && strpos($htmlFile, 'index.html') === false) continue;

        $innerHtml = '';
        foreach ($section->childNodes as $child) {
            $innerHtml .= $dom->saveHTML($child);
        }

        $results[$moduleKey] = [
            'class' => $class,
            'id' => $id ?: $moduleKey,
            'raw_html' => trim($innerHtml),
            'tag' => $tagName
        ];
    }

    return $results;
}

$allFiles = glob('storage/generated/site1/*.html');
$combinedResults = [];

foreach ($allFiles as $file) {
    $results = extractSections($file);
    foreach ($results as $key => $data) {
        if (!isset($combinedResults[$key])) {
            $combinedResults[$key] = $data;
        }
    }
}

// Special case for hero-sitemap
$sitemapFile = 'storage/generated/site1/sitemap.html';
if (file_exists($sitemapFile)) {
    $heroSitemap = extractSections($sitemapFile)['hero'] ?? null;
    if ($heroSitemap) {
        $combinedResults['hero-sitemap'] = $heroSitemap;
    }
}

$outputDir = 'resources/views/defaults/modules';
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

$catalog = [];

foreach ($combinedResults as $key => $data) {
    file_put_contents("{$outputDir}/{$key}.html", $data['raw_html']);
    $catalog[$key] = [
        'class' => $data['class'],
        'id' => $data['id'],
    ];
}

echo "GENERATE_CATALOG_START\n";
echo json_encode($catalog, JSON_PRETTY_PRINT);
echo "\nGENERATE_CATALOG_END\n";

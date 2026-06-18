<?php

namespace App\Services;

use App\Models\AiAgentConfig;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class AiAgentService
{
    private const PAGE_EDITABLE_FIELDS = [
        'title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical',
    ];

    /**
     * Head meta tags with these names are already represented by dedicated page fields.
     *
     * @var array<int, string>
     */
    private const HEAD_META_CONTENT_EXCLUDED_NAMES = [
        'description',
        'keywords',
        'geo.region',
        'geo.position',
        'icbm',
        'contact',
        'copyright',
        'designer',
        'generator',
        'author',
        'rating',
        'telegram:channel',
        'telegram:bot',
        'twitter:title',
        'twitter:description',
        'twitter:site',
        'twitter:creator',
        'twitter:image',
    ];

    /**
     * @var array<int, string>
     */
    private const HEAD_META_CONTENT_EXCLUDED_PROPERTIES = [
        'vk:image',
        'vk:app_id',
        'og:image',
    ];

    /**
     * @var array<int, string>
     */
    private const HEAD_LINK_EDITABLE_RELS = [
        'alternate',
    ];

    private const SECTION_TECHNICAL_FIELDS = [
        'module',
        'module_key',
        'render_mode',
    ];

    /**
     * @var array<int, array{value:string,label:string}>
     */
    private const PROVIDER_OPTIONS = [
        ['value' => 'openai', 'label' => 'OpenAI (direct)'],
        ['value' => 'anthropic', 'label' => 'Anthropic (direct)'],
        ['value' => 'google-ai-studio', 'label' => 'Google AI Studio'],
        ['value' => 'google-vertex', 'label' => 'Google Vertex AI'],
        ['value' => 'meta-llama', 'label' => 'Meta Llama (partner)'],
        ['value' => 'mistral', 'label' => 'Mistral AI'],
        ['value' => 'cohere', 'label' => 'Cohere'],
        ['value' => 'xai', 'label' => 'xAI'],
        ['value' => 'deepseek', 'label' => 'DeepSeek'],
        ['value' => 'openrouter', 'label' => 'OpenRouter'],
        ['value' => 'closerouter', 'label' => 'CloseRouter'],
        ['value' => 'together', 'label' => 'Together AI'],
        ['value' => 'fireworks', 'label' => 'Fireworks AI'],
        ['value' => 'groq', 'label' => 'Groq'],
        ['value' => 'replicate', 'label' => 'Replicate'],
        ['value' => 'aws-bedrock', 'label' => 'AWS Bedrock'],
        ['value' => 'azure-openai', 'label' => 'Azure OpenAI'],
        ['value' => 'huggingface-inference', 'label' => 'Hugging Face Inference'],
        ['value' => 'custom', 'label' => 'Custom (OpenAI-compatible)'],
    ];

    /**
     * @var array<string, string>
     */
    private const OPENAI_COMPATIBLE_BASE_URLS = [
        'openai' => 'https://api.openai.com/v1',
        'openrouter' => 'https://openrouter.ai/api/v1',
        'closerouter' => 'https://api.closerouter.dev/v1',
        'together' => 'https://api.together.xyz/v1',
        'fireworks' => 'https://api.fireworks.ai/inference/v1',
        'groq' => 'https://api.groq.com/openai/v1',
        'deepseek' => 'https://api.deepseek.com/v1',
        'xai' => 'https://api.x.ai/v1',
        'mistral' => 'https://api.mistral.ai/v1',
    ];

    private const RAW_HTML_TEXT_SCOPE_FILE = 'index-raw_html.md';
    private const RAW_HTML_VIRTUAL_PATH_PATTERN = '/^pages\.(\d+)\.sections\.(\d+)\.raw_html\.__([a-z]+)__\.([a-f0-9]{16})$/';
    private const HEAD_EXTRA_SCRIPT_VIRTUAL_PATH_PATTERN = '/^pages\.(\d+)\.og_data\.head_extra\.__script__\.(\d+)$/';
    private const RAW_HTML_SECTION_PATH_PATTERN = '/^pages\.(\d+)\.sections\.(\d+)$/';

    /**
     * @var array<int, string>
     */
    private const RAW_HTML_REMOVABLE_TAGS = [
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'p',
        'ul',
        'ol',
        'li',
        'tr',
    ];

    /**
     * @var array<int, string>
     */
    private const RAW_HTML_ADDABLE_TEXT_TAGS = [
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'p',
    ];

    /**
     * @var array<int, string>
     */
    private const RAW_HTML_GROUP_BLOCK_TAGS = [
        'ul',
        'ol',
        'table',
    ];

    /**
     * @var array<int, string>
     */
    private const RAW_HTML_TEXT_SKIP_TAGS = [
        'script',
        'style',
        'noscript',
        'svg',
        'path',
        'title',
    ];

    /**
     * @var array<int, string>
     */
    private const HEADING_TAGS = [
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
    ];

    /**
     * @var array<int, string>
     */
    private const NON_EDITABLE_MENU_MODULES = [
        'mobile-menu',
    ];

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public function providerOptions(): array
    {
        return self::PROVIDER_OPTIONS;
    }

    /**
     * @return array<int, array{file:string,page_fields:array<int, array<string, mixed>>,section_fields:array<int, array<string, mixed>>,section_block_controls:array<int, array<string, mixed>>}>
     */
    public function listTemplateFields(string $sourceDomain): array
    {
        $sourceDirectory = $this->resolveSourceDirectory($sourceDomain);
        $files = glob($sourceDirectory . '/*-raw_html.md') ?: [];
        sort($files);

        $catalog = [];

        foreach ($files as $fullPath) {
            $fileName = basename($fullPath);
            $data = Yaml::parseFile($fullPath);
            if (!is_array($data)) {
                continue;
            }

            $pageFields = [];
            $sectionFields = [];
            $sectionBlockControls = [];
            $pages = isset($data['pages']) && is_array($data['pages']) ? $data['pages'] : [];

            foreach ($pages as $pageIndex => $page) {
                if (!is_array($page)) {
                    continue;
                }

                foreach (self::PAGE_EDITABLE_FIELDS as $pageField) {
                    if (!array_key_exists($pageField, $page) || !is_string($page[$pageField])) {
                        continue;
                    }

                    $path = "pages.{$pageIndex}.{$pageField}";
                    $pageFields[] = $this->buildPageField(
                        path: $path,
                        label: $pageField,
                        value: $page[$pageField]
                    );
                }

                foreach ($this->extractSeoHeadEditableFields($page, $pageIndex) as $seoField) {
                    $pageFields[] = $seoField;
                }

                $sections = isset($page['sections']) && is_array($page['sections']) ? $page['sections'] : [];
                foreach ($sections as $sectionIndex => $section) {
                    if (!is_array($section)) {
                        continue;
                    }

                    $module = (string) ($section['module'] ?? $section['module_key'] ?? "section-{$sectionIndex}");
                    if ($this->shouldHideSectionFromEditing($module)) {
                        continue;
                    }

                    foreach ($section as $field => $value) {
                        if (!is_string($value) || in_array($field, self::SECTION_TECHNICAL_FIELDS, true)) {
                            continue;
                        }

                        $path = "pages.{$pageIndex}.sections.{$sectionIndex}.{$field}";
                        if ($this->shouldExtractRawHtmlTextFields($fileName, $field)) {
                            $sectionFields = array_merge(
                                $sectionFields,
                                $this->extractRawHtmlEditableFields(
                                    rawHtml: $value,
                                    sectionPath: "pages.{$pageIndex}.sections.{$sectionIndex}",
                                    module: $module
                                )
                            );

                            $sectionBlockControl = $this->extractRawHtmlBlockControls(
                                rawHtml: $value,
                                sectionPath: "pages.{$pageIndex}.sections.{$sectionIndex}",
                                module: $module
                            );
                            if (is_array($sectionBlockControl) && $sectionBlockControl !== []) {
                                $sectionBlockControls[] = $sectionBlockControl;
                            }

                            continue;
                        }

                        $sectionFields[] = [
                            'path' => $path,
                            'field' => $field,
                            'module' => $module,
                            'value_preview' => $this->previewText($value),
                            'length' => mb_strlen($value),
                        ];
                    }
                }
            }

            $catalog[] = [
                'file' => $fileName,
                'page_fields' => $pageFields,
                'section_fields' => $sectionFields,
                'section_block_controls' => $sectionBlockControls,
            ];
        }

        return $catalog;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPageField(string $path, string $label, string $value, int $rows = 2): array
    {
        return [
            'path' => $path,
            'field' => $label,
            'value' => $value,
            'value_preview' => $this->previewText($value),
            'length' => mb_strlen($value),
            'input_rows' => max(2, $rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<int, array<string, mixed>>
     */
    private function extractSeoHeadEditableFields(array $page, int $pageIndex): array
    {
        $fields = [];
        $headMeta = Arr::get($page, 'og_data.head_meta');
        if (is_array($headMeta)) {
            foreach ($headMeta as $metaIndex => $metaItem) {
                if (!is_array($metaItem)) {
                    continue;
                }

                $content = $metaItem['content'] ?? null;
                if (!is_string($content)) {
                    continue;
                }

                if ($this->isHeadMetaContentExcluded($metaItem)) {
                    continue;
                }

                $label = $this->describeHeadMetaEntry($metaItem);
                $path = "pages.{$pageIndex}.og_data.head_meta.{$metaIndex}.content";
                $fields[] = $this->buildPageField($path, $label, $content);
            }
        }

        $headLinks = Arr::get($page, 'og_data.head_links');
        if (is_array($headLinks)) {
            foreach ($headLinks as $linkIndex => $linkItem) {
                if (!is_array($linkItem)) {
                    continue;
                }

                $rel = strtolower(trim((string) ($linkItem['rel'] ?? '')));
                if (!in_array($rel, self::HEAD_LINK_EDITABLE_RELS, true)) {
                    continue;
                }

                $href = $linkItem['href'] ?? null;
                if (is_string($href)) {
                    $path = "pages.{$pageIndex}.og_data.head_links.{$linkIndex}.href";
                    $label = $this->describeHeadLinkEntry($linkItem) . ' href';
                    $fields[] = $this->buildPageField($path, $label, $href);
                }

                $hreflang = $linkItem['hreflang'] ?? null;
                if ($rel === 'alternate' && is_string($hreflang)) {
                    $path = "pages.{$pageIndex}.og_data.head_links.{$linkIndex}.hreflang";
                    $label = $this->describeHeadLinkEntry($linkItem) . ' hreflang';
                    $fields[] = $this->buildPageField($path, $label, $hreflang);
                }
            }
        }

        $headExtra = Arr::get($page, 'og_data.head_extra');
        if (is_string($headExtra)) {
            foreach ($this->extractHeadExtraScriptBlocks($headExtra) as $scriptIndex => $scriptBlock) {
                $path = "pages.{$pageIndex}.og_data.head_extra.__script__.{$scriptIndex}";
                $fields[] = $this->buildPageField(
                    path: $path,
                    label: 'Head JSON-LD script block #' . ($scriptIndex + 1),
                    value: $scriptBlock,
                    rows: 14
                );
            }
        }

        $headCustom = Arr::get($page, 'og_data.head_custom');
        if (is_string($headCustom)) {
            $fields[] = $this->buildPageField(
                path: "pages.{$pageIndex}.og_data.head_custom",
                label: 'Head custom scripts/styles',
                value: $headCustom,
                rows: 6
            );
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $metaItem
     */
    private function isHeadMetaContentExcluded(array $metaItem): bool
    {
        $name = strtolower(trim((string) ($metaItem['name'] ?? '')));
        if ($name !== '' && in_array($name, self::HEAD_META_CONTENT_EXCLUDED_NAMES, true)) {
            return true;
        }

        $property = strtolower(trim((string) ($metaItem['property'] ?? '')));
        if ($property !== '' && in_array($property, self::HEAD_META_CONTENT_EXCLUDED_PROPERTIES, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $metaItem
     */
    private function describeHeadMetaEntry(array $metaItem): string
    {
        $name = trim((string) ($metaItem['name'] ?? ''));
        $property = trim((string) ($metaItem['property'] ?? ''));
        $httpEquiv = trim((string) ($metaItem['http_equiv'] ?? ''));

        if ($name !== '') {
            return "Head meta: {$name}";
        }

        if ($property !== '') {
            return "Head meta: {$property}";
        }

        if ($httpEquiv !== '') {
            return "Head meta: http-equiv {$httpEquiv}";
        }

        return 'Head meta: content';
    }

    /**
     * @param  array<string, mixed>  $linkItem
     */
    private function describeHeadLinkEntry(array $linkItem): string
    {
        $rel = trim((string) ($linkItem['rel'] ?? 'link'));
        return "Head link: {$rel}";
    }

    /**
     * @return array<int, string>
     */
    private function extractHeadExtraScriptBlocks(string $headExtra): array
    {
        if (!preg_match_all('/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>[\s\S]*?<\/script>/i', $headExtra, $matches)) {
            return [];
        }

        $blocks = [];
        foreach (($matches[0] ?? []) as $block) {
            if (!is_string($block)) {
                continue;
            }

            $trimmed = trim($block);
            if ($trimmed === '') {
                continue;
            }

            $blocks[] = $trimmed;
        }

        return $blocks;
    }

    public function cloneDomainTemplates(string $sourceDomain, string $targetDomain): string
    {
        $sourceDirectory = $this->resolveSourceDirectory($sourceDomain);
        $targetDirectory = $this->resolveTargetDirectory($targetDomain);

        if (!is_dir($sourceDirectory)) {
            throw new RuntimeException("Source template directory was not found: {$sourceDirectory}");
        }

        if (is_dir($targetDirectory)) {
            throw new RuntimeException("Target template directory already exists: {$targetDirectory}");
        }

        if (!File::copyDirectory($sourceDirectory, $targetDirectory)) {
            throw new RuntimeException("Could not copy template directory to: {$targetDirectory}");
        }

        $this->rewriteClonedDomainMetadata($targetDirectory, $targetDomain);

        return $targetDirectory;
    }

    /**
     * @param  array<int, array{file?:string,path?:string,prompt?:string}>  $fieldPrompts
     * @return array{updated_fields:int,updated_files:int,details:array<int,array<string,mixed>>}
     */
    public function applyPromptsToDomain(
        string $targetDomain,
        array $fieldPrompts,
        ?AiAgentConfig $config,
        ?int $siteId = null
    ): array {
        // TODO(PROD): remove temporary per-file/per-field AI prompt execution debug logging.
        $startedAt = microtime(true);
        $targetDirectory = $this->resolveTargetDirectory($targetDomain);
        if (!is_dir($targetDirectory)) {
            throw new RuntimeException("Target template directory was not found: {$targetDirectory}");
        }

        $normalizedPrompts = $this->normalizeFieldPrompts($fieldPrompts);
        if ($normalizedPrompts === []) {
            Log::info('ai.apply_prompts.skipped_empty', [
                'target_domain' => $targetDomain,
            ]);
            return [
                'updated_fields' => 0,
                'updated_files' => 0,
                'details' => [],
            ];
        }

        if (!$config || !$config->is_active || trim((string) $config->api_key) === '') {
            throw new RuntimeException('AI agent config is not active or API key is missing.');
        }

        if ($siteId !== null && !$config->isSiteAllowed($siteId)) {
            throw new RuntimeException("AI agent does not have access to site #{$siteId}.");
        }

        $groupedByFile = [];
        foreach ($normalizedPrompts as $item) {
            $groupedByFile[$item['file']][] = $item;
        }

        Log::info('ai.apply_prompts.start', [
            'target_domain' => $targetDomain,
            'target_directory' => $targetDirectory,
            'prompt_count' => count($normalizedPrompts),
            'file_count' => count($groupedByFile),
            'provider' => $config->provider,
            'model_name' => $config->model_name,
        ]);

        $updatedFields = 0;
        $updatedFiles = 0;
        $details = [];

        foreach ($groupedByFile as $fileName => $items) {
            $fileStartedAt = microtime(true);
            $filePath = $targetDirectory . '/' . $fileName;

            if (!is_file($filePath)) {
                throw new RuntimeException("Template file not found: {$fileName}");
            }

            if (!$config->isPathAllowed($filePath)) {
                throw new RuntimeException("AI access denied for file: {$fileName}");
            }

            $data = Yaml::parseFile($filePath);
            if (!is_array($data)) {
                throw new RuntimeException("Template file has invalid YAML: {$fileName}");
            }

            Log::info('ai.apply_prompts.file.start', [
                'target_domain' => $targetDomain,
                'file' => $fileName,
                'path' => $filePath,
                'field_count' => count($items),
            ]);

            $fileUpdates = 0;
            $updatedPaths = [];
            $rawHtmlWorkingPaths = [];
            $insertionAnchorMap = [];

            foreach ($items as $item) {
                $headExtraVirtualPath = $this->parseHeadExtraScriptVirtualPath($item['path']);
                if ($headExtraVirtualPath !== null) {
                    $headExtra = Arr::get($data, $headExtraVirtualPath['head_extra_path']);
                    if (!is_string($headExtra)) {
                        Log::warning('ai.apply_prompts.field.skipped_non_string_head_extra', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                        ]);
                        continue;
                    }

                    $currentValue = $this->readHeadExtraScriptVirtualValue(
                        headExtra: $headExtra,
                        scriptIndex: $headExtraVirtualPath['script_index']
                    );
                    if ($currentValue === null) {
                        Log::warning('ai.apply_prompts.field.skipped_head_extra_value_missing', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                        ]);
                        continue;
                    }

                    $fieldStartedAt = microtime(true);
                    Log::info('ai.apply_prompts.field.start', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                        'prompt_len' => mb_strlen($item['prompt']),
                        'current_len' => mb_strlen($currentValue),
                    ]);

                    try {
                        $newValue = $this->rewriteFieldWithAi(
                            currentValue: $currentValue,
                            prompt: $item['prompt'],
                            fieldPath: $item['path'],
                            config: $config,
                            sendCurrentValue: $item['send_current_value'] ?? true
                        );
                    } catch (\Throwable $e) {
                        Log::error('ai.apply_prompts.field.failed', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                            'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                            'exception_class' => $e::class,
                            'message' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    if ($newValue === $currentValue) {
                        Log::info('ai.apply_prompts.field.completed', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                            'changed' => false,
                            'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                        ]);
                        continue;
                    }

                    $updatedHeadExtra = $this->applyHeadExtraScriptVirtualValue(
                        headExtra: $headExtra,
                        scriptIndex: $headExtraVirtualPath['script_index'],
                        newValue: $newValue
                    );
                    if ($updatedHeadExtra === null || $updatedHeadExtra === $headExtra) {
                        Log::warning('ai.apply_prompts.field.skipped_head_extra_apply_failed', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                            'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                        ]);
                        continue;
                    }

                    Arr::set($data, $headExtraVirtualPath['head_extra_path'], $updatedHeadExtra);
                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = $item['path'];
                    Log::info('ai.apply_prompts.field.completed', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                        'changed' => true,
                        'new_len' => mb_strlen($newValue),
                        'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                    ]);
                    continue;
                }

                $virtualPath = $this->parseRawHtmlVirtualPath($item['path']);
                if ($virtualPath !== null) {
                    $rawHtml = Arr::get($data, $virtualPath['raw_html_path']);
                    if (!is_string($rawHtml)) {
                        Log::warning('ai.apply_prompts.field.skipped_non_string_raw_html', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                        ]);
                        continue;
                    }

                    $currentValue = $this->readRawHtmlVirtualValue($rawHtml, $virtualPath);
                    if ($currentValue === null) {
                        Log::warning('ai.apply_prompts.field.skipped_raw_html_value_missing', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                        ]);
                        continue;
                    }

                    $fieldStartedAt = microtime(true);
                    Log::info('ai.apply_prompts.field.start', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                        'prompt_len' => mb_strlen($item['prompt']),
                        'current_len' => mb_strlen($currentValue),
                    ]);

                    try {
                        $newValue = $this->rewriteFieldWithAi(
                            currentValue: $currentValue,
                            prompt: $item['prompt'],
                            fieldPath: $item['path'],
                            config: $config,
                            sendCurrentValue: $item['send_current_value'] ?? true
                        );
                    } catch (\Throwable $e) {
                        Log::error('ai.apply_prompts.field.failed', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                            'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                            'exception_class' => $e::class,
                            'message' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    if ($newValue === $currentValue) {
                        Log::info('ai.apply_prompts.field.completed', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                            'changed' => false,
                            'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                        ]);
                        continue;
                    }

                    $updatedRawHtml = $this->applyRawHtmlVirtualValue($rawHtml, $virtualPath, $newValue);
                    if ($updatedRawHtml === null || $updatedRawHtml === $rawHtml) {
                        Log::warning('ai.apply_prompts.field.skipped_raw_html_apply_failed', [
                            'file' => $fileName,
                            'field_path' => $item['path'],
                            'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                        ]);
                        continue;
                    }

                    Arr::set($data, $virtualPath['raw_html_path'], $updatedRawHtml);
                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = $item['path'];
                    Log::info('ai.apply_prompts.field.completed', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                        'changed' => true,
                        'new_len' => mb_strlen($newValue),
                        'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                    ]);
                    continue;
                }

                $currentValue = Arr::get($data, $item['path']);
                if (!is_string($currentValue)) {
                    Log::warning('ai.apply_prompts.field.skipped_non_string_value', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                    ]);
                    continue;
                }

                $fieldStartedAt = microtime(true);
                Log::info('ai.apply_prompts.field.start', [
                    'file' => $fileName,
                    'field_path' => $item['path'],
                    'prompt_len' => mb_strlen($item['prompt']),
                    'current_len' => mb_strlen($currentValue),
                ]);

                try {
                    $newValue = $this->rewriteFieldWithAi(
                        currentValue: $currentValue,
                        prompt: $item['prompt'],
                        fieldPath: $item['path'],
                        config: $config,
                        sendCurrentValue: $item['send_current_value'] ?? true
                    );
                } catch (\Throwable $e) {
                    Log::error('ai.apply_prompts.field.failed', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                        'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                        'exception_class' => $e::class,
                        'message' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                if ($newValue === $currentValue) {
                    Log::info('ai.apply_prompts.field.completed', [
                        'file' => $fileName,
                        'field_path' => $item['path'],
                        'changed' => false,
                        'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                    ]);
                    continue;
                }

                Arr::set($data, $item['path'], $newValue);
                $fileUpdates++;
                $updatedFields++;
                $updatedPaths[] = $item['path'];
                Log::info('ai.apply_prompts.field.completed', [
                    'file' => $fileName,
                    'field_path' => $item['path'],
                    'changed' => true,
                    'new_len' => mb_strlen($newValue),
                    'duration_ms' => $this->elapsedMilliseconds($fieldStartedAt),
                ]);
            }

            if ($fileUpdates > 0 && $fileName === self::RAW_HTML_TEXT_SCOPE_FILE) {
                $faqPageSynced = $this->syncFaqPageJsonLdFromRawHtmlSection($data, $updatedPaths);
                if ($faqPageSynced) {
                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = 'pages.0.og_data.head_extra.__script__.3';
                }

                $howToSynced = $this->syncHowToJsonLdFromRawHtmlSection($data, $updatedPaths);
                if ($howToSynced) {
                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = 'pages.0.og_data.head_extra.__script__.4';
                }
            }

            if ($fileUpdates > 0) {
                $updatedFiles++;
                $this->writeYamlDocument($filePath, $data);
            }

            $details[] = [
                'file' => $fileName,
                'updated_fields' => $fileUpdates,
                'updated_paths' => $updatedPaths,
            ];

            Log::info('ai.apply_prompts.file.completed', [
                'target_domain' => $targetDomain,
                'file' => $fileName,
                'updated_fields' => $fileUpdates,
                'updated_paths_count' => count($updatedPaths),
                'duration_ms' => $this->elapsedMilliseconds($fileStartedAt),
            ]);
        }

        Log::info('ai.apply_prompts.completed', [
            'target_domain' => $targetDomain,
            'updated_fields' => $updatedFields,
            'updated_files' => $updatedFiles,
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
        ]);

        return [
            'updated_fields' => $updatedFields,
            'updated_files' => $updatedFiles,
            'details' => $details,
        ];
    }

    /**
     * @param  array<int, array{file?:string,path?:string,value?:string}>  $fieldEdits
     * @return array{updated_fields:int,updated_files:int,details:array<int,array<string,mixed>>}
     */
    public function applyFieldEditsToDomain(string $targetDomain, array $fieldEdits): array
    {
        $targetDirectory = $this->resolveTargetDirectory($targetDomain);
        if (!is_dir($targetDirectory)) {
            throw new RuntimeException("Target template directory was not found: {$targetDirectory}");
        }

        $normalizedEdits = $this->normalizeFieldEdits($fieldEdits);
        if ($normalizedEdits === []) {
            return [
                'updated_fields' => 0,
                'updated_files' => 0,
                'details' => [],
            ];
        }

        $groupedByFile = [];
        foreach ($normalizedEdits as $item) {
            $groupedByFile[$item['file']][] = $item;
        }

        $updatedFields = 0;
        $updatedFiles = 0;
        $details = [];

        foreach ($groupedByFile as $fileName => $items) {
            $filePath = $targetDirectory . '/' . $fileName;

            if (!is_file($filePath)) {
                throw new RuntimeException("Template file not found: {$fileName}");
            }

            $data = Yaml::parseFile($filePath);
            if (!is_array($data)) {
                throw new RuntimeException("Template file has invalid YAML: {$fileName}");
            }

            $fileUpdates = 0;
            $updatedPaths = [];

            foreach ($items as $item) {
                $headExtraVirtualPath = $this->parseHeadExtraScriptVirtualPath($item['path']);
                if ($headExtraVirtualPath !== null) {
                    $headExtra = Arr::get($data, $headExtraVirtualPath['head_extra_path']);
                    if (!is_string($headExtra)) {
                        continue;
                    }

                    $currentValue = $this->readHeadExtraScriptVirtualValue(
                        headExtra: $headExtra,
                        scriptIndex: $headExtraVirtualPath['script_index']
                    );
                    if ($currentValue === null) {
                        continue;
                    }

                    $newValue = $item['value'];
                    if ($newValue === $currentValue) {
                        continue;
                    }

                    $updatedHeadExtra = $this->applyHeadExtraScriptVirtualValue(
                        headExtra: $headExtra,
                        scriptIndex: $headExtraVirtualPath['script_index'],
                        newValue: $newValue
                    );
                    if ($updatedHeadExtra === null || $updatedHeadExtra === $headExtra) {
                        continue;
                    }

                    Arr::set($data, $headExtraVirtualPath['head_extra_path'], $updatedHeadExtra);
                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = $item['path'];
                    continue;
                }

                $virtualPath = $this->parseRawHtmlVirtualPath($item['path']);
                if ($virtualPath !== null) {
                    $rawHtml = Arr::get($data, $virtualPath['raw_html_path']);
                    if (!is_string($rawHtml)) {
                        continue;
                    }

                    $currentValue = $this->readRawHtmlVirtualValue($rawHtml, $virtualPath);
                    if ($currentValue === null) {
                        continue;
                    }

                    $newValue = $item['value'];
                    if ($newValue === $currentValue) {
                        continue;
                    }

                    $updatedRawHtml = $this->applyRawHtmlVirtualValue($rawHtml, $virtualPath, $newValue);
                    if ($updatedRawHtml === null || $updatedRawHtml === $rawHtml) {
                        continue;
                    }

                    Arr::set($data, $virtualPath['raw_html_path'], $updatedRawHtml);
                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = $item['path'];
                    continue;
                }

                $currentValue = Arr::get($data, $item['path']);
                if (!is_string($currentValue)) {
                    $headMetaOrLinkPath = $this->parseHeadMetaOrLinkCreationPath($item['path']);
                    if ($headMetaOrLinkPath !== null) {
                        $applied = $this->applyHeadMetaOrLinkCreationEdit($data, $headMetaOrLinkPath, $item['value']);
                        if ($applied) {
                            $fileUpdates++;
                            $updatedFields++;
                            $updatedPaths[] = $item['path'];
                        }
                    }
                    continue;
                }

                $newValue = $item['value'];
                if ($newValue === $currentValue) {
                    continue;
                }

                Arr::set($data, $item['path'], $newValue);
                $fileUpdates++;
                $updatedFields++;
                $updatedPaths[] = $item['path'];
            }

            if ($fileUpdates > 0) {
                $updatedFiles++;
                $this->writeYamlDocument($filePath, $data);
            }

            $details[] = [
                'file' => $fileName,
                'updated_fields' => $fileUpdates,
                'updated_paths' => $updatedPaths,
            ];
        }

        return [
            'updated_fields' => $updatedFields,
            'updated_files' => $updatedFiles,
            'details' => $details,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $blockOperations
     * @return array{updated_fields:int,updated_files:int,details:array<int,array<string,mixed>>}
     */
    public function applyBlockOperationsToDomain(string $targetDomain, array $blockOperations, ?AiAgentConfig $config = null): array
    {
        $targetDirectory = $this->resolveTargetDirectory($targetDomain);
        if (!is_dir($targetDirectory)) {
            throw new RuntimeException("Target template directory was not found: {$targetDirectory}");
        }

        $normalizedOperations = $this->normalizeBlockOperations($blockOperations);
        if ($normalizedOperations === []) {
            return [
                'updated_fields' => 0,
                'updated_files' => 0,
                'details' => [],
            ];
        }

        $groupedByFile = [];
        foreach ($normalizedOperations as $item) {
            $groupedByFile[$item['file']][] = $item;
        }

        $updatedFields = 0;
        $updatedFiles = 0;
        $details = [];

        foreach ($groupedByFile as $fileName => $items) {
            $filePath = $targetDirectory . '/' . $fileName;
            if (!is_file($filePath)) {
                throw new RuntimeException("Template file not found: {$fileName}");
            }

            $data = Yaml::parseFile($filePath);
            if (!is_array($data)) {
                throw new RuntimeException("Template file has invalid YAML: {$fileName}");
            }

            $fileUpdates = 0;
            $updatedPaths = [];

            foreach ($items as $operation) {
                $sectionAction = (string) ($operation['action'] ?? '');
                if (in_array($sectionAction, ['add_section', 'remove_section'], true)) {
                    $sectionChanged = $this->applyRawHtmlSectionOperation($data, $operation);
                    if (!$sectionChanged) {
                        Log::warning('ai.block_ops.operation_skipped', [
                            'file' => $fileName,
                            'section_path' => (string) ($operation['section_path'] ?? ''),
                            'action' => $sectionAction,
                            'reason' => 'section_not_applied',
                        ]);
                        continue;
                    }

                    $fileUpdates++;
                    $updatedFields++;
                    $updatedPaths[] = (string) ($operation['section_path'] ?? '') . ' [' . $sectionAction . ']';
                    continue;
                }

                $sectionPath = (string) ($operation['section_path'] ?? '');
                $sectionInfo = $this->parseRawHtmlSectionPath($sectionPath);
                if ($sectionInfo === null) {
                    continue;
                }

                $rawHtml = Arr::get($data, $sectionInfo['raw_html_path']);
                if (!is_string($rawHtml)) {
                    continue;
                }

                $module = (string) (Arr::get($data, $sectionPath . '.module') ?? '');
                if (!isset($rawHtmlWorkingPaths[$sectionInfo['raw_html_path']])) {
                    $rawHtml = $this->decorateRawHtmlWithStableAnchorKeys($rawHtml, $module);
                    Arr::set($data, $sectionInfo['raw_html_path'], $rawHtml);
                    $rawHtmlWorkingPaths[$sectionInfo['raw_html_path']] = true;
                }
                $operation = $this->rewriteBlockOperationValues($operation, $config);
                $anchorChainKey = $this->makeInsertionAnchorChainKey($fileName, $operation);
                if ($anchorChainKey !== null && isset($insertionAnchorMap[$anchorChainKey])) {
                    $operation['anchor_key'] = $insertionAnchorMap[$anchorChainKey];
                    if (($operation['anchor_position'] ?? 'after') === 'before') {
                        $operation['anchor_position'] = 'after';
                    }
                }

                $operationResult = $this->applyRawHtmlBlockOperation($rawHtml, $operation, $module);
                if (!is_array($operationResult)) {
                    Log::warning('ai.block_ops.operation_skipped', [
                        'file' => $fileName,
                        'section_path' => $sectionPath,
                        'action' => (string) ($operation['action'] ?? ''),
                        'reason' => 'not_applied',
                    ]);
                    continue;
                }

                $updatedRawHtml = (string) ($operationResult['raw_html'] ?? '');
                if ($updatedRawHtml === $rawHtml) {
                    Log::warning('ai.block_ops.operation_skipped', [
                        'file' => $fileName,
                        'section_path' => $sectionPath,
                        'action' => (string) ($operation['action'] ?? ''),
                        'reason' => 'no_change',
                    ]);
                    continue;
                }

                Arr::set($data, $sectionInfo['raw_html_path'], $updatedRawHtml);
                if ($anchorChainKey !== null && isset($operationResult['inserted_anchor_key'])) {
                    $insertionAnchorMap[$anchorChainKey] = (string) $operationResult['inserted_anchor_key'];
                }
                $fileUpdates++;
                $updatedFields++;
                $updatedPaths[] = $sectionPath . '.raw_html [' . $operation['action'] . ']';
            }

            if ($fileUpdates > 0) {
                foreach (array_keys($rawHtmlWorkingPaths) as $rawHtmlPath) {
                    $value = Arr::get($data, $rawHtmlPath);
                    if (!is_string($value)) {
                        continue;
                    }

                    Arr::set($data, $rawHtmlPath, $this->stripStableAnchorAttributes($value));
                }

                $updatedFiles++;
                $this->writeYamlDocument($filePath, $data);
            }

            $details[] = [
                'file' => $fileName,
                'updated_fields' => $fileUpdates,
                'updated_paths' => $updatedPaths,
            ];
        }

        return [
            'updated_fields' => $updatedFields,
            'updated_files' => $updatedFiles,
            'details' => $details,
        ];
    }

    private function makeInsertionAnchorChainKey(string $fileName, array $operation): ?string
    {
        $action = (string) ($operation['action'] ?? '');
        if (!in_array($action, ['add_text', 'add_list_block', 'add_table_block'], true)) {
            return null;
        }

        $sectionPath = trim((string) ($operation['section_path'] ?? ''));
        $anchorKey = trim((string) ($operation['anchor_key'] ?? ''));
        if ($sectionPath === '' || $anchorKey === '') {
            return null;
        }

        $anchorPosition = strtolower(trim((string) ($operation['anchor_position'] ?? 'after')));
        if (!in_array($anchorPosition, ['before', 'after'], true)) {
            $anchorPosition = 'after';
        }

        return implode('|', [$fileName, $sectionPath, $anchorKey, $anchorPosition]);
    }

    private function rewriteFieldWithAi(
        string $currentValue,
        string $prompt,
        string $fieldPath,
        AiAgentConfig $config,
        bool $sendCurrentValue = true
    ): string {
        $isHtmlField = str_ends_with($fieldPath, '.raw_html');
        $isJsonLdScriptField = str_contains($fieldPath, '.og_data.head_extra.__script__.');
        $isMetaTitleField = str_ends_with($fieldPath, '.meta_title');
        $isMetaDescriptionField = str_ends_with($fieldPath, '.meta_description');

        $systemMessage = 'You rewrite one field value. Return only final rewritten value text without JSON, markdown, or explanations.';
        if ($isHtmlField) {
            $systemMessage .= ' Preserve valid HTML structure. Do not remove required tags.';
        }
        if ($isJsonLdScriptField) {
            $systemMessage .= ' Preserve a full <script type="application/ld+json">...</script> block. Keep valid JSON-LD.';
        }
        if ($isMetaTitleField) {
            $systemMessage .= ' For meta_title fields: produce a specific SEO title, max 60 characters, without placeholder text or meta-commentary.';
        }
        if ($isMetaDescriptionField) {
            $systemMessage .= ' For meta_description fields: produce a specific SEO description, max 160 characters, without placeholder text or meta-commentary.';
        }

        $tone = trim((string) $config->tone);
        if ($tone !== '') {
            $systemMessage .= " Target tone: {$tone}.";
        }

        $userMessage = "Field path: {$fieldPath}\n";
        $userMessage .= "Instruction: {$prompt}\n";
        if ($sendCurrentValue) {
            $userMessage .= "Current value:\n{$currentValue}";
        }

        $generatedValue = $this->callConfiguredModel($config, $systemMessage, $userMessage);

        if ($isHtmlField || $isJsonLdScriptField) {
            return $generatedValue;
        }

        return $this->stripWrappingQuotes($generatedValue);
    }

    private function stripWrappingQuotes(string $value): string
    {
        $normalized = trim($value);
        if (strlen($normalized) < 2) {
            return $normalized;
        }

        $firstChar = $normalized[0];
        $lastChar = $normalized[strlen($normalized) - 1];

        $isDoubleQuoted = $firstChar === '"' && $lastChar === '"';
        $isSingleQuoted = $firstChar === "'" && $lastChar === "'";

        if (!$isDoubleQuoted && !$isSingleQuoted) {
            return $normalized;
        }

        return trim(substr($normalized, 1, -1));
    }

    private function callConfiguredModel(
        AiAgentConfig $config,
        string $systemMessage,
        string $userMessage
    ): string {
        $provider = strtolower(trim((string) $config->provider));
        if ($provider === '') {
            $provider = 'openai';
        }

        if ($provider === 'anthropic') {
            return $this->callAnthropic($config, $systemMessage, $userMessage);
        }

        $openAiCompatibleProviders = array_keys(self::OPENAI_COMPATIBLE_BASE_URLS);
        $openAiCompatibleProviders[] = 'custom';
        $openAiCompatibleProviders[] = 'azure-openai';

        if (in_array($provider, $openAiCompatibleProviders, true)) {
            return $this->callOpenAiCompatible($config, $provider, $systemMessage, $userMessage);
        }

        throw new RuntimeException("Provider '{$provider}' is saved, but direct API integration is not implemented for it.");
    }

    private function callOpenAiCompatible(
        AiAgentConfig $config,
        string $provider,
        string $systemMessage,
        string $userMessage
    ): string {
        // TODO(PROD): remove temporary provider-level request/response debug telemetry.
        $apiKey = trim((string) $config->api_key);
        $baseUrl = trim((string) $config->api_base_url);

        // If OpenRouter key is used while provider is still set to "openai",
        // route to OpenRouter automatically to avoid false 401 from OpenAI.
        if ($baseUrl === '' && $provider === 'openai' && str_starts_with($apiKey, 'sk-or-v1-')) {
            $baseUrl = self::OPENAI_COMPATIBLE_BASE_URLS['openrouter'];
        }

        if ($baseUrl === '') {
            $baseUrl = self::OPENAI_COMPATIBLE_BASE_URLS[$provider] ?? '';
        }
        if ($baseUrl === '') {
            throw new RuntimeException("Provider '{$provider}' requires api_base_url.");
        }

        $model = trim((string) $config->model_name);
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $config->temperature ?? 0.7,
        ];

        if ($config->max_tokens !== null) {
            $payload['max_tokens'] = $config->max_tokens;
        }
        if ($config->top_p !== null) {
            $payload['top_p'] = $config->top_p;
        }
        if ($config->frequency_penalty !== null) {
            $payload['frequency_penalty'] = $config->frequency_penalty;
        }
        if ($config->presence_penalty !== null) {
            $payload['presence_penalty'] = $config->presence_penalty;
        }

        $requestStartedAt = microtime(true);
        Log::info('ai.provider.request.start', [
            'provider' => $provider,
            'base_url' => $baseUrl,
            'model_name' => $model,
            'prompt_len' => mb_strlen($userMessage),
        ]);

        $response = Http::withToken($apiKey)
            ->connectTimeout(15)
            ->timeout(120)
            ->acceptJson()
            ->post(rtrim($baseUrl, '/') . '/chat/completions', $payload);

        if ($response->failed()) {
            Log::error('ai.provider.request.failed', [
                'provider' => $provider,
                'base_url' => $baseUrl,
                'model_name' => $model,
                'status' => $response->status(),
                'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
                'response_body_preview' => mb_substr(trim($response->body()), 0, 1500),
            ]);
            throw new RuntimeException('OpenAI-compatible API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI-compatible API returned empty response.');
        }

        Log::info('ai.provider.request.completed', [
            'provider' => $provider,
            'base_url' => $baseUrl,
            'model_name' => $model,
            'status' => $response->status(),
            'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
            'response_len' => mb_strlen(trim($content)),
        ]);

        return trim($content);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBlockOperations(array $blockOperations): array
    {
        $normalized = [];

        foreach ($blockOperations as $item) {
            $file = basename((string) ($item['file'] ?? ''));
            $sectionPath = trim((string) ($item['section_path'] ?? ''));
            $action = trim((string) ($item['action'] ?? ''));

            if ($file !== self::RAW_HTML_TEXT_SCOPE_FILE || $sectionPath === '' || $action === '') {
                continue;
            }

            if ($this->parseRawHtmlSectionPath($sectionPath) === null) {
                continue;
            }

            if (str_contains($sectionPath, '..')) {
                continue;
            }

            if ($action === 'remove_block') {
                $targetKey = trim((string) ($item['target_key'] ?? ''));
                if ($targetKey === '') {
                    continue;
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'target_key' => $targetKey,
                ];
                continue;
            }

            if ($action === 'remove_last_list_item') {
                $containerKey = trim((string) ($item['container_key'] ?? ''));
                if ($containerKey === '') {
                    continue;
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'container_key' => $containerKey,
                ];
                continue;
            }

            if ($action === 'remove_last_table_row') {
                $containerKey = trim((string) ($item['container_key'] ?? ''));
                if ($containerKey === '') {
                    continue;
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'container_key' => $containerKey,
                ];
                continue;
            }

            if ($action === 'remove_section') {
                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                ];
                continue;
            }

            if ($action === 'add_section') {
                $module = trim((string) ($item['module'] ?? ''));
                if ($module === '') {
                    continue;
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'module' => $module,
                ];
                continue;
            }

            if ($action === 'add_text') {
                $tag = strtolower(trim((string) ($item['tag'] ?? '')));
                $value = trim((string) ($item['value'] ?? ''));
                if (!in_array($tag, self::RAW_HTML_ADDABLE_TEXT_TAGS, true) || $value === '') {
                    continue;
                }

                $anchorPosition = strtolower(trim((string) ($item['anchor_position'] ?? 'after')));
                if (!in_array($anchorPosition, ['before', 'after'], true)) {
                    $anchorPosition = 'after';
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'tag' => $tag,
                    'value' => $value,
                    'value_prompt' => trim((string) ($item['value_prompt'] ?? '')),
                    'class' => trim((string) ($item['class'] ?? '')),
                    'anchor_key' => trim((string) ($item['anchor_key'] ?? '')),
                    'anchor_position' => $anchorPosition,
                ];
                continue;
            }

            if ($action === 'add_list_block') {
                $listTag = strtolower(trim((string) ($item['list_tag'] ?? '')));
                if (!in_array($listTag, ['ul', 'ol'], true)) {
                    continue;
                }

                $items = [];
                if (isset($item['items']) && is_array($item['items'])) {
                    foreach ($item['items'] as $entry) {
                        $text = trim((string) $entry);
                        if ($text !== '') {
                            $items[] = $text;
                        }
                    }
                }

                $anchorPosition = strtolower(trim((string) ($item['anchor_position'] ?? 'after')));
                if (!in_array($anchorPosition, ['before', 'after'], true)) {
                    $anchorPosition = 'after';
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'list_tag' => $listTag,
                    'items' => $items,
                    'item_prompts' => $this->normalizeStringList($item['item_prompts'] ?? []),
                    'class' => trim((string) ($item['class'] ?? '')),
                    'item_class' => trim((string) ($item['item_class'] ?? '')),
                    'aria_label' => trim((string) ($item['aria_label'] ?? '')),
                    'anchor_key' => trim((string) ($item['anchor_key'] ?? '')),
                    'anchor_position' => $anchorPosition,
                ];
                continue;
            }

            if ($action === 'add_list_item') {
                $containerKey = trim((string) ($item['container_key'] ?? ''));
                $value = trim((string) ($item['value'] ?? ''));
                if ($containerKey === '' || $value === '') {
                    continue;
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'container_key' => $containerKey,
                    'value' => $value,
                    'value_prompt' => trim((string) ($item['value_prompt'] ?? '')),
                    'class' => trim((string) ($item['class'] ?? '')),
                ];
                continue;
            }

            if ($action === 'add_table_block') {
                $headers = [];
                if (isset($item['headers']) && is_array($item['headers'])) {
                    foreach ($item['headers'] as $entry) {
                        $text = trim((string) $entry);
                        if ($text !== '') {
                            $headers[] = $text;
                        }
                    }
                }

                $rows = [];
                if (isset($item['rows']) && is_array($item['rows'])) {
                    foreach ($item['rows'] as $row) {
                        if (!is_array($row)) {
                            continue;
                        }

                        $normalizedRow = [];
                        foreach ($row as $entry) {
                            $normalizedRow[] = trim((string) $entry);
                        }

                        if (array_filter($normalizedRow, fn (string $value): bool => $value !== '') !== []) {
                            $rows[] = $normalizedRow;
                        }
                    }
                }

                $anchorPosition = strtolower(trim((string) ($item['anchor_position'] ?? 'after')));
                if (!in_array($anchorPosition, ['before', 'after'], true)) {
                    $anchorPosition = 'after';
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'headers' => $headers,
                    'rows' => $rows,
                    'header_prompts' => $this->normalizeStringList($item['header_prompts'] ?? []),
                    'row_prompts' => $this->normalizeStringRows($item['row_prompts'] ?? []),
                    'class' => trim((string) ($item['class'] ?? '')),
                    'aria_label' => trim((string) ($item['aria_label'] ?? '')),
                    'row_class' => trim((string) ($item['row_class'] ?? '')),
                    'cell_class' => trim((string) ($item['cell_class'] ?? '')),
                    'anchor_key' => trim((string) ($item['anchor_key'] ?? '')),
                    'anchor_position' => $anchorPosition,
                ];
                continue;
            }

            if ($action === 'add_card_feature') {
                $containerKey = trim((string) ($item['container_key'] ?? ''));
                $text = trim((string) ($item['text'] ?? ''));
                if ($containerKey === '' || $text === '') {
                    continue;
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'container_key' => $containerKey,
                    'text' => $text,
                    'text_prompt' => trim((string) ($item['text_prompt'] ?? '')),
                    'icon_src' => trim((string) ($item['icon_src'] ?? '/assets/svg/')),
                    'icon_alt' => trim((string) ($item['icon_alt'] ?? '')),
                    'class' => trim((string) ($item['class'] ?? '')),
                    'icon_class' => trim((string) ($item['icon_class'] ?? '')),
                    'text_class' => trim((string) ($item['text_class'] ?? '')),
                ];
                continue;
            }

            if ($action === 'add_table_row') {
                $containerKey = trim((string) ($item['container_key'] ?? ''));
                $col1 = trim((string) ($item['col1'] ?? ''));
                $col2 = trim((string) ($item['col2'] ?? ''));
                if ($containerKey === '' || ($col1 === '' && $col2 === '')) {
                    continue;
                }

                $anchorPosition = strtolower(trim((string) ($item['anchor_position'] ?? 'after')));
                if (!in_array($anchorPosition, ['before', 'after'], true)) {
                    $anchorPosition = 'after';
                }

                $normalized[] = [
                    'file' => $file,
                    'section_path' => $sectionPath,
                    'action' => $action,
                    'container_key' => $containerKey,
                    'col1' => $col1,
                    'col2' => $col2,
                    'col1_prompt' => trim((string) ($item['col1_prompt'] ?? '')),
                    'col2_prompt' => trim((string) ($item['col2_prompt'] ?? '')),
                    'row_class' => trim((string) ($item['row_class'] ?? '')),
                    'cell_class' => trim((string) ($item['cell_class'] ?? '')),
                    'anchor_key' => trim((string) ($item['anchor_key'] ?? '')),
                    'anchor_position' => $anchorPosition,
                ];
            }
        }

        return $normalized;
    }

    /**
     * @param  mixed  $items
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_map(
            fn ($item): string => trim((string) $item),
            $items
        ));
    }

    /**
     * @param  mixed  $rows
     * @return array<int, array<int, string>>
     */
    private function normalizeStringRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $normalized[] = [];
                continue;
            }

            $normalized[] = array_values(array_map(
                fn ($item): string => trim((string) $item),
                $row
            ));
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function rewriteBlockOperationValues(array $operation, ?AiAgentConfig $config): array
    {
        if (!$config instanceof AiAgentConfig) {
            return $operation;
        }

        $action = (string) ($operation['action'] ?? '');
        $basePath = (string) ($operation['section_path'] ?? 'pages.0.sections.0') . '.raw_html.__new_block__';

        if ($action === 'add_text') {
            $prompt = trim((string) ($operation['value_prompt'] ?? ''));
            if ($prompt !== '') {
                $operation['value'] = $this->rewriteFieldWithAi(
                    currentValue: (string) ($operation['value'] ?? ''),
                    prompt: $prompt,
                    fieldPath: $basePath . '.' . (string) ($operation['tag'] ?? 'text'),
                    config: $config
                );
            }

            return $operation;
        }

        if ($action === 'add_list_item') {
            $prompt = trim((string) ($operation['value_prompt'] ?? ''));
            if ($prompt !== '') {
                $operation['value'] = $this->rewriteFieldWithAi(
                    currentValue: (string) ($operation['value'] ?? ''),
                    prompt: $prompt,
                    fieldPath: $basePath . '.li',
                    config: $config
                );
            }

            return $operation;
        }

        if ($action === 'add_card_feature') {
            $prompt = trim((string) ($operation['text_prompt'] ?? ''));
            if ($prompt !== '') {
                $operation['text'] = $this->rewriteFieldWithAi(
                    currentValue: (string) ($operation['text'] ?? ''),
                    prompt: $prompt,
                    fieldPath: $basePath . '.card_feature_text',
                    config: $config
                );
            }

            return $operation;
        }

        if ($action === 'add_table_row') {
            foreach (['col1', 'col2'] as $column) {
                $prompt = trim((string) ($operation[$column . '_prompt'] ?? ''));
                if ($prompt === '') {
                    continue;
                }

                $operation[$column] = $this->rewriteFieldWithAi(
                    currentValue: (string) ($operation[$column] ?? ''),
                    prompt: $prompt,
                    fieldPath: $basePath . ".{$column}",
                    config: $config
                );
            }

            return $operation;
        }

        if ($action === 'add_list_block') {
            $items = isset($operation['items']) && is_array($operation['items']) ? $operation['items'] : [];
            $prompts = isset($operation['item_prompts']) && is_array($operation['item_prompts']) ? $operation['item_prompts'] : [];
            foreach ($items as $index => $value) {
                $prompt = trim((string) ($prompts[$index] ?? ''));
                if ($prompt === '') {
                    continue;
                }

                $items[$index] = $this->rewriteFieldWithAi(
                    currentValue: (string) $value,
                    prompt: $prompt,
                    fieldPath: $basePath . ".li.{$index}",
                    config: $config
                );
            }
            $operation['items'] = $items;

            return $operation;
        }

        if ($action === 'add_table_block') {
            $headers = isset($operation['headers']) && is_array($operation['headers']) ? $operation['headers'] : [];
            $headerPrompts = isset($operation['header_prompts']) && is_array($operation['header_prompts']) ? $operation['header_prompts'] : [];
            foreach ($headers as $index => $value) {
                $prompt = trim((string) ($headerPrompts[$index] ?? ''));
                if ($prompt === '') {
                    continue;
                }

                $headers[$index] = $this->rewriteFieldWithAi(
                    currentValue: (string) $value,
                    prompt: $prompt,
                    fieldPath: $basePath . ".table.header.{$index}",
                    config: $config
                );
            }
            $operation['headers'] = $headers;

            $rows = isset($operation['rows']) && is_array($operation['rows']) ? $operation['rows'] : [];
            $rowPrompts = isset($operation['row_prompts']) && is_array($operation['row_prompts']) ? $operation['row_prompts'] : [];
            foreach ($rows as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }

                foreach ($row as $cellIndex => $value) {
                    $prompt = trim((string) ($rowPrompts[$rowIndex][$cellIndex] ?? ''));
                    if ($prompt === '') {
                        continue;
                    }

                    $rows[$rowIndex][$cellIndex] = $this->rewriteFieldWithAi(
                        currentValue: (string) $value,
                        prompt: $prompt,
                        fieldPath: $basePath . ".table.row.{$rowIndex}.cell.{$cellIndex}",
                        config: $config
                    );
                }
            }
            $operation['rows'] = $rows;

            return $operation;
        }

        return $operation;
    }

    private function callAnthropic(
        AiAgentConfig $config,
        string $systemMessage,
        string $userMessage
    ): string {
        $model = trim((string) $config->model_name);
        if ($model === '') {
            $model = 'claude-3-5-sonnet-latest';
        }

        $payload = [
            'model' => $model,
            'system' => $systemMessage,
            'max_tokens' => $config->max_tokens ?? 2048,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $config->temperature ?? 0.7,
        ];

        if ($config->top_p !== null) {
            $payload['top_p'] = $config->top_p;
        }

        $requestStartedAt = microtime(true);
        Log::info('ai.provider.request.start', [
            'provider' => 'anthropic',
            'base_url' => 'https://api.anthropic.com/v1/messages',
            'model_name' => $model,
            'prompt_len' => mb_strlen($userMessage),
        ]);

        $response = Http::withHeaders([
            'x-api-key' => (string) $config->api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->connectTimeout(15)->timeout(120)->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            Log::error('ai.provider.request.failed', [
                'provider' => 'anthropic',
                'base_url' => 'https://api.anthropic.com/v1/messages',
                'model_name' => $model,
                'status' => $response->status(),
                'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
                'response_body_preview' => mb_substr(trim($response->body()), 0, 1500),
            ]);
            throw new RuntimeException('Anthropic API request failed: ' . $response->body());
        }

        $content = $response->json('content.0.text');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Anthropic API returned empty response.');
        }

        Log::info('ai.provider.request.completed', [
            'provider' => 'anthropic',
            'base_url' => 'https://api.anthropic.com/v1/messages',
            'model_name' => $model,
            'status' => $response->status(),
            'duration_ms' => $this->elapsedMilliseconds($requestStartedAt),
            'response_len' => mb_strlen(trim($content)),
        ]);

        return trim($content);
    }

    /**
     * @param  array<int, array{file?:string,path?:string,prompt?:string}>  $fieldPrompts
     * @return array<int, array{file:string,path:string,prompt:string,send_current_value:bool}>
     */
    private function normalizeFieldPrompts(array $fieldPrompts): array
    {
        $normalized = [];

        foreach ($fieldPrompts as $item) {
            $file = basename((string) ($item['file'] ?? ''));
            $path = trim((string) ($item['path'] ?? ''));
            $prompt = trim((string) ($item['prompt'] ?? ''));

            if ($file === '' || $path === '' || $prompt === '') {
                continue;
            }

            if (str_contains($path, '..')) {
                continue;
            }

            if ($file === self::RAW_HTML_TEXT_SCOPE_FILE && str_ends_with($path, '.raw_html')) {
                continue;
            }

            $normalized[] = [
                'file' => $file,
                'path' => $path,
                'prompt' => $prompt,
                'send_current_value' => array_key_exists('send_current_value', $item)
                    ? filter_var($item['send_current_value'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false
                    : true,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{file?:string,path?:string,value?:string}>  $fieldEdits
     * @return array<int, array{file:string,path:string,value:string}>
     */
    private function normalizeFieldEdits(array $fieldEdits): array
    {
        $normalized = [];

        foreach ($fieldEdits as $item) {
            $file = basename((string) ($item['file'] ?? ''));
            $path = trim((string) ($item['path'] ?? ''));

            if (!array_key_exists('value', $item)) {
                continue;
            }

            $value = (string) $item['value'];

            if ($file === '' || $path === '') {
                continue;
            }

            if (str_contains($path, '..')) {
                continue;
            }

            if ($file === self::RAW_HTML_TEXT_SCOPE_FILE && str_ends_with($path, '.raw_html')) {
                continue;
            }

            $normalized[] = [
                'file' => $file,
                'path' => $path,
                'value' => $value,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{page_index:int,section_index:int,raw_html_path:string}|null
     */
    private function parseRawHtmlSectionPath(string $sectionPath): ?array
    {
        if (!preg_match(self::RAW_HTML_SECTION_PATH_PATTERN, $sectionPath, $matches)) {
            return null;
        }

        $pageIndex = (int) ($matches[1] ?? -1);
        $sectionIndex = (int) ($matches[2] ?? -1);
        if ($pageIndex < 0 || $sectionIndex < 0) {
            return null;
        }

        return [
            'page_index' => $pageIndex,
            'section_index' => $sectionIndex,
            'raw_html_path' => "pages.{$pageIndex}.sections.{$sectionIndex}.raw_html",
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $operation
     */
    private function applyRawHtmlSectionOperation(array &$data, array $operation): bool
    {
        $sectionPath = (string) ($operation['section_path'] ?? '');
        $sectionInfo = $this->parseRawHtmlSectionPath($sectionPath);
        if ($sectionInfo === null) {
            return false;
        }

        $pagePath = "pages.{$sectionInfo['page_index']}";
        $sectionsPath = "{$pagePath}.sections";
        $sections = Arr::get($data, $sectionsPath);
        if (!is_array($sections)) {
            return false;
        }

        $sectionIndex = $sectionInfo['section_index'];
        $action = (string) ($operation['action'] ?? '');

        if ($action === 'remove_section') {
            if (!array_key_exists($sectionIndex, $sections)) {
                return false;
            }

            array_splice($sections, $sectionIndex, 1);
            Arr::set($data, $sectionsPath, array_values($sections));
            return true;
        }

        if ($action !== 'add_section') {
            return false;
        }

        $module = trim((string) ($operation['module'] ?? ''));
        if ($module === '') {
            return false;
        }

        $newSection = $this->buildSectionForModule($sections, $module);
        array_splice($sections, $sectionIndex + 1, 0, [$newSection]);
        Arr::set($data, $sectionsPath, array_values($sections));

        return true;
    }

    /**
     * @param  array<int, mixed>  $sections
     * @return array<string, mixed>
     */
    private function buildSectionForModule(array $sections, string $module): array
    {
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $sectionModule = (string) ($section['module'] ?? $section['module_key'] ?? '');
            if ($sectionModule === $module) {
                return $section;
            }
        }

        $rawHtml = '';
        $defaultPath = resource_path("views/defaults/modules/{$module}.html");
        if (is_file($defaultPath)) {
            $contents = file_get_contents($defaultPath);
            $rawHtml = is_string($contents) ? $contents : '';
        }

        return [
            'module' => $module,
            'module_key' => $module,
            'heading' => $this->humanizeModuleName($module),
            'raw_html' => $rawHtml,
            'render_mode' => 'raw_html',
        ];
    }

    private function humanizeModuleName(string $module): string
    {
        $normalized = trim(str_replace(['-', '_'], ' ', $module));
        if ($normalized === '') {
            return 'Section';
        }

        return ucwords($normalized);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractRawHtmlBlockControls(string $rawHtml, string $sectionPath, string $module): ?array
    {
        [$dom, $root, $xpath] = $this->parseHtmlFragment($rawHtml);
        if (!$dom || !$root || !$xpath) {
            return null;
        }

        $container = $this->resolveRawHtmlSectionContainer($root);
        $removableBlocks = [];
        $listContainers = [];
        $tableContainers = [];
        $cardFeatureContainers = [];
        $tagDefaults = [];

        foreach (self::RAW_HTML_ADDABLE_TEXT_TAGS as $tag) {
            $tagDefaults[$tag] = $this->inferFirstClassByTag($container, $tag, $module);
        }

        $nodes = $xpath->query('.//*', $container);
        if ($nodes === false) {
            return null;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $node)) {
                continue;
            }

            $tag = strtolower($node->tagName);
            $key = $this->shortHash('struct|' . $this->buildNodePath($node));
            $class = trim((string) $node->getAttribute('class'));

            if (in_array($tag, self::RAW_HTML_REMOVABLE_TAGS, true)) {
                $preview = $this->previewText($this->normalizeEditableText((string) $node->textContent));
                $label = "{$module} :: <{$tag}>";
                if ($preview !== '') {
                    $label .= ' — ' . $preview;
                }

                $removableBlocks[] = [
                    'key' => $key,
                    'tag' => $tag,
                    'label' => $label,
                    'class' => $class,
                ];
            }

            if (in_array($tag, ['ul', 'ol'], true)) {
                $itemClass = $this->firstDescendantClass($node, 'li');
                $ariaLabel = trim((string) $node->getAttribute('aria-label'));
                $listLabel = $ariaLabel !== '' ? $ariaLabel : ($class !== '' ? $class : "{$tag} list");
                $isCardFeature = str_contains($class, 'card__features') || str_contains($itemClass, 'card__feature');

                $listItem = [
                    'key' => $key,
                    'tag' => $tag,
                    'label' => $listLabel,
                    'class' => $class,
                    'item_class' => $itemClass,
                    'aria_label' => $ariaLabel,
                ];

                $listContainers[] = $listItem;
                if ($isCardFeature) {
                    $cardFeatureContainers[] = $listItem;
                }
            }

            if ($tag === 'table') {
                $ariaLabel = trim((string) $node->getAttribute('aria-label'));
                $tableLabel = $ariaLabel !== '' ? $ariaLabel : ($class !== '' ? $class : 'table');
                $tableContainers[] = [
                    'key' => $key,
                    'label' => $tableLabel,
                    'class' => $class,
                    'row_class' => $this->firstDescendantClass($node, 'tr'),
                    'cell_class' => $this->firstDescendantClass($node, 'td') ?: $this->firstDescendantClass($node, 'th'),
                ];
            }
        }

        if ($removableBlocks === [] && $listContainers === [] && $tableContainers === []) {
            return null;
        }

        return [
            'section_path' => $sectionPath,
            'module' => $module,
            'addable_tags' => self::RAW_HTML_ADDABLE_TEXT_TAGS,
            'tag_defaults' => $tagDefaults,
            'removable_blocks' => $removableBlocks,
            'list_containers' => $listContainers,
            'table_containers' => $tableContainers,
            'card_feature_containers' => $cardFeatureContainers,
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private function applyRawHtmlBlockOperation(string $rawHtml, array $operation, string $module): ?array
    {
        [$dom, $root, $xpath] = $this->parseHtmlFragment($rawHtml);
        if (!$dom || !$root || !$xpath) {
            return null;
        }

        $container = $this->resolveRawHtmlSectionContainer($root);

        $elementsByKey = [];
        $listsByKey = [];
        $tablesByKey = [];
        $nodes = $xpath->query('.//*', $container);
        if ($nodes === false) {
            return null;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $node)) {
                continue;
            }

            $tag = strtolower($node->tagName);
            $key = trim((string) $node->getAttribute('data-ai-anchor-key'));
            if ($key === '') {
                $key = $this->shortHash('struct|' . $this->buildNodePath($node));
                $node->setAttribute('data-ai-anchor-key', $key);
            }
            $elementsByKey[$key] = $node;

            if (in_array($tag, ['ul', 'ol'], true)) {
                $listsByKey[$key] = $node;
            }

            if ($tag === 'table') {
                $tablesByKey[$key] = $node;
            }
        }

        $action = (string) ($operation['action'] ?? '');

        if ($action === 'remove_block') {
            $targetKey = (string) ($operation['target_key'] ?? '');
            $target = $elementsByKey[$targetKey] ?? null;
            if (!$target instanceof DOMElement || !($target->parentNode instanceof DOMNode)) {
                return null;
            }

            $target->parentNode->removeChild($target);
            return ['raw_html' => $this->innerHtml($root)];
        }

        if ($action === 'add_text') {
            $tag = strtolower((string) ($operation['tag'] ?? ''));
            $value = (string) ($operation['value'] ?? '');
            if (!in_array($tag, self::RAW_HTML_ADDABLE_TEXT_TAGS, true) || trim($value) === '') {
                return null;
            }
            $anchorKey = trim((string) ($operation['anchor_key'] ?? ''));
            $anchorPosition = strtolower(trim((string) ($operation['anchor_position'] ?? 'after')));
            if (!in_array($anchorPosition, ['before', 'after'], true)) {
                $anchorPosition = 'after';
            }

            $element = $dom->createElement($tag);
            $class = trim((string) ($operation['class'] ?? ''));
            if ($class === '') {
                $class = $this->inferFirstClassByTag($container, $tag, $module);
            }
            if ($class !== '') {
                $element->setAttribute('class', $class);
            }

            $tempInsertId = $this->shortHash('insert|' . microtime(true) . '|' . mt_rand());
            $element->setAttribute('data-ai-insert-id', $tempInsertId);
            $element->appendChild($dom->createTextNode($value));
            $this->insertNodeInSection(
                container: $container,
                newNode: $element,
                elementsByKey: $elementsByKey,
                anchorKey: $anchorKey,
                anchorPosition: $anchorPosition
            );
            $insertedAnchorKey = $this->resolveInsertedAnchorKey($container, $tempInsertId);
            return [
                'raw_html' => $this->innerHtml($root),
                'inserted_anchor_key' => $insertedAnchorKey,
            ];
        }

        if ($action === 'add_list_block') {
            $listTag = strtolower((string) ($operation['list_tag'] ?? ''));
            if (!in_array($listTag, ['ul', 'ol'], true)) {
                return null;
            }
            $anchorKey = trim((string) ($operation['anchor_key'] ?? ''));
            $anchorPosition = strtolower(trim((string) ($operation['anchor_position'] ?? 'after')));
            if (!in_array($anchorPosition, ['before', 'after'], true)) {
                $anchorPosition = 'after';
            }

            $list = $dom->createElement($listTag);
            $listClass = trim((string) ($operation['class'] ?? ''));
            if ($listClass === '') {
                $listClass = $this->inferFirstClassByTag($container, $listTag, $module);
            }
            if ($listClass !== '') {
                $list->setAttribute('class', $listClass);
            }

            $ariaLabel = trim((string) ($operation['aria_label'] ?? ''));
            if ($ariaLabel === '') {
                $ariaLabel = $this->inferFirstAriaLabelByTag($container, $listTag, $module);
            }
            if ($ariaLabel === '') {
                $ariaLabel = $listTag === 'ol' ? 'How to deposit numbered list' : 'Gameplay bullet list';
            }
            $list->setAttribute('aria-label', $ariaLabel);

            $items = [];
            if (isset($operation['items']) && is_array($operation['items'])) {
                foreach ($operation['items'] as $item) {
                    $text = trim((string) $item);
                    if ($text !== '') {
                        $items[] = $text;
                    }
                }
            }
            if ($items === []) {
                $items = [
                    'We will explore the key features of the Aviator',
                    'Casino game, discuss its gameplay mechanics',
                    'User interface and overall experience',
                ];
            }

            $itemClass = trim((string) ($operation['item_class'] ?? ''));
            if ($itemClass === '') {
                $itemClass = $this->inferListItemClassByListTag($container, $listTag, $module);
            }
            if ($itemClass === '') {
                $itemClass = 'list__item';
            }

            $stepClass = $this->inferOrderedListStepClass($container, $module);
            if ($stepClass === '') {
                $stepClass = 'text--primary';
            }

            foreach ($items as $itemText) {
                $li = $dom->createElement('li');
                if ($itemClass !== '') {
                    $li->setAttribute('class', $itemClass);
                }

                if ($listTag === 'ol') {
                    $span = $dom->createElement('span');
                    $span->appendChild($dom->createTextNode('Step:'));
                    if ($stepClass !== '') {
                        $span->setAttribute('class', $stepClass);
                    }
                    $li->appendChild($span);
                    $li->appendChild($dom->createTextNode(' ' . $itemText));
                } else {
                    $li->appendChild($dom->createTextNode($itemText));
                }

                $list->appendChild($li);
            }

            $tempInsertId = $this->shortHash('insert|' . microtime(true) . '|' . mt_rand());
            $list->setAttribute('data-ai-insert-id', $tempInsertId);
            $this->insertNodeInSection(
                container: $container,
                newNode: $list,
                elementsByKey: $elementsByKey,
                anchorKey: $anchorKey,
                anchorPosition: $anchorPosition
            );
            $insertedAnchorKey = $this->resolveInsertedAnchorKey($container, $tempInsertId);
            return [
                'raw_html' => $this->innerHtml($root),
                'inserted_anchor_key' => $insertedAnchorKey,
            ];
        }

        if ($action === 'add_table_block') {
            $anchorKey = trim((string) ($operation['anchor_key'] ?? ''));
            $anchorPosition = strtolower(trim((string) ($operation['anchor_position'] ?? 'after')));
            if (!in_array($anchorPosition, ['before', 'after'], true)) {
                $anchorPosition = 'after';
            }

            $headers = isset($operation['headers']) && is_array($operation['headers'])
                ? array_values(array_filter(array_map(
                    fn ($value): string => trim((string) $value),
                    $operation['headers']
                ), fn (string $value): bool => $value !== ''))
                : [];
            if ($headers === []) {
                $headers = [
                    'Method',
                    'Withdrawal Availability',
                    'Min Deposit/Withdrawal',
                    'Withdrawal Time',
                    'Fees',
                ];
            }

            $rows = [];
            if (isset($operation['rows']) && is_array($operation['rows'])) {
                foreach ($operation['rows'] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $values = array_map(fn ($value): string => trim((string) $value), $row);
                    if (array_filter($values, fn (string $value): bool => $value !== '') !== []) {
                        $rows[] = $values;
                    }
                }
            }
            if ($rows === []) {
                $rows = [
                    ['Visa', 'Yes (limited)', '€25/€50', '1-3 days', '3% on deposit'],
                    ['Mastercard', 'Yes (limited)', '€25/€50', '1-3 days', '3% on deposit'],
                    ['Skrill', 'Yes', '€10/€20', '24-48 hours', 'None'],
                    ['Neteller', 'Yes', '€10/€20', '24-48 hours', 'None'],
                    ['EcoPayz', 'Yes', '€10/€20', 'Instant-24 hours', 'None'],
                    ['Bitcoin', 'Yes', '€20 equiv/€100', '1-3 hours', 'None'],
                    ['Paysafecard', 'No', '€5/N/A', 'N/A', 'None'],
                    ['Bank Transfer', 'Yes', '€100/€100', '3-7 days', 'Varies by bank'],
                ];
            }

            $outer = $dom->createElement('div');
            $outerClass = trim((string) ($operation['class'] ?? ''));
            $outer->setAttribute('class', $outerClass !== '' ? $outerClass : 'payments__tables');
            $ariaLabel = trim((string) ($operation['aria_label'] ?? ''));
            $outer->setAttribute('aria-label', $ariaLabel !== '' ? $ariaLabel : 'Payment methods list');

            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('class', 'payments__table-wrapper');

            $table = $dom->createElement('table');
            $table->setAttribute('class', 'payments__table');
            $table->setAttribute('aria-label', 'Payment methods table');

            $thead = $dom->createElement('thead');
            $thead->setAttribute('class', 'payments__table-head');
            $headerRow = $dom->createElement('tr');
            $headerRow->setAttribute('class', 'payments__row');
            foreach ($headers as $header) {
                $cell = $dom->createElement('th');
                $cell->setAttribute('class', 'payments__cell payments__cell--header');
                $cell->setAttribute('scope', 'col');
                $cell->appendChild($dom->createTextNode($header));
                $headerRow->appendChild($cell);
            }
            $thead->appendChild($headerRow);

            $tbody = $dom->createElement('tbody');
            $tbody->setAttribute('class', 'payments__table-body');
            $rowClass = trim((string) ($operation['row_class'] ?? '')) ?: 'payments__row';
            $cellClass = trim((string) ($operation['cell_class'] ?? '')) ?: 'payments__cell';
            foreach ($rows as $rowValues) {
                $row = $dom->createElement('tr');
                $row->setAttribute('class', $rowClass);
                foreach ($headers as $index => $_header) {
                    $cell = $dom->createElement('td');
                    $cell->setAttribute('class', $cellClass);
                    $cell->appendChild($dom->createTextNode((string) ($rowValues[$index] ?? '')));
                    $row->appendChild($cell);
                }
                $tbody->appendChild($row);
            }

            $table->appendChild($thead);
            $table->appendChild($tbody);
            $wrapper->appendChild($table);
            $outer->appendChild($wrapper);

            $tempInsertId = $this->shortHash('insert|' . microtime(true) . '|' . mt_rand());
            $outer->setAttribute('data-ai-insert-id', $tempInsertId);
            $this->insertNodeInSection(
                container: $container,
                newNode: $outer,
                elementsByKey: $elementsByKey,
                anchorKey: $anchorKey,
                anchorPosition: $anchorPosition
            );
            $insertedAnchorKey = $this->resolveInsertedAnchorKey($container, $tempInsertId);
            return [
                'raw_html' => $this->innerHtml($root),
                'inserted_anchor_key' => $insertedAnchorKey,
            ];
        }

        if ($action === 'add_list_item') {
            $containerKey = (string) ($operation['container_key'] ?? '');
            $list = $listsByKey[$containerKey] ?? null;
            if (!$list instanceof DOMElement) {
                return null;
            }

            $value = trim((string) ($operation['value'] ?? ''));
            if ($value === '') {
                return null;
            }

            $li = $dom->createElement('li');
            $itemClass = trim((string) ($operation['class'] ?? ''));
            if ($itemClass === '') {
                $itemClass = $this->firstDescendantClass($list, 'li');
            }
            if ($itemClass !== '') {
                $li->setAttribute('class', $itemClass);
            }
            $li->appendChild($dom->createTextNode($value));
            $list->appendChild($li);

            return ['raw_html' => $this->innerHtml($root)];
        }

        if ($action === 'remove_last_list_item') {
            $containerKey = (string) ($operation['container_key'] ?? '');
            $list = $listsByKey[$containerKey] ?? null;
            if (!$list instanceof DOMElement) {
                return null;
            }

            $items = [];
            foreach ($list->childNodes as $child) {
                if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                    $items[] = $child;
                }
            }

            $lastItem = $items[count($items) - 1] ?? null;
            if (!$lastItem instanceof DOMElement || !($lastItem->parentNode instanceof DOMNode)) {
                return null;
            }

            $lastItem->parentNode->removeChild($lastItem);
            return ['raw_html' => $this->innerHtml($root)];
        }

        if ($action === 'add_card_feature') {
            $containerKey = (string) ($operation['container_key'] ?? '');
            $list = $listsByKey[$containerKey] ?? null;
            if (!$list instanceof DOMElement) {
                return null;
            }

            $text = trim((string) ($operation['text'] ?? ''));
            if ($text === '') {
                return null;
            }

            $li = $dom->createElement('li');
            $liClass = trim((string) ($operation['class'] ?? ''));
            if ($liClass === '') {
                $liClass = $this->firstDescendantClass($list, 'li');
            }
            if ($liClass === '') {
                $liClass = 'card__feature';
            }
            $li->setAttribute('class', $liClass);

            $img = $dom->createElement('img');
            $imgClass = trim((string) ($operation['icon_class'] ?? ''));
            if ($imgClass === '') {
                $imgClass = $this->firstDescendantClass($list, 'img');
            }
            if ($imgClass === '') {
                $imgClass = 'card__feature-icon';
            }
            $img->setAttribute('class', $imgClass);
            $img->setAttribute('src', trim((string) ($operation['icon_src'] ?? '/assets/svg/')) ?: '/assets/svg/');
            $img->setAttribute('width', '20');
            $img->setAttribute('height', '20');
            $img->setAttribute('alt', trim((string) ($operation['icon_alt'] ?? '')));

            $span = $dom->createElement('span');
            $spanClass = trim((string) ($operation['text_class'] ?? ''));
            if ($spanClass === '') {
                $spanClass = $this->firstDescendantClass($list, 'span');
            }
            if ($spanClass === '') {
                $spanClass = 'card__feature-text';
            }
            $span->setAttribute('class', $spanClass);
            $span->appendChild($dom->createTextNode($text));

            $li->appendChild($img);
            $li->appendChild($span);
            $list->appendChild($li);

            return ['raw_html' => $this->innerHtml($root)];
        }

        if ($action === 'remove_last_table_row') {
            $containerKey = (string) ($operation['container_key'] ?? '');
            $table = $tablesByKey[$containerKey] ?? null;
            if (!$table instanceof DOMElement) {
                return null;
            }

            $rows = [];
            foreach ($table->getElementsByTagName('tr') as $row) {
                if ($row instanceof DOMElement) {
                    $rows[] = $row;
                }
            }

            $lastRow = $rows[count($rows) - 1] ?? null;
            if (!$lastRow instanceof DOMElement || !($lastRow->parentNode instanceof DOMNode)) {
                return null;
            }

            $lastRow->parentNode->removeChild($lastRow);
            return ['raw_html' => $this->innerHtml($root)];
        }

        if ($action === 'add_table_row') {
            $containerKey = (string) ($operation['container_key'] ?? '');
            $table = $tablesByKey[$containerKey] ?? null;
            if (!$table instanceof DOMElement) {
                return null;
            }
            $anchorKey = trim((string) ($operation['anchor_key'] ?? ''));
            $anchorPosition = strtolower(trim((string) ($operation['anchor_position'] ?? 'after')));
            if (!in_array($anchorPosition, ['before', 'after'], true)) {
                $anchorPosition = 'after';
            }

            $col1 = trim((string) ($operation['col1'] ?? ''));
            $col2 = trim((string) ($operation['col2'] ?? ''));
            if ($col1 === '' && $col2 === '') {
                return null;
            }

            $row = $dom->createElement('tr');
            $rowClass = trim((string) ($operation['row_class'] ?? ''));
            if ($rowClass === '') {
                $rowClass = $this->firstDescendantClass($table, 'tr');
            }
            if ($rowClass !== '') {
                $row->setAttribute('class', $rowClass);
            }

            $cellClass = trim((string) ($operation['cell_class'] ?? ''));
            if ($cellClass === '') {
                $cellClass = $this->firstDescendantClass($table, 'td') ?: $this->firstDescendantClass($table, 'th');
            }

            $cellTag = $this->firstDescendantTag($table, ['td', 'th']) ?? 'td';
            foreach ([$col1, $col2] as $value) {
                $cell = $dom->createElement($cellTag);
                if ($cellClass !== '') {
                    $cell->setAttribute('class', $cellClass);
                }
                $cell->appendChild($dom->createTextNode($value));
                $row->appendChild($cell);
            }

            $anchor = $anchorKey !== '' ? ($elementsByKey[$anchorKey] ?? null) : null;
            if (
                $anchor instanceof DOMElement
                && strtolower($anchor->tagName) === 'tr'
                && $this->nodeIsWithinContainer($anchor, $table)
                && ($anchor->parentNode instanceof DOMNode)
            ) {
                if ($anchorPosition === 'before') {
                    $anchor->parentNode->insertBefore($row, $anchor);
                } else {
                    $nextSibling = $anchor->nextSibling;
                    if ($nextSibling instanceof DOMNode) {
                        $anchor->parentNode->insertBefore($row, $nextSibling);
                    } else {
                        $anchor->parentNode->appendChild($row);
                    }
                }
            } else {
                $tbody = $this->firstDescendantElementByTag($table, 'tbody');
                if ($tbody instanceof DOMElement) {
                    $tbody->appendChild($row);
                } else {
                    $table->appendChild($row);
                }
            }

            return ['raw_html' => $this->innerHtml($root)];
        }

        return null;
    }

    private function resolveInsertedAnchorKey(DOMElement $container, string $tempInsertId): ?string
    {
        $xpath = new DOMXPath($container->ownerDocument);
        $query = './/*[@data-ai-insert-id="' . $tempInsertId . '"]';
        $nodes = $xpath->query($query, $container);
        if ($nodes === false) {
            return null;
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return null;
        }

        $key = trim((string) $node->getAttribute('data-ai-anchor-key'));
        if ($key === '') {
            $key = $this->shortHash('insert-anchor|' . $tempInsertId);
            $node->setAttribute('data-ai-anchor-key', $key);
        }
        $node->removeAttribute('data-ai-insert-id');

        return $key;
    }

    private function decorateRawHtmlWithStableAnchorKeys(string $rawHtml, string $module): string
    {
        [$dom, $root, $xpath] = $this->parseHtmlFragment($rawHtml);
        if (!$dom || !$root || !$xpath) {
            return $rawHtml;
        }

        $container = $this->resolveRawHtmlSectionContainer($root);
        $nodes = $xpath->query('.//*', $container);
        if ($nodes === false) {
            return $rawHtml;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $node)) {
                continue;
            }

            if (trim((string) $node->getAttribute('data-ai-anchor-key')) !== '') {
                continue;
            }

            $node->setAttribute('data-ai-anchor-key', $this->shortHash('struct|' . $this->buildNodePath($node)));
        }

        return $this->innerHtml($root);
    }

    private function stripStableAnchorAttributes(string $rawHtml): string
    {
        [$dom, $root, $xpath] = $this->parseHtmlFragment($rawHtml);
        if (!$dom || !$root || !$xpath) {
            return $rawHtml;
        }

        $nodes = $xpath->query('.//*[@data-ai-anchor-key or @data-ai-insert-id]', $root);
        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $node->removeAttribute('data-ai-anchor-key');
                $node->removeAttribute('data-ai-insert-id');
            }
        }

        return $this->innerHtml($root);
    }

    private function resolveRawHtmlSectionContainer(DOMElement $root): DOMElement
    {
        $elements = [];
        foreach ($root->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        if (count($elements) === 1) {
            return $elements[0];
        }

        return $root;
    }

    /**
     * @param  array<string, DOMElement>  $elementsByKey
     */
    private function insertNodeInSection(
        DOMElement $container,
        DOMElement $newNode,
        array $elementsByKey,
        string $anchorKey,
        string $anchorPosition
    ): void {
        $anchor = $anchorKey !== '' ? ($elementsByKey[$anchorKey] ?? null) : null;
        if (
            !$anchor instanceof DOMElement
            || !$this->nodeIsWithinContainer($anchor, $container)
            || !($anchor->parentNode instanceof DOMNode)
        ) {
            $container->appendChild($newNode);
            return;
        }

        if ($anchorPosition === 'before') {
            $anchor->parentNode->insertBefore($newNode, $anchor);
            return;
        }

        $nextSibling = $anchor->nextSibling;
        if ($nextSibling instanceof DOMNode) {
            $anchor->parentNode->insertBefore($newNode, $nextSibling);
            return;
        }

        $anchor->parentNode->appendChild($newNode);
    }

    private function nodeIsWithinContainer(DOMNode $node, DOMElement $container): bool
    {
        $current = $node;
        while ($current instanceof DOMNode) {
            if ($current->isSameNode($container)) {
                return true;
            }
            $current = $current->parentNode;
        }

        return false;
    }

    private function inferFirstClassByTag(DOMElement $container, string $tag, string $module): string
    {
        $xpath = new DOMXPath($container->ownerDocument);
        $nodes = $xpath->query('.//' . $tag, $container);
        if ($nodes === false) {
            return '';
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $node)) {
                continue;
            }

            $class = trim((string) $node->getAttribute('class'));
            if ($class !== '') {
                return $class;
            }
        }

        return '';
    }

    private function inferFirstAriaLabelByTag(DOMElement $container, string $tag, string $module): string
    {
        $xpath = new DOMXPath($container->ownerDocument);
        $nodes = $xpath->query('.//' . $tag, $container);
        if ($nodes === false) {
            return '';
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $node)) {
                continue;
            }

            $aria = trim((string) $node->getAttribute('aria-label'));
            if ($aria !== '') {
                return $aria;
            }
        }

        return '';
    }

    private function inferListItemClassByListTag(DOMElement $container, string $listTag, string $module): string
    {
        $xpath = new DOMXPath($container->ownerDocument);
        $lists = $xpath->query('.//' . $listTag, $container);
        if ($lists === false) {
            return '';
        }

        foreach ($lists as $list) {
            if (!$list instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $list)) {
                continue;
            }

            $class = $this->firstDescendantClass($list, 'li');
            if ($class !== '') {
                return $class;
            }
        }

        return '';
    }

    private function inferOrderedListStepClass(DOMElement $container, string $module): string
    {
        $xpath = new DOMXPath($container->ownerDocument);
        $spans = $xpath->query('.//ol//li//span', $container);
        if ($spans === false) {
            return '';
        }

        foreach ($spans as $span) {
            if (!$span instanceof DOMElement) {
                continue;
            }

            if ($this->shouldHideRawHtmlTargetForModule($module, $span)) {
                continue;
            }

            $class = trim((string) $span->getAttribute('class'));
            if ($class !== '') {
                return $class;
            }
        }

        return '';
    }

    private function firstDescendantClass(DOMElement $container, string $tag): string
    {
        foreach ($container->getElementsByTagName($tag) as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            $class = trim((string) $element->getAttribute('class'));
            if ($class !== '') {
                return $class;
            }
        }

        return '';
    }

    /**
     * @param  array<int, string>  $allowedTags
     */
    private function firstDescendantTag(DOMElement $container, array $allowedTags): ?string
    {
        $normalizedAllowed = array_values(array_map(
            static fn (string $tag) => strtolower(trim($tag)),
            $allowedTags
        ));
        if ($normalizedAllowed === []) {
            return null;
        }

        foreach ($container->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($element->tagName);
            if (in_array($tag, $normalizedAllowed, true)) {
                return $tag;
            }
        }

        return null;
    }

    private function firstDescendantElementByTag(DOMElement $container, string $tag): ?DOMElement
    {
        foreach ($container->getElementsByTagName($tag) as $element) {
            if ($element instanceof DOMElement) {
                return $element;
            }
        }

        return null;
    }

    private function shouldExtractRawHtmlTextFields(string $fileName, string $field): bool
    {
        return $fileName === self::RAW_HTML_TEXT_SCOPE_FILE && $field === 'raw_html';
    }

    private function shouldHideSectionFromEditing(string $module): bool
    {
        return in_array(strtolower(trim($module)), self::NON_EDITABLE_MENU_MODULES, true);
    }

    private function shouldHideRawHtmlTargetForModule(string $module, DOMNode $node): bool
    {
        $normalizedModule = strtolower(trim($module));
        if ($normalizedModule === 'mobile-menu') {
            return true;
        }

        if ($normalizedModule !== 'hero') {
            return false;
        }

        if ($this->hasAncestorTag($node, 'header')) {
            return true;
        }

        return $this->hasAncestorClassFragment($node, ['header', 'menu', 'mobile-menu']);
    }

    /**
     * @return array{page_index:int,section_index:int,type:string,key:string,raw_html_path:string}|null
     */
    private function parseRawHtmlVirtualPath(string $path): ?array
    {
        if (!preg_match(self::RAW_HTML_VIRTUAL_PATH_PATTERN, $path, $matches)) {
            return null;
        }

        $type = (string) ($matches[3] ?? '');
        if (!in_array($type, ['text', 'attr', 'group'], true)) {
            return null;
        }

        $pageIndex = (int) $matches[1];
        $sectionIndex = (int) $matches[2];

        return [
            'page_index' => $pageIndex,
            'section_index' => $sectionIndex,
            'type' => $type,
            'key' => (string) $matches[4],
            'raw_html_path' => "pages.{$pageIndex}.sections.{$sectionIndex}.raw_html",
        ];
    }

    /**
     * @return array{page_index:int,script_index:int,head_extra_path:string}|null
     */
    private function parseHeadExtraScriptVirtualPath(string $path): ?array
    {
        if (!preg_match(self::HEAD_EXTRA_SCRIPT_VIRTUAL_PATH_PATTERN, $path, $matches)) {
            return null;
        }

        $pageIndex = (int) ($matches[1] ?? -1);
        $scriptIndex = (int) ($matches[2] ?? -1);
        if ($pageIndex < 0 || $scriptIndex < 0) {
            return null;
        }

        return [
            'page_index' => $pageIndex,
            'script_index' => $scriptIndex,
            'head_extra_path' => "pages.{$pageIndex}.og_data.head_extra",
        ];
    }

    private function readHeadExtraScriptVirtualValue(string $headExtra, int $scriptIndex): ?string
    {
        $blocks = $this->extractHeadExtraScriptBlocks($headExtra);
        if (!array_key_exists($scriptIndex, $blocks)) {
            return null;
        }

        $value = trim((string) $blocks[$scriptIndex]);
        return $value === '' ? null : $value;
    }

    private function applyHeadExtraScriptVirtualValue(string $headExtra, int $scriptIndex, string $newValue): ?string
    {
        $blocks = $this->extractHeadExtraScriptBlocks($headExtra);
        if (!array_key_exists($scriptIndex, $blocks)) {
            return null;
        }

        $normalizedNewValue = trim($newValue);
        if ($normalizedNewValue === '') {
            unset($blocks[$scriptIndex]);

            return implode("\n", array_values($blocks));
        }

        $blocks[$scriptIndex] = $normalizedNewValue;

        return implode("\n", $blocks);
    }

    private function parseHeadMetaOrLinkCreationPath(string $path): ?array
    {
        if (preg_match('/^pages\.(\d+)\.og_data\.(head_meta|head_links)\.(\d+)\.(name|property|http_equiv|content|rel|href|hreflang|type|sizes)$/', $path, $matches) !== 1) {
            return null;
        }

        $collection = $matches[2];
        $field = $matches[4];

        $allowedFields = $collection === 'head_meta'
            ? ['name', 'property', 'http_equiv', 'content']
            : ['rel', 'href', 'hreflang', 'type', 'sizes'];

        if (!in_array($field, $allowedFields, true)) {
            return null;
        }

        return [
            'collection_path' => "pages.{$matches[1]}.og_data.{$collection}",
            'item_index' => (int) $matches[3],
            'field' => $field,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array{collection_path:string,item_index:int,field:string} $parsedPath
     */
    private function applyHeadMetaOrLinkCreationEdit(array &$data, array $parsedPath, string $newValue): bool
    {
        $items = Arr::get($data, $parsedPath['collection_path']);
        if ($items === null) {
            $items = [];
        }

        if (!is_array($items)) {
            return false;
        }

        $itemIndex = $parsedPath['item_index'];
        for ($i = count($items); $i <= $itemIndex; $i++) {
            if (!array_key_exists($i, $items)) {
                $items[$i] = [];
            }
        }

        if (!is_array($items[$itemIndex])) {
            $items[$itemIndex] = [];
        }

        $currentValue = $items[$itemIndex][$parsedPath['field']] ?? null;
        $currentValue = is_string($currentValue) ? $currentValue : '';
        if ($currentValue === $newValue) {
            return false;
        }

        $items[$itemIndex][$parsedPath['field']] = $newValue;
        Arr::set($data, $parsedPath['collection_path'], $items);

        return true;
    }

    /**
     * Keep the index template FAQPage JSON-LD aligned with the visible FAQ section.
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $updatedPaths
     */
    private function syncFaqPageJsonLdFromRawHtmlSection(array &$data, array $updatedPaths): bool
    {
        $faqSectionPath = 'pages.0.sections.17.raw_html';
        $faqScriptPath = 'pages.0.og_data.head_extra';
        $faqScriptIndex = 3;

        $faqSectionChanged = false;
        foreach ($updatedPaths as $path) {
            if (str_starts_with($path, $faqSectionPath . '.__text__.')) {
                $faqSectionChanged = true;
                break;
            }
        }

        if (!$faqSectionChanged) {
            return false;
        }

        $rawHtml = Arr::get($data, $faqSectionPath);
        $headExtra = Arr::get($data, $faqScriptPath);
        if (!is_string($rawHtml) || !is_string($headExtra)) {
            return false;
        }

        $pairs = $this->extractFaqPairsFromRawHtml($rawHtml);
        if ($pairs === []) {
            return false;
        }

        $newValue = $this->buildFaqPageJsonLdScript($pairs);
        $updatedHeadExtra = $this->applyHeadExtraScriptVirtualValue($headExtra, $faqScriptIndex, $newValue);
        if ($updatedHeadExtra === null || $updatedHeadExtra === $headExtra) {
            return false;
        }

        Arr::set($data, $faqScriptPath, $updatedHeadExtra);

        return true;
    }

    /**
     * @return array<int, array{question:string,answer:string}>
     */
    private function extractFaqPairsFromRawHtml(string $rawHtml): array
    {
        $index = $this->indexRawHtmlTargets($rawHtml, 'faq');
        $pairs = [];
        $pendingQuestion = null;

        foreach (($index['ordered'] ?? []) as $key) {
            $target = $index['targets'][$key] ?? null;
            if (!is_array($target) || ($target['type'] ?? '') !== 'text') {
                continue;
            }

            $tag = (string) ($target['tag'] ?? '');
            $value = trim((string) ($target['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($tag === 'span') {
                $pendingQuestion = $value;
                continue;
            }

            if ($tag === 'p' && is_string($pendingQuestion) && $pendingQuestion !== '') {
                $pairs[] = [
                    'question' => $pendingQuestion,
                    'answer' => $value,
                ];
                $pendingQuestion = null;
            }
        }

        return $pairs;
    }

    /**
     * @param array<int, array{question:string,answer:string}> $pairs
     */
    private function buildFaqPageJsonLdScript(array $pairs): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $pair): array => [
                '@type' => 'Question',
                'name' => $pair['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $pair['answer'],
                ],
            ], $pairs),
        ];

        return '<script type="application/ld+json">' . "\n"
            . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . "\n" . '</script>';
    }

    /**
     * Keep the index template HowTo JSON-LD aligned with the visible STEPS section.
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $updatedPaths
     */
    private function syncHowToJsonLdFromRawHtmlSection(array &$data, array $updatedPaths): bool
    {
        $stepsSectionPath = 'pages.0.sections.10.raw_html';
        $headExtraPath = 'pages.0.og_data.head_extra';
        $howToScriptIndex = 4;

        $stepsSectionChanged = false;
        foreach ($updatedPaths as $path) {
            if (str_starts_with($path, $stepsSectionPath . '.__text__.')) {
                $stepsSectionChanged = true;
                break;
            }
        }

        if (!$stepsSectionChanged) {
            return false;
        }

        $rawHtml = Arr::get($data, $stepsSectionPath);
        $headExtra = Arr::get($data, $headExtraPath);
        if (!is_string($rawHtml) || !is_string($headExtra)) {
            return false;
        }

        $howTo = $this->extractHowToFromRawHtml($rawHtml);
        if ($howTo === null) {
            return false;
        }

        $currentScript = $this->readHeadExtraScriptVirtualValue($headExtra, $howToScriptIndex);
        $imageUrls = is_string($currentScript) ? $this->extractHowToStepImages($currentScript) : [];

        $newValue = $this->buildHowToJsonLdScript($howTo, $imageUrls);
        $updatedHeadExtra = $this->applyHeadExtraScriptVirtualValue($headExtra, $howToScriptIndex, $newValue);
        if ($updatedHeadExtra === null || $updatedHeadExtra === $headExtra) {
            return false;
        }

        Arr::set($data, $headExtraPath, $updatedHeadExtra);

        return true;
    }

    /**
     * @return array{name:string,description:string,steps:array<int,array{name:string,text:string}>}|null
     */
    private function extractHowToFromRawHtml(string $rawHtml): ?array
    {
        $index = $this->indexRawHtmlTargets($rawHtml, 'steps');
        $titleParts = [];
        $description = null;
        $steps = [];
        $currentStep = null;
        $sawStepMarker = false;

        foreach (($index['ordered'] ?? []) as $key) {
            $target = $index['targets'][$key] ?? null;
            if (!is_array($target) || ($target['type'] ?? '') !== 'text') {
                continue;
            }

            $tag = (string) ($target['tag'] ?? '');
            $value = trim((string) ($target['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            if (!$sawStepMarker && $tag === 'h2') {
                $titleParts[] = $value;
                continue;
            }

            if (!$sawStepMarker && $tag === 'p' && $description === null) {
                $description = $value;
                continue;
            }

            if ($tag === 'span' && preg_match('/^step\s+\d+$/i', $value)) {
                $sawStepMarker = true;
                $currentStep = [
                    'name' => '',
                    'text' => '',
                ];
                continue;
            }

            if ($sawStepMarker && is_array($currentStep) && $tag === 'div' && $currentStep['name'] === '') {
                $currentStep['name'] = $value;
                continue;
            }

            if ($sawStepMarker && is_array($currentStep) && $tag === 'p' && $currentStep['name'] !== '') {
                $currentStep['text'] = $value;
                $steps[] = $currentStep;
                $currentStep = null;
            }
        }

        $name = trim(implode(' ', $titleParts));
        if ($name === '' || !is_string($description) || $description === '' || $steps === []) {
            return null;
        }

        return [
            'name' => $name,
            'description' => $description,
            'steps' => $steps,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractHowToStepImages(string $script): array
    {
        $json = trim((string) preg_replace('/^<script\b[^>]*>|<\/script>$/i', '', trim($script)));
        $schema = json_decode($json, true);
        if (!is_array($schema) || !isset($schema['step']) || !is_array($schema['step'])) {
            return [];
        }

        $images = [];
        foreach ($schema['step'] as $step) {
            if (is_array($step) && isset($step['image']) && is_string($step['image']) && $step['image'] !== '') {
                $images[] = $step['image'];
            }
        }

        return $images;
    }

    /**
     * @param array{name:string,description:string,steps:array<int,array{name:string,text:string}>} $howTo
     * @param array<int, string> $imageUrls
     */
    private function buildHowToJsonLdScript(array $howTo, array $imageUrls): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $howTo['name'],
            'description' => $howTo['description'],
            'step' => array_map(static function (array $step, int $index) use ($imageUrls): array {
                return [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'name' => $step['name'],
                    'text' => $step['text'],
                    'image' => $imageUrls[$index] ?? ($imageUrls[0] ?? 'https://{site}/assets/images/steps/step.webp'),
                ];
            }, $howTo['steps'], array_keys($howTo['steps'])),
        ];

        return '<script type="application/ld+json">' . "\n"
            . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . "\n" . '</script>';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractRawHtmlEditableFields(string $rawHtml, string $sectionPath, string $module): array
    {
        $index = $this->indexRawHtmlTargets($rawHtml, $module);
        if (($index['ordered'] ?? []) === []) {
            return [];
        }

        $fields = [];
        $promptShown = [];

        foreach ($index['ordered'] as $key) {
            $target = $index['targets'][$key] ?? null;
            if (!is_array($target)) {
                continue;
            }

            $virtualType = $target['type'] === 'attr' ? 'attr' : 'text';
            $path = "{$sectionPath}.raw_html.__{$virtualType}__.{$key}";
            $promptPath = $path;
            $showPrompt = true;

            $groupKey = $target['group_key'] ?? null;
            if (is_string($groupKey) && $groupKey !== '') {
                $promptPath = "{$sectionPath}.raw_html.__group__.{$groupKey}";
                if (isset($promptShown[$groupKey])) {
                    $showPrompt = false;
                }
                $promptShown[$groupKey] = true;
            }

            $value = (string) ($target['value'] ?? '');
            $blockLabel = (string) ($target['block_label'] ?? $this->labelForRawHtmlTarget($module, $target));
            $fields[] = [
                'path' => $path,
                'prompt_path' => $promptPath,
                'show_prompt' => $showPrompt,
                'field' => $this->labelForRawHtmlTarget($module, $target),
                'module' => $module,
                'section_path' => $sectionPath,
                'target_key' => (string) ($target['key'] ?? $key),
                'target_type' => (string) ($target['type'] ?? 'text'),
                'tag' => (string) ($target['tag'] ?? 'text'),
                'block_key' => (string) ($target['block_key'] ?? $key),
                'block_type' => (string) ($target['block_type'] ?? 'text'),
                'block_tag' => (string) ($target['block_tag'] ?? ($target['tag'] ?? 'text')),
                'block_label' => $blockLabel,
                'item_key' => (string) ($target['item_key'] ?? $key),
                'item_tag' => (string) ($target['item_tag'] ?? ($target['tag'] ?? 'text')),
                'item_label' => (string) ($target['item_label'] ?? ''),
                'value' => $value,
                'value_preview' => $this->previewText($value),
                'length' => mb_strlen($value),
            ];
        }

        return $fields;
    }

    /**
     * @return array{
     *   dom:DOMDocument|null,
     *   root:DOMElement|null,
     *   xpath:DOMXPath|null,
     *   targets:array<string,array<string,mixed>>,
     *   groups:array<string,array{keys:array<int,string>,values:array<int,string>}>,
     *   ordered:array<int,string>
     * }
     */
    private function indexRawHtmlTargets(string $rawHtml, ?string $module = null): array
    {
        [$dom, $root, $xpath] = $this->parseHtmlFragment($rawHtml);
        if (!$dom || !$root || !$xpath) {
            return [
                'dom' => null,
                'root' => null,
                'xpath' => null,
                'targets' => [],
                'groups' => [],
                'ordered' => [],
            ];
        }

        $targets = [];
        $groups = [];
        $ordered = [];
        $groupLineCounters = [];

        $textNodes = $xpath->query('.//text()[normalize-space(.) != ""]', $root);
        if ($textNodes !== false) {
            foreach ($textNodes as $node) {
                if (!$node instanceof DOMText || !$this->isEditableTextNode($node)) {
                    continue;
                }

                $value = $this->normalizeEditableText((string) $node->nodeValue);
                if ($value === '') {
                    continue;
                }

                $element = $this->closestEditableElement($node);
                if (!$element) {
                    continue;
                }

                if ($module !== null && $this->shouldHideRawHtmlTargetForModule($module, $node)) {
                    continue;
                }

                $tag = strtolower($element->tagName);
                $key = $this->shortHash('text|' . $this->buildNodePath($node));
                $blockElement = $this->closestRawHtmlBlockElement($node);
                $blockKey = $blockElement instanceof DOMElement
                    ? $this->shortHash('struct|' . $this->buildNodePath($blockElement))
                    : $key;
                $blockTag = $blockElement instanceof DOMElement ? strtolower($blockElement->tagName) : $tag;
                $itemElement = $this->closestRawHtmlItemElement($node);
                $itemKey = $itemElement instanceof DOMElement
                    ? $this->shortHash('struct|' . $this->buildNodePath($itemElement))
                    : $key;

                $groupKey = null;
                $lineIndex = null;
                $heading = $this->closestHeadingWithBreak($node);
                if ($heading instanceof DOMElement) {
                    $groupKey = $this->shortHash('group|' . $this->buildNodePath($heading));
                    $groupLineCounters[$groupKey] = ($groupLineCounters[$groupKey] ?? 0) + 1;
                    $lineIndex = $groupLineCounters[$groupKey];

                    if (!isset($groups[$groupKey])) {
                        $groups[$groupKey] = [
                            'keys' => [],
                            'values' => [],
                        ];
                    }
                    $groups[$groupKey]['keys'][] = $key;
                    $groups[$groupKey]['values'][] = $value;
                }

                $targets[$key] = [
                    'key' => $key,
                    'type' => 'text',
                    'node' => $node,
                    'tag' => $tag,
                    'value' => $value,
                    'group_key' => $groupKey,
                    'line_index' => $lineIndex,
                    'block_key' => $blockKey,
                    'block_tag' => $blockTag,
                    'block_type' => $this->classifyRawHtmlBlockElement($blockElement),
                    'block_label' => $blockElement instanceof DOMElement
                        ? $this->labelForRawHtmlBlock((string) ($module ?? 'raw_html'), $blockElement)
                        : $this->labelForRawHtmlTarget((string) ($module ?? 'raw_html'), ['tag' => $tag, 'type' => 'text']),
                    'item_key' => $itemKey,
                    'item_tag' => $itemElement instanceof DOMElement ? strtolower($itemElement->tagName) : $tag,
                    'item_label' => $itemElement instanceof DOMElement
                        ? $this->labelForRawHtmlItem($itemElement)
                        : '',
                ];
                $ordered[] = $key;
            }
        }

        $altNodes = $xpath->query('.//*[@alt]', $root);
        if ($altNodes !== false) {
            foreach ($altNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                if ($module !== null && $this->shouldHideRawHtmlTargetForModule($module, $node)) {
                    continue;
                }

                $value = trim((string) $node->getAttribute('alt'));

                $key = $this->shortHash('attr|' . $this->buildNodePath($node) . '@alt');
                $blockElement = $this->closestRawHtmlBlockElement($node);
                $blockKey = $blockElement instanceof DOMElement
                    ? $this->shortHash('struct|' . $this->buildNodePath($blockElement))
                    : $key;
                $blockTag = $blockElement instanceof DOMElement ? strtolower($blockElement->tagName) : strtolower($node->tagName);
                $itemElement = $this->closestRawHtmlItemElement($node);
                $itemKey = $itemElement instanceof DOMElement
                    ? $this->shortHash('struct|' . $this->buildNodePath($itemElement))
                    : $key;
                $targets[$key] = [
                    'key' => $key,
                    'type' => 'attr',
                    'node' => $node,
                    'tag' => strtolower($node->tagName),
                    'attribute' => 'alt',
                    'value' => $value,
                    'group_key' => null,
                    'line_index' => null,
                    'block_key' => $blockKey,
                    'block_tag' => $blockTag,
                    'block_type' => $this->classifyRawHtmlBlockElement($blockElement),
                    'block_label' => $blockElement instanceof DOMElement
                        ? $this->labelForRawHtmlBlock((string) ($module ?? 'raw_html'), $blockElement)
                        : $this->labelForRawHtmlTarget((string) ($module ?? 'raw_html'), ['tag' => strtolower($node->tagName), 'type' => 'attr']),
                    'item_key' => $itemKey,
                    'item_tag' => $itemElement instanceof DOMElement ? strtolower($itemElement->tagName) : strtolower($node->tagName),
                    'item_label' => $itemElement instanceof DOMElement
                        ? $this->labelForRawHtmlItem($itemElement)
                        : '',
                ];
                $ordered[] = $key;
            }
        }

        return [
            'dom' => $dom,
            'root' => $root,
            'xpath' => $xpath,
            'targets' => $targets,
            'groups' => $groups,
            'ordered' => $ordered,
        ];
    }

    private function closestRawHtmlBlockElement(DOMNode $node): ?DOMElement
    {
        $current = $node instanceof DOMElement ? $node : $node->parentNode;
        $fallback = null;

        while ($current instanceof DOMNode) {
            if (!$current instanceof DOMElement) {
                $current = $current->parentNode;
                continue;
            }

            $tag = strtolower($current->tagName);
            if (in_array($tag, self::RAW_HTML_GROUP_BLOCK_TAGS, true)) {
                return $current;
            }

            if ($fallback === null && in_array($tag, self::RAW_HTML_REMOVABLE_TAGS, true)) {
                $fallback = $current;
            }

            $current = $current->parentNode;
        }

        return $fallback;
    }

    private function closestRawHtmlItemElement(DOMNode $node): ?DOMElement
    {
        $current = $node instanceof DOMElement ? $node : $node->parentNode;

        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement) {
                $tag = strtolower($current->tagName);
                if (in_array($tag, ['tr', 'li'], true)) {
                    return $current;
                }
            }

            $current = $current->parentNode;
        }

        return null;
    }

    private function labelForRawHtmlItem(DOMElement $element): string
    {
        $tag = strtolower($element->tagName);
        $preview = $this->previewText($this->normalizeEditableText((string) $element->textContent));

        if ($tag === 'tr') {
            return $preview !== '' ? "Table row — {$preview}" : 'Table row';
        }

        if ($tag === 'li') {
            return $preview !== '' ? "List item — {$preview}" : 'List item';
        }

        return $preview;
    }

    private function classifyRawHtmlBlockElement(?DOMElement $element): string
    {
        if (!$element instanceof DOMElement) {
            return 'text';
        }

        $tag = strtolower($element->tagName);
        if ($tag === 'table') {
            return 'table';
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            $class = trim((string) $element->getAttribute('class'));
            $itemClass = $this->firstDescendantClass($element, 'li');
            return str_contains($class, 'card__features') || str_contains($itemClass, 'card__feature')
                ? 'card'
                : 'list';
        }

        return 'text';
    }

    private function labelForRawHtmlBlock(string $module, DOMElement $element): string
    {
        $tag = strtolower($element->tagName);
        $type = $this->classifyRawHtmlBlockElement($element);
        $class = trim((string) $element->getAttribute('class'));
        $aria = trim((string) $element->getAttribute('aria-label'));
        $preview = $this->previewText($this->normalizeEditableText((string) $element->textContent));

        if ($type === 'table') {
            return $module . ' :: table' . ($aria !== '' ? " — {$aria}" : ($class !== '' ? " — {$class}" : ''));
        }

        if ($type === 'list' || $type === 'card') {
            $label = $module . ' :: ' . ($type === 'card' ? 'cards' : $tag . ' list');
            if ($aria !== '') {
                return "{$label} — {$aria}";
            }
            if ($class !== '') {
                return "{$label} — {$class}";
            }

            return $label;
        }

        $label = "{$module} :: {$tag}";
        if ($preview !== '') {
            $label .= " — {$preview}";
        }

        return $label;
    }

    /**
     * @return array{0:DOMDocument|null,1:DOMElement|null,2:DOMXPath|null}
     */
    private function parseHtmlFragment(string $rawHtml): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrapped = '<!DOCTYPE html><html><body><div id="ai-agent-root">' . $rawHtml . '</div></body></html>';
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return [null, null, null];
        }

        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@id="ai-agent-root"]')->item(0);
        if (!$root instanceof DOMElement) {
            return [null, null, null];
        }

        return [$dom, $root, $xpath];
    }

    private function closestEditableElement(DOMNode $node): ?DOMElement
    {
        $current = $node->parentNode;
        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement) {
                return $current;
            }
            $current = $current->parentNode;
        }

        return null;
    }

    private function hasAncestorTag(DOMNode $node, string $tagName): bool
    {
        $tagName = strtolower($tagName);
        $current = $node;

        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement && strtolower($current->tagName) === $tagName) {
                return true;
            }
            $current = $current->parentNode;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $fragments
     */
    private function hasAncestorClassFragment(DOMNode $node, array $fragments): bool
    {
        $normalized = array_values(array_filter(array_map(
            fn ($fragment) => strtolower(trim($fragment)),
            $fragments
        )));
        if ($normalized === []) {
            return false;
        }

        $current = $node;
        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement) {
                $classAttr = strtolower(trim((string) $current->getAttribute('class')));
                if ($classAttr !== '') {
                    foreach ($normalized as $fragment) {
                        if ($fragment !== '' && str_contains($classAttr, $fragment)) {
                            return true;
                        }
                    }
                }
            }
            $current = $current->parentNode;
        }

        return false;
    }

    private function closestHeadingWithBreak(DOMNode $node): ?DOMElement
    {
        $current = $node->parentNode;
        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement) {
                $tag = strtolower($current->tagName);
                if (in_array($tag, self::HEADING_TAGS, true) && $this->elementContainsLineBreak($current)) {
                    return $current;
                }
            }
            $current = $current->parentNode;
        }

        return null;
    }

    private function elementContainsLineBreak(DOMElement $element): bool
    {
        return $element->getElementsByTagName('br')->length > 0;
    }

    private function isEditableTextNode(DOMText $node): bool
    {
        $current = $node->parentNode;
        while ($current instanceof DOMNode) {
            if ($current instanceof DOMElement) {
                $tag = strtolower($current->tagName);
                if (in_array($tag, self::RAW_HTML_TEXT_SKIP_TAGS, true)) {
                    return false;
                }
            }
            $current = $current->parentNode;
        }

        return true;
    }

    private function buildNodePath(DOMNode $node): string
    {
        $segments = [];
        $current = $node;

        while ($current && !($current instanceof DOMDocument)) {
            if ($current instanceof DOMElement) {
                $index = 1;
                $sibling = $current->previousSibling;
                while ($sibling) {
                    if ($sibling instanceof DOMElement && $sibling->tagName === $current->tagName) {
                        $index++;
                    }
                    $sibling = $sibling->previousSibling;
                }

                $segments[] = $current->tagName . '[' . $index . ']';
            } elseif ($current instanceof DOMText) {
                $index = 1;
                $sibling = $current->previousSibling;
                while ($sibling) {
                    if ($sibling instanceof DOMText) {
                        $index++;
                    }
                    $sibling = $sibling->previousSibling;
                }

                $segments[] = 'text()[' . $index . ']';
            }

            $current = $current->parentNode;
        }

        $segments = array_reverse($segments);
        return '/' . implode('/', $segments);
    }

    private function shortHash(string $value): string
    {
        return substr(sha1($value), 0, 16);
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function labelForRawHtmlTarget(string $module, array $target): string
    {
        $tag = strtolower((string) ($target['tag'] ?? 'text'));
        $type = (string) ($target['type'] ?? 'text');
        if ($type === 'attr') {
            return "{$module} :: {$tag} alt";
        }

        $lineIndex = $target['line_index'] ?? null;
        if (is_int($lineIndex) && $lineIndex > 0) {
            return "{$module} :: {$tag} line {$lineIndex}";
        }

        return "{$module} :: {$tag} text";
    }

    private function normalizeEditableText(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    /**
     * @param  array{page_index:int,section_index:int,type:string,key:string,raw_html_path:string}  $virtualPath
     */
    private function readRawHtmlVirtualValue(string $rawHtml, array $virtualPath): ?string
    {
        $index = $this->indexRawHtmlTargets($rawHtml);
        if ($virtualPath['type'] === 'group') {
            $group = $index['groups'][$virtualPath['key']] ?? null;
            if (!is_array($group)) {
                return null;
            }

            $values = $group['values'] ?? [];
            if (!is_array($values) || $values === []) {
                return null;
            }

            return implode("\n", array_map(
                fn ($value) => (string) $value,
                $values
            ));
        }

        $target = $index['targets'][$virtualPath['key']] ?? null;
        if (!is_array($target)) {
            return null;
        }

        return (string) ($target['value'] ?? '');
    }

    /**
     * @param  array{page_index:int,section_index:int,type:string,key:string,raw_html_path:string}  $virtualPath
     */
    private function applyRawHtmlVirtualValue(string $rawHtml, array $virtualPath, string $newValue): ?string
    {
        $index = $this->indexRawHtmlTargets($rawHtml);
        $root = $index['root'] ?? null;
        if (!$root instanceof DOMElement) {
            return null;
        }

        if ($virtualPath['type'] === 'group') {
            $group = $index['groups'][$virtualPath['key']] ?? null;
            if (!is_array($group)) {
                return null;
            }

            $keys = isset($group['keys']) && is_array($group['keys']) ? $group['keys'] : [];
            $currentValues = isset($group['values']) && is_array($group['values']) ? $group['values'] : [];
            if ($keys === [] || $currentValues === []) {
                return null;
            }

            $splitValues = $this->splitHeadingGroupText($newValue, $currentValues);
            foreach ($keys as $indexKey => $key) {
                $target = $index['targets'][$key] ?? null;
                if (!is_array($target)) {
                    continue;
                }

                $lineValue = $splitValues[$indexKey] ?? '';
                if (($target['node'] ?? null) instanceof DOMText) {
                    $target['node']->nodeValue = $lineValue;
                }
            }

            return $this->innerHtml($root);
        }

        $target = $index['targets'][$virtualPath['key']] ?? null;
        if (!is_array($target)) {
            return null;
        }

        if ($virtualPath['type'] === 'text' && ($target['node'] ?? null) instanceof DOMText) {
            $target['node']->nodeValue = $newValue;
            return $this->innerHtml($root);
        }

        if (
            $virtualPath['type'] === 'attr'
            && ($target['node'] ?? null) instanceof DOMElement
            && (($target['attribute'] ?? '') === 'alt')
        ) {
            $target['node']->setAttribute('alt', $newValue);
            return $this->innerHtml($root);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $templateParts
     * @return array<int, string>
     */
    private function splitHeadingGroupText(string $generatedText, array $templateParts): array
    {
        $partsCount = count($templateParts);
        if ($partsCount <= 1) {
            return [$this->normalizeEditableText($generatedText)];
        }

        $explicitByBr = preg_split('/<br\s*\/?>/i', $generatedText);
        if (is_array($explicitByBr) && count($explicitByBr) === $partsCount) {
            return array_map(fn ($part) => $this->normalizeEditableText((string) $part), $explicitByBr);
        }

        $explicitByNewLine = preg_split('/\R/u', $generatedText);
        if (is_array($explicitByNewLine) && count($explicitByNewLine) === $partsCount) {
            return array_map(fn ($part) => $this->normalizeEditableText((string) $part), $explicitByNewLine);
        }

        $normalized = $this->normalizeEditableText($generatedText);
        if ($normalized === '') {
            return array_fill(0, $partsCount, '');
        }

        return $this->splitWordsByRatio($normalized, $templateParts);
    }

    /**
     * @param  array<int, string>  $templateParts
     * @return array<int, string>
     */
    private function splitWordsByRatio(string $text, array $templateParts): array
    {
        $partsCount = count($templateParts);
        if ($partsCount <= 1) {
            return [$text];
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($words) || $words === []) {
            return $this->splitTextByLengthRatio($text, $templateParts);
        }

        if (count($words) < $partsCount) {
            return $this->splitTextByLengthRatio($text, $templateParts);
        }

        $lengths = array_map(
            fn ($part) => max(1, mb_strlen($this->normalizeEditableText((string) $part))),
            $templateParts
        );
        $totalLength = max(1, array_sum($lengths));
        $totalWords = count($words);

        $result = [];
        $cursor = 0;

        for ($index = 0; $index < $partsCount; $index++) {
            if ($index === $partsCount - 1) {
                $result[] = implode(' ', array_slice($words, $cursor));
                break;
            }

            $desiredWords = (int) round($totalWords * ($lengths[$index] / $totalLength));
            $desiredWords = max(1, $desiredWords);
            $remainingParts = $partsCount - $index - 1;
            $maxAllowed = $totalWords - $cursor - $remainingParts;
            $desiredWords = max(1, min($desiredWords, $maxAllowed));

            $result[] = implode(' ', array_slice($words, $cursor, $desiredWords));
            $cursor += $desiredWords;
        }

        while (count($result) < $partsCount) {
            $result[] = '';
        }

        return array_slice($result, 0, $partsCount);
    }

    /**
     * @param  array<int, string>  $templateParts
     * @return array<int, string>
     */
    private function splitTextByLengthRatio(string $text, array $templateParts): array
    {
        $partsCount = count($templateParts);
        if ($partsCount <= 1) {
            return [$text];
        }

        $totalLength = mb_strlen($text);
        if ($totalLength === 0) {
            return array_fill(0, $partsCount, '');
        }

        $lengths = array_map(
            fn ($part) => max(1, mb_strlen($this->normalizeEditableText((string) $part))),
            $templateParts
        );
        $ratioSum = max(1, array_sum($lengths));

        $result = [];
        $cursor = 0;
        for ($index = 0; $index < $partsCount; $index++) {
            if ($index === $partsCount - 1) {
                $result[] = trim(mb_substr($text, $cursor));
                break;
            }

            $chunkLength = (int) round($totalLength * ($lengths[$index] / $ratioSum));
            $chunkLength = max(1, $chunkLength);
            $result[] = trim(mb_substr($text, $cursor, $chunkLength));
            $cursor += $chunkLength;
        }

        while (count($result) < $partsCount) {
            $result[] = '';
        }

        return array_slice($result, 0, $partsCount);
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

    private function rewriteClonedDomainMetadata(string $targetDirectory, string $targetDomain): void
    {
        $files = glob($targetDirectory . '/*-raw_html.md') ?: [];

        foreach ($files as $filePath) {
            $data = Yaml::parseFile($filePath);
            if (!is_array($data)) {
                continue;
            }

            $oldDomain = (string) ($data['domain'] ?? '');

            $data['domain'] = $targetDomain;
            $data['name'] = $targetDomain;
            $data['output_path'] = "generated/{$targetDomain}";

            $pages = isset($data['pages']) && is_array($data['pages']) ? $data['pages'] : [];
            foreach ($pages as $pageIndex => $page) {
                if (!is_array($page)) {
                    continue;
                }

                if (isset($page['canonical']) && is_string($page['canonical'])) {
                    $pages[$pageIndex]['canonical'] = $this->replaceDomainInCanonical(
                        $page['canonical'],
                        $oldDomain,
                        $targetDomain
                    );
                }

                $headMeta = Arr::get($page, 'og_data.head_meta');
                if (is_array($headMeta)) {
                    foreach ($headMeta as $metaIndex => $metaItem) {
                        if (!is_array($metaItem)) {
                            continue;
                        }

                        if (isset($metaItem['content']) && is_string($metaItem['content'])) {
                            $headMeta[$metaIndex]['content'] = $this->replaceDomainPlaceholders(
                                value: $metaItem['content'],
                                oldDomain: $oldDomain,
                                targetDomain: $targetDomain
                            );
                        }
                    }

                    Arr::set($pages[$pageIndex], 'og_data.head_meta', $headMeta);
                }

                $headLinks = Arr::get($page, 'og_data.head_links');
                if (is_array($headLinks)) {
                    foreach ($headLinks as $linkIndex => $linkItem) {
                        if (!is_array($linkItem)) {
                            continue;
                        }

                        if (isset($linkItem['href']) && is_string($linkItem['href'])) {
                            $headLinks[$linkIndex]['href'] = $this->replaceDomainPlaceholders(
                                value: $linkItem['href'],
                                oldDomain: $oldDomain,
                                targetDomain: $targetDomain
                            );
                        }
                    }

                    Arr::set($pages[$pageIndex], 'og_data.head_links', $headLinks);
                }

                foreach (['head_extra', 'head_custom', 'body_extra'] as $seoField) {
                    $seoValue = Arr::get($page, "og_data.{$seoField}");
                    if (!is_string($seoValue)) {
                        continue;
                    }

                    Arr::set(
                        $pages[$pageIndex],
                        "og_data.{$seoField}",
                        $this->replaceDomainPlaceholders(
                            value: $seoValue,
                            oldDomain: $oldDomain,
                            targetDomain: $targetDomain
                        )
                    );
                }
            }

            $data['pages'] = $pages;
            $this->writeYamlDocument($filePath, $data);
        }
    }

    private function replaceDomainInCanonical(
        string $canonical,
        string $oldDomain,
        string $targetDomain
    ): string {
        $canonical = trim($canonical);
        if ($canonical === '') {
            return $canonical;
        }

        if ($oldDomain !== '' && str_contains($canonical, $oldDomain)) {
            return str_replace($oldDomain, $targetDomain, $canonical);
        }

        $parts = parse_url($canonical);
        if (!is_array($parts) || !isset($parts['host'])) {
            return $canonical;
        }

        $parts['host'] = $targetDomain;

        $rebuilt = '';
        if (isset($parts['scheme'])) {
            $rebuilt .= $parts['scheme'] . '://';
        }

        if (isset($parts['user'])) {
            $rebuilt .= $parts['user'];
            if (isset($parts['pass'])) {
                $rebuilt .= ':' . $parts['pass'];
            }
            $rebuilt .= '@';
        }

        $rebuilt .= $parts['host'];

        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';

        if (isset($parts['query'])) {
            $rebuilt .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function replaceDomainPlaceholders(string $value, string $oldDomain, string $targetDomain): string
    {
        $updated = $value;

        if ($oldDomain !== '') {
            $updated = str_replace($oldDomain, $targetDomain, $updated);
            $updated = str_replace('support@' . $oldDomain, 'support@' . $targetDomain, $updated);
        }

        $updated = str_replace('support@site.com', 'support@' . $targetDomain, $updated);
        $updated = str_replace('https://site.com', 'https://' . $targetDomain, $updated);
        $updated = str_replace('http://site.com', 'http://' . $targetDomain, $updated);
        $updated = str_replace('site.com', $targetDomain, $updated);

        return $updated;
    }

    private function writeYamlDocument(string $filePath, array $data): void
    {
        $yaml = Yaml::dump($data, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $content = "---\n{$yaml}";

        if (file_put_contents($filePath, $content) === false) {
            throw new RuntimeException("Could not write template file: {$filePath}");
        }
    }

    private function resolveSourceDirectory(string $sourceDomain): string
    {
        $domain = $this->normalizeDomain($sourceDomain);
        $root = $this->templateRoot();
        $source = $root . '/' . $domain;
        $this->assertPathWithinRoot($source, $root);

        return $source;
    }

    private function resolveTargetDirectory(string $targetDomain): string
    {
        $domain = $this->normalizeDomain($targetDomain);
        $root = $this->templateRoot();
        $target = $root . '/' . $domain;
        $this->assertPathWithinRoot($target, $root);

        return $target;
    }

    private function templateRoot(): string
    {
        $root = (string) config(
            'services.ai_agent.templates_root',
            storage_path('import-deploy/md/test/raw_html')
        );

        if (!is_dir($root)) {
            throw new RuntimeException("Templates root was not found: {$root}");
        }

        return rtrim(str_replace('\\', '/', $root), '/');
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if ($domain === '') {
            throw new RuntimeException('Domain cannot be empty.');
        }

        if (!preg_match('/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/', $domain)) {
            throw new RuntimeException("Invalid domain format: {$domain}");
        }

        return $domain;
    }

    private function assertPathWithinRoot(string $path, string $root): void
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');

        if ($normalizedPath === $normalizedRoot) {
            return;
        }

        if (!str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            throw new RuntimeException('Resolved path is outside allowed templates root.');
        }
    }

    private function previewText(string $value): string
    {
        $flat = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($flat === '') {
            return '';
        }

        return mb_strlen($flat) > 180
            ? mb_substr($flat, 0, 180) . '...'
            : $flat;
    }

    private function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}

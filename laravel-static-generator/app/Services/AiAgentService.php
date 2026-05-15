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
    ];

    /**
     * @var array<int, string>
     */
    private const HEAD_LINK_EDITABLE_RELS = [
        'publisher',
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
     * @return array<int, array{file:string,page_fields:array<int, array<string, mixed>>,section_fields:array<int, array<string, mixed>>}>
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
                if (!is_string($href)) {
                    continue;
                }

                $path = "pages.{$pageIndex}.og_data.head_links.{$linkIndex}.href";
                $label = $this->describeHeadLinkEntry($linkItem);
                $fields[] = $this->buildPageField($path, $label, $href);
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
                            config: $config
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
                            config: $config
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
                        config: $config
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

    private function rewriteFieldWithAi(
        string $currentValue,
        string $prompt,
        string $fieldPath,
        AiAgentConfig $config
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
        $userMessage .= "Current value:\n{$currentValue}";

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
     * @return array<int, array{file:string,path:string,prompt:string}>
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
            return null;
        }

        $blocks[$scriptIndex] = $normalizedNewValue;

        return implode("\n", $blocks);
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
            $fields[] = [
                'path' => $path,
                'prompt_path' => $promptPath,
                'show_prompt' => $showPrompt,
                'field' => $this->labelForRawHtmlTarget($module, $target),
                'module' => $module,
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
                if ($value === '') {
                    continue;
                }

                $key = $this->shortHash('attr|' . $this->buildNodePath($node) . '@alt');
                $targets[$key] = [
                    'key' => $key,
                    'type' => 'attr',
                    'node' => $node,
                    'tag' => strtolower($node->tagName),
                    'attribute' => 'alt',
                    'value' => $value,
                    'group_key' => null,
                    'line_index' => null,
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

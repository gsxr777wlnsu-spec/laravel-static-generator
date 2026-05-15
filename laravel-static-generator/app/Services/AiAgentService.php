<?php

namespace App\Services;

use App\Models\AiAgentConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
                    $pageFields[] = [
                        'path' => $path,
                        'field' => $pageField,
                        'value_preview' => $this->previewText($page[$pageField]),
                        'length' => mb_strlen($page[$pageField]),
                    ];
                }

                $sections = isset($page['sections']) && is_array($page['sections']) ? $page['sections'] : [];
                foreach ($sections as $sectionIndex => $section) {
                    if (!is_array($section)) {
                        continue;
                    }

                    $module = (string) ($section['module'] ?? $section['module_key'] ?? "section-{$sectionIndex}");
                    foreach ($section as $field => $value) {
                        if (!is_string($value) || in_array($field, self::SECTION_TECHNICAL_FIELDS, true)) {
                            continue;
                        }

                        $path = "pages.{$pageIndex}.sections.{$sectionIndex}.{$field}";
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
                'file' => basename($fullPath),
                'page_fields' => $pageFields,
                'section_fields' => $sectionFields,
            ];
        }

        return $catalog;
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
        $targetDirectory = $this->resolveTargetDirectory($targetDomain);
        if (!is_dir($targetDirectory)) {
            throw new RuntimeException("Target template directory was not found: {$targetDirectory}");
        }

        $normalizedPrompts = $this->normalizeFieldPrompts($fieldPrompts);
        if ($normalizedPrompts === []) {
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

        $updatedFields = 0;
        $updatedFiles = 0;
        $details = [];

        foreach ($groupedByFile as $fileName => $items) {
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

            $fileUpdates = 0;
            $updatedPaths = [];

            foreach ($items as $item) {
                $currentValue = Arr::get($data, $item['path']);
                if (!is_string($currentValue)) {
                    continue;
                }

                $newValue = $this->rewriteFieldWithAi(
                    currentValue: $currentValue,
                    prompt: $item['prompt'],
                    fieldPath: $item['path'],
                    config: $config
                );

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
        $isMetaTitleField = str_ends_with($fieldPath, '.meta_title');
        $isMetaDescriptionField = str_ends_with($fieldPath, '.meta_description');

        $systemMessage = 'You rewrite one field value. Return only final rewritten value text without JSON, markdown, or explanations.';
        if ($isHtmlField) {
            $systemMessage .= ' Preserve valid HTML structure. Do not remove required tags.';
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

        if ($isHtmlField) {
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

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->acceptJson()
            ->post(rtrim($baseUrl, '/') . '/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI-compatible API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI-compatible API returned empty response.');
        }

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

        $response = Http::withHeaders([
            'x-api-key' => (string) $config->api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic API request failed: ' . $response->body());
        }

        $content = $response->json('content.0.text');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Anthropic API returned empty response.');
        }

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

            $normalized[] = [
                'file' => $file,
                'path' => $path,
                'prompt' => $prompt,
            ];
        }

        return $normalized;
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
}

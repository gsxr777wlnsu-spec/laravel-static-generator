<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\AiAgentConfig;
use App\Models\AiPromptRule;
use App\Models\Page;
use App\Models\Section;
use App\Services\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AiAgentController extends Controller
{
    public function __construct(
        private AiAgentConfigRepositoryInterface $configs,
        private AiAgentService $aiAgentService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $config = $this->configs->findForUser((int) $user->id);

        return response()->json([
            'config' => $config ? $this->serializeConfig($config) : null,
            'providers' => $this->aiAgentService->providerOptions(),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $providerValues = array_column($this->aiAgentService->providerOptions(), 'value');

        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:' . implode(',', $providerValues),
            'api_key' => 'nullable|string|max:4000',
            'api_base_url' => 'nullable|string|max:500',
            'model_name' => 'nullable|string|max:150',
            'ai_models' => 'nullable|array',
            'ai_models.*.provider' => 'required_with:ai_models|string|in:' . implode(',', $providerValues),
            'ai_models.*.api_key' => 'nullable|string|max:4000',
            'ai_models.*.api_base_url' => 'nullable|string|max:500',
            'ai_models.*.model_name' => 'nullable|string|max:150',
            'ai_models.*.label' => 'nullable|string|max:80',
            'ai_models.*.temperature' => 'nullable|numeric|min:0|max:2',
            'ai_models.*.tone' => 'nullable|string|max:100',
            'ai_models.*.max_tokens' => 'nullable|integer|min:1|max:128000',
            'ai_models.*.top_p' => 'nullable|numeric|min:0|max:1',
            'ai_models.*.frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'ai_models.*.presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'tone' => 'nullable|string|max:100',
            'max_tokens' => 'nullable|integer|min:1|max:128000',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'allowed_paths' => 'nullable|array',
            'allowed_paths.*' => 'nullable|string|max:1000',
            'allowed_sites' => 'nullable|array',
            'allowed_sites.*' => 'integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $existing = $this->configs->findForUser((int) $user->id);

        $cleanPaths = array_values(array_filter(
            array_map(static fn ($path) => trim((string) $path), $data['allowed_paths'] ?? []),
            static fn ($path) => $path !== ''
        ));

        $cleanSites = array_values(array_unique(array_map(
            static fn ($siteId) => (int) $siteId,
            $data['allowed_sites'] ?? []
        )));

        $payload = [
            'provider' => $data['provider'],
            'api_base_url' => $data['api_base_url'] ?? null,
            'model_name' => $data['model_name'] ?? null,
            'ai_models' => $this->sanitizeModelSlots($data['ai_models'] ?? [], $existing),
            'temperature' => $data['temperature'] ?? 0.7,
            'tone' => $data['tone'] ?? null,
            'max_tokens' => $data['max_tokens'] ?? null,
            'top_p' => $data['top_p'] ?? null,
            'frequency_penalty' => $data['frequency_penalty'] ?? null,
            'presence_penalty' => $data['presence_penalty'] ?? null,
            'allowed_paths' => $cleanPaths,
            'allowed_sites' => $cleanSites,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        $incomingApiKey = array_key_exists('api_key', $data) ? trim((string) $data['api_key']) : null;
        if ($incomingApiKey !== null && $incomingApiKey !== '') {
            $payload['api_key'] = $incomingApiKey;
        } elseif ($existing) {
            $payload['api_key'] = $existing->api_key;
        }

        try {
            $saved = $this->configs->upsertForUser((int) $user->id, $payload);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => 'AI agent storage is not ready',
                'message' => $e->getMessage(),
            ], 503);
        }

        return response()->json([
            'message' => 'AI agent settings saved.',
            'config' => $this->serializeConfig($saved),
        ]);
    }

    public function generateSection(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|min:1|max:12000',
            'model_key' => 'nullable|string|in:big_main,big_alternate,medium_main,medium_alternate,small_main,small_alternate',
            'context_mode' => 'nullable|string|in:none,previous,next,adjacent,all,selected',
            'context_section_ids' => 'nullable|array',
            'context_section_ids.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $section = Section::with('page.site')->find($id);
        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        $config = $this->configs->findForUser((int) $user->id);
        if (!$config) {
            return response()->json(['error' => 'AI agent config was not found.'], 422);
        }

        $data = $validator->validated();

        try {
            $html = $this->aiAgentService->generateSectionHtml(
                section: $section,
                config: $config,
                prompt: $data['prompt'],
                modelKey: $data['model_key'] ?? 'medium_main',
                contextMode: $data['context_mode'] ?? 'none',
                selectedSectionIds: array_map('intval', $data['context_section_ids'] ?? []),
                mandatoryRule: $this->ruleForSection($section)
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Generation successful.',
            'html' => $html,
        ]);
    }

    public function showPromptRule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_set' => 'required|string|max:100',
            'page_key' => 'required|string|max:100',
            'field_key' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $rule = AiPromptRule::where($data)->first();

        return response()->json(['rule' => $rule?->rule ?? '']);
    }

    public function savePromptRule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_set' => 'required|string|max:100',
            'page_key' => 'required|string|max:100',
            'field_key' => 'required|string|max:255',
            'rule' => 'nullable|string|max:12000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $rule = AiPromptRule::updateOrCreate(
            [
                'template_set' => $data['template_set'],
                'page_key' => $data['page_key'],
                'field_key' => $data['field_key'],
            ],
            ['rule' => trim((string) ($data['rule'] ?? ''))]
        );

        return response()->json(['rule' => $rule->rule ?? '']);
    }

    public function generatePageField(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'field_key' => ['required', 'string', 'regex:/^(title|meta_title|meta_description|head_meta\.(3|4)\.content)$/'],
            'prompt' => 'required|string|min:1|max:12000',
            'model_key' => 'nullable|string|in:big_main,big_alternate,medium_main,medium_alternate,small_main,small_alternate',
            'context_mode' => 'nullable|string|in:none,all,selected',
            'context_section_ids' => 'nullable|array',
            'context_section_ids.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = Page::with('site')->find($id);
        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $config = $this->configs->findForUser((int) $user->id);
        if (!$config) {
            return response()->json(['error' => 'AI agent config was not found.'], 422);
        }

        $data = $validator->validated();
        $field = $data['field_key'];
        $currentValue = $this->pageFieldValue($page, $field);

        try {
            $value = $this->aiAgentService->rewriteFieldValue(
                currentValue: $currentValue,
                prompt: $data['prompt'],
                fieldPath: "pages.0.{$field}",
                config: $config,
                modelKey: $data['model_key'] ?? 'medium_main',
                mandatoryRule: $this->ruleForPageField($page, $field),
                context: $this->pageFieldContext(
                    $page,
                    $data['context_mode'] ?? 'none',
                    array_map('intval', $data['context_section_ids'] ?? [])
                )
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['value' => $value]);
    }

    private function pageFieldValue(Page $page, string $field): string
    {
        if (preg_match('/^head_meta\.(\d+)\.content$/', $field, $matches)) {
            return (string) data_get($page->og_data, "head_meta.{$matches[1]}.content", '');
        }

        return (string) ($page->{$field} ?? '');
    }

    /** @param array<int, int> $selectedSectionIds */
    private function pageFieldContext(Page $page, string $mode, array $selectedSectionIds): string
    {
        if ($mode === 'none') {
            return '';
        }

        $sections = $page->sections()->orderBy('order')->get();
        if ($mode === 'selected') {
            $sections = $sections->filter(fn (Section $section) => in_array((int) $section->id, $selectedSectionIds, true));
        }

        return $sections->map(function (Section $section): string {
            $content = is_array($section->content) ? $section->content : [];
            $html = (string) ($content['raw_html'] ?? $section->raw_html ?? '');
            $module = (string) ($section->module ?? $content['module'] ?? $content['module_key'] ?? $section->type);

            return "Module #{$section->id} ({$module}):\n{$html}";
        })->implode("\n\n---\n\n");
    }

    private function ruleForSection(Section $section): string
    {
        $section->loadMissing('page.site');
        $templateSet = (string) ($section->page?->site?->template_set ?? '');
        $pageKey = (string) ($section->page?->template_key ?: $section->page?->slug ?: 'page');
        $content = is_array($section->content) ? $section->content : [];
        $module = (string) ($content['module'] ?? $content['module_key'] ?? $section->type);

        return (string) (AiPromptRule::where([
            'template_set' => $templateSet,
            'page_key' => $pageKey,
            'field_key' => "{$module}/module_prompt",
        ])->value('rule') ?? '');
    }

    private function ruleForPageField(Page $page, string $field): string
    {
        $page->loadMissing('site');

        return (string) (AiPromptRule::where([
            'template_set' => (string) ($page->site?->template_set ?? ''),
            'page_key' => (string) ($page->template_key ?: $page->slug ?: 'page'),
            'field_key' => $field,
        ])->value('rule') ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConfig(AiAgentConfig $config): array
    {
        $payload = $config->toArray();
        $payload['has_api_key'] = !empty($config->getRawOriginal('api_key'));
        $payload['ai_models'] = $this->aiAgentService->modelSlots($config);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $modelSlots
     * @return array<string, array<string, mixed>>
     */
    private function sanitizeModelSlots(array $modelSlots, ?AiAgentConfig $existing): array
    {
        $defaults = $this->aiAgentService->modelSlots();
        $existingSlots = is_array($existing?->ai_models) ? $existing->ai_models : [];
        $clean = [];

        foreach ($defaults as $key => $defaultSlot) {
            $slot = isset($modelSlots[$key]) && is_array($modelSlots[$key]) ? $modelSlots[$key] : [];
            $existingSlot = isset($existingSlots[$key]) && is_array($existingSlots[$key]) ? $existingSlots[$key] : [];
            $incomingApiKey = trim((string) ($slot['api_key'] ?? ''));
            $clean[$key] = [
                'provider' => trim((string) ($slot['provider'] ?? $defaultSlot['provider'])),
                'api_base_url' => trim((string) ($slot['api_base_url'] ?? $defaultSlot['api_base_url'])),
                'model_name' => trim((string) ($slot['model_name'] ?? $defaultSlot['model_name'])),
                'label' => trim((string) ($slot['label'] ?? $defaultSlot['label'])),
                'temperature' => $this->nullableFloat($slot['temperature'] ?? $defaultSlot['temperature'] ?? null),
                'tone' => trim((string) ($slot['tone'] ?? $defaultSlot['tone'] ?? '')),
                'max_tokens' => $this->nullableInt($slot['max_tokens'] ?? $defaultSlot['max_tokens'] ?? null),
                'top_p' => $this->nullableFloat($slot['top_p'] ?? $defaultSlot['top_p'] ?? null),
                'frequency_penalty' => $this->nullableFloat($slot['frequency_penalty'] ?? $defaultSlot['frequency_penalty'] ?? null),
                'presence_penalty' => $this->nullableFloat($slot['presence_penalty'] ?? $defaultSlot['presence_penalty'] ?? null),
            ];

            if ($incomingApiKey !== '') {
                $clean[$key]['api_key'] = Crypt::encryptString($incomingApiKey);
            } elseif (isset($existingSlot['api_key']) && trim((string) $existingSlot['api_key']) !== '') {
                $clean[$key]['api_key'] = (string) $existingSlot['api_key'];
            }
        }

        return $clean;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}

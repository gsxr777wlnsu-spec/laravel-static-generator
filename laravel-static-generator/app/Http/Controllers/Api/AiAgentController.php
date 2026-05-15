<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AiAgentConfigRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\AiAgentConfig;
use App\Services\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /**
     * @return array<string, mixed>
     */
    private function serializeConfig(AiAgentConfig $config): array
    {
        $payload = $config->toArray();
        $payload['has_api_key'] = !empty($config->getRawOriginal('api_key'));

        return $payload;
    }
}

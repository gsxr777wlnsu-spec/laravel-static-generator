@extends('layouts.admin')

@section('title', 'AI Agent Settings')

@section('content')
<form id="ai-agent-form" class="space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">AI Agent Configuration</h3>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provider</label>
                    <select name="provider"
                            id="ai-provider-select"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach($providers as $provider)
                            <option value="{{ $provider['value'] }}" {{ ($config?->provider ?? 'openai') === $provider['value'] ? 'selected' : '' }}>
                                {{ $provider['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                    <input type="text" name="model_name" value="{{ $config?->model_name }}"
                           placeholder="gpt-4o-mini / claude-3-5-sonnet-latest"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                    <input type="password" name="api_key" autocomplete="off" placeholder="Leave empty to keep current key"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Base URL (optional)</label>
                    <input type="text" name="api_base_url" value="{{ $config?->api_base_url }}"
                           id="ai-api-base-url"
                           placeholder="https://api.openai.com/v1"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Model Slots</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Six selectable models for page module generation. Leave a slot API key empty to keep the current key or use the shared API key.</p>
                    <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @foreach(($modelSlots ?? []) as $slotKey => $slot)
                            <div class="ai-model-slot rounded-md border border-gray-200 p-3 dark:border-gray-700" data-slot-key="{{ $slotKey }}">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $slot['label'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $slot['group'] }} / {{ $slot['role'] }}</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Provider</label>
                                        <select data-ai-model-field="provider"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            @foreach($providers as $provider)
                                                <option value="{{ $provider['value'] }}" {{ ($slot['provider'] ?? 'openai') === $provider['value'] ? 'selected' : '' }}>
                                                    {{ $provider['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Model</label>
                                        <input type="text" data-ai-model-field="model_name" value="{{ $slot['model_name'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Base URL</label>
                                        <input type="text" data-ai-model-field="api_base_url" value="{{ $slot['api_base_url'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">API Key</label>
                                        <input type="password" data-ai-model-field="api_key" autocomplete="off"
                                               placeholder="{{ ($slot['has_api_key'] ?? false) ? 'Current key saved' : 'Use shared key' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Temperature</label>
                                        <input type="number" step="0.01" min="0" max="2" data-ai-model-field="temperature" value="{{ $slot['temperature'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tone</label>
                                        <input type="text" data-ai-model-field="tone" value="{{ $slot['tone'] ?? '' }}" placeholder="fallback to shared"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Max Tokens</label>
                                        <input type="number" min="1" max="128000" data-ai-model-field="max_tokens" value="{{ $slot['max_tokens'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Top P</label>
                                        <input type="number" step="0.01" min="0" max="1" data-ai-model-field="top_p" value="{{ $slot['top_p'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Frequency Penalty</label>
                                        <input type="number" step="0.01" min="-2" max="2" data-ai-model-field="frequency_penalty" value="{{ $slot['frequency_penalty'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Presence Penalty</label>
                                        <input type="number" step="0.01" min="-2" max="2" data-ai-model-field="presence_penalty" value="{{ $slot['presence_penalty'] ?? '' }}"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Temperature</label>
                    <input type="number" step="0.01" min="0" max="2" name="temperature" value="{{ $config?->temperature ?? 0.7 }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tone</label>
                    <input type="text" name="tone" value="{{ $config?->tone }}" placeholder="formal / concise / friendly"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Tokens</label>
                    <input type="number" min="1" max="128000" name="max_tokens" value="{{ $config?->max_tokens }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Top P</label>
                    <input type="number" step="0.01" min="0" max="1" name="top_p" value="{{ $config?->top_p }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequency Penalty</label>
                    <input type="number" step="0.01" min="-2" max="2" name="frequency_penalty" value="{{ $config?->frequency_penalty }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Presence Penalty</label>
                    <input type="number" step="0.01" min="-2" max="2" name="presence_penalty" value="{{ $config?->presence_penalty }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Access Control</h3>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Allowed Paths (one per line)</label>
                    <textarea id="allowed_paths" rows="5"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ implode("\n", $config?->allowed_paths ?? []) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Allowed Sites</label>
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @php
                            $allowedSites = array_map('intval', $config?->allowed_sites ?? []);
                        @endphp
                        @foreach($sites as $site)
                            <label class="flex items-center gap-2 rounded-md border border-gray-200 dark:border-gray-700 p-2">
                                <input type="checkbox" class="allowed-site-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                       value="{{ $site->id }}" {{ in_array((int) $site->id, $allowedSites, true) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $site->name }} ({{ $site->domain }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ ($config?->is_active ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">AI Agent Active</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit"
                class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
            Save Settings
        </button>
    </div>
    <div id="ai-agent-status" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
        <div id="ai-agent-status-text"></div>
    </div>
</form>

<script>
async function readApiResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    return {
        error: `Server returned non-JSON response (HTTP ${response.status})`,
        raw_body: await response.text(),
    };
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function renderAiAgentStatus(message, tone = 'error') {
    const statusBox = document.getElementById('ai-agent-status');
    const statusText = document.getElementById('ai-agent-status-text');

    if (!statusBox || !statusText) {
        return;
    }

    statusBox.classList.remove(
        'hidden',
        'border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100',
        'border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100'
    );

    statusBox.classList.add(...(tone === 'success'
        ? ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100']
        : ['border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100']));
    statusText.textContent = message;
}

const aiProviderBaseUrls = {
    openai: 'https://api.openai.com/v1',
    openrouter: 'https://openrouter.ai/api/v1',
    closerouter: 'https://api.closerouter.dev/v1',
    together: 'https://api.together.xyz/v1',
    fireworks: 'https://api.fireworks.ai/inference/v1',
    groq: 'https://api.groq.com/openai/v1',
    deepseek: 'https://api.deepseek.com/v1',
    xai: 'https://api.x.ai/v1',
    mistral: 'https://api.mistral.ai/v1',
};

document.getElementById('ai-provider-select')?.addEventListener('change', function () {
    const baseUrlInput = document.getElementById('ai-api-base-url');
    const baseUrl = aiProviderBaseUrls[this.value] || '';

    if (!baseUrlInput || baseUrl === '') {
        return;
    }

    baseUrlInput.value = baseUrl;
});

document.getElementById('ai-agent-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    data.is_active = Boolean(formData.get('is_active'));
    data.ai_models = {};

    document.querySelectorAll('.ai-model-slot').forEach((slot) => {
        const slotKey = slot.dataset.slotKey;
        if (!slotKey) {
            return;
        }

        data.ai_models[slotKey] = {};
        slot.querySelectorAll('[data-ai-model-field]').forEach((field) => {
            data.ai_models[slotKey][field.dataset.aiModelField] = field.value.trim();
        });
    });

    if (!data.model_name && data.ai_models.medium_main?.model_name) {
        data.model_name = data.ai_models.medium_main.model_name;
    }

    const allowedPathsRaw = document.getElementById('allowed_paths')?.value || '';
    data.allowed_paths = allowedPathsRaw
        .split('\n')
        .map(line => line.trim())
        .filter(Boolean);

    data.allowed_sites = Array.from(document.querySelectorAll('.allowed-site-checkbox:checked'))
        .map(element => Number(element.value))
        .filter(value => Number.isInteger(value) && value > 0);

    const numericFields = ['temperature', 'top_p', 'frequency_penalty', 'presence_penalty', 'max_tokens'];
    numericFields.forEach((field) => {
        if (data[field] === '' || data[field] === undefined) {
            data[field] = null;
            return;
        }

        data[field] = field === 'max_tokens' ? parseInt(data[field], 10) : parseFloat(data[field]);
    });

    Object.values(data.ai_models).forEach((slot) => {
        numericFields.forEach((field) => {
            if (slot[field] === '' || slot[field] === undefined) {
                slot[field] = null;
                return;
            }

            slot[field] = field === 'max_tokens' ? parseInt(slot[field], 10) : parseFloat(slot[field]);
        });
    });

    try {
        const response = await fetch('/api/ai-agent/config', {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        });

        const result = await readApiResponse(response);
        if (!response.ok) {
            const errorMessage = result?.errors
                ? JSON.stringify(result.errors)
                : (result?.error || result?.message || `Request failed with status ${response.status}`);

            renderAiAgentStatus('Error: ' + errorMessage, 'error');
            return;
        }

        renderAiAgentStatus('AI agent settings saved.', 'success');
    } catch (error) {
        renderAiAgentStatus('Error: ' + error.message, 'error');
    }
});
</script>
@endsection

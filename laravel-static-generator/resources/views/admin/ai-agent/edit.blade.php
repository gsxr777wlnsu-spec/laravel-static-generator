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
                           placeholder="https://api.openai.com/v1"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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

document.getElementById('ai-agent-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    data.is_active = Boolean(formData.get('is_active'));

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

            alert('Error: ' + errorMessage);
            return;
        }

        alert('AI agent settings saved.');
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
@endsection

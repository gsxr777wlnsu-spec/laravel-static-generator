@extends('layouts.admin')

@section('title', 'Create Site')

@section('content')
<form method="POST" action="/api/sites" class="space-y-6">
    @csrf
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Site Information</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" name="name" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Domain</label>
                    <input type="text" name="domain" required placeholder="example.com"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Set</label>
                    <input type="text" name="template_set" value="base" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Output Path</label>
                    <input type="text" name="output_path" required placeholder="generated/example"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Locale</label>
                    <input type="text" name="locale" value="en"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SFTP Configuration (Optional)</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SFTP Host</label>
                    <input type="text" name="sftp_host" placeholder="sftp.example.com"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SFTP Port</label>
                    <input type="number" name="sftp_port" value="22"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" name="sftp_username"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auth Method</label>
                    <select name="sftp_auth_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="key">SSH Key</option>
                        <option value="password">Password</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" name="sftp_password"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remote Path</label>
                    <input type="text" name="sftp_remote_path" placeholder="/var/www/site.com"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">AI Content Generation (Optional)</h3>
                <label class="inline-flex items-center gap-2">
                    <input id="ai_clone_templates" type="checkbox" name="ai_clone_templates" value="1" checked
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Clone MD Templates On Create</span>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Source Template Folder</label>
                    <input id="ai_source_domain" type="text" name="ai_source_domain" value="{{ $aiSourceDomain ?? 'test.com' }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">AI Config Status</label>
                    <div class="mt-1 rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm {{ ($hasActiveAiConfig ?? false) ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300' }}">
                        {{ ($hasActiveAiConfig ?? false) ? 'Configured and active' : 'No active API key in /admin/ai-agent' }}
                    </div>
                </div>
            </div>

            @if(empty($templateFieldCatalog))
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    Template field catalog is unavailable. Site can still be created and folder cloned.
                </p>
            @else
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">MD Fields and Prompts</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        You can edit field values manually and/or add prompts for AI rewrites.
                    </p>

                    <div class="space-y-10">
                        @foreach($templateFieldCatalog as $fileItem)
                            <details class="rounded-md border border-gray-200 dark:border-gray-700">
                                <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $fileItem['file'] }}
                                </summary>

                                <div class="space-y-5 border-t border-gray-200 dark:border-gray-700 px-3 py-3">
                                    @foreach(($fileItem['page_fields'] ?? []) as $field)
                                        <div class="ai-prompt-row rounded-md border border-gray-200 dark:border-gray-700 p-3"
                                             data-file="{{ $fileItem['file'] }}"
                                             data-path="{{ $field['path'] }}">
                                            <div class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                Page field: {{ $field['field'] }} ({{ $field['length'] }} chars)
                                            </div>
                                            <textarea rows="{{ $field['input_rows'] ?? 2 }}" class="ai-manual-input mb-2 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                      placeholder="Edit field value manually">{{ $field['value'] ?? '' }}</textarea>
                                            <textarea rows="2" class="ai-prompt-input block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                      placeholder="Instruction for AI to rewrite this field"></textarea>
                                        </div>
                                    @endforeach

                                    @foreach(($fileItem['section_fields'] ?? []) as $field)
                                        <div class="ai-prompt-row rounded-md border border-gray-200 dark:border-gray-700 p-3"
                                             data-file="{{ $fileItem['file'] }}"
                                             data-path="{{ $field['path'] }}"
                                             data-prompt-path="{{ $field['prompt_path'] ?? $field['path'] }}">
                                            <div class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $field['field'] }} ({{ $field['length'] }} chars)
                                            </div>
                                            @if(array_key_exists('value', $field))
                                                <textarea rows="2" class="ai-manual-input mb-2 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                          placeholder="Edit field value manually">{{ $field['value'] ?? '' }}</textarea>
                                            @else
                                                <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $field['value_preview'] }}
                                                </div>
                                            @endif

                                            @if(($field['show_prompt'] ?? true) === true)
                                                <textarea rows="2" class="ai-prompt-input block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                          placeholder="Instruction for AI to rewrite this module field"></textarea>
                                            @elseif(($field['prompt_path'] ?? '') !== ($field['path'] ?? ''))
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Shared AI prompt is attached to the first line of this heading.
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="flex justify-end space-x-3">
        <a href="/admin/sites" class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
            Cancel
        </a>
        <button type="submit" class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
            Create Site
        </button>
    </div>
    <div id="site-create-status" role="status" aria-live="polite"
         class="hidden rounded-md border border-gray-300 bg-gray-50 px-3 py-3 text-sm text-gray-800 shadow-sm dark:border-blue-800 dark:bg-blue-950 dark:text-gray-200">
        <div class="flex items-start gap-2">
            <span class="mt-1 inline-flex h-3 w-3 shrink-0 animate-pulse rounded-full bg-blue-600 dark:bg-blue-700"></span>
            <div class="min-w-0">
                <div class="font-medium text-gray-900 dark:text-white">Creation In Progress</div>
                <div id="site-create-status-text" class="mt-0.5 text-gray-700 dark:text-blue-300"></div>
            </div>
        </div>
    </div>
</form>

<script>
async function readApiResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    const rawBody = await response.text();

    return {
        error: `Server returned non-JSON response (HTTP ${response.status})`,
        raw_body: rawBody,
    };
}

function makeSiteCreateDebugId() {
    if (globalThis.crypto && typeof globalThis.crypto.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    return `site-create-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

const siteCreateForm = document.querySelector('form');
let isSubmitting = false;
// TODO(PROD): remove temporary create-site client debug flow (debug ID, console logs, extra debug payload/header, timeout diagnostics).
const SITE_CREATE_TIMEOUT_MS = 180000;

siteCreateForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (isSubmitting) {
        return;
    }

    isSubmitting = true;

    const submitButton = this.querySelector('button[type="submit"]');
    const statusBox = document.getElementById('site-create-status');
    const statusText = document.getElementById('site-create-status-text');
    const initialSubmitText = submitButton?.textContent || 'Create Site';
    const debugRequestId = makeSiteCreateDebugId();
    const startedAt = Date.now();

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.classList.add('opacity-60', 'cursor-not-allowed');
        submitButton.textContent = 'Creating...';
    }

    if (statusBox && statusText) {
        statusText.textContent = `Site creation started (debug ID: ${debugRequestId}). Please wait while templates are cloned and AI generation is running.`;
        statusBox.classList.remove('hidden');
    }

    let data = null;
    let shouldResetSubmitState = true;
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), SITE_CREATE_TIMEOUT_MS);

    try {
        const formData = new FormData(this);
        data = Object.fromEntries(formData);

        // Convert numbers
        data.sftp_port = data.sftp_port ? parseInt(data.sftp_port, 10) : null;
        data.ai_clone_templates = document.getElementById('ai_clone_templates')?.checked === true;
        data.ai_source_domain = String(document.getElementById('ai_source_domain')?.value || '').trim();
        data.debug_request_id = debugRequestId;
        data.ai_field_prompts = Array.from(document.querySelectorAll('.ai-prompt-row'))
            .reduce((acc, row) => {
                const promptInput = row.querySelector('.ai-prompt-input');
                if (!promptInput) {
                    return acc;
                }

                const promptPath = row.dataset.promptPath || row.dataset.path;
                const prompt = promptInput.value?.trim() || '';
                if (!prompt) {
                    return acc;
                }

                if (!row.dataset.file || !promptPath) {
                    throw new Error(`Prompt row is missing file/path metadata (debug ID: ${debugRequestId})`);
                }

                const dedupeKey = `${row.dataset.file}::${promptPath}`;
                if (acc.some((item) => `${item.file}::${item.path}` === dedupeKey)) {
                    return acc;
                }

                acc.push({
                    file: row.dataset.file,
                    path: promptPath,
                    prompt,
                });

                return acc;
            }, [])
            ;

        data.ai_field_edits = Array.from(document.querySelectorAll('.ai-prompt-row'))
            .map((row) => {
                const manualInput = row.querySelector('.ai-manual-input');
                if (!manualInput) {
                    return null;
                }

                const currentValue = manualInput.value ?? '';
                const initialValue = manualInput.defaultValue ?? '';
                if (currentValue === initialValue) {
                    return null;
                }

                if (!row.dataset.file || !row.dataset.path) {
                    throw new Error(`Manual edit row is missing file/path metadata (debug ID: ${debugRequestId})`);
                }

                return {
                    file: row.dataset.file,
                    path: row.dataset.path,
                    value: currentValue,
                };
            })
            .filter(Boolean);

        console.info('[site-create] request:start', {
            debugId: debugRequestId,
            domain: data.domain || null,
            name: data.name || null,
            aiCloneTemplates: data.ai_clone_templates === true,
            aiPromptCount: Array.isArray(data.ai_field_prompts) ? data.ai_field_prompts.length : 0,
            aiEditCount: Array.isArray(data.ai_field_edits) ? data.ai_field_edits.length : 0,
        });

        const response = await fetch('/api/sites', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Site-Create-Debug-Id': debugRequestId,
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
            signal: controller.signal
        });
        const result = await readApiResponse(response);
        const responseDebugId = result?.debug_id || response.headers.get('X-Site-Create-Debug-Id') || debugRequestId;
        const durationMs = Date.now() - startedAt;

        console.info('[site-create] request:response', {
            debugId: responseDebugId,
            httpStatus: response.status,
            durationMs,
            responseOk: response.ok,
        });
        
        if (response.ok) {
            shouldResetSubmitState = false;
            const updatedPaths = Array.isArray(result?.ai_generation?.updated_paths)
                ? result.ai_generation.updated_paths.filter((path) => typeof path === 'string' && path.trim() !== '')
                : [];
            const manualUpdatedPaths = Array.isArray(result?.ai_generation?.manual_updated_paths)
                ? result.ai_generation.manual_updated_paths.filter((path) => typeof path === 'string' && path.trim() !== '')
                : [];

            let successMessage = 'Site created successfully!';
            if (manualUpdatedPaths.length > 0) {
                successMessage += '\n\nManual field updates:\n- ' + manualUpdatedPaths.join('\n- ');
            }
            if (updatedPaths.length > 0) {
                successMessage += '\n\nAI updated fields:\n- ' + updatedPaths.join('\n- ');
            } else if (result?.ai_generation?.enabled === true && manualUpdatedPaths.length === 0) {
                successMessage += '\n\nAI completed without field rewrites.';
            }
            successMessage += `\n\nDebug ID: ${responseDebugId}`;

            alert(successMessage);
            window.location.href = '/admin/sites';
        } else {
            let errorMessage = `Request failed with status ${response.status}`;

            if (result?.errors) {
                errorMessage = JSON.stringify(result.errors);
            } else if (result?.error && result?.message) {
                errorMessage = `${result.error}: ${result.message}`;
            } else if (result?.error) {
                errorMessage = result.error;
            } else if (result?.message) {
                errorMessage = result.message;
            }

            alert('Error: ' + errorMessage + `\n\nDebug ID: ${responseDebugId}`);
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            console.error('[site-create] request:timeout', {
                debugId: debugRequestId,
                durationMs: Date.now() - startedAt,
            });
            alert('Error: Site creation request timed out after 180 seconds. The request may still be processing on the server. Please refresh /admin/sites and check whether the site was created.' + `\n\nDebug ID: ${debugRequestId}`);
        } else {
            console.error('[site-create] request:error', {
                debugId: debugRequestId,
                durationMs: Date.now() - startedAt,
                requestDataReady: data !== null,
                message: error?.message || String(error),
            });
            alert('Error: ' + error.message + `\n\nDebug ID: ${debugRequestId}`);
        }
    } finally {
        clearTimeout(timeoutId);

        if (shouldResetSubmitState) {
            isSubmitting = false;

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
                submitButton.textContent = initialSubmitText;
            }

            if (statusBox && statusText) {
                statusBox.classList.add('hidden');
                statusText.textContent = '';
            }
        }
    }
});
</script>
@endsection

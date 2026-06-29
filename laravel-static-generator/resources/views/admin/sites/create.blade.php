@extends('layouts.admin')

@section('title', 'Create Site')

@section('content')
<form method="POST" action="/api/sites" class="space-y-6">
    @csrf
    <div class="space-y-3">
        <div class="flex justify-end">
            <label style="background-color: #059669;" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 cursor-pointer">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import
                <input type="file" id="create-site-import-file" accept=".md,.txt" class="hidden">
            </label>
        </div>
        <div id="site-import-status" class="hidden rounded-md border px-4 py-3 shadow-sm">
            <div id="site-import-status-summary" class="text-sm font-medium"></div>
            <ul id="site-import-status-warnings" class="mt-2 list-disc space-y-1 pl-5 text-sm"></ul>
        </div>
    </div>

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
                            @if(($fileItem['file'] ?? '') === 'index-raw_html.md')
                                @include('admin.sites.partials.create-index-raw-html-fields', [
                                    'fileItem' => $fileItem,
                                    'moduleCatalog' => $moduleCatalog ?? [],
                                ])
                            @else
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
                                                Page field: {{ $field['field'] }} (<span class="ai-field-length">{{ $field['length'] }}</span> chars)
                                            </div>
                                            <textarea rows="{{ $field['input_rows'] ?? 2 }}" data-default-rows="{{ $field['input_rows'] ?? 2 }}" class="ai-manual-input mb-2 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                      placeholder="Edit field value manually">{{ $field['value'] ?? '' }}</textarea>
                                            <textarea rows="2" class="ai-prompt-input block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                      placeholder="Instruction for AI to rewrite this field"></textarea>
                                            <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                                <input type="checkbox" class="ai-send-current-value-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                                <span>Send current value to AI</span>
                                            </label>
                                        </div>
                                    @endforeach

                                    @foreach(($fileItem['section_fields'] ?? []) as $field)
                                        <div class="ai-prompt-row rounded-md border border-gray-200 dark:border-gray-700 p-3"
                                             data-file="{{ $fileItem['file'] }}"
                                             data-path="{{ $field['path'] }}"
                                             data-prompt-path="{{ $field['prompt_path'] ?? $field['path'] }}">
                                            <div class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $field['field'] }} (<span class="ai-field-length">{{ $field['length'] }}</span> chars)
                                            </div>
                                            @if(array_key_exists('value', $field))
                                                <textarea rows="2" data-default-rows="2" class="ai-manual-input mb-2 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                          placeholder="Edit field value manually">{{ $field['value'] ?? '' }}</textarea>
                                            @else
                                                <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $field['value_preview'] }}
                                                </div>
                                            @endif

                                            @if(($field['show_prompt'] ?? true) === true)
                                                <textarea rows="2" class="ai-prompt-input block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                          placeholder="Instruction for AI to rewrite this module field"></textarea>
                                                <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                                    <input type="checkbox" class="ai-send-current-value-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                                    <span>Send current value to AI</span>
                                                </label>
                                            @elseif(($field['prompt_path'] ?? '') !== ($field['path'] ?? ''))
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Shared AI prompt is attached to the first line of this heading.
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                </div>
                            </details>
                            @endif
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
    <div id="site-create-report" class="hidden rounded-md border px-4 py-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div id="site-create-report-title" class="text-sm font-semibold"></div>
            <div class="flex flex-wrap items-center gap-2">
                <button id="site-create-report-copy" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:text-white dark:hover:bg-indigo-400">
                    Copy Report
                </button>
                <a id="site-create-report-edit-link" href="#" class="hidden inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Edit Site
                </a>
                <a href="/admin/sites" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                    Back to Sites
                </a>
            </div>
        </div>
        <div id="site-create-report-meta" class="mt-2 text-xs text-gray-600 dark:text-gray-300"></div>
        <pre id="site-create-report-body" class="mt-3 overflow-x-auto whitespace-pre-wrap rounded-md bg-white px-4 py-3 text-xs leading-6 text-gray-900 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-white dark:ring-gray-700"></pre>
    </div>
    <div id="site-create-feedback" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
        <div id="site-create-feedback-text"></div>
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
const pendingBlockOperations = [];
const queuedBlockOperationKeys = new Set();
const importedHiddenFieldEdits = [];
const pendingImageReplacements = [];

function parseImportMultiline(lines, startIndex) {
    const marker = lines[startIndex]?.trim() || '';
    if (!marker.startsWith('```')) {
        return {
            value: '',
            nextIndex: startIndex,
        };
    }

    const valueLines = [];
    let index = startIndex + 1;
    while (index < lines.length && lines[index].trim() !== '```') {
        valueLines.push(lines[index]);
        index += 1;
    }

    return {
        value: valueLines.join('\n'),
        nextIndex: index,
    };
}

function parseImportVariableDeclaration(rawLine) {
    const variableMatch = rawLine.match(/^\{([A-Za-z0-9_:-]+)\}\s*=\s*(.*)$/);
    if (!variableMatch) {
        return null;
    }

    return {
        name: variableMatch[1],
        value: variableMatch[2].trim(),
    };
}

function substituteImportVariables(value, variables) {
    let output = String(value ?? '');

    Object.entries(variables || {}).forEach(([name, replacement]) => {
        output = output.replaceAll(`{${name}}`, String(replacement ?? ''));
    });

    return output;
}

function escapeImportVariableForJson(value) {
    return JSON.stringify(String(value ?? '')).slice(1, -1);
}

function substituteImportVariablesForJsonLd(value, variables) {
    let output = String(value ?? '');

    Object.entries(variables || {}).forEach(([name, replacement]) => {
        output = output.replaceAll(`{${name}}`, escapeImportVariableForJson(replacement));
    });

    return output;
}

function buildAlternateHref(canonicalUrl, lang, isDefaultLang = false) {
    const canonical = String(canonicalUrl || '').trim();
    const normalizedLang = String(lang || '').trim();

    if (canonical === '' || normalizedLang === '') {
        return '';
    }

    if (isDefaultLang) {
        return canonical;
    }

    return `${canonical.replace(/\/+$/, '')}/${normalizedLang}/`;
}

function appendAlternateLangBlocks(blocks, alternateLangs, canonicalUrl) {
    const langs = Array.from(new Set((alternateLangs || [])
        .map((lang) => String(lang || '').trim())
        .filter((lang) => lang !== '')));

    if (langs.length < 2) {
        return;
    }

    langs.forEach((lang, index) => {
        const href = buildAlternateHref(canonicalUrl, lang, index === 0);
        if (href === '') {
            return;
        }

        [
            ['rel', 'alternate'],
            ['href', href],
            ['hreflang', lang],
        ].forEach(([field, value]) => {
            blocks.push({
                type: 'FIELD',
                values: {
                    file: 'index-raw_html.md',
                    path: `pages.0.og_data.head_links.${index}.${field}`,
                    value,
                },
            });
        });
    });
}

function collectImportVariableTokens(value) {
    const matches = String(value ?? '').match(/\{([A-Za-z0-9_:-]+)\}/g) || [];
    return matches.map((token) => token.slice(1, -1));
}

function parseCreateSiteImportTemplate(rawText) {
    const lines = String(rawText || '').replaceAll('\r\n', '\n').replaceAll('\r', '\n').split('\n');
    const blocks = [];
    const variables = {};
    const variableValues = {};
    const variableDeclarationCounts = {};
    const variableUsage = {};
    const warnings = [];
    let current = null;

    const pushCurrent = () => {
        if (current) {
            blocks.push(current);
        }
    };

    const registerVariableUsage = (value) => {
        collectImportVariableTokens(value).forEach((name) => {
            variableUsage[name] = (variableUsage[name] || 0) + 1;
        });
    };

    for (let index = 0; index < lines.length; index += 1) {
        const rawLine = lines[index];
        const line = rawLine.trim();
        const blockMatch = line.match(/^\[(FORM|FIELD|OPERATION)\]$/);

        if (blockMatch) {
            pushCurrent();
            current = {
                type: blockMatch[1],
                values: {},
            };
            continue;
        }

        if (!current) {
            if (line === '' || line.startsWith('#')) {
                continue;
            }

            const variable = parseImportVariableDeclaration(rawLine);
            if (variable) {
                variableDeclarationCounts[variable.name] = (variableDeclarationCounts[variable.name] || 0) + 1;
                variables[variable.name] = substituteImportVariables(variable.value, variables);
                variableValues[variable.name] = variableValues[variable.name] || [];
                variableValues[variable.name].push(variables[variable.name]);
                registerVariableUsage(variable.value);
            }

            continue;
        }

        if (line === '' || line.startsWith('#')) {
            continue;
        }

        const multilineMatch = rawLine.match(/^([A-Za-z0-9_]+):\s*$/);
        if (multilineMatch) {
            const parsed = parseImportMultiline(lines, index + 1);
            registerVariableUsage(parsed.value);
            current.values[multilineMatch[1]] = parsed.value.includes('<script type="application/ld+json">')
                ? substituteImportVariablesForJsonLd(parsed.value, variables)
                : substituteImportVariables(parsed.value, variables);
            index = parsed.nextIndex;
            continue;
        }

        const scalarMatch = rawLine.match(/^([A-Za-z0-9_]+)\s*=\s*(.*)$/);
        if (scalarMatch) {
            registerVariableUsage(scalarMatch[2].trim());
            current.values[scalarMatch[1]] = substituteImportVariables(scalarMatch[2].trim(), variables);
        }
    }

    pushCurrent();

    const hasCanonicalUrl = Object.prototype.hasOwnProperty.call(variables, 'canonical_url') && String(variables.canonical_url || '').trim() !== '';

    if (!hasCanonicalUrl) {
        warnings.push('Variable {canonical_url} is required.');
    }

    if (Array.isArray(variableValues.alternate_lang) && variableValues.alternate_lang.length > 0) {
        if (hasCanonicalUrl) {
            appendAlternateLangBlocks(blocks, variableValues.alternate_lang, variables.canonical_url);
        }
    }

    Object.entries(variableDeclarationCounts).forEach(([name, count]) => {
        if (count > 1 && name !== 'alternate_lang') {
            warnings.push(`Variable {${name}} is declared ${count} times. Last value wins.`);
        }
    });

    Object.keys(variableUsage).forEach((name) => {
        if (!Object.prototype.hasOwnProperty.call(variables, name)) {
            warnings.push(`Variable {${name}} is used but not declared at the top of the file.`);
        }
    });

    Object.keys(variables).forEach((name) => {
        if (!variableUsage[name] && name !== 'alternate_lang') {
            warnings.push(`Variable {${name}} is declared but not used anywhere in the import file.`);
        }
    });

    return {
        blocks,
        warnings,
    };
}

function parseImportJsonArray(value) {
    const trimmed = String(value || '').trim();
    if (trimmed === '') {
        return [];
    }

    try {
        const parsed = JSON.parse(trimmed);
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return trimmed
            .split('\n')
            .map((line) => line.trim())
            .filter((line) => line !== '');
    }
}

function findPromptRow(file, path) {
    return Array.from(document.querySelectorAll('.ai-prompt-row'))
        .find((row) => row.dataset.file === file && row.dataset.path === path) || null;
}

function getPromptRowValue(path) {
    const row = findPromptRow('index-raw_html.md', path);
    return row?.querySelector('.ai-manual-input')?.value ?? '';
}

function setPromptRowValue(path, value) {
    const row = findPromptRow('index-raw_html.md', path);
    const input = row?.querySelector('.ai-manual-input');
    if (!row || !input) {
        return false;
    }

    input.value = String(value ?? '');
    updatePromptRowLength(row, input.value);
    resizeManualInputRows(input, input.value);
    return true;
}

function normalizeJsonLdUrl(value, origin) {
    const rawValue = String(value ?? '').trim();
    const rawOrigin = String(origin ?? '').replace(/\/+$/, '');
    if (rawValue === '' || rawOrigin === '') {
        return rawValue;
    }

    if (rawValue.startsWith('//')) {
        return `https:${rawValue}`;
    }

    if (rawValue.startsWith('/')) {
        return `${rawOrigin}${rawValue}`;
    }

    try {
        const url = new URL(rawValue);
        return `${rawOrigin}${url.pathname}${url.search}${url.hash}`;
    } catch (error) {
        return rawValue;
    }
}

function siteOriginFromCanonical(canonical, domain) {
    const rawCanonical = String(canonical ?? '').trim();
    if (rawCanonical !== '') {
        try {
            return new URL(rawCanonical).origin;
        } catch (error) {
            // Fall through to domain.
        }
    }

    const rawDomain = String(domain ?? '').trim();
    return rawDomain === '' ? '' : `https://${rawDomain.replace(/^https?:\/\//i, '').replace(/\/+$/, '')}`;
}

function currentHeadState() {
    const canonical = getPromptRowValue('pages.0.canonical');
    const formName = String(siteCreateForm?.elements?.name?.value || '').trim();
    const formDomain = String(siteCreateForm?.elements?.domain?.value || '').trim();
    const metaTitle = getPromptRowValue('pages.0.meta_title') || getPromptRowValue('pages.0.title') || formName;
    const metaDescription = getPromptRowValue('pages.0.meta_description');
    const locale = getPromptRowValue('pages.0.locale') || String(siteCreateForm?.elements?.locale?.value || '').trim();
    const origin = siteOriginFromCanonical(canonical, formDomain);

    return {
        name: formName,
        domain: formDomain,
        canonical,
        origin,
        metaTitle,
        metaDescription,
        locale,
        robots: getPromptRowValue('pages.0.og_data.head_meta.0.content'),
        ogLocale: getPromptRowValue('pages.0.og_data.head_meta.2.content') || locale,
        ogTitle: getPromptRowValue('pages.0.og_data.head_meta.3.content') || metaTitle,
        ogDescription: getPromptRowValue('pages.0.og_data.head_meta.4.content') || metaDescription,
        publishedTime: getPromptRowValue('pages.0.og_data.head_meta.5.content'),
        modifiedTime: getPromptRowValue('pages.0.og_data.head_meta.6.content'),
    };
}

function syncJsonLdNode(node, state) {
    if (Array.isArray(node)) {
        return node.map((item) => syncJsonLdNode(item, state));
    }

    if (!node || typeof node !== 'object') {
        return node;
    }

    const next = { ...node };
    const type = String(next['@type'] || '');

    if (typeof next['@id'] === 'string') {
        next['@id'] = normalizeJsonLdUrl(next['@id'], state.origin);
    }
    if (typeof next.url === 'string') {
        next.url = normalizeJsonLdUrl(next.url, state.origin);
    }
    if (typeof next.image === 'string') {
        next.image = normalizeJsonLdUrl(next.image, state.origin);
    }
    if (next.logo && typeof next.logo === 'object' && typeof next.logo.url === 'string') {
        next.logo = { ...next.logo, url: normalizeJsonLdUrl(next.logo.url, state.origin) };
    }

    if (type === 'WebPage') {
        if (state.canonical !== '') {
            next.url = state.canonical;
        }
        if (state.metaTitle !== '') {
            next.name = state.metaTitle;
        }
        if (state.metaDescription !== '') {
            next.description = state.metaDescription;
        }
        if (state.locale !== '') {
            next.inLanguage = state.locale;
        }
        if (state.publishedTime !== '') {
            next.datePublished = state.publishedTime;
        }
        if (state.modifiedTime !== '') {
            next.dateModified = state.modifiedTime;
        }
    }

    if (type === 'VideoGame') {
        if (state.canonical !== '') {
            next.url = state.canonical;
        }
        if (state.metaTitle !== '') {
            next.name = state.metaTitle;
        }
        if (state.metaDescription !== '') {
            next.description = state.metaDescription;
        }
    }

    if (type === 'Organization') {
        if (state.origin !== '') {
            next.url = `${state.origin}/`;
        }
        if (state.name !== '') {
            next.name = state.name;
        } else if (state.domain !== '') {
            next.name = state.domain;
        }
    }

    if (next.mainEntity) {
        next.mainEntity = syncJsonLdNode(next.mainEntity, state);
    }
    if (next['@graph']) {
        next['@graph'] = syncJsonLdNode(next['@graph'], state);
    }
    if (next.itemListElement) {
        next.itemListElement = syncJsonLdNode(next.itemListElement, state);
    }

    return next;
}

function syncPrimaryJsonLdFromHeadFields() {
    const path = 'pages.0.og_data.head_extra.__script__.0';
    const currentValue = getPromptRowValue(path);
    if (String(currentValue).trim() === '') {
        return false;
    }

    let parsed = null;
    try {
        parsed = JSON.parse(currentValue);
    } catch (error) {
        return false;
    }

    const state = currentHeadState();
    const synced = syncJsonLdNode(parsed, state);
    return setPromptRowValue(path, JSON.stringify(synced, null, 2));
}

function setImportedFormValue(name, value) {
    if (name === 'ai_clone_templates') {
        const checkbox = document.getElementById('ai_clone_templates');
        if (checkbox) {
            checkbox.checked = ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
            return true;
        }

        return false;
    }

    if (name === 'ai_source_domain') {
        const input = document.getElementById('ai_source_domain');
        if (input) {
            input.value = value;
            return true;
        }

        return false;
    }

    const field = siteCreateForm?.elements?.[name];
    if (!field) {
        return false;
    }

    field.value = value;
    return true;
}

function buildImportedOperation(values) {
    const operation = {
        file: values.file || 'index-raw_html.md',
        section_path: values.section_path || '',
        action: values.action || '',
    };

    if (!operation.section_path || !operation.action) {
        return null;
    }

    const copyScalar = (from, to = from) => {
        if (Object.prototype.hasOwnProperty.call(values, from)) {
            operation[to] = values[from];
        }
    };

    [
        'module',
        'target_key',
        'container_key',
        'anchor_key',
        'anchor_position',
        'tag',
        'class',
        'item_class',
        'aria_label',
        'list_tag',
        'value',
        'value_prompt',
        'text',
        'text_prompt',
        'icon_src',
        'icon_alt',
        'col1',
        'col1_prompt',
        'col2',
        'col2_prompt',
        'row_class',
    ].forEach((key) => copyScalar(key));

    if (operation.action === 'add_list_block') {
        operation.items = parseImportJsonArray(values.items || '');
        operation.item_prompts = parseImportJsonArray(values.item_prompts || '');
    }

    if (operation.action === 'add_table_block') {
        operation.headers = parseImportJsonArray(values.headers || '');
        operation.header_prompts = parseImportJsonArray(values.header_prompts || '');
        operation.rows = parseImportJsonArray(values.rows || '');
        operation.row_prompts = parseImportJsonArray(values.row_prompts || '');
    }

    return operation;
}

function resetPendingBlockOperations() {
    pendingBlockOperations.splice(0, pendingBlockOperations.length);
    queuedBlockOperationKeys.clear();

    document.querySelectorAll('.ai-queued-block-editor').forEach((editor) => {
        const previousElement = editor.previousElementSibling;
        if (previousElement instanceof HTMLButtonElement) {
            restoreQueueButton(previousElement);
        }

        editor.remove();
    });

    renderBlockOperations();
}

function resetCreateSiteImportState() {
    siteCreateForm?.reset();
    importedHiddenFieldEdits.splice(0, importedHiddenFieldEdits.length);
    pendingImageReplacements.splice(0, pendingImageReplacements.length);

    document.querySelectorAll('.ai-prompt-row').forEach((row) => {
        const manualInput = row.querySelector('.ai-manual-input');
        const promptInput = row.querySelector('.ai-prompt-input');
        const sendCurrentValueCheckbox = row.querySelector('.ai-send-current-value-checkbox');

        if (manualInput) {
            manualInput.value = manualInput.defaultValue ?? '';
            updatePromptRowLength(row, manualInput.value);
            resizeManualInputRows(manualInput, manualInput.value);
        }

        if (promptInput) {
            promptInput.value = promptInput.defaultValue ?? '';
        }

        if (sendCurrentValueCheckbox) {
            sendCurrentValueCheckbox.checked = sendCurrentValueCheckbox.defaultChecked;
        }

        const imageReplacementName = row.querySelector('.ai-image-replacement-name');
        if (imageReplacementName) {
            imageReplacementName.textContent = '';
            imageReplacementName.classList.add('hidden');
        }
    });
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(reader.error || new Error('Could not read file.'));
        reader.readAsDataURL(file);
    });
}

function buildReplacementImageSrc(currentSrc, fileName) {
    const cleanFileName = String(fileName || 'image').replace(/[^A-Za-z0-9._-]/g, '-');
    const normalizedSrc = String(currentSrc || '').trim();
    const slashIndex = normalizedSrc.lastIndexOf('/');

    if (slashIndex < 0) {
        return cleanFileName;
    }

    return `${normalizedSrc.slice(0, slashIndex + 1)}${cleanFileName}`;
}

function updatePromptRowLength(row, value) {
    const lengthNode = row?.querySelector('.ai-field-length');
    if (lengthNode) {
        lengthNode.textContent = String(String(value ?? '').length);
    }
}

function resizeManualInputRows(input, value) {
    const defaultRows = Number(input?.dataset?.defaultRows || input?.getAttribute('rows') || 2);
    const lineCount = String(value ?? '').split('\n').length;
    input.rows = Math.min(Math.max(defaultRows, lineCount), 60);
}

function openPromptRowDetails(row) {
    let details = row?.closest('details') || null;
    while (details) {
        details.open = true;
        details = details.parentElement?.closest('details') || null;
    }
}

function editableJsonLdValue(path, value) {
    const rawValue = String(value ?? '');
    if (!String(path || '').includes('.og_data.head_extra.__script__.')) {
        return rawValue;
    }

    const match = rawValue.match(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/i);
    return match ? match[1].trim() : rawValue;
}

function hiddenJsonLdScriptPath(path) {
    const match = String(path || '').match(/\.og_data\.head_extra\.__script__\.(\d+)$/);
    return match ? Number(match[1]) > 0 : false;
}

function primaryJsonLdScriptPath(path) {
    return String(path || '') === 'pages.0.og_data.head_extra.__script__.0';
}

function primaryModifiedTimeMetaPath() {
    return 'pages.0.og_data.head_meta.6.content';
}

function primaryPublishedTimeMetaPath() {
    return 'pages.0.og_data.head_meta.5.content';
}

function currentUtcIsoTimestamp() {
    return new Date().toISOString().replace(/\.\d{3}Z$/, '+00:00');
}

let modifiedTimeSyncState = {
    publishedAutoManaged: true,
    modifiedAutoManaged: true,
    isApplyingProgrammaticSync: false,
};

function withModifiedTimeSyncGuard(callback) {
    modifiedTimeSyncState.isApplyingProgrammaticSync = true;
    try {
        return callback();
    } finally {
        modifiedTimeSyncState.isApplyingProgrammaticSync = false;
    }
}

function extractJsonLdModifiedTime(value) {
    return extractJsonLdWebPageDate(value, 'dateModified');
}

function extractJsonLdPublishedTime(value) {
    return extractJsonLdWebPageDate(value, 'datePublished');
}

function extractJsonLdWebPageDate(value, dateKey) {
    const rawValue = String(value ?? '').trim();
    if (rawValue === '') {
        return '';
    }

    let parsed = null;
    try {
        parsed = JSON.parse(rawValue);
    } catch (error) {
        return '';
    }

    const queue = [parsed];
    while (queue.length > 0) {
        const node = queue.shift();

        if (Array.isArray(node)) {
            queue.push(...node);
            continue;
        }

        if (!node || typeof node !== 'object') {
            continue;
        }

        if (String(node['@type'] || '') === 'WebPage' && typeof node[dateKey] === 'string') {
            return node[dateKey].trim();
        }

        if (node.mainEntity) {
            queue.push(node.mainEntity);
        }
        if (node['@graph']) {
            queue.push(node['@graph']);
        }
        if (node.itemListElement) {
            queue.push(node.itemListElement);
        }
    }

    return '';
}

function setPublishedTimeSyncMode(autoManaged) {
    modifiedTimeSyncState.publishedAutoManaged = autoManaged;
}

function setModifiedTimeSyncMode(autoManaged) {
    modifiedTimeSyncState.modifiedAutoManaged = autoManaged;
}

function setDateEverywhere(metaPath, value, syncMode, options = {}) {
    const normalizedValue = String(value ?? '').trim();
    const {
        autoManaged = false,
        syncJsonLd = true,
    } = options;

    return withModifiedTimeSyncGuard(() => {
        setPromptRowValue(metaPath, normalizedValue);

        if (syncJsonLd) {
            syncPrimaryJsonLdFromHeadFields();
        }

        syncMode(autoManaged);
        return true;
    });
}

function setModifiedTimeEverywhere(value, options = {}) {
    return setDateEverywhere(primaryModifiedTimeMetaPath(), value, setModifiedTimeSyncMode, options);
}

function setPublishedTimeEverywhere(value, options = {}) {
    return setDateEverywhere(primaryPublishedTimeMetaPath(), value, setPublishedTimeSyncMode, options);
}

function syncDateFromJsonLd(metaPath, extractor, syncMode, options = {}) {
    const jsonLdValue = getPromptRowValue('pages.0.og_data.head_extra.__script__.0');
    const extractedValue = extractor(jsonLdValue);
    const {
        autoManaged = false,
    } = options;

    return withModifiedTimeSyncGuard(() => {
        setPromptRowValue(metaPath, extractedValue);
        syncMode(autoManaged);
        return extractedValue;
    });
}

function syncModifiedTimeFromJsonLd(options = {}) {
    return syncDateFromJsonLd(primaryModifiedTimeMetaPath(), extractJsonLdModifiedTime, setModifiedTimeSyncMode, options);
}

function syncPublishedTimeFromJsonLd(options = {}) {
    return syncDateFromJsonLd(primaryPublishedTimeMetaPath(), extractJsonLdPublishedTime, setPublishedTimeSyncMode, options);
}

function applyImportedPublishedTime(preferredSource = null) {
    const importedMetaValue = getPromptRowValue(primaryPublishedTimeMetaPath()).trim();
    const importedJsonLdValue = extractJsonLdPublishedTime(getPromptRowValue('pages.0.og_data.head_extra.__script__.0'));

    if (preferredSource === 'jsonld' && importedJsonLdValue !== '') {
        syncPublishedTimeFromJsonLd({ autoManaged: false });
        return;
    }

    if (preferredSource === 'meta' && importedMetaValue !== '') {
        setPublishedTimeEverywhere(importedMetaValue, { autoManaged: false });
        return;
    }

    if (importedJsonLdValue !== '') {
        syncPublishedTimeFromJsonLd({ autoManaged: false });
        return;
    }

    if (importedMetaValue !== '') {
        setPublishedTimeEverywhere(importedMetaValue, { autoManaged: false });
        return;
    }

    setPublishedTimeEverywhere(currentUtcIsoTimestamp(), { autoManaged: true });
}

function applyImportedModifiedTime(preferredSource = null) {
    const importedMetaValue = getPromptRowValue(primaryModifiedTimeMetaPath()).trim();
    const importedJsonLdValue = extractJsonLdModifiedTime(getPromptRowValue('pages.0.og_data.head_extra.__script__.0'));

    if (preferredSource === 'jsonld' && importedJsonLdValue !== '') {
        syncModifiedTimeFromJsonLd({ autoManaged: false });
        return;
    }

    if (preferredSource === 'meta' && importedMetaValue !== '') {
        setModifiedTimeEverywhere(importedMetaValue, { autoManaged: false });
        return;
    }

    if (importedJsonLdValue !== '') {
        syncModifiedTimeFromJsonLd({ autoManaged: false });
        return;
    }

    if (importedMetaValue !== '') {
        setModifiedTimeEverywhere(importedMetaValue, { autoManaged: false });
        return;
    }

    setModifiedTimeEverywhere(currentUtcIsoTimestamp(), { autoManaged: true });
}

function refreshAutoManagedDates() {
    if (!modifiedTimeSyncState.publishedAutoManaged && !modifiedTimeSyncState.modifiedAutoManaged) {
        return false;
    }

    const timestamp = currentUtcIsoTimestamp();

    return withModifiedTimeSyncGuard(() => {
        if (modifiedTimeSyncState.publishedAutoManaged) {
            setPromptRowValue(primaryPublishedTimeMetaPath(), timestamp);
        }

        if (modifiedTimeSyncState.modifiedAutoManaged) {
            setPromptRowValue(primaryModifiedTimeMetaPath(), timestamp);
        }

        syncPrimaryJsonLdFromHeadFields();
        return true;
    });
}

function queueImageReplacement(row, file, dataUrl, nextSrc) {
    const key = `${row.dataset.file || ''}::${row.dataset.path || ''}`;
    const existingIndex = pendingImageReplacements.findIndex((item) => `${item.file}::${item.path}` === key);
    const replacement = {
        file: row.dataset.file || 'index-raw_html.md',
        path: row.dataset.path || '',
        src: nextSrc,
        filename: file.name || 'image',
        data_url: dataUrl,
    };

    if (existingIndex >= 0) {
        pendingImageReplacements[existingIndex] = replacement;
        return;
    }

    pendingImageReplacements.push(replacement);
}

function applyCreateSiteImportTemplate(rawText) {
    const parsedImport = parseCreateSiteImportTemplate(rawText);
    const blocks = parsedImport.blocks || [];
    let importedPublishedTimeSource = null;
    let importedModifiedTimeSource = null;

    resetCreateSiteImportState();
    resetPendingBlockOperations();

    const stats = {
        form: 0,
        fields: 0,
        operations: 0,
        skipped: 0,
        warnings: parsedImport.warnings || [],
    };

    blocks.forEach((block) => {
        const values = block.values || {};

        if (block.type === 'FORM') {
            Object.entries(values).forEach(([key, value]) => {
                stats.form += setImportedFormValue(key, value) ? 1 : 0;
            });
            return;
        }

        if (block.type === 'FIELD') {
            const row = findPromptRow(values.file || 'index-raw_html.md', values.path || '');
            if (!row) {
                const importedValue = Object.prototype.hasOwnProperty.call(values, 'value')
                    ? String(values.value ?? '')
                    : null;

                if (hiddenJsonLdScriptPath(values.path || '')) {
                    stats.skipped += 1;
                    stats.warnings.push(`Skipped hidden JSON-LD script field: ${values.path}`);
                    return;
                }

                if (values.file && values.path && importedValue !== null) {
                    importedHiddenFieldEdits.push({
                        file: values.file,
                        path: values.path,
                        value: importedValue,
                    });
                    stats.fields += 1;
                } else {
                    stats.skipped += 1;
                }
                return;
            }

            const manualInput = row.querySelector('.ai-manual-input');
            const promptInput = row.querySelector('.ai-prompt-input');
            const sendCurrentValueCheckbox = row.querySelector('.ai-send-current-value-checkbox');
            if (manualInput && Object.prototype.hasOwnProperty.call(values, 'value')) {
                const fieldPath = values.path || row.dataset.path || '';
                const nextValue = editableJsonLdValue(fieldPath, values.value);
                if (!(primaryJsonLdScriptPath(fieldPath) && String(nextValue).trim() === '')) {
                    manualInput.value = nextValue;
                    updatePromptRowLength(row, manualInput.value);
                    resizeManualInputRows(manualInput, manualInput.value);

                    if (fieldPath === primaryPublishedTimeMetaPath() && String(nextValue).trim() !== '') {
                        importedPublishedTimeSource = 'meta';
                    } else if (primaryJsonLdScriptPath(fieldPath) && extractJsonLdPublishedTime(nextValue) !== '') {
                        importedPublishedTimeSource = 'jsonld';
                    }

                    if (fieldPath === primaryModifiedTimeMetaPath() && String(nextValue).trim() !== '') {
                        importedModifiedTimeSource = 'meta';
                    } else if (primaryJsonLdScriptPath(fieldPath) && extractJsonLdModifiedTime(nextValue) !== '') {
                        importedModifiedTimeSource = 'jsonld';
                    }
                }
            }
            if (promptInput && Object.prototype.hasOwnProperty.call(values, 'prompt')) {
                promptInput.value = values.prompt;
            }
            if (sendCurrentValueCheckbox && Object.prototype.hasOwnProperty.call(values, 'send_current_value')) {
                sendCurrentValueCheckbox.checked = ['1', 'true', 'yes', 'on'].includes(String(values.send_current_value).toLowerCase());
            }

            stats.fields += 1;
            return;
        }

        if (block.type === 'OPERATION') {
            if (!['1', 'true', 'yes', 'on'].includes(String(values.enabled || '').toLowerCase())) {
                return;
            }

            const operation = buildImportedOperation(values);
            if (!operation) {
                stats.skipped += 1;
                return;
            }

            const label = values.label || `${operation.section_path} ${operation.action}`;
            stats.operations += queueBlockOperation(operation, label) ? 1 : 0;
        }
    });

    renderBlockOperations();
    applyImportedPublishedTime(importedPublishedTimeSource);
    applyImportedModifiedTime(importedModifiedTimeSource);
    syncPrimaryJsonLdFromHeadFields();

    return stats;
}

function renderImportStatus(stats) {
    const statusBox = document.getElementById('site-import-status');
    const summary = document.getElementById('site-import-status-summary');
    const warningsList = document.getElementById('site-import-status-warnings');

    if (!statusBox || !summary || !warningsList) {
        return;
    }

    const warnings = Array.isArray(stats?.warnings) ? stats.warnings : [];

    statusBox.classList.remove(
        'hidden',
        'border-emerald-200',
        'bg-emerald-50',
        'text-emerald-900',
        'dark:border-emerald-800',
        'dark:bg-emerald-950',
        'dark:text-emerald-100',
        'border-amber-200',
        'bg-amber-50',
        'text-amber-900',
        'dark:border-amber-800',
        'dark:bg-amber-950',
        'dark:text-amber-100'
    );

    if (warnings.length > 0) {
        statusBox.classList.add(
            'border-amber-200',
            'bg-amber-50',
            'text-amber-900',
            'dark:border-amber-800',
            'dark:bg-amber-950',
            'dark:text-amber-100'
        );
    } else {
        statusBox.classList.add(
            'border-emerald-200',
            'bg-emerald-50',
            'text-emerald-900',
            'dark:border-emerald-800',
            'dark:bg-emerald-950',
            'dark:text-emerald-100'
        );
    }

    summary.textContent = `Import loaded. Form fields: ${stats.form}. Page/template fields: ${stats.fields}. Queued structural operations: ${stats.operations}. Skipped blocks: ${stats.skipped}.`;
    warningsList.innerHTML = warnings.map((warning) => `<li>${escapeHtml(warning)}</li>`).join('');
    warningsList.classList.toggle('hidden', warnings.length === 0);
}

function renderCreateStatusBlock(element, textElement, message, tone = 'error') {
    if (!element || !textElement) {
        return;
    }

    element.classList.remove(
        'hidden',
        'border-emerald-200',
        'bg-emerald-50',
        'text-emerald-900',
        'dark:border-emerald-800',
        'dark:bg-emerald-950',
        'dark:text-emerald-100',
        'border-amber-200',
        'bg-amber-50',
        'text-amber-900',
        'dark:border-amber-800',
        'dark:bg-amber-950',
        'dark:text-amber-100',
        'border-rose-200',
        'bg-rose-50',
        'text-rose-900',
        'dark:border-rose-800',
        'dark:bg-rose-950',
        'dark:text-rose-100',
        'border-blue-200',
        'bg-blue-50',
        'text-blue-900',
        'dark:border-blue-800',
        'dark:bg-blue-950',
        'dark:text-blue-100'
    );

    const toneClasses = tone === 'success'
        ? ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100']
        : (tone === 'warning'
            ? ['border-amber-200', 'bg-amber-50', 'text-amber-900', 'dark:border-amber-800', 'dark:bg-amber-950', 'dark:text-amber-100']
            : (tone === 'info'
                ? ['border-blue-200', 'bg-blue-50', 'text-blue-900', 'dark:border-blue-800', 'dark:bg-blue-950', 'dark:text-blue-100']
                : ['border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100']));

    element.classList.add(...toneClasses);
    textElement.textContent = message;
}

function renderSiteCreateFeedback(message, tone = 'error') {
    renderCreateStatusBlock(
        document.getElementById('site-create-feedback'),
        document.getElementById('site-create-feedback-text'),
        message,
        tone
    );
}

function buildSiteCreateReport(result, responseDebugId) {
    const aiGeneration = result?.ai_generation || {};
    const updatedPaths = Array.isArray(aiGeneration.updated_paths)
        ? aiGeneration.updated_paths.filter((path) => typeof path === 'string' && path.trim() !== '')
        : [];
    const manualUpdatedPaths = Array.isArray(aiGeneration.manual_updated_paths)
        ? aiGeneration.manual_updated_paths.filter((path) => typeof path === 'string' && path.trim() !== '')
        : [];
    const blockUpdatedPaths = Array.isArray(aiGeneration.block_updated_paths)
        ? aiGeneration.block_updated_paths.filter((path) => typeof path === 'string' && path.trim() !== '')
        : [];

    const lines = [
        'Site created successfully.',
        '',
        `Site ID: ${result?.id ?? 'n/a'}`,
        `Name: ${result?.name ?? ''}`,
        `Domain: ${result?.domain ?? ''}`,
        `Template set: ${result?.template_set ?? ''}`,
        `Output path: ${result?.output_path ?? ''}`,
        `Status: ${result?.status ?? ''}`,
        `Locale: ${result?.locale ?? ''}`,
        `AI generation enabled: ${aiGeneration.enabled === true ? 'yes' : 'no'}`,
        `AI updated fields: ${aiGeneration.updated_fields ?? 0}`,
        `Manual updated fields: ${aiGeneration.manual_updated_fields ?? 0}`,
        `Block updated fields: ${aiGeneration.block_updated_fields ?? 0}`,
        `Debug ID: ${responseDebugId}`,
    ];

    if (blockUpdatedPaths.length > 0) {
        lines.push('', 'Block operations:', ...blockUpdatedPaths.map((path) => `- ${path}`));
    }

    if (manualUpdatedPaths.length > 0) {
        lines.push('', 'Manual field updates:', ...manualUpdatedPaths.map((path) => `- ${path}`));
    }

    if (updatedPaths.length > 0) {
        lines.push('', 'AI updated fields:', ...updatedPaths.map((path) => `- ${path}`));
    } else if (aiGeneration.enabled === true && manualUpdatedPaths.length === 0) {
        lines.push('', 'AI completed without field rewrites.');
    }

    if (result?.create_report?.stored_path) {
        lines.push('', `Saved report: ${result.create_report.stored_path}`);
    }

    return lines.join('\n');
}

function renderSiteCreateReport(result, responseDebugId) {
    const reportBox = document.getElementById('site-create-report');
    const reportTitle = document.getElementById('site-create-report-title');
    const reportMeta = document.getElementById('site-create-report-meta');
    const reportBody = document.getElementById('site-create-report-body');
    const reportEditLink = document.getElementById('site-create-report-edit-link');

    if (!reportBox || !reportTitle || !reportMeta || !reportBody || !reportEditLink) {
        return;
    }

    reportBox.classList.remove(
        'hidden',
        'border-emerald-200',
        'bg-emerald-50',
        'text-emerald-900',
        'dark:border-emerald-800',
        'dark:bg-emerald-950',
        'dark:text-emerald-100'
    );
    reportBox.classList.add(
        'border-emerald-200',
        'bg-emerald-50',
        'text-emerald-900',
        'dark:border-emerald-800',
        'dark:bg-emerald-950',
        'dark:text-emerald-100'
    );

    reportTitle.textContent = 'Site Creation Report';
    reportMeta.textContent = `Debug ID: ${responseDebugId}`;
    reportBody.textContent = String(result?.create_report?.text || buildSiteCreateReport(result, responseDebugId));

    if (result?.id) {
        reportEditLink.href = `/admin/sites/${result.id}/edit`;
        reportEditLink.classList.remove('hidden');
    } else {
        reportEditLink.classList.add('hidden');
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderBlockOperations() {
    const log = document.getElementById('ai-block-ops-log');
    if (!log) {
        return;
    }

    if (pendingBlockOperations.length === 0) {
        log.innerHTML = '<div>No operations queued.</div>';
        return;
    }

    log.innerHTML = pendingBlockOperations
        .map((item, index) => {
            const label = escapeHtml(item._label || item.action || 'operation');
            return `<div class="flex items-center justify-between gap-2 rounded bg-gray-100 px-2 py-1 dark:bg-gray-700">
                <span class="min-w-0 truncate">${label}</span>
                <button type="button" class="ai-block-op-remove-queued rounded bg-gray-200 px-2 py-0.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500" data-index="${index}">Remove</button>
            </div>`;
        })
        .join('');
}

function queueBlockOperation(operation, label) {
    if (!operation.queue_id && (operation.action === 'add_text' || operation.action === 'add_list_block' || operation.action === 'add_table_block')) {
        operation.queue_id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }

    const key = JSON.stringify(normalizeBlockOperation(operation));
    if (queuedBlockOperationKeys.has(key)) {
        return false;
    }

    queuedBlockOperationKeys.add(key);
    pendingBlockOperations.push({
        ...operation,
        _label: label,
        _key: key,
    });
    renderBlockOperations();
    return true;
}

function normalizeBlockOperation(value) {
    if (Array.isArray(value)) {
        return value.map((item) => normalizeBlockOperation(item));
    }

    if (value && typeof value === 'object') {
        return Object.keys(value)
            .filter((key) => key !== '_label' && key !== '_key')
            .sort()
            .reduce((acc, key) => {
                acc[key] = normalizeBlockOperation(value[key]);
                return acc;
            }, {});
    }

    return value;
}

function queueAndLockButton(button, operation, label) {
    if (!queueBlockOperation(operation, label)) {
        return;
    }

    if (!button) {
        return;
    }

    const lastOperation = pendingBlockOperations[pendingBlockOperations.length - 1];
    const isNewBlockQueueButton = button.dataset.action === 'add_text' || button.dataset.action === 'add_standard_block';
    if (!button.dataset.defaultText) {
        button.dataset.defaultText = button.textContent.trim();
    }

    button.disabled = true;
    button.classList.add('opacity-60', 'cursor-not-allowed');
    if (isNewBlockQueueButton) {
        button.textContent = 'Block Added to Queue';
        renderQueuedBlockEditor(button, lastOperation);
        return;
    }

    window.setTimeout(() => {
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-not-allowed');
    }, 700);
}

function findPendingBlockOperation(key) {
    return pendingBlockOperations.find((operation) => operation._key === key) || null;
}

function removePendingBlockOperation(key) {
    const index = pendingBlockOperations.findIndex((operation) => operation._key === key);
    if (index < 0) {
        return;
    }

    pendingBlockOperations.splice(index, 1);
    queuedBlockOperationKeys.delete(key);
    renderBlockOperations();
}

function restoreQueueButton(button) {
    if (!button) {
        return;
    }

    button.disabled = false;
    button.classList.remove('opacity-60', 'cursor-not-allowed');
    button.textContent = button.dataset.defaultText || button.textContent;
}

function renderQueuedTextField(label, value, prompt, valueClass, promptClass) {
    return `<div class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">${escapeHtml(label)} (${String(value || '').length} chars)</div>
        <textarea rows="2" class="${valueClass} ai-queued-value mb-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Edit field value manually">${escapeHtml(value || '')}</textarea>
        <textarea rows="2" class="${promptClass} ai-queued-prompt block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Instruction for AI to rewrite this field">${escapeHtml(prompt || '')}</textarea>
    </div>`;
}

function renderQueuedOperationFields(operation) {
    if (operation.action === 'add_text') {
        return renderQueuedTextField(
            `New ${String(operation.tag || 'text').toUpperCase()} block`,
            operation.value || '',
            operation.value_prompt || '',
            'ai-queued-text-value',
            'ai-queued-text-prompt'
        );
    }

    if (operation.action === 'add_list_block') {
        const items = Array.isArray(operation.items) ? operation.items : [];
        const prompts = Array.isArray(operation.item_prompts) ? operation.item_prompts : [];
        return items.map((value, index) => renderQueuedTextField(
            `New ${String(operation.list_tag || 'UL').toUpperCase()} item ${index + 1}`,
            value,
            prompts[index] || '',
            'ai-queued-list-item-value',
            'ai-queued-list-item-prompt'
        )).join('');
    }

    if (operation.action === 'add_table_block') {
        const headers = Array.isArray(operation.headers) ? operation.headers : [];
        const headerPrompts = Array.isArray(operation.header_prompts) ? operation.header_prompts : [];
        const rows = Array.isArray(operation.rows) ? operation.rows : [];
        const rowPrompts = Array.isArray(operation.row_prompts) ? operation.row_prompts : [];
        const headerFields = headers.map((value, index) => renderQueuedTextField(
            `New table header ${index + 1}`,
            value,
            headerPrompts[index] || '',
            'ai-queued-table-header-value',
            'ai-queued-table-header-prompt'
        )).join('');
        const rowFields = rows.map((row, rowIndex) => {
            if (!Array.isArray(row)) {
                return '';
            }

            return row.map((value, cellIndex) => renderQueuedTextField(
                `New table row ${rowIndex + 1} cell ${cellIndex + 1}`,
                value,
                rowPrompts[rowIndex]?.[cellIndex] || '',
                'ai-queued-table-cell-value',
                'ai-queued-table-cell-prompt'
            )).join('');
        }).join('');

        return headerFields + rowFields;
    }

    return '';
}

function syncQueuedOperationFromEditor(editor) {
    const key = editor.dataset.operationKey || '';
    const operation = findPendingBlockOperation(key);
    if (!operation) {
        return;
    }

    if (operation.action === 'add_text') {
        operation.value = editor.querySelector('.ai-queued-text-value')?.value || '';
        operation.value_prompt = editor.querySelector('.ai-queued-text-prompt')?.value?.trim() || '';
        return;
    }

    if (operation.action === 'add_list_block') {
        operation.items = Array.from(editor.querySelectorAll('.ai-queued-list-item-value')).map((input) => input.value || '');
        operation.item_prompts = Array.from(editor.querySelectorAll('.ai-queued-list-item-prompt')).map((input) => input.value?.trim() || '');
        return;
    }

    if (operation.action === 'add_table_block') {
        operation.headers = Array.from(editor.querySelectorAll('.ai-queued-table-header-value')).map((input) => input.value || '');
        operation.header_prompts = Array.from(editor.querySelectorAll('.ai-queued-table-header-prompt')).map((input) => input.value?.trim() || '');
        const headerCount = operation.headers.length;
        const cellValues = Array.from(editor.querySelectorAll('.ai-queued-table-cell-value')).map((input) => input.value || '');
        const cellPrompts = Array.from(editor.querySelectorAll('.ai-queued-table-cell-prompt')).map((input) => input.value?.trim() || '');
        operation.rows = [];
        operation.row_prompts = [];
        for (let index = 0; index < cellValues.length; index += headerCount) {
            operation.rows.push(cellValues.slice(index, index + headerCount));
            operation.row_prompts.push(cellPrompts.slice(index, index + headerCount));
        }
    }
}

function renderQueuedBlockEditor(button, operation) {
    if (!button || !operation?._key) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'ai-queued-block-editor mt-4 rounded-md border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-800 dark:bg-indigo-950/40';
    wrapper.dataset.operationKey = operation._key;
    wrapper.dataset.buttonAction = button.dataset.action || '';
    wrapper.innerHTML = `<div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold uppercase text-indigo-800 dark:text-indigo-200">Block Added to Queue</span>
        <button type="button" class="ai-queued-add-another inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">+</button>
        <button type="button" class="ai-queued-remove inline-flex items-center rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">-</button>
    </div>
    <div class="space-y-3">${renderQueuedOperationFields(operation)}</div>`;

    let insertAfter = button;
    while (insertAfter.nextElementSibling?.classList.contains('ai-queued-block-editor')) {
        insertAfter = insertAfter.nextElementSibling;
    }
    insertAfter.insertAdjacentElement('afterend', wrapper);
}

document.addEventListener('change', function (event) {
    const imageInput = event.target.closest('.ai-image-replacement-input');
    if (imageInput) {
        const row = imageInput.closest('.ai-prompt-row');
        const manualInput = row?.querySelector('.ai-manual-input');
        const file = imageInput.files?.[0] || null;
        if (!row || !manualInput || !file) {
            return;
        }

        const currentSrc = manualInput.value || manualInput.defaultValue || '';
        const nextSrc = buildReplacementImageSrc(currentSrc, file.name);
        readFileAsDataUrl(file)
            .then((dataUrl) => {
                manualInput.value = nextSrc;
                queueImageReplacement(row, file, dataUrl, nextSrc);

                const nameElement = row.querySelector('.ai-image-replacement-name');
                if (nameElement) {
                    nameElement.textContent = `Selected: ${file.name}`;
                    nameElement.classList.remove('hidden');
                }
            })
            .catch((error) => {
                renderSiteCreateFeedback('Image replacement failed: ' + (error?.message || String(error)), 'error');
            })
            .finally(() => {
                imageInput.value = '';
            });
        return;
    }

    const select = event.target.closest('.ai-standard-block-type');
    if (!select) {
        return;
    }

    const scope = select.closest('.ai-structural-control');
    if (!scope) {
        return;
    }

    const type = select.value || 'ul';
    scope.querySelectorAll('.ai-standard-block-panel').forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.standardPanel !== type);
    });
});

document.addEventListener('input', function (event) {
    const manualInput = event.target.closest('.ai-manual-input');
    if (manualInput) {
        const row = manualInput.closest('.ai-prompt-row');
        if (row) {
            updatePromptRowLength(row, manualInput.value);
            resizeManualInputRows(manualInput, manualInput.value);

            if (modifiedTimeSyncState.isApplyingProgrammaticSync) {
                return;
            }

            const rowPath = String(row.dataset.path || '');
            if (rowPath === primaryPublishedTimeMetaPath()) {
                syncPrimaryJsonLdFromHeadFields();
                setPublishedTimeSyncMode(false);
            } else if (rowPath === primaryModifiedTimeMetaPath()) {
                syncPrimaryJsonLdFromHeadFields();
                setModifiedTimeSyncMode(false);
            } else if (primaryJsonLdScriptPath(rowPath)) {
                syncPublishedTimeFromJsonLd({ autoManaged: false });
                syncModifiedTimeFromJsonLd({ autoManaged: false });
            } else if (!rowPath.includes('.og_data.head_extra.__script__.')) {
                syncPrimaryJsonLdFromHeadFields();
            }
        }
    }

    const editor = event.target.closest('.ai-queued-block-editor');
    if (!editor) {
        return;
    }

    syncQueuedOperationFromEditor(editor);
});

document.addEventListener('click', function (event) {
    const queuedRemoveButton = event.target.closest('.ai-queued-remove');
    if (queuedRemoveButton) {
        const editor = queuedRemoveButton.closest('.ai-queued-block-editor');
        const key = editor?.dataset.operationKey || '';
        if (key) {
            removePendingBlockOperation(key);
        }

        const scope = editor?.closest('.ai-structural-control');
        const buttonAction = editor?.dataset.buttonAction || '';
        const button = buttonAction !== '' ? scope?.querySelector(`.ai-block-op-btn[disabled][data-action="${buttonAction}"]`) : null;
        restoreQueueButton(button);
        editor?.remove();
        return;
    }

    const queuedAddAnotherButton = event.target.closest('.ai-queued-add-another');
    if (queuedAddAnotherButton) {
        const editor = queuedAddAnotherButton.closest('.ai-queued-block-editor');
        const scope = editor?.closest('.ai-structural-control');
        const buttonAction = editor?.dataset.buttonAction || '';
        const button = buttonAction !== '' ? scope?.querySelector(`.ai-block-op-btn[disabled][data-action="${buttonAction}"]`) : null;
        restoreQueueButton(button);
        return;
    }

    const removeQueuedButton = event.target.closest('.ai-block-op-remove-queued');
    if (removeQueuedButton) {
        const index = parseInt(removeQueuedButton.dataset.index || '-1', 10);
        if (!Number.isNaN(index) && index >= 0 && index < pendingBlockOperations.length) {
            const removed = pendingBlockOperations.splice(index, 1);
            const removedKey = removed[0]?._key;
            if (removedKey) {
                queuedBlockOperationKeys.delete(removedKey);
            }
            renderBlockOperations();
        }
        return;
    }

    const toggleAddButton = event.target.closest('.ai-inline-toggle-add');
    if (toggleAddButton) {
        const scope = toggleAddButton.closest('.ai-structural-control');
        const panel = scope?.querySelector('.ai-inline-add-panel');
        if (panel) {
            panel.classList.toggle('hidden');
        }
        return;
    }

    const inlineRemoveButton = event.target.closest('.ai-inline-remove-btn');
    if (inlineRemoveButton) {
        const scope = inlineRemoveButton.closest('.ai-structural-control');
        const section = inlineRemoveButton.closest('[data-section-path]');
        const file = scope?.dataset.file || section?.dataset.file || '';
        const sectionPath = scope?.dataset.sectionPath || section?.dataset.sectionPath || '';
        const action = inlineRemoveButton.dataset.action || '';
        const confirmLabel = inlineRemoveButton.dataset.confirmLabel || 'selected item';

        if (!file || !sectionPath || !action) {
            return;
        }

        if (!window.confirm(`Remove ${confirmLabel}?`)) {
            return;
        }

        const operation = {
            file,
            section_path: sectionPath,
            action,
        };

        if (action === 'remove_block') {
            operation.target_key = inlineRemoveButton.dataset.targetKey || scope?.dataset.blockKey || '';
            if (!operation.target_key) {
                return;
            }
        }

        if (action === 'remove_last_list_item' || action === 'remove_last_table_row') {
            operation.container_key = inlineRemoveButton.dataset.containerKey || scope?.dataset.blockKey || '';
            if (!operation.container_key) {
                return;
            }
        }

        queueAndLockButton(inlineRemoveButton, operation, `${sectionPath} ${action}: ${confirmLabel}`);
        return;
    }

    const button = event.target.closest('.ai-block-op-btn');
    if (!button) {
        return;
    }

    const scope = button.closest('.ai-structural-control') || button.closest('.ai-block-section');
    const section = button.closest('[data-section-path]');
    if (!section) {
        return;
    }

    const file = scope?.dataset.file || section.dataset.file || '';
    const sectionPath = scope?.dataset.sectionPath || section.dataset.sectionPath || '';
    const action = button.dataset.action || '';
    const anchorKey = button.dataset.anchorKey || scope?.dataset.blockKey || section.querySelector('.ai-block-insert-anchor')?.value?.trim() || '';
    const anchorPosition = button.dataset.anchorPosition || section.querySelector('.ai-block-insert-position')?.value?.trim() || 'after';
    if (!file || !sectionPath || !action) {
        return;
    }

    if (action === 'add_section') {
        const module = scope?.querySelector('.ai-section-module-select')?.value?.trim() || '';
        if (!module) {
            return;
        }

        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            module,
        }, `${sectionPath} add section ${module}`);
        return;
    }

    if (action === 'remove_block') {
        const select = scope?.querySelector('.ai-block-remove-select');
        const targetKey = select?.value?.trim() || '';
        if (!targetKey) {
            return;
        }

        const selectedText = select?.selectedOptions?.[0]?.textContent?.trim() || targetKey;
        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            target_key: targetKey,
        }, `${sectionPath} remove: ${selectedText}`);
        return;
    }

    if (action === 'add_text') {
        const tag = scope?.querySelector('.ai-block-text-tag')?.value?.trim() || '';
        const value = scope?.querySelector('.ai-block-text-value')?.value?.trim() || '';
        const valuePrompt = scope?.querySelector('.ai-block-text-prompt')?.value?.trim() || '';
        const className = scope?.querySelector('.ai-block-text-class')?.value?.trim() || '';
        if (!tag || !value) {
            return;
        }

        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            tag,
            value,
            value_prompt: valuePrompt,
            class: className,
            anchor_key: anchorKey,
            anchor_position: anchorPosition,
        }, `${sectionPath} add <${tag}>`);
        return;
    }

    if (action === 'add_list_block') {
        const listTag = scope?.querySelector('.ai-block-list-tag')?.value?.trim() || 'ul';
        const className = scope?.querySelector('.ai-block-list-class')?.value?.trim() || '';
        const rawItems = scope?.querySelector('.ai-block-list-items')?.value || '';
        const items = rawItems
            .split('\n')
            .map((item) => item.trim())
            .filter((item) => item !== '');

        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            list_tag: listTag,
            class: className,
            items,
            anchor_key: anchorKey,
            anchor_position: anchorPosition,
        }, `${sectionPath} add ${listTag} list`);
        return;
    }

    if (action === 'add_standard_block') {
        const blockType = scope?.querySelector('.ai-standard-block-type')?.value?.trim() || 'ul';
        const panel = scope?.querySelector(`.ai-standard-block-panel-${blockType}`);
        if (!panel) {
            return;
        }

        if (blockType === 'ul' || blockType === 'ol') {
            const className = panel.querySelector('.ai-standard-list-class')?.value?.trim() || '';
            const ariaLabel = panel.querySelector('.ai-standard-list-aria')?.value?.trim() || '';
            const itemClass = panel.querySelector('.ai-standard-list-item-class')?.value?.trim() || '';
            const listEntries = Array.from(panel.querySelectorAll('.ai-standard-list-item'))
                .map((input) => ({
                    value: input.value?.trim() || '',
                    prompt: input.closest('div')?.querySelector('.ai-standard-list-item-prompt')?.value?.trim() || '',
                }))
                .filter((entry) => entry.value !== '');
            const items = listEntries.map((entry) => entry.value);
            const itemPrompts = listEntries.map((entry) => entry.prompt);
            if (items.length === 0) {
                return;
            }

            queueAndLockButton(button, {
                file,
                section_path: sectionPath,
                action: 'add_list_block',
                list_tag: blockType,
                class: className,
                item_class: itemClass,
                aria_label: ariaLabel,
                items,
                item_prompts: itemPrompts,
                anchor_key: anchorKey,
                anchor_position: anchorPosition,
            }, `${sectionPath} add ${blockType.toUpperCase()} standard list`);
            return;
        }

        if (blockType === 'table') {
            const headerEntries = Array.from(panel.querySelectorAll('.ai-standard-table-header'))
                .map((input) => ({
                    value: input.value?.trim() || '',
                    prompt: input.closest('div')?.querySelector('.ai-standard-table-header-prompt')?.value?.trim() || '',
                }))
                .filter((entry) => entry.value !== '');
            const headers = headerEntries.map((entry) => entry.value);
            const headerPrompts = headerEntries.map((entry) => entry.prompt);
            const rowEntries = Array.from(panel.querySelectorAll('.ai-standard-table-row'))
                .map((row) => ({
                    cells: Array.from(row.querySelectorAll('.ai-standard-table-cell')).map((input) => input.value?.trim() || ''),
                    prompts: Array.from(row.querySelectorAll('.ai-standard-table-cell-prompt')).map((input) => input.value?.trim() || ''),
                }))
                .filter((row) => row.cells.some((value) => value !== ''));
            const rows = rowEntries.map((row) => row.cells);
            const rowPrompts = rowEntries.map((row) => row.prompts);
            if (headers.length === 0 || rows.length === 0) {
                return;
            }

            queueAndLockButton(button, {
                file,
                section_path: sectionPath,
                action: 'add_table_block',
                class: panel.querySelector('.ai-standard-table-class')?.value?.trim() || '',
                aria_label: panel.querySelector('.ai-standard-table-aria')?.value?.trim() || '',
                headers,
                header_prompts: headerPrompts,
                rows,
                row_prompts: rowPrompts,
                anchor_key: anchorKey,
                anchor_position: anchorPosition,
            }, `${sectionPath} add standard table`);
        }

        return;
    }

    if (action === 'add_list_item') {
        const containerKey = button.dataset.containerKey || scope?.dataset.blockKey || scope?.querySelector('.ai-block-list-container')?.value?.trim() || '';
        const value = scope?.querySelector('.ai-block-list-item-text')?.value?.trim() || '';
        const valuePrompt = scope?.querySelector('.ai-block-list-item-prompt')?.value?.trim() || '';
        const className = scope?.querySelector('.ai-block-list-item-class')?.value?.trim() || '';
        if (!containerKey || !value) {
            return;
        }

        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            container_key: containerKey,
            value,
            value_prompt: valuePrompt,
            class: className,
        }, `${sectionPath} add li`);
        return;
    }

    if (action === 'add_card_feature') {
        const containerKey = button.dataset.containerKey || scope?.dataset.blockKey || scope?.querySelector('.ai-block-feature-container')?.value?.trim() || '';
        const text = scope?.querySelector('.ai-block-feature-text')?.value?.trim() || '';
        const textPrompt = scope?.querySelector('.ai-block-feature-prompt')?.value?.trim() || '';
        const iconSrc = scope?.querySelector('.ai-block-feature-icon-src')?.value?.trim() || '/assets/svg/';
        const iconAlt = scope?.querySelector('.ai-block-feature-icon-alt')?.value?.trim() || '';
        if (!containerKey || !text) {
            return;
        }

        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            container_key: containerKey,
            text,
            text_prompt: textPrompt,
            icon_src: iconSrc,
            icon_alt: iconAlt,
        }, `${sectionPath} add card feature`);
        return;
    }

    if (action === 'add_table_row') {
        const containerKey = button.dataset.containerKey || scope?.dataset.blockKey || scope?.querySelector('.ai-block-table-container')?.value?.trim() || '';
        const col1 = scope?.querySelector('.ai-block-table-col1')?.value?.trim() || '';
        const col2 = scope?.querySelector('.ai-block-table-col2')?.value?.trim() || '';
        const col1Prompt = scope?.querySelector('.ai-block-table-col1-prompt')?.value?.trim() || '';
        const col2Prompt = scope?.querySelector('.ai-block-table-col2-prompt')?.value?.trim() || '';
        const rowClass = scope?.querySelector('.ai-block-table-row-class')?.value?.trim() || '';
        if (!containerKey || (!col1 && !col2)) {
            return;
        }

        queueAndLockButton(button, {
            file,
            section_path: sectionPath,
            action,
            container_key: containerKey,
            col1,
            col2,
            col1_prompt: col1Prompt,
            col2_prompt: col2Prompt,
            row_class: rowClass,
            anchor_key: anchorKey,
            anchor_position: anchorPosition,
        }, `${sectionPath} add table row`);
    }
});

renderBlockOperations();

document.getElementById('create-site-import-file')?.addEventListener('change', async function () {
    const file = this.files?.[0] || null;
    if (!file) {
        return;
    }

    try {
        const rawText = await file.text();
        const stats = applyCreateSiteImportTemplate(rawText);
        renderImportStatus(stats);
    } catch (error) {
        renderSiteCreateFeedback('Import failed: ' + (error?.message || String(error)), 'error');
    } finally {
        this.value = '';
    }
});

document.getElementById('site-create-report-copy')?.addEventListener('click', async function () {
    const reportBody = document.getElementById('site-create-report-body');
    const text = reportBody?.textContent || '';
    if (text === '') {
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        this.textContent = 'Copied';
        renderSiteCreateFeedback('Report copied to clipboard.', 'success');
        setTimeout(() => {
            this.textContent = 'Copy Report';
        }, 1500);
    } catch (error) {
        renderSiteCreateFeedback('Copy failed: ' + (error?.message || String(error)), 'error');
    }
});

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
        refreshAutoManagedDates();
        syncPrimaryJsonLdFromHeadFields();
        data.ai_field_prompts = Array.from(document.querySelectorAll('.ai-prompt-row'))
            .reduce((acc, row) => {
                const promptInput = row.querySelector('.ai-prompt-input');
                if (!promptInput) {
                    return acc;
                }

                const promptPath = row.dataset.promptPath || row.dataset.path;
                const prompt = promptInput.value?.trim() || '';
                const sendCurrentValueCheckbox = row.querySelector('.ai-send-current-value-checkbox');
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
                    send_current_value: sendCurrentValueCheckbox ? sendCurrentValueCheckbox.checked : true,
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

        for (const importedFieldEdit of importedHiddenFieldEdits) {
            const alreadyPresent = data.ai_field_edits.some((item) =>
                item.file === importedFieldEdit.file && item.path === importedFieldEdit.path
            );

            if (!alreadyPresent) {
                data.ai_field_edits.push(importedFieldEdit);
            }
        }

        data.ai_block_operations = pendingBlockOperations.map(({ _label, _key, ...operation }) => operation);
        data.ai_image_replacements = pendingImageReplacements.map((replacement) => ({ ...replacement }));

        console.info('[site-create] request:start', {
            debugId: debugRequestId,
            domain: data.domain || null,
            name: data.name || null,
            aiCloneTemplates: data.ai_clone_templates === true,
            aiPromptCount: Array.isArray(data.ai_field_prompts) ? data.ai_field_prompts.length : 0,
            aiEditCount: Array.isArray(data.ai_field_edits) ? data.ai_field_edits.length : 0,
            aiBlockOperationCount: Array.isArray(data.ai_block_operations) ? data.ai_block_operations.length : 0,
            aiImageReplacementCount: Array.isArray(data.ai_image_replacements) ? data.ai_image_replacements.length : 0,
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
            renderSiteCreateReport(result, responseDebugId);
            if (statusBox && statusText) {
                statusText.textContent = `Site creation completed (debug ID: ${responseDebugId}). Review the detailed report below.`;
            }
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

            renderSiteCreateFeedback('Error: ' + errorMessage + ` Debug ID: ${responseDebugId}`, 'error');
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            console.error('[site-create] request:timeout', {
                debugId: debugRequestId,
                durationMs: Date.now() - startedAt,
            });
            renderSiteCreateFeedback('Error: Site creation request timed out after 180 seconds. The request may still be processing on the server. Please refresh /admin/sites and check whether the site was created. Debug ID: ' + debugRequestId, 'error');
        } else {
            console.error('[site-create] request:error', {
                debugId: debugRequestId,
                durationMs: Date.now() - startedAt,
                requestDataReady: data !== null,
                message: error?.message || String(error),
            });
            renderSiteCreateFeedback('Error: ' + error.message + ` Debug ID: ${debugRequestId}`, 'error');
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

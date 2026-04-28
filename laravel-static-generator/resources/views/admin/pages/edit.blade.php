@extends('layouts.admin')

@section('title', "Edit Page - {$page->title}")

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Page</h2>
            <script src="https://cdn.tiny.cloud/1/ef27sb0c58mn3pqskiq67vhyz4flt3txoqm94bt0f6q4v5lx/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Site: {{ $site->name }}</p>
        </div>
        <div class="mt-4 flex items-center gap-3 sm:mt-0">
            <button id="preview-page-btn" type="button"
                    class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                Preview
            </button>
            <a href="{{ route('admin.pages.index', $site->id) }}"
               class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                Back To Pages
            </a>
        </div>
    </div>

    <form id="page-form" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Page Settings</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                        <input type="text" name="slug" value="{{ $page->slug }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" name="title" value="{{ $page->title }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="draft" {{ $page->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ $page->status === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ $page->status === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Page Type</label>
                        <select name="template_key" id="page-template-key"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @foreach(($pageTemplates ?? []) as $template)
                            <option value="{{ $template['key'] }}" {{ ($page->template_key ?? 'blank') === $template['key'] ? 'selected' : '' }}>
                                {{ $template['label'] }}{{ $template['source_file'] ? " ({$template['source_file']})" : '' }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Save page settings to update template key only.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Locale</label>
                        <input type="text" value="{{ $page->locale }}" disabled
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Fields</h3>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ $page->meta_title }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meta Description</label>
                        <textarea name="meta_description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $page->meta_description }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meta Keywords</label>
                        <textarea name="meta_keywords" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $page->meta_keywords }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Canonical URL</label>
                        <input type="text" name="canonical" value="{{ $page->canonical }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">OpenGraph Data (JSON)</label>
                        <textarea name="og_data" rows="6"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $page->og_data ? json_encode($page->og_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">JSON-LD (JSON)</label>
                        <textarea name="json_ld" rows="8"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $page->json_ld ? json_encode($page->json_ld, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pages.index', $site->id) }}"
               class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                Cancel
            </a>
            <button id="save-deploy-btn" type="button"
                    style="background-color: #d97706 !important;"
                    class="inline-flex cursor-pointer items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none focus-visible:outline-none">
                Save & Deploy
            </button>
            <button type="submit"
                    class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                Save Changes
            </button>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Modules</h3>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <select id="new-module-key"
                                class="block w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @foreach(($moduleCatalog ?? []) as $module)
                                <option value="{{ $module['key'] }}">{{ $module['label'] }}</option>
                            @endforeach
                        </select>
                        <button id="add-module-btn" type="button"
                                style="background-color: #4f46e5 !important;"
                                class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                            Add Module
                        </button>
                    </div>
                    <button id="clear-all-btn" type="button"
                            style="background-color: #dc2626 !important;"
                            class="inline-flex cursor-pointer items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus-visible:outline-none">
                        Clear All
                    </button>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-700 hidden lg:block"></div>
                    <button id="apply-template-btn" type="button"
                            style="background-color: #d97706 !important;"
                            class="inline-flex cursor-pointer items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none focus-visible:outline-none">
                        Apply Selected Template To Modules
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($page->sections as $section)
                <div class="rounded-md border border-gray-200 dark:border-gray-700 p-4 section-item" data-section-id="{{ $section->id }}">
                    @php
                        $moduleKey = is_array($section->content ?? null)
                            ? ($section->content['module'] ?? $section->content['module_key'] ?? 'module')
                            : 'module';
                    @endphp
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Module</label>
                            <input type="text" disabled value="{{ $moduleKey }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-300 shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Position</label>
                            <select onchange="handleOrderChange(this)" 
                                    class="section-order-select mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-300 shadow-sm sm:text-sm">
                                @for($i = 1; $i <= $page->sections->count(); $i++)
                                    <option value="{{ $i }}" {{ ($section->order + 1) == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mt-4" x-data="{ tab: 'visual' }">
                        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                            <button type="button" @click="tab = 'visual'; initTinyMCE($el.closest('.section-item'))" 
                                    :class="tab === 'visual' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="cursor-pointer py-2 px-4 border-b-2 font-medium text-sm focus:outline-none focus-visible:outline-none">
                                Visual Editor
                            </button>
                            <button type="button" @click="tab = 'json'; syncFromTinyMCE($el.closest('.section-item'))" 
                                    :class="tab === 'json' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="cursor-pointer py-2 px-4 border-b-2 font-medium text-sm focus:outline-none focus-visible:outline-none">
                                JSON
                            </button>
                        </div>

                        <div x-show="tab === 'visual'" class="wysiwyg-container">
                            <textarea id="wysiwyg-{{ $section->id }}" class="wysiwyg-editor h-80 w-full"></textarea>
                        </div>

                        <div x-show="tab === 'json'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Module Content (JSON)</label>
                            <textarea rows="8"
                                      class="section-content mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-3">
                        <button type="button"
                                style="background-color: #4f46e5 !important;"
                                class="save-section-btn inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                            Save Module
                        </button>
                        <button type="button"
                                style="background-color: #dc2626 !important;"
                                class="delete-section-btn inline-flex cursor-pointer items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus-visible:outline-none">
                            Delete Module
                        </button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No modules on this page yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
const moduleDefaults = @json($moduleDefaults ?? []);
const editorAssetPrefix = '/admin/sites/{{ $site->id }}/media/serve/assets/';
const canonicalSiteDomain = @json($site->domain);
const currentSiteId = {{ $site->id }};
let canonicalManuallyEdited = false;
let pageActionInProgress = false;

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function normalizeCanonicalDomain(domain) {
    const value = String(domain || '').trim();
    if (value === '') {
        return 'https://';
    }

    const withProtocol = /^https?:\/\//i.test(value) ? value : `https://${value}`;
    return withProtocol.replace(/\/+$/, '');
}

function buildCanonicalFromSlug(slugValue) {
    const domain = normalizeCanonicalDomain(canonicalSiteDomain);
    const slug = String(slugValue || '').trim().replace(/^\/+|\/+$/g, '');

    if (slug === '' || slug.toLowerCase() === 'index' || slug.toLowerCase() === 'index.html') {
        return `${domain}/`;
    }

    return `${domain}/${slug.replace(/\.html$/i, '')}.html`;
}

function buildLegacyCanonicalFromSlug(slugValue) {
    const domain = normalizeCanonicalDomain(canonicalSiteDomain);
    const slug = String(slugValue || '').trim().replace(/^\/+|\/+$/g, '');

    if (slug === '' || slug.toLowerCase() === 'index' || slug.toLowerCase() === 'index.html') {
        return `${domain}/`;
    }

    return `${domain}/${slug.replace(/\.html$/i, '')}`;
}

function syncCanonicalFromSlug(force = false) {
    const slugInput = document.querySelector('input[name="slug"]');
    const canonicalInput = document.querySelector('input[name="canonical"]');

    if (!slugInput || !canonicalInput) {
        return;
    }

    const generatedCanonical = buildCanonicalFromSlug(slugInput.value);
    if (force || !canonicalManuallyEdited || canonicalInput.value.trim() === '') {
        canonicalInput.value = generatedCanonical;
    }
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

const slugInput = document.querySelector('input[name="slug"]');
const canonicalInput = document.querySelector('input[name="canonical"]');

if (slugInput && canonicalInput) {
    const generatedCanonical = buildCanonicalFromSlug(slugInput.value);
    const legacyGeneratedCanonical = buildLegacyCanonicalFromSlug(slugInput.value);
    const currentCanonical = canonicalInput.value.trim();

    canonicalManuallyEdited = currentCanonical !== ''
        && currentCanonical !== generatedCanonical
        && currentCanonical !== legacyGeneratedCanonical;

    if (currentCanonical === '') {
        canonicalInput.value = generatedCanonical;
    }

    slugInput.addEventListener('input', () => syncCanonicalFromSlug());
    canonicalInput.addEventListener('input', () => {
        const nextGenerated = buildCanonicalFromSlug(slugInput.value);
        const nextLegacyGenerated = buildLegacyCanonicalFromSlug(slugInput.value);
        canonicalManuallyEdited = canonicalInput.value.trim() !== ''
            && canonicalInput.value.trim() !== nextGenerated
            && canonicalInput.value.trim() !== nextLegacyGenerated;
    });
}

function toEditorAssetUrls(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    const attrRewritten = html.replace(
        /((?:src|href)\s*=\s*["'])\/assets\/([^"']+)(["'])/gi,
        `$1${editorAssetPrefix}$2$3`
    );

    return attrRewritten.replace(
        /url\((['"]?)\/assets\/([^'")]+)\1\)/gi,
        `url($1${editorAssetPrefix}$2$1)`
    );
}

function toStorageAssetUrls(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    const escapedEditorPrefix = escapeRegExp(editorAssetPrefix);
    const attrPattern = new RegExp(`((?:src|href)\\s*=\\s*["'])${escapedEditorPrefix}([^"']+)(["'])`, 'gi');
    const cssPattern = new RegExp(`url\\((['"]?)${escapedEditorPrefix}([^'")]+)\\1\\)`, 'gi');

    const normalizedAttr = html.replace(attrPattern, '$1/assets/$2$3');
    return normalizedAttr.replace(cssPattern, 'url($1/assets/$2$1)');
}

function extensionByMime(mimeType) {
    switch ((mimeType || '').toLowerCase().trim()) {
        case 'image/jpeg':
            return 'jpg';
        case 'image/png':
            return 'png';
        case 'image/gif':
            return 'gif';
        case 'image/webp':
        case 'image/x-webp':
            return 'webp';
        case 'image/svg+xml':
            return 'svg';
        default:
            return null;
    }
}

function normalizeUploadFilename(filename, mimeType) {
    const normalizedExt = extensionByMime(mimeType);
    if (!normalizedExt) {
        return filename || 'image';
    }

    const sourceName = String(filename || 'image').trim() || 'image';
    const baseName = sourceName.replace(/\.[^./\\]+$/, '') || 'image';
    return `${baseName}.${normalizedExt}`;
}

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

function parseJsonField(value, label) {
    const trimmed = value.trim();
    if (!trimmed) {
        return null;
    }

    try {
        const parsed = JSON.parse(trimmed);
        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            alert(`${label} must be a JSON object.`);
            return false;
        }
        return parsed;
    } catch (error) {
        alert(`${label} has invalid JSON syntax.`);
        return false;
    }
}

function parseSectionJson(text, label) {
    const parsed = parseJsonField(text, label);
    if (parsed === null) {
        alert(`${label} cannot be empty.`);
        return false;
    }

    return parsed;
}

async function saveSection(sectionId, container, options = {}) {
    const silent = options.silent === true;
    const contentText = container.querySelector('.section-content').value;
    const content = parseSectionJson(contentText, 'Section content');
    if (content === false) return false;

    const response = await fetch(`/api/sections/${sectionId}`, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ type: 'module', content }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        if (!silent) {
            alert('Error: ' + message);
        }
        return false;
    }

    if (!silent) {
        alert('Module updated.');
    }
    return true;
}

async function deleteSection(sectionId) {
    if (!confirm('Delete this module?')) {
        return;
    }

    const response = await fetch(`/api/sections/${sectionId}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    window.location.reload();
}

async function addModule() {
    const moduleSelect = document.getElementById('new-module-key');
    const moduleKey = moduleSelect ? moduleSelect.value : null;

    if (!moduleKey) {
        alert('Please select a module type.');
        return;
    }

    let content = {
        module: moduleKey,
        module_key: moduleKey,
        id: moduleKey + '_' + Math.random().toString(36).substr(2, 5),
        class: moduleKey,
        heading: moduleKey.charAt(0).toUpperCase() + moduleKey.slice(1).replace('-', ' ')
    };

    if (moduleDefaults[moduleKey]) {
        content = { ...content, ...moduleDefaults[moduleKey] };
    }

    const response = await fetch('/api/sections', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            page_id: {{ $page->id }},
            type: 'module',
            content: content
        }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    window.location.reload();
}

async function handleOrderChange(select) {
    const container = select.closest('.section-item');
    const sectionId = parseInt(container.dataset.sectionId);
    const newPosition = parseInt(select.value);
    
    await reorderSections(sectionId, newPosition);
}

async function reorderSections(sectionId, newPosition) {
    // Get all current section IDs in order
    const sectionElements = document.querySelectorAll('.section-item');
    let ids = Array.from(sectionElements).map(el => parseInt(el.dataset.sectionId));
    
    // Find current index
    const currentIndex = ids.indexOf(sectionId);
    if (currentIndex === -1) return;
    
    // Remove from current index
    ids.splice(currentIndex, 1);
    
    // Insert at new index (position - 1)
    ids.splice(newPosition - 1, 0, sectionId);
    
    const response = await fetch('/api/sections/reorder', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            page_id: {{ $page->id }},
            order: ids
        }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    window.location.reload();
}

async function clearAllModules() {
    if (!confirm('This will delete ALL modules from this page. This action cannot be undone. Continue?')) {
        return;
    }

    const response = await fetch('/api/pages/{{ $page->id }}/sections/bootstrap', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            template_key: 'blank',
            replace_existing: true,
        }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    alert('All modules cleared.');
    window.location.reload();
}

async function openPreview() {
    const response = await fetch('/api/pages/{{ $page->id }}/preview-token', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    if (!result.preview_url) {
        alert('Error: Preview URL is missing in response.');
        return;
    }

    window.open(result.preview_url, '_blank', 'noopener');
}

async function applyTemplateToSections() {
    const templateField = document.getElementById('page-template-key');
    const templateKey = templateField ? String(templateField.value || '').trim() : '';

    if (!templateKey) {
        alert('Select a page template first.');
        return;
    }

    if (!confirm('This will replace all existing sections with sections from selected template. Continue?')) {
        return;
    }

    const response = await fetch('/api/pages/{{ $page->id }}/sections/bootstrap', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            template_key: templateKey,
            replace_existing: true,
        }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    alert('Template modules were applied.');
    window.location.reload();
}

function setPageActionBusy(busy) {
    const saveChangesBtn = document.querySelector('#page-form button[type="submit"]');
    const saveDeployBtn = document.getElementById('save-deploy-btn');

    if (saveChangesBtn) {
        saveChangesBtn.disabled = busy;
        saveChangesBtn.classList.toggle('opacity-60', busy);
        saveChangesBtn.classList.toggle('cursor-not-allowed', busy);
    }

    if (saveDeployBtn) {
        saveDeployBtn.disabled = busy;
        saveDeployBtn.classList.toggle('opacity-60', busy);
        saveDeployBtn.classList.toggle('cursor-not-allowed', busy);
    }
}

async function savePageSettings() {
    const pageForm = document.getElementById('page-form');
    if (!pageForm) {
        return false;
    }

    const formData = new FormData(pageForm);
    const data = Object.fromEntries(formData);

    if (!String(data.canonical || '').trim()) {
        data.canonical = buildCanonicalFromSlug(data.slug || '');
    }

    const ogData = parseJsonField(data.og_data || '', 'OpenGraph Data');
    if (ogData === false) return false;

    const jsonLd = parseJsonField(data.json_ld || '', 'JSON-LD');
    if (jsonLd === false) return false;

    // Sync all module editors before saving
    document.querySelectorAll('.section-item').forEach(container => {
        syncFromTinyMCE(container);
    });

    delete data.og_data;
    delete data.json_ld;

    if (ogData !== null) data.og_data = ogData;
    if (jsonLd !== null) data.json_ld = jsonLd;

    const response = await fetch('/api/pages/{{ $page->id }}', {
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
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return false;
    }

    return true;
}

async function saveAllSectionsSilently() {
    const containers = document.querySelectorAll('.section-item');

    for (const container of containers) {
        const sectionId = container.dataset.sectionId;
        if (!sectionId) {
            continue;
        }

        const ok = await saveSection(sectionId, container, { silent: true });
        if (!ok) {
            alert(`Module save failed (section #${sectionId}). Deploy aborted.`);
            return false;
        }
    }

    return true;
}

async function deploySiteWithSettings() {
    const response = await fetch(`/api/sites/${currentSiteId}/deploy`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            run_post_deploy_commands: true,
        }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        alert('Deployment failed: ' + (result.error || result.message || `HTTP ${response.status}`));
        return false;
    }

    if (result.status !== 'completed') {
        alert('Deployment failed: ' + (result.error_message || result.message || 'Unknown deployment error'));
        return false;
    }

    return result;
}

async function handlePageSave(options = {}) {
    const deployAfterSave = options.deployAfterSave === true;

    if (pageActionInProgress) {
        return;
    }

    pageActionInProgress = true;
    setPageActionBusy(true);

    try {
        const pageSaved = await savePageSettings();
        if (!pageSaved) {
            return;
        }

        if (!deployAfterSave) {
            alert('Page updated successfully.');
            window.location.href = '{{ route('admin.pages.index', $site->id) }}';
            return;
        }

        const sectionsSaved = await saveAllSectionsSilently();
        if (!sectionsSaved) {
            return;
        }

        const deployment = await deploySiteWithSettings();
        if (!deployment) {
            return;
        }

        alert(`Page saved and deployed successfully to ${deployment.sftp_host}${deployment.remote_path}`);
        window.location.href = '{{ route('admin.pages.index', $site->id) }}';
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        pageActionInProgress = false;
        setPageActionBusy(false);
    }
}

document.getElementById('page-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    await handlePageSave({ deployAfterSave: false });
});

document.querySelectorAll('.section-item').forEach((container) => {
    const sectionId = container.dataset.sectionId;

    container.querySelector('.save-section-btn')?.addEventListener('click', async () => {
        try {
            await saveSection(sectionId, container);
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    container.querySelector('.delete-section-btn')?.addEventListener('click', async () => {
        try {
            await deleteSection(sectionId);
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
});

function initTinyMCE(container) {
    const textarea = container.querySelector('.wysiwyg-editor');
    if (!textarea || textarea.classList.contains('tinymce-initialized')) return;

    const jsonTextarea = container.querySelector('.section-content');
    let content = {};
    try {
        content = JSON.parse(jsonTextarea.value);
    } catch (e) {}

    const rawHtml = typeof content.raw_html === 'string' ? content.raw_html : '';
    textarea.value = toEditorAssetUrls(rawHtml);

    tinymce.init({
        target: textarea,
        plugins: [
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
            'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'advtemplate', 'tinymceai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf', 'code', 'fullscreen', 'image'
        ],
        toolbar: 'undo redo | tinymceai-chat tinymceai-quickactions tinymceai-review | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat | code fullscreen',
        tinycomments_mode: 'embedded',
        tinycomments_author: 'Admin',
        mergetags_list: [
            { value: 'First.Name', title: 'First Name' },
            { value: 'Email', title: 'Email' },
        ],
        tinymceai_token_provider: async () => {
            await fetch(`https://demo.api.tiny.cloud/1/ef27sb0c58mn3pqskiq67vhyz4flt3txoqm94bt0f6q4v5lx/auth/random`, { method: "POST", credentials: "include" });
            return { token: await fetch(`https://demo.api.tiny.cloud/1/ef27sb0c58mn3pqskiq67vhyz4flt3txoqm94bt0f6q4v5lx/jwt/tinymceai`, { credentials: "include" }).then(r => r.text()) };
        },
        skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
        content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
        height: 500,
        verify_html: false,
        relative_urls: false,
        remove_script_host: true,
        convert_urls: false,
        schema: 'html5',
        images_upload_url: '/api/media',
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const blob = blobInfo.blob();
            const fileName = normalizeUploadFilename(blobInfo.filename(), blob.type);
            const formData = new FormData();
            formData.append('file', blob, fileName);
            formData.append('site_id', '{{ $site->id }}');
            formData.append('alt', fileName);

            fetch('/api/media', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.errors) {
                    reject(JSON.stringify(result.errors));
                } else if (result.error) {
                    reject(result.error);
                } else {
                    resolve(result.url);
                }
            })
            .catch(error => {
                reject('Upload failed: ' + error.message);
            });
        }),
        file_picker_types: 'image',
        file_picker_callback: (callback, value, meta) => {
            if (meta.filetype === 'image') {
                const dialog = tinymce.activeEditor.windowManager.open({
                    title: 'Server Image Library',
                    body: {
                        type: 'panel',
                        items: [
                            {
                                type: 'htmlpanel',
                                html: `
                                    <style>
                                        .media-browser-container { padding: 15px; }
                                        .media-browser-toolbar { margin-bottom: 15px; display: flex; justify-content: flex-end; }
                                        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding-right: 5px; }
                                        .media-item { position: relative; cursor: pointer; border-radius: 6px; overflow: hidden; background: #f8fafc; border: 2px solid transparent; transition: 0.2s; }
                                        .media-item:hover { border-color: #3b82f6; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
                                        .media-item img { width: 100%; height: 100px; object-fit: cover; }
                                        .media-item-name { font-size: 10px; padding: 4px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: rgba(255,255,255,0.9); }
                                        .upload-btn { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; }
                                        .upload-btn:hover { background: #2563eb; }
                                        .upload-loading { font-size: 12px; color: #64748b; margin-right: 10px; display: none; align-items: center; }
                                    </style>
                                    <div class="media-browser-container">
                                        <div class="media-browser-toolbar">
                                            <div id="upload-status" class="upload-loading">Uploading...</div>
                                            <button type="button" class="upload-btn cursor-pointer focus:outline-none focus-visible:outline-none" onclick="document.getElementById('picker-upload').click()">+ Upload New</button>
                                            <input type="file" id="picker-upload" style="display:none" accept="image/*">
                                        </div>
                                        <div id="media-gallery" class="media-grid">Loading library...</div>
                                    </div>
                                `
                            }
                        ]
                    },
                    buttons: [{ type: 'cancel', text: 'Close' }]
                });

                const loadGallery = () => {
                    fetch(`/api/media?site_id={{ $site->id }}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(media => {
                        const container = document.getElementById('media-gallery');
                        if (!container) return;
                        
                        if (!Array.isArray(media)) {
                            container.innerHTML = `<p style="color:red">${media.error || 'Error loading library'}</p>`;
                            return;
                        }

                        if (media.length === 0) {
                            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; padding: 40px; color: #64748b;">No images found. Upload your first one!</p>';
                            return;
                        }

                        container.innerHTML = '';
                        media.sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).forEach(item => {
                            const fileName = item.path.split('/').pop();
                            const div = document.createElement('div');
                            div.className = 'media-item';
                            div.innerHTML = `<img src="${item.url}" title="${item.alt}"><div class="media-item-name">${fileName}</div>`;
                            div.onclick = () => {
                                callback(item.url, { alt: item.alt });
                                dialog.close();
                            };
                            container.appendChild(div);
                        });
                    });
                };

                // Initialize gallery
                setTimeout(loadGallery, 100);

                // Handle direct upload from picker
                setTimeout(() => {
                    const uploadInput = document.getElementById('picker-upload');
                    if (uploadInput) {
                        uploadInput.onchange = (e) => {
                            const file = e.target.files[0];
                            if (!file) return;

                            const status = document.getElementById('upload-status');
                            status.style.display = 'flex';

                            const formData = new FormData();
                            formData.append('file', file);
                            formData.append('site_id', '{{ $site->id }}');
                            formData.append('alt', file.name);

                            fetch('/api/media', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
                                body: formData
                            })
                            .then(r => r.json())
                            .then(result => {
                                status.style.display = 'none';
                                if (result.error || result.errors) {
                                    alert('Upload failed: ' + (result.error || JSON.stringify(result.errors)));
                                } else {
                                    loadGallery();
                                }
                            })
                            .catch(err => {
                                status.style.display = 'none';
                                alert('Upload failed: ' + err.message);
                            });
                        };
                    }
                }, 200);
            }
        },
        valid_elements: '*[*]',
        extended_valid_elements: '*[*]',
        valid_children: '+body[style],+body[script],+div[style],+div[script],+*[style],+*[script]',
        forced_root_block: '',
        inline_styles: true,
        entity_encoding: 'raw',
        setup: function (editor) {
            editor.on('change blur', function () {
                syncFromTinyMCE(container);
            });
            // Strip junk attributes when displaying source code or getting content
            editor.on('GetContent', function(e) {
                if (e.content) {
                    e.content = e.content.replace(/\s*bis_[a-z]+="[^"]*"/gi, '');
                }
            });
        }
    });

    textarea.classList.add('tinymce-initialized');
}

function syncFromTinyMCE(container) {
    const textarea = container.querySelector('.wysiwyg-editor');
    if (!textarea) return;
    
    const editor = tinymce.get(textarea.id);
    if (!editor) return;

    const jsonTextarea = container.querySelector('.section-content');
    if (!jsonTextarea) return;

    try {
        let content = JSON.parse(jsonTextarea.value);
        const wasDirty = editor.isDirty();
        let html = editor.getContent();
        
        // Clean up junk attributes injected by browser extensions (like bis_size)
        html = html.replace(/\s*bis_[a-z]+="[^"]*"/gi, '');
        html = toStorageAssetUrls(html);
        
        content.raw_html = html;
        if (wasDirty) {
            content.render_mode = 'raw_html';
        }
        jsonTextarea.value = JSON.stringify(content, null, 4);
    } catch (e) {
        console.error('Failed to sync TinyMCE to JSON:', e);
    }
}

// Auto-init visual editors for existing modules
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.section-item').forEach(container => {
        initTinyMCE(container);
    });
});

document.getElementById('preview-page-btn')?.addEventListener('click', async () => {
    try {
        await openPreview();
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

document.getElementById('apply-template-btn')?.addEventListener('click', async () => {
    try {
        await applyTemplateToSections();
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

document.getElementById('add-module-btn')?.addEventListener('click', async () => {
    try {
        await addModule();
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

document.getElementById('clear-all-btn')?.addEventListener('click', async () => {
    try {
        await clearAllModules();
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

document.getElementById('save-deploy-btn')?.addEventListener('click', async () => {
    const confirmed = confirm('Save page and deploy site to remote server using current site settings?');
    if (!confirmed) {
        return;
    }

    await handlePageSave({ deployAfterSave: true });
});

</script>
@endsection

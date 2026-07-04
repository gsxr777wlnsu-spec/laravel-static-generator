@extends('layouts.admin')

@section('title', "Edit Page - {$page->title}")

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Page</h2>
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Head JSON</label>
                        <textarea name="og_data" rows="6"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $page->og_data ? json_encode($page->og_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Editable object for <code>head_meta</code>, <code>head_links</code>, <code>head_extra</code>, <code>head_custom</code>.</p>
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

                    <div class="mt-4">
                        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                            <button type="button" data-editor-tab="visual"
                                    class="cursor-pointer py-2 px-4 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600 dark:text-indigo-400 focus:outline-none focus-visible:outline-none">
                                Visual
                            </button>
                            <button type="button" data-editor-tab="code"
                                    class="cursor-pointer py-2 px-4 border-b-2 font-medium text-sm focus:outline-none focus-visible:outline-none">
                                Code
                            </button>
                            <button type="button" data-editor-tab="json"
                                    class="cursor-pointer py-2 px-4 border-b-2 font-medium text-sm focus:outline-none focus-visible:outline-none">
                                JSON
                            </button>
                        </div>

                        <div data-editor-panel="visual" class="space-y-3">
                            <div class="tiptap-toolbar"></div>
                            <div class="tiptap-editor-layout">
                                <div class="tiptap-editor-shell">
                                    <div class="tiptap-editor min-h-80"></div>
                                </div>
                                <aside class="tiptap-image-sidebar hidden">
                                    <div class="tiptap-image-sidebar-header">
                                        <h4>Image</h4>
                                        <p>Edit selected image attributes.</p>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alt</label>
                                            <input type="text" class="tiptap-image-input mt-1 block w-full rounded-md shadow-sm sm:text-sm" data-image-attr="alt">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                            <input type="text" class="tiptap-image-input mt-1 block w-full rounded-md shadow-sm sm:text-sm" data-image-attr="title">
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Width</label>
                                                <input type="number" min="1" class="tiptap-image-input mt-1 block w-full rounded-md shadow-sm sm:text-sm" data-image-attr="width">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Height</label>
                                                <input type="number" min="1" class="tiptap-image-input mt-1 block w-full rounded-md shadow-sm sm:text-sm" data-image-attr="height">
                                            </div>
                                        </div>
                                    </div>
                                </aside>
                                <aside class="tiptap-background-sidebar hidden">
                                    <div class="tiptap-image-sidebar-header">
                                        <h4>Backgrounds</h4>
                                        <p>Edit background-image asset URLs from inline CSS.</p>
                                    </div>
                                    <div class="tiptap-background-fields space-y-3"></div>
                                </aside>
                            </div>
                        </div>

                        <div data-editor-panel="code" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Module HTML</label>
                            <textarea rows="12"
                                      class="section-raw-html mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div data-editor-panel="json" class="hidden">
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
    <div id="page-edit-status" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
        <div id="page-edit-status-text" class="whitespace-pre-wrap"></div>
    </div>
</div>

<div id="page-editor-config" data-site-id="{{ $site->id }}" class="hidden"></div>
@php
    $pageEditorManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    $pageEditorJsFile = $pageEditorManifest['resources/js/page-editor.js']['file'] ?? null;
@endphp
@if($pageEditorJsFile)
    <script type="module" src="/build/{{ $pageEditorJsFile }}"></script>
@endif

<script>
const moduleDefaults = @json($moduleDefaults ?? []);
const editorAssetPrefix = '/admin/sites/{{ $site->id }}/media/serve/assets/';
const editorAssetServePrefix = '/admin/sites/{{ $site->id }}/media/serve/';
const canonicalSiteDomain = @json($site->domain);
const currentSiteId = {{ $site->id }};
let canonicalManuallyEdited = false;
let pageActionInProgress = false;
const ogDataField = document.querySelector('textarea[name="og_data"]');
const initialHeadSyncState = (() => {
    const ogData = parseLooseJsonObject(ogDataField?.value || '');

    return {
        metaPublishedTime: extractHeadMetaPublishedTime(ogData),
        jsonPublishedTimes: extractJsonLdPublishedTimes(ogData?.head_extra),
    };
})();

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

    const escapedAssetPrefix = escapeRegExp(editorAssetPrefix);
    const escapedServePrefix = escapeRegExp(editorAssetServePrefix);
    const currentOrigin = escapeRegExp(window.location.origin);

    const normalizeEditorUrl = (value) => {
        if (typeof value !== 'string' || value === '') {
            return value;
        }

        if (/^\/assets\//i.test(value)) {
            return value.replace(/^\/assets\//i, editorAssetPrefix);
        }

        return value
            .replace(new RegExp(`^${currentOrigin}${escapedAssetPrefix}`, 'i'), editorAssetPrefix)
            .replace(new RegExp(`^${currentOrigin}${escapedServePrefix}(?:\\d+\\/)?assets\\/`, 'i'), editorAssetPrefix)
            .replace(new RegExp(`^${escapedServePrefix}(?:\\d+\\/)?assets\\/`, 'i'), editorAssetPrefix);
    };

    const attrRewritten = html.replace(/((?:src|href)\s*=\s*["'])([^"']+)(["'])/gi, (match, before, url, after) => {
        return `${before}${normalizeEditorUrl(url)}${after}`;
    });

    return attrRewritten.replace(/url\((['"]?)([^'")]+)\1\)/gi, (match, quote, url) => {
        return `url(${quote}${normalizeEditorUrl(url)}${quote})`;
    });
}

function toStorageAssetUrls(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    const escapedEditorPrefix = escapeRegExp(editorAssetPrefix);
    const escapedServePrefix = escapeRegExp(editorAssetServePrefix);
    const currentOrigin = escapeRegExp(window.location.origin);

    const normalizeStorageUrl = (value) => {
        if (typeof value !== 'string' || value === '') {
            return value;
        }

        return value
            .replace(new RegExp(`^${currentOrigin}${escapedEditorPrefix}`, 'i'), '/assets/')
            .replace(new RegExp(`^${escapedEditorPrefix}`, 'i'), '/assets/')
            .replace(new RegExp(`^${currentOrigin}${escapedServePrefix}(?:\\d+\\/)?assets\\/`, 'i'), '/assets/')
            .replace(new RegExp(`^${escapedServePrefix}(?:\\d+\\/)?assets\\/`, 'i'), '/assets/');
    };

    const normalizedAttr = html.replace(/((?:src|href)\s*=\s*["'])([^"']+)(["'])/gi, (match, before, url, after) => {
        return `${before}${normalizeStorageUrl(url)}${after}`;
    });

    return normalizedAttr.replace(/url\((['"]?)([^'")]+)\1\)/gi, (match, quote, url) => {
        return `url(${quote}${normalizeStorageUrl(url)}${quote})`;
    });
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

function renderPageEditStatus(message, tone = 'error') {
    const statusBox = document.getElementById('page-edit-status');
    const statusText = document.getElementById('page-edit-status-text');

    if (!statusBox || !statusText) {
        return false;
    }

    statusBox.classList.remove(
        'hidden',
        'border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100',
        'border-amber-200', 'bg-amber-50', 'text-amber-900', 'dark:border-amber-800', 'dark:bg-amber-950', 'dark:text-amber-100',
        'border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100',
        'border-blue-200', 'bg-blue-50', 'text-blue-900', 'dark:border-blue-800', 'dark:bg-blue-950', 'dark:text-blue-100'
    );

    statusBox.classList.add(...(
        tone === 'success'
            ? ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100']
            : (tone === 'warning'
                ? ['border-amber-200', 'bg-amber-50', 'text-amber-900', 'dark:border-amber-800', 'dark:bg-amber-950', 'dark:text-amber-100']
                : (tone === 'info'
                    ? ['border-blue-200', 'bg-blue-50', 'text-blue-900', 'dark:border-blue-800', 'dark:bg-blue-950', 'dark:text-blue-100']
                    : ['border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100']))
    ));

    statusText.textContent = message;
    return false;
}
window.renderPageEditStatus = renderPageEditStatus;

function parseJsonField(value, label) {
    const trimmed = value.trim();
    if (!trimmed) {
        return null;
    }

    try {
        const parsed = JSON.parse(trimmed);
        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            return renderPageEditStatus(`${label} must be a JSON object.`, 'error');
        }
        return parsed;
    } catch (error) {
        return renderPageEditStatus(`${label} has invalid JSON syntax.`, 'error');
    }
}

function parseLooseJsonObject(value) {
    const trimmed = String(value || '').trim();
    if (trimmed === '') {
        return null;
    }

    try {
        const parsed = JSON.parse(trimmed);
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : null;
    } catch (error) {
        return null;
    }
}

function normalizeHeadValue(value) {
    return typeof value === 'string' ? value.trim() : '';
}

function extractHeadMetaPublishedTime(ogData) {
    const headMeta = Array.isArray(ogData?.head_meta) ? ogData.head_meta : [];

    for (const item of headMeta) {
        if (!item || typeof item !== 'object') {
            continue;
        }

        if (String(item.property || '').trim().toLowerCase() === 'article:published_time') {
            return normalizeHeadValue(item.content);
        }
    }

    return '';
}

function collectJsonLdPublishedTimes(node, values = []) {
    if (Array.isArray(node)) {
        node.forEach((item) => collectJsonLdPublishedTimes(item, values));
        return values;
    }

    if (!node || typeof node !== 'object') {
        return values;
    }

    if (String(node['@type'] || '') === 'WebPage' && typeof node.datePublished === 'string') {
        values.push(node.datePublished.trim());
    }

    if (Array.isArray(node['@graph'])) {
        collectJsonLdPublishedTimes(node['@graph'], values);
    }

    if (node.mainEntity) {
        collectJsonLdPublishedTimes(node.mainEntity, values);
    }

    if (node.itemListElement) {
        collectJsonLdPublishedTimes(node.itemListElement, values);
    }

    return values;
}

function extractJsonLdPublishedTimes(headExtra) {
    if (typeof headExtra !== 'string' || headExtra.trim() === '') {
        return [];
    }

    const values = [];
    const scriptPattern = /<script\b[^>]*type=(["'])application\/ld\+json\1[^>]*>([\s\S]*?)<\/script>/gi;
    let match = null;

    while ((match = scriptPattern.exec(headExtra)) !== null) {
        try {
            const parsed = JSON.parse(String(match[2] || '').trim());
            collectJsonLdPublishedTimes(parsed, values);
        } catch (error) {
            // Ignore malformed JSON-LD fragments during sync.
        }
    }

    return values;
}

function setHeadMetaPublishedTime(ogData, value) {
    const normalizedValue = normalizeHeadValue(value);
    if (normalizedValue === '') {
        return;
    }

    if (!Array.isArray(ogData.head_meta)) {
        ogData.head_meta = [];
    }

    const existingItem = ogData.head_meta.find((item) => (
        item
        && typeof item === 'object'
        && String(item.property || '').trim().toLowerCase() === 'article:published_time'
    ));

    if (existingItem) {
        existingItem.content = normalizedValue;
        return;
    }

    ogData.head_meta.push({
        property: 'article:published_time',
        content: normalizedValue,
    });
}

function syncJsonLdPublishedTime(node, publishedTime) {
    if (Array.isArray(node)) {
        return node.map((item) => syncJsonLdPublishedTime(item, publishedTime));
    }

    if (!node || typeof node !== 'object') {
        return node;
    }

    const next = { ...node };

    if (String(next['@type'] || '') === 'WebPage' && publishedTime !== '') {
        next.datePublished = publishedTime;
    }

    if (Array.isArray(next['@graph'])) {
        next['@graph'] = next['@graph'].map((item) => syncJsonLdPublishedTime(item, publishedTime));
    }

    if (next.mainEntity) {
        next.mainEntity = syncJsonLdPublishedTime(next.mainEntity, publishedTime);
    }

    if (next.itemListElement) {
        next.itemListElement = syncJsonLdPublishedTime(next.itemListElement, publishedTime);
    }

    return next;
}

function syncHeadExtraPublishedTimes(headExtra, publishedTime) {
    if (typeof headExtra !== 'string' || headExtra.trim() === '' || publishedTime === '') {
        return headExtra;
    }

    const scriptPattern = /<script\b([^>]*)type=(["'])application\/ld\+json\2([^>]*)>([\s\S]*?)<\/script>/gi;

    return headExtra.replace(scriptPattern, (fullMatch, beforeType, quote, afterType, body) => {
        try {
            const parsed = JSON.parse(String(body || '').trim());
            const synced = syncJsonLdPublishedTime(parsed, publishedTime);
            return `<script${beforeType}type=${quote}application/ld+json${quote}${afterType}>\n${JSON.stringify(synced, null, 2)}\n<\/script>`;
        } catch (error) {
            return fullMatch;
        }
    });
}

function syncPublishedTimeFields(ogData) {
    if (!ogData || typeof ogData !== 'object' || Array.isArray(ogData)) {
        return ogData;
    }

    const next = {
        ...ogData,
        head_meta: Array.isArray(ogData.head_meta)
            ? ogData.head_meta.map((item) => (item && typeof item === 'object' ? { ...item } : item))
            : [],
    };

    const currentMetaPublishedTime = extractHeadMetaPublishedTime(next);
    const currentJsonPublishedTimes = extractJsonLdPublishedTimes(next.head_extra);
    const firstJsonPublishedTime = currentJsonPublishedTimes.find((value) => normalizeHeadValue(value) !== '') || '';
    const initialMetaPublishedTime = normalizeHeadValue(initialHeadSyncState.metaPublishedTime);
    const metaChanged = normalizeHeadValue(currentMetaPublishedTime) !== initialMetaPublishedTime;
    const jsonChanged = JSON.stringify(currentJsonPublishedTimes) !== JSON.stringify(initialHeadSyncState.jsonPublishedTimes || []);

    let canonicalPublishedTime = normalizeHeadValue(currentMetaPublishedTime);

    if (jsonChanged && !metaChanged && firstJsonPublishedTime !== '') {
        canonicalPublishedTime = firstJsonPublishedTime;
    } else if (canonicalPublishedTime === '' && firstJsonPublishedTime !== '') {
        canonicalPublishedTime = firstJsonPublishedTime;
    }

    if (canonicalPublishedTime !== '') {
        setHeadMetaPublishedTime(next, canonicalPublishedTime);
        next.head_extra = syncHeadExtraPublishedTimes(next.head_extra, canonicalPublishedTime);
    }

    return next;
}

function applyPublishedTimeSyncToOgDataField() {
    if (!ogDataField) {
        return null;
    }

    const ogData = parseLooseJsonObject(ogDataField.value);
    if (!ogData) {
        return null;
    }

    const synced = syncPublishedTimeFields(ogData);
    const serialized = JSON.stringify(synced, null, 4);

    if (ogDataField.value !== serialized) {
        ogDataField.value = serialized;
    }

    return synced;
}

function parseSectionJson(text, label) {
    const parsed = parseJsonField(text, label);
    if (parsed === null) {
        return renderPageEditStatus(`${label} cannot be empty.`, 'error');
    }

    return parsed;
}

async function saveSection(sectionId, container, options = {}) {
    const silent = options.silent === true;
    const button = options.button || null;

    if (typeof window.syncFromTipTap === 'function') {
        window.syncFromTipTap(container);
    }

    if (!silent) {
        setInlineButtonBusy(button, true);
        renderPageEditStatus(`Saving module #${sectionId}…`, 'warning');
    }

    const contentText = container.querySelector('.section-content').value;
    const content = parseSectionJson(contentText, 'Section content');
    if (content === false) {
        setInlineButtonBusy(button, false);
        return false;
    }

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
            renderPageEditStatus('Error: ' + message, 'error');
            setInlineButtonBusy(button, false);
        }
        return false;
    }

    if (!silent) {
        renderPageEditStatus(`Module #${sectionId} saved at ${currentSaveTime()}.`, 'success');
        setInlineButtonBusy(button, false);
    }
    return true;
}

window.savePageSection = saveSection;

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
        renderPageEditStatus('Error: ' + message, 'error');
        return;
    }

    window.location.reload();
}

async function addModule() {
    const moduleSelect = document.getElementById('new-module-key');
    const moduleKey = moduleSelect ? moduleSelect.value : null;

    if (!moduleKey) {
        renderPageEditStatus('Please select a module type.', 'warning');
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
        renderPageEditStatus('Error: ' + message, 'error');
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
        renderPageEditStatus('Error: ' + message, 'error');
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
        renderPageEditStatus('Error: ' + message, 'error');
        return;
    }

    renderPageEditStatus('All modules cleared. Reloading…', 'success');
    window.location.reload();
}

async function openPreview() {
    if (pageActionInProgress) {
        return;
    }

    pageActionInProgress = true;
    setPageActionBusy(true);

    const previewWindow = window.open('', '_blank');
    if (previewWindow) {
        previewWindow.opener = null;
        previewWindow.document.write('<title>Preview</title><p style="font-family: sans-serif; padding: 16px;">Loading preview...</p>');
    }

    try {
        const settingsSaved = await savePageSettings();
        if (!settingsSaved) {
            if (previewWindow) {
                previewWindow.close();
            }
            return;
        }

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
            if (previewWindow) {
                previewWindow.close();
            }
            renderPageEditStatus('Error: ' + message, 'error');
            return;
        }

        if (!result.preview_url) {
            if (previewWindow) {
                previewWindow.close();
            }
            renderPageEditStatus('Error: Preview URL is missing in response.', 'error');
            return;
        }

        if (previewWindow) {
            previewWindow.location = result.preview_url;
            return;
        }

        window.location.href = result.preview_url;
    } finally {
        pageActionInProgress = false;
        setPageActionBusy(false);
    }
}

async function applyTemplateToSections() {
    const templateField = document.getElementById('page-template-key');
    const templateKey = templateField ? String(templateField.value || '').trim() : '';

    if (!templateKey) {
        renderPageEditStatus('Select a page template first.', 'warning');
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
        renderPageEditStatus('Error: ' + message, 'error');
        return;
    }

    renderPageEditStatus('Template modules were applied. Reloading…', 'success');
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

function setInlineButtonBusy(button, busy, busyText = 'Saving…') {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultText) {
        button.dataset.defaultText = button.textContent.trim();
    }

    button.disabled = busy;
    button.textContent = busy ? busyText : button.dataset.defaultText;
    button.classList.toggle('opacity-60', busy);
    button.classList.toggle('cursor-not-allowed', busy);
}

function currentSaveTime() {
    return new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

async function savePageSettings() {
    const pageForm = document.getElementById('page-form');
    if (!pageForm) {
        return false;
    }

    applyPublishedTimeSyncToOgDataField();

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
    if (typeof window.syncAllTipTapEditors === 'function') {
        window.syncAllTipTapEditors();
    }

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
        renderPageEditStatus('Error: ' + message, 'error');
        return false;
    }

    return true;
}

ogDataField?.addEventListener('change', () => {
    applyPublishedTimeSyncToOgDataField();
});

async function saveAllSectionsSilently() {
    const containers = document.querySelectorAll('.section-item');

    for (const container of containers) {
        const sectionId = container.dataset.sectionId;
        if (!sectionId) {
            continue;
        }

        const ok = await saveSection(sectionId, container, { silent: true });
        if (!ok) {
            renderPageEditStatus(`Module save failed (section #${sectionId}). Deploy aborted.`, 'error');
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
        renderPageEditStatus('Deployment failed: ' + (result.error || result.message || `HTTP ${response.status}`), 'error');
        return false;
    }

    if (result.status !== 'completed') {
        renderPageEditStatus('Deployment failed: ' + (result.error_message || result.message || 'Unknown deployment error'), 'error');
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

        const sectionsSaved = await saveAllSectionsSilently();
        if (!sectionsSaved) {
            return;
        }

        if (!deployAfterSave) {
            renderPageEditStatus(`Page and modules saved at ${currentSaveTime()}.`, 'success');
            return;
        }

        const deployment = await deploySiteWithSettings();
        if (!deployment) {
            return;
        }

        renderPageEditStatus(`Page saved and deployed successfully to ${deployment.sftp_host}${deployment.remote_path}`, 'success');
    } catch (error) {
        renderPageEditStatus('Error: ' + error.message, 'error');
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

    container.querySelector('.save-section-btn')?.addEventListener('click', async (event) => {
        try {
            await saveSection(sectionId, container, { button: event.currentTarget });
        } catch (error) {
            setInlineButtonBusy(event.currentTarget, false);
            renderPageEditStatus('Error: ' + error.message, 'error');
        }
    });

    container.querySelector('.delete-section-btn')?.addEventListener('click', async () => {
        try {
            await deleteSection(sectionId);
        } catch (error) {
            renderPageEditStatus('Error: ' + error.message, 'error');
        }
    });
});

document.getElementById('preview-page-btn')?.addEventListener('click', async () => {
    try {
        await openPreview();
    } catch (error) {
        renderPageEditStatus('Error: ' + error.message, 'error');
    }
});

document.getElementById('apply-template-btn')?.addEventListener('click', async () => {
    try {
        await applyTemplateToSections();
    } catch (error) {
        renderPageEditStatus('Error: ' + error.message, 'error');
    }
});

document.getElementById('add-module-btn')?.addEventListener('click', async () => {
    try {
        await addModule();
    } catch (error) {
        renderPageEditStatus('Error: ' + error.message, 'error');
    }
});

document.getElementById('clear-all-btn')?.addEventListener('click', async () => {
    try {
        await clearAllModules();
    } catch (error) {
        renderPageEditStatus('Error: ' + error.message, 'error');
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

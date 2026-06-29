@extends('layouts.admin')

@section('title', strtoupper($part) . " - {$site->name}")

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ strtoupper($part) }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This HTML is rendered on every page of {{ $site->name }}.</p>
        </div>
        <div class="mt-4 flex items-center gap-3 sm:mt-0">
            <a href="{{ route('admin.pages.index', $site->id) }}"
               class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                Back To Pages
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="section-item" data-preserve-raw-html="true">
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
                    </div>
                </div>

                <div data-editor-panel="code" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">HTML</label>
                    <textarea rows="16"
                              class="section-raw-html mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>

                <div data-editor-panel="json" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content (JSON)</label>
                    <textarea rows="10"
                              class="section-content mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ json_encode(['raw_html' => $html, 'render_mode' => 'raw_html'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="save-shared-block-btn"
                        class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                    Save {{ strtoupper($part) }}
                </button>
            </div>
        </div>
    </div>

    <div id="shared-block-status" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
        <div id="shared-block-status-text" class="whitespace-pre-wrap"></div>
    </div>
</div>

<div id="page-editor-config" data-site-id="{{ $site->id }}" class="hidden"></div>
<script>
const editorAssetPrefix = '/admin/sites/{{ $site->id }}/media/serve/assets/';
const editorAssetServePrefix = '/admin/sites/{{ $site->id }}/media/serve/';

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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

window.toEditorAssetUrls = toEditorAssetUrls;
window.toStorageAssetUrls = toStorageAssetUrls;
</script>
@php
    $pageEditorManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    $pageEditorJsFile = $pageEditorManifest['resources/js/page-editor.js']['file'] ?? null;
@endphp
@if($pageEditorJsFile)
    <script type="module" src="/build/{{ $pageEditorJsFile }}"></script>
@endif

<script>
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function renderSharedBlockStatus(message, tone = 'error') {
    const statusBox = document.getElementById('shared-block-status');
    const statusText = document.getElementById('shared-block-status-text');

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

function setSharedButtonBusy(button, busy) {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultText) {
        button.dataset.defaultText = button.textContent.trim();
    }

    button.disabled = busy;
    button.textContent = busy ? 'Saving…' : button.dataset.defaultText;
    button.classList.toggle('opacity-60', busy);
    button.classList.toggle('cursor-not-allowed', busy);
}

function currentSharedSaveTime() {
    return new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

async function saveSharedBlock() {
    const button = document.getElementById('save-shared-block-btn');
    const container = document.querySelector('.section-item');
    const sectionJsonField = container?.querySelector('.section-content');
    if (!container || !sectionJsonField) {
        return;
    }

    setSharedButtonBusy(button, true);
    renderSharedBlockStatus('Saving {{ strtoupper($part) }}…', 'success');

    if (typeof window.syncFromTipTap === 'function') {
        window.syncFromTipTap(container);
    }

    let content = null;
    try {
        content = JSON.parse(sectionJsonField.value);
    } catch (error) {
        renderSharedBlockStatus('Invalid JSON payload in editor.', 'error');
        setSharedButtonBusy(button, false);
        return;
    }

    const fieldName = @json($part === 'footer' ? 'footer_html' : ($part === 'mobile-menu' ? 'mobile_menu_html' : 'menu_html'));
    const response = await fetch(`/api/sites/{{ $site->id }}`, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            [fieldName]: typeof content.raw_html === 'string' ? content.raw_html : '',
        }),
    });

    const result = await response.json();
    if (!response.ok) {
        renderSharedBlockStatus(result.error || JSON.stringify(result.errors || {}) || `Request failed with status ${response.status}`, 'error');
        setSharedButtonBusy(button, false);
        return;
    }

    renderSharedBlockStatus(`{{ strtoupper($part) }} saved at ${currentSharedSaveTime()}.`, 'success');
    setSharedButtonBusy(button, false);
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('save-shared-block-btn')?.addEventListener('click', saveSharedBlock);
});
</script>
@endsection

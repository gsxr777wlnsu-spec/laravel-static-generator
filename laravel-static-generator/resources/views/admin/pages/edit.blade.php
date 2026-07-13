@extends('layouts.admin')

@section('title', "Edit Page - {$page->title}")

@section('content')
<style>
@media (min-width: 1024px) {
    .ai-section-control-row {
        display: flex;
        width: 100%;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .ai-section-control-button {
        flex: 0 0 auto;
    }

    .ai-section-control-field {
        flex: 1 1 0;
        min-width: 0;
    }

    .ai-section-control-field select {
        width: 100%;
    }
}

#preview-widget-btn {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 50;
    display: inline-flex;
    width: 3.5rem;
    height: 3.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: #4f46e5;
    color: #fff;
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.2), 0 4px 6px -4px rgb(0 0 0 / 0.2);
}

.dark #preview-widget-btn {
    background: #6366f1;
}

#preview-widget-btn:hover {
    background: #6366f1;
}

.dark #preview-widget-btn:hover {
    background: #818cf8;
}

#preview-history-panel {
    max-height: 24rem;
}

</style>
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Page</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Site: {{ $site->name }}</p>
        </div>
        <div class="mt-4 flex items-center gap-3 sm:mt-0">
            <div class="relative">
                <button id="preview-history-btn" type="button"
                        class="inline-flex cursor-pointer items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus-visible:outline-none dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                    Preview History
                </button>
                <div id="preview-history-panel"
                     class="absolute right-0 z-40 mt-2 hidden w-96 overflow-y-auto rounded-md border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Preview History</h3>
                        <button id="preview-history-close-btn" type="button"
                                class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Close
                        </button>
                    </div>
                    <div id="preview-history-status" class="hidden rounded-md border px-3 py-2 text-sm"></div>
                    <div id="preview-history-list" class="mt-2 space-y-2"></div>
                </div>
            </div>
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
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Page Settings</h3>
                    <button type="button"
                            data-collapsible-toggle
                            data-collapsible-target="#page-settings-panel"
                            class="inline-flex cursor-pointer items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600 dark:hover:bg-gray-600">
                        Collapse
                    </button>
                </div>

                <div id="page-settings-panel" class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
	                @php
	                    $headOgData = is_array($page->og_data) ? $page->og_data : [];
	                    $headMetaRows = isset($headOgData['head_meta']) && is_array($headOgData['head_meta']) ? $headOgData['head_meta'] : [];
	                    $headLinkRows = isset($headOgData['head_links']) && is_array($headOgData['head_links']) ? $headOgData['head_links'] : [];
	                    $headExtra = isset($headOgData['head_extra']) && is_string($headOgData['head_extra']) ? $headOgData['head_extra'] : '';
	                    $headCustom = isset($headOgData['head_custom']) && is_string($headOgData['head_custom']) ? $headOgData['head_custom'] : '';
	                    $bodyExtra = isset($headOgData['body_extra']) && is_string($headOgData['body_extra']) ? $headOgData['body_extra'] : '';
	                    $headExtraScripts = [];
	                    if ($headExtra !== '' && preg_match_all('/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>([\s\S]*?)<\/script>/i', $headExtra, $headExtraScriptMatches)) {
	                        $headExtraScripts = $headExtraScriptMatches[1];
	                    }
	                @endphp

	                <div class="grid grid-cols-1 gap-6">
		                    <div data-head-editor-panel class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
	                        <div class="mb-4 flex items-start justify-between gap-3">
	                            <div>
	                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">SECTION HEAD</h4>
	                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Editable fields follow the same paths as the import .txt template.</p>
	                            </div>
                                <button type="button"
                                        data-collapsible-toggle
                                        data-collapsible-target="#section-head-panel"
                                        class="inline-flex cursor-pointer items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600 dark:hover:bg-gray-600">
                                    Collapse
                                </button>
	                        </div>

                            <div id="section-head-panel">
		                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
		                            <div>
		                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">title</label>
		                                <input type="text" data-head-page-field="title" value="{{ $page->title }}"
		                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
		                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.title</code></p>
                                        <div class="mt-2" data-page-ai-field="title">
                                            <textarea rows="2" class="page-ai-prompt mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm" placeholder="AI Prompt"></textarea>
                                            <div class="mt-2 flex items-center gap-2">
                                                <button type="button" class="page-ai-generate-btn inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500">Generate</button>
                                                <select class="page-ai-model block rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    @foreach(($aiModelOptions ?? []) as $modelOption)
                                                        <option value="{{ $modelOption['value'] }}" {{ $modelOption['value'] === 'medium_main' ? 'selected' : '' }}>{{ $modelOption['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <details class="ai-prompt-rule mt-2 rounded-md border border-gray-200 p-2 dark:border-gray-700" data-ai-rule-field="title">
                                                <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-300">AI Prompt Rule</summary>
                                                <textarea rows="2" class="ai-prompt-rule-input mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                                <button type="button" class="ai-prompt-rule-save mt-2 rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600">Save Rule</button>
                                            </details>
                                        </div>
		                            </div>
		                            <div>
		                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">meta_title</label>
		                                <input type="text" name="meta_title" data-head-page-field="meta_title" value="{{ $page->meta_title }}"
		                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
		                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.meta_title</code></p>
                                        <div class="mt-2" data-page-ai-field="meta_title">
                                            <textarea rows="2" class="page-ai-prompt mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm" placeholder="AI Prompt"></textarea>
                                            <div class="mt-2 flex items-center gap-2">
                                                <button type="button" class="page-ai-generate-btn inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500">Generate</button>
                                                <select class="page-ai-model block rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    @foreach(($aiModelOptions ?? []) as $modelOption)
                                                        <option value="{{ $modelOption['value'] }}" {{ $modelOption['value'] === 'medium_main' ? 'selected' : '' }}>{{ $modelOption['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <details class="ai-prompt-rule mt-2 rounded-md border border-gray-200 p-2 dark:border-gray-700" data-ai-rule-field="meta_title">
                                                <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-300">AI Prompt Rule</summary>
                                                <textarea rows="2" class="ai-prompt-rule-input mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                                <button type="button" class="ai-prompt-rule-save mt-2 rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600">Save Rule</button>
                                            </details>
                                        </div>
		                            </div>
		                            <div>
		                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">meta_description</label>
		                                <textarea rows="3" name="meta_description" data-head-page-field="meta_description"
		                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $page->meta_description }}</textarea>
		                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.meta_description</code></p>
                                        <div class="mt-2" data-page-ai-field="meta_description">
                                            <textarea rows="2" class="page-ai-prompt mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm" placeholder="AI Prompt"></textarea>
                                            <div class="mt-2 flex items-center gap-2">
                                                <button type="button" class="page-ai-generate-btn inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500">Generate</button>
                                                <select class="page-ai-model block rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    @foreach(($aiModelOptions ?? []) as $modelOption)
                                                        <option value="{{ $modelOption['value'] }}" {{ $modelOption['value'] === 'medium_main' ? 'selected' : '' }}>{{ $modelOption['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <details class="ai-prompt-rule mt-2 rounded-md border border-gray-200 p-2 dark:border-gray-700" data-ai-rule-field="meta_description">
                                                <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-300">AI Prompt Rule</summary>
                                                <textarea rows="2" class="ai-prompt-rule-input mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                                <button type="button" class="ai-prompt-rule-save mt-2 rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600">Save Rule</button>
                                            </details>
                                        </div>
		                            </div>
		                            <div>
		                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">canonical</label>
		                                <input type="text" name="canonical" data-head-page-field="canonical" value="{{ $page->canonical }}"
		                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
		                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.canonical</code></p>
		                            </div>
		                            <div>
		                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">page locale</label>
		                                <input type="text" data-head-page-field="locale" value="{{ $page->locale }}"
	                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
	                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.locale</code></p>
	                            </div>
	                        </div>

	                        <div class="mt-5 space-y-3">
	                            <div class="flex items-center justify-between gap-3">
	                                <h5 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Head meta</h5>
	                                <button type="button" data-add-head-meta
		                                        class="inline-flex cursor-pointer items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600 dark:hover:bg-gray-600">
	                                    Add meta
	                                </button>
	                            </div>
	                            <div data-head-meta-list class="space-y-3">
	                                @forelse($headMetaRows as $metaIndex => $metaItem)
	                                    @php $metaItem = is_array($metaItem) ? $metaItem : []; @endphp
		                                    <div data-head-meta-row class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
	                                        <div class="mb-2 flex items-center justify-between gap-3">
	                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">head_meta.{{ $metaIndex }}</span>
	                                            <button type="button" data-remove-head-row
	                                                    class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">Remove</button>
	                                        </div>
	                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">name</label>
	                                                <input type="text" data-head-meta-key="name" value="{{ $metaItem['name'] ?? '' }}"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">property</label>
	                                                <input type="text" data-head-meta-key="property" value="{{ $metaItem['property'] ?? '' }}"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">http_equiv</label>
	                                                <input type="text" data-head-meta-key="http_equiv" value="{{ $metaItem['http_equiv'] ?? '' }}"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">content</label>
	                                                <input type="text" data-head-meta-key="content" value="{{ $metaItem['content'] ?? '' }}"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                        </div>
                                            @if(in_array($metaIndex, [3, 4], true))
                                                <div class="mt-3" data-page-ai-field="head_meta.{{ $metaIndex }}.content">
                                                    <textarea rows="2" class="page-ai-prompt mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm" placeholder="AI Prompt"></textarea>
                                                    <div class="mt-2 flex items-center gap-2">
                                                        <button type="button" class="page-ai-generate-btn inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500">Generate</button>
                                                        <select class="page-ai-model block rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                            @foreach(($aiModelOptions ?? []) as $modelOption)
                                                                <option value="{{ $modelOption['value'] }}" {{ $modelOption['value'] === 'medium_main' ? 'selected' : '' }}>{{ $modelOption['label'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
	                                    </div>
	                                @empty
		                                    <div data-head-meta-row class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
	                                        <div class="mb-2 flex items-center justify-between gap-3">
	                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">head_meta.new</span>
	                                            <button type="button" data-remove-head-row
	                                                    class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">Remove</button>
	                                        </div>
	                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">name</label>
	                                                <input type="text" data-head-meta-key="name"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">property</label>
	                                                <input type="text" data-head-meta-key="property"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">http_equiv</label>
	                                                <input type="text" data-head-meta-key="http_equiv"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                            <div>
	                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">content</label>
	                                                <input type="text" data-head-meta-key="content"
	                                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                            </div>
	                                        </div>
	                                    </div>
	                                @endforelse
	                            </div>
	                        </div>

	                        <div class="mt-5 space-y-3">
	                            <div class="flex items-center justify-between gap-3">
	                                <h5 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Head links</h5>
	                                <button type="button" data-add-head-link
		                                        class="inline-flex cursor-pointer items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600 dark:hover:bg-gray-600">
	                                    Add link
	                                </button>
	                            </div>
	                            <div data-head-link-list class="space-y-3">
	                                @forelse($headLinkRows as $linkIndex => $linkItem)
	                                    @php $linkItem = is_array($linkItem) ? $linkItem : []; @endphp
		                                    <div data-head-link-row class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
	                                        <div class="mb-2 flex items-center justify-between gap-3">
	                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">head_links.{{ $linkIndex }}</span>
	                                            <button type="button" data-remove-head-row
	                                                    class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">Remove</button>
	                                        </div>
	                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
	                                            @foreach(['rel', 'href', 'hreflang', 'type', 'sizes'] as $linkKey)
	                                                <div>
	                                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">{{ $linkKey }}</label>
	                                                    <input type="text" data-head-link-key="{{ $linkKey }}" value="{{ $linkItem[$linkKey] ?? '' }}"
	                                                           class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
	                                                </div>
	                                            @endforeach
	                                        </div>
	                                    </div>
	                                @empty
	                                @endforelse
	                            </div>
	                        </div>

	                        <div class="mt-5 space-y-4">
	                            @foreach($headExtraScripts as $scriptIndex => $scriptBody)
	                                <div>
	                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Head JSON-LD script block #{{ $scriptIndex + 1 }}</label>
	                                    <textarea rows="10" data-head-extra-script="{{ $scriptIndex }}"
	                                              class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ trim($scriptBody) }}</textarea>
	                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.og_data.head_extra.__script__.{{ $scriptIndex }}</code></p>
	                                </div>
	                            @endforeach

	                            <div>
	                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Head extra HTML</label>
	                                <textarea rows="5" data-head-extra-template
	                                          class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $headExtra }}</textarea>
	                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.og_data.head_extra</code>. JSON-LD script blocks above are written back into this HTML.</p>
	                            </div>

	                            <div>
	                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Head custom scripts/styles</label>
	                                <textarea rows="4" data-head-custom
	                                          class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $headCustom }}</textarea>
	                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.og_data.head_custom</code></p>
	                            </div>

	                            <div>
	                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Body extra</label>
	                                <textarea rows="4" data-body-extra
	                                          class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $bodyExtra }}</textarea>
	                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><code>pages.0.og_data.body_extra</code></p>
	                            </div>
	                        </div>

	                        <div class="mt-5">
	                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Head JSON</label>
	                            <textarea name="og_data" rows="8" readonly
	                                      class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 font-mono text-sm text-gray-700 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ $page->og_data ? json_encode($page->og_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
	                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Generated from SECTION HEAD fields before save.</p>
	                        </div>
                            </div>
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
                    @php
                        $moduleKey = is_array($section->content ?? null)
                            ? ($section->content['module'] ?? $section->content['module_key'] ?? 'module')
                            : 'module';
                    @endphp
                <div class="rounded-md border border-gray-200 dark:border-gray-700 p-4 section-item" data-section-id="{{ $section->id }}" data-module-key="{{ $moduleKey }}">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">Module: {{ $moduleKey }}</h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Section #{{ $section->id }}</p>
                        </div>
                        <button type="button"
                                data-collapsible-toggle
                                data-collapsible-target="#section-panel-{{ $section->id }}"
                                class="inline-flex cursor-pointer items-center rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600 dark:hover:bg-gray-600">
                            Collapse
                        </button>
                    </div>
                    <div id="section-panel-{{ $section->id }}" class="section-collapsible-body">
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

                    <div class="mt-4 rounded-md border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/20">
                        <div class="grid grid-cols-1 gap-3">
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">AI Prompt</label>
                                <textarea rows="3"
                                          class="ai-section-prompt mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                          placeholder="Describe what to create or rewrite in this module"></textarea>
                                <details class="ai-prompt-rule mt-2 rounded-md border border-gray-200 p-2 dark:border-gray-700" data-ai-rule-field="{{ $moduleKey }}/module_prompt">
                                    <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-300">AI Prompt Rule</summary>
                                    <textarea rows="2" class="ai-prompt-rule-input mt-2 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                    <button type="button" class="ai-prompt-rule-save mt-2 rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:ring-gray-600">Save Rule</button>
                                </details>
                            </div>
                            <div class="ai-section-control-row flex flex-col gap-3 lg:flex-row lg:items-end">
                                <button type="button"
                                        style="background-color: #059669 !important;"
                                        class="ai-section-control-button ai-generate-section-btn inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus-visible:outline-none">
                                    Generate
                                </button>
                                <div class="ai-section-control-field min-w-0 lg:flex-1 lg:basis-0">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                                    <select class="ai-section-model mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @foreach(($aiModelOptions ?? []) as $modelOption)
                                            <option value="{{ $modelOption['value'] }}" {{ $modelOption['value'] === 'medium_main' ? 'selected' : '' }}>
                                                {{ $modelOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ai-section-control-field min-w-0 lg:flex-1 lg:basis-0">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Context</label>
                                    <select class="ai-section-context-mode mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="none" selected>Nothing</option>
                                        <option value="previous">Previous module only</option>
                                        <option value="next">Next module only</option>
                                        <option value="adjacent">Previous and next modules</option>
                                        <option value="all">All modules</option>
                                        <option value="selected">Selected modules</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ai-section-context-selected hidden lg:col-span-3">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($page->sections as $contextSection)
                                        @if((int) $contextSection->id !== (int) $section->id)
                                            @php
                                                $contextModuleKey = is_array($contextSection->content ?? null)
                                                    ? ($contextSection->content['module'] ?? $contextSection->content['module_key'] ?? 'module')
                                                    : 'module';
                                            @endphp
                                            <label class="flex items-center gap-2 rounded-md border border-gray-200 bg-white p-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                <input type="checkbox" class="ai-context-section-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="{{ $contextSection->id }}">
                                                <span>#{{ $contextSection->order + 1 }} {{ $contextModuleKey }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
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
                        <button type="button"
                                class="section-history-btn inline-flex cursor-pointer items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus-visible:outline-none dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                            History
                        </button>
                    </div>
                    <div class="section-history-panel mt-3 hidden rounded-md border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Module History</h4>
                            <button type="button" class="section-history-close-btn text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Close</button>
                        </div>
                        <div class="section-history-status hidden rounded-md border px-3 py-2 text-sm"></div>
                        <div class="section-history-list space-y-2"></div>
                    </div>
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

<button id="preview-widget-btn" type="button"
        title="Save and preview"
        aria-label="Save and preview"
        class="fixed bottom-6 right-6 z-50 inline-flex h-14 w-14 cursor-pointer items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg ring-1 ring-indigo-500/40 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 dark:bg-indigo-500 dark:ring-indigo-300/30 dark:hover:bg-indigo-400">
    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z" />
        <circle cx="12" cy="12" r="2.75" />
    </svg>
</button>

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
const currentTemplateSet = @json($site->template_set);
const currentPageRuleKey = @json($page->template_key ?: $page->slug ?: 'page');
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

function getHeadPageField(field) {
    return document.querySelector(`[data-head-page-field="${field}"]`);
}

function getPageAiTarget(field) {
    const headMetaMatch = String(field || '').match(/^head_meta\.(\d+)\.content$/);
    if (headMetaMatch) {
        const row = document.querySelectorAll('[data-head-meta-row]')[Number(headMetaMatch[1])];
        return row?.querySelector('[data-head-meta-key="content"]') || null;
    }

    return getHeadPageField(field);
}

function collectHeadRows(rowSelector, keySelector) {
    return Array.from(document.querySelectorAll(rowSelector)).map((row) => {
        const item = {};

        row.querySelectorAll(keySelector).forEach((input) => {
            const key = input.dataset.headMetaKey || input.dataset.headLinkKey;
            const value = input.value.trim();

            if (key && value !== '') {
                item[key] = value;
            }
        });

        return item;
    }).filter((item) => Object.keys(item).length > 0);
}

function buildHeadExtraWithScripts(templateHtml, scripts) {
    const scriptBodies = Array.isArray(scripts) ? scripts : [];
    let scriptIndex = 0;
    let html = String(templateHtml || '');
    const scriptPattern = /<script\b([^>]*)type=(["'])application\/ld\+json\2([^>]*)>[\s\S]*?<\/script>/gi;

    html = html.replace(scriptPattern, (fullMatch, beforeType, quote, afterType) => {
        if (scriptIndex >= scriptBodies.length) {
            return fullMatch;
        }

        const body = scriptBodies[scriptIndex];
        scriptIndex += 1;
        return `<script${beforeType}type=${quote}application/ld+json${quote}${afterType}>\n${body.trim()}\n<\/script>`;
    });

    for (; scriptIndex < scriptBodies.length; scriptIndex += 1) {
        const body = scriptBodies[scriptIndex].trim();
        if (body === '') {
            continue;
        }

        html += `${html.trim() === '' ? '' : '\n'}<script type="application/ld+json">\n${body}\n<\/script>`;
    }

    return html;
}

function syncHeadJsonFromFields() {
    if (!ogDataField) {
        return null;
    }

    const ogData = parseLooseJsonObject(ogDataField.value) || {};
    const headMeta = collectHeadRows('[data-head-meta-row]', '[data-head-meta-key]');
    const headLinks = collectHeadRows('[data-head-link-row]', '[data-head-link-key]');
    const headExtraTemplate = document.querySelector('[data-head-extra-template]')?.value || '';
    const headExtraScripts = Array.from(document.querySelectorAll('[data-head-extra-script]')).map((field) => field.value || '');
    const headCustom = document.querySelector('[data-head-custom]')?.value || '';
    const bodyExtra = document.querySelector('[data-body-extra]')?.value || '';

    ogData.head_meta = headMeta;
    ogData.head_links = headLinks;
    ogData.head_extra = buildHeadExtraWithScripts(headExtraTemplate, headExtraScripts);
    ogData.head_custom = headCustom;
    ogData.body_extra = bodyExtra;

    ogDataField.value = JSON.stringify(ogData, null, 4);
    return ogData;
}

function createHeadMetaRow() {
    const row = document.createElement('div');
    row.dataset.headMetaRow = '';
    row.className = 'rounded-md border border-gray-200 p-3 dark:border-gray-700';
    row.innerHTML = `
        <div class="mb-2 flex items-center justify-between gap-3">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">head_meta.new</span>
            <button type="button" data-remove-head-row class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">Remove</button>
        </div>
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
            ${['name', 'property', 'http_equiv', 'content'].map((key) => `
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">${key}</label>
                    <input type="text" data-head-meta-key="${key}" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            `).join('')}
        </div>
    `;

    return row;
}

function createHeadLinkRow() {
    const row = document.createElement('div');
    row.dataset.headLinkRow = '';
    row.className = 'rounded-md border border-gray-200 p-3 dark:border-gray-700';
    row.innerHTML = `
        <div class="mb-2 flex items-center justify-between gap-3">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">head_links.new</span>
            <button type="button" data-remove-head-row class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">Remove</button>
        </div>
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            ${['rel', 'href', 'hreflang', 'type', 'sizes'].map((key) => `
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">${key}</label>
                    <input type="text" data-head-link-key="${key}" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            `).join('')}
        </div>
    `;

    return row;
}

document.querySelectorAll('[data-head-page-field]').forEach((field) => {
    field.addEventListener('input', () => {
        syncHeadJsonFromFields();
    });
});

document.querySelector('[data-add-head-meta]')?.addEventListener('click', () => {
    document.querySelector('[data-head-meta-list]')?.appendChild(createHeadMetaRow());
    syncHeadJsonFromFields();
});

document.querySelector('[data-add-head-link]')?.addEventListener('click', () => {
    document.querySelector('[data-head-link-list]')?.appendChild(createHeadLinkRow());
    syncHeadJsonFromFields();
});

document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-remove-head-row]');
    if (!removeButton) {
        return;
    }

    removeButton.closest('[data-head-meta-row], [data-head-link-row]')?.remove();
    syncHeadJsonFromFields();
});

document.addEventListener('input', (event) => {
    if (event.target.closest('[data-head-meta-row], [data-head-link-row]')
        || event.target.matches('[data-head-extra-template], [data-head-extra-script], [data-head-custom], [data-body-extra]')) {
        syncHeadJsonFromFields();
    }
});

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
    await commitPendingPrompt(container);
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

function renderSectionHistoryStatus(container, message, type = 'info') {
    const status = container.querySelector('.section-history-status');
    if (!status) {
        return;
    }

    status.textContent = message;
    status.className = 'section-history-status rounded-md border px-3 py-2 text-sm';

    if (type === 'error') {
        status.classList.add('border-red-200', 'bg-red-50', 'text-red-700', 'dark:border-red-800', 'dark:bg-red-900/20', 'dark:text-red-200');
    } else {
        status.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-700', 'dark:border-gray-700', 'dark:bg-gray-900', 'dark:text-gray-200');
    }
}

function formatSectionHistoryDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString([], {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function renderSectionHistory(container, histories) {
    const list = container.querySelector('.section-history-list');
    if (!list) {
        return;
    }

    list.innerHTML = '';

    if (!Array.isArray(histories) || histories.length === 0) {
        renderSectionHistoryStatus(container, 'No saved history for this module yet.');
        return;
    }

    container.querySelector('.section-history-status')?.classList.add('hidden');

    histories.forEach((history) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'block w-full rounded-md border border-gray-200 px-3 py-2 text-left text-sm font-semibold text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500 dark:border-gray-700 dark:text-indigo-400 dark:hover:bg-indigo-950/20 dark:hover:text-indigo-300';
        button.textContent = formatSectionHistoryDate(history.created_at) || history.label || `History #${history.id}`;
        button.addEventListener('click', async () => {
            await restoreSectionHistory(container, history.id);
        });

        list.append(button);
    });
}

async function loadSectionHistory(sectionId, container) {
    renderSectionHistoryStatus(container, 'Loading history...');

    const response = await fetch(`/api/sections/${sectionId}/history`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        renderSectionHistoryStatus(container, 'Error: ' + message, 'error');
        return;
    }

    renderSectionHistory(container, result.histories || []);
}

async function restoreSectionHistory(container, historyId) {
    const sectionId = container.dataset.sectionId;
    if (!sectionId || !confirm('Restore this saved module version? Current module content will be saved to history first.')) {
        return;
    }

    renderSectionHistoryStatus(container, 'Restoring module...');

    const response = await fetch(`/api/sections/${sectionId}/history/${historyId}/restore`, {
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
        renderSectionHistoryStatus(container, 'Error: ' + message, 'error');
        return;
    }

    renderPageEditStatus(`Module #${sectionId} restored. Reloading...`, 'success');
    window.location.reload();
}

function setGeneratedSectionHtml(container, html) {
    const contentTextarea = container.querySelector('.section-content');
    if (!contentTextarea) {
        throw new Error('Section content field was not found.');
    }

    const content = parseSectionJson(contentTextarea.value, 'Section content');
    if (content === false) {
        throw new Error('Section content JSON is invalid.');
    }

    content.raw_html = html;
    if (String(html || '').trim() !== '') {
        content.render_mode = 'raw_html';
    }

    contentTextarea.value = JSON.stringify(content, null, 4);
    contentTextarea.dispatchEvent(new Event('input', { bubbles: true }));

    const rawHtmlTextarea = container.querySelector('.section-raw-html');
    if (rawHtmlTextarea) {
        rawHtmlTextarea.value = html;
    }
}

async function loadAiPromptRule(details) {
    const fieldKey = details?.dataset.aiRuleField || '';
    const input = details?.querySelector('.ai-prompt-rule-input');
    if (!fieldKey || !input || details.dataset.ruleLoaded === 'true') {
        return;
    }

    const params = new URLSearchParams({
        template_set: currentTemplateSet,
        page_key: currentPageRuleKey,
        field_key: fieldKey,
    });

    const response = await fetch(`/api/ai-agent/prompt-rule?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const result = await readApiResponse(response);
    if (!response.ok) {
        throw new Error(result.error || result.message || `Request failed with status ${response.status}`);
    }

    input.value = result.rule || '';
    details.dataset.ruleLoaded = 'true';
}

async function saveAiPromptRule(details) {
    const fieldKey = details?.dataset.aiRuleField || '';
    const input = details?.querySelector('.ai-prompt-rule-input');
    if (!fieldKey || !input) {
        return;
    }

    const response = await fetch('/api/ai-agent/prompt-rule', {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            template_set: currentTemplateSet,
            page_key: currentPageRuleKey,
            field_key: fieldKey,
            rule: input.value || '',
        }),
    });

    const result = await readApiResponse(response);
    if (!response.ok) {
        throw new Error(result.error || result.message || `Request failed with status ${response.status}`);
    }

    details.dataset.ruleLoaded = 'true';
    renderPageEditStatus('AI prompt rule saved.', 'success');
}

async function generatePageAiField(container) {
    const button = container.querySelector('.page-ai-generate-btn');
    const fieldKey = container.dataset.pageAiField || '';
    const prompt = container.querySelector('.page-ai-prompt')?.value.trim() || '';
    if (!fieldKey || prompt === '') {
        renderAiGenerateError(button, 'AI prompt cannot be empty.');
        renderPageEditStatus('AI prompt cannot be empty.', 'error');
        return;
    }

    renderAiGenerateError(button, '');
    setInlineButtonBusy(button, true, 'Generating…');
    renderPageEditStatus(`Generating ${fieldKey}...`, 'warning');

    const selectedSectionIds = Array.from(container.querySelectorAll('.page-ai-context-section-checkbox:checked'))
        .map((checkbox) => Number(checkbox.value))
        .filter((value) => Number.isInteger(value) && value > 0);
    const response = await fetch('/api/ai-agent/pages/{{ $page->id }}/field/generate', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            field_key: fieldKey,
            prompt,
            model_key: container.querySelector('.page-ai-model')?.value || 'medium_main',
            context_mode: container.querySelector('.page-ai-context-mode')?.value || 'none',
            context_section_ids: selectedSectionIds,
        }),
    });

    const result = await readApiResponse(response);
    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        renderAiGenerateError(button, message);
        setInlineButtonBusy(button, false);
        renderPageEditStatus('Error: ' + message, 'error');
        return;
    }

    container.dataset.pendingAiPrompt = prompt;

    const field = getPageAiTarget(fieldKey);
    if (field) {
        field.value = result.value || '';
        field.dispatchEvent(new Event('input', { bubbles: true }));
        syncHeadJsonFromFields();
    }

    renderPageEditStatus(`${fieldKey} generated. Review it, then save the page.`, 'success');
    setInlineButtonBusy(button, false);
}

function initializePageAiContext(container) {
    if (container.querySelector('.page-ai-context-mode')) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'mt-2';
    wrapper.innerHTML = `
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Context</label>
        <select class="page-ai-context-mode mt-1 block w-full rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="none" selected>Nothing</option>
            <option value="all">All modules</option>
            <option value="selected">Selected modules</option>
        </select>
        <div class="page-ai-context-selected mt-2 hidden grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($page->sections as $contextSection)
                @php
                    $headContextModuleKey = is_array($contextSection->content ?? null)
                        ? ($contextSection->content['module'] ?? $contextSection->content['module_key'] ?? 'module')
                        : 'module';
                @endphp
                <label class="flex items-center gap-2 rounded-md border border-gray-200 bg-white p-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <input type="checkbox" class="page-ai-context-section-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="{{ $contextSection->id }}">
                    <span>#{{ $contextSection->order + 1 }} {{ $headContextModuleKey }}</span>
                </label>
            @endforeach
        </div>
    `;
    container.appendChild(wrapper);
    wrapper.querySelector('.page-ai-context-mode')?.addEventListener('change', (event) => {
        wrapper.querySelector('.page-ai-context-selected')?.classList.toggle('hidden', event.currentTarget.value !== 'selected');
    });
}

async function generateSectionContent(sectionId, container, button) {
    const promptInput = container.querySelector('.ai-section-prompt');
    const prompt = promptInput?.value.trim() || '';
    if (prompt === '') {
        renderAiGenerateError(button, 'AI prompt cannot be empty.');
        renderPageEditStatus('AI prompt cannot be empty.', 'error');
        return;
    }

    const contextMode = container.querySelector('.ai-section-context-mode')?.value || 'none';
    const selectedSectionIds = Array.from(container.querySelectorAll('.ai-context-section-checkbox:checked'))
        .map((checkbox) => Number(checkbox.value))
        .filter((value) => Number.isInteger(value) && value > 0);

    renderAiGenerateError(button, '');
    setInlineButtonBusy(button, true, 'Generating…');
    renderPageEditStatus(`Generating module #${sectionId}…`, 'warning');

    const response = await fetch(`/api/ai-agent/sections/${sectionId}/generate`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            prompt,
            model_key: container.querySelector('.ai-section-model')?.value || 'medium_main',
            context_mode: contextMode,
            context_section_ids: selectedSectionIds,
        }),
    });

    const result = await readApiResponse(response);
    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        renderAiGenerateError(button, message);
        renderPageEditStatus('Error: ' + message, 'error');
        setInlineButtonBusy(button, false);
        return;
    }

    container.dataset.pendingAiPrompt = prompt;

    setGeneratedSectionHtml(container, result.html || '');
    renderPageEditStatus(`Module #${sectionId} generated. Review it, then save the module.`, 'success');
    setInlineButtonBusy(button, false);
}

function promptHistoryScope(container) {
    const section = container.closest('.section-item');
    const fieldKey = container.dataset.pageAiField || 'module_prompt';

    return {
        template_set: @json((string) ($site->template_set ?? 'base')),
        page_key: @json((string) ($page->template_key ?: $page->slug ?: 'page')),
        module_key: section?.dataset.moduleKey || 'head',
        locale: @json((string) ($page->locale ?? 'en')),
        field_key: fieldKey,
    };
}

async function promptHistoryRequest(path, method, payload = null) {
    const query = method === 'GET' ? `?${new URLSearchParams(payload).toString()}` : '';
    const response = await fetch(`/api/ai-prompt-history${path}${query}`, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: method === 'GET' ? null : JSON.stringify(payload),
    });
    const result = await readApiResponse(response);
    if (!response.ok) throw new Error(result.error || result.message || 'Prompt history request failed');
    return result;
}

async function loadPromptHistory(container) {
    const result = await promptHistoryRequest('/', 'GET', promptHistoryScope(container));
    const input = container.querySelector('.page-ai-prompt, .ai-section-prompt');
    const history = Array.isArray(result.history) ? result.history : [];
    const favorites = Array.isArray(result.favorites) ? result.favorites : [];
    if (input && input.value.trim() === '' && history[0]?.prompt) input.value = history[0].prompt;
    container.dataset.latestPromptHistoryId = history[0]?.id || '';

    const panel = container.querySelector('.ai-prompt-history-panel');
    if (!panel) return;
    const renderItems = (items) => items.map((item) => `
        <div class="flex items-start gap-2 rounded border border-gray-200 p-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
            <button type="button" class="ai-prompt-history-use flex-1 text-left text-gray-700 dark:text-gray-200" data-prompt="${encodeURIComponent(item.prompt)}">${String(item.prompt).replace(/</g, '&lt;')}</button>
            <button type="button" class="ai-prompt-history-delete text-rose-600 dark:text-rose-300" data-id="${item.id}">Delete</button>
        </div>`).join('');
    panel.innerHTML = `
        <div class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-200">History</div>${renderItems(history.slice(1)) || '<div class="text-xs text-gray-500 dark:text-gray-400">Empty</div>'}
        <div class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-200">Favorites</div>${renderItems(favorites) || '<div class="text-xs text-gray-500 dark:text-gray-400">Empty</div>'}`;
    panel.querySelectorAll('.ai-prompt-history-use').forEach((button) => button.addEventListener('click', () => {
        if (input) input.value = decodeURIComponent(button.dataset.prompt || '');
    }));
    panel.querySelectorAll('.ai-prompt-history-delete').forEach((button) => button.addEventListener('click', async () => {
        await promptHistoryRequest(`/${button.dataset.id}`, 'DELETE');
        await loadPromptHistory(container);
    }));
}

function initializePromptHistory(container) {
    const input = container.querySelector('.page-ai-prompt, .ai-section-prompt');
    if (!input || container.querySelector('.ai-prompt-history-controls')) return;
    const controls = document.createElement('div');
    controls.className = 'ai-prompt-history-controls mt-2';
    controls.innerHTML = '<div class="flex gap-2"><button type="button" class="ai-prompt-history-toggle text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">History</button><button type="button" class="ai-prompt-favorite text-xs font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">Add to favorites</button><button type="button" class="ai-prompt-delete-current text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-300 dark:hover:text-rose-200">Delete current</button></div><div class="ai-prompt-history-panel hidden"></div>';
    input.insertAdjacentElement('afterend', controls);
    controls.querySelector('.ai-prompt-history-toggle')?.addEventListener('click', () => controls.querySelector('.ai-prompt-history-panel')?.classList.toggle('hidden'));
    controls.querySelector('.ai-prompt-favorite')?.addEventListener('click', async () => {
        const prompt = input.value.trim();
        if (!prompt) return;
        await promptHistoryRequest('/favorite', 'POST', { ...promptHistoryScope(container), prompt });
        await loadPromptHistory(container);
    });
    controls.querySelector('.ai-prompt-delete-current')?.addEventListener('click', async () => {
        const id = container.dataset.latestPromptHistoryId || '';
        if (!id) return;
        await promptHistoryRequest(`/${id}`, 'DELETE');
        input.value = '';
        await loadPromptHistory(container);
    });
    loadPromptHistory(container).catch(() => {});
}

async function commitPendingPrompt(container) {
    const prompt = container.dataset.pendingAiPrompt || '';
    if (!prompt) return;
    await promptHistoryRequest('/record', 'POST', { ...promptHistoryScope(container), prompt });
    delete container.dataset.pendingAiPrompt;
    await loadPromptHistory(container);
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

    const writePreviewWindowMessage = (title, message) => {
        if (!previewWindow) {
            return;
        }

        const safeTitle = String(title || 'Preview').replace(/[<>&"]/g, '');
        const safeMessage = String(message || '').replace(/[<>&"]/g, '');
        previewWindow.document.open();
        previewWindow.document.write(`<title>${safeTitle}</title><p style="font-family: sans-serif; padding: 16px;">${safeMessage}</p>`);
        previewWindow.document.close();
    };

    try {
        if (typeof window.waitForBackgroundSelections === 'function') {
            await window.waitForBackgroundSelections();
        }

        const settingsSaved = await savePageSettings();
        if (!settingsSaved) {
            writePreviewWindowMessage('Preview error', 'Page settings were not saved.');
            return;
        }

        const sectionsSaved = await saveAllSectionsSilently();
        if (!sectionsSaved) {
            writePreviewWindowMessage('Preview error', 'Modules were not saved.');
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
            writePreviewWindowMessage('Preview error', message);
            renderPageEditStatus('Error: ' + message, 'error');
            return;
        }

        if (!result.preview_url) {
            writePreviewWindowMessage('Preview error', 'Preview URL is missing in response.');
            renderPageEditStatus('Error: Preview URL is missing in response.', 'error');
            return;
        }

        if (previewWindow) {
            previewWindow.location.href = new URL(result.preview_url, window.location.origin).toString();
            if (!document.getElementById('preview-history-panel')?.classList.contains('hidden')) {
                await loadPreviewHistory();
            }
            return;
        }

        renderPageEditStatus('Preview window was blocked by the browser. Allow pop-ups for this admin panel and click Preview again.', 'warning');
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
    const previewButtons = document.querySelectorAll('#preview-page-btn, #preview-widget-btn');

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

    previewButtons.forEach((button) => {
        button.disabled = busy;
        button.classList.toggle('opacity-60', busy);
        button.classList.toggle('cursor-not-allowed', busy);
    });
}

function setInlineButtonBusy(button, busy, busyText = 'Saving…') {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultText) {
        button.dataset.defaultText = button.textContent.trim();
        button.dataset.defaultHtml = button.innerHTML;
    }

    button.disabled = busy;
    button.innerHTML = busy
        ? `<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path></svg><span>${busyText}</span>`
        : button.dataset.defaultHtml;
    button.classList.toggle('gap-2', busy);
    button.classList.toggle('opacity-60', busy);
    button.classList.toggle('cursor-not-allowed', busy);
}

function renderAiGenerateError(button, message) {
    if (!button) return;
    const host = button.parentElement || button;
    let error = host.parentElement?.querySelector('.ai-generate-inline-error');
    if (!error) {
        error = document.createElement('p');
        error.className = 'ai-generate-inline-error mt-2 text-xs font-medium text-rose-600 dark:text-rose-300';
        host.insertAdjacentElement('afterend', error);
    }
    error.textContent = message || '';
    error.classList.toggle('hidden', !message);
}

function currentSaveTime() {
    return new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function collapsibleStorageKey(targetSelector) {
    return `page-edit-collapsed:{{ $page->id }}:${targetSelector}`;
}

function setCollapsibleState(button, collapsed) {
    const targetSelector = button.dataset.collapsibleTarget || '';
    const target = targetSelector ? document.querySelector(targetSelector) : null;
    if (!target) {
        return;
    }

    target.classList.toggle('hidden', collapsed);
    button.textContent = collapsed ? 'Expand' : 'Collapse';
    button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    localStorage.setItem(collapsibleStorageKey(targetSelector), collapsed ? '1' : '0');
}

function initializeCollapsibles() {
    document.querySelectorAll('[data-collapsible-toggle]').forEach((button) => {
        const targetSelector = button.dataset.collapsibleTarget || '';
        if (!targetSelector) {
            return;
        }

        const collapsed = localStorage.getItem(collapsibleStorageKey(targetSelector)) === '1';
        setCollapsibleState(button, collapsed);

        button.addEventListener('click', () => {
            const target = document.querySelector(targetSelector);
            setCollapsibleState(button, !target?.classList.contains('hidden'));
        });
    });
}

function renderPreviewHistoryStatus(message, type = 'info') {
    const status = document.getElementById('preview-history-status');
    if (!status) {
        return;
    }

    status.textContent = message;
    status.className = 'rounded-md border px-3 py-2 text-sm';

    if (type === 'error') {
        status.classList.add('border-red-200', 'bg-red-50', 'text-red-700', 'dark:border-red-800', 'dark:bg-red-900/20', 'dark:text-red-200');
    } else {
        status.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-700', 'dark:border-gray-700', 'dark:bg-gray-900', 'dark:text-gray-200');
    }
}

function formatPreviewDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString([], {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function renderPreviewHistory(previews) {
    const list = document.getElementById('preview-history-list');
    if (!list) {
        return;
    }

    list.innerHTML = '';

    if (!Array.isArray(previews) || previews.length === 0) {
        renderPreviewHistoryStatus('No previews for this page yet.');
        return;
    }

    document.getElementById('preview-history-status')?.classList.add('hidden');

    previews.forEach((preview) => {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-3 rounded-md border border-gray-200 p-2 dark:border-gray-700';

        const link = document.createElement('button');
        link.type = 'button';
        link.className = 'min-w-0 flex-1 text-left text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300';
        link.textContent = `${preview.title || 'Preview'}${preview.created_at ? ' — ' + formatPreviewDate(preview.created_at) : ''}`;
        link.addEventListener('click', () => {
            window.open(new URL(preview.url, window.location.origin).toString(), '_blank', 'noopener');
        });

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'shrink-0 rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-500';
        deleteButton.textContent = 'Delete';
        deleteButton.addEventListener('click', async () => {
            await deletePreviewHistoryItem(preview.id);
        });

        row.append(link, deleteButton);
        list.append(row);
    });
}

async function loadPreviewHistory() {
    renderPreviewHistoryStatus('Loading previews...');

    const response = await fetch('/api/pages/{{ $page->id }}/previews', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        renderPreviewHistoryStatus('Error: ' + message, 'error');
        return;
    }

    renderPreviewHistory(result.previews || []);
}

async function deletePreviewHistoryItem(previewId) {
    if (!confirm('Delete this preview from disk and database?')) {
        return;
    }

    const response = await fetch(`/api/pages/{{ $page->id }}/previews/${previewId}`, {
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
        renderPreviewHistoryStatus('Error: ' + message, 'error');
        return;
    }

    await loadPreviewHistory();
}

async function savePageSettings() {
    const pageForm = document.getElementById('page-form');
    if (!pageForm) {
        return false;
    }

	    syncHeadJsonFromFields();
	    applyPublishedTimeSyncToOgDataField();

    const formData = new FormData(pageForm);
    const data = Object.fromEntries(formData);

	    if (!String(data.canonical || '').trim()) {
	        data.canonical = buildCanonicalFromSlug(data.slug || '');
	    }

	    const localeField = getHeadPageField('locale');
	    if (localeField) {
	        data.locale = localeField.value;
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

    for (const container of document.querySelectorAll('[data-page-ai-field]')) {
        await commitPendingPrompt(container);
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
            renderPageEditStatus(`Module save failed (section #${sectionId}). Action aborted.`, 'error');
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

initializeCollapsibles();

document.querySelectorAll('.ai-prompt-rule').forEach((details) => {
    details.addEventListener('toggle', async () => {
        if (!details.open) {
            return;
        }

        try {
            await loadAiPromptRule(details);
        } catch (error) {
            renderPageEditStatus('Error: ' + error.message, 'error');
        }
    });

    details.querySelector('.ai-prompt-rule-save')?.addEventListener('click', async () => {
        try {
            await saveAiPromptRule(details);
        } catch (error) {
            renderPageEditStatus('Error: ' + error.message, 'error');
        }
    });
});

document.querySelectorAll('[data-page-ai-field]').forEach((container) => {
    initializePageAiContext(container);
    initializePromptHistory(container);
    container.querySelector('.page-ai-generate-btn')?.addEventListener('click', async (event) => {
        try {
            await generatePageAiField(container);
        } catch (error) {
            setInlineButtonBusy(event.currentTarget, false);
            renderAiGenerateError(event.currentTarget, error.message || 'Model is unavailable. Generation failed.');
            renderPageEditStatus('Error: ' + error.message, 'error');
        }
    });
});

document.querySelectorAll('.section-item').forEach((container) => {
    const sectionId = container.dataset.sectionId;
    initializePromptHistory(container);

    container.querySelector('.ai-section-context-mode')?.addEventListener('change', (event) => {
        const selectedBox = container.querySelector('.ai-section-context-selected');
        selectedBox?.classList.toggle('hidden', event.currentTarget.value !== 'selected');
    });

    container.querySelector('.ai-generate-section-btn')?.addEventListener('click', async (event) => {
        try {
            await generateSectionContent(sectionId, container, event.currentTarget);
        } catch (error) {
            setInlineButtonBusy(event.currentTarget, false);
            renderAiGenerateError(event.currentTarget, error.message || 'Model is unavailable. Generation failed.');
            renderPageEditStatus('Error: ' + error.message, 'error');
        }
    });

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

    container.querySelector('.section-history-btn')?.addEventListener('click', async () => {
        const panel = container.querySelector('.section-history-panel');
        if (!panel) {
            return;
        }

        const willOpen = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !willOpen);

        if (willOpen) {
            try {
                await loadSectionHistory(sectionId, container);
            } catch (error) {
                renderSectionHistoryStatus(container, 'Error: ' + error.message, 'error');
            }
        }
    });

    container.querySelector('.section-history-close-btn')?.addEventListener('click', () => {
        container.querySelector('.section-history-panel')?.classList.add('hidden');
    });
});

document.getElementById('preview-page-btn')?.addEventListener('click', async () => {
    try {
        await openPreview();
    } catch (error) {
        renderPageEditStatus('Error: ' + error.message, 'error');
    }
});

document.getElementById('preview-history-btn')?.addEventListener('click', async () => {
    const panel = document.getElementById('preview-history-panel');
    if (!panel) {
        return;
    }

    const willOpen = panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !willOpen);

    if (willOpen) {
        try {
            await loadPreviewHistory();
        } catch (error) {
            renderPreviewHistoryStatus('Error: ' + error.message, 'error');
        }
    }
});

document.getElementById('preview-history-close-btn')?.addEventListener('click', () => {
    document.getElementById('preview-history-panel')?.classList.add('hidden');
});

document.getElementById('preview-widget-btn')?.addEventListener('click', async () => {
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

@extends('layouts.admin')

@section('title', "Pages - {$site->name}")

@section('content')
<style>
.language-suggestion-code {
    color: #111827;
}

.language-suggestion-name {
    color: #1f2937;
}

.language-suggestion-native {
    color: #4b5563;
}

.dark .language-suggestion-code {
    color: #ffffff;
}

.dark .language-suggestion-name {
    color: #f3f4f6;
}

.dark .language-suggestion-native {
    color: #a7f3d0;
}
</style>
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Pages for {{ $site->name }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage pages and SEO content for this site.</p>
        </div>
        <div class="mt-4 flex items-center gap-3 sm:mt-0">
            <a href="{{ route('admin.sites.index') }}"
               class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                Back To Sites
            </a>
            <a href="{{ route('admin.media.index', $site->id) }}"
               class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                Media
            </a>
            <button type="button" id="open-add-language-modal"
                    class="inline-flex cursor-pointer items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus-visible:outline-none">
                Add Language
            </button>
            <a href="{{ route('admin.pages.create', $site->id) }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create Page
            </a>
        </div>
    </div>

    @forelse($locales as $locale)
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ strtoupper($locale) }} Pages</h3>
            @if($locale !== $defaultLocale)
            <button type="button"
                    class="cursor-pointer rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus-visible:outline-none"
                    onclick="deleteLanguage('{{ $locale }}')">
                Delete {{ strtoupper($locale) }}
            </button>
            @endif
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sections</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse(($pagesByLocale[$locale] ?? collect()) as $page)
                <tr>
                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        @if($page->slug === '')
                            /
                        @elseif($locale === $defaultLocale)
                            /{{ $page->slug }}
                        @else
                            /{{ $locale }}/{{ $page->slug === 'index' ? '' : $page->slug }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                            {{ $page->status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($page->status) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $page->sections->count() }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-3">
                        <a href="{{ route('admin.pages.edit', [$site->id, $page->id]) }}"
                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Edit
                        </a>
                        <button type="button"
                                class="cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus-visible:outline-none"
                                onclick="deletePage({{ $page->id }}, '{{ addslashes($page->title) }}')">
                            Delete
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No pages found for {{ strtoupper($locale) }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @empty
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        No pages found. Create your first page for this site.
    </div>
    @endforelse
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Shared Site Blocks</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">These blocks are rendered on every page of the site.</p>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @foreach($locales as $locale)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Menu {{ strtoupper($locale) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Header navigation and logo from <code>&lt;div class="header__inner"&gt;</code></td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.pages.shared.edit', [$site->id, 'menu', $locale]) }}"
                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Edit
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Mobile Menu {{ strtoupper($locale) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Shared mobile navigation from <code>&lt;div class="mobile-menu"&gt;</code></td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.pages.shared.edit', [$site->id, 'mobile-menu', $locale]) }}"
                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Edit
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">Footer {{ strtoupper($locale) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Shared footer content from <code>&lt;footer class="footer" id="footer"&gt;</code></td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.pages.shared.edit', [$site->id, 'footer', $locale]) }}"
                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Edit
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div id="pages-index-status" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
        <div id="pages-index-status-text"></div>
    </div>
</div>

<div id="add-language-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 px-4">
    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Language</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter an ISO 639-1 code or language name.</p>
            </div>
            <button type="button" id="close-add-language-modal" class="cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">×</button>
        </div>
        <div class="mt-5 space-y-3">
            <label for="add-language-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Language</label>
            <input id="add-language-input" type="text" autocomplete="off"
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                   placeholder="ru, русский, russian">
            <div id="language-suggestions" class="max-h-56 overflow-auto rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"></div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="cancel-add-language"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                Cancel
            </button>
            <button type="button" id="submit-add-language"
                    class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                Add Language
            </button>
        </div>
    </div>
</div>

<script>
const availableLanguages = @json($languageOptions ?? []);
const existingLocales = new Set(@json($locales ?? []));
let selectedLanguageCode = null;

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function openAddLanguageModal() {
    const modal = document.getElementById('add-language-modal');
    const input = document.getElementById('add-language-input');
    selectedLanguageCode = null;
    if (input) {
        input.value = '';
    }
    renderLanguageSuggestions('');
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
    setTimeout(() => input?.focus(), 0);
}

function closeAddLanguageModal() {
    const modal = document.getElementById('add-language-modal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

function normalizeLanguageSearch(value) {
    return String(value || '').trim().toLowerCase();
}

function renderLanguageSuggestions(value) {
    const box = document.getElementById('language-suggestions');
    if (!box) {
        return;
    }

    const query = normalizeLanguageSearch(value);
    const suggestions = availableLanguages
        .filter((language) => !existingLocales.has(language.code))
        .filter((language) => {
            if (query === '') {
                return true;
            }

            return language.code.startsWith(query)
                || normalizeLanguageSearch(language.name_en).startsWith(query)
                || normalizeLanguageSearch(language.name_ru).startsWith(query);
        })
        .slice(0, 12);

    if (suggestions.length === 0) {
        box.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">No matching language.</div>';
        return;
    }

    box.innerHTML = suggestions.map((language) => `
        <button type="button" data-language-code="${language.code}"
                class="language-suggestion block w-full px-3 py-2 text-left text-sm hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none dark:hover:bg-gray-700 dark:focus:bg-gray-700">
            <span class="language-suggestion-code font-semibold uppercase">${language.code}</span>
            <span class="language-suggestion-name ml-2">${language.name_en}</span>
            <span class="language-suggestion-native ml-2">${language.name_ru}</span>
        </button>
    `).join('');
}

function resolveLanguageCode(value) {
    const query = normalizeLanguageSearch(value);
    const direct = availableLanguages.find((language) => language.code === query);
    if (direct) {
        return direct.code;
    }

    const byName = availableLanguages.find((language) =>
        normalizeLanguageSearch(language.name_en) === query
        || normalizeLanguageSearch(language.name_ru) === query
    );

    return byName ? byName.code : selectedLanguageCode;
}

async function addLanguage() {
    const input = document.getElementById('add-language-input');
    const code = resolveLanguageCode(input?.value);

    if (!code) {
        renderPagesIndexStatus('Choose a language from the suggestions or enter a valid ISO 639-1 code.', 'warning');
        return;
    }

    const response = await fetch(`/api/sites/{{ $site->id }}/languages`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ locale: code }),
    });

    const data = await readApiResponse(response);
    if (!response.ok) {
        renderPagesIndexStatus('Error: ' + (data.error || data.message || JSON.stringify(data.errors || {})), 'error');
        return;
    }

    window.location.reload();
}

async function deleteLanguage(locale) {
    const normalized = String(locale || '').toLowerCase();
    if (!normalized || !confirm(`Delete ${normalized.toUpperCase()} pages and shared blocks?`)) {
        return;
    }

    const response = await fetch(`/api/sites/{{ $site->id }}/languages/${encodeURIComponent(normalized)}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const data = await readApiResponse(response);
    if (!response.ok) {
        renderPagesIndexStatus('Error: ' + (data.error || data.message || JSON.stringify(data.errors || {})), 'error');
        return;
    }

    window.location.reload();
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

function renderPagesIndexStatus(message, tone = 'error') {
    const statusBox = document.getElementById('pages-index-status');
    const statusText = document.getElementById('pages-index-status-text');

    if (!statusBox || !statusText) {
        return;
    }

    statusBox.classList.remove(
        'hidden',
        'border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100',
        'border-amber-200', 'bg-amber-50', 'text-amber-900', 'dark:border-amber-800', 'dark:bg-amber-950', 'dark:text-amber-100',
        'border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100'
    );

    statusBox.classList.add(...(tone === 'success'
        ? ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100']
        : (tone === 'warning'
            ? ['border-amber-200', 'bg-amber-50', 'text-amber-900', 'dark:border-amber-800', 'dark:bg-amber-950', 'dark:text-amber-100']
            : ['border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100'])));
    statusText.textContent = message;
}

async function deletePage(pageId, pageTitle) {
    if (!confirm(`Delete page "${pageTitle}"?`)) {
        return;
    }

    try {
        const response = await fetch(`/api/pages/${pageId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await readApiResponse(response);

        if (!response.ok) {
            renderPagesIndexStatus('Error: ' + (data.error || data.message || `Request failed with status ${response.status}`), 'error');
            return;
        }

        if (data.warning) {
            renderPagesIndexStatus(data.warning, 'warning');
        }

        window.location.reload();
    } catch (error) {
        renderPagesIndexStatus('Error: ' + error.message, 'error');
    }
}

document.getElementById('open-add-language-modal')?.addEventListener('click', openAddLanguageModal);
document.getElementById('close-add-language-modal')?.addEventListener('click', closeAddLanguageModal);
document.getElementById('cancel-add-language')?.addEventListener('click', closeAddLanguageModal);
document.getElementById('submit-add-language')?.addEventListener('click', addLanguage);
document.getElementById('add-language-input')?.addEventListener('input', (event) => {
    selectedLanguageCode = null;
    renderLanguageSuggestions(event.target.value);
});
document.getElementById('language-suggestions')?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-language-code]');
    if (!button) {
        return;
    }

    selectedLanguageCode = button.dataset.languageCode || null;
    const language = availableLanguages.find((item) => item.code === selectedLanguageCode);
    const input = document.getElementById('add-language-input');
    if (input && language) {
        input.value = `${language.code} - ${language.name_en} / ${language.name_ru}`;
    }
});
</script>
@endsection

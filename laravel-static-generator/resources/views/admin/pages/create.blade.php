@extends('layouts.admin')

@section('title', "Create Page - {$site->name}")

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Page</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Site: {{ $site->name }}</p>
        </div>
        <a href="{{ route('admin.pages.index', $site->id) }}"
           class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
            Back To Pages
        </a>
    </div>

    <form id="page-form" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Page Settings</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Page Type</label>
                        <select name="template_key" id="template-key-select"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @foreach(($pageTemplates ?? []) as $template)
                            <option value="{{ $template['key'] }}" data-default-slug="{{ $template['default_slug'] }}">
                                {{ $template['label'] }}{{ $template['source_file'] ? " ({$template['source_file']})" : '' }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose an empty or prefilled page preset.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                        <input type="text" name="slug" required placeholder="about-us"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" name="title" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Locale</label>
                        <input type="text" name="locale" value="{{ $site->locale ?? 'en' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
                        <input type="text" name="meta_title"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meta Description</label>
                        <textarea name="meta_description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meta Keywords</label>
                        <textarea name="meta_keywords" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Canonical URL</label>
                        <input type="text" name="canonical"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">OpenGraph Data (JSON)</label>
                        <textarea name="og_data" rows="6" placeholder='{"title":"Title","description":"Description","type":"website"}'
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">JSON-LD (JSON)</label>
                        <textarea name="json_ld" rows="8" placeholder='{"@@context":"https://schema.org","@@type":"WebPage"}'
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pages.index', $site->id) }}"
               class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                Create Page
            </button>
        </div>
        <div id="page-create-status" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
            <div id="page-create-status-text"></div>
        </div>
    </form>
</div>

<script>
const canonicalSiteDomain = @json($site->domain);
let canonicalManuallyEdited = false;

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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

function renderPageCreateStatus(message, tone = 'error') {
    const statusBox = document.getElementById('page-create-status');
    const statusText = document.getElementById('page-create-status-text');

    if (!statusBox || !statusText) {
        return false;
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
    return false;
}

function parseJsonField(value, label) {
    const trimmed = value.trim();
    if (!trimmed) {
        return null;
    }

    try {
        const parsed = JSON.parse(trimmed);
        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            return renderPageCreateStatus(`${label} must be a JSON object.`, 'error');
        }
        return parsed;
    } catch (error) {
        return renderPageCreateStatus(`${label} has invalid JSON syntax.`, 'error');
    }
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

document.getElementById('template-key-select')?.addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const defaultSlug = selected?.dataset?.defaultSlug || '';
    const slugInput = document.querySelector('input[name="slug"]');
    const titleInput = document.querySelector('input[name="title"]');

    if (!slugInput || !titleInput) {
        return;
    }

    if (!slugInput.value.trim() && defaultSlug) {
        slugInput.value = defaultSlug;
        syncCanonicalFromSlug();
    }

    if (!titleInput.value.trim() && defaultSlug) {
        const title = defaultSlug
            .replace(/-/g, ' ')
            .replace(/\b\w/g, (char) => char.toUpperCase());
        titleInput.value = title;
    }
});

document.getElementById('page-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.site_id = {{ (int) $site->id }};

    if (!String(data.canonical || '').trim()) {
        data.canonical = buildCanonicalFromSlug(data.slug || '');
    }

    const ogData = parseJsonField(data.og_data || '', 'OpenGraph Data');
    if (ogData === false) return;

    const jsonLd = parseJsonField(data.json_ld || '', 'JSON-LD');
    if (jsonLd === false) return;

    delete data.og_data;
    delete data.json_ld;

    if (ogData !== null) data.og_data = ogData;
    if (jsonLd !== null) data.json_ld = jsonLd;

    try {
        const response = await fetch('/api/pages', {
            method: 'POST',
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
            renderPageCreateStatus('Error: ' + message, 'error');
            return;
        }

        if (Array.isArray(result.warnings) && result.warnings.length > 0) {
            renderPageCreateStatus('Page created with warnings: ' + result.warnings.join(' | '), 'warning');
        } else {
            renderPageCreateStatus('Page created successfully.', 'success');
        }

        setTimeout(() => {
            window.location.href = '{{ route('admin.pages.index', $site->id) }}';
        }, 1000);
    } catch (error) {
        renderPageCreateStatus('Error: ' + error.message, 'error');
    }
});
</script>
@endsection

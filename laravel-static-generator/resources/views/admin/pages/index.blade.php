@extends('layouts.admin')

@section('title', "Pages - {$site->name}")

@section('content')
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
            <a href="{{ route('admin.pages.create', $site->id) }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create Page
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
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
                @forelse($pages as $page)
                <tr>
                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        @if($page->slug === '')
                            /
                        @else
                            /{{ $page->slug }}
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
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                onclick="deletePage({{ $page->id }}, '{{ addslashes($page->title) }}')">
                            Delete
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No pages found. Create your first page for this site.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
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
            alert('Error: ' + (data.error || data.message || `Request failed with status ${response.status}`));
            return;
        }

        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>
@endsection

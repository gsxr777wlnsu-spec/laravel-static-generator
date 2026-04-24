@extends('layouts.admin')

@section('title', "Media - {$site->name}")

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Site: {{ $site->name }}</p>
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
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Upload Media</h3>

            <form id="upload-media-form" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File</label>
                    <input type="file" name="file" required
                           class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alt (required)</label>
                    <input type="text" name="alt" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input type="text" name="title"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="sm:col-span-4">
                    <button type="submit"
                            class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline-none">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Path</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Alt</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Meta</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($media as $item)
                <tr class="media-row" data-media-id="{{ $item->id }}">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $item->id }}</td>
                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 break-all">{{ $item->path }}</td>
                    <td class="px-6 py-4">
                        <input type="text" value="{{ $item->alt }}" class="media-alt block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm">
                    </td>
                    <td class="px-6 py-4">
                        <input type="text" value="{{ $item->title }}" class="media-title block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm sm:text-sm">
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                        {{ $item->mime_type ?? '-' }}<br>
                        {{ $item->width ?? '-' }}x{{ $item->height ?? '-' }}<br>
                        {{ $item->size ?? '-' }} bytes
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                        <button type="button" class="save-media-btn cursor-pointer text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 focus:outline-none focus-visible:outline-none">
                            Save
                        </button>
                        <button type="button" class="delete-media-btn cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus-visible:outline-none">
                            Delete
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No media files uploaded yet.
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

document.getElementById('upload-media-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();

    const formData = new FormData(this);
    formData.append('site_id', '{{ $site->id }}');

    try {
        const response = await fetch('/api/media', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: formData,
        });

        const result = await readApiResponse(response);

        if (!response.ok) {
            const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
            alert('Error: ' + message);
            return;
        }

        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

async function saveMedia(row) {
    const mediaId = row.dataset.mediaId;
    const alt = row.querySelector('.media-alt')?.value || '';
    const title = row.querySelector('.media-title')?.value || '';

    const response = await fetch(`/api/media/${mediaId}`, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ alt, title }),
    });

    const result = await readApiResponse(response);

    if (!response.ok) {
        const message = result.errors ? JSON.stringify(result.errors) : (result.error || result.message || `Request failed with status ${response.status}`);
        alert('Error: ' + message);
        return;
    }

    alert('Media updated.');
}

async function deleteMedia(row) {
    if (!confirm('Delete this media file?')) {
        return;
    }

    const mediaId = row.dataset.mediaId;
    const response = await fetch(`/api/media/${mediaId}`, {
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

document.querySelectorAll('.media-row').forEach((row) => {
    row.querySelector('.save-media-btn')?.addEventListener('click', async () => {
        try {
            await saveMedia(row);
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    row.querySelector('.delete-media-btn')?.addEventListener('click', async () => {
        try {
            await deleteMedia(row);
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
});
</script>
@endsection

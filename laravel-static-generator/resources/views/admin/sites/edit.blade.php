@extends('layouts.admin')

@section('title', 'Edit Site')

@section('content')
<form id="site-edit-form" class="space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Site</h3>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Update site configuration</p>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" name="name" required value="{{ $site->name }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Domain</label>
                    <input type="text" name="domain" required value="{{ $site->domain }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Set</label>
                    <input type="text" name="template_set" required value="{{ $site->template_set }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Output Path</label>
                    <input type="text" name="output_path" required value="{{ $site->output_path }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="draft" {{ $site->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ $site->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $site->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Locale</label>
                    <input type="text" name="locale" value="{{ $site->locale }}"
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
                    <input type="text" name="sftp_host" value="{{ $site->sftp_host }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SFTP Port</label>
                    <input type="number" name="sftp_port" value="{{ $site->sftp_port ?? 22 }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" name="sftp_username" value="{{ $site->sftp_username }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auth Method</label>
                    <select name="sftp_auth_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="key" {{ $site->sftp_auth_method === 'key' ? 'selected' : '' }}>SSH Key</option>
                        <option value="password" {{ $site->sftp_auth_method === 'password' ? 'selected' : '' }}>Password</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" name="sftp_password" placeholder="Leave blank to keep current value"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remote Path</label>
                    <input type="text" name="sftp_remote_path" value="{{ $site->sftp_remote_path }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-3">
        <a href="/admin/sites" class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
            Cancel
        </a>
        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            Save Changes
        </button>
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

document.getElementById('site-edit-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    if (data.sftp_port) {
        data.sftp_port = parseInt(data.sftp_port, 10);
    }

    if (!data.sftp_password) {
        delete data.sftp_password;
    }

    try {
        const response = await fetch('/api/sites/{{ $site->id }}', {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        });

        const result = await readApiResponse(response);

        if (!response.ok) {
            const errorMessage = result?.errors
                ? JSON.stringify(result.errors)
                : (result?.error || result?.message || `Request failed with status ${response.status}`);

            alert('Error: ' + errorMessage);
            return;
        }

        alert('Site updated successfully!');
        window.location.href = '/admin/sites';
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
@endsection

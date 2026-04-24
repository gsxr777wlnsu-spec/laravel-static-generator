@extends('layouts.admin')

@section('title', 'Sites')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Sites</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all your static sites</p>
        </div>
<div class="mt-4 sm:ml-16 sm:mt-0 sm:flex sm:items-center gap-3">
            <label style="background-color: #6b21a8;" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 cursor-pointer">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import & Deploy
                <input type="file" id="import-deploy-file" accept=".md,.yaml,.yml,.txt" class="hidden" onchange="handleImportAndDeployFile(this)">
            </label>
            <label style="background-color: #059669;" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 cursor-pointer">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import
                <input type="file" id="import-file" accept=".md,.yaml,.yml,.txt" class="hidden" onchange="handleImportFile(this)">
            </label>
            <a href="{{ route('admin.sites.create') }}" style="background-color: #4f46e5;" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create Site
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Template</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($sites as $site)
                <tr>
                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $site->name }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $site->domain }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                            {{ $site->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($site->status) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $site->template_set }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                        <a href="{{ route('admin.pages.index', $site->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Pages</a>
                        <a href="{{ route('admin.media.index', $site->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Media</a>
                        <a href="{{ route('admin.sites.edit', $site->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</a>
                        <button onclick="generateSite({{ $site->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">Generate</button>
                        <button onclick="deleteSite({{ $site->id }}, '{{ addslashes($site->name) }}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                        <button onclick="deploySite({{ $site->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">Deploy</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No sites found. <a href="{{ route('admin.sites.create') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Create your first site</a>
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

async function generateSite(siteId) {
    if (!confirm('Generate HTML for this site?')) return;
    
    try {
        const response = await fetch(`/api/sites/${siteId}/generate`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const data = await readApiResponse(response);

        if (!response.ok) {
            alert('Generation failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        if (response.status === 202) {
            alert(data.message || 'Generation was queued.');
            return;
        }
        
        if (data.success) {
            alert(`Generated ${data.files_count} files successfully!`);
        } else {
            alert('Generation failed. Check console for errors.');
            console.error(data.errors);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deploySite(siteId) {
    if (!confirm('Deploy this site to production?')) return;
    
    try {
        const response = await fetch(`/api/sites/${siteId}/deploy`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const data = await readApiResponse(response);

        if (!response.ok) {
            alert('Deployment failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        if (response.status === 202) {
            alert(data.message || 'Deployment was queued.');
            return;
        }
        
        if (data.status === 'completed') {
            alert('Deployment completed successfully!');
        } else {
            alert('Deployment failed: ' + (data.error_message || 'Unknown error'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deleteSite(siteId, siteName) {
    if (!confirm(`Delete site "${siteName}"? This action cannot be undone.`)) return;
    
    try {
        const response = await fetch(`/api/sites/${siteId}`, {
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
            alert('Delete failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function handleImportFile(input) {
    const file = input.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch('/api/import', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: formData,
        });

        const data = await readApiResponse(response);

        if (!response.ok) {
            alert('Import failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        alert(`Import successful!\n\nImported ${data.pages_count} page(s) for site: ${data.site.domain}\n\nSite ID: ${data.site.id}`);

        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }

    input.value = '';
}

async function handleImportAndDeployFile(input) {
    const file = input.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch('/api/import/deploy', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: formData,
        });

        const data = await readApiResponse(response);

        if (!response.ok) {
            alert('Import & Deploy failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        alert(`Import & Deploy completed!\n\nImported ${data.pages_count} page(s)\nDeployed to: ${data.deployment.sftp_host}${data.deployment.remote_path}`);
        
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }

    input.value = '';
}

async function importAndDeploy(siteId) {
    if (!confirm('Import & Deploy: This will import contact-us.md and deploy to remote server. Continue?')) return;
    
    try {
        const response = await fetch(`/api/sites/${siteId}/import-and-deploy`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await readApiResponse(response);

        if (!response.ok) {
            alert('Import & Deploy failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        if (data.success) {
            alert(`Import & Deploy completed successfully!\n\n${data.message || ''}`);
        } else {
            alert('Import & Deploy failed: ' + (data.error || 'Unknown error'));
        }
        
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function promptImportAndDeploy() {
    const sites = @json($sites->pluck('domain', 'id'));
    const siteList = Object.entries(sites).map(([id, domain]) => `${id}: ${domain}`).join('\n');
    
    const siteId = prompt('Enter site ID for Import & Deploy:\n\nAvailable sites:\n' + siteList);
    
    if (siteId && sites[siteId]) {
        importAndDeploy(siteId);
    } else if (siteId) {
        alert('Site not found with ID: ' + siteId);
    }
}

async function importAndDeploy(siteId) {
    if (!confirm('Import & Deploy: This will import contact-us.md and deploy to remote server. Continue?')) return;
    
    try {
        const response = await fetch(`/api/sites/${siteId}/import-and-deploy`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await readApiResponse(response);

        if (!response.ok) {
            alert('Import & Deploy failed: ' + (data.error || data.message || `HTTP ${response.status}`));
            return;
        }

        if (data.success) {
            alert(`Import & Deploy completed successfully!\n\n${data.message || ''}`);
        } else {
            alert('Import & Deploy failed: ' + (data.error || 'Unknown error'));
        }
        
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>
@endsection

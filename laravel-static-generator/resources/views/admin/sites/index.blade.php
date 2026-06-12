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
                <input type="file" id="import-deploy-file" accept=".md,.yaml,.yml,.txt" class="hidden" multiple onchange="handleImportAndDeployFile(this)">
            </label>
            <label style="background-color: #059669;" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 cursor-pointer">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import
                <input type="file" id="import-file" accept=".md,.yaml,.yml,.txt" class="hidden" multiple onchange="handleImportFile(this)">
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
                        <button type="button" onclick="generateSite({{ $site->id }})" class="cursor-pointer text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 focus:outline-none focus-visible:outline-none">Generate</button>
                        <button type="button" onclick="deleteSite({{ $site->id }}, '{{ addslashes($site->name) }}')" class="cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 focus:outline-none focus-visible:outline-none">Delete</button>
                        <button type="button" onclick="deploySite({{ $site->id }})" class="cursor-pointer text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 focus:outline-none focus-visible:outline-none">Deploy</button>
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
    <div id="sites-index-status" class="hidden rounded-md border px-4 py-3 text-sm shadow-sm">
        <div id="sites-index-status-text" class="whitespace-pre-wrap"></div>
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

function renderSitesIndexStatus(message, tone = 'error') {
    const statusBox = document.getElementById('sites-index-status');
    const statusText = document.getElementById('sites-index-status-text');

    if (!statusBox || !statusText) {
        return;
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
            renderSitesIndexStatus('Generation failed: ' + (data.error || data.message || `HTTP ${response.status}`), 'error');
            return;
        }

        if (response.status === 202) {
            renderSitesIndexStatus(data.message || 'Generation was queued.', 'info');
            return;
        }
        
        if (data.success) {
            renderSitesIndexStatus(`Generated ${data.files_count} files successfully!`, 'success');
        } else {
            renderSitesIndexStatus('Generation failed. Check console for errors.', 'error');
            console.error(data.errors);
        }
    } catch (error) {
        renderSitesIndexStatus('Error: ' + error.message, 'error');
    }
}

async function deploySite(siteId) {
    if (!confirm('Deploy this site to production?')) return;
    
    try {
        const response = await fetch(`/api/sites/${siteId}/deploy`, {
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
        const data = await readApiResponse(response);

        if (!response.ok) {
            renderSitesIndexStatus('Deployment failed: ' + (data.error || data.message || `HTTP ${response.status}`), 'error');
            return;
        }

        if (response.status === 202) {
            renderSitesIndexStatus(data.message || 'Deployment was queued.', 'info');
            return;
        }
        
        if (data.status === 'completed') {
            renderSitesIndexStatus('Deployment completed successfully!', 'success');
        } else {
            renderSitesIndexStatus('Deployment failed: ' + (data.error_message || 'Unknown error'), 'error');
        }
    } catch (error) {
        renderSitesIndexStatus('Error: ' + error.message, 'error');
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
            renderSitesIndexStatus('Delete failed: ' + (data.error || data.message || `HTTP ${response.status}`), 'error');
            return;
        }

        if (Array.isArray(data.cleanup_warnings) && data.cleanup_warnings.length > 0) {
            const lines = data.cleanup_warnings.map((issue) => {
                const scope = issue?.scope ? `[${issue.scope}] ` : '';
                const resource = issue?.resource ? `${issue.resource}: ` : '';
                const path = issue?.path ? `${issue.path} - ` : '';
                const message = issue?.message || 'Cleanup warning';
                return `- ${scope}${resource}${path}${message}`;
            });
            renderSitesIndexStatus((data.message || 'Site deleted with cleanup warnings') + '\n\n' + lines.join('\n'), 'warning');
        }
        if (!Array.isArray(data.cleanup_warnings) || data.cleanup_warnings.length === 0) {
            renderSitesIndexStatus(data.message || 'Site deleted successfully. Reloading…', 'success');
        }
        setTimeout(() => window.location.reload(), 1000);
    } catch (error) {
        renderSitesIndexStatus('Error: ' + error.message, 'error');
    }
}

async function handleImportFile(input) {
    await processImportBatch(input, {
        endpoint: '/api/import',
        actionTitle: 'Import',
        showDeploymentInfo: false,
    });
}

async function handleImportAndDeployFile(input) {
    await processImportBatch(input, {
        endpoint: '/api/import/deploy',
        actionTitle: 'Import & Deploy',
        showDeploymentInfo: true,
    });
}

let importBatchInProgress = false;

async function processImportBatch(input, options) {
    const files = Array.from(input.files || []);
    if (!files.length) {
        return;
    }

    if (importBatchInProgress) {
        renderSitesIndexStatus('Another import batch is already running. Please wait for it to finish.', 'warning');
        input.value = '';
        return;
    }

    const invalidFiles = files.filter((file) => !/\.(md|yaml|yml|txt)$/i.test(file.name));
    if (invalidFiles.length > 0) {
        renderSitesIndexStatus(`Unsupported file type(s):\n${invalidFiles.map((file) => `- ${file.name}`).join('\n')}`, 'error');
        input.value = '';
        return;
    }

    const runConfirmed = confirm(`${options.actionTitle}: process ${files.length} file(s) sequentially?`);
    if (!runConfirmed) {
        input.value = '';
        return;
    }

    importBatchInProgress = true;
    const results = [];

    try {
        for (let index = 0; index < files.length; index++) {
            const file = files[index];
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(options.endpoint, {
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
                    const message = data.error || data.message || `HTTP ${response.status}`;
                    results.push({ file: file.name, ok: false, message });
                    continue;
                }

                const pagesCount = data.pages_count ?? 0;
                const siteDomain = data.site?.domain || `site_id:${data.site?.id ?? 'unknown'}`;
                let message = `OK: ${pagesCount} page(s), ${siteDomain}`;

                if (options.showDeploymentInfo && data.deployment?.sftp_host) {
                    message += `, deployed to ${data.deployment.sftp_host}${data.deployment.remote_path || ''}`;
                }

                results.push({ file: file.name, ok: true, message });
            } catch (error) {
                results.push({ file: file.name, ok: false, message: error.message });
            }
        }

        const successCount = results.filter((item) => item.ok).length;
        const failCount = results.length - successCount;
        const summaryLines = results.map((item) => `${item.ok ? '[OK]' : '[FAIL]'} ${item.file}: ${item.message}`);

        renderSitesIndexStatus(
            `${options.actionTitle} batch finished.\n` +
            `Success: ${successCount}\n` +
            `Failed: ${failCount}\n\n` +
            summaryLines.join('\n'),
            failCount > 0 ? 'warning' : 'success'
        );

        if (successCount > 0) {
            setTimeout(() => window.location.reload(), 1200);
        }
    } finally {
        importBatchInProgress = false;
        input.value = '';
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
            renderSitesIndexStatus('Import & Deploy failed: ' + (data.error || data.message || `HTTP ${response.status}`), 'error');
            return;
        }

        if (data.success) {
            renderSitesIndexStatus(`Import & Deploy completed successfully!\n\n${data.message || ''}`, 'success');
        } else {
            renderSitesIndexStatus('Import & Deploy failed: ' + (data.error || 'Unknown error'), 'error');
        }
        
        setTimeout(() => window.location.reload(), 1200);
    } catch (error) {
        renderSitesIndexStatus('Error: ' + error.message, 'error');
    }
}

function promptImportAndDeploy() {
    const sites = @json($sites->pluck('domain', 'id'));
    const siteList = Object.entries(sites).map(([id, domain]) => `${id}: ${domain}`).join('\n');
    
    const siteId = prompt('Enter site ID for Import & Deploy:\n\nAvailable sites:\n' + siteList);
    
    if (siteId && sites[siteId]) {
        importAndDeploy(siteId);
    } else if (siteId) {
        renderSitesIndexStatus('Site not found with ID: ' + siteId, 'error');
    }
}
</script>
@endsection

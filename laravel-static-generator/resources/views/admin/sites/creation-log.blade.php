@extends('layouts.admin')

@section('title', 'Site Creation Log')

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Site Creation Log</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $site->domain }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button id="copy-site-create-log" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:text-white dark:hover:bg-indigo-400">
                        Copy Report
                    </button>
                    <a href="/admin/sites/{{ $site->id }}/edit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Back to Edit
                    </a>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                Saved file: {{ $reportPath }}
            </div>

            <pre id="site-create-log-body" class="mt-4 overflow-x-auto whitespace-pre-wrap rounded-md bg-gray-50 px-4 py-3 text-xs leading-6 text-gray-900 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-white dark:ring-gray-700">{{ $reportText }}</pre>
            <div id="site-create-log-status" class="mt-4 hidden rounded-md border px-4 py-3 text-sm shadow-sm">
                <div id="site-create-log-status-text"></div>
            </div>
        </div>
    </div>
</div>

<script>
function renderSiteCreateLogStatus(message, tone = 'error') {
    const statusBox = document.getElementById('site-create-log-status');
    const statusText = document.getElementById('site-create-log-status-text');

    if (!statusBox || !statusText) {
        return;
    }

    statusBox.classList.remove(
        'hidden',
        'border-emerald-200',
        'bg-emerald-50',
        'text-emerald-900',
        'dark:border-emerald-800',
        'dark:bg-emerald-950',
        'dark:text-emerald-100',
        'border-rose-200',
        'bg-rose-50',
        'text-rose-900',
        'dark:border-rose-800',
        'dark:bg-rose-950',
        'dark:text-rose-100'
    );

    const toneClasses = tone === 'success'
        ? ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900', 'dark:border-emerald-800', 'dark:bg-emerald-950', 'dark:text-emerald-100']
        : ['border-rose-200', 'bg-rose-50', 'text-rose-900', 'dark:border-rose-800', 'dark:bg-rose-950', 'dark:text-rose-100'];

    statusBox.classList.add(...toneClasses);
    statusText.textContent = message;
}

document.getElementById('copy-site-create-log')?.addEventListener('click', async function () {
    const reportBody = document.getElementById('site-create-log-body');
    const text = reportBody?.textContent || '';
    if (text === '') {
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        this.textContent = 'Copied';
        renderSiteCreateLogStatus('Report copied to clipboard.', 'success');
        setTimeout(() => {
            this.textContent = 'Copy Report';
        }, 1500);
    } catch (error) {
        renderSiteCreateLogStatus('Copy failed: ' + (error?.message || String(error)), 'error');
    }
});
</script>
@endsection

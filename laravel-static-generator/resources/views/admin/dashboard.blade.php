@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Overview of your static sites</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Sites</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $sites->count() }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Active Sites</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $sites->where('status', 'active')->count() }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Recent Deployments</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $recentDeployments->count() }}</dd>
        </div>
    </div>

    <!-- Sites List -->
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Sites</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your static sites</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0">
                    <a href="{{ route('admin.sites.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Site
                    </a>
                </div>
            </div>
            
            @if($sites->isEmpty())
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No sites yet. Create your first site to get started.</p>
                </div>
            @else
                <div class="mt-6 flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sites as $site)
                        <li class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $site->name }}</p>
                                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $site->domain }}</p>
                                </div>
                                <div>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                                        {{ $site->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ ucfirst($site->status) }}
                                    </span>
                                </div>
                                <div>
                                    <a href="{{ route('admin.sites.edit', $site->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

<!DOCTYPE html>
<html lang="en" class="h-full{{ request()->cookie('color-theme') === 'dark' ? ' dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - Laravel Static Generator</title>
    <script>
    (function() {
        var cookieName = 'color-theme';

        function getCookie(name) {
            var escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));
            return match ? decodeURIComponent(match[1]) : null;
        }

        function setCookie(name, value) {
            var securePart = window.location.protocol === 'https:' ? '; secure' : '';
            document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; samesite=lax' + securePart;
        }

        var cookieTheme = getCookie(cookieName);
        var storageTheme = null;

        try {
            storageTheme = window.localStorage.getItem(cookieName);
        } catch (error) {}

        var theme = null;
        if (cookieTheme === 'dark' || cookieTheme === 'light') {
            theme = cookieTheme;
        } else if (storageTheme === 'dark' || storageTheme === 'light') {
            theme = storageTheme;
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            theme = 'dark';
        } else {
            theme = 'light';
        }

        document.documentElement.classList.toggle('dark', theme === 'dark');
        if (cookieTheme !== theme) {
            setCookie(cookieName, theme);
        }

        try {
            window.localStorage.setItem(cookieName, theme);
        } catch (error) {}
    })();
    </script>
    @php
        $adminViteManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $adminCssFile = $adminViteManifest['resources/css/app.css']['file'] ?? null;
    @endphp
    @if($adminCssFile)
        <link rel="stylesheet" href="/build/{{ $adminCssFile }}">
    @endif
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Static Generator</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('admin.dashboard') }}" 
                               class="inline-flex items-center border-b-2 {{ request()->routeIs('admin.dashboard') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }} px-1 pt-1 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.sites.index') }}" 
                               class="inline-flex items-center border-b-2 {{ request()->routeIs('admin.sites.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }} px-1 pt-1 text-sm font-medium">
                                Sites
                            </a>
                            <a href="{{ route('admin.ai-agent.edit') }}"
                               class="inline-flex items-center border-b-2 {{ request()->routeIs('admin.ai-agent.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }} px-1 pt-1 text-sm font-medium">
                                AI Agent
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button id="theme-toggle" class="cursor-pointer rounded-lg p-2.5 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus-visible:outline-none dark:text-gray-400 dark:hover:bg-gray-700">
                            <svg id="theme-toggle-dark-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                            <svg id="theme-toggle-light-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                        <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                        <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                @endif

@yield('content')
            </div>
        </main>
    </div>

    <script>
    (function() {
        var cookieName = 'color-theme';
        var toggle = document.getElementById('theme-toggle');
        var darkIcon = document.getElementById('theme-toggle-dark-icon');
        var lightIcon = document.getElementById('theme-toggle-light-icon');
        
        if (!toggle || !darkIcon || !lightIcon) return;

        function persistTheme(theme) {
            var securePart = window.location.protocol === 'https:' ? '; secure' : '';
            document.cookie = cookieName + '=' + encodeURIComponent(theme) + '; path=/; max-age=31536000; samesite=lax' + securePart;

            try {
                window.localStorage.setItem(cookieName, theme);
            } catch (error) {}
        }
        
        function updateIcons() {
            if (document.documentElement.classList.contains('dark')) {
                darkIcon.classList.add('hidden');
                lightIcon.classList.remove('hidden');
            } else {
                darkIcon.classList.remove('hidden');
                lightIcon.classList.add('hidden');
            }
        }
        
        updateIcons();
        persistTheme(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        
        toggle.addEventListener('click', function() {
            var isDark = document.documentElement.classList.toggle('dark');
            persistTheme(isDark ? 'dark' : 'light');
            updateIcons();
        });
    })();
    </script>
</body>
</html>

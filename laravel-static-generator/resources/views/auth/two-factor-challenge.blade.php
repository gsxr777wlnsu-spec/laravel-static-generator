<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication - Static Generator Admin</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="h-full flex items-center justify-center font-sans antialiased bg-slate-50">

<div class="max-w-md w-full mx-auto pb-12">
    <div class="text-center mb-10">
        <h2 class="mt-6 text-3xl font-extrabold text-slate-900 drop-shadow-sm">Two-Factor Auth</h2>
        <p class="mt-2 text-sm text-slate-600">Please confirm access to your account by entering the authentication code provided by your authenticator application.</p>
    </div>

    <div class="bg-white py-8 px-10 shadow-xl rounded-2xl border border-slate-100 backdrop-blur-sm relative overflow-hidden" x-data="{ recovery: false }">
        <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-indigo-50 opacity-50 blur-2xl pointer-events-none"></div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-6 relative z-10">
            @csrf

            <div x-show="!recovery">
                <label for="code" class="block text-sm font-medium text-slate-700">Code</label>
                <div class="mt-2">
                    <input id="code" name="code" type="text" inputmode="numeric" autofocus autocomplete="one-time-code"
                           class="appearance-none block w-full px-4 py-3 border border-slate-200 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-lg tracking-widest text-center transition-all duration-200 bg-slate-50/50 hover:bg-white focus:bg-white text-slate-800">
                </div>
                @error('code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="recovery" style="display: none;">
                <label for="recovery_code" class="block text-sm font-medium text-slate-700">Recovery Code</label>
                <div class="mt-2">
                    <input id="recovery_code" name="recovery_code" type="text" autocomplete="recovery-code"
                           class="appearance-none block w-full px-4 py-3 border border-slate-200 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all duration-200 bg-slate-50/50 hover:bg-white focus:bg-white text-slate-800">
                </div>
                @error('recovery_code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end mt-4">
                <button type="button" class="text-sm text-slate-600 hover:text-indigo-600 cursor-pointer underline mr-4"
                        x-show="!recovery"
                        x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    Use a recovery code
                </button>

                <button type="button" class="text-sm text-slate-600 hover:text-indigo-600 cursor-pointer underline mr-4"
                        x-show="recovery" style="display: none;"
                        x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    Use an authentication code
                </button>

                <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                    Log in
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Simple inline script to handle toggle without needing full Alpine.js if not installed -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const recoveryBtns = document.querySelectorAll('button[type="button"]');
        const codeDiv = document.querySelector('div[x-show="!recovery"]');
        const recoveryDiv = document.querySelector('div[x-show="recovery"]');
        const codeInput = document.getElementById('code');
        const recoveryInput = document.getElementById('recovery_code');

        let isRecovery = false;

        recoveryBtns[0].addEventListener('click', function() {
            isRecovery = true;
            codeDiv.style.display = 'none';
            recoveryDiv.style.display = 'block';
            recoveryBtns[0].style.display = 'none';
            recoveryBtns[1].style.display = 'inline';
            recoveryInput.focus();
            codeInput.value = ''; // clear
        });

        recoveryBtns[1].addEventListener('click', function() {
            isRecovery = false;
            codeDiv.style.display = 'block';
            recoveryDiv.style.display = 'none';
            recoveryBtns[0].style.display = 'inline';
            recoveryBtns[1].style.display = 'none';
            codeInput.focus();
            recoveryInput.value = ''; // clear
        });
    });
</script>

</body>
</html>

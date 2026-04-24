<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Static Generator Admin</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="h-full flex items-center justify-center font-sans antialiased bg-slate-50">

<div class="max-w-md w-full mx-auto pb-12">
    <div class="text-center mb-10">
        <h2 class="mt-6 text-3xl font-extrabold text-slate-900 drop-shadow-sm">Sign in to your account</h2>
        <p class="mt-2 text-sm text-slate-600">Static Site Generator Administrator</p>
    </div>

    <div class="bg-white py-8 px-10 shadow-xl rounded-2xl border border-slate-100 backdrop-blur-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-indigo-50 opacity-50 blur-2xl pointer-events-none"></div>

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-indigo-600">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-6 relative z-10">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <div class="mt-2">
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                           class="appearance-none block w-full px-4 py-3 border border-slate-200 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all duration-200 bg-slate-50/50 hover:bg-white focus:bg-white text-slate-800">
                </div>
                @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <div class="mt-2">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="appearance-none block w-full px-4 py-3 border border-slate-200 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all duration-200 bg-slate-50/50 hover:bg-white focus:bg-white text-slate-800">
                </div>
                @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember" type="checkbox"
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded cursor-pointer transition-colors">
                    <label for="remember_me" class="ml-2 block text-sm text-slate-700 cursor-pointer">
                        Remember me
                    </label>
                </div>

                @if (Route::has('password.request'))
                    <div class="text-sm">
                        <a href="{{ route('password.request') }}" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                            Forgot your password?
                        </a>
                    </div>
                @endif
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md shadow-indigo-500/20 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                    Sign in
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>

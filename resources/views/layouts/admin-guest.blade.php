<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Login' }} - {{ config('app.name', 'Homework') }}</title>
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-slate-900">
    <div class="min-h-screen flex">
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-green-darker via-brand-green to-brand-green-light p-12 text-white flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-8">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 font-bold text-xl">A</span>
                    <div>
                        <p class="text-xl font-bold">Admin Back Panel</p>
                        <p class="text-white/80 text-sm">Secure management area</p>
                    </div>
                </div>
                <h1 class="text-4xl font-bold leading-tight mb-4">Manage your homework platform</h1>
                <p class="text-white/80 text-lg max-w-md">Login to access dashboard, update your password, and manage your account securely.</p>
            </div>
            <p class="text-sm text-white/60">&copy; {{ date('Y') }} {{ config('app.name', 'Homework') }}</p>
        </div>

        <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-slate-50">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-8 text-center">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-green text-white font-bold text-xl">A</span>
                    <p class="mt-3 text-lg font-semibold text-slate-900">Admin Back Panel</p>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-8">
                    {{ $slot }}
                </div>

                <p class="text-center mt-6 text-sm text-slate-500">
                    <a href="{{ route('home') }}" class="text-brand-green hover:text-brand-green-dark font-medium">&larr; Back to login</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

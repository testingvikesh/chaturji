<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Home' }} - {{ $settings['site_name'] ?? config('app.name', 'Homework') }}</title>
    <meta name="description" content="{{ $settings['site_tagline'] ?? config('app.name', 'Homework') }}">
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800 flex flex-col min-h-screen">
    @include('layouts.partials.front-header')

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('layouts.partials.front-footer')
</body>
</html>

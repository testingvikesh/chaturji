<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} - {{ $settings['site_name'] ?? config('app.name', 'Gses Chaturji') }}</title>
    <meta name="description" content="{{ $settings['site_tagline'] ?? 'Gujarat School of Excellence System' }}">
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body.auth-direct-body { font-family: 'Outfit', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="auth-direct-body antialiased text-slate-800">
    {{ $slot }}
</body>
</html>

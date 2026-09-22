<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Student Dashboard' }} - {{ config('app.name', 'Homework') }}</title>
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body.font-sans { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="admin-shell min-h-screen lg:pl-64">
        @include('layouts.partials.student-sidebar')
        @include('layouts.partials.top-right-logout')

        <div class="lg:pt-0 pt-14">
            @if (isset($header))
                <header class="admin-header">
                    <div class="px-4 sm:px-6 lg:px-8 py-5 pr-16">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    @include('layouts.partials.page-nav-loader')
    @include('layouts.partials.disable-context-menu')
</body>
</html>

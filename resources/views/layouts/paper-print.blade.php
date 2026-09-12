<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? 'Question Paper' }} - {{ config('app.name', 'Homework') }}</title>
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
            .print-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            /* Flow continuously — do not push whole cards to next page */
            .admin-card {
                break-inside: auto !important;
                page-break-inside: auto !important;
                box-shadow: none !important;
                overflow: visible !important;
            }
            .admin-card-top,
            .admin-card-header,
            .material-question-group-header {
                break-after: avoid-page;
                page-break-after: avoid;
            }
            .material-question {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .print-page > * + * {
                margin-top: 0.5rem !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="no-print fixed top-0 inset-x-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur px-4 py-3 shadow-sm">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Question Paper</p>
                <p class="truncate text-sm font-bold text-slate-900">{{ $documentTitle ?? 'Question Paper' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="window.print()"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-green px-4 text-sm font-semibold text-white hover:bg-brand-green-dark">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    </svg>
                    Download PDF
                </button>
                <button type="button" onclick="window.close()"
                        class="inline-flex h-10 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div class="print-page mx-auto max-w-5xl pt-20">
        {{ $slot }}
    </div>
</body>
</html>

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
        .pdf-sheet {
            background: #fff;
            color: #111;
            font-family: "Times New Roman", Times, serif;
        }
        .pdf-sheet .print-page {
            max-width: 820px;
            background: #fff;
        }
        .pdf-sheet .admin-card,
        .pdf-sheet .admin-card-header {
            background: #fff !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }
        .pdf-sheet .admin-card-top,
        .pdf-sheet .pointer-events-none,
        .pdf-sheet .material-question-group-header .text-lg,
        .pdf-sheet .bg-gradient-to-br svg,
        .pdf-sheet .grid > div > span:first-child,
        .pdf-sheet .border-amber-100 .mb-2 {
            display: none !important;
        }
        .pdf-sheet .bg-gradient-to-br {
            background: #fff !important;
            color: #111 !important;
            padding: 0 0 8px !important;
        }
        .pdf-sheet .bg-gradient-to-br > .relative {
            display: flex !important;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 4px;
        }
        .pdf-sheet .bg-gradient-to-br img {
            height: 72px;
            width: 72px;
            margin: 0 auto;
        }
        .pdf-sheet .bg-gradient-to-br h2,
        .pdf-sheet .bg-gradient-to-br p,
        .pdf-sheet .text-white\/70,
        .pdf-sheet .text-white\/90 {
            color: #111 !important;
            text-align: center;
        }
        .pdf-sheet .bg-gradient-to-br h2 {
            font-size: 26px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .pdf-sheet .bg-gradient-to-br > .relative > div:last-child {
            width: 100%;
            display: flex !important;
            justify-content: space-between;
            margin-top: 8px;
            padding: 6px 0;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
        }
        .pdf-sheet .bg-gradient-to-br span {
            background: transparent !important;
            color: #111 !important;
            box-shadow: none !important;
            border: none !important;
            font-size: 15px !important;
        }
        .pdf-sheet .grid {
            display: flex !important;
            flex-wrap: wrap;
            gap: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            border-left: 1px solid #111;
            border-bottom: 1px solid #111;
        }
        .pdf-sheet .grid > div {
            flex: 1 1 180px;
            border-radius: 0 !important;
            border: none !important;
            border-top: 1px solid #111;
            border-right: 1px solid #111;
            box-shadow: none !important;
            background: #fff !important;
            padding: 6px 8px !important;
        }
        .pdf-sheet .border-amber-100,
        .pdf-sheet .bg-amber-50,
        .pdf-sheet .from-amber-50\/70 {
            background: #fff !important;
            border-color: #111 !important;
            color: #111 !important;
        }
        .pdf-sheet .border-amber-100 ol li {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            border-radius: 0 !important;
            padding: 2px 0 !important;
        }
        .pdf-sheet .border-amber-100 ol li > span {
            background: transparent !important;
            color: #111 !important;
            width: auto !important;
            height: auto !important;
            border-radius: 0 !important;
            font-size: 14px !important;
        }
        .pdf-sheet .material-question-group-header {
            background: #fff !important;
            border: none !important;
            border-bottom: 1.5px solid #111;
            border-radius: 0 !important;
            padding: 10px 0 4px !important;
            margin-top: 12px;
        }
        .pdf-sheet .material-question-group-header h3 {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .pdf-sheet .admin-badge-slate,
        .pdf-sheet .admin-badge-gold {
            background: transparent !important;
            color: #111 !important;
            border: none !important;
            font-weight: 700;
        }
        .pdf-sheet .material-question {
            break-inside: avoid;
            page-break-inside: avoid;
            padding: 8px 0 !important;
            border: none !important;
        }
        .pdf-sheet .material-question .rounded-lg,
        .pdf-sheet .material-question .rounded-full {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        .pdf-sheet .divide-y > :not([hidden]) ~ :not([hidden]) {
            border-color: transparent !important;
        }
        @page {
            size: A4;
            margin: 12mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
            .print-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            .admin-card {
                break-inside: auto !important;
                page-break-inside: auto !important;
                box-shadow: none !important;
                overflow: visible !important;
            }
            .material-question-group-header {
                break-after: avoid-page;
                page-break-after: avoid;
            }
            .material-question {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="pdf-sheet bg-white font-serif text-slate-900 antialiased">
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v6H6v-6z"/>
                    </svg>
                    Print
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

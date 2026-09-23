@php
    $catalogItem = collect(\App\Support\AdminReportCatalog::navItems())
        ->first(fn (array $item) => request()->routeIs(...$item['match']));
    $printTitle = $printTitle ?? ($catalogItem['title'] ?? 'Report');
    $printFilters = collect(request()->query())
        ->except(['page'])
        ->filter(fn ($value) => $value !== null && $value !== '' && $value !== 'all')
        ->map(function ($value, $key) {
            $label = str_replace('_', ' ', ucfirst((string) $key));
            $text = is_array($value) ? implode(', ', $value) : (string) $value;

            return $label.': '.$text;
        })
        ->values()
        ->all();
@endphp

@once
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm 8mm; }
            html, body { background: #fff !important; color: #0f172a !important; }
            .admin-shell { padding-left: 0 !important; min-height: 0 !important; }
            aside,
            .lg\:hidden,
            .print\:hidden,
            .report-print-hide,
            header,
            .admin-header,
            .admin-filter-grid,
            form[method="get"],
            form[method="GET"],
            .admin-pagination,
            nav[role="navigation"],
            .admin-btn-primary,
            .admin-btn-secondary,
            .admin-btn-ghost,
            .admin-btn-filter,
            .admin-card-top,
            .admin-section-label { display: none !important; }

            [x-cloak] { display: block !important; }

            .admin-header {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }
            .admin-page-title { font-size: 18px !important; }
            .admin-page-subtitle { font-size: 11px !important; color: #475569 !important; }

            main { padding: 0 !important; }
            .admin-card { box-shadow: none !important; border-color: #cbd5e1 !important; break-inside: avoid; }
            .admin-table-wrap,
            .overflow-x-auto { overflow: visible !important; }

            .admin-table,
            table.min-w-full {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            .admin-table th,
            .admin-table td,
            table.min-w-full th,
            table.min-w-full td {
                border: 1px solid #cbd5e1 !important;
                padding: 5px 7px !important;
                font-size: 11px !important;
                text-align: left !important;
                color: #0f172a !important;
            }
            .admin-table th,
            table.min-w-full thead th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-print-sheet {
                display: block !important;
                margin: 0 0 12px;
                padding: 0 0 8px;
                border-bottom: 2px solid #0f172a;
            }
        }
    </style>
@endonce

<div class="hidden report-print-sheet">
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ config('app.name', 'Gses Chaturji') }} · Admin report</p>
    <h1 class="text-lg font-bold text-slate-900 leading-tight" x-data x-init="
        const title = document.querySelector('.admin-page-title');
        if (title) $el.textContent = title.textContent.trim();
    ">{{ $printTitle ?: 'Report' }}</h1>
    <p class="text-[11px] text-slate-600 mt-1">
        Printed {{ now()->format('d M Y, h:i A') }}
        @if ($printFilters !== [])
            · {{ implode(' · ', $printFilters) }}
        @endif
    </p>
</div>

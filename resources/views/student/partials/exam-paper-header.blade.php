@props([
    'title',
    'className' => '—',
    'subjectName' => '—',
    'teacherName' => '—',
    'durationMinutes' => null,
    'totalMarks' => null,
    'studentName' => null,
    'fatherName' => null,
    'section' => null,
    'chapterNumber' => null,
    'chapterName' => null,
    'topicName' => null,
    'blankStudentFields' => false,
    'instructions' => null,
])

@php
    $settings = $settings ?? \App\Models\Setting::allCached();
    $schoolName = $settings['site_name'] ?? config('app.name', 'Gses Chaturji');
    $logoLetter = $settings['site_logo_letter'] ?? 'G';
    $logoUrl = asset('images/brand/logo.png');
    $instructionGroups = \App\Support\ExamPaperHelper::instructionGroups($instructions);
    $instructionCount = collect($instructionGroups)->sum(fn (array $group) => count($group['points']));

    $durationLabel = '—';
    if ($durationMinutes) {
        $hours = intdiv((int) $durationMinutes, 60);
        $minutes = (int) $durationMinutes % 60;
        $durationLabel = $minutes === 0
            ? $hours.' '.str('Hour')->plural($hours)
            : sprintf('%d hr %d min', $hours, $minutes);
    }

    $marksLabel = filled($totalMarks) ? (string) $totalMarks : '—';

    $displayValue = fn (?string $value): string => filled($value) ? $value : '—';

    $chapterLabel = '—';
    if (filled($chapterNumber) && filled($chapterName)) {
        $chapterLabel = 'Chapter '.$chapterNumber.' — '.$chapterName;
    } elseif (filled($chapterNumber)) {
        $chapterLabel = 'Chapter '.$chapterNumber;
    } elseif (filled($chapterName)) {
        $chapterLabel = $chapterName;
    }

    $infoItems = array_values(array_filter([
        [
            'label' => 'Class',
            'value' => $className,
            'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'tone' => 'green',
        ],
        [
            'label' => 'Subject',
            'value' => $subjectName,
            'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
            'tone' => 'gold',
        ],
        $chapterLabel !== '—' ? [
            'label' => 'Chapter',
            'value' => $chapterLabel,
            'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'tone' => 'green',
        ] : null,
        filled($topicName) ? [
            'label' => 'Topic',
            'value' => $topicName,
            'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
            'tone' => 'gold',
        ] : null,
        [
            'label' => 'Teacher',
            'value' => $teacherName,
            'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
            'tone' => 'slate',
        ],
        filled($studentName) || $blankStudentFields ? [
            'label' => 'Student',
            'value' => $displayValue($studentName),
            'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
            'tone' => 'green',
        ] : null,
        filled($fatherName) || $blankStudentFields ? [
            'label' => 'Father Name',
            'value' => $displayValue($fatherName),
            'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
            'tone' => 'slate',
        ] : null,
        filled($section) || $blankStudentFields ? [
            'label' => 'Section',
            'value' => $displayValue($section),
            'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
            'tone' => 'gold',
        ] : null,
    ]));

    $toneClasses = [
        'green' => 'bg-brand-green-50 text-brand-green border-brand-green-100',
        'gold' => 'bg-amber-50 text-amber-700 border-amber-100',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'admin-card overflow-hidden']) }}>
    <div class="admin-card-top"></div>

    <div class="relative overflow-hidden bg-gradient-to-br from-brand-green via-brand-green to-brand-green-dark px-3 py-2 sm:px-4 sm:py-2 text-white">
        <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-10 left-1/3 h-32 w-32 rounded-full bg-brand-gold/20 blur-2xl"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center gap-2.5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl overflow-hidden">
                <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="h-full w-full object-contain">
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">Question Paper</p>
                <h2 class="mt-0.5 text-lg sm:text-xl font-bold leading-tight">{{ $schoolName }}</h2>
                <p class="mt-0.5 text-xs sm:text-sm text-white/90 break-words leading-snug">{{ $title }}</p>
            </div>

            <div class="flex flex-wrap gap-2 sm:justify-end">
                @if ($durationMinutes)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-2.5 py-1.5 text-xs sm:text-sm font-semibold ring-1 ring-white/20 backdrop-blur-sm">
                        <svg class="h-4 w-4 shrink-0 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $durationLabel }}
                    </span>
                @endif
                @if (filled($totalMarks))
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-gold/90 px-2.5 py-1.5 text-xs sm:text-sm font-bold text-brand-green-dark shadow-sm">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                        {{ $marksLabel }} Marks
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 p-2.5 sm:p-3 bg-gradient-to-b from-slate-50/80 to-white">
        @foreach ($infoItems as $item)
            <div class="flex items-start gap-2 rounded-lg border border-slate-100 bg-white px-2.5 py-2 shadow-sm">
                <span @class([
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border',
                    $toneClasses[$item['tone']] ?? $toneClasses['slate'],
                ])>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-medium text-slate-500">{{ $item['label'] }}</p>
                    <p class="mt-0.5 text-sm font-semibold text-slate-900 break-words leading-snug">{{ $item['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    @if ($instructionGroups !== [])
        <div class="border-t border-amber-100 bg-gradient-to-b from-amber-50/70 via-white to-white px-3 py-2.5 sm:px-4 sm:py-2">
            <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                <div class="flex items-start gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-100 text-amber-700 shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Instructions</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Please read all points carefully before you start.</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full bg-brand-green-50 px-3 py-1 text-xs font-bold text-brand-green ring-1 ring-brand-green/15">
                    {{ $instructionCount }} {{ str('Point')->plural($instructionCount) }}
                </span>
            </div>

            <div class="space-y-2">
                @foreach ($instructionGroups as $group)
                    <div>
                        @if (count($instructionGroups) > 1)
                            <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-amber-800/80">
                                {{ $group['label'] }}
                            </p>
                        @endif

                        <ol class="space-y-1.5">
                            @foreach ($group['points'] as $point)
                                <li class="flex items-start gap-2 rounded-lg border border-slate-100 bg-white px-2.5 py-1.5 shadow-sm">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-brand-green text-xs font-bold text-white">
                                        {{ $loop->iteration }}
                                    </span>
                                    <p class="min-w-0 flex-1 text-sm leading-snug text-slate-700 pt-0.5">{{ $point }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

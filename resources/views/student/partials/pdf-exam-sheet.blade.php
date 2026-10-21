@props([
    'title',
    'className' => '',
    'subjectName' => 'General',
    'standardSlug' => null,
    'config' => [],
    'chapterName' => null,
    'topicName' => null,
    'totalMarks' => null,
    'totalQuestions' => 0,
    'breakdown' => [],
    'grouped',
    'attemptCounts' => [],
    'optionalCounts' => [],
    'instructions' => null,
])

@php
    $settings = $settings ?? \App\Models\Setting::allCached();
    $schoolName = $settings['site_tagline'] ?? 'Gujarat School of Excellence System';
    if (! str_contains(strtolower($schoolName), 'school')) {
        $schoolName = 'Gujarat School of Excellence System';
    }
    $logoUrl = asset('images/brand/logo.png');

    $now = now()->timezone('Asia/Kolkata');
    $startYear = ((int) $now->format('n') >= 4) ? (int) $now->format('Y') : ((int) $now->format('Y') - 1);
    $academicYear = $startYear.'-'.substr((string) ($startYear + 1), -2);

    $standardModel = filled($standardSlug)
        ? \App\Models\Standard::query()->where('slug', $standardSlug)->first(['name', 'medium'])
        : null;
    $mediumLabel = $standardModel?->mediumLabel() ?? 'Gujarati';

    $stdShort = trim((string) ($className !== '' ? $className : ($standardModel->name ?? '')));
    if (preg_match('/(\d+)/', $stdShort, $stdMatch)) {
        $stdShort = $stdMatch[1];
    }
    $stdShort = $stdShort !== '' ? $stdShort : '—';

    $materialIds = array_values(array_filter((array) ($config['material_ids'] ?? [])));
    $topicIds = array_values(array_filter((array) ($config['material_topic_ids'] ?? [])));
    $chapterCount = count($materialIds);
    if ($chapterCount < 1 && filled($chapterName)) {
        $chapterCount = max(1, count(array_filter(array_map('trim', preg_split('/[,|]/', (string) $chapterName)))));
    }
    $topicCount = count($topicIds);
    if ($topicCount < 1 && filled($topicName) && $topicName !== 'All topics') {
        $topicCount = max(1, count(array_filter(array_map('trim', preg_split('/[,|]/', (string) $topicName)))));
    }

    $banner = strtoupper((string) $title).' • STD. '.$stdShort.' • '.strtoupper((string) $subjectName);
    $instructionGroups = \App\Support\ExamPaperHelper::instructionGroups($instructions);
    $questionNumber = 0;
    $sectionIndex = 0;
@endphp

<style>
    .pdf-exam {
        font-family: Arial, "Nirmala UI", Helvetica, sans-serif;
        color: #111;
        font-size: 12px;
        line-height: 1.35;
        background: #fff;
        max-width: 210mm;
        margin: 12px auto 28px;
        border: 2.5px solid #1a4f8b;
        padding: 10px 14px 18px;
    }
    .pdf-frame { display: none; }
    .pdf-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .pdf-table > thead { display: table-header-group; }
    .pdf-table > tfoot { display: table-footer-group; }
    .pdf-table > tbody > tr > td,
    .pdf-table > thead > tr > td,
    .pdf-table > tfoot > tr > td { padding: 0; vertical-align: top; }
    .pdf-foot-space { height: 0; }
    .pdf-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 2px 4px 8px;
        border-bottom: 2px solid #1a4f8b;
        margin-bottom: 8px;
        background: #fff;
    }
    .pdf-brand.pdf-brand-fixed { display: none; }
    .pdf-brand img { width: 54px; height: 54px; object-fit: contain; }
    .pdf-brand-name {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: 0.03em;
        color: #1a4f8b;
        text-transform: uppercase;
        line-height: 1.15;
    }
    .pdf-brand-sub { font-size: 11px; color: #1a4f8b; line-height: 1.35; }
    .pdf-banner {
        margin: 6px 0;
        padding: 5px 8px;
        text-align: center;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #12345f;
        background: #eef4fb;
        border: 1px solid #1a4f8b;
    }
    .pdf-meta {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
        border: 1px solid #1a4f8b;
    }
    .pdf-meta td { padding: 4px 8px; font-size: 12px; vertical-align: bottom; }
    .pdf-blank {
        display: inline-block;
        min-width: 150px;
        border-bottom: 1px solid #111;
        height: 14px;
        vertical-align: bottom;
    }
    .pdf-blank.short { min-width: 80px; }
    .pdf-extra { margin: 6px 0; padding-left: 18px; }
    .pdf-extra li { margin: 1px 0; }
    .pdf-break-title {
        margin: 8px 0 4px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.02em;
    }
    .pdf-break { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .pdf-break th, .pdf-break td {
        border: 1px solid #1a4f8b;
        padding: 3px 8px;
        text-align: left;
        font-size: 12px;
    }
    .pdf-break th { background: #e7f0fa; font-weight: 700; }
    .pdf-sec {
        margin: 10px 0 4px;
        padding: 3px 0;
        border-bottom: 1.5px solid #1a4f8b;
        font-size: 12.5px;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #12345f;
    }
    .pdf-attempt { margin: 2px 0 4px; font-size: 11.5px; font-style: italic; }
    .pdf-q { padding: 5px 0 2px; }
    .pdf-q-line { display: flex; align-items: flex-start; gap: 6px; }
    .pdf-q-no { min-width: 28px; font-weight: 700; }
    .pdf-q-text { flex: 1; min-width: 0; }
    .pdf-q-text p { margin: 0; }
    .pdf-q-marks { white-space: nowrap; font-size: 11px; padding-top: 1px; }
    .pdf-opts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px 18px;
        margin: 2px 0 2px 34px;
    }
    .pdf-match {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 18px;
        margin: 4px 0 2px 34px;
    }
    .pdf-match p { margin: 0 0 2px; font-weight: 700; font-size: 11px; }
    .pdf-match ol { margin: 0; padding-left: 18px; }
    @page { size: A4; margin: 0; }
    @media print {
        .pdf-exam {
            max-width: none;
            margin: 0;
            border: none;
            padding: 0 8mm;
            box-shadow: none;
        }
        .pdf-brand.pdf-brand-fixed {
            display: flex;
            position: fixed;
            top: 5mm;
            left: 8mm;
            right: 8mm;
            margin: 0 0 0;
            z-index: 30;
        }
        .pdf-brand-flow { visibility: hidden; margin-top: 4mm; }
        .pdf-foot-space { height: 8mm; }
        .pdf-table > thead { display: table-header-group !important; }
        .pdf-table > tfoot { display: table-footer-group !important; }
        .pdf-frame {
            display: block;
            position: fixed;
            top: 3mm;
            right: 3mm;
            bottom: 3mm;
            left: 3mm;
            border: 2.5px solid #1a4f8b;
            pointer-events: none;
            z-index: 1;
        }
        .pdf-keep { break-inside: avoid; page-break-inside: avoid; }
        .pdf-sec { break-after: avoid; page-break-after: avoid; }
        .pdf-brand, .pdf-banner, .pdf-break th {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<div class="pdf-frame" aria-hidden="true"></div>
<div class="pdf-brand pdf-brand-fixed">
    <img src="{{ $logoUrl }}" alt="{{ $schoolName }}">
    <div>
        <div class="pdf-brand-name">{{ $schoolName }}</div>
        <div class="pdf-brand-sub">Schooling With Skills (Year: {{ $academicYear }})</div>
        <div class="pdf-brand-sub">CBSE Curriculum | {{ $mediumLabel }} Medium</div>
    </div>
</div>
<div class="pdf-exam">
    <table class="pdf-table">
        <thead>
            <tr>
                <td>
                    <div class="pdf-brand pdf-brand-flow">
                        <img src="{{ $logoUrl }}" alt="">
                        <div>
                            <div class="pdf-brand-name">{{ $schoolName }}</div>
                            <div class="pdf-brand-sub">Schooling With Skills (Year: {{ $academicYear }})</div>
                            <div class="pdf-brand-sub">CBSE Curriculum | {{ $mediumLabel }} Medium</div>
                        </div>
                    </div>
                </td>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td><div class="pdf-foot-space"></div></td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td>
                    <div class="pdf-banner">{{ $banner }}</div>
                    <table class="pdf-meta">
                        <tr>
                            <td>Student Name : <span class="pdf-blank"></span></td>
                            <td>Roll No. : <span class="pdf-blank short"></span></td>
                        </tr>
                        <tr>
                            <td>Std : {{ $stdShort }}</td>
                            <td>Date : <span class="pdf-blank short"></span></td>
                            <td>Topics : {{ $topicCount > 0 ? $topicCount : 'All' }}</td>
                        </tr>
                        <tr>
                            <td>Subject : {{ $subjectName }}</td>
                            <td>Chapters : {{ $chapterCount > 0 ? $chapterCount : '—' }}</td>
                            <td>Obtain Marks : <span class="pdf-blank short"></span></td>
                        </tr>
                    </table>
                    @if ($instructionGroups !== [])
                        <ol class="pdf-extra">
                            @foreach ($instructionGroups as $group)
                                @foreach ($group['points'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            @endforeach
                        </ol>
                    @endif

                    @if ($breakdown !== [])
                        <div class="pdf-break-title">
                            PAPER TYPE BREAKDOWN
                            @if ($totalQuestions)
                                | {{ $totalQuestions }} Questions
                            @endif
                            @if (filled($totalMarks))
                                | {{ $totalMarks }} Marks
                            @endif
                        </div>
                        <table class="pdf-break">
                            <tbody>
                                <tr>
                                    <th>Question Type</th>
                                    <th>Count / Marks</th>
                                </tr>
                                @foreach ($breakdown as $row)
                                    @php
                                        $attempt = (int) ($row['count'] ?? 0);
                                        $optional = (int) (($optionalCounts ?? [])[$row['type']] ?? 0);
                                        $printed = $attempt + $optional;
                                        $marksEach = (int) ($row['marks'] ?? 1);
                                        $subtotal = (int) ($row['subtotal'] ?? ($attempt * $marksEach));
                                    @endphp
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>
                                            {{ $printed }} Q | {{ $marksEach }} × {{ $attempt }} = {{ $subtotal }} marks
                                            @if ($optional > 0)
                                                | Attempt any {{ $attempt }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </td>
            </tr>

            @foreach ($grouped as $type => $typeQuestions)
                @php
                    $sectionIndex++;
                    $sectionLetter = $sectionIndex <= 26 ? chr(64 + $sectionIndex) : (string) $sectionIndex;
                    $typeRow = collect($breakdown)->firstWhere('type', $type);
                    $sectionMarks = (int) ($typeRow['subtotal'] ?? $typeQuestions->sum('marks'));
                    $attemptLine = \App\Support\PaperTypeHelper::attemptInstruction(
                        (int) (($attemptCounts ?? [])[$type] ?? 0),
                        (int) (($optionalCounts ?? [])[$type] ?? 0)
                    );
                @endphp
                <tr class="pdf-keep">
                    <td>
                        <div class="pdf-sec">
                            SECTION {{ $sectionLetter }} — {{ strtoupper(\App\Support\PaperTypeHelper::sectionLabel($type)) }}
                            | {{ $typeQuestions->count() }} Questions
                            | {{ $sectionMarks }} Marks
                        </div>
                        @if ($attemptLine)
                            <p class="pdf-attempt">{{ $attemptLine }}</p>
                        @endif
                    </td>
                </tr>
                @foreach ($typeQuestions as $question)
                    @php
                        $questionNumber++;
                        $rawOptions = $question->options;
                        if (is_string($rawOptions)) {
                            $rawOptions = json_decode($rawOptions, true);
                        }
                        $isMatch = is_array($rawOptions) && (isset($rawOptions['column_a']) || isset($rawOptions['column_b']));
                        $optionItems = (is_array($rawOptions) && ! $isMatch) ? $rawOptions : [];
                        $resolvedType = strtolower((string) ($question->question_type ?? $type));
                        $optionEntries = \App\Support\ObjectiveOptionHelper::entries($optionItems, $resolvedType);
                        $questionMarks = (int) ($question->marks ?? 1);
                    @endphp
                    <tr class="pdf-keep">
                        <td>
                            <div class="pdf-q">
                                <div class="pdf-q-line">
                                    <span class="pdf-q-no">({{ $questionNumber }})</span>
                                    <div class="pdf-q-text">{!! \App\Support\MathMarkup::render($question->question_text) !!}</div>
                                    @if ($questionMarks > 0)
                                        <span class="pdf-q-marks">[{{ $questionMarks }} {{ $questionMarks === 1 ? 'mark' : 'marks' }}]</span>
                                    @endif
                                </div>
                                @if ($optionEntries !== [])
                                    <div class="pdf-opts">
                                        @foreach ($optionEntries as $entry)
                                            <div>{{ strtoupper($entry['key']) }}. {!! \App\Support\MathMarkup::render($entry['text']) !!}</div>
                                        @endforeach
                                    </div>
                                @elseif ($isMatch)
                                    <div class="pdf-match">
                                        <div>
                                            <p>Column A</p>
                                            <ol type="a">
                                                @foreach ($rawOptions['column_a'] ?? [] as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ol>
                                        </div>
                                        <div>
                                            <p>Column B</p>
                                            <ol type="a">
                                                @foreach ($rawOptions['column_b'] ?? [] as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

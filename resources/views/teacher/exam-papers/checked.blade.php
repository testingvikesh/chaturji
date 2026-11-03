@php
    $questions = $sheet->ai_result['questions'] ?? [];
    $awardedTotal = (float) ($sheet->ai_result['total_awarded'] ?? $sheet->marks_obtained ?? 0);
    $paperTotal = (float) ($sheet->ai_result['total_marks'] ?? $paper->total_marks ?? 0);
    $percentage = (int) ($sheet->ai_result['percentage'] ?? ($paperTotal > 0 ? round(($awardedTotal / $paperTotal) * 100) : 0));
    $grade = (string) ($sheet->ai_result['grade'] ?? match (true) {
        $percentage >= 80 => 'A',
        $percentage >= 60 => 'B',
        $percentage >= 40 => 'C',
        default => 'D',
    });
    $markText = function (float $value): string {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    };
    $checkedOn = $sheet->checked_at?->timezone('Asia/Kolkata')->format('d / m / Y') ?? now('Asia/Kolkata')->format('d / m / Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Teacher Checked Sheet · {{ $sheet->student?->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 16px; background: #f3f4f6; color: #7a1f1f; font-family: "Segoe UI", Tahoma, sans-serif; }
        .tools { max-width: 920px; margin: 0 auto 12px; font-family: sans-serif; }
        .tools button, .tools a { margin-right: 10px; }
        .page {
            max-width: 920px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #c4b4b4;
            padding: 18px 22px 28px;
            background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 27px, #d9e6f5 28px);
            background-position: 0 46px;
        }
        h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .head, .q, .summary, .foot { display: grid; grid-template-columns: 1fr 190px; gap: 12px; }
        .head { border-bottom: 2px solid #7a1f1f; padding-bottom: 8px; margin-bottom: 8px; }
        .marks-label, .marks-big, .side-note, .q-marks { text-align: right; }
        .marks-big { font-size: 22px; font-weight: 700; text-decoration: underline; text-underline-offset: 4px; }
        .q { padding: 8px 0 4px; border-bottom: 1px solid #e7d0d0; break-inside: avoid; }
        .answer-no { margin: 0; font-weight: 700; }
        .meta { margin: 4px 0 0; font-size: 13px; }
        .answer-line { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .pen { flex: none; color: #c41616; font-weight: 800; text-align: right; line-height: 1.15; }
        .pen small { display: block; font-size: 12px; }
        .label { margin: 8px 0 2px; font-weight: 700; }
        ol { margin: 0 0 8px 18px; padding: 0; }
        .note {
            display: inline-block;
            max-width: 240px;
            margin: 6px 0;
            padding: 6px 8px;
            border: 1px solid #e7b4b4;
            border-left: 3px solid #b42323;
            background: #fff7f7;
            font-size: 13px;
        }
        .comment { margin: 4px 0 8px; }
        .side-note, .q-marks { font-size: 13px; margin-top: 8px; }
        .summary { margin-top: 12px; }
        .summary ul { list-style: none; margin: 6px 0 0; padding: 0; }
        .summary li { display: flex; justify-content: space-between; max-width: 280px; padding: 2px 0; }
        .total {
            border: 2px solid #7a1f1f;
            padding: 8px 10px;
            font-weight: 700;
            line-height: 1.6;
        }
        .grade {
            display: inline-flex;
            width: 28px;
            height: 28px;
            margin-left: 6px;
            border: 2px solid #7a1f1f;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
        }
        .foot { margin-top: 22px; align-items: end; font-weight: 700; }
        .signed { border-bottom: 1px solid #7a1f1f; display: inline-block; min-width: 140px; }
        @media print {
            body { margin: 0; background: #fff; }
            .tools { display: none; }
            .page { border: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="tools">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('teacher.exam-papers.show', $paper) }}">Back</a>
    </div>
    <article class="page">
        <div class="head">
            <div>
                <h1>Teacher Checked Sheet</h1>
                <p class="meta">{{ $sheet->student?->name }} · {{ $paper->title }} · {{ $paper->standard?->name }} · {{ $paper->subject_name }}</p>
            </div>
            <div class="marks-label">Marks</div>
        </div>

        @foreach ($questions as $row)
            @php
                $awarded = (float) ($row['awarded'] ?? 0);
                $max = (float) ($row['max_marks'] ?? 0);
                $number = $row['number'] ?? '';
                $covered = is_array($row['covered'] ?? null) ? array_values($row['covered']) : [];
                $missing = is_array($row['missing'] ?? null) ? array_values($row['missing']) : [];
                $full = $max > 0 && $awarded >= $max;
                $ticked = $awarded > 0;
                $praise = $full ? 'Excellent!' : 'Nice try!';
                $side = $full
                    ? 'Wonderful! Covered: '.($covered[0] ?? ($row['comment'] ?? ''))
                    : 'Great start! Also: '.(($row['tip'] ?? '') !== '' ? $row['tip'] : ($missing[0] ?? ($row['comment'] ?? '')));
            @endphp
            <section class="q">
                <div>
                    <p class="answer-no">{{ $number }}. {{ $ticked ? '✓' : '○' }} [Answer - No{{ $number }}]</p>
                    @if (! empty($row['question']))
                        <p class="meta">{{ $row['question'] }}</p>
                    @endif
                    <p class="meta answer-line">
                        <span><strong>Answer:</strong> {{ ($row['student_answer'] ?? '') !== '' ? $row['student_answer'] : 'Not written' }}</span>
                        @if ($ticked)
                            <span class="pen">✓ {{ $markText($awarded) }}/{{ $markText($max) }}<small>{{ $praise }}</small></span>
                        @endif
                    </p>
                    @if ($covered !== [])
                        <p class="label">Key points covered:</p>
                        <ol>
                            @foreach ($covered as $point)
                                <li>✓ {{ $point }}</li>
                            @endforeach
                        </ol>
                    @endif
                    @if ($missing !== [])
                        <p class="label">Missing:</p>
                        <ol>
                            @foreach ($missing as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ol>
                    @endif
                    @if (! empty($row['comment']))
                        <div class="note">{{ $row['comment'] }}</div>
                        <p class="comment">○ Teacher's Comment: ★ {{ $row['comment'] }}</p>
                    @endif
                </div>
                <div>
                    <div class="marks-big">{{ $markText($awarded) }}/{{ $markText($max) }}</div>
                    <p class="side-note">{{ $side }}</p>
                    <p class="q-marks">Q. Marks {{ $number }} : {{ $markText($awarded) }} / {{ $markText($max) }}</p>
                </div>
            </section>
        @endforeach

        <div class="summary">
            <div>
                <p class="label">Marks summary</p>
                <ul>
                    @foreach ($questions as $row)
                        <li>
                            <span>{{ ((float) ($row['awarded'] ?? 0)) > 0 ? '✓' : '○' }} Q.{{ $row['number'] ?? '' }}</span>
                            <span>{{ $markText((float) ($row['awarded'] ?? 0)) }}/{{ $markText((float) ($row['max_marks'] ?? 0)) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="total">
                <div>Total Marks : {{ $markText($awardedTotal) }} / {{ $markText($paperTotal) }}</div>
                <div>Percentage : {{ $percentage }}%</div>
                <div>Grade : ({{ $grade }}) <span class="grade">{{ $grade }}</span></div>
            </div>
        </div>

        <div class="foot">
            <div>Checked by: <span class="signed">{{ $paper->teacher?->name ?: 'Teacher' }}</span></div>
            <div style="text-align:right;">Date : {{ $checkedOn }}</div>
        </div>
    </article>
    <script>setTimeout(function () { window.print(); }, 400);</script>
</body>
</html>

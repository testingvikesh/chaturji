@php
    $questions = $questions ?? ($sheet->ai_result['questions'] ?? []);
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
@endphp

<div style="border:1px solid #d6d3c4;border-radius:12px;overflow:hidden;background:#fffdf8;">
    <div style="display:flex;justify-content:space-between;gap:12px;padding:12px 14px;background:#1a367c;color:#fff;">
        <div>
            <p style="margin:0;font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#d4af37;">Teacher checked sheet</p>
            <p style="margin:4px 0 0;font-size:16px;font-weight:700;">{{ $sheet->student?->name }}</p>
        </div>
        <div style="text-align:right;">
            <p style="margin:0;font-size:20px;font-weight:800;">{{ $markText($awardedTotal) }}/{{ $markText($paperTotal) }}</p>
            <p style="margin:2px 0 0;font-size:13px;">{{ $percentage }}% · Grade {{ $grade }}</p>
        </div>
    </div>

    <div style="padding:12px 14px;">
        @foreach ($questions as $row)
            @php
                $awarded = (float) ($row['awarded'] ?? 0);
                $max = (float) ($row['max_marks'] ?? 0);
                $markColor = $awarded <= 0 ? '#b91c1c' : ($max > 0 && $awarded >= $max ? '#047857' : '#92400e');
                $covered = is_array($row['covered'] ?? null) ? $row['covered'] : [];
                $missing = is_array($row['missing'] ?? null) ? $row['missing'] : [];
            @endphp
            <section style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eadfbe;">
                <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                    <p style="margin:0;font-weight:700;color:#1a367c;">Q{{ $row['number'] ?? '' }} {{ $row['question'] ?? '' }}</p>
                    <span style="flex:none;font-weight:800;color:{{ $markColor }};">{{ $markText($awarded) }}/{{ $markText($max) }}</span>
                </div>
                @php $ticked = $awarded > 0; $praise = ($max > 0 && $awarded >= $max) ? 'Excellent!' : 'Nice try!'; @endphp
                <p style="margin:6px 0 0;font-size:13px;color:#334155;display:flex;justify-content:space-between;gap:8px;">
                    <span><span style="font-weight:700;">Answer:</span> {{ ($row['student_answer'] ?? '') !== '' ? $row['student_answer'] : 'Not written' }}</span>
                    @if ($ticked)
                        <span style="flex:none;color:#c41616;font-weight:800;text-align:right;">✓ {{ $markText($awarded) }}/{{ $markText($max) }}<br>{{ $praise }}</span>
                    @endif
                </p>
                @if ($covered !== [])
                    <p style="margin:8px 0 2px;font-size:12px;font-weight:700;color:#047857;">Key points covered</p>
                    <ul style="margin:0;padding-left:18px;font-size:13px;color:#334155;">
                        @foreach ($covered as $point)
                            <li>✓ {{ $point }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($missing !== [])
                    <p style="margin:8px 0 2px;font-size:12px;font-weight:700;color:#b91c1c;">Missing</p>
                    <ul style="margin:0;padding-left:18px;font-size:13px;color:#334155;">
                        @foreach ($missing as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                @endif
                @if (! empty($row['comment']))
                    <p style="margin:8px 0 0;padding:6px 8px;background:#f4f0e4;border-left:3px solid #1a367c;font-size:13px;">{{ $row['comment'] }}</p>
                @endif
                @if (! empty($row['tip']))
                    <p style="margin:6px 0 0;font-size:13px;color:#92400e;"><span style="font-weight:700;">Also:</span> {{ $row['tip'] }}</p>
                @endif
            </section>
        @endforeach

        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f4f0e4;">
                    <th style="padding:6px 8px;text-align:left;">Question</th>
                    <th style="padding:6px 8px;text-align:right;">Marks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($questions as $row)
                    <tr style="border-top:1px solid #eadfbe;">
                        <td style="padding:6px 8px;">Q{{ $row['number'] ?? '' }}</td>
                        <td style="padding:6px 8px;text-align:right;font-weight:700;">{{ $markText((float) ($row['awarded'] ?? 0)) }}/{{ $markText((float) ($row['max_marks'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin:10px 0 0;font-weight:800;color:#1a367c;">Total {{ $markText($awardedTotal) }}/{{ $markText($paperTotal) }} · {{ $percentage }}% · Grade {{ $grade }}</p>
    </div>
</div>

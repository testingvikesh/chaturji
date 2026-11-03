<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checked sheet · {{ $sheet->student?->name }}</title>
    <style>
        body { margin: 24px; font-family: Georgia, "Times New Roman", serif; color: #1e293b; }
        .tools { margin-bottom: 16px; }
        .tools button, .tools a { font-family: sans-serif; font-size: 14px; }
        @media print { .tools { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="tools">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('teacher.exam-papers.show', $paper) }}">Back</a>
    </div>
    <p style="margin:0 0 8px;font-size:14px;">{{ $paper->title }} · {{ $paper->standard?->name }} · {{ $paper->subject_name }}</p>
    @include('teacher.exam-papers.partials.checked-sheet', ['paper' => $paper, 'sheet' => $sheet])
    <script>setTimeout(function () { window.print(); }, 400);</script>
</body>
</html>

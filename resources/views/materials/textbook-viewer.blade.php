<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $material->displayChapterName() }} — Textbook</title>
    <style>
        html, body { margin: 0; height: 100%; }
        body { display: flex; flex-direction: column; font-family: ui-sans-serif, system-ui, sans-serif; background: #f1f5f9; color: #0f172a; }
        header { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        a.back { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; text-decoration: none; }
        a.back:hover { background: #f8fafc; }
        .title { min-width: 0; }
        .title strong { display: block; font-size: 14px; }
        .title span { display: block; font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        iframe { flex: 1; width: 100%; border: 0; background: #e2e8f0; }
    </style>
</head>
<body>
    <header>
        @if ($backUrl)
            <a class="back" href="{{ $backUrl }}" aria-label="Back">
        @else
            <a class="back" href="{{ url()->previous() }}" aria-label="Back">
        @endif
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="title">
            <strong>Textbook</strong>
            <span>{{ $material->displayChapterName() }}@if ($material->material_attachment) · {{ $material->textbookPdfFilename() }}@endif</span>
        </div>
    </header>
    <iframe src="{{ $pdfUrl }}" title="Textbook PDF"></iframe>
</body>
</html>

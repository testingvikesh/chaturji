<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $material->displayChapterName() }} — Textbook</title>
    <style>
        html, body { margin: 0; height: 100%; }
        body { display: flex; flex-direction: column; font-family: ui-sans-serif, system-ui, sans-serif; background: #e2e8f0; color: #0f172a; }
        header { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        a.back { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; text-decoration: none; flex-shrink: 0; }
        a.back:hover { background: #f8fafc; }
        .title { min-width: 0; }
        .title strong { display: block; font-size: 14px; }
        .title span { display: block; font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #pages { flex: 1; overflow: auto; width: 100%; -webkit-overflow-scrolling: touch; }
        .page { width: 100%; max-width: 100%; margin: 0 auto 12px; background: #fff; }
        .page canvas { display: block; width: 100%; height: auto; margin: 0 auto; }
        #status { padding: 24px 16px; text-align: center; color: #475569; font-size: 14px; }
        iframe.fallback { flex: 1; width: 100%; border: 0; background: #e2e8f0; }
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
    <div id="pages">
        <p id="status">Loading textbook…</p>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        const pdfUrl = @json($pdfUrl);
        const pagesEl = document.getElementById('pages');
        const statusEl = document.getElementById('status');

        function showFallback() {
            pagesEl.remove();
            const frame = document.createElement('iframe');
            frame.className = 'fallback';
            frame.title = 'Textbook PDF';
            frame.src = pdfUrl + '#view=FitH&zoom=page-width';
            document.body.appendChild(frame);
        }

        if (!window.pdfjsLib) {
            showFallback();
        } else {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true }).promise.then(async function (pdf) {
                statusEl.remove();
                const holders = [];

                for (let number = 1; number <= pdf.numPages; number++) {
                    const page = await pdf.getPage(number);
                    const wrap = document.createElement('div');
                    wrap.className = 'page';
                    const canvas = document.createElement('canvas');
                    wrap.appendChild(canvas);
                    pagesEl.appendChild(wrap);
                    holders.push({ page: page, canvas: canvas });
                }

                let painting = false;
                let paintAgain = false;

                async function paint() {
                    if (painting) {
                        paintAgain = true;
                        return;
                    }
                    painting = true;
                    const width = Math.max(pagesEl.clientWidth, document.documentElement.clientWidth);
                    for (const item of holders) {
                        const base = item.page.getViewport({ scale: 1 });
                        const viewport = item.page.getViewport({ scale: width / base.width });
                        item.canvas.width = viewport.width;
                        item.canvas.height = viewport.height;
                        await item.page.render({ canvasContext: item.canvas.getContext('2d'), viewport: viewport }).promise;
                    }
                    painting = false;
                    if (paintAgain) {
                        paintAgain = false;
                        paint();
                    }
                }

                paint();
                window.addEventListener('resize', paint);
                window.addEventListener('orientationchange', function () {
                    setTimeout(paint, 200);
                });
            }).catch(function () {
                showFallback();
            });
        }
    </script>
</body>
</html>

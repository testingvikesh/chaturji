<div class="textbook-pdf-canvas h-full overflow-y-auto bg-slate-200" data-pdf-url="{{ $pdfUrl }}"></div>
@once
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        window.renderTextbookPages = function (container) {
            if (!container || container.dataset.rendering === '1') {
                return;
            }
            const url = container.dataset.pdfUrl;
            if (!url || !window.pdfjsLib) {
                return;
            }
            container.dataset.rendering = '1';
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            pdfjsLib.getDocument({ url: url, withCredentials: true }).promise.then(async function (pdf) {
                container.innerHTML = '';
                const holders = [];
                for (let number = 1; number <= pdf.numPages; number++) {
                    const page = await pdf.getPage(number);
                    const wrap = document.createElement('div');
                    wrap.className = 'mx-auto w-full bg-white';
                    wrap.id = 'textbook-pdf-page-' + number;
                    const canvas = document.createElement('canvas');
                    canvas.className = 'block h-auto w-full';
                    wrap.appendChild(canvas);
                    container.appendChild(wrap);
                    holders.push({ page: page, canvas: canvas });
                }

                let painting = false;
                let paintAgain = false;
                async function paint() {
                    if (painting) {
                        paintAgain = true;
                        return;
                    }
                    const width = container.clientWidth || container.getBoundingClientRect().width;
                    if (width < 40) {
                        setTimeout(paint, 80);
                        return;
                    }
                    painting = true;
                    for (const item of holders) {
                        const base = item.page.getViewport({ scale: 1 });
                        const viewport = item.page.getViewport({ scale: Math.max(width, 1) / base.width });
                        item.canvas.width = viewport.width;
                        item.canvas.height = viewport.height;
                        await item.page.render({ canvasContext: item.canvas.getContext('2d'), viewport: viewport }).promise;
                    }
                    painting = false;
                    container.dispatchEvent(new CustomEvent('textbook-ready', { bubbles: true }));
                    if (paintAgain) {
                        paintAgain = false;
                        paint();
                    }
                }

                await paint();
                container.dataset.rendering = '0';
                container.dataset.ready = '1';
                if (!container.dataset.bound) {
                    container.dataset.bound = '1';
                    window.addEventListener('resize', paint);
                    window.addEventListener('orientationchange', function () { setTimeout(paint, 200); });
                }
            }).catch(function () {
                container.dataset.rendering = '0';
                container.innerHTML = '<p class="p-6 text-center text-sm text-slate-600">Textbook could not be opened.</p>';
                container.dispatchEvent(new CustomEvent('textbook-ready', { bubbles: true }));
            });
        };
    </script>
@endonce

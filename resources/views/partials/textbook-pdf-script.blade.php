@once
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        window.renderTextbookPages = function (container) {
            if (!container || container.dataset.rendering === '1' || container.dataset.ready === '1') {
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
                        const pixelRatio = Math.min(window.devicePixelRatio || 1, 1.5);
                        const viewport = item.page.getViewport({ scale: (width / base.width) * pixelRatio });
                        item.canvas.width = viewport.width;
                        item.canvas.height = viewport.height;
                        item.canvas.style.width = '100%';
                        item.canvas.style.height = 'auto';
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
                    window.addEventListener('orientationchange', function () { setTimeout(paint, 250); });
                }
            }).catch(function () {
                container.dataset.rendering = '0';
                const frame = document.createElement('iframe');
                frame.src = url;
                frame.title = 'Textbook PDF';
                frame.style.cssText = 'width:100%;height:100%;border:0;background:#fff;';
                container.innerHTML = '';
                container.appendChild(frame);
                container.dataset.ready = '1';
                container.dispatchEvent(new CustomEvent('textbook-ready', { bubbles: true }));
            });
        };
    </script>
@endonce

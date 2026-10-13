@props([
    'name',
    'value' => '',
    'rows' => 4,
    'placeholder' => 'Paste or type. Subscript and superscript are kept.',
])

<div class="math-editor" data-math-editor>
    <div class="math-editor-bar">
        <button type="button" data-math-cmd="subscript" title="Subscript">x<sub>2</sub></button>
        <button type="button" data-math-cmd="superscript" title="Superscript">x<sup>2</sup></button>
        <span class="math-editor-hint">Paste the equation as it is. The preview below shows how it will look.</span>
    </div>
    <div class="math-editor-area"
         contenteditable="true"
         role="textbox"
         data-placeholder="{{ $placeholder }}"
         style="min-height: {{ max(3, (int) $rows) * 1.55 }}rem">{!! \App\Support\MathMarkup::render($value) !!}</div>
    <div class="math-editor-preview" hidden></div>
    <textarea name="{{ $name }}" class="math-editor-store" hidden>{{ $value }}</textarea>
</div>

@once
    <style>
        .math-editor { display: flex; flex-direction: column; gap: .4rem; }
        .math-editor-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
        .math-editor-bar button {
            height: 2rem;
            min-width: 2.4rem;
            border-radius: .6rem;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            padding: 0 .55rem;
            font-size: .85rem;
            font-weight: 800;
            color: #166534;
            cursor: pointer;
        }
        .math-editor-bar button:hover { background: #15803d; color: #fff; border-color: #15803d; }
        .math-editor-hint { font-size: .75rem; color: #64748b; }
        .math-editor-area {
            width: 100%;
            border-radius: .85rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: .7rem .85rem;
            font-size: .95rem;
            line-height: 1.55;
            color: #0f172a;
            white-space: pre-wrap;
            outline: none;
        }
        .math-editor-area:focus { border-color: #15803d; box-shadow: 0 0 0 3px rgba(21, 128, 61, .15); }
        .math-editor-area:empty:before { content: attr(data-placeholder); color: #94a3b8; }
        .math-editor-area sub, .math-editor-area sup { font-size: .75em; }
        .math-editor-preview {
            border-radius: .85rem;
            border: 1px dashed #86efac;
            background: #f0fdf4;
            padding: .65rem .85rem;
            overflow-x: auto;
            line-height: 1.7;
        }
        .math-editor-preview:empty { display: none; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            function escapeHtml(value) {
                return value
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function walkClipboard(node) {
                let html = '';
                node.childNodes.forEach(function (child) {
                    if (child.nodeType === 3) {
                        html += escapeHtml(child.textContent || '');
                        return;
                    }
                    if (child.nodeType !== 1) {
                        return;
                    }
                    const tag = child.tagName;
                    const style = (child.getAttribute('style') || '').toLowerCase();
                    const vertical = /vertical-align\s*:\s*(sub|super|superscript|subscript)/.exec(style);
                    let wrap = null;
                    if (tag === 'SUB' || (vertical && vertical[1].indexOf('sub') === 0)) {
                        wrap = 'sub';
                    }
                    if (tag === 'SUP' || (vertical && (vertical[1] === 'super' || vertical[1] === 'superscript'))) {
                        wrap = 'sup';
                    }
                    if (tag === 'BR') {
                        html += '<br>';
                        return;
                    }
                    const inner = walkClipboard(child);
                    if (tag === 'DIV' || tag === 'P' || tag === 'LI' || tag === 'TR') {
                        if (html && !html.endsWith('<br>')) {
                            html += '<br>';
                        }
                    }
                    html += wrap ? ('<' + wrap + '>' + inner + '</' + wrap + '>') : inner;
                });
                return html;
            }

            function extractTex(doc) {
                const bits = [];
                doc.querySelectorAll('annotation, script[type="math/tex"], script[type="math/tex; mode=display"]').forEach(function (node) {
                    const tex = (node.textContent || '').trim();
                    if (tex) {
                        bits.push((node.getAttribute('encoding') === 'application/x-tex' || node.tagName === 'SCRIPT' || node.getAttribute('encoding') === 'application/x-tex') ? tex : tex);
                    }
                });
                const unique = bits.filter(function (tex, index) { return bits.indexOf(tex) === index; });
                if (!unique.length) {
                    return '';
                }
                return unique.map(function (tex) {
                    if (tex.indexOf('\\(') === 0 || tex.indexOf('$') === 0) {
                        return tex;
                    }
                    return '\\(' + tex + '\\)';
                }).join('\n');
            }

            function clipboardHtml(event) {
                const html = event.clipboardData ? event.clipboardData.getData('text/html') : '';
                const plain = event.clipboardData ? event.clipboardData.getData('text/plain') : '';
                if (!html) {
                    return escapeHtml(plain).replace(/\n/g, '<br>');
                }
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const tex = extractTex(doc);
                if (tex) {
                    return escapeHtml(tex).replace(/\n/g, '<br>');
                }
                const clean = walkClipboard(doc.body);
                const plainHtml = escapeHtml(plain).replace(/\n/g, '<br>');
                const cleanText = clean.replace(/<[^>]+>/g, '');
                if (plain && cleanText.length < plain.length * 0.7) {
                    return plainHtml;
                }
                return clean.trim() !== '' ? clean : plainHtml;
            }

            function serialize(node) {
                let out = '';
                node.childNodes.forEach(function (child) {
                    if (child.nodeType === 3) {
                        out += child.textContent || '';
                        return;
                    }
                    if (child.nodeType !== 1) {
                        return;
                    }
                    const tag = child.tagName;
                    if (tag === 'BR') {
                        out += '\n';
                        return;
                    }
                    if (tag === 'SUB' || tag === 'SUP') {
                        const name = tag.toLowerCase();
                        out += '<' + name + '>' + serialize(child) + '</' + name + '>';
                        return;
                    }
                    if (tag === 'DIV' || tag === 'P') {
                        if (out && !out.endsWith('\n')) {
                            out += '\n';
                        }
                        out += serialize(child);
                        return;
                    }
                    out += serialize(child);
                });
                return out;
            }

            function previewHtml(text) {
                const lines = text.split('\n').map(function (line) {
                    const math = /\\[a-zA-Z]+|[_^]\{/.test(line);
                    const wrapped = math && line.indexOf('\\(') === -1 && line.indexOf('$') === -1
                        ? '\\(' + line + '\\)'
                        : line;
                    return escapeHtml(wrapped)
                        .replace(/&lt;sub&gt;/g, '<sub>')
                        .replace(/&lt;\/sub&gt;/g, '</sub>')
                        .replace(/&lt;sup&gt;/g, '<sup>')
                        .replace(/&lt;\/sup&gt;/g, '</sup>');
                });
                return lines.join('<br>');
            }

            function paintPreview(root, text) {
                const preview = root.querySelector('.math-editor-preview');
                if (!preview) {
                    return;
                }
                if (!text.trim()) {
                    preview.hidden = true;
                    preview.innerHTML = '';
                    return;
                }
                preview.hidden = false;
                preview.innerHTML = previewHtml(text);
                if (!window.renderMathInElement) {
                    return;
                }
                window.renderMathInElement(preview, {
                    delimiters: [
                        { left: '\\(', right: '\\)', display: false },
                        { left: '\\[', right: '\\]', display: true },
                        { left: '$', right: '$', display: false }
                    ],
                    throwOnError: false
                });
            }

            function sync(box) {
                const root = box.parentElement;
                const store = root.querySelector('.math-editor-store');
                const value = serialize(box).replace(/\u00a0/g, ' ').replace(/\n+$/g, '').trim();
                if (store) {
                    store.value = value;
                }
                paintPreview(root, value);
            }

            function init(root) {
                if (root.dataset.ready === '1') {
                    return;
                }
                root.dataset.ready = '1';
                const box = root.querySelector('.math-editor-area');
                const form = root.closest('form');
                root.querySelectorAll('[data-math-cmd]').forEach(function (button) {
                    button.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                    });
                    button.addEventListener('click', function () {
                        box.focus();
                        document.execCommand(button.dataset.mathCmd, false, null);
                        sync(box);
                    });
                });
                box.addEventListener('paste', function (event) {
                    event.preventDefault();
                    document.execCommand('insertHTML', false, clipboardHtml(event));
                    sync(box);
                });
                box.addEventListener('input', function () { sync(box); });
                if (form) {
                    form.addEventListener('submit', function () { sync(box); });
                }
                sync(box);
            }

            function boot() {
                document.querySelectorAll('[data-math-editor]').forEach(init);
            }

            function bootWhenReady() {
                boot();
                if (!window.renderMathInElement) {
                    setTimeout(bootWhenReady, 80);
                    return;
                }
                document.querySelectorAll('[data-math-editor] .math-editor-area').forEach(function (box) {
                    sync(box);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootWhenReady);
            } else {
                bootWhenReady();
            }
        })();
    </script>
@endonce

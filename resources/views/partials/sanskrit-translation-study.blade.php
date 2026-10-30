@php
    $study = $translationStudy ?? null;
@endphp
@if (is_array($study))
    <section id="sanskrit-translate" class="admin-card mb-6 overflow-hidden">
        <div class="admin-card-top"></div>
        @if (($study['pairs'] ?? []) !== [] || ($study['original'] ?? []) !== [])
            <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="min-w-0 font-bold text-slate-900 text-lg">{{ $study['title'] ?? 'Translate' }}@if (($study['pairs'] ?? []) !== []) ({{ count($study['pairs']) }})@endif</h3>
                @if (($study['original'] ?? []) !== [] || ($study['lines'] ?? []) !== [] || ($study['pairs'] ?? []) !== [])
                    <button type="button" data-explain-open aria-expanded="false" aria-controls="sanskrit-explain" class="ml-auto inline-flex h-9 shrink-0 items-center rounded-full border border-brand-green bg-brand-green px-4 text-sm font-bold text-white shadow-sm hover:bg-brand-green-dark">Explain</button>
                @endif
            </header>
            <ol class="divide-y divide-amber-100">
                @foreach ($study['pairs'] as $pair)
                    <li class="flex items-start justify-between gap-3 border-l-4 border-amber-300 px-5 py-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $pair['source'] }}</p>
                            @if (($pair['translation'] ?? '') !== '')
                                <p class="mt-1 pl-4 text-sm text-slate-700">• {{ $pair['translation'] }}</p>
                            @endif
                        </div>
                        @if (! empty($pair['edit_url']) || ! empty($pair['important_url']))
                            <div class="flex shrink-0 items-center gap-2">
                                @include('partials.question-imp-button', [
                                    'url' => $pair['important_url'] ?? null,
                                    'important' => (bool) ($pair['is_important'] ?? false),
                                ])
                                @if (! empty($pair['edit_url']))
                                    <a href="{{ $pair['edit_url'] }}" class="inline-flex h-9 shrink-0 items-center rounded-lg border border-brand-green-100 bg-brand-green-50 px-2.5 text-xs font-semibold text-brand-green transition hover:bg-brand-green hover:text-white">Edit</a>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    @if (($study['original'] ?? []) !== [] || ($study['lines'] ?? []) !== [])
        <section id="sanskrit-explain" class="admin-card mb-6 hidden overflow-hidden"
            @if (($study['source'] ?? '') !== '') data-sanskrit-explain data-explain-url="{{ route('sanskrit-explain') }}" @endif>
            <div class="admin-card-top"></div>
            <input type="hidden" data-explain-token value="{{ csrf_token() }}">
            <textarea hidden data-explain-source>{{ $study['source'] ?? '' }}</textarea>
            <header class="material-question-group-header">
                <h3 class="font-bold text-slate-900 text-lg">Explain</h3>
                <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">English</span>
            </header>
            <div class="space-y-5 bg-slate-50/70 px-4 py-5 sm:px-6">
                @if (($study['original'] ?? []) !== [])
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h4 class="text-sm font-bold text-sky-800">1. Original Text</h4>
                        <ol class="mt-3 space-y-2 border-l-4 border-amber-300 bg-amber-50/60 px-4 py-3 text-sm leading-7 text-slate-900">
                            @foreach ($study['original'] as $line)
                                <li><span class="mr-2 font-bold text-amber-800">{{ $loop->iteration }}.</span>{{ $line }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h4 class="text-sm font-bold text-sky-800">2. Word-to-word English translation</h4>
                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-sky-50 text-left text-sky-900">
                                    <th class="w-1/2 px-3 py-2.5 font-semibold">Original Word</th>
                                    <th class="w-1/2 px-3 py-2.5 font-semibold">English Meaning</th>
                                </tr>
                            </thead>
                            <tbody data-explain-words class="divide-y divide-slate-100">
                                @forelse ($study['words'] ?? [] as $word)
                                    <tr class="odd:bg-white even:bg-slate-50">
                                        <td class="px-3 py-2 font-semibold text-slate-900">{{ $word['word'] }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ \Illuminate\Support\Str::ucfirst($word['meaning']) }}</td>
                                    </tr>
                                @empty
                                    <tr data-explain-wait>
                                        <td class="px-3 py-3 text-slate-500" colspan="2">Preparing the English explain…</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if (($study['lines'] ?? []) !== [])
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h4 class="text-sm font-bold text-sky-800">3. Line-to-line English translation</h4>
                        <div class="mt-3 space-y-3" data-explain-lines>
                            @foreach ($study['lines'] as $line)
                                <article class="overflow-hidden rounded-xl border border-slate-200">
                                    <p class="bg-slate-100 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-slate-500">Line {{ $loop->iteration }}</p>
                                    <div class="space-y-2 px-3 py-3 text-sm">
                                        @if (($line['original'] ?? '') !== '')
                                            <p class="text-slate-900"><span class="font-semibold text-slate-500">Original</span><br>{{ $line['original'] }}</p>
                                        @endif
                                        @if (($line['translation'] ?? '') !== '')
                                            <p class="rounded-lg bg-emerald-50 px-3 py-2 text-slate-800"><span class="font-semibold text-emerald-800">English</span><br>{{ \Illuminate\Support\Str::ucfirst($line['translation']) }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (($study['paragraph'] ?? '') !== '')
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h4 class="text-sm font-bold text-sky-800">4. Full paragraph English translation</h4>
                        <p class="mt-3 border-l-4 border-sky-400 bg-sky-50 px-4 py-3 text-sm leading-7 text-slate-800">{{ \Illuminate\Support\Str::ucfirst($study['paragraph']) }}</p>
                    </div>
                @endif

                <div data-explain-more class="space-y-5"></div>
            </div>
        </section>
    @endif
@endif

@once
    <script>
        function capitalEnglish(value) {
            const text = String(value || '');
            const index = text.search(/\S/);
            if (index < 0) {
                return text;
            }
            return text.slice(0, index) + text.charAt(index).toUpperCase() + text.slice(index + 1);
        }
        function renderExplainLines(panel, rows) {
            const wrap = panel.querySelector('[data-explain-lines]');
            const prepared = [];
            (Array.isArray(rows) ? rows : []).forEach(function (row) {
                const original = String((row && row.original) || '').trim();
                const english = String((row && (row.english || row.translation)) || '').trim();
                const parts = original.split(/(?<=[।॥])\s*/u).map(function (part) {
                    return part.trim();
                }).filter(function (part) {
                    return part !== '' && part !== '।' && part !== '॥';
                });
                if (parts.length <= 1) {
                    if (original !== '' || english !== '') {
                        prepared.push({ original: original, english: english });
                    }
                    return;
                }
                parts.forEach(function (part) {
                    prepared.push({ original: part, english: '' });
                });
            });
            if (!wrap || !prepared.length) {
                return;
            }
            wrap.innerHTML = prepared.map(function () {
                return '<article class="overflow-hidden rounded-xl border border-slate-200"><p class="bg-slate-100 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-slate-500"></p><div class="space-y-2 px-3 py-3 text-sm"><p class="text-slate-900"><span class="font-semibold text-slate-500">Original</span><br><span data-line-original></span></p><p class="rounded-lg bg-emerald-50 px-3 py-2 text-slate-800"><span class="font-semibold text-emerald-800">English</span><br><span data-line-english></span></p></div></article>';
            }).join('');
            wrap.querySelectorAll('article').forEach(function (article, index) {
                const row = prepared[index] || {};
                article.querySelector('p').textContent = 'Line ' + (index + 1);
                article.querySelector('[data-line-original]').textContent = row.original || '';
                const english = article.querySelector('[data-line-english]');
                english.textContent = capitalEnglish(row.english || '');
                if (!row.english) {
                    english.parentElement.hidden = true;
                }
            });
        }
        function loadExplain(panel) {
            if (!panel || panel.dataset.loaded === '1' || panel.dataset.loading === '1') {
                return;
            }
            const sourceNode = panel.querySelector('[data-explain-source]');
            const source = sourceNode ? sourceNode.value : '';
            const url = panel.getAttribute('data-explain-url') || '';
            if (!source || !url) {
                return;
            }
            panel.dataset.loading = '1';
            const token = panel.querySelector('[data-explain-token]');
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.value : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ text: source })
            }).then(function (res) { return res.json(); }).then(function (data) {
                const explain = data && data.explain ? data.explain : null;
                const wordsBody = panel.querySelector('[data-explain-words]');
                const more = panel.querySelector('[data-explain-more]');
                if (!explain || !wordsBody) {
                    const wait = panel.querySelector('[data-explain-wait]');
                    if (wait) wait.remove();
                    return;
                }
                const words = Array.isArray(explain.words) ? explain.words : [];
                if (words.length) {
                    wordsBody.innerHTML = words.map(function () {
                        return '<tr class="odd:bg-white even:bg-slate-50"><td class="px-3 py-2 font-semibold text-slate-900"></td><td class="px-3 py-2 text-slate-700"></td></tr>';
                    }).join('');
                    wordsBody.querySelectorAll('tr').forEach(function (tr, index) {
                        const cells = tr.children;
                        cells[0].textContent = words[index].word || '';
                        cells[1].textContent = capitalEnglish(words[index].meaning || '');
                    });
                } else {
                    const wait = panel.querySelector('[data-explain-wait]');
                    if (wait) wait.remove();
                }
                renderExplainLines(panel, Array.isArray(explain.lines) ? explain.lines : []);
                if (!more) return;
                const blocks = [];
                const important = Array.isArray(explain.important_words) ? explain.important_words : [];
                if (important.length) {
                    blocks.push('<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><h4 class="text-sm font-bold text-sky-800">5. Important words</h4><ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-800">'
                        + important.map(function (row) {
                            return '<li><strong></strong></li>';
                        }).join('') + '</ul></div>');
                }
                more.innerHTML = blocks.join('');
                more.querySelectorAll('li').forEach(function (li, index) {
                    const row = important[index] || {};
                    li.querySelector('strong').textContent = (row.word || '') + ' — ' + capitalEnglish(row.meaning || '');
                });
                const exp = explain.explanation || {};
                const notes = [
                    ['What does it mean?', exp.meaning],
                    ['Main idea', exp.main_idea],
                    ['Why is it important?', exp.why_important],
                    ['What should the student remember?', exp.remember]
                ].filter(function (row) { return row[1]; });
                if (notes.length) {
                    const box = document.createElement('div');
                    box.className = 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm';
                    box.innerHTML = '<h4 class="text-sm font-bold text-sky-800">6. Simple English explanation</h4>';
                    notes.forEach(function (row) {
                        const p = document.createElement('p');
                        p.className = 'mt-3 text-sm leading-6 text-slate-800';
                        const label = document.createElement('span');
                        label.className = 'font-semibold';
                        label.textContent = row[0];
                        p.appendChild(label);
                        p.appendChild(document.createElement('br'));
                        p.appendChild(document.createTextNode(capitalEnglish(row[1])));
                        box.appendChild(p);
                    });
                    more.appendChild(box);
                }
            }).catch(function () {
                const wait = panel.querySelector('[data-explain-wait]');
                if (wait) wait.remove();
            }).finally(function () {
                panel.dataset.loading = '0';
                panel.dataset.loaded = '1';
            });
        }
        function openExplain(scroll) {
            const panel = document.getElementById('sanskrit-explain');
            if (!panel) {
                return;
            }
            panel.classList.remove('hidden');
            document.querySelectorAll('[data-explain-open]').forEach(function (button) {
                button.setAttribute('aria-expanded', 'true');
            });
            loadExplain(panel);
            if (scroll) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        document.querySelectorAll('[data-explain-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                const panel = document.getElementById('sanskrit-explain');
                if (!panel) {
                    return;
                }
                if (panel.classList.contains('hidden')) {
                    openExplain(true);
                    return;
                }
                panel.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            });
        });
        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[href="#sanskrit-explain"]');
            if (!link) {
                return;
            }
            event.preventDefault();
            openExplain(true);
        });
    </script>
@endonce

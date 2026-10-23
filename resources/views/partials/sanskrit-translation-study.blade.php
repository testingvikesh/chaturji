@php
    $study = $translationStudy ?? null;
@endphp
@if (is_array($study))
    <section id="sanskrit-translate" class="admin-card mb-6 overflow-hidden">
        <div class="admin-card-top"></div>
        @if (($study['pairs'] ?? []) !== [])
            <header class="material-question-group-header">
                <h3 class="font-bold text-slate-900 text-lg">{{ $study['title'] ?? 'Translate' }} ({{ count($study['pairs']) }})</h3>
            </header>
            <ol class="divide-y divide-amber-100">
                @foreach ($study['pairs'] as $pair)
                    <li class="px-5 py-3 border-l-4 border-amber-300">
                        <p class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $pair['source'] }}</p>
                        @if (($pair['translation'] ?? '') !== '')
                            <p class="mt-1 pl-4 text-sm text-slate-700">• {{ $pair['translation'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    @if (($study['original'] ?? []) !== [] || ($study['lines'] ?? []) !== [])
        <section id="sanskrit-explain" class="admin-card mb-6 overflow-hidden"
            @if (($study['source'] ?? '') !== '') data-sanskrit-explain data-explain-url="{{ route('sanskrit-explain') }}" @endif>
            <div class="admin-card-top"></div>
            <input type="hidden" data-explain-token value="{{ csrf_token() }}">
            <textarea hidden data-explain-source>{{ $study['source'] ?? '' }}</textarea>
            <header class="material-question-group-header">
                <h3 class="font-bold text-slate-900 text-lg">Explain</h3>
            </header>
            <div class="space-y-6 px-5 py-5">
                @if (($study['original'] ?? []) !== [])
                    <div>
                        <h4 class="font-bold text-slate-900">1. Original Text</h4>
                        <ol class="mt-2 space-y-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                            @foreach ($study['original'] as $line)
                                <li>{{ $loop->iteration }}. {{ $line }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                <div>
                    <h4 class="font-bold text-slate-900">2. Word-to-word English translation</h4>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-left">
                                    <th class="border border-slate-200 px-3 py-2 font-semibold">Original Word</th>
                                    <th class="border border-slate-200 px-3 py-2 font-semibold">English Meaning</th>
                                </tr>
                            </thead>
                            <tbody data-explain-words>
                                @forelse ($study['words'] ?? [] as $word)
                                    <tr>
                                        <td class="border border-slate-200 px-3 py-1.5 font-semibold">{{ $word['word'] }}</td>
                                        <td class="border border-slate-200 px-3 py-1.5">{{ $word['meaning'] }}</td>
                                    </tr>
                                @empty
                                    <tr data-explain-wait>
                                        <td class="border border-slate-200 px-3 py-2 text-slate-500" colspan="2">Preparing the English explain…</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if (($study['lines'] ?? []) !== [])
                    <div>
                        <h4 class="font-bold text-slate-900">3. Line-to-line English translation</h4>
                        <div class="mt-2 space-y-4">
                            @foreach ($study['lines'] as $line)
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Line {{ $loop->iteration }}</p>
                                    @if (($line['original'] ?? '') !== '')
                                        <p class="mt-1 text-sm text-slate-900"><span class="font-semibold">Original:</span> {{ $line['original'] }}</p>
                                    @endif
                                    @if (($line['translation'] ?? '') !== '')
                                        <p class="text-sm text-slate-700"><span class="font-semibold">English Translation:</span> {{ $line['translation'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (($study['paragraph'] ?? '') !== '')
                    <div>
                        <h4 class="font-bold text-slate-900">4. Full paragraph English translation</h4>
                        <p class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $study['paragraph'] }}</p>
                    </div>
                @endif

                <div data-explain-more class="space-y-6"></div>
            </div>
        </section>
    @endif
@endif

@once
    <script>
        document.querySelectorAll('[data-sanskrit-explain]').forEach(function (panel) {
            const sourceNode = panel.querySelector('[data-explain-source]');
            const source = sourceNode ? sourceNode.value : '';
            const url = panel.getAttribute('data-explain-url') || '';
            if (!source || !url) return;
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
                    wordsBody.innerHTML = words.map(function (row) {
                        return '<tr><td class="border border-slate-200 px-3 py-1.5 font-semibold"></td><td class="border border-slate-200 px-3 py-1.5"></td></tr>';
                    }).join('');
                    wordsBody.querySelectorAll('tr').forEach(function (tr, index) {
                        const cells = tr.children;
                        cells[0].textContent = words[index].word || '';
                        cells[1].textContent = words[index].meaning || '';
                    });
                } else {
                    const wait = panel.querySelector('[data-explain-wait]');
                    if (wait) wait.remove();
                }
                if (!more) return;
                const blocks = [];
                const important = Array.isArray(explain.important_words) ? explain.important_words : [];
                if (important.length) {
                    blocks.push('<div><h4 class="font-bold text-slate-900">5. Important words</h4><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-800">'
                        + important.map(function (row) {
                            return '<li><strong></strong></li>';
                        }).join('') + '</ul></div>');
                }
                more.innerHTML = blocks.join('');
                more.querySelectorAll('li').forEach(function (li, index) {
                    const row = important[index] || {};
                    li.querySelector('strong').textContent = (row.word || '') + ' — ' + (row.meaning || '');
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
                    box.innerHTML = '<h4 class="font-bold text-slate-900">6. Simple English explanation</h4>';
                    notes.forEach(function (row) {
                        const p = document.createElement('p');
                        p.className = 'mt-2 text-sm text-slate-800';
                        const label = document.createElement('span');
                        label.className = 'font-semibold';
                        label.textContent = row[0];
                        p.appendChild(label);
                        p.appendChild(document.createElement('br'));
                        p.appendChild(document.createTextNode(row[1]));
                        box.appendChild(p);
                    });
                    more.appendChild(box);
                }
            }).catch(function () {
                const wait = panel.querySelector('[data-explain-wait]');
                if (wait) wait.remove();
            });
        });
    </script>
@endonce

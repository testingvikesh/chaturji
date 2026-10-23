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

        @if (($study['original'] ?? []) !== [] || ($study['words'] ?? []) !== [] || ($study['lines'] ?? []) !== [])
            <div class="border-t border-slate-100 px-5 py-4">
                <span class="inline-flex rounded-full bg-sky-600 px-3 py-1 text-xs font-bold text-white">{{ $study['language'] ?? 'English' }}</span>
            </div>
            <div class="space-y-6 px-5 pb-5">
                @if (($study['original'] ?? []) !== [])
                    <div>
                        <h4 class="font-bold text-slate-900">1. Original Text</h4>
                        <ol class="mt-2 space-y-1 text-sm text-slate-800">
                            @foreach ($study['original'] as $line)
                                <li>{{ $loop->iteration }}. {{ $line }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if (($study['words'] ?? []) !== [])
                    <div>
                        <h4 class="font-bold text-slate-900">2. Word-to-word English translation</h4>
                        <div class="mt-2 overflow-x-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-left">
                                        <th class="border border-slate-200 px-3 py-2 font-semibold">Word</th>
                                        <th class="border border-slate-200 px-3 py-2 font-semibold">English Meaning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($study['words'] as $word)
                                        <tr>
                                            <td class="border border-slate-200 px-3 py-1.5">{{ $word['word'] }}</td>
                                            <td class="border border-slate-200 px-3 py-1.5">{{ $word['meaning'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

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
            </div>
        @endif
    </section>
@endif

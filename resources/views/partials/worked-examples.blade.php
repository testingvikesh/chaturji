@props([
    'examples',
    'practiceMode' => false,
    'heading' => 'Solved Examples',
    'filterByPage' => false,
    'pages' => [],
])

@php
    use App\Support\MaterialWorkedExamples;

    $examples = collect($examples ?? []);
    $groups = $examples->groupBy(function ($row) {
        $page = MaterialWorkedExamples::normalizePage($row['page'] ?? '');

        return $page !== '' ? $page : '0';
    })->mapWithKeys(fn ($items, $key) => [(string) $key => $items]);
    $pages = collect($pages ?? [])->isNotEmpty()
        ? collect($pages)->map(fn ($page) => (string) $page)->values()
        : $groups->keys()->reject(fn ($page) => (string) $page === '0')->values();
@endphp

@if ($examples->isNotEmpty())
    <section class="worked-examples" id="worked-examples">
        <header class="worked-examples-head">
            <div>
                <h3 class="worked-examples-title">
                    {{ $heading }}
                    @if ($filterByPage)
                        <span class="worked-examples-page-title">· Page <span x-text="currentPage"></span></span>
                    @endif
                </h3>
                <p class="worked-examples-sub">
                    @if ($filterByPage)
                        Click a page number above to view that page's examples
                    @else
                        {{ $examples->count() }} {{ Str::plural('example', $examples->count()) }} in this chapter
                    @endif
                </p>
            </div>
            @if ($filterByPage)
                @foreach ($pages as $page)
                    <span class="admin-badge-green" x-show="isPage(@js((string) $page))" x-cloak>
                        {{ (int) ($groups->get($page)?->count() ?? 0) }}
                    </span>
                @endforeach
            @else
                <span class="admin-badge-green">{{ $examples->count() }}</span>
            @endif
        </header>

        <nav class="worked-examples-toc" aria-label="Examples">
            @foreach ($examples as $row)
                <a href="#{{ $row['uid'] }}"
                   class="worked-examples-toc-link"
                   @if ($filterByPage) x-show="isPage(@js((string) (MaterialWorkedExamples::normalizePage($row['page'] ?? '') ?: '0')))" x-cloak @endif
                >{{ $row['label'] }}</a>
            @endforeach
        </nav>

        @foreach ($groups as $page => $pageExamples)
            <div @if ($filterByPage) x-show="isPage(@js((string) $page))" x-cloak @endif>
                @if ($page !== '0' && ! $filterByPage)
                    <p class="worked-examples-page-label">Page {{ $page }}</p>
                @endif

                @if ($filterByPage && $pageExamples->isEmpty())
                    @continue
                @endif

                @foreach ($pageExamples as $row)
                    @php
                        $item = $row['item'] ?? [];
                        $given = trim((string) ($item['given'] ?? ''));
                        $find = trim((string) ($item['find'] ?? ''));
                        $formula = trim((string) ($item['formula'] ?? ''));
                        $concept = trim((string) ($item['concept'] ?? ''));
                        $overview = trim((string) ($item['solution_overview'] ?? ''));
                        $toProve = trim((string) ($item['to_prove'] ?? ''));
                        $construction = trim((string) ($item['construction'] ?? ''));
                        $conclusion = trim((string) ($item['conclusion'] ?? ''));
                        $shortcut = trim((string) ($item['shortcut'] ?? ''));
                        $answer = trim((string) ($item['final_answer'] ?? ''));
                        $question = trim((string) ($item['question_text'] ?? ''));
                        $steps = is_array($item['solution'] ?? null) ? $item['solution'] : [];
                        $figure = is_array($item['figure'] ?? null) ? $item['figure'] : [];
                        $hasFigure = filled($figure['shape'] ?? null) && ! in_array(strtolower((string) $figure['shape']), ['', 'none'], true);
                        $blockNo = 0;
                        $stepColors = ['blue', 'purple', 'green', 'teal', 'orange', 'gold'];
                    @endphp

                    <article id="{{ $row['uid'] }}" class="ex-card scroll-mt-36 lg:scroll-mt-32"
                             x-data="{ open: {{ $practiceMode ? 'false' : 'true' }} }">
                        <header class="ex-card-head">
                            <span class="ex-card-label">{{ $row['label'] }}</span>
                            @if (! empty($item['difficulty']))
                                <span class="ex-card-diff">{{ ucfirst((string) $item['difficulty']) }}</span>
                            @endif
                        </header>

                        @if ($question !== '')
                            <div class="ex-card-question">
                                @include('partials.worked-example-points', ['text' => $question])
                            </div>
                        @endif

                        <div class="ex-flow">
                            @if ($find !== '')
                                @php $blockNo++; @endphp
                                <section class="ex-block ex-block--find">
                                    <span class="ex-block-pill">{{ $blockNo }}. Find</span>
                                    @include('partials.worked-example-points', ['text' => $find])
                                </section>
                            @endif

                            @if ($given !== '')
                                @php $blockNo++; @endphp
                                <section class="ex-block ex-block--given">
                                    <span class="ex-block-pill">{{ $blockNo }}. Given</span>
                                    @include('partials.worked-example-points', ['text' => $given])
                                </section>
                            @endif

                            @if ($toProve !== '')
                                @php $blockNo++; @endphp
                                <section class="ex-block ex-block--prove">
                                    <span class="ex-block-pill">{{ $blockNo }}. To prove</span>
                                    @include('partials.worked-example-points', ['text' => $toProve])
                                </section>
                            @endif

                            @if ($formula !== '' || $concept !== '')
                                @php $blockNo++; @endphp
                                <section class="ex-block ex-block--formula">
                                    <span class="ex-block-pill">{{ $blockNo }}. Formula / Concept</span>
                                    <div class="ex-block-body">
                                        @if ($formula !== '')
                                            @include('partials.worked-example-points', ['text' => $formula, 'asFormula' => true])
                                        @endif
                                        @if ($concept !== '')
                                            @include('partials.worked-example-points', ['text' => $concept])
                                        @endif
                                    </div>
                                </section>
                            @endif

                            @if ($hasFigure)
                                @php $blockNo++; @endphp
                                <section class="ex-block ex-block--given">
                                    <span class="ex-block-pill">{{ $blockNo }}. Figure</span>
                                    <div class="ex-block-body">
                                        {{ ucfirst((string) $figure['shape']) }}
                                        @if (filled($figure['base'] ?? null)) · base {{ $figure['base'] }} @endif
                                        @if (filled($figure['height'] ?? null)) · height {{ $figure['height'] }} @endif
                                        @if (filled($figure['radius'] ?? null)) · radius {{ $figure['radius'] }} @endif
                                        @if (filled($figure['side'] ?? null)) · side {{ $figure['side'] }} @endif
                                    </div>
                                </section>
                            @endif

                            @if ($practiceMode)
                                <button type="button"
                                        class="ex-toggle"
                                        @click="open = !open"
                                        x-text="open ? 'Hide solution' : 'Show solution'"></button>
                            @endif

                            <div class="ex-solution" x-show="open" @if ($practiceMode) x-cloak @endif>
                                @if ($overview !== '')
                                    @php $blockNo++; @endphp
                                    <section class="ex-block ex-block--solution">
                                        <span class="ex-block-pill">{{ $blockNo }}. Solution</span>
                                        @include('partials.worked-example-points', ['text' => $overview])
                                    </section>
                                @endif

                                @if ($construction !== '')
                                    @php $blockNo++; @endphp
                                    <section class="ex-block ex-block--solution">
                                        <span class="ex-block-pill">{{ $blockNo }}. Construction</span>
                                        @include('partials.worked-example-points', ['text' => $construction])
                                    </section>
                                @endif

                                @if ($steps !== [])
                                    @php $blockNo++; @endphp
                                    <section class="ex-block ex-block--steps">
                                        <span class="ex-block-pill">{{ $blockNo }}. Step-by-step</span>
                                        <ol class="ex-steps">
                                            @foreach ($steps as $step)
                                                @php
                                                    $rawEquation = (string) ($step['equation'] ?? '');
                                                    $equation = MaterialWorkedExamples::latex($rawEquation);
                                                    $equationIsMath = MaterialWorkedExamples::looksLikeLatex($rawEquation);
                                                    $note = trim((string) ($step['note'] ?? ''));
                                                    $isFinal = (bool) ($step['final'] ?? false);
                                                    $stepNo = (int) ($step['step'] ?? $loop->iteration);
                                                    $color = $isFinal ? 'gold' : $stepColors[($stepNo - 1) % count($stepColors)];
                                                @endphp
                                                <li class="ex-step ex-step--{{ $color }} {{ $isFinal ? 'ex-step--final' : '' }}">
                                                    <span class="ex-step-badge">{{ $stepNo }}</span>
                                                    <div class="ex-step-main">
                                                        <p class="ex-step-kicker">
                                                            Step {{ $stepNo }}{{ $isFinal ? ' — Final' : '' }}
                                                        </p>
                                                        @if ($equation !== '')
                                                            <div class="ex-step-eq">
                                                                @if ($equationIsMath)
                                                                    \( {{ $equation }} \)
                                                                @else
                                                                    {{ MaterialWorkedExamples::readable($rawEquation) }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                        @if ($note !== '')
                                                            <div class="ex-step-note">
                                                                @include('partials.worked-example-points', ['text' => $note])
                                                            </div>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </section>
                                @endif

                                @if ($shortcut !== '')
                                    @php $blockNo++; @endphp
                                    <section class="ex-block ex-block--formula">
                                        <span class="ex-block-pill">{{ $blockNo }}. Shortcut</span>
                                        @include('partials.worked-example-points', ['text' => $shortcut])
                                    </section>
                                @endif

                                @if ($conclusion !== '')
                                    @php $blockNo++; @endphp
                                    <section class="ex-block ex-block--solution">
                                        <span class="ex-block-pill">{{ $blockNo }}. Conclusion</span>
                                        @include('partials.worked-example-points', ['text' => $conclusion])
                                    </section>
                                @endif

                                @if ($answer !== '')
                                    @php $blockNo++; @endphp
                                    <section class="ex-block ex-block--answer">
                                        <span class="ex-block-pill">{{ $blockNo }}. Final answer</span>
                                        <div class="ex-answer-box">
                                            @include('partials.worked-example-points', ['text' => $answer, 'asFormula' => MaterialWorkedExamples::looksLikeLatex($answer)])
                                        </div>
                                    </section>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endforeach
    </section>

    @once
        <style>
            .worked-examples {
                margin-top: .5rem;
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            }
            .worked-examples-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.75rem; }
            .worked-examples-title { font-size:1.125rem; font-weight:700; color:#0f172a; }
            .worked-examples-sub { font-size:.75rem; color:#64748b; margin-top:.125rem; }
            .worked-examples-toc { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
            .worked-examples-toc-link { display:inline-flex; height:2.25rem; align-items:center; border-radius:9999px; border:1px solid #bbf7d0; background:#fff; padding:0 .75rem; font-size:.75rem; font-weight:600; color:#15803d; text-decoration:none; }
            .worked-examples-toc-link:hover { background:#15803d; color:#fff; }
            .worked-examples-page-title { font-weight:700; color:#15803d; }

            .ex-card {
                background:#fff;
                border:1px solid #e2e8f0;
                border-radius:1.25rem;
                padding:1.1rem 1.1rem 1.25rem;
                margin-bottom:1.25rem;
                box-shadow:0 10px 30px -18px rgba(15,23,42,.35);
            }
            .ex-card-head { display:flex; align-items:center; gap:.5rem; margin-bottom:.6rem; }
            .ex-card-label {
                display:inline-flex; align-items:center; border-radius:9999px;
                background:linear-gradient(135deg,#0f766e,#15803d);
                color:#fff; font-size:.75rem; font-weight:800; letter-spacing:.02em;
                padding:.28rem .8rem;
            }
            .ex-card-diff { font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; }
            .ex-card-question { margin:0 0 .9rem; }
            .ex-card-question .ex-points-text { font-size:1.02rem; font-weight:700; color:#0f172a; line-height:1.65; }
            .ex-flow { display:flex; flex-direction:column; gap:.7rem; }

            .ex-block { border-radius:1rem; padding:.85rem 1rem .95rem; border:1px solid transparent; }
            .ex-block-pill {
                display:inline-flex; align-items:center; border-radius:9999px;
                color:#fff; font-size:11px; font-weight:800; letter-spacing:.04em;
                text-transform:uppercase; padding:.22rem .7rem; margin-bottom:.55rem;
            }
            .ex-block-body { font-size:.95rem; line-height:1.7; color:#1e293b; }
            .ex-block-note { margin:.45rem 0 0; font-size:.9rem; line-height:1.65; color:#475569; }
            .ex-formula { font-size:1.05rem; font-weight:700; color:#1e1b4b; overflow-x:auto; word-spacing:.28em; letter-spacing:.03em; }
            .ex-points { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.4rem; }
            .ex-points li {
                display:flex; gap:.55rem; align-items:flex-start;
                background:rgba(255,255,255,.62);
                border-radius:.75rem;
                padding:.5rem .65rem;
            }
            .ex-points-no {
                flex-shrink:0; width:1.4rem; height:1.4rem; border-radius:999px;
                background:rgba(15,23,42,.08); color:#0f172a;
                font-size:.7rem; font-weight:800;
                display:flex; align-items:center; justify-content:center;
                margin-top:.12rem;
            }
            .ex-points-text { font-size:.95rem; line-height:1.8; color:#1e293b; word-spacing:.28em; letter-spacing:.03em; }
            .ex-step-note .ex-points li { background:#fff; border:1px solid #e2e8f0; }
            .ex-step-note .ex-points-text { font-size:.88rem; font-weight:500; color:#475569; }
            .ex-answer-box .ex-points li { background:#fff7ed; border:1px solid #fde68a; }
            .ex-answer-box .ex-points-text, .ex-answer-box .ex-formula { font-size:1.02rem; font-weight:800; color:#92400e; }

            .ex-block--find { background:#eff6ff; border-color:#bfdbfe; }
            .ex-block--find .ex-block-pill { background:#2563eb; }
            .ex-block--given { background:#ecfdf5; border-color:#a7f3d0; }
            .ex-block--given .ex-block-pill { background:#059669; }
            .ex-block--prove { background:#f5f3ff; border-color:#ddd6fe; }
            .ex-block--prove .ex-block-pill { background:#7c3aed; }
            .ex-block--formula { background:#f5f3ff; border-color:#ddd6fe; }
            .ex-block--formula .ex-block-pill { background:#7c3aed; }
            .ex-block--solution { background:#fff7ed; border-color:#fed7aa; }
            .ex-block--solution .ex-block-pill { background:#ea580c; }
            .ex-block--steps { background:#f8fafc; border-color:#e2e8f0; }
            .ex-block--steps .ex-block-pill { background:#0f172a; }
            .ex-block--answer { background:#fffbeb; border-color:#f59e0b; box-shadow:inset 0 0 0 1px rgba(245,158,11,.2); }
            .ex-block--answer .ex-block-pill { background:#d97706; }

            .ex-toggle {
                align-self:flex-start; height:2.35rem; border-radius:9999px; border:1px solid #fcd34d;
                background:#fffbeb; padding:0 .9rem; font-size:.8rem; font-weight:700; color:#92400e; cursor:pointer;
            }

            .ex-steps { list-style:none; margin:.35rem 0 0; padding:0; display:flex; flex-direction:column; gap:.65rem; }
            .ex-step { display:flex; gap:.75rem; align-items:flex-start; padding:.2rem 0; }
            .ex-step-badge {
                flex-shrink:0; width:2rem; height:2rem; border-radius:9999px; color:#fff;
                display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:800;
                box-shadow:0 6px 14px -8px rgba(15,23,42,.5);
            }
            .ex-step-main { min-width:0; flex:1; }
            .ex-step-kicker { margin:0 0 .35rem; font-size:11px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#64748b; }
            .ex-step-eq {
                background:#fff; border:1px solid #e2e8f0; border-radius:.85rem; padding:.7rem .9rem;
                font-size:1.05rem; font-weight:700; color:#0f172a; overflow-x:auto; text-align:center;
                word-spacing:.32em; letter-spacing:.04em; line-height:1.85;
            }
            .ex-step-note { margin:.4rem 0 0; font-size:.88rem; line-height:1.6; color:#64748b; }

            .ex-step--blue .ex-step-badge { background:#1d4ed8; }
            .ex-step--purple .ex-step-badge { background:#6d28d9; }
            .ex-step--green .ex-step-badge { background:#047857; }
            .ex-step--teal .ex-step-badge { background:#0f766e; }
            .ex-step--orange .ex-step-badge { background:#c2410c; }
            .ex-step--gold .ex-step-badge { background:#d97706; }
            .ex-step--final .ex-step-eq { border-color:#f59e0b; background:#fffbeb; color:#92400e; }

            .ex-answer-box {
                background:#fff; border:1.5px solid #f59e0b; border-radius:.9rem;
                padding:.85rem 1rem; font-size:1.05rem; font-weight:800; color:#92400e;
                line-height:1.9; overflow-x:auto; word-spacing:.32em; letter-spacing:.04em;
            }

            @media (min-width: 640px) {
                .ex-card { padding:1.25rem 1.35rem 1.4rem; }
                .ex-step-eq { font-size:1.12rem; }
            }
        </style>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" crossorigin="anonymous">
        <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" crossorigin="anonymous"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function renderWorkedExampleMath() {
                if (!window.renderMathInElement) {
                    setTimeout(renderWorkedExampleMath, 60);
                    return;
                }
                document.querySelectorAll('.worked-examples').forEach(function (el) {
                    window.renderMathInElement(el, {
                        delimiters: [
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true},
                            {left: '$', right: '$', display: false}
                        ],
                        throwOnError: false
                    });
                });
            });
        </script>
    @endonce
@endif

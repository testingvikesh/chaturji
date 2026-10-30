<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Paper Generate" subtitle="Quantities follow the paper style. Only important questions are used." />
    </x-slot>

    <div class="admin-page">
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.paper-generate.store') }}" class="space-y-6">
            @csrf
            <div class="admin-card space-y-4 p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="standard_id">Standard</label>
                        <select id="standard_id" name="standard_id" class="admin-select w-full" required>
                            <option value="">Select standard</option>
                            @foreach ($standards as $standard)
                                <option value="{{ $standard->id }}" @selected((string) old('standard_id') === (string) $standard->id)>{{ $standard->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="subject_id">Subject</label>
                        <select id="subject_id" name="subject_id" class="admin-select w-full" required>
                            <option value="">Select subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" data-standard="{{ $subject->standard_id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p id="style-note" class="text-sm text-amber-800" hidden>No paper style is saved for this subject. Enter the quantities yourself.</p>

                <div>
                    <label class="admin-label" for="title">Title</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" required class="w-full rounded-xl border-slate-200">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="duration_minutes">Duration (minutes)</label>
                        <input id="duration_minutes" type="number" name="duration_minutes" min="1" max="300" value="{{ old('duration_minutes') }}" class="w-full rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="admin-label" for="instructions">Instructions</label>
                        <textarea id="instructions" name="instructions" rows="2" class="w-full rounded-xl border-slate-200">{{ old('instructions') }}</textarea>
                    </div>
                </div>

                <div>
                    <p class="admin-label">Chapters</p>
                    <div id="chapter-list" class="max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3 text-sm text-slate-500">Select a subject to load chapters.</div>
                    <p class="mt-1 text-xs text-slate-500">Leave every chapter unticked to use all chapters. The paper uses only questions marked IMP.</p>
                </div>
            </div>

            <div class="admin-card space-y-4 p-5 sm:p-6">
                @php
                    $objective = [];
                    $subjective = [];
                    foreach ($paperTypes as $type => $label) {
                        if (\App\Support\SubjectiveQuestionTypes::isSubjective($type)) {
                            $subjective[$type] = $label;
                        } else {
                            $objective[$type] = $label;
                        }
                    }
                    $oldCounts = old('type_counts', []);
                @endphp
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach (['objective' => $objective, 'subjective' => $subjective] as $group => $types)
                        <div class="rounded-2xl border border-slate-200">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                <h3 class="font-bold text-slate-900">{{ ucfirst($group) }} quantity</h3>
                                <p class="text-sm font-semibold text-brand-green"><span data-group-marks="{{ $group }}">0</span> marks</p>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @foreach ($types as $type => $label)
                                    @php $each = (int) ($marksEach[$type] ?? 1); @endphp
                                    <label class="flex items-center justify-between gap-3 px-4 py-3">
                                        <span class="min-w-0 text-sm font-medium text-slate-800">
                                            <span class="mr-1">{{ \App\Support\PaperTypeHelper::icon($type) }}</span>{{ $label }}
                                            <span class="block text-xs font-normal text-slate-500">{{ $each }} mark{{ $each === 1 ? '' : 's' }} each</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-3">
                                            <input type="number" name="type_counts[{{ $type }}]" min="0" max="200" value="{{ (int) ($oldCounts[$type] ?? 0) }}" data-type-count data-type="{{ $type }}" data-group="{{ $group }}" data-each="{{ $each }}" class="w-24 rounded-lg border-slate-200 text-center">
                                            <span class="w-16 text-right text-sm font-semibold text-slate-700" data-row-marks>0</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">
                    Paper total: <span class="text-base font-bold" data-paper-marks>0</span> marks
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-brand-green px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-dark">Generate paper</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const styles = @json($styleCounts);
            const standard = document.getElementById('standard_id');
            const subject = document.getElementById('subject_id');
            const note = document.getElementById('style-note');
            const chapterList = document.getElementById('chapter-list');
            const chaptersUrl = @json(route('admin.paper-generate.chapters'));
            const oldChapters = @json(array_map('strval', (array) old('chapter_ids', [])));
            const countInputs = Array.from(document.querySelectorAll('[data-type-count]'));
            const subjectOptions = subject ? Array.from(subject.options) : [];

            function refreshMarks() {
                const groups = { objective: 0, subjective: 0 };
                countInputs.forEach(function (input) {
                    const count = Math.max(0, parseInt(input.value, 10) || 0);
                    const each = Math.max(1, parseInt(input.getAttribute('data-each'), 10) || 1);
                    const marks = count * each;
                    const row = input.parentElement ? input.parentElement.querySelector('[data-row-marks]') : null;
                    if (row) row.textContent = String(marks);
                    const group = input.getAttribute('data-group');
                    if (groups[group] !== undefined) groups[group] += marks;
                });
                Object.keys(groups).forEach(function (group) {
                    const node = document.querySelector('[data-group-marks="' + group + '"]');
                    if (node) node.textContent = String(groups[group]);
                });
                const total = document.querySelector('[data-paper-marks]');
                if (total) total.textContent = String(groups.objective + groups.subjective);
            }

            function applyStyle() {
                const counts = styles[subject.value] || null;
                countInputs.forEach(function (input) {
                    const type = input.getAttribute('data-type');
                    input.value = counts && counts[type] ? counts[type] : 0;
                });
                if (note) note.hidden = !subject.value || !!counts;
                refreshMarks();
            }

            function filterSubjects() {
                const selected = standard.value;
                let keep = false;
                subjectOptions.forEach(function (option, index) {
                    if (index === 0) return;
                    const show = selected !== '' && option.getAttribute('data-standard') === selected;
                    option.hidden = !show;
                    option.disabled = !show;
                    if (show && option.value === subject.value) keep = true;
                });
                if (!keep) subject.value = '';
            }

            function loadChapters() {
                chapterList.textContent = subject.value ? 'Loading chapters…' : 'Select a subject to load chapters.';
                if (!subject.value) return;
                fetch(chaptersUrl + '?subject_id=' + encodeURIComponent(subject.value), { headers: { 'Accept': 'application/json' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        const chapters = Array.isArray(data.chapters) ? data.chapters : [];
                        chapterList.textContent = '';
                        if (!chapters.length) {
                            chapterList.textContent = 'No chapters for this subject.';
                            return;
                        }
                        chapters.forEach(function (chapter) {
                            const label = document.createElement('label');
                            label.className = 'flex items-center gap-2 text-slate-800';
                            const box = document.createElement('input');
                            box.type = 'checkbox';
                            box.name = 'chapter_ids[]';
                            box.value = String(chapter.id);
                            box.className = 'rounded border-slate-300 text-brand-green focus:ring-brand-green';
                            if (oldChapters.indexOf(String(chapter.id)) !== -1) box.checked = true;
                            const text = document.createElement('span');
                            text.textContent = chapter.name || ('Chapter ' + chapter.id);
                            label.appendChild(box);
                            label.appendChild(text);
                            chapterList.appendChild(label);
                        });
                    })
                    .catch(function () {
                        chapterList.textContent = 'Could not load chapters.';
                    });
            }

            countInputs.forEach(function (input) { input.addEventListener('input', refreshMarks); });
            standard.addEventListener('change', function () {
                filterSubjects();
                applyStyle();
                loadChapters();
            });
            subject.addEventListener('change', function () {
                applyStyle();
                loadChapters();
            });
            filterSubjects();
            if (!@json(old('type_counts') !== null)) applyStyle();
            else refreshMarks();
            loadChapters();
        })();
    </script>
</x-app-layout>

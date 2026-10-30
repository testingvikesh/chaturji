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
@endphp

@php
    $multiple = $multiple ?? false;
    $selectedStandardIds = array_map('strval', (array) old('standard_ids', $selectedStandard ? [$selectedStandard] : []));
    $selectedSubjectIds = array_map('strval', (array) old('subject_ids', $selectedSubject ? [$selectedSubject] : []));
@endphp

@if ($multiple)
    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            <p class="admin-label">Standards</p>
            <div class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" id="standards-all" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                    All standards
                </label>
                @foreach ($standards as $standard)
                    <label class="flex items-center gap-2 text-sm text-slate-800">
                        <input type="checkbox" name="standard_ids[]" value="{{ $standard->id }}" class="js-standard rounded border-slate-300 text-brand-green focus:ring-brand-green" @checked(in_array((string) $standard->id, $selectedStandardIds, true))>
                        {{ $standard->name }}
                    </label>
                @endforeach
            </div>
            @error('standard_ids')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <p class="admin-label">Subjects</p>
            <div class="max-h-72 space-y-3 overflow-y-auto rounded-xl border border-slate-200 p-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" id="subjects-all" class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
                    All shown subjects
                </label>
                @foreach ($standards as $standard)
                    @php $standardSubjects = $subjects->where('standard_id', $standard->id); @endphp
                    @if ($standardSubjects->isNotEmpty())
                        <div class="js-subject-group space-y-2" data-standard="{{ $standard->id }}" hidden>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $standard->name }}</p>
                            @foreach ($standardSubjects as $subject)
                                <label class="flex items-center gap-2 text-sm text-slate-800">
                                    <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" class="js-subject rounded border-slate-300 text-brand-green focus:ring-brand-green" @checked(in_array((string) $subject->id, $selectedSubjectIds, true))>
                                    {{ $subject->name }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
            @error('subject_ids')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
@else
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="admin-label" for="standard_id">Standard</label>
        <select id="standard_id" name="standard_id" class="admin-select w-full" required>
            <option value="">Select standard</option>
            @foreach ($standards as $standard)
                <option value="{{ $standard->id }}" @selected((string) $selectedStandard === (string) $standard->id)>{{ $standard->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="admin-label" for="subject_id">Subject</label>
        <select id="subject_id" name="subject_id" class="admin-select w-full" required>
            <option value="">Select subject</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" data-standard="{{ $subject->standard_id }}" @selected((string) $selectedSubject === (string) $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>
</div>
@endif

@error('subject_id')
    <p class="text-sm text-red-600">{{ $message }}</p>
@enderror
@error('type_counts')
    <p class="text-sm text-red-600">{{ $message }}</p>
@enderror

<div class="grid gap-6 lg:grid-cols-2">
    @foreach (['Objective' => $objective, 'Subjective' => $subjective] as $heading => $types)
        <div class="rounded-2xl border border-slate-200">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="font-bold text-slate-900">{{ $heading }} quantity</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($types as $type => $label)
                    <label class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="text-sm font-medium text-slate-800">
                            <span class="mr-1">{{ \App\Support\PaperTypeHelper::icon($type) }}</span>{{ $label }}
                        </span>
                        <input
                            type="number"
                            name="type_counts[{{ $type }}]"
                            min="0"
                            max="200"
                            value="{{ (int) ($counts[$type] ?? 0) }}"
                            class="w-24 rounded-lg border-slate-200 text-center"
                        >
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
    (function () {
        const standardBoxes = Array.from(document.querySelectorAll('.js-standard'));
        if (standardBoxes.length) {
            const groups = Array.from(document.querySelectorAll('.js-subject-group'));
            const allStandards = document.getElementById('standards-all');
            const allSubjects = document.getElementById('subjects-all');
            function selectedStandards() {
                return standardBoxes.filter(function (box) { return box.checked; }).map(function (box) { return box.value; });
            }
            function syncGroups() {
                const chosen = selectedStandards();
                groups.forEach(function (group) {
                    const show = chosen.indexOf(group.getAttribute('data-standard')) !== -1;
                    group.hidden = !show;
                    if (!show) {
                        group.querySelectorAll('.js-subject').forEach(function (box) { box.checked = false; });
                    }
                });
                if (allStandards) {
                    allStandards.checked = standardBoxes.length > 0 && standardBoxes.every(function (box) { return box.checked; });
                }
            }
            standardBoxes.forEach(function (box) { box.addEventListener('change', syncGroups); });
            if (allStandards) {
                allStandards.addEventListener('change', function () {
                    standardBoxes.forEach(function (box) { box.checked = allStandards.checked; });
                    syncGroups();
                });
            }
            if (allSubjects) {
                allSubjects.addEventListener('change', function () {
                    groups.forEach(function (group) {
                        if (group.hidden) return;
                        group.querySelectorAll('.js-subject').forEach(function (box) { box.checked = allSubjects.checked; });
                    });
                });
            }
            syncGroups();
            return;
        }

        const standard = document.getElementById('standard_id');
        const subject = document.getElementById('subject_id');
        if (!standard || !subject) {
            return;
        }
        const options = Array.from(subject.options);
        function filterSubjects() {
            const selected = standard.value;
            let visibleSelected = false;
            options.forEach(function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }
                const show = selected !== '' && option.getAttribute('data-standard') === selected;
                option.hidden = !show;
                option.disabled = !show;
                if (show && option.value === subject.value) {
                    visibleSelected = true;
                }
            });
            if (!visibleSelected) {
                subject.value = '';
            }
        }
        standard.addEventListener('change', filterSubjects);
        filterSubjects();
    })();
</script>

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

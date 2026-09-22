<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header
            title="Login Reports"
            subtitle="Open student or teacher login report for today’s activity and history" />
    </x-slot>

    <div class="admin-page space-y-6">
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('admin.reports.student-logins') }}"
               class="rounded-2xl border border-slate-200 bg-white p-6 hover:border-brand-green-200 hover:shadow-md transition group">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Student</p>
                <h3 class="mt-1 text-xl font-bold text-slate-900 group-hover:text-brand-green">Student Login Report</h3>
                <p class="mt-2 text-sm text-slate-500">Success / failed logins with name, mobile, medium &amp; standard</p>
                <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-slate-50 px-2 py-3">
                        <p class="text-lg font-bold text-slate-900">{{ $studentToday }}</p>
                        <p class="text-[10px] font-semibold uppercase text-slate-400">Today OK</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-2 py-3">
                        <p class="text-lg font-bold text-slate-900">{{ $studentUniqueToday }}</p>
                        <p class="text-[10px] font-semibold uppercase text-slate-400">Unique</p>
                    </div>
                    <div class="rounded-xl bg-red-50 px-2 py-3">
                        <p class="text-lg font-bold text-red-700">{{ $studentFailedToday }}</p>
                        <p class="text-[10px] font-semibold uppercase text-red-400">Failed</p>
                    </div>
                </div>
                <p class="mt-4 text-sm font-semibold text-brand-green">Open student report →</p>
            </a>

            <a href="{{ route('admin.reports.teacher-logins') }}"
               class="rounded-2xl border border-slate-200 bg-white p-6 hover:border-brand-green-200 hover:shadow-md transition group">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Teacher</p>
                <h3 class="mt-1 text-xl font-bold text-slate-900 group-hover:text-brand-green">Teacher Login Report</h3>
                <p class="mt-2 text-sm text-slate-500">Success / failed logins with name, mobile, email &amp; employee code</p>
                <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-slate-50 px-2 py-3">
                        <p class="text-lg font-bold text-slate-900">{{ $teacherToday }}</p>
                        <p class="text-[10px] font-semibold uppercase text-slate-400">Today OK</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-2 py-3">
                        <p class="text-lg font-bold text-slate-900">{{ $teacherUniqueToday }}</p>
                        <p class="text-[10px] font-semibold uppercase text-slate-400">Unique</p>
                    </div>
                    <div class="rounded-xl bg-red-50 px-2 py-3">
                        <p class="text-lg font-bold text-red-700">{{ $teacherFailedToday }}</p>
                        <p class="text-[10px] font-semibold uppercase text-red-400">Failed</p>
                    </div>
                </div>
                <p class="mt-4 text-sm font-semibold text-brand-green">Open teacher report →</p>
            </a>
        </div>
    </div>
</x-app-layout>

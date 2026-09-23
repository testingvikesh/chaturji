@php
    $isDashboard1 = request()->routeIs('admin.dashboard')
        && ! request()->routeIs('admin.dashboard.materials')
        && ! request()->routeIs('admin.dashboard.syllabus');
    $isDashboard3 = request()->routeIs('admin.dashboard.materials');
    $isDashboard4 = request()->routeIs('admin.dashboard.syllabus');
@endphp

<div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4 mb-6 print:hidden">
    <a
        href="{{ route('admin.dashboard') }}"
        class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $isDashboard1 ? 'bg-brand-green text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
    >
        Dashboard 1
    </a>
    <a
        href="{{ route('admin.dashboard.materials') }}"
        class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $isDashboard3 ? 'bg-brand-green text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
    >
        Material Report
    </a>
    <a
        href="{{ route('admin.dashboard.syllabus') }}"
        class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $isDashboard4 ? 'bg-brand-green text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
    >
        Syllabus Dashboard
    </a>
</div>

<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-slate-900 via-slate-900 to-brand-green-darker text-slate-100 hidden lg:flex lg:flex-col shadow-xl">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b border-white/10">
        <img src="{{ asset('images/brand/logo.png') }}" alt="Gses Chaturji" class="h-10 w-10 shrink-0 object-contain drop-shadow-lg">
        <img src="{{ asset('images/brand/ganpati.png') }}" alt="Shree Ganpati" class="h-10 w-10 shrink-0 object-contain drop-shadow-lg">
        <div class="min-w-0">
            <p class="font-bold text-white leading-tight">Admin Panel</p>
            <p class="text-[11px] text-brand-gold/90 font-medium truncate">Homework Manager</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') && ! request()->routeIs('admin.dashboard.materials') && ! request()->routeIs('admin.dashboard.syllabus') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard 1
        </a>
        <a href="{{ route('admin.dashboard.materials') }}" class="{{ request()->routeIs('admin.dashboard.materials') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Material Report
        </a>
        <a href="{{ route('admin.materials.index') }}" class="{{ request()->routeIs('admin.materials.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            All Materials
        </a>
        <a href="{{ route('admin.dashboard.syllabus') }}" class="{{ request()->routeIs('admin.dashboard.syllabus') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Syllabus Dashboard
        </a>

        <p class="admin-sidebar-section">Users</p>
        <a href="{{ route('admin.students.index') }}" class="{{ request()->routeIs('admin.students.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
            Students
        </a>
        <a href="{{ route('admin.teachers.index') }}" class="{{ request()->routeIs('admin.teachers.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Teachers
        </a>
        <a href="{{ route('admin.timetable.index') }}" class="{{ request()->routeIs('admin.timetable.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Teacher Timetable
        </a>

        <p class="admin-sidebar-section">Curriculum</p>
        <a href="{{ route('admin.standards.index') }}" class="{{ request()->routeIs('admin.standards.*', 'admin.subjects.*', 'admin.chapters.*', 'admin.topics.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Standards
        </a>

        <p class="admin-sidebar-section">Reports</p>
        <a href="{{ route('admin.reports.index') }}" class="{{ \App\Support\AdminReportCatalog::isReportsSidebarActive() ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reports
        </a>

        <p class="admin-sidebar-section">System</p>
        <a href="{{ route('admin.prompts.index') }}" class="{{ request()->routeIs('admin.prompts.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Prompts
        </a>
        <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Settings
        </a>
        <a href="{{ route('admin.change-password') }}" class="{{ request()->routeIs('admin.change-password') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Change Password
        </a>
        <a href="{{ route('login') }}" class="admin-sidebar-link-inactive" target="_blank">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            Login Page
        </a>
    </nav>

    <div class="border-t border-white/10 p-4 mt-auto">
        <div class="mb-3 px-2 flex items-center gap-3">
            <span class="admin-avatar text-[10px]">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" onsubmit="return confirm('Are you sure you want to logout?')">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-white/10 hover:bg-white/15 px-3 py-2.5 text-sm font-medium text-slate-200 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </div>
</aside>

<div class="lg:hidden fixed top-0 inset-x-0 z-30 bg-slate-900 text-white shadow-lg">
    <div class="flex items-center gap-2 px-3 h-14">
        <span class="font-bold text-sm shrink-0">Admin</span>
        <div class="flex items-center gap-1.5 text-[11px] overflow-x-auto flex-1 min-w-0">
            <a href="{{ route('admin.dashboard') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Dash 1</a>
            <a href="{{ route('admin.dashboard.materials') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Report</a>
            <a href="{{ route('admin.materials.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Materials</a>
            <a href="{{ route('admin.dashboard.syllabus') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Syllabus</a>
            <a href="{{ route('admin.students.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Students</a>
            <a href="{{ route('admin.teachers.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Teachers</a>
            <a href="{{ route('admin.timetable.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Timetable</a>
            <a href="{{ route('admin.reports.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Reports</a>
            <a href="{{ route('admin.standards.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Curriculum</a>
            <a href="{{ route('admin.prompts.index') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Prompts</a>
            <a href="{{ route('admin.settings.edit') }}" class="px-2 py-1 rounded-lg bg-white/10 whitespace-nowrap">Settings</a>
        </div>
        @include('layouts.partials.logout-icon-button', [
            'action' => route('admin.logout'),
            'tone' => 'dark',
        ])
    </div>
</div>

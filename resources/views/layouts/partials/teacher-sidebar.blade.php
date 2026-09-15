@php
    $unreadNotifications = auth()->user()->unreadNotifications()
        ->where(function ($q) {
            $q->where('data->type', 'otp')->orWhere('data->type', 'ticket');
        })
        ->count();
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-brand-green-darker via-brand-green to-brand-green-light text-white hidden lg:flex lg:flex-col shadow-xl">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b border-white/15">
        <img src="{{ asset('images/brand/logo.png') }}" alt="Gses Chaturji" class="h-10 w-10 shrink-0 object-contain drop-shadow-lg">
        <img src="{{ asset('images/brand/ganpati.png') }}" alt="Shree Ganpati" class="h-10 w-10 shrink-0 object-contain drop-shadow-lg">
        <div class="min-w-0">
            <p class="font-bold text-white leading-tight">Teacher Panel</p>
            <p class="text-[11px] text-brand-gold/90 font-medium truncate">Gses Chaturji</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-5 space-y-0.5">
        <a href="{{ route('teacher.dashboard') }}" class="{{ request()->routeIs('teacher.dashboard') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('teacher.books.index') }}" class="{{ request()->routeIs('teacher.books.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Books
        </a>
        <a href="{{ route('teacher.exams.index') }}" class="{{ request()->routeIs('teacher.exams.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            My Exams
        </a>
        <a href="{{ route('teacher.homework.index') }}" class="{{ request()->routeIs('teacher.homework.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            My Homework
        </a>
        <a href="{{ route('teacher.todays-exam.index') }}" class="{{ request()->routeIs('teacher.todays-exam.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Today's Exam
        </a>
        <a href="{{ route('teacher.todays-teaching.index') }}" class="{{ request()->routeIs('teacher.todays-teaching.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Today's Homework
        </a>
        <a href="{{ route('teacher.daily-syllabus.create') }}" class="{{ request()->routeIs('teacher.daily-syllabus.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Daily Syllabus
        </a>
        <a href="{{ route('teacher.settings.edit') }}" class="{{ request()->routeIs('teacher.settings.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Settings
        </a>
        <a href="{{ route('teacher.profile.edit') }}" class="{{ request()->routeIs('teacher.profile.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Edit Profile
        </a>
        <a href="{{ route('teacher.change-password') }}" class="{{ request()->routeIs('teacher.change-password') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Change Password
        </a>
        <a href="{{ route('teacher.tickets.index') }}" class="{{ request()->routeIs('teacher.tickets.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            Tickets
        </a>
        <a href="{{ route('teacher.notifications.index') }}" class="{{ request()->routeIs('teacher.notifications.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/80 hover:!bg-white/10 hover:!text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span class="flex-1">Notifications</span>
            @if ($unreadNotifications > 0)
                <span class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
            @endif
        </a>
    </nav>

    <div class="border-t border-white/15 p-4 mt-auto">
        <div class="mb-3 px-2 flex items-center gap-3">
            <span class="admin-avatar text-[10px]">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-white/60 truncate">{{ Auth::user()->mobile }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Are you sure you want to logout?')">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-white/15 hover:bg-white/20 px-3 py-2.5 text-sm font-medium text-white transition">
                Logout
            </button>
        </form>
    </div>
</aside>

{{-- Mobile top bar + hamburger drawer --}}
<div class="lg:hidden fixed top-0 inset-x-0 z-50" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="bg-brand-green text-white shadow-lg">
        <div class="flex items-center justify-between gap-3 px-4 h-14">
            <div class="min-w-0 flex items-center gap-2">
                <img src="{{ asset('images/brand/logo.png') }}" alt="Gses Chaturji" class="h-7 w-7 object-contain shrink-0 drop-shadow">
                <img src="{{ asset('images/brand/ganpati.png') }}" alt="Shree Ganpati" class="h-7 w-7 object-contain shrink-0 drop-shadow">
                <span class="font-bold text-sm truncate">Teacher Panel</span>
            </div>
            <div class="relative shrink-0">
                <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 hover:bg-white/25"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-label="Toggle menu">
                    <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @if ($unreadNotifications > 0)
                    <span class="pointer-events-none absolute -right-1 -top-1 z-10 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-none text-white shadow ring-2 ring-brand-green"
                          x-show="!open"
                          x-cloak
                          aria-hidden="true">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                @endif
            </div>
        </div>
    </div>

    <div x-show="open"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 top-14 z-40 bg-black/40"
         @click="open = false"></div>

    <nav x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed top-14 left-0 bottom-0 z-50 w-[min(18rem,86vw)] overflow-y-auto bg-gradient-to-b from-brand-green-darker via-brand-green to-brand-green-light text-white shadow-2xl">
        <div class="px-3 py-4 space-y-0.5">
            <a href="{{ route('teacher.dashboard') }}" @click="open = false" class="{{ request()->routeIs('teacher.dashboard') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Dashboard</a>
            <a href="{{ route('teacher.books.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.books.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Books</a>
            <a href="{{ route('teacher.exams.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.exams.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">My Exams</a>
            <a href="{{ route('teacher.homework.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.homework.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">My Homework</a>
            <a href="{{ route('teacher.todays-exam.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.todays-exam.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Today's Exam</a>
            <a href="{{ route('teacher.todays-teaching.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.todays-teaching.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Today's Homework</a>
            <a href="{{ route('teacher.daily-syllabus.create') }}" @click="open = false" class="{{ request()->routeIs('teacher.daily-syllabus.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Daily Syllabus</a>
            <a href="{{ route('teacher.settings.edit') }}" @click="open = false" class="{{ request()->routeIs('teacher.settings.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Settings</a>
            <a href="{{ route('teacher.profile.edit') }}" @click="open = false" class="{{ request()->routeIs('teacher.profile.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Edit Profile</a>
            <a href="{{ route('teacher.change-password') }}" @click="open = false" class="{{ request()->routeIs('teacher.change-password') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Change Password</a>
            <a href="{{ route('teacher.tickets.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.tickets.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">Tickets</a>
            <a href="{{ route('teacher.notifications.index') }}" @click="open = false" class="{{ request()->routeIs('teacher.notifications.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive !text-white/90 hover:!bg-white/10 hover:!text-white' }}">
                <span class="flex-1">Notifications</span>
                @if ($unreadNotifications > 0)
                    <span class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                @endif
            </a>
        </div>
        <div class="border-t border-white/15 p-4 mt-2">
            <p class="px-2 text-sm font-semibold truncate">{{ Auth::user()->name }}</p>
            <p class="px-2 text-xs text-white/60 truncate mb-3">{{ Auth::user()->mobile }}</p>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Logout?')">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-white/15 hover:bg-white/25 px-3 py-2.5 text-sm font-medium">Logout</button>
            </form>
        </div>
    </nav>
</div>

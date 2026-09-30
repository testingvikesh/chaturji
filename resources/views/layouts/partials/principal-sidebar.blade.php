<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-slate-900 via-slate-900 to-brand-green-darker text-slate-100 hidden lg:flex lg:flex-col shadow-xl">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b border-white/10">
        <img src="{{ asset('images/brand/logo.png') }}" alt="Gses Chaturji" class="h-10 w-10 shrink-0 object-contain drop-shadow-lg">
        <img src="{{ asset('images/brand/ganpati.png') }}" alt="Shree Ganpati" class="h-10 w-10 shrink-0 object-contain drop-shadow-lg">
        <div class="min-w-0">
            <p class="font-bold text-white leading-tight">Principal Panel</p>
            <p class="text-[11px] text-brand-gold/90 font-medium truncate">School Overview</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
        <a href="{{ route('principal.dashboard') }}" class="{{ request()->routeIs('principal.dashboard') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('principal.books.index') }}" class="{{ request()->routeIs('principal.books.*') ? 'admin-sidebar-link-active' : 'admin-sidebar-link-inactive' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Books
        </a>
    </nav>

    <div class="px-4 py-4 border-t border-white/10">
        <p class="text-xs text-slate-400 truncate">{{ Auth::user()->name }}</p>
        <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email }}</p>
    </div>
</aside>

<div class="lg:hidden fixed top-0 inset-x-0 z-40 bg-slate-900 text-white border-b border-white/10" x-data="{ open: false }">
    <div class="flex items-center justify-between px-4 h-14">
        <p class="font-bold">Principal Panel</p>
        <button type="button" @click="open = !open" class="p-2 rounded-lg bg-white/10">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
    <div x-show="open" x-cloak class="px-3 pb-3 space-y-1 border-t border-white/10">
        <a href="{{ route('principal.dashboard') }}" class="block px-3 py-2 rounded-lg bg-white/10">Dashboard</a>
        <a href="{{ route('principal.books.index') }}" class="block px-3 py-2 rounded-lg bg-white/10">Books</a>
        <form method="POST" action="{{ route('principal.logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 rounded-lg bg-white/10">Logout</button>
        </form>
    </div>
</div>

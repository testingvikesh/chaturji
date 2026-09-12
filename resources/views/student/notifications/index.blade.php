<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="admin-section-label">Student</span>
                <h2 class="admin-page-title">Notifications</h2>
                <p class="admin-page-subtitle">Exam and homework updates from your teachers</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('student.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="admin-btn-secondary text-sm">
                        Mark all as read ({{ $unreadCount }})
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="admin-page">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-brand-green-200 bg-brand-green-50 px-4 py-3 text-sm text-brand-green font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if ($notifications->isEmpty())
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="p-10 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-green-50 text-brand-green">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">No notifications</h3>
                    <p class="text-sm text-slate-500 mt-2">You will be notified when teachers publish exams or assign homework.</p>
                </div>
            </div>
        @else
            <div class="admin-card overflow-hidden">
                <div class="admin-card-top"></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = is_null($notification->read_at);
                            $type = $data['type'] ?? 'general';
                            $typeMeta = match ($type) {
                                'exam' => ['label' => 'Exam', 'icon' => 'exam', 'class' => 'bg-blue-50 text-blue-600'],
                                'ticket' => ['label' => 'Ticket', 'icon' => 'ticket', 'class' => 'bg-emerald-50 text-emerald-700'],
                                default => ['label' => 'Homework', 'icon' => 'homework', 'class' => 'bg-amber-50 text-amber-700'],
                            };
                            $openUrl = route('student.notifications.read', $notification->id);
                        @endphp
                        <div class="p-4 sm:p-5 flex items-start gap-4 {{ $isUnread ? 'bg-brand-green-50/40' : 'bg-white' }}">
                            <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-xl {{ $typeMeta['class'] }}">
                                @if ($typeMeta['icon'] === 'exam')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                @elseif ($typeMeta['icon'] === 'ticket')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="admin-badge-slate text-xs">{{ $typeMeta['label'] }}</span>
                                    @if ($isUnread)
                                        <span class="inline-flex h-2 w-2 rounded-full bg-brand-green"></span>
                                    @endif
                                </div>
                                <a href="{{ $openUrl }}" class="font-semibold text-slate-900 hover:text-brand-green">{{ $data['title'] ?? 'Notification' }}</a>
                                <p class="text-sm text-slate-600 mt-1">{{ $data['message'] ?? '' }}</p>
                                <p class="text-xs text-slate-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            <a href="{{ $openUrl }}" class="admin-btn-secondary text-xs py-2 px-3 shrink-0">
                                {{ $isUnread ? 'View' : 'Open' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-student-layout>

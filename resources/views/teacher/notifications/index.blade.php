<x-teacher-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="admin-section-label">Teacher</span>
                <h2 class="admin-page-title">Notifications</h2>
                <p class="admin-page-subtitle">Today's login OTP and ticket updates</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('teacher.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="admin-btn-secondary text-sm">
                        Mark all as read ({{ $unreadCount }})
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="admin-page space-y-4">
        @if (session('success'))
            <div class="rounded-xl border border-brand-green-200 bg-brand-green-50 px-4 py-3 text-sm text-brand-green font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Today's login OTP</p>
                    <p class="text-sm text-slate-500 mt-1">{{ now()->format('d M Y') }} — use this code instead of password</p>
                </div>
                <div class="rounded-2xl border border-brand-green-200 bg-brand-green-50 px-6 py-4 text-center min-w-[10rem]">
                    @if ($todayOtp?->otp)
                        <p class="font-mono text-3xl font-bold tracking-[0.35em] text-brand-green">{{ $todayOtp->otp }}</p>
                    @else
                        <p class="text-sm text-slate-400">Not generated yet</p>
                    @endif
                </div>
            </div>
        </div>

        @if ($notifications->isEmpty())
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="p-10 text-center">
                    <h3 class="text-lg font-bold text-slate-900">No notifications</h3>
                    <p class="text-sm text-slate-500 mt-2">OTP alerts and ticket replies will appear here.</p>
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
                                'otp' => ['label' => 'OTP', 'class' => 'bg-brand-green-50 text-brand-green'],
                                'ticket' => ['label' => 'Ticket', 'class' => 'bg-emerald-50 text-emerald-700'],
                                default => ['label' => 'Alert', 'class' => 'bg-slate-100 text-slate-600'],
                            };
                            $openUrl = route('teacher.notifications.read', $notification->id);
                        @endphp
                        <div class="p-4 sm:p-5 flex items-start gap-4 {{ $isUnread ? 'bg-brand-green-50/40' : 'bg-white' }}">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $typeMeta['class'] }}">{{ $typeMeta['label'] }}</span>
                                    @if ($isUnread)
                                        <span class="inline-flex h-2 w-2 rounded-full bg-brand-green"></span>
                                    @endif
                                </div>
                                <a href="{{ $openUrl }}" class="font-semibold text-slate-900 hover:text-brand-green">{{ $data['title'] ?? 'Notification' }}</a>
                                <p class="text-sm text-slate-600 mt-1">{{ $data['message'] ?? '' }}</p>
                                @if ($type === 'otp' && ! empty($data['otp']))
                                    <p class="mt-2 font-mono text-xl font-bold tracking-[0.3em] text-brand-green">{{ $data['otp'] }}</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            <a href="{{ $openUrl }}" class="admin-btn-secondary text-xs py-2 px-3 shrink-0">
                                {{ $isUnread ? 'View' : 'Open' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-2">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-teacher-layout>

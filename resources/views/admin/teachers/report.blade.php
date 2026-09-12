<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Teacher Report</h2>
                <p class="text-sm text-slate-500 mt-1">Registration and login statistics</p>
            </div>
            <a href="{{ route('admin.teachers.index') }}" class="text-sm text-brand-green hover:underline">&larr; Back to Teachers</a>
        </div>
    </x-slot>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        @include('admin.partials.stat-card', ['label' => 'Total Teachers', 'value' => $totalTeachers])
        @include('admin.partials.stat-card', ['label' => 'Registered Today', 'value' => $todayRegistered])
        @include('admin.partials.stat-card', ['label' => 'Logins Today', 'value' => $todayLogins])
        @include('admin.partials.stat-card', ['label' => 'Active Sessions', 'value' => $activeSessions])
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200"><h3 class="font-semibold">Recent Teachers</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Mobile</th><th class="px-4 py-3 text-left">Registered</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recentTeachers as $teacher)
                            <tr>
                                <td class="px-4 py-3">{{ $teacher->name }}</td>
                                <td class="px-4 py-3">{{ $teacher->mobile }}</td>
                                <td class="px-4 py-3">{{ $teacher->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-4">Logins (Last 7 Days)</h3>
            <div class="space-y-2">
                @forelse ($weeklyLogins as $row)
                    <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                        <span>{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</span>
                        <span class="font-semibold">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-slate-400 text-sm">No login data</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

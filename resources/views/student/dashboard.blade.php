<x-student-layout>
    <x-slot name="header">
        <div>
            <span class="admin-section-label">Student</span>
            <h2 class="admin-page-title">Welcome, {{ $user->name }}!</h2>
            <p class="admin-page-subtitle">Your student dashboard — manage homework and track progress</p>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            @include('admin.partials.stat-card', ['label' => 'Standard', 'value' => $standardName])
            @include('admin.partials.stat-card', ['label' => 'Medium', 'value' => ucfirst($user->medium)])
            @include('admin.partials.stat-card', ['label' => 'Self Exams', 'value' => $examCount])
            @include('admin.partials.stat-card', ['label' => 'My Homework', 'value' => $homeworkCount])
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-3 admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">My Profile</h3>
                </div>
                <div class="p-6 grid sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <p class="text-slate-500 mb-1">Full Name</p>
                        <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">Mobile</p>
                        <p class="font-semibold text-slate-900">{{ $user->mobile }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">Email</p>
                        <p class="font-semibold text-slate-900">{{ $user->email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">Registered</p>
                        <p class="font-semibold text-slate-900">{{ $user->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            {{--
            <div class="admin-card">
                <div class="admin-card-top"></div>
                <div class="admin-card-header">
                    <h3 class="font-bold text-slate-900">Quick Links</h3>
                </div>
                <div class="p-5 space-y-3">
                    <a href="{{ route('student.subjects.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">My Subjects</p>
                        <p class="text-xs text-slate-500 mt-1">View subjects for {{ $standardName }}</p>
                    </a>
                    <a href="{{ route('student.self-practice.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">Self Practice</p>
                        <p class="text-xs text-slate-500 mt-1">Subject & chapter wise questions</p>
                    </a>
                    <a href="{{ route('student.exams.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">Self Exam</p>
                        <p class="text-xs text-slate-500 mt-1">Create & attempt · {{ $examCount }} available</p>
                    </a>
                    <a href="{{ route('student.homework.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">My Homework</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $homeworkCount }} assignment{{ $homeworkCount !== 1 ? 's' : '' }}</p>
                    </a>
                    <a href="{{ route('student.notifications.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">Notifications</p>
                        <p class="text-xs text-slate-500 mt-1">
                            @if ($unreadNotifications > 0)
                                {{ $unreadNotifications }} unread alert{{ $unreadNotifications !== 1 ? 's' : '' }}
                            @else
                                Exam & homework updates
                            @endif
                        </p>
                    </a>
                    <a href="{{ route('student.profile.edit') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">Edit Profile</p>
                        <p class="text-xs text-slate-500 mt-1">Update name, mobile, email & standard</p>
                    </a>
                    <a href="{{ route('student.change-password') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-brand-green-200 hover:bg-brand-green-50/50 transition">
                        <p class="font-semibold text-slate-900">Change Password</p>
                        <p class="text-xs text-slate-500 mt-1">Update your login password</p>
                    </a>
                    <a href="{{ route('pwa.install') }}" class="block rounded-xl border border-brand-green-100 bg-brand-green-50/50 p-4 hover:border-brand-green-200 hover:bg-brand-green-50 transition">
                        <p class="font-semibold text-slate-900">Install App</p>
                        <p class="text-xs text-slate-500 mt-1">Add Gses Chaturji to your home screen</p>
                    </a>
                </div>
            </div>
            --}}
        </div>

        @include('student.partials.subjects-grid', [
            'subjects' => $subjects,
            'standardName' => $standardName,
        ])
    </div>
</x-student-layout>

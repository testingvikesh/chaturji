<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Change Password</h2>
            <p class="text-sm text-slate-500 mt-1">Update your account password</p>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>

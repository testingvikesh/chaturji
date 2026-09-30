<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="Edit Principal" :subtitle="$principal->name">
            <x-slot name="actions">
                <a href="{{ route('admin.principals.index') }}" class="admin-btn-secondary">Back</a>
            </x-slot>
        </x-admin.partials.page-header>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card max-w-2xl">
            <div class="admin-card-top"></div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.principals.update', $principal) }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2">
                        <label class="admin-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $principal->name) }}" required class="admin-input">
                        @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Mobile *</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $principal->mobile) }}" required class="admin-input">
                        @error('mobile')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $principal->email) }}" required class="admin-input">
                        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">New password</label>
                        <input type="password" name="password" class="admin-input" minlength="8">
                        @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label">Confirm password</label>
                        <input type="password" name="password_confirmation" class="admin-input" minlength="8">
                    </div>
                    @include('admin.principals.partials.standard-allotment', [
                        'standards' => $standards,
                        'selected' => old('standard_ids', $selectedStandardIds),
                    ])
                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="send_mail" value="0">
                            <input type="checkbox" name="send_mail" value="1" class="rounded border-slate-300 text-brand-green" @checked(old('send_mail'))>
                            Send login email (requires new password above)
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="admin-btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

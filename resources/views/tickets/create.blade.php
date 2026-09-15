<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <div>
            <span class="admin-section-label">{{ $label }}</span>
            <h2 class="admin-page-title">Generate Ticket</h2>
            <p class="admin-page-subtitle">Write your issue. Admin will see it in the ticket report.</p>
        </div>
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="mb-4">
            <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-brand-green hover:underline font-medium">&larr; Back to Tickets</a>
        </div>

        <div class="admin-form-card">
            <div class="admin-card-top"></div>
            <form method="POST"
                  action="{{ route($routePrefix.'.store') }}"
                  class="admin-card-body"
                  x-data="{ sending: false }"
                  @submit="if (sending) { $event.preventDefault(); return; } sending = true">
                @csrf
                <div>
                    <label class="admin-label">Category</label>
                    <select name="category" required class="admin-select">
                        @foreach ($categories as $key => $name)
                            <option value="{{ $key }}" @selected(old('category', 'other') === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="160" class="admin-input" placeholder="Short title of your issue">
                    @error('subject')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label">Message</label>
                    <textarea name="message" rows="6" required maxlength="4000" class="admin-input" placeholder="Describe the problem">{{ old('message') }}</textarea>
                    @error('message')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="admin-btn-primary"
                            :disabled="sending"
                            :class="{ 'opacity-60 cursor-not-allowed': sending }">
                        <span x-text="sending ? 'Submitting…' : 'Generate Ticket'">Generate Ticket</span>
                    </button>
                    <a href="{{ route($routePrefix.'.index') }}" class="admin-btn-ghost" @click="if (sending) $event.preventDefault()">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-dynamic-component>

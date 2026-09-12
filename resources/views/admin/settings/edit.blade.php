<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Website Settings</h2>
            <p class="text-sm text-slate-500 mt-1">Manage website content and mail setup. Sent mail is listed in <a href="{{ route('admin.email-logs.index') }}" class="text-brand-green font-semibold hover:underline">Email Log</a>. AI prompts are under <a href="{{ route('admin.prompts.index') }}" class="text-brand-green font-semibold hover:underline">Prompts</a>.</p>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-brand-green-50 border border-brand-green-200 px-4 py-3 text-sm text-brand-green-dark">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div x-data="{ tab: @js($activeTab ?: 'general') }" class="max-w-5xl">
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach ($groups as $groupKey => $group)
                <button type="button"
                    @click="tab = '{{ $groupKey }}'"
                    :class="tab === '{{ $groupKey }}' ? 'bg-brand-green text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-200 transition">
                    {{ $group['label'] }}
                </button>
            @endforeach
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" :value="tab">

            @foreach ($groups as $groupKey => $group)
                <div x-show="tab === '{{ $groupKey }}'" x-cloak class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                    <h3 class="text-lg font-bold text-slate-900 border-b border-slate-200 pb-3">{{ $group['label'] }}</h3>

                    @foreach ($group['fields'] as $fieldKey => $field)
                        <div>
                            <label for="{{ $fieldKey }}" class="block text-sm font-medium text-slate-700 mb-1">
                                {{ $field['label'] }}
                            </label>
                            @if ($field['type'] === 'textarea')
                                <textarea id="{{ $fieldKey }}" name="{{ $fieldKey }}" rows="{{ $field['rows'] ?? 3 }}"
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">{{ old($fieldKey, $settings[$fieldKey] ?? '') }}</textarea>
                            @elseif ($field['type'] === 'select')
                                <select id="{{ $fieldKey }}" name="{{ $fieldKey }}"
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                                    @foreach (($field['options'] ?? []) as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) old($fieldKey, $settings[$fieldKey] ?? '') === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'password')
                                <input type="password" id="{{ $fieldKey }}" name="{{ $fieldKey }}" value=""
                                    autocomplete="new-password" placeholder="{{ filled($settings[$fieldKey] ?? null) ? 'Saved — leave blank to keep' : 'Enter SMTP password' }}"
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                            @else
                                <input type="{{ $field['type'] }}" id="{{ $fieldKey }}" name="{{ $fieldKey }}"
                                    value="{{ old($fieldKey, $settings[$fieldKey] ?? '') }}"
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                            @endif
                            @error($fieldKey)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if (! empty($field['hint']))
                                <p class="mt-1.5 text-xs text-slate-500">{{ $field['hint'] }}</p>
                            @endif
                        </div>
                    @endforeach

                    @if ($groupKey === 'mail')
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-800">Send test mail</p>
                            <p class="text-xs text-slate-500 mt-1 mb-3">Save settings first, then send a test to confirm SMTP.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="mt-6 flex items-center gap-4">
                <x-primary-button>Save Settings</x-primary-button>
                <a href="{{ route('login') }}" target="_blank" class="text-sm text-brand-green hover:text-brand-green-dark font-medium">
                    Preview Login Page &rarr;
                </a>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.settings.test-mail') }}" x-show="tab === 'mail'" x-cloak class="mt-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
            @csrf
            <label for="test_email" class="block text-sm font-medium text-slate-700 mb-1">Test email address</label>
            <div class="flex flex-wrap gap-3">
                <input type="email" id="test_email" name="test_email" required
                    value="{{ old('test_email', $settings['mail_admin_email'] ?? auth()->user()?->email) }}"
                    class="flex-1 min-w-[16rem] rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                <button type="submit" class="admin-btn-secondary">Send Test Mail</button>
            </div>
            @error('test_email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>

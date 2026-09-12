<x-app-layout>
    <x-slot name="header">
        <x-admin.partials.page-header title="AI Prompts" subtitle="Set the prompts used for answer-sheet checking and OCR" />
    </x-slot>

    <div class="admin-page">
        @include('admin.partials.alert')

        <div class="admin-card">
            <div class="admin-card-top"></div>
            <div class="admin-card-header">
                <div>
                    <h3 class="font-bold text-slate-900">Prompt list</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Edit a prompt, then save. Leave unused ones on default.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Prompt</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prompts as $item)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $item['label'] }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $item['hint'] }}</p>
                                </td>
                                <td>
                                    @if ($item['custom'])
                                        <span class="admin-badge-green text-xs">Custom</span>
                                    @else
                                        <span class="admin-badge-slate text-xs">Default</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.prompts.edit', $item['key']) }}" class="admin-btn-secondary text-xs py-2 px-3">Set prompt</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

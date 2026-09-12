@props(['colspan' => 6, 'message' => 'No records found', 'hint' => 'Try adjusting your filters or check back later.'])

<tr>
    <td colspan="{{ $colspan }}">
        <div class="admin-empty">
            <div class="admin-empty-icon">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-700">{{ $message }}</p>
            <p class="text-sm text-slate-400 mt-1 max-w-sm">{{ $hint }}</p>
        </div>
    </td>
</tr>

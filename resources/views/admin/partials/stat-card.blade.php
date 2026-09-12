<div class="admin-stat-card">
    <p class="admin-stat-label">{{ $label }}</p>
    <p class="admin-stat-value">{{ $value }}</p>
    @isset($hint)
        <p class="text-xs text-slate-400 mt-1.5">{{ $hint }}</p>
    @endisset
</div>

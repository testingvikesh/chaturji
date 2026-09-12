<div>
    <label class="admin-label">Name</label>
    <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" required class="admin-input">
    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="admin-label">Sort Order</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0" class="admin-input">
</div>
<label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-slate-300 text-brand-green focus:ring-brand-green">
    Active
</label>

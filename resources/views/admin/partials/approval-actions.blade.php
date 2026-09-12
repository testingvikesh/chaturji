@props(['user', 'approveRoute', 'pendingRoute', 'inline' => true])

<div class="{{ $inline ? 'admin-action-group justify-end' : 'flex flex-wrap gap-2' }}">
    @if (! $user->is_approved)
        <form method="POST" action="{{ $approveRoute }}" class="{{ $inline ? 'inline' : '' }}"
              onsubmit="return confirm('Approve {{ $user->name }}?\n\nThey will be able to login after approval.')">
            @csrf
            <button type="submit" class="{{ $inline ? 'admin-action-view' : 'rounded-lg bg-brand-green px-4 py-2 text-sm text-white font-semibold' }}">
                Approve
            </button>
        </form>
    @else
        <form method="POST" action="{{ $pendingRoute }}" class="{{ $inline ? 'inline' : '' }}"
              onsubmit="return confirm('Set {{ $user->name }} to Pending?\n\nThey will not be able to login until approved again.')">
            @csrf
            <button type="submit" class="{{ $inline ? 'admin-action-edit' : 'rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-800 font-semibold hover:bg-amber-100' }}">
                Pending
            </button>
        </form>
    @endif
</div>

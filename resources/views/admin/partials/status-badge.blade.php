@props(['user', 'approveRoute', 'pendingRoute'])

@if ($user->is_approved)
    <form method="POST" action="{{ $pendingRoute }}" class="inline"
          onsubmit="return confirm('Set {{ $user->name }} to Pending?\n\nThey will not be able to login until approved again.')">
        @csrf
        <button type="submit" class="admin-badge-btn admin-badge-green hover:opacity-80 cursor-pointer transition" title="Click to set pending">
            Approved
        </button>
    </form>
@else
    <form method="POST" action="{{ $approveRoute }}" class="inline"
          onsubmit="return confirm('Approve {{ $user->name }}?\n\nThey will be able to login after approval.')">
        @csrf
        <button type="submit" class="admin-badge-btn admin-badge-gold hover:opacity-80 cursor-pointer transition" title="Click to approve">
            Pending
        </button>
    </form>
@endif

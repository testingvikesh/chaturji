<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(Request $request): View
    {
        $query = Ticket::query()->with('user')->withCount('replies')->latest();

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', $role);
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->trim()->toString()) {
            $query->where('category', $category);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('ticket_no', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
            });
        }

        return view('admin.tickets.index', [
            'tickets' => $query->paginate(20)->withQueryString(),
            'categories' => Ticket::CATEGORIES,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'role' => $request->string('role')->toString(),
                'status' => $request->string('status')->toString(),
                'category' => $request->string('category')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'summary' => [
                'all' => Ticket::query()->count(),
                'open' => Ticket::query()->where('status', 'open')->count(),
                'answered' => Ticket::query()->where('status', 'answered')->count(),
                'closed' => Ticket::query()->where('status', 'closed')->count(),
                'student' => Ticket::query()->where('role', 'student')->count(),
                'teacher' => Ticket::query()->where('role', 'teacher')->count(),
            ],
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['user', 'replies.user']);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'statuses' => Ticket::STATUSES,
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'status' => ['nullable', Rule::in(array_keys(Ticket::STATUSES))],
        ]);

        $this->tickets->reply($ticket, auth()->user(), $validated['message'], true);

        if (! empty($validated['status']) && $validated['status'] === 'closed') {
            $this->tickets->close($ticket->fresh());
        }

        return back()->with('success', 'Reply sent to '.$ticket->ticket_no.'.');
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Ticket::STATUSES))],
        ]);

        match ($validated['status']) {
            'closed' => $this->tickets->close($ticket),
            'open' => $this->tickets->reopen($ticket),
            default => $ticket->update(['status' => $validated['status']]),
        };

        return back()->with('success', 'Ticket status updated.');
    }
}

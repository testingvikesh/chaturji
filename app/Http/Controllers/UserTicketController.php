<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserTicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(): View
    {
        $user = auth()->user();

        return view('tickets.index', $this->panelData() + [
            'tickets' => Ticket::query()
                ->where('user_id', $user->id)
                ->withCount('replies')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', $this->panelData() + [
            'categories' => Ticket::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $ticket = $this->tickets->create(auth()->user(), $validated);

        return redirect()
            ->route($this->routeName('show'), $ticket)
            ->with('success', 'Ticket '.$ticket->ticket_no.' generated.');
    }

    public function show(Ticket $ticket): View
    {
        $this->assertOwner($ticket);
        $ticket->load(['replies.user', 'user']);

        return view('tickets.show', $this->panelData() + [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->assertOwner($ticket);
        abort_if($ticket->isClosed(), 403, 'This ticket is closed.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $this->tickets->reply($ticket, auth()->user(), $validated['message']);

        return back()->with('success', 'Reply sent.');
    }

    private function assertOwner(Ticket $ticket): void
    {
        abort_unless((int) $ticket->user_id === (int) auth()->id(), 404);
    }

    /**
     * @return array{layout:string,panel:string,label:string,routePrefix:string}
     */
    private function panelData(): array
    {
        $role = auth()->user()?->role === 'teacher' ? 'teacher' : 'student';

        return [
            'layout' => $role.'-layout',
            'panel' => $role,
            'label' => $role === 'teacher' ? 'Teacher' : 'Student',
            'routePrefix' => $role.'.tickets',
        ];
    }

    private function routeName(string $action): string
    {
        return $this->panelData()['routePrefix'].'.'.$action;
    }
}

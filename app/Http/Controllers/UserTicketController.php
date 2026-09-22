<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Support\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserTicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(): View
    {
        $user = auth()->user();

        return view('tickets.index', $this->panelData() + [
            'tickets' => Ticket::query()
                ->where('user_id', $user->id)
                ->withCount(['replies', 'attachments'])
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
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt,zip'],
        ], [
            'attachments.max' => 'You can upload up to 5 files.',
            'attachments.*.max' => 'Each file must be 5 MB or less.',
            'attachments.*.mimes' => 'Allowed files: JPG, PNG, WEBP, PDF, DOC, DOCX, TXT, ZIP.',
        ]);

        $files = $request->file('attachments', []) ?: [];
        if (! is_array($files)) {
            $files = [$files];
        }

        $ticket = $this->tickets->create(auth()->user(), $validated, array_values($files));

        return redirect()
            ->route($this->routeName('show'), $ticket)
            ->with('success', 'Ticket '.$ticket->ticket_no.' generated.');
    }

    public function show(Ticket $ticket): View
    {
        $this->assertOwner($ticket);
        $ticket->load(['replies.user', 'user', 'attachments']);

        return view('tickets.show', $this->panelData() + [
            'ticket' => $ticket,
        ]);
    }

    public function downloadAttachment(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->assertOwner($ticket);
        abort_unless((int) $attachment->ticket_id === (int) $ticket->id, 404);
        abort_unless($attachment->existsOnDisk(), 404);

        return Storage::disk($attachment->disk ?: 'public')->download(
            $attachment->path,
            $attachment->original_name
        );
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

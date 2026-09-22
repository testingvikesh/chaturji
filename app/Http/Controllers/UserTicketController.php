<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Support\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                ->paginate(500),
        ]);
    }

    public function create(Request $request): View
    {
        $user = auth()->user();
        $type = old('ticket_type', $request->query('type'));
        $type = in_array($type, ['issue', 'missing'], true) ? $type : null;

        return view('tickets.create', $this->panelData() + [
            'ticketType' => $type,
            'categories' => collect(Ticket::CATEGORIES)
                ->reject(fn ($label, $key) => $key === 'missing_chapter')
                ->all(),
            'mediums' => Standard::MEDIUMS,
            'standards' => Standard::query()
                ->where('is_active', true)
                ->orderedByNumber()
                ->get(['id', 'name', 'slug']),
            'initialMedium' => Material::normalizeMedium(old('medium', $user->medium)) ?: '',
            'subjectsUrl' => route($this->routeName('subjects')),
        ]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
        ]);

        $standard = Standard::query()->where('id', $validated['standard_id'])->where('is_active', true)->firstOrFail();
        $medium = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];

        $subjects = Material::subjectsForStudent($standard, $medium)
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
            ])
            ->values();

        return response()->json($subjects);
    }

    public function chapters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        $subject = Subject::query()->with('standard')->findOrFail($validated['subject_id']);
        $medium = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];

        $chapters = Material::forStudentSubject($subject, $medium)
            ->map(function (Material $material) {
                $label = trim((string) ($material->chapter_name ?: $material->title ?: 'Chapter'));
                $no = trim((string) ($material->chapter_no ?? ''));

                return [
                    'id' => $material->id,
                    'name' => $no !== '' ? $no.'. '.$label : $label,
                ];
            })
            ->values();

        return response()->json($chapters);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('ticket_type');

        if (! in_array($type, ['issue', 'missing'], true)) {
            throw ValidationException::withMessages([
                'ticket_type' => 'Choose Issue Ticket or Missing File Upload.',
            ]);
        }

        if ($type === 'missing') {
            return $this->storeMissing($request);
        }

        return $this->storeIssue($request);
    }

    private function storeIssue(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_type' => ['required', Rule::in(['issue'])],
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt,zip'],
        ], [
            'attachments.max' => 'You can upload up to 5 files.',
            'attachments.*.max' => 'Each file must be 10 MB or less.',
        ]);

        if (($validated['category'] ?? '') === 'missing_chapter') {
            $validated['category'] = 'other';
        }

        $validated['medium'] = null;
        $validated['standard_id'] = null;
        $validated['subject_id'] = null;
        $validated['chapter_id'] = null;
        $validated['chapter_name'] = null;
        $validated['chapter_no'] = null;

        $files = $this->normalizeFiles($request->file('attachments'));
        $ticket = $this->tickets->create(auth()->user(), $validated, $files);

        return redirect()
            ->route($this->routeName('show'), $ticket)
            ->with('success', 'Ticket '.$ticket->ticket_no.' generated.');
    }

    private function storeMissing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_type' => ['required', Rule::in(['missing'])],
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['required', 'integer', 'exists:standards,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'chapter_name' => ['required', 'string', 'max:255'],
            'chapter_no' => ['required', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:4000'],
            'chapter_files' => ['required', 'array', 'min:1', 'max:5'],
            'chapter_files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt,zip'],
        ], [
            'chapter_files.required' => 'Please upload at least one missing file.',
            'chapter_files.min' => 'Please upload at least one missing file.',
            'chapter_files.*.max' => 'Each file must be 10 MB or less.',
        ]);

        $validated['medium'] = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];
        $validated['category'] = 'missing_chapter';
        $validated['chapter_id'] = null;

        $standard = Standard::query()->find($validated['standard_id']);
        $subjectModel = Subject::query()->find($validated['subject_id']);
        $mediumLabel = Standard::MEDIUMS[$validated['medium']] ?? ucfirst((string) $validated['medium']);
        $chapterLabel = trim('Ch. '.$validated['chapter_no'].' '.$validated['chapter_name']);
        $summary = trim(implode(' · ', array_filter([
            $mediumLabel,
            $standard?->name,
            $subjectModel?->name,
            $chapterLabel,
        ])));

        $validated['subject'] = 'Missing file: '.($summary ?: 'upload');
        $note = trim((string) ($validated['message'] ?? ''));
        $validated['message'] = $note !== ''
            ? $note."\n\nMissing file details: ".$summary
            : 'Please add this missing chapter file.'."\n\nMissing file details: ".$summary;

        $files = $this->normalizeFiles($request->file('chapter_files'));
        $ticket = $this->tickets->create(auth()->user(), $validated, $files);

        return redirect()
            ->route($this->routeName('show'), $ticket)
            ->with('success', 'Ticket '.$ticket->ticket_no.' generated for missing file upload.');
    }

    public function show(Ticket $ticket): View
    {
        $this->assertOwner($ticket);
        $ticket->load(['replies.user', 'user', 'attachments', 'standard', 'curriculumSubject']);

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

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    private function normalizeFiles(mixed $files): array
    {
        if (! $files) {
            return [];
        }

        if (! is_array($files)) {
            $files = [$files];
        }

        return array_values(array_filter($files));
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

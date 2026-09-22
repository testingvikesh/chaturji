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
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('tickets.create', $this->panelData() + [
            'categories' => Ticket::CATEGORIES,
            'mediums' => Standard::MEDIUMS,
            'standards' => Standard::query()
                ->where('is_active', true)
                ->orderedByNumber()
                ->get(['id', 'name', 'slug']),
            'initialMedium' => Material::normalizeMedium(old('medium', $user->medium)) ?: '',
            'subjectsUrl' => route($this->routeName('subjects')),
            'chaptersUrl' => route($this->routeName('chapters')),
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
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'medium' => ['nullable', Rule::in(array_keys(Standard::MEDIUMS))],
            'standard_id' => ['nullable', 'integer', 'exists:standards,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'chapter_id' => ['nullable', 'integer'],
            'chapter_name' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt,zip'],
            'chapter_files' => ['nullable', 'array', 'max:5'],
            'chapter_files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt,zip'],
        ], [
            'attachments.max' => 'You can upload up to 5 general files.',
            'attachments.*.max' => 'Each file must be 10 MB or less.',
            'chapter_files.max' => 'You can upload up to 5 chapter files.',
            'chapter_files.*.max' => 'Each chapter file must be 10 MB or less.',
        ]);

        $usingMissingChapter = filled($validated['medium'] ?? null)
            || filled($validated['standard_id'] ?? null)
            || filled($validated['subject_id'] ?? null)
            || filled($validated['chapter_name'] ?? null)
            || $request->hasFile('chapter_files');

        if ($usingMissingChapter) {
            foreach (['medium', 'standard_id', 'subject_id', 'chapter_name'] as $field) {
                if (blank($validated[$field] ?? null)) {
                    throw ValidationException::withMessages([
                        $field => 'Complete medium, standard, subject and chapter for Missing Chapter Upload.',
                    ]);
                }
            }

            if (! $request->hasFile('chapter_files') && ! $request->hasFile('attachments')) {
                throw ValidationException::withMessages([
                    'chapter_files' => 'Please upload at least one chapter file.',
                ]);
            }

            $validated['category'] = 'missing_chapter';
            $validated['medium'] = Material::normalizeMedium($validated['medium']) ?: $validated['medium'];

            $standard = Standard::query()->find($validated['standard_id']);
            $subjectModel = Subject::query()->find($validated['subject_id']);
            $mediumLabel = Standard::MEDIUMS[$validated['medium']] ?? ucfirst((string) $validated['medium']);
            $summary = trim(implode(' · ', array_filter([
                $mediumLabel,
                $standard?->name,
                $subjectModel?->name,
                $validated['chapter_name'],
            ])));

            if (blank($validated['subject']) || $validated['subject'] === 'Missing chapter') {
                $validated['subject'] = 'Missing chapter: '.($summary ?: 'upload');
            }

            $metaLine = 'Missing chapter details: '.$summary;
            if (! str_contains($validated['message'], $metaLine)) {
                $validated['message'] = trim($validated['message']."\n\n".$metaLine);
            }
        } else {
            $validated['medium'] = null;
            $validated['standard_id'] = null;
            $validated['subject_id'] = null;
            $validated['chapter_id'] = null;
            $validated['chapter_name'] = null;
        }

        $files = array_merge(
            $this->normalizeFiles($request->file('attachments')),
            $this->normalizeFiles($request->file('chapter_files'))
        );

        if (count($files) > 5) {
            throw ValidationException::withMessages([
                'attachments' => 'Total attachments cannot exceed 5 files.',
            ]);
        }

        $ticket = $this->tickets->create(auth()->user(), $validated, $files);

        return redirect()
            ->route($this->routeName('show'), $ticket)
            ->with('success', 'Ticket '.$ticket->ticket_no.' generated.');
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

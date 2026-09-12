<?php

namespace App\Http\Controllers;

use App\Models\ChapterContent;
use App\Models\Standard;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChapterOriginalPdfController extends Controller
{
    public function show(ChapterContent $chapterContent): StreamedResponse|BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $chapterContent->loadMissing('chapter.subject.standard');

        abort_unless($chapterContent->hasOriginalPdf(), 404);
        abort_unless(
            Storage::disk('local')->exists($chapterContent->original_pdf_path),
            404
        );
        $this->authorizeAccess($user, $chapterContent);

        $filename = $chapterContent->original_pdf_filename ?: 'practice.pdf';

        return Storage::disk('local')->response(
            $chapterContent->original_pdf_path,
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    private function authorizeAccess($user, ChapterContent $chapterContent): void
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if (($user->role ?? null) === 'admin') {
            return;
        }

        if (($user->role ?? null) === 'teacher') {
            return;
        }

        if (($user->role ?? null) === 'student') {
            $standard = Standard::where('slug', $user->standard)->where('is_active', true)->first();
            $contentStandardId = $chapterContent->chapter?->subject?->standard_id;

            abort_unless(
                $standard && $contentStandardId && (int) $standard->id === (int) $contentStandardId,
                403
            );

            return;
        }

        abort(403);
    }
}

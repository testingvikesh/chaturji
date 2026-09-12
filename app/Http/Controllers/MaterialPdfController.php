<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Standard;
use App\Services\S3ObjectService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialPdfController extends Controller
{
    public function show(Material $material): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $this->authorizeAccess($user, $material);

        $path = $material->textbookPdfAbsolutePath();
        if ($path) {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$material->textbookPdfFilename().'"',
            ]);
        }

        $publicUrl = $material->textbookPdfPublicUrl();
        abort_unless($publicUrl, 404);

        $s3 = S3ObjectService::fromConfig();
        if ($s3 && $s3->isConfiguredBucketUrl($publicUrl)) {
            $streamed = $s3->streamUrl($publicUrl, $material->textbookPdfFilename(), 'application/pdf');
            if ($streamed) {
                return $streamed;
            }
        }

        $streamed = $this->streamFromPublicUrl($publicUrl, $material->textbookPdfFilename());
        if ($streamed) {
            return $streamed;
        }

        return redirect()->away($publicUrl);
    }

    public function page(Material $material, string $page): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);
        $this->authorizeAccess($user, $material);

        $key = $material->resolveTextbookPageKey($page);
        abort_unless($key, 404);

        $s3 = S3ObjectService::fromConfig();
        abort_unless($s3, 404);

        $streamed = $s3->streamKey($key, basename($key));
        abort_unless($streamed, 404);

        return $streamed;
    }

    private function streamFromPublicUrl(string $url, string $filename): ?StreamedResponse
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'follow_location' => 1,
                'header' => "Accept: application/pdf,*/*\r\nUser-Agent: GSES-Homework-Textbook\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $handle = @fopen($url, 'rb', false, $context);
        if ($handle === false) {
            return null;
        }

        $status = 0;
        foreach ($http_response_header ?? [] as $headerLine) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $headerLine, $match)) {
                $status = (int) $match[1];
            }
        }
        if ($status !== 0 && $status !== 200) {
            fclose($handle);

            return null;
        }

        return response()->stream(function () use ($handle) {
            fpassthru($handle);
            fclose($handle);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function authorizeAccess($user, Material $material): void
    {
        if (($user->role ?? null) === 'admin' || ($user->role ?? null) === 'teacher') {
            return;
        }

        if (($user->role ?? null) !== 'student') {
            abort(403);
        }

        $standard = Standard::where('slug', $user->standard)->where('is_active', true)->first();
        abort_unless($standard, 403);

        $material->loadMissing('chapter.subject');
        $subject = $material->chapter?->subject;

        $belongs = false;
        if ($subject && (int) $subject->standard_id === (int) $standard->id) {
            $belongs = true;
        }

        if (! $belongs && filled($material->subject)) {
            $belongs = $standard->activeSubjects()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $material->subject))])
                ->exists();
        }

        abort_unless($belongs, 403);

        if (Material::normalizeMedium($user->medium) !== null) {
            abort_unless(
                Material::normalizeMedium($material->medium) === Material::normalizeMedium($user->medium),
                403
            );
        }
    }
}

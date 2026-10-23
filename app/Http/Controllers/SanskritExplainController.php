<?php

namespace App\Http\Controllers;

use App\Services\SanskritExplainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SanskritExplainController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $text = trim((string) $request->input('text', ''));
        if ($text === '' || mb_strlen($text) > 8000) {
            return response()->json(['explain' => null], 422);
        }

        return response()->json([
            'explain' => SanskritExplainService::remember($text),
        ]);
    }
}

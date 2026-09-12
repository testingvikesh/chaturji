<?php

namespace App\Http\Middleware;

use App\Services\AuthActivityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    public function handle(Request $request, Closure $next): Response
    {
        AuthActivityService::touchSession($request);

        return $next($request);
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Laravel turns a CSRF mismatch into HttpException 419 before these callbacks run.
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $route = $this->loginRouteFor($request);
            $message = 'Your session expired. Please sign in again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => route($route),
                ], 419);
            }

            return redirect()
                ->route($route)
                ->with('status', $message);
        });
    }

    private function loginRouteFor(Request $request): string
    {
        $previousPath = trim((string) parse_url((string) url()->previous(), PHP_URL_PATH), '/');
        $areas = [
            explode('/', trim($request->path(), '/'))[0] ?? '',
            explode('/', $previousPath)[0] ?? '',
        ];

        foreach ($areas as $area) {
            $route = match ($area) {
                'admin' => 'admin.login',
                'principal' => 'principal.login',
                'teacher' => 'teacher.login',
                'student' => 'student.login',
                default => null,
            };

            if ($route && Route::has($route)) {
                return $route;
            }
        }

        return 'login';
    }
}

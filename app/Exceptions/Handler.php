<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
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

        $this->renderable(function (TokenMismatchException $e, $request) {
            $path = trim($request->path(), '/');
            $previous = (string) url()->previous();

            if (str_starts_with($path, 'teacher') || str_contains($previous, '/teacher')) {
                $route = 'teacher.login';
            } elseif (str_starts_with($path, 'student') || str_contains($previous, '/student')) {
                $route = 'student.login';
            } elseif (str_starts_with($path, 'admin') || str_contains($previous, '/admin')) {
                $route = 'admin.login';
            } else {
                $route = 'login';
            }

            return redirect()
                ->route($route)
                ->with('status', 'Your session expired. Please sign in again.');
        });
    }
}

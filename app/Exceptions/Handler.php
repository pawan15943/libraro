<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/v1/*') && $exception instanceof ValidationException) {
            return response()->json([
                'status' => false,
                'message' => collect($exception->errors())->flatten()->first() ?? 'Validation failed',
                'errors' => $exception->errors(),
            ], 200);
        }

        if ($request->is('api/v1/*') && $exception instanceof HttpException && $exception->getStatusCode() === 422) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage() ?: 'Validation failed',
            ], 200);
        }

        if ($request->is('api/v1/*') && $exception instanceof AuthenticationException) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 200);
        }

        if ($request->is('api/v1/*') && ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException)) {
            return response()->json([
                'status' => false,
                'message' => 'Not found',
            ], 200);
        }

        if ($request->is('api/v1/*') && $exception instanceof HttpException && $exception->getStatusCode() < 500) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage() ?: 'Something went wrong',
            ], 200);
        }

        if (
            ! $request->expectsJson()
            && ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException)
            && $this->shouldRedirectAfterBranchSwitch()
        ) {
            session()->forget(['branch_switched', 'branch_switched_at']);

            return redirect()
                ->route('library.home')
                ->with('branch_error', 'Branch changed. Requested data not found.');
        }

        return parent::render($request, $exception);
    }

    /**
     * Branch switch sets a short-lived session flag so the next missing record / 404
     * can send the user home with a message instead of a bare error page.
     */
    private function shouldRedirectAfterBranchSwitch(): bool
    {
        if (! session()->get('branch_switched')) {
            return false;
        }

        $at = (int) session()->get('branch_switched_at', 0);

        return $at > 0 && (time() - $at) <= 120;
    }

}

<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
    }

    /**
     * Render HTTP exceptions (403) with our custom minimal view.
     */
    protected function renderHttpException(HttpExceptionInterface $e)
    {
        if ($e->getStatusCode() === 403) {
            return response()->view('errors.403', [
                'exception' => $e,
            ], 403, $e->getHeaders());
        }

        return parent::renderHttpException($e);
    }
}

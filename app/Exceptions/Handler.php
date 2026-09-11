<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
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

        // Token CSRF vencido (sesión expirada): en vez de "419 Page Expired" se manda al login.
        // Laravel ya lo convirtió en HttpException 419 al llegar aquí. Livewire y AJAX
        // conservan su manejo propio del 419.
        $this->renderable(function (HttpExceptionInterface $e, $request) {
            if (! $e->getPrevious() instanceof TokenMismatchException
                || $request->expectsJson()
                || $request->hasHeader('X-Livewire')) {
                return null;
            }

            return redirect()->guest(route('login'))
                ->with('status', 'Tu sesión expiró. Inicia sesión de nuevo.');
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

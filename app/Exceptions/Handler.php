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

        // Token CSRF vencido (página vieja tras expirar la sesión): en vez de
        // mostrar la pantalla "419 Page Expired", mandamos al login, que es el
        // estado real (sin sesión). Aplica al logout y a cualquier form viejo.
        $this->renderable(function (TokenMismatchException $e, $request) {
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

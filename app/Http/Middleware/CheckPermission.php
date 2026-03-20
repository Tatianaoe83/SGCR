<?php

namespace App\Http\Middleware;

use App\Services\UserPuestoService;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(private UserPuestoService $userPuestoService) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            abort(401, 'No autenticado.');
        }

        // Bypass global para usuarios de acceso total.
        if ($this->userPuestoService->tieneAccesoTotal(auth()->user())) {
            return $next($request);
        }

        try {
            $hasPermission = auth()->user()->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist $e) {
            // Si el permiso no existe en catálogo, tratarlo como no autorizado.
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        if (!$hasPermission) {
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}

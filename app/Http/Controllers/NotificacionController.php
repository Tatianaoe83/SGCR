<?php

namespace App\Http\Controllers;

use App\Services\NotificacionFirmaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function __construct(private NotificacionFirmaService $notificaciones) {}

    /**
     * Marca como leída la notificación de rechazo de un elemento.
     */
    public function marcarRechazoLeido(Request $request, int $elemento): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['ok' => false], 401);
        }

        $this->notificaciones->marcarRechazoLeido($user, $elemento);

        return response()->json([
            'ok' => true,
            'pendientes' => $this->notificaciones->pendientes($user)->count()
                + $this->notificaciones->rechazos($user)->count(),
        ]);
    }
}

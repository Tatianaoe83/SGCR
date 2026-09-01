<?php

namespace App\Services;

use App\Models\Elemento;
use App\Models\Empleados;
use App\Models\Firmas;
use App\Models\NotificacionLeida;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificacionFirmaService
{
    /**
     * Firmas pendientes del usuario que están en la prioridad activa de su elemento.
     *
     * @return Collection<int, Firmas>
     */
    public function pendientes(?User $user): Collection
    {
        $empleadoIds = $this->empleadoIdsDe($user);

        if (empty($empleadoIds)) {
            return collect();
        }

        return Firmas::whereIn('empleado_id', $empleadoIds)
            ->where('estatus', 'Pendiente')
            ->where('is_active', true)
            ->with(['elemento', 'elemento.tipoElemento'])
            ->get()
            ->filter(function (Firmas $firma) {
                $prioridadMinima = Firmas::obtenerPrioridadMinimaPendiente($firma->elemento_id);

                return $prioridadMinima !== null && $firma->prioridad === $prioridadMinima;
            })
            ->values();
    }

    /**
     * Elementos rechazados que el usuario debe conocer y que aún no marcó como leídos.
     *
     * Aplica a quien creó el elemento y también a los firmantes involucrados.
     *
     * @return Collection<int, array{elemento: Elemento, rechazadaPor: ?Firmas, fecha: ?\Illuminate\Support\Carbon, motivo: ?string, esCreador: bool}>
     */
    public function rechazos(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        $empleadoIds = $this->empleadoIdsDe($user);

        // Elementos donde el usuario firma (o firmaba antes del rechazo)
        $idsComoFirmante = empty($empleadoIds)
            ? []
            : Firmas::whereIn('empleado_id', $empleadoIds)
                ->where('is_active', true)
                ->distinct()
                ->pluck('elemento_id')
                ->all();

        $elementos = Elemento::where('status', 'Rechazado')
            ->where(function ($query) use ($user, $idsComoFirmante) {
                $query->where('created_by', $user->id);

                if (!empty($idsComoFirmante)) {
                    $query->orWhereIn('id_elemento', $idsComoFirmante);
                }
            })
            ->with('tipoElemento')
            ->get();

        if ($elementos->isEmpty()) {
            return collect();
        }

        $elementoIds = $elementos->pluck('id_elemento')->all();

        // Firma que originó el rechazo: la que conserva nombre del firmante (no es arrastre del flujo).
        $origenes = Firmas::whereIn('elemento_id', $elementoIds)
            ->where('estatus', 'Rechazado')
            ->where('is_active', true)
            ->whereNotNull('nombre_firmante')
            ->get()
            ->groupBy('elemento_id')
            ->map(fn(Collection $delElemento) => $delElemento->sortByDesc('fecha')->first());

        $leidas = NotificacionLeida::where('user_id', $user->id)
            ->where('tipo', NotificacionLeida::TIPO_FIRMA_RECHAZADA)
            ->whereIn('elemento_id', $elementoIds)
            ->get()
            ->keyBy('elemento_id');

        return $elementos
            ->map(function (Elemento $elemento) use ($origenes, $leidas, $user) {
                $origen = $origenes->get($elemento->id_elemento);
                $fecha = $origen?->fecha;
                $leida = $leidas->get($elemento->id_elemento);

                // Se oculta solo si el rechazo leído es igual o posterior al actual:
                // un rechazo nuevo del mismo elemento vuelve a notificar.
                if ($leida && (!$fecha || ($leida->evento_at && $leida->evento_at->gte($fecha)))) {
                    return null;
                }

                return [
                    'elemento' => $elemento,
                    'rechazadaPor' => $origen,
                    'fecha' => $fecha,
                    'motivo' => $origen?->comentario_rechazo,
                    'esCreador' => (int) $elemento->created_by === (int) $user->id,
                ];
            })
            ->filter()
            ->sortByDesc(fn(array $item) => $item['fecha']?->timestamp ?? 0)
            ->values();
    }

    /**
     * Marca como leída la notificación de rechazo de un elemento para el usuario.
     */
    public function marcarRechazoLeido(User $user, int $elementoId): void
    {
        $fecha = Firmas::where('elemento_id', $elementoId)
            ->where('estatus', 'Rechazado')
            ->where('is_active', true)
            ->max('fecha');

        NotificacionLeida::updateOrCreate(
            [
                'user_id' => $user->id,
                'tipo' => NotificacionLeida::TIPO_FIRMA_RECHAZADA,
                'elemento_id' => $elementoId,
            ],
            ['evento_at' => $fecha ?? now()]
        );
    }

    /**
     * Texto legible de quién rechazó el documento.
     */
    public function nombreDeQuienRechazo(?Firmas $origen): ?string
    {
        if (!$origen) {
            return null;
        }

        $nombre = trim((string) $origen->nombre_firmante);

        return $nombre !== '' ? $nombre : null;
    }

    /**
     * Ids de empleado ligados al correo del usuario.
     *
     * Un mismo correo puede tener varios registros de empleado (distintos puestos),
     * por eso se consideran todos y no solo el primero.
     *
     * @return array<int, int>
     */
    private function empleadoIdsDe(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return Empleados::where('correo', $user->email)
            ->pluck('id_empleado')
            ->all();
    }
}

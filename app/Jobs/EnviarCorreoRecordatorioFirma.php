<?php

namespace App\Jobs;

use App\Mail\EnviarCorreoRecordatorioFirmas;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCorreoRecordatorioFirma implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_FIRMA_RECORDATORIO)
            ->first();

        if (!$template) {
            return;
        }

        // Obtener firmas pendientes activas que necesitan recordatorio
        $firmasPendientes = Firmas::with('empleado', 'elemento')
            ->where('estatus', 'Pendiente')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_reminder_at')
                    ->orWhere('next_reminder_at', '<=', now());
            })
            ->get();

        if ($firmasPendientes->isEmpty()) {
            return;
        }

        $enviados = 0;

        foreach ($firmasPendientes as $firma) {
            if (!$this->esDePrioridadActual($firma)) {
                continue;
            }

            if (!$firma->empleado?->correo) {
                continue;
            }

            Mail::to($firma->empleado->correo)
                ->send(new EnviarCorreoRecordatorioFirmas(
                    collect([$firma]),
                    $template
                ));

            $firma->last_reminder_at = now();

            $nextReminder = $firma->calcularSiguienteRecordatorio(now());

            $firma->next_reminder_at = $nextReminder->setTime(9, 0, 0);

            $firma->save();

            $enviados++;
        }
    }

    /**
     * Validar que la firma pertenece a la prioridad actual (mínima pendiente) de su elemento.
     * Esto previene envíos a prioridades futuras si aún hay firmas pendientes en prioridades anteriores.
     *
     * @param \App\Models\Firmas $firma
     * @return bool
     */
    private function esDePrioridadActual(Firmas $firma): bool
    {
        $prioridadActualDelElemento = Firmas::obtenerPrioridadMinimaPendiente($firma->elemento_id);

        // Si no hay prioridad pendiente, la firma ya debería estar completada
        if (is_null($prioridadActualDelElemento)) {
            return false;
        }

        // La firma debe ser de la prioridad actual (mínima)
        return $firma->prioridad === $prioridadActualDelElemento;
    }
}

<?php

namespace App\Services;

use App\Jobs\EnviarCorreoFechaVencimiento;
use App\Models\Elemento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ElementoReminderService
{
    public function procesar(Elemento $elemento, Carbon $hoy): void
    {
        if ($elemento->status !== 'Publicado' || !$elemento->periodo_revision) {
            return;
        }

        $hoyDia = $hoy->copy()->startOfDay();
        $revisionDia = $elemento->periodo_revision->copy()->startOfDay();

        $diasRestantes = $hoyDia->diffInDays($revisionDia, false);

        $debeEnviar = $this->debeEnviarHoy(
            $diasRestantes,
            $elemento->last_reminder_sent_at ? $elemento->last_reminder_sent_at->copy() : null,
            $hoyDia
        );

        Log::info('[REMINDER CHECK]', [
            'id' => $elemento->getKey(),
            'dias_restantes' => $diasRestantes,
            'periodo_revision' => $revisionDia->toDateString(),
            'ultimo_envio' => $elemento->last_reminder_sent_at?->toDateString(),
            'se_envia' => $debeEnviar ? 'SI' : 'NO',
        ]);

        if ($debeEnviar) {
            EnviarCorreoFechaVencimiento::dispatch((int) $elemento->getKey());
        }
    }

    private function debeEnviarHoy(int $diasRestantes, ?Carbon $ultimoEnvio, Carbon $hoy): bool
    {
        if ($diasRestantes > 180) {
            return false;
        }

        if ($diasRestantes > 120) {
            return $this->pasaronDias($ultimoEnvio, $hoy, 30);
        }

        if ($diasRestantes > 60) {
            return $this->pasaronDias($ultimoEnvio, $hoy, 15);
        }

        if ($diasRestantes > 0) {
            return $this->pasaronDias($ultimoEnvio, $hoy, 7);
        }

        return $this->pasaronDias($ultimoEnvio, $hoy, 1);
    }

    private function pasaronDias(?Carbon $ultimoEnvio, Carbon $hoy, int $dias): bool
    {
        if (!$ultimoEnvio) {
            return true;
        }

        $u = $ultimoEnvio->copy()->startOfDay();
        $h = $hoy->copy()->startOfDay();

        return $u->diffInDays($h) >= $dias;
    }
}

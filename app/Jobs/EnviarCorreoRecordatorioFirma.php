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
            Log::warning('[FIRMAS] Template no encontrado');
            return;
        }

        $firmasPendientes = Firmas::with('empleado', 'elemento')
            ->where('estatus', 'Pendiente')
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
            Log::info('[FIRMAS] Revisando firma', [
                'firma_id' => $firma->id,
                'next_reminder_at' => $firma->next_reminder_at,
                'now' => now(),
                'last_reminder_at' => $firma->last_reminder_at,
            ]);

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

            Log::info('[FIRMAS] Recordatorio enviado', [
                'firma_id' => $firma->id,
                'last_reminder_at' => $firma->last_reminder_at,
                'next_reminder_at' => $firma->next_reminder_at,
            ]);

            $enviados++;
        }
    }
}

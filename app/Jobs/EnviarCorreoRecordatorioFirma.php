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
        Log::info('[FIRMAS] Job de recordatorios iniciado');

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
            Log::info('[FIRMAS] No hay firmas pendientes');
            return;
        }

        $enviados = 0;

        foreach ($firmasPendientes as $firma) {

            if (!$firma->empleado?->correo) {
                Log::warning('[FIRMAS] Firma sin correo de empleado', [
                    'firma_id' => $firma->id,
                    'empleado_id' => $firma->empleado?->id,
                ]);
                continue;
            }

            Log::info('[FIRMAS] Enviando correo de recordatorio', [
                'firma_id' => $firma->id,
                'empleado' => $firma->empleado->nombre,
                'correo' => $firma->empleado->correo,
                'elemento' => $firma->elemento?->nombre ?? 'N/A',
            ]);

            Mail::to($firma->empleado->correo)
                ->send(new EnviarCorreoRecordatorioFirmas(
                    collect([$firma]),
                    $template
                ));

            $firma->last_reminder_at = now();
            $firma->next_reminder_at = $firma->calcularSiguienteRecordatorio(now());
            $firma->save();

            Log::info('[FIRMAS] Correo enviado exitosamente', [
                'firma_id' => $firma->id,
                'correo' => $firma->empleado->correo,
                'next_reminder_at' => $firma->next_reminder_at?->format('Y-m-d H:i:s'),
            ]);

            $enviados++;
        }

        Log::info('[FIRMAS] Correos enviados y firmas actualizadas', [
            'total_firmas'  => $firmasPendientes->count(),
            'correos_enviados' => $enviados,
        ]);
    }
}

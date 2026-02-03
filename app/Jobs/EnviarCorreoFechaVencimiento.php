<?php

namespace App\Jobs;

use App\Mail\EnviarElementoRecordatorios;
use App\Models\CuerpoCorreo;
use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCorreoFechaVencimiento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private int $elementoId) {}

    public function handle(): void
    {
        $elemento = Elemento::query()
            ->with('puestoResponsable')
            ->whereKey($this->elementoId)
            ->first();

        if (!$elemento || $elemento->status !== 'Publicado') {
            return;
        }

        $responsables = Empleados::query()
            ->where('puesto_trabajo_id', $elemento->puesto_responsable_id)
            ->whereNotNull('correo')
            ->get();

        if ($responsables->isEmpty()) {
            Log::warning("Elemento {$elemento->getKey()} sin responsables con correo.");
            return;
        }

        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_FECHA_REVISION)
            ->first();

        if (!$template) {
            Log::warning("Sin template activo para TIPO_FECHA_REVISION. Elemento {$elemento->getKey()}.");
            return;
        }

        $correosEnviados = 0;

        foreach ($responsables as $responsable) {
            try {
                Mail::to($responsable->correo)->send(
                    new EnviarElementoRecordatorios($elemento, $template, $responsable)
                );
                $correosEnviados++;
            } catch (\Throwable $e) {
                Log::error("Error enviando correo a {$responsable->correo} (Elemento {$elemento->getKey()}): {$e->getMessage()}");
            }
        }

        if ($correosEnviados > 0) {
            $updated = Elemento::query()
                ->whereKey($elemento->getKey())
                ->update([
                    'last_reminder_sent_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info("Elemento {$elemento->getKey()} correos enviados={$correosEnviados} actualizado={$updated}.");
        } else {
            Log::warning("Elemento {$elemento->getKey()} no envió correos; no se actualiza last_reminder_sent_at.");
        }
    }
}

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
use Illuminate\Support\Facades\Mail;

class EnviarCorreoFechaVencimiento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $elementoId
    ) {}

    public function handle(): void
    {
        $elemento = Elemento::with('puestoResponsable')->find($this->elementoId);
        if (! $elemento || ! $elemento->puestoResponsable) {
            return;
        }

        $responsables = Empleados::where(
            'puesto_trabajo_id',
            $elemento->puesto_responsable_id
        )->get();

        if ($responsables->isEmpty()) {
            return;
        }

        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_FECHA_REVISION)
            ->first();

        if (! $template) {
            return;
        }

        foreach ($responsables as $responsable) {
            if (! $responsable->correo) {
                continue;
            }

            Mail::to($responsable->correo)
                ->send(new EnviarElementoRecordatorios(
                    $elemento,
                    $template,
                    $responsable
                ));
        }
    }
}

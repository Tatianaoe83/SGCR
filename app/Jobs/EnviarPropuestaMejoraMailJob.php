<?php

namespace App\Jobs;

use App\Mail\PropuestaMejoraMail;
use App\Models\CuerpoCorreo;
use App\Models\Empleados;
use App\Models\PropuestaMejoras;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarPropuestaMejoraMailJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PropuestaMejoras $propuesta
    ) {}

    public function handle(): void
    {
        \Log::info('Entró al job', [
            'queueable_id' => $this->propuesta->getKey(),
            'primary_key_name' => $this->propuesta->getKeyName(),
        ]);

        $propuesta = PropuestaMejoras::query()
            ->with([
                'empleado:id_empleado,nombres,apellido_paterno,apellido_materno,correo',
                'elemento:id_elemento,nombre_elemento',
            ])
            ->find($this->propuesta->getKey());

        if (!$propuesta) {
            \Log::error('No se encontró la propuesta al rehidratar en el job', [
                'queueable_id' => $this->propuesta->getKey(),
                'primary_key_name' => $this->propuesta->getKeyName(),
            ]);
            return;
        }

        if (!$propuesta->empleado) {
            \Log::error('La propuesta no tiene relación empleado', [
                'propuesta_id' => $propuesta->getKey(),
            ]);
            return;
        }

        if (!$propuesta->elemento) {
            \Log::error('La propuesta no tiene relación elemento', [
                'propuesta_id' => $propuesta->getKey(),
            ]);
            return;
        }

        $coordinadorCalidad = Empleados::query()
            ->with('puestoTrabajo')
            ->whereHas('puestoTrabajo', function ($query) {
                $query->where('nombre', 'Coordinador de Calidad');
            })
            ->whereNotNull('correo')
            ->first();

        if (!$coordinadorCalidad) {
            \Log::error('No se encontró Coordinador de Calidad con correo');
            return;
        }

        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_PROPUESTA_MEJORA)
            ->first();

        if (!$template) {
            \Log::error('Template no encontrado para tipo PROPUESTA_MEJORA');
            return;
        }

        \Log::info('Enviando mail', [
            'to' => 'econg@proser.com.mx',
            'propuesta_id' => $propuesta->getKey(),
        ]);

        Mail::to('econg@proser.com.mx')->send(
            new PropuestaMejoraMail(
                empleado: $coordinadorCalidad,
                template: $template,
                elemento: $propuesta->elemento,
                propuesta: $propuesta,
            )
        );

        \Log::info('Mail enviado');
    }
}

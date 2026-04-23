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
        $propuesta = PropuestaMejoras::query()
            ->with([
                'empleado:id_empleado,nombres,apellido_paterno,apellido_materno,correo',
                'elemento:id_elemento,nombre_elemento',
            ])
            ->find($this->propuesta->getKey());

        if (!$propuesta) {
            return;
        }

        if (!$propuesta->empleado) {
            return;
        }

        if (!$propuesta->elemento) {
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
            return;
        }

        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_PROPUESTA_MEJORA)
            ->first();

        if (!$template) {
            return;
        }

        Mail::to($coordinadorCalidad->correo)->send(
            new PropuestaMejoraMail(
                empleado: $coordinadorCalidad,
                template: $template,
                elemento: $propuesta->elemento,
                propuesta: $propuesta,
            )
        );

    }
}

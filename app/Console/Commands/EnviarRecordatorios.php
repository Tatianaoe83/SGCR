<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioMail;
use App\Models\CuerpoCorreo;
use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar';
    protected $description = 'Envía correos de recordatorio para los elementos que requieren revisión.';

    public function handle()
    {
        $tipoCorreo = CuerpoCorreo::where('tipo', 'recordatorio')
            ->where('activo', 1)
            ->first();

        if (!$tipoCorreo) {
            $this->error("No existe template de recordatorio.");
            return Command::FAILURE;
        }

        $elementos = Elemento::select(
            'id_elemento',
            'nombre_elemento',
            'periodo_revision',
            'puesto_responsable_id',
            'estado_semaforo'
        )
            ->with([
                'responsableEmpleado:id_empleado,nombres,apellido_paterno,apellido_materno,correo'
            ])
            ->get();

        if ($elementos->isEmpty()) {
            $this->error("No hay elementos para procesar.");
            return Command::FAILURE;
        }

        foreach ($elementos as $elemento) {

            $responsable = $elemento->puestoResponsable;

            $empleados = Empleados::where('puesto_trabajo_id', $responsable->id_puesto_trabajo)->get();

            if ($empleados->isEmpty() || !$responsable) {
                $this->warn("Sin empleados asociados al puesto responsable del elemento ID {$elemento->id_elemento}");
                continue;
            }

            $estado = $this->calcularSemaforo($elemento->periodo_revision);

            foreach ($empleados as $empleado) {
                $nombreResponsable = trim(
                    $empleado->nombres . ' ' .
                        $empleado->apellido_paterno . ' ' .
                        $empleado->apellido_materno
                );

                $link = "https://sgcpro.konkret.mx/login";

                if (in_array($estado, ['rojo', 'amarillo'])) {

                    Mail::to($empleado->correo)
                        ->send(new RecordatorioMail(
                            $elemento,
                            $tipoCorreo,
                            $nombreResponsable,
                            $link
                        ));

                    $this->info("{$estado} → Enviado a {$empleado->correo}");
                } else {
                    $this->info("{$estado} → No se envía correo");
                }
            }
        }

        return Command::SUCCESS;
    }

    private function calcularSemaforo($fechaRevision)
    {
        if (!$fechaRevision) {
            return 'sin_fecha';
        }

        $hoy = now();
        $revision = \Carbon\Carbon::parse($fechaRevision);

        $diferenciaMeses = $hoy->diffInMonths($revision, false);

        if ($diferenciaMeses <= 2) {
            return 'rojo';
        } elseif ($diferenciaMeses <= 6) {
            return 'amarillo';
        } elseif ($diferenciaMeses <= 12) {
            return 'verde';
        } else {
            return 'azul';
        }
    }
}

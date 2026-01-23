<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Firmas extends Model
{
    protected $table = 'firmas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'puestoTrabajo_id',
        'empleado_id',
        'elemento_id',
        'tipo',
        'estatus',
        'fecha',
        'comentario_rechazo',
        'timer_recordatorio',
        'last_reminder_at',
        'next_reminder_at'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'empleado_id', 'id_empleado');
    }

    public function puestoTrabajo()
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puestoTrabajo_id', 'id_puesto_trabajo');
    }

    public function elemento()
    {
        return $this->belongsTo(Elemento::class, 'elemento_id', 'id_elemento');
    }

    public function calcularSiguienteRecordatorio(?Carbon $desde = null): Carbon
    {
        $base = $desde ?? now();

        return match ($this->timer_recordatorio) {
            'Diario'     => $base->copy()->addDay(),
            'Cada3Días'  => $base->copy()->addDays(3),
            default      => $base->copy()->addWeek(),
        };
    }
}

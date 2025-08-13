<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empleados extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'id_empleado';


    protected $fillable = [
        'nombres',
        'apellido_materno',
        'apellido_paterno',
        'puesto_trabajo_id',
        'correo',
        'telefono',
    ];


    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }
    

}

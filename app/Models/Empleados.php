<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleados extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'id_empleado';
    public $timestamps = false;

    protected $fillable = [
        'nombres',
        'apellido_materno',
        'apellido_paterno',
        'id_puesto_trabajo',
        'correo',
        'telefono',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleados extends Model
{
    use SoftDeletes;
    protected $table = 'empleados';
    protected $primaryKey = 'id_empleado';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'nombres',
        'apellido_materno',
        'apellido_paterno',
        'puesto_trabajo_id',
        'correo',
        'telefono',
        'fecha_ingreso',
        'fecha_nacimiento',
    ];


    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }

    /**
     * Generar contraseña automática según el patrón especificado
     * 1er letra mayúscula + 3 primeras letras del apellido paterno + 4 letras del nombre + # aleatoria del 0 al 9
     */
    public function generarContrasenaAutomatica(): string
    {
        $primeraLetra = strtoupper(substr($this->nombres, 0, 1));
        
        $apellidoPaterno = substr($this->apellido_paterno, 0, 3);
        
        $nombre = substr($this->nombres, 0, 4);
        
        $numeroAleatorio = rand(0, 9);
        
        $contrasena = $primeraLetra . $apellidoPaterno . $nombre . $numeroAleatorio;
        
        return $contrasena;
    }
    

}

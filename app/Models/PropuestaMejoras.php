<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaMejoras extends Model
{
    protected $table = 'propuestas_mejora';
    protected $primaryKey = 'id_propuesta';

    protected $fillable = [
        'titulo',
        'comentario',
        'justificacion',
        'estatus',
        'id_elemento',
        'id_usuario_solicita',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'id_usuario_solicita', 'id_empleado');
    }

    public function elemento()
    {
        return $this->belongsTo(Elemento::class, 'id_elemento', 'id_elemento');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoProceso extends Model
{
    use SoftDeletes;
    
    protected $table = 'tipo_procesos';
    protected $fillable = ['nombre', 'nivel'];

    protected $primaryKey = 'id_tipo_proceso';

    protected $casts = [
        'nivel' => 'decimal:1', // Cast a decimal con 1 decimal
    ];

    protected $attributes = [
        'nivel' => 1.0, // Valor por defecto
    ];

    // Reglas de validación
    public static $rules = [
        'nombre' => 'required|string|max:255',
        'nivel' => 'required|numeric|min:0|max:99.9',
    ];
}

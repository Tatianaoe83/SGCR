<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ControlCambio extends Model
{
    use SoftDeletes;

    protected $table = 'control_cambios';

    protected $primaryKey = 'id';

    /**
     * Asignación masiva permitida
     */
    protected $fillable = [
        'id_elemento',
        'FolioCambio',
        'Naturaleza',
        'Descripcion',
        'Afectacion',
        'RedaccionCambio',
        'DetalleStatus',
        'Seguimiento',
        'Prioridad',
        'HistorialStatus',
    ];

    public function elemento()
    {
        return $this->belongsTo(Elemento::class, 'id_elemento', 'id_elemento');
    }
}

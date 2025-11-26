<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'area';
    protected $primaryKey = 'id_area';
    
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'nombre',
        'unidad_negocio_id'
    ];

    public function unidadNegocio()
    {
        return $this->belongsTo(UnidadNegocio::class, 'unidad_negocio_id', 'id_unidad_negocio')->withTrashed();
    }
}

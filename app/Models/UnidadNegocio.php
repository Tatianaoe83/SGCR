<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadNegocio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unidad_negocios';
    protected $primaryKey = 'id_unidad_negocio';
    
    protected $dates = ['deleted_at'];

    protected $fillable = ['division_id', 'nombre'];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'id_division')->withTrashed();
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'unidad_negocio_id', 'id_unidad_negocio');
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PuestoTrabajo extends Model
{
    use SoftDeletes;
    
    protected $primaryKey = 'id_puesto_trabajo';
    
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'nombre',
        'division_id',
        'unidad_negocio_id',
        'area_id',
        'puesto_trabajo_id'
    ];

    protected $casts = [
        'puesto_trabajo_id' => 'integer',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id_division');
    }

    public function unidadNegocio(): BelongsTo
    {
        return $this->belongsTo(UnidadNegocio::class, 'unidad_negocio_id', 'id_unidad_negocio');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id_area');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleados::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }

    public function puestosTrabajos(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }
}

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
        'areas_ids',
        'puesto_trabajo_id'
    ];

    protected $casts = [
        'puesto_trabajo_id' => 'integer',
        'areas_ids' => 'array',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id_division');
    }

    public function unidadNegocio(): BelongsTo
    {
        return $this->belongsTo(UnidadNegocio::class, 'unidad_negocio_id', 'id_unidad_negocio');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleados::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }

    public function puestosTrabajos(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }

    public function setAreasIdsAttribute($value)
    {
        $this->attributes['areas_ids'] = json_encode(
            array_map('intval', $value ?? [])
        );
    }

    public function getAreasAttribute()
    {
        $ids = $this->areas_ids ?? [];

        return Area::whereIn('id_area', $ids)->get();
    }
}

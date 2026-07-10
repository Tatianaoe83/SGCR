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
        'unidades_negocio_ids',
        'areas_ids',
        'puesto_trabajo_id'
    ];

    protected $casts = [
        'puesto_trabajo_id' => 'integer',
        'unidades_negocio_ids' => 'array',
        'areas_ids' => 'array',
        'is_global' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id_division');
    }

    public function unidadNegocio(): BelongsTo
    {
        return $this->belongsTo(UnidadNegocio::class, 'unidad_negocio_id', 'id_unidad_negocio');
    }

    public function setUnidadesNegocioIdsAttribute($value)
    {
        $this->attributes['unidades_negocio_ids'] = $value === null
            ? null
            : json_encode(array_map('intval', $value));
    }

    public function getUnidadesNegocioAttribute()
    {
        $ids = $this->unidadesNegocioIdsList();

        if (empty($ids)) {
            return collect();
        }

        return UnidadNegocio::whereIn('id_unidad_negocio', $ids)->get();
    }

    public function esDirector(): bool
    {
        return str_contains(strtolower($this->nombre ?? ''), 'director');
    }

    public function unidadesNegocioIdsList(): array
    {
        if (!empty($this->unidades_negocio_ids) && is_array($this->unidades_negocio_ids)) {
            return array_map('intval', $this->unidades_negocio_ids);
        }

        if (!$this->esDirector() && $this->unidad_negocio_id) {
            return [(int) $this->unidad_negocio_id];
        }

        return [];
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
        $ids = $this->areas_ids;

        if (empty($ids) || !is_array($ids)) {
            return collect();
        }

        return Area::whereIn('id_area', $ids)->get();
    }

    public function isGlobal(): bool
    {
        return $this->is_global === true;
    }
}

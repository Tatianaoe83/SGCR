<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Elemento extends Model
{
    protected $table = 'elementos';
    protected $primaryKey = 'id_elemento';
    
    protected $fillable = [
        'tipo_elemento_id',
        'nombre_elemento',
        'tipo_proceso_id',
        'unidad_negocio_id',
        'ubicacion_eje_x',
        'control',
        'folio_elemento',
        'version_elemento',
        'fecha_elemento',
        'periodo_revision',
        'puesto_responsable_id',
        'puestos_relacionados',
        'es_formato',
        'archivo_formato',
        'puesto_ejecutor_id',
        'puesto_resguardo_id',
        'medio_soporte',
        'ubicacion_resguardo',
        'periodo_resguardo',
        'elemento_padre_id',
        'elemento_relacionado_id',
        'correo_implementacion',
        'correo_agradecimiento',
        'archivo_agradecimiento'
    ];

    protected $casts = [
        'fecha_elemento' => 'date',
        'periodo_revision' => 'date',
        'periodo_resguardo' => 'date',
        'correo_implementacion' => 'boolean',
        'version_elemento' => 'decimal:1',
        'puestos_relacionados' => 'array'
    ];

    // Relaciones
    public function tipoElemento(): BelongsTo
    {
        return $this->belongsTo(TipoElemento::class, 'tipo_elemento_id', 'id_tipo_elemento');
    }

    public function tipoProceso(): BelongsTo
    {
        return $this->belongsTo(TipoProceso::class, 'tipo_proceso_id', 'id_tipo_proceso');
    }

    public function unidadNegocio(): BelongsTo
    {
        return $this->belongsTo(UnidadNegocio::class, 'unidad_negocio_id', 'id_unidad_negocio');
    }

    public function puestoResponsable(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_responsable_id', 'id_puesto_trabajo');
    }

    public function puestoEjecutor(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_ejecutor_id', 'id_puesto_trabajo');
    }

    public function puestoResguardo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_resguardo_id', 'id_puesto_trabajo');
    }

    public function elementoPadre(): BelongsTo
    {
        return $this->belongsTo(Elemento::class, 'elemento_padre_id', 'id_elemento');
    }

    public function elementoRelacionado(): BelongsTo
    {
        return $this->belongsTo(Elemento::class, 'elemento_relacionado_id', 'id_elemento');
    }

    public function elementosHijos(): HasMany
    {
        return $this->hasMany(Elemento::class, 'elemento_padre_id', 'id_elemento');
    }

    public function elementosRelacionados(): HasMany
    {
        return $this->hasMany(Elemento::class, 'elemento_relacionado_id', 'id_elemento');
    }
}

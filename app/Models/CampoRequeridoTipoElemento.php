<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampoRequeridoTipoElemento extends Model
{
    protected $table = 'campos_requeridos_tipo_elemento';
    
    protected $fillable = [
        'tipo_elemento_id',
        'campo_nombre',
        'campo_label',
        'es_requerido',
        'es_obligatorio',
        'orden'
    ];
    
    protected $casts = [
        'es_requerido' => 'boolean',
        'es_obligatorio' => 'boolean',
        'orden' => 'integer'
    ];
    
    /**
     * Relación con TipoElemento
     */
    public function tipoElemento(): BelongsTo
    {
        return $this->belongsTo(TipoElemento::class, 'tipo_elemento_id', 'id_tipo_elemento');
    }
    
    /**
     * Scope para campos requeridos
     */
    public function scopeRequeridos($query)
    {
        return $query->where('es_requerido', true);
    }
    
    /**
     * Scope para campos obligatorios
     */
    public function scopeObligatorios($query)
    {
        return $query->where('es_obligatorio', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'nombres_relacion',
        'es_formato',
        'archivo_es_formato',
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
        'estado_semaforo'
    ];

    protected $casts = [
        'fecha_elemento' => 'date',
        'periodo_revision' => 'date',
        'periodo_resguardo' => 'date',
        'correo_implementacion' => 'boolean',
        'version_elemento' => 'decimal:1',
        'puestos_relacionados' => 'array',
        'nombres_relacion' => 'array',
        'elementos_padre' => 'array',
        'elementos_relacionado_id' => 'array',
        'unidad_negocio_id' => 'integer',
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

    public function wordDocument(): HasOne
    {
        return $this->hasOne(WordDocument::class, 'elemento_id', 'id_elemento');
    }

    public function elementosRelacionados(): HasMany
    {
        return $this->hasMany(Elemento::class, 'elemento_relacionado_id', 'id_elemento');
    }

    /**
     * Obtener el estado del semáforo basado en el periodo de revisión
     */
    public function getEstadoSemaforoAttribute()
    {
        if (!$this->periodo_revision) {
            return 'sin fecha';
        }

        $hoy = now();
        $periodoRevision = $this->periodo_revision;
        $diferencia = $hoy->diffInMonths($periodoRevision, false);

        if ($diferencia <= 2) {
            return 'rojo'; // Crítico: today - 2 meses
        } elseif ($diferencia >= 4 && $diferencia <= 6) {
            return 'amarillo'; // Advertencia: 4-6 meses
        } elseif ($diferencia >= 6 && $diferencia <= 12) {
            return 'verde'; // Normal: 6 meses a 1 año
        } else {
            return 'azul'; // Lejano: más de 1 año
        }
    }

    /**
     * Obtener la clase CSS del semáforo
     */
    public function getClaseSemaforoAttribute()
    {
        $estado = $this->estado_semaforo;

        switch ($estado) {
            case 'rojo':
                return 'bg-red-500 text-white';
            case 'amarillo':
                return 'bg-yellow-500 text-black';
            case 'verde':
                return 'bg-green-500 text-white';
            case 'azul':
                return 'bg-blue-500 text-white';
            default:
                return 'bg-gray-500 text-white';
        }
    }

    /**
     * Obtener el texto del semáforo
     */
    public function getTextoSemaforoAttribute()
    {
        $estado = $this->estado_semaforo;

        switch ($estado) {
            case 'rojo':
                return ['texto' => 'Crítico', 'color' => 'bg-red-500'];
            case 'amarillo':
                return ['texto' => 'Advertencia', 'color' => 'bg-yellow-500'];
            case 'verde':
                return ['texto' => 'Normal', 'color' => 'bg-green-500'];
            case 'azul':
                return ['texto' => 'Lejano', 'color' => 'bg-blue-500'];
            default:
                return ['texto' => 'Sin fecha', 'color' => 'bg-gray-300'];
        }
    }


    /**
     * Obtener usuarios seleccionados para correos
     */
    public function usuariosCorreo()
    {
        if (!$this->usuarios_correo) {
            return collect();
        }

        return User::whereIn('id', $this->usuarios_correo)->get();
    }

    /**
     * Obtener todos los correos (usuarios + libres)
     */
    public function getAllCorreosAttribute()
    {
        $correos = collect();

        // Agregar correos de usuarios seleccionados
        if ($this->usuarios_correo) {
            $usuarios = User::whereIn('id', $this->usuarios_correo)->get();
            $correos = $correos->merge($usuarios->pluck('email'));
        }

        // Agregar correos libres
        if ($this->correos_libres) {
            $correos = $correos->merge($this->correos_libres);
        }

        return $correos->filter()->unique();
    }
}

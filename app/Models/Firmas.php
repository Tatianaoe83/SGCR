<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Firmas extends Model
{
    protected $table = 'firmas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'puestoTrabajo_id',
        'empleado_id',
        'elemento_id',
        'tipo',
        'estatus',
        'fecha',
        'prioridad',
        'comentario_rechazo',
        'timer_recordatorio',
        'last_reminder_at',
        'next_reminder_at',
        'email_sent_at',
        'evidencia_rechazo_path',
        'nombre_firmante',
        'puesto_firmante',
        'firma_snapshot_path',
        'firma_snapshot_hash',
        'firma_ip',
        'firma_user_agent',
        'is_active'
    ];

    protected $casts = [
        'last_reminder_at' => 'date',
        'next_reminder_at' => 'date',
        'email_sent_at' => 'date',
        'evidencia_rechazo_path' => 'array',
        'fecha' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'empleado_id', 'id_empleado');
    }

    public function puestoTrabajo()
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puestoTrabajo_id', 'id_puesto_trabajo');
    }

    public function elemento()
    {
        return $this->belongsTo(Elemento::class, 'elemento_id', 'id_elemento');
    }

    public function calcularSiguienteRecordatorio(?Carbon $desde = null): Carbon
    {
        $base = $desde ?? now();

        return match ($this->timer_recordatorio) {
            'Diario'     => $base->copy()->addDay(),
            'Cada3Días'  => $base->copy()->addDays(3),
            default      => $base->copy()->addWeek(),
        };
    }

    /**
     * Obtener la prioridad mínima pendiente para un elemento específico.
     * Esto determina cuál es la prioridad actual que debe recibir recordatorios.
     *
     * @param int $elementoId
     * @return int|null La prioridad mínima o null si no hay firmas pendientes
     */
    public static function obtenerPrioridadMinimaPendiente(int $elementoId): ?int
    {
        return static::query()
            ->where('elemento_id', $elementoId)
            ->where('estatus', 'Pendiente')
            ->where('is_active', true)
            ->min('prioridad');
    }

    /**
     * Scope: Obtener firmas pendientes de un elemento con prioridad específica.
     * Filtra activas y pendientes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $elementoId
     * @param int $prioridad
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeElementoYPrioridad($query, int $elementoId, int $prioridad)
    {
        return $query
            ->where('elemento_id', $elementoId)
            ->where('prioridad', $prioridad)
            ->where('estatus', 'Pendiente')
            ->where('is_active', true);
    }

    /**
     * Scope: Firmas que necesitan recordatorio enviado.
     * Valida que tenga fecha de siguiente recordatorio y que haya llegado su momento.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQueNecesitanRecordatorio($query)
    {
        return $query
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now());
    }
}

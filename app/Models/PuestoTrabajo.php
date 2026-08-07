<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class PuestoTrabajo extends Model
{
    use SoftDeletes;

    /**
     * Niveles que pueden abarcar varias unidades de negocio. Se deducen de la
     * primera palabra del nombre del puesto: "Gerente de Compras" es Gerente.
     */
    public const NIVELES_MULTIUNIDAD = ['director', 'subdirector', 'gerente'];

    /** Formas femeninas que apuntan al mismo nivel. */
    private const EQUIVALENCIAS_NIVEL = [
        'directora' => 'director',
        'subdirectora' => 'subdirector',
        'gerenta' => 'gerente',
    ];

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

    protected static function booted(): void
    {
        // unidad_negocio_id se conserva como la unidad principal para el codigo
        // que todavia espera un solo valor.
        static::saving(function (self $puesto) {
            $ids = $puesto->unidades_negocio_ids;

            if (is_array($ids) && $ids !== []) {
                $puesto->attributes['unidad_negocio_id'] = array_values($ids)[0];
                return;
            }

            if ($puesto->unidad_negocio_id) {
                $puesto->unidades_negocio_ids = [$puesto->unidad_negocio_id];
            }
        });
    }

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

    public function setUnidadesNegocioIdsAttribute($value): void
    {
        $this->attributes['unidades_negocio_ids'] = json_encode(
            array_values(array_unique(array_map('intval', $value ?? [])))
        );
    }

    public function setAreasIdsAttribute($value): void
    {
        $this->attributes['areas_ids'] = json_encode(
            array_values(array_unique(array_map('intval', $value ?? [])))
        );
    }

    public function getUnidadesNegocioAttribute(): Collection
    {
        $ids = $this->unidades_negocio_ids;

        if (empty($ids) || !is_array($ids)) {
            return collect();
        }

        return UnidadNegocio::whereIn('id_unidad_negocio', $ids)
            ->orderBy('nombre')
            ->get();
    }

    public function getAreasAttribute(): Collection
    {
        $ids = $this->areas_ids;

        if (empty($ids) || !is_array($ids)) {
            return collect();
        }

        return Area::whereIn('id_area', $ids)
            ->orderBy('nombre')
            ->get();
    }

    public function isGlobal(): bool
    {
        return $this->is_global === true;
    }

    public function permiteMultiplesUnidades(): bool
    {
        return self::nombrePermiteMultiplesUnidades($this->nombre);
    }

    /**
     * Directores, Subdirectores y Gerentes pueden abarcar varias unidades de negocio.
     */
    public static function nombrePermiteMultiplesUnidades(?string $nombre): bool
    {
        return in_array(self::nivelDesdeNombre($nombre), self::NIVELES_MULTIUNIDAD, true);
    }

    /**
     * Nivel del puesto según la primera palabra de su nombre, normalizada.
     * "Sub-director de Obra", "Subdirectora de Obra" y "Subdirector" dan "subdirector".
     */
    public static function nivelDesdeNombre(?string $nombre): string
    {
        $nombre = trim((string) $nombre);

        // "Sub director" y "Sub-director" son una sola palabra.
        $nombre = preg_replace('/^sub\s*-\s*/iu', 'sub', $nombre);
        $nombre = preg_replace('/^sub\s+(?=director)/iu', 'sub', $nombre);

        $primera = preg_split('/\s+/u', $nombre, 2)[0] ?? '';

        $primera = strtr(mb_strtolower($primera, 'UTF-8'), [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);

        return self::EQUIVALENCIAS_NIVEL[$primera] ?? $primera;
    }

    /**
     * Puestos que incluyen la unidad de negocio indicada, sin importar si es la principal.
     */
    public function scopeDeUnidadNegocio(Builder $query, int $unidadNegocioId): Builder
    {
        return $query->where(function (Builder $q) use ($unidadNegocioId) {
            $q->where('unidad_negocio_id', $unidadNegocioId)
                ->orWhereJsonContains('unidades_negocio_ids', $unidadNegocioId);
        });
    }
}

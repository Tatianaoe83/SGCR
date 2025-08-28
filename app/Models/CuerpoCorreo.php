<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuerpoCorreo extends Model
{
    use HasFactory;

    protected $table = 'cuerpos_correo';
    protected $primaryKey = 'id_cuerpo';

    protected $fillable = [
        'nombre',
        'cuerpo_html',
        'cuerpo_texto',
        'tipo',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Tipos de correo disponibles
    const TIPO_ACCESO = 'acceso';
    const TIPO_IMPLEMENTACION = 'implementacion';
    const TIPO_AGRADECIMIENTO = 'agradecimiento';

    /**
     * Obtener todos los tipos disponibles
     */
    public static function getTipos()
    {
        return [
            self::TIPO_ACCESO => 'Acceso al Portal',
            self::TIPO_IMPLEMENTACION => 'Implementación',
            self::TIPO_AGRADECIMIENTO => 'Agradecimiento'
        ];
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar solo activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener el nombre del tipo
     */
    public function getTipoNombreAttribute()
    {
        return self::getTipos()[$this->tipo] ?? $this->tipo;
    }
}

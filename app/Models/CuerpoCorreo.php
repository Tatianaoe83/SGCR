<?php

namespace App\Models;

use Spatie\MailTemplates\Models\MailTemplate as BaseMailTemplate;

class CuerpoCorreo extends BaseMailTemplate
{
    protected $table = 'cuerpos_correo';
    protected $primaryKey = 'id_cuerpo';

    protected $fillable = [
        'nombre',
        'subject',
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
    const TIPO_FECHA = 'fecha_vencimiento';

    /**
     * Obtener todos los tipos disponibles
     */
    public static function getTipos()
    {
        return [
            self::TIPO_ACCESO => 'Acceso al Portal',
            self::TIPO_IMPLEMENTACION => 'Implementación',
            self::TIPO_AGRADECIMIENTO => 'Agradecimiento',
            self::TIPO_FECHA => 'Fecha de Revisión'
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

    public function getHtmlTemplate(): string
    {
        return $this->cuerpo_html ?? '';
    }

    public function getTextTemplate(): string
    {
        return $this->cuerpo_texto ?? strip_tags($this->cuerpo_html);
    }

    /**
     * Obtener el asunto del correo
     */
    public function getSubject(): string
    {
        return $this->subject ?? $this->getDefaultSubject();
    }

    /**
     * Obtener asunto por defecto basado en el tipo
     */
    private function getDefaultSubject(): string
    {
        $defaultSubjects = [
            self::TIPO_ACCESO => 'Acceso al Portal SGCR',
            self::TIPO_IMPLEMENTACION => 'Implementación de Elemento',
            self::TIPO_AGRADECIMIENTO => 'Agradecimiento',
            self::TIPO_FECHA => 'Recordatorio de Fecha de Revisión'
        ];

        return $defaultSubjects[$this->tipo] ?? 'Notificación SGCR';
    }
}

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

    const TIPO_ACCESO                   = 'acceso';
    const TIPO_IMPLEMENTACION           = 'implementacion';
    const TIPO_AGRADECIMIENTO           = 'agradecimiento';
    const TIPO_FECHA_REVISION           = 'fecha_vencimiento';
    const TIPO_FIRMA_APROBADO           = 'documento_aprobado';
    const TIPO_FIRMA_RECHAZADO          = 'documento_rechazado';
    const TIPO_FIRMA_DOCUMENTO          = 'firma_documento';
    const TIPO_FIRMA_RECORDATORIO      = 'firma_recordatorio';

    /**
     * Tipos disponibles
     */
    public static function getTipos(): array
    {
        return [
            self::TIPO_ACCESO         => 'Bienvenida - Nuevo Usuario',
            self::TIPO_IMPLEMENTACION => 'Notificación - Elemento Implementado',
            self::TIPO_AGRADECIMIENTO => 'Agradecimiento - Colaboración',
            self::TIPO_FECHA_REVISION => 'Fecha de Revisión',
            self::TIPO_FIRMA_APROBADO => 'Notificación - Documento Firmado Aprobado',
            self::TIPO_FIRMA_RECHAZADO=> 'Notificación - Documento Firmado Rechazado',
            self::TIPO_FIRMA_DOCUMENTO=> 'Notificación - Firma de Documento',
            self::TIPO_FIRMA_RECORDATORIO=> 'Notificación - Recordatorio Firma de Documento',
        ];
    }

    /**
     * Scope por tipo
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Nombre legible del tipo
     */
    public function getTipoNombreAttribute(): string
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

    public function getSubject(): string
    {
        return $this->subject ?? $this->getDefaultSubject();
    }

    private function getDefaultSubject(): string
    {
        $defaultSubjects = [
            self::TIPO_ACCESO         => 'Bienvenido al Portal SGCR',
            self::TIPO_IMPLEMENTACION => 'Elemento Implementado',
            self::TIPO_AGRADECIMIENTO => 'Gracias por su colaboración',
            self::TIPO_FECHA_REVISION => 'Recordatorio de Fecha de Revisión',
            self::TIPO_FIRMA_APROBADO => 'Documento Aprobado',
            self::TIPO_FIRMA_RECHAZADO=> 'Documento Rechazado',
            self::TIPO_FIRMA_DOCUMENTO=> 'Asignación de Firmante',
            self::TIPO_FIRMA_RECORDATORIO=> 'Recordatorio de Firma de Documento',
        ];

        return $defaultSubjects[$this->tipo] ?? 'Notificación SGCR';
    }
}

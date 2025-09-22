<?php

namespace App\Mail;

use Spatie\MailTemplates\TemplateMailable;
use Spatie\MailTemplates\Interfaces\MailTemplateInterface;
use App\Models\CuerpoCorreo;

class AccesoMail extends TemplateMailable
{
    public string $template = 'acceso';

    public function __construct() {}

    /* public function placeholders(): array
    {
        return [
            '{{nombre}}' => $this->nombre,
            '{{correo}}' => $this->correo,
            '{{contraseña}}' => $this->contraseña
        ];
    } */

    public function resolveTemplateModel(): MailTemplateInterface
    {
        return CuerpoCorreo::where('tipo', $this->template)->firstOrFail();
    }
}

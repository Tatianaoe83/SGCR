<?php

namespace App\Mail;

use Spatie\MailTemplates\TemplateMailable;

class AgradecimientoMail extends TemplateMailable
{
    public string $template = 'agradecimiento';

    public function __construct() {}

    /* public function placeholders(): array
    {
        return [
            '{{nombreProceso}}' => $this->nombreProceso,
            '{{folio}}' => $this->folio,
            '{{link}}' => $this->link
        ];
    } */

    public function resolveTemplateModel(): MailTemplateInterface
    {
        return CuerpoCorreo::where('tipo', $this->template)->firstOrFail();
    }
}

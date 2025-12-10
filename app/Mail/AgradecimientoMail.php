<?php

namespace App\Mail;

use Spatie\MailTemplates\TemplateMailable;

class AgradecimientoMail extends TemplateMailable
{
    public string $template = 'agradecimiento';

    public function __construct() {}

    public function resolveTemplateModel(): MailTemplateInterface
    {
        return CuerpoCorreo::where('tipo', $this->template)->firstOrFail();
    }
}

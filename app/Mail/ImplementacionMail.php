<?php

namespace App\Mail;

use Spatie\MailTemplates\TemplateMailable;

class ImplementacionMail extends TemplateMailable
{
    public string $template = 'implementacion';

    public function __construct() {}

    public function resolveTemplateModel(): MailTemplateInterface
    {
        return CuerpoCorreo::where('tipo', $this->template)->firstOrFail();
    }
}

<?php

namespace App\Mail;

use App\Models\CuerpoCorreo;
use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Mail\Mailable;

class EnviarElementoRecordatorios extends Mailable
{
    public function __construct(
        private Elemento $elemento,
        private CuerpoCorreo $template,
        private Empleados $responsable
    ){}

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $html = str_replace(
            '{{elemento}}',
            $this->elemento->nombre_elemento,
            $html
        );

        $html = str_replace(
            '{{responsable}}',
            trim("{$this->responsable->nombres} {$this->responsable->apellido_paterno} {$this->responsable->apellido_materno}"),
            $html
        );

        $html = str_replace('{{fecha}}', $this->elemento->periodo_revision, $html);

        $html = str_replace('{{link}}', '', $html);

        return $this
            ->subject($this->template->subject)
            ->html($html);
    }
}

<?php

namespace App\Mail;

use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Mail\Mailable;

class EnviarCorreoRecordatorioFirmas extends Mailable
{
    public function __construct(private Firmas $firmas, private CuerpoCorreo $template) {}

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $nombreCompleto = implode(' ', array_filter([
            $this->firma->empleado->nombres ?? null,
            $this->firma->empleado->apellido_paterno ?? null,
            $this->firma->empleado->apellido_materno ?? null,
        ]));

        $html = str_replace('{{elemento}}', $this->firmas->elemento->nombre_elemento, $html);

        $html = str_replace('{{responsable}}', $nombreCompleto, $html);

        return $this->subject($this->template->subject)
            ->html($html);
    }
}

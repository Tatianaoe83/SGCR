<?php

namespace App\Mail;

use App\Models\Elemento;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Mail\Mailable;

class FirmaRechazadaMail extends Mailable
{
    public function __construct(
        private Elemento $elemento,
        private Firmas $firma,
        private CuerpoCorreo $template
    ) {}

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $nombreCompleto = implode(' ', array_filter([
            $this->firma->empleado->nombres ?? null,
            $this->firma->empleado->apellido_paterno ?? null,
            $this->firma->empleado->apellido_materno ?? null,
        ]));

        $html = str_replace('{{responsable}}', $nombreCompleto, $html);
        $html = str_replace('{{elemento}}', $this->elemento->nombre_elemento, $html);
        $html = str_replace('{{motivo}}', $this->firma->comentario_rechazo ?? '', $html);
        $html = str_replace('{{link}}', '', $html);

        return $this
            ->subject($this->template->subject ?? 'Documento pendiente de firma')
            ->html($html);
    }
}

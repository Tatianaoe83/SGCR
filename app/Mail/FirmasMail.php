<?php

namespace App\Mail;

use App\Models\Elemento;
use App\Models\Firmas;
use App\Models\CuerpoCorreo;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\URL;

class FirmasMail extends Mailable
{
    public function __construct(
        private Elemento $elemento,
        private Firmas $firma,
        private CuerpoCorreo $template
    ) {}

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $link = URL::signedRoute('revision.documento', [
            'id'    => $this->elemento->id_elemento,
            'firma' => $this->firma->id,
        ]);

        $html = str_replace('{{responsable}}', ($this->firma->empleado->nombres . ' ' . $this->firma->empleado->apellido_paterno . ' ' . $this->firma->empleado->apellido_materno), $html);
        $html = str_replace('{{elemento}}', $this->elemento->nombre_elemento, $html);
        $html = str_replace('{{link}}', $link, $html);

        return $this
            ->subject($this->template->subject)
            ->html($html);
    }
}

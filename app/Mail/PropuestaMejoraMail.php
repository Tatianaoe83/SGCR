<?php

namespace App\Mail;

use App\Models\CuerpoCorreo;
use App\Models\Elemento;
use App\Models\Empleados;
use App\Models\PropuestaMejoras;
use Illuminate\Mail\Mailable;

class PropuestaMejoraMail extends Mailable
{
    public function __construct(
        private Empleados $empleado,
        private CuerpoCorreo $template,
        private Elemento $elemento,
        private PropuestaMejoras $propuesta,
    ) {}

    public function build()
    {

        $link = route('propuestas.revision', [
            'propuesta' => $this->propuesta
        ]);

        $justificacionCorta = Str::limit(
            trim(strip_tags((string) $this->propuesta->justificacion)),
            80,
            '...'
        );

        $html = $this->template->cuerpo_html;
        $html = str_replace('{{responsable}}', ($this->empleado->nombres . ' ' . $this->empleado->apellido_paterno . ' ' . $this->empleado->apellido_materno), $html);
        $html = str_replace('{{elemento}}', $this->elemento->nombre_elemento, $html);
        $html = str_replace('{{justificacion}}', $justificacionCorta, $html);
        $html = str_replace('{{link}}', $link, $html);

        return $this
            ->subject($this->template->subject)
            ->html($html);
    }
}

<?php

namespace App\Mail;

use App\Models\CuerpoCorreo;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class EnviarCorreoRecordatorioFirmas extends Mailable
{
    public function __construct(private Collection $firmas, private CuerpoCorreo $template) {}

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $elementos = $this->firmas
            ->map(fn($f) => $f->elemento?->nombre_elemento)
            ->filter(fn($e) => $e !== null)
            ->unique()
            ->implode(', ');

        $empleados = $this->firmas
            ->map(fn($f) => trim(
                ($f->empleado->nombres ?? '') . ' ' .
                    ($f->empleado->apellido_paterno ?? '') . ' ' .
                    ($f->empleado->apellido_materno ?? '')
            ))
            ->filter()
            ->unique()
            ->implode(', ');

        $links = $this->firmas
            ->map(function ($firma) {
                if (!$firma->elemento) {
                    return null;
                }

                return URL::signedRoute('revision.documento', [
                    'id'    => $firma->elemento->id_elemento,
                    'firma' => $firma->id,
                ]);
            })
            ->filter()
            ->unique()
            ->implode("\n");

        $html = str_replace('{{responsable}}', $empleados, $html);
        $html = str_replace('{{elemento}}', $elementos, $html);
        $html = str_replace('{{link}}', $links, $html);

        return $this
            ->subject($this->template->subject)
            ->html($html);
    }
}

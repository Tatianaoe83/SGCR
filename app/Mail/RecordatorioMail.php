<?php

namespace App\Mail;

use App\Models\Elemento;
use App\Models\CuerpoCorreo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    public Elemento $elemento;
    public CuerpoCorreo $correo;
    public string $responsable;
    public string $link;

    public function __construct(Elemento $elemento, CuerpoCorreo $correo, string $responsable, string $link)
    {
        $this->elemento = $elemento;
        $this->correo = $correo;
        $this->responsable = $responsable;
        $this->link = $link;
    }

    public function build()
    {
        $html = $this->correo->cuerpo_html;

        $html = str_replace('{{responsable}}', $this->responsable, $html);
        $html = str_replace('{{elemento}}', $this->elemento->nombre_elemento, $html);
        $html = str_replace('{{fecha}}', now()->format('d/m/Y'), $html);

        return $this
            ->subject($this->correo->subject ?? 'Recordatorio de Documento Pendiente')
            ->html($html);
    }
}

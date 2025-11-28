<?php

namespace App\Mail;

use App\Models\CuerpoCorreo;
use App\Models\Elemento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    public $elemento;
    public $correo;

    /**
     * Create a new message instance.
     */
    public function __construct(Elemento $elemento, CuerpoCorreo $cuerpCorreo)
    {
        $this->elemento = $elemento;
        $this->cuerpoCorreo = $correo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de Revisión',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $content = $this->cuerpoCorreo->cuerpo_html;

        $content = str_replace('{{nombre}}', $this->elemento->nombre_elemento, $content);
        $content = str_replace('{{fecha_revision}}', $this->elemento->periodo_revision, $content);

        return new Content(
            html: $content
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

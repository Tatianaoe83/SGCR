<?php

namespace App\Mail;

use App\Models\Elemento;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

class FirmaRechazadaMail extends Mailable
{
    public function __construct(
        private Elemento $elemento,
        private ?Firmas $firma,
        private CuerpoCorreo $template
    ) {}

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $html = str_replace('{{elemento}}', $this->elemento->nombre_elemento, $html);
        $html = str_replace('{{motivo}}', $this->firma->comentario_rechazo ?? '', $html);
        $html = str_replace('{{link}}', '', $html);

        $mail = $this
            ->subject($this->template->subject ?? 'Documento pendiente de firma')
            ->html($html);

        $this->attachEvidencias();

        return $mail;
    }

    private function attachEvidencias(): void
    {
        if (!$this->firma) return;

        $disk = Storage::disk('public');

        // 1. Attach the annotated PDF (new flow) — shown first so it's prominent
        $anotadoPdf = $this->firma->anotaciones_pdf_path ?? null;
        if ($anotadoPdf && $disk->exists($anotadoPdf)) {
            $this->attachFromStorageDisk('public', $anotadoPdf, basename($anotadoPdf), [
                'mime' => 'application/pdf',
            ]);
        }

        // 2. Attach legacy evidencias files (old flow / additional files)
        $paths = $this->firma->evidencias_rechazo_paths ?? null;

        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($paths)) {
            $paths = [];
        }

        if (empty($paths)) {
            $single = $this->firma->evidencia_rechazo_path ?? null;

            if (is_array($single)) {
                $paths = $single;
            } elseif (is_string($single) && trim($single) !== '') {
                $paths = [trim($single)];
            }
        }

        $paths = array_values(array_filter($paths, fn($p) => is_string($p) && trim($p) !== ''));

        foreach ($paths as $path) {
            if (!$disk->exists($path)) continue;

            $name = basename($path);
            $mime = $disk->mimeType($path) ?: 'application/octet-stream';

            $this->attachFromStorageDisk('public', $path, $name, ['mime' => $mime]);
        }
    }
}

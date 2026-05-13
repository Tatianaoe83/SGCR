<?php

namespace App\Mail;

use App\Models\Elemento;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class FirmaAprobadaMail extends Mailable
{
    public function __construct(
        private Elemento $elemento,
        private Firmas $firma,
        private CuerpoCorreo $template,
        private ?Collection $firmasAprobadas = null
    ) {
    }

    public function build()
    {
        $html = $this->template->cuerpo_html;

        $nombreCompleto = implode(' ', array_filter([
            $this->firma->empleado->nombres ?? null,
            $this->firma->empleado->apellido_paterno ?? null,
            $this->firma->empleado->apellido_materno ?? null,
        ]));

        // Obtener todas las firmas aprobadas si no se pasaron
        if (!$this->firmasAprobadas) {
            $this->firmasAprobadas = Firmas::where('elemento_id', $this->elemento->id_elemento)
                ->where('estatus', 'Aprobado')
                ->where('is_active', true)
                ->with('empleado')
                ->orderBy('fecha', 'asc')
                ->get();
        }

        $comentariosHtml = '';
        $firmasConComentario = $this->firmasAprobadas->filter(fn($f) => !empty($f->comentario_aceptacion));

        // Deduplicar por empleado (mostrar solo un comentario por empleado)
        $firmasUnicas = $firmasConComentario->unique(function ($firma) {
            return $firma->empleado_id;
        })->values();

        if ($firmasUnicas->isNotEmpty()) {
            $comentariosHtml = '<div style="margin:16px 0; border-left:4px solid #10b981; border-radius:6px; padding:12px 14px; background:linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);">';
            $comentariosHtml .= '<h3 style="margin:0 0 10px 0; font-size:12px; font-weight:700; color:#047857; text-transform:uppercase; letter-spacing:0.5px;">✓ Comentarios de Aprobación</h3>';
            
            foreach ($firmasUnicas as $firmaAprobada) {
                $nombreFirmante = implode(' ', array_filter([
                    $firmaAprobada->empleado->nombres ?? null,
                    $firmaAprobada->empleado->apellido_paterno ?? null,
                    $firmaAprobada->empleado->apellido_materno ?? null,
                ]));
                
                $puestoFirmante = $firmaAprobada->puestoTrabajo->nombre ?? 'Puesto no especificado';
                
                $comentariosHtml .= '<div style="margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid rgba(16,185,129,0.2);">';
                $comentariosHtml .= '<p style="margin:0 0 4px 0; font-weight:600; font-size:12px; color:#059669;">';
                $comentariosHtml .= htmlspecialchars($nombreFirmante);
                $comentariosHtml .= '<span style="font-weight:400; color:#10b981;"> • ' . htmlspecialchars($puestoFirmante) . '</span>';
                $comentariosHtml .= '</p>';
                $comentariosHtml .= '<p style="margin:0; font-size:12px; color:#1f2937; line-height:1.4;">';
                $comentariosHtml .= htmlspecialchars($firmaAprobada->comentario_aceptacion);
                $comentariosHtml .= '</p>';
                $comentariosHtml .= '</div>';
            }
            
            $comentariosHtml .= '</div>';
        }

        $html = str_replace('{{responsable}}', $nombreCompleto, $html);
        $html = str_replace('{{elemento}}', $this->elemento->nombre_elemento, $html);
        $html = str_replace('{{comentario}}', $comentariosHtml, $html);
        $html = str_replace('{{link}}', '', $html);

        return $this
            ->subject($this->template->subject ?? 'Documento pendiente de firma')
            ->html($html);
    }
}

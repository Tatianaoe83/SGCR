<?php

namespace App\Jobs;

use App\Mail\FirmaAprobadaMail;
use App\Mail\FirmaRechazadaMail;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarFirmaRespuestaMail implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        private int $firmaId,
        private string $evento
    ) {}

    public function handle(): void
    {
        $firma = Firmas::with('elemento')->find($this->firmaId);

        if (!$firma || !$firma->elemento) {
            return;
        }

        $tipoTemplate = match ($this->evento) {
            'aprobado'  => CuerpoCorreo::TIPO_FIRMA_APROBADO,
            'rechazado' => CuerpoCorreo::TIPO_FIRMA_RECHAZADO,
            default     => null,
        };

        if (!$tipoTemplate) {
            return;
        }

        $template = CuerpoCorreo::activos()
            ->porTipo($tipoTemplate)
            ->first();

        if (!$template) {
            return;
        }

        match ($this->evento) {
            'aprobado'  => $this->handleAprobado($firma, $template),
            'rechazado' => $this->handleRechazado($firma, $template),
        };
    }

    private function handleAprobado(Firmas $firmaOrigen, $template): void
    {
        if ($firmaOrigen->elemento->status !== 'Publicado') {
            return;
        }

        $firmasDestino = Firmas::where('elemento_id', $firmaOrigen->elemento_id)
            ->whereIn('tipo', 'Participante')
            ->with('empleado')
            ->get();

        if ($firmasDestino->isEmpty()) {
            return;
        }

        foreach ($firmasDestino as $firmaDestino) {

            if (!$firmaDestino->empleado || !$firmaDestino->empleado->correo) {
                continue;
            }

            Mail::to($firmaDestino->empleado->correo)->send(
                new FirmaAprobadaMail(
                    $firmaOrigen->elemento,
                    $firmaDestino,
                    $template
                )
            );
        }
    }

    private function handleRechazado(Firmas $firmaOrigen, $template): void
    {
        $firmasResponsables = Firmas::where('elemento_id', $firmaOrigen->elemento_id)
            ->where('tipo', 'Responsable')
            ->with('empleado')
            ->get();

        if ($firmasResponsables->isEmpty()) {
            return;
        }

        foreach ($firmasResponsables as $firmaResponsable) {

            if (!$firmaResponsable->empleado || !$firmaResponsable->empleado->correo) {
                continue;
            }

            Mail::to($firmaResponsable->empleado->correo)
                ->cc('tordonez@proser.com.mx')
                ->send(
                    new FirmaRechazadaMail(
                        $firmaOrigen->elemento,
                        $firmaResponsable,
                        $template
                    )
                );
        }
    }
}

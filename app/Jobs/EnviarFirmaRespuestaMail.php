<?php

namespace App\Jobs;

use App\Mail\FirmaAprobadaMail;
use App\Mail\FirmaRechazadaMail;
use App\Models\CuerpoCorreo;
use App\Models\Empleados;
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

        if (!$firma || !$firma->elemento || !$firma->is_active) {
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
            ->where('tipo', 'Participante')
            ->where('is_active', true)
            ->with('empleado')
            ->get()
            ->filter(function ($firmaDestino) {
                return $firmaDestino->empleado && !empty($firmaDestino->empleado->correo);
            })
            ->unique(function ($firmaDestino) {
                return mb_strtolower(trim((string) $firmaDestino->empleado->correo), 'UTF-8');
            })
            ->values();

        if ($firmasDestino->isEmpty()) {
            return;
        }

        foreach ($firmasDestino as $firmaDestino) {
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
        $correosResponsables = Firmas::where('elemento_id', $firmaOrigen->elemento_id)
            ->where('tipo', 'Responsable')
            ->with('empleado')
            ->get()
            ->pluck('empleado.correo')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($correosResponsables)) {
            return;
        }

        $coordinadoresCalidad = Empleados::whereHas('puestoTrabajo', function ($query) {
            $query->where('nombre', 'Coordinador de Calidad');
        })->get();

        $ccCorreos = $coordinadoresCalidad
            ->pluck('correo')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        Mail::to($correosResponsables)
            ->cc($ccCorreos)
            ->send(
                new FirmaRechazadaMail(
                    $firmaOrigen->elemento,
                    $firmaOrigen,
                    $template
                )
            );
    }
}

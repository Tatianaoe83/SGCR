<?php

namespace App\Jobs;

use App\Mail\FirmasMail;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarFirmaMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $firmaId
    ) {}

    public function handle(): void
    {
        $firma = Firmas::with(['empleado', 'elemento'])->find($this->firmaId);
        if (!$firma || !$firma->empleado?->correo) return;

        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_FIRMA_DOCUMENTO)
            ->first();

        if (!$template) return;

        //$firma->empleado->correo
        Mail::to($firma->empleado->correo)
            ->send(new FirmasMail(
                $firma->elemento,
                $firma,
                $template
            ));
    }
}

<?php

namespace App\Jobs;

use App\Mail\EnviarCorreoRecordatorioFirmas;
use App\Models\CuerpoCorreo;
use App\Models\Firmas;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCorreoRecordatorioFirma implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $firmaId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[FIRMA] Job iniciado', [
            'firma_id' => $this->firmaId,
        ]);

        $firma = Firmas::with('empleado', 'elemento')->find($this->firmaId);

        if (!$firma) {
            Log::warning('[FIRMA] Firma no encontrada', [
                'firma_id' => $this->firmaId,
            ]);
            return;
        }

        if (!$firma->empleado?->correo) {
            Log::warning('[FIRMA] Empleado sin correo', [
                'firma_id' => $firma->id,
                'empleado_id' => $firma->empleado_id,
            ]);
            return;
        }

        $template = CuerpoCorreo::activos()
            ->porTipo(CuerpoCorreo::TIPO_FIRMA_RECORDATORIO)
            ->first();

        if (!$template) {
            Log::warning('[FIRMA] Template no encontrado');
            return;
        }

        Log::info('[FIRMA] Enviando correo', [
            'firma_id' => $firma->id,
            'correo' => $firma->empleado->correo,
        ]);

        Mail::to($firma->empleado->correo)
            ->send(new EnviarCorreoRecordatorioFirmas($firma, $template));

        Log::info('[FIRMA] Correo enviado OK', [
            'firma_id' => $firma->id,
        ]);
    }
}

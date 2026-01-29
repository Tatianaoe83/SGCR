<?php

namespace App\Console\Commands;

use App\Jobs\EnviarCorreoRecordatorioFirma;
use App\Models\Firmas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarCorreosFirmasRecordatoriosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firmas:recordatorios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envío de correo de firma pendiente a los responsables y participantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('[FIRMAS] Buscando firmas pendientes para recordatorios');
        $firmas = Firmas::query()
            ->where('estatus', 'Pendiente')
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now())
            ->with('empleado', 'elemento')
            ->get();

        Log::info('[FIRMAS] Firmas encontradas para procesar', ['total' => $firmas->count()]);
            
        foreach ($firmas as $firma) {
            Log::info('[FIRMAS] Procesando firma', [
                'firma_id' => $firma->id,
                'empleado' => $firma->empleado?->nombre ?? 'N/A',
                'correo' => $firma->empleado?->correo ?? 'N/A',
                'elemento' => $firma->elemento?->nombre ?? 'N/A',
                'next_reminder_at' => $firma->next_reminder_at?->format('Y-m-d H:i:s'),
            ]);   

            EnviarCorreoRecordatorioFirma::dispatch($firma->id);

            $firma->last_reminder_at = now();
            $firma->next_reminder_at = $firma->calcularSiguienteRecordatorio(now());
            $firma->save();
        }
        
        Log::info('[FIRMAS] Comando finalizado', ['total_procesadas' => $firmas->count()]);
    }
}

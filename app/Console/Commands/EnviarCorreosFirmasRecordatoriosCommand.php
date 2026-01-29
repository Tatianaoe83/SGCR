<?php

namespace App\Console\Commands;

use App\Jobs\EnviarCorreoRecordatorioFirma;
use App\Models\Firmas;
use Illuminate\Console\Command;

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
        $firmas = Firmas::query()
            ->where('estatus', 'Pendiente')
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now())
            ->with('empleado', 'elemento')
            ->get();

        foreach ($firmas as $firma) {
            EnviarCorreoRecordatorioFirma::dispatch($firma->id);

            $firma->last_reminder_at = now();
            $firma->next_reminder_at = $firma->calcularSiguienteRecordatorio(now());
            $firma->save();
        }
    }
}

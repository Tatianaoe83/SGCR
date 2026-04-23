<?php

namespace App\Console\Commands;

use App\Jobs\EnviarCorreoRecordatorioFirma;
use App\Models\Elemento;
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
    protected $description = 'Envío de correo de firma pendiente a los responsables y participantes, respetando prioridades';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('[FIRMAS-CMD] Iniciando recordatorios de firmas', ['tiempo' => now()]);

        // Contar todas las firmas pendientes
        $totalPendientes = Firmas::where('estatus', 'Pendiente')
            ->where('is_active', true)
            ->count();

        // Contar firmas pendientes que necesitan recordatorio
        $totalConRecordatorio = Firmas::where('estatus', 'Pendiente')
            ->where('is_active', true)
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now())
            ->count();

        if ($totalConRecordatorio === 0) {
            return;
        }

        // Obtener elementos que tienen firmas pendientes activas que necesitan recordatorio
        $elementosConFirmasPendientes = Elemento::query()
            ->whereHas('firmas', function ($query) {
                $query
                    ->where('estatus', 'Pendiente')
                    ->where('is_active', true)
                    ->whereNotNull('next_reminder_at')
                    ->where('next_reminder_at', '<=', now());
            })
            ->with(['firmas' => function ($query) {
                $query
                    ->where('estatus', 'Pendiente')
                    ->where('is_active', true)
                    ->whereNotNull('next_reminder_at')
                    ->where('next_reminder_at', '<=', now())
                    ->with('empleado', 'elemento');
            }])
            ->get();

        // Procesar cada elemento
        foreach ($elementosConFirmasPendientes as $elemento) {
            $this->procesarElemento($elemento);
        }
    }

    /**
     * Procesa un elemento específico.
     * Detecta la prioridad mínima pendiente y envía recordatorios solo a esa prioridad.
     *
     * @param \App\Models\Elemento $elemento
     * @return void
     */
    private function procesarElemento(Elemento $elemento): void
    {
        // Obtener la prioridad mínima (actual) que está pendiente
        $prioridadActual = Firmas::obtenerPrioridadMinimaPendiente($elemento->id_elemento);

        // Si no hay firmas pendientes, no hacer nada
        if (is_null($prioridadActual)) {
            return;
        }

        // Obtener solo los firmantes de la prioridad actual que necesitan recordatorio
        $firmasPendientesActuales = Firmas::query()
            ->deElementoYPrioridad($elemento->id_elemento, $prioridadActual)
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', now())
            ->with('empleado', 'elemento')
            ->get();

        // Enviar recordatorio a cada firmante de la prioridad actual
        foreach ($firmasPendientesActuales as $firma) {
            EnviarCorreoRecordatorioFirma::dispatch($firma->id);
        }
    }
}

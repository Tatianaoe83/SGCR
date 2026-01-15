<?php

namespace App\Console\Commands;

use App\Jobs\EnviarCorreoFechaVencimiento;
use App\Models\Elemento;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar';
    protected $description = 'Envía recordatorios de elementos según semáforo sin estado';

    public function handle(): int
    {
        $hoy = Carbon::today();

        $elementos = Elemento::whereNotIn('status', ['Rechazado', 'Aprobado'])
            ->whereNotNull('periodo_revision')
            ->get(['id_elemento', 'periodo_revision']);

        foreach ($elementos as $elemento) {

            $fechaVencimiento = Carbon::parse($elemento->periodo_revision);
            $diasRestantes = $hoy->diffInDays($fechaVencimiento, false);

            if ($diasRestantes <= 10) {
                $frecuencia = 1;
            } elseif ($diasRestantes <= 60) {
                $frecuencia = 15;
            } else {
                $frecuencia = 30;
            }

            if (abs($diasRestantes) % $frecuencia !== 0) {
                continue;
            }

            EnviarCorreoFechaVencimiento::dispatch(
                $elemento->id_elemento
            );
        }

        $this->info('Recordatorios enviados correctamente.');

        return Command::SUCCESS;
    }
}
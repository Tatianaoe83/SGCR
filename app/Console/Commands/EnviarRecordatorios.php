<?php

namespace App\Console\Commands;

use App\Models\Elemento;
use App\Services\ElementoReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar';
    protected $description = 'Envía recordatorios según semáforo';

    public function __construct(
        private ElementoReminderService $reminderService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hoy = Carbon::today()->startOfDay();
        $keyName = (new Elemento())->getKeyName();

        Elemento::query()
            ->where('status', 'Publicado')
            ->whereNotNull('periodo_revision')
            ->orderBy($keyName)
            ->chunkById(300, function ($elementos) use ($hoy) {
                foreach ($elementos as $elemento) {
                    $this->reminderService->procesar($elemento, $hoy);
                }
            }, $keyName);

        $this->info('Recordatorios procesados.');
        return Command::SUCCESS;
    }
}

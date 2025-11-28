<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduleMail;
use App\Models\Elemento;
use Illuminate\Console\Command;

class SendMailRecordatorio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correos:send-mail-recordatorio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios a los responsables de los elementos cuya fecha de revisión está pendiente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $elementos = Elemento::where('periodo_revision', '>', now())->get();

        foreach ($elementos as $elemento) {
            SendScheduleMail::dispatch($elemento->id_elemento);
            $this->info("Recordatorio enviado para el elemento: {$elemento->id_elemento}");
        }
    }
}

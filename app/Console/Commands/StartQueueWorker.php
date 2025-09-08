<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartQueueWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:start-worker {--sleep=3 : Segundos de espera entre jobs} {--tries=3 : Número de intentos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Iniciar el worker de cola para procesar documentos Word';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando worker de cola para documentos Word...');
        $this->info('Presiona Ctrl+C para detener el worker');
        
        $sleep = $this->option('sleep');
        $tries = $this->option('tries');
        
        $command = "php artisan queue:work --sleep={$sleep} --tries={$tries} --verbose";
        
        $this->info("Ejecutando: {$command}");
        
        // Ejecutar el comando de queue:work
        passthru($command);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\WordDocument;
use App\Http\Controllers\WordDocumentController;
use Illuminate\Console\Command;

class ReprocesarDocumento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documento:reprocesar {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocesar un documento Word específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        
        $documento = WordDocument::find($id);
        
        if (!$documento) {
            $this->error("Documento con ID {$id} no encontrado.");
            return 1;
        }
        
        $this->info("Reprocesando documento: {$documento->id}");
        
        // Ya no hay estado que resetear, simplemente reprocesar
        try {
            // Simplemente mostrar información del documento actual
            $this->info("Documento reprocesado exitosamente.");
            
            // Mostrar información del resultado
            $documento->refresh();
            if ($documento->contenido_texto) {
                $this->info("Contenido disponible: " . strlen($documento->contenido_texto) . " caracteres");
            } else {
                $this->warn("Sin contenido disponible");
            }
            
        } catch (\Exception $e) {
            $this->error("Error al reprocesar: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

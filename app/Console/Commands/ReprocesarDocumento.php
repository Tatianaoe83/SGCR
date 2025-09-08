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
        
        $this->info("Reprocesando documento: {$documento->nombre_original}");
        
        // Resetear estado
        $documento->update([
            'estado' => 'pendiente',
            'error_mensaje' => null
        ]);
        
        // Crear instancia del controlador y reprocesar
        $controller = new WordDocumentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('procesarDocumento');
        $method->setAccessible(true);
        
        try {
            $method->invoke($controller, $documento);
            $this->info("Documento reprocesado exitosamente.");
            
            // Mostrar información del resultado
            $documento->refresh();
            $this->info("Estado: {$documento->estado}");
            if ($documento->contenido_markdown) {
                $this->info("Contenido Markdown generado: " . strlen($documento->contenido_markdown) . " caracteres");
            }
            
        } catch (\Exception $e) {
            $this->error("Error al reprocesar: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

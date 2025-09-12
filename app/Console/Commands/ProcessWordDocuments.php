<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordDocument;

class ProcessWordDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'word-documents:process {--id= : ID específico del documento} {--all : Procesar todos los documentos pendientes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar documentos Word de forma asíncrona';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->option('id');
        $all = $this->option('all');

        if ($id) {
            // Procesar documento específico
            $documento = WordDocument::find($id);
            if (!$documento) {
                $this->error("Documento con ID {$id} no encontrado.");
                return 1;
            }

            $this->info("Procesando documento específico: {$documento->nombre_original}");
            $this->processDocument($documento);

        } elseif ($all) {
            // Procesar todos los documentos pendientes
            $documentos = WordDocument::where('estado', 'pendiente')->get();
            
            if ($documentos->isEmpty()) {
                $this->info("No hay documentos pendientes para procesar.");
                return 0;
            }

            $this->info("Procesando {$documentos->count()} documentos pendientes...");
            
            foreach ($documentos as $documento) {
                $this->processDocument($documento);
            }

        } else {
            $this->error("Debe especificar --id o --all");
            return 1;
        }

        return 0;
    }

    private function processDocument(WordDocument $documento)
    {
        $this->info("  - Procesando: {$documento->nombre_original}");
        
        // Resetear estado
        $documento->update([
            'estado' => 'pendiente',
            'error_mensaje' => null
        ]);

        // Crear instancia del controlador y procesar
        $controller = new \App\Http\Controllers\WordDocumentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('procesarDocumento');
        $method->setAccessible(true);

        try {
            $method->invoke($controller, $documento);
            $this->info("    ✓ Procesado exitosamente");
            
            // Mostrar información del resultado
            $documento->refresh();
            if ($documento->contenido_markdown) {
                $this->info("    - Contenido Markdown: " . strlen($documento->contenido_markdown) . " caracteres");
            }
            
        } catch (\Exception $e) {
            $this->error("    ✗ Error: " . $e->getMessage());
        }
    }
}

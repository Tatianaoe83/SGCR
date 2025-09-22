<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordDocument;
use App\Jobs\ProcesarDocumentoWordJob;

class TestPdfConversion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:pdf-conversion {--document-id= : ID del documento Word a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la conversión de documentos Word a PDF';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->option('document-id');
        
        if ($documentId) {
            // Procesar un documento específico
            $document = WordDocument::find($documentId);
            
            if (!$document) {
                $this->error("Documento con ID {$documentId} no encontrado.");
                return 1;
            }
            
            $this->info("Procesando documento ID: {$documentId}");
            $this->info("Elemento: {$document->elemento->nombre_elemento}");
            $this->info("Archivo: {$document->elemento->archivo_formato}");
            
            // Ejecutar el job directamente
            $job = new ProcesarDocumentoWordJob($document);
            $job->handle();
            
            $this->info("Procesamiento completado.");
            
        } else {
            // Mostrar documentos disponibles
            $documents = WordDocument::with('elemento')
                ->where('estado', 'pendiente')
                ->orWhere('estado', 'procesado')
                ->get();
            
            if ($documents->isEmpty()) {
                $this->info("No hay documentos Word disponibles para procesar.");
                return 0;
            }
            
            $this->info("Documentos Word disponibles:");
            $this->table(
                ['ID', 'Contenido', 'Creado'],
                $documents->map(function ($doc) {
                    return [
                        $doc->id,
                        !empty($doc->contenido_texto) ? 'Sí (' . strlen($doc->contenido_texto) . ' chars)' : 'No',
                        $doc->created_at->format('Y-m-d H:i:s')
                    ];
                })
            );
            
            $this->info("\nPara procesar un documento específico, usa:");
            $this->info("php artisan test:pdf-conversion --document-id=<ID>");
        }
        
        return 0;
    }
}

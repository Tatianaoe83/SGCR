<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordDocument;
use App\Services\WordDocumentSearchService;
use Illuminate\Support\Facades\Log;

class CleanWordDocumentsIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:clean-word-index 
                           {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
                           {--orphaned : Solo limpiar documentos huérfanos del índice}
                           {--invalid : Solo limpiar documentos con estado no procesado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar y mantener el índice de documentos Word';

    private $wordSearchService;

    public function __construct()
    {
        parent::__construct();
        $this->wordSearchService = new WordDocumentSearchService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $dryRun = $this->option('dry-run');
        $orphanedOnly = $this->option('orphaned');
        $invalidOnly = $this->option('invalid');

        $this->info('🧹 Iniciando limpieza del índice de documentos Word...');
        
        if ($dryRun) {
            $this->warn('🔍 Modo DRY-RUN: Solo se mostrarán los cambios sin ejecutarlos');
        }

        $cleaned = 0;
        $errors = 0;

        // Limpiar documentos sin contenido
        if (!$orphanedOnly) {
            $invalidDocuments = WordDocument::where(function($query) {
                $query->whereNull('contenido_texto')
                      ->orWhere('contenido_texto', '');
            })->get();
            
            $this->info("🔍 Encontrados {$invalidDocuments->count()} documentos sin contenido");
            
            foreach ($invalidDocuments as $document) {
                try {
                    if (!$dryRun) {
                        $document->unsearchable();
                        $cleaned++;
                    }
                    
                    $this->line("  ❌ Removido: Doc {$document->id} (Sin contenido)");
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ⚠️  Error removiendo documento {$document->id}: " . $e->getMessage());
                }
            }
        }

        // Verificar documentos con contenido que deberían estar indexados
        if (!$invalidOnly) {
            $processedDocuments = WordDocument::whereNotNull('contenido_texto')
                ->where('contenido_texto', '!=', '')
                ->get();
            
            $this->info("🔍 Verificando {$processedDocuments->count()} documentos con contenido...");
            
            $notIndexed = 0;
            
            foreach ($processedDocuments as $document) {
                try {
                    // Verificar si está en el índice
                    $inIndex = $this->isDocumentInIndex($document);
                    
                    if (!$inIndex) {
                        $notIndexed++;
                        
                        if (!$dryRun) {
                            $document->searchable();
                            $cleaned++;
                        }
                        
                        $this->line("  ✅ Re-indexado: Doc {$document->id} ({$document->title})");
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ⚠️  Error verificando documento {$document->id}: " . $e->getMessage());
                }
            }
            
            if ($notIndexed > 0) {
                $this->warn("📋 Se encontraron {$notIndexed} documentos procesados no indexados");
            } else {
                $this->info("✅ Todos los documentos procesados están correctamente indexados");
            }
        }

        // Mostrar estadísticas finales
        $this->showFinalStats($cleaned, $errors, $startTime, $dryRun);

        // Limpiar caché de búsquedas
        if (!$dryRun && $cleaned > 0) {
            $this->wordSearchService->clearSearchCache();
            $this->info('🗑️  Caché de búsquedas limpiado');
        }

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Verificar si un documento está en el índice
     */
    private function isDocumentInIndex(WordDocument $document): bool
    {
        try {
            // Intentar buscar el documento específico
            $results = WordDocument::search($document->title ?? 'documento')
                ->where('id', $document->id)
                ->take(1)
                ->get();
                
            return $results->contains('id', $document->id);
            
        } catch (\Exception $e) {
            // Si hay error con Scout, asumir que no está indexado
            return false;
        }
    }

    /**
     * Mostrar estadísticas finales
     */
    private function showFinalStats(int $cleaned, int $errors, float $startTime, bool $dryRun): void
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->newLine();
        
        if ($dryRun) {
            $this->info('🔍 Resumen DRY-RUN:');
        } else {
            $this->info('🎉 Limpieza completada!');
        }
        
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Documentos procesados', $cleaned],
                ['Errores encontrados', $errors],
                ['Tiempo de ejecución', "{$executionTime} segundos"],
                ['Modo', $dryRun ? 'DRY-RUN' : 'EJECUCIÓN']
            ]
        );

        // Mostrar estadísticas del índice
        try {
            $stats = $this->wordSearchService->getSearchStats();
            
            $this->newLine();
            $this->info('📊 Estado actual del índice:');
            $this->line("   • Total documentos: {$stats['total_documents']}");
            $this->line("   • Documentos indexados: {$stats['total_indexed']}");
            $this->line("   • Porcentaje indexado: {$stats['indexing_percentage']}%");
            
            if ($stats['last_updated']) {
                $this->line("   • Última actualización: {$stats['last_updated']}");
            }
            
        } catch (\Exception $e) {
            $this->warn('⚠️  No se pudieron obtener estadísticas del índice: ' . $e->getMessage());
        }

        if ($errors > 0) {
            $this->newLine();
            $this->warn("⚠️  Se encontraron {$errors} errores durante la limpieza.");
            $this->line('   Revisa los logs para más detalles.');
        }
    }
}

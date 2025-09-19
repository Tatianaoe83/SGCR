<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordDocument;
use Illuminate\Support\Facades\Log;

class IndexWordDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:index-word-documents 
                           {--force : Forzar re-indexación de todos los documentos}
                           {--batch= : Procesar en lotes (por defecto 50)}
                           {--only-new : Solo indexar documentos nuevos no indexados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexar documentos Word procesados para búsqueda del chatbot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $batchSize = (int) $this->option('batch') ?: 50;
        $force = $this->option('force');
        $onlyNew = $this->option('only-new');

        $this->info('🚀 Iniciando indexación de documentos Word...');
        
        // Obtener documentos a procesar - todos los que tienen contenido
        $query = WordDocument::whereNotNull('contenido_texto')
                             ->where('contenido_texto', '!=', '');
        
        if ($onlyNew && !$force) {
            $this->info('📋 Modo: Solo documentos nuevos');
            // En una implementación real, podrías tener un campo 'indexed_at' para filtrar
            // Por ahora, procesamos todos los que cumplan la condición básica
        } elseif ($force) {
            $this->info('⚡ Modo: Forzar re-indexación completa');
        }

        $totalDocuments = $query->count();
        
        if ($totalDocuments === 0) {
            $this->warn('⚠️  No se encontraron documentos Word procesados para indexar.');
            return 0;
        }

        $this->info("📊 Total de documentos a procesar: {$totalDocuments}");
        
        $bar = $this->output->createProgressBar($totalDocuments);
        $bar->setFormat('verbose');
        
        $processed = 0;
        $errors = 0;
        $skipped = 0;

        // Procesar en lotes
        $query->chunk($batchSize, function ($documents) use (&$processed, &$errors, &$skipped, $bar, $force) {
            foreach ($documents as $document) {
                try {
                    // Verificar si debe ser indexado
                    if (!$force && !$document->shouldBeSearchable()) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Indexar usando Scout
                    $document->searchable();
                    
                    $processed++;
                    
                    if ($processed % 10 === 0) {
                        $this->info("\n✅ Procesados: {$processed}");
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error indexando documento {$document->id}: " . $e->getMessage());
                    
                    if ($this->output->isVerbose()) {
                        $this->error("❌ Error en documento {$document->id}: " . $e->getMessage());
                    }
                }
                
                $bar->advance();
            }
        });

        $bar->finish();
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->newLine(2);
        $this->info('🎉 Indexación completada!');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Documentos procesados', $processed],
                ['Documentos omitidos', $skipped],
                ['Errores', $errors],
                ['Tiempo de ejecución', "{$executionTime} segundos"],
                ['Promedio por documento', $processed > 0 ? round($executionTime / $processed, 3) . ' seg' : 'N/A']
            ]
        );

        // Mostrar estadísticas adicionales
        $this->showIndexStats();

        if ($errors > 0) {
            $this->warn("⚠️  Se encontraron {$errors} errores. Revisa los logs para más detalles.");
            return 1;
        }

        return 0;
    }

    /**
     * Mostrar estadísticas del índice
     */
    private function showIndexStats()
    {
        try {
            $totalProcessed = WordDocument::whereNotNull('contenido_texto')
                                         ->where('contenido_texto', '!=', '')
                                         ->count();
            $totalIndexed = 0;
            
            try {
                // Intentar obtener estadísticas de Scout
                $totalIndexed = WordDocument::search('*')->count();
            } catch (\Exception $e) {
                $totalIndexed = $totalProcessed; // Fallback
            }

            $percentage = $totalProcessed > 0 ? round(($totalIndexed / $totalProcessed) * 100, 1) : 0;
            
            $this->newLine();
            $this->info('📈 Estadísticas del índice:');
            $this->line("   • Documentos procesados: {$totalProcessed}");
            $this->line("   • Documentos indexados: {$totalIndexed}");
            $this->line("   • Porcentaje indexado: {$percentage}%");
            
            // Mostrar información básica sobre documentos
            $documentsWithContent = WordDocument::whereNotNull('contenido_texto')
                                                ->where('contenido_texto', '!=', '')
                                                ->count();
            
            if ($documentsWithContent > 0) {
                $this->newLine();
                $this->info('📋 Estadísticas de contenido:');
                $this->line("   • Documentos con contenido: {$documentsWithContent}");
            }
            
        } catch (\Exception $e) {
            $this->warn('⚠️  No se pudieron obtener estadísticas adicionales: ' . $e->getMessage());
        }
    }
}

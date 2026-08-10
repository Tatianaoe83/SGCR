<?php

namespace App\Console\Commands;

use App\Models\DocumentChunk;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

/**
 * Genera embeddings para los chunks que aún no los tienen.
 * Idempotente: solo procesa los que están vacíos. Se corre una vez sobre el contenido
 * existente y luego cada nuevo chunk ya nace con embedding vía DocumentChunkingService.
 */
class EmbedChunks extends Command
{
    protected $signature = 'chatbot:embed-chunks
                            {--batch=50 : Cuántos chunks por llamada a la API}
                            {--recreate : Re-generar embeddings de TODOS, no solo los faltantes}';

    protected $description = 'Genera embeddings de los chunks de documentos para búsqueda semántica';

    public function handle(EmbeddingService $embeddingService): int
    {
        $batchSize = (int) $this->option('batch');
        if ($batchSize < 1) {
            $batchSize = 50;
        }

        $query = DocumentChunk::query();
        if (!$this->option('recreate')) {
            $query->whereNull('embedding');
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No hay chunks pendientes de embedding.');
            return self::SUCCESS;
        }

        $this->info("Generando embeddings para {$total} chunks (lotes de {$batchSize})...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $model = $embeddingService->getModel();
        $failed = 0;

        $query->select('id', 'content')->chunkById($batchSize, function ($chunks) use ($embeddingService, $model, $bar, &$failed) {
            $texts = $chunks->pluck('content')->all();
            $vectors = $embeddingService->embedBatch($texts);

            foreach ($chunks->values() as $i => $chunk) {
                $vector = $vectors[$i] ?? null;
                if ($vector !== null) {
                    DocumentChunk::whereKey($chunk->id)->update([
                        'embedding' => json_encode($vector),
                        'embedding_model' => $model,
                    ]);
                } else {
                    $failed++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        if ($failed > 0) {
            $this->warn("{$failed} chunks no se pudieron embeddear (revisa logs / API key). Vuelve a correr el comando para reintentarlos.");
        }
        $this->info('Embeddings completados.');

        return self::SUCCESS;
    }
}

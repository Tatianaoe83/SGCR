<?php

namespace App\Observers;

use App\Models\WordDocument;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentChunkingService;


class WordDocumentObserver
{
    /**
     * Handle the WordDocument "created" event.
     */
    public function created(WordDocument $wordDocument): void
    {
        // Si se crea con contenido, indexar automáticamente
        if (!empty($wordDocument->contenido_texto)) {
            $this->indexDocument($wordDocument, 'created');
        }
    }

    /**
     * Handle the WordDocument "updated" event.
     */
    public function updated(WordDocument $wordDocument): void
    {
        $original = $wordDocument->getOriginal();

        // Si el contenido cambió
        if (($original['contenido_texto'] ?? '') !== ($wordDocument->contenido_texto ?? '')) {

            // 🔥 SI HAY CONTENIDO → RE-INDEXAR + RE-CHUNKEAR
            if (!empty($wordDocument->contenido_texto)) {

                try {
                    // 1️⃣ Re-chunk automático
                    app(\App\Services\DocumentChunkingService::class)
                        ->chunkWordDocument($wordDocument);

                    Log::info("📦 Chunks regenerados para WordDocument {$wordDocument->id}");

                    // 2️⃣ Re-index Scout
                    $this->reindexDocument($wordDocument, 'content_updated');

                    Log::info("🔄 WordDocument {$wordDocument->id} re-indexado - contenido actualizado");

                } catch (\Throwable $e) {
                    Log::error("❌ Error re-chunkeando WordDocument {$wordDocument->id}: " . $e->getMessage());
                }

            } 
            // ❌ SI SE BORRA EL CONTENIDO → LIMPIAR ÍNDICE + CHUNKS
            else {
                \App\Models\DocumentChunk::where('word_word_document_id', $wordDocument->id)->delete();
                $this->removeFromIndex($wordDocument, 'content_removed');

                Log::info("🧹 WordDocument {$wordDocument->id} chunks e índice eliminados");
            }
        }
    }


    /**
     * Handle the WordDocument "deleted" event.
     */
    public function deleted(WordDocument $wordDocument): void
    {
        $this->removeFromIndex($wordDocument, 'deleted');
        
        Log::info("WordDocument {$wordDocument->id} removido del índice - documento eliminado");
    }

    /**
     * Handle the WordDocument "restored" event.
     */
    public function restored(WordDocument $wordDocument): void
    {
        // Si se restaura un documento con contenido, indexar
        if (!empty($wordDocument->contenido_texto)) {
            $this->indexDocument($wordDocument, 'restored');
            
            Log::info("WordDocument {$wordDocument->id} re-indexado - documento restaurado");
        }
    }

    /**
     * Handle the WordDocument "force deleted" event.
     */
    public function forceDeleted(WordDocument $wordDocument): void
    {
        $this->removeFromIndex($wordDocument, 'force_deleted');
        
        Log::info("WordDocument {$wordDocument->id} removido permanentemente del índice");
    }

    /**
     * Indexar documento
     */
    private function indexDocument(WordDocument $wordDocument, string $reason): void
    {
        try {
            if (!$wordDocument->shouldBeSearchable()) {
                Log::warning("WordDocument {$wordDocument->id} no cumple criterios ({$reason})");
                return;
            }

            // 🔥 1. CHUNKEAR DOCUMENTO
            app(DocumentChunkingService::class)
                ->chunkWordDocument($wordDocument);

            Log::info("📚 Documento {$wordDocument->id} chunkeado automáticamente");

            // 🔍 2. INDEXAR DOCUMENTO
            $wordDocument->searchable();

            $this->logIndexingInfo($wordDocument, 'indexed', $reason);

        } catch (\Throwable $e) {
            Log::error("Error indexando WordDocument {$wordDocument->id}: " . $e->getMessage());
        }
    }



    /**
     * Re-indexar documento
     */
    private function reindexDocument(WordDocument $wordDocument, string $reason): void
    {
        try {
            // Remover del índice primero
            $wordDocument->unsearchable();
            
            // Esperar un momento antes de re-indexar
            usleep(100000); // 0.1 segundos
            
            // Re-indexar
            $this->indexDocument($wordDocument, $reason);
            
        } catch (\Exception $e) {
            Log::error("Error re-indexando WordDocument {$wordDocument->id} ({$reason}): " . $e->getMessage());
        }
    }

    /**
     * Remover del índice
     */
    private function removeFromIndex(WordDocument $wordDocument, string $reason): void
    {
        try {
            $wordDocument->unsearchable();
            
            $this->logIndexingInfo($wordDocument, 'removed', $reason);
            
        } catch (\Exception $e) {
            Log::error("Error removiendo WordDocument {$wordDocument->id} del índice ({$reason}): " . $e->getMessage());
        }
    }

    /**
     * Log información detallada de indexación
     */
    private function logIndexingInfo(WordDocument $wordDocument, string $action, string $reason): void
    {
        $info = [
            'action' => $action,
            'reason' => $reason,
            'word_document_id' => $wordDocument->id,
            'content_length' => strlen($wordDocument->contenido_texto ?? ''),
            'has_content' => !empty($wordDocument->contenido_texto),
        ];

        Log::info("WordDocument indexing: " . json_encode($info));
    }
}

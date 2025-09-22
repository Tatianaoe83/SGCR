<?php

namespace App\Observers;

use App\Models\WordDocument;
use Illuminate\Support\Facades\Log;

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
        
        // Si el contenido cambió, re-indexar
        if ($original['contenido_texto'] !== $wordDocument->contenido_texto) {
            if (!empty($wordDocument->contenido_texto)) {
                $this->reindexDocument($wordDocument, 'content_updated');
                Log::info("WordDocument {$wordDocument->id} re-indexado - contenido actualizado");
            } else {
                $this->removeFromIndex($wordDocument, 'content_removed');
                Log::info("WordDocument {$wordDocument->id} removido del índice - contenido eliminado");
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
            // Verificar que debe ser indexado
            if (!$wordDocument->shouldBeSearchable()) {
                Log::warning("WordDocument {$wordDocument->id} no cumple criterios para indexación ({$reason})");
                return;
            }

            // Indexar usando Scout
            $wordDocument->searchable();
            
            // Log adicional con información del documento
            $this->logIndexingInfo($wordDocument, 'indexed', $reason);
            
        } catch (\Exception $e) {
            Log::error("Error indexando WordDocument {$wordDocument->id} ({$reason}): " . $e->getMessage());
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
            'document_id' => $wordDocument->id,
            'content_length' => strlen($wordDocument->contenido_texto ?? ''),
            'has_content' => !empty($wordDocument->contenido_texto),
        ];

        Log::info("WordDocument indexing: " . json_encode($info));
    }
}

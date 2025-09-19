<?php

namespace App\Services;

use App\Models\WordDocument;
use App\Models\SmartIndex;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WordDocumentSearchService
{
    private $cacheTimeout = 300; // 5 minutos

    /**
     * Búsqueda híbrida principal
     */
    public function search($query, $options = [])
    {
        $startTime = microtime(true);
        
        $defaults = [
            'limit' => 10,
            'min_score' => 1,
            'use_cache' => true,
            'include_chunks' => true,
            'boost_recent' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Intentar búsqueda en caché primero
        if ($options['use_cache']) {
            $cachedResults = $this->getCachedResults($query, $options);
            if ($cachedResults) {
                return $this->formatResponse($cachedResults, 'cache', $startTime);
            }
        }

        try {
            // 1. Búsqueda con Scout (preferida)
            $scoutResults = $this->searchWithScout($query, $options);
            
            if ($scoutResults->isNotEmpty()) {
                $results = $this->processResults($scoutResults, $query, $options);
                $this->cacheResults($query, $results, $options);
                return $this->formatResponse($results, 'scout', $startTime);
            }
        } catch (\Exception $e) {
            Log::warning('Scout search failed: ' . $e->getMessage());
        }

        try {
            // 2. Búsqueda manual como fallback
            $manualResults = $this->searchManually($query, $options);
            $results = $this->processResults($manualResults, $query, $options);
            $this->cacheResults($query, $results, $options);
            return $this->formatResponse($results, 'manual', $startTime);
        } catch (\Exception $e) {
            Log::error('Manual search failed: ' . $e->getMessage());
            return $this->formatResponse(collect(), 'error', $startTime, $e->getMessage());
        }
    }

    /**
     * Búsqueda usando Scout
     */
    private function searchWithScout($query, $options)
    {
        return WordDocument::search($query)
            ->take($options['limit'] * 2) // Buscar más para filtrar después
            ->get();
    }

    /**
     * Búsqueda manual
     */
    private function searchManually($query, $options)
    {
        return WordDocument::manualSearch($query, $options['limit'] * 2);
    }

    /**
     * Procesar y puntuar resultados
     */
    private function processResults($results, $query, $options)
    {
        $processed = collect();

        foreach ($results as $item) {
            // Si ya viene del método manualSearch, usar tal como está
            if (is_array($item) && isset($item['document'])) {
                $document = $item['document'];
                $score = $item['score'];
                $matchedChunks = $item['matched_chunks'];
            } else {
                // Si viene de Scout, procesar
                $document = $item;
                $score = $document->calculateRelevanceScore($query);
                $matchedChunks = $options['include_chunks'] ? $document->findMatchedChunks($query) : [];
            }

            // Aplicar boost por recencia si está habilitado
            if ($options['boost_recent']) {
                $score = $this->applyRecencyBoost($score, $document);
            }

            // Filtrar por score mínimo
            if ($score >= $options['min_score']) {
                $processed->push([
                    'document' => $document,
                    'score' => $score,
                    'matched_chunks' => $matchedChunks,
                    'metadata' => $this->extractMetadata($document, $query)
                ]);
            }
        }

        return $processed
            ->sortByDesc('score')
            ->take($options['limit'])
            ->values();
    }

    /**
     * Aplicar boost por recencia
     */
    private function applyRecencyBoost($score, $document)
    {
        $daysSinceCreated = now()->diffInDays($document->created_at);
        $daysSinceUpdated = now()->diffInDays($document->updated_at);
        
        // Boost por documento reciente (hasta 20% más)
        $recencyBoost = max(0, (30 - min($daysSinceCreated, $daysSinceUpdated)) / 30 * 0.2);
        
        return $score * (1 + $recencyBoost);
    }

    /**
     * Extraer metadata del documento
     */
    private function extractMetadata($document, $query)
    {
        return [
            'title' => $document->title,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
            'content_length' => strlen($document->contenido_texto ?? ''),
            'keywords' => $document->extractKeywords(),
            'query_words_found' => $this->countQueryWordsInDocument($query, $document)
        ];
    }

    /**
     * Contar palabras de la consulta encontradas en el documento
     */
    private function countQueryWordsInDocument($query, $document)
    {
        $queryWords = explode(' ', strtolower(trim($query)));
        $content = strtolower($document->contenido_texto ?? '');
        $title = strtolower($document->title ?? '');
        
        $found = [];
        foreach ($queryWords as $word) {
            if (strlen($word) > 2) {
                $inContent = substr_count($content, $word);
                $inTitle = substr_count($title, $word);
                if ($inContent > 0 || $inTitle > 0) {
                    $found[$word] = [
                        'in_content' => $inContent,
                        'in_title' => $inTitle,
                        'total' => $inContent + $inTitle
                    ];
                }
            }
        }
        
        return $found;
    }

    /**
     * Buscar documentos relacionados
     */
    public function findRelatedDocuments($documentId, $limit = 5)
    {
        $document = WordDocument::find($documentId);
        
        if (!$document) {
            return collect();
        }

        $keywords = $document->extractKeywords();
        
        if (empty($keywords)) {
            return collect();
        }

        // Usar las primeras 5 keywords más relevantes para buscar documentos relacionados
        $searchQuery = implode(' ', array_slice($keywords, 0, 5));
        
        $results = $this->search($searchQuery, [
            'limit' => $limit + 1, // +1 para excluir el documento original
            'min_score' => 0.5,
            'use_cache' => false
        ]);

        // Excluir el documento original
        return collect($results['results'])->filter(function ($item) use ($documentId) {
            return $item['document']->id !== $documentId;
        })->take($limit);
    }

    /**
     * Búsqueda por categoría/tipo de elemento
     */
    public function searchByCategory($query, $tipoElementoId, $options = [])
    {
        // Ya no hay filtro por categoría ya que no hay relación con elemento
        // Simplemente realizar búsqueda normal
        return $this->search($query, $options);
    }

    /**
     * Obtener estadísticas de búsqueda
     */
    public function getSearchStats()
    {
        $totalDocuments = WordDocument::whereNotNull('contenido_texto')
            ->where('contenido_texto', '!=', '')
            ->count();
        $totalIndexed = 0;
        
        try {
            // Intentar obtener estadísticas de Scout si está disponible
            $totalIndexed = WordDocument::search('*')->count();
        } catch (\Exception $e) {
            $totalIndexed = $totalDocuments; // Fallback
        }

        return [
            'total_documents' => $totalDocuments,
            'total_indexed' => $totalIndexed,
            'indexing_percentage' => $totalDocuments > 0 ? round(($totalIndexed / $totalDocuments) * 100, 2) : 0,
            'last_updated' => WordDocument::whereNotNull('contenido_texto')
                ->where('contenido_texto', '!=', '')
                ->max('updated_at')
        ];
    }

    /**
     * Formatear respuesta final
     */
    private function formatResponse($results, $method, $startTime, $error = null)
    {
        return [
            'results' => $results,
            'method' => $method,
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'total_found' => $results->count(),
            'cached' => $method === 'cache',
            'error' => $error,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Obtener resultados del caché
     */
    private function getCachedResults($query, $options)
    {
        $cacheKey = $this->getCacheKey($query, $options);
        return Cache::get($cacheKey);
    }

    /**
     * Guardar resultados en caché
     */
    private function cacheResults($query, $results, $options)
    {
        $cacheKey = $this->getCacheKey($query, $options);
        Cache::put($cacheKey, $results, $this->cacheTimeout);
    }

    /**
     * Generar clave de caché
     */
    private function getCacheKey($query, $options)
    {
        $keyData = [
            'query' => strtolower(trim($query)),
            'limit' => $options['limit'],
            'min_score' => $options['min_score'],
            'include_chunks' => $options['include_chunks']
        ];
        
        return 'word_doc_search_' . md5(json_encode($keyData));
    }

    /**
     * Limpiar caché de búsquedas
     */
    public function clearSearchCache()
    {
        // En una implementación real, podrías usar tags de caché
        // Por ahora, esto es un placeholder
        Log::info('Word document search cache cleared');
        return true;
    }

    /**
     * Sugerir consultas relacionadas
     */
    public function suggestRelatedQueries($query, $limit = 5)
    {
        // Buscar en SmartIndex consultas similares
        $normalizedQuery = strtolower(trim($query));
        $queryWords = explode(' ', $normalizedQuery);
        
        $suggestions = SmartIndex::where('normalized_query', '!=', $normalizedQuery)
            ->where(function ($queryBuilder) use ($queryWords) {
                foreach ($queryWords as $word) {
                    if (strlen($word) > 2) {
                        $queryBuilder->orWhere('normalized_query', 'LIKE', "%{$word}%");
                    }
                }
            })
            ->orderByDesc('usage_count')
            ->take($limit)
            ->pluck('original_query')
            ->unique()
            ->values();

        return $suggestions;
    }
}

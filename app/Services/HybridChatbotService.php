<?php

namespace App\Services;

use App\Models\ChatbotAnalytics;
use App\Models\WordDocument;
use App\Models\SmartIndex;
use Illuminate\Support\Facades\Log;

class HybridChatbotService
{
    private $smartIndexing;
    private $ollamaService;
    private $wordDocumentSearch;
    
    public function __construct()
    {
        $this->smartIndexing = new SmartIndexingService();
        $this->ollamaService = new OllamaService();
        $this->wordDocumentSearch = new WordDocumentSearchService();
    }

    public function processQuery($query, $userId = null, $sessionId = null)
    {
        $startTime = microtime(true);
        
        // 1. PASO 1: Buscar directamente en smart_indexes
        try {
            $smartIndexResponse = $this->searchInSmartIndexes($query);
            
            if ($smartIndexResponse) {
                // Registrar analytics para smart_index
                $this->logAnalytics($query, $smartIndexResponse, 'smart_index', $startTime, $userId, $sessionId);
                
            return [
                    'response' => $smartIndexResponse,
                'method' => 'smart_index',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'cached' => true
            ];
            }
        } catch (\Exception $e) {
            \Log::warning('Error buscando en smart_indexes: ' . $e->getMessage());
            // Continuar con el siguiente paso
        }
        
        // 2. PASO 2: Si no encuentra en smart_indexes, buscar en word_documents usando el nuevo servicio
        try {
            $searchResults = $this->wordDocumentSearch->search($query, [
                'limit' => 5,
                'min_score' => 1,
                'include_chunks' => true
            ]);
            
            if (!empty($searchResults['results']) && $searchResults['results']->isNotEmpty()) {
                // Preparar contexto avanzado con chunks relevantes
                $context = $this->prepareAdvancedContext($searchResults['results']);
                $ollamaResponse = $this->ollamaService->generateResponse($query, $context);
                
                // Guardar la nueva respuesta en smart_indexes para futuras consultas
                $this->saveToSmartIndex($query, $ollamaResponse, 'ollama');
                
                // Registrar analytics para ollama con información adicional
                $this->logAnalytics($query, $ollamaResponse, 'ollama_advanced', $startTime, $userId, $sessionId);
            
                return [
                    'response' => $ollamaResponse,
                    'method' => 'ollama_advanced',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => $searchResults['results']->pluck('document.id'),
                    'search_method' => $searchResults['method'],
                    'total_found' => $searchResults['total_found'],
                    'cached' => false,
                    'matched_chunks' => $searchResults['results']->pluck('matched_chunks')->flatten(1)->take(5)
                ];
            } else {
                // No hay documentos relevantes, usar Ollama sin contexto
                $ollamaResponse = $this->ollamaService->generateResponse($query);
                
                // Guardar respuesta en smart_indexes
                $this->saveToSmartIndex($query, $ollamaResponse, 'ollama');
                
                // Registrar analytics
                $this->logAnalytics($query, $ollamaResponse, 'ollama', $startTime, $userId, $sessionId);
                
                return [
                    'response' => $ollamaResponse,
                    'method' => 'ollama',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'cached' => false
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Chatbot error: ' . $e->getMessage());
            
            // Registrar analytics para fallback
            $fallbackResponse = $this->getFallbackResponse($e->getMessage());
            $this->logAnalytics($query, $fallbackResponse, 'fallback', $startTime, $userId, $sessionId);
            
            return [
                'response' => $fallbackResponse,
                'method' => 'fallback',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'cached' => false,
                'error' => true,
                'error_type' => $this->getErrorType($e->getMessage())
            ];
        }
    }

    /**
     * Buscar directamente en la tabla smart_indexes
     */
    private function searchInSmartIndexes($query)
    {
        // Normalizar query para búsqueda
        $normalizedQuery = strtolower(trim($query));
        
        // Buscar coincidencias exactas primero
        $exactMatch = SmartIndex::where('normalized_query', $normalizedQuery)
                                ->where('confidence_score', '>=', 0.7)
                                ->orderByDesc('usage_count')
                                ->first();
        
        if ($exactMatch) {
            $exactMatch->incrementUsage();
            return $exactMatch->response;
        }
        
        // Buscar coincidencias parciales usando LIKE
        $partialMatch = SmartIndex::where('normalized_query', 'LIKE', '%' . $normalizedQuery . '%')
                                  ->where('confidence_score', '>=', 0.8)
                                  ->orderByDesc('usage_count')
                                  ->first();
        
        if ($partialMatch) {
            $partialMatch->incrementUsage();
            return $partialMatch->response;
        }
        
        return null;
    }

    /**
     * Buscar documentos relevantes en word_documents por contenido_texto
     */
    private function findRelevantDocuments($query)
    {
        try {
            return WordDocument::where('contenido_texto', 'LIKE', '%' . $query . '%')
                              ->where('estado', 'procesado')
                              ->orderByRaw('LENGTH(contenido_texto) ASC') // Documentos más cortos primero
                              ->take(3)
                              ->get();
        } catch (\Exception $e) {
            \Log::warning('No se pudieron buscar documentos relevantes: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Preparar contexto avanzado con chunks relevantes
     */
    private function prepareAdvancedContext($searchResults)
    {
        $contextParts = [];
        
        foreach ($searchResults as $result) {
            $document = $result['document'];
            $matchedChunks = $result['matched_chunks'] ?? [];
            $score = $result['score'] ?? 0;
            
            $title = $document->title ?? 'Documento';
            
            // Si hay chunks específicos, usar esos
            if (!empty($matchedChunks)) {
                $chunkContents = collect($matchedChunks)->take(2)->map(function($chunk) {
                    return $chunk['content'];
                })->implode("\n\n");
                
                $contextParts[] = "**{$title}** (Relevancia: {$score}):\n{$chunkContents}";
            } else {
                // Fallback: usar contenido truncado
                $content = substr($document->contenido_texto, 0, 800);
                $contextParts[] = "**{$title}** (Relevancia: {$score}):\n{$content}";
            }
        }
        
        return implode("\n\n---\n\n", $contextParts);
    }

    /**
     * Preparar contexto de documentos para Ollama (método legacy)
     */
    private function prepareContext($documents)
    {
        return $documents->map(function($doc) {
            $title = $doc->title ?? 'Documento';
            $content = substr($doc->contenido_texto, 0, 1000); // Limitar a 1000 caracteres
            return $title . ":\n" . $content;
        })->implode("\n\n---\n\n");
    }

    /**
     * Guardar respuesta en smart_indexes
     */
    private function saveToSmartIndex($query, $response, $method = 'ollama')
    {
        try {
            $normalizedQuery = strtolower(trim($query));
            
            // Verificar si ya existe para evitar duplicados
            $existing = SmartIndex::where('normalized_query', $normalizedQuery)->first();
            if ($existing) {
                return; // Ya existe, no duplicar
            }
            
            $confidenceScore = match($method) {
                'ollama' => 0.6,
                'verified' => 1.0,
                default => 0.5
            };
            
            SmartIndex::create([
                'original_query' => $query,
                'normalized_query' => $normalizedQuery,
                'keywords' => $this->extractSimpleKeywords($query),
                'entities' => [],
                'response' => $response,
                'confidence_score' => $confidenceScore,
                'auto_generated' => true,
                'verified' => false
            ]);
        } catch (\Exception $e) {
            \Log::warning('No se pudo guardar en smart_indexes: ' . $e->getMessage());
        }
    }

    /**
     * Extraer palabras clave simples
     */
    private function extractSimpleKeywords($query)
    {
        $stopWords = ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'como', 'las', 'del', 'los', 'una'];
        
        $words = explode(' ', strtolower($query));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        
        return array_values($keywords);
    }

    /**
     * Registrar analytics
     */
    private function logAnalytics($query, $response, $method, $startTime, $userId, $sessionId)
    {
        try {
            ChatbotAnalytics::create([
                'user_id' => $userId,
                'query' => $query,
                'normalized_query' => strtolower(trim($query)),
                'response_method' => $method,
                'response' => $response,
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'session_id' => $sessionId ?? session()->getId()
            ]);
        } catch (\Exception $e) {
            \Log::warning('No se pudo guardar analytics: ' . $e->getMessage());
        }
    }

    private function getFallbackResponse($errorMessage)
    {
        if (strpos($errorMessage, 'cURL error 28') !== false || strpos($errorMessage, 'Operation timed out') !== false) {
            return 'El asistente de IA está tardando más de lo esperado en responder. Esto puede deberse a una consulta compleja o a alta demanda del servicio. Por favor intenta nuevamente con una pregunta más específica. ⏱️';
        }
        
        if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Connection') !== false) {
            return 'El servicio está temporalmente ocupado. Por favor intenta nuevamente en unos segundos. ⏱️';
        }
        
        if (strpos($errorMessage, '404') !== false || strpos($errorMessage, 'not found') !== false) {
            return 'El modelo de IA no está disponible en este momento. Por favor contacta al administrador. 🔧';
        }
        
        if (strpos($errorMessage, 'search') !== false) {
            return 'No pude buscar en la base de conocimientos. Intenta reformular tu pregunta. 🔍';
        }
        
        return 'Lo siento, no pude procesar tu consulta en este momento. Por favor intenta nuevamente o reformula tu pregunta. ⚡';
    }

    private function getErrorType($errorMessage)
    {
        if (strpos($errorMessage, 'cURL error 28') !== false || strpos($errorMessage, 'Operation timed out') !== false) {
            return 'curl_timeout';
        }
        
        if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Connection') !== false) {
            return 'connection_timeout';
        }
        
        if (strpos($errorMessage, '404') !== false || strpos($errorMessage, 'not found') !== false) {
            return 'model_not_found';
        }
        
        if (strpos($errorMessage, 'search') !== false) {
            return 'search_error';
        }
        
        return 'unknown_error';
    }
}

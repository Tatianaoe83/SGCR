<?php

namespace App\Services;

use App\Models\ChatbotAnalytics;
use App\Models\WordDocument;
use App\Models\SmartIndex;
use App\Models\Elemento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class HybridChatbotService
{
    private $smartIndexing;
    private $ollamaService;
    private $wordDocumentSearch;
    private $nlpProcessor;
    private $conversationalToneInstruction;
    
    public function __construct()
    {
        $this->smartIndexing = new SmartIndexingService();
        $this->ollamaService = new OllamaService();
        $this->wordDocumentSearch = new WordDocumentSearchService();
        $this->nlpProcessor = new NLPProcessor();
        $this->conversationalToneInstruction = $this->buildToneInstruction();
    }

    private function buildToneInstruction()
    {
        return "Instrucciones de tono: responde siempre en español con un estilo cálido, cercano y empático. "
            . "Utiliza un lenguaje claro, profesional y positivo. Incluye un saludo amable al inicio, explica la información de forma sencilla "
            . "y finaliza ofreciendo ayuda adicional si la persona lo necesita. Evita sonar robótico o demasiado formal.";
    }

    private function applyToneInstruction(?string $context = null)
    {
        $instruction = $this->conversationalToneInstruction;

        if ($context && trim($context) !== '') {
            return $instruction . "\n\n" . $context;
        }

        return $instruction;
    }

    private function buildWarmGreeting($intent = null)
    {
        $intentHint = '';
        if (is_array($intent) && !empty($intent['primary_intent'])) {
            $intentHint = " sobre {$this->mapIntentToFriendlyLabel($intent['primary_intent'])}";
        }

        return "👋 ¡Hola! Gracias por tu consulta{$intentHint}. A continuación te comparto la información más útil que encontré.";
    }

    private function buildWarmClosing()
    {
        return "Si necesitas profundizar en algún punto o tienes otra duda, estaré encantado de ayudarte.";
    }

    private function mapIntentToFriendlyLabel(string $intentKey)
    {
        return match ($intentKey) {
            'buscar_procedimientos_lineamientos' => 'procedimientos y lineamientos',
            'buscar_procedimientos' => 'procedimientos',
            'buscar_lineamientos' => 'lineamientos o políticas',
            default => 'este tema',
        };
    }

    public function processQuery($query, $userId = null, $sessionId = null)
    {
        $startTime = microtime(true);
        
        // PASO 1: Buscar directamente en smart_indexes (caché inteligente)
        try {
            $smartIndexResponse = $this->searchInSmartIndexes($query);
            
            if ($smartIndexResponse) {
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
        }
        
        // PASO 2: Búsqueda integrada en todos los modelos
        try {
            $searchResults = $this->performIntegratedSearch($query);
            
            if ($searchResults['has_results']) {
                // Intentar generar respuesta con IA
                $response = $this->generateResponseWithFallback($query, $searchResults, $startTime, $userId, $sessionId);
                if ($response) {
                    return $response;
                }
            } else {
                // Sin resultados relevantes, intentar IA sin contexto
                $response = $this->generateBasicResponseWithFallback($query, $startTime, $userId, $sessionId);
                if ($response) {
                    return $response;
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Chatbot error: ' . $e->getMessage());
            
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
     * Realizar búsqueda integrada en todos los modelos
     */
    private function performIntegratedSearch($query)
    {
        $results = [
            'elementos' => $this->searchInElementos($query),
            'word_documents' => $this->searchInWordDocuments($query),
            'has_results' => false,
            'sources' => [],
            'search_details' => []
        ];
        
        // Determinar si hay resultados relevantes
        $results['has_results'] = 
            $results['elementos']->isNotEmpty() || 
            $results['word_documents']->isNotEmpty();
        
        // Recopilar fuentes
        $results['sources'] = collect([
            'elementos' => $results['elementos']->pluck('id_elemento')->toArray(),
            'word_documents' => $results['word_documents']->pluck('id')->toArray()
        ]);
        
        // Detalles de búsqueda
        $results['search_details'] = [
            'elementos_found' => $results['elementos']->count(),
            'documents_found' => $results['word_documents']->count(),
            'total_sources' => $results['elementos']->count() + $results['word_documents']->count()
        ];
        
        return $results;
    }

    /**
     * Buscar en el modelo Elemento con razonamiento semántico
     */
    private function searchInElementos($query)
    {
        $normalizedQuery = strtolower(trim($query));
        
        // Análisis semántico de la consulta
        $intent = $this->nlpProcessor->analyzeIntent($query);

        $keywords = collect($this->nlpProcessor->extractKeywords($normalizedQuery))
            ->filter(fn($keyword) => is_string($keyword) || is_numeric($keyword))
            ->map(fn($keyword) => strtolower(trim((string) $keyword)))
            ->filter(fn($keyword) => $keyword !== '')
            ->unique()
            ->values()
            ->all();

        $expandedKeywords = collect($this->nlpProcessor->expandSemanticTerms($keywords))
            ->filter(fn($keyword) => is_string($keyword) || is_numeric($keyword))
            ->map(fn($keyword) => strtolower(trim((string) $keyword)))
            ->filter(fn($keyword) => $keyword !== '')
            ->unique()
            ->values()
            ->all();

        $semanticKeywords = collect($intent['semantic_keywords'] ?? [])
            ->filter(fn($keyword) => is_string($keyword) || is_numeric($keyword))
            ->map(fn($keyword) => strtolower(trim((string) $keyword)))
            ->filter(fn($keyword) => $keyword !== '')
            ->unique()
            ->values()
            ->all();

        $intent['semantic_keywords'] = $semanticKeywords;

        $folioPatterns = $this->extractFolioPatterns($query);
        
      
        
        try {
            $elementQuery = Elemento::with([
                'tipoElemento', 
                'tipoProceso', 
                'unidadNegocio', 
                'puestoResponsable',
                'wordDocument'
            ])
            ->where('tipo_elemento_id', 2)
            ->whereHas('wordDocument');

            $elementQuery->where(function ($searchQuery) use ($normalizedQuery, $keywords, $expandedKeywords, $folioPatterns, $intent) {
                $searchQuery->where(function ($elementConditions) use ($normalizedQuery, $keywords, $expandedKeywords, $folioPatterns, $intent) {
                    // Búsqueda específica por folios detectados (máxima prioridad)
                    foreach ($folioPatterns as $folio) {
                        if (!is_string($folio) && !is_numeric($folio)) {
                            continue;
                        }

                        $folio = strtolower(trim((string) $folio));
                        if ($folio === '') {
                            continue;
                        }

                        $elementConditions->orWhereRaw('LOWER(folio_elemento) LIKE ?', ['%' . $folio . '%']);
                    }

                    // Búsqueda semántica basada en la intención
                    if (($intent['confidence'] ?? 0) > 0.5) {
                        foreach ($intent['semantic_keywords'] as $semanticKeyword) {
                            if (!is_string($semanticKeyword) && !is_numeric($semanticKeyword)) {
                                continue;
                            }

                            $semanticKeyword = strtolower(trim((string) $semanticKeyword));
                            if ($semanticKeyword === '') {
                                continue;
                            }

                            $elementConditions->orWhereRaw('LOWER(nombre_elemento) LIKE ?', ['%' . $semanticKeyword . '%']);
                        }
                    }

                    // Búsqueda por palabras expandidas semánticamente
                    foreach ($expandedKeywords as $keyword) {
                        if (!is_string($keyword) && !is_numeric($keyword)) {
                            continue;
                        }

                        $keyword = strtolower(trim((string) $keyword));
                        if ($keyword === '' || strlen($keyword) <= 2) {
                            continue;
                        }

                        $elementConditions->orWhereRaw('LOWER(nombre_elemento) LIKE ?', ['%' . $keyword . '%'])
                                          ->orWhereRaw('LOWER(folio_elemento) LIKE ?', ['%' . $keyword . '%']);
                    }

                    // Fallback: búsqueda por consulta original
                    if ($normalizedQuery !== '') {
                        $elementConditions->orWhereRaw('LOWER(nombre_elemento) LIKE ?', ['%' . $normalizedQuery . '%']);
                    }
                });

                $searchQuery->orWhereHas('wordDocument', function ($query) use ($folioPatterns, $normalizedQuery, $expandedKeywords, $intent) {
                    // Buscar folios específicos en el contenido de documentos Word
                    foreach ($folioPatterns as $folio) {
                        if (!is_string($folio) && !is_numeric($folio)) {
                            continue;
                        }

                        $folio = strtolower(trim((string) $folio));
                        if ($folio === '') {
                            continue;
                        }

                        $query->orWhereRaw('LOWER(contenido_texto) LIKE ?', ['%' . $folio . '%']);
                    }

                    // Búsqueda semántica en contenido
                    if (($intent['confidence'] ?? 0) > 0.5) {
                        foreach ($intent['semantic_keywords'] as $semanticKeyword) {
                            if (!is_string($semanticKeyword) && !is_numeric($semanticKeyword)) {
                                continue;
                            }

                            $semanticKeyword = strtolower(trim((string) $semanticKeyword));
                            if ($semanticKeyword === '') {
                                continue;
                            }

                            $query->orWhereRaw('LOWER(contenido_texto) LIKE ?', ['%' . $semanticKeyword . '%']);
                        }
                    }

                    // Búsqueda por palabras expandidas en contenido
                    foreach ($expandedKeywords as $keyword) {
                        if (!is_string($keyword) && !is_numeric($keyword)) {
                            continue;
                        }

                        $keyword = strtolower(trim((string) $keyword));
                        if ($keyword === '' || strlen($keyword) <= 2) {
                            continue;
                        }

                        $query->orWhereRaw('LOWER(contenido_texto) LIKE ?', ['%' . $keyword . '%']);
                    }

                    // También buscar la consulta completa en el contenido
                    if ($normalizedQuery !== '') {
                        $query->orWhereRaw('LOWER(contenido_texto) LIKE ?', ['%' . $normalizedQuery . '%']);
                    }
                });

                $searchQuery->orWhereHas('tipoElemento', function ($query) use ($normalizedQuery, $expandedKeywords, $intent) {
                    // Búsqueda semántica en tipos de elemento
                    if (($intent['confidence'] ?? 0) > 0.5) {
                        foreach ($intent['semantic_keywords'] as $semanticKeyword) {
                            if (!is_string($semanticKeyword) && !is_numeric($semanticKeyword)) {
                                continue;
                            }

                            $semanticKeyword = strtolower(trim((string) $semanticKeyword));
                            if ($semanticKeyword === '') {
                                continue;
                            }

                            $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $semanticKeyword . '%']);
                        }
                    }

                    foreach ($expandedKeywords as $keyword) {
                        if (!is_string($keyword) && !is_numeric($keyword)) {
                            continue;
                        }

                        $keyword = strtolower(trim((string) $keyword));
                        if ($keyword === '' || strlen($keyword) <= 2) {
                            continue;
                        }

                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $keyword . '%']);
                    }

                    if ($normalizedQuery !== '') {
                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizedQuery . '%']);
                    }
                });

                $searchQuery->orWhereHas('tipoProceso', function ($query) use ($normalizedQuery, $expandedKeywords, $intent) {
                    // Búsqueda semántica en tipos de proceso
                    if (($intent['confidence'] ?? 0) > 0.5) {
                        foreach ($intent['semantic_keywords'] as $semanticKeyword) {
                            if (!is_string($semanticKeyword) && !is_numeric($semanticKeyword)) {
                                continue;
                            }

                            $semanticKeyword = strtolower(trim((string) $semanticKeyword));
                            if ($semanticKeyword === '') {
                                continue;
                            }

                            $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $semanticKeyword . '%']);
                        }
                    }

                    foreach ($expandedKeywords as $keyword) {
                        if (!is_string($keyword) && !is_numeric($keyword)) {
                            continue;
                        }

                        $keyword = strtolower(trim((string) $keyword));
                        if ($keyword === '' || strlen($keyword) <= 2) {
                            continue;
                        }

                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $keyword . '%']);
                    }

                    if ($normalizedQuery !== '') {
                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizedQuery . '%']);
                    }
                });

                $searchQuery->orWhereHas('unidadNegocio', function ($query) use ($normalizedQuery, $expandedKeywords) {
                    foreach ($expandedKeywords as $keyword) {
                        if (!is_string($keyword) && !is_numeric($keyword)) {
                            continue;
                        }

                        $keyword = strtolower(trim((string) $keyword));
                        if ($keyword === '' || strlen($keyword) <= 2) {
                            continue;
                        }

                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $keyword . '%']);
                    }

                    if ($normalizedQuery !== '') {
                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizedQuery . '%']);
                    }
                });
            });

            return $elementQuery
            ->limit(15)
            ->get()
            ->map(function ($elemento) use ($query, $intent) {
                $elemento->relevance_score = $this->calculateSemanticRelevance($elemento, $query, $intent);
                return $elemento;
            })
            ->sortByDesc('relevance_score');
            
        } catch (\Exception $e) {
            \Log::warning('Error buscando en elementos: ' . $e->getMessage());
            \Log::debug('Trace buscar elementos', ['trace' => $e->getTraceAsString()]);
            return collect();
        }
    }

    /**
     * Buscar en WordDocuments con scoring mejorado
     */
    private function searchInWordDocuments($query)
    {
        try {
            // Usar el método avanzado del modelo WordDocument
            $searchResults = WordDocument::searchWithAdvancedScoring($query, 5);
            
            return $searchResults->map(function ($result) {
                $document = $result['document'];
                $document->relevance_score = $result['score'];
                $document->matched_chunks = $result['matched_chunks'];
                return $document;
            });
            
        } catch (\Exception $e) {
            \Log::warning('Error buscando en word_documents: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Calcular relevancia semántica para elementos
     */
    private function calculateSemanticRelevance($elemento, $query, $intent)
    {
        $score = 0;
        $normalizedQuery = strtolower(trim($query));
        $folioPatterns = $this->extractFolioPatterns($query);
        
        // MÁXIMA PRIORIDAD: Folios específicos
        $folioElemento = strtolower($elemento->folio_elemento ?? '');
        foreach ($folioPatterns as $folio) {
            if (strpos($folioElemento, $folio) !== false) {
                $score += 150; // Peso MUY alto para folios específicos
            }
        }
        
        // ALTA PRIORIDAD: Folios en documento Word asociado
        if ($elemento->wordDocument && $elemento->wordDocument->contenido_texto) {
            $contenidoDoc = strtolower($elemento->wordDocument->contenido_texto);
            foreach ($folioPatterns as $folio) {
                $occurrences = substr_count($contenidoDoc, $folio);
                $score += $occurrences * 100; // Peso muy alto para folios en documento
            }
        }
        
        // RAZONAMIENTO SEMÁNTICO: Bonus por intención detectada
        if ($intent['confidence'] > 0.5) {
        $nombreElemento = strtolower($elemento->nombre_elemento ?? '');
            $contenidoDoc = strtolower($elemento->wordDocument->contenido_texto ?? '');
            
            foreach ($intent['semantic_keywords'] as $semanticKeyword) {
                // Score alto por coincidencias semánticas en nombre
                if (strpos($nombreElemento, $semanticKeyword) !== false) {
                    $score += 25 * $intent['confidence'];
                }
                
                // Score por coincidencias semánticas en contenido
                if (strpos($contenidoDoc, $semanticKeyword) !== false) {
                    $occurrences = substr_count($contenidoDoc, $semanticKeyword);
                    $score += $occurrences * 15 * $intent['confidence'];
                }
            }
            
            // Bonus específico por tipo de intención
            switch ($intent['primary_intent']) {
                case 'buscar_procedimientos_lineamientos':
                    if ($elemento->tipoElemento && 
                        (strpos(strtolower($elemento->tipoElemento->nombre), 'procedimiento') !== false ||
                         strpos(strtolower($elemento->tipoElemento->nombre), 'lineamiento') !== false ||
                         strpos(strtolower($elemento->tipoElemento->nombre), 'política') !== false)) {
                        $score += 50;
                    }
                    break;
                case 'buscar_procedimientos':
                    if ($elemento->tipoElemento && 
                        strpos(strtolower($elemento->tipoElemento->nombre), 'procedimiento') !== false) {
                        $score += 40;
                    }
                    break;
                case 'buscar_lineamientos':
                    if ($elemento->tipoElemento && 
                        (strpos(strtolower($elemento->tipoElemento->nombre), 'lineamiento') !== false ||
                         strpos(strtolower($elemento->tipoElemento->nombre), 'política') !== false)) {
                        $score += 40;
                    }
                    break;
            }
        }
        
        // Score por coincidencias exactas en nombre
        $nombreElemento = strtolower($elemento->nombre_elemento ?? '');
        if (strpos($nombreElemento, $normalizedQuery) !== false) {
            $score += 30; // Peso alto para coincidencia exacta
        }
        
        // Score por tipo de elemento
        if ($elemento->tipoElemento) {
            $tipoNombre = strtolower($elemento->tipoElemento->nombre ?? '');
            if (strpos($tipoNombre, $normalizedQuery) !== false) {
                $score += 20;
            }
        }
        
        // Score por tipo de proceso
        if ($elemento->tipoProceso) {
            $procesoNombre = strtolower($elemento->tipoProceso->nombre ?? '');
            if (strpos($procesoNombre, $normalizedQuery) !== false) {
                $score += 15;
            }
        }
        
        // Score por unidad de negocio
        if ($elemento->unidadNegocio) {
            $unidadNombre = strtolower($elemento->unidadNegocio->nombre ?? '');
            if (strpos($unidadNombre, $normalizedQuery) !== false) {
                $score += 10;
            }
        }
        
        // Bonus si tiene documento Word asociado
        if ($elemento->wordDocument) {
            $score += 15;
        }
        
        return $score;
    }
    
    /**
     * Calcular relevancia para elementos (método legacy para compatibilidad)
     */
    private function calculateElementoRelevance($elemento, $query)
    {
        // Usar el nuevo método semántico con intención básica
        $basicIntent = [
            'primary_intent' => 'unknown',
            'semantic_keywords' => $this->extractSimpleKeywords($query),
            'confidence' => 0.3
        ];
        
        return $this->calculateSemanticRelevance($elemento, $query, $basicIntent);
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
     * Construir contexto enriquecido con todos los resultados
     */
    private function buildEnrichedContext($searchResults)
    {
        $contextParts = [];
        
        // Contexto de elementos encontrados
        if ($searchResults['elementos']->isNotEmpty()) {
            $contextParts[] = "=== ELEMENTOS ENCONTRADOS ===";
            
            foreach ($searchResults['elementos']->take(5) as $elemento) {
                $elementoInfo = [];
                $elementoInfo[] = "**Elemento:** {$elemento->nombre_elemento}";
                
                if ($elemento->folio_elemento) {
                    $elementoInfo[] = "**Folio:** {$elemento->folio_elemento}";
                }
                
                if ($elemento->tipoElemento) {
                    $elementoInfo[] = "**Tipo:** {$elemento->tipoElemento->nombre}";
                }
                
                if ($elemento->tipoProceso) {
                    $elementoInfo[] = "**Proceso:** {$elemento->tipoProceso->nombre}";
                }
                
                if ($elemento->unidadNegocio) {
                    $elementoInfo[] = "**Unidad de Negocio:** {$elemento->unidadNegocio->nombre}";
                }
                
                if ($elemento->puestoResponsable) {
                    $elementoInfo[] = "**Responsable:** {$elemento->puestoResponsable->nombre_puesto}";
                }
                
                if ($elemento->wordDocument && $elemento->wordDocument->contenido_texto) {
                    $contenido = substr($elemento->wordDocument->contenido_texto, 0, 500);
                    $elementoInfo[] = "**Contenido del documento:** {$contenido}...";
                }
                
                $elementoInfo[] = "**Relevancia:** {$elemento->relevance_score}";
                
                $contextParts[] = implode("\n", $elementoInfo);
            }
        }
        
        // Contexto de documentos Word encontrados
        if ($searchResults['word_documents']->isNotEmpty()) {
            $contextParts[] = "\n=== DOCUMENTOS WORD ENCONTRADOS ===";
            
            foreach ($searchResults['word_documents']->take(5) as $document) {
                $docInfo = [];
                $docInfo[] = "**Documento ID:** {$document->id}";
                
                if ($document->elemento) {
                    $docInfo[] = "**Elemento relacionado:** {$document->elemento->nombre_elemento}";
                }
                
                // Usar chunks específicos si están disponibles
                if (isset($document->matched_chunks) && !empty($document->matched_chunks)) {
                    $docInfo[] = "**Contenido relevante:**";
                    foreach (array_slice($document->matched_chunks, 0, 2) as $chunk) {
                        $docInfo[] = $chunk['content'];
                    }
                } else {
                    // Fallback: usar contenido truncado
                    $contenido = substr($document->contenido_texto, 0, 600);
                    $docInfo[] = "**Contenido:** {$contenido}...";
                }
                
                $docInfo[] = "**Relevancia:** {$document->relevance_score}";
                
                $contextParts[] = implode("\n", $docInfo);
            }
        }
        
        // Estadísticas de búsqueda
        $contextParts[] = "\n=== ESTADÍSTICAS DE BÚSQUEDA ===";
        $contextParts[] = "Total de elementos encontrados: {$searchResults['search_details']['elementos_found']}";
        $contextParts[] = "Total de documentos encontrados: {$searchResults['search_details']['documents_found']}";
        $contextParts[] = "Total de fuentes: {$searchResults['search_details']['total_sources']}";
        
        return implode("\n\n---\n\n", $contextParts);
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
     * Guardar respuesta en smart_indexes con scoring mejorado
     */
    private function saveToSmartIndex($query, $response, $method = 'ollama')
    {
        try {
            $normalizedQuery = strtolower(trim($query));
            
            // Verificar si ya existe para evitar duplicados
            $existing = SmartIndex::where('normalized_query', $normalizedQuery)->first();
            if ($existing) {
                // Actualizar el existente si el nuevo método tiene mayor confianza
                $newConfidence = $this->calculateConfidenceScore($method);
                if ($newConfidence > $existing->confidence_score) {
                    $existing->update([
                        'response' => $response,
                        'confidence_score' => $newConfidence,
                        'last_used_at' => now()
                    ]);
                }
                return;
            }
            
            SmartIndex::create([
                'original_query' => $query,
                'normalized_query' => $normalizedQuery,
                'keywords' => $this->extractSimpleKeywords($query),
                'entities' => $this->extractEntities($query),
                'response' => $response,
                'confidence_score' => $this->calculateConfidenceScore($method),
                'auto_generated' => true,
                'verified' => false,
                'last_used_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::warning('No se pudo guardar en smart_indexes: ' . $e->getMessage());
        }
    }

    /**
     * Calcular score de confianza basado en el método
     */
    private function calculateConfidenceScore($method)
    {
        return match($method) {
            'integrated_search' => 0.85, // Alta confianza: datos de múltiples fuentes
            'data_based' => 0.80,        // Alta confianza: basado en datos reales
            'ollama_advanced' => 0.75,   // Buena confianza: IA con contexto
            'verified' => 1.0,           // Máxima confianza: verificado manualmente
            'ollama' => 0.6,             // Media confianza: IA sin contexto
            'ollama_no_context' => 0.5,  // Baja confianza: IA sin contexto específico
            'generic_fallback' => 0.2,   // Muy baja confianza: respuesta genérica
            default => 0.4
        };
    }

    /**
     * Extraer entidades básicas de la consulta
     */
    private function extractEntities($query)
    {
        $entities = [];
        $normalizedQuery = strtolower($query);
        
        // Detectar tipos de entidades comunes
        $patterns = [
            'elemento' => '/\b(elemento|documento|formato|procedimiento)\b/i',
            'proceso' => '/\b(proceso|flujo|workflow)\b/i',
            'unidad' => '/\b(unidad|área|departamento|división)\b/i',
            'puesto' => '/\b(puesto|cargo|responsable|ejecutor)\b/i',
            'fecha' => '/\b(fecha|período|plazo|revisión)\b/i',
            'estado' => '/\b(estado|estatus|semáforo|crítico|normal)\b/i'
        ];
        
        foreach ($patterns as $entity => $pattern) {
            if (preg_match($pattern, $normalizedQuery)) {
                $entities[] = $entity;
            }
        }
        
        return $entities;
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
     * Extraer patrones de folios de la consulta
     */
    private function extractFolioPatterns($query)
    {
        $folios = [];
        $normalizedQuery = strtolower($query);
        
        // Patrones comunes para folios:
        // GC + números (GC2134, GC25170, etc.)
        // Letras + números en general
        $patterns = [
            '/\b([a-z]{1,4}\d{3,6})\b/i',  // Patrón: 1-4 letras + 3-6 números (GC2134, ABC123456)
            '/\b(folio\s+([a-z]{1,4}\d{3,6}))\b/i', // "folio GC2134"
            '/\b([a-z]+\d+[a-z]*\d*)\b/i', // Patrones mixtos alfanuméricos
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $normalizedQuery, $matches)) {
                // Tomar el grupo de captura más específico
                $captures = isset($matches[2]) && !empty(array_filter($matches[2])) ? $matches[2] : $matches[1];
                foreach ($captures as $match) {
                    if (!empty(trim($match)) && strlen($match) >= 3) {
                        $folios[] = strtolower(trim($match));
                    }
                }
            }
        }
        
        // También buscar códigos que puedan estar separados por espacios
        if (preg_match('/\b([a-z]{1,4})\s*(\d{3,6})\b/i', $normalizedQuery, $matches)) {
            $folios[] = strtolower($matches[1] . $matches[2]);
        }
        
        return array_unique($folios);
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

    /**
     * Método público para búsqueda directa en elementos (API)
     */
    public function searchElementos($query, $limit = 10)
    {
        return $this->searchInElementos($query)->take($limit);
    }

    /**
     * Método público para búsqueda directa en documentos Word (API)
     */
    public function searchDocuments($query, $limit = 10)
    {
        return $this->searchInWordDocuments($query)->take($limit);
    }

    /**
     * Método público para obtener estadísticas de búsqueda
     */
    public function getSearchStats($query)
    {
        $searchResults = $this->performIntegratedSearch($query);
        
        return [
            'query' => $query,
            'normalized_query' => strtolower(trim($query)),
            'keywords' => $this->extractSimpleKeywords($query),
            'entities' => $this->extractEntities($query),
            'elementos_found' => $searchResults['search_details']['elementos_found'],
            'documents_found' => $searchResults['search_details']['documents_found'],
            'total_sources' => $searchResults['search_details']['total_sources'],
            'has_cached_response' => $this->searchInSmartIndexes($query) !== null
        ];
    }

    /**
     * Método público para limpiar caché de respuestas con baja confianza
     */
    public function cleanLowConfidenceCache($threshold = 0.3)
    {
        try {
            $deleted = SmartIndex::where('confidence_score', '<', $threshold)
                                ->where('auto_generated', true)
                                ->delete();
            
     
            
            return $deleted;
        } catch (\Exception $e) {
            \Log::error('Error limpiando caché: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generar respuesta con IA usando contexto enriquecido y manejo de fallback
     */
    private function generateResponseWithFallback($query, $searchResults, $startTime, $userId, $sessionId)
    {
        try {
            // Verificar si Ollama está disponible
            $healthCheck = $this->ollamaService->healthCheck();
            
            if ($healthCheck !== 'ok') {
                \Log::warning('Ollama no disponible, usando respuesta basada en datos encontrados');
                return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
            }

            // Generar respuesta con contexto enriquecido
            $context = $this->applyToneInstruction($this->buildEnrichedContext($searchResults));
            $ollamaResponse = $this->ollamaService->generateResponse($query, $context);
            
            // Guardar respuesta en smart_indexes para futuras consultas
            $this->saveToSmartIndex($query, $ollamaResponse, 'integrated_search');
            
            $this->logAnalytics($query, $ollamaResponse, 'integrated_search', $startTime, $userId, $sessionId);
        
            return [
                'response' => $ollamaResponse,
                'method' => 'integrated_search',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => $searchResults['sources'],
                'search_details' => $searchResults['search_details'],
                'cached' => false
            ];
            
        } catch (\Exception $e) {
            \Log::warning('Error con IA, usando respuesta basada en datos: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Generar respuesta básica con IA sin contexto y manejo de fallback
     */
    private function generateBasicResponseWithFallback($query, $startTime, $userId, $sessionId)
    {
        try {
            // Verificar si Ollama está disponible
            $healthCheck = $this->ollamaService->healthCheck();
            
            if ($healthCheck !== 'ok') {
                \Log::warning('Ollama no disponible, usando respuesta genérica');
                return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
            }

            $ollamaResponse = $this->ollamaService->generateResponse($query, $this->applyToneInstruction());
            $this->saveToSmartIndex($query, $ollamaResponse, 'ollama_no_context');
            $this->logAnalytics($query, $ollamaResponse, 'ollama_no_context', $startTime, $userId, $sessionId);
            
            return [
                'response' => $ollamaResponse,
                'method' => 'ollama_no_context',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'cached' => false
            ];
            
        } catch (\Exception $e) {
            \Log::warning('Error con IA básica, usando respuesta genérica: ' . $e->getMessage());
            return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Generar respuesta basada únicamente en los datos encontrados con razonamiento semántico
     */
    private function generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId)
    {
        // Analizar la intención para generar una respuesta contextual
        $intent = $this->nlpProcessor->analyzeIntent($query);
        
        if ($searchResults['search_details']['total_sources'] == 0) {
            $response = $this->generateNoResultsResponse($query, $intent);
        } else {
            $response = $this->generateContextualResponse($query, $searchResults, $intent);
        }
        
        $this->saveToSmartIndex($query, $response, 'data_based_semantic');
        $this->logAnalytics($query, $response, 'data_based_semantic', $startTime, $userId, $sessionId);
        
        return [
            'response' => $response,
            'method' => 'data_based_semantic',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => $searchResults['sources'],
            'search_details' => $searchResults['search_details'],
            'cached' => false,
            'intent_detected' => $intent
        ];
    }
    
    /**
     * Generar respuesta contextual basada en la intención detectada
     */
    private function generateContextualResponse($query, $searchResults, $intent)
    {
        $sections = [];
        $totalElementos = $searchResults['search_details']['elementos_found'];
        $totalDocumentos = $searchResults['search_details']['documents_found'];

        $sections[] = $this->buildWarmGreeting($intent);
        
        // Introducción contextual basada en la intención
        switch ($intent['primary_intent']) {
            case 'buscar_procedimientos_lineamientos':
                $sections[] = "📋 **Procedimientos útiles para establecer lineamientos**";
                $sections[] = "He localizado recursos que pueden ayudarte a definir lineamientos claros para tu operación.";
                break;
            case 'buscar_procedimientos':
                $sections[] = "📋 **Procedimientos relevantes**";
                $sections[] = "Estos son los procedimientos que mejor responden a tu consulta.";
                break;
            case 'buscar_lineamientos':
                $sections[] = "📋 **Lineamientos y políticas alineadas con tu búsqueda**";
                $sections[] = "Te comparto los lineamientos que guardan mayor relación con la necesidad planteada.";
                break;
            default:
                $sections[] = "📋 **Información relevante encontrada**";
                $sections[] = "Aquí tienes un panorama de los elementos más útiles para tu consulta.";
        }
        
        // Resumen ejecutivo
        $sections[] = "🔎 **Resumen rápido:**\n- Elementos destacados: {$totalElementos}\n- Documentos relacionados: {$totalDocumentos}\n- Fuentes consultadas: {$searchResults['search_details']['total_sources']}";
        
        // Información de elementos con contexto mejorado
        if ($searchResults['elementos']->isNotEmpty()) {
            $elementos = $searchResults['elementos']->take(5);
            $elementosSection = "📌 **Elementos destacados:**\n";
            foreach ($elementos as $index => $elemento) {
                $detalleLinea = "- **" . ($index + 1) . ". {$elemento->nombre_elemento}**";
                
                if ($elemento->tipoElemento) {
                    $detalleLinea .= " · 📂 {$elemento->tipoElemento->nombre}";
                }
                
                if ($elemento->folio_elemento) {
                    $detalleLinea .= " · 🏷️ {$elemento->folio_elemento}";
                }
                
                if ($elemento->tipoProceso) {
                    $detalleLinea .= " · ⚙️ {$elemento->tipoProceso->nombre}";
                }
                
                if ($elemento->unidadNegocio) {
                    $detalleLinea .= " · 🏢 {$elemento->unidadNegocio->nombre}";
                }
                
                if ($elemento->puestoResponsable) {
                    $detalleLinea .= " · 👤 {$elemento->puestoResponsable->nombre_puesto}";
                }
                
                $detalleLinea .= " · ⭐ Relevancia: " . round($elemento->relevance_score, 1);
                $elementosSection .= $detalleLinea . "\n";
                
                // Mostrar fragmento del contenido si está disponible
                if ($elemento->wordDocument && $elemento->wordDocument->contenido_texto) {
                    $contenido = $elemento->wordDocument->contenido_texto;
                    $fragment = $this->extractRelevantFragment($contenido, $intent['semantic_keywords'], 150);
                    if ($fragment) {
                        $elementosSection .= "  📝 {$fragment}...\n";
                    }
                }
                
                $elementosSection .= "\n";
            }
            
            $sections[] = rtrim($elementosSection);
        }
        
        // Información de documentos
        if ($searchResults['word_documents']->isNotEmpty()) {
            $documentosSection = "📄 **Documentos sugeridos:**\n";
            foreach ($searchResults['word_documents']->take(3) as $index => $document) {
                $documentoLinea = "- Documento " . ($index + 1);
                if ($document->elemento) {
                    $documentoLinea .= " · {$document->elemento->nombre_elemento}";
                }
                $documentosSection .= $documentoLinea . "\n";
                
                // Mostrar fragmento relevante del contenido
                if (isset($document->matched_chunks) && !empty($document->matched_chunks)) {
                    $chunk = $document->matched_chunks[0];
                    $fragment = substr($chunk['content'], 0, 200);
                    $documentosSection .= "  📝 {$fragment}...\n";
                } elseif ($document->contenido_texto) {
                    $fragment = $this->extractRelevantFragment($document->contenido_texto, $intent['semantic_keywords'], 200);
                    if ($fragment) {
                        $documentosSection .= "  📝 {$fragment}...\n";
                    }
                }
            }
            $sections[] = rtrim($documentosSection);
        }
        
        // Sugerencia contextual basada en la intención
        $sugerencia = "";
        if ($intent['primary_intent'] === 'buscar_procedimientos_lineamientos') {
            $sugerencia = "💡 **Paso siguiente recomendado:** Revisa los procedimientos listados y valida que los lineamientos propuestos estén alineados con las prácticas vigentes.";
        } elseif ($intent['primary_intent'] === 'buscar_procedimientos') {
            $sugerencia = "💡 **Paso siguiente recomendado:** Analiza los procedimientos prioritarios y confirma si cubren el alcance requerido. Si necesitas más detalle, abre los documentos sugeridos.";
        } elseif ($intent['primary_intent'] === 'buscar_lineamientos') {
            $sugerencia = "💡 **Paso siguiente recomendado:** Contrasta estos lineamientos con tus políticas actuales para identificar brechas o necesidades de actualización.";
        }
        
        if ($sugerencia) {
            $sections[] = $sugerencia;
        }

        $sections[] = $this->buildWarmClosing();
        
        return implode("\n\n", array_filter($sections));
    }
    
    /**
     * Generar respuesta cuando no se encuentran resultados
     */
    private function generateNoResultsResponse($query, $intent)
    {
        $response = $this->buildWarmGreeting($intent) . "\n\n";
        $response .= "🔍 No encontré información específica sobre tu consulta en la base de conocimientos.\n\n";
        
        // Sugerencias contextuales basadas en la intención
        switch ($intent['primary_intent']) {
            case 'buscar_procedimientos_lineamientos':
                $response .= "💡 **Sugerencias para encontrar procedimientos sobre lineamientos:**\n";
                $response .= "• Intenta buscar términos como 'política', 'normativa', 'directriz'\n";
                $response .= "• Busca por unidades específicas como 'Calidad', 'Recursos Humanos'\n";
                $response .= "• Revisa documentos de 'Procedimientos' o 'Manuales'\n";
                break;
            case 'buscar_procedimientos':
                $response .= "💡 **Sugerencias para encontrar procedimientos:**\n";
                $response .= "• Especifica el área o proceso (ej: 'procedimiento de compras')\n";
                $response .= "• Busca por folio si lo conoces (ej: 'GC2134')\n";
                $response .= "• Intenta términos como 'proceso', 'metodología', 'protocolo'\n";
                break;
            case 'buscar_lineamientos':
                $response .= "💡 **Sugerencias para encontrar lineamientos:**\n";
                $response .= "• Busca términos como 'política', 'norma', 'directriz'\n";
                $response .= "• Especifica el área (ej: 'lineamientos de seguridad')\n";
                $response .= "• Revisa documentos de 'Políticas' o 'Normativas'\n";
                break;
            default:
                $response .= "💡 **Sugerencias:**\n";
                $response .= "• Reformula tu pregunta con términos más específicos\n";
                $response .= "• Incluye el área o proceso de interés\n";
                $response .= "• Si conoces algún folio, inclúyelo en la búsqueda\n";
        }
        
        $response .= "\n" . $this->buildWarmClosing();

        return $response;
    }
    
    /**
     * Extraer fragmento relevante del contenido basado en palabras clave semánticas
     */
    private function extractRelevantFragment($content, $semanticKeywords, $maxLength = 200)
    {
        $content = strtolower($content);
        $bestMatch = '';
        $bestScore = 0;
        
        // Dividir el contenido en párrafos
        $paragraphs = array_filter(explode("\n", $content));
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (strlen($paragraph) < 50) continue; // Saltar párrafos muy cortos
            
            $score = 0;
            foreach ($semanticKeywords as $keyword) {
                $score += substr_count($paragraph, strtolower($keyword));
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $paragraph;
            }
        }
        
        if ($bestMatch && strlen($bestMatch) > $maxLength) {
            $bestMatch = substr($bestMatch, 0, $maxLength);
            // Cortar en la última palabra completa
            $lastSpace = strrpos($bestMatch, ' ');
            if ($lastSpace !== false) {
                $bestMatch = substr($bestMatch, 0, $lastSpace);
            }
        }
        
        return $bestMatch;
    }

    /**
     * Generar respuesta genérica cuando no hay datos ni IA disponible
     */
    private function generateGenericResponse($query, $startTime, $userId, $sessionId)
    {
        $greeting = $this->buildWarmGreeting();
        $closing = $this->buildWarmClosing();

        $response = "{$greeting}\n\nPor ahora el sistema de IA está tardando en responder y no pude recuperar información específica. "
            . "Puedes intentar nuevamente en unos minutos o reformular tu pregunta con más contexto. {$closing}";
        
        $this->logAnalytics($query, $response, 'generic_fallback', $startTime, $userId, $sessionId);
        
        return [
            'response' => $response,
            'method' => 'generic_fallback',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'cached' => false,
            'error' => true,
            'error_type' => 'service_unavailable'
        ];
    }
}

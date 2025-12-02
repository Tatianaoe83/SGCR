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
    // private $ollamaService; // OLLAMA COMENTADO - SOLO USAR OPENAI
    private $paidAIService;
    private $wordDocumentSearch;
    private $nlpProcessor;
    private $conversationalToneInstruction;
    private $usePaidAI;
    
    // Configuración para búsqueda de Elementos
    private const ELEMENTO_SEARCH_LIMIT = 15;
    private const ELEMENTO_MIN_RELEVANCE_SCORE = 0;
    private const ELEMENTO_TIPO_ID = 2; // Tipo de elemento para búsqueda
    
    public function __construct()
    {
        $this->smartIndexing = new SmartIndexingService();
        // $this->ollamaService = new OllamaService(); // OLLAMA COMENTADO - SOLO USAR OPENAI
        $this->paidAIService = new PaidAIService();
        $this->wordDocumentSearch = new WordDocumentSearchService();
        $this->nlpProcessor = new NLPProcessor();
        $this->conversationalToneInstruction = $this->buildToneInstruction();
        
        // Verificar si hay configuración de IA de pago disponible
        $this->usePaidAI = !empty(config('services.ai.api_key')) && 
                          config('services.ai.provider') !== null;
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

    // ============================================
    // SECCIÓN: OPERACIONES CON ELEMENTO
    // ============================================

    /**
     * Buscar en el modelo Elemento con razonamiento semántico
     * Método principal centralizado para todas las búsquedas de Elemento
     */
    private function searchInElementos($query)
    {
        try {
            // Preparar datos de búsqueda
            $searchData = $this->prepareElementoSearchData($query);
            
            // Construir query base de Elemento
            $elementQuery = $this->buildElementoBaseQuery();
            
            // Aplicar condiciones de búsqueda
            $elementQuery = $this->applyElementoSearchConditions($elementQuery, $searchData);
            
            // Ejecutar búsqueda y calcular relevancia
            $elementos = $elementQuery
                ->limit(self::ELEMENTO_SEARCH_LIMIT)
                ->get()
                ->map(function ($elemento) use ($query, $searchData) {
                    $elemento->relevance_score = $this->calculateSemanticRelevance(
                        $elemento, 
                        $query, 
                        $searchData['intent']
                    );
                    return $elemento;
                })
                ->sortByDesc('relevance_score');
            
            return $elementos;
            
        } catch (\Exception $e) {
            \Log::warning('Error buscando en elementos: ' . $e->getMessage());
            \Log::debug('Trace buscar elementos', ['trace' => $e->getTraceAsString()]);
            return collect();
        }
    }
    
    /**
     * Preparar todos los datos necesarios para búsqueda en Elemento
     */
    private function prepareElementoSearchData($query): array
    {
        $normalizedQuery = strtolower(trim($query));
        
        // Análisis semántico de la consulta
        $intent = $this->nlpProcessor->analyzeIntent($query);

        // Extraer y normalizar keywords
        $keywords = $this->normalizeKeywords(
            $this->nlpProcessor->extractKeywords($normalizedQuery)
        );
        
        // Expandir keywords semánticamente
        $expandedKeywords = $this->normalizeKeywords(
            $this->nlpProcessor->expandSemanticTerms($keywords)
        );
        
        // Keywords semánticas de la intención
        $semanticKeywords = $this->normalizeKeywords(
            $intent['semantic_keywords'] ?? []
        );
        $intent['semantic_keywords'] = $semanticKeywords;
        
        // Extraer patrones de folios
        $folioPatterns = $this->extractFolioPatterns($query);
        
        return [
            'query' => $query,
            'normalized_query' => $normalizedQuery,
            'intent' => $intent,
            'keywords' => $keywords,
            'expanded_keywords' => $expandedKeywords,
            'semantic_keywords' => $semanticKeywords,
            'folio_patterns' => $folioPatterns
        ];
    }
    
    /**
     * Normalizar array de keywords
     */
    private function normalizeKeywords(array $keywords): array
    {
        return collect($keywords)
            ->filter(fn($keyword) => is_string($keyword) || is_numeric($keyword))
            ->map(fn($keyword) => strtolower(trim((string) $keyword)))
            ->filter(fn($keyword) => $keyword !== '')
            ->unique()
            ->values()
            ->all();
    }
    
    /**
     * Construir query base para Elemento con relaciones
     */
    private function buildElementoBaseQuery()
    {
        return Elemento::with([
                'tipoElemento', 
                'tipoProceso', 
                'unidadNegocio', 
                'puestoResponsable',
                'wordDocument'
            ])
        ->where('tipo_elemento_id', self::ELEMENTO_TIPO_ID)
            ->whereHas('wordDocument');
    }
    
    /**
     * Aplicar todas las condiciones de búsqueda a la query de Elemento
     */
    private function applyElementoSearchConditions($elementQuery, array $searchData)
    {
        return $elementQuery->where(function ($searchQuery) use ($searchData) {
            // Búsqueda en campos directos del Elemento
            $searchQuery->where(function ($elementConditions) use ($searchData) {
                $this->applyElementoDirectSearch($elementConditions, $searchData);
            });
            
            // Búsqueda en documentos Word relacionados
            $searchQuery->orWhereHas('wordDocument', function ($query) use ($searchData) {
                $this->applyElementoWordDocumentSearch($query, $searchData);
            });
            
            // Búsqueda en relaciones: tipoElemento
            $searchQuery->orWhereHas('tipoElemento', function ($query) use ($searchData) {
                $this->applyElementoRelationSearch($query, $searchData);
            });
            
            // Búsqueda en relaciones: tipoProceso
            $searchQuery->orWhereHas('tipoProceso', function ($query) use ($searchData) {
                $this->applyElementoRelationSearch($query, $searchData);
            });
            
            // Búsqueda en relaciones: unidadNegocio
            $searchQuery->orWhereHas('unidadNegocio', function ($query) use ($searchData) {
                $this->applyElementoUnidadNegocioSearch($query, $searchData);
            });
        });
    }
    
    /**
     * Aplicar búsqueda en campos directos del Elemento
     */
    private function applyElementoDirectSearch($query, array $searchData)
    {
        $folioPatterns = $searchData['folio_patterns'];
        $expandedKeywords = $searchData['expanded_keywords'];
        $semanticKeywords = $searchData['semantic_keywords'];
        $intent = $searchData['intent'];
        $normalizedQuery = $searchData['normalized_query'];
        
        // Prioridad 1: Búsqueda por folios (máxima prioridad)
        $this->applyFolioSearch($query, $folioPatterns, 'folio_elemento');
        
        // Prioridad 2: Búsqueda semántica en nombre_elemento
        if (($intent['confidence'] ?? 0) > 0.5) {
            $this->applyKeywordSearch($query, $semanticKeywords, 'nombre_elemento');
        }
        
        // Prioridad 3: Búsqueda por keywords expandidas
        $this->applyKeywordSearch($query, $expandedKeywords, 'nombre_elemento');
        $this->applyFolioSearch($query, $expandedKeywords, 'folio_elemento');
        
        // Prioridad 4: Fallback - búsqueda por consulta original
                    if ($normalizedQuery !== '') {
            $query->orWhereRaw('LOWER(nombre_elemento) LIKE ?', ['%' . $normalizedQuery . '%']);
        }
    }
    
    /**
     * Aplicar búsqueda en documentos Word relacionados
     */
    private function applyElementoWordDocumentSearch($query, array $searchData)
    {
        $folioPatterns = $searchData['folio_patterns'];
        $expandedKeywords = $searchData['expanded_keywords'];
        $semanticKeywords = $searchData['semantic_keywords'];
        $intent = $searchData['intent'];
        $normalizedQuery = $searchData['normalized_query'];
        
        // Búsqueda por folios en contenido
        $this->applyKeywordSearch($query, $folioPatterns, 'contenido_texto');

                    // Búsqueda semántica en contenido
                    if (($intent['confidence'] ?? 0) > 0.5) {
            $this->applyKeywordSearch($query, $semanticKeywords, 'contenido_texto');
        }
        
        // Búsqueda por keywords expandidas en contenido
        $this->applyKeywordSearch($query, $expandedKeywords, 'contenido_texto');
        
        // Búsqueda por consulta completa
        if ($normalizedQuery !== '') {
            $query->orWhereRaw('LOWER(contenido_texto) LIKE ?', ['%' . $normalizedQuery . '%']);
        }
    }
    
    /**
     * Aplicar búsqueda en relaciones del Elemento (tipoElemento, tipoProceso)
     */
    private function applyElementoRelationSearch($query, array $searchData)
    {
        $expandedKeywords = $searchData['expanded_keywords'];
        $semanticKeywords = $searchData['semantic_keywords'];
        $intent = $searchData['intent'];
        $normalizedQuery = $searchData['normalized_query'];
        
        // Búsqueda semántica
        if (($intent['confidence'] ?? 0) > 0.5) {
            $this->applyKeywordSearch($query, $semanticKeywords, 'nombre');
        }
        
        // Búsqueda por keywords expandidas
        $this->applyKeywordSearch($query, $expandedKeywords, 'nombre');
        
        // Fallback
                    if ($normalizedQuery !== '') {
            $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizedQuery . '%']);
        }
    }
    
    /**
     * Aplicar búsqueda en unidadNegocio (sin búsqueda semántica)
     */
    private function applyElementoUnidadNegocioSearch($query, array $searchData)
    {
        $expandedKeywords = $searchData['expanded_keywords'];
        $normalizedQuery = $searchData['normalized_query'];
        
        // Solo búsqueda por keywords expandidas
        $this->applyKeywordSearch($query, $expandedKeywords, 'nombre');
        
        // Fallback
                    if ($normalizedQuery !== '') {
                        $query->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizedQuery . '%']);
                    }
    }
    
    /**
     * Aplicar búsqueda por keywords en un campo específico
     */
    private function applyKeywordSearch($query, array $keywords, string $field)
    {
        foreach ($keywords as $keyword) {
                        if (!is_string($keyword) && !is_numeric($keyword)) {
                            continue;
                        }

                        $keyword = strtolower(trim((string) $keyword));
                        if ($keyword === '' || strlen($keyword) <= 2) {
                            continue;
                        }

            $query->orWhereRaw("LOWER({$field}) LIKE ?", ['%' . $keyword . '%']);
        }
    }
    
    /**
     * Aplicar búsqueda por folios en un campo específico
     */
    private function applyFolioSearch($query, array $folios, string $field)
    {
        foreach ($folios as $folio) {
            if (!is_string($folio) && !is_numeric($folio)) {
                            continue;
                        }

            $folio = strtolower(trim((string) $folio));
            if ($folio === '') {
                            continue;
                        }

            $query->orWhereRaw("LOWER({$field}) LIKE ?", ['%' . $folio . '%']);
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
     * Construir sección resumen de Elementos para respuesta contextual
     */
    private function buildElementoSummarySection($elementos, $intent): string
    {
        $elementosSection = "📌 **Elementos destacados:**\n";
        
        foreach ($elementos->take(5) as $index => $elemento) {
            $detalleLinea = $this->formatElementoSummaryLine($elemento, $index + 1);
            $elementosSection .= $detalleLinea . "\n";
            
            // Mostrar fragmento del contenido si está disponible
            $fragment = $this->getElementoContentFragment($elemento, $intent['semantic_keywords'] ?? []);
            if ($fragment) {
                $elementosSection .= "  📝 {$fragment}...\n";
            }
            
            $elementosSection .= "\n";
        }
        
        return rtrim($elementosSection);
    }
    
    /**
     * Formatear línea de resumen de un Elemento
     */
    private function formatElementoSummaryLine($elemento, int $index): string
    {
        $detalleLinea = "- **{$elemento->nombre_elemento}**\n";
        
        if ($elemento->tipoElemento) {
            $detalleLinea .= "  • Tipo: {$elemento->tipoElemento->nombre}\n";
        }
        
        if ($elemento->folio_elemento) {
            $detalleLinea .= "  • Folio: {$elemento->folio_elemento}\n";
        }
        
        if ($elemento->tipoProceso) {
            $detalleLinea .= "  • Proceso: {$elemento->tipoProceso->nombre}\n";
        }
        
        if ($elemento->unidadNegocio) {
            $detalleLinea .= "  • Unidad de Negocio: {$elemento->unidadNegocio->nombre}\n";
        }
        
        if ($elemento->puestoResponsable) {
            $detalleLinea .= "  • Responsable: {$elemento->puestoResponsable->nombre_puesto}\n";
        }
        
        $detalleLinea .= "  • Relevancia: " . round($elemento->relevance_score, 1);
        
        return rtrim($detalleLinea);
    }
    
    /**
     * Obtener fragmento de contenido relevante de un Elemento
     */
    private function getElementoContentFragment($elemento, array $semanticKeywords): ?string
    {
        if (!$elemento->wordDocument || !$elemento->wordDocument->contenido_texto) {
            return null;
        }
        
        $contenido = $elemento->wordDocument->contenido_texto;
        return $this->extractRelevantFragment($contenido, $semanticKeywords, 150);
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
        
        // Contexto de elementos encontrados (centralizado)
        if ($searchResults['elementos']->isNotEmpty()) {
            $contextParts[] = $this->buildElementoContextSection($searchResults['elementos']);
        }
        
        // Contexto de documentos Word encontrados
        if ($searchResults['word_documents']->isNotEmpty()) {
            $contextParts[] = $this->buildWordDocumentContextSection($searchResults['word_documents']);
        }
        
        // Estadísticas de búsqueda
        $contextParts[] = $this->buildSearchStatsSection($searchResults['search_details']);
        
        return implode("\n\n---\n\n", $contextParts);
    }
    
    /**
     * Construir sección de contexto para Elementos
     */
    private function buildElementoContextSection($elementos)
    {
        $contextParts = ["=== ELEMENTOS ENCONTRADOS ==="];
        
        foreach ($elementos->take(5) as $elemento) {
            $elementoInfo = $this->formatElementoForContext($elemento);
            $contextParts[] = implode("\n", $elementoInfo);
        }
        
        return implode("\n\n", $contextParts);
    }
    
    /**
     * Formatear un Elemento para contexto
     */
    private function formatElementoForContext($elemento): array
    {
        $elementoInfo = [];
        $elementoInfo[] = "=== INFORMACIÓN DEL ELEMENTO ===";
        $elementoInfo[] = "**Nombre del Elemento:** {$elemento->nombre_elemento}";
        
        if ($elemento->folio_elemento) {
            $elementoInfo[] = "**Folio:** {$elemento->folio_elemento}";
        }
        
        if ($elemento->tipoElemento) {
            $elementoInfo[] = "**Tipo de Elemento:** {$elemento->tipoElemento->nombre}";
        }
        
        if ($elemento->tipoProceso) {
            $elementoInfo[] = "**Tipo de Proceso:** {$elemento->tipoProceso->nombre}";
        }
        
        if ($elemento->unidadNegocio) {
            $elementoInfo[] = "**Unidad de Negocio:** {$elemento->unidadNegocio->nombre}";
        }
        
        // INFORMACIÓN DEL RESPONSABLE - SIEMPRE INCLUIR SI EXISTE
        if ($elemento->puestoResponsable) {
            $elementoInfo[] = "**Puesto Responsable:** {$elemento->puestoResponsable->nombre_puesto}";
            // Si hay más información del puesto, incluirla
            if (isset($elemento->puestoResponsable->nombre)) {
                $elementoInfo[] = "**Nombre del Responsable:** {$elemento->puestoResponsable->nombre}";
            }
        } else {
            $elementoInfo[] = "**Puesto Responsable:** No asignado";
        }
        
        // Información adicional del elemento si existe
        if ($elemento->version_elemento) {
            $elementoInfo[] = "**Versión:** {$elemento->version_elemento}";
        }
        
        if ($elemento->fecha_elemento) {
            $elementoInfo[] = "**Fecha:** {$elemento->fecha_elemento}";
        }
        
        if ($elemento->wordDocument && $elemento->wordDocument->contenido_texto) {
            $contenido = substr($elemento->wordDocument->contenido_texto, 0, 500);
            $elementoInfo[] = "**Contenido del documento relacionado:** {$contenido}...";
        }
        
        $elementoInfo[] = "**Relevancia de búsqueda:** {$elemento->relevance_score}";
        $elementoInfo[] = "---";
        
        return $elementoInfo;
    }
    
    /**
     * Construir sección de contexto para documentos Word
     */
    private function buildWordDocumentContextSection($documents)
    {
        $contextParts = ["=== DOCUMENTOS WORD ENCONTRADOS ==="];
        
        foreach ($documents->take(5) as $document) {
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
        
        return implode("\n\n", $contextParts);
    }
    
    /**
     * Construir sección de estadísticas de búsqueda
     */
    private function buildSearchStatsSection($searchDetails)
    {
        $stats = ["=== ESTADÍSTICAS DE BÚSQUEDA ==="];
        $stats[] = "Total de elementos encontrados: {$searchDetails['elementos_found']}";
        $stats[] = "Total de documentos encontrados: {$searchDetails['documents_found']}";
        $stats[] = "Total de fuentes: {$searchDetails['total_sources']}";
        
        return implode("\n", $stats);
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
     * Preparar contexto de documentos (método legacy - OLLAMA COMENTADO)
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
    private function saveToSmartIndex($query, $response, $method = 'paid_ai_integrated')
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
            // SOLO USAR OPENAI - OLLAMA COMENTADO
            if ($this->usePaidAI) {
                $healthCheck = $this->paidAIService->healthCheck();
                
                if ($healthCheck === 'ok') {
                    return $this->generatePaidAIResponse($query, $searchResults, $startTime, $userId, $sessionId);
                } else {
                    \Log::warning('IA de pago (OpenAI) no disponible, usando respuesta basada en datos');
                    return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
                }
            }
            
            // Si no hay IA de pago configurada, usar respuesta basada en datos
            \Log::warning('IA de pago no configurada, usando respuesta basada en datos');
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
            
            /* OLLAMA COMENTADO - SOLO USAR OPENAI
            // Verificar si Ollama está disponible
            $healthCheck = $this->ollamaService->healthCheck();
            
            if ($healthCheck !== 'ok') {
                \Log::warning('Ollama no disponible, usando respuesta basada en datos encontrados');
                return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
            }

            // PASO 3: Generar respuesta con contexto enriquecido
            $context = $this->applyToneInstruction($this->buildEnrichedContext($searchResults));
            
            // Medir tiempo antes de la llamada a IA
            $step3StartTime = microtime(true);
            
            try {
                // Intentar generar respuesta con timeout de 30 segundos
                $ollamaResponse = $this->ollamaService->generateResponse($query, $context, 30);
                
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
                
            } catch (\Exception $step3Exception) {
                // Verificar si el paso 3 tardó más de 30 segundos
                $step3Elapsed = microtime(true) - $step3StartTime;
                
                if ($step3Elapsed >= 30 || 
                    strpos($step3Exception->getMessage(), 'timeout') !== false || 
                    strpos($step3Exception->getMessage(), 'timed out') !== false ||
                    strpos($step3Exception->getMessage(), 'cURL error 28') !== false) {
                    
                    \Log::warning('Paso 3 tardó más de 30 segundos, solicitando más contexto');
                    
                    // Generar mensaje pidiendo más contexto
                    $contextRequestMessage = $this->buildWarmGreeting() . "\n\n";
                    $contextRequestMessage .= "La consulta está tomando más tiempo del esperado. Para darte una respuesta más precisa y rápida, ¿podrías proporcionarme más contexto o detalles específicos sobre lo que necesitas?\n\n";
                    $contextRequestMessage .= "Por ejemplo:\n";
                    $contextRequestMessage .= "• ¿Hay algún folio o código específico que conozcas?\n";
                    $contextRequestMessage .= "• ¿En qué área o proceso estás interesado?\n";
                    $contextRequestMessage .= "• ¿Buscas información sobre un procedimiento, lineamiento o política en particular?\n\n";
                    $contextRequestMessage .= $this->buildWarmClosing();
                    
                    $this->logAnalytics($query, $contextRequestMessage, 'timeout_context_request', $startTime, $userId, $sessionId);
                    
                    return [
                        'response' => $contextRequestMessage,
                        'method' => 'timeout_context_request',
                        'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                        'sources' => $searchResults['sources'],
                        'search_details' => $searchResults['search_details'],
                        'cached' => false,
                        'timeout' => true
                    ];
                }
                
                // Si no es un timeout, re-lanzar la excepción para que se maneje en el catch externo
                throw $step3Exception;
            }
            */
            
        } catch (\Exception $e) {
            \Log::warning('Error con IA, usando respuesta basada en datos: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Generar respuesta con IA de pago usando contexto enriquecido
     */
    private function generatePaidAIResponse($query, $searchResults, $startTime, $userId, $sessionId)
    {
        try {
            // Generar respuesta con contexto enriquecido
            $context = $this->applyToneInstruction($this->buildEnrichedContext($searchResults));
            
            // Medir tiempo antes de la llamada a IA
            $aiStartTime = microtime(true);
            
            try {
                // Generar respuesta con timeout de 30 segundos
                $aiResponse = $this->paidAIService->generateResponse($query, $context, 30);
                
                // Guardar respuesta en smart_indexes para futuras consultas
                $this->saveToSmartIndex($query, $aiResponse, 'paid_ai_integrated');
                
                $this->logAnalytics($query, $aiResponse, 'paid_ai_integrated', $startTime, $userId, $sessionId);
            
                return [
                    'response' => $aiResponse,
                    'method' => 'paid_ai_integrated',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => $searchResults['sources'],
                    'search_details' => $searchResults['search_details'],
                    'cached' => false,
                    'ai_provider' => config('services.ai.provider')
                ];
                
            } catch (\Exception $aiException) {
                // Verificar si tardó más de 30 segundos
                $aiElapsed = microtime(true) - $aiStartTime;
                
                if ($aiElapsed >= 30 || 
                    strpos($aiException->getMessage(), 'timeout') !== false || 
                    strpos($aiException->getMessage(), 'timed out') !== false) {
                    
                    \Log::warning('IA de pago tardó más de 30 segundos, usando respuesta basada en datos');
                    return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
                }
                
                throw $aiException;
            }
            
        } catch (\Exception $e) {
            \Log::warning('Error con IA de pago, usando respuesta basada en datos: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Generar respuesta básica con IA de pago sin contexto
     */
    private function generatePaidAIBasicResponse($query, $startTime, $userId, $sessionId)
    {
        try {
            // Medir tiempo antes de la llamada a IA
            $aiStartTime = microtime(true);
            
            try {
                // Generar respuesta con timeout de 30 segundos
                $aiResponse = $this->paidAIService->generateResponse($query, $this->applyToneInstruction(), 30);
                $this->saveToSmartIndex($query, $aiResponse, 'paid_ai_no_context');
                $this->logAnalytics($query, $aiResponse, 'paid_ai_no_context', $startTime, $userId, $sessionId);
                
                return [
                    'response' => $aiResponse,
                    'method' => 'paid_ai_no_context',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'cached' => false,
                    'ai_provider' => config('services.ai.provider')
                ];
                
            } catch (\Exception $aiException) {
                // Verificar si tardó más de 30 segundos
                $aiElapsed = microtime(true) - $aiStartTime;
                
                if ($aiElapsed >= 30 || 
                    strpos($aiException->getMessage(), 'timeout') !== false || 
                    strpos($aiException->getMessage(), 'timed out') !== false) {
                    
                    \Log::warning('IA de pago tardó más de 30 segundos, usando respuesta genérica');
                    return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
                }
                
                throw $aiException;
            }
            
        } catch (\Exception $e) {
            \Log::warning('Error con IA de pago, usando respuesta genérica: ' . $e->getMessage());
            return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Generar respuesta básica con IA sin contexto y manejo de fallback
     */
    private function generateBasicResponseWithFallback($query, $startTime, $userId, $sessionId)
    {
        try {
            // SOLO USAR OPENAI - OLLAMA COMENTADO
            if ($this->usePaidAI) {
                $healthCheck = $this->paidAIService->healthCheck();
                
                if ($healthCheck === 'ok') {
                    return $this->generatePaidAIBasicResponse($query, $startTime, $userId, $sessionId);
                } else {
                    \Log::warning('IA de pago (OpenAI) no disponible, usando respuesta genérica');
                    return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
                }
            }
            
            // Si no hay IA de pago configurada, usar respuesta genérica
            \Log::warning('IA de pago no configurada, usando respuesta genérica');
            return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
            
            /* OLLAMA COMENTADO - SOLO USAR OPENAI
            // Verificar si Ollama está disponible
            $healthCheck = $this->ollamaService->healthCheck();
            
            if ($healthCheck !== 'ok') {
                \Log::warning('Ollama no disponible, usando respuesta genérica');
                return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
            }

            // Medir tiempo antes de la llamada a IA
            $step3StartTime = microtime(true);
            
            try {
                // Intentar generar respuesta con timeout de 30 segundos
                $ollamaResponse = $this->ollamaService->generateResponse($query, $this->applyToneInstruction(), 30);
                $this->saveToSmartIndex($query, $ollamaResponse, 'ollama_no_context');
                $this->logAnalytics($query, $ollamaResponse, 'ollama_no_context', $startTime, $userId, $sessionId);
                
                return [
                    'response' => $ollamaResponse,
                    'method' => 'ollama_no_context',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'cached' => false
                ];
                
            } catch (\Exception $step3Exception) {
                // Verificar si el paso 3 tardó más de 30 segundos
                $step3Elapsed = microtime(true) - $step3StartTime;
                
                if ($step3Elapsed >= 30 || 
                    strpos($step3Exception->getMessage(), 'timeout') !== false || 
                    strpos($step3Exception->getMessage(), 'timed out') !== false ||
                    strpos($step3Exception->getMessage(), 'cURL error 28') !== false) {
                    
                    \Log::warning('Paso 3 tardó más de 30 segundos, solicitando más contexto');
                    
                    // Generar mensaje pidiendo más contexto
                    $contextRequestMessage = $this->buildWarmGreeting() . "\n\n";
                    $contextRequestMessage .= "⏱️ La consulta está tomando más tiempo del esperado. Para darte una respuesta más precisa y rápida, ¿podrías proporcionarme más contexto o detalles específicos sobre lo que necesitas?\n\n";
                    $contextRequestMessage .= "Por ejemplo:\n";
                    $contextRequestMessage .= "• ¿Hay algún folio o código específico que conozcas?\n";
                    $contextRequestMessage .= "• ¿En qué área o proceso estás interesado?\n";
                    $contextRequestMessage .= "• ¿Buscas información sobre un procedimiento, lineamiento o política en particular?\n\n";
                    $contextRequestMessage .= $this->buildWarmClosing();
                    
                    $this->logAnalytics($query, $contextRequestMessage, 'timeout_context_request', $startTime, $userId, $sessionId);
                    
                    return [
                        'response' => $contextRequestMessage,
                        'method' => 'timeout_context_request',
                        'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                        'cached' => false,
                        'timeout' => true
                    ];
                }
                
                // Si no es un timeout, re-lanzar la excepción para que se maneje en el catch externo
                throw $step3Exception;
            }
            */
            
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
        
        // Información de elementos con contexto mejorado (centralizado)
        if ($searchResults['elementos']->isNotEmpty()) {
            $sections[] = $this->buildElementoSummarySection($searchResults['elementos'], $intent);
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

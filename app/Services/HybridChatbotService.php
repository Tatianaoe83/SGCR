<?php

namespace App\Services;

use App\Models\ChatbotAnalytics;
use App\Models\WordDocument;
use App\Models\SmartIndex;
use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class HybridChatbotService
{
    private $smartIndexing;
    // private $ollamaService; // OLLAMA COMENTADO - SOLO USAR OPENAI
    private $paidAIService;
    private $wordDocumentSearch;
    private $nlpProcessor;
    private $conversationalToneInstruction;
    private $usePaidAI;
    private $userPuestoService;

    // Configuración para búsqueda de Elementos
    private const ELEMENTO_SEARCH_LIMIT = 15;
    private const ELEMENTO_MIN_RELEVANCE_SCORE = 10; // Umbral mínimo de relevancia para considerar un resultado válido
    private const ELEMENTO_TIPO_ID = 2; // Tipo de elemento para búsqueda

    public function __construct()
    {
        $this->smartIndexing = new SmartIndexingService();
        // $this->ollamaService = new OllamaService(); // OLLAMA COMENTADO - SOLO USAR OPENAI
        $this->paidAIService = new PaidAIService();
        $this->wordDocumentSearch = new WordDocumentSearchService();
        $this->nlpProcessor = new NLPProcessor();
        $this->conversationalToneInstruction = $this->buildToneInstruction();
        $this->userPuestoService = new UserPuestoService();

        // Verificar si hay configuración de IA de pago disponible
        $this->usePaidAI = !empty(config('services.ai.api_key')) &&
            config('services.ai.provider') !== null;
    }

    private function buildToneInstruction()
    {
        return "Instrucciones de tono: responde siempre en español con un estilo cálido, cercano y empático. "
            . "Utiliza un lenguaje claro, profesional y positivo. Incluye un saludo amable al inicio, explica la información de forma sencilla "
            . "y finaliza ofreciendo ayuda adicional si la persona lo necesita. Evita sonar robótico o demasiado formal. Responde de forma breve y clara.";
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

    /**
     * Contar palabras en texto en español
     */
    private function countWords(string $text): int
    {
        // Limpiar el texto (remover markdown y HTML básico)
        $cleanText = strip_tags($text);
        // Remover símbolos especiales pero mantener acentos
        $cleanText = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanText);
        // Normalizar espacios
        $cleanText = preg_replace('/\s+/', ' ', trim($cleanText));

        if (empty($cleanText)) {
            return 0;
        }

        // Dividir por espacios y contar
        $words = explode(' ', $cleanText);
        $wordCount = 0;

        foreach ($words as $word) {
            $word = trim($word);
            // Contar solo palabras con al menos 1 letra o número
            if (preg_match('/[\p{L}\p{N}]/u', $word)) {
                $wordCount++;
            }
        }

        return $wordCount;
    }

    /**
     * Ajustar respuesta a un rango de palabras (500-700 palabras)
     */
    private function adjustResponseLength(string $response, int $minWords = 250, int $maxWords = 400): string
    {
        $wordCount = $this->countWords($response);

        // Si está dentro del rango, retornar sin cambios
        if ($wordCount >= $minWords && $wordCount <= $maxWords) {
            return $response;
        }

        // Si tiene menos palabras, retornar como está (mejor tener contenido completo aunque sea corto)
        if ($wordCount < $minWords) {
            return $response;
        }

        // Si excede el máximo, recortar inteligentemente
        if ($wordCount > $maxWords) {
            // Limpiar y dividir en palabras
            $cleanText = strip_tags($response);
            $words = preg_split('/\s+/', $cleanText);

            // Tomar aproximadamente 325 palabras (punto medio del rango)
            $targetWords = 325;
            $wordsToKeep = array_slice($words, 0, $targetWords);

            // Reconstruir el texto
            $truncated = implode(' ', $wordsToKeep);

            // Intentar terminar en una oración completa
            // Buscar el último punto, exclamación o interrogación cerca del final
            $sentenceEnds = ['. ', '.\n', '!\n', '?\n', '.', '!', '?'];
            $bestCut = strlen($truncated);

            foreach ($sentenceEnds as $end) {
                $pos = strrpos($truncated, $end);
                if ($pos !== false && $pos > (strlen($truncated) * 0.8)) {
                    $bestCut = $pos + strlen($end);
                    break;
                }
            }

            if ($bestCut < strlen($truncated)) {
                $truncated = substr($truncated, 0, $bestCut);
            } else {
                // Si no encontramos un buen punto de corte, truncar y agregar puntos suspensivos
                $truncated = rtrim($truncated) . '...';
            }

            return $truncated;
        }

        return $response;
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
/**
     * Procesa la consulta del usuario gestionando el contexto (memoria) de la conversación.
     */
/**
     * Procesa la consulta del usuario gestionando el contexto con IA.
     */
    public function processQuery($query, $userId = null, $sessionId = null)
    {
        $startTime = microtime(true);

        // 1. Análisis preliminar
        $mode = $this->getQueryMode($query);
        if ($mode == 'conversation') {
            $response = $this->respondConversation($query);
            $this->logAnalytics($query, $response['response'], 'conversation_guidance', $startTime, $userId, $sessionId);
            return ['response' => $response['response'], 'method' => 'conversation_guidance', 'response_time_ms' => round((microtime(true) - $startTime) * 1000), 'cached' => false];
        }

        // 2. GESTIÓN DE CONTEXTO (USANDO LA LLAVE CORRECTA)
        // IMPORTANTE: Aquí estaba el error. Ahora usamos la función helper.
        $contextKey = $this->getContextKey($sessionId, $userId);
        
        $searchQuery = $query; 

        // Recuperamos tema anterior para protegerlo
        $cachedContext = \Cache::get($contextKey);
        $lastTopic = $cachedContext['title'] ?? null;

        // Contextualizar
        if ($this->usePaidAI && ($sessionId || $userId)) {
            $searchQuery = $this->contextualizeQueryWithAI($query, $sessionId, $userId);
        }

        // 3. BÚSQUEDA INTEGRADA
        try {
            $searchResults = $this->performIntegratedSearch($searchQuery);

            if ($searchResults['has_results']) {
                
                $newContextInfo = $this->extractBestContextInfo($searchResults);
                
                // --- PROTECCIÓN DE MEMORIA ---
                $shouldUpdateMemory = true;

                // Si es una pregunta de seguimiento (la búsqueda incluye el tema anterior),
                // NO borramos el tema principal aunque encontremos documentos basura.
                if ($lastTopic && stripos($searchQuery, $lastTopic) !== false) {
                    $shouldUpdateMemory = false;
                }

                // Guardamos solo si es un tema nuevo, válido y no estamos en modo "seguimiento"
                if ($shouldUpdateMemory && $newContextInfo && !in_array($newContextInfo['title'], ['Elemento', 'Procedimiento', 'Documento', 'Elemento del Sistema'])) {
                    \Cache::put($contextKey, $newContextInfo, 600); 
                    Log::info("💾 Nuevo Contexto Guardado: " . $newContextInfo['title']);
                }
                // -----------------------------

                $response = $this->generateResponseWithFallback($query, $searchResults, $startTime, $userId, $sessionId);
                if ($response) return $response;
                
            } else {
                return $this->generateBasicResponseWithFallback($query, $startTime, $userId, $sessionId);
            }
        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage());
            return ['response' => $this->getFallbackResponse($e->getMessage()), 'method' => 'fallback', 'error' => true];
        }
    }

    /**
     * Función auxiliar para extraer el documento o elemento "ganador" de los resultados.
     * CORREGIDA: Usa los nombres de atributo correctos para guardar el contexto real.
     */
    private function extractBestContextInfo($searchResults)
    {
        // Prioridad 1: Elementos (Suelen ser los más específicos en tu sistema)
        if (isset($searchResults['elementos']) && $searchResults['elementos']->isNotEmpty()) {
            $elem = $searchResults['elementos']->first();
            
            // ERROR ANTERIOR: usaba $elem->nombre (que no existe)
            // CORRECCIÓN: Usamos nombre_elemento, nombre, title o descripcion
            $titulo = $elem->nombre_elemento 
                   ?? $elem->nombre 
                   ?? $elem->title 
                   ?? $elem->descripcion 
                   ?? 'Elemento';

            return [
                'type'  => 'element',
                'title' => $titulo,
                'folio' => $elem->folio_elemento ?? '', 
                'id'    => $elem->id
            ];
        }

        // Prioridad 2: Documentos Word
        if (isset($searchResults['word_documents']) && $searchResults['word_documents']->isNotEmpty()) {
            $doc = $searchResults['word_documents']->first();
            
            // Aseguramos capturar el nombre correcto del archivo
            $titulo = $doc->nombre 
                   ?? $doc->title 
                   ?? $doc->original_name // A veces los docs se guardan así
                   ?? 'Documento';

            return [
                'type'  => 'document',
                'title' => $titulo,
                'folio' => $doc->folio ?? '',
                'id'    => $doc->id
            ];
        }

        return null;
    }

    /**
     * Realizar búsqueda integrada en todos los modelos
     */
    private function performIntegratedSearch($query)
    {
        $rawElementos = $this->searchInElementos($query);
        $filteredElementos = $this->filterValidElementos($rawElementos)
            ->map(function ($elemento) {
                $elemento->file_url = $this->buildPublicFileUrl(
                    $elemento->archivo_es_formato ?? null
                );
                return $elemento;
            });

        $wordDocuments = $this->searchInWordDocuments($query);

        $results = [
            'elementos' => $filteredElementos,
            'word_documents' => $wordDocuments,
            'has_results' => false,
            'sources' => [],
            'search_details' => []
        ];

        $results['has_results'] =
            $filteredElementos->isNotEmpty() ||
            $wordDocuments->isNotEmpty();

        $results['sources'] = [
            'elementos' => $filteredElementos->pluck('id_elemento')->toArray(),
            'word_documents' => $wordDocuments->pluck('id')->toArray()
        ];

        $results['search_details'] = [
            'elementos_found' => $filteredElementos->count(),
            'documents_found' => $wordDocuments->count(),
            'total_sources' =>
            $filteredElementos->count() + $wordDocuments->count()
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
                ->filter(function ($elemento) {
                    // Filtrar solo resultados con relevancia mínima
                    return $elemento->relevance_score >= self::ELEMENTO_MIN_RELEVANCE_SCORE;
                })
                ->sortByDesc('relevance_score');

            return $elementos;
        } catch (\Exception $e) {
            Log::warning('Error buscando en elementos: ' . $e->getMessage());
            Log::debug('Trace buscar elementos', ['trace' => $e->getTraceAsString()]);
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
        $puestoUsuario = $this->resolvePuestoUsuario();
        $query = Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'puestoResponsable',
            'wordDocument'
        ]);

        if ($puestoUsuario !== null) {
            $query->visibleParaPuesto($puestoUsuario);
        }

        return $query;
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
            $puestoUsuarioId = $this->resolvePuestoUsuario();

            // Ejecutar búsqueda usando el service
            $result = $this->wordDocumentSearch->search($query, [
                'limit' => 5,
                'min_score' => 1,
                'use_cache' => true,
                'include_chunks' => true,
                'boost_recent' => true,
            ]);

            return collect($result['results'])
                ->map(function ($item) {
                    $document = $item['document'];
                    $document->relevance_score = $item['score'];
                    $document->matched_chunks = $item['matched_chunks'] ?? [];
                    $document->search_metadata = $item['metadata'] ?? [];
                    return $document;
                });
        } catch (\Exception $e) {
            Log::warning('Error buscando en word_documents: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Construir sección resumen de Elementos para respuesta contextual
     */
    private function buildElementoSummarySection($elementos, $intent): string
    {
        if ($elementos->isEmpty()) {
            return '';
        }

        $lines = [];

        foreach ($elementos as $index => $elemento) {
            $lines[] = $this->formatElementoSummaryLine($elemento, $index + 1);
        }

        return implode("\n", $lines);
    }


    /**
     * Formatear línea de resumen de un Elemento
     */
    private function formatElementoSummaryLine($elemento, int $index): string
    {
        $nombre = $elemento->nombre_elemento ?? 'Sin nombre';
        $folio = $elemento->folio_elemento ?? 'Sin folio';

        $line = "- **{$nombre}** - {$folio}";

        if (!empty($elemento->file_url)) {
            $line .= "\n  " . $this->renderDocumentoLink($elemento->file_url);
        }

        return $line;
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

    private function getElementoTextForAIDescription($elemento): ?string
    {
        if (!$elemento->wordDocument || empty($elemento->wordDocument->contenido_texto)) {
            return null;
        }

        return trim(mb_substr(strip_tags($elemento->wordDocument->contenido_texto), 0, 800));
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
                    if (
                        $elemento->tipoElemento &&
                        (strpos(strtolower($elemento->tipoElemento->nombre), 'procedimiento') !== false ||
                            strpos(strtolower($elemento->tipoElemento->nombre), 'lineamiento') !== false ||
                            strpos(strtolower($elemento->tipoElemento->nombre), 'política') !== false)
                    ) {
                        $score += 50;
                    }
                    break;
                case 'buscar_procedimientos':
                    if (
                        $elemento->tipoElemento &&
                        strpos(strtolower($elemento->tipoElemento->nombre), 'procedimiento') !== false
                    ) {
                        $score += 40;
                    }
                    break;
                case 'buscar_lineamientos':
                    if (
                        $elemento->tipoElemento &&
                        (strpos(strtolower($elemento->tipoElemento->nombre), 'lineamiento') !== false ||
                            strpos(strtolower($elemento->tipoElemento->nombre), 'política') !== false)
                    ) {
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
        if ($elementos->isEmpty()) {
            return '';
        }

        $contextParts = ["=== ELEMENTOS ENCONTRADOS ==="];

        foreach ($elementos as $elemento) {
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

        if (!empty($elemento->file_url)) {
            $elementoInfo[] = $this->renderDocumentoLink($elemento->file_url);
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

        if ($elemento->puestoResponsable) {
            $elementoInfo[] = "**Puesto Responsable:** {$elemento->puestoResponsable->nombre}";
        }

        $docText = $this->getElementoTextForAIDescription($elemento);

        if ($docText) {
            $elementoInfo[] = "**CONTENIDO DEL DOCUMENTO (para descripción):**";
            $elementoInfo[] = $docText;
        }

        $elementoInfo[] = "---";

        return $elementoInfo;
    }

    /**
     * Construir contexto INTELIGENTE:
     * Si no encuentra nada específico, lee el inicio del documento (resumen).
     */
    private function buildWordDocumentContextSection($documents)
    {
        $contextParts = ["=== DOCUMENTOS ENCONTRADOS ==="];

        foreach ($documents->take(3) as $document) {
            $docInfo = [];
            
            $titulo = $document->nombre ?? $document->title ?? 'Documento';
            $docInfo[] = "--- DOCUMENTO: {$titulo} (ID: {$document->id}) ---";

            if ($document->elemento) {
                $docInfo[] = "**Asociado a:** {$document->elemento->nombre_elemento}";
            }

            // --- LÓGICA DE "HOJA 89" ---
            
            // ESCENARIO A: Búsqueda Específica (Ej: "¿Qué dice la cláusula de recisión?")
            // Si el buscador encontró fragmentos exactos (que pueden estar en la pág 89),
            // USAMOS ESOS FRAGMENTOS. Es como ir directo a la página correcta.
            if (isset($document->matched_chunks) && !empty($document->matched_chunks)) {
                $docInfo[] = "🔎 **Información relevante encontrada (Extractos):**";
                
                // Unimos los 5 mejores fragmentos encontrados (pueden ser de cualquier hoja)
                foreach (array_slice($document->matched_chunks, 0, 5) as $chunk) {
                    $docInfo[] = "..." . trim($chunk['content']) . "...";
                }
                
                // Agregamos una nota para que la IA sepa que esto es solo una parte
                $docInfo[] = "(Nota: Estos son fragmentos extraídos de diferentes partes del documento)";
            } 
            // ESCENARIO B: Lectura General (Ej: "¿De qué trata el documento?")
            // Si no hay fragmentos específicos, leemos el principio (Resumen general)
            else {
                $fullContent = $document->contenido_texto ?? '';
                // Leemos hasta 5000 caracteres (aprox 2-3 páginas) para dar contexto general
                $limit = 5000; 
                $contentPreview = substr($fullContent, 0, $limit);
                
                if (strlen($fullContent) > $limit) {
                    $contentPreview .= "\n... [Documento muy largo, se muestra solo el inicio] ...";
                }
                
                $docInfo[] = "**Inicio del Documento (Contexto General):**\n" . $contentPreview;
            }

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
                $chunkContents = collect($matchedChunks)->take(2)->map(function ($chunk) {
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
        return $documents->map(function ($doc) {
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
            Log::warning('No se pudo guardar en smart_indexes: ' . $e->getMessage());
        }
    }

    /**
     * Calcular score de confianza basado en el método
     */
    private function calculateConfidenceScore($method)
    {
        return match ($method) {
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
        $keywords = array_filter($words, function ($word) use ($stopWords) {
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
            Log::warning('No se pudo guardar analytics: ' . $e->getMessage());
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
        return $this->filterValidElementos(
            $this->searchInElementos($query)
        )->take($limit);
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
            Log::error('Error limpiando caché: ' . $e->getMessage());
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
                    Log::warning('IA de pago (OpenAI) no disponible, usando respuesta basada en datos');
                    return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
                }
            }

            // Si no hay IA de pago configurada, usar respuesta basada en datos
            Log::warning('IA de pago no configurada, usando respuesta basada en datos');
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
            Log::warning('Error con IA, usando respuesta basada en datos: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Generar respuesta con IA de pago usando contexto enriquecido
     * AHORA INCLUYE MEMORIA DE CONVERSACIÓN Y BÚSQUEDA PROFUNDA
     */
    private function generatePaidAIResponse($query, $searchResults, $startTime, $userId, $sessionId)
    {
        try {
            // 1. Generar respuesta con contexto enriquecido y tono
            // CAMBIO IMPORTANTE: Pasamos $query como segundo parámetro.
            // Esto permite que 'buildEnrichedContext' busque fragmentos específicos (página 10, etc.)
            $context = $this->applyToneInstruction($this->buildEnrichedContext($searchResults, $query));

            // 2. OBTENER HISTORIAL
            // Recuperamos los últimos mensajes para que la IA tenga memoria
            $history = $this->getConversationHistory($sessionId);

            // Medir tiempo antes de la llamada a IA
            $aiStartTime = microtime(true);

            try {
                // 3. Generar respuesta PASANDO EL HISTORIAL (4to parámetro)
                $aiResponse = $this->paidAIService->generateResponse($query, $context, 30, $history);

                // Ajustar longitud a 250-400 palabras
                $aiResponse = $this->adjustResponseLength($aiResponse);

                // Guardar respuesta en smart_indexes (Opcional, descomentar si se usa)
                //$this->saveToSmartIndex($query, $aiResponse, 'paid_ai_integrated');

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

                if (
                    $aiElapsed >= 30 ||
                    strpos($aiException->getMessage(), 'timeout') !== false ||
                    strpos($aiException->getMessage(), 'timed out') !== false
                ) {

                    Log::warning('IA de pago tardó más de 30 segundos, usando respuesta basada en datos');
                    return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
                }

                throw $aiException;
            }
        } catch (\Exception $e) {
            Log::warning('Error con IA de pago, usando respuesta basada en datos: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    
    /**
     * Generar respuesta básica con IA de pago sin contexto (Chat General)
     * AHORA INCLUYE MEMORIA DE CONVERSACIÓN
     */
    private function generatePaidAIBasicResponse($query, $startTime, $userId, $sessionId)
    {
        try {
            // Medir tiempo antes de la llamada a IA
            $aiStartTime = microtime(true);

            // 1. OBTENER HISTORIAL (¡NUEVO!)
            $history = $this->getConversationHistory($sessionId);

            try {
                // 2. Generar respuesta PASANDO EL HISTORIAL (4to parámetro)
                // Nota: applyToneInstruction() actúa como el "contexto" o instrucciones del sistema aquí
                $aiResponse = $this->paidAIService->generateResponse($query, $this->applyToneInstruction(), 30, $history);

                // Ajustar longitud a 250-400 palabras
                $aiResponse = $this->adjustResponseLength($aiResponse);

                // Guardar respuesta en smart_indexes para futuras consultas
                //$this->saveToSmartIndex($query, $aiResponse, 'paid_ai_no_context');
                
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

                if (
                    $aiElapsed >= 30 ||
                    strpos($aiException->getMessage(), 'timeout') !== false ||
                    strpos($aiException->getMessage(), 'timed out') !== false
                ) {

                    Log::warning('IA de pago tardó más de 30 segundos, usando respuesta genérica');
                    return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
                }

                throw $aiException;
            }
        } catch (\Exception $e) {
            Log::warning('Error con IA de pago, usando respuesta genérica: ' . $e->getMessage());
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
                    Log::warning('IA de pago (OpenAI) no disponible, usando respuesta genérica');
                    return $this->generateGenericResponse($query, $startTime, $userId, $sessionId);
                }
            }

            // Si no hay IA de pago configurada, usar respuesta genérica
            Log::warning('IA de pago no configurada, usando respuesta genérica');
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
            Log::warning('Error con IA básica, usando respuesta genérica: ' . $e->getMessage());
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

        //$this->saveToSmartIndex($query, $response, 'data_based_semantic');
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
        $sections[] = $this->buildWarmGreeting($intent);

        if (
            isset($searchResults['elementos']) &&
            $searchResults['elementos']->isNotEmpty()
        ) {
            $sections[] = "📂 **Procedimientos encontrados:**\n";
            $sections[] = $this->buildElementoSummarySection(
                $searchResults['elementos'],
                $intent
            );
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

        // Extraer palabras clave principales de la consulta
        $keywords = $this->extractSimpleKeywords($query);
        $mainKeyword = !empty($keywords) ? $keywords[0] : '';

        // Construir mensaje específico sobre lo que no se encontró
        if (!empty($mainKeyword)) {
            // Intentar identificar si es un folio
            $folioPatterns = $this->extractFolioPatterns($query);
            if (!empty($folioPatterns)) {
                $response .= "No se encontró ningún registro del folio \"" . strtoupper($folioPatterns[0]) . "\" en la base de conocimientos.\n\n";
            } else {
                // Extraer término principal más significativo
                $mainTerms = array_slice($keywords, 0, 3);
                $mainTerm = implode(' ', $mainTerms);
                $response .= "No se encontró ningún registro sobre \"" . ucwords($mainTerm) . "\" en la base de conocimientos.\n\n";
            }
        } else {
            $response .= "No se encontró ningún registro relacionado con tu consulta en la base de conocimientos.\n\n";
        }

        $response .= $this->buildWarmClosing();

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

    //Generar respuesta genérica cuando no hay datos ni IA disponible
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

    // Determinar si la consulta es solo de conversación (saludos, cortesías, etc.)
    private function isConversationOnly(string $query): bool
    {
        $q = trim(mb_strtolower($query));

        if ($q === '' || mb_strlen($q) < 3) {
            return true;
        }

        $q = preg_replace('/[^\p{L}\p{N}\s]/u', '', $q);


        $greetings = [
            'hola',
            'buen dia',
            'buenos dias',
            'buenas tardes',
            'buenas noches',
        ];

        foreach ($greetings as $greeting) {
            if (str_starts_with($q, $greeting)) {
                if (str_word_count($q) <= 2) {
                    return true;
                }
            }
        }

        $courtesy = [
            'gracias',
            'muchas gracias',
            'ok',
            'ok gracias',
            'perfecto',
            'vale',
        ];

        return in_array($q, $courtesy, true);
    }

    // Determinar modo de consulta: 'conversation' o 'search'
    public function getQueryMode(string $query): string
    {
        return $this->isConversationOnly($query)
            ? 'conversation'
            : 'search';
    }

    // Responder con guía de conversación
    private function respondConversation(string $query)
    {
        return [
            'response' =>
            "👋 ¡Hola!\n" .
                "Puedo ayudarte a buscar procedimientos, lineamientos o documentos del sistema.\n\n" .
                "✍️ Por favor, escribe qué deseas buscar, por ejemplo:\n" .
                "• Procedimiento de compras\n" .
                "• Lineamiento ERP TI\n" .
                "• Folio PA-78647",
            'method' => 'conversation_guidance',
            'cached' => false
        ];
    }

    // Resolver el puesto de trabajo del usuario autenticado
    private function resolvePuestoUsuario(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if ($user->hasAnyRole('Super Administrador', 'Administrador')) {
            return null;
        }

        return $this->userPuestoService->obtenerPuesto($user);
    }

    // Filtrar y ordenar elementos válidos según criterios definidos
    public function filterValidElementos(Collection $elementos): Collection
    {
        return $elementos
            ->filter(function ($elemento) {
                return $elemento->tipo_elemento_id === self::ELEMENTO_TIPO_ID
                    && isset($elemento->relevance_score);
            })
            ->sortByDesc('relevance_score')
            ->take(self::ELEMENTO_SEARCH_LIMIT)
            ->values();
    }


    public function buildPublicFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $normalizedPath = preg_replace('#^storage/#', '', $path);

        return '/storage/' . ltrim($normalizedPath, '/');
    }


    private function renderDocumentoLink(?string $url): string
    {
        if (!$url) {
            return '';
        }

        return "📄 **[Ver documento]({$url})**";
    }

    /**
     * "TRADUCTOR DE CONTEXTO": Reescribe la pregunta vaga usando el historial.
     */
    private function contextualizeQueryWithAI(string $currentQuery, ?string $sessionId, ?string $userId = null)
    {
        if (!$sessionId && !$userId) return $currentQuery;

        $contextKey = $this->getContextKey($sessionId, $userId);
        $cachedContext = \Cache::get($contextKey);
        $lastTopic = $cachedContext['title'] ?? null;

        // Limpiar basura
        if (in_array($lastTopic, ['Elemento', 'Documento', 'Procedimiento', 'Elemento del Sistema'])) {
            $lastTopic = null;
        }

        if (!$lastTopic) return $currentQuery;

        // --- OPTIMIZACIÓN MANUAL AGRESIVA ---
        // Si la pregunta es de seguimiento, PEGALE EL TEMA. No preguntes a la IA.
        $words = explode(' ', trim($currentQuery));
        
        // Palabras que indican que el usuario sigue hablando de lo mismo
        $referencias = ['tipos', 'funciones', 'que', 'cuales', 'como', 'sus', 'su', 'el', 'la', 'origen', 'termino', 'nombre', 'historia', 'significado', 'donde', 'proviene'];
        
        // AUMENTADO A 12 PALABRAS para capturar: "¿y de dónde proviene el término transistor?"
        if (count($words) < 12 && $this->containsAny($currentQuery, $referencias)) {
             // Si la query NO tiene el tema, se lo pegamos al final
             if (stripos($currentQuery, $lastTopic) === false) {
                 $manualQuery = $currentQuery . " " . $lastTopic; // Ej: "¿y sus tipos? Transistores"
                 Log::info("⚡ Contexto Rápido: '$currentQuery' -> '$manualQuery'");
                 return $manualQuery;
             }
        }

        // Si es muy compleja, usamos IA
        try {
            $prompt = "Contexto: '$lastTopic'. Pregunta: '$currentQuery'. Reescribe para búsqueda explícita. Respuesta:";
            $refined = $this->paidAIService->generateResponse($prompt, "Eres un buscador.", 5);
            return trim(str_replace(['"', "'", "."], '', $refined));
        } catch (\Exception $e) {
            return $currentQuery . " " . $lastTopic;
        }
    }

    // Helper necesario para que funcione containsAny
    private function containsAny($str, array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str, $a) !== false) return true;
        }
        return false;
    }
    // Helper simple para quitar acentos en el fallback manual
    private function removeAccents($string) {
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
            ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'],
            $string
        );
    }

    /**
     * Recupera el historial de chat formateado para enviarlo a la IA.
     * CORRECCIÓN: Busca por UserID si existe, para mayor estabilidad.
     */
    private function getConversationHistory($sessionId, $limit = 6)
    {
        // Intentamos obtener el usuario actual si no se pasó explícitamente
        $user = auth()->user();
        $userId = $user ? $user->id : null;

        $query = ChatbotAnalytics::query();

        // Si tenemos usuario, buscamos su historial (es más seguro)
        if ($userId) {
            $query->where('user_id', $userId);
        } 
        // Si no, dependemos de la sesión
        elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return [];
        }

        $chats = $query->latest()
            ->take($limit)
            ->get()
            ->reverse();

        $history = [];
        foreach ($chats as $chat) {
            $history[] = ['role' => 'user', 'content' => $chat->query];
            $history[] = ['role' => 'assistant', 'content' => strip_tags($chat->response)];
        }

        return $history;
    }



    /**
     * Genera una clave de caché única y consistente.
     * CORRECCIÓN: Priorizamos UserID para evitar pérdida de memoria si la sesión cambia.
     */
    private function getContextKey(?string $sessionId, ?string $userId)
    {
        // IMPORTANTE: Primero UserID, luego SessionID.
        // Esto arregla el problema de "amnesia" si la cookie de sesión cambia.
        $suffix = $userId ?? $sessionId ?? 'guest';
        
        // Log para depurar (mira tu archivo laravel.log)
        // Si ves sufijos diferentes en cada mensaje, ese es el problema.
        // Log::info("🔑 Clave de contexto usada: chat_context_" . $suffix);
        
        return "chat_context_" . $suffix;
    }
}

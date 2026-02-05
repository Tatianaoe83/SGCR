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
        return "Eres un asistente virtual experto en procedimientos y documentos de calidad."
             . "\n\nREGLAS CRÍTICAS DE RESPUESTA:"
             . "\n1. Responde siempre en español con un tono cálido y profesional."
             . "\n2. PRIORIDAD DE FUENTES: Si el usuario pregunta por 'Responsables' o 'Definiciones', busca PRIMERO en las secciones específicas del documento (ej: '10. RESPONSABLE', '4. DEFINICIONES') que suelen estar al final o inicio del texto."
             . "\n3. La información dentro del 'CONTENIDO RELEVANTE' (el texto del documento) tiene MÁS VALOR que los metadatos del encabezado (Nombre, Folio, Puesto Responsable general)."
             . "\n4. Si el documento tiene una sección explícita numerada (ej: 10.1, 10.2), úsala textualmente."
             . "\n5. No repitas la ficha técnica completa en cada turno, ve al grano."
             . "\n6. Si hay contradicción, aclara: 'Según la ficha es X, pero el documento especifica Y'.";
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
     * Función auxiliar para extraer el MEJOR contexto (El Ganador)
     * CORREGIDA: Ordena por relevancia y exige un puntaje mínimo.
     */
    private function extractBestContextInfo($searchResults)
    {
        // 1. Definimos un puntaje mínimo para que valga la pena recordarlo.
        // Si el resultado tiene menos de 15 puntos, es basura y no lo guardamos.
        $MIN_SCORE_TO_MEMORIZE = 15; 

        // Revisamos si hay Elementos
        if (isset($searchResults['elementos']) && $searchResults['elementos']->isNotEmpty()) {
            
            // PASO CRÍTICO: Ordenamos explícitamente por relevancia (mayor a menor)
            // Esto asegura que "Transistores" (score alto) suba y "Automatización" (score bajo) baje.
            $bestElem = $searchResults['elementos']->sortByDesc('relevance_score')->first();
            
            // Validamos que el ganador tenga suficientes puntos
            if (($bestElem->relevance_score ?? 0) >= $MIN_SCORE_TO_MEMORIZE) {
                
                $titulo = $bestElem->nombre_elemento 
                       ?? $bestElem->nombre 
                       ?? $bestElem->title 
                       ?? 'Elemento';

                Log::info("💾 Contexto Ganador (Elemento): '$titulo' con Score: " . ($bestElem->relevance_score ?? 0));

                return [
                    'type'  => 'element',
                    'title' => $titulo,
                    'folio' => $bestElem->folio_elemento ?? '', 
                    'id'    => $bestElem->id
                ];
            } else {
                Log::info("⚠️ Contexto ignorado (Elemento): '" . ($bestElem->nombre_elemento ?? '') . "' Score muy bajo: " . ($bestElem->relevance_score ?? 0));
            }
        }

        // Si no hubo elementos buenos, revisamos Documentos
        if (isset($searchResults['word_documents']) && $searchResults['word_documents']->isNotEmpty()) {
            
            $bestDoc = $searchResults['word_documents']->sortByDesc('relevance_score')->first();
            
            if (($bestDoc->relevance_score ?? 0) >= $MIN_SCORE_TO_MEMORIZE) {
                
                $titulo = $bestDoc->nombre 
                       ?? $bestDoc->title 
                       ?? 'Documento';

                Log::info("💾 Contexto Ganador (Doc): '$titulo' con Score: " . ($bestDoc->relevance_score ?? 0));

                return [
                    'type'  => 'document',
                    'title' => $titulo,
                    'folio' => $bestDoc->folio ?? '',
                    'id'    => $bestDoc->id
                ];
            }
        }

        return null; // Si nada supera el umbral, no guardamos contexto.
    }
    /**
     * Realizar búsqueda integrada en todos los modelos
     * CORREGIDA: Pone los documentos importantes AL PRINCIPIO (prepend) para que la IA los lea.
     */
    private function performIntegratedSearch($query)
    {
        // 1. Buscamos Elementos (Las fichas)
        $rawElementos = $this->searchInElementos($query);
        $filteredElementos = $this->filterValidElementos($rawElementos)
            ->map(function ($elemento) {
                $elemento->file_url = $this->buildPublicFileUrl(
                    $elemento->archivo_es_formato ?? null
                );
                return $elemento;
            });

        // 2. Buscamos Documentos directamente
        $wordDocuments = $this->searchInWordDocuments($query);

        // --- EL ARREGLO MAESTRO (PRIORIDAD ALTA) ---
        // Si encontramos el Elemento "Transistores", metemos su documento
        // AL PRINCIPIO DE LA LISTA para asegurar que la IA lo lea primero.
        foreach ($filteredElementos as $elemento) {
            if ($elemento->wordDocument) {
                // Verificamos que no esté ya en la lista para no duplicar
                if (!$wordDocuments->contains('id', $elemento->wordDocument->id)) {
                    
                    $docToAdd = $elemento->wordDocument;
                    
                    // CAMBIO CRÍTICO: Usamos 'prepend' en vez de 'push'
                    // Esto coloca el documento en la posición #1 de lectura.
                    $wordDocuments->prepend($docToAdd);
                    
                    Log::info("Puente activado (Prioridad Alta): Documento agregado: " . $elemento->nombre_elemento);
                }
            }
        }
        // -------------------------------------------

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
            'total_sources' => $filteredElementos->count() + $wordDocuments->count()
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

            // Construir query base de Elemento (🔥 CARGA wordDocument 🔥)
            $elementQuery = $this->buildElementoBaseQuery()
                ->with('wordDocument');

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

    /**
     * Extrae texto del documento.
     * VERSIÓN FINAL: Soporta "PHP" (2 letras) y "Frases Exactas" (limpieza de preguntas).
     */
    private function getElementoTextForAIDescription($elemento, $query = null): ?string
    {
        if (!$elemento->wordDocument) return null;

        $wordDoc = $elemento->wordDocument;
        
        // 1. OBTENCIÓN DEL CONTENIDO
        $rawContent = $wordDoc->contenido_texto;
        if (empty($rawContent)) $rawContent = $wordDoc->contenido_estructurado;
        if (empty($rawContent)) return null;

        $json = json_decode($rawContent, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['parrafos'])) {
            $fullText = implode("\n", $json['parrafos']);
        } else {
            $fullText = $rawContent;
        }

        // 2. LA LAVADORA (Aquí está el ahorro de dinero) 🧼
        // Limpiamos etiquetas HTML
        $cleanText = strip_tags($fullText);

        // Patrones de basura comunes en tus documentos (Headers repetitivos)
        $garbagePatterns = [
            '/MANUAL DE PROCEDIMIENTOS/i',
            '/Página \d+ DE \d+/i', // Quita "Página 1 DE 10"
            '/Fecha de Emisión.*/i',
            '/Versión \d+/i',
            '/PC05 ENTREGAR LA OBRA/i', // Tu proceso repetitivo
            '/Unidad de Negocio:/i',
            '/_+/' // Líneas de guiones bajos
        ];
        
        $cleanText = preg_replace($garbagePatterns, '', $cleanText);
        
        // Compactar espacios: Cambiar múltiples saltos de línea por uno solo
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        
        $totalLen = mb_strlen($cleanText);

        // 3. REGLA DE COSTO (Límite Inteligente)
        // Ahora que está limpio, es probable que quepa todo en menos caracteres.
        if ($totalLen < 10000) {
            return $cleanText; 
        }

        
        $headText = mb_substr($cleanText, 0, 2500); // Cubre definiciones
        $footerText = mb_substr($cleanText, -1500); // Cubre responsables (Punto 10)

        if (empty($query)) {
            return $headText . "\n... [OMITIDO] ...\n" . $footerText;
        }

        // 5. BÚSQUEDA DEL RELLENO (Solo si hace falta)
        $snippets = [];
        $foundPositions = []; 
        $normalizedQuery = strtolower(trim($query));
        $chatGarbage = ['y', 'cuales', 'son', 'que', 'es', 'dime', 'sobre', 'el', 'la', 'los', 'las', 'un', 'una', 'en', 'me', 'refiero', 'al', 'quien', 'menciona'];

        $cleanQuery = str_replace(array_map(fn($w) => " $w ", $chatGarbage), ' ', " $normalizedQuery ");
        $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));

        // Búsqueda Exacta
        if (mb_strlen($cleanQuery) > 3) {
            $offset = 0;
            while (($exactPos = mb_stripos($cleanText, $cleanQuery, $offset)) !== false) {
                // Ignorar zonas ya cubiertas
                if ($exactPos < 2500 || $exactPos > ($totalLen - 1500)) {
                    $offset = $exactPos + 10; continue; 
                }
                
                $isDuplicate = false;
                foreach ($foundPositions as $existingPos) {
                    if (abs($exactPos - $existingPos) < 500) { $isDuplicate = true; break; }
                }

                if (!$isDuplicate) {
                    $start = max(2500, $exactPos - 150); 
                    $fragment = mb_substr($cleanText, $start, 800);
                    $snippets[] = "!!! EXACTA ('$cleanQuery') !!!: " . trim($fragment);
                    $foundPositions[] = $exactPos;
                }
                $offset = $exactPos + mb_strlen($cleanQuery) + 10;
                if (count($snippets) >= 2) break; 
            }
        }

        // Búsqueda Palabras Sueltas
        if (empty($snippets)) {
            $keywords = array_filter(explode(' ', $normalizedQuery), function($w) use ($chatGarbage) {
                return strlen($w) >= 2 && !in_array($w, array_merge($chatGarbage, ['de', 'para']));
            });

            foreach ($keywords as $word) {
                $offset = 0; $limitPerWord = 1; // Solo 1 fragmento por palabra para ahorrar
                $count = 0;
                while (($pos = mb_stripos($cleanText, $word, $offset)) !== false) {
                    if ($count >= $limitPerWord) break;
                    if ($pos < 2500 || $pos > ($totalLen - 1500)) { $offset = $pos + 10; continue; }

                    $isDuplicate = false;
                    foreach ($foundPositions as $existingPos) {
                        if (abs($pos - $existingPos) < 500) { $isDuplicate = true; break; }
                    }

                    if (!$isDuplicate) {
                        $start = max(2500, $pos - 100);
                        $fragment = mb_substr($cleanText, $start, 400);
                        $snippets[] = "... " . trim($fragment) . " ...";
                        $foundPositions[] = $pos;
                        $count++;
                    }
                    $offset = $pos + mb_strlen($word) + 50; 
                }
            }
        }

        $middleText = implode("\n", $snippets);
        
        // Límite final estricto para cuidar tu billetera (6000 chars máx)
        return mb_substr($headText . "\n...[BUSQUEDA]...\n" . $middleText . "\n...[FINAL]...\n" . $footerText, 0, 6000);
    }

    /**
     * Calcular relevancia semántica para elementos
     */
    /**
     * Calcular relevancia semántica (VERSIÓN FINAL MEJORADA)
     * Funciona para cualquier búsqueda contando palabras dentro del documento.
     */
    private function calculateSemanticRelevance($elemento, $query, $intent)
    {
        $score = 0;
        $normalizedQuery = strtolower(trim($query));
        $folioPatterns = $this->extractFolioPatterns($query);

        // ---------------------------------------------------------
        // 1. MÁXIMA PRIORIDAD: Folios específicos (ID exacto)
        // ---------------------------------------------------------
        $folioElemento = strtolower($elemento->folio_elemento ?? '');
        foreach ($folioPatterns as $folio) {
            if (strpos($folioElemento, $folio) !== false) {
                $score += 150; // ¡Bingo! Es el documento exacto.
            }
        }

        // ---------------------------------------------------------
        // 2. PRIORIDAD ALTA: Folios dentro del texto
        // ---------------------------------------------------------
        // Preparamos el contenido una sola vez para buscar
        $docContent = '';
        if ($elemento->wordDocument) {
            $docContent = strtolower($elemento->wordDocument->contenido_texto ?? '');
            // Fallback si contenido_texto está vacío (por si es JSON puro en estructurado)
            if (empty($docContent)) {
                $docContent = strtolower($elemento->wordDocument->contenido_estructurado ?? '');
            }
        }

        if (!empty($docContent)) {
            foreach ($folioPatterns as $folio) {
                $occurrences = substr_count($docContent, $folio);
                $score += $occurrences * 100;
            }
        }

        // ---------------------------------------------------------
        // 3. FUERZA BRUTA: Relevancia por Contenido (¡LA SOLUCIÓN!)
        // ---------------------------------------------------------
        // Esto hace que funcione para TODOS los temas, no solo transistores.
        if (!empty($docContent)) {
            // Limpiamos la query para quitar palabras vacías ("el", "la", "de")
            $stopWords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'que', 'y', 'en', 'por', 'para', 'con', 'se', 'su', 'sus', 'es', 'son', 'como'];
            
            $queryWords = explode(' ', $normalizedQuery);
            
            // Filtramos: solo palabras de más de 3 letras que no sean stopWords
            $meaningfulWords = array_filter($queryWords, function($w) use ($stopWords) {
                return strlen($w) > 3 && !in_array($w, $stopWords);
            });

            foreach ($meaningfulWords as $word) {
                // Contamos cuántas veces aparece la palabra clave en el documento
                $count = substr_count($docContent, $word);
                
                if ($count > 0) {
                    // SUMAR PUNTOS: 5 puntos por cada mención.
                    // Ejemplo: Si "transistor" aparece 6 veces = 30 puntos.
                    // Ponemos un TOPE de 60 puntos para no desbalancear todo.
                    $points = min($count * 5, 60);
                    $score += $points;
                }
            }
        }

        // ---------------------------------------------------------
        // 4. RAZONAMIENTO SEMÁNTICO (NLP)
        // ---------------------------------------------------------
        if (($intent['confidence'] ?? 0) > 0.5) {
            $nombreElemento = strtolower($elemento->nombre_elemento ?? '');
            
            foreach ($intent['semantic_keywords'] as $semanticKeyword) {
                // Coincidencia en título
                if (strpos($nombreElemento, $semanticKeyword) !== false) {
                    $score += 25 * $intent['confidence'];
                }

                // Coincidencia en contenido (Bonus extra NLP)
                if (!empty($docContent) && strpos($docContent, $semanticKeyword) !== false) {
                    $occurrences = substr_count($docContent, $semanticKeyword);
                    // Tope bajo aquí porque ya sumamos en fuerza bruta
                    $score += min($occurrences * 5, 20) * $intent['confidence']; 
                }
            }

            // Bonus por intención específica
            switch ($intent['primary_intent']) {
                case 'buscar_procedimientos_lineamientos':
                case 'buscar_procedimientos':
                    if ($elemento->tipoElemento && strpos(strtolower($elemento->tipoElemento->nombre), 'procedimiento') !== false) {
                        $score += 40;
                    }
                    break;
                case 'buscar_lineamientos':
                    if ($elemento->tipoElemento && strpos(strtolower($elemento->tipoElemento->nombre), 'lineamiento') !== false) {
                        $score += 40;
                    }
                    break;
            }
        }

        // ---------------------------------------------------------
        // 5. COINCIDENCIAS EN TÍTULO (METADATOS)
        // ---------------------------------------------------------
        $nombreElemento = strtolower($elemento->nombre_elemento ?? '');
        
        // Coincidencia exacta en el título
        if (strpos($nombreElemento, $normalizedQuery) !== false) {
            $score += 40; 
        }

        // Coincidencia parcial en el título
        if (!empty($meaningfulWords)) {
            foreach ($meaningfulWords as $word) {
                if (strpos($nombreElemento, $word) !== false) {
                    $score += 15; // 15 puntos por cada palabra clave en el título
                }
            }
        }

        // ---------------------------------------------------------
        // 6. BONUS MENORES
        // ---------------------------------------------------------
        
        // Bonus si tiene documento Word (porque es más rico en información)
        if ($elemento->wordDocument) {
            $score += 10;
        }

        // Coincidencias en metadatos secundarios
        if ($elemento->tipoElemento && strpos(strtolower($elemento->tipoElemento->nombre), $normalizedQuery) !== false) $score += 20;
        if ($elemento->tipoProceso && strpos(strtolower($elemento->tipoProceso->nombre), $normalizedQuery) !== false) $score += 15;
        if ($elemento->unidadNegocio && strpos(strtolower($elemento->unidadNegocio->nombre), $normalizedQuery) !== false) $score += 10;

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
    private function buildEnrichedContext($searchResults, $query = null)
    {
        $contextParts = [];
        $hasRealContent = false;

        // 1. CONTEXTO DE ELEMENTOS (solo si tienen texto real)
        if (
            isset($searchResults['elementos']) &&
            $searchResults['elementos']->isNotEmpty()
        ) {
            $elementosContext = $this->buildElementoContextSection(
                $searchResults['elementos'],
                $query // 👈 permite priorizar coincidencias
            );

            if (!empty(trim($elementosContext))) {
                $contextParts[] = $elementosContext;
                $hasRealContent = true;
            }
        }

        // 2. CONTEXTO DE DOCUMENTOS WORD (solo si hay texto)
        if (
            isset($searchResults['word_documents']) &&
            $searchResults['word_documents']->isNotEmpty()
        ) {
            $documentsContext = $this->buildWordDocumentContextSection(
                $searchResults['word_documents'],
                $query // 👈 clave para fragmentos relevantes
            );

            if (!empty(trim($documentsContext))) {
                $contextParts[] = $documentsContext;
                $hasRealContent = true;
            }
        }

        // 3. ESTADÍSTICAS SOLO SI HAY CONTENIDO REAL
        if ($hasRealContent && isset($searchResults['search_details'])) {
            $statsContext = $this->buildSearchStatsSection(
                $searchResults['search_details']
            );

            if (!empty(trim($statsContext))) {
                $contextParts[] = $statsContext;
            }
        }

        // 4. SI NO HAY CONTENIDO LEGIBLE → CONTEXTO VACÍO
        if (!$hasRealContent) {
            \Log::warning('⚠️ buildEnrichedContext sin contenido usable', [
                'query' => $query
            ]);
            return '';
        }

        $finalContext = implode("\n\n---\n\n", $contextParts);

        // 5. LOG FINAL PARA VERIFICAR QUE YA JALA
        \Log::info('🧠 CONTEXTO ENRIQUECIDO FINAL', [
            'chars' => mb_strlen($finalContext),
            'sections' => count($contextParts)
        ]);

        return $finalContext;
    }




    /**
     * Construir sección de contexto para Elementos.
     * ACTUALIZADA: Ahora recibe $query para pasárselo al extractor de texto.
     */
    private function buildElementoContextSection($elementos, $query = null)
    {
        if (!$elementos || $elementos->isEmpty()) {
            return '';
        }

        $contextParts = [];
        $hasValidElement = false;

        foreach ($elementos as $elemento) {
            // AQUÍ ESTÁ EL CAMBIO CLAVE: Pasamos $query
            $docText = $this->getElementoTextForAIDescription($elemento, $query);

            if (empty($docText)) {
                continue;
            }

            $elementoInfo = [];
            
            // Encabezado claro para la IA
            $elementoInfo[] = "=== INFORMACIÓN DEL ELEMENTO ===";
            
            if (!empty($elemento->nombre_elemento)) {
                $elementoInfo[] = "**Nombre del Elemento:** {$elemento->nombre_elemento}";
            }

            if (!empty($elemento->folio_elemento)) {
                $elementoInfo[] = "**Folio:** {$elemento->folio_elemento}";
            }

            if (!empty($elemento->file_url)) {
                $elementoInfo[] = $this->renderDocumentoLink($elemento->file_url);
            }

            // Pasamos los metadatos útiles
            if ($elemento->tipoElemento) $elementoInfo[] = "**Tipo:** {$elemento->tipoElemento->nombre}";
            if ($elemento->unidadNegocio) $elementoInfo[] = "**Unidad:** {$elemento->unidadNegocio->nombre}";

            // Contenido Inteligente
            $elementoInfo[] = "**CONTENIDO RELEVANTE ENCONTRADO EN EL DOCUMENTO:**";
            $elementoInfo[] = $docText; 
            $elementoInfo[] = "---";

            $contextParts[] = implode("\n", $elementoInfo);
            $hasValidElement = true;
        }

        if (!$hasValidElement) {
            return '';
        }

        array_unshift($contextParts, "=== ELEMENTOS ENCONTRADOS ===");

        return implode("\n\n", $contextParts);
    }


    /**
     * Formatear un Elemento para contexto
     */
    private function formatElementoForContext($elemento): array
    {
        // 1. Intentar obtener texto legible del documento
        $docText = $this->getElementoTextForAIDescription($elemento);

        // 2. Si NO hay texto real, este elemento NO debe entrar al contexto
        if (empty($docText)) {
            return [];
        }

        $elementoInfo = [];

        // 3. Encabezado claro
        $elementoInfo[] = "=== INFORMACIÓN DEL ELEMENTO ===";

        // 4. Datos básicos (solo los útiles)
        if (!empty($elemento->nombre_elemento)) {
            $elementoInfo[] = "**Nombre del Elemento:** {$elemento->nombre_elemento}";
        }

        if (!empty($elemento->folio_elemento)) {
            $elementoInfo[] = "**Folio:** {$elemento->folio_elemento}";
        }

        // 5. Link solo como referencia, no como fuente de conocimiento
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

        // 6. CONTENIDO REAL (la parte importante)
        $elementoInfo[] = "**CONTENIDO DEL DOCUMENTO (para descripción):**";
        $elementoInfo[] = $docText;

        // 7. Separador final
        $elementoInfo[] = "---";

        return $elementoInfo;
    }

    /**
     * Construir contexto: ESTRATEGIA "TOTAL RECALL"
     * Muestra SIEMPRE el inicio, el final y los fragmentos encontrados.
     * Ya no usa 'else', para evitar ocultar información si la búsqueda es pobre.
     */
    private function buildWordDocumentContextSection($documents, $query = '')
    {
        if (!$documents || $documents->isEmpty()) {
            return '';
        }

        $contextParts = [];
        $hasRealContent = false;
        $keywords = $this->extractSearchKeywords($query);

        foreach ($documents->take(15) as $document) {
            if (empty($document->contenido_texto)) {
                continue;
            }

            $fullContent = trim(strip_tags($document->contenido_texto));

            // Evitar texto basura
            if (mb_strlen($fullContent) < 100) {
                continue;
            }

            $docInfo = [];

            // 2. Encabezado del documento
            $titulo = $document->nombre ?? $document->title ?? 'Documento';
            $docInfo[] = "--- DOCUMENTO: {$titulo} (ID: {$document->id}) ---";

            if ($document->elemento && !empty($document->elemento->nombre_elemento)) {
                $docInfo[] = "**Asociado a:** {$document->elemento->nombre_elemento}";
            }

            $textLen = mb_strlen($fullContent);

            // 3. INICIO del documento (contexto general / definiciones)
            $startText = mb_substr($fullContent, 0, 3500);
            $docInfo[] = "**[INICIO DEL DOCUMENTO (Contexto y Definiciones)]**:\n"
                . $startText
                . ($textLen > 3500 ? "\n..." : "");

            // 4. FRAGMENTOS ESPECÍFICOS (solo si aportan algo distinto)
            $finalSnippets = [];

            // A) Chunks vectoriales
            if (isset($document->matched_chunks) && !empty($document->matched_chunks)) {
                foreach ($document->matched_chunks as $chunk) {
                    if (!empty($chunk['content'])) {
                        $snippet = trim(strip_tags($chunk['content']));
                        if (mb_strlen($snippet) > 50) {
                            $finalSnippets[] = $snippet;
                        }
                    }
                }
            }

            // B) Deep scan por keywords
            if (!empty($keywords)) {
                $manualSnippets = $this->findRelevantTextSnippets($fullContent, $keywords);
                foreach ($manualSnippets as $ms) {
                    $snippet = trim(strip_tags($ms));
                    if (mb_strlen($snippet) > 50) {
                        $finalSnippets[] = $snippet;
                    }
                }
            }

            if (!empty($finalSnippets)) {
                $uniqueSnippets = array_unique($finalSnippets);
                $addedSnippets = [];

                foreach ($uniqueSnippets as $snippet) {
                    // Evitar repetir lo que ya está en el inicio
                    if (strpos($startText, mb_substr($snippet, 0, 60)) === false) {
                        $addedSnippets[] = "..." . $snippet . "...";
                    }
                    if (count($addedSnippets) >= 10) {
                        break;
                    }
                }

                if (!empty($addedSnippets)) {
                    $docInfo[] = "🔎 **[SECCIONES ESPECÍFICAS ENCONTRADAS EN EL CUERPO]**:";
                    foreach ($addedSnippets as $snip) {
                        $docInfo[] = $snip;
                    }
                }
            }

            // 5. FINAL del documento (solo si aporta algo distinto)
            if ($textLen > 3500) {
                $endText = mb_substr($fullContent, -2500);

                if (strpos($startText, mb_substr($endText, 0, 60)) === false) {
                    $docInfo[] =
                        "...\n**[FINAL DEL DOCUMENTO (Referencias/Anexos)]**:\n" . $endText;
                }
            }

            $contextParts[] = implode("\n", $docInfo);
            $hasRealContent = true;
        }

        // 6. Si ningún documento aportó texto real, no devolvemos nada
        if (!$hasRealContent) {
            return '';
        }

        // Encabezado global SOLO si hay contenido
        array_unshift($contextParts, "=== DOCUMENTOS WORD ENCONTRADOS ===");

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
            // ===============================
            // SOLO USAR OPENAI
            // ===============================
            if ($this->usePaidAI) {

                $healthCheck = $this->paidAIService->healthCheck();

                if ($healthCheck === 'ok') {

                    // ✅ CONSTRUIR CONTEXTO ENRIQUECIDO (ANTES NO SE HACÍA)
                    $context = $this->applyToneInstruction(
                        $this->buildEnrichedContext($searchResults)
                    );

                    // 🔍 LOG DE VERIFICACIÓN (clave para debug)
                    \Log::info('🧠 CONTEXTO ENVIADO A OPENAI', [
                        'chars'   => mb_strlen($context),
                        'preview' => mb_substr($context, 0, 600)
                    ]);

                    // ✅ ENVIAR CONTEXTO A OPENAI
                    return $this->generatePaidAIResponse(
                        $query,
                        $context,
                        $searchResults,
                        $startTime,
                        $userId,
                        $sessionId
                    );

                } else {
                    \Log::warning('IA de pago (OpenAI) no disponible, usando respuesta basada en datos');
                    return $this->generateDataBasedResponse(
                        $query,
                        $searchResults,
                        $startTime,
                        $userId,
                        $sessionId
                    );
                }
            }

            // ===============================
            // SIN IA DE PAGO → DATA BASED
            // ===============================
            \Log::warning('IA de pago no configurada, usando respuesta basada en datos');

            return $this->generateDataBasedResponse(
                $query,
                $searchResults,
                $startTime,
                $userId,
                $sessionId
            );

        } catch (\Exception $e) {

            \Log::warning(
                'Error con IA, usando respuesta basada en datos: ' . $e->getMessage()
            );

            return $this->generateDataBasedResponse(
                $query,
                $searchResults,
                $startTime,
                $userId,
                $sessionId
            );
        }
    }


    /**
     * Generar respuesta con IA de pago usando contexto enriquecido
     * AHORA INCLUYE MEMORIA DE CONVERSACIÓN Y BÚSQUEDA PROFUNDA
     */
    private function generatePaidAIResponse(
        $query,
        $context,
        $searchResults,
        $startTime,
        $userId,
        $sessionId
    ) 
    {
        try {

            // 🔍 LOG CLAVE PARA DEBUG
            \Log::info('🧠 CONTEXTO FINAL OPENAI', [
                'chars' => mb_strlen($context),
                'preview' => mb_substr($context, 0, 600)
            ]);

            // 1. OBTENER HISTORIAL
            $history = $this->getConversationHistory($sessionId);

            // 2. MEDIR TIEMPO IA
            $aiStartTime = microtime(true);

            try {
                // 3. GENERAR RESPUESTA OPENAI
                $aiResponse = $this->paidAIService->generateResponse(
                    $query,
                    $context,
                    30,
                    $history
                );

                // 4. AJUSTAR LONGITUD
                $aiResponse = $this->adjustResponseLength($aiResponse);

                // 5. ANALYTICS
                $this->logAnalytics(
                    $query,
                    $aiResponse,
                    'paid_ai_integrated',
                    $startTime,
                    $userId,
                    $sessionId
                );

                return [
                    'response' => $aiResponse,
                    'method' => 'paid_ai_integrated',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => $searchResults['sources'] ?? [],
                    'search_details' => $searchResults['search_details'] ?? [],
                    'cached' => false,
                    'ai_provider' => config('services.ai.provider')
                ];

            } catch (\Exception $aiException) {

                $aiElapsed = microtime(true) - $aiStartTime;

                if (
                    $aiElapsed >= 30 ||
                    str_contains($aiException->getMessage(), 'timeout') ||
                    str_contains($aiException->getMessage(), 'timed out')
                ) {
                    \Log::warning('IA de pago tardó más de 30s, fallback a datos');
                    return $this->generateDataBasedResponse(
                        $query,
                        $searchResults,
                        $startTime,
                        $userId,
                        $sessionId
                    );
                }

                throw $aiException;
            }

        } catch (\Exception $e) {

            \Log::warning(
                'Error con IA de pago, usando respuesta basada en datos: ' . $e->getMessage()
            );

            return $this->generateDataBasedResponse(
                $query,
                $searchResults,
                $startTime,
                $userId,
                $sessionId
            );
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
     * Generar respuesta basada únicamente en los datos encontrados (Fallback sin IA Generativa)
     */
    private function generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId)
    {
        // 1. Analizar la intención para generar una respuesta contextual (aunque sea sin IA)
        $intent = $this->nlpProcessor->analyzeIntent($query);

        // 2. Verificar si encontramos algo
        if ($searchResults['search_details']['total_sources'] == 0) {
            // Si no hay nada, damos el mensaje de "No encontré nada"
            $response = $this->generateNoResultsResponse($query, $intent);
        } else {
            // Si hay resultados, construimos el resumen con enlaces (Título + Link)
            // Nota: Aquí podrías pasar también el $query si quisieras resaltar palabras clave en el futuro
            $response = $this->generateContextualResponse($query, $searchResults, $intent);
        }

        // 3. Registrar Analytics
        // Guardamos que se usó el método 'data_based_semantic'
        $this->logAnalytics($query, $response, 'data_based_semantic', $startTime, $userId, $sessionId);

        // 4. Retornar la estructura estándar
        return [
            'response' => $response,
            'method' => 'data_based_semantic', // Importante para saber que NO se usó la IA de pago
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
        Log::info("🔑 Clave de contexto usada: chat_context_" . $suffix);
        
        return "chat_context_" . $suffix;
    }

    // =================================================================
    // FUNCIONES AUXILIARES FALTANTES (PÉGALAS AL FINAL DE LA CLASE)
    // =================================================================

    /**
     * Helper: Limpia la pregunta y saca palabras clave
     * (Versión corregida que acepta números como "4" o "10")
     */
    private function extractSearchKeywords($query) {
        if (empty($query)) return [];
        
        $stopWords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'que', 'y', 'en', 'por', 'para', 'con', 'se', 'su', 'sus', 'es', 'son', 'como', 'donde', 'cual', 'cuales', 'dime', 'sobre', 'dame', 'necesito'];
        
        // Limpieza: permitimos letras, números y puntos (para cosas como "3.5")
        $clean = preg_replace('/[^\p{L}\p{N}\s\.]/u', '', mb_strtolower($query));
        $words = explode(' ', $clean);
        
        return array_filter($words, function($w) use ($stopWords) {
            $w = trim($w);
            if (empty($w)) return false;

            // ¡IMPORTANTE! Si es número, déjalo pasar (ej: "4", "10")
            if (is_numeric(str_replace('.', '', $w)) || preg_match('/\d/', $w)) {
                return true;
            }

            // Si es texto, aplica filtro de stopwords y longitud mínima
            return strlen($w) > 2 && !in_array($w, $stopWords);
        });
    }

    /**
     * Helper: Busca palabras clave en TODO el texto (Francotirador Persistente)
     * Busca sin límites para ignorar encabezados repetidos y encontrar el contenido real.
     */
    private function findRelevantTextSnippets($text, $keywords, $window = 1000) {
        if (empty($keywords) || empty($text)) return [];
        
        $snippets = [];
        $textLower = mb_strtolower($text);
        
        foreach ($keywords as $keyword) {
            $offset = 0;
            
            // Bucle infinito hasta que se acabe el texto
            while (($pos = mb_strpos($textLower, $keyword, $offset)) !== false) {
                
                // 1. Detección de Encabezados (Basura)
                // Miramos 250 caracteres alrededor para ver si huele a encabezado
                $checkStart = max(0, $pos - 100);
                $checkSnippet = mb_substr($textLower, $checkStart, 250);

                if (
                    strpos($checkSnippet, 'manual de procedimientos') !== false ||
                    strpos($checkSnippet, 'página') !== false || 
                    strpos($checkSnippet, 'clave') !== false ||
                    strpos($checkSnippet, 'fecha de emisión') !== false ||
                    strpos($checkSnippet, 'versión') !== false
                ) {
                    // Es basura, avanzamos un poco y seguimos buscando
                    $offset = $pos + mb_strlen($keyword);
                    continue; 
                }

                // 2. Es contenido real: Lo guardamos
                $start = max(0, $pos - ($window / 2));
                $length = $window; // Tomamos un bloque grande
                
                $realSnippet = mb_substr($text, $start, $length);
                // Limpieza de espacios múltiples
                $snippets[] = preg_replace('/\s+/', ' ', trim($realSnippet));
                
                // Avanzamos el cursor para buscar la siguiente ocurrencia de la misma palabra
                $offset = $pos + mb_strlen($keyword) + 500; 
            }
        }
        
        return array_unique($snippets);
    }
}

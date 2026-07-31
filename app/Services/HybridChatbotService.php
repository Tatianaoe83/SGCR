<?php

namespace App\Services;

use App\Models\ChatbotAnalytics;
use App\Models\WordDocument;
use App\Models\SmartIndex;
use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentChunk;


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
    private $embeddingService;

    // Umbrales de decisión semántica (coseno 0-1). Reemplazan las listas de palabras gatillo.
    // Sesgo fuerte a PERMANECER en el doc: una pregunta de seguimiento genérica ("y los
    // riesgos?", "el objetivo") tiene similitud moderada (~0.3-0.4) y casi siempre pierde
    // contra el doc del corpus que más habla de ese aspecto. Por eso sólo se cambia de tema
    // si el usuario NOMBRA otro documento, o si algo lo supera por un margen grande.
    // Calibrado con datos reales: seguimiento ~0.4, doc nombrado explícito domina vía pin.
    private const SIM_STAY = 0.30;        // sim_doc >= esto: seguimiento, se queda en el doc cacheado
    private const SIM_SWITCH_NEW = 0.50;  // un doc nuevo (no nombrado) debe ser al menos así de fuerte para robar foco
    private const SIM_SWITCH_MARGIN = 0.12; // y superar al cacheado por este margen
    private const SIM_DEAD = 0.20;        // sim_doc < esto y sin doc nuevo fuerte -> contexto muerto, se limpia

    // Peso del solape de título dentro del score híbrido (que va de 0 a 1). Alto a propósito:
    // un documento cuyo TÍTULO es el tema preguntado debe ganarle al que sólo dedica un
    // apartado a ese tema, aunque ese apartado tenga mejor coseno.
    private const W_TITLE_OVERLAP = 0.6;

    // Configuración para búsqueda de Elementos
    private const ELEMENTO_SEARCH_LIMIT = 15;
    // Candidatos que se traen de la BD para puntuar. Debe cubrir el catálogo publicado
    // completo: el recorte final se hace por relevancia, no por el orden de la BD.
    private const ELEMENTO_CANDIDATE_LIMIT = 500;
    private const ELEMENTO_MIN_RELEVANCE_SCORE = 10; // Umbral mínimo de relevancia para considerar un resultado válido

    // Tipos de elemento que el chatbot puede consultar. Única fuente de verdad: la usa la query y el filtro posterior.
    private const ELEMENTO_TIPOS_BUSCABLES = ['Procedimiento', 'Política', 'Procedimiento_Firmas'];

    public function __construct()
    {
        $this->smartIndexing = new SmartIndexingService();
        // $this->ollamaService = new OllamaService(); // OLLAMA COMENTADO - SOLO USAR OPENAI
        $this->paidAIService = new PaidAIService();
        $this->wordDocumentSearch = new WordDocumentSearchService();
        $this->nlpProcessor = new NLPProcessor();
        $this->conversationalToneInstruction = $this->buildToneInstruction();
        $this->userPuestoService = new UserPuestoService();
        $this->embeddingService = new EmbeddingService();

        // Verificar si hay configuración de IA de pago disponible
        $this->usePaidAI = !empty(config('services.ai.api_key')) &&
            config('services.ai.provider') !== null;
    }

    private function buildToneInstruction()
    {
        return "Eres Bob, el asistente del Sistema de Gestión de Calidad de Proser."
            . "\n\nTONO:"
            . "\n- Habla en español, de tú, como en un chat cercano y claro."
            . "\n- Sé amable y natural; puedes usar lenguaje cotidiano sin sonar de informe formal."
            . "\n- Responde directo. Una frase breve de contexto está bien si ayuda."
            . "\n\nFORMATO:"
            . "\n- Si piden lista, pasos, riesgos, responsables, definiciones o varios puntos, usa viñetas (-) o números."
            . "\n- Si la duda es corta, responde en 1–3 frases; no fuerces listas."
            . "\n- Usa **negritas** para conceptos clave, sin abusar."
            . "\n- Evita párrafos largos sin saltos de línea."
            . "\n\nCONTENIDO:"
            . "\n- Basa la respuesta solo en la información proporcionada. No inventes."
            . "\n- Para definiciones o responsables, busca primero en esas secciones del documento."
            . "\n- Si una definición aparece explícita, úsala tal cual."
            . "\n- Si el documento no contiene la respuesta, responde EXACTAMENTE con esta única línea y nada más: [[SIN_INFO]]";
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

        return " ¡Hola! Gracias por tu consulta{$intentHint}. A continuación te comparto la información más útil que encontré.";
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
     * Recortar respuestas excesivamente largas sin romper listas.
     * Las respuestas cortas o con viñetas se respetan tal cual.
     */
    private function adjustResponseLength(string $response, int $minWords = 0, int $maxWords = 700): string
    {
        $wordCount = $this->countWords($response);

        if ($wordCount <= $maxWords) {
            return $response;
        }

        // Respuestas con listas: recortar por líneas completas, no a mitad de viñeta.
        if (preg_match('/^\s*[-•*]\s+/m', $response) || preg_match('/^\s*\d+[.)]\s+/m', $response)) {
            $lines = preg_split('/\r\n|\r|\n/', $response) ?: [];
            $kept = [];
            $wordsSoFar = 0;

            foreach ($lines as $line) {
                $lineWords = $this->countWords($line);
                if ($wordsSoFar + $lineWords > $maxWords && !empty($kept)) {
                    break;
                }
                $kept[] = $line;
                $wordsSoFar += $lineWords;
            }

            return rtrim(implode("\n", $kept));
        }

        $cleanText = strip_tags($response);
        $words = preg_split('/\s+/', $cleanText) ?: [];
        $targetWords = (int) min(count($words), max(1, $maxWords - 20));
        $truncated = implode(' ', array_slice($words, 0, $targetWords));

        $sentenceEnds = ['. ', ".\n", "!\n", "?\n", '.', '!', '?'];
        $bestCut = strlen($truncated);

        foreach ($sentenceEnds as $end) {
            $pos = strrpos($truncated, $end);
            if ($pos !== false && $pos > (strlen($truncated) * 0.75)) {
                $bestCut = $pos + strlen($end);
                break;
            }
        }

        if ($bestCut < strlen($truncated)) {
            return substr($truncated, 0, $bestCut);
        }

        return rtrim($truncated) . '...';
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
     * Inyección de Contexto Forzada.
     */
    public function processQuery($query, $userId = null, $sessionId = null)
    {
        $startTime = microtime(true);
        $cleanQuery = trim($query);
        // Búsqueda con query normalizada (typos/coloquial → términos SGC).
        // A la IA se manda la pregunta original para que responda natural.
        $searchQuery = $this->normalizeColloquialQuery($cleanQuery);

        // 1. SALUDOS
        if (preg_match('/^(hola|holi|buenos dias|buenas tardes|hi|hello|start|inicio)\b/i', $cleanQuery)) {
            $contextKey = $this->getContextKey($sessionId, $userId);
            \Cache::forget($contextKey);
            return [
                'response' => "**Hola, soy Bob**, tu asistente de calidad en Proser.\n\nPuedo ayudarte a encontrar:\n\n- **Procedimientos** y su contenido (objetivo, alcance, responsables)\n- **Lineamientos** y políticas\n- Cualquier **documento** por su nombre o folio\n\nEscríbeme qué necesitas.",
                'method' => 'conversation_greeting',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }

        // 2. COMANDOS DE REINICIO
        if (preg_match('/^(olvida|borra|reinicia|limpia|reset)\b/i', $cleanQuery)) {
            $this->resetConversation($sessionId, $userId);
            return [
                'response' => "Listo, borré el contexto de esta conversación. Empezamos de cero.\n\n¿Sobre qué documento o tema quieres consultar ahora?",
                'method' => 'conversation_reset',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }

        // 3. RECUPERAR CONTEXTO
        $contextKey = $this->getContextKey($sessionId, $userId);
        $cachedContext = \Cache::get($contextKey);

        // 3.0 CATÁLOGO / LISTAS POR ÁREA O TEMA
        // "lista de procedimientos de calidad", "procedimientos de TI", "falta uno de TI".
        // Estas consultas miran el inventario de elementos, NO el documento en foco.
        // Si no soltamos el ancla, Bob inventa la lista desde el PDF anterior.
        if ($this->isCatalogBrowseQuery($cleanQuery) || $this->isCatalogBrowseQuery($searchQuery)) {
            \Cache::forget($contextKey);

            return $this->generateCatalogBrowseResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // 3.1 DECISIÓN SEMÁNTICA DE CONTEXTO
        // Reemplaza las listas de palabras gatillo (isContextMismatch / isFollowUp por regex):
        // compara el SIGNIFICADO de la pregunta contra el doc cacheado y contra el mejor doc
        // nuevo, y decide seguimiento vs cambio de tema por coseno, no por palabras.
        $hadContextMismatch = false;
        $isFollowUp = false;

        if ($cachedContext && !empty($cachedContext['id'])) {
            $qVec = $this->embeddingService->embed($searchQuery);

            if ($qVec !== null) {
                $simDoc = $this->bestChunkSimilarityForElemento($qVec, $cachedContext['id']);

                // Candidatos nuevos por semántica (para detectar si nombra otro doc y su fuerza).
                $topNew = $this->performSemanticSearch($searchQuery, 5);
                $simNew = $topNew->isNotEmpty() ? ($topNew->first()->semantic_score ?? 0.0) : 0.0;

                // ¿La pregunta NOMBRA explícitamente un documento distinto al cacheado?
                // Ése es el modo legítimo de cambiar de tema: mencionar el nuevo procedimiento.
                $namesOther = false;
                foreach ($topNew as $cand) {
                    $candElem = optional($cand->wordDocument)->elemento;
                    if ($candElem
                        && $candElem->getKey() != $cachedContext['id']
                        && $this->queryNamesElemento($searchQuery, $candElem)) {
                        $namesOther = true;
                        break;
                    }
                }

                if ($namesOther) {
                    // Cambio de tema explícito: nombró otro documento.
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $hadContextMismatch = true;
                    $decision = 'named_other';
                } elseif ($simDoc >= self::SIM_STAY) {
                    // Seguimiento: el doc cacheado sigue siendo pertinente. Sesgo a quedarse.
                    $isFollowUp = true;
                    $decision = 'stay';
                } elseif ($simNew >= self::SIM_SWITCH_NEW && $simNew > $simDoc + self::SIM_SWITCH_MARGIN) {
                    // Sin nombrar, pero algo nuevo es MUCHO más pertinente -> cambio de tema.
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $hadContextMismatch = true;
                    $decision = 'topic_change';
                } elseif ($simDoc < self::SIM_DEAD) {
                    // Ni el doc cacheado ni uno nuevo son pertinentes -> contexto muerto.
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $hadContextMismatch = true;
                    $decision = 'weak_context';
                } else {
                    // Zona gris baja: por continuidad, mejor quedarse.
                    $isFollowUp = true;
                    $decision = 'stay_gray';
                }

                \Log::info('Chatbot decisión semántica de contexto', [
                    'query' => $cleanQuery,
                    'search_query' => $searchQuery,
                    'sim_doc' => round($simDoc, 3),
                    'sim_new' => round($simNew, 3),
                    'names_other' => $namesOther,
                    'decision' => $decision,
                ]);
            } else {
                // Sin embeddings (API caída): fallback al comportamiento por palabras.
                if ($this->isContextMismatch($searchQuery, $cachedContext)) {
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $hadContextMismatch = true;
                } elseif (preg_match('/^(y|e|o|pero|entonces|ademas|tambien|cuales|sus|su|el|la|que|cual|como|donde|normas|reglas|objetivo|alcance|responsable)\b/i', $cleanQuery)
                    || (str_word_count($cleanQuery) < 5 && !$this->mentionsSpecificDocumentSignal($searchQuery))) {
                    $isFollowUp = true;
                }
            }
        }

        // 3.2 ACLARACIÓN TEMPRANA PARA CONSULTAS AMBIGUAS
        if ($this->shouldAskClarification($searchQuery, $cachedContext)) {
            return [
                'response' => $this->buildClarificationQuestion($cleanQuery),
                'method' => 'conversation_clarification',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }

        // El seguimiento ($isFollowUp) y el cambio de tema ($hadContextMismatch) ya se
        // decidieron arriba por similitud semántica (bloque 3.0).
        $finalResults = null;

        // MODO LEALTAD (FORZAR HISTORIAL)
        if ($isFollowUp && $cachedContext) {
            $prevElemento = \App\Models\Elemento::with('wordDocument')->find($cachedContext['id']);

            if ($prevElemento) {
                $finalResults = [
                    'elementos' => collect([$prevElemento])->filter(),
                    'word_documents' => collect([$prevElemento->wordDocument])->filter(),
                    'document_chunks' => collect(),
                    'has_results' => true,
                    'search_details' => ['forced_context' => true, 'documents_found' => 1]
                ];
            }
        }

        // MODO EXPLORADOR
        if (!$finalResults) {
            $finalResults = $this->performIntegratedSearch($searchQuery);
        }

        // 5. GENERAR RESPUESTA (PRIMERO GENERAMOS, LUEGO GUARDAMOS)
        $responseArray = null;

        // 🚫 CASO ESPECIAL: Si no hay resultados relevantes
        if ($finalResults && !$finalResults['has_results']) {            
            // No encontramos nada relevante
            $intent = $this->nlpProcessor->analyzeIntent($query);
            $responseArray = [
                'response' => $this->buildNoResultsFriendlyMessage($query, $intent),
                'method' => 'no_relevant_results',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [],
                'cached' => false,
            ];
            $responseArray['analytics_id'] = $this->logAnalytics($query, $responseArray['response'], 'no_results', $startTime, $userId, $sessionId);
        } elseif ($finalResults && $finalResults['has_results']) {
            $responseArray = $this->generateResponseWithFallback($query, $finalResults, $startTime, $userId, $sessionId);
            // Cambio de tema CON resultados: la respuesta ya se generó sobre el documento
            // nuevo. Antes se sobrescribía con "ese tema no aparece", tirando a la basura una
            // respuesta correcta cada vez que el usuario cambiaba de documento.
            if ($hadContextMismatch && !empty($responseArray['response'])) {
                $responseArray['response'] = $this->buildTopicSwitchNote($responseArray)
                    . $responseArray['response'];
            }
        } else {
            if ($hadContextMismatch) {
                $intent = $this->nlpProcessor->analyzeIntent($query);
                $responseArray = [
                    'response' => $this->buildNewTopicPreamble() . "\n\n" . $this->buildNoResultsFriendlyMessage($query, $intent),
                    'method' => 'topic_change_no_results',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => [],
                    'search_details' => [],
                    'cached' => false,
                ];
                $this->logAnalytics($query, $responseArray['response'], 'fallback', $startTime, $userId, $sessionId);
            } else {
                $responseArray = $this->generateBasicResponseWithFallback($query, $startTime, $userId, $sessionId);
            }
        }

        // 5.9 CONTEXTO AGOTADO: la pregunta no se responde con el documento en foco.
        // La IA marca [[SIN_INFO]]; también cuenta el caso de seguimiento forzado que no
        // devolvió nada. Se avisa nombrando el documento y se borra el contexto.
        $focoTitulo = $responseArray['final_context']['title']
            ?? ($cachedContext['title'] ?? null);

        $sinInfo = $this->responseSaysNoInfo($responseArray['response'] ?? '');

        // Marcador pegado a una respuesta con contenido real: se quita el marcador y se
        // conserva la respuesta, en vez de tirarla.
        if ($sinInfo && $this->markerHasRealContent($responseArray['response'])) {
            $responseArray['response'] = $this->stripNoInfoMarker($responseArray['response']);
            $sinInfo = false;
        }

        $seguimientoVacio = $isFollowUp
            && $cachedContext
            && in_array($responseArray['method'] ?? '', ['no_relevant_results', 'no_content_found'], true);

        if ($sinInfo || $seguimientoVacio) {
            $this->resetConversation($sessionId, $userId);

            return [
                'response' => $this->buildNotFoundInElementoMessage($cleanQuery, $focoTitulo),
                'method' => 'context_exhausted',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [],
                'cached' => false,
                'analytics_id' => $responseArray['analytics_id'] ?? null,
            ];
        }

        // 6. ACTUALIZAR CACHÉ CON EL GANADOR REAL
        // Si se decidió un ganador ('final_context'), usamos ese.
        // Si no, usamos el fallback de resultados de búsqueda.

        $contextToSave = null;

        if (isset($responseArray['final_context'])) {
            // Usamos su decisión.
            $contextToSave = $responseArray['final_context'];
        } elseif ($finalResults && isset($finalResults['word_documents']) && $finalResults['word_documents']->isNotEmpty()) {
            // Fallback: Si no hubo árbitro, usamos el primer resultado (Comportamiento antiguo)
            $bestMatch = $finalResults['word_documents']->first();
            $bestElementoId = $bestMatch->elemento_id ?? null;
            $contextToSave = [
                'id' => $bestElementoId,
                'title' => $bestMatch->nombre_elemento ?? $bestMatch->nombre ?? 'Documento'
            ];
        }

        if ($contextToSave) {
            // Validamos que no sea null antes de guardar
            if (!empty($contextToSave['id'])) {
                \Cache::put($contextKey, $contextToSave, 600);
            }
        }

        return $responseArray;
    }

    /**
     * Normaliza typos y lenguaje coloquial a términos del SGC para mejorar la búsqueda.
     * La pregunta original se conserva para la respuesta de la IA.
     */
    private function normalizeColloquialQuery(string $query): string
    {
        $normalized = mb_strtolower(trim($query));
        if ($normalized === '') {
            return $query;
        }

        // Frases coloquiales → conceptos del documento (orden: más largas primero).
        $phraseMap = [
            'pa que sirve' => 'objetivo',
            'para que sirve' => 'objetivo',
            'de que va' => 'objetivo',
            'de qué va' => 'objetivo',
            'a que va' => 'objetivo',
            'hasta donde aplica' => 'alcance',
            'hasta dónde aplica' => 'alcance',
            'donde aplica' => 'alcance',
            'quién lleva' => 'responsable',
            'quien lleva' => 'responsable',
            'quien es el encargado' => 'responsable',
            'quién es el encargado' => 'responsable',
            'quien esta a cargo' => 'responsable',
            'quién está a cargo' => 'responsable',
            'que puede salir mal' => 'riesgos',
            'qué puede salir mal' => 'riesgos',
            'dame el listado' => 'listado',
            'area de calidad' => 'calidad',
            'área de calidad' => 'calidad',
            'de ti' => 'tecnologia informacion',
            'de t.i.' => 'tecnologia informacion',
            'de t.i' => 'tecnologia informacion',
        ];

        foreach ($phraseMap as $from => $to) {
            if (str_contains($normalized, $from)) {
                $normalized = str_replace($from, $to, $normalized);
            }
        }

        // "TI" solo (2 letras) se pierde en NLP; expandir a términos buscables.
        $normalized = preg_replace('/\b(t\.?i\.?)\b/u', 'tecnologia informacion', $normalized) ?? $normalized;

        // Typos y sinónimos de una palabra.
        $wordMap = [
            'alcanze' => 'alcance',
            'objetibo' => 'objetivo',
            'objetvo' => 'objetivo',
            'responsavle' => 'responsable',
            'responsables' => 'responsables',
            'definis' => 'definiciones',
            'definicion' => 'definiciones',
            'definición' => 'definiciones',
            'riegos' => 'riesgos',
            'riesgo' => 'riesgos',
            'encargado' => 'responsable',
            'encargada' => 'responsable',
            'checa' => 'consulta',
            'chequea' => 'consulta',
            'mira' => 'consulta',
            'dime' => 'explica',
            'enumera' => 'lista',
            'enumerar' => 'lista',
            'listame' => 'lista',
            'enlista' => 'lista',
        ];

        $parts = preg_split('/\s+/u', $normalized) ?: [];
        $parts = array_map(function ($word) use ($wordMap) {
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $word) ?? $word;
            return $wordMap[$clean] ?? $word;
        }, $parts);

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? $normalized);
    }

    /**
     * ¿El usuario pide un listado / catálogo del sistema (no contenido de un documento)?
     */
    private function isCatalogBrowseQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        // Preguntas de contenido interno del documento actual: no son catálogo.
        if (preg_match('/\b(documentos? de referencia|anexos?|dentro del (documento|procedimiento)|de este (documento|procedimiento)|en (el|este) (documento|procedimiento))\b/u', $q)) {
            return false;
        }

        // Listas / inventario del sistema (exige entidades de catálogo o área).
        // Evita capturar "pásame la lista" / "lista de riesgos" del documento en foco.
        $pideLista = (bool) preg_match('/\b(lista|listado|listar|enumera|enumerar|inventario|todos los|todas las|cu[aá]les (son|hay|tengo)|cu[aá]ntos|mu[eé]strame|p[aá]same (la|el) (lista|listado)|quiero una lista|necesito una lista|dame una lista)\b/u', $q);
        $hablaDeCatalogo = (bool) preg_match('/\b(procedimientos?|documentos?|elementos?|lineamientos?|pol[ií]ticas?|reglamentos?)\b/u', $q);
        $hablaDeArea = (bool) preg_match('/\b(area|área|ti|t\.i\.?|tecnolog|calidad|informaci[oó]n|corporativo|construcci[oó]n)\b/u', $q);

        if ($pideLista && ($hablaDeCatalogo || $hablaDeArea)) {
            return true;
        }

        if (preg_match('/\bqu[eé] (procedimientos|documentos|elementos|pol[ií]ticas|lineamientos)\b/u', $q)) {
            return true;
        }

        // Exploración por área/tema: "procedimientos de TI", "del área de calidad".
        if ($hablaDeCatalogo && $hablaDeArea) {
            return true;
        }

        // Corrección de lista incompleta: "falta un procedimiento más de TI".
        if (
            preg_match('/\b(falta|faltan|hay m[aá]s|me falta|se te fue|te falt[oó]|incomplet)\b/u', $q)
            && preg_match('/\b(procedimiento|documento|lineamiento|pol[ií]tica|ti|t\.i\.?|tecnolog|calidad)\b/u', $q)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Términos de filtro para buscar en el catálogo (nombre, folio, unidad).
     */
    private function extractCatalogTopicTerms(string $query): array
    {
        $q = mb_strtolower(trim($query));
        $terms = [];

        if (preg_match('/\b(ti|t\.i\.?|tecnolog\w*|informaci[oó]n)\b/u', $q)) {
            // No usar "ti" suelto en LIKE: coincide con demasiados nombres.
            array_push($terms, 'tecnolog', 'informacion', 'información');
        }
        if (preg_match('/\bcalidad\b/u', $q)) {
            $terms[] = 'calidad';
        }
        if (preg_match('/\bcorporativo\b/u', $q)) {
            $terms[] = 'corporativo';
        }
        if (preg_match('/\bconstrucci[oó]n\b/u', $q)) {
            array_push($terms, 'construccion', 'construcción');
        }

        // Palabra significativa tras "área/de/del".
        if (preg_match_all('/\b(?:area|área|de|del)\s+([\p{L}]{4,})/u', $q, $matches)) {
            $skip = ['todos', 'todas', 'procedimientos', 'procedimiento', 'documentos', 'documento',
                'lineamientos', 'lineamiento', 'politicas', 'políticas', 'elementos', 'lista', 'listado'];
            foreach ($matches[1] as $word) {
                if (!in_array($word, $skip, true)) {
                    $terms[] = $word;
                }
            }
        }

        return array_values(array_unique(array_filter($terms, fn($t) => mb_strlen(trim($t)) >= 2)));
    }

    /**
     * Catálogo real de elementos publicados filtrado por tema/área.
     */
    private function searchCatalogElementos(array $topicTerms, int $limit = 50)
    {
        // No eager-load unidadNegocio: unidad_negocio_id puede ser array y rompe BelongsTo.
        $query = Elemento::with(['tipoElemento'])
            ->where('status', 'Publicado')
            ->where('active', true)
            ->whereHas('tipoElemento', fn($q) => $q->whereIn('nombre', self::ELEMENTO_TIPOS_BUSCABLES));

        if (!empty($topicTerms)) {
            $query->where(function ($outer) use ($topicTerms) {
                foreach ($topicTerms as $term) {
                    $term = mb_strtolower(trim($term));
                    if ($term === '') {
                        continue;
                    }
                    $like = '%' . $term . '%';
                    $outer->orWhereRaw('LOWER(nombre_elemento) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(folio_elemento, \'\')) LIKE ?', [$like]);
                }
            });
        }

        return $query->orderBy('nombre_elemento')->limit($limit)->get();
    }

    /**
     * Responde listados desde el inventario de la BD, sin anclar un documento concreto.
     */
    private function generateCatalogBrowseResponse(
        string $originalQuery,
        string $searchQuery,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $topicTerms = $this->extractCatalogTopicTerms($originalQuery . ' ' . $searchQuery);
        $elementos = $this->searchCatalogElementos($topicTerms);

        \Log::info('Chatbot catálogo / lista por área', [
            'query' => $originalQuery,
            'terms' => $topicTerms,
            'found' => $elementos->count(),
        ]);

        if ($elementos->isEmpty()) {
            $intent = $this->nlpProcessor->analyzeIntent($originalQuery);
            $msg = $this->buildNoResultsFriendlyMessage($originalQuery, $intent);

            return [
                'response' => $msg,
                'method' => 'catalog_browse_empty',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => ['catalog_terms' => $topicTerms],
                'cached' => false,
                'document' => null,
                'analytics_id' => $this->logAnalytics($originalQuery, $msg, 'catalog_browse_empty', $startTime, $userId, $sessionId),
            ];
        }

        $listaTexto = $elementos->map(function ($el) {
            $folio = $el->folio_elemento ?: 's/folio';
            $ver = $el->version_elemento ?: '?';
            $tipo = optional($el->tipoElemento)->nombre ?: 'Elemento';

            return "- {$folio}: {$el->nombre_elemento} (v{$ver}) — {$tipo}";
        })->implode("\n");

        $filtro = empty($topicTerms)
            ? 'catálogo general de procedimientos/políticas publicados'
            : 'filtro: ' . implode(', ', $topicTerms);

        $context =
            "╔══ INVENTARIO REAL DEL SISTEMA ({$filtro}) ══╗\n"
            . $listaTexto . "\n"
            . "╚════════════════════════════════════════════╝\n\n"
            . "TAREA:\n"
            . "- Responde SOLO con documentos de esta lista. No inventes ni uses el historial de otro procedimiento.\n"
            . "- Presenta una lista clara con viñetas (nombre y folio).\n"
            . "- Si el usuario dice que falta alguno, revisa de nuevo esta lista y corrige.\n"
            . "- No digas que salen de un documento concreto: son del catálogo del sistema.\n"
            . "- Al final puedes preguntar si quiere abrir o detallar alguno.\n";

        $history = $this->getConversationHistory($sessionId);

        try {
            if ($this->usePaidAI && $this->paidAIService->healthCheck() === 'ok') {
                $aiResponse = $this->paidAIService->generateResponse(
                    $originalQuery,
                    $context,
                    30,
                    $history,
                    null // sin elemento: no ficha de documento previo
                );
                $aiResponse = $this->adjustResponseLength($aiResponse);
            } else {
                $aiResponse = "Estos son los elementos publicados que coinciden:\n\n" . $listaTexto
                    . "\n\n¿Quieres que te detalle alguno?";
            }
        } catch (\Exception $e) {
            \Log::warning('Catálogo: fallo IA, lista cruda: ' . $e->getMessage());
            $aiResponse = "Estos son los elementos publicados que coinciden:\n\n" . $listaTexto
                . "\n\n¿Quieres que te detalle alguno?";
        }

        $analyticsId = $this->logAnalytics(
            $originalQuery,
            $aiResponse,
            'catalog_browse',
            $startTime,
            $userId,
            $sessionId
        );

        return [
            'response' => $aiResponse,
            'method' => 'catalog_browse',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [
                'catalog_terms' => $topicTerms,
                'documents_found' => $elementos->count(),
            ],
            'cached' => false,
            'document' => null,
            'analytics_id' => $analyticsId,
            // Sin final_context: no reanclar la conversación a un solo documento.
        ];
    }

    private function shouldAskClarification(string $query, $cachedContext): bool
    {
        $normalized = mb_strtolower(trim($query));
        if ($normalized === '') {
            return true;
        }

        // Con documento en foco, los seguimientos cortos son naturales: no interrumpir.
        if ($cachedContext && !empty($cachedContext['id'])) {
            return false;
        }

        // Señales de sección / intención concreta: dejar pasar aunque sea corta.
        if (preg_match('/\b(objetivo|alcance|responsable|responsables|definicion|definiciones|riesgo|riesgos|lista|listado|pasos|actividades|encargado)\b/u', $normalized)) {
            return false;
        }

        if ($this->mentionsSpecificDocumentSignal($normalized)) {
            return false;
        }

        $wordCount = str_word_count($normalized);

        // Solo aclarar consultas realmente genéricas sin foco (1-2 palabras tipo "procedimientos").
        if (
            $wordCount <= 2 &&
            preg_match('/\b(procedimiento|procedimientos|lineamiento|lineamientos|manual|manuales|documento|documentos|proceso|procesos)\b/u', $normalized)
        ) {
            return true;
        }

        // Frases abiertas sin ningún detalle concreto.
        if (
            preg_match('/^(quiero|necesito|busco|dame|sobre)\b/u', $normalized) &&
            $wordCount <= 3 &&
            !preg_match('/\b(objetivo|alcance|responsable|riesgo|lista|listado|definicion)\b/u', $normalized)
        ) {
            return true;
        }

        return false;
    }

    private function mentionsSpecificDocumentSignal(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return
            preg_match('/\b([a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q) || // folios mixtos
            preg_match('/\b(v\d+(\.\d+)?)\b/u', $q) ||                  // versión v1, v2.0
            preg_match('/\b\d{3,}\b/u', $q) ||                          // números largos
            preg_match('/"[^"]{3,}"/u', $query) ||                      // texto entre comillas
            str_word_count($q) >= 6 ||                                  // suficiente detalle libre
            $this->matchesKnownDocumentName($q);                        // nombra un documento real
    }

    /**
     * Si la consulta contiene una palabra del nombre de un documento existente, no es ambigua.
     * Sin esto el bot pide el nombre del documento y luego ignora el nombre que le dan.
     */
    private function matchesKnownDocumentName(string $normalizedQuery): bool
    {
        // Palabras genéricas: aparecen en muchos nombres y no identifican nada.
        $genericas = ['procedimiento', 'procedimientos', 'lineamiento', 'lineamientos', 'manual', 'manuales',
            'documento', 'documentos', 'proceso', 'procesos', 'politica', 'politicas', 'reglamento', 'reglamentos'];

        $palabras = array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn($w) => mb_strlen($w) >= 5 && !in_array($w, $genericas, true)
        );

        if (empty($palabras)) {
            return false;
        }

        return Cache::remember(
            'chat_known_name_' . md5(implode('|', $palabras)),
            300,
            function () use ($palabras) {
                return Elemento::where('status', 'Publicado')
                    ->where('active', true)
                    ->whereHas('tipoElemento', fn($q) => $q->whereIn('nombre', self::ELEMENTO_TIPOS_BUSCABLES))
                    ->where(function ($q) use ($palabras) {
                        foreach ($palabras as $palabra) {
                            $q->orWhereRaw('LOWER(nombre_elemento) LIKE ?', ['%' . $palabra . '%']);
                        }
                    })
                    ->exists();
            }
        );
    }

    private function buildClarificationQuestion(string $query): string
    {
        $normalized = mb_strtolower(trim($query));

        if (preg_match('/\b(responsable|quien)\b/u', $normalized)) {
            return "Para darte el responsable correcto necesito ubicar el documento. Dime:\n\n- **Nombre** del procedimiento\n- **Tema** o de qué trata\n\nO su **folio** si lo tienes.";
        }

        if (preg_match('/\b(procedimiento|lineamiento|manual|documento)\b/u', $normalized)) {
            return "Con gusto. Para encontrarlo rápido, compárteme:\n\n- **Nombre** del documento o procedimiento\n- **Tema** o de qué trata\n\nO su **folio** si lo conoces.";
        }

        return "Te ayudo con gusto. Para darte una respuesta precisa, dime:\n\n- **Nombre** del documento\n- **Tema** o de qué trata\n\nO su **folio**.";
    }

    /**
     * Detecta si la consulta ya no tiene que ver con el documento en caché (cambio de tema)
     */
    private function isContextMismatch(string $query, ?array $cachedContext): bool
    {
        if (!$cachedContext || empty($cachedContext['title'])) {
            return false;
        }

        $q = mb_strtolower(trim($query));

        // Si la pregunta es claramente de seguimiento sobre el doc actual, no es cambio de tema
        if (preg_match('/^(y|e|o|pero|entonces|ademas|tambien|cuales|sus|su|el|la|que|cual|como|donde|del documento|de este|de ese)\b/i', $q)) {
            return false;
        }
        if (preg_match('/\b(objetivo|alcance|responsable|riesgos|indicadores)\b/i', $q)) {
            return false;
        }

        $title = mb_strtolower($cachedContext['title']);
        $titleWords = $this->extractSimpleKeywords($title);
        $queryWords = $this->extractSimpleKeywords($query);

        if (empty($queryWords)) {
            return false;
        }

        $overlap = 0;
        foreach ($queryWords as $qw) {
            if (str_contains($title, $qw)) {
                $overlap++;
            }
        }

        return $overlap < 1;
    }

    /**
     * Mensaje cuando se cambió de tema y NO se encontró nada.
     */
    private function buildNewTopicPreamble(): string
    {
        return "Ese tema no aparece en los documentos que tengo. Probemos reformulando la pregunta o buscando otro documento.";
    }

    /**
     * Nota corta al cambiar de documento, cuando SÍ hubo resultados. Sólo avisa de qué
     * documento se está hablando ahora; la respuesta real va después.
     */
    private function buildTopicSwitchNote(array $responseArray): string
    {
        $titulo = $responseArray['final_context']['title'] ?? null;

        if (!$titulo) {
            return '';
        }

        return "Cambiando a **{$titulo}**:\n\n";
    }

    /**
     * ¿La IA respondió que el documento no contiene la información? Se detecta por el
     * marcador [[SIN_INFO]] que se le pide en las instrucciones.
     */
    private function responseSaysNoInfo(string $response): bool
    {
        return (bool) preg_match('/\[\[\s*SIN[_\s]?INFO\s*\]\]/i', $response);
    }

    /**
     * Quita el marcador [[SIN_INFO]] del texto.
     */
    private function stripNoInfoMarker(string $response): string
    {
        return trim(preg_replace('/\[\[\s*SIN[_\s]?INFO\s*\]\]/i', '', $response));
    }

    /**
     * ¿La respuesta trae contenido real además del marcador? Evita descartar una respuesta
     * buena sólo porque el modelo pegó el marcador de más.
     */
    private function markerHasRealContent(string $response): bool
    {
        return $this->countWords($this->stripNoInfoMarker($response)) >= 25;
    }

    /**
     * Cierre de contexto: dice en qué documento se buscó, que no estaba, y que se
     * reinició la conversación para empezar de cero.
     */
    private function buildNotFoundInElementoMessage(string $query, ?string $titulo): string
    {
        $consulta = trim($query);
        $consulta = mb_strlen($consulta) > 120 ? mb_substr($consulta, 0, 120) . '…' : $consulta;

        $mensaje = $titulo
            ? "No encontré **{$consulta}** en **{$titulo}**, que es el documento sobre el que veníamos hablando.\n\n"
            : "No encontré **{$consulta}** en el documento sobre el que veníamos hablando.\n\n";

        return $mensaje
            . "Borré el contexto de la conversación para empezar de cero.\n\n"
            . "Dime el **nombre** o **folio** del documento que quieres consultar, o reformula la pregunta.";
    }

    /**
     * Mensaje amigable cuando no hay resultados en documentos publicados (tras cambio de tema)
     */
    private function buildNoResultsFriendlyMessage($query = null, $intent = null): string
    {
        return "No encontré información sobre eso en los documentos disponibles.\n\n"
            . "Intenta con el **nombre** o **folio** del documento, o reformula con otros términos.";
    }

    private function searchWordDocuments(string $query)
    {
        // Buscamos Chunks
        return DocumentChunk::with('wordDocument.elemento')
            ->where('content', 'LIKE', '%' . $query . '%')
            ->orderByDesc('char_count')
            ->limit(8)
            ->get();
    }

    /**
     * Recuperación por keyword en UNA sola pasada a la BD.
     *
     * Antes esto eran dos consultas (frase completa + una condición por palabra sobre 50
     * candidatos que se re-puntuaban en PHP). Ahora se puntúa en SQL: la frase completa
     * vale más que las palabras sueltas, y el título vale más que el cuerpo. Devuelve ya
     * ordenado, sin traer candidatos de más.
     */
    private function searchChunksByKeyword(string $query, int $limit = 8)
    {
        $phrase = trim($query);

        $cleanQuery = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        $words = array_values(array_unique(array_filter(
            explode(' ', $cleanQuery),
            fn($w) => mb_strlen($w) > 3
        )));

        if ($phrase === '' && empty($words)) {
            return collect();
        }

        $scoreParts = [];
        $bindings = [];

        if ($phrase !== '') {
            // Frase exacta: la señal más fuerte.
            $scoreParts[] = '(CASE WHEN content LIKE ? THEN 8 ELSE 0 END)';
            $bindings[] = '%' . $phrase . '%';
            $scoreParts[] = '(CASE WHEN section_title LIKE ? THEN 12 ELSE 0 END)';
            $bindings[] = '%' . $phrase . '%';
        }

        foreach ($words as $word) {
            $scoreParts[] = '(CASE WHEN content LIKE ? THEN 1 ELSE 0 END)';
            $bindings[] = '%' . $word . '%';
            $scoreParts[] = '(CASE WHEN section_title LIKE ? THEN 3 ELSE 0 END)';
            $bindings[] = '%' . $word . '%';
        }

        $scoreSql = implode(' + ', $scoreParts);

        return \App\Models\DocumentChunk::query()
            ->with(['wordDocument', 'wordDocument.elemento'])
            ->selectRaw("document_chunks.*, ({$scoreSql}) as keyword_score", $bindings)
            ->havingRaw('keyword_score > 0')
            ->orderByDesc('keyword_score')
            ->orderByDesc('char_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Realizar búsqueda integrada en todos los modelos.
     * OPTIMIZADA: Clasifica correctamente los resultados para evitar errores de ID nulos.
     */
    private function performIntegratedSearch(string $query): array
    {
        $results = [
            'elementos' => collect(),
            'word_documents' => collect(), // Aquí irían docs enteros si los usaras
            'document_chunks' => collect(), // Aquí van los pedacitos de texto (Chunks)
            'has_results' => false,
            'search_details' => ['elementos_found' => 0, 'documents_found' => 0, 'total_sources' => 0]
        ];

        // 1. Elementos (Búsqueda por título - Lo más importante)
        $elementosRaw = $this->searchElementos($query);
        
        // 🎯 FILTRAR POR RELEVANCIA MÍNIMA
        $results['elementos'] = $elementosRaw->filter(function ($elemento) {
            return isset($elemento->relevance_score) && $elemento->relevance_score >= self::ELEMENTO_MIN_RELEVANCE_SCORE;
        });

        // 2. Chunks por keyword: UNA sola consulta puntuada en SQL (antes eran dos pasadas
        // y un re-scoring en PHP sobre 50 candidatos).
        $keywordChunks = $this->searchChunksByKeyword($query, 6);
        $results['document_chunks'] = $results['document_chunks']
            ->merge($keywordChunks)
            ->unique('id')
            ->values();

        // 4. DOCUMENTOS NOMBRADOS (directo en BD, independiente de chunks).
        // Si el usuario nombra un documento por nombre o folio, lo inyectamos aunque tenga
        // pocos o ningún chunk indexado. Esto arregla el "existe pero no lo encuentra".
        $namedElementos = $this->findNamedElementos($query);
        if ($namedElementos->isNotEmpty()) {
            $existingIds = $results['elementos']->map(fn($e) => $e->getKey())->all();
            foreach ($namedElementos as $named) {
                if (!in_array($named->getKey(), $existingIds, true)) {
                    // relevance_score placeholder; la fusión y el pin por nombre lo reordenan.
                    if (($named->relevance_score ?? 0) <= 0) {
                        $named->relevance_score = self::ELEMENTO_MIN_RELEVANCE_SCORE;
                    }
                    $results['elementos'] = $results['elementos']->push($named);
                }
            }
        }

        // 5. BÚSQUEDA SEMÁNTICA (Embeddings): significado, no palabras exactas.
        $semanticChunks = $this->performSemanticSearch($query);
        if ($semanticChunks->isNotEmpty()) {
            // Sumamos los chunks semánticos al contexto (sin duplicar por id).
            $results['document_chunks'] = $results['document_chunks']
                ->merge($semanticChunks)
                ->unique('id')
                ->values();
        }

        // 6. FUSIÓN: siempre (aunque no haya chunks semánticos) para aplicar el pin por
        // nombre a los documentos nombrados y ordenar por score híbrido.
        $results['elementos'] = $this->fuseElementoRankings(
            $results['elementos'],
            $semanticChunks,
            $query
        );

        // Calcular totales reales
        $results['has_results'] =
            $results['elementos']->isNotEmpty() ||
            $results['document_chunks']->isNotEmpty();

        $results['search_details'] = [
            'elementos_found' => $results['elementos']->count(),
            'documents_found' => 0,
            'total_sources' => $results['elementos']->count() + $results['document_chunks']->count()
        ];

        return $results;
    }

    /**
     * Búsqueda semántica por embeddings: recupera los chunks cuyo SIGNIFICADO se parece
     * a la pregunta (coseno), no los que comparten palabras exactas. Cada chunk devuelto
     * lleva $chunk->semantic_score (0-1) y su relación wordDocument.elemento cargada.
     *
     * Sólo devuelve chunks de elementos visibles (publicados/activos/tipo consultable/puesto),
     * reutilizando buildElementoBaseQuery() para respetar la misma visibilidad que el keyword.
     * Si no hay embeddings o la API falla, devuelve colección vacía (cae al keyword).
     */
    private function performSemanticSearch(string $query, int $topK = 8)
    {
        $qVec = $this->embeddingService->embed($query);
        if ($qVec === null) {
            return collect();
        }

        // Se puntúa contra la matriz de vectores (id => vector), no contra los modelos
        // completos: antes cada pregunta traía de MySQL el content de todos los chunks
        // más sus relaciones sólo para calcular un coseno y descartar casi todo.
        $matrix = $this->embeddingMatrix();
        if (empty($matrix)) {
            return collect();
        }

        $scores = [];
        foreach ($matrix as $chunkId => $vector) {
            $score = $this->embeddingService->cosine($qVec, $vector);
            if ($score >= 0.20) {
                $scores[$chunkId] = $score;
            }
        }

        if (empty($scores)) {
            return collect();
        }

        arsort($scores);
        $topIds = array_slice(array_keys($scores), 0, $topK, true);

        // Sólo ahora se hidratan los modelos: topK filas en vez de la tabla entera.
        $scored = \App\Models\DocumentChunk::with(['wordDocument', 'wordDocument.elemento'])
            ->whereIn('id', $topIds)
            ->get()
            ->map(function ($chunk) use ($scores) {
                $chunk->semantic_score = $scores[$chunk->id] ?? 0.0;
                return $chunk;
            })
            ->sortByDesc('semantic_score')
            ->values();

        if ($scored->isEmpty()) {
            return collect();
        }

        // Enforce visibilidad: sólo elementos que pasan buildElementoBaseQuery.
        $elementoIds = $scored
            ->map(fn($c) => optional(optional($c->wordDocument)->elemento)->getKey())
            ->filter()
            ->unique()
            ->values();

        if ($elementoIds->isEmpty()) {
            return collect();
        }

        $visibles = $this->buildElementoBaseQuery()
            ->whereIn('id_elemento', $elementoIds->all())
            ->pluck('id_elemento')
            ->flip();

        return $scored
            ->filter(function ($chunk) use ($visibles) {
                $elemId = optional(optional($chunk->wordDocument)->elemento)->getKey();
                return $elemId !== null && $visibles->has($elemId);
            })
            ->values();
    }

    /**
     * Matriz de embeddings [chunk_id => vector], cacheada.
     *
     * La clave incluye el conteo y el updated_at más reciente, así que cualquier chunk
     * nuevo, borrado o re-embebido invalida el caché solo. Los vectores se guardan
     * empaquetados (float32) porque el JSON de 1536 floats por chunk pesa ~6x más y
     * decodificarlo en cada pregunta era el costo dominante de la búsqueda.
     */
    private function embeddingMatrix(): array
    {
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }

        $stamp = \App\Models\DocumentChunk::whereNotNull('embedding')
            ->selectRaw('COUNT(*) as total, MAX(updated_at) as last_update')
            ->first();

        if (!$stamp || (int) $stamp->total === 0) {
            return $memo = [];
        }

        $cacheKey = 'chunk_embeddings_v1_' . $stamp->total . '_' . md5((string) $stamp->last_update);

        $packed = Cache::remember($cacheKey, 86400, function () {
            $out = [];
            \App\Models\DocumentChunk::whereNotNull('embedding')
                ->select(['id', 'embedding'])
                ->chunkById(200, function ($rows) use (&$out) {
                    foreach ($rows as $row) {
                        $vector = $row->embedding; // cast 'array'
                        if (is_array($vector) && !empty($vector)) {
                            $out[$row->id] = pack('f*', ...$vector);
                        }
                    }
                });
            return $out;
        });

        $memo = [];
        foreach ($packed as $id => $blob) {
            $memo[$id] = array_values(unpack('f*', $blob));
        }

        return $memo;
    }

    /**
     * Fusión híbrida del ranking de elementos: combina el score por keyword
     * (relevance_score) con el mejor coseno semántico de cada elemento. Normaliza ambas
     * señales a 0-1 y las promedia con peso; devuelve la colección de elementos ordenada
     * por el score fusionado. Incluye elementos que sólo aparecieron por semántica.
     */
    private function fuseElementoRankings($keywordElementos, $semanticChunks, ?string $query = null)
    {
        $W_KEYWORD = 0.5;
        $W_SEMANTIC = 0.5;

        // Mejor coseno por elemento (un elemento tiene varios chunks).
        $semScoreByElem = [];
        $semElementos = [];
        foreach ($semanticChunks as $chunk) {
            $elem = optional($chunk->wordDocument)->elemento;
            if (!$elem) {
                continue;
            }
            $id = $elem->getKey();
            $score = $chunk->semantic_score ?? 0.0;
            if (!isset($semScoreByElem[$id]) || $score > $semScoreByElem[$id]) {
                $semScoreByElem[$id] = $score;
            }
            $semElementos[$id] = $elem;
        }

        // Normalizador del keyword: el mayor relevance_score presente (guard div/0).
        $maxKeyword = max(
            1.0,
            (float) $keywordElementos->max('relevance_score')
        );

        // Unir universo de elementos (keyword + semántico) por id.
        $universe = [];
        foreach ($keywordElementos as $elem) {
            $universe[$elem->getKey()] = $elem;
        }
        foreach ($semElementos as $id => $elem) {
            if (!isset($universe[$id])) {
                $universe[$id] = $elem;
            }
        }

        // ¿La pregunta NOMBRA explícitamente un documento? (por nombre o folio). Si el usuario
        // dice "en el documento de prospectar", ese doc debe ganar aunque otro tenga mejor
        // score semántico por compartir vocabulario común (director, desarrollo, negocios...).
        $q = (string) ($query ?? '');

        $fused = collect($universe)->map(function ($elem) use ($semScoreByElem, $maxKeyword, $W_KEYWORD, $W_SEMANTIC, $q) {
            $id = $elem->getKey();
            $kwNorm = min(1.0, ((float) ($elem->relevance_score ?? 0)) / $maxKeyword);
            $semNorm = $semScoreByElem[$id] ?? 0.0;

            $elem->semantic_score = $semNorm;
            $elem->fused_score = $W_KEYWORD * $kwNorm + $W_SEMANTIC * $semNorm;

            // Pin por mención explícita: los documentos NOMBRADOS viven en una banda propia
            // (>=10) por encima de cualquier score híbrido normal (<=1). Dentro de esa banda
            // ordenan por fuerza del match, para que la versión más específica (p.ej. "... VT"
            // o un folio exacto) gane el desempate; el score base rompe empates de igual fuerza.
            $strength = $this->namedMatchStrength($q, $elem);
            if ($strength > 0) {
                $elem->fused_score = 10.0 + $strength + $elem->fused_score;
                $elem->named_match = true;
                $elem->named_strength = $strength;
            } else {
                // Solape parcial de título: "contratacion de personal" empuja al documento que
                // TRATA de eso por encima del que sólo lo menciona en un apartado. No llega a la
                // banda del pin: sigue siendo una señal más dentro del score híbrido.
                $overlap = $this->titleOverlapRatio($q, $elem);
                $elem->title_overlap = $overlap;
                $elem->fused_score += self::W_TITLE_OVERLAP * $overlap;
            }

            // Mantener relevance_score utilizable aguas abajo aunque el elemento venga
            // sólo por semántica (los filtros posteriores esperan relevance_score).
            if (($elem->relevance_score ?? 0) <= 0 && $semNorm > 0) {
                $elem->relevance_score = (int) round($semNorm * 100);
            }

            return $elem;
        });

        return $fused
            ->sortByDesc('fused_score')
            ->values();
    }

    /**
     * ¿La pregunta nombra explícitamente este elemento? (bool de conveniencia).
     */
    private function queryNamesElemento(string $query, $elemento): bool
    {
        return $this->namedMatchStrength($query, $elemento) > 0;
    }

    /**
     * Fuerza con la que la pregunta NOMBRA a este elemento (0 = no lo nombra).
     * Mayor = match más específico, para desempatar nombres casi iguales (p.ej. la versión
     * "... Proyecto VT" vs "... Proyecto"). Sin listas de temas: compara contra el nombre y
     * folio reales del elemento.
     */
    private function namedMatchStrength(string $query, $elemento): int
    {
        if ($query === '' || !$elemento) {
            return 0;
        }

        $q = ' ' . $this->stripAccentsLower($query) . ' ';
        $strength = 0;

        // 1) Folio exacto mencionado (PE01-PR02, PC04-PR08-VT, GC2134, etc.). Señal fortísima.
        $folio = trim((string) ($elemento->folio_elemento ?? ''));
        if ($folio !== '' && mb_strpos($q, $this->stripAccentsLower($folio)) !== false) {
            $strength += 100;
        }

        // 2) Nombre: palabras distintivas del título presentes en la pregunta.
        $genericas = ['procedimiento', 'procedimientos', 'politica', 'politicas', 'lineamiento',
            'lineamientos', 'manual', 'manuales', 'documento', 'documentos', 'proceso', 'procesos',
            'reglamento', 'reglamentos', 'formato', 'formatos'];

        $titulo = $this->stripAccentsLower((string) ($elemento->nombre_elemento ?? ''));
        // Palabras distintivas (>=4 chars, no genéricas) para el umbral de "nombrado".
        $titleWords = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $titulo, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn($w) => mb_strlen($w) >= 4 && !in_array($w, $genericas, true)
        ));

        if (!empty($titleWords)) {
            $queryWords = preg_split('/[^\p{L}\p{N}]+/u', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $matched = 0;
            $exactos = 0;
            foreach ($titleWords as $w) {
                if (mb_strpos($q, $w) !== false) {
                    $matched++;
                    $exactos++;
                } elseif ($this->matchesByStem($w, $queryWords)) {
                    // "realizo" contra el título "Realizar ...": misma raíz, distinta conjugación.
                    $matched++;
                }
            }

            // Se considera "nombrado" si aparece la mayoría (>=75%) de las palabras distintivas.
            if (($matched / count($titleWords)) >= 0.75) {
                // El match por raíz pesa menos que el literal, para no empatar con un título
                // que la pregunta sí escribe tal cual.
                $strength += $exactos * 10 + ($matched - $exactos) * 7;

                // Bonus por tokens cortos del título también presentes (ej. "vt", "01"),
                // para que la versión más específica gane el desempate.
                $allTokens = array_values(array_filter(
                    preg_split('/[^\p{L}\p{N}]+/u', $titulo, -1, PREG_SPLIT_NO_EMPTY) ?: [],
                    fn($w) => mb_strlen($w) < 4 && !in_array($w, $genericas, true)
                ));
                foreach ($allTokens as $w) {
                    if (mb_strpos($q, ' ' . $w . ' ') !== false) {
                        $strength += 5;
                    }
                }
            }
        }

        return $strength;
    }

    /**
     * ¿Alguna palabra de la pregunta comparte raíz con esta palabra del título?
     * Comparación por prefijo común: cubre conjugaciones y plurales ("realizo"/"realizar",
     * "contrataciones"/"contratacion") sin necesitar un stemmer completo.
     */
    private function matchesByStem(string $palabra, array $queryWords): bool
    {
        $minRaiz = 5;

        if (mb_strlen($palabra) < $minRaiz) {
            return false;
        }

        foreach ($queryWords as $qw) {
            if (mb_strlen($qw) < $minRaiz) {
                continue;
            }

            $comun = 0;
            $max = min(mb_strlen($palabra), mb_strlen($qw));
            for ($i = 0; $i < $max; $i++) {
                if (mb_substr($palabra, $i, 1) !== mb_substr($qw, $i, 1)) {
                    break;
                }
                $comun++;
            }

            if ($comun >= $minRaiz) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fracción (0-1) de palabras distintivas del título que aparecen en la pregunta, contando
     * también coincidencias por raíz. Señal suave: no fija el documento como el pin por
     * nombre, sólo lo empuja frente a otros que comparten vocabulario del tema.
     */
    private function titleOverlapRatio(string $query, $elemento): float
    {
        if ($query === '' || !$elemento) {
            return 0.0;
        }

        $genericas = ['procedimiento', 'procedimientos', 'politica', 'politicas', 'lineamiento',
            'lineamientos', 'manual', 'manuales', 'documento', 'documentos', 'proceso', 'procesos',
            'reglamento', 'reglamentos', 'formato', 'formatos'];

        $q = ' ' . $this->stripAccentsLower($query) . ' ';
        $queryWords = preg_split('/[^\p{L}\p{N}]+/u', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $titulo = $this->stripAccentsLower((string) ($elemento->nombre_elemento ?? ''));
        $titleWords = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $titulo, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn($w) => mb_strlen($w) >= 4 && !in_array($w, $genericas, true)
        ));

        if (empty($titleWords)) {
            return 0.0;
        }

        $matched = 0;
        foreach ($titleWords as $w) {
            if (mb_strpos($q, $w) !== false || $this->matchesByStem($w, $queryWords)) {
                $matched++;
            }
        }

        return $matched / count($titleWords);
    }

    /**
     * Busca en la BD los elementos cuyo NOMBRE o FOLIO nombra la pregunta, independiente de
     * los chunks. Garantiza que un documento nombrado se encuentre aunque tenga pocos o
     * ningún chunk indexado (el punto que fallaba: la búsqueda dependía de los chunks).
     */
    private function findNamedElementos(string $query, int $limit = 3)
    {
        $candidates = $this->buildElementoBaseQuery()
            ->limit(self::ELEMENTO_CANDIDATE_LIMIT)
            ->get();

        return $candidates
            ->map(function ($e) use ($query) {
                $e->named_strength = $this->namedMatchStrength($query, $e);

                // Sin llegar al pin, un título que solapa fuerte con la pregunta también entra
                // al ranking: si no, el documento correcto ni siquiera se puede puntuar.
                if ($e->named_strength <= 0 && $this->titleOverlapRatio($query, $e) >= 0.6) {
                    $e->named_strength = 1;
                    $e->title_candidate = true;
                }

                return $e;
            })
            ->filter(fn($e) => $e->named_strength > 0)
            ->sortByDesc('named_strength')
            ->take($limit)
            ->values();
    }

    /**
     * Minúsculas + sin acentos, para comparar nombres/folios sin depender de tildes.
     */
    private function stripAccentsLower(string $text): string
    {
        $text = mb_strtolower(trim($text));
        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    /**
     * Mejor similitud coseno entre la pregunta (ya vectorizada) y los chunks de un elemento.
     * Sirve para decidir si una pregunta de seguimiento sigue hablando del doc cacheado.
     */
    private function bestChunkSimilarityForElemento(array $qVec, $elementoId): float
    {
        $chunks = \App\Models\DocumentChunk::whereNotNull('embedding')
            ->whereHas('wordDocument', function ($q) use ($elementoId) {
                $q->where('elemento_id', $elementoId);
            })
            ->get(['id', 'embedding']);

        $best = 0.0;
        foreach ($chunks as $chunk) {
            $sim = $this->embeddingService->cosine($qVec, $chunk->embedding);
            if ($sim > $best) {
                $best = $sim;
            }
        }
        return $best;
    }

    /**
     * Buscar en el modelo Elemento con razonamiento semántico
     * Método principal centralizado para todas las búsquedas de Elemento
     */
    private function searchInElementos($query)
    {
        try {
            // Preparar datos de búsqueda
            $searchData = $this->prepareElementoSearchData($query);

            // 🚨 VALIDACIÓN CRÍTICA: Si no hay términos de búsqueda válidos, devolver vacío
            $hasValidSearchTerms = 
                !empty($searchData['keywords']) ||
                !empty($searchData['expanded_keywords']) ||
                !empty($searchData['semantic_keywords']) ||
                !empty($searchData['folio_patterns']) ||
                !empty($searchData['normalized_query']);

            if (!$hasValidSearchTerms) {
                return collect();
            }

            // Construir query base de Elemento (🔥 CARGA wordDocument 🔥)
            $elementQuery = $this->buildElementoBaseQuery()
                ->with('wordDocument');

            // Aplicar condiciones de búsqueda
            $elementQuery = $this->applyElementoSearchConditions($elementQuery, $searchData);

            // Traemos todos los candidatos del WHERE para puntuarlos. El límite pequeño iba aquí
            // y, como la query no venía ordenada, cortaba al target antes de calcular relevancia
            // (los folios recién creados quedaban fuera del corte). El recorte final va por score.
            $elementosRaw = $elementQuery
                ->limit(self::ELEMENTO_CANDIDATE_LIMIT)
                ->get();

            $elementosConScore = $elementosRaw->map(function ($elemento) use ($query, $searchData) {
                $elemento->relevance_score = $this->calculateSemanticRelevance(
                    $elemento,
                    $query,
                    $searchData['intent']
                );
                return $elemento;
            });

            // Filtrar por relevancia mínima. En empate (mismo folio/nombre en varias versiones o
            // tipos) desempatamos por versión más alta y luego por el más reciente, para que el
            // resultado sea estable y no dependa del orden que devuelva la BD.
            $elementos = $elementosConScore
                ->filter(function ($elemento) {
                    return $elemento->relevance_score >= self::ELEMENTO_MIN_RELEVANCE_SCORE;
                })
                ->sort(function ($a, $b) {
                    return [$b->relevance_score, (float) $b->version_elemento, (string) $b->created_at]
                        <=> [$a->relevance_score, (float) $a->version_elemento, (string) $a->created_at];
                })
                ->values();

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
        ])->where('status', 'Publicado')
            ->where('active', true)
            ->whereHas('tipoElemento', function ($q) {
                $q->whereIn('nombre', self::ELEMENTO_TIPOS_BUSCABLES);
            });

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
        // 🚨 PROTECCIÓN: Solo aplicar búsquedas si hay términos válidos
        $hasValidTerms = 
            !empty($searchData['keywords']) ||
            !empty($searchData['expanded_keywords']) ||
            !empty($searchData['folio_patterns']) ||
            !empty($searchData['normalized_query']);

        if (!$hasValidTerms) {
            Log::warning('⚠️ applyElementoSearchConditions: No hay términos válidos, agregando condición imposible');
            // Agregar condición que nunca se cumple para evitar devolver todos los elementos
            return $elementQuery->whereRaw('1 = 0');
        }

        return $elementQuery->where(function ($searchQuery) use ($searchData) {
            $conditionsApplied = false;

            // Búsqueda en campos directos del Elemento
            if (!empty($searchData['keywords']) || !empty($searchData['expanded_keywords']) || !empty($searchData['folio_patterns'])) {
                $searchQuery->where(function ($elementConditions) use ($searchData) {
                    $this->applyElementoDirectSearch($elementConditions, $searchData);
                });
                $conditionsApplied = true;
            }

            // Búsqueda en documentos Word relacionados
            if (!empty($searchData['keywords']) || !empty($searchData['expanded_keywords'])) {
                $searchQuery->orWhereHas('wordDocument', function ($query) use ($searchData) {
                    $this->applyElementoWordDocumentSearch($query, $searchData);
                });
                $conditionsApplied = true;
            }

            // Búsqueda en relaciones: tipoElemento
            if (!empty($searchData['expanded_keywords'])) {
                $searchQuery->orWhereHas('tipoElemento', function ($query) use ($searchData) {
                    $this->applyElementoRelationSearch($query, $searchData);
                });
                $conditionsApplied = true;
            }

            // Búsqueda en relaciones: tipoProceso
            if (!empty($searchData['expanded_keywords'])) {
                $searchQuery->orWhereHas('tipoProceso', function ($query) use ($searchData) {
                    $this->applyElementoRelationSearch($query, $searchData);
                });
                $conditionsApplied = true;
            }

            // Búsqueda en relaciones: unidadNegocio
            if (!empty($searchData['expanded_keywords'])) {
                $searchQuery->orWhereHas('unidadNegocio', function ($query) use ($searchData) {
                    $this->applyElementoUnidadNegocioSearch($query, $searchData);
                });
                $conditionsApplied = true;
            }

            // Si después de todo no se aplicó ninguna condición, agregar condición imposible
            if (!$conditionsApplied) {
                $searchQuery->whereRaw('1 = 0');
            }
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
     * Extrae y prepara texto del documento para IA
     * VERSIÓN PRODUCCIÓN FINAL
     * - Delimitación fuerte por secciones (Incluye Objetivo y Alcance)
     * - Límite estricto de caracteres
     * - Corrección automática de errores ortográficos en búsqueda
     */
    private function getElementoTextForAIDescription($elemento, ?string $query = null): ?string
    {
        if (!$elemento->wordDocument) return null;

        $wordDoc = $elemento->wordDocument;
        $rawContent = $wordDoc->contenido_texto ?: $wordDoc->contenido_estructurado;

        if (empty($rawContent)) return null;

        $json = json_decode($rawContent, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['parrafos'])) {
            $fullText = implode("\n\n", $json['parrafos']);
        } else {
            $fullText = $rawContent;
        }

        $text = strip_tags($fullText);

        $garbagePatterns = [
            '/^MANUAL DE PROCEDIMIENTOS$/mi',
            '/^Página\s+\d+\s+DE\s+\d+$/mi',
            '/^PC\d+\s+ENTREGAR\s+LA\s+OBRA.*$/mi',
            '/^_{3,}$/m',
        ];
        $text = preg_replace($garbagePatterns, '', $text);
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        $totalLen = mb_strlen($text);

        if ($totalLen <= 15000) {
            return "=== DOCUMENTO OFICIAL ===\nFuente: Procedimiento interno\n\n" . $text . "\n\n=== FIN DOCUMENTO ===";
        }

        $sections = [
            'OBJETIVO'      => '',
            'ALCANCE'       => '',
            'DEFINICIONES'  => '',
            'RESPONSABLE'   => '',
            'RESPONSABLES'  => '',
        ];


        foreach ($sections as $key => $_) {

            if (preg_match("/(?:^|\n)\s*$key\b(.*?)(\n[A-ZÁÉÍÓÚÑ ]{5,}|$)/si", $text, $m)) {
                $sections[$key] = trim($m[0]);
            }
        }

        $headText   = mb_substr($text, 0, 3000);
        $footerText = mb_substr($text, -2000);

        $snippets = [];

        if (!empty($query)) {
            $normalizedQuery = $this->normalizeColloquialQuery($query);

            $words = array_filter(
                explode(' ', $normalizedQuery),
                fn($w) => mb_strlen($w) >= 3
            );

            foreach ($words as $word) {
                if (mb_stripos($text, $word) !== false) {
                    $pos = mb_stripos($text, $word);
                    $start = max(0, $pos - 300);
                    $snippets[] = trim(mb_substr($text, $start, 800));
                    break;
                }
            }
        }

        $final =
            "=== DOCUMENTO OFICIAL ===\n" .
            "Fuente única: Procedimiento seleccionado\n\n" .

            "=== CONTEXTO GENERAL ===\n" .
            $headText . "\n\n" .

            (!empty($sections['OBJETIVO']) ? "=== SECCIÓN: OBJETIVO ===\n{$sections['OBJETIVO']}\n\n" : "") .
            (!empty($sections['ALCANCE']) ? "=== SECCIÓN: ALCANCE ===\n{$sections['ALCANCE']}\n\n" : "") .
            (!empty($sections['DEFINICIONES']) ? "=== SECCIÓN: DEFINICIONES ===\n{$sections['DEFINICIONES']}\n\n" : "") .
            (!empty($sections['RESPONSABLE']) ? "=== SECCIÓN: RESPONSABLE ===\n{$sections['RESPONSABLE']}\n\n" : "") .
            (!empty($sections['RESPONSABLES']) ? "=== SECCIÓN: RESPONSABLES ===\n{$sections['RESPONSABLES']}\n\n" : "") .

            (!empty($snippets)
                ? "=== CONTEXTO ESPECÍFICO DE LA PREGUNTA ===\n" . implode("\n---\n", $snippets) . "\n\n"
                : "") .

            "=== CIERRE DEL DOCUMENTO ===\n" .
            $footerText . "\n\n" .

            "=== FIN DOCUMENTO ===";

        return mb_substr($final, 0, 12000);
    }

    /**
     * Calcular relevancia semántica (VERSIÓN FINAL MEJORADA)
     * Funciona para cualquier búsqueda contando palabras dentro del documento.
     */
    private function calculateSemanticRelevance($elemento, $query, $intent)
    {
        $score = 0;
        $normalizedQuery = strtolower(trim($query));
        $folioPatterns = $this->extractFolioPatterns($query);

        // 1. MÁXIMA PRIORIDAD: Folios específicos (ID exacto)
        $folioElemento = strtolower($elemento->folio_elemento ?? '');
        foreach ($folioPatterns as $folio) {
            if (strpos($folioElemento, $folio) !== false) {
                $score += 150; // ¡Bingo! Es el documento exacto.
            }
        }

        // 2. PRIORIDAD ALTA: Folios dentro del texto
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

        // 3. FUERZA BRUTA: Relevancia por Contenido (¡LA SOLUCIÓN!)
        // Esto hace que funcione para TODOS los temas, no solo transistores.
        if (!empty($docContent)) {
            // Limpiamos la query para quitar palabras vacías ("el", "la", "de") Y palabras interrogativas
            $stopWords = [
                'el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'que', 'y', 'en', 'por', 'para', 'con', 'se', 'su', 'sus', 'es', 'son', 'como',
                'quien', 'quienes', 'donde', 'cuando', 'cual', 'cuales', 'cuanto', 'cuantos', 'cuanta', 'cuantas',
                'este', 'esta', 'estos', 'estas', 'ese', 'esa', 'esos', 'esas', 'hay', 'tiene', 'tienes', 'tengo',
                'dime', 'dame', 'muestra', 'busca', 'encuentra', 'necesito', 'quiero', 'puedes', 'puede',
                // Meta-palabras de intención: piden un documento, no son parte del tema. Aparecen en casi
                // todos los textos y empataban documentos irrelevantes con el que de verdad se busca.
                'archivo', 'archivos', 'documento', 'documentos', 'pdf', 'descargar', 'descarga', 'abrir',
                'link', 'enlace', 'ver', 'informacion', 'información', 'sobre', 'acerca',
            ];

            $queryWords = explode(' ', $normalizedQuery);

            // Filtramos: solo palabras de más de 3 letras que no sean stopWords
            $meaningfulWords = array_filter($queryWords, function ($w) use ($stopWords) {
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

        // 4. RAZONAMIENTO SEMÁNTICO (NLP)
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

        // 5. COINCIDENCIAS EN TÍTULO (METADATOS)
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

        // 6. BONUS MENORES

        // 🚫 BONUS REMOVIDO: Ya no damos puntos solo por tener wordDocument
        // Eso causaba que elementos irrelevantes pasaran el filtro con score=10

        // Coincidencias en metadatos secundarios
        if ($elemento->tipoElemento && strpos(strtolower($elemento->tipoElemento->nombre), $normalizedQuery) !== false) $score += 20;
        if ($elemento->tipoProceso && strpos(strtolower($elemento->tipoProceso->nombre), $normalizedQuery) !== false) $score += 15;
        if ($elemento->unidadNegocio && strpos(strtolower($elemento->unidadNegocio->nombre), $normalizedQuery) !== false) $score += 10;

        return $score;
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
     * Construir contexto enriquecido: UNIFICADO
     * Junta Elementos + Documentos + Chunks y los procesa todos igual.
     */
    /**
     * Construir contexto enriquecido: UNIFICADO Y SIN CRASHES
     */
    private function buildEnrichedContext($searchResults, $query = null)
    {
        // 1. CREAR INVENTARIO DE CHUNKS (Para que no se pierdan)
        $chunksMap = [];
        if (isset($searchResults['document_chunks'])) {
            foreach ($searchResults['document_chunks'] as $chunk) {
                // Aseguramos que sea array para estandarizar
                $cData = is_array($chunk) ? $chunk : $chunk->toArray();

                // ID del documento padre
                $docId = $chunk->word_document_id ?? $chunk->wordDocument->id ?? null;

                if ($docId) {
                    if (!isset($chunksMap[$docId])) {
                        $chunksMap[$docId] = [];
                    }
                    $chunksMap[$docId][] = $cData;
                }
            }
        }

        $allDocs = collect();

        // 2. PROCESAR WORD DOCUMENTS (Búsqueda General)
        if (isset($searchResults['word_documents'])) {
            foreach ($searchResults['word_documents'] as $doc) {
                // Si este doc tiene chunks en el inventario, SE LOS PEGAMOS A LA FUERZA
                if (isset($chunksMap[$doc->id])) {
                    $doc->matched_chunks = $chunksMap[$doc->id];
                }
                $allDocs->push($doc);
            }
        }

        // 3. PROCESAR DOCS DE CHUNKS (Búsqueda Vectorial)
        if (isset($searchResults['document_chunks'])) {
            foreach ($searchResults['document_chunks'] as $chunk) {
                if ($chunk->wordDocument) {
                    $doc = $chunk->wordDocument;
                    // Le pegamos TODOS los chunks del mapa, no solo este
                    if (isset($chunksMap[$doc->id])) {
                        $doc->matched_chunks = $chunksMap[$doc->id];
                    }
                    $allDocs->push($doc);
                }
            }
        }

        // 4. PROCESAR ELEMENTOS
        if (isset($searchResults['elementos'])) {
            foreach ($searchResults['elementos'] as $elem) {
                if ($elem->wordDocument) {
                    $doc = $elem->wordDocument;
                    $doc->setRelation('elemento', $elem);
                    if (isset($chunksMap[$doc->id])) {
                        $doc->matched_chunks = $chunksMap[$doc->id];
                    }
                    $allDocs->push($doc);
                }
            }
        }

        // 5. LIMPIAR DUPLICADOS
        // Ahora es seguro usar unique() porque todos tienen los chunks pegados
        $uniqueDocs = $allDocs->unique('id')->values();

        if ($uniqueDocs->isEmpty()) return '';

        return $this->buildWordDocumentContextSection($uniqueDocs, $query);
    }

    /**
     * Chunks de UN documento ordenados por similitud semántica con la pregunta.
     * Devuelve los contenidos (top-N, acotados por caracteres) para alimentar a la IA con
     * las secciones que de verdad responden, aunque el usuario use otras palabras.
     * Vacío si no hay embeddings (la API cae o el doc aún no está indexado) -> se usa el
     * respaldo por keyword.
     */
    private function getRankedChunksForDocument($wordDocumentId, ?string $query, int $topN = 6, int $maxChars = 6000): array
    {
        if (empty($query)) {
            return [];
        }

        $qVec = $this->embeddingService->embed($query);
        if ($qVec === null) {
            return [];
        }

        $chunks = \App\Models\DocumentChunk::where('word_document_id', $wordDocumentId)
            ->whereNotNull('embedding')
            ->get(['id', 'content', 'embedding']);

        if ($chunks->isEmpty()) {
            return [];
        }

        $ranked = $chunks
            ->map(function ($c) use ($qVec) {
                $c->sim = $this->embeddingService->cosine($qVec, $c->embedding);
                return $c;
            })
            ->sortByDesc('sim')
            ->take($topN)
            ->values();

        $out = [];
        $acc = 0;
        foreach ($ranked as $c) {
            $content = trim((string) $c->content);
            if ($content === '') {
                continue;
            }
            $out[] = $content;
            $acc += mb_strlen($content);
            if ($acc >= $maxChars) {
                break;
            }
        }

        return $out;
    }

    /**
     * Construir sección de contexto para Documentos.
     * VERSIÓN FINAL: Prioriza chunks por similitud semántica; respaldo a chunks de la
     * búsqueda y a búsqueda manual por keyword si no hay embeddings.
     */
    private function buildWordDocumentContextSection($documents, $query = '')
    {
        if (!$documents || $documents->isEmpty()) return '';

        $contextParts = [];
        $totalChars = 0;

        // Evita errores de "contexto vacío" sin gastar en exceso.
        $MAX_CHARS = 25000;

        foreach ($documents as $document) {
            // Freno de emergencia si ya llenamos el contexto
            if ($totalChars >= $MAX_CHARS) break;

            $docInfo = [];
            $title = $document->nombre ?? 'Sin Título';
            $id = $document->id;

            $docInfo[] = "=== DOCUMENTO: $title (ID: $id) ===";

            // Generar enlace público al archivo si existe
            if ($document->elemento && !empty($document->elemento->archivo_actual_url)) {
                $docInfo[] = "Link: " . $document->elemento->archivo_actual_url;
            }

            // ESTRATEGIA SEMÁNTICA (PRIORITARIA): los chunks del propio documento ordenados
            // por similitud con la pregunta. Resuelve el caso "está en el doc pero el chat no
            // lo trae": aunque el usuario use otras palabras o la info esté al final del doc,
            // el coseno la encuentra. Funciona también en modo lealtad (seguimiento), donde
            // antes se mandaban ciegamente los primeros 4000 caracteres.
            $semanticChunks = $this->getRankedChunksForDocument($id, $query, 6);

            if (!empty($semanticChunks)) {
                $docInfo[] = "\n[FRAGMENTOS MÁS RELEVANTES (SEMÁNTICO)]:";
                foreach ($semanticChunks as $content) {
                    $docInfo[] = trim($content);
                    $docInfo[] = "---";
                }
            }
            // ESTRATEGIA A: CHUNKS DE LA BÚSQUEDA (si no hubo embeddings del doc)
            elseif (!empty($document->matched_chunks)) {
                $docInfo[] = "\n[FRAGMENTOS RELEVANTES DETECTADOS POR IA]:";

                // Tomamos hasta 10 chunks para tener mucho contexto
                $chunks = collect($document->matched_chunks)->take(10);

                foreach ($chunks as $chunk) {
                    // Manejo seguro: puede venir como objeto (Eloquent) o array
                    $content = is_array($chunk) ? ($chunk['content'] ?? '') : ($chunk->content ?? '');

                    if (!empty($content)) {
                        $docInfo[] = trim($content);
                        $docInfo[] = "---";
                    }
                }
            }
            // ESTRATEGIA B: BÚSQUEDA MANUAL (RESPALDO PHP)
            // Si el documento fue inyectado por "Lealtad" o búsqueda por título,
            // no tiene chunks asociados, así que leemos el contenido completo.
            else {
                // 1. Obtener contenido crudo de la BD
                $rawContent = \Illuminate\Support\Facades\DB::table('word_documents')
                    ->where('id', $id)
                    ->value('contenido_texto');

                // Limpieza y normalización
                $fullContent = trim(strip_tags($rawContent ?? ''));
                $fullContent = preg_replace('/\s+/', ' ', $fullContent);

                // Fallback a contenido estructurado si el texto plano está vacío
                if (empty($fullContent)) {
                    $jsonContent = \Illuminate\Support\Facades\DB::table('word_documents')
                        ->where('id', $id)
                        ->value('contenido_estructurado');
                    if ($jsonContent) $fullContent = trim(strip_tags($jsonContent));
                }

                // Si hay contenido, procedemos a buscar
                if (!empty($fullContent)) {
                    // 2. Preparar palabras clave de la query
                    $cleanString = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower(trim($query)));
                    $words = array_filter(explode(' ', $cleanString), fn($w) => mb_strlen($w) >= 3);

                    $snippets = [];

                    // 3. Buscar coincidencias en el texto
                    if (!empty($words)) {
                        foreach ($words as $w) {
                            $pos = mb_stripos($fullContent, $w);
                            if ($pos !== false) {
                                // Extraemos ventana amplia: 300 antes, 1500 después
                                $start = max(0, $pos - 300);
                                $extract = mb_substr($fullContent, $start, 1500);
                                $snippets[] = "..." . $extract . "...";

                                // Limitamos a 3 snippets manuales para no saturar
                                if (count($snippets) >= 3) break;
                            }
                        }
                    }

                    // 4. Decidir qué mostrar
                    if (!empty($snippets)) {
                        $docInfo[] = "\n[SECCIONES RELACIONADAS (Búsqueda Manual)]:";
                        $docInfo[] = implode("\n---\n", $snippets);
                    } else {
                        // Si no encontró palabras clave, 
                        // enviamos los primeros 4000 caracteres. Mejor que sobre a que falte.
                        $docInfo[] = "\n[RESUMEN INICIAL DEL DOCUMENTO]:";
                        $docInfo[] = mb_substr($fullContent, 0, 4000);
                    }
                }
            }

            $block = implode("\n", $docInfo);

            // Verificar límite de tokens antes de agregar este bloque
            if (($totalChars + mb_strlen($block)) > $MAX_CHARS) {
                break;
            }

            $totalChars += mb_strlen($block);
            $contextParts[] = $block;
        }

        if (empty($contextParts)) return "";

        array_unshift($contextParts, "=== RESULTADOS DE DOCUMENTOS ===");
        return implode("\n\n", $contextParts);
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
        $normalizedQuery = strtolower($query);

        // Folio compuesto (PC00-PR01). Tiene que ir primero y salir con return:
        // \b corta en el guión, así que los patrones sueltos lo partirían en "pc00" y "pr01",
        // y ese "pr01" coincide con todos los demás procedimientos.
        if (preg_match_all('/\b([a-z]{1,5}\d{1,4}(?:-[a-z]{1,5}\d{1,4})+)\b/i', $normalizedQuery, $matches)) {
            return array_values(array_unique(array_map('strtolower', $matches[1])));
        }

        $folios = [];

        // Folio de un solo segmento (GC2134, PC00), con o sin espacio entre letras y números.
        if (preg_match_all('/\b([a-z]{1,5})\s*(\d{2,6})\b/i', $normalizedQuery, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $folios[] = strtolower($match[1] . $match[2]);
            }
        }

        return array_values(array_unique($folios));
    }

    /**
     * Registrar analytics
     */
    private function logAnalytics($query, $response, $method, $startTime, $userId, $sessionId)
    {
        try {
            // Devolvemos el id para asociar el feedback del usuario a esta respuesta.
            return ChatbotAnalytics::create([
                'user_id' => $userId,
                'query' => $query,
                'normalized_query' => strtolower(trim($query)),
                'response_method' => $method,
                'response' => $response,
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'session_id' => $sessionId ?? session()->getId()
            ])->id;
        } catch (\Exception $e) {
            Log::warning('No se pudo guardar analytics: ' . $e->getMessage());
            return null;
        }
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
     * Generar respuesta con IA gestionando prioridades de lealtad absoluta y pureza de contexto.
     * Versión Final: Usa getKey() para seguridad y devuelve final_context.
     */
    private function generateResponseWithFallback($query, $searchResults, $startTime, $userId, $sessionId)
    {
        try {
            if (!$this->usePaidAI) {
                return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
            }

            $healthCheck = $this->paidAIService->healthCheck();
            if ($healthCheck !== 'ok') {
                return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
            }

            // 1. RECUPERAR LA VERDAD DE LA MEMORIA (CACHÉ)
            $contextKey = $this->getContextKey($sessionId, $userId);
            $cachedContext = \Cache::get($contextKey);
            $historyDocId = $cachedContext['id'] ?? null;

            // 2. IDENTIFICAR CANDIDATOS

            // Candidato A: Historial (Memoria)
            $historyDoc = null;
            if ($historyDocId) {
                $historyDoc = \App\Models\Elemento::find($historyDocId);
            }

            // Candidato B: Búsqueda Vectorial (Chunks)
            $chunkDoc = null;
            if ($searchResults['document_chunks']->isNotEmpty()) {
                $bestChunk = $searchResults['document_chunks']->first();
                // Navegamos correctamente la relación Chunk -> Doc -> Elemento
                if ($bestChunk->wordDocument && $bestChunk->wordDocument->elemento) {
                    $chunkDoc = $bestChunk->wordDocument->elemento;
                }
            }

            // Candidato C: Mejor elemento del ranking fusionado (keyword + semántico).
            $titleDoc = $searchResults['elementos']->isNotEmpty() ? $searchResults['elementos']->first() : null;

            // 3. ÁRBITRO DE DECISIÓN
            // La decisión seguimiento vs cambio de tema ya se tomó por similitud semántica en
            // processQuery(): si era seguimiento, $searchResults trae forzado el doc cacheado;
            // si cambió el tema, la caché ya se limpió. Aquí sólo elegimos el mejor candidato.
            $bestElemento = null;
            $razon = 'SIN DEFINIR';

            if ($titleDoc) {
                $bestElemento = $titleDoc;
                $razon = "RANKING_FUSIONADO";
            } elseif ($chunkDoc) {
                $bestElemento = $chunkDoc;
                $razon = "BUSQUEDA_VECTORIAL";
            } elseif ($historyDoc) {
                $bestElemento = $historyDoc;
                $razon = "FALLBACK_HISTORIAL";
            }

            // 4. FILTRO DE PUREZA DE CONTEXTO
            if ($bestElemento) {
                $targetId = $bestElemento->getKey(); // Usamos getKey() por seguridad

                // Limpieza de documentos
                $searchResults['word_documents'] = $searchResults['word_documents']->filter(function ($doc) use ($targetId) {
                    // Verificamos ambas relaciones por si acaso (WordDoc a veces tiene elemento_id, a veces es el objeto mismo)
                    return ($doc->elemento_id == $targetId) || ($doc->id == $targetId);
                });

                // Limpieza de chunks
                $searchResults['document_chunks'] = $searchResults['document_chunks']->filter(function ($chunk) use ($targetId) {
                    return optional($chunk->wordDocument)->elemento_id == $targetId;
                });

                // Inyección forzada si está vacío (porque la búsqueda no trajo nada del doc ganador)
                if ($searchResults['word_documents']->isEmpty()) {
                    $docToInject = \App\Models\WordDocument::where('elemento_id', $targetId)->first();
                    if ($docToInject) {
                        $searchResults['word_documents']->push($docToInject);
                    }
                }
            }

            // 5. CONSTRUIR CONTEXTO FINAL
            $docContext = $this->buildEnrichedContext($searchResults, $query);

            // 5.5. VALIDACIÓN DE CONTENIDO ÚTIL
            // Si después de todo el proceso no hay contenido relevante, devolver mensaje genérico
            $hasUsefulContent = $this->hasUsefulContent($bestElemento, $searchResults, $docContext);
            
            if (!$hasUsefulContent) {
                \Log::warning('⚠️ Sin contenido útil después de búsqueda completa', [
                    'query' => $query,
                    'elemento_found' => $bestElemento ? $bestElemento->nombre_elemento : 'NINGUNO',
                    'chunks_count' => $searchResults['document_chunks']->count(),
                    'context_length' => mb_strlen($docContext)
                ]);
                
                return $this->generateNoContentResponse($query, $startTime, $userId, $sessionId);
            }

            // 6. EJECUCIÓN CON IA
            $aiResult = $this->generatePaidAIResponse(
                $query,
                $docContext,
                $searchResults,
                $startTime,
                $userId,
                $sessionId,
                $bestElemento
            );

            // 7. DEVOLVER GANADOR PARA CACHÉ
            if ($bestElemento) {
                $aiResult['final_context'] = [
                    'id' => $bestElemento->getKey(),
                    'title' => $bestElemento->nombre_elemento
                ];
            }

            return $aiResult;
        } catch (\Exception $e) {
            \Log::error('Error crítico en generateResponseWithFallback: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Elemento representativo de una búsqueda: el mejor por título, o el del primer chunk.
     * Sirve para adjuntar la ficha del documento aunque la respuesta no venga de la IA.
     */
    private function resolveElementoFromResults(array $searchResults)
    {
        if (!empty($searchResults['elementos']) && $searchResults['elementos']->isNotEmpty()) {
            return $searchResults['elementos']->first();
        }

        if (!empty($searchResults['document_chunks']) && $searchResults['document_chunks']->isNotEmpty()) {
            $chunk = $searchResults['document_chunks']->first();
            if ($chunk->wordDocument && $chunk->wordDocument->elemento) {
                return $chunk->wordDocument->elemento;
            }
        }

        return null;
    }

    /**
     * Ficha del documento que la interfaz pinta debajo del mensaje.
     * Sacamos estos datos del texto de la IA para que la respuesta suene natural.
     */
    private function buildDocumentCard($elemento): ?array
    {
        if (!$elemento) {
            return null;
        }

        return [
            'nombre'      => $elemento->nombre_elemento ?? 'Documento',
            'folio'       => $elemento->folio_elemento ?? null,
            'version'     => $elemento->version_elemento ?? null,
            'tipo'        => optional($elemento->tipoElemento)->nombre,
            'unidad'      => optional($elemento->unidadNegocio)->nombre,
            'responsable' => optional($elemento->puestoResponsable)->nombre,
            'url'         => $this->paidAIService->resolveDocumentUrl($elemento) ?: null,
        ];
    }

    /**
     * Generar respuesta con IA de pago usando contexto enriquecido
     */
    private function generatePaidAIResponse(
        $query,
        $context,
        $searchResults,
        $startTime,
        $userId,
        $sessionId,
        $elemento = null
    ) {
        try {
            // 1. OBTENER HISTORIAL
            $history = $this->getConversationHistory($sessionId);

            // 2. MEDIR TIEMPO IA
            $aiStartTime = microtime(true);

            try {
                // 3. GENERAR RESPUESTA OPENAI
                $aiResponse = $this->paidAIService->generateResponse(
                    $query,
                    $context,
                    30,       // Timeout
                    $history, // Historial
                    $elemento // <--- 2. SE LO ENVIAMOS A PaidAIService
                );

                // 4. AJUSTAR LONGITUD
                $aiResponse = $this->adjustResponseLength($aiResponse);

                // 5. ANALYTICS
                $analyticsId = $this->logAnalytics(
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
                    'ai_provider' => config('services.ai.provider'),
                    'analytics_id' => $analyticsId,
                    'document' => $this->buildDocumentCard($elemento),
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
        $intent = $this->nlpProcessor->analyzeIntent($query);

        // FIX 1: Usamos '?? 0' por si 'search_details' o 'total_sources' no existen
        $totalSources = $searchResults['search_details']['total_sources'] ?? 0;

        if ($totalSources == 0) {
            $response = $this->generateNoResultsResponse($query, $intent);
        } else {
            $response = $this->generateContextualResponse($query, $searchResults, $intent);
        }

        $this->logAnalytics($query, $response, 'data_based_semantic', $startTime, $userId, $sessionId);

        // 4. Retornar la estructura estándar
        return [
            'response' => $response,
            'method' => 'data_based_semantic',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),

            // FIX 2: La solución al error. Si no existe 'sources', devuelve array vacío []
            'sources' => $searchResults['sources'] ?? [],

            // FIX 3: Protección extra por si acaso también falta 'search_details'
            'search_details' => $searchResults['search_details'] ?? [],

            'cached' => false,
            'intent_detected' => $intent,
            'document' => $this->buildDocumentCard($this->resolveElementoFromResults($searchResults)),
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
        $response = "No encontré coincidencias exactas con ese dato.\n\n";

        // Extraer palabras clave principales de la consulta
        $keywords = $this->extractSimpleKeywords($query);
        $mainKeyword = !empty($keywords) ? $keywords[0] : '';

        // Construir mensaje específico sobre lo que no se encontró
        if (!empty($mainKeyword)) {
            // Intentar identificar si es un folio
            $folioPatterns = $this->extractFolioPatterns($query);
            if (!empty($folioPatterns)) {
                $response .= "No encontré el folio \"" . strtoupper($folioPatterns[0]) . "\".\n\n";
            } else {
                // Extraer término principal más significativo
                $mainTerms = array_slice($keywords, 0, 3);
                $mainTerm = implode(' ', $mainTerms);
                $response .= "No encontré resultados sobre \"" . ucwords($mainTerm) . "\".\n\n";
            }
        } else {
            $response .= "No encontré resultados relacionados con tu consulta.\n\n";
        }

        $response .= "Te puedo ayudar si seguimos platicando para entender mejor qué buscas. Dime por favor:\n\n";
        $response .= "- Nombre exacto del documento\n";
        $response .= "- De qué trata (por ejemplo: cierres de mes, presupuesto, compras, etc.)";

        return $response;
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

    // Resolver el puesto de trabajo del usuario autenticado
    private function resolvePuestoUsuario(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // Si tiene acceso total (Admin, Super Admin o Directora General)
        if ($this->userPuestoService->tieneAccesoTotal($user)) {
            return null;
        }

        return $this->userPuestoService->obtenerPuesto($user);
    }

    // Filtrar y ordenar elementos válidos según criterios definidos
    public function filterValidElementos(Collection $elementos): Collection
    {
        // El tipo ya viene restringido por la query base; aquí solo exigimos que se haya puntuado.
        return $elementos
            ->filter(fn($elemento) => isset($elemento->relevance_score))
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

    // Helper necesario para que funcione containsAny
    private function containsAny($str, array $arr)
    {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false) return true;
        }
        return false;
    }

    /**
     * Verifica si hay contenido útil después de todo el proceso de búsqueda
     * (elementos, word documents, chunks). Si no hay contenido relevante,
     * es mejor devolver un mensaje genérico que forzar una respuesta.
     */
    private function hasUsefulContent($elemento, $searchResults, string $docContext): bool
    {
        // Si no hay elemento seleccionado
        if (!$elemento) {
            return false;
        }

        // Si el contexto está prácticamente vacío (menos de 100 caracteres útiles)
        $cleanContext = trim(strip_tags($docContext));
        if (mb_strlen($cleanContext) < 100) {
            return false;
        }

        // Si no hay chunks relevantes Y no hay documentos útiles
        $hasChunks = $searchResults['document_chunks']->isNotEmpty();
        $hasDocs = $searchResults['word_documents']->isNotEmpty();
        
        if (!$hasChunks && !$hasDocs) {
            return false;
        }

        // Si llegamos aquí, hay contenido suficiente para intentar responder
        return true;
    }

    /**
     * Genera una respuesta genérica cuando no se encuentra información relevante
     * después de realizar toda la búsqueda.
     */
    private function generateNoContentResponse($query, $startTime, $userId, $sessionId): array
    {
        $response = "Lo siento, no encontré información relevante sobre tu consulta en la base de documentos.\n\n";
        $response .= "**Sugerencias:**\n";
        $response .= "• Intenta reformular tu pregunta con términos más específicos\n";
        $response .= "• Verifica si estás usando el nombre correcto del procedimiento o documento\n";
        $response .= "• Asegúrate de que tu consulta esté relacionada con procedimientos, políticas o lineamientos del sistema de gestión de calidad\n\n";
        $response .= "Si necesitas ayuda específica, puedo ayudarte con:\n";
        $response .= "- Procedimientos de gestión\n";
        $response .= "- Políticas y lineamientos\n";
        $response .= "- Reglamentos internos\n";
        $response .= "- Controles de cambios\n";
        $response .= "- Matrices de documentos";

        // Registrar en analytics
        $this->logAnalytics(
            $query,
            $response,
            'no_content_found',
            $startTime,
            $userId,
            $sessionId
        );

        return [
            'response' => $response,
            'method' => 'no_content_found'
        ];
    }

    /**
     * Recupera el historial de chat formateado para enviarlo a la IA.
     */
    private function getConversationHistory($sessionId, $limit = 6)
    {
        // Si hay un ID de sesión (que cambia con el F5), filtramos SOLO por eso.
        if (!empty($sessionId)) {
            $query = ChatbotAnalytics::where('session_id', $sessionId);
        }
        // Si por alguna razón no hay sesión pero sí usuario, usamos el usuario (historial global)
        elseif ($userId = auth()->id()) {
            $query = ChatbotAnalytics::where('user_id', $userId);
        } else {
            return [];
        }

        // Barrera de reinicio: lo anterior a un reset no cuenta como historial.
        $resetAt = \Cache::get($this->getHistoryResetKey($sessionId, (string) auth()->id()));
        if ($resetAt) {
            $query->where('created_at', '>', $resetAt);
        }

        // Obtenemos los últimos mensajes de ESTA sesión
        $chats = $query->latest()
            ->take($limit)
            ->get()
            ->reverse();

        $history = [];
        foreach ($chats as $chat) {
            $history[] = ['role' => 'user', 'content' => $chat->query];
            // Importante: strip_tags para no mandar basura de HTML al modelo
            $history[] = ['role' => 'assistant', 'content' => strip_tags($chat->response)];
        }

        return $history;
    }

    /**
     * Genera una clave de caché única y estricta.
     * El F5 es la ley: si el ID de sesión cambia, el cerebro se limpia.
     */
    private function getContextKey(?string $sessionId, ?string $userId)
    {
        // Forzamos el uso de SessionID para garantizar que el F5 limpie el contexto.
        // Si no hay sesión (caso extremo), usamos un ID único temporal para no mezclar datos.
        $key = !empty($sessionId) ? $sessionId : ($userId ?? 'temp_' . uniqid());

        return "chat_context_" . $key;
    }

    /**
     * Clave de la barrera de reinicio: marca desde qué momento cuenta el historial.
     * El historial vive en chatbot_analytics (BD), así que no se borra: se ignora lo anterior.
     */
    private function getHistoryResetKey(?string $sessionId, ?string $userId)
    {
        $key = !empty($sessionId) ? $sessionId : ($userId ?? 'temp');

        return "chat_history_reset_" . $key;
    }

    /**
     * Deja la conversación en cero: sin documento en foco y sin historial previo.
     */
    private function resetConversation(?string $sessionId, ?string $userId): void
    {
        \Cache::forget($this->getContextKey($sessionId, $userId));
        \Cache::put($this->getHistoryResetKey($sessionId, $userId), now()->toDateTimeString(), 3600);
    }

    /**
     * Helper: Limpia la pregunta y saca palabras clave
     * (Versión corregida que acepta números como "4" o "10")
     */
    private function extractSearchKeywords($query)
    {
        if (empty($query)) return [];

        $stopWords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'que', 'y', 'en', 'por', 'para', 'con', 'se', 'su', 'sus', 'es', 'son', 'como', 'donde', 'cual', 'cuales', 'dime', 'sobre', 'dame', 'necesito'];

        // Limpieza: permitimos letras, números y puntos (para cosas como "3.5")
        $clean = preg_replace('/[^\p{L}\p{N}\s\.]/u', '', mb_strtolower($query));
        $words = explode(' ', $clean);

        return array_filter($words, function ($w) use ($stopWords) {
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
}

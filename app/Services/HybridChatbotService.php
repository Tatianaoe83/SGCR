<?php

namespace App\Services;

use App\Models\ChatbotAnalytics;
use App\Models\WordDocument;
use App\Models\SmartIndex;
use App\Models\Elemento;
use App\Models\Empleados;
use App\Models\PuestoTrabajo;
use App\Models\UnidadNegocio;
use App\Models\Area;
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
    private array $lastSearchReasoning = [];

    // Umbrales de decisión semántica (coseno 0-1). Reemplazan las listas de palabras gatillo.
    // Sesgo fuerte a PERMANECER en el doc: una pregunta de seguimiento genérica ("y los
    // riesgos?", "el objetivo") tiene similitud moderada (~0.3-0.4) y casi siempre pierde
    // contra el doc del corpus que más habla de ese aspecto. Por eso sólo se cambia de tema
    // si el usuario NOMBRA otro documento, o si algo lo supera por un margen grande.
    // Calibrado con datos reales: seguimiento ~0.4, doc nombrado explícito domina vía pin.
    // Menos anclaje al PDF: el usuario cambia de tema seguido (empresa, área, “cómo me llamo”).
    // Máximo de correos que el chat entrega en una respuesta. Por encima de esto se
    // pide acotar: el directorio completo nunca se vuelca en el chat.
    private const EMAIL_MAX_RESULTADOS = 70;

    private const SIM_STAY = 0.42;        // sim_doc >= esto: seguimiento (antes 0.30, demasiado pegajoso)
    private const SIM_SWITCH_NEW = 0.40;  // un doc nuevo puede robar foco sin margen enorme
    private const SIM_SWITCH_MARGIN = 0.06;
    private const SIM_DEAD = 0.28;        // debajo de esto se limpia el contexto

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
    // Cuando el usuario pide "procedimientos", nunca mezclar con tipo Proceso (mapa IND/PAA…).
    private const ELEMENTO_TIPOS_PROCEDIMIENTO = ['Procedimiento', 'Procedimiento_Firmas'];

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
            . "\n- Habla en español de tú, con un registro semiformal: cordial, claro y profesional."
            . "\n- Completa las oraciones. Evita el tono de chat informal (jerga, frases cortantes, “dímelo”, “como quieras”)."
            . "\n- No suenes a informe rígido ni a manual. Sé atento, sin sequedad."
            . "\n- Responde con precisión a lo preguntado. Una breve frase de contexto está bien si orienta."
            . "\n\nFORMATO:"
            . "\n- Si piden lista, pasos, riesgos, responsables, definiciones o varios puntos, usa viñetas (-) o números."
            . "\n- Si la duda es corta, responde en 1–3 frases; no fuerces listas."
            . "\n- Usa **negritas** para conceptos clave, sin abusar."
            . "\n- Evita párrafos largos sin saltos de línea."
            . "\n\nCONTENIDO:"
            . "\n- Basa la respuesta solo en la información proporcionada. No inventes."
            . "\n- No inventes personas, correos, puestos, folios ni procedimientos que no vengan en los datos."
            . "\n- Si no hay dato, indícalo con claridad: no completes con suposiciones."
            . "\n- Revisa todo el contenido que te pasan, no te quedes con el primer párrafo."
            . "\n- Para definiciones o responsables, busca primero en esas secciones del documento."
            . "\n- Si una definición aparece explícita, úsala tal cual."
            . "\n- Si el documento no contiene la respuesta, responde EXACTAMENTE con esta única línea y nada más: [[SIN_INFO]]"
            . "\n\nPLÁTICA:"
            . "\n- Recuerda de qué estaban hablando. \"Eso\", \"y los riesgos\", \"explícame\" se refieren al hilo."
            . "\n- Si hay dos lecturas posibles, formula una pregunta de aclaración; si el hilo ya lo deja claro, responde."
            . "\n- Cierra con un siguiente paso útil solo si aporta, en tono semiformal.";
    }


    private function applyToneInstruction(?string $context = null)
    {
        $instruction = $this->conversationalToneInstruction;

        if ($context && trim($context) !== '') {
            return $instruction . "\n\n" . $context;
        }

        return $instruction;
    }

    /**
     * Enruta un turno libre con IA. Los datos los resuelven BD/RAG; si no hay, se dice.
     */
    private function maybeRouteByConversationAi(
        string $cleanQuery,
        string $searchQuery,
        $startTime,
        $userId,
        $sessionId,
        string $contextKey,
        string $catalogStateKey,
        string $offerMenuKey,
        $cachedContext
    ): ?array {
        if (!$this->usePaidAI) {
            return null;
        }

        $history = $this->getConversationHistory($sessionId, 8, $userId);
        $classified = $this->paidAIService->classifyConversationTurn(
            $cleanQuery,
            $history,
            is_array($cachedContext) ? $cachedContext : null
        );
        if (!is_array($classified)) {
            return null;
        }

        $route = $classified['route'] ?? 'unknown';
        $confidence = (float) ($classified['confidence'] ?? 0);
        $topic = trim((string) ($classified['topic'] ?? ''));
        if ($topic !== '' && !$this->topicGroundedInThread($topic, $cleanQuery, $history)) {
            $topic = '';
        }

        if (!empty($classified['reject_previous'])) {
            \Cache::forget($contextKey);
            \Cache::forget($catalogStateKey);
            $cachedContext = null;
        }

        $queryForHandlers = $topic !== '' && mb_stripos($cleanQuery, $topic) === false
            ? trim($cleanQuery . ' ' . $topic)
            : $cleanQuery;

        $min = in_array($route, ['people_area', 'people', 'identity', 'email', 'contact'], true) ? 0.55 : 0.75;
        if ($confidence < $min || in_array($route, ['unknown', 'document', 'followup', 'diagram'], true)) {
            return null;
        }

        \Cache::forget($offerMenuKey);

        if ($route === 'identity') {
            \Cache::forget($contextKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            return $this->generatePersonalIdentityResponse($cleanQuery, $startTime, $userId, $sessionId);
        }

        if ($route === 'email') {
            $documentoEnFocoId = is_array($cachedContext) ? ($cachedContext['id'] ?? null) : null;
            \Cache::forget($contextKey);
            return $this->generateEmailDirectoryResponse(
                $queryForHandlers,
                $startTime,
                $userId,
                $sessionId,
                $documentoEnFocoId
            );
        }

        $docFocus = (is_array($cachedContext) && !empty($cachedContext['id']))
            ? $cachedContext
            : \Cache::get($this->getLastDocHintKey($sessionId, $userId));
        $docSectionFollowUp = is_array($docFocus)
            && !empty($docFocus['id'])
            && (
                $this->isElementoResponsableMetaQuery($cleanQuery)
                || $this->isDocumentSectionQuery($cleanQuery)
                || $this->isContextDependentQuestion($cleanQuery)
            );

        if ($docSectionFollowUp && in_array($route, ['people', 'people_area', 'contact'], true)) {
            return null;
        }

        if ($route === 'contact') {
            $topic = $this->detectHrPersonalTopic($queryForHandlers);
            if ($topic === '') {
                $topic = $this->detectHrPersonalTopic($cleanQuery);
            }
            if ($topic === '') {
                $topic = 'personal';
            }
            \Cache::forget($contextKey);
            return $this->buildContactForTopicResponse(
                $queryForHandlers,
                $topic,
                $startTime,
                $userId,
                $sessionId
            );
        }

        if ($route === 'people_area' || $route === 'people') {
            \Cache::forget($contextKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            return $this->generatePeopleOrOrgResponse(
                $queryForHandlers,
                $this->normalizeColloquialQuery($queryForHandlers),
                $startTime,
                $userId,
                $sessionId
            );
        }

        if ($route === 'catalog') {
            \Cache::forget($contextKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            $catalogResponse = $this->generateCatalogBrowseResponse(
                $queryForHandlers,
                $this->normalizeColloquialQuery($queryForHandlers),
                $startTime,
                $userId,
                $sessionId,
                null
            );
            if (!empty($catalogResponse['catalog_state'])) {
                \Cache::put($catalogStateKey, $catalogResponse['catalog_state'], 600);
            }

            return $catalogResponse;
        }

        if ($route === 'chitchat') {
            $cat = $this->resolveChitChatCategory($cleanQuery) ?: 'queja';
            return $this->buildChitChatResponse(
                $cat,
                $cleanQuery,
                is_array($cachedContext) ? $cachedContext : null,
                $startTime,
                $userId,
                $sessionId
            );
        }

        return null;
    }

    /**
     * El topic de la IA tiene que aparecer en la pregunta o en el hilo. Si no, se descarta.
     */
    private function topicGroundedInThread(string $topic, string $query, array $history): bool
    {
        $t = $this->foldAccents($topic);
        if ($t === '' || mb_strlen($t) < 3) {
            return false;
        }
        if (str_contains($this->foldAccents($query), $t)) {
            return true;
        }
        foreach ($history as $msg) {
            if (str_contains($this->foldAccents((string) ($msg['content'] ?? '')), $t)) {
                return true;
            }
        }

        return false;
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
    private function adjustResponseLength(string $response, int $minWords = 0, int $maxWords = 900): string
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
        if (preg_match('/^(hola|holi|buenos dias|buenas tardes|buenas noches|hi|hello|start|inicio)\b/i', $cleanQuery)) {
            $resto = trim(preg_replace(
                '/^(hola|holi|buenos d[ií]as|buenas tardes|buenas noches|hi|hello|start|inicio)\b[,!.\s]*/iu',
                '',
                $cleanQuery
            ) ?? '');
            $soloCortesia = $resto !== ''
                && (bool) preg_match('/^(guapo|guapa|amigo|amiga|bonit[oa]|lind[oa]|hermoso|crack|bb|bebe|querido|hermos[oa])\b/iu', $resto)
                && !preg_match('/\b(procedimiento|folio|puesto|qui[eé]n|vacacion)/iu', $resto);

            if ($soloCortesia) {
                return [
                    'response' => "Hola. ¿En qué te oriento del SGC? Puedo consultar procedimientos, tu puesto o el directorio.",
                    'method' => 'conversation_greeting_short',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'chips' => [
                        ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                        ['label' => 'Directorio', 'query' => 'quién ocupa un puesto'],
                    ],
                ];
            }

            $this->resetConversation($sessionId, $userId);
            return [
                'response' => "**Hola, soy Bob**, asistente del Sistema de Gestión de Calidad de Proser.\n\nPuedes plantear tu consulta con tus propias palabras. Reviso la información registrada en el SGC: procedimientos, tu puesto y el directorio. Si un dato no está registrado, te lo indico; no invento personas ni folios.\n\n¿En qué puedo orientarte?",
                'method' => 'conversation_greeting',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'chips' => [
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                    ['label' => 'Directorio', 'query' => 'quién ocupa un puesto'],
                ],
            ];
        }

        // 2. COMANDOS DE REINICIO
        if (preg_match('/^(olvida|borra|reinicia|limpia|reset)\b/i', $cleanQuery)) {
            $this->resetConversation($sessionId, $userId);
            return [
                'response' => "He restablecido el contexto de esta conversación.\n\n¿Sobre qué documento o tema deseas consultar?",
                'method' => 'conversation_reset',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }

        // 3. RECUPERAR CONTEXTO
        $contextKey = $this->getContextKey($sessionId, $userId);
        $cachedContext = \Cache::get($contextKey);

        // Recordar el aspecto pedido (riesgos, evidencias…) para seguimientos tipo "sí existen".
        $detectedAspect = $this->detectQueryAspect($cleanQuery);
        if ($detectedAspect !== '') {
            \Cache::put($this->getLastAspectKey($sessionId, $userId), $detectedAspect, 1800);
        }

        // 2.5 Identidad del usuario logueado (no es pregunta de un PDF).
        if ($this->isPersonalIdentityQuery($cleanQuery)) {
            \Cache::forget($contextKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            return $this->generatePersonalIdentityResponse($cleanQuery, $startTime, $userId, $sessionId);
        }

        // 2.55 PERSONAS DE UN ÁREA (antes de catálogo y RAG).
        if ($this->isPeopleOfAreaQuery($cleanQuery) || $this->isPeopleOfAreaQuery($searchQuery)) {
            \Cache::forget($contextKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            return $this->generatePeopleOrOrgResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // 2.6 CORREOS ELECTRÓNICOS (propio, por nombre, por puesto o por área).
        // Bloque aditivo: no altera la identidad personal ni el directorio existentes.
        if ($this->isEmailDirectoryQuery($cleanQuery) || $this->isEmailDirectoryQuery($searchQuery)) {
            // $cachedContext ya se leyó arriba: se pasa ANTES de olvidarlo para que
            // "dame su correo" pueda seguir resolviendo al responsable del documento
            // que se estaba viendo, aunque este bloque suelte el foco del PDF.
            $documentoEnFocoId = is_array($cachedContext) ? ($cachedContext['id'] ?? null) : null;
            \Cache::forget($contextKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            return $this->generateEmailDirectoryResponse(
                $cleanQuery,
                $startTime,
                $userId,
                $sessionId,
                $documentoEnFocoId
            );
        }

        // 3.0 CATÁLOGO / LISTAS DESDE BD (puesto, relacionados, área, unidad…)
        // Usan el inventario y relaciones reales. No deben anclarse al PDF en foco
        // (salvo "relacionados" del documento actual, que sí usa el contexto).
        $catalogStateKey = $this->getCatalogStateKey($sessionId, $userId);
        $catalogState = \Cache::get($catalogStateKey);
        $offerMenuKey = $this->getOfferMenuKey($sessionId, $userId);
        $offerMenu = \Cache::get($offerMenuKey);

        // Directorio / obligaciones / "soy un X": antes de la IA, para que no abra un PDF al azar.
        if ($this->isFullEmployeeDumpQuery($cleanQuery) && !$this->isPeopleOfAreaQuery($cleanQuery)) {
            return $this->buildFullDirectoryRefuseResponse($cleanQuery, $startTime, $userId, $sessionId);
        }
        if ($this->isRoleDutiesQuery($cleanQuery)) {
            $duties = $this->generatePuestoDutiesResponse(
                $cleanQuery,
                $startTime,
                $userId,
                $sessionId,
                is_array($catalogState) ? $catalogState : null,
                is_array($cachedContext) ? $cachedContext : null
            );
            if ($duties !== null) {
                \Cache::forget($contextKey);
                if (!empty($duties['catalog_state'])) {
                    \Cache::put($catalogStateKey, $duties['catalog_state'], 600);
                }
                return $duties;
            }
        }
        if (
            !$this->isDocumentSectionQuery($cleanQuery)
            && !$this->isCatalogBrowseQuery($cleanQuery)
            && (
                $this->queryNamesDirectoryPuesto($cleanQuery)
                || $this->isPeopleOrOrgDirectoryQuery($cleanQuery)
            )
        ) {
            \Cache::forget($contextKey);
            $directoryResponse = $this->generatePeopleOrOrgResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );
            if (!empty($directoryResponse['catalog_state'])) {
                \Cache::put($catalogStateKey, $directoryResponse['catalog_state'], 600);
            }

            return $directoryResponse;
        }

        // "Ninguno de esos" + tema nuevo: soltar chips/PDF y no seguir el vecino semántico.
        if ($this->isRejectingOfferedOptions($cleanQuery)) {
            \Cache::forget($contextKey);
            \Cache::forget($catalogStateKey);
            \Cache::forget($this->getPendingContactKey($sessionId, $userId));
            $cachedContext = null;
            $catalogState = null;
            $sinRechazo = trim(preg_replace(
                '/^(ninguno|ninguna)(\s+de\s+(esos|esas|ellos))?[,.]?\s*/iu',
                '',
                $cleanQuery
            ) ?? '');
            if ($sinRechazo !== '' && mb_strtolower($sinRechazo) !== mb_strtolower($cleanQuery)) {
                $cleanQuery = $sinRechazo;
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
            }
        }

        if ($this->shouldRouteToHrContact($cleanQuery)) {
            $topic = $this->detectHrPersonalTopic($cleanQuery);
            if ($topic === '') {
                $topic = 'personal';
            }
            \Cache::forget($contextKey);

            return $this->buildContactForTopicResponse(
                $cleanQuery,
                $topic,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Menú pendiente ("¿tus procedimientos, directorio o documento?") + respuesta vaga ("sí quiero").
        // Si hay documento en foco y el usuario solo dice "sí", NO reabrir el menú: seguir el PDF.
        if (is_array($offerMenu) && !empty($offerMenu['options'])) {
            $picked = $this->resolveOfferMenuChoice($cleanQuery, $offerMenu);
            $hasDocFocus = $cachedContext && !empty($cachedContext['id']);
            if ($picked !== null && $picked !== 'clarify') {
                \Cache::forget($offerMenuKey);
                \Cache::forget($contextKey);
                return $this->executeOfferMenuChoice(
                    $picked,
                    $cleanQuery,
                    $searchQuery,
                    $startTime,
                    $userId,
                    $sessionId,
                    $catalogStateKey
                );
            }
            if (!$hasDocFocus && ($picked === 'clarify' || $this->isVagueAffirmation($cleanQuery))) {
                \Cache::forget($contextKey);
                return $this->buildOfferMenuClarifyResponse($cleanQuery, $startTime, $userId, $sessionId);
            }
            // "sí" con PDF en foco: soltar el menú pendiente y continuar abajo.
            if ($hasDocFocus && $this->isVagueAffirmation($cleanQuery)) {
                \Cache::forget($offerMenuKey);
            }
        }

        // Frase libre: la IA clasifica la intención; los datos salen de BD/RAG.
        if (
            !$this->isVagueAffirmation($cleanQuery)
            && !preg_match('/^\s*[123]\b/u', $cleanQuery)
            && !$this->mentionsSpecificDocumentSignal($cleanQuery)
        ) {
            $aiRouted = $this->maybeRouteByConversationAi(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId,
                $contextKey,
                $catalogStateKey,
                $offerMenuKey,
                $cachedContext
            );
            if ($aiRouted !== null) {
                return $aiRouted;
            }
        }

        // Confirmación pendiente: "¿Te refieres a Cierre de Mes?" → "sí" abre ese doc.
        $pendingDocKey = $this->getPendingDocConfirmKey($sessionId, $userId);
        $pendingDoc = \Cache::get($pendingDocKey);
        $userInsistsContent = $this->isUserInsistingContentExists($cleanQuery);
        $pendingContactKey = $this->getPendingContactKey($sessionId, $userId);
        $pendingContact = \Cache::get($pendingContactKey);
        $hasDocThread = ($cachedContext && !empty($cachedContext['id']))
            || !empty(\Cache::get($this->getLastDocHintKey($sessionId, $userId))['id'] ?? null);
        if (
            ($userInsistsContent || $this->isVagueAffirmation($cleanQuery))
            && $this->lastAssistantOfferedDirectoryLookup($sessionId, $userId)
        ) {
            $puestoNombre = $this->puestoNombreFromFocusedDocument($cachedContext, $sessionId, $userId);
            if ($puestoNombre !== '') {
                \Cache::forget($pendingContactKey);
                \Cache::forget($contextKey);
                $lookup = 'quién ocupa el puesto de ' . $puestoNombre;

                return $this->generatePeopleOrOrgResponse(
                    $lookup,
                    $this->normalizeColloquialQuery($lookup),
                    $startTime,
                    $userId,
                    $sessionId
                );
            }
        }
        if (
            is_array($pendingContact)
            && !empty($pendingContact['topic'])
            && !$hasDocThread
            && ($userInsistsContent || $this->isVagueAffirmation($cleanQuery))
        ) {
            \Cache::forget($pendingContactKey);
            \Cache::forget($contextKey);
            $cachedContext = null;

            return $this->buildContactForTopicResponse(
                $cleanQuery,
                (string) $pendingContact['topic'],
                $startTime,
                $userId,
                $sessionId
            );
        }
        if (is_array($pendingDoc) && !empty($pendingDoc['id']) && $this->isVagueAffirmation($cleanQuery)) {
            \Cache::forget($pendingDocKey);
            $cachedContext = [
                'id' => $pendingDoc['id'],
                'title' => $pendingDoc['title'] ?? 'Documento',
            ];
            \Cache::put($contextKey, $cachedContext, 600);
            \Cache::put($this->getLastDocHintKey($sessionId, $userId), $cachedContext, 1800);
            $expanded = $this->expandAffirmationToDocFollowUp(
                $cachedContext,
                $sessionId,
                $userId,
                $cleanQuery,
                $userInsistsContent
            );
            $searchQuery = $expanded;
            $query = $expanded;
            $affirmationContinued = true;
        }

        // "sí"/"ok"/"sí existen": si hay documento o listado en foco, CONTINUAR ese hilo.
        // La insistencia ("sí existen") relee el último aspecto (riesgos, evidencias…),
        // no abre un catálogo ni pide pasos genéricos.
        if (!isset($affirmationContinued)) {
            $affirmationContinued = false;
        }
        if (!$affirmationContinued && ($userInsistsContent || $this->isVagueAffirmation($cleanQuery))) {
            $lastDocHint = \Cache::get($this->getLastDocHintKey($sessionId, $userId));
            $docForFollow = ($cachedContext && !empty($cachedContext['id']))
                ? $cachedContext
                : ((is_array($lastDocHint) && !empty($lastDocHint['id'])) ? $lastDocHint : null);

            if ($docForFollow && !$this->docHintFitsRecentTopic($docForFollow, $sessionId, $userId, $cleanQuery)) {
                // Un "sí" no debe volver a un tema viejo (vacaciones) si ya hay otro documento en el hilo.
                if ($this->detectHrPersonalTopic($cleanQuery) !== '') {
                    $hrTopic = $this->detectHrPersonalTopic($cleanQuery);
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $docForFollow = null;
                    return $this->buildContactForTopicResponse(
                        $cleanQuery,
                        $hrTopic,
                        $startTime,
                        $userId,
                        $sessionId
                    );
                }
            }
            if ($docForFollow) {
                $cachedContext = $docForFollow;
                \Cache::put($contextKey, $cachedContext, 600);
                $expanded = $this->expandAffirmationToDocFollowUp(
                    $cachedContext,
                    $sessionId,
                    $userId,
                    $cleanQuery,
                    $userInsistsContent
                );
                $searchQuery = $expanded;
                $query = $expanded;
                $affirmationContinued = true;
            } elseif ($userInsistsContent) {
                // Sin PDF en caché: reescribir con el hilo para no caer al catálogo vacío.
                $aspect = $this->recallLastAspect($sessionId, $userId, $cleanQuery);
                $searchQuery = $this->normalizeColloquialQuery(
                    'sección ' . ($aspect !== '' ? $aspect : 'riesgos')
                    . ' del procedimiento del hilo; extrae RIESGOS Y DESCRIPCIÓN, EVIDENCIAS o REGISTROS si aparecen'
                );
                $query = $searchQuery;
                $affirmationContinued = true;
            } elseif (is_array($catalogState)
                && (!empty($catalogState['area_ids']) || !empty($catalogState['puesto_ids']))
            ) {
                $label = $catalogState['label'] ?? 'esa lista';

                return [
                    'response' => "Con gusto. Indica el **folio** o el **nombre** del documento de {$label} "
                        . "que deseas consultar con más detalle.",
                    'method' => 'catalog_affirmation_clarify',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => [],
                    'search_details' => [],
                    'cached' => false,
                    'document' => null,
                    'analytics_id' => $this->logAnalytics(
                        $cleanQuery,
                        'catalog_affirmation_clarify',
                        'catalog_affirmation_clarify',
                        $startTime,
                        $userId,
                        $sessionId
                    ),
                ];
            } else {
                \Cache::forget($contextKey);
                return $this->buildOfferMenuClarifyResponse($cleanQuery, $startTime, $userId, $sessionId);
            }
        }

        // 3.0-bis CONSULTA QUE NOMBRA SU PROPIO OBJETIVO (área, unidad o puesto).
        // "procedimientos del área de TI" trae las mismas palabras que un seguimiento
        // ("del área" + "procedimientos"), así que la rama de seguimiento de abajo lo
        // tomaba como continuación y respondía con el área del listado anterior.
        // Si la frase nombra un área/unidad/puesto concreto, es un listado nuevo:
        // se resuelve desde cero, ignorando el catalog_state en caché.
        if (
            !$this->isDocumentSectionQuery($cleanQuery)
            && !$this->isDocumentSectionQuery($searchQuery)
            && !$userInsistsContent
            && !$affirmationContinued
            && $this->mencionaObjetivoExplicitoDeCatalogo($cleanQuery, $searchQuery)
            && ($this->isCatalogBrowseQuery($cleanQuery) || $this->isCatalogBrowseQuery($searchQuery))
            && !$this->isPeopleOfAreaQuery($cleanQuery)
            && !$this->isPeopleOrOrgDirectoryQuery($cleanQuery)
            && !$this->isRelatedProceduresListQuery($cleanQuery)
            && !$this->isRelatedProceduresListQuery($searchQuery)
        ) {
            \Cache::forget($contextKey);

            $catalogResponse = $this->generateCatalogBrowseResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId,
                null
            );

            if (!empty($catalogResponse["catalog_state"])) {
                \Cache::put($catalogStateKey, $catalogResponse["catalog_state"], 600);
            }

            return $catalogResponse;
        }

        // Seguimiento de listado: puesto/área previa, o "su lista de procedimientos".
        $asksPropiosOnly = (bool) preg_match(
            '/\b(propios?|solo (como )?responsable|como responsable)\b/u',
            mb_strtolower($cleanQuery)
        );
        if (
            !$affirmationContinued
            && !$userInsistsContent
            && !$this->isDocumentSectionQuery($cleanQuery)
            && (
                $this->isCatalogListFollowUp($cleanQuery)
                || $this->isProceduresAssignedFollowUp($cleanQuery)
                || $this->isAreaCatalogFollowUp($cleanQuery)
                || $this->isTheirProceduresListFollowUp($cleanQuery)
                || ($asksPropiosOnly && is_array($catalogState) && !empty($catalogState['puesto_ids']))
            )
        ) {
            \Cache::forget($contextKey);

            $followState = null;
            if (is_array($catalogState)) {
                if (!empty($catalogState['area_ids']) || !empty($catalogState['puesto_ids'])) {
                    $followState = $catalogState;
                }
            }

            // "su lista" / "toda su lista" → puesto del hilo (historial o doc en foco).
            if (!$followState && $this->isTheirProceduresListFollowUp($cleanQuery)) {
                $followState = $this->resolvePuestoStateFromRecentContext(
                    $sessionId,
                    $cachedContext,
                    $catalogState
                );
            }

            // Sin estado previo: caer a "mis procedimientos" (puesto del usuario).
            if (!$followState && $this->isCatalogListFollowUp($cleanQuery)) {
                $puestoId = $this->resolvePuestoUsuarioForLists();
                if ($puestoId) {
                    $p = PuestoTrabajo::find($puestoId);
                    if ($p) {
                        $followState = [
                            'mode' => 'by_puesto',
                            'puesto_ids' => [(int) $puestoId],
                            'puesto_nombres' => [$p->nombre],
                            'label' => 'mis procedimientos',
                        ];
                    }
                }
            }

            if ($followState) {
                $catalogResponse = $this->generateCatalogBrowseResponse(
                    $cleanQuery,
                    $searchQuery,
                    $startTime,
                    $userId,
                    $sessionId,
                    null,
                    $followState
                );
                if (!empty($catalogResponse['catalog_state'])) {
                    \Cache::put($catalogStateKey, $catalogResponse['catalog_state'], 600);
                }

                return $catalogResponse;
            }

            // Pidió "su lista" pero no hay puesto en contexto: no tirar los 69.
            if ($this->isTheirProceduresListFollowUp($cleanQuery)) {
                return [
                    'response' => "Para mostrarte **su lista de procedimientos** necesito identificar de **quién** se trata.\n\n"
                        . "Indica el **nombre del puesto** (por ejemplo, Director Jurídico y de Gestión Estratégica) "
                        . "o el **área** (por ejemplo, procedimientos de Jurídico).",
                    'method' => 'catalog_followup_need_puesto',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => [],
                    'search_details' => [],
                    'cached' => false,
                    'document' => null,
                    'analytics_id' => $this->logAnalytics(
                        $cleanQuery,
                        'need_puesto_for_su_lista',
                        'catalog_followup_need_puesto',
                        $startTime,
                        $userId,
                        $sessionId
                    ),
                ];
            }
        }

        // 3.045 ANCLAJE DE PREGUNTAS DEPENDIENTES DEL CONTEXTO
        // "cuál es su alcance", "el objetivo", "quién es el responsable": no traen
        // contenido propio, así que su embedding se parece a la sección homónima de
        // cualquier documento (simDoc ~0.29) y la decisión de contexto las manda a la
        // zona gris, donde el default es SOLTAR el foco.
        // Se les antepone el título del documento en foco: simDoc sube a ~0.65 y la
        // decisión existente elige "quedarse" sola. No se modifica esa decisión.
        //
        // Va ANTES del bloque de directorio a propósito: "quién es el responsable"
        // lo capturaba esa ruta, que hace Cache::forget del documento en foco y luego
        // pide aclarar un puesto que nadie preguntó. Anclada, deja de parecer consulta
        // de directorio y el foco sobrevive para los turnos siguientes.
        if (
            !$affirmationContinued
            && $cachedContext
            && !empty($cachedContext['id'])
            && !empty($cachedContext['title'])
            && $this->isContextDependentQuestion($cleanQuery)
            && !$this->isWhoIsPersonQuery($cleanQuery)
            && !$this->isPeopleOrOrgDirectoryQuery($cleanQuery)
        ) {
            $anclada = $this->anchorQuestionToFocusedDoc($searchQuery, $cachedContext);
            if ($anclada !== $searchQuery) {
                \Log::info('Chatbot pregunta anclada al documento en foco', [
                    'query' => $cleanQuery,
                    'anclada' => $anclada,
                    'doc' => $cachedContext['title'] ?? null,
                ]);
                $searchQuery = $anclada;
                $cleanQuery = $this->anchorQuestionToFocusedDoc($cleanQuery, $cachedContext);
            }
        }

        // 3.046 RESCATE DE CONSULTAS DE DIRECTORIO QUE MORIRÍAN EN "CLARIFY"
        // "quién es el jefe de TI" no resuelve porque no existe ese rol para el área:
        // la ruta rol+área falla y el fallback por tokens se envenena con "quien".
        // Sin la muletilla sí encuentra el puesto real del área (Coordinador de TI).
        //
        // Guarda estricta: SÓLO actúa si el base ya se rindió (ambos resolvedores
        // vacíos) Y la versión limpia sí resuelve Y sigue siendo consulta de
        // directorio. Si el base resuelve algo, este bloque ni se ejecuta.
        if (
            $this->isPeopleOrOrgDirectoryQuery($cleanQuery)
            || $this->isPeopleOrOrgDirectoryQuery($searchQuery)
        ) {
            $baseResuelve = $this->resolveExactPuestoFromQuery($cleanQuery)->isNotEmpty()
                || $this->resolveExactPuestoFromQuery($searchQuery)->isNotEmpty()
                || $this->findPuestosMentionedInQuery($cleanQuery . ' ' . $searchQuery)->isNotEmpty();

            if (!$baseResuelve) {
                $cleanLimpio = $this->stripDirectoryQuestionPreamble($cleanQuery);

                if ($cleanLimpio !== $cleanQuery) {
                    $searchLimpio = $this->normalizeColloquialQuery($cleanLimpio);
                    $rescatados = $this->findPuestosMentionedInQuery($cleanLimpio . ' ' . $searchLimpio);
                    $sigueSiendoDirectorio = $this->isPeopleOrOrgDirectoryQuery($cleanLimpio)
                        || $this->isPeopleOrOrgDirectoryQuery($searchLimpio);

                    if ($rescatados->isNotEmpty()) {
                        if ($sigueSiendoDirectorio) {
                            $cleanQuery = $cleanLimpio;
                            $searchQuery = $searchLimpio;
                        } else {
                            // Al quitar la muletilla se perdió la señal de directorio
                            // ("quién es jefe ti" → "jefe ti"). Reescribir con el nombre
                            // real del puesto la vuelve una consulta exacta y la ruta
                            // de directorio la resuelve sin ambigüedad.
                            $cleanQuery = 'quien ocupa el puesto de ' . $rescatados->first()->nombre;
                            $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                        }

                        \Log::info('Chatbot directorio rescatado sin muletilla', [
                            'original' => $cleanLimpio,
                            'usada' => $cleanQuery,
                            'puestos' => $rescatados->pluck('nombre')->take(3)->all(),
                        ]);
                    }
                }
            }
        }

        // 3.05 DIRECTORIO / EMPRESA (antes que catálogo y RAG)
        // "unidades de la empresa", "directores de esas áreas", "coordinador de TI".
        // NO deben anclarse al procedimiento en foco ni mezclarse con listados TI.
        if ((!$cachedContext || empty($cachedContext['id']))
            && (
                $this->isElementoResponsableMetaQuery($cleanQuery)
                || $this->isDocumentSectionQuery($cleanQuery)
                || $this->isContextDependentQuestion($cleanQuery)
            )
        ) {
            $hintDoc = \Cache::get($this->getLastDocHintKey($sessionId, $userId));
            if (is_array($hintDoc) && !empty($hintDoc['id'])) {
                $cachedContext = $hintDoc;
                \Cache::put($contextKey, $cachedContext, 600);
            }
        }

        $sigueDocEnFoco = $cachedContext
            && !empty($cachedContext['id'])
            && (
                $this->isElementoResponsableMetaQuery($cleanQuery)
                || $this->isDocumentSectionQuery($cleanQuery)
                || $this->isContextDependentQuestion($cleanQuery)
            );

        if (
            !$sigueDocEnFoco
            && (
                $this->isPeopleOrOrgDirectoryQuery($cleanQuery)
                || $this->isPeopleOrOrgDirectoryQuery($searchQuery)
                || $this->isCompanyOrgQuery($cleanQuery)
                || $this->isCompanyOrgQuery($searchQuery)
            )
        ) {
            \Cache::forget($contextKey);
            $cachedContext = null;

            $directoryResponse = $this->generatePeopleOrOrgResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );

            // Guardar puesto del directorio para "qué procedimientos tienen asignados".
            if (!empty($directoryResponse['catalog_state'])) {
                \Cache::put($catalogStateKey, $directoryResponse['catalog_state'], 600);
            }

            return $directoryResponse;
        }

        $skipCatalogForSection = $affirmationContinued
            || $userInsistsContent
            || $this->isDocumentSectionQuery($cleanQuery)
            || $this->isDocumentSectionQuery($searchQuery);

        if (
            !$skipCatalogForSection
            && ($this->isCatalogBrowseQuery($cleanQuery) || $this->isCatalogBrowseQuery($searchQuery))
        ) {
            $browseContext = $cachedContext;
            $asksRelated = $this->isRelatedProceduresListQuery($cleanQuery)
                || $this->isRelatedProceduresListQuery($searchQuery);

            if (!$asksRelated) {
                \Cache::forget($contextKey);
            }

            $catalogResponse = $this->generateCatalogBrowseResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId,
                $browseContext
            );

            // Relacionados: conservar el elemento en foco para seguimientos.
            if (!empty($catalogResponse['final_context']['id'])) {
                \Cache::put($contextKey, $catalogResponse['final_context'], 600);
            }

            // Guardar filtro de puesto para "toda la lista".
            if (!empty($catalogResponse['catalog_state'])) {
                \Cache::put($catalogStateKey, $catalogResponse['catalog_state'], 600);
            }

            return $catalogResponse;
        }

        // 3.05 COMPUERTA CONVERSACIONAL (charla / queja / meta)
        // "estás mal", "no me refiero a eso", "jajaja", "gracias"… no preguntan por un
        // documento, pero la búsqueda híbrida siempre devuelve un top-1 y terminaban
        // robando el foco y disparando "Cambiando a …".
        // Se responden aquí: SIN buscar.
        $chitChatCategoria = $this->resolveChitChatCategory($cleanQuery);
        if ($chitChatCategoria !== null) {
            \Log::info('Chatbot compuerta conversacional', [
                'query' => $cleanQuery,
                'categoria' => $chitChatCategoria,
                'doc_en_foco' => $cachedContext['id'] ?? null,
            ]);

            // RECHAZO / "me perdí" / "volvamos": soltar PDF y RETOMAR el tema del hilo
            // (chips), no abrir menú genérico 1/2/3 que confunde a usuarios básicos.
            if ($chitChatCategoria === 'queja') {
                $tituloSoltado = trim((string) ($cachedContext['title'] ?? ''));
                \Cache::forget($contextKey);
                \Cache::forget($this->getOfferMenuKey($sessionId, $userId));
                $cachedContext = null;

                return $this->buildTopicRecoveryResponse(
                    $cleanQuery,
                    $startTime,
                    $userId,
                    $sessionId,
                    $catalogState,
                    $tituloSoltado
                );
            }

            // Resto (cortesía, risa, despedida): responder SIN tocar el contexto.
            return $this->buildChitChatResponse(
                $chitChatCategoria,
                $cleanQuery,
                $cachedContext,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // 3.055 ORIENTACIÓN NOVATO: "necesito algo de X" sin folio/nombre claro.
        // Antes de RAG: 1 aclaración + chips (evita PDF al azar).
        // Si hay documento en el hilo, no se tira: se pregunta si siguen con ese o cambian.
        if (
            !$affirmationContinued
            && $this->isVagueTopicNeedQuery($cleanQuery)
        ) {
            $threadDoc = null;
            if ($cachedContext && !empty($cachedContext['id'])) {
                $threadDoc = $cachedContext;
            } else {
                $hint = \Cache::get($this->getLastDocHintKey($sessionId, $userId));
                if (is_array($hint) && !empty($hint['id'])) {
                    $threadDoc = $hint;
                }
            }

            if (!$threadDoc) {
                \Cache::forget($contextKey);
                $cachedContext = null;
            }

            return $this->buildVagueTopicClarifyResponse(
                $cleanQuery,
                $startTime,
                $userId,
                $sessionId,
                $threadDoc
            );
        }

        // 3.056 COMPARAR DOS PROCEDIMIENTOS: no abrir un solo PDF al azar.
        if (
            !$affirmationContinued
            && $this->isCompareProceduresQuery($cleanQuery)
        ) {
            return $this->buildCompareProceduresResponse(
                $cleanQuery,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // 3.06 COMPUERTA "FUERA DE TEMA" (matemáticas, chistes, consejos, roleplay,
        // cultura general, tareas escolares, inyección de instrucciones…)
        // Bloque aditivo, mismo patrón que la compuerta conversacional de arriba: corta
        // ANTES de la búsqueda/IA para que Bob nunca resuelva "cuánto es 15 por 8" ni
        // similares usando conocimiento externo. Solo cubre patrones inequívocos, así
        // que una pregunta real del SGC nunca cae aquí.
        if ($this->isFueraDeTemaQuery($cleanQuery)) {
            return $this->buildFueraDeTemaResponse($cleanQuery, $startTime, $userId, $sessionId);
        }

        // 3.07 RAZONAMIENTO DE BÚSQUEDA CON EL HILO
        // Reescribe la pregunta (historial + documento en foco) ANTES de embeddings
        // y de decidir si se queda o cambia de tema. La pregunta original se conserva
        // para que Bob responda natural.
        if (!$affirmationContinued) {
            if ($this->isTopicEscapeQuery($cleanQuery, $cachedContext)) {
                $this->lastSearchReasoning = [
                    'search' => $searchQuery,
                    'intent' => 'switch',
                    'aspect' => '',
                ];
                if ($cachedContext && !empty($cachedContext['id'])) {
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                }
            } else {
                $reasoned = $this->applyConversationalSearchReasoning(
                    $cleanQuery,
                    $searchQuery,
                    $cachedContext,
                    $sessionId,
                    $userId
                );
                $this->lastSearchReasoning = $reasoned;
                if (!empty($reasoned['aspect'])) {
                    \Cache::put($this->getLastAspectKey($sessionId, $userId), (string) $reasoned['aspect'], 1800);
                }
                if (($reasoned['intent'] ?? '') === 'switch' && $cachedContext) {
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $searchQuery = $reasoned['search'] ?: $searchQuery;
                } elseif (!empty($reasoned['search']) && $reasoned['search'] !== $searchQuery) {
                    \Log::info('Chatbot búsqueda razonada con hilo', [
                        'original' => $cleanQuery,
                        'search' => $reasoned['search'],
                        'intent' => $reasoned['intent'] ?? null,
                        'aspect' => $reasoned['aspect'] ?? null,
                    ]);
                    $searchQuery = $reasoned['search'];
                }
            }
        }

        // 3.1 DECISIÓN SEMÁNTICA DE CONTEXTO
        // Reemplaza las listas de palabras gatillo (isContextMismatch / isFollowUp por regex):
        // compara el SIGNIFICADO de la pregunta contra el doc cacheado y contra el mejor doc
        // nuevo, y decide seguimiento vs cambio de tema por coseno, no por palabras.
        $hadContextMismatch = false;
        $isFollowUp = false;

        // "sí" sobre el PDF en foco: forzar seguimiento (no soltar por similitud baja de "si").
        if ($affirmationContinued && $cachedContext && !empty($cachedContext['id'])) {
            $isFollowUp = true;
            \Log::info('Chatbot afirmación continúa documento en foco', [
                'original' => $cleanQuery,
                'expanded' => $searchQuery,
                'doc_id' => $cachedContext['id'] ?? null,
            ]);
        }

        // "en bullets / más corto / formal": reformatear el PDF en foco, NO cambiar de tema.
        // Si no hay foco (p.ej. tras comparar), reanclar el último documento del hilo.
        if (
            !$isFollowUp
            && !($cachedContext && !empty($cachedContext['id']))
            && $this->isFormatOnlyFollowUp($cleanQuery)
        ) {
            $hint = \Cache::get($this->getLastDocHintKey($sessionId, $userId));
            if (is_array($hint) && !empty($hint['id'])) {
                $cachedContext = $hint;
                \Cache::put($contextKey, $cachedContext, 600);
            }
        }

        if (
            !$isFollowUp
            && $cachedContext
            && !empty($cachedContext['id'])
            && $this->isFormatOnlyFollowUp($cleanQuery)
        ) {
            $titulo = trim((string) ($cachedContext['title'] ?? 'este procedimiento'));
            $qLow = mb_strtolower($cleanQuery);
            if (preg_match('/\b(m[aá]s corto|corto|breve)\b/u', $qLow)) {
                $expanded = "Dame un resumen MUY corto del procedimiento {$titulo} "
                    . "(objetivo + 3-5 puntos clave).";
            } elseif (preg_match('/\bformal\b/u', $qLow)) {
                $expanded = "Explica de forma formal el procedimiento {$titulo}: objetivo, alcance y pasos.";
            } elseif (preg_match('/\b(objetivo|alcance|responsables?|riesgos?)\b/u', $qLow)) {
                $sec = 'objetivo';
                if (preg_match('/\balcance\b/u', $qLow)) {
                    $sec = 'alcance';
                } elseif (preg_match('/\bresponsables?\b/u', $qLow)) {
                    $sec = 'responsables';
                } elseif (preg_match('/\briesgos?\b/u', $qLow)) {
                    $sec = 'riesgos';
                }
                $expanded = "Resume en viñetas (bullets) la sección de {$sec} del procedimiento {$titulo}.";
            } else {
                $expanded = "Resume en viñetas claras (bullets) el procedimiento {$titulo}: "
                    . "objetivo, pasos principales y responsables. Sé breve.";
            }
            $searchQuery = $expanded;
            $query = $expanded;
            $isFollowUp = true;
            \Log::info('Chatbot formato sigue documento en foco', [
                'original' => $cleanQuery,
                'doc_id' => $cachedContext['id'] ?? null,
            ]);
        }

        if ($cachedContext && !empty($cachedContext['id']) && !$affirmationContinued && !$isFollowUp) {
            // Preguntas de empresa / catálogo / identidad: salir YA del PDF.
            if ($this->isHardTopicSwitchQuery($cleanQuery) || $this->isHardTopicSwitchQuery($searchQuery)) {
                \Cache::forget($contextKey);
                $cachedContext = null;
                $hadContextMismatch = true;
                \Log::info('Chatbot cambio de tema duro (sin embeddings)', [
                    'query' => $cleanQuery,
                    'decision' => 'hard_switch',
                ]);
            }
        }

        if ($cachedContext && !empty($cachedContext['id']) && !$affirmationContinued && !$isFollowUp) {
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
                } elseif ($simDoc >= self::SIM_STAY && $simNew <= $simDoc + self::SIM_SWITCH_MARGIN) {
                    // Seguimiento solo si el doc sigue fuerte Y nada nuevo lo supera claro.
                    $isFollowUp = true;
                    $decision = 'stay';
                } elseif ($simNew >= self::SIM_SWITCH_NEW && $simNew > $simDoc + self::SIM_SWITCH_MARGIN) {
                    // Sin nombrar, pero algo nuevo es más pertinente -> cambio de tema.
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
                    // Zona gris: preferir SOLTAR el PDF (antes se quedaba y “perdía” al usuario).
                    \Cache::forget($contextKey);
                    $cachedContext = null;
                    $hadContextMismatch = true;
                    $decision = 'release_gray';
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

            // Tema buscado sin ancla BD (solo vecino semántico): no abrir PDF ajeno.
            if (
                !$isFollowUp
                && $this->isNeighborOnlySemanticMatch($searchQuery, $finalResults)
            ) {
                return $this->buildUnpublishedTopicResponse(
                    $cleanQuery,
                    $searchQuery,
                    $finalResults,
                    $startTime,
                    $userId,
                    $sessionId
                );
            }
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

        // 5.85 FUERA DE TEMA (capa 2): red de seguridad para lo que la compuerta 3.06
        // (regex) no anticipó. La IA marca [[FUERA_DE_TEMA]] cuando la pregunta no
        // tiene relación con el SGC (ver instrucción en PaidAIService::buildPrompt).
        // Bloque aditivo, mismo mecanismo que [[SIN_INFO]] de abajo pero sin tocarlo.
        if ($this->responseSaysFueraDeTema($responseArray['response'] ?? '')) {
            return $this->buildFueraDeTemaResponse($query, $startTime, $userId, $sessionId);
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
            $titulo = $focoTitulo;
            $consulta = trim($cleanQuery);

            // Te perdiste o cambiaste de tema: GUIAR, no quedarse en el PDF vacío.
            if ($this->isWhoIsPersonQuery($cleanQuery) || $this->isEmployeeConfirmQuery($cleanQuery)) {
                \Cache::forget($contextKey);
                return $this->generatePeopleOrOrgResponse(
                    $cleanQuery,
                    $searchQuery,
                    $startTime,
                    $userId,
                    $sessionId
                );
            }
            if ($this->isNewProcedureSeekQuery($cleanQuery, $cachedContext)
                && $cachedContext
                && !empty($cachedContext['id'])
            ) {
                \Cache::forget($contextKey);
                $retry = $this->performIntegratedSearch($this->normalizeColloquialQuery($cleanQuery));
                if (!empty($retry['has_results'])) {
                    return $this->generateResponseWithFallback(
                        $cleanQuery,
                        $retry,
                        $startTime,
                        $userId,
                        $sessionId
                    );
                }
            }

            \Cache::forget($contextKey);
            $cachedContext = null;

            $chips = [
                ['label' => 'Directorio', 'query' => 'quién ocupa un puesto'],
                ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ];
            if ($titulo) {
                $chips = array_merge([
                    ['label' => 'Seguir: ' . mb_substr($titulo, 0, 22), 'query' => $titulo],
                ], $chips);
            }

            if ($titulo) {
                $texto = "En **{$titulo}** no aparece «{$consulta}».\n\n"
                    . "Puedo ayudarte a localizar **otra persona** en el directorio, "
                    . "**otro procedimiento**, o continuar con **{$titulo}**.";
            } else {
                $texto = "No pude resolver «{$consulta}» con el documento anterior.\n\n"
                    . "Puedo buscar por **folio o nombre**, consultar el **directorio** o **tus procedimientos**.";
            }

            return [
                'response' => $texto,
                'method' => 'context_clarify_keep_thread',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [],
                'cached' => false,
                'document' => null,
                'chips' => array_slice($chips, 0, 5),
                'analytics_id' => $responseArray['analytics_id'] ?? $this->logAnalytics(
                    $cleanQuery,
                    $texto,
                    'context_clarify_keep_thread',
                    $startTime,
                    $userId,
                    $sessionId
                ),
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

        $hrTopicNow = $this->detectHrPersonalTopic($cleanQuery);
        $docNoCuadra = $contextToSave
            && !$this->docHintFitsRecentTopic($contextToSave, $sessionId, $userId, $cleanQuery);
        if (
            ($this->isWhoToContactQuery($cleanQuery) || $hrTopicNow !== '')
            && $docNoCuadra
        ) {
            $contextToSave = null;
            $responseArray['document'] = null;
            $responseArray['final_context'] = null;
            \Cache::forget($contextKey);
            \Cache::put($this->getPendingContactKey($sessionId, $userId), [
                'topic' => $hrTopicNow !== '' ? $hrTopicNow : 'vacaciones',
                'asked_at' => time(),
            ], 600);
        }

        if ($contextToSave) {
            // Validamos que no sea null antes de guardar
            if (!empty($contextToSave['id'])) {
                \Cache::put($contextKey, $contextToSave, 600);
                \Cache::put($this->getLastDocHintKey($sessionId, $userId), $contextToSave, 1800);
                \Cache::forget($this->getPendingContactKey($sessionId, $userId));
                if (empty($responseArray['chips'])) {
                    $responseArray['chips'] = $this->documentGuideChips();
                }
            }
        }

        // Guardar puesto del hilo para "su lista de procedimientos".
        $this->rememberPuestoCatalogStateFromTurn(
            $cleanQuery,
            (string) ($responseArray['response'] ?? ''),
            $catalogStateKey,
            $catalogState
        );

        return $responseArray;
    }

    /**
     * Si en el turno se habló de un puesto concreto, dejarlo en catalog_state.
     */
    private function rememberPuestoCatalogStateFromTurn(
        string $query,
        string $response,
        string $catalogStateKey,
        ?array $existingState
    ): void {
        // No pisar un filtro de área activo.
        if (is_array($existingState) && !empty($existingState['area_ids'])) {
            return;
        }

        $blob = $query . ' ' . strip_tags($response);
        $puestos = $this->resolveExactPuestoFromQuery($blob);
        if ($puestos->isEmpty()) {
            $catalog = $this->getPuestosCatalog();
            $hay = $this->foldAccents($blob);
            $puestos = $catalog->filter(function ($p) use ($hay) {
                $name = $this->foldAccents((string) $p->nombre);
                return mb_strlen($name) >= 12 && str_contains($hay, $name);
            })->values();
        }

        if ($puestos->isEmpty()) {
            return;
        }

        $best = $puestos->sortByDesc(fn ($p) => mb_strlen((string) $p->nombre))->first();
        \Cache::put($catalogStateKey, [
            'mode' => 'by_puesto',
            'puesto_ids' => [(int) $best->id_puesto_trabajo],
            'puesto_nombres' => [$best->nombre],
            'label' => 'puesto(s): ' . $best->nombre,
        ], 600);
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
            'que unidades' => 'unidades de negocio',
            'qué unidades' => 'unidades de negocio',
            'a que unidades aplica' => 'unidades de negocio',
            'a qué unidades aplica' => 'unidades de negocio',
            'que areas' => 'áreas',
            'qué áreas' => 'áreas',
            'que puestos' => 'puestos relacionados',
            'qué puestos' => 'puestos relacionados',
            'quienes son los empleados' => 'empleados',
            'quiénes son los empleados' => 'empleados',
            'elemento padre' => 'elemento padre',
            'documentos relacionados' => 'elementos relacionados',
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

        // "TI" / "IT" (2 letras) se pierde en NLP; expandir a términos buscables.
        $normalized = preg_replace('/\b(t\.?i\.?|it)\b/u', 'tecnologia informacion', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bse llamada\b/u', 'se llama', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bse llaman\b/u', 'se llama', $normalized) ?? $normalized;

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
            'solitud' => 'solicitud',
            'campameto' => 'campamento',
            'cordinador' => 'coordinador',
            'cordinadora' => 'coordinadora',
            'gerent' => 'gerente',
            'presupesto' => 'presupuesto',
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

        // Preguntas de contenido interno del documento: no son catálogo global.
        if ($this->isDocumentSectionQuery($q)) {
            return false;
        }

        if (preg_match('/\b(documentos? de referencia|anexos?|dentro del (documento|procedimiento)|de este (documento|procedimiento)|en (el|este) (documento|procedimiento))\b/u', $q)) {
            return false;
        }

        if (
            $this->isRelatedProceduresListQuery($q)
            || $this->isAreaListQuery($q)
            || $this->isPuestoListQuery($q)
            || $this->isUnidadListQuery($q)
        ) {
            return true;
        }

        // "qué procesos existen" / "qué procesos hay" → catálogo de tipo Proceso.
        if (
            preg_match('/\bprocesos?\b/u', $q)
            && preg_match('/\b(existen?|hay|cu[aá]les|lista|listado|todos|todas|mu[eé]strame|dame)\b/u', $q)
            && !preg_match('/\b(objetivo|alcance|riesgos?|definiciones?)\b/u', $q)
        ) {
            return true;
        }

        $pideLista = (bool) preg_match('/\b(lista|listado|listar|enumera|enumerar|inventario|todos los|todas las|cu[aá]ntos|mu[eé]strame|p[aá]same (la|el) (lista|listado)|quiero una lista|necesito una lista|dame una lista)\b/u', $q);
        // "cuáles son sus evidencias/riesgos" es sección de un documento, no listado del área.
        if (preg_match('/\bcu[aá]les (son|hay|tengo)\b/u', $q)
            && !$this->isDocumentSectionQuery($q)
        ) {
            $pideLista = true;
        }
        $hablaDeCatalogo = (bool) preg_match('/\b(procedimientos?|procesos?|documentos?|elementos?|lineamientos?|pol[ií]ticas?|reglamentos?)\b/u', $q);
        $hablaDeArea = (bool) preg_match('/\b(area|área|ti|t\.i\.?|tecnolog|calidad|jur[ií]dic|compras?|presupuest|informaci[oó]n|corporativo|construcci[oó]n)\b/u', $q);

        if ($pideLista && ($hablaDeCatalogo || $hablaDeArea)) {
            return true;
        }

        if (preg_match('/\bqu[eé] (procedimientos|documentos|elementos|pol[ií]ticas|lineamientos)\b/u', $q)) {
            return true;
        }

        // "propios del director de jurídico" (cambio de tema desde un PDF).
        if (
            preg_match('/\b(propios?|solo (como )?responsable)\b/u', $q)
            && preg_match('/\b(director|directora|gerente|coordinador|puesto|jurid|compras?|calidad|ti)\b/u', $q)
        ) {
            return true;
        }

        if ($hablaDeCatalogo && $hablaDeArea) {
            return true;
        }

        if (
            preg_match('/\b(falta|faltan|hay m[aá]s|me falta|se te fue|te falt[oó]|incomplet)\b/u', $q)
            && preg_match('/\b(procedimiento|documento|lineamiento|pol[ií]tica|ti|t\.i\.?|tecnolog|calidad|puesto)\b/u', $q)
        ) {
            return true;
        }

        return false;
    }

    private function isRelatedProceduresListQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(relacionados?|vinculados?|padres?|hijos?|asociados?)\b/u',
            $q
        ) && (bool) preg_match(
            '/\b(procedimientos?|documentos?|elementos?|lineamientos?|pol[ií]ticas?|lista|listado)\b/u',
            $q
        );
    }

    private function isPuestoListQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        // Si pide listado por área, no tratarlo como búsqueda por puesto
        // ("procedimientos de Jurídico" ≠ "puestos de Jurídico").
        if ($this->looksLikeAreaCatalogQuery($q)) {
            return false;
        }

        // Mis procedimientos / relación conmigo (igual que el naranja del mapa).
        if (
            $this->isMyProceduresQuery($q)
            || preg_match('/\b(por puesto|seg[uú]n puesto|del puesto|mi puesto|en qu[eé] (procedimientos?|documentos?) particip|participa)\b/u', $q)
        ) {
            return true;
        }

        // "el puesto de coordinador / calidad … en qué procedimientos"
        if (preg_match('/\bpuesto\b/u', $q) && preg_match('/\b(procedimientos?|particip)/u', $q)) {
            return true;
        }

        // "propios del director jurídico" (sin decir "procedimientos").
        if (
            preg_match('/\b(propios?|solo (como )?responsable|como responsable)\b/u', $q)
            && (
                $this->resolveExactPuestoFromQuery($query)->isNotEmpty()
                || $this->resolveExactPuestoFromQuery($q)->isNotEmpty()
                || preg_match('/\b(director|directora|gerente|coordinador|puesto)\b/u', $q)
            )
        ) {
            return true;
        }

        // Solo por puesto si el usuario NOMBRA un puesto concreto (no "de presupuestos"/"de TI").
        // Antes findPuestosMentionedInQuery convertía listados por área en "¿cuál puesto?".
        if (
            preg_match('/\b(procedimientos?|documentos?|elementos?|lista|listado)\b/u', $q)
            && (
                $this->resolveExactPuestoFromQuery($query)->isNotEmpty()
                || $this->resolveExactPuestoFromQuery($q)->isNotEmpty()
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Listado por área organizacional (no por nombre de puesto).
     * Ej: "procedimientos de Jurídico", "listado por área de calidad".
     */
    private function isAreaListQuery(string $query): bool
    {
        return $this->looksLikeAreaCatalogQuery($query);
    }

    private function looksLikeAreaCatalogQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        // Si nombra un puesto completo o dice "puesto", es por puesto.
        if (preg_match('/\bpuestos?\b/u', $q)) {
            return false;
        }
        if ($this->resolveExactPuestoFromQuery($query)->isNotEmpty()) {
            return false;
        }

        $hablaCatalogo = (bool) preg_match(
            '/\b(procedimientos?|documentos?|elementos?|lista|listado|pol[ií]ticas?|procesos?)\b/u',
            $q
        );
        if (!$hablaCatalogo) {
            return false;
        }

        if (preg_match('/\b(por\s+[aá]rea|[aá]rea\s+de|del\s+[aá]rea|de\s+su\s+[aá]rea)\b/u', $q)) {
            return true;
        }

        // "listado/procedimientos de Jurídico|Calidad|TI…"
        // Incluye alias cortos (rh, sistemas, legal…) para no perder la ruta de área.
        return $this->findAreasMentionedInQuery($query)->isNotEmpty()
            || $this->findExplicitAreasInQuery($query)->isNotEmpty();
    }

    private function getAreasCatalog(): Collection
    {
        return Cache::remember('chat_areas_catalog_v2', 300, function () {
            return Area::query()->select('id_area', 'nombre', 'unidad_negocio_id')->get();
        });
    }

    /**
     * Áreas mencionadas en la pregunta (nombre o alias: jurídico, calidad, TI…).
     */
    private function findAreasMentionedInQuery(string $query): Collection
    {
        $q = $this->foldAccents($query);
        $areas = $this->getAreasCatalog();

        $matched = $areas->filter(function ($area) use ($q) {
            $name = $this->foldAccents((string) $area->nombre);
            if ($name === '' || mb_strlen($name) < 4) {
                return false;
            }
            // Nombre completo del área en la frase.
            if (str_contains($q, $name)) {
                return true;
            }
            // Token de la pregunta contenido en el nombre del área (juridico ⊂ jurídico).
            $tokens = preg_split('/[^\p{L}\p{N}]+/u', $q) ?: [];
            foreach ($tokens as $token) {
                if (mb_strlen($token) < 5) {
                    continue;
                }
                if (str_contains($name, $token) || str_contains($token, $name)) {
                    return true;
                }
            }

            return false;
        });

        // Alias cortos frecuentes.
        if (preg_match('/\b(ti|t\.?i\.?)\b/u', $q) || preg_match('/tecnolog/u', $q)) {
            $ti = $areas->filter(function ($a) {
                $n = $this->foldAccents((string) $a->nombre);
                return str_contains($n, 'tecnolog') || str_contains($n, 'informaci');
            });
            $matched = $matched->merge($ti);
        }

        // Preferir áreas cuyo nombre es más corto/específico cuando hay varias
        // (Jurídico antes que "Dirección Jurídica…"), pero devolver todas las útiles.
        return $matched->unique('id_area')->sortBy(function ($a) {
            return mb_strlen((string) $a->nombre);
        })->values();
    }

    /**
     * Fragmentos de nombre de área implicados por alias cortos de la pregunta.
     * Permite que "ti" o "rh" cuenten como mención explícita de un área.
     *
     * @return array<string, string> fragmento => alias que lo disparó
     */
    private function areaAliasFragments(string $qFold): array
    {
        // "ti" como PRONOMBRE (para ti, a ti, gracias a ti) no es el área de Tecnologías.
        // "de ti" sí se acepta: es la forma habitual de pedir el área ("procedimientos de TI").
        $tiEsPronombre = (bool) preg_match('/\b(para|por|sin|hasta|hacia|sobre|contra|entre)\s+ti\b/u', $qFold);

        $mapa = [
            'tecnolog' => array_values(array_filter([
                $tiEsPronombre ? null : '/\bt\.?\s?i\.?\b/u',
                '/tecnolog/u',
                // "sistemas de gestión" es SGC, no el área de TI.
                '/\bsistemas\b(?!\s+de\s+gestion)/u',
                '/\binformatica\b/u',
            ])),
            'capital humano' => ['/\br\.?\s?h\.?\b/u', '/\brrhh\b/u', '/\brecursos humanos\b/u', '/\bcapital humano\b/u'],
            'seguridad e higiene' => ['/\bsst\b/u'],
            'calidad' => ['/\bsgc\b/u'],
            'jurid' => ['/\bjuridic/u', '/\blegal\b/u'],
        ];

        $fragmentos = [];
        foreach ($mapa as $fragmento => $patrones) {
            foreach ($patrones as $patron) {
                if (preg_match($patron, $qFold)) {
                    $fragmentos[$fragmento] = $fragmento;
                    break;
                }
            }
        }

        return $fragmentos;
    }

    /**
     * Áreas nombradas de forma EXPLÍCITA (nombre literal o alias corto).
     *
     * A diferencia de findAreasMentionedInQuery(), no incluye coincidencias
     * laxas por token, de modo que se pueden pedir VARIAS áreas en un mismo
     * prompt sin arrastrar homónimos: "procedimientos de capital humano y ti".
     */
    private function findExplicitAreasInQuery(string $query, bool $colapsarSubsumidas = true): Collection
    {
        $qFold = $this->foldAccents($query);
        if ($qFold === '') {
            return collect();
        }

        $areas = $this->getAreasCatalog();

        // 1) Nombre completo del área tal cual en la frase.
        $explicitas = $areas->filter(function ($area) use ($qFold) {
            $name = $this->foldAccents((string) $area->nombre);

            return $name !== '' && mb_strlen($name) >= 4 && str_contains($qFold, $name);
        });

        // 2) Alias cortos (ti, rh…): tomar el área más específica del fragmento.
        foreach ($this->areaAliasFragments($qFold) as $fragmento) {
            $candidatas = $areas->filter(function ($area) use ($fragmento) {
                return str_contains($this->foldAccents((string) $area->nombre), $fragmento);
            })->sortBy(fn ($a) => mb_strlen((string) $a->nombre))->values();

            if ($candidatas->isNotEmpty()) {
                $explicitas = $explicitas->push($candidatas->first());
            }
        }

        $explicitas = $explicitas->unique('id_area')->values();

        if (!$colapsarSubsumidas) {
            return $explicitas->sortBy(fn ($a) => mb_strlen((string) $a->nombre))->values();
        }

        // 3) Descartar áreas SUBSUMIDAS: si "Contabilidad y Finanzas" es un área real
        // mencionada, no listar además "Contabilidad" y "Finanzas" como secciones aparte.
        $nombres = $explicitas->map(fn ($a) => $this->foldAccents((string) $a->nombre))->all();
        $explicitas = $explicitas->reject(function ($area) use ($nombres) {
            $name = $this->foldAccents((string) $area->nombre);
            foreach ($nombres as $otro) {
                if ($otro !== $name && mb_strlen($otro) > mb_strlen($name) && str_contains($otro, $name)) {
                    return true;
                }
            }

            return false;
        });

        return $explicitas->sortBy(fn ($a) => mb_strlen((string) $a->nombre))->values();
    }

    /**
     * Términos de tema propios de UNA área (palabras de su nombre + alias).
     * Aísla el tema por área para que una lista multi-área no se contamine.
     */
    private function buildTopicTermsForArea($area, array $baseTerms = []): array
    {
        $skipTopic = ['informacion', 'información', 'direccion', 'dirección', 'gestion',
            'gestión', 'general', 'empresa', 'negocio', 'unidad', 'unidades'];

        $terms = $baseTerms;
        $nombreArea = $this->foldAccents((string) $area->nombre);

        foreach (preg_split('/\s+/u', $nombreArea) ?: [] as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 4 && !in_array($w, $skipTopic, true)) {
                $terms[] = $w;
            }
        }

        if (str_contains($nombreArea, 'tecnolog')) {
            $terms[] = 'tecnolog';
        }
        if (str_contains($nombreArea, 'compras') || str_contains($nombreArea, 'proveedor')) {
            array_push($terms, 'compra', 'proveedor', 'proveedores');
        }
        if (str_contains($nombreArea, 'juridic')) {
            array_push($terms, 'fianzas', 'seguros', 'paa03');
        }

        return array_values(array_unique(array_filter(
            $terms,
            fn ($t) => mb_strlen(trim((string) $t)) >= 4
                && !in_array(mb_strtolower((string) $t), $skipTopic, true)
        )));
    }

    /**
     * Une nombres en lenguaje natural: "A", "A y B", "A, B y C".
     */
    private function joinNombresNaturales(array $nombres): string
    {
        $nombres = array_values(array_filter(array_unique($nombres)));
        if (count($nombres) <= 1) {
            return (string) ($nombres[0] ?? '');
        }

        $ultimo = array_pop($nombres);

        return implode(', ', $nombres) . ' y ' . $ultimo;
    }

    /**
     * Listado por área(s).
     *
     * - $agrupar = false: comportamiento histórico (un solo bloque, ids juntos).
     * - $agrupar = true: una sección por área, sin repetir documentos entre
     *   secciones. Habilita varias consultas de área en un mismo prompt.
     *
     * @param  array<int, array>|null  $termsPorArea  términos ya calculados (seguimientos)
     */
    private function buildAreaCatalogResult(
        Collection $areas,
        array $baseTopicTerms,
        ?array $tipos,
        bool $agrupar,
        ?array $termsPorArea = null
    ): array {
        $areas = $areas->unique('id_area')->values();

        if (!$agrupar || $areas->count() < 2) {
            $terms = $baseTopicTerms;
            foreach ($areas as $area) {
                $terms = $this->buildTopicTermsForArea($area, $terms);
            }

            $elementos = $this->searchElementosOfArea(
                $areas->pluck('id_area')->map(fn ($id) => (int) $id)->all(),
                $terms,
                120,
                $tipos
            );

            $nombres = $areas->pluck('nombre')->unique()->values()->all();

            return [
                'elementos' => $elementos,
                'lista_texto' => $elementos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n"),
                'label' => 'del área ' . implode(', ', $nombres),
                'area_nombres' => $nombres,
                'topic_terms' => $terms,
                'area_topic_terms' => null,
                'grouped' => false,
            ];
        }

        $todos = collect();
        $vistos = [];
        $bloques = [];
        $calculados = [];

        foreach ($areas as $area) {
            $areaId = (int) $area->id_area;
            $terms = $termsPorArea[$areaId] ?? $this->buildTopicTermsForArea($area);
            $calculados[$areaId] = $terms;

            $elementos = $this->searchElementosOfArea([$areaId], $terms, 120, $tipos);
            $nuevos = $elementos->reject(fn ($el) => isset($vistos[$el->id_elemento]))->values();
            foreach ($nuevos as $el) {
                $vistos[$el->id_elemento] = true;
            }
            $todos = $todos->merge($nuevos);

            $cuerpo = $nuevos->isEmpty()
                ? '(Sin documentos publicados para esta área.)'
                : $nuevos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");

            $bloques[] = "**{$area->nombre}** (" . $nuevos->count() . "):\n" . $cuerpo;
        }

        $nombres = $areas->pluck('nombre')->unique()->values()->all();

        return [
            'elementos' => $todos->values(),
            'lista_texto' => implode("\n\n", $bloques),
            'label' => 'de las áreas ' . $this->joinNombresNaturales($nombres),
            'area_nombres' => $nombres,
            'topic_terms' => array_values(array_unique(array_merge([], ...array_values($calculados)))),
            'area_topic_terms' => $calculados,
            'grouped' => true,
        ];
    }

    /**
     * Puestos cuyo areas_ids incluye alguna de las áreas pedidas.
     */
    private function puestoIdsForAreaIds(array $areaIds): array
    {
        $areaIds = array_values(array_unique(array_filter(array_map('intval', $areaIds))));
        if (empty($areaIds)) {
            return [];
        }

        return PuestoTrabajo::query()
            ->where(function ($q) use ($areaIds) {
                foreach ($areaIds as $aid) {
                    $q->orWhereJsonContains('areas_ids', $aid)
                        ->orWhereJsonContains('areas_ids', (string) $aid);
                }
            })
            ->pluck('id_puesto_trabajo')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Procedimientos DEL área (no “relacionados con” cualquier puesto del área).
     * Incluye:
     * - responsable = puesto del área
     * - nombre/folio que menciona el tema del área
     * No usa puestos_relacionados (eso mezclaba Nómina, Cierre de Mes, etc.).
     */
    private function searchElementosOfArea(
        array $areaIds,
        array $topicTerms = [],
        int $limit = 120,
        ?array $tipos = null
    ): Collection {
        $puestoIds = $this->puestoIdsForAreaIds($areaIds);
        $found = collect();

        if (!empty($puestoIds)) {
            $byResponsable = $this->baseCatalogElementoQuery($tipos)
                ->whereIn('puesto_responsable_id', $puestoIds)
                ->orderBy('nombre_elemento')
                ->limit($limit)
                ->get();
            $found = $found->merge($byResponsable);
        }

        $topicTerms = array_values(array_unique(array_filter(array_map(
            fn ($t) => $this->foldAccents(trim((string) $t)),
            $topicTerms
        ), fn ($t) => mb_strlen($t) >= 3)));

        // Alias del área Jurídico (procedimientos bajo el proceso PAA03, fianzas, etc.).
        $topicJoined = implode(' ', $topicTerms);
        if (preg_match('/\bjurid/u', $topicJoined)) {
            array_push($topicTerms, 'fianzas', 'seguros', 'paa03');
        }

        if (!empty($topicTerms)) {
            $found = $found->merge($this->searchCatalogElementos($topicTerms, $limit, $tipos));
        }

        // Hijos de procesos del área (ej. PAA03 → PAA03-PR01, PAA03-PR02).
        if (!empty($puestoIds) || !empty($topicTerms)) {
            $procesosPadre = Elemento::query()
                ->where('status', 'Publicado')
                ->where('active', true)
                ->whereHas('tipoElemento', fn ($q) => $q->where('nombre', 'Proceso'))
                ->where(function ($q) use ($puestoIds, $topicTerms) {
                    if (!empty($puestoIds)) {
                        $q->whereIn('puesto_responsable_id', $puestoIds);
                    }
                    if (!empty($topicTerms)) {
                        $nombreFold = $this->sqlUnaccentLower('nombre_elemento');
                        $folioFold = $this->sqlUnaccentLower('folio_elemento');
                        $q->orWhere(function ($inner) use ($topicTerms, $nombreFold, $folioFold) {
                            foreach ($topicTerms as $term) {
                                if (mb_strlen($term) < 3) {
                                    continue;
                                }
                                $like = '%' . $term . '%';
                                $inner->orWhereRaw("{$nombreFold} LIKE ?", [$like])
                                    ->orWhereRaw("{$folioFold} LIKE ?", [$like]);
                            }
                        });
                    }
                })
                ->pluck('id_elemento')
                ->all();

            if (!empty($procesosPadre)) {
                $hijos = $this->baseCatalogElementoQuery($tipos)
                    ->whereIn('elemento_padre_id', $procesosPadre)
                    ->orderBy('nombre_elemento')
                    ->limit($limit)
                    ->get();
                $found = $found->merge($hijos);
            }
        }

        return $found->unique('id_elemento')->sortBy('nombre_elemento')->values()->take($limit);
    }

    /**
     * @deprecated Preferir searchElementosOfArea para listados "de [área]".
     */
    private function searchElementosByAreaIds(array $areaIds, int $limit = 120, ?array $tipos = null): Collection
    {
        return $this->searchElementosOfArea($areaIds, [], $limit, $tipos);
    }

    /**
     * Seguimiento de un listado por puesto: no debe ir al documento en foco.
     */
    private function isCatalogListFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/^(pero\s+)?(toda la lista|la lista completa|lista completa|el listado completo|listado completo|completa|completos?|todos|todas|dame todos|dame todas|m[aá]s|faltan)\b/u',
            $q
        );
    }

    /**
     * Seguimiento del listado por área: "¿son todos los del área?", "hay más para el área?".
     */
    /**
     * ¿La consulta nombra por sí misma el área, unidad o puesto que quiere listar?
     *
     * Sirve para distinguir un listado nuevo ("procedimientos del área de TI") de un
     * seguimiento del listado anterior ("¿son todos?", "toda la lista"), que sí debe
     * reusar el catalog_state en caché. No modifica ninguna detección existente:
     * solo responde si hay un objetivo explícito en el texto.
     */
    private function mencionaObjetivoExplicitoDeCatalogo(string $originalQuery, string $searchQuery): bool
    {
        foreach ([$originalQuery, $searchQuery] as $q) {
            $q = trim((string) $q);
            if ($q === "") {
                continue;
            }

            if ($this->findExplicitAreasInQuery($q)->isNotEmpty()) {
                return true;
            }

            if ($this->findAreasMentionedInQuery($q)->isNotEmpty()) {
                return true;
            }

            if ($this->findUnidadesMentionedInQuery($q)->isNotEmpty()) {
                return true;
            }

            if ($this->resolveExactPuestoFromQuery($q)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }
    private function isAreaCatalogFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        $hablaArea = (bool) preg_match('/\b([aá]rea|esa [aá]rea|el [aá]rea|del [aá]rea|para el [aá]rea)\b/u', $q);
        $pideConfirmacion = (bool) preg_match(
            '/\b(son todos|todas? las?|hay m[aá]s|faltan|solo (esos|esas|ese)|es todo|existen para)\b/u',
            $q
        );
        $hablaLista = (bool) preg_match('/\b(procedimientos?|procesos?|documentos?|lista|listado)\b/u', $q);

        return ($hablaArea && ($pideConfirmacion || $hablaLista))
            || ($pideConfirmacion && $hablaLista && preg_match('/\b(para|del|de)\b/u', $q));
    }

    private function isPersonalIdentityQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(c[oó]mo me llamo|como me llamo|qui[eé]n soy|quien soy|cu[aá]l es mi nombre|mi nombre\??|dime mi nombre)\b/u',
            $q
        );
    }

    /**
     * Temas que NUNCA deben responderse con el PDF en foco.
     */
    private function isHardTopicSwitchQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if ($this->isPersonalIdentityQuery($q) || $this->isCompanyOrgQuery($q) || $this->isPeopleOrOrgDirectoryQuery($q)) {
            return true;
        }

        // Listados generales / por área / procesos del sistema.
        if (
            preg_match('/\b(listado|lista|cu[aá]les|qu[eé] procesos|que procesos|procesos existen|procesos hay)\b/u', $q)
            && preg_match('/\b(procesos?|procedimientos?|[aá]rea|empresa|unidad|puesto)\b/u', $q)
        ) {
            return true;
        }

        // "propios del director…" / salir del PDF en foco.
        if (preg_match('/\b(propios?|listado de procedimientos)\b/u', $q)
            && preg_match('/\b(director|puesto|[aá]rea|jurid|compras?|calidad|ti)\b/u', $q)
        ) {
            return true;
        }

        return false;
    }

    private function generatePersonalIdentityResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $user = auth()->user();
        $nombre = $user->name ?? null;

        // Intentar nombre del empleado por correo (más completo).
        if ($user && !empty($user->email)) {
            $emp = Empleados::where('correo', $user->email)
                ->whereNull('deleted_at')
                ->first(['nombres', 'apellido_paterno', 'apellido_materno']);
            if ($emp) {
                $nombreEmp = trim(implode(' ', array_filter([
                    $emp->nombres,
                    $emp->apellido_paterno,
                    $emp->apellido_materno,
                ])));
                if ($nombreEmp !== '') {
                    $nombre = $nombreEmp;
                }
            }
        }

        if ($nombre) {
            $msg = "En el sistema apareces como **{$nombre}**.\n\n"
                . "Puedes escribirlo libre: un procedimiento, un área, un puesto o un nombre.\n"
                . "Solo te respondo con lo que está registrado.";
        } else {
            $msg = "No pude leer tu nombre de la sesión. ¿Estás logueado?\n\n"
                . "Mientras tanto puedo ayudarte con procedimientos, listados por área o el directorio.";
        }

        \Cache::put($this->getOfferMenuKey($sessionId, $userId), [
            'options' => ['mis_procedimientos', 'directorio', 'documento'],
            'asked_at' => time(),
        ], 600);

        return [
            'response' => $msg,
            'method' => 'directory_personal_identity',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'chips' => [
                ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ['label' => 'Directorio', 'query' => 'directorio'],
                ['label' => 'Consultar un documento', 'query' => 'consultar un documento'],
            ],
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'directory_personal_identity',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * ¿La pregunta pide un CORREO ELECTRÓNICO del directorio?
     *
     * Modos soportados (bloque aditivo, no toca identidad personal ni directorio):
     *  - Propio:  "cuál es mi correo", "qué correo tengo registrado", "mi email".
     *  - Persona: "correo de Juan Pérez", "dame el email de María López".
     *  - Puesto:  "correo del coordinador de TI", "email del director jurídico".
     *  - Área:    "correos del área de calidad", "dame los correos de compras".
     */
    private function isEmailDirectoryQuery(string $query): bool
    {
        $q = $this->foldAccents($query);
        if ($q === '') {
            return false;
        }

        // Búsqueda inversa: la pregunta trae la dirección escrita ("¿de quién es ssauri@…?").
        if ($this->extraerDireccionDeCorreo($query) !== null) {
            return true;
        }

        if (!preg_match('/\b(correos?|e-?mails?|mails?)\b/u', $q)) {
            return false;
        }

        // "procedimiento de correo electrónico", "formato de correo": es documental, no directorio.
        // Excepción: la matriz de responsabilidades sí liga documento ↔ personas
        // ("correo del responsable del procedimiento X").
        if (
            preg_match(
                '/\b(procedimientos?|documentos?|folios?|politicas?|lineamientos?|manuales?|manual|'
                . 'procesos?|formatos?|plantillas?|elementos?|versiones?)\b/u',
                $q
            )
            && !$this->mencionaRolDeMatriz($q)
        ) {
            return false;
        }

        // Acción sobre un correo dentro de un flujo ("enviar correo al cliente"): no es directorio.
        if (
            preg_match('/\b(enviar|envio|envia\w*|mandar|manda\w*|notificar|notifica\w*|responder|responde\w*|'
                . 'adjuntar|adjunta\w*|redactar|redacta\w*|firmar|firma\w*|reenviar|contestar)\b/u', $q)
            && !preg_match('/\b(correos?|e-?mails?|mails?)\s+(electronicos?\s+)?(de|del)\b/u', $q)
        ) {
            return false;
        }

        $pideDato = (bool) preg_match(
            '/\b(cual|cuales|que|dame|dime|damelo|necesito|quiero|quisiera|proporciona\w*|comparte\w*|'
            . 'muestra\w*|indica\w*|contactar|contacto|conoces|sabes|obtener|consultar|busca\w*|'
            . 'tiene|tienen|tienes|tengo|cuenta|posee|registrad[oa]s?|aparece|hay|existe)\b/u',
            $q
        );
        $pideDeAlguien = (bool) preg_match(
            '/\b(correos?|e-?mails?|mails?)\s+(electronicos?\s+)?(de|del|para)\b/u',
            $q
        );
        // "eduardo cong tiene correo?", "¿cuenta con email?": sujeto + verbo de posesión.
        $preguntaSiTiene = (bool) preg_match(
            '/\b(tiene|tienen|tienes|cuenta con|posee|hay|existe|sabes)\b[^.?!]{0,30}\b(correos?|e-?mails?|mails?)\b/u',
            $q
        ) || (bool) preg_match(
            '/\b(correos?|e-?mails?|mails?)\b[^.?!]{0,30}\b(tiene|tienen|registrad[oa]s?|asignad[oa]s?)\b/u',
            $q
        );

        if ($preguntaSiTiene || $this->isEmailFollowUpQuery($query)) {
            return true;
        }

        return $pideDato || $pideDeAlguien || $this->isPersonalEmailQuery($query);
    }

    /**
     * Variante "mi propio correo" del modo anterior.
     */
    private function isPersonalEmailQuery(string $query): bool
    {
        $q = $this->foldAccents($query);
        if ($q === '') {
            return false;
        }

        return (bool) preg_match('/\bmis?\s+(correos?|e-?mails?|mails?)\b/u', $q)
            || (bool) preg_match('/\b(correos?|e-?mails?|mails?)\s+(electronicos?\s+)?(mio|mios|mia|tengo)\b/u', $q)
            || (bool) preg_match('/\bcon\s+que\s+(correo|e-?mail|mail)\b/u', $q)
            || (bool) preg_match('/\b(correo|e-?mail|mail)\s+(electronico\s+)?(tengo|esta)\s+registrad[oa]\b/u', $q)
            || (bool) preg_match('/\bcorreo\s+(de\s+)?mi\s+(usuario|cuenta|sesion|perfil)\b/u', $q)
            // "tengo correo?", "yo tengo correo", "tengo algún email registrado"
            || (bool) preg_match(
                '/\b(yo\s+)?tengo\s+(algun[a]?\s+)?(correos?|e-?mails?|mails?)\b/u',
                $q
            )
            // "yo" como sujeto de la pregunta: "yo cuento con correo?"
            || (bool) preg_match(
                '/\byo\b[^.?!]{0,25}\b(correos?|e-?mails?|mails?)\b|\b(correos?|e-?mails?|mails?)\b[^.?!]{0,25}\byo\b/u',
                $q
            )
            // "el correo mío", "cuál es el mío"
            || (bool) preg_match('/\b(correos?|e-?mails?|mails?)\s+(electronicos?\s+)?mi[oa]s?\b/u', $q);
    }

    /**
     * Punto de entrada del modo correo: decide propio vs. búsqueda en el directorio.
     */
    private function generateEmailDirectoryResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        $documentoEnFocoId = null
    ): array {
        if ($this->isPersonalEmailQuery($query)) {
            return $this->buildDirectoryChatResponse(
                $query,
                $this->buildOwnEmailMessage(),
                'directory_email_self',
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Búsqueda inversa: "¿de quién es ssauri@proser.com.mx?".
        $direccion = $this->extraerDireccionDeCorreo($query);
        if ($direccion !== null) {
            $duenio = $this->resolverDuenioDeCorreo($direccion);

            $msg = $duenio
                ? "**{$direccion}** es de **{$duenio['nombre']}**"
                    . (!empty($duenio['puesto']) ? " ({$duenio['puesto']})" : '') . '.'
                : "No encontré a nadie con el correo **{$direccion}** en el directorio.";

            return $this->buildDirectoryChatResponse(
                $query,
                $msg,
                'directory_email_reverse',
                $startTime,
                $userId,
                $sessionId
            );
        }

        [$contactos, $criterio] = $this->findEmpleadosForEmailQuery(
            $query,
            $sessionId,
            $userId,
            $documentoEnFocoId
        );

        $msg = $this->buildEmailListMessage($contactos, $criterio);

        if ($contactos->isEmpty()) {
            // Seguimiento ("dame su correo") sin nadie en el hilo: pedir a quién.
            if ($this->isEmailFollowUpQuery($query)) {
                $msg = "No tengo claro de quién me pides el correo. Dime el nombre o el puesto, "
                    . "por ejemplo \"correo de Juan Pérez\" o \"correo del jefe jurídico\".";
            } else {
                // Sin resultado exacto: ofrecer nombres parecidos en vez de dejar sin salida.
                $tokensObjetivo = $this->tokensNombreParaCorreo($this->stripEmailQueryNoise($query));
                $parecidos = $this->sugerirEmpleadosPorNombre($tokensObjetivo);

                // Si tampoco hay parciales, probar por parecido ortográfico ("Ordoñes").
                if ($parecidos->isEmpty()) {
                    $parecidos = $this->sugerirContactosParecidos($tokensObjetivo);
                }

                if ($parecidos->isNotEmpty()) {
                    $lineas = $parecidos->map(
                        fn ($c) => '- **' . $c['nombre'] . '**: ' . $c['correo']
                    )->implode("\n");

                    $msg .= "\n\n¿Te refieres a alguno de estos?\n\n" . $lineas;
                }
            }
        }

        // Deja el hilo listo para el siguiente turno ("¿y el de su jefe?", "dame su correo").
        $this->rememberEmailState($contactos, $criterio, $sessionId, $userId);

        return $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_email_lookup',
            $startTime,
            $userId,
            $sessionId
        );
    }

    /**
     * ¿Es un seguimiento que se apoya en el turno anterior? "dame su correo", "y el correo?".
     */
    private function isEmailFollowUpQuery(string $query): bool
    {
        $q = $this->foldAccents($query);
        $q = trim($q, " \t\n\r\0\x0B?¿!.");
        if ($q === '' || $this->isPersonalEmailQuery($query)) {
            return false;
        }

        if (!preg_match('/\b(correos?|e-?mails?|mails?)\b/u', $q)) {
            return false;
        }

        // Deíctico explícito: "su correo", "el correo de ella", "de esa/esta persona".
        if (preg_match(
            '/\b(su|sus|suyo|suya|de\s+el|de\s+ella|de\s+ellos|de\s+ellas|de\s+esa\s+persona|'
            . 'de\s+esta\s+persona|de\s+esas?\s+personas?|de\s+estas?\s+personas?|de\s+ese|de\s+esa|'
            . 'de\s+este|de\s+esta|del\s+mismo|de\s+la\s+misma|de\s+ambos|de\s+todos\s+ellos)\b/u',
            $q
        )) {
            return true;
        }

        // Frase suelta sin objetivo: "y el correo", "dame el correo", "correo".
        return (bool) preg_match(
            '/^(y\s+)?(dame|dime|pasame|comparte\w*|muestra\w*|necesito|quiero)?\s*'
            . '(el|los|sus?)?\s*(correos?|e-?mails?|mails?)(\s+electronicos?)?$/u',
            $q
        );
    }

    private function getEmailStateKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_email_state_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    /**
     * Ventana en la que se confía ciegamente en el último correo resuelto para
     * responder un seguimiento ("dame su correo"). Pasado este tiempo, es más
     * probable que la conversación haya avanzado a otro tema (un listado de
     * procedimientos, otro puesto...) que sigue siendo un "seguimiento" válido
     * en la forma de la frase pero ya no se refiere a esa misma persona.
     *
     * El módulo de correo tiene un `return` temprano que nunca pasa por el
     * mecanismo general que actualiza el puesto "en foco" del chatbot, así que
     * sin este límite de tiempo el correo recordado se queda pegado indefinidamente
     * (hasta los 600s de caché) sin importar cuántos temas distintos se hayan
     * tocado después.
     */
    private const EMAIL_STATE_FRESH_SECONDS = 90;

    /**
     * Guarda a quién se acaba de resolver, para encadenar seguimientos.
     */
    private function rememberEmailState(Collection $contactos, string $criterio, $sessionId, $userId): void
    {
        if ($contactos->isEmpty()) {
            return;
        }

        \Cache::put(
            $this->getEmailStateKey($sessionId, $userId),
            [
                'contactos' => $contactos->take(25)->values()->all(),
                'criterio' => $criterio,
                'asked_at' => time(),
            ],
            600
        );
    }

    /**
     * Resuelve el objetivo del seguimiento con lo último que se habló en el hilo.
     *
     * Prioridad:
     *  1. El propio correo resuelto, PERO solo si se pidió hace poco
     *     (EMAIL_STATE_FRESH_SECONDS) — un seguimiento inmediato ("correo de
     *     Said Sauri" → "y su correo?") debe ganar aunque exista un catalog_state
     *     más viejo de fondo.
     *  2. El puesto en foco del directorio (catalog_state), que sí se refresca en
     *     casi cualquier otro turno de la conversación (listados, documentos,
     *     directorio) y por eso es más confiable una vez que el correo recordado
     *     ya no es reciente.
     *  3. Como último recurso, el correo viejo igual se usa antes que no responder
     *     nada — mejor una respuesta posiblemente desactualizada que un vacío.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: string}
     */
    private function resolveEmpleadosFromChatContext($sessionId, $userId, $documentoEnFocoId = null): array
    {
        // El responsable del documento que se estaba viendo justo antes de este turno.
        //
        // Va PRIMERO, sin ventana de tiempo, porque su sola presencia ya prueba que es
        // más reciente que cualquier otra cosa guardada: TODA rama que cambia de tema
        // (este mismo modo de correo, el directorio, un listado por área…) hace
        // `Cache::forget` del documento en foco antes de responder. Si esta variable
        // trae algo, es porque el turno inmediato anterior fue una pregunta sobre ESE
        // documento — nada más nuevo pudo haberlo pisado.
        //
        // El id llega por parámetro (no se relee de caché aquí) porque el propio
        // despacho del modo correo hace ese `Cache::forget` justo antes de entrar a
        // esta rama, así que para cuando resolveEmpleadosFromChatContext se ejecuta
        // esa caché ya no existe.
        $delDocumentoEnFoco = $this->responsableDelDocumentoEnFoco($documentoEnFocoId);
        if ($delDocumentoEnFoco[0]->isNotEmpty()) {
            return $delDocumentoEnFoco;
        }

        $emailState = \Cache::get($this->getEmailStateKey($sessionId, $userId));
        $delTurno = collect();
        $criterioEmail = '';

        if (is_array($emailState) && !empty($emailState['contactos'])) {
            $delTurno = collect($emailState['contactos'])
                ->filter(fn ($c) => is_array($c) && !empty($c['correo']))
                ->values();
            $criterioEmail = (string) ($emailState['criterio'] ?? '');
        }

        $esReciente = is_array($emailState)
            && (time() - (int) ($emailState['asked_at'] ?? 0)) <= self::EMAIL_STATE_FRESH_SECONDS;

        if ($delTurno->isNotEmpty() && $esReciente) {
            return [$delTurno, $criterioEmail];
        }

        // El directorio deja aquí el puesto del que se acaba de hablar ("Jefe Jurídico").
        // Si trae un filtro de área activo (area_ids), el propio mecanismo que lo mantiene
        // se niega a pisarlo turno a turno (ver rememberPuestoCatalogStateFromTurn), así que
        // puede quedarse apuntando a un puesto de hace rato aunque la conversación ya haya
        // señalado a otro. Por eso ese caso vale menos que el resto.
        $catalogState = \Cache::get($this->getCatalogStateKey($sessionId, $userId));
        $catalogTieneFiltroArea = is_array($catalogState) && !empty($catalogState['area_ids']);

        if (!$catalogTieneFiltroArea) {
            $delCatalogo = $this->contactosDesdeCatalogState($catalogState);
            if ($delCatalogo[0]->isNotEmpty()) {
                return $delCatalogo;
            }
        }

        // catalog_state con filtro de área, como último intento antes del correo viejo.
        if ($catalogTieneFiltroArea) {
            $delCatalogo = $this->contactosDesdeCatalogState($catalogState);
            if ($delCatalogo[0]->isNotEmpty()) {
                return $delCatalogo;
            }
        }

        // Nada fresco: mejor el correo viejo que nada.
        if ($delTurno->isNotEmpty()) {
            return [$delTurno, $criterioEmail];
        }

        return [collect(), ''];
    }

    /**
     * Empleados del puesto que guarda el catalog_state del directorio.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: string}
     */
    private function contactosDesdeCatalogState(?array $catalogState): array
    {
        if (!is_array($catalogState) || empty($catalogState['puesto_ids'])) {
            return [collect(), ''];
        }

        $ids = array_map('intval', (array) $catalogState['puesto_ids']);
        $emps = $this->contactosPorPuestoIds($ids);

        if ($emps->isEmpty()) {
            return [collect(), ''];
        }

        $nombres = (array) ($catalogState['puesto_nombres'] ?? []);
        $criterio = !empty($nombres) ? 'el puesto de ' . implode(', ', $nombres) : '';

        return [$emps, $criterio];
    }

    /**
     * Responsable del documento que estaba en foco justo antes de este turno, si lo hay.
     * Recibe el id ya resuelto por el llamador (ver nota en resolveEmpleadosFromChatContext
     * sobre por qué no se relee de caché aquí).
     *
     * @return array{0: \Illuminate\Support\Collection, 1: string}
     */
    private function responsableDelDocumentoEnFoco($elementoId): array
    {
        if (empty($elementoId)) {
            return [collect(), ''];
        }

        $elemento = Elemento::find($elementoId);
        if (!$elemento) {
            return [collect(), ''];
        }

        // Misma fuente que usa la respuesta "¿quién es el responsable de X?": hay
        // fichas con `puesto_responsable_id` vacío (duplicados de un folio, versiones
        // en firmas vs. publicadas...) donde el responsable solo consta en la sección
        // "Responsable del elemento" del Word. Leer solo la columna de BD, como hacía
        // antes, dejaba a este documento sin responsable aunque la respuesta que Bob
        // acababa de mostrar sí lo traía.
        $resuelto = $this->resolveElementoResponsableNombre($elemento);
        $nombrePuesto = $resuelto['nombre'] ?? null;
        if (!$nombrePuesto) {
            return [collect(), ''];
        }

        $puestoId = (int) ($elemento->puesto_responsable_id ?? 0);
        if (!$puestoId) {
            // Nombre sacado del texto del documento, puede venir con ruido detrás
            // ("Director de Desarrollo de Negocios 9" — un número de tabla/nota que
            // se coló en la extracción). resolveExactPuestoFromQuery ya sabe rescatar
            // el nombre real de puesto contenido dentro de una frase más larga; se usa
            // también para limpiar el nombre que se muestra al usuario.
            $puesto = $this->resolveExactPuestoFromQuery($nombrePuesto)->first();
            $puestoId = $puesto ? (int) $puesto->id_puesto_trabajo : 0;
            if ($puesto) {
                $nombrePuesto = $puesto->nombre;
            }
        }

        if (!$puestoId) {
            return [collect(), ''];
        }

        $emps = $this->contactosPorPuestoIds([$puestoId]);
        if ($emps->isEmpty()) {
            return [collect(), ''];
        }

        $titulo = $elemento->nombre_elemento ?: ($elemento->folio_elemento ?: 'ese documento');
        $criterio = 'el responsable de ' . $titulo . ' (' . $nombrePuesto . ')';

        return [$emps, $criterio];
    }

    private function buildOwnEmailMessage(): string
    {
        $user = auth()->user();
        if (!$user) {
            return "No pude leer tu sesión, así que no tengo tu correo. Inicia sesión y vuelve a preguntármelo.";
        }

        $correoCuenta = trim((string) ($user->email ?? ''));
        if ($correoCuenta === '') {
            return "No encontré un correo registrado en tu cuenta. Pídele al administrador o a Recursos Humanos que lo registre.";
        }

        // El expediente se empareja por nombre: `empleados.correo` puede venir genérico.
        $emp = $this->empleadoPorNombreDeUsuario((string) ($user->name ?? ''))
            ?? Empleados::where('correo', $correoCuenta)->whereNull('deleted_at')->first();

        $nombre = $emp
            ? $this->nombreCompletoEmpleado($emp)
            : trim((string) ($user->name ?? ''));

        $msg = $nombre !== ''
            ? "**{$nombre}**, tu correo registrado es **{$correoCuenta}**."
            : "Tu correo registrado es **{$correoCuenta}**.";

        if ($emp) {
            $puesto = optional($emp->puestoTrabajo)->nombre;
            if ($puesto) {
                $msg .= "\n\nPuesto: {$puesto}.";
            }
        }

        $msg .= "\n\nTambién puedo darte el correo de alguien más:\n\n"
            . "- \"correo de Juan Pérez\"\n"
            . "- \"correo del coordinador de TI\"\n"
            . "- \"correos del área de calidad\"";

        return $msg;
    }

    /**
     * Expediente de empleado que corresponde al nombre de un usuario del sistema.
     */
    private function empleadoPorNombreDeUsuario(string $nombreUsuario)
    {
        $clave = $this->claveNombreParaCorreo($nombreUsuario);
        if ($clave === '') {
            return null;
        }

        return Empleados::query()
            ->with('puestoTrabajo')
            ->get()
            ->first(fn ($emp) => $this->claveNombreParaCorreo($this->nombreCompletoEmpleado($emp)) === $clave);
    }

    /**
     * Resuelve a quién le pide el correo: puesto, persona o área.
     *
     * Los correos individuales viven en `users`; `empleados` aporta puesto y nombre completo
     * (y su columna `correo` sirve de respaldo). Por eso todo se normaliza a "contactos":
     * ['nombre' => …, 'correo' => …, 'puesto' => …].
     *
     * @return array{0: \Illuminate\Support\Collection, 1: string} [contactos, criterio]
     */
    private function findEmpleadosForEmailQuery(
        string $query,
        $sessionId = null,
        $userId = null,
        $documentoEnFocoId = null
    ): array {
        $limpio = $this->stripEmailQueryNoise($query);
        $qFold = $this->foldAccents($query);

        // 1) Matriz de responsabilidades: "correo del responsable del procedimiento X".
        if ($this->mencionaRolDeMatriz($qFold)) {
            [$deMatriz, $critMatriz] = $this->contactosDesdeMatriz($query, $limpio, $qFold);
            if ($deMatriz->isNotEmpty()) {
                return [$deMatriz, $critMatriz];
            }
        }

        // 2) Puesto: "correo del coordinador de TI".
        $puestos = $this->findPuestosMentionedInQuery($limpio);
        if ($puestos->isNotEmpty()) {
            $ids = $puestos->pluck('id_puesto_trabajo')->map(fn ($id) => (int) $id)->all();
            $porPuesto = $this->contactosPorPuestoIds($ids);

            if ($porPuesto->isNotEmpty()) {
                $criterio = $puestos->count() === 1
                    ? 'el puesto de ' . $puestos->first()->nombre
                    : 'los puestos: ' . $puestos->pluck('nombre')->take(4)->implode(', ');

                return [$porPuesto, $criterio];
            }
        }

        // 3) Cargo genérico en plural: "correos de los directores", "de los coordinadores".
        [$puestosRol, $etiquetaRol] = $this->puestosPorRolGenerico($qFold);
        if ($puestosRol->isNotEmpty()) {
            $porRol = $this->contactosPorPuestoIds(
                $puestosRol->pluck('id_puesto_trabajo')->map(fn ($id) => (int) $id)->all()
            );

            if ($porRol->isNotEmpty()) {
                return [$porRol, $etiquetaRol];
            }
        }

        // 4) Persona: "correo de Juan Pérez" (busca en empleados y en usuarios del sistema).
        $tokensNombre = $this->tokensNombreParaCorreo($limpio);
        $porNombre = $this->buscarContactosPorNombre($tokensNombre);
        if ($porNombre->isNotEmpty()) {
            return [$porNombre, $this->criterioNombre($tokensNombre)];
        }

        // 5) Área: "correos del área de calidad".
        $areas = $this->findExplicitAreasInQuery($limpio);
        if ($areas->isEmpty() && preg_match('/\b[aá]reas?\b/u', $qFold)) {
            // El match laxo sólo se usa si la pregunta habla de un área: "puestos"
            // se parece a "presupuestos" y arrastraba a toda esa área.
            $areas = $this->findAreasMentionedInQuery($limpio);
        }

        if ($areas->isNotEmpty()) {
            $puestoIds = $this->puestoIdsForAreaIds(
                $areas->pluck('id_area')->map(fn ($id) => (int) $id)->all()
            );

            if (!empty($puestoIds)) {
                $porArea = $this->contactosPorPuestoIds($puestoIds);

                if ($porArea->isNotEmpty()) {
                    return [$porArea, 'el área de ' . $areas->first()->nombre];
                }
            }
        }

        // 6) Unidad de negocio: "correos de Konkret", "correos de la unidad corporativo".
        $unidades = $this->findUnidadesMentionedInQuery($limpio);
        if ($unidades->isNotEmpty()) {
            $porUnidad = $this->contactosPorPuestoIds(
                $this->puestoIdsForUnidadIds(
                    $unidades->pluck('id_unidad_negocio')->map(fn ($id) => (int) $id)->all()
                )
            );

            if ($porUnidad->isNotEmpty()) {
                return [$porUnidad, 'la unidad ' . $unidades->first()->nombre];
            }
        }

        // 7) Seguimiento del hilo, como último recurso: "dame su correo" tras hablar
        // de un puesto o persona. Va al final para que un objetivo explícito en la
        // misma frase ("correo de el jefe de compras") nunca lo pise: "de el" se
        // confunde con el pronombre "de él" si se revisa antes de buscar el objetivo real.
        if ($this->isEmailFollowUpQuery($query)) {
            [$delHilo, $critHilo] = $this->resolveEmpleadosFromChatContext(
                $sessionId,
                $userId,
                $documentoEnFocoId
            );
            if ($delHilo->isNotEmpty()) {
                return [$delHilo, $critHilo];
            }
        }

        // Sin coincidencia: se nombra el objetivo (ya sin muletillas) en la respuesta.
        return [collect(), $this->criterioNombre($tokensNombre)];
    }

    /**
     * Empleados vigentes (con o sin correo propio: el bueno suele estar en `users`).
     */
    private function empleadosConCorreoQuery()
    {
        return Empleados::query()->with('puestoTrabajo');
    }

    /**
     * Usuarios del sistema, que son la fuente real del correo.
     *
     * @return \Illuminate\Support\Collection listas por nombre normalizado
     */
    private function catalogoUsuariosParaCorreo(): Collection
    {
        return Cache::remember('chat_usuarios_correo_v1', 300, function () {
            return \App\Models\User::query()
                ->select('id', 'name', 'email')
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->get()
                ->map(fn ($u) => [
                    'nombre' => trim((string) $u->name),
                    'correo' => trim((string) $u->email),
                    'fold' => $this->foldAccents((string) $u->name),
                    'clave' => $this->claveNombreParaCorreo((string) $u->name),
                ])
                ->values();
        });
    }

    /**
     * Clave insensible al orden: "Ordoñez Tatiana" y "Tatiana Ordoñez" comparten clave.
     */
    private function claveNombreParaCorreo(string $nombre): string
    {
        $tokens = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $this->foldAccents($nombre)) ?: [],
            fn ($t) => mb_strlen($t) >= 2
        ));
        sort($tokens);

        return implode(' ', $tokens);
    }

    /**
     * Correo del usuario del sistema que corresponde a ese nombre completo.
     */
    private function correoUsuarioPorNombre(string $nombreCompleto): ?string
    {
        if (trim($nombreCompleto) === '') {
            return null;
        }

        $usuarios = $this->catalogoUsuariosParaCorreo();
        $fold = $this->foldAccents($nombreCompleto);

        $exacto = $usuarios->firstWhere('fold', $fold);
        if ($exacto) {
            return $exacto['correo'];
        }

        $porClave = $usuarios->firstWhere('clave', $this->claveNombreParaCorreo($nombreCompleto));

        return $porClave ? $porClave['correo'] : null;
    }

    /**
     * Empleado → contacto, con el correo de `users` cuando existe.
     */
    private function contactoDesdeEmpleado($empleado): array
    {
        $nombre = $this->nombreCompletoEmpleado($empleado);

        return [
            'nombre' => $nombre,
            'correo' => $this->correoUsuarioPorNombre($nombre)
                ?? $this->correoRespaldoDeEmpleado($empleado),
            'puesto' => optional($empleado->puestoTrabajo)->nombre,
        ];
    }

    /**
     * `empleados.correo` sólo sirve de respaldo si es exclusivo de ese expediente:
     * un correo repetido en varios registros es un marcador de captura, no su correo,
     * y devolverlo le daría al usuario la dirección de otra persona.
     */
    private function correoRespaldoDeEmpleado($empleado): string
    {
        $correo = trim((string) ($empleado->correo ?? ''));
        if ($correo === '') {
            return '';
        }

        $repeticiones = Cache::remember('chat_correos_empleados_repetidos_v1', 300, function () {
            return Empleados::query()
                ->whereNotNull('correo')
                ->where('correo', '<>', '')
                ->get(['correo'])
                ->groupBy(fn ($e) => mb_strtolower(trim((string) $e->correo)))
                ->map(fn ($g) => $g->count())
                ->all();
        });

        $clave = mb_strtolower($correo);
        if (($repeticiones[$clave] ?? 0) > 1) {
            return '';
        }

        // Tampoco vale si en `users` ese correo pertenece a otra persona.
        $duenio = $this->catalogoUsuariosParaCorreo()
            ->first(fn ($u) => mb_strtolower($u['correo']) === $clave);

        if ($duenio && $duenio['clave'] !== $this->claveNombreParaCorreo($this->nombreCompletoEmpleado($empleado))) {
            return '';
        }

        return $correo;
    }

    /**
     * @param \Illuminate\Support\Collection $empleados
     */
    private function contactosDesdeEmpleados($empleados): Collection
    {
        return $empleados
            ->map(fn ($emp) => $this->contactoDesdeEmpleado($emp))
            ->values();
    }

    /**
     * Puesto registrado para un nombre, para enriquecer contactos que sólo existen en `users`.
     */
    private function puestoPorNombreEmpleado(string $nombreCompleto): ?string
    {
        $mapa = Cache::remember('chat_puesto_por_nombre_v1', 300, function () {
            return Empleados::query()
                ->with('puestoTrabajo')
                ->get()
                ->mapWithKeys(fn ($emp) => [
                    $this->claveNombreParaCorreo($this->nombreCompletoEmpleado($emp))
                        => optional($emp->puestoTrabajo)->nombre,
                ])
                ->all();
        });

        return $mapa[$this->claveNombreParaCorreo($nombreCompleto)] ?? null;
    }

    private function contactosPorPuestoIds(array $puestoIds): Collection
    {
        if (empty($puestoIds)) {
            return collect();
        }

        return $this->contactosDesdeEmpleados(
            $this->empleadosConCorreoQuery()
                ->whereIn('puesto_trabajo_id', $puestoIds)
                ->orderBy('apellido_paterno')
                ->get()
        );
    }

    /**
     * ¿La pregunta apunta a la matriz de responsabilidades (documento ↔ puestos)?
     */
    private function mencionaRolDeMatriz(string $queryFold): bool
    {
        return (bool) preg_match(
            '/\b(responsables?|encargad[oa]s?|participan?|participantes?|participa|'
            . 'relacionad[oa]s?|involucrad[oa]s?|matriz|responsabilidades?)\b/u',
            $queryFold
        );
    }

    /**
     * Correos de quienes figuran en la matriz de un documento.
     *
     * Por defecto entrega el puesto responsable de la ficha; si se piden todos los
     * involucrados suma `puestos_relacionados` y la tabla `puestos_relacion`.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: string}
     */
    private function contactosDesdeMatriz(string $query, string $limpio, string $queryFold): array
    {
        $elemento = $this->resolverElementoParaCorreo($query, $limpio);
        if (!$elemento) {
            return [collect(), ''];
        }

        $pideTodos = (bool) preg_match(
            '/\b(participan?|participantes?|participa|relacionad[oa]s?|involucrad[oa]s?|matriz|tod[oa]s)\b/u',
            $queryFold
        );
        $responsableId = (int) ($elemento->puesto_responsable_id ?? 0);
        $titulo = $elemento->nombre_elemento ?: ($elemento->folio_elemento ?: 'ese documento');

        if (!$pideTodos && $responsableId) {
            return [
                $this->contactosPorPuestoIds([$responsableId]),
                'el responsable de ' . $titulo,
            ];
        }

        $puestoIds = array_map('intval', (array) ($elemento->puestos_relacionados ?? []));

        foreach (\App\Models\Relaciones::where('elementoID', $elemento->id_elemento)->get() as $rel) {
            foreach ((array) $rel->puestos_trabajo as $pid) {
                $puestoIds[] = (int) $pid;
            }
        }

        if ($responsableId) {
            $puestoIds[] = $responsableId;
        }

        return [
            $this->contactosPorPuestoIds(array_values(array_unique(array_filter($puestoIds)))),
            'la matriz de ' . $titulo,
        ];
    }

    /**
     * Documento al que se refiere la pregunta: por folio (PAA01-PR05) o por nombre.
     */
    private function resolverElementoParaCorreo(string $query, string $limpio)
    {
        // Folio tal cual está en la BD: cubre formatos que el extractor genérico no
        // contempla (AD-20260107 tiene 8 dígitos y no cuadra con su patrón).
        $qFold = $this->foldAccents($query);
        $catalogo = Cache::remember('chat_folios_elementos_v1', 300, function () {
            return Elemento::query()
                ->whereNotNull('folio_elemento')
                ->where('folio_elemento', '<>', '')
                ->orderByRaw("CASE WHEN status = 'Publicado' THEN 0 ELSE 1 END")
                ->get(['id_elemento', 'folio_elemento'])
                ->map(fn ($e) => [
                    'id' => (int) $e->id_elemento,
                    'folio' => mb_strtolower(trim((string) $e->folio_elemento)),
                ])
                ->filter(fn ($e) => mb_strlen($e['folio']) >= 4)
                ->values();
        });

        $porFolio = $catalogo->first(fn ($e) => str_contains($qFold, $e['folio']));
        if ($porFolio) {
            return Elemento::find($porFolio['id']);
        }

        foreach ($this->extractFolioPatterns($query) as $folio) {
            $elemento = Elemento::query()
                ->whereRaw('LOWER(folio_elemento) LIKE ?', ['%' . mb_strtolower($folio) . '%'])
                ->orderByRaw("CASE WHEN status = 'Publicado' THEN 0 ELSE 1 END")
                ->first();

            if ($elemento) {
                return $elemento;
            }
        }

        // Por nombre: tokens largos del texto ya limpio, todos presentes en el título.
        $tokens = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $this->foldAccents($limpio)) ?: [],
            fn ($t) => mb_strlen($t) >= 4 && !in_array($t, [
                'responsable', 'responsables', 'encargado', 'encargada', 'participan', 'participa',
                'participantes', 'relacionados', 'relacionadas', 'involucrados', 'matriz', 'procedimiento',
                'procedimientos', 'documento', 'documentos', 'proceso', 'procesos', 'elemento', 'para',
                'sobre', 'todos', 'todas', 'quienes', 'quien',
            ], true)
        ));

        if (empty($tokens)) {
            return null;
        }

        $consulta = Elemento::query();
        foreach (array_slice($tokens, 0, 4) as $token) {
            $consulta->whereRaw('LOWER(nombre_elemento) LIKE ?', ['%' . $token . '%']);
        }

        $candidatos = $consulta
            ->orderByRaw("CASE WHEN status = 'Publicado' THEN 0 ELSE 1 END")
            ->get(['id_elemento', 'status']);

        $elegido = null;

        // Con un solo token genérico ("calidad", "compras") suele haber varios
        // documentos con ese nombre: sin certeza de cuál, mejor no adivinar.
        if ($candidatos->count() > 1) {
            $publicados = $candidatos->filter(fn ($e) => $e->status === 'Publicado');
            $elegido = $publicados->count() === 1 ? $publicados->first() : null;
        } else {
            $elegido = $candidatos->first();
        }

        // Se vuelve a cargar completo: el `get()` de arriba sólo trae las columnas
        // necesarias para decidir, y el llamador necesita el modelo entero
        // (puesto_responsable_id, puestos_relacionados…).
        return $elegido ? Elemento::find($elegido->id_elemento) : null;
    }

    /**
     * Puestos que pertenecen a esas unidades de negocio (campo simple o lista JSON).
     */
    private function puestoIdsForUnidadIds(array $unidadIds): array
    {
        $unidadIds = array_values(array_unique(array_filter(array_map('intval', $unidadIds))));
        if (empty($unidadIds)) {
            return [];
        }

        return PuestoTrabajo::query()
            ->where(function ($q) use ($unidadIds) {
                $q->whereIn('unidad_negocio_id', $unidadIds);
                foreach ($unidadIds as $uid) {
                    $q->orWhereJsonContains('unidades_negocio_ids', $uid)
                        ->orWhereJsonContains('unidades_negocio_ids', (string) $uid);
                }
            })
            ->pluck('id_puesto_trabajo')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Cargo genérico en plural: "los directores", "las coordinadoras", "los gerentes".
     *
     * `findPuestosMentionedInQuery` descarta estos cargos sueltos a propósito (son
     * demasiado amplios para listar procedimientos), pero para correos sí tienen sentido.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: string} [puestos, etiqueta]
     */
    private function puestosPorRolGenerico(string $queryFold): array
    {
        // "mi jefe", "mi coordinador": relación jerárquica personal ("mi jefe directo")
        // que la BD no modela. Confundirlo con el cargo genérico devolvería a las 12
        // jefaturas de la empresa como si fueran "tu jefe", que es simplemente falso.
        if (preg_match(
            '/\bmis?\s+(jefe|jefa|coordinador\w*|gerente\w*|director\w*|subdirector\w*|'
            . 'analista\w*|residente\w*|auxiliar\w*|supervisor\w*)\b/u',
            $queryFold
        )) {
            return [collect(), ''];
        }

        $roles = [
            'director' => ['/\bdirector(?:es|as|a)?\b/u', 'los directores'],
            'subdirector' => ['/\bsubdirector(?:es|as|a)?\b/u', 'los subdirectores'],
            'gerente' => ['/\bgerent(?:es|e|a)\b/u', 'los gerentes'],
            'coordinador' => ['/\bcoordinador(?:es|as|a)?\b/u', 'los coordinadores'],
            'jefe' => ['/\bjefes?\b|\bjefas?\b|\bjefaturas?\b/u', 'las jefaturas'],
            'analista' => ['/\banalistas?\b/u', 'los analistas'],
            'residente' => ['/\bresidentes?\b/u', 'los residentes'],
            'auxiliar' => ['/\bauxiliar(?:es)?\b/u', 'los auxiliares'],
        ];

        // Palabras sueltas alrededor del cargo que no cuentan como "calificador":
        // conectores, el propio módulo de correo y muletillas de la pregunta.
        $ruido = [
            'de', 'del', 'los', 'las', 'el', 'la', 'un', 'una', 'y', 'o', 'para', 'con',
            'correo', 'correos', 'email', 'emails', 'mail', 'mails', 'electronico', 'electronicos',
            'dame', 'dime', 'quiero', 'necesito', 'cual', 'cuales', 'son', 'es', 'hay', 'todos', 'todas',
        ];

        foreach ($roles as $raiz => [$patron, $etiqueta]) {
            if (!preg_match($patron, $queryFold)) {
                continue;
            }

            // Si tras quitar el cargo y el ruido queda algo ("marketing", "ventas"…),
            // el usuario pidió un puesto específico que no existe: no se debe sustituir
            // en silencio por el listado completo del cargo, eso sería engañoso.
            $resto = preg_replace($patron, ' ', $queryFold);
            $tokensResto = array_filter(
                preg_split('/[^\p{L}\p{N}]+/u', $resto) ?: [],
                fn ($t) => mb_strlen($t) >= 4 && !in_array($t, $ruido, true)
            );

            if (!empty($tokensResto)) {
                continue;
            }

            $puestos = $this->getPuestosCatalog()->filter(
                fn ($p) => str_starts_with($this->foldAccents((string) $p->nombre), $raiz)
            )->values();

            if ($puestos->isNotEmpty()) {
                return [$puestos, $etiqueta];
            }
        }

        return [collect(), ''];
    }

    /**
     * Dirección de correo escrita dentro de la pregunta, para la búsqueda inversa.
     */
    private function extraerDireccionDeCorreo(string $query): ?string
    {
        if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/u', $query, $m)) {
            return mb_strtolower(rtrim($m[0], '.?,;:'));
        }

        return null;
    }

    /**
     * ¿De quién es esta dirección? Busca en `users` y, si no, en `empleados`.
     */
    private function resolverDuenioDeCorreo(string $direccion): ?array
    {
        $direccion = mb_strtolower(trim($direccion));

        $usuario = $this->catalogoUsuariosParaCorreo()
            ->first(fn ($u) => mb_strtolower($u['correo']) === $direccion);

        if ($usuario) {
            return [
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'puesto' => $this->puestoPorNombreEmpleado($usuario['nombre']),
            ];
        }

        $empleado = Empleados::query()
            ->with('puestoTrabajo')
            ->whereRaw('LOWER(correo) = ?', [$direccion])
            ->first();

        return $empleado ? $this->contactoDesdeEmpleado($empleado) : null;
    }

    /**
     * Nombres parecidos cuando el escrito no existe: "Ordoñes" → "Ordoñez".
     *
     * Compara token a token contra el directorio con distancia de edición, así que
     * también entra con un solo apellido (donde la sugerencia por OR no aplicaba).
     */
    private function sugerirContactosParecidos(array $tokens, int $limite = 5): Collection
    {
        $tokens = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) >= 4));
        if (empty($tokens)) {
            return collect();
        }

        $candidatos = $this->catalogoUsuariosParaCorreo()
            ->map(fn ($u) => [
                'nombre' => $u['nombre'],
                'correo' => $u['correo'],
                'fold' => $u['fold'],
            ]);

        return $candidatos
            ->map(function ($c) use ($tokens) {
                $partes = preg_split('/[^\p{L}\p{N}]+/u', $c['fold']) ?: [];
                $mejor = PHP_INT_MAX;

                foreach ($tokens as $token) {
                    // Tolerancia proporcional: una letra en nombres cortos, dos en largos.
                    // Sin esto "saury" arrastraba a "laura" y "sara".
                    $maxDistancia = mb_strlen($token) >= 6 ? 2 : 1;

                    foreach ($partes as $parte) {
                        if (mb_strlen($parte) < 3) {
                            continue;
                        }

                        $distancia = levenshtein($token, $parte);
                        if ($distancia <= $maxDistancia) {
                            $mejor = min($mejor, $distancia);
                        }
                    }
                }

                $c['distancia'] = $mejor;

                return $c;
            })
            ->filter(fn ($c) => $c['distancia'] !== PHP_INT_MAX)
            ->sortBy('distancia')
            ->take($limite)
            ->map(fn ($c) => [
                'nombre' => $c['nombre'],
                'correo' => $c['correo'],
                'puesto' => $this->puestoPorNombreEmpleado($c['nombre']),
            ])
            ->values();
    }

    /**
     * Quita las palabras del propio modo ("correo", "cuál es"…) y deja el objetivo.
     */
    private function stripEmailQueryNoise(string $query): string
    {
        $out = $this->stripDirectoryQuestionPreamble($query);
        $out = preg_replace('/\b(correos?|e-?mails?|mails?)\b/iu', ' ', $out) ?? $out;
        $out = preg_replace('/\b(electr[oó]nicos?|institucionales?|corporativos?|laborales?)\b/iu', ' ', $out) ?? $out;
        $out = preg_replace(
            '/^\s*(cu[aá]l(es)?|qu[eé]|dame|dime|necesito|quiero|quisiera|proporci[oó]name|'
            . 'comp[aá]rteme|mu[eé]strame|ind[ií]came|me\s+puedes?\s+dar)\b/iu',
            ' ',
            $out
        ) ?? $out;
        $out = preg_replace('/^\s*(es|son)\b/iu', ' ', $out) ?? $out;
        $out = trim(preg_replace('/\s+/u', ' ', $out) ?? $out);

        // Artículos/preposiciones sueltos al inicio: "el de Juan Pérez" → "Juan Pérez".
        for ($i = 0; $i < 3; $i++) {
            $recorte = preg_replace('/^\s*(el|la|los|las|de|del|un|una)\s+/iu', '', $out) ?? $out;
            if ($recorte === $out) {
                break;
            }
            $out = trim($recorte);
        }

        return $out !== '' ? $out : $query;
    }

    /**
     * Tokens útiles del objetivo: quita muletillas ("tiene", "sabes"…) y deja nombre/apellidos.
     */
    private function tokensNombreParaCorreo(string $texto): array
    {
        $stop = [
            'usuario', 'usuarios', 'empleado', 'empleados', 'empleada', 'empleadas', 'persona', 'personas',
            'senor', 'senora', 'trabajador', 'trabajadores', 'colaborador', 'colaboradores', 'registrado',
            'registrada', 'registrados', 'sistema', 'favor', 'para', 'por', 'con', 'que', 'tiene', 'tienen',
            'tienes', 'tengo', 'cuenta', 'posee', 'hay', 'existe', 'sabes', 'saber', 'conoces', 'area',
            'areas', 'unidad', 'unidades', 'puesto', 'puestos', 'del', 'las', 'los', 'una', 'uno', 'sus',
            'todos', 'todas', 'cual', 'cuales', 'dame', 'dime', 'quiero', 'necesito', 'algun', 'alguna',
            'directorio', 'contacto', 'contactar', 'escribir', 'lista', 'listado', 'mismo', 'misma',
            'duda', 'quien', 'quienes', 'saber', 'ocupa', 'ocupan', 'llama', 'llaman', 'proser',
            'ninguno', 'ninguna', 'ningunos', 'ningunas', 'general', 'alguien',
            'llamado', 'llamada', 'llamaba', 'llamaban', 'nombres', 'nombre',
            'pero', 'solo', 'solamente', 'tambien', 'ademas',
            'ella', 'ellas', 'ellos', 'ese', 'esa', 'esos', 'esas', 'eso', 'esto', 'esta', 'estos', 'estas',
            'usted', 'ustedes', 'das', 'doy', 'dan', 'dar', 'puedes', 'puedo', 'podrias', 'podria',
            'pasame', 'pasarme', 'comparte', 'compartir', 'compartelo', 'compartemelo', 'muestrame',
            'muestra', 'indicame', 'indica', 'oye', 'porfa', 'porfavor', 'gracias', 'este', 'esos',
            'ocupo', 'conocer', 'busco', 'busca', 'exacto', 'exactamente', 'encarga', 'obligaciones',
            'analista', 'auxiliar', 'coordinador', 'coordinadora', 'gerente', 'director', 'directora',
            'jefe', 'jefa', 'residente', 'programador', 'programacion', 'administrativo', 'administracion',
            'contador', 'nominas', 'nomina',
        ];

        $tokens = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $this->foldAccents($texto)) ?: [],
            fn ($t) => mb_strlen($t) >= 3 && !in_array($t, $stop, true)
        ));

        return array_slice($tokens, 0, 4);
    }

    /**
     * Etiqueta legible del objetivo buscado: "eduardo cong tiene ?" → "Eduardo Cong".
     */
    private function criterioNombre(array $tokens): string
    {
        // Sin tokens de nombre no hay objetivo que nombrar: mejor mensaje genérico
        // que citar muletillas sueltas ("No encontré a **tengo**…").
        if (empty($tokens)) {
            return '';
        }

        return implode(' ', array_map(
            fn ($t) => mb_convert_case($t, MB_CASE_TITLE, 'UTF-8'),
            $tokens
        ));
    }

    private function expresionNombreCompletoSql(): string
    {
        return "LOWER(CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno))";
    }

    /**
     * Busca por nombre/apellidos sueltos: "juan perez", "lopez".
     *
     * Exige TODOS los tokens (AND): "carlos cong" no debe devolver a todos los Carlos.
     * Cubre empleados y usuarios del sistema sin expediente (p. ej. altas recientes).
     */
    private function buscarContactosPorNombre(array $tokens): Collection
    {
        if (empty($tokens)) {
            return collect();
        }

        $expr = $this->expresionNombreCompletoSql();
        $consulta = $this->empleadosConCorreoQuery();
        foreach ($tokens as $token) {
            $consulta->whereRaw($expr . ' LIKE ?', ['%' . $token . '%']);
        }

        $contactos = $this->contactosDesdeEmpleados(
            $consulta->orderBy('apellido_paterno')->limit(30)->get()
        );

        $yaListados = $contactos->pluck('correo')->map(fn ($c) => mb_strtolower($c))->all();

        $deUsuarios = $this->catalogoUsuariosParaCorreo()
            ->filter(function ($u) use ($tokens) {
                foreach ($tokens as $token) {
                    if (!str_contains($u['fold'], $token)) {
                        return false;
                    }
                }

                return true;
            })
            ->reject(fn ($u) => in_array(mb_strtolower($u['correo']), $yaListados, true))
            ->map(fn ($u) => [
                'nombre' => $u['nombre'],
                'correo' => $u['correo'],
                'puesto' => $this->puestoPorNombreEmpleado($u['nombre']),
            ])
            ->values();

        return $contactos->concat($deUsuarios)->values();
    }

    /**
     * Coincidencias parciales (OR) para sugerir cuando el nombre exacto no existe.
     */
    private function sugerirEmpleadosPorNombre(array $tokens, int $limite = 5): Collection
    {
        if (count($tokens) < 2) {
            return collect();
        }

        $expr = $this->expresionNombreCompletoSql();

        $contactos = $this->contactosDesdeEmpleados(
            $this->empleadosConCorreoQuery()
                ->where(function ($sub) use ($tokens, $expr) {
                    foreach ($tokens as $token) {
                        $sub->orWhereRaw($expr . ' LIKE ?', ['%' . $token . '%']);
                    }
                })
                ->orderBy('apellido_paterno')
                ->limit($limite)
                ->get()
        );

        $yaListados = $contactos->pluck('correo')->map(fn ($c) => mb_strtolower($c))->all();

        $deUsuarios = $this->catalogoUsuariosParaCorreo()
            ->filter(function ($u) use ($tokens) {
                foreach ($tokens as $token) {
                    if (str_contains($u['fold'], $token)) {
                        return true;
                    }
                }

                return false;
            })
            ->reject(fn ($u) => in_array(mb_strtolower($u['correo']), $yaListados, true))
            ->map(fn ($u) => [
                'nombre' => $u['nombre'],
                'correo' => $u['correo'],
                'puesto' => $this->puestoPorNombreEmpleado($u['nombre']),
            ])
            ->values();

        return $contactos->concat($deUsuarios)->take($limite)->values();
    }

    private function nombreCompletoEmpleado($empleado): string
    {
        return trim(implode(' ', array_filter([
            $empleado->nombres ?? null,
            $empleado->apellido_paterno ?? null,
            $empleado->apellido_materno ?? null,
        ])));
    }

    private function buildEmailListMessage(Collection $contactos, string $criterio): string
    {
        if ($contactos->isEmpty()) {
            $objetivo = trim($criterio, " \t\n\r\0\x0B?¿!.");

            $encabezadoVacio = $objetivo !== ''
                ? "No encontré a **{$objetivo}** en el directorio con un correo registrado."
                : "No encontré ningún correo con ese criterio.";

            return $encabezadoVacio . "\n\n"
                . "Puedo buscarlo de estas formas:\n\n"
                . "- **El tuyo**: \"¿cuál es mi correo?\"\n"
                . "- **Por persona**: \"correo de Juan Pérez\"\n"
                . "- **Por puesto**: \"correo del coordinador de TI\"\n"
                . "- **Por área**: \"correos del área de calidad\"";
        }

        // Personas localizadas pero sin correo utilizable: se informan, no se inventan.
        $sinCorreo = $contactos->filter(fn ($c) => trim((string) $c['correo']) === '')->values();
        $contactos = $contactos->filter(fn ($c) => trim((string) $c['correo']) !== '')->values();

        if ($contactos->isEmpty()) {
            if ($sinCorreo->count() === 1) {
                $c = $sinCorreo->first();

                return '**' . $c['nombre'] . '**'
                    . (!empty($c['puesto']) ? " ({$c['puesto']})" : '')
                    . " está en el directorio, pero **no tiene un correo registrado**.\n\n"
                    . "Puedo darte el de otra persona o el del puesto completo.";
            }

            $nombres = $sinCorreo->pluck('nombre')->implode(', ');

            return "Localicé a estas personas, pero ninguna tiene correo registrado: {$nombres}.";
        }

        if ($contactos->count() === 1 && $sinCorreo->isEmpty()) {
            $c = $contactos->first();

            return '**' . $c['nombre'] . '**'
                . (!empty($c['puesto']) ? " ({$c['puesto']})" : '')
                . "\n\nTiene el correo: **{$c['correo']}**";
        }

        $total = $contactos->count();

        // Tope duro: aunque el criterio sea legítimo (un área enorme), no se vuelca
        // medio directorio de una sola vez.
        if ($total > self::EMAIL_MAX_RESULTADOS) {
            $donde = $criterio !== '' ? " en *{$criterio}*" : '';

            return "Encontré **{$total}** personas con correo{$donde}: son demasiadas para listarlas.\n\n"
                . "Acota la búsqueda por puesto o por persona, por ejemplo "
                . "\"correo del coordinador de calidad\" o \"correo de Juan Pérez\".";
        }

        $limite = 25;

        $lineas = $contactos->take($limite)->map(
            fn ($c) => '- **' . $c['nombre'] . '**'
                . (!empty($c['puesto']) ? " — {$c['puesto']}" : '')
                . ": {$c['correo']}"
        )->implode("\n");

        $encabezado = $criterio !== ''
            ? "Correos de *{$criterio}* ({$total}):"
            : "Correos encontrados ({$total}):";

        $msg = $encabezado . "\n\n" . $lineas;

        if ($sinCorreo->isNotEmpty()) {
            $msg .= "\n\nSin correo registrado: " . $sinCorreo->pluck('nombre')->implode(', ') . '.';
        }

        return $msg;
    }


    private function getOfferMenuKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_offer_menu_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    private function getPendingDocConfirmKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_pending_doc_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    private function getLastDocHintKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_last_doc_hint_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    private function getLastAspectKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_last_aspect_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    private function getPendingContactKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_pending_contact_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    /**
     * "con quién me puedo comunicar", "a quién le pregunto", "quién me ayuda".
     */
    private function isWhoToContactQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        return (bool) preg_match(
            '/\b(con qui[eé]n (me )?(puedo |podria )?(comunic(ar|o)|hablar|ver|tramitar|gestionar|guiar)|'
            . 'a qui[eé]n (me )?(puedo |podria )?(comunic(ar|o)|llamar)|'
            . 'con qui[eé]n (hablo|veo|tramito|gestiono)|'
            . 'a qui[eé]n (le )?(pregunto|acudo|llamo|escribo|pido)|'
            . 'qui[eé]n me (puede |podria )?(ayudar|orientar|apoyar)|'
            . 'a qui[eé]n me comunico|'
            . 'con qui[eé]n veo)\b/u',
            $q
        );
    }

    /**
     * El usuario rechaza las opciones que Bob acaba de ofrecer.
     */
    private function isRejectingOfferedOptions(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(ninguno de (esos|esas|ellos)|ninguna de (esas|ellos)|'
            . 'no (es|son) (ninguno|ninguna|esos|esas|eso)|'
            . 'no (quiero|necesito) (ninguno|ninguna|esos|esas)|'
            . 'no me refiero (a )?(eso|esos|esas|documentos?|procedimientos?)|'
            . 'no (estoy hablando|hablo) de (eso|documentos?|procedimientos?))\b/u',
            $q
        ) || (bool) preg_match('/^(ninguno|ninguna)\b/u', $q)
        || (bool) preg_match(
            '/^no,?\s+(quiero|necesito|estoy|me refiero|pregunto|era|es que|busco)\b/u',
            $q
        );
    }

    /**
     * Temas de personal/RH que casi nunca están como PDF del SGC.
     */
    private function detectHrPersonalTopic(string $query): string
    {
        $q = $this->foldAccents($query);
        if (preg_match('/\bvacaciones?\b/u', $q)) {
            return 'vacaciones';
        }
        if (preg_match('/\b(aumento|incremento|sueldo|salario|prestaciones?)\b/u', $q)) {
            return 'aumento / prestaciones';
        }
        if (preg_match('/\b(nomina|aguinaldo|finiquito|prestamo|permiso|incapacidad)\b/u', $q)) {
            return 'nómina';
        }

        return '';
    }

    /**
     * "con quién veo mis vacaciones", "ayuda para solicitar aumento":
     * orientar a Capital Humano, no a un PDF al azar.
     */
    private function shouldRouteToHrContact(string $query): bool
    {
        $topic = $this->detectHrPersonalTopic($query);
        if ($topic === '') {
            return $this->isWhoToContactQuery($query)
                && (bool) preg_match('/\b(capital humano|recursos humanos|rh|rrhh)\b/u', mb_strtolower($query));
        }

        return $this->isWhoToContactQuery($query)
            || (bool) preg_match(
                '/\b(ayuda|ayudame|solicitar|tramitar|pedir|ver mis|gestionar mis)\b/u',
                mb_strtolower($query)
            );
    }

    /**
     * Chips de seguimiento de un documento ya identificado (no un menú inventado).
     */
    private function documentGuideChips(): array
    {
        return [
            ['label' => 'Pasos / actividades', 'query' => 'cuáles son las actividades'],
            ['label' => 'Responsable', 'query' => 'quién es el responsable'],
            ['label' => 'Objetivo', 'query' => 'cuál es el objetivo'],
        ];
    }

    private function hrPersonalTopicFromThread(?string $sessionId, ?string $userId, string $currentQuery): string
    {
        $found = $this->detectHrPersonalTopic($currentQuery);
        if ($found !== '') {
            return $found;
        }

        foreach (array_reverse($this->getConversationHistory($sessionId, 8, $userId)) as $msg) {
            if (($msg['role'] ?? '') !== 'user') {
                continue;
            }
            $found = $this->detectHrPersonalTopic((string) ($msg['content'] ?? ''));
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    /**
     * El PDF en hint/foco habla del mismo tema que el hilo reciente.
     * Evita que un "sí" siga "Gestionar Vuelos" después de preguntar por vacaciones.
     */
    private function docHintFitsRecentTopic(
        array $hint,
        ?string $sessionId,
        ?string $userId,
        string $currentQuery
    ): bool {
        $title = $this->foldAccents((string) ($hint['title'] ?? ''));
        if ($title === '') {
            return false;
        }

        $blob = $this->foldAccents($currentQuery);
        foreach ($this->getConversationHistory($sessionId, 8, $userId) as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $blob .= ' ' . $this->foldAccents((string) ($msg['content'] ?? ''));
            }
        }

        $topics = [
            'vacacion', 'nomina', 'permiso', 'prestamo', 'campamento',
            'factura', 'pago', 'compra', 'auditor', 'calidad', 'juridic',
            'vuelo', 'seguridad',
        ];
        $found = [];
        foreach ($topics as $t) {
            if (preg_match('/\b' . preg_quote($t, '/') . '/u', $blob)) {
                $found[] = $t;
            }
        }
        if (empty($found)) {
            return true;
        }
        foreach ($found as $t) {
            if (str_contains($title, $t)) {
                return true;
            }
        }

        $titleStub = mb_substr(trim((string) ($hint['title'] ?? '')), 0, 10);
        if ($titleStub !== '' && mb_stripos($blob, $this->foldAccents($titleStub)) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Contactos del área (RH / Capital Humano) cuando no hay procedimiento publicado.
     */
    private function buildContactForTopicResponse(
        string $query,
        string $topic,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $areas = collect();
        foreach (['capital humano', 'recursos humanos'] as $areaQuery) {
            $areas = $this->findExplicitAreasInQuery($areaQuery);
            if ($areas->isEmpty()) {
                $areas = $this->findAreasMentionedInQuery($areaQuery);
            }
            if ($areas->isNotEmpty()) {
                break;
            }
        }

        $lineas = [];
        $areaNombre = $areas->pluck('nombre')->filter()->first() ?: 'Capital Humano';
        $puestoIds = [];
        if ($areas->isNotEmpty()) {
            $puestoIds = $this->puestoIdsForAreaIds(
                $areas->pluck('id_area')->map(fn ($id) => (int) $id)->all()
            );
        }
        if (empty($puestoIds)) {
            $puestoIds = PuestoTrabajo::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ['%recursos humanos%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['%capital humano%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['% gerente de rh%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['%rh %']);
                })
                ->pluck('id_puesto_trabajo')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        if (!empty($puestoIds)) {
            $puestos = PuestoTrabajo::query()
                ->whereIn('id_puesto_trabajo', $puestoIds)
                ->orderBy('nombre')
                ->get(['id_puesto_trabajo', 'nombre']);
            $empleados = Empleados::whereIn('puesto_trabajo_id', $puestoIds)
                ->whereNull('deleted_at')
                ->orderBy('apellido_paterno')
                ->limit(40)
                ->get(['nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id']);
            $map = $puestos->keyBy('id_puesto_trabajo');
            $ranked = $empleados->map(function ($emp) use ($map, $areaNombre) {
                $nombre = trim(implode(' ', array_filter([
                    $emp->nombres,
                    $emp->apellido_paterno,
                    $emp->apellido_materno,
                ])));
                $puesto = optional($map->get($emp->puesto_trabajo_id))->nombre ?? $areaNombre;

                return [
                    'nombre' => $nombre,
                    'puesto' => $puesto,
                    'rank' => $this->leadershipRankForPuesto($puesto),
                ];
            })->filter(fn ($row) => $row['nombre'] !== '')
                ->sortBy([
                    ['rank', 'asc'],
                    ['nombre', 'asc'],
                ])->values();

            $lideres = $ranked->filter(fn ($row) => (int) $row['rank'] <= 4);
            $mostrar = ($lideres->isNotEmpty() ? $lideres : $ranked)->take(5);
            foreach ($mostrar as $row) {
                $lineas[] = "- **{$row['nombre']}** — {$row['puesto']}";
            }
        }

        $msg = "No hay un procedimiento publicado de **{$topic}** en el SGC.\n\n"
            . "Para este trámite te conviene acudir a **{$areaNombre}** o a tu **jefe directo**.";
        if (!empty($lineas)) {
            $msg .= "\n\nEn el directorio aparecen:\n" . implode("\n", $lineas);
        } else {
            $msg .= "\n\nPuedo localizar un **puesto** concreto (por ejemplo, Gerente de Recursos Humanos) "
                . "o consultar **tus procedimientos** según tu puesto.";
        }

        \Cache::forget($this->getContextKey($sessionId, $userId));
        \Cache::forget($this->getLastDocHintKey($sessionId, $userId));
        \Cache::put($this->getPendingContactKey($sessionId, $userId), [
            'topic' => $topic,
            'asked_at' => time(),
        ], 600);

        $resp = $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_topic_contact',
            $startTime,
            $userId,
            $sessionId
        );
        $resp['document'] = null;
        $resp['final_context'] = null;
        $resp['chips'] = [
            ['label' => 'Gerente de RH', 'query' => 'quién ocupa Gerente de Recursos Humanos'],
            ['label' => 'Proc. de RH', 'query' => 'procedimientos de Recursos Humanos'],
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
        ];

        return $resp;
    }

    /**
     * Aspecto de documento que pide el usuario (riesgos, evidencias, objetivo…).
     */
    private function documentSectionPattern(): string
    {
        return '/\b(objetivo|objetivos|alcance|alcances|re?sponsable|re?sponsables|'
            . 'paso|pasos|actividad|actividades|riesgo|riesgos|indicador|indicadores|'
            . 'politica|politicas|registro|registros|referencia|referencias|'
            . 'frecuencia|periodicidad|vigencia|version|proposito|finalidad|'
            . 'entradas|salidas|formatos?|anexos?|definiciones?|glosario|requisitos|'
            . 'controles|evidencias?|flujograma|resumen|normas?\s+generales)\b/u';
    }

    /**
     * Pregunta por una sección de UN procedimiento, no por el inventario del sistema.
     * Ej: "cuáles son sus evidencias", "riesgos de programar pagos".
     */
    private function isDocumentSectionQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if (!preg_match($this->documentSectionPattern(), $q)) {
            return false;
        }

        // Listado explícito del sistema: "lista de procedimientos de calidad".
        if (
            preg_match('/\b(lista|listado|todos los|todas las)\b/u', $q)
            && preg_match('/\b(procedimientos?|documentos?|elementos?)\b/u', $q)
            && !preg_match('/\b(su|sus|este|esta|de este|del procedimiento)\b/u', $q)
        ) {
            return false;
        }

        return true;
    }

    /**
     * El usuario niega el "no está" del bot: "sí existen", "sí hay", "busca otra vez".
     */
    private function isUserInsistingContentExists(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        $q = trim($q, " \t\n\r\0\x0B?¿!.");
        if ($q === '') {
            return false;
        }

        if (preg_match(
            '/^(s[ií]|si)\s+(que\s+)?(existen?|hay|est[aá]n|esta|viene|vienen)(\s+\w+){0,6}$/u',
            $q
        )) {
            return true;
        }

        return (bool) preg_match(
            '/\b(busca(lo|la)?\s+(otra vez|mejor|bien|de nuevo)|revisa(lo|la)?\s+(otra vez|mejor|bien|de nuevo)|'
            . 'vuelve a (buscar|leer|revisar)|ah[ií]\s+(est[aá]n?|hay|viene)|'
            . 'en el documento s[ií]|s[ií] que (hay|existen))\b/u',
            $q
        );
    }

    private function detectQueryAspect(string $query): string
    {
        $q = $this->foldAccents($query);
        $pairs = [
            'riesgos' => '/\briesgos?\b/u',
            'evidencias' => '/\bevidencias?\b/u',
            'objetivo' => '/\bobjetivos?\b/u',
            'alcance' => '/\balcances?\b/u',
            'responsable' => '/\bresponsables?\b/u',
            'definiciones' => '/\b(definiciones?|glosario)\b/u',
            'actividades' => '/\b(actividades|pasos)\b/u',
            'registros' => '/\b(registros?|anexos?|formatos?)\b/u',
            'controles' => '/\bcontroles?\b/u',
        ];
        foreach ($pairs as $aspect => $pat) {
            if (preg_match($pat, $q)) {
                return $aspect;
            }
        }

        return '';
    }

    private function recallLastAspect(?string $sessionId, ?string $userId, string $currentQuery): string
    {
        $fromQuery = $this->detectQueryAspect($currentQuery);
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        $cached = \Cache::get($this->getLastAspectKey($sessionId, $userId));
        if (is_string($cached) && trim($cached) !== '' && trim($cached) !== 'general') {
            return trim($cached);
        }

        foreach (array_reverse($this->getConversationHistory($sessionId, 8, $userId)) as $msg) {
            if (($msg['role'] ?? '') !== 'user') {
                continue;
            }
            $found = $this->detectQueryAspect((string) ($msg['content'] ?? ''));
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    /**
     * Palabras/títulos de sección a buscar en el texto completo del Word.
     *
     * @return array<int, string>
     */
    private function sectionNeedlesForQuery(string $query): array
    {
        $q = $this->foldAccents($query);
        $map = [
            'riesgo' => ['RIESGOS Y DESCRIPCIÓN', 'RIESGOS Y DESCRIPCION', '8. RIESGOS', 'RIESGOS', 'RIESGO'],
            'evidencia' => ['EVIDENCIAS', 'EVIDENCIA', 'REGISTROS', 'FORMATOS'],
            'objetivo' => ['OBJETIVO'],
            'alcance' => ['ALCANCE'],
            'definicion' => ['DEFINICIONES', 'GLOSARIO'],
            'actividad' => [
                '| Responsable | Actividad |',
                '| Responsable | Actividad',
                'Responsable | Actividad',
                'DESARROLLO',
                '5. DESARROLLO',
                '6. DESARROLLO',
                'DESCRIPCIÓN DE ACTIVIDADES',
                'DESCRIPCION DE ACTIVIDADES',
            ],
            'responsable' => [
                'RESPONSABLE DEL ELEMENTO',
                'RESPONSABLE DE PROCEDIMIENTO',
                'RESPONSABLE DEL PROCEDIMIENTO',
                '10. RESPONSABLE',
                '9. RESPONSABLE',
                'RESPONSABLE',
                'RESPONSABILIDADES',
            ],
            'control' => ['CONTROLES', 'PUNTOS CRÍTICOS', 'PUNTOS CRITICOS'],
            'registro' => ['REGISTROS', 'ANEXOS', 'FORMATOS'],
        ];

        $needles = [];
        foreach ($map as $key => $words) {
            if (str_contains($q, $key)) {
                foreach ($words as $w) {
                    $needles[] = $w;
                }
            }
        }

        return array_values(array_unique($needles));
    }

    /**
     * Ventanas del texto completo alrededor de RIESGOS / EVIDENCIAS / etc.
     *
     * @return array<int, string>
     */
    private function extractKeywordSectionSnippets(int $wordDocumentId, string $query, int $window = 2800): array
    {
        $needles = $this->sectionNeedlesForQuery($query);
        if (empty($needles)) {
            return [];
        }

        $raw = \Illuminate\Support\Facades\DB::table('word_documents')
            ->where('id', $wordDocumentId)
            ->value('contenido_texto');
        $full = trim(preg_replace('/\s+/', ' ', strip_tags((string) $raw)));
        if ($full === '') {
            $json = \Illuminate\Support\Facades\DB::table('word_documents')
                ->where('id', $wordDocumentId)
                ->value('contenido_estructurado');
            $full = trim(preg_replace('/\s+/', ' ', strip_tags((string) $json)));
        }
        if ($full === '') {
            return [];
        }

        $snippets = [];
        $usedStarts = [];
        foreach ($needles as $needle) {
            $pos = mb_stripos($full, $needle);
            if ($pos === false) {
                continue;
            }
            $tooClose = false;
            foreach ($usedStarts as $start) {
                if (abs($pos - $start) < 400) {
                    $tooClose = true;
                    break;
                }
            }
            if ($tooClose) {
                continue;
            }
            $usedStarts[] = $pos;
            $from = max(0, $pos - 80);
            $snippets[] = trim(mb_substr($full, $from, $window));
            if (count($snippets) >= 3) {
                break;
            }
        }

        return $snippets;
    }

    private function isVagueAffirmation(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        $q = trim($q, " \t\n\r\0\x0B?¿!.");
        if ($q === '') {
            return true;
        }

        // "sí", "sí quiero", "ok", "dale"… sin elegir opción concreta.
        if (preg_match(
            '/^(s[ií]|sip|ok|okay|vale|claro|dale|de acuerdo|por favor|quiero|si quiero|sí quiero|si por favor|sí por favor)(\s+por\s+favor)?$/u',
            $q
        )) {
            return true;
        }

        $words = preg_split('/\s+/u', $q) ?: [];
        if (count($words) <= 3 && preg_match('/^(s[ií]|ok|vale|claro|dale)\b/u', $q)
            && !preg_match('/\b(procedimientos?|directorio|documento|folio|puesto|[aá]rea|1|2|3)\b/u', $q)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Convierte un "sí"/"ok"/"sí existen" en una pregunta útil sobre el documento en foco.
     */
    private function expandAffirmationToDocFollowUp(
        array $cachedContext,
        ?string $sessionId = null,
        ?string $userId = null,
        string $cleanQuery = '',
        bool $insist = false
    ): string {
        $titulo = trim((string) ($cachedContext['title'] ?? 'este procedimiento'));
        if ($titulo === '') {
            $titulo = 'este procedimiento';
        }

        $aspect = $this->recallLastAspect($sessionId, $userId, $cleanQuery);
        if ($aspect === '') {
            $aspect = 'objetivo';
        }

        $insistBit = $insist
            ? ' El usuario insiste en que ESA sección SÍ está en el documento. '
                . 'Búscala en TODO el texto, incluidos títulos numerados '
                . '(8. RIESGOS Y DESCRIPCIÓN, EVIDENCIAS, REGISTROS). '
                . 'Si aparece la palabra, lista cada punto con su descripción. No digas que no existe.'
            : '';

        return "En el procedimiento {$titulo}, extrae la sección de {$aspect} completa.{$insistBit} "
            . "Si no hay encabezado literal, usa controles, puntos críticos, registros o actividades equivalentes.";
    }

    /**
     * @return string|null 'mis_procedimientos'|'directorio'|'documento'|'clarify'|null
     */
    private function resolveOfferMenuChoice(string $query, array $offerMenu): ?string
    {
        $q = mb_strtolower(trim($query));

        if (preg_match('/^\s*1\b/u', $q) || $this->isMyProceduresQuery($q)
            || preg_match('/\b(mis procedimientos|tus procedimientos|mis documentos)\b/u', $q)
        ) {
            return 'mis_procedimientos';
        }
        if (preg_match('/^\s*2\b/u', $q) || preg_match('/\bdirectorio\b/u', $q)) {
            return 'directorio';
        }
        if (
            preg_match('/^\s*3\b/u', $q)
            || preg_match('/\b(consultar|documento|procedimiento concreto|un folio)\b/u', $q)
        ) {
            return 'documento';
        }

        return null;
    }

    private function buildOfferMenuClarifyResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $msg = "Con gusto. Elige una de estas opciones:\n\n"
            . "1. **Mis procedimientos** (según tu puesto)\n"
            . "2. **Directorio** (quién ocupa un puesto o unidad)\n"
            . "3. **Consultar un documento** (nombre o folio)\n\n"
            . "Puedes responder **1**, **2** o **3**.";

        \Cache::put($this->getOfferMenuKey($sessionId, $userId), [
            'options' => ['mis_procedimientos', 'directorio', 'documento'],
            'asked_at' => time(),
        ], 600);

        return [
            'response' => $msg,
            'method' => 'conversation_offer_clarify',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_offer_clarify',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    private function executeOfferMenuChoice(
        string $choice,
        string $cleanQuery,
        string $searchQuery,
        $startTime,
        $userId,
        $sessionId,
        string $catalogStateKey
    ): array {
        if ($choice === 'mis_procedimientos') {
            $resp = $this->generateCatalogBrowseResponse(
                'mis procedimientos',
                'mis procedimientos',
                $startTime,
                $userId,
                $sessionId,
                null,
                null
            );
            if (!empty($resp['catalog_state'])) {
                \Cache::put($catalogStateKey, $resp['catalog_state'], 600);
            }
            return $resp;
        }

        if ($choice === 'directorio') {
            $msg = "Con gusto. En el **directorio** puedo indicarte quién está registrado "
                . "(por área, puesto o nombre), de acuerdo con la información del sistema.\n\n"
                . "Plantea tu consulta con tus propias palabras. Si la persona no está registrada, te lo indico; no invento nombres.";

            return [
                'response' => $msg,
                'method' => 'directory_offer_prompt',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [],
                'cached' => false,
                'document' => null,
                'chips' => [
                    ['label' => 'Unidades', 'query' => 'dime las unidades'],
                    ['label' => 'Directores', 'query' => 'lista los directores'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
                'analytics_id' => $this->logAnalytics(
                    $cleanQuery,
                    $msg,
                    'directory_offer_prompt',
                    $startTime,
                    $userId,
                    $sessionId
                ),
            ];
        }

        // documento
        $msg = "Ok. Dime el **nombre** o el **folio** del documento "
            . "(ej. Desarrollar Proyectos de Tecnología o PAA02-PR03) y te ayudo con el detalle.";

        return [
            'response' => $msg,
            'method' => 'conversation_ask_document',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'analytics_id' => $this->logAnalytics(
                $cleanQuery,
                $msg,
                'conversation_ask_document',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    private function isMyProceduresQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(mis procedimientos|mis documentos|lista de mis|listado de mis|'
            . 'los m[ií]os|que me aplican|asignados a mi|para mi puesto|de mi puesto|'
            . 'tengo relaci[oó]n|relacionados? conmigo|donde participo|'
            . 'qu[eé] procedimientos (tengo|me tocan|me corresponden)|'
            . 'procedimientos tengo|documentos tengo)\b/u',
            $q
        );
    }

    /**
     * Tras elegir un puesto en directorio: "qué procedimientos tienen / asignados".
     */
    private function isProceduresAssignedFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(que procedimientos|qu[eé] procedimientos|procedimientos (tienen|tienen asignados|asignados)|tienen asignados|asignados)\b/u',
            $q
        ) && !preg_match('/\b(folio|[a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q);
    }

    /**
     * "toda su lista de procedimientos", "su lista", "los procedimientos de ese puesto".
     */
    private function isTheirProceduresListFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        $hablaLista = (bool) preg_match('/\b(lista|listado|procedimientos?|todos|toda)\b/u', $q);
        $hablaSu = (bool) preg_match(
            '/\b(su|sus|ese puesto|esa persona|de ese|de esa|del puesto|de él|de ella)\b/u',
            $q
        );

        return $hablaLista && $hablaSu
            && !preg_match('/\b(mis|mi puesto|m[ií]os)\b/u', $q);
    }

    /**
     * Recupera el puesto del hilo: catalog_state, historial o responsable del doc en foco.
     */
    private function resolvePuestoStateFromRecentContext(
        ?string $sessionId,
        ?array $cachedContext,
        ?array $catalogState
    ): ?array {
        if (is_array($catalogState) && !empty($catalogState['puesto_ids'])) {
            return $catalogState;
        }

        $blob = '';
        foreach ($this->getConversationHistory($sessionId, 8) as $msg) {
            $blob .= ' ' . strip_tags((string) ($msg['content'] ?? ''));
        }

        $puestos = collect();
        if (trim($blob) !== '') {
            $puestos = $this->resolveExactPuestoFromQuery($blob);
            if ($puestos->isEmpty()) {
                // Nombres largos contenidos en el historial (Director Jurídico…).
                $catalog = $this->getPuestosCatalog();
                $puestos = $catalog->filter(function ($p) use ($blob) {
                    $name = $this->foldAccents((string) $p->nombre);
                    $hay = $this->foldAccents($blob);
                    return mb_strlen($name) >= 12 && str_contains($hay, $name);
                })->values();
            }
        }

        if ($puestos->isEmpty() && $cachedContext && !empty($cachedContext['id'])) {
            $el = Elemento::with('puestoResponsable:id_puesto_trabajo,nombre')->find($cachedContext['id']);
            if ($el && $el->puestoResponsable) {
                $puestos = collect([$el->puestoResponsable]);
            }
        }

        if ($puestos->isEmpty()) {
            return null;
        }

        // El más específico (nombre más largo) suele ser el del hilo.
        $best = $puestos->sortByDesc(fn ($p) => mb_strlen((string) $p->nombre))->first();

        return [
            'mode' => 'by_puesto',
            'puesto_ids' => [(int) $best->id_puesto_trabajo],
            'puesto_nombres' => [$best->nombre],
            'label' => 'puesto(s): ' . $best->nombre,
        ];
    }

    private function getCatalogStateKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_catalog_state_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    private function isUnidadListQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match('/\b(por unidad|seg[uú]n unidad|de la unidad|unidad de negocio|unidades)\b/u', $q)
            && (bool) preg_match('/\b(procedimientos?|documentos?|elementos?|lista|listado|pol[ií]ticas?)\b/u', $q);
    }

    /**
     * Términos de filtro para buscar en el catálogo (nombre, folio, unidad).
     */
    private function extractCatalogTopicTerms(string $query): array
    {
        $q = mb_strtolower(trim($query));
        $terms = [];

        // TI: solo "tecnolog…" (evitar "información" suelta, que ensancha de más el listado).
        if (preg_match('/\b(ti|t\.i\.?|tecnolog\w*)\b/u', $q)) {
            $terms[] = 'tecnolog';
        }
        if (preg_match('/\bcalidad\b/u', $q)) {
            $terms[] = 'calidad';
        }
        if (preg_match('/\bjur[ií]dic\w*\b/u', $q)) {
            array_push($terms, 'juridico', 'jurid', 'fianzas', 'seguros', 'paa03');
        }
        if (preg_match('/\bpresupuest\w*\b/u', $q)) {
            $terms[] = 'presupuest';
        }
        if (preg_match('/\bcompras?\b/u', $q)) {
            array_push($terms, 'compra', 'proveedor');
        }
        if (preg_match('/\bcorporativo\b/u', $q)) {
            $terms[] = 'corporativo';
        }
        if (preg_match('/\bconstrucci[oó]n\b/u', $q)) {
            array_push($terms, 'construccion', 'construcción');
        }

        if (preg_match_all('/\b(?:area|área|de|del)\s+([\p{L}]{4,})/u', $q, $matches)) {
            $skip = ['todos', 'todas', 'procedimientos', 'procedimiento', 'documentos', 'documento',
                'lineamientos', 'lineamiento', 'politicas', 'políticas', 'elementos', 'lista', 'listado',
                'puesto', 'puestos', 'unidad', 'unidades', 'negocio'];
            foreach ($matches[1] as $word) {
                if (!in_array($word, $skip, true)) {
                    $terms[] = $word;
                }
            }
        }

        return array_values(array_unique(array_filter($terms, fn($t) => mb_strlen(trim($t)) >= 2)));
    }

    private function getPuestosCatalog(): Collection
    {
        return Cache::remember('chat_puestos_catalog_v1', 300, function () {
            return PuestoTrabajo::query()
                ->select('id_puesto_trabajo', 'nombre')
                ->orderByRaw('CHAR_LENGTH(nombre) DESC')
                ->get();
        });
    }

    private function foldAccents(string $text): string
    {
        $text = mb_strtolower(trim($text));

        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        ]);
    }

    /**
     * Coincidencia exacta o por nombre completo (evita el loop de "varios coordinadores").
     */
    private function resolveExactPuestoFromQuery(string $query): Collection
    {
        $q = $this->foldAccents($query);
        $q = preg_replace(
            '/^(como se llam\w*|quien es|quien ocupa|el|la|los|las)\s+/u',
            '',
            $q
        ) ?? $q;
        $q = trim($q, " \t\n\r\0\x0B?¿!.");

        if ($q === '') {
            return collect();
        }

        $puestos = $this->getPuestosCatalog();

        // 1) Igualdad exacta: el usuario pegó el nombre de la lista.
        $exact = $puestos->filter(
            fn($p) => $this->foldAccents((string) $p->nombre) === $q
        )->values();
        if ($exact->isNotEmpty()) {
            return $exact;
        }

        // 1b) Igualdad tolerante a plural, palabra por palabra: "auxiliares contables"
        // == "Auxiliar Contable". El español pluraliza con "+s" (vocal) o "+es"
        // (consonante) y ambas formas terminan pareciendo "...es" en superficie
        // ("contables" y "auxiliares"), así que no hay una regla fija fiable: se
        // prueban las dos reducciones posibles contra el catálogo real.
        $pluralTolerante = $puestos->filter(
            fn($p) => $this->coincideConPluralTolerante($q, $this->foldAccents((string) $p->nombre))
        )->values();
        if ($pluralTolerante->isNotEmpty()) {
            return $pluralTolerante;
        }

        // 2) Nombre completo contenido en la frase → quedarse con el más largo.
        $contained = $puestos->filter(function ($p) use ($q) {
            $name = $this->foldAccents((string) $p->nombre);
            return mb_strlen($name) >= 10 && str_contains($q, $name);
        })->values();

        if ($contained->isNotEmpty()) {
            $maxLen = $contained->max(fn($p) => mb_strlen($this->foldAccents((string) $p->nombre)));

            return $contained
                ->filter(fn($p) => mb_strlen($this->foldAccents((string) $p->nombre)) === $maxLen)
                ->values();
        }

        // 3) Atajos de rol + área (TI, soporte técnico…).
        $roleArea = $this->matchPuestosByRoleAndArea($q, $puestos);
        if ($roleArea->isNotEmpty()) {
            return $roleArea;
        }

        return collect();
    }

    /**
     * ¿La consulta y el nombre del puesto son la misma frase salvo plurales?
     * Compara palabra por palabra, en el mismo orden, permitiendo que cada
     * palabra matchee en su forma tal cual o en cualquiera de sus reducciones
     * candidatas a singular.
     */
    private function coincideConPluralTolerante(string $consulta, string $nombrePuesto): bool
    {
        $wq = preg_split('/\s+/u', trim($consulta)) ?: [];
        $wp = preg_split('/\s+/u', trim($nombrePuesto)) ?: [];

        if (empty($wq) || count($wq) !== count($wp)) {
            return false;
        }

        foreach ($wq as $i => $palabra) {
            $candidatosQ = $this->candidatosSingularPalabra($palabra);
            $candidatosP = $this->candidatosSingularPalabra($wp[$i]);

            if (empty(array_intersect($candidatosQ, $candidatosP))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reducciones plausibles de una palabra a singular: sin sufijo (ya singular),
     * quitando la "s" final (contables → contable) y quitando "es" (auxiliares →
     * auxiliar). Se devuelven todas porque la superficie "...es" es ambigua entre
     * ambas reglas y no hay forma de saber cuál aplica sin comparar contra datos reales.
     */
    private function candidatosSingularPalabra(string $palabra): array
    {
        $candidatos = [$palabra];

        if (mb_strlen($palabra) >= 4 && mb_substr($palabra, -1) === 's') {
            $candidatos[] = mb_substr($palabra, 0, -1);

            if (mb_strlen($palabra) >= 5 && mb_substr($palabra, -2) === 'es') {
                $candidatos[] = mb_substr($palabra, 0, -2);
            }
        }

        return array_unique($candidatos);
    }

    /**
     * "auxiliar de tecnologias", "coordinador de TI" → puestos que cumplen ROL + ÁREA (AND).
     */
    private function matchPuestosByRoleAndArea(string $foldedQuery, ?Collection $puestos = null): Collection
    {
        $q = $this->foldAccents($foldedQuery);
        $puestos = $puestos ?? $this->getPuestosCatalog();

        $roles = ['auxiliar', 'coordinador', 'coordinadora', 'gerente', 'director', 'directora',
            'analista', 'jefe', 'jefa', 'residente', 'programador'];
        $matchedRoles = [];
        foreach ($roles as $role) {
            // Tolera plural ("residentes", "auxiliares", "directores"): sin esto \b
            // exige la palabra exacta y "residentes de compras" no matcheaba nada,
            // cayendo al área completa sin filtrar por el rol pedido.
            if (preg_match('/\b' . preg_quote($role, '/') . '(?:es|s)?\b/u', $q)) {
                $matchedRoles[] = $role;
            }
        }
        if (empty($matchedRoles)) {
            return collect();
        }

        // Especialidad / área pedida (sinónimos).
        $areaNeedles = [];
        if (preg_match('/\b(ti|it|tecnolog\w*|informaci\w*|sistemas?)\b/u', $q)) {
            $areaNeedles = array_merge($areaNeedles, ['tecnolog', 'informaci', 'soporte', 'programador', 'sistemas']);
        }
        if (preg_match('/\b(soporte|tecnico|técnico)\b/u', $q)) {
            $areaNeedles = array_merge($areaNeedles, ['soporte', 'tecnico']);
        }
        if (preg_match('/\bcalidad\b/u', $q)) {
            $areaNeedles[] = 'calidad';
        }
        if (preg_match('/\b(nomina|nómina)\b/u', $q)) {
            $areaNeedles[] = 'nomina';
        }
        if (preg_match('/\b(jurid|juríd)\w*\b/u', $q)) {
            $areaNeedles[] = 'jurid';
        }
        if (preg_match('/\bcompras?\b/u', $q)) {
            $areaNeedles[] = 'compra';
        }
        if (preg_match('/\b(administraci\w*|administrativ\w*)\b/u', $q)) {
            $areaNeedles = array_merge($areaNeedles, ['administraci', 'administrativ']);
        }
        if (preg_match('/\b(programaci\w*|programador(?:es)?)\b/u', $q)) {
            $areaNeedles = array_merge($areaNeedles, ['programaci', 'programador']);
        }
        if (preg_match('/\b(cuentas por pagar|cxp)\b/u', $q)) {
            $areaNeedles = array_merge($areaNeedles, ['cuentas por pagar', 'cuentas']);
        }
        if (preg_match('/\bcontador(?:es|a)?\b/u', $q)) {
            $areaNeedles = array_merge($areaNeedles, ['contabil', 'cuentas por pagar', 'contador']);
        }
        $areaNeedles = array_values(array_unique($areaNeedles));

        // Sin área: no expandir a "todos los auxiliares/coordinadores".
        if (empty($areaNeedles)) {
            return collect();
        }

        return $puestos->filter(function ($p) use ($matchedRoles, $areaNeedles) {
            $name = $this->foldAccents((string) $p->nombre);
            $hasRole = false;
            foreach ($matchedRoles as $role) {
                if (str_contains($name, $role)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                return false;
            }
            foreach ($areaNeedles as $needle) {
                if (str_contains($name, $needle)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function findPuestosMentionedInQuery(string $query): Collection
    {
        // Prioridad: nombre completo exacto (rompe el loop al elegir de la lista).
        $exact = $this->resolveExactPuestoFromQuery($query);
        if ($exact->isNotEmpty()) {
            return $exact;
        }

        $q = $this->foldAccents($query);
        $puestos = $this->getPuestosCatalog();

        // ROL + ÁREA (AND): "auxiliar de tecnologias" ≠ todos los auxiliares.
        $byRoleArea = $this->matchPuestosByRoleAndArea($q, $puestos);
        if ($byRoleArea->isNotEmpty()) {
            return $byRoleArea;
        }

        // Fragmento tras "puesto de/del …" (completo, no solo el cargo).
        if (preg_match('/\bpuesto(?:\s+de|\s+del)?\s+(.+?)(?:\s+en\s+|\s+particip|\s+que\s+|\s+proced|$)/u', $q, $m)) {
            $fragment = trim($m[1]);
            $fragment = preg_replace('/\b(el|la|los|las|un|una)\b/u', '', $fragment) ?? $fragment;
            $fragment = trim(preg_replace('/\s+/u', ' ', $fragment) ?? $fragment);
            if (mb_strlen($fragment) >= 5) {
                $byFragment = $puestos->filter(
                    fn($p) => str_contains($this->foldAccents((string) $p->nombre), $fragment)
                )->values();
                if ($byFragment->isNotEmpty()) {
                    return $byFragment;
                }
            }
        }

        // Tokens de especialidad (NO cargos sueltos: auxiliar/coordinador solos son demasiado amplios).
        $tokens = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $q) ?: [],
            function ($t) {
                $stop = ['puesto', 'puestos', 'procedimiento', 'procedimientos', 'documento', 'documentos',
                    'lista', 'listado', 'participa', 'participan', 'participo', 'donde', 'tiene', 'tienen',
                    'quiero', 'necesito', 'dame', 'dime', 'puedes', 'decir', 'que', 'qué', 'cual', 'cuál',
                    'en', 'de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'por', 'para', 'con', 'me',
                    'mi', 'mis', 'toda', 'todo', 'relacion', 'relación', 'relacionados', 'llamada', 'llama',
                    'asignados', 'asignado', 'auxiliar', 'auxiliares', 'coordinador', 'coordinadores',
                    'gerente', 'director', 'analista', 'jefe'];
                return mb_strlen($t) >= 4 && !in_array($t, $stop, true);
            }
        ));

        if (empty($tokens)) {
            return collect();
        }

        // AND: el nombre del puesto debe contener TODOS los tokens de especialidad.
        return $puestos->filter(function ($p) use ($tokens) {
            $name = $this->foldAccents((string) $p->nombre);
            if ($name === '') {
                return false;
            }
            foreach ($tokens as $token) {
                if (!str_contains($name, $token)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    private function findUnidadesMentionedInQuery(string $query): Collection
    {
        $q = mb_strtolower($query);

        $unidades = Cache::remember('chat_unidades_catalog_v1', 300, function () {
            return UnidadNegocio::query()->select('id_unidad_negocio', 'nombre')->get();
        });

        return $unidades->filter(function ($u) use ($q) {
            $name = mb_strtolower(trim((string) $u->nombre));
            return mb_strlen($name) >= 4 && str_contains($q, $name);
        })->values();
    }

    /**
     * Tipos a usar en listados según lo que pidió el usuario.
     * "procedimientos" ≠ "procesos" (Proyectar Operación IND01 es Proceso).
     */
    private function resolveCatalogTipoNombres(string $query): array
    {
        $q = mb_strtolower($query);
        $pideProcesos = (bool) preg_match('/\bprocesos?\b/u', $q);
        $pideProcedimientos = (bool) preg_match('/\bprocedimientos?\b/u', $q);
        $pidePoliticas = (bool) preg_match('/\bpol[ií]ticas?\b/u', $q);

        if ($pideProcedimientos && !$pideProcesos) {
            $tipos = self::ELEMENTO_TIPOS_PROCEDIMIENTO;
            if ($pidePoliticas) {
                $tipos[] = 'Política';
            }
            return $tipos;
        }

        if ($pideProcesos && !$pideProcedimientos) {
            return ['Proceso'];
        }

        if ($pidePoliticas && !$pideProcedimientos && !$pideProcesos) {
            return ['Política'];
        }

        return self::ELEMENTO_TIPOS_BUSCABLES;
    }

    private function baseCatalogElementoQuery(?array $tipos = null)
    {
        $tipos = $tipos ?: self::ELEMENTO_TIPOS_BUSCABLES;

        return Elemento::with(['tipoElemento', 'puestoResponsable:id_puesto_trabajo,nombre'])
            ->where('status', 'Publicado')
            ->where('active', true)
            ->whereHas('tipoElemento', fn ($q) => $q->whereIn('nombre', $tipos));
    }

    /**
     * Catálogo real de elementos publicados filtrado por tema/área.
     */
    /**
     * Expresión SQL para comparar sin acentos (jurid ≈ juríd).
     */
    private function sqlUnaccentLower(string $column): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE("
            . "COALESCE({$column}, ''),"
            . "'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),'ü','u'),'ñ','n'))";
    }

    private function searchCatalogElementos(array $topicTerms, int $limit = 80, ?array $tipos = null)
    {
        $query = $this->baseCatalogElementoQuery($tipos);
        $nombreFold = $this->sqlUnaccentLower('nombre_elemento');
        $folioFold = $this->sqlUnaccentLower('folio_elemento');

        if (!empty($topicTerms)) {
            $query->where(function ($outer) use ($topicTerms, $nombreFold, $folioFold) {
                foreach ($topicTerms as $term) {
                    $term = $this->foldAccents(trim((string) $term));
                    if ($term === '' || mb_strlen($term) < 3) {
                        continue;
                    }
                    $like = '%' . $term . '%';
                    $outer->orWhereRaw("{$nombreFold} LIKE ?", [$like])
                        ->orWhereRaw("{$folioFold} LIKE ?", [$like]);
                }
            });
        }

        return $query->orderBy('nombre_elemento')->limit($limit)->get();
    }

    /**
     * Elementos ligados a puestos.
     * @param string $roleMode 'all' | 'responsable' | 'relacionado'
     */
    private function searchElementosByPuestoIds(
        array $puestoIds,
        int $limit = 120,
        ?array $tipos = null,
        string $roleMode = 'all'
    ): Collection {
        $puestoIds = array_values(array_unique(array_filter(array_map('intval', $puestoIds))));
        if (empty($puestoIds)) {
            return collect();
        }

        $query = $this->baseCatalogElementoQuery($tipos);

        if ($roleMode === 'responsable') {
            $query->whereIn('puesto_responsable_id', $puestoIds);
        } elseif ($roleMode === 'relacionado') {
            $query->where(function ($q) use ($puestoIds) {
                foreach ($puestoIds as $pid) {
                    $q->orWhereJsonContains('puestos_relacionados', $pid)
                        ->orWhereJsonContains('puestos_relacionados', (string) $pid);
                }
            })->where(function ($q) use ($puestoIds) {
                $q->whereNull('puesto_responsable_id')
                    ->orWhereNotIn('puesto_responsable_id', $puestoIds);
            });
        } else {
            $query->where(function ($q) use ($puestoIds) {
                $q->whereIn('puesto_responsable_id', $puestoIds);
                foreach ($puestoIds as $pid) {
                    $q->orWhereJsonContains('puestos_relacionados', $pid)
                        ->orWhereJsonContains('puestos_relacionados', (string) $pid);
                }
            });
        }

        return $query->orderBy('nombre_elemento')->limit($limit)->get();
    }

    /**
     * Indica el rol del puesto dentro del elemento: Responsable y/o Relacionado.
     */
    private function describePuestoRolesOnElemento($elemento, array $puestoIds): string
    {
        $roles = [];
        $respId = (int) ($elemento->puesto_responsable_id ?? 0);
        if ($respId && in_array($respId, $puestoIds, true)) {
            $roles[] = 'Responsable';
        }

        $relIds = array_map('intval', (array) ($elemento->puestos_relacionados ?? []));
        if (array_intersect($puestoIds, $relIds)) {
            $roles[] = 'Relacionado';
        }

        return empty($roles) ? '' : ' [' . implode(' + ', $roles) . ']';
    }

    private function searchElementosByUnidadIds(array $unidadIds, int $limit = 120, ?array $tipos = null): Collection
    {
        $unidadIds = array_values(array_unique(array_filter(array_map('intval', $unidadIds))));
        if (empty($unidadIds)) {
            return collect();
        }

        return $this->baseCatalogElementoQuery($tipos)
            ->where(function ($q) use ($unidadIds) {
                foreach ($unidadIds as $uid) {
                    $q->orWhereJsonContains('unidad_negocio_id', $uid)
                        ->orWhereJsonContains('unidad_negocio_id', (string) $uid);
                }
            })
            ->orderBy('nombre_elemento')
            ->limit($limit)
            ->get();
    }

    /**
     * Filtra una colección de elementos a los tipos pedidos (recarga tipo si hace falta).
     */
    private function filterElementosCollectionByTipos(Collection $items, array $tipos): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $ids = $items->map(fn ($el) => (int) ($el->getKey() ?? $el->id_elemento ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return Elemento::with(['tipoElemento:id_tipo_elemento,nombre', 'puestoResponsable:id_puesto_trabajo,nombre'])
            ->whereIn('id_elemento', $ids)
            ->whereHas('tipoElemento', fn ($q) => $q->whereIn('nombre', $tipos))
            ->get();
    }

    /**
     * Padres, relacionados e hijos del elemento en foco o nombrado.
     */
    private function searchRelatedElementos(?array $cachedContext, string $query): array
    {
        $elemento = null;
        $tipos = $this->resolveCatalogTipoNombres($query);

        if ($cachedContext && !empty($cachedContext['id'])) {
            $elemento = Elemento::find($cachedContext['id']);
        }

        if (!$elemento) {
            // Intentar por nombre/folio en la pregunta.
            $normalized = mb_strtolower($query);
            $candidato = $this->baseCatalogElementoQuery($tipos)
                ->get(['id_elemento', 'nombre_elemento', 'folio_elemento'])
                ->first(function ($el) use ($normalized) {
                    $nombre = mb_strtolower((string) $el->nombre_elemento);
                    $folio = mb_strtolower((string) $el->folio_elemento);
                    return ($nombre !== '' && str_contains($normalized, $nombre))
                        || ($folio !== '' && mb_strlen($folio) >= 4 && str_contains($normalized, $folio));
                });

            if ($candidato) {
                $elemento = Elemento::find($candidato->id_elemento);
            }
        }

        if (!$elemento) {
            return ['elemento' => null, 'items' => collect(), 'sections' => []];
        }

        $meta = $this->paidAIService->resolveElementoRelatedData($elemento);
        $sections = [];
        $all = collect();

        // Relación principal para el chat: puestos del procedimiento.
        $puestoIds = [];
        if (!empty($elemento->puesto_responsable_id)) {
            $puestoIds[] = (int) $elemento->puesto_responsable_id;
        }
        foreach ((array) ($elemento->puestos_relacionados ?? []) as $pid) {
            $puestoIds[] = (int) $pid;
        }
        $puestoIds = array_values(array_unique(array_filter($puestoIds)));

        if (!empty($puestoIds)) {
            $porPuesto = $this->searchElementosByPuestoIds($puestoIds, 120, $tipos)
                ->filter(fn ($el) => $el->getKey() != $elemento->getKey())
                ->values();

            if ($porPuesto->isNotEmpty()) {
                $sections[] = [
                    'title' => 'Procedimientos ligados a los mismos puestos',
                    'items' => $porPuesto,
                ];
                $all = $all->merge($porPuesto);
            }
        }

        // Padres / relacionados / hijos: solo tipos pedidos (si piden procedimientos, no Procesos).
        $padres = $this->filterElementosCollectionByTipos($meta['padres'], $tipos);
        $relacionados = $this->filterElementosCollectionByTipos($meta['relacionados'], $tipos);
        $hijos = $this->filterElementosCollectionByTipos($meta['hijos'], $tipos);

        if ($padres->isNotEmpty()) {
            $sections[] = ['title' => 'Elementos padre', 'items' => $padres];
            $all = $all->merge($padres);
        }
        if ($relacionados->isNotEmpty()) {
            $sections[] = ['title' => 'Elementos relacionados', 'items' => $relacionados];
            $all = $all->merge($relacionados);
        }
        if ($hijos->isNotEmpty()) {
            $sections[] = ['title' => 'Elementos hijos', 'items' => $hijos];
            $all = $all->merge($hijos);
        }

        return [
            'elemento' => $elemento,
            'items' => $all->unique('id_elemento')->values(),
            'sections' => $sections,
        ];
    }

    private function formatElementoCatalogLine($el): string
    {
        $folio = $el->folio_elemento ?: 's/folio';
        $ver = $el->version_elemento ?: '?';
        $tipo = $this->friendlyTipoElementoNombre(optional($el->tipoElemento)->nombre);
        // Lista corta y legible; el detalle (unidades/puestos) al pedir un folio concreto.
        $resp = optional($el->puestoResponsable)->nombre
            ?: (optional($el->loadMissing('puestoResponsable')->puestoResponsable)->nombre);
        $respTxt = $resp ? " — Responsable: {$resp}" : '';

        return "- **{$folio}**: {$el->nombre_elemento} (v{$ver}) · {$tipo}{$respTxt}";
    }

    /**
     * Puesto del usuario para listados (misma idea que el mapa naranja).
     * No se anula por ser admin: "mis procedimientos" debe listar su relación real.
     */
    private function resolvePuestoUsuarioForLists(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // Igual que MapaProcesos: primero por correo del empleado.
        if (!empty($user->email)) {
            $byEmail = Empleados::where('correo', $user->email)
                ->whereNull('deleted_at')
                ->value('puesto_trabajo_id');
            if ($byEmail) {
                return (int) $byEmail;
            }
        }

        $byName = $this->userPuestoService->obtenerPuesto($user);
        return $byName ? (int) $byName : null;
    }

    private function buildPuestoCatalogResult(
        Collection $puestos,
        array $extra = [],
        ?array $tipos = null,
        ?string $queryHint = null
    ): array {
        $ids = $puestos->pluck('id_puesto_trabajo')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $label = 'puesto(s): ' . $puestos->pluck('nombre')->implode(', ');
        $q = mb_strtolower((string) $queryHint);
        $soloPropios = (bool) preg_match('/\b(propios?|solo (como )?responsable|como responsable)\b/u', $q);

        $comoResponsable = $this->searchElementosByPuestoIds($ids, 200, $tipos, 'responsable');
        $comoRelacionadoAll = $this->searchElementosByPuestoIds($ids, 200, $tipos, 'relacionado');
        $comoRelacionado = $soloPropios ? collect() : $comoRelacionadoAll;

        $lista = '';
        if ($comoResponsable->isNotEmpty()) {
            $lista .= "**Como responsable** (" . $comoResponsable->count() . "):\n";
            foreach ($comoResponsable as $el) {
                $lista .= $this->formatElementoCatalogLine($el) . " [Responsable]\n";
            }
            $lista .= "\n";
        }

        if ($soloPropios && $comoResponsable->isEmpty()) {
            $lista = "Ese puesto **no figura como responsable** de ningún procedimiento publicado.\n";
            if ($comoRelacionadoAll->isNotEmpty()) {
                $lista .= "Sí aparece como **relacionado** en " . $comoRelacionadoAll->count()
                    . ". Si quieres verlos, escribe: **qué procedimientos tienen asignados**.";
            }
        }

        if ($comoRelacionado->isNotEmpty()) {
            if ($comoResponsable->isEmpty()) {
                $lista .= "No figura como **responsable** de ningún procedimiento publicado.\n\n";
            }
            $lista .= "**Como relacionado** (" . $comoRelacionado->count() . ") "
                . "— participa, pero no es el responsable del documento:\n";
            foreach ($comoRelacionado->take(25) as $el) {
                $lista .= $this->formatElementoCatalogLine($el) . " [Relacionado]\n";
            }
            if ($comoRelacionado->count() > 25) {
                $lista .= "- … y " . ($comoRelacionado->count() - 25) . " más\n";
            }
            if ($comoResponsable->isNotEmpty()) {
                $lista .= "\nSi solo quieres los **propios** (como responsable), escribe: **propios del puesto**.";
            }
        }

        if ($lista === '') {
            $lista = "No encontré procedimientos publicados ligados a ese puesto.";
        }

        $elementos = $soloPropios
            ? $comoResponsable->values()
            : $comoResponsable->merge($comoRelacionado)->unique('id_elemento')->values();

        return array_merge([
            'mode' => 'by_puesto',
            'label' => $label,
            'elementos' => $elementos,
            'lista_texto' => trim($lista),
            'document' => null,
            'final_context' => null,
            'catalog_state' => [
                'mode' => 'by_puesto',
                'puesto_ids' => $ids,
                'puesto_nombres' => $puestos->pluck('nombre')->values()->all(),
                'label' => $label,
            ],
        ], $extra);
    }

    /**
     * Resuelve el modo de listado y los elementos desde la BD.
     *
     * @return array{mode:string,label:string,elementos:Collection,lista_texto:string,document:?array}
     */
    private function resolveCatalogBrowseData(
        string $originalQuery,
        string $searchQuery,
        ?array $cachedContext,
        ?array $forcedCatalogState = null
    ): array {
        $combined = $originalQuery . ' ' . $searchQuery;
        $q = mb_strtolower($combined);
        $tipos = $this->resolveCatalogTipoNombres($combined);

        // Seguimiento con filtro de ÁREA previo ("¿son todos los del área?").
        if (is_array($forcedCatalogState) && !empty($forcedCatalogState['area_ids'])) {
            $areaIds = array_map('intval', $forcedCatalogState['area_ids']);
            $topicTerms = $forcedCatalogState['topic_terms'] ?? [];
            $agrupar = (bool) ($forcedCatalogState['grouped'] ?? false);

            // Mantener el mismo orden/agrupación del listado que originó el seguimiento.
            $areasFollow = $this->getAreasCatalog()
                ->whereIn('id_area', $areaIds)
                ->sortBy(fn ($a) => array_search((int) $a->id_area, $areaIds, true))
                ->values();

            if ($areasFollow->isEmpty()) {
                $nombresArea = !empty($forcedCatalogState['area_nombres'])
                    ? implode(', ', $forcedCatalogState['area_nombres'])
                    : 'área';
                $elementos = $this->searchElementosOfArea($areaIds, $topicTerms, 120, $tipos);
                $areaResult = [
                    'elementos' => $elementos,
                    'lista_texto' => $elementos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n"),
                    'label' => 'del área ' . $nombresArea,
                    'area_nombres' => $forcedCatalogState['area_nombres'] ?? [],
                    'topic_terms' => $topicTerms,
                    'area_topic_terms' => null,
                    'grouped' => false,
                ];
            } else {
                $areaResult = $this->buildAreaCatalogResult(
                    $areasFollow,
                    $agrupar ? [] : $topicTerms,
                    $tipos,
                    $agrupar,
                    $forcedCatalogState['area_topic_terms'] ?? null
                );
            }

            return [
                'mode' => 'by_area',
                'label' => $areaResult['label'],
                'elementos' => $areaResult['elementos'],
                'lista_texto' => $areaResult['lista_texto'],
                'document' => null,
                'final_context' => null,
                'tipos' => $tipos,
                'catalog_state' => [
                    'mode' => 'by_area',
                    'area_ids' => $areaIds,
                    'area_nombres' => $areaResult['area_nombres'],
                    'topic_terms' => $areaResult['topic_terms'],
                    'area_topic_terms' => $areaResult['area_topic_terms'],
                    'grouped' => $areaResult['grouped'],
                    'label' => $areaResult['label'],
                ],
            ];
        }

        // Seguimiento "toda la lista" con filtro de puesto previo.
        if (is_array($forcedCatalogState) && !empty($forcedCatalogState['puesto_ids'])) {
            $puestos = PuestoTrabajo::whereIn('id_puesto_trabajo', $forcedCatalogState['puesto_ids'])->get();
            if ($puestos->isNotEmpty()) {
                return $this->buildPuestoCatalogResult($puestos, [], $tipos, $combined);
            }
        }

        // 0) "soy un contador / analista de cuentas por pagar, qué procedimientos tengo"
        $claimedPuestos = $this->resolveClaimedPuestoFromQuery($combined);
        if ($claimedPuestos->isNotEmpty() && preg_match('/\b(procedimientos?|documentos?|tengo)\b/u', $q)) {
            return $this->buildPuestoCatalogResult($claimedPuestos, [], $tipos, $combined);
        }

        // 0b) Mis procedimientos / donde tengo relación → puesto del usuario logueado
        if ($this->isMyProceduresQuery($q) || preg_match('/\bmi puesto\b/u', $q)) {
            $puestoId = $this->resolvePuestoUsuarioForLists();
            if ($puestoId) {
                $p = PuestoTrabajo::find($puestoId);
                if ($p) {
                    return $this->buildPuestoCatalogResult(collect([$p]), [], $tipos, $combined);
                }
            }

            // NUNCA caer al catálogo global (los 69): el usuario pidió LOS SUYOS.
            return [
                'mode' => 'by_puesto_empty_user',
                'label' => 'mis procedimientos',
                'elementos' => collect(),
                'lista_texto' => "No tengo un **puesto** ligado a tu usuario en el directorio, "
                    . "así que no puedo armar tu lista personal.\n\n"
                    . "Puedes:\n"
                    . "- Pedir procedimientos de un **puesto** (ej. procedimientos del Analista Jurídico)\n"
                    . "- Pedir por **área** (ej. procedimientos de Compras)\n"
                    . "- O darme un **folio / nombre** de documento\n\n"
                    . "Si crees que deberías tener puesto asignado, revisa que tu correo de usuario "
                    . "coincida con el del empleado en el sistema.",
                'document' => null,
                'final_context' => null,
                'catalog_state' => null,
            ];
        }

        // 1) Procedimientos relacionados del documento en foco / nombrado
        if ($this->isRelatedProceduresListQuery($q)) {
            $related = $this->searchRelatedElementos($cachedContext, $combined);
            if ($related['elemento']) {
                $lista = '';
                if (empty($related['sections'])) {
                    $lista = "(Sin padres, relacionados ni hijos registrados en BD para este elemento.)\n";
                } else {
                    foreach ($related['sections'] as $section) {
                        $lista .= "**{$section['title']}:**\n";
                        foreach ($section['items'] as $item) {
                            $lista .= '- ' . ($item->folio_elemento ?: 's/folio') . ': '
                                . $item->nombre_elemento
                                . ' (v' . ($item->version_elemento ?? '?') . ")\n";
                        }
                        $lista .= "\n";
                    }
                }

                return [
                    'mode' => 'related',
                    'label' => 'relacionados de ' . ($related['elemento']->nombre_elemento ?? 'elemento'),
                    'elementos' => $related['items'],
                    'lista_texto' => trim($lista),
                    'document' => $this->buildDocumentCard($related['elemento']),
                    'final_context' => [
                        'id' => $related['elemento']->getKey(),
                        'title' => $related['elemento']->nombre_elemento,
                    ],
                ];
            }
        }

        // 2) Por área organizacional (antes que puesto: "de Jurídico" no debe pedir aclarar puestos)
        if ($this->isAreaListQuery($combined) || $this->isAreaListQuery($originalQuery)) {
            $areas = $this->findAreasMentionedInQuery($combined);
            if ($areas->isEmpty()) {
                $areas = $this->findAreasMentionedInQuery($originalQuery);
            }
            // Alias cortos (rh, sistemas, legal…) que el matcher laxo no reconoce.
            if ($areas->isEmpty()) {
                $areas = $this->findExplicitAreasInQuery($combined);
            }

            if ($areas->isNotEmpty()) {
                $qFold = $this->foldAccents($combined);

                // Áreas nombradas de forma explícita (nombre completo o alias corto).
                // Varias en un mismo prompt → una sección por área.
                $explicitas = $this->findExplicitAreasInQuery($combined);
                if ($explicitas->isEmpty()) {
                    $explicitas = $this->findExplicitAreasInQuery($originalQuery);
                }

                $agrupar = $explicitas->count() > 1;

                if ($explicitas->isNotEmpty()) {
                    $prioritarias = $explicitas;
                } else {
                    // Sin mención explícita: comportamiento previo (coincidencia laxa).
                    $prioritarias = $areas->take(2);
                }

                // DEL área = responsable del área + nombre/folio del tema.
                // NO "cualquier procedimiento donde aparezca un puesto de Compras como relacionado".
                // Con varias áreas el tema base se omite: cada área aporta el suyo.
                $baseTopicTerms = $agrupar ? [] : $this->extractCatalogTopicTerms($combined);
                if (!$agrupar && preg_match('/\bcompras?\b/u', $qFold)) {
                    array_push($baseTopicTerms, 'compra', 'proveedor', 'proveedores');
                }
                if (!$agrupar && preg_match('/\b(ti|tecnolog)\b/u', $qFold)) {
                    $baseTopicTerms[] = 'tecnolog';
                }

                $areaResult = $this->buildAreaCatalogResult(
                    $prioritarias,
                    $baseTopicTerms,
                    $tipos,
                    $agrupar
                );

                // Nombres anidados ("Contabilidad y Finanzas" gana sobre "Contabilidad"
                // y "Finanzas"): si el área específica no tiene documentos, ampliar la
                // búsqueda a las áreas que absorbió, conservando la etiqueta pedida.
                if ($areaResult['elementos']->isEmpty() && !$agrupar) {
                    $ampliadas = $this->findExplicitAreasInQuery($combined, false);
                    if ($ampliadas->count() > $prioritarias->count()) {
                        $etiqueta = $areaResult['label'];
                        $areaResult = $this->buildAreaCatalogResult(
                            $ampliadas,
                            $baseTopicTerms,
                            $tipos,
                            false
                        );
                        $areaResult['label'] = $etiqueta;
                        $areaResult['area_nombres'] = $prioritarias->pluck('nombre')->values()->all();
                        $prioritarias = $ampliadas;
                    }
                }

                return [
                    'mode' => 'by_area',
                    'label' => $areaResult['label'],
                    'elementos' => $areaResult['elementos'],
                    'lista_texto' => $areaResult['lista_texto'],
                    'document' => null,
                    'final_context' => null,
                    'tipos' => $tipos,
                    'catalog_state' => [
                        'mode' => 'by_area',
                        'area_ids' => $prioritarias->pluck('id_area')->map(fn ($id) => (int) $id)->values()->all(),
                        'area_nombres' => $areaResult['area_nombres'],
                        'topic_terms' => $areaResult['topic_terms'],
                        'area_topic_terms' => $areaResult['area_topic_terms'],
                        'grouped' => $areaResult['grouped'],
                        'label' => $areaResult['label'],
                    ],
                ];
            }
        }

        // 3) Por puesto (solo si nombró puesto o dijo "por puesto" / "puesto de…")
        if ($this->isPuestoListQuery($q)) {
            $puestos = $this->resolveExactPuestoFromQuery($originalQuery);
            if ($puestos->isEmpty()) {
                $puestos = $this->resolveExactPuestoFromQuery($searchQuery);
            }
            // Sin nombre exacto: ampliar solo si la pregunta habla explícitamente de puesto.
            if ($puestos->isEmpty() && preg_match('/\bpuestos?\b/u', $q)) {
                $puestos = $this->findPuestosMentionedInQuery($combined);
            }

            // Si habla de "participa" sin puesto claro → usar el del usuario (mapa naranja).
            if ($puestos->isEmpty() && preg_match('/\b(participa|participo|participan)\b/u', $q)) {
                $puestoId = $this->resolvePuestoUsuarioForLists();
                if ($puestoId) {
                    $p = PuestoTrabajo::find($puestoId);
                    if ($p) {
                        $puestos = collect([$p]);
                    }
                }
            }

            if ($puestos->isNotEmpty()) {
                // Varios puestos: aclarar SOLO si pidió por puesto (no por área/tema).
                $yaEsExacto = $this->resolveExactPuestoFromQuery($originalQuery)->isNotEmpty()
                    || $this->resolveExactPuestoFromQuery($searchQuery)->isNotEmpty();
                if ($puestos->count() > 3 && !$yaEsExacto) {
                    $opciones = $puestos->take(10)->pluck('nombre')
                        ->map(fn ($n) => "- {$n}")
                        ->implode("\n");

                    return [
                        'mode' => 'by_puesto_clarify',
                        'label' => 'aclarar puesto',
                        'elementos' => collect(),
                        'lista_texto' => "Encontré varios puestos. ¿Cuál te interesa?\n\n{$opciones}\n\n"
                            . "Copia el **nombre completo** y te listo solo sus procedimientos.\n\n"
                            . "Si en realidad querías el **listado por área**, escribe por ejemplo: "
                            . "**procedimientos de Jurídico** o **procedimientos de Presupuestos**.",
                        'document' => null,
                        'final_context' => null,
                        'catalog_state' => null,
                    ];
                }

                return $this->buildPuestoCatalogResult($puestos, [], $tipos, $combined);
            }

            // "listado por puesto" sin nombre: agrupa solo por puesto_responsable_id
            // y puestos_relacionados (no ejecutor/resguardo/comités).
            if (preg_match('/\bpor puesto\b/u', $q)) {
                $elementos = $this->baseCatalogElementoQuery($tipos)
                    ->orderBy('nombre_elemento')
                    ->limit(150)
                    ->get();

                $grouped = [];
                foreach ($elementos as $el) {
                    $meta = $this->paidAIService->resolveElementoRelatedData($el);

                    // Responsable
                    $respNombre = optional($el->puestoResponsable)->nombre;
                    if ($respNombre) {
                        $grouped[$respNombre][] = [
                            'el' => $el,
                            'rol' => 'Responsable',
                        ];
                    }

                    // Relacionados (puestos_relacionados)
                    foreach ($meta['puestos_relacionados'] as $puestoRel) {
                        $nombreRel = $puestoRel->nombre ?? null;
                        if (!$nombreRel) {
                            continue;
                        }
                        // Evitar duplicar si es el mismo que el responsable
                        if ($respNombre && mb_strtolower($nombreRel) === mb_strtolower($respNombre)) {
                            continue;
                        }
                        $grouped[$nombreRel][] = [
                            'el' => $el,
                            'rol' => 'Relacionado',
                        ];
                    }

                    if (!$respNombre && $meta['puestos_relacionados']->isEmpty()) {
                        $grouped['Sin puesto asignado'][] = [
                            'el' => $el,
                            'rol' => '',
                        ];
                    }
                }

                ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
                $lista = '';
                foreach ($grouped as $puestoNombre => $items) {
                    $lista .= "**{$puestoNombre}** (" . count($items) . "):\n";
                    foreach ($items as $row) {
                        $el = $row['el'];
                        $rol = $row['rol'] !== '' ? " [{$row['rol']}]" : '';
                        $lista .= '- ' . ($el->folio_elemento ?: 's/folio') . ': '
                            . $el->nombre_elemento
                            . ' (v' . ($el->version_elemento ?? '?') . ')'
                            . $rol . "\n";
                    }
                    $lista .= "\n";
                }

                return [
                    'mode' => 'by_puesto_grouped',
                    'label' => 'listado agrupado por puesto',
                    'elementos' => $elementos,
                    'lista_texto' => trim($lista),
                    'document' => null,
                    'final_context' => null,
                ];
            }
        }

        // 4) Por unidad de negocio
        if ($this->isUnidadListQuery($q)) {
            $unidades = $this->findUnidadesMentionedInQuery($q);
            if ($unidades->isNotEmpty()) {
                $elementos = $this->searchElementosByUnidadIds(
                    $unidades->pluck('id_unidad_negocio')->all(),
                    120,
                    $tipos
                );
                $label = 'de la unidad ' . $unidades->pluck('nombre')->implode(', ');
                $lista = $elementos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");

                return [
                    'mode' => 'by_unidad',
                    'label' => $label,
                    'elementos' => $elementos,
                    'lista_texto' => $lista,
                    'document' => null,
                    'final_context' => null,
                    'tipos' => $tipos,
                ];
            }
        }

        // 5) Por tema / nombre (comportamiento previo)
        $topicTerms = $this->extractCatalogTopicTerms($combined);
        $elementos = $this->searchCatalogElementos($topicTerms, 80, $tipos);
        $label = $this->buildCatalogUserLabel($combined, $topicTerms, $tipos);
        $lista = $elementos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");

        return [
            'mode' => 'by_topic',
            'label' => $label,
            'elementos' => $elementos,
            'lista_texto' => $lista,
            'document' => null,
            'final_context' => null,
            'topic_terms' => $topicTerms,
            'tipos' => $tipos,
        ];
    }

    /**
     * Etiqueta legible para el usuario (sin términos técnicos de filtro).
     */
    private function buildCatalogUserLabel(string $query, array $topicTerms, array $tipos): string
    {
        $q = mb_strtolower($query);
        $tipoTxt = 'procedimientos';
        if (in_array('Proceso', $tipos, true) && count($tipos) === 1) {
            $tipoTxt = 'procesos';
        } elseif (in_array('Política', $tipos, true) && !in_array('Procedimiento', $tipos, true)) {
            $tipoTxt = 'políticas';
        }

        if (preg_match('/\b(ti|t\.i\.?|tecnolog)/u', $q)) {
            return "{$tipoTxt} de tecnología / TI";
        }
        if (preg_match('/\bcalidad\b/u', $q)) {
            return "{$tipoTxt} de calidad";
        }
        if (preg_match('/\bjur[ií]dic/u', $q)) {
            return "{$tipoTxt} de jurídico";
        }
        if (preg_match('/\b(por\s+[aá]rea|[aá]rea\s+de)/u', $q)) {
            return $tipoTxt . ' por área';
        }
        if (!empty($topicTerms)) {
            // Solo palabras humanas, no stems técnicos.
            $amigables = collect($topicTerms)
                ->reject(fn ($t) => in_array($this->foldForCatalogLabel($t), ['tecnolog', 'informacion', 'informaci'], true))
                ->take(3)
                ->implode(', ');
            if ($amigables !== '') {
                return "{$tipoTxt} relacionados con {$amigables}";
            }
        }

        return $tipoTxt . ' publicados';
    }

    private function foldForCatalogLabel(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    /**
     * Nombre de tipo amigable en listados (oculta sufijos técnicos).
     */
    private function friendlyTipoElementoNombre(?string $tipo): string
    {
        $tipo = trim((string) $tipo);
        if ($tipo === '') {
            return 'Elemento';
        }
        if (strcasecmp($tipo, 'Procedimiento_Firmas') === 0) {
            return 'Procedimiento';
        }
        if (strcasecmp($tipo, 'Reglamento_Firmas') === 0) {
            return 'Reglamento';
        }

        return $tipo;
    }

    /**
     * Responde listados desde el inventario/relaciones de la BD.
     */
    private function generateCatalogBrowseResponse(
        string $originalQuery,
        string $searchQuery,
        $startTime,
        $userId,
        $sessionId,
        ?array $cachedContext = null,
        ?array $forcedCatalogState = null
    ): array {
        \Cache::forget($this->getPendingContactKey($sessionId, $userId));

        $data = $this->resolveCatalogBrowseData(
            $originalQuery,
            $searchQuery,
            $cachedContext,
            $forcedCatalogState
        );
        $elementos = $data['elementos'];
        $listaTexto = $data['lista_texto'];
        $filtro = $data['label'];
        $mode = $data['mode'] ?? 'by_topic';

        \Log::info('Chatbot catálogo / lista BD', [
            'query' => $originalQuery,
            'mode' => $mode,
            'label' => $filtro,
            'found' => $elementos instanceof Collection ? $elementos->count() : 0,
        ]);

        // Mensaje ya armado (ej. sin puesto de usuario): devolverlo tal cual.
        if ($mode === 'by_puesto_empty_user' && trim((string) $listaTexto) !== '') {
            return [
                'response' => $listaTexto,
                'method' => 'catalog_browse_empty_user',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => ['catalog_mode' => $mode],
                'cached' => false,
                'document' => null,
                'analytics_id' => $this->logAnalytics(
                    $originalQuery,
                    $listaTexto,
                    'catalog_browse_empty_user',
                    $startTime,
                    $userId,
                    $sessionId
                ),
            ];
        }

        if (($elementos instanceof Collection && $elementos->isEmpty()) && trim((string) $listaTexto) === '') {
            if ($mode === 'by_area') {
                $msg = "No encontré procedimientos vinculados a puestos de esa área.\n\n"
                    . "Puedes probar por **puesto** (ej. Jefe Jurídico) o por otro nombre de área.";
            } elseif (in_array($mode, ['by_puesto', 'by_puesto_grouped'], true)) {
                $msg = "No encontré procedimientos ligados a ese puesto.\n\n"
                    . "Prueba con el nombre completo del puesto o escribe **mis procedimientos**.";
            } else {
                $msg = "No encontré resultados con ese criterio.\n\n"
                    . "Puedes pedir listado **por área** (ej. procedimientos de Jurídico) "
                    . "o **por puesto** (ej. procedimientos del Analista Jurídico).";
            }

            return [
                'response' => $msg,
                'method' => 'catalog_browse_empty',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [
                    'catalog_mode' => $mode,
                    'catalog_label' => $filtro,
                ],
                'cached' => false,
                'document' => null,
                'analytics_id' => $this->logAnalytics($originalQuery, $msg, 'catalog_browse_empty', $startTime, $userId, $sessionId),
            ];
        }

        $count = $elementos instanceof Collection ? $elementos->count() : 0;

        // Aclaración de puesto: devolver el menú tal cual, sin IA ni ficha.
        if ($mode === 'by_puesto_clarify') {
            $analyticsId = $this->logAnalytics(
                $originalQuery,
                $listaTexto,
                'catalog_browse_clarify',
                $startTime,
                $userId,
                $sessionId
            );

            return [
                'response' => $listaTexto,
                'method' => 'catalog_browse_clarify',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => ['catalog_mode' => $mode],
                'cached' => false,
                'document' => null,
                'analytics_id' => $analyticsId,
                'catalog_state' => null,
            ];
        }

        // Listados de catálogo: respuesta DIRECTA de BD (sin metadatos técnicos de filtro).
        $nombresList = $data['catalog_state']['puesto_nombres'] ?? [];
        if (in_array($mode, ['by_puesto', 'by_puesto_grouped'], true)) {
            if (count($nombresList) > 3) {
                $nombres = implode(', ', array_slice($nombresList, 0, 3))
                    . ' (+' . (count($nombresList) - 3) . ' puestos)';
            } elseif (!empty($nombresList)) {
                $nombres = implode(', ', $nombresList);
            } else {
                $nombres = '';
            }

            $esMios = $this->isMyProceduresQuery($originalQuery)
                || (($data['catalog_state']['label'] ?? '') === 'mis procedimientos');
            if ($esMios && $nombres !== '') {
                $aiResponse = "Estos son los **{$count}** procedimientos ligados a tu puesto (**{$nombres}**):\n\n"
                    . $listaTexto
                    . "\n\nSi quieres el detalle de alguno, dime el folio o el nombre.";
            } else {
                $aiResponse = "Encontré **{$count}**"
                    . ($nombres !== '' ? " relacionados con **{$nombres}**" : '')
                    . ":\n\n"
                    . $listaTexto
                    . "\n\nSi quieres el detalle de alguno, dime el folio o el nombre.";
            }
        } elseif ($mode === 'related') {
            $aiResponse = "Esto es lo relacionado que encontré (**{$count}**):\n\n"
                . $listaTexto
                . "\n\nSi quieres el detalle de alguno, dime el folio o el nombre.";
        } else {
            $tema = is_string($filtro) && $filtro !== '' ? $filtro : 'lo que pediste';
            $aiResponse = "Encontré **{$count}** {$tema}:\n\n"
                . $listaTexto
                . "\n\nSi quieres el detalle de alguno, dime el folio o el nombre.";
        }

        $analyticsId = $this->logAnalytics(
            $originalQuery,
            $aiResponse,
            'catalog_browse',
            $startTime,
            $userId,
            $sessionId
        );

        $result = [
            'response' => $aiResponse,
            'method' => 'catalog_browse',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [
                'catalog_mode' => $mode,
                'catalog_label' => $filtro,
                'documents_found' => $count,
                'topic_terms' => $data['topic_terms'] ?? [],
            ],
            'cached' => false,
            'document' => null, // no ficha de un PDF ajeno en listados por puesto
            'analytics_id' => $analyticsId,
            'catalog_state' => $data['catalog_state'] ?? null,
        ];

        if (!empty($data['final_context']) && ($mode === 'related')) {
            $result['final_context'] = $data['final_context'];
            $result['document'] = $data['document'] ?? null;
        }

        return $result;
    }

    /**
     * Preguntas de la empresa / organigrama (no del procedimiento en foco).
     * Ej: "unidades de la empresa", "directores de esas áreas".
     */
    private function isCompanyOrgQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        // Si habla de un documento concreto, no es org general.
        if (
            preg_match('/\b(folio|[a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
            || preg_match('/\b(objetivo|alcance|riesgos?|definiciones?|pasos|actividades)\b/u', $q)
        ) {
            return false;
        }

        // Unidades / divisiones / áreas / cómo está organizada (catálogo global).
        if (
            preg_match('/\b(unidades?|divisiones?|[aá]reas?|organizada|organigrama|estructura)\b/u', $q)
            && preg_match(
                '/\b(empresa|negocio|proser|organizaci[oó]n|hay|cu[aá]les|todas|lista|listado|'
                . 'dime|decir|como esta|c[oó]mo est[aá]|que hay|qu[eé] hay)\b/u',
                $q
            )
            && !preg_match('/\b(procedimiento|documento|de este|de ese|aplican)\b/u', $q)
        ) {
            return true;
        }

        // "áreas de la empresa?", "qué áreas hay", "cómo está organizada" (sin verbo extra).
        if (
            preg_match('/\b([aá]reas? de la empresa|que [aá]reas|qu[eé] [aá]reas|como esta organizada|'
                . 'c[oó]mo est[aá] organizada|organigrama)\b/u', $q)
            && !preg_match('/\b(procedimiento|documento|folio)\b/u', $q)
        ) {
            return true;
        }

        // Directores: lista explícita O con unidades/áreas/empresa.
        if (
            preg_match('/\bdirectores?\b/u', $q)
            && (
                preg_match('/\b(lista|listado|listar|dame|dime|mu[eé]strame|cu[aá]les|todos)\b/u', $q)
                || preg_match('/\b(unidad(es)?|[aá]reas?|empresa|esas|esos|negocio)\b/u', $q)
            )
            && (
                !preg_match('/\b(procedimiento|documento|folio)\b/u', $q)
                || preg_match('/\b(no tiene que ver|nada que ver|no es (de|un)|ya no)\b/u', $q)
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * "quiénes son de jurídico", "personas del área de TI", "el equipo de calidad".
     * Lista gente del área, no procedimientos ni un puesto concreto.
     */
    private function isPeopleOfAreaQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        $pideDocumentos = (bool) preg_match(
            '/\b(procedimientos?|documentos?|folios?|pol[ií]ticas?|lineamientos?)\b/u',
            $q
        );
        $pidePersonas = (bool) preg_match(
            '/\b(qui[eé]nes son|quienes son|qui[eé]n son|qui[eé]n es de|quien es de|'
            . 'personas|gente|equipo|staff|n[oó]mina de|'
            . 'colaboradores?|empleados?|'
            . 'qui[eé]nes (trabajan|est[aá]n|integran|pertenecen)|'
            . 'qui[eé]n trabaja|quienes trabajan|'
            . 'qui[eé]nes hay|'
            . 'lista\w*|nombres? completos?)\b/u',
            $q
        );

        if (
            preg_match('/\b(coordinador(?:es|as)?|gerente(?:s)?|director(?:es|as)?|'
            . 'auxiliar(?:es)?|analista(?:s)?|jefe(?:s|as)?)\b/u', $q)
            && !preg_match('/\b(personas|gente|equipo|tod[oa]s)\b/u', $q)
        ) {
            return false;
        }

        if ($pideDocumentos && !$this->isRejectingOfferedOptions($q)) {
            return false;
        }

        if (
            preg_match('/\b(qui[eé]n|quien).{0,40}\b(responsables?|encargad[oa]s?)\b/u', $q)
            && !preg_match('/\b([aá]rea|departamento|unidad)\b/u', $q)
            && $this->findAreasMentionedInQuery($q)->isEmpty()
        ) {
            return false;
        }

        $areas = $this->findAreasMentionedInQuery($q);
        if ($areas->isEmpty()) {
            $areas = $this->findExplicitAreasInQuery($q);
        }

        if (!$pidePersonas && $areas->isNotEmpty()
            && preg_match('/\bqui[eé]n(es)? (es|son)\b/u', $q)
        ) {
            $pidePersonas = true;
        }

        if (!$pidePersonas) {
            return false;
        }

        return $areas->isNotEmpty()
            || (bool) preg_match('/\b([aá]rea|departamento|unidad)\b/u', $q);
    }

    /**
     * Preguntas de directorio organizacional (quién ocupa un puesto, responsable de unidad…).
     * Aún no apuntan a un procedimiento: no deben abrir ficha/RAG.
     */
    private function isPeopleOrOrgDirectoryQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if ($this->isCompanyOrgQuery($q)) {
            return true;
        }

        if ($this->queryNamesDirectoryPuesto($query)
            && !preg_match('/\b(procedimientos?|documentos?|folios?)\b/u', $q)
        ) {
            return true;
        }

        // "quién es [nombre]" / "es un empleado": directorio, no el PDF en foco.
        if ($this->isWhoIsPersonQuery($q) || $this->isEmployeeConfirmQuery($q)) {
            return true;
        }

        // "quiénes son de jurídico / personas del área de TI"
        if ($this->isPeopleOfAreaQuery($q)) {
            return true;
        }

        // Solo salir del directorio si habla de un documento… salvo que lo esté NEGANDO
        // ("no es de procedimientos, quién es Eduardo").
        $niegaDocumento = (bool) preg_match(
            '/\bno\s+es\s+(de\s+)?(eso|este|esta|un|una\s+)?(procedimientos?|documentos?|folios?|pol[ií]ticas?)\b/u',
            $q
        );

        if (
            !$niegaDocumento
            && (
                preg_match('/\b(procedimiento|documento|folio|pol[ií]tica|lineamiento|elemento|proceso)\b/u', $q)
                || preg_match('/\b([a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
            )
        ) {
            return false;
        }

        // "quién es el responsable" / "quién es el responsable de [tema]":
        // sección del procedimiento, no directorio de personas.
        if (
            preg_match('/\b(qui[eé]n|quien|cu[aá]l).{0,60}\b(re?sponsables?|encargad[oa]s?)\b/u', $q)
            && !preg_match('/\b(unidad|[aá]rea|empresa|departamento|puesto)\b/u', $q)
            && !preg_match(
                '/\b(coordinador(?:es|as)?|gerente(?:s)?|director(?:es|as)?|auxiliar(?:es)?|analista(?:s)?|jefe(?:s|as)?)\b/u',
                $q
            )
        ) {
            return false;
        }

        // "quién me puede ayudar / a quién le pregunto / con quién me comunico"
        if (
            $this->isWhoToContactQuery($q)
            && !preg_match('/\b(procedimiento|documento|folio|objetivo|alcance)\b/u', $q)
        ) {
            return true;
        }

        // "cómo se llama / se llamada / se llaman…" (tolera typos comunes)
        $pidePersona = (bool) preg_match(
            '/\b(c[oó]mo se llam\w*|qui[eé]n es|quien es|qui[eé]n ocupa|quien ocupa|'
            . 'nombre del|nombre de la|a cargo|qui[eé]nes son|quienes son|'
            . 'dime qui[eé]n|me puedes decir|puedes decir|'
            . 'mi jefe|mi jefa|qui[eé]n me reporta|quien me reporta|'
            . 'qui[eé]n es mi jefe|quien es mi jefe|qui[eé]n es mi jefa|'
            . 'qui[eé]n me puede ayudar|a qui[eé]n (le )?pregunto|'
            . 'personas|gente|equipo|colaboradores?)\b/u',
            $q
        );
        $hablaDePuesto = (bool) preg_match(
            '/\b(coordinador(?:es|as)?|gerente(?:s)?|director(?:es|as)?|'
            . 'auxiliar(?:es)?|analista(?:s)?|jefe(?:s|as)?|responsable(?:s)?|'
            . 'encargad[oa](?:s)?|puestos?)\b/u',
            $q
        );
        $hablaDeUnidad = (bool) preg_match(
            '/\b(unidades?|corporativo|departamentos?|[aá]reas?|empresa|organizaci[oó]n)\b/u',
            $q
        );

        if ($pidePersona && ($hablaDePuesto || $hablaDeUnidad || $this->findAreasMentionedInQuery($q)->isNotEmpty())) {
            return true;
        }

        // Pregunta directa al puesto sin verbo "llamar": "el coordinador de TI", "coordinador de calidad"
        if (
            $hablaDePuesto
            && preg_match('/\b(de|del)\b/u', $q)
            && !preg_match('/\b(objetivo|alcance|riesgos?|definiciones?|pasos|actividades)\b/u', $q)
        ) {
            return true;
        }

        // "responsable de la unidad Corporativo" sin procedimiento.
        if (
            preg_match('/\bresponsables?\b/u', $q)
            && $hablaDeUnidad
            && !preg_match('/\b(objetivo|alcance|riesgos?|definiciones?)\b/u', $q)
        ) {
            return true;
        }

        return false;
    }

    /**
     * "quién es Eduardo Cong" / "conoces a Mariel" / "qué puesto tiene X":
     * persona del directorio, no ficha de un procedimiento.
     */
    private function isWhoIsPersonQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        $hablaDeDocumento = (bool) preg_match(
            '/\b(procedimientos?|documentos?|folios?|pol[ií]ticas?|lineamientos?)\b/u',
            $q
        );
        $niegaDocumento = (bool) preg_match(
            '/\bno\s+(es|son|quiero|necesito).{0,40}\b(procedimientos?|documentos?|folios?|esos|esas|ninguno)\b/u',
            $q
        ) || $this->isRejectingOfferedOptions($q);

        // "quién es el responsable / el alcance": sigue siendo del PDF, salvo que
        // dejen claro que hablan de una persona o están negando el documento.
        if (
            preg_match('/\b(responsables?|encargad[oa]s?|objetivo|alcance|riesgos?)\b/u', $q)
            && !preg_match('/\b(persona|emplead[oa]|se llam\w*|conoces a)\b/u', $q)
            && !$niegaDocumento
        ) {
            return false;
        }

        if ($hablaDeDocumento && !$niegaDocumento
            && !preg_match('/\b(persona|emplead[oa]|se llam\w*|conoces a|a una persona)\b/u', $q)
        ) {
            return false;
        }

        if ($this->isPersonLookupFollowUp($q)) {
            return true;
        }

        $tokens = $this->tokensNombreParaCorreo($query);
        $tieneNombre = count($tokens) >= 2
            || (count($tokens) === 1 && mb_strlen($tokens[0]) >= 5);

        $pidePorNombre = (bool) preg_match(
            '/\b(qui[eé]n es|quien es|c[oó]mo se llam\w*|a qui[eé]n le (escribo|hablo)|'
            . 'qui[eé]n es esa persona|'
            . 'conoces a|conoce a|conoces a alguien|'
            . 'sabes (qui[eé]n es|de)|'
            . 'qu[eé] puesto (tiene|ocupa)|cu[aá]l es el puesto (de|que tiene)|su puesto|'
            . 'se llam[ae]|que se llam[ae]|llamad[oa]|'
            . 'una persona|a una persona|esa persona|'
            . 'busc[oa] a|buscando a)\b/u',
            $q
        );

        // "qué puesto tiene el coordinador de TI" es por rol, no por nombre.
        if (
            preg_match('/\bqu[eé] puesto (tiene|ocupa)\b/u', $q)
            && preg_match(
                '/\b(coordinador(?:es|as)?|gerente(?:s)?|director(?:es|as)?|'
                . 'auxiliar(?:es)?|analista(?:s)?|jefe(?:s|as)?)\b/u',
                $q
            )
        ) {
            $pidePorNombre = false;
        }

        // "es Mariel Campos" (nombre + apellido), no "es un procedimiento".
        $pareceNombreTrasEs = $tieneNombre
            && (bool) preg_match(
                '/^\s*(no,?\s+)?es\s+[a-záéíóúñü]{3,}(\s+[a-záéíóúñü]{3,})+\s*\??\s*$/u',
                $q
            )
            && !preg_match('/\b(procedimiento|documento|formato|pol[ií]tica|empleado)\b/u', $q);

        if ($pareceNombreTrasEs) {
            return true;
        }

        if ($pidePorNombre && $tieneNombre) {
            return true;
        }

        return false;
    }

    /**
     * Sigue pidiendo a la misma persona sin repetir el nombre
     * ("es en general", "solo tengo el nombre", "a una persona").
     */
    private function isPersonLookupFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(es en general|en general|'
            . 'solo tengo el nombre|s[oó]lo (tengo|s[eé]|se) el nombre|'
            . 'no s[eé] (el )?puesto|no lo s[eé]|no lo se|'
            . 'a una persona|una persona que se llam|'
            . 'no,? a una persona|no es (un )?procedimiento)\b/u',
            $q
        );
    }

    private function isEmployeeConfirmQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b(es un[a]? empleado|es empleado|emplead[oa] de proser|'
            . 'trabaja (en|aqu[ií]|con nosotros)|de la empresa)\b/u',
            $q
        );
    }

    /**
     * Pide OTRO procedimiento (pagos, "en cuál procedimiento…") distinto al PDF en foco.
     */
    private function isNewProcedureSeekQuery(string $query, ?array $cachedContext = null): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }
        if (preg_match('/\b(en qu[eé]|cu[aá]l|qu[eé])\s+procedimiento\b/u', $q)) {
            return true;
        }
        if (!preg_match('/\b(programar|ejecutar|solicitar)\s+(pagos?|vacaciones|compras?|vi[aá]ticos)\b/u', $q)
            && !preg_match('/\bprocedimiento (para|de)\s+\w{4,}/u', $q)
        ) {
            return false;
        }
        $title = mb_strtolower((string) ($cachedContext['title'] ?? ''));
        if ($title === '') {
            return true;
        }
        $tema = '';
        if (preg_match('/\b(pagos?|vacaciones|compras?|vi[aá]ticos)\b/u', $q, $m)) {
            $tema = $this->foldAccents($m[1]);
        }

        return $tema === '' || !str_contains($this->foldAccents($title), mb_substr($tema, 0, 4));
    }

    private function isTopicEscapeQuery(string $query, ?array $cachedContext = null): bool
    {
        $q = mb_strtolower(trim($query));
        if ($this->isWhoIsPersonQuery($q) || $this->isEmployeeConfirmQuery($q)) {
            return true;
        }
        if ($this->isNewProcedureSeekQuery($query, $cachedContext)) {
            return true;
        }

        return (bool) preg_match(
            '/\bno\s+es\s+(de\s+)?(eso|este|esta|un|una\s+)?(procedimientos?|documentos?)\b/u',
            $q
        );
    }

    private function getLastPersonHintKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_last_person_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    private function isRoleDutiesQuery(string $query): bool
    {
        $q = $this->foldAccents($query);

        return (bool) preg_match(
            '/\b(obligaciones?|de que se encarga|a que se dedica|'
            . 'que (tengo|tiene|debo|debo de) (que )?hacer|'
            . 'que hace(n)? en (mi|su|el) trabajo|'
            . 'que hace ella|que hace el|'
            . 'de que se encarga ella|de que se encarga el|'
            . 'que hace en su trabajo|que tengo que hacer en mi trabajo)\b/u',
            $q
        ) && !preg_match('/\b(folio|[a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q);
    }

    private function isFullEmployeeDumpQuery(string $query): bool
    {
        $q = $this->foldAccents($query);

        return (bool) preg_match(
            '/\b(todos los empleados|lista(r|me)? (a )?todos( los empleados)?|'
            . 'nombres completos( de (todos )?ellos)?|directorio completo|'
            . 'todos los nombres|con sus numeros|con sus telefonos)\b/u',
            $q
        ) && !preg_match('/\b(procedimientos?|documentos?|folios?)\b/u', $q);
    }

    private function queryNamesDirectoryPuesto(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || $this->isDocumentSectionQuery($q)) {
            return false;
        }
        if (preg_match('/\b(procedimientos?|documentos?|folios?|objetivo|alcance)\b/u', $q)) {
            return false;
        }

        return $this->puestosNamedInDirectoryQuery($query, $query)->isNotEmpty();
    }

    private function puestosNamedInDirectoryQuery(string $originalQuery, string $searchQuery): Collection
    {
        $puestos = $this->resolveExactPuestoFromQuery($originalQuery);
        if ($puestos->isEmpty()) {
            $puestos = $this->resolveExactPuestoFromQuery($searchQuery);
        }
        if ($puestos->isEmpty()) {
            $puestos = $this->matchPuestosByRoleAndArea($this->foldAccents($originalQuery . ' ' . $searchQuery));
        }

        return $puestos;
    }

    private function tokensLookLikeJobTitle(array $tokens): bool
    {
        $roles = ['analista', 'auxiliar', 'coordinador', 'gerente', 'director', 'jefe', 'residente', 'programador'];
        foreach ($tokens as $t) {
            if (in_array($t, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    private function lastAssistantOfferedDirectoryLookup(?string $sessionId, ?string $userId): bool
    {
        foreach (array_reverse($this->getConversationHistory($sessionId, 6, $userId)) as $msg) {
            if (($msg['role'] ?? '') !== 'assistant') {
                continue;
            }
            $c = $this->foldAccents((string) ($msg['content'] ?? ''));

            return (bool) preg_match(
                '/\b(quien ocupa|identificar quien|te gustaria que te (ayude|diga) quien|'
                . 'correo (del|de (el )?)?\s*coordinador|quien ocupa actualmente ese puesto)\b/u',
                $c
            );
        }

        return false;
    }

    private function puestoNombreFromFocusedDocument(?array $cachedContext, ?string $sessionId, ?string $userId): string
    {
        $id = (int) ($cachedContext['id'] ?? 0);
        if ($id < 1) {
            $hint = \Cache::get($this->getLastDocHintKey($sessionId, $userId));
            $id = (int) ($hint['id'] ?? 0);
        }
        if ($id < 1) {
            return '';
        }
        $el = Elemento::with('puestoResponsable:id_puesto_trabajo,nombre')->find($id);

        return trim((string) optional($el?->puestoResponsable)->nombre);
    }

    private function resolveClaimedPuestoFromQuery(string $query): Collection
    {
        $q = $this->foldAccents($query);
        if (!preg_match(
            '/\b(?:soy|yo soy|trabajo de|trabajo como|me desempeno como)\s+(?:un|una|el|la)?\s*(.+)$/u',
            $q,
            $m
        )) {
            return collect();
        }

        $rest = trim((string) ($m[1] ?? ''));
        $rest = trim(preg_replace(
            '/\b(que|cuales?|son)?\s*(procedimientos?|documentos?|tengo|me tocan|me corresponden).*$/u',
            '',
            $rest
        ) ?? $rest);
        if ($rest === '') {
            return collect();
        }

        $puestos = $this->resolveExactPuestoFromQuery($rest);
        if ($puestos->isEmpty()) {
            $puestos = $this->matchPuestosByRoleAndArea($rest);
        }
        if ($puestos->isEmpty() && preg_match('/\bcontador(?:es|a)?\b/u', $this->foldAccents($rest))) {
            $puestos = $this->getPuestosCatalog()->filter(function ($p) {
                $name = $this->foldAccents((string) $p->nombre);

                return str_contains($name, 'cuentas por pagar')
                    || str_contains($name, 'contabil')
                    || str_contains($name, 'contador');
            })->values();
        }

        return $puestos;
    }

    private function buildFullDirectoryRefuseResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $msg = "No listo el **directorio completo** ni números telefónicos.\n\n"
            . "Puedo mostrarte personas por **área**, **unidad** o **puesto**. "
            . "Por ejemplo: *quiénes son de Administración* o *quién es el analista administrativo*.";

        return $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_refuse_full_dump',
            $startTime,
            $userId,
            $sessionId
        );
    }

    private function generatePuestoDutiesResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        ?array $catalogState,
        ?array $cachedContext
    ): ?array {
        $puesto = $this->resolvePuestoForDutiesQuery($query, $sessionId, $userId, $catalogState);
        if ($puesto === null) {
            return null;
        }

        $puestoId = (int) $puesto['id'];
        $puestoNombre = (string) $puesto['nombre'];
        $persona = (string) ($puesto['persona'] ?? '');
        $ids = [$puestoId];

        $comoResp = $this->searchElementosByPuestoIds($ids, 40, self::ELEMENTO_TIPOS_PROCEDIMIENTO, 'responsable');
        $comoRel = $this->searchElementosByPuestoIds($ids, 40, self::ELEMENTO_TIPOS_PROCEDIMIENTO, 'relacionado');

        $quien = $persona !== '' ? "**{$persona}** — **{$puestoNombre}**" : "**{$puestoNombre}**";
        $msg = "En el SGC, {$quien} ";
        if ($comoResp->isEmpty()) {
            $msg .= "no figura como **responsable** de ningún procedimiento publicado.";
        } else {
            $msg .= "es responsable de:\n"
                . $comoResp->take(12)->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");
        }

        if ($comoRel->isNotEmpty()) {
            $msg .= ($comoResp->isEmpty() ? "\n\n" : "\n\n")
                . "Participa como relacionado (" . $comoRel->count() . "):\n"
                . $comoRel->take(12)->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");
            if ($comoRel->count() > 12) {
                $msg .= "\n…y " . ($comoRel->count() - 12) . " más.";
            }
        }

        $acts = $this->collectActividadesForPuesto(
            $comoResp->concat($comoRel)->unique('id_elemento')->values(),
            $puestoNombre
        );
        if (!empty($acts)) {
            $msg .= "\n\nActividades que el Desarrollo le asigna (tabla Responsable | Actividad):\n";
            $n = 1;
            foreach (array_slice($acts, 0, 12) as $act) {
                $msg .= "\n{$n}. **{$act['folio']}** — {$act['texto']}";
                $n++;
            }
        }

        $msg .= "\n\nSi quieres el detalle de alguno, dime el folio.";

        $catalog = [
            'mode' => 'by_puesto',
            'puesto_ids' => $ids,
            'puesto_nombres' => [$puestoNombre],
            'label' => 'puesto(s): ' . $puestoNombre,
        ];

        $resp = $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_puesto_duties',
            $startTime,
            $userId,
            $sessionId,
            $catalog
        );
        $resp['chips'] = [
            ['label' => 'Sus procedimientos', 'query' => 'qué procedimientos tienen asignados'],
            ['label' => 'Directorio', 'query' => 'quién ocupa ' . $puestoNombre],
        ];

        return $resp;
    }

    private function resolvePuestoForDutiesQuery(
        string $query,
        ?string $sessionId,
        ?string $userId,
        ?array $catalogState
    ): ?array {
        $claimed = $this->resolveClaimedPuestoFromQuery($query);
        if ($claimed->isNotEmpty()) {
            $p = $claimed->first();

            return ['id' => (int) $p->id_puesto_trabajo, 'nombre' => $p->nombre, 'persona' => ''];
        }

        $named = $this->puestosNamedInDirectoryQuery($query, $query);
        if ($named->isNotEmpty()) {
            $p = $named->first();

            return ['id' => (int) $p->id_puesto_trabajo, 'nombre' => $p->nombre, 'persona' => ''];
        }

        $hint = \Cache::get($this->getLastPersonHintKey($sessionId, $userId));
        if (is_array($hint) && !empty($hint['puesto_id'])) {
            return [
                'id' => (int) $hint['puesto_id'],
                'nombre' => (string) ($hint['puesto_nombre'] ?? 'ese puesto'),
                'persona' => (string) ($hint['empleado_nombre'] ?? ''),
            ];
        }

        if (is_array($catalogState) && !empty($catalogState['puesto_ids'][0])) {
            $id = (int) $catalogState['puesto_ids'][0];
            $p = PuestoTrabajo::find($id);
            if ($p) {
                return ['id' => $id, 'nombre' => $p->nombre, 'persona' => ''];
            }
        }

        $fromThread = $this->resolvePuestoStateFromRecentContext($sessionId, null, $catalogState);
        if (is_array($fromThread) && !empty($fromThread['puesto_ids'][0])) {
            return [
                'id' => (int) $fromThread['puesto_ids'][0],
                'nombre' => (string) ($fromThread['puesto_nombres'][0] ?? 'ese puesto'),
                'persona' => '',
            ];
        }

        if (preg_match('/\b(mi|mis|tengo|yo)\b/u', mb_strtolower($query))) {
            $uid = $this->resolvePuestoUsuarioForLists();
            if ($uid) {
                $p = PuestoTrabajo::find($uid);
                if ($p) {
                    return ['id' => $uid, 'nombre' => $p->nombre, 'persona' => ''];
                }
            }
        }

        return null;
    }

    /**
     * @param Collection $elementos
     * @return array<int, array{folio:string,texto:string}>
     */
    private function collectActividadesForPuesto($elementos, string $puestoNombre): array
    {
        $foldPuesto = $this->foldAccents($puestoNombre);
        $out = [];
        $structure = $this->sgcStructure();
        foreach ($elementos->take(10) as $el) {
            $el->loadMissing(['wordDocument:id,elemento_id,contenido_texto']);
            $rows = $structure->extractActividadesTable($structure->collectElementoText($el));
            $folio = (string) ($el->folio_elemento ?: $el->nombre_elemento);
            foreach ($rows as $row) {
                $foldRow = $this->foldAccents((string) ($row['responsable'] ?? ''));
                if ($foldPuesto === '' || $foldRow === '') {
                    continue;
                }
                if (!str_contains($foldRow, $foldPuesto) && !str_contains($foldPuesto, $foldRow)) {
                    $corto = preg_split('/\s+/u', $foldPuesto)[0] ?? '';
                    if ($corto === '' || mb_strlen($corto) < 6 || !str_contains($foldRow, $corto)) {
                        continue;
                    }
                }
                $out[] = [
                    'folio' => $folio,
                    'texto' => trim((string) ($row['actividad'] ?? '')),
                ];
                if (count($out) >= 12) {
                    return $out;
                }
            }
        }

        return $out;
    }

    private function findEmpleadosPorNombreDirectorio(array $tokens): Collection
    {
        if (empty($tokens)) {
            return collect();
        }
        if (count($tokens) === 1 && mb_strlen($tokens[0]) < 5) {
            return collect();
        }

        $expr = $this->expresionNombreCompletoSql();
        $q = Empleados::query()->whereNull('deleted_at');
        foreach ($tokens as $token) {
            $q->whereRaw($expr . ' LIKE ?', ['%' . $token . '%']);
        }

        return $q->with('puestoTrabajo')
            ->orderBy('apellido_paterno')
            ->limit(8)
            ->get(['nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id', 'correo']);
    }

    /**
     * Busca por todos los tokens; si no hay match (sobran muletillas),
     * reintenta con nombre+apellido del final o un solo nombre largo.
     */
    private function buscarEmpleadosPorTokensNombre(array $tokens): Collection
    {
        $tokens = array_values(array_filter($tokens, fn ($t) => is_string($t) && $t !== ''));
        if (empty($tokens)) {
            return collect();
        }

        $found = $this->findEmpleadosPorNombreDirectorio($tokens);
        if ($found->isNotEmpty() || count($tokens) < 2) {
            return $found;
        }

        $cola = array_slice($tokens, -2);
        $found = $this->findEmpleadosPorNombreDirectorio($cola);
        if ($found->isNotEmpty()) {
            return $found;
        }

        $ultimo = [end($tokens)];
        if (mb_strlen($ultimo[0]) >= 5) {
            return $this->findEmpleadosPorNombreDirectorio($ultimo);
        }

        return collect();
    }

    private function buildEmployeeDirectoryByNameResponse(
        string $originalQuery,
        $empleados,
        $startTime,
        $userId,
        $sessionId,
        ?string $prevDocTitle = null
    ): array {
        $lineas = $empleados->map(function ($emp) {
            $nombre = trim(implode(' ', array_filter([
                $emp->nombres,
                $emp->apellido_paterno,
                $emp->apellido_materno,
            ])));
            $puesto = optional($emp->puestoTrabajo)->nombre;
            $correo = trim((string) ($emp->correo ?? ''));
            $linea = '- **' . $nombre . '**';
            if ($puesto) {
                $linea .= ' — ' . $puesto;
            }
            if ($correo !== '') {
                $linea .= "\n  " . $correo;
            }

            return $linea;
        })->implode("\n");

        $guia = $prevDocTitle
            ? "Te desviaste un momento de **{$prevDocTitle}**. En el **directorio**:\n\n"
            : "Según el **directorio**:\n\n";

        $msg = $guia . $lineas
            . "\n\nSi no es esa persona, dime apellido o puesto."
            . ($prevDocTitle ? " Para volver al procedimiento, nómbralo o di **volvamos**." : '');

        $first = $empleados->first();
        $tokens = $this->tokensNombreParaCorreo(trim(implode(' ', array_filter([
            $first->nombres ?? '',
            $first->apellido_paterno ?? '',
        ]))));
        \Cache::put($this->getLastPersonHintKey($sessionId, $userId), [
            'tokens' => $tokens,
            'puesto_id' => (int) ($first->puesto_trabajo_id ?? 0) ?: null,
            'puesto_nombre' => optional($first->puestoTrabajo)->nombre,
            'empleado_nombre' => trim(implode(' ', array_filter([
                $first->nombres ?? '',
                $first->apellido_paterno ?? '',
                $first->apellido_materno ?? '',
            ]))),
        ], 1800);

        $chips = [];
        if ($prevDocTitle) {
            $chips[] = ['label' => 'Volver: ' . mb_substr($prevDocTitle, 0, 22), 'query' => $prevDocTitle];
        }

        $resp = $this->buildDirectoryChatResponse(
            $originalQuery,
            $msg,
            'directory_person_by_name',
            $startTime,
            $userId,
            $sessionId
        );
        if ($chips !== []) {
            $resp['chips'] = $chips;
        }

        return $resp;
    }

    /**
     * Responde desde empleados/puestos o pide más datos. Sin ficha de documento.
     */
    private function generatePeopleOrOrgResponse(
        string $originalQuery,
        string $searchQuery,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $combined = mb_strtolower($originalQuery . ' ' . $searchQuery);

        if ($this->isPeopleOfAreaQuery($originalQuery) || $this->isPeopleOfAreaQuery($searchQuery)) {
            $peopleArea = $this->buildPeopleOfAreaResponse(
                $originalQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );
            if ($peopleArea !== null) {
                return $peopleArea;
            }
        }

        $tokensPersona = $this->tokensNombreParaCorreo($originalQuery);
        $hintPersona = \Cache::get($this->getLastPersonHintKey($sessionId, $userId));
        $puestosNombrados = $this->puestosNamedInDirectoryQuery($originalQuery, $searchQuery);
        $pareceNombreDePersona = count($tokensPersona) >= 2
            && !$this->tokensLookLikeJobTitle($tokensPersona);

        if (
            $puestosNombrados->isNotEmpty()
            && !$pareceNombreDePersona
        ) {
            // "quién es el analista administrativo": es un puesto, no un nombre.
            $tokensPersona = [];
        } elseif (empty($tokensPersona) && (
            $this->isEmployeeConfirmQuery($originalQuery) || $this->isPersonLookupFollowUp($originalQuery)
        )) {
            $tokensPersona = $hintPersona['tokens'] ?? [];
        }
        if (
            ($pareceNombreDePersona || $this->isWhoIsPersonQuery($originalQuery) || $this->isEmployeeConfirmQuery($originalQuery) || count($tokensPersona) >= 2)
            && $puestosNombrados->isEmpty()
        ) {
            $empleadosNom = $this->buscarEmpleadosPorTokensNombre($tokensPersona);
            $prevHint = \Cache::get($this->getLastDocHintKey($sessionId, $userId));
            $prevTitle = is_array($prevHint) ? trim((string) ($prevHint['title'] ?? '')) : '';
            if ($empleadosNom->isNotEmpty()) {
                return $this->buildEmployeeDirectoryByNameResponse(
                    $originalQuery,
                    $empleadosNom,
                    $startTime,
                    $userId,
                    $sessionId,
                    $prevTitle !== '' ? $prevTitle : null
                );
            }
            if (!empty($tokensPersona)) {
                \Cache::put($this->getLastPersonHintKey($sessionId, $userId), [
                    'tokens' => $tokensPersona,
                ], 1800);
            }

            if ($this->isWhoIsPersonQuery($originalQuery) || $this->isEmployeeConfirmQuery($originalQuery)
                || $this->isPersonLookupFollowUp($originalQuery)
            ) {
                if (empty($tokensPersona)) {
                    $msg = "Para buscarla en el **directorio** necesito el **nombre** de la persona"
                        . " (y si puedes, el apellido).\n\n"
                        . "Ejemplo: *qué puesto tiene Mariel Campos*.";

                    return $this->buildDirectoryChatResponse(
                        $originalQuery,
                        $msg,
                        'directory_person_need_name',
                        $startTime,
                        $userId,
                        $sessionId
                    );
                }

                $label = $this->criterioNombre($tokensPersona) ?: 'esa persona';
                $msg = "No encontré a **{$label}** en el directorio de empleados.\n\n"
                    . "Puede que el nombre esté escrito distinto, o que no esté dado de alta. "
                    . "Prueba con **apellido** o el **puesto** (ej. quién ocupa Coordinador de TI)."
                    . ($prevTitle !== '' ? "\n\nSeguimos pudiendo volver a **{$prevTitle}** si dices **volvamos**." : '');

                return $this->buildDirectoryChatResponse(
                    $originalQuery,
                    $msg,
                    'directory_person_not_found',
                    $startTime,
                    $userId,
                    $sessionId
                );
            }
        }

        // "mi jefe / quién me reporta": la BD no modela jerarquía personal.
        // Responder con lo que SÍ hay (puesto del usuario + rutas) sin inventar ni listar "todas las jefaturas".
        if (preg_match(
            '/\b(mi jefe|mi jefa|qui[eé]n me reporta|quien me reporta|'
            . 'qui[eé]n es mi jefe|quien es mi jefe|qui[eé]n es mi jefa|'
            . 'a qui[eé]n reporto|superior inmediato)\b/u',
            $combined
        )) {
            return $this->buildMyBossDirectoryResponse(
                $originalQuery,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Unidades / áreas / organización de la empresa (catálogo global, no las del PDF).
        if (
            preg_match('/\b(unidades?|divisiones?|[aá]reas?|organizada|organigrama|estructura)\b/u', $combined)
            && preg_match(
                '/\b(empresa|negocio|proser|organizaci[oó]n|hay|cu[aá]les|todas|lista|listado|'
                . 'dime|decir|como esta|c[oó]mo est[aá]|que hay|qu[eé] hay)\b/u',
                $combined
            )
            && !preg_match('/\b(procedimiento|documento|de este|de ese|aplican)\b/u', $combined)
        ) {
            return $this->buildCompanyOrgStructureResponse(
                $originalQuery,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // "quién me puede ayudar / con quién me comunico" (+ vacaciones → Capital Humano)
        if ($this->isWhoToContactQuery($originalQuery) || $this->isWhoToContactQuery($combined)) {
            $hrTopic = $this->hrPersonalTopicFromThread($sessionId, $userId, $originalQuery);
            if ($hrTopic !== '') {
                return $this->buildContactForTopicResponse(
                    $originalQuery,
                    $hrTopic,
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            return $this->buildWhoCanHelpResponse(
                $originalQuery,
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Directores (lista / organigrama), sin amarrar a un PDF.
        if (
            preg_match('/\bdirectores?\b/u', $combined)
            && (
                preg_match('/\b(lista|listado|listar|dame|dime|mu[eé]strame|cu[aá]les|todos)\b/u', $combined)
                || preg_match('/\b(unidad(es)?|[aá]reas?|empresa|esas|esos|negocio)\b/u', $combined)
            )
            && (
                !preg_match('/\b(procedimiento|documento|folio)\b/u', $combined)
                || preg_match('/\b(no tiene que ver|nada que ver|no es (de|un)|ya no)\b/u', $combined)
            )
        ) {
            $puestosDir = PuestoTrabajo::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ['%director%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['%directora%']);
                })
                ->orderBy('nombre')
                ->limit(40)
                ->get(['id_puesto_trabajo', 'nombre']);

            if ($puestosDir->isEmpty()) {
                $msg = "No encontré puestos de director en el directorio.";
            } else {
                $puestoIds = $puestosDir->pluck('id_puesto_trabajo')->map(fn ($id) => (int) $id)->all();
                $empleados = Empleados::whereIn('puesto_trabajo_id', $puestoIds)
                    ->whereNull('deleted_at')
                    ->orderBy('apellido_paterno')
                    ->limit(40)
                    ->get(['nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id']);
                $map = $puestosDir->keyBy('id_puesto_trabajo');

                if ($empleados->isEmpty()) {
                    $lineas = $puestosDir->map(fn ($p) => '- ' . $p->nombre)->implode("\n");
                    $msg = "Estos son los puestos de **dirección** que tengo registrados "
                        . "(sin personas asignadas en el directorio):\n\n" . $lineas;
                } else {
                    $lineas = $empleados->map(function ($emp) use ($map) {
                        $nombre = trim(implode(' ', array_filter([
                            $emp->nombres,
                            $emp->apellido_paterno,
                            $emp->apellido_materno,
                        ])));
                        $puesto = optional($map->get($emp->puesto_trabajo_id))->nombre ?? 'Director';

                        return "- **{$nombre}** — {$puesto}";
                    })->implode("\n");

                    $msg = "Según el directorio, estos son los **directores** registrados:\n\n"
                        . $lineas
                        . "\n\nEsto es información de la **empresa**, no de un procedimiento concreto.";
                }
            }

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_company_directors',
                $startTime,
                $userId,
                $sessionId
            );
        }

        // 1) Si el usuario pegó el nombre completo de la lista → ese puesto y ya.
        $puestos = $this->resolveExactPuestoFromQuery($originalQuery);
        if ($puestos->isEmpty()) {
            $puestos = $this->resolveExactPuestoFromQuery($searchQuery);
        }
        // 2) Si no, búsqueda parcial (TI, calidad, etc.).
        if ($puestos->isEmpty()) {
            $puestos = $this->findPuestosMentionedInQuery($combined);
        }
        if ($puestos->isEmpty()) {
            $puestos = $this->findPuestosMentionedInQuery(
                $this->normalizeColloquialQuery($originalQuery)
            );
        }
        $unidades = $this->findUnidadesMentionedInQuery($combined);

        // Pregunta de unidad sin puesto claro → amortiguar y pedir precisión.
        if (
            $puestos->isEmpty()
            && (
                preg_match('/\b(unidad|corporativo|departamento)\b/u', $combined)
                || $unidades->isNotEmpty()
            )
            && !preg_match('/\b(coordinador|gerente|director|analista|auxiliar|jefe)\b/u', $combined)
        ) {
            $unidadLabel = $unidades->isNotEmpty()
                ? $unidades->pluck('nombre')->implode(', ')
                : 'esa unidad';

            $msg = "Para **{$unidadLabel}** no hay un único “responsable de la unidad” en el directorio.\n\n"
                . "¿Me das un poco más de detalle?\n\n"
                . "- El **nombre del puesto** (ej. Director de Administración y Finanzas, Gerente de Contabilidad…)\n"
                . "- O el **procedimiento / folio** si buscas el responsable de un documento concreto\n\n"
                . "También puedes escribir **mis procedimientos** para ver los ligados a tu puesto.";

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_clarify_unit',
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Sin puestos reconocidos: si hay un área, listar a su gente.
        if ($puestos->isEmpty()) {
            $peopleArea = $this->buildPeopleOfAreaResponse(
                $originalQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );
            if ($peopleArea !== null) {
                return $peopleArea;
            }

            $msg = "Para buscar en el **directorio** necesito un ancla que sí exista en el sistema: "
                . "un **área**, un **puesto** o el **nombre** de la persona.\n\n"
                . "Si no está registrado, te lo digo; no completo con suposiciones.";

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_clarify_puesto',
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Si hay muchos puestos genéricos, acotar o pedir cuál.
        // Nunca repreguntar si ya hay coincidencia exacta de nombre completo.
        $yaEsExacto = $this->resolveExactPuestoFromQuery($originalQuery)->isNotEmpty()
            || $this->resolveExactPuestoFromQuery($searchQuery)->isNotEmpty();

        if ($puestos->count() > 1 && !$yaEsExacto) {
            $userPuestoId = $this->resolvePuestoUsuarioForLists();
            $mio = $userPuestoId
                ? $puestos->firstWhere('id_puesto_trabajo', $userPuestoId)
                : null;
            if ($mio) {
                $puestos = collect([$mio]);
            } elseif ($puestos->count() > 3) {
                $opciones = $puestos->take(8)->pluck('nombre')
                    ->map(fn($n) => "- {$n}")
                    ->implode("\n");

                $msg = "Encontré varios puestos que coinciden. ¿Cuál te interesa?\n\n"
                    . $opciones
                    . "\n\nCopia y pega el **nombre completo** del puesto y te digo quién aparece registrado.";

                return $this->buildDirectoryChatResponse(
                    $originalQuery,
                    $msg,
                    'directory_clarify_many_puestos',
                    $startTime,
                    $userId,
                    $sessionId
                );
            }
        }

        $puestoIds = $puestos->pluck('id_puesto_trabajo')->map(fn($id) => (int) $id)->all();
        $empleados = Empleados::whereIn('puesto_trabajo_id', $puestoIds)
            ->whereNull('deleted_at')
            ->orderBy('apellido_paterno')
            ->limit(20)
            ->get(['nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id']);

        $puestosMap = $puestos->keyBy('id_puesto_trabajo');

        $catalogState = [
            'mode' => 'by_puesto',
            'puesto_ids' => $puestoIds,
            'puesto_nombres' => $puestos->pluck('nombre')->values()->all(),
            'label' => 'puesto(s): ' . $puestos->pluck('nombre')->implode(', '),
        ];

        // El directorio acaba de fijar un puesto nuevo en el hilo: si había un correo
        // recordado de una pregunta anterior, ya no aplica. Sin esto, "dame su correo"
        // después de preguntar por un puesto distinto seguía devolviendo a la persona
        // de la última búsqueda de correo, no a la de este puesto.
        \Cache::forget($this->getEmailStateKey($sessionId, $userId));

        if ($empleados->isEmpty()) {
            $nombresPuesto = $puestos->pluck('nombre')->implode(', ');
            $msg = "Para **{$nombresPuesto}** no tengo personas registradas en el directorio ahora mismo.\n\n"
                . "Puedes escribir **qué procedimientos tienen asignados** para ver los documentos ligados a ese puesto, "
                . "o darme otro nombre de puesto / folio.";

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_no_employees',
                $startTime,
                $userId,
                $sessionId,
                $catalogState
            );
        }

        $lineas = $empleados->map(function ($emp) use ($puestosMap) {
            $nombre = trim(implode(' ', array_filter([
                $emp->nombres,
                $emp->apellido_paterno,
                $emp->apellido_materno,
            ])));
            $puestoNombre = optional($puestosMap->get($emp->puesto_trabajo_id))->nombre ?? 'Puesto';

            return "- **{$nombre}** — {$puestoNombre}";
        })->implode("\n");

        $tituloPuestos = $puestos->pluck('nombre')->implode(', ');
        $msg = "Según el directorio, esto es lo que tengo para **{$tituloPuestos}**:\n\n"
            . $lineas
            . "\n\nSi quieres saber **de qué se encarga** ese puesto en el SGC, dímelo. "
            . "También puedo listar los procedimientos donde figura.";

        $resp = $this->buildDirectoryChatResponse(
            $originalQuery,
            $msg,
            'directory_people',
            $startTime,
            $userId,
            $sessionId,
            $catalogState
        );
        $resp['chips'] = [
            ['label' => 'De qué se encarga', 'query' => 'de qué se encarga ese puesto'],
            ['label' => 'Sus procedimientos', 'query' => 'qué procedimientos tienen asignados'],
        ];
        $firstEmp = $empleados->first();
        if ($firstEmp) {
            \Cache::put($this->getLastPersonHintKey($sessionId, $userId), [
                'tokens' => $this->tokensNombreParaCorreo(trim($firstEmp->nombres . ' ' . $firstEmp->apellido_paterno)),
                'puesto_id' => (int) ($firstEmp->puesto_trabajo_id ?? 0) ?: ($puestoIds[0] ?? null),
                'puesto_nombre' => optional($puestosMap->get($firstEmp->puesto_trabajo_id))->nombre
                    ?? optional($puestos->first())->nombre,
                'empleado_nombre' => trim(implode(' ', array_filter([
                    $firstEmp->nombres,
                    $firstEmp->apellido_paterno,
                    $firstEmp->apellido_materno,
                ]))),
            ], 1800);
        }

        return $resp;
    }

    private function buildDirectoryChatResponse(
        string $query,
        string $response,
        string $method,
        $startTime,
        $userId,
        $sessionId,
        ?array $catalogState = null
    ): array {
        return [
            'response' => $response,
            'method' => $method,
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'catalog_state' => $catalogState,
            'analytics_id' => $this->logAnalytics($query, $response, $method, $startTime, $userId, $sessionId),
        ];
    }

    /**
     * Personas cuyos puestos pertenecen al área nombrada (Jurídico, TI, Calidad…).
     */
    private function buildPeopleOfAreaResponse(
        string $originalQuery,
        string $searchQuery,
        $startTime,
        $userId,
        $sessionId
    ): ?array {
        $blob = $originalQuery . ' ' . $searchQuery;
        $areas = $this->findExplicitAreasInQuery($blob);
        if ($areas->isEmpty()) {
            $areas = $this->findAreasMentionedInQuery($blob);
        }
        if ($areas->isEmpty()) {
            $fold = $this->foldAccents($blob);
            $areas = $this->getAreasCatalog()->filter(function ($a) use ($fold) {
                $name = $this->foldAccents((string) $a->nombre);

                return mb_strlen($name) >= 5 && (
                    str_contains($fold, $name)
                    || (str_contains($name, 'administraci') && str_contains($fold, 'administraci'))
                );
            })->values();
        }
        if ($areas->isEmpty()) {
            return null;
        }

        $areas = $areas->unique('id_area')->sortBy(fn ($a) => mb_strlen((string) $a->nombre))->values();
        $area = $areas->first();
        $areaNombre = trim((string) $area->nombre);
        $areaIds = $areas->pluck('id_area')->map(fn ($id) => (int) $id)->all();
        $puestoIds = $this->puestoIdsForAreaIds($areaIds);

        if (empty($puestoIds)) {
            $frag = $this->foldAccents($areaNombre);
            $token = preg_split('/\s+/u', $frag)[0] ?? $frag;
            if (mb_strlen($token) >= 4) {
                $puestoIds = PuestoTrabajo::query()
                    ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $token . '%'])
                    ->pluck('id_puesto_trabajo')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        $puestos = empty($puestoIds)
            ? collect()
            : PuestoTrabajo::query()
                ->whereIn('id_puesto_trabajo', $puestoIds)
                ->orderBy('nombre')
                ->get(['id_puesto_trabajo', 'nombre']);

        $empleados = empty($puestoIds)
            ? collect()
            : Empleados::query()
                ->whereIn('puesto_trabajo_id', $puestoIds)
                ->whereNull('deleted_at')
                ->orderBy('apellido_paterno')
                ->limit(80)
                ->get(['nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id', 'correo']);

        $catalogState = [
            'mode' => 'by_area',
            'area_ids' => $areaIds,
            'puesto_ids' => $puestoIds,
            'label' => 'área ' . $areaNombre,
        ];

        $chips = [
            ['label' => 'Procedimientos de ' . mb_substr($areaNombre, 0, 22), 'query' => 'procedimientos de ' . $areaNombre],
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
            ['label' => 'Directores', 'query' => 'lista los directores'],
        ];

        if ($empleados->isEmpty()) {
            $msg = $puestos->isEmpty()
                ? "En el directorio no hay puestos ligados al área **{$areaNombre}** en este momento.\n\n"
                    . "Puedo listar los **procedimientos de {$areaNombre}** o ubicar a quien ocupe un puesto si me indicas el nombre."
                : "Para **{$areaNombre}** hay puestos registrados, pero **nadie asignado** en el directorio:\n\n"
                    . $puestos->take(12)->map(fn ($p) => '- ' . $p->nombre)->implode("\n")
                    . "\n\nSi lo deseas, indica un puesto de esa lista y verifico si hay alguien asignado.";

            $resp = $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_people_of_area_empty',
                $startTime,
                $userId,
                $sessionId,
                $catalogState
            );
            $resp['chips'] = $chips;

            return $resp;
        }

        $puestosMap = $puestos->keyBy('id_puesto_trabajo');
        $ranked = $empleados->map(function ($emp) use ($puestosMap) {
            $nombre = trim(implode(' ', array_filter([
                $emp->nombres,
                $emp->apellido_paterno,
                $emp->apellido_materno,
            ])));
            $puesto = optional($puestosMap->get($emp->puesto_trabajo_id))->nombre;
            $rank = $this->leadershipRankForPuesto($puesto);

            return [
                'nombre' => $nombre,
                'puesto' => $puesto,
                'correo' => trim((string) ($emp->correo ?? '')),
                'rank' => $rank,
            ];
        })->sortBy([
            ['rank', 'asc'],
            ['nombre', 'asc'],
        ])->values();

        $total = $ranked->count();
        $quiereTodas = (bool) preg_match(
            '/\b(todas?|completo|completas?|listado completo)\b/u',
            mb_strtolower($originalQuery . ' ' . $searchQuery)
        );
        $lideres = $ranked->filter(fn ($row) => (int) $row['rank'] <= 4)->values();
        if ($quiereTodas) {
            $mostrar = $ranked->take(20);
        } elseif ($lideres->isNotEmpty()) {
            $mostrar = $lideres->take(8);
        } else {
            $mostrar = $ranked->take(6);
        }

        $lineas = $mostrar->map(function ($row) {
            $linea = '- **' . $row['nombre'] . '**';
            if (!empty($row['puesto'])) {
                $linea .= ' — ' . $row['puesto'];
            }
            if (!empty($row['correo'])) {
                $linea .= "\n  " . $row['correo'];
            }

            return $linea;
        })->implode("\n");

        $resto = $total - $mostrar->count();
        $esOrientacion = !$quiereTodas && $total > $mostrar->count();
        if ($esOrientacion && $lideres->isNotEmpty()) {
            $msg = "Para **{$areaNombre}** te oriento primero con quienes coordinan el área "
                . "({$total} personas en el directorio):\n\n"
                . $lineas
                . "\n\nHay **{$resto}** personas más. Si buscas a alguien en concreto, indica su **nombre**. "
                . "Si prefieres el listado completo, indícamelo. También puedo mostrar los **procedimientos** del área.";
            $chips = array_merge([
                ['label' => 'Listar todas', 'query' => 'lista todas las personas de ' . $areaNombre],
            ], $chips);
        } else {
            $extra = $resto > 0 ? "\n\n(Muestro {$mostrar->count()} de {$total}.)" : '';
            $msg = "Estas son las personas de **{$areaNombre}** registradas en el directorio:\n\n"
                . $lineas
                . $extra
                . "\n\nSi buscas a alguien en concreto, indica su **nombre**. "
                . "Si deseas los **procedimientos** del área, indícamelo.";
        }

        $resp = $this->buildDirectoryChatResponse(
            $originalQuery,
            $msg,
            'directory_people_of_area',
            $startTime,
            $userId,
            $sessionId,
            $catalogState
        );
        $resp['chips'] = $chips;

        return $resp;
    }

    /**
     * Prioridad para orientar: dirección / gerencia / coordinación antes que el resto.
     */
    private function leadershipRankForPuesto(?string $puestoNombre): int
    {
        $p = $this->foldAccents((string) $puestoNombre);
        if ($p === '') {
            return 99;
        }
        if (preg_match('/\bdirector(a|es)?\b/u', $p)) {
            return 1;
        }
        if (preg_match('/\bgerente(s)?\b/u', $p)) {
            return 2;
        }
        if (preg_match('/\b(coordinador(a|es)?|jefe|jefa|jefes)\b/u', $p)) {
            return 3;
        }
        if (preg_match('/\b(supervisor(a|es)?|lider(es)?)\b/u', $p)) {
            return 4;
        }

        return 50;
    }

    /**
     * "¿Quién es mi jefe?": sin jerarquía en BD → respuesta honesta + rutas útiles.
     */
    private function buildMyBossDirectoryResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $puestoId = $this->resolvePuestoUsuarioForLists();
        $puestoNombre = null;
        $areaChips = [];
        if ($puestoId) {
            $p = PuestoTrabajo::with(['unidadNegocio'])->find($puestoId);
            $puestoNombre = $p?->nombre;
            if ($p) {
                foreach (($p->areas ?? collect())->take(2) as $area) {
                    $nom = trim((string) ($area->nombre ?? ''));
                    if ($nom !== '') {
                        $areaChips[] = [
                            'label' => 'Personas de ' . $nom,
                            'query' => 'personas del área de ' . $nom,
                        ];
                    }
                }
                $unidad = trim((string) ($p->unidadNegocio->nombre ?? ''));
                if ($unidad !== '') {
                    $areaChips[] = [
                        'label' => 'Gerente / dir. ' . mb_substr($unidad, 0, 28),
                        'query' => 'quién ocupa Gerente de ' . $unidad,
                    ];
                }
            }
        }

        $chips = [
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
            ['label' => 'Directores', 'query' => 'lista los directores'],
            ['label' => 'Coordinador de TI', 'query' => 'quién ocupa Coordinador de TI'],
            ['label' => 'Gerente de RH', 'query' => 'quién ocupa Gerente de Recursos Humanos'],
        ];
        foreach ($areaChips as $c) {
            $chips[] = $c;
        }

        if ($puestoNombre) {
            $msg = "En el directorio apareces con el puesto **{$puestoNombre}**.\n\n"
                . "No tengo modelada la relación **jefe directo** (quién te reporta), "
                . "así que no puedo inventarte un nombre.\n\n"
                . "Escribe el **puesto** de tu jefe (ej. «Gerente de …» o «Coordinador de …») "
                . "y te digo quién aparece registrado. También puedes usar los atajos de abajo.";
        } else {
            $msg = "No pude leer tu puesto de la sesión, y además el directorio **no guarda** "
                . "quién es el jefe directo de cada persona.\n\n"
                . "Escribe el **nombre del puesto** del superior (ej. Gerente de Recursos Humanos) "
                . "o prueba uno de los atajos.";
        }

        $resp = $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_my_boss_unavailable',
            $startTime,
            $userId,
            $sessionId
        );
        $resp['chips'] = array_slice($chips, 0, 6);

        return $resp;
    }

    /**
     * Tras "volvamos / me perdí / ese no es": retomar tema del hilo con chips (no menú 1/2/3).
     */
    private function buildTopicRecoveryResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        ?array $catalogState,
        string $tituloSoltado = ''
    ): array {
        $tema = $this->inferLastTopicHint($sessionId, $catalogState, $tituloSoltado);
        $genericos = ['documento', 'documentos', 'procedimiento', 'procedimientos', 'elemento'];
        $tituloOk = $tituloSoltado !== ''
            && !in_array(mb_strtolower($tituloSoltado), $genericos, true);

        $chips = [
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
            ['label' => 'Directorio', 'query' => 'quién ocupa un puesto'],
        ];

        if ($tema !== '') {
            $chips = array_merge([
                ['label' => 'Proc. de ' . mb_substr($tema, 0, 22), 'query' => 'hay procedimiento de ' . $tema . '?'],
                ['label' => 'Algo de ' . mb_substr($tema, 0, 22), 'query' => 'necesito algo de ' . $tema],
            ], $chips);
        }

        if ($tituloOk) {
            $msg = "De acuerdo, dejo de lado **{$tituloSoltado}**.\n\n";
        } else {
            $msg = "De acuerdo, retomemos el tema.\n\n";
        }

        if ($tema !== '') {
            $msg .= "Veníamos hablando de **{$tema}**. "
                . "¿Deseas un procedimiento relacionado, consultar el directorio, o describir en una frase lo que necesitas?";
        } else {
            $msg .= "Describe en una frase lo que necesitas (tema, área o puesto). "
                . "Por ejemplo: «pagos», «facturas» o «mis procedimientos».";
        }

        return [
            'response' => $msg,
            'method' => 'conversation_topic_recovery',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'chips' => array_slice($chips, 0, 5),
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_topic_recovery',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * Extrae un tema útil del hilo / catálogo / título soltado.
     */
    private function inferLastTopicHint(?string $sessionId, ?array $catalogState, string $tituloSoltado = ''): string
    {
        $label = trim((string) ($catalogState['label'] ?? ''));
        if ($label !== '' && !preg_match('/^puesto/u', mb_strtolower($label))) {
            $clean = preg_replace('/^(mis procedimientos|puesto\(s\):\s*)/iu', '', $label) ?? $label;
            $clean = trim($clean);
            if ($clean !== '' && mb_strlen($clean) <= 40) {
                return $clean;
            }
        }

        $topics = [
            'factura', 'facturas', 'pago', 'pagos', 'campamento', 'campamentos',
            'compra', 'compras', 'obra', 'maquinaria', 'vacacion', 'vacaciones',
            'presupuesto', 'presupuestos', 'calidad', 'juridico', 'jurídico',
            'cierre', 'capacitar', 'rh', 'nómina', 'nomina',
        ];

        $blob = mb_strtolower($tituloSoltado . ' ');
        foreach ($this->getConversationHistory($sessionId, 10) as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $blob .= ' ' . mb_strtolower(strip_tags((string) ($msg['content'] ?? '')));
            }
        }

        foreach ($topics as $t) {
            if (preg_match('/\b' . preg_quote($t, '/') . '\b/u', $blob)) {
                if (str_ends_with($t, 's')) {
                    return $t;
                }
                return $t . (in_array($t, ['pago', 'factura', 'compra', 'campamento', 'presupuesto'], true) ? 's' : '');
            }
        }

        if ($tituloSoltado !== '') {
            $words = preg_split('/\s+/u', $tituloSoltado) ?: [];
            $pick = [];
            foreach ($words as $w) {
                $w = trim($w, ".,;:¿?¡!");
                if (mb_strlen($w) >= 4 && !preg_match('/^(para|desde|sobre|procedimiento|documento)$/iu', $w)) {
                    $pick[] = $w;
                }
                if (count($pick) >= 2) {
                    break;
                }
            }
            if (!empty($pick)) {
                return implode(' ', $pick);
            }
        }

        return '';
    }

    /**
     * "necesito algo de X" / orientación novato sin folio ni nombre claro.
     */
    private function isVagueTopicNeedQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }
        // Folio / versión / comillas = ya es específico.
        if (
            !empty($this->extractFolioPatterns($query))
            || preg_match('/\b([a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
            || preg_match('/"[^"]{3,}"/u', $query)
        ) {
            return false;
        }
        if ($this->isPeopleOrOrgDirectoryQuery($q) || $this->isCompanyOrgQuery($q)) {
            return false;
        }
        if ($this->isMyProceduresQuery($q) || $this->isCatalogBrowseQuery($q)) {
            return false;
        }

        // Siempre aclarar estas formas, aunque haya overlap de título (ej. "cierre" → Cierre de Mes).
        return (bool) preg_match(
            '/\b(necesito algo de|algo de|proc(edimiento)? de|documento de|pa(ra)? que sirve lo de|'
            . 'hay procedimiento de|solitud de|solicitud de)\b/u',
            $q
        );
    }

    private function buildVagueTopicClarifyResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        ?array $threadDoc = null
    ): array {
        $tema = $this->extractVagueTopicWord($query);
        $chips = [
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
        ];
        $pregunta = "Para orientarte con mayor precisión, ¿buscas un **procedimiento del SGC**, "
            . "**a quién consultar**, o un **área**?";

        // Candidato fuerte en BD → pedir confirmación (no abrir PDF solo).
        $candidatos = $this->findNamedElementos($this->normalizeColloquialQuery($query), 3);
        $best = null;
        $bestScore = 0;
        foreach ($candidatos as $el) {
            $score = max(
                (float) $this->namedMatchStrength($query, $el),
                $this->titleOverlapRatio($query, $el) * 100
            );
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $el;
            }
        }
        if ($best && $bestScore >= 35) {
            $nombre = trim((string) ($best->nombre_elemento ?? ''));
            $folio = trim((string) ($best->folio_elemento ?? ''));
            if ($nombre !== '') {
                \Cache::put($this->getPendingDocConfirmKey($sessionId, $userId), [
                    'id' => $best->getKey(),
                    'title' => $nombre,
                    'asked_at' => time(),
                ], 600);
                $pregunta = "¿Te refieres a **{$nombre}**"
                    . ($folio !== '' ? " ({$folio})" : '')
                    . "?\n\nResponde **sí** para abrirlo, o elige otra opción.";
                array_unshift($chips, [
                    'label' => 'Sí: ' . mb_substr($nombre, 0, 24),
                    'query' => $nombre,
                ]);
            }
        }

        $map = [
            'factura' => [
                'q' => "¿La factura es de **proveedor/gasto** o de **cobro a cliente**?",
                'chips' => [
                    ['label' => 'Gasto / proveedor', 'query' => 'procedimiento de pago a proveedor'],
                    ['label' => 'Cobro a cliente', 'query' => 'procedimiento de cobro a cliente'],
                    ['label' => 'Programar pagos', 'query' => 'Programar Pagos'],
                ],
            ],
            'pago' => [
                'q' => "¿Hablamos de **programar/ejecutar pagos** o de **cobros**?",
                'chips' => [
                    ['label' => 'Programar pagos', 'query' => 'Programar Pagos'],
                    ['label' => 'Ejecutar pagos', 'query' => 'Ejecutar Pagos'],
                    ['label' => 'Cierre de mes', 'query' => 'Cierre de Mes'],
                ],
            ],
            'compra' => [
                'q' => "¿Compras de **materiales/OC** o algo de **proveedores**?",
                'chips' => [
                    ['label' => 'Proc. de compras', 'query' => 'procedimientos de compras'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
            ],
            'obra' => [
                'q' => "¿Algo de **obra/construcción**, **maquinaria** o **campamentos**?",
                'chips' => [
                    ['label' => 'Renta de maquinaria', 'query' => 'Renta de Maquinaria'],
                    ['label' => 'Proc. de construcción', 'query' => 'procedimientos de Construcción'],
                ],
            ],
            'campamento' => [
                'q' => "Sobre **campamentos**: si no está publicado el procedimiento exacto, "
                    . "puedo listar lo cercano o tu área.",
                'chips' => [
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                    ['label' => 'Proc. de obra', 'query' => 'procedimientos de Construcción'],
                ],
            ],
            'vacacion' => [
                'q' => "Vacaciones/nómina suelen ser de **RH**, no siempre están como PDF del SGC. "
                    . "¿Buscas un procedimiento publicado o a quién preguntar?",
                'chips' => [
                    ['label' => 'Proc. de RH', 'query' => 'procedimientos de Recursos Humanos'],
                    ['label' => 'Quién ocupa Gerente RH', 'query' => 'quién ocupa Gerente de Recursos Humanos'],
                ],
            ],
            'calidad' => [
                'q' => "¿Quieres el **listado de Calidad** o un documento concreto?",
                'chips' => [
                    ['label' => 'Proc. de Calidad', 'query' => 'procedimientos de Calidad'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
            ],
            'juridico' => [
                'q' => "¿Listado de **Jurídico** o un tema (fianzas, contratos…)?",
                'chips' => [
                    ['label' => 'Proc. de Jurídico', 'query' => 'procedimientos de Jurídico'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
            ],
            'presupuesto' => [
                'q' => "¿Procedimientos de **presupuestos** o el área/puesto?",
                'chips' => [
                    ['label' => 'Proc. presupuestos', 'query' => 'procedimientos de presupuestos'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
            ],
            'maquinaria' => [
                'q' => "¿Te refieres a **renta/control de maquinaria**?",
                'chips' => [
                    ['label' => 'Renta de Maquinaria', 'query' => 'Renta de Maquinaria'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
            ],
            'cierre' => [
                'q' => "¿**Cierre de mes** u otro cierre?",
                'chips' => [
                    ['label' => 'Cierre de Mes', 'query' => 'Cierre de Mes'],
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ],
            ],
        ];

        $key = '';
        foreach (array_keys($map) as $k) {
            if ($tema !== '' && str_contains($tema, $k)) {
                $key = $k;
                break;
            }
        }

        // Si ya hay confirmación de candidato, no pisar con el mapa genérico del tema.
        $yaConfirma = str_contains($pregunta, '¿Te refieres a');
        if (!$yaConfirma && $key !== '') {
            $pregunta = $map[$key]['q'];
            $chips = array_merge($map[$key]['chips'], $chips);
        } elseif (!$yaConfirma && $tema !== '') {
            $pregunta = "Sobre **{$tema}**: ¿quieres que busque un **procedimiento publicado**, "
                . "el **listado de tu área**, o a **quién preguntar**?";
            $chips = array_merge([
                ['label' => 'Proc. de ' . mb_substr($tema, 0, 20), 'query' => 'procedimientos de ' . $tema],
                ['label' => 'Quién me ayuda', 'query' => 'quién me puede ayudar'],
            ], $chips);
        }

        $hiloTitulo = trim((string) ($threadDoc['title'] ?? ''));
        if ($hiloTitulo !== '' && !$yaConfirma) {
            $pregunta = "Veníamos con **{$hiloTitulo}**. "
                . ($tema !== '' ? "¿Sigues con ese documento o buscas algo de **{$tema}**?" : "¿Sigues con ese documento o cambias de tema?");
            array_unshift($chips, [
                'label' => 'Seguir: ' . mb_substr($hiloTitulo, 0, 22),
                'query' => $hiloTitulo,
            ]);
        }

        $msg = ($tema !== '' ? "Entiendo que necesitas algo de **{$tema}**.\n\n" : '')
            . $pregunta;

        return [
            'response' => $msg,
            'method' => 'conversation_vague_topic_clarify',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'chips' => array_slice($chips, 0, 5),
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_vague_topic_clarify',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    private function extractVagueTopicWord(string $query): string
    {
        $q = mb_strtolower(trim($query));
        if (preg_match(
            '/\b(?:necesito algo de|algo de|proc(?:edimiento)? de|documento de|'
            . 'pa(?:ra)? que sirve lo de|hay procedimiento de|solitud de|solicitud de)\s+([a-záéíóúñü]+)/u',
            $q,
            $m
        )) {
            return $m[1];
        }
        $stop = ['necesito', 'algo', 'de', 'un', 'una', 'el', 'la', 'los', 'las', 'por', 'para', 'que', 'hay'];
        $words = preg_split('/\s+/u', $q) ?: [];
        foreach ($words as $w) {
            $w = trim($w, ".,;:¿?¡!");
            if (mb_strlen($w) >= 4 && !in_array($w, $stop, true)) {
                return $w;
            }
        }
        return '';
    }

    /**
     * Solo pide reformato del PDF en foco (bullets / corto / formal).
     */
    private function isFormatOnlyFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || mb_strlen($q) > 100) {
            return false;
        }
        if (!empty($this->extractFolioPatterns($query))
            || preg_match('/\b([a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
        ) {
            return false;
        }
        if (preg_match('/\b(cambiemos|otro documento|otro procedimiento|mejor [a-záéíóú]|ahora de)\b/u', $q)) {
            return false;
        }
        // Nombre largo de otro doc: no es solo formato.
        if (preg_match('/\b(documento|procedimiento)\s+de\s+\w{4,}/u', $q)
            && !preg_match('/\b(en bullets?|vi[nñ]etas?|m[aá]s corto|formal)\b/u', $q)
        ) {
            return false;
        }

        return (bool) preg_match(
            '/\b(en bullets?|vi[nñ]etas?|m[aá]s corto|corto|breve|resumen corto|'
            . 'formal|mas detalle|m[aá]s detalle|explicame facil|expl[ií]came f[aá]cil|'
            . 'como para novato)\b/u',
            $q
        );
    }

    private function isCompareProceduresQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        return (bool) preg_match(
            '/\b(compara|comparar|comparaci[oó]n|diferencia entre|versus|vs\.?|'
            . 'cu[aá]ndo uso cada|cuando uso cada|uno u otro)\b/u',
            $q
        );
    }

    private function buildCompareProceduresResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $named = $this->findNamedElementos($this->normalizeColloquialQuery($query), 6);
        $picked = [];
        foreach ($named as $el) {
            $id = $el->getKey();
            if (isset($picked[$id])) {
                continue;
            }
            $strength = $this->namedMatchStrength($query, $el);
            $overlap = $this->titleOverlapRatio($query, $el);
            if ($strength >= 20 || $overlap >= 0.35) {
                $picked[$id] = $el;
            }
            if (count($picked) >= 2) {
                break;
            }
        }

        // Fallback: partir por "con" / "vs" / "y" y buscar cada lado.
        if (count($picked) < 2) {
            $parts = preg_split(
                '/\b(?:con|vs\.?|versus|contra|y|o)\b/ui',
                preg_replace('/^.*?\b(?:compara(?:r)?|diferencia entre)\s+/ui', '', $query) ?? $query,
                2
            ) ?: [];
            foreach ($parts as $part) {
                $part = trim((string) $part);
                if (mb_strlen($part) < 4) {
                    continue;
                }
                $side = $this->findNamedElementos($this->normalizeColloquialQuery($part), 2);
                foreach ($side as $el) {
                    $picked[$el->getKey()] = $el;
                    if (count($picked) >= 2) {
                        break 2;
                    }
                }
            }
        }

        $list = array_values($picked);
        $chips = [
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
        ];

        if (count($list) >= 2) {
            $a = $list[0];
            $b = $list[1];
            $na = trim((string) ($a->nombre_elemento ?? 'A'));
            $nb = trim((string) ($b->nombre_elemento ?? 'B'));
            $fa = trim((string) ($a->folio_elemento ?? ''));
            $fb = trim((string) ($b->folio_elemento ?? ''));
            $msg = "Puedo detallar **un procedimiento a la vez** (no hago una tabla completa de ambos).\n\n"
                . "Detecté:\n"
                . "- **{$na}**" . ($fa !== '' ? " (`{$fa}`)" : '') . "\n"
                . "- **{$nb}**" . ($fb !== '' ? " (`{$fb}`)" : '') . "\n\n"
                . "¿Cuál quieres abrir primero? Luego puedes pedir el otro.";
            $chips = [
                ['label' => mb_substr($na, 0, 28), 'query' => $fa !== '' ? $fa : $na],
                ['label' => mb_substr($nb, 0, 28), 'query' => $fb !== '' ? $fb : $nb],
                ['label' => 'Objetivo del 1.º', 'query' => 'objetivo de ' . $na],
                ['label' => 'Objetivo del 2.º', 'query' => 'objetivo de ' . $nb],
            ];
            // Dejar hint del primero por si sigue con "en bullets".
            \Cache::put($this->getLastDocHintKey($sessionId, $userId), [
                'id' => $a->getKey(),
                'title' => $na,
            ], 1800);
        } elseif (count($list) === 1) {
            $a = $list[0];
            $na = trim((string) ($a->nombre_elemento ?? 'ese procedimiento'));
            $msg = "Encontré **{$na}**, pero no pude ubicar claro el **segundo** procedimiento.\n\n"
                . "Dime el otro por **nombre o folio**, o abre este primero.";
            $chips = [
                ['label' => 'Abrir ' . mb_substr($na, 0, 22), 'query' => $na],
                ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ];
            \Cache::put($this->getLastDocHintKey($sessionId, $userId), [
                'id' => $a->getKey(),
                'title' => $na,
            ], 1800);
        } else {
            $msg = "Para comparar necesito **dos** procedimientos por nombre o folio.\n\n"
                . "Ejemplo: «compara Programar Pagos con Ejecutar Pagos». "
                . "Luego te detallo **uno a la vez**.";
        }

        return [
            'response' => $msg,
            'method' => 'conversation_compare_procedures',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'chips' => array_slice($chips, 0, 5),
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_compare_procedures',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * Match solo por semántica débil (sin pin de nombre/folio/solape de título).
     */
    private function isNeighborOnlySemanticMatch(string $query, ?array $finalResults): bool
    {
        if (!$finalResults || empty($finalResults['has_results'])) {
            return false;
        }
        if (!empty($finalResults['search_details']['pinned_by_name'])) {
            return false;
        }
        if ($this->mentionsSpecificDocumentSignal($query) || !empty($this->extractFolioPatterns($query))) {
            return false;
        }

        $best = $finalResults['elementos']->first() ?? null;
        if (!$best) {
            return ($finalResults['document_chunks'] ?? collect())->isNotEmpty();
        }

        if (!empty($best->named_match)) {
            return false;
        }
        $overlap = (float) ($best->title_overlap ?? $this->titleOverlapRatio($query, $best));
        if ($overlap >= 0.45) {
            return false;
        }
        $fused = (float) ($best->fused_score ?? 0);
        $sem = (float) ($best->semantic_score ?? 0);
        if ($fused >= 8.0) {
            return false;
        }
        if ($fused >= 0.55 && $overlap >= 0.3) {
            return false;
        }

        $buscaTema = (bool) preg_match(
            '/\b(de|sobre|solitud|solicitud|proc|procedimiento|documento|hay|nada de|tienes)\b/u',
            mb_strtolower($query)
        );

        $temaRh = $this->detectHrPersonalTopic($query);
        if ($temaRh !== '') {
            $titleFold = $this->foldAccents((string) ($best->nombre_elemento ?? ''));
            $temaFold = $this->foldAccents($temaRh);
            if ($overlap < 0.45 && !str_contains($titleFold, $temaFold)) {
                return true;
            }
        }

        return $buscaTema && ($sem < 0.55 || $overlap < 0.35);
    }

    private function buildUnpublishedTopicResponse(
        string $cleanQuery,
        string $searchQuery,
        array $finalResults,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $tema = $this->extractVagueTopicWord($cleanQuery) ?: $this->extractVagueTopicWord($searchQuery);
        $alt = [];
        foreach (($finalResults['elementos'] ?? collect())->take(3) as $el) {
            $nom = trim((string) ($el->nombre_elemento ?? ''));
            if ($nom !== '') {
                $alt[] = $nom;
            }
        }

        $msg = $tema !== ''
            ? "No hay un procedimiento publicado que coincida con claridad con **{$tema}**.\n\n"
            : "No hay un procedimiento publicado que coincida con claridad con esa búsqueda.\n\n";
        $msg .= "Puedo listar **tus procedimientos**, un **área**, o puedes indicar el **nombre o folio** exacto.";

        if (!empty($alt)) {
            $msg .= "\n\nCercanos en el catálogo (si te sirven):\n"
                . collect($alt)->map(fn ($n) => '- ' . $n)->implode("\n");
        }

        $chips = [
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
        ];
        if ($tema !== '') {
            array_unshift($chips, [
                'label' => 'Proc. de ' . mb_substr($tema, 0, 20),
                'query' => 'procedimientos de ' . $tema,
            ]);
        }
        foreach (array_slice($alt, 0, 2) as $n) {
            $chips[] = ['label' => mb_substr($n, 0, 28), 'query' => $n];
        }

        return [
            'response' => $msg,
            'method' => 'unpublished_topic_alternatives',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => $finalResults['search_details'] ?? [],
            'cached' => false,
            'document' => null,
            'chips' => array_slice($chips, 0, 5),
            'analytics_id' => $this->logAnalytics(
                $cleanQuery,
                $msg,
                'unpublished_topic_alternatives',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    private function buildCompanyOrgStructureResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $unidades = UnidadNegocio::query()
            ->orderBy('nombre')
            ->get(['id_unidad_negocio', 'nombre']);

        if ($unidades->isEmpty()) {
            $msg = "No hay unidades de negocio registradas en el directorio en este momento.";
        } else {
            $lineas = $unidades->map(fn ($u) => '- ' . $u->nombre)->implode("\n");
            $msg = "La empresa está organizada en el directorio por **unidades de negocio** "
                . "({$unidades->count()}):\n\n"
                . $lineas
                . "\n\nLas **áreas** se ubican dentro de esas unidades, a través de los puestos. "
                . "Si lo deseas, puedo listar **directores**, un **puesto** o los **procedimientos de un área**.";
        }

        $resp = $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_company_units',
            $startTime,
            $userId,
            $sessionId
        );
        $resp['chips'] = [
            ['label' => 'Directores', 'query' => 'lista los directores'],
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Coordinador de TI', 'query' => 'quién ocupa Coordinador de TI'],
        ];

        return $resp;
    }

    private function buildWhoCanHelpResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $tema = $this->inferLastTopicHint($sessionId, null, '');
        $msg = "Puedo orientarte con el **directorio** (puestos/unidades), no invento jerarquías.\n\n"
            . "Opciones rápidas:\n"
            . "- Dime el **puesto** (ej. Coordinador de TI)\n"
            . "- Pide **unidades** o **directores**\n"
            . "- O **mis procedimientos** según tu puesto";
        if ($tema !== '') {
            $msg .= "\n\nSi es sobre **{$tema}**, también puedes pedir «procedimientos de {$tema}».";
        }

        $chips = [
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
            ['label' => 'Directores', 'query' => 'lista los directores'],
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Mi jefe', 'query' => 'quién es mi jefe'],
        ];
        if ($tema !== '') {
            array_unshift($chips, [
                'label' => 'Proc. de ' . mb_substr($tema, 0, 20),
                'query' => 'procedimientos de ' . $tema,
            ]);
        }

        $resp = $this->buildDirectoryChatResponse(
            $query,
            $msg,
            'directory_who_can_help',
            $startTime,
            $userId,
            $sessionId
        );
        $resp['chips'] = array_slice($chips, 0, 5);

        return $resp;
    }

    private function shouldAskClarification(string $query, $cachedContext): bool
    {
        $normalized = mb_strtolower(trim($query));
        if ($normalized === '') {
            return true;
        }

        // Afirmaciones vagas: se atienden con el menú de oferta, no con RAG.
        if ($this->isVagueAffirmation($normalized) || $this->isMyProceduresQuery($normalized)) {
            return false;
        }

        // Preguntas de directorio: las atiende generatePeopleOrOrgResponse, no el RAG.
        if ($this->isPeopleOrOrgDirectoryQuery($normalized)) {
            return false;
        }

        // Con documento en foco, los seguimientos cortos son naturales: no interrumpir.
        if ($cachedContext && !empty($cachedContext['id'])) {
            return false;
        }

        // Señales de sección de documento: dejar pasar (si no es directorio).
        if (preg_match('/\b(objetivo|alcance|definicion|definiciones|riesgo|riesgos|lista|listado|pasos|actividades)\b/u', $normalized)) {
            return false;
        }

        // "responsable/encargado" solo si parece de un documento (o ya hay folio/nombre).
        if (
            preg_match('/\b(responsable|responsables|encargado)\b/u', $normalized)
            && (
                preg_match('/\b(procedimiento|documento|folio)\b/u', $normalized)
                || $this->mentionsSpecificDocumentSignal($normalized)
            )
        ) {
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
            !preg_match('/\b(objetivo|alcance|responsable|riesgo|lista|listado|definicion|mis)\b/u', $normalized)
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
            return "Para indicarte el responsable correcto necesito ubicar el documento. ¿Puedes precisar?\n\n- **Nombre** del procedimiento\n- **Tema** o de qué trata\n\nO su **folio**, si lo tienes a la mano.";
        }

        if (preg_match('/\b(procedimiento|lineamiento|manual|documento)\b/u', $normalized)) {
            return "Con gusto. ¿Qué necesitas consultar?\n\n"
                . "1. **Mis procedimientos** (según tu puesto)\n"
                . "2. Un documento por **nombre o folio**\n"
                . "3. Un listado por **área** (por ejemplo, Compras o Jurídico)\n\n"
                . "Puedes responder con el número o describir lo que buscas.";
        }

        return "¿En qué puedo ayudarte?\n\n"
            . " **Mis procedimientos**\n"
            . " **Directorio** (puestos / unidades)\n"
            . " Un **documento** (nombre o folio)\n"
            . " Un listado por **área**\n\n"
            . "Responde con una frase breve.";
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
        return "Ese tema no aparece en los documentos disponibles. Podemos reformular la pregunta o consultar otro documento.";
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

        return "Consultando **{$titulo}**:\n\n";
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
     * ¿La IA marcó la pregunta como ajena al SGC? Mismo mecanismo que [[SIN_INFO]],
     * ver instrucción en PaidAIService::buildPrompt. Capa 2 de la compuerta
     * "fuera de tema": la compuerta 3.06 (regex) cubre lo obvio antes de llamar a la
     * IA; esto cubre lo que esa regex no anticipó.
     */
    private function responseSaysFueraDeTema(string $response): bool
    {
        return (bool) preg_match('/\[\[\s*FUERA[_\s]?DE[_\s]?TEMA\s*\]\]/i', $response);
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
            ? "No encontré **{$consulta}** en **{$titulo}**, el documento sobre el que veníamos hablando.\n\n"
            : "No encontré **{$consulta}** en el documento sobre el que veníamos hablando.\n\n";

        return $mensaje
            . "He restablecido el contexto de la conversación.\n\n"
            . "Indica el **nombre** o **folio** del documento que deseas consultar, o reformula la pregunta.";
    }

    /**
     * Mensaje amigable cuando no hay resultados en documentos publicados (tras cambio de tema)
     */
    private function buildNoResultsFriendlyMessage($query = null, $intent = null): string
    {
        return "No encontré información sobre ese tema en los documentos disponibles.\n\n"
            . "Puedes intentar con el **nombre** o **folio** del documento, o reformular con otros términos.";
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

        // 4.5 CORTOCIRCUITO BD: folio o título fuerte → NO competir con embeddings ajenos.
        $pinned = $this->pickStrongNamedElemento($query, $results['elementos']);
        if ($pinned !== null) {
            $pinnedId = $pinned->getKey();
            $pinned->named_match = true;
            $pinned->named_strength = $this->namedMatchStrength($query, $pinned);
            $pinned->fused_score = 10.0 + (float) $pinned->named_strength;

            $results['elementos'] = collect([$pinned]);
            // Solo chunks del documento anclado (keyword), sin semántica global.
            $results['document_chunks'] = $results['document_chunks']->filter(function ($chunk) use ($pinnedId) {
                return (int) optional($chunk->wordDocument)->elemento_id === (int) $pinnedId;
            })->values();

            if ($results['document_chunks']->isEmpty()) {
                $kwPinned = $this->searchChunksByKeyword($query, 8);
                $results['document_chunks'] = $kwPinned->filter(function ($chunk) use ($pinnedId) {
                    return (int) optional($chunk->wordDocument)->elemento_id === (int) $pinnedId;
                })->values();
            }

            $results['has_results'] = true;
            $results['search_details'] = [
                'elementos_found' => 1,
                'documents_found' => 0,
                'total_sources' => 1 + $results['document_chunks']->count(),
                'pinned_by_name' => true,
                'pinned_strength' => $pinned->named_strength,
            ];

            \Log::info('Chatbot pin BD (sin semántica global)', [
                'query' => $query,
                'elemento_id' => $pinnedId,
                'nombre' => $pinned->nombre_elemento ?? null,
                'strength' => $pinned->named_strength,
            ]);

            return $results;
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
    private function performSemanticSearch(string $query, int $topK = 16)
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
                // Solape fuerte de título (≥60%): banda pin suave (8+) para que
                // "solicitud de campamentos" gane a un PDF que solo menciona el tema.
                $overlap = $this->titleOverlapRatio($q, $elem);
                $elem->title_overlap = $overlap;
                if ($overlap >= 0.6) {
                    $elem->fused_score = 8.0 + $overlap + $elem->fused_score;
                    $elem->named_match = true;
                    $elem->named_strength = (int) round($overlap * 50);
                } else {
                    $elem->fused_score += self::W_TITLE_OVERLAP * $overlap;
                }
            }

            // Mantener relevance_score utilizable aguas abajo aunque el elemento venga
            // sólo por semántica (los filtros posteriores esperan relevance_score).
            if (($elem->relevance_score ?? 0) <= 0 && $semNorm > 0) {
                $elem->relevance_score = (int) round($semNorm * 100);
            }

            return $elem;
        });

        return $this->preferCanonicalByFolio(
            $fused->sortByDesc('fused_score')->values()
        );
    }

    /**
     * ¿La pregunta nombra explícitamente este elemento? (bool de conveniencia).
     */
    private function queryNamesElemento(string $query, $elemento): bool
    {
        return $this->namedMatchStrength($query, $elemento) > 0;
    }

    /**
     * Elige el elemento con pin BD fuerte (folio o título) para cortocircuitar semántica.
     * Umbral: folio (100) o título con fuerza >= 30.
     */
    private function pickStrongNamedElemento(string $query, $elementos)
    {
        if (!$elementos || $elementos->isEmpty()) {
            return null;
        }

        $best = null;
        $bestScore = 0;
        foreach ($elementos as $el) {
            $s = $this->namedMatchStrength($query, $el);
            if ($s > $bestScore) {
                $bestScore = $s;
                $best = $el;
            }
        }

        // Folio exacto siempre; título fuerte (p.ej. 3+ palabras distintivas).
        if ($bestScore >= 100 || $bestScore >= 30) {
            $tied = collect($elementos)->filter(
                fn ($el) => $this->namedMatchStrength($query, $el) === $bestScore
            );
            if ($tied->count() > 1) {
                return $this->preferCanonicalByFolio($tied)->first() ?: $best;
            }

            return $best;
        }

        // Solape de título muy alto también ancla (campamentos, cierre de mes…).
        foreach ($elementos as $el) {
            if ($this->titleOverlapRatio($query, $el) >= 0.75) {
                return $el;
            }
        }

        return null;
    }

    /**
     * Si un folio existe en dos fichas (versión vieja / sin Word), Bob se queda
     * con la publicada, activa, con Word y de versión/fecha más nueva.
     */
    private function preferCanonicalByFolio($elementos): Collection
    {
        $items = $elementos instanceof Collection ? $elementos : collect($elementos);
        if ($items->count() < 2) {
            return $items->values();
        }

        $winners = $items->groupBy(function ($el) {
            $folio = trim((string) ($el->folio_elemento ?? ''));

            return $folio !== '' ? mb_strtolower($folio) : 'id:' . $el->getKey();
        })->map(function ($group) {
            return $group->sortByDesc(fn ($el) => $this->canonicalElementoScore($el))->first();
        });

        $ordered = [];
        foreach ($items as $el) {
            $folio = trim((string) ($el->folio_elemento ?? ''));
            $key = $folio !== '' ? mb_strtolower($folio) : 'id:' . $el->getKey();
            if (!isset($ordered[$key])) {
                $ordered[$key] = $winners->get($key) ?? $el;
            }
        }

        return collect(array_values($ordered));
    }

    private function canonicalElementoScore($el): float
    {
        $score = 0.0;
        if (strcasecmp((string) ($el->status ?? ''), 'Publicado') === 0) {
            $score += 100;
        }
        if (!empty($el->active)) {
            $score += 50;
        }

        $wd = $el->relationLoaded('wordDocument') ? $el->wordDocument : null;
        if ($wd && trim((string) ($wd->contenido_texto ?? '')) !== '') {
            $score += 40;
        } elseif (!empty($el->archivo_es_formato) || !empty($el->archivo_markdown)) {
            $score += 10;
        }

        $score += ((float) ($el->version_elemento ?? 0)) * 10;
        $score += ((int) $el->getKey()) * 0.0001;

        return $score;
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
                } elseif ($this->matchesByTypo($w, $queryWords)) {
                    // "solitud" ≈ "solicitud"
                    $matched++;
                }
            }

            // Se considera "nombrado" si aparece la mayoría (>=65%) de las palabras distintivas
            // (antes 75%: fallaba con typos leves tipo solitud/solicitud).
            if (($matched / count($titleWords)) >= 0.65) {
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
     * Typo de 1 carácter (inserción/sustitución/borrado) en palabras >= 6 letras.
     */
    private function matchesByTypo(string $palabra, array $queryWords): bool
    {
        if (mb_strlen($palabra) < 6) {
            return false;
        }

        foreach ($queryWords as $qw) {
            if (mb_strlen($qw) < 5) {
                continue;
            }
            if (levenshtein(
                mb_strtolower($palabra),
                mb_strtolower($qw)
            ) <= 1) {
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
            if (
                mb_strpos($q, $w) !== false
                || $this->matchesByStem($w, $queryWords)
                || $this->matchesByTypo($w, $queryWords)
            ) {
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

        $scored = $candidates
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
            ->values();

        return $this->preferCanonicalByFolio($scored)->take($limit)->values();
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
            $elementos = $this->preferCanonicalByFolio(
                $elementosConScore
                    ->filter(function ($elemento) {
                        return $elemento->relevance_score >= self::ELEMENTO_MIN_RELEVANCE_SCORE;
                    })
                    ->sort(function ($a, $b) {
                        return [$b->relevance_score, (float) $b->version_elemento, (string) $b->created_at]
                            <=> [$a->relevance_score, (float) $a->version_elemento, (string) $a->created_at];
                    })
                    ->values()
            );

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

        // Sección 9 a menudo viene pegada al final sin saltos: "9. RESPONSABLE DEL ELEMENTO:9.1. …"
        if ($sections['RESPONSABLE'] === ''
            && preg_match(
                '/(?:^|[.\s])\d*\.?\s*RESPONSABLE\s+DEL?\s+(?:ELEMENTO|PROCEDIMIENTO)\s*:?\s*(?:\d+\.\d+\.?\s*)?[^\n]{0,120}/iu',
                $text,
                $mResp
            )
        ) {
            $sections['RESPONSABLE'] = trim($mResp[0]);
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
    private function getRankedChunksForDocument($wordDocumentId, ?string $query, int $topN = 12, int $maxChars = 14000): array
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
            ->get(['id', 'content', 'embedding', 'chunk_type', 'section_title']);

        if ($chunks->isEmpty()) {
            return [];
        }

        $ranked = $chunks
            ->map(function ($c) use ($qVec) {
                $c->sim = $this->embeddingService->cosine($qVec, $c->embedding);
                return $c;
            })
            ->sortByDesc('sim')
            ->values();

        $top = $ranked->take($topN);
        $byId = $chunks->keyBy('id');
        $orderedIds = $chunks->sortBy('id')->pluck('id')->values()->all();
        $idPos = array_flip($orderedIds);

        // Vecinos: no mandar un párrafo suelto; el de antes y el de después dan más cobertura.
        $selected = [];
        foreach ($top as $c) {
            $pos = $idPos[$c->id] ?? null;
            foreach ([-1, 0, 1] as $delta) {
                if ($pos === null) {
                    $selected[$c->id] = $c;
                    continue;
                }
                $nid = $orderedIds[$pos + $delta] ?? null;
                if ($nid && isset($byId[$nid])) {
                    $selected[$nid] = $byId[$nid];
                }
            }
        }

        $aspect = $this->detectQueryAspect((string) $query);
        $aspectType = [
            'objetivo' => 'objective',
            'alcance' => 'alcance',
            'actividades' => 'development',
            'responsable' => 'responsibles',
            'definiciones' => 'definitions',
            'evidencias' => 'evidences',
            'riesgos' => 'risks',
        ][$aspect] ?? '';
        if ($aspectType !== '') {
            foreach ($chunks as $c) {
                if (($c->chunk_type ?? '') === $aspectType) {
                    $selected[$c->id] = $c;
                }
            }
        }
        if ($aspect === 'actividades') {
            foreach ($chunks as $c) {
                if (preg_match('/responsable.{0,40}actividad/iu', (string) $c->content)) {
                    $selected[$c->id] = $c;
                }
            }
        }

        // Forzar chunks que contienen el encabezado pedido (RIESGOS, EVIDENCIAS…),
        // aunque el coseno no los haya ranqueado arriba.
        $needles = $this->sectionNeedlesForQuery((string) $query);
        if (!empty($needles)) {
            foreach ($chunks as $c) {
                $content = (string) $c->content;
                foreach ($needles as $needle) {
                    if ($content !== '' && mb_stripos($content, $needle) !== false) {
                        $selected[$c->id] = $c;
                        $pos = $idPos[$c->id] ?? null;
                        if ($pos !== null) {
                            foreach ([-1, 1] as $delta) {
                                $nid = $orderedIds[$pos + $delta] ?? null;
                                if ($nid && isset($byId[$nid])) {
                                    $selected[$nid] = $byId[$nid];
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }

        $out = [];
        $acc = 0;
        foreach (collect($selected)->sortBy('id') as $c) {
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
            $semanticChunks = $this->getRankedChunksForDocument($id, $query, 12, 14000);

            // Cabeza del documento: objetivo/alcance suelen ir al inicio y la búsqueda
            // semántica a veces se queda con el primer hit de otra sección.
            $rawHead = \Illuminate\Support\Facades\DB::table('word_documents')
                ->where('id', $id)
                ->value('contenido_texto');
            $head = trim(preg_replace('/\s+/', ' ', strip_tags((string) $rawHead)));
            if ($head !== '') {
                $docInfo[] = "\n[INICIO DEL DOCUMENTO]:";
                $docInfo[] = mb_substr($head, 0, 1800);
            }

            $keywordSnippets = $this->extractKeywordSectionSnippets((int) $id, (string) $query);
            if (!empty($keywordSnippets)) {
                $docInfo[] = "\n[SECCIÓN POR PALABRA CLAVE EN EL TEXTO COMPLETO]:";
                $docInfo[] = "Si el usuario pide riesgos/evidencias/registros, USA este bloque. "
                    . "No digas que la sección no existe si aquí aparece.";
                foreach ($keywordSnippets as $snippet) {
                    $docInfo[] = $snippet;
                    $docInfo[] = "---";
                }
            }

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
                $chunks = collect($document->matched_chunks)->take(16);

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
                                $extract = mb_substr($fullContent, $start, 2000);
                                $snippets[] = "..." . $extract . "...";

                                if (count($snippets) >= 6) break;
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
                        $docInfo[] = mb_substr($fullContent, 0, 6000);
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

            // 3.4 Empate de documentos: no abrir el primero si hay otro igual de plausible.
            if (!$historyDoc) {
                $tie = $this->maybeClarifyAmbiguousElementos(
                    $query,
                    $searchResults,
                    $startTime,
                    $userId,
                    $sessionId
                );
                if ($tie !== null) {
                    return $tie;
                }
            }

            // 3.5 Metadatos áreas/puestos: respuesta directa desde BD (evita que la IA
            // invente catálogos de áreas a partir de areas_ids de puestos).
            if ($bestElemento && $this->isElementoAreaPuestoMetaQuery($query)) {
                $metaResp = $this->generateElementoAreaPuestoMetaResponse(
                    $query,
                    $bestElemento,
                    $startTime,
                    $userId,
                    $sessionId
                );
                if ($metaResp !== null) {
                    return $metaResp;
                }
            }

            // 3.6 "¿Quién es el responsable?" → BD o sección 9 del Word (RESPONSABLE DEL ELEMENTO).
            if ($bestElemento && $this->isElementoResponsableMetaQuery($query)) {
                $metaResp = $this->generateElementoResponsableMetaResponse(
                    $query,
                    $bestElemento,
                    $startTime,
                    $userId,
                    $sessionId
                );
                if ($metaResp !== null) {
                    return $metaResp;
                }
            }

            // 3.7 Pasos = tabla Responsable | Actividad del Desarrollo (no un título "Actividades").
            if ($bestElemento && $this->isElementoActividadesQuery($query)) {
                $actResp = $this->generateElementoActividadesResponse(
                    $query,
                    $bestElemento,
                    $startTime,
                    $userId,
                    $sessionId
                );
                if ($actResp !== null) {
                    return $actResp;
                }
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
                    'title' => $bestElemento->nombre_elemento,
                    'folio' => $bestElemento->folio_elemento ?? null,
                ];
            }

            return $aiResult;
        } catch (\Exception $e) {
            \Log::error('Error crítico en generateResponseWithFallback: ' . $e->getMessage());
            return $this->generateDataBasedResponse($query, $searchResults, $startTime, $userId, $sessionId);
        }
    }

    /**
     * Si el 2º documento está muy cerca del 1º, no abrir el primero: preguntar.
     */
    private function maybeClarifyAmbiguousElementos(
        $query,
        array $searchResults,
        $startTime,
        $userId,
        $sessionId
    ): ?array {
        $q = (string) $query;
        if (
            $this->shouldRouteToHrContact($q)
            || $this->isWhoToContactQuery($q)
            || $this->detectHrPersonalTopic($q) !== ''
            || $this->isRoleDutiesQuery($q)
            || $this->isPeopleOrOrgDirectoryQuery($q)
            || $this->isFullEmployeeDumpQuery($q)
        ) {
            return null;
        }

        $els = $searchResults['elementos'] ?? collect();
        if (!$els instanceof Collection || $els->count() < 2) {
            return null;
        }

        $first = $els->get(0);
        $second = $els->get(1);
        if (!$first || !$second) {
            return null;
        }

        if (!empty($first->named_match) && (float) ($first->named_strength ?? 0) >= 30) {
            return null;
        }

        $s1 = (float) ($first->fused_score ?? 0);
        $s2 = (float) ($second->fused_score ?? 0);
        if ($s1 <= 0 || $s2 <= 0) {
            return null;
        }
        if ($s1 >= 8 && ($s1 - $s2) >= 1.5) {
            return null;
        }
        if (($s2 / max($s1, 0.0001)) < 0.82) {
            return null;
        }

        return $this->buildAmbiguousDocChoiceResponse(
            $query,
            $els->take(3),
            $startTime,
            $userId,
            $sessionId
        );
    }

    private function buildAmbiguousDocChoiceResponse(
        $query,
        $elementos,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $lineas = [];
        $chips = [];
        foreach ($elementos as $el) {
            $nombre = trim((string) ($el->nombre_elemento ?? 'Documento'));
            $folio = trim((string) ($el->folio_elemento ?? ''));
            $lineas[] = '- **' . $nombre . '**' . ($folio !== '' ? " ({$folio})" : '');
            $chips[] = [
                'label' => mb_substr($nombre, 0, 28),
                'query' => $folio !== '' ? $folio : $nombre,
            ];
        }

        $msg = "Encontré varios documentos similares y prefiero no asumir el primero.\n\n"
            . implode("\n", $lineas)
            . "\n\n¿Cuál necesitas consultar?";

        return [
            'response' => $msg,
            'method' => 'conversation_ambiguous_docs',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['catalog_mode' => 'ambiguous_docs'],
            'cached' => false,
            'document' => null,
            'chips' => $chips,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_ambiguous_docs',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
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
     * Preguntas de metadatos del elemento en foco: áreas involucradas o puestos de un área.
     * Se responden desde BD para no inventar catálogos (Calidad, Administración…).
     */
    private function isElementoAreaPuestoMetaQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        $asksAreas = (bool) preg_match(
            '/\b(áreas?|areas?)\b.*(involucr|particip|entran|tiene|tienen|hay|relacion)|'
            . 'involucr\w*\s+(áreas?|areas?)|'
            . 'qué\s+(áreas?|areas?)|que\s+(áreas?|areas?)|'
            . 'se\s+involucran\s+(áreas?|areas?)/u',
            $q
        );

        $asksPuestosArea = (bool) preg_match(
            '/puestos?\s+.+\b(área|area)\b|'
            . 'puestos?\s+de\s+\w+|'
            . '(área|area)\s+de\s+\w+.+\bpuestos?\b|'
            . 'qué\s+puestos?\s+.+\b(área|area|\w+)\b|'
            . 'que\s+puestos?\s+.+\b(área|area|\w+)\b|'
            . '(tiene|tienen|hay)\s+de\s+\w+|'
            . 'y\s+de\s+(calidad|jur[ií]dic\w*|ti|tecnolog\w*)\b/u',
            $q
        );

        $asksPuestosVinculados = (bool) preg_match(
            '/puestos?\s+(relacionados|vinculados|involucrados)|'
            . 'quiénes\s+(participan|entran)|quienes\s+(participan|entran)/u',
            $q
        );

        // Seguimiento corto nombrando área: "juridico?", "y calidad?"
        $asksAreaShortcut = (bool) preg_match(
            '/^\s*(y\s+)?(de\s+)?(calidad|jur[ií]dic\w*|ti|tecnolog[ií]as?)\s*\??\s*$/u',
            $q
        );

        return $asksAreas || $asksPuestosArea || $asksPuestosVinculados || $asksAreaShortcut;
    }

    /**
     * "quién es el responsable del procedimiento/elemento …"
     */
    private function isElementoResponsableMetaQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        // Evitar confusión con directorio de unidad/área.
        if (preg_match('/\b(unidad|[aá]rea|empresa|departamento)\b/u', $q)) {
            return false;
        }

        return (bool) preg_match(
            '/\b(qui[eé]n|quien|cu[aá]l).{0,60}\b(re?sponsables?|encargad[oa]s?)\b|'
            . '\b(re?sponsables?|encargad[oa]s?)\s+(del|de\s+l[ao]s?|principal)\b|'
            . '\b(re?sponsable|encargado)\s+de\b|'
            . '\b(debe tener|tiene que tener|s[ií] (que )?(tiene|tiene un|est[aá]|existe)).{0,30}re?sponsables?\b/u',
            $q
        );
    }

    /**
     * Extrae responsable(s) del Word: el número de sección varía (9, 10…)
     * y el título también (DEL ELEMENTO / DE PROCEDIMIENTO). Puede haber 9.1 y 9.2.
     *
     * @return array{heading:string,puestos:array<int,string>}
     */
    private function sgcStructure(): SgcProcedureStructureService
    {
        return app(SgcProcedureStructureService::class);
    }

    private function extractResponsableSectionFromDocument(?string $text): array
    {
        return $this->sgcStructure()->extractResponsableSection($text);
    }

    private function collectElementoTextForResponsable(Elemento $elemento): string
    {
        return $this->sgcStructure()->collectElementoText($elemento);
    }

    private function isElementoActividadesQuery(string $query): bool
    {
        return $this->detectQueryAspect($query) === 'actividades';
    }

    private function generateElementoActividadesResponse(
        string $query,
        $elemento,
        $startTime,
        $userId,
        $sessionId
    ): ?array {
        $elemento->loadMissing(['wordDocument:id,elemento_id,contenido_texto']);
        $rows = $this->sgcStructure()->extractActividadesTable(
            $this->sgcStructure()->collectElementoText($elemento)
        );
        if (empty($rows)) {
            return null;
        }

        $nombreDoc = $elemento->nombre_elemento ?? 'este procedimiento';
        $msg = "Estas son las actividades de **{$nombreDoc}**, según la tabla Responsable | Actividad del Desarrollo:\n";
        $n = 1;
        foreach (array_slice($rows, 0, 40) as $row) {
            $msg .= "\n{$n}. **{$row['responsable']}** — {$row['actividad']}";
            $n++;
        }
        if (count($rows) > 40) {
            $msg .= "\n\nHay más pasos en el documento; si quieres, te los detallo por responsable.";
        }

        return [
            'response' => $msg,
            'method' => 'elemento_meta_actividades',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [
                'meta' => 'actividades',
                'filas' => count($rows),
            ],
            'cached' => false,
            'document' => $this->buildDocumentCard($elemento),
            'final_context' => [
                'id' => $elemento->getKey(),
                'title' => $elemento->nombre_elemento,
            ],
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'elemento_meta_actividades',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    private function extractResponsableFromDocumentText(?string $text): ?string
    {
        $sec = $this->extractResponsableSectionFromDocument($text);
        if (empty($sec['puestos'])) {
            return null;
        }

        return implode(', ', $sec['puestos']);
    }

    private function resolveElementoResponsableNombre($elemento): array
    {
        $elemento->loadMissing([
            'puestoResponsable:id_puesto_trabajo,nombre',
            'wordDocument:id,elemento_id,contenido_texto',
        ]);

        $text = $this->collectElementoTextForResponsable($elemento);
        $sec = $this->extractResponsableSectionFromDocument($text);
        if (!empty($sec['puestos'])) {
            return [
                'nombre' => implode(', ', $sec['puestos']),
                'puestos' => $sec['puestos'],
                'heading' => $sec['heading'],
                'fuente' => 'documento',
            ];
        }

        $fromBd = optional($elemento->puestoResponsable)->nombre;
        if ($fromBd) {
            return [
                'nombre' => $fromBd,
                'puestos' => [$fromBd],
                'heading' => '',
                'fuente' => 'bd',
            ];
        }

        return ['nombre' => null, 'puestos' => [], 'heading' => '', 'fuente' => 'ninguna'];
    }

    private function generateElementoResponsableMetaResponse(
        string $query,
        $elemento,
        $startTime,
        $userId,
        $sessionId
    ): ?array {
        $nombreDoc = $elemento->nombre_elemento ?? 'este procedimiento';
        $resolved = $this->resolveElementoResponsableNombre($elemento);
        $meta = $this->paidAIService->resolveElementoRelatedData($elemento);
        $rels = ($meta['puestos_relacionados'] ?? collect())->pluck('nombre')->filter()->values();

        $puestos = $resolved['puestos'] ?? array_filter([(string) ($resolved['nombre'] ?? '')]);
        if (!empty($puestos)) {
            if (count($puestos) === 1) {
                $msg = "El responsable de **{$nombreDoc}** es el **{$puestos[0]}**.";
            } else {
                $msg = "Los responsables de **{$nombreDoc}** son:\n"
                    . collect($puestos)->map(fn ($p) => '- **' . $p . '**')->implode("\n");
            }
        } else {
            $msg = "En **{$nombreDoc}** no aparece un responsable con claridad. "
                . "Si tienes el nombre del puesto, indícamelo y lo ubico.";
            if ($rels->isNotEmpty()) {
                $msg .= "\n\nSí participan, entre otros:\n"
                    . $rels->take(3)->map(fn ($p) => '- ' . $p)->implode("\n");
            }
        }

        return [
            'response' => $msg,
            'method' => 'elemento_meta_responsable',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [
                'meta' => 'responsable',
                'fuente' => $resolved['fuente'],
            ],
            'cached' => false,
            'document' => $this->buildDocumentCard($elemento),
            'final_context' => [
                'id' => $elemento->getKey(),
                'title' => $elemento->nombre_elemento,
            ],
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'elemento_meta_responsable',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * Respuesta estructurada: áreas/puestos del elemento sin pasar por la IA.
     */
    private function generateElementoAreaPuestoMetaResponse(
        string $query,
        $elemento,
        $startTime,
        $userId,
        $sessionId
    ): ?array {
        $elemento->loadMissing([
            'puestoResponsable:id_puesto_trabajo,nombre,areas_ids',
            'tipoElemento:id_tipo_elemento,nombre',
        ]);

        $meta = $this->paidAIService->resolveElementoRelatedData($elemento);
        $nombreDoc = $elemento->nombre_elemento ?? 'este procedimiento';
        $areaPedida = $this->paidAIService->detectAreaMentionInQuery($query, $meta['puestos_por_area'] ?? collect());
        $q = mb_strtolower($query);

        $asksPuestosVinculados = (bool) preg_match(
            '/puestos?\s+(relacionados|vinculados|involucrados)|quiénes\s+(participan|entran)|quienes\s+(participan|entran)/u',
            $q
        );

        // Si nombró un área (Calidad, Jurídico…), responder puestos de esa área.
        if ($areaPedida) {
            $puestos = $this->paidAIService->puestosVinculadosMatchingAreaName($elemento, $meta, $areaPedida);
            if (empty($puestos)) {
                $msg = "En **{$nombreDoc}** no hay puestos del área de **{$areaPedida}** "
                    . "en la lista vinculada (responsable y puestos relacionados).\n\n"
                    . "Si quieres, te listo los puestos que sí están vinculados al procedimiento.";
            } else {
                $bullets = collect($puestos)->map(fn ($p) => '- ' . $p)->implode("\n");
                $msg = "En **{$nombreDoc}**, estos puestos de la lista vinculada coinciden con **{$areaPedida}**:\n\n"
                    . $bullets
                    . "\n\n¿Quieres el detalle de alguno?";
            }
        } elseif ($asksPuestosVinculados) {
            $resp = optional($elemento->puestoResponsable)->nombre;
            $rels = ($meta['puestos_relacionados'] ?? collect())->pluck('nombre')->filter()->values();
            $lines = [];
            if ($resp) {
                $lines[] = '- **Responsable:** ' . $resp;
            }
            if ($rels->isNotEmpty()) {
                $lines[] = '- **Relacionados:**';
                foreach ($rels->take(40) as $p) {
                    $lines[] = '  - ' . $p;
                }
                if ($rels->count() > 40) {
                    $lines[] = '  - … y ' . ($rels->count() - 40) . ' más';
                }
            }
            if (empty($lines)) {
                $msg = "En **{$nombreDoc}** no hay puestos responsable ni relacionados registrados en BD.";
            } else {
                $msg = "En **{$nombreDoc}** estos son los puestos vinculados en BD:\n\n" . implode("\n", $lines);
            }
        } else {
            // Pregunta genérica de áreas: NO volcar catálogo de areas_ids.
            $nRels = ($meta['puestos_relacionados'] ?? collect())->count();
            $resp = optional($elemento->puestoResponsable)->nombre;
            $msg = "En **{$nombreDoc}** no hay un campo de **áreas** del procedimiento. "
                . "Lo que sí está registrado son los **puestos vinculados**"
                . ($resp ? " (responsable: **{$resp}**" . ($nRels ? " y {$nRels} relacionados)" : ')') : ($nRels ? " ({$nRels} relacionados)" : ''))
                . ".\n\n"
                . "Si te interesa un área concreta (por ejemplo **Calidad** o **TI**), dime cuál y te digo "
                . "qué puestos de esa área aparecen en la lista vinculada.";
        }

        return [
            'response' => $msg,
            'method' => 'elemento_meta_areas_puestos',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['meta' => 'areas_puestos_bd'],
            'cached' => false,
            'document' => $this->buildDocumentCard($elemento),
            'final_context' => [
                'id' => $elemento->getKey(),
                'title' => $elemento->nombre_elemento,
            ],
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'elemento_meta_areas_puestos',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
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

        $meta = $this->paidAIService->resolveElementoRelatedData($elemento);
        $unidades = $meta['unidades']->pluck('nombre')->filter()->implode(', ');

        $resolvedResp = $this->resolveElementoResponsableNombre($elemento);

        return [
            'nombre'      => $elemento->nombre_elemento ?? 'Documento',
            'folio'       => $elemento->folio_elemento ?? null,
            'version'     => $elemento->version_elemento ?? null,
            'tipo'        => optional($elemento->tipoElemento)->nombre,
            'unidad'      => $unidades !== '' ? $unidades : (optional($elemento->unidadNegocio)->nombre),
            'responsable' => $resolvedResp['nombre'],
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
            $history = $this->getConversationHistory($sessionId, 12, $userId);

            // 2. MEDIR TIEMPO IA
            $aiStartTime = microtime(true);
            $chatTimeout = $this->paidAIService->getChatTimeout();

            try {
                $conversationState = [
                    'focused_title' => $elemento ? ($elemento->nombre_elemento ?? null) : null,
                    'focused_folio' => $elemento ? ($elemento->folio_elemento ?? null) : null,
                    'search_query' => $this->lastSearchReasoning['search'] ?? null,
                    'inferred_intent' => $this->lastSearchReasoning['intent'] ?? null,
                    'aspect' => $this->lastSearchReasoning['aspect'] ?? null,
                ];

                // 3. GENERAR RESPUESTA OPENAI
                $aiResponse = $this->paidAIService->generateResponse(
                    $query,
                    $context,
                    $chatTimeout,
                    $history,
                    $elemento,
                    $conversationState
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
                    $aiElapsed >= $chatTimeout ||
                    str_contains($aiException->getMessage(), 'timeout') ||
                    str_contains($aiException->getMessage(), 'timed out')
                ) {
                    \Log::warning("IA de pago tardó más de {$chatTimeout}s, fallback a datos");
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
            $chatTimeout = $this->paidAIService->getChatTimeout();

            // 1. OBTENER HISTORIAL (¡NUEVO!)
            $history = $this->getConversationHistory($sessionId, 12, $userId);

            try {
                // 2. Generar respuesta PASANDO EL HISTORIAL (4to parámetro)
                $aiResponse = $this->paidAIService->generateResponse(
                    $query,
                    $this->applyToneInstruction(),
                    $chatTimeout,
                    $history
                );

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
                    $aiElapsed >= $chatTimeout ||
                    strpos($aiException->getMessage(), 'timeout') !== false ||
                    strpos($aiException->getMessage(), 'timed out') !== false
                ) {

                    Log::warning("IA de pago tardó más de {$chatTimeout} segundos, usando respuesta genérica");
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

    /**
     * Quita el preámbulo interrogativo ("quién es…", "quiero saber…") de una consulta
     * de directorio.
     *
     * findPuestosMentionedInQuery() intenta primero rol+área y, si ese par no existe en
     * el catálogo (no hay "Jefe de TI"), cae a un AND de tokens que sí encuentra el
     * puesto correcto del área. Ese fallback exige que el nombre del puesto contenga
     * TODOS los tokens, y "quien" sobrevive a su lista de stopwords: basta esa palabra
     * para que nunca case. Por eso "jefe de ti" resuelve y "quién es jefe de ti" no.
     *
     * Sólo se toca el arranque de la frase. Nada que las ramas de directorio usen como
     * señal ("dime", "decir", "cuáles", "hay") se remueve.
     */
    private function stripDirectoryQuestionPreamble(string $query): string
    {
        $patrones = [
            '/^\s*(me\s+)?(lo\s+)?(puedes?|podrias?)\s+(decir(me)?|indicar(me)?)\s+/iu',
            '/^\s*qui[eé]n(es)?\s+(es|son)\s+/iu',
            '/^\s*qui[eé]n(es)?\s+/iu',
            '/^\s*(yo\s+)?(quiero|necesito|quisiera|me\s+gustaria|me\s+gustaría)\s+(saber|conocer|ver)\s+/iu',
            '/^\s*(saber|conocer)\s+/iu',
        ];

        $out = $query;
        foreach ($patrones as $patron) {
            $out = preg_replace($patron, '', $out) ?? $out;
        }

        $out = trim($out);

        return $out !== '' ? $out : $query;
    }

    /**
     * ¿La pregunta sólo tiene sentido respecto del documento en foco?
     *
     * Dos formas:
     *  - Pronominal: "cuál es SU alcance", "de ESE procedimiento", "el mismo".
     *  - Atributo suelto: "cuál es el objetivo", "quién es el responsable", "los pasos".
     *
     * Estas preguntas no traen contenido propio, así que su embedding se parece a la
     * sección homónima de CUALQUIER documento: simDoc queda ~0.29, cae en la zona gris
     * de la decisión de contexto y el foco se suelta. Detectarlas permite anclarlas
     * antes de esa decisión.
     */
    private function isContextDependentQuestion(string $query): bool
    {
        $q = $this->foldAccents($query);
        $q = trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $q)));

        if ($q === '') {
            return false;
        }

        // "su puesto" de una persona nombrada no es el alcance del PDF en foco.
        if ($this->isWhoIsPersonQuery($query) || $this->isEmployeeConfirmQuery($query)
            || $this->isPersonLookupFollowUp($query)
            || $this->isRoleDutiesQuery($query)
            || $this->queryNamesDirectoryPuesto($query)
        ) {
            return false;
        }

        $pronominal = '/\b(su|sus|este|esta|ese|esa|eso|esos|esas|mismo|misma|ahi|alli|dicho|citado|lo mismo|eso mismo)\b/u';

        // Folio o nombre real de documento: la pregunta se identifica sola.
        if (!empty($this->extractFolioPatterns($query)) || $this->matchesKnownDocumentName($q)) {
            return false;
        }

        if (str_word_count($q) > 14 && !preg_match($pronominal, $q)) {
            return false;
        }

        $atributo = '/\b(objetivo|objetivos|alcance|alcances|re?sponsable|re?sponsables|'
            . 'paso|pasos|actividad|actividades|riesgo|riesgos|indicador|indicadores|'
            . 'politica|politicas|registro|registros|referencia|referencias|'
            . 'frecuencia|periodicidad|vigencia|version|proposito|finalidad|'
            . 'entradas|salidas|formatos|anexos|definiciones|glosario|requisitos|'
            . 'controles|evidencias?|flujograma|resumen)\b/u';

        $seguimiento = '/\b(explica|explicame|expliqueme|detalle|detalla|'
            . 'profundiza|continua|sigue|mas|completo|a fondo|'
            . 'como se hace|que hago)\b/u';

        if ($this->resolveChitChatCategory($query) !== null) {
            return false;
        }

        if (preg_match($pronominal, $q) || preg_match($seguimiento, $q)) {
            return true;
        }

        if (!preg_match($atributo, $q)) {
            return false;
        }

        if (preg_match('/\b(cual|cuales|que|quien|quienes|dame|dime|explica|cuentame|muestra)\b/u', $q)) {
            return true;
        }

        return str_word_count($q) <= 6;
    }

    /**
     * Reescribe la pregunta incluyendo el título del documento en foco.
     *
     * Sube simDoc de ~0.29 a ~0.62 (medido), de modo que la decisión de contexto
     * existente elige "quedarse" por sí sola. No se toca esa decisión: sólo se le
     * entrega una pregunta que sí identifica al documento.
     */
    private function anchorQuestionToFocusedDoc(string $query, array $cachedContext): string
    {
        $titulo = trim((string) ($cachedContext['title'] ?? ''));
        if ($titulo === '') {
            return $query;
        }

        return trim("{$titulo}: " . trim($query));
    }

    /**
     * Combina hilo + documento en foco + (si aplica) IA para armar la query de búsqueda.
     *
     * @return array{search:string,intent:string,aspect:string}
     */
    private function applyConversationalSearchReasoning(
        string $cleanQuery,
        string $searchQuery,
        ?array $cachedContext,
        $sessionId,
        $userId
    ): array {
        $history = $this->getConversationHistory($sessionId, 6, $userId);

        if ($this->isTopicEscapeQuery($cleanQuery, $cachedContext)) {
            return [
                'search' => $searchQuery,
                'intent' => 'switch',
                'aspect' => '',
            ];
        }

        $local = $this->buildThreadAwareSearchQuery($searchQuery, $cachedContext, $history, $cleanQuery);
        $out = [
            'search' => $local,
            'intent' => ($cachedContext && !empty($cachedContext['id'])) ? 'followup' : 'new',
            'aspect' => '',
        ];

        if (!$this->searchQueryNeedsReasoning($cleanQuery, $cachedContext, $history)) {
            return $out;
        }

        $title = trim((string) ($cachedContext['title'] ?? ''));
        $folio = trim((string) ($cachedContext['folio'] ?? ''));
        $reasoned = null;
        if ($this->usePaidAI) {
            try {
                $reasoned = $this->paidAIService->reasonSearchQuery(
                    $cleanQuery,
                    $history,
                    $title !== '' ? $title : null,
                    $folio !== '' ? $folio : null
                );
            } catch (\Exception $e) {
                \Log::warning('Razonamiento de búsqueda no disponible: ' . $e->getMessage());
            }
        }

        if (!is_array($reasoned) || empty($reasoned['search'])) {
            return $out;
        }

        $search = $this->normalizeColloquialQuery($reasoned['search']);
        $intent = $reasoned['intent'] ?? $out['intent'];
        if ($title !== '' && $intent !== 'switch' && mb_stripos($search, mb_substr($title, 0, 10)) === false) {
            $search = $title . ': ' . $search;
        }

        return [
            'search' => $search,
            'intent' => $intent,
            'aspect' => (string) ($reasoned['aspect'] ?? ''),
        ];
    }

    private function buildThreadAwareSearchQuery(
        string $searchQuery,
        ?array $cachedContext,
        array $history,
        string $cleanQuery
    ): string {
        $title = trim((string) ($cachedContext['title'] ?? ''));
        if ($title !== '' && $this->isTopicEscapeQuery($cleanQuery, $cachedContext)) {
            return mb_substr($searchQuery, 0, 240);
        }
        $lastUser = '';
        foreach (array_reverse($history) as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $lastUser = trim(strip_tags((string) ($msg['content'] ?? '')));
                break;
            }
        }

        $bits = [];
        if ($title !== '') {
            $bits[] = $title;
        }
        $currentShort = str_word_count(mb_strtolower($cleanQuery)) <= 8;
        if (
            $title !== ''
            && $currentShort
            && $lastUser !== ''
            && mb_strtolower($lastUser) !== mb_strtolower($cleanQuery)
            && mb_strlen($lastUser) <= 90
        ) {
            $bits[] = $lastUser;
        }
        $bits[] = $searchQuery;

        $joined = trim(implode(' ', array_unique($bits)));
        return mb_substr($joined, 0, 240);
    }

    private function searchQueryNeedsReasoning(string $query, ?array $cachedContext, array $history): bool
    {
        if (!empty($this->extractFolioPatterns($query))) {
            return false;
        }
        if ($this->isTopicEscapeQuery($query, $cachedContext)) {
            return false;
        }
        if ($this->isWhoIsPersonQuery($query) || $this->isEmployeeConfirmQuery($query)) {
            return false;
        }
        if ($cachedContext && !empty($cachedContext['id'])) {
            return true;
        }
        if ($this->isContextDependentQuestion($query)) {
            return true;
        }
        $wc = str_word_count(mb_strtolower(trim($query)));
        if ($wc <= 10 && !empty($history)) {
            return true;
        }
        return $wc <= 6;
    }

    /**
     * Patrones de charla / queja / meta, agrupados por categoría de respuesta.
     *
     * @return array<string, array<int, string>>
     */
    private function chitChatPatterns(): array
    {
        return [
            // RECHAZO / corrección: el usuario dice que no es eso.
            // Esta categoría SUELTA el documento en foco y vuelve a preguntar qué quiere.
            'queja' => [
                // --- Negación al arranque: cubre la mayoría de formas de rechazo
                //     ("no es de eso we", "no requiero", "no we", "no, otra cosa").
                '/^no\b/u',
                '/^(nel|nop|nope|nah|nada|ninguno|ninguna|ningun)\b/u',
                '/^(ya no|mejor no|asi no|pues no|creo que no)\b/u',

                // --- Negación explícita del objeto
                '/\bno (es|era|fue|seria) (de |el |la )?(eso|ese|esa|esto|este|esta|ahi|alli)\b/u',
                '/\b(eso|ese|esa|esto) no (es|era|va|aplica)\b/u',
                '/\b(ese|esa|eso|esto|asi) no\b/u',
                '/\bno es lo que\b/u',
                '/\bni (uno|una|ese|eso)\b/u',
                '/\bpara nada\b/u',
                '/\bnada que ver\b/u',
                '/\bno tiene nada que ver\b/u',

                // --- El usuario dice que no lo quiere / no lo pidió
                '/\bno (requiero|necesito|quiero|queria|busco|buscaba|pedi|pregunte|solicite)\b/u',
                '/\bno me (sirve|refiero|ayuda|interesa|funciona eso)\b/u',
                '/\bno era (eso|lo que)\b/u',
                '/\bno aplica\b/u',
                '/\bno va por (ahi|ahí)\b/u',
                '/\bpor (ahi|ahí) no\b/u',

                // --- Cancelar / abortar / soltar el tema
                '/\b(cancela|cancelalo|cancelar|cancelemos)\b/u',
                '/\b(dejalo|dejalo asi|dejemoslo|ya dejalo|olvidalo|olvidemoslo)\b/u',
                '/\b(quitalo|sacalo|bajale|parale|ya parale|alto)\b/u',
                '/\b(mejor|prefiero) (otra|otro|no)\b/u',
                '/\b(otra cosa|otro tema|otro documento|otro procedimiento)\b/u',
                '/\b(cambiemos|cambia de tema|cambiale|movamonos)\b/u',
                '/\b(empecemos de nuevo|de nuevo|desde cero|reinicia eso)\b/u',
                '/\b(regresa|regresemos|volvamos|atras|vuelve)\b/u',

                // --- El bot se equivocó
                '/\b(estas|esta|estan|andas) mal\b/u',
                '/\bte (equivocas|equivocaste|volaste|fuiste)\b/u',
                '/\bno (sirves|entiendes|entendiste|captas|me entiendes|le atinas)\b/u',
                '/\b(estas|andas) fallando\b/u',
                '/\bya valiste\b/u',
                '/\bque onda\b/u',
                '/\b(incorrecto|equivocado|pesimo|malisimo|erroneo)\b/u',
                '/\bno es correcto\b/u',
                '/\b(me perdiste|ya me perdi|me perd[ií]|estoy perdido|estoy perdida)\b/u',
                '/^mal$/u',
            ],
            'risa' => [
                '/\bja(ja)+\b/u',
                '/\bje(je)+\b/u',
                '/\bxd+\b/u',
                '/\blol\b/u',
            ],
            'cortesia' => [
                '/\bgracias\b/u',
                '/^(va|sale|listo|perfecto|excelente|muy bien|de acuerdo|vale|entendido|orale)$/u',
            ],
            'saludo' => [
                '/^(como estas|como esta|que tal|que tal estas|todo bien)\b/u',
            ],
            'despedida' => [
                '/^(adios|bye|hasta luego|hasta pronto|nos vemos|chao|chau)\b/u',
            ],
        ];
    }

    /**
     * Categoría de charla / queja / meta del mensaje, o null si es consulta real.
     *
     * COMPUERTA CONVERSACIONAL: estos mensajes no preguntan por un documento, pero la
     * búsqueda híbrida siempre devuelve un top-1, así que acababan robando el foco y
     * disparando "Cambiando a …". Aquí se detectan ANTES de buscar.
     *
     * Sólo aplica a mensajes cortos y sin señal de documento: con folio, nombre de un
     * elemento real o suficiente detalle, se deja pasar a la búsqueda normal.
     */
    /**
     * Frases de cancelación que NO pueden confundirse con el nombre de un documento.
     * Se evalúan antes que cualquier guarda para que ninguna heurística las descarte.
     */
    private function matchesUnambiguousCancellation(string $qFold): bool
    {
        if (str_word_count($qFold) > 5) {
            return false;
        }

        $patrones = [
            '/^(nel|nop|nope|nah)\b/u',
            '/^(ya no|mejor no|asi no|pues no|creo que no)\b/u',
            '/\b(mejor|prefiero) (otra|otro|no)\b/u',
            '/\b(cancela|cancelalo|cancelar|cancelemos)\b/u',
            '/\b(dejalo|dejemoslo|olvidalo|olvidemoslo)\b/u',
            '/\b(ninguno|ninguna|ningun)\b/u',
            '/\bpara nada\b/u',
            '/\bnada que ver\b/u',
            '/\bno me interesa\b/u',
            '/\bte (equivocas|equivocaste|volaste)\b/u',
            '/\b(me perdiste|ya me perdi)\b/u',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $qFold)) {
                return true;
            }
        }

        return false;
    }

    private function resolveChitChatCategory(string $query): ?string
    {
        $q = $this->foldAccents($query);
        $q = trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $q)));

        if ($q === '') {
            return null;
        }

        // Cancelación inequívoca: ninguna de estas frases puede ser el nombre de un
        // documento, así que se resuelve ANTES de las guardas. Necesario porque la
        // heurística de "nombre conocido" tiene falsos positivos por subcadena
        // (ej. "mejor no" choca con el elemento "Realizar Mejoras al SGC").
        if ($this->matchesUnambiguousCancellation($q)) {
            return 'queja';
        }

        // Señal de documento (folio, nombre real, detalle) → es búsqueda, no charla.
        if ($this->mentionsSpecificDocumentSignal($query)) {
            return null;
        }

        // Catálogo o directorio: los resuelven las compuertas previas.
        if ($this->isCatalogBrowseQuery($query) || $this->isPeopleOrOrgDirectoryQuery($query)) {
            return null;
        }

        // Frase larga = probablemente pregunta real aunque traiga una palabra suelta.
        if (str_word_count($q) > 5) {
            return null;
        }

        foreach ($this->chitChatPatterns() as $categoria => $patrones) {
            foreach ($patrones as $patron) {
                if (preg_match($patron, $q)) {
                    return $categoria;
                }
            }
        }

        return null;
    }

    /**
     * Respuesta a un mensaje de charla / queja / meta.
     * NO busca y NO toca el documento en foco: sólo reencauza al usuario.
     */
    private function buildChitChatResponse(
        string $categoria,
        string $query,
        ?array $cachedContext,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $titulo = trim((string) ($cachedContext['title'] ?? ''));

        // Títulos genéricos ("Documento") no sirven para reanclar al usuario.
        $genericos = ['documento', 'documentos', 'procedimiento', 'procedimientos', 'elemento'];
        if ($titulo === '' || in_array(mb_strtolower($titulo), $genericos, true)) {
            $titulo = null;
        }

        switch ($categoria) {
            case 'queja':
                $msg = "Disculpa, me desvié del tema.\n\n"
                    . ($titulo ? "Podemos continuar con **{$titulo}** si lo deseas.\n\n" : '')
                    . "Para ubicarte con precisión, indica cualquiera de estos datos:\n\n"
                    . "- El **folio** (por ejemplo, PAA01-PR02)\n"
                    . "- El **nombre** del procedimiento\n"
                    . "- O un **área** (por ejemplo, procedimientos de Compras)";
                break;

            case 'cortesia':
                $msg = $titulo
                    ? "Con gusto. Seguimos en **{$titulo}** por si necesitas otro dato, "
                        . "o puedes indicar un folio, un nombre o un área."
                    : "Con gusto. Si necesitas algo más, indica un folio, "
                        . "el nombre de un procedimiento o un área.";
                break;

            case 'saludo':
                $msg = $titulo
                    ? "Muy bien. Continuamos con **{$titulo}**. ¿Qué te gustaría consultar de ese procedimiento?"
                    : "Muy bien. ¿En qué puedo ayudarte? Puedo consultar un procedimiento, el directorio o un correo.";
                break;

            case 'despedida':
                $msg = "Quedo a tu disposición cuando lo necesites.";
                break;

            case 'risa':
            default:
                $msg = $titulo
                    ? "Continuamos con **{$titulo}**. ¿Seguimos con ese documento o prefieres consultar otro?"
                    : "¿En qué puedo ayudarte? Indica un folio, el nombre de un procedimiento o un área.";
                break;
        }

        return [
            'response' => $msg,
            'method' => 'conversation_chitchat',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['chitchat_category' => $categoria],
            'cached' => false,
            'document' => null,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_chitchat',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * ¿La pregunta es inequívocamente ajena al SGC (matemáticas, chistes, consejos
     * personales, roleplay, cultura general, tareas escolares, inyección de
     * instrucciones…)? Bloque aditivo, hermano de resolveChitChatCategory().
     *
     * Solo dispara con patrones que NO tienen forma de ser una pregunta real del
     * SGC (nada de folios, puestos, áreas ni procedimientos se parece a "cuéntame
     * un chiste" o "raíz cuadrada de 144"). Ante la duda, no dispara: se prefiere
     * dejar pasar algo raro a bloquear por error una consulta legítima.
     */
    private function isFueraDeTemaQuery(string $query): bool
    {
        $q = $this->foldAccents($query);
        $q = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $q));
        $q = trim(preg_replace('/\s+/u', ' ', $q));

        if ($q === '') {
            return false;
        }

        // Datos sensibles / off-topic duros: ganan aunque mencione un puesto del SGC
        // ("cuánto gana un analista jurídico" NO debe ir a un PDF de Jurídico).
        if (preg_match(
            '/\b(sueldo|salario|nomina|cuanto\s+gana|chisme|api\s+key|system\s+prompt|'
            . 'jailbreak|va\s+a\s+llover|temperatura\s+en)\b/u',
            $q
        )) {
            return true;
        }

        // Señal de documento real: nunca es "fuera de tema" aunque comparta alguna
        // palabra con los patrones de abajo.
        if ($this->mentionsSpecificDocumentSignal($query) || !empty($this->extractFolioPatterns($query))) {
            return false;
        }

        $patrones = [
            // Matemáticas / aritmética directa. Los folios son letras+dígitos con
            // guión (PAA01-PR04); esto exige dígito, operador en palabra o símbolo, y
            // otro dígito, así que nunca coincide con un folio.
            '/\b\d+\s*(por|entre|mas|menos|dividido\s+entre)\s*\d+\b/u',
            '/\b\d+\s*[\+\-x\*\/]\s*\d+\b/u',
            '/\bra[ií]z\s+cuadrada\b/u',
            '/\bqu[eé]\s+porcentaje\s+es\s+\d+/u',
            '/\bes\s+primo\s+el\s+n[uú]mero\b/u',
            '/\bcu[aá]nto\s+es\s+\d+\s*(por|entre|mas|menos|\+|\-|x|\*)\b/u',
            '/\bconvierte\s+\d+.{0,15}(dolares|pesos|euros)\b/u',

            // Roleplay / contenido creativo sin relación al SGC.
            '/\bfinge\s+que\s+eres\b/u',
            '/\bcu[eé]ntame\s+un\s+chiste\b/u',
            '/\bdime\s+un\s+chiste\b/u',
            '/\b(un\s+)?(poema|poesia)\b/u',
            '/\btraduc(e|cion|ir)\b.{0,20}\b(ingles|frances|espanol)\b/u',

            // Cultura general / actualidad ajena a Proser.
            '/\bpresidente\s+de\s+(mexico|espana|estados\s+unidos|francia)\b/u',
            '/\bcapital\s+de\s+(mexico|espana|francia|estados\s+unidos)\b/u',
            '/\bquien\s+gan[oó]\s+el\s+mundial\b/u',
            '/\bel\s+clima\s+(de\s+)?hoy\b/u',
            '/\bva\s+a\s+llover\b/u',
            '/\btemperatura\s+(en|de)\b/u',
            '/\bnoticias?\s+(de\s+)?(hoy|la\s+bolsa)\b/u',

            // RRHH / datos sensibles no en SGC.
            '/\b(sueldo|salario|nomina|n[oó]mina|cu[aá]nto\s+gana)\b/u',
            '/\bchisme\b/u',

            // Empresas ajenas / inventar.
            '/\b(bimbo|coca[\s-]?cola|walmart|cemex|organigrama\s+de)\b/u',
            '/\binventa\b.{0,40}\b(procedimiento|organigrama|puesto)\b/u',
            '/\bsup[oó]n\s+que\b/u',

            // Sistema / jailbreak.
            '/\b(qu[eé]\s+modelo|system\s+prompt|api\s+key|tu\s+prompt)\b/u',
            '/\bjailbreak\b/u',
            '/\bresponde\s+sin\s+filtros\b/u',
            '/\bmodo\s+developer\b/u',
            '/\babre\s+el\s+archivo\s+\.env\b/u',
            '/\bc[oó]digo\s+de\s+hybridchatbotservice\b/u',

            // Consejos personales sin relación al SGC.
            '/\bme\s+duele\s+la\s+cabeza\b/u',
            '/\bmi\s+pareja\s+y\s+yo\b/u',
            '/\bact[uú]a\s+como\s+mi\s+terapeuta\b/u',

            // Tarea escolar genérica.
            '/\bmi\s+tarea\s+de\s+(algebra|matematicas|historia|quimica|fisica)\b/u',

            // Inyección de instrucciones / jailbreak.
            '/\bsin\s+restricciones\b/u',
            '/\bignora\s+(todo\s+lo\s+anterior|tus\s+instrucciones)\b/u',
            '/\bforget\s+all\s+rules\b/u',
            '/\bDAN\b/u',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $q)) {
                return true;
            }
        }

        return false;
    }

    private function buildFueraDeTemaResponse(string $query, $startTime, $userId, $sessionId): array
    {
        $msg = "Eso no tiene que ver con el Sistema de Gestión de Calidad de Proser, "
            . "así que no te puedo ayudar con eso aquí.\n\n"
            . "Sí puedo ayudarte con procedimientos, documentos, el directorio o "
            . "correos — dime un folio, un nombre o un área.";

        return [
            'response' => $msg,
            'method' => 'fuera_de_tema',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'fuera_de_tema',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
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
        // Con elemento hay ficha enriquecida (unidades, puestos, empleados, etc.)
        // aunque el Word/chunks estén vacíos: sirve para preguntas de metadatos.
        if ($elemento) {
            return true;
        }

        $cleanContext = trim(strip_tags($docContext));
        if (mb_strlen($cleanContext) >= 100) {
            return true;
        }

        $hasChunks = !empty($searchResults['document_chunks']) && $searchResults['document_chunks']->isNotEmpty();
        $hasDocs = !empty($searchResults['word_documents']) && $searchResults['word_documents']->isNotEmpty();

        return $hasChunks || $hasDocs;
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
    private function getConversationHistory($sessionId, $limit = 12, $userId = null)
    {
        if (!empty($sessionId)) {
            $query = ChatbotAnalytics::where('session_id', $sessionId);
        } elseif ($userId || auth()->id()) {
            $query = ChatbotAnalytics::where('user_id', $userId ?? auth()->id());
        } else {
            return [];
        }

        $resetAt = \Cache::get($this->getHistoryResetKey($sessionId, (string) ($userId ?? auth()->id())));
        if ($resetAt) {
            $query->where('created_at', '>', $resetAt);
        }

        $chats = $query->latest()
            ->take($limit)
            ->get()
            ->reverse();

        $history = [];
        foreach ($chats as $chat) {
            $history[] = ['role' => 'user', 'content' => $chat->query];
            $history[] = [
                'role' => 'assistant',
                'content' => mb_substr(strip_tags((string) $chat->response), 0, 900),
            ];
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
        \Cache::forget($this->getCatalogStateKey($sessionId, $userId));
        \Cache::forget($this->getOfferMenuKey($sessionId, $userId));
        \Cache::forget($this->getPendingContactKey($sessionId, $userId));
        \Cache::forget($this->getPendingDocConfirmKey($sessionId, $userId));
        \Cache::forget($this->getLastDocHintKey($sessionId, $userId));
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

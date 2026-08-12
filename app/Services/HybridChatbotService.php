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
    /** @var string|null procedimientos|empresa */
    private $activeConversationMode = null;

    // Umbrales de decisión semántica (coseno 0-1). Reemplazan las listas de palabras gatillo.
    // Sesgo fuerte a PERMANECER en el doc: una pregunta de seguimiento genérica ("y los
    // riesgos?", "el objetivo") tiene similitud moderada (~0.3-0.4) y casi siempre pierde
    // contra el doc del corpus que más habla de ese aspecto. Por eso sólo se cambia de tema
    // si el usuario NOMBRA otro documento, o si algo lo supera por un margen grande.
    // Calibrado con datos reales: seguimiento ~0.4, doc nombrado explícito domina vía pin.
    // Menos anclaje al PDF: el usuario cambia de tema seguido (empresa, área, “cómo me llamo”).
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
        return "Eres Bob, el asistente del SGC de Proser."
            . "\n- Responde en español, de tú, directo: sin saludos de relleno ni cierres tipo «gracias por tu consulta»."
            . "\n- Listas en viñetas; dudas cortas en 1–3 frases."
            . "\n- Usa solo la información dada. Si no está, responde EXACTAMENTE: [[SIN_INFO]]";
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
     * Un solo chat: documentos SGC y estructura de empresa se infieren por historial/pregunta.
     */
    public function processQuery($query, $userId = null, $sessionId = null)
    {
        $startTime = microtime(true);
        $cleanQuery = trim($query);
        // Búsqueda con query normalizada (typos/coloquial → términos SGC).
        // A la IA se manda la pregunta original para que responda natural.
        $searchQuery = $this->normalizeColloquialQuery($cleanQuery);

        // 1. SALUDOS (cortos / casuales): NO reiniciar el hilo ni soltar monólogo.
        if ($this->isGreetingOnly($cleanQuery)) {
            $hasHistory = !empty($this->getConversationHistory($sessionId, 2));

            return [
                'response' => $hasHistory
                    ? "¡Hola! Seguimos en la misma conversación. ¿En qué te ayudo?"
                    : "**Hola, soy Bob.** Pregúntame por documentos del SGC, puestos o la estructura de la empresa.",
                'method' => 'conversation_greeting',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'chips' => $hasHistory ? [] : [
                    ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                    ['label' => 'Quién ocupa un puesto', 'query' => 'quién ocupa el puesto de Coordinador de TI'],
                    ['label' => 'Unidades de la empresa', 'query' => 'cuáles son las unidades de la empresa'],
                ],
            ];
        }

        // 2. COMANDOS DE REINICIO (solo si el usuario lo pide explícito)
        if (preg_match('/^(olvida|borra|reinicia|limpia|reset)\b/i', $cleanQuery)) {
            $this->resetConversation($sessionId, $userId);
            return [
                'response' => "Listo, borré el contexto de esta conversación. Empezamos de cero.\n\n¿Sobre qué documento o tema quieres consultar ahora?",
                'method' => 'conversation_reset',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }

        // Meta: "no borres", "no me sé el nombre", "no te acuerdas", "mi duda es…"
        if ($this->isContextMetaComplaint($cleanQuery)) {
            return $this->buildContextMetaResponse($cleanQuery, $startTime, $userId, $sessionId);
        }

        // 3. RECUPERAR CONTEXTO (sin candado de sección: la conciencia del modelo sigue el hilo)
        $contextKey = $this->getContextKey($sessionId, $userId);
        $cachedContext = \Cache::get($contextKey);
        $modeKey = $this->getConversationModeKey($sessionId, $userId);
        $stickyMode = \Cache::get($modeKey);
        $this->activeConversationMode = in_array($stickyMode, ['empresa', 'procedimientos'], true)
            ? $stickyMode
            : null;

        // Duda práctica (factura, a quién enviar…): no amarrar a un PDF irrelevante.
        if (
            $this->isPracticalOperationalQuery($cleanQuery)
            && $cachedContext
            && !$this->docFocusLooksRelevantToPracticalQuery($cachedContext, $cleanQuery)
        ) {
            \Cache::forget($contextKey);
            $cachedContext = null;
        }

        // 2.5 Identidad del usuario logueado (no es pregunta de un PDF).
        if ($this->isPersonalIdentityQuery($cleanQuery)) {
            \Cache::forget($contextKey);
            return $this->generatePersonalIdentityResponse($cleanQuery, $startTime, $userId, $sessionId);
        }

        // 3.0 CATÁLOGO / LISTAS DESDE BD (puesto, relacionados, área, unidad…)
        // Usan el inventario y relaciones reales. No deben anclarse al PDF en foco
        // (salvo "relacionados" del documento actual, que sí usa el contexto).
        $catalogStateKey = $this->getCatalogStateKey($sessionId, $userId);
        $catalogState = \Cache::get($catalogStateKey);
        $offerMenuKey = $this->getOfferMenuKey($sessionId, $userId);
        $offerMenu = \Cache::get($offerMenuKey);
        $talkTopicKey = $this->getTalkTopicKey($sessionId);
        $talkTopic = \Cache::get($talkTopicKey);

        // Si el usuario ya va a otro tema, soltar el menú 1/2/3 (evita que un "sí" lo reabra).
        if (is_array($offerMenu) && $this->shouldDropOfferMenu($cleanQuery, $talkTopic)) {
            \Cache::forget($offerMenuKey);
            $offerMenu = null;
        }

        // "me perdí / te pregunté otra cosa / no eso" → soltar PDF y reenganchar el hilo.
        if ($this->isUserLostOrResetIntent($cleanQuery)) {
            \Cache::forget($contextKey);
            \Cache::forget($offerMenuKey);
            $cachedContext = null;

            // Rechazo puro del PDF ("ese no es"): reintentar con la intención original del hilo.
            $historyForRecover = $this->getConversationHistory($sessionId, 8);
            $originalIntent = $this->extractOriginalDocumentIntentFromHistory($historyForRecover);
            $isPureReject = (bool) preg_match(
                '/^(pero\s+)?(ese|esa|esto|eso|este|esta)\s+no\s+(es|era)\b/iu',
                trim($cleanQuery)
            );

            if ($isPureReject && $originalIntent !== null) {
                $cleanQuery = $originalIntent;
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                $query = $cleanQuery;
                \Cache::put($talkTopicKey, [
                    'open' => false,
                    'retry_after_reject' => true,
                    'updated_at' => now()->toDateTimeString(),
                ], 900);
                $talkTopic = \Cache::get($talkTopicKey);
                \Log::info('Chatbot rechazo de PDF → reintento con intención original', [
                    'original_intent' => $cleanQuery,
                ]);
            } else {
                return $this->buildConversationRecoveryResponse(
                    $cleanQuery,
                    $startTime,
                    $userId,
                    $sessionId,
                    $catalogState,
                    $talkTopic
                );
            }
        }

        // Seguimiento de un listado reciente: "el segundo", "un resumen", "las funciones".
        if (is_array($catalogState) && !empty($catalogState['items'])) {
            $ordinal = $this->matchCatalogOrdinalFollowUp($cleanQuery);
            if ($ordinal !== null && isset($catalogState['items'][$ordinal])) {
                $item = $catalogState['items'][$ordinal];
                $cleanQuery = trim(($item['folio'] ?? '') . ' ' . ($item['nombre'] ?? ''));
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                $query = $cleanQuery;
                \Cache::forget($contextKey);
                $cachedContext = null;
                \Log::info('Chatbot seguimiento ordinal de catálogo', [
                    'ordinal' => $ordinal + 1,
                    'query' => $cleanQuery,
                ]);
            } elseif ($this->isCatalogSummaryFollowUp($cleanQuery)) {
                return $this->buildCatalogSummaryFromState(
                    $cleanQuery,
                    $catalogState,
                    $startTime,
                    $userId,
                    $sessionId
                );
            }
        } elseif (
            $cachedContext
            && !empty($cachedContext['id'])
            && $this->isCatalogSummaryFollowUp($cleanQuery)
        ) {
            // "resumen / funciones" con PDF en foco → seguir ese documento, no RAG suelto.
            $titulo = trim((string) ($cachedContext['title'] ?? 'este procedimiento'));
            $cleanQuery = "Dame un resumen y las funciones generales del procedimiento {$titulo}";
            $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
            $query = $cleanQuery;
        } elseif (
            is_array($catalogState)
            && (!empty($catalogState['puesto_ids']) || !empty($catalogState['area_ids']))
            && $this->isCatalogSummaryFollowUp($cleanQuery)
        ) {
            // Listado previo sin items cacheados: reabrir catálogo y resumir.
            \Cache::forget($contextKey);
            $cachedContext = null;
            $label = $catalogState['label'] ?? 'la lista anterior';
            $cleanQuery = "procedimientos {$label}";
            $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
            $query = $cleanQuery;
        }

        // Menú pendiente ("¿tus procedimientos, directorio o documento?") + respuesta vaga ("sí quiero").
        // Si hay documento en foco o hilo de plática, NO reabrir el menú: seguir ese hilo.
        if (is_array($offerMenu) && !empty($offerMenu['options'])) {
            $hasTalkOpen = is_array($talkTopic) && !empty($talkTopic['open']);
            $hasDocFocus = $cachedContext && !empty($cachedContext['id']);
            $recentForMenu = $this->getConversationHistory($sessionId, 6);
            $bossThreadOpen = $this->historyMentionsBoss($recentForMenu);

            // "sí" con plática/jefe: soltar menú y NO aclarar 1/2/3.
            if (
                $this->isVagueAffirmation($cleanQuery)
                && ($hasTalkOpen || $bossThreadOpen || $hasDocFocus)
            ) {
                \Cache::forget($offerMenuKey);
                $offerMenu = null;
            } else {
                $picked = $this->resolveOfferMenuChoice($cleanQuery, $offerMenu);
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
                if (!$hasDocFocus && !$hasTalkOpen && !$bossThreadOpen
                    && ($picked === 'clarify' || $this->isVagueAffirmation($cleanQuery))
                ) {
                    \Cache::forget($contextKey);
                    return $this->buildOfferMenuClarifyResponse($cleanQuery, $startTime, $userId, $sessionId);
                }
            }
        }

        // "sí"/"ok": si hay documento o listado en foco, CONTINUAR ese hilo.
        // Solo menú genérico cuando no hay contexto (evita perder el tema anterior).
        $affirmationContinued = false;
        if ($this->isVagueAffirmation($cleanQuery)) {
            $recentHistory = $this->getConversationHistory($sessionId, 6);

            // Hilo de plática abierto (ej. "¿quieres que busque a tu jefe?"): no abrir menú 1/2/3.
            $pendingConfirm = null;
            if (is_array($talkTopic)) {
                $pendingConfirm = $this->extractPendingDocumentConfirm($recentHistory, $talkTopic);
            } else {
                $pendingConfirm = $this->extractPendingDocumentConfirm($recentHistory, []);
            }

            if ($pendingConfirm !== null && $pendingConfirm !== '') {
                // "sí" tras «¿Te refieres a X?» → buscar X directo (no coincidencias sueltas).
                $cleanQuery = $pendingConfirm;
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                $query = $cleanQuery;
                \Cache::forget($offerMenuKey);
                \Cache::put($talkTopicKey, [
                    'open' => false,
                    'confirmed_doc' => $cleanQuery,
                    'updated_at' => now()->toDateTimeString(),
                ], 900);
                $talkTopic = \Cache::get($talkTopicKey);
                \Cache::forget($contextKey);
                $cachedContext = null;
                \Log::info('Chatbot confirmó documento propuesto', ['doc' => $cleanQuery]);
            } elseif (is_array($talkTopic) && !empty($talkTopic['open'])) {
                $cleanQuery = 'sí, continúa con lo que veníamos hablando';
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                $query = $cleanQuery;
                \Cache::forget($offerMenuKey);
                // Forzar carril talk más abajo.
            } elseif ($this->historyMentionsBoss($recentHistory)) {
                // Tras "quién es mi jefe" / aclaración de puesto: "sí" = buscar jefe, no menú 1/2/3.
                $cleanQuery = 'quién es mi jefe';
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                $query = $cleanQuery;
                \Cache::forget($offerMenuKey);
                \Cache::forget($contextKey);
                $cachedContext = null;
            } elseif ($cachedContext && !empty($cachedContext['id'])) {
                $expanded = $this->expandAffirmationToDocFollowUp($cachedContext);
                $searchQuery = $expanded;
                $query = $expanded;
                $affirmationContinued = true;
            } elseif (is_array($catalogState)
                && (!empty($catalogState['area_ids']) || !empty($catalogState['puesto_ids']) || !empty($catalogState['items']))
            ) {
                $label = $catalogState['label'] ?? 'esa lista';
                $chips = [];
                foreach (array_slice($catalogState['items'] ?? [], 0, 3) as $it) {
                    $chips[] = [
                        'label' => $it['folio'] ?? ($it['nombre'] ?? 'documento'),
                        'query' => trim(($it['folio'] ?? '') . ' ' . ($it['nombre'] ?? '')),
                    ];
                }

                return [
                    'response' => "Claro, seguimos con **{$label}**.\n\n"
                        . "Dime el **folio** o el **nombre**, o elige uno de la lista.",
                    'method' => 'catalog_affirmation_clarify',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => [],
                    'search_details' => [],
                    'cached' => false,
                    'document' => null,
                    'chips' => $chips,
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

        // Seguimiento de listado: puesto/área previa, o "su lista de procedimientos".
        $asksPropiosOnly = (bool) preg_match(
            '/\b(propios?|solo (como )?responsable|como responsable)\b/u',
            mb_strtolower($cleanQuery)
        );
        if (
            $this->isCatalogListFollowUp($cleanQuery)
            || $this->isProceduresAssignedFollowUp($cleanQuery)
            || $this->isAreaCatalogFollowUp($cleanQuery)
            || $this->isTheirProceduresListFollowUp($cleanQuery)
            || ($asksPropiosOnly && is_array($catalogState) && !empty($catalogState['puesto_ids']))
        ) {
            \Cache::forget($contextKey);

            $followState = null;
            if (is_array($catalogState)) {
                if (!empty($catalogState['area_ids']) || !empty($catalogState['puesto_ids'])) {
                    $followState = $catalogState;
                }
            }

            // Seguimiento de "qué procedimientos tiene/tienen asignados" o "su lista":
            // recuperar el puesto del hilo (caché, historial o doc en foco).
            if (
                !$followState
                && (
                    $this->isTheirProceduresListFollowUp($cleanQuery)
                    || $this->isProceduresAssignedFollowUp($cleanQuery)
                    || $asksPropiosOnly
                )
            ) {
                $followState = $this->resolvePuestoStateFromRecentContext(
                    $sessionId,
                    $cachedContext,
                    $catalogState
                );
            }

            // Sin estado previo: caer a "mis procedimientos" (puesto del usuario).
            // Solo para "toda la lista" / completar listado propio — NUNCA para
            // "qué procedimientos tiene asignados" del puesto del que hablábamos.
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

            // Pidió lista del puesto del hilo sin contexto: no tirar el catálogo global.
            if (
                $this->isTheirProceduresListFollowUp($cleanQuery)
                || $this->isProceduresAssignedFollowUp($cleanQuery)
                || $asksPropiosOnly
            ) {
                return [
                    'response' => "Para darte **su lista de procedimientos** necesito saber de **quién** hablamos.\n\n"
                        . "Dime el **nombre del puesto** (ej. Director de Desarrollo de Negocios) "
                        . "o el **área** (ej. procedimientos de Jurídico).",
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

        // 3.05 DIRECTORIO / EMPRESA (sin candado de botón).
        // Con documento en foco, "puestos"/"responsable" es seguimiento del PDF → no organigrama.
        $isDirectoryAsk = $this->isPeopleOrOrgDirectoryQuery($cleanQuery)
            || $this->isPeopleOrOrgDirectoryQuery($searchQuery)
            || $this->isCompanyOrgQuery($cleanQuery)
            || $this->isCompanyOrgQuery($searchQuery);
        $hasDocFocus = $cachedContext && !empty($cachedContext['id']);
        $shortDocFollowUp = $hasDocFocus && $this->isShortDocumentFollowUp($cleanQuery);

        if ($shortDocFollowUp) {
            \Cache::put($modeKey, 'procedimientos', 900);
            $this->activeConversationMode = 'procedimientos';
        } elseif ($isDirectoryAsk) {
            \Cache::forget($contextKey);
            $cachedContext = null;
            \Cache::put($modeKey, 'empresa', 900);
            $this->activeConversationMode = 'empresa';

            $directoryResponse = $this->generatePeopleOrOrgResponse(
                $cleanQuery,
                $searchQuery,
                $startTime,
                $userId,
                $sessionId
            );

            // Persistir puesto del hilo para "qué procedimientos tiene asignados".
            if (!empty($directoryResponse['catalog_state'])) {
                \Cache::put($catalogStateKey, $directoryResponse['catalog_state'], 600);
            } else {
                $this->rememberPuestoCatalogStateFromTurn(
                    $cleanQuery,
                    (string) ($directoryResponse['response'] ?? ''),
                    $catalogStateKey,
                    $catalogState
                );
            }

            return $directoryResponse;
        } elseif ($this->looksLikeSgcDocumentAsk($cleanQuery, $searchQuery) || $hasDocFocus) {
            \Cache::put($modeKey, 'procedimientos', 900);
            $this->activeConversationMode = 'procedimientos';
        }

        if ($this->isCatalogBrowseQuery($cleanQuery) || $this->isCatalogBrowseQuery($searchQuery)) {
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

        // ═══ DOS CASOS AISLADOS ═══
        // A) TALK: la IA decide/aclara solo con el hilo (sin RAG ni PDF al azar).
        // B) SEARCH: intención clara → buscar en archivos por el prompt.
        $lane = $this->resolveQueryLane($cleanQuery, $searchQuery, $cachedContext, $sessionId);
        if ($lane === 'talk') {
            $talk = $this->runTalkLaneDecision(
                $cleanQuery,
                $startTime,
                $userId,
                $sessionId,
                $cachedContext
            );

            // La IA aisló el tema y ya puede buscar: pasar a archivos con prompt refinado.
            if (!empty($talk['search_query'])) {
                $refined = trim((string) $talk['search_query']);

                // Tema fuera de PROSER / no existe en BD → NO buscar ni pegar un PDF ajeno.
                if ($this->isOutOfScopeTopicQuery($refined) || $this->isOutOfScopeTopicQuery($cleanQuery)) {
                    return $this->buildTopicNotInDatabaseResponse(
                        $cleanQuery,
                        $refined,
                        $startTime,
                        $userId,
                        $sessionId
                    );
                }

                $cleanQuery = $refined;
                $searchQuery = $this->normalizeColloquialQuery($cleanQuery);
                $query = $cleanQuery;
                \Cache::forget($contextKey);
                $cachedContext = null;
                \Log::info('Chatbot TALK→SEARCH', [
                    'original' => $query,
                    'search_query' => $searchQuery,
                ]);

                // Si el SEARCH refinado es directorio/empresa, no mandarlo a RAG de PDFs.
                if (
                    $this->isPeopleOrOrgDirectoryQuery($cleanQuery)
                    || $this->isPeopleOrOrgDirectoryQuery($searchQuery)
                    || $this->isCompanyOrgQuery($cleanQuery)
                    || $this->isCompanyOrgQuery($searchQuery)
                ) {
                    \Cache::put($modeKey, 'empresa', 900);
                    $this->activeConversationMode = 'empresa';
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

                // Listados de catálogo también deben ir por BD, no por RAG.
                if ($this->isCatalogBrowseQuery($cleanQuery) || $this->isMyProceduresQuery($cleanQuery)) {
                    $catalogResponse = $this->generateCatalogBrowseResponse(
                        $cleanQuery,
                        $searchQuery,
                        $startTime,
                        $userId,
                        $sessionId,
                        null,
                        null
                    );
                    if (!empty($catalogResponse['catalog_state'])) {
                        \Cache::put($catalogStateKey, $catalogResponse['catalog_state'], 600);
                    }

                    return $catalogResponse;
                }
            } elseif (!empty($talk['not_found'])) {
                return $talk['response'];
            } else {
                return $talk['response'];
            }
        }

        // 3.1 DECISIÓN SEMÁNTICA DE CONTEXTO (carril SEARCH / archivos)
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

        if ($cachedContext && !empty($cachedContext['id']) && !$affirmationContinued) {
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

        if ($cachedContext && !empty($cachedContext['id']) && !$affirmationContinued) {
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
            // Pregunta de persona: directorio, sin pedir folio.
            if ($this->wantsEmployeeByNameQuery($cleanQuery)) {
                return $this->generatePeopleOrOrgResponse(
                    $cleanQuery,
                    $searchQuery,
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            // Tema que no está en PROSER / SGC: decirlo claro, sin PDF al azar.
            if ($this->isOutOfScopeTopicQuery($cleanQuery) || $this->isOutOfScopeTopicQuery($searchQuery)) {
                \Cache::forget($contextKey);

                return $this->buildTopicNotInDatabaseResponse(
                    $cleanQuery,
                    $searchQuery,
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            // NUNCA resetConversation aquí: el usuario random sigue el hilo.
            // Solo soltamos el PDF en foco si ya no sirve.
            \Cache::forget($contextKey);

            $msg = $this->buildKeepThreadClarifyMessage($cleanQuery, $focoTitulo);

            return [
                'response' => $msg,
                'method' => 'context_clarify_keep_thread',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [],
                'cached' => false,
                'document' => null,
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
            // No reescribir "qué áreas existen" ni "qué puestos": son preguntas de directorio.

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
            'campamentos' => 'campamentos',
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

        // Preguntas de contenido interno del documento actual: no son catálogo global.
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

        $pideLista = (bool) preg_match('/\b(lista|listado|listar|enumera|enumerar|inventario|todos los|todas las|cu[aá]les (son|hay|tengo)|cu[aá]ntos|mu[eé]strame|p[aá]same (la|el) (lista|listado)|quiero una lista|necesito una lista|dame una lista)\b/u', $q);
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
        return $this->findAreasMentionedInQuery($query)->isNotEmpty();
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
                . "Puedo ayudarte con tus procedimientos, el directorio (quién ocupa un puesto) "
                . "o el detalle de un documento. Dime qué necesitas, con tus palabras.";
        } else {
            $msg = "No pude leer tu nombre de la sesión. ¿Estás logueado?\n\n"
                . "Mientras tanto puedo ayudarte con procedimientos, listados por área o el directorio.";
        }

        // No amarrar menú 1/2/3: un "sí" posterior no debe reabrir opciones genéricas.

        return [
            'response' => $msg,
            'method' => 'directory_personal_identity',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'document' => null,
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

    private function getOfferMenuKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_offer_menu_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    /**
     * Soltar menú 1/2/3 cuando el usuario ya cambió de tema o sigue un hilo concreto.
     */
    private function shouldDropOfferMenu(string $query, $talkTopic = null): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if (is_array($talkTopic) && !empty($talkTopic['open'])) {
            return true;
        }

        if ($this->isVagueAffirmation($q)) {
            // "sí" solo: no soltar aún (puede ser respuesta al menú).
            // Sí soltar si trae más contexto.
            if (preg_match('/\b(jefe|jefa|factura|procedimiento|directorio|área|area|puesto)\b/u', $q)) {
                return true;
            }
        }

        return (bool) preg_match(
            '/\b(jefe|jefa|factura|procedimiento|directorio|unidad|unidades|'
            . 'qui[eé]n es|quien es|me perd[ií]|te pregunt[eé]|otra cosa|'
            . 'no eso|no era|cambiemos|mejor|resumen|segundo|tercero|'
            . 'funciones|tareas|analista|coordinador)\b/u',
            $q
        )
            || $this->isPeopleOrOrgDirectoryQuery($q)
            || $this->isPracticalOperationalQuery($q)
            || $this->isCatalogListFollowUp($q)
            || $this->isUserLostOrResetIntent($q);
    }

    /**
     * Usuario indica que se perdió el hilo o quiere otro tema.
     */
    private function isUserLostOrResetIntent(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        return (bool) preg_match(
            '/\b(me perd[ií]|estoy perdido|te perdiste|te confundiste|'
            . 'no (era|es) (eso|ese|esa|este|esta)|no eso|'
            . 'ese no es|esa no es|este no es|esta no es|pero ese no es|pero esa no es|'
            . 'te pregunt[eé] otra|otra cosa|cambiemos de tema|'
            . 'olv[ií]dalo|dejalo|d[eé]jalo|no me des (eso|ese)|'
            . 'no quiero (eso|ese|ese documento)|vuelve al tema|'
            . 'regresa|regresemos)\b/u',
            $q
        );
    }

    /**
     * "el segundo", "ahora del 2", "el tercero de la lista".
     * @return int|null índice 0-based
     */
    private function matchCatalogOrdinalFollowUp(string $query): ?int
    {
        $q = $this->foldAccents(mb_strtolower(trim($query)));
        if ($q === '' || mb_strlen($q) > 80) {
            return null;
        }

        $map = [
            'primer' => 0, 'primero' => 0, '1' => 0, '1o' => 0,
            'segund' => 1, 'segundo' => 1, '2' => 1, '2o' => 1,
            'tercer' => 2, 'tercero' => 2, '3' => 2, '3o' => 2,
            'cuart' => 3, 'cuarto' => 3, '4' => 3, '4o' => 3,
            'quint' => 4, 'quinto' => 4, '5' => 4, '5o' => 4,
        ];

        if (preg_match(
            '/\b(?:el|la|del|de\s+la|ahora\s+del?|ahora\s+el|quiero\s+el|dame\s+el|abre\s+el)?\s*'
            . '(primer(?:o|a)?|segund[oa]|tercer(?:o|a)?|cuart[oa]|quint[oa]|[1-5](?:o|er|do|ro)?)\b/u',
            $q,
            $m
        )) {
            $token = $this->foldAccents($m[1]);
            foreach ($map as $key => $idx) {
                if (str_starts_with($token, $key) || $token === $key) {
                    return $idx;
                }
            }
        }

        if (preg_match('/^(?:el|la|del)?\s*([1-5])\s*\.?$/u', $q, $m)) {
            return ((int) $m[1]) - 1;
        }

        return null;
    }

    private function isCatalogSummaryFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || mb_strlen($q) > 100) {
            return false;
        }

        return (bool) preg_match(
            '/\b(un resumen|resumen(es)?|resumen de cada|res[uú]meme|'
            . 'las funciones( generales)?|funciones generales|'
            . 'y las tareas|las tareas|de qu[eé] tratan|de que tratan|'
            . 'en qu[eé] consisten|breve(mente)?)\b/u',
            $q
        );
    }

    private function buildCatalogSummaryFromState(
        string $query,
        array $catalogState,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $items = $catalogState['items'] ?? [];
        $label = $catalogState['label'] ?? 'la lista anterior';
        if (!is_array($items) || $items === []) {
            $msg = "Mantengo el hilo de **{$label}**, pero no tengo el detalle a mano.\n\n"
                . "Pide de nuevo el listado o dime un folio/nombre concreto.";

            return [
                'response' => $msg,
                'method' => 'catalog_summary_empty',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'document' => null,
                'analytics_id' => $this->logAnalytics(
                    $query,
                    $msg,
                    'catalog_summary_empty',
                    $startTime,
                    $userId,
                    $sessionId
                ),
            ];
        }

        $lines = [];
        foreach (array_slice($items, 0, 12) as $i => $it) {
            $n = $i + 1;
            $folio = trim((string) ($it['folio'] ?? ''));
            $nombre = trim((string) ($it['nombre'] ?? 'Sin nombre'));
            $lines[] = $folio !== ''
                ? "{$n}. **{$folio}** — {$nombre}"
                : "{$n}. {$nombre}";
        }

        $msg = "Resumen de **{$label}** (según el listado que vimos):\n\n"
            . implode("\n", $lines)
            . "\n\nSi quieres el detalle de alguno, dime el número («el segundo»), el folio o el nombre.";

        $chips = [];
        foreach (array_slice($items, 0, 3) as $it) {
            $chips[] = [
                'label' => $it['folio'] ?? ($it['nombre'] ?? 'documento'),
                'query' => trim(($it['folio'] ?? '') . ' ' . ($it['nombre'] ?? '')),
            ];
        }

        return [
            'response' => $msg,
            'method' => 'catalog_summary_followup',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['from_catalog_state' => true],
            'cached' => false,
            'document' => null,
            'chips' => $chips,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'catalog_summary_followup',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * Reenganche cuando el usuario dice que se perdió o cambió de tema.
     */
    private function buildConversationRecoveryResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        $catalogState,
        $talkTopic
    ): array {
        $chips = [
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Directorio', 'query' => 'quién ocupa un puesto'],
            ['label' => 'Unidades', 'query' => 'dime las unidades'],
        ];

        $hint = '';
        if (is_array($catalogState) && !empty($catalogState['label'])) {
            $hint = "Antes estábamos con **{$catalogState['label']}**. ";
            if (!empty($catalogState['items'][0])) {
                $it = $catalogState['items'][0];
                $chips[0] = [
                    'label' => 'Seguir con la lista',
                    'query' => 'un resumen de la lista',
                ];
            }
        } elseif (is_array($talkTopic) && !empty($talkTopic['label'])) {
            $hint = "Antes hablábamos de «{$talkTopic['label']}». ";
        }

        // Si el mensaje trae la nueva intención (después de "no eso, …"), intentar usarla.
        $stripped = preg_replace(
            '/\b(me perd[ií]|no (era|es) eso|no eso|te pregunt[eé] otra( cosa)?|'
            . 'cambiemos de tema|olv[ií]dalo|mejor)\b[,:]?\s*/iu',
            '',
            $query
        );
        $stripped = trim((string) $stripped);

        $msg = "Ok, suelto eso y retomo el hilo contigo. {$hint}\n\n"
            . "¿Qué necesitas ahora? Puedes pedirme un procedimiento, el directorio o un listado.";

        if ($stripped !== '' && mb_strlen($stripped) >= 8 && $stripped !== trim($query)) {
            $msg = "Ok, dejo el tema anterior. Entendí que quieres: **{$stripped}**.\n\n"
                . "Si es correcto, confírmalo o reformúlalo en una frase; si no, dime qué buscas.";
            $chips = [
                ['label' => 'Sí, busca eso', 'query' => $stripped],
                ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
                ['label' => 'Directorio', 'query' => 'quién es mi jefe'],
            ];
        }

        // Cerrar talk sticky para no re-encasillar.
        \Cache::put($this->getTalkTopicKey($sessionId), [
            'open' => false,
            'recovered' => true,
            'updated_at' => now()->toDateTimeString(),
        ], 900);

        return [
            'response' => trim($msg),
            'method' => 'conversation_recovery',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['recovered' => true],
            'cached' => false,
            'document' => null,
            'chips' => $chips,
            'analytics_id' => $this->logAnalytics(
                $query,
                trim($msg),
                'conversation_recovery',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
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
     * Convierte un "sí"/"ok" en una pregunta útil sobre el documento en foco.
     */
    private function expandAffirmationToDocFollowUp(array $cachedContext): string
    {
        $titulo = trim((string) ($cachedContext['title'] ?? 'este procedimiento'));
        if ($titulo === '') {
            $titulo = 'este procedimiento';
        }

        return "Detalle los pasos del procedimiento {$titulo} y las responsabilidades "
            . "de cada puesto involucrado. Si aplica, indica con quién dirigirme.";
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
        $msg = "Claro. Dime cuál de estas opciones:\n\n"
            . "1. **Mis procedimientos** (según tu puesto)\n"
            . "2. **Directorio** (quién ocupa un puesto / unidades)\n"
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
            $msg = "Perfecto. En el **directorio** puedo ayudarte con:\n\n"
                . "- Quién ocupa un **puesto** (ej. Coordinador de TI)\n"
                . "- **Unidades** de la empresa\n"
                . "- **Directores** registrados\n\n"
                . "¿Qué quieres consultar?";

            return [
                'response' => $msg,
                'method' => 'directory_offer_prompt',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => [],
                'cached' => false,
                'document' => null,
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
            . 'tengo relaci[oó]n|relacionados? conmigo|donde participo)\b/u',
            $q
        );
    }

    /**
     * Tras elegir un puesto en directorio: "qué procedimientos tiene/tienen asignados".
     */
    private function isProceduresAssignedFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b('
            . 'qu[eé]?\s+procedimientos?'
            . '|procedimientos?\s+(tiene|tienen|tiene\s+asignados|tienen\s+asignados|asignados)'
            . '|(tiene|tienen)\s+asignados'
            . '|asignados'
            . '|propios\s+del\s+puesto'
            . ')\b/u',
            $q
        ) && !preg_match('/\b(folio|[a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
            && !preg_match('/\b(mis procedimientos|mi puesto|m[ií]os)\b/u', $q);
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
     * Recupera el puesto del hilo: catalog_state, historial (más reciente) o doc en foco.
     */
    private function resolvePuestoStateFromRecentContext(
        ?string $sessionId,
        ?array $cachedContext,
        ?array $catalogState
    ): ?array {
        if (is_array($catalogState) && !empty($catalogState['puesto_ids'])) {
            return $catalogState;
        }

        // Del mensaje más reciente al más viejo: el último puesto citado es el del hilo.
        $history = array_reverse($this->getConversationHistory($sessionId, 8));
        $catalog = $this->getPuestosCatalog();

        foreach ($history as $msg) {
            $text = trim(strip_tags((string) ($msg['content'] ?? '')));
            if ($text === '') {
                continue;
            }

            $puestos = $this->resolveExactPuestoFromQuery($text);
            if ($puestos->isEmpty()) {
                $hay = $this->foldAccents($text);
                $puestos = $catalog->filter(function ($p) use ($hay) {
                    $name = $this->foldAccents((string) $p->nombre);

                    return mb_strlen($name) >= 12 && str_contains($hay, $name);
                })->values();
            }

            if ($puestos->isEmpty()) {
                continue;
            }

            // Si hay varios en el mismo mensaje, el más largo (más específico).
            $best = $puestos->sortByDesc(fn ($p) => mb_strlen((string) $p->nombre))->first();

            return [
                'mode' => 'by_puesto',
                'puesto_ids' => [(int) $best->id_puesto_trabajo],
                'puesto_nombres' => [$best->nombre],
                'label' => 'puesto(s): ' . $best->nombre,
            ];
        }

        if ($cachedContext && !empty($cachedContext['id'])) {
            $el = Elemento::with('puestoResponsable:id_puesto_trabajo,nombre')->find($cachedContext['id']);
            if ($el && $el->puestoResponsable) {
                $best = $el->puestoResponsable;

                return [
                    'mode' => 'by_puesto',
                    'puesto_ids' => [(int) $best->id_puesto_trabajo],
                    'puesto_nombres' => [$best->nombre],
                    'label' => 'puesto(s): ' . $best->nombre,
                ];
            }
        }

        return null;
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
     * "auxiliar de tecnologias", "coordinador de TI" → puestos que cumplen ROL + ÁREA (AND).
     */
    private function matchPuestosByRoleAndArea(string $foldedQuery, ?Collection $puestos = null): Collection
    {
        $q = $this->foldAccents($foldedQuery);
        $puestos = $puestos ?? $this->getPuestosCatalog();

        $roles = ['auxiliar', 'coordinador', 'coordinadora', 'gerente', 'director', 'directora',
            'analista', 'jefe', 'jefa', 'residente'];
        $matchedRoles = [];
        foreach ($roles as $role) {
            if (preg_match('/\b' . preg_quote($role, '/') . '\b/u', $q)) {
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
            $nombresArea = !empty($forcedCatalogState['area_nombres'])
                ? implode(', ', $forcedCatalogState['area_nombres'])
                : 'área';
            $elementos = $this->searchElementosOfArea($areaIds, $topicTerms, 120, $tipos);
            $lista = $elementos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");

            return [
                'mode' => 'by_area',
                'label' => 'del área ' . $nombresArea,
                'elementos' => $elementos,
                'lista_texto' => $lista,
                'document' => null,
                'final_context' => null,
                'tipos' => $tipos,
                'catalog_state' => [
                    'mode' => 'by_area',
                    'area_ids' => $areaIds,
                    'area_nombres' => $forcedCatalogState['area_nombres'] ?? [],
                    'topic_terms' => $topicTerms,
                    'label' => 'del área ' . $nombresArea,
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

        // 0) Mis procedimientos / donde tengo relación → puesto del usuario logueado
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

            if ($areas->isNotEmpty()) {
                // Priorizar coincidencia literal ("juridico" → Jurídico), no todas las
                // "Dirección Jurídica…" salvo que no haya otra.
                $qFold = $this->foldAccents($combined);
                $prioritarias = $areas->filter(function ($a) use ($qFold) {
                    $name = $this->foldAccents((string) $a->nombre);
                    return $name !== '' && str_contains($qFold, $name);
                });
                if ($prioritarias->isEmpty()) {
                    $prioritarias = $areas->take(2);
                }

                // DEL área = responsable del área + nombre/folio del tema.
                // NO "cualquier procedimiento donde aparezca un puesto de Compras como relacionado".
                $topicTerms = $this->extractCatalogTopicTerms($combined);
                $skipTopic = ['informacion', 'información', 'direccion', 'dirección', 'gestion',
                    'gestión', 'general', 'empresa', 'negocio', 'unidad', 'unidades'];
                foreach ($prioritarias as $area) {
                    $nombreArea = $this->foldAccents((string) $area->nombre);
                    foreach (preg_split('/\s+/u', $nombreArea) ?: [] as $w) {
                        $w = trim($w);
                        if (mb_strlen($w) >= 4 && !in_array($w, $skipTopic, true)) {
                            $topicTerms[] = $w;
                        }
                    }
                }
                // Alias útiles por área.
                if (preg_match('/\bcompras?\b/u', $qFold)) {
                    array_push($topicTerms, 'compra', 'proveedor', 'proveedores');
                }
                if (preg_match('/\b(ti|tecnolog)\b/u', $qFold)) {
                    $topicTerms[] = 'tecnolog';
                }
                $topicTerms = array_values(array_unique(array_filter(
                    $topicTerms,
                    fn ($t) => mb_strlen(trim((string) $t)) >= 4 && !in_array(mb_strtolower((string) $t), $skipTopic, true)
                )));

                $elementos = $this->searchElementosOfArea(
                    $prioritarias->pluck('id_area')->all(),
                    $topicTerms,
                    120,
                    $tipos
                );

                $nombresArea = $prioritarias->pluck('nombre')->unique()->implode(', ');
                $lista = $elementos->map(fn ($el) => $this->formatElementoCatalogLine($el))->implode("\n");

                return [
                    'mode' => 'by_area',
                    'label' => 'del área ' . $nombresArea,
                    'elementos' => $elementos,
                    'lista_texto' => $lista,
                    'document' => null,
                    'final_context' => null,
                    'tipos' => $tipos,
                    'catalog_state' => [
                        'mode' => 'by_area',
                        'area_ids' => $prioritarias->pluck('id_area')->map(fn ($id) => (int) $id)->values()->all(),
                        'area_nombres' => $prioritarias->pluck('nombre')->unique()->values()->all(),
                        'topic_terms' => $topicTerms,
                        'label' => 'del área ' . $nombresArea,
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

        $catalogStateOut = $data['catalog_state'] ?? null;
        // Guardar ítems numerados para seguimientos: "el segundo", "un resumen", chips.
        if (is_array($catalogStateOut) && $elementos instanceof Collection && $elementos->isNotEmpty()) {
            $catalogStateOut['items'] = $elementos->take(25)->values()->map(function ($el) {
                return [
                    'id' => (int) $el->getKey(),
                    'folio' => (string) ($el->folio_elemento ?? ''),
                    'nombre' => (string) ($el->nombre_elemento ?? ''),
                ];
            })->all();
        }

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
            'catalog_state' => $catalogStateOut,
            'chips' => collect($catalogStateOut['items'] ?? [])->take(3)->map(function ($it) {
                $label = trim((string) (($it['folio'] ?? '') !== '' ? $it['folio'] : ($it['nombre'] ?? 'documento')));

                return [
                    'label' => mb_strlen($label) > 42 ? mb_substr($label, 0, 40) . '…' : $label,
                    'query' => trim(($it['folio'] ?? '') . ' ' . ($it['nombre'] ?? '')),
                ];
            })->values()->all(),
        ];

        if (!empty($data['final_context']) && ($mode === 'related')) {
            $result['final_context'] = $data['final_context'];
            $result['document'] = $data['document'] ?? null;
        }

        return $result;
    }

    /**
     * Preguntas de la empresa / organigrama (no del procedimiento en foco).
     * Ej: "unidades de la empresa", "directores", "qué es el corporativo".
     */
    private function isCompanyOrgQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        // Si habla de contenido de un documento concreto, no es org general.
        if (
            preg_match('/\b(folio|[a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
            || preg_match('/\b(objetivo|alcance|riesgos?|definiciones?|pasos|actividades)\b/u', $q)
        ) {
            return false;
        }

        // Unidades / divisiones de la empresa (catálogo global).
        if (
            preg_match('/\b(unidades?|divisiones?)\b/u', $q)
            && preg_match('/\b(empresa|negocio|proser|organizaci[oó]n|hay|cu[aá]les|todas|lista|listado|dime|decir)\b/u', $q)
            && !preg_match('/\b(de este|de ese|aplican)\b/u', $q)
        ) {
            return true;
        }

        // "qué es el corporativo / qué es la unidad X" → catálogo, no RAG por etiqueta.
        if ($this->isBusinessUnitDefinitionQuery($q)) {
            return true;
        }

        // Lista de directores / quiénes son los directores (organigrama).
        if ($this->wantsCompanyDirectorsList($q)) {
            return true;
        }

        // "qué áreas existen" → catálogo Area, no procedimientos.
        if ($this->wantsCompanyAreasList($q)) {
            return true;
        }

        // "personas de Jurídico / del área de TI" → directorio, no PDF.
        if ($this->wantsPeopleInAreaQuery($q)) {
            return true;
        }

        // "quién es Alberto Bas" → empleado del directorio, no buscar en el procedimiento.
        if ($this->wantsEmployeeByNameQuery($q)) {
            return true;
        }

        return false;
    }

    /**
     * "quién es alberto bas?", "cómo se llama pedro castro" (nombre de persona, no puesto).
     */
    private function wantsEmployeeByNameQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if (!preg_match('/\b(qui[eé]n\s+es|quien\s+es|c[oó]mo\s+se\s+llam\w*)\b/u', $q)) {
            return false;
        }

        // Puesto / responsable de documento → otras rutas.
        if (preg_match(
            '/\b(coordinador|gerente|director|auxiliar|analista|jefe|jefa|'
            . 'responsable|encargad|puesto|procedimiento|documento|folio|'
            . 'objetivo|alcance|riesgos?|definiciones?)\b/u',
            $q
        )) {
            return false;
        }

        $name = $this->extractPersonNameFromWhoIsQuery($q);

        return $name !== null && mb_strlen($name) >= 3;
    }

    /**
     * Extrae el nombre tras "quién es …" / "cómo se llama …".
     */
    private function extractPersonNameFromWhoIsQuery(string $query): ?string
    {
        $q = mb_strtolower(trim($query));
        $q = trim($q, " \t\n\r\0\x0B?¿!.");

        if (!preg_match(
            '/\b(?:qui[eé]n\s+es|quien\s+es|c[oó]mo\s+se\s+llam\w*)\s+(.+)$/u',
            $q,
            $m
        )) {
            return null;
        }

        $name = trim($m[1]);
        $name = preg_replace('/^(el|la|los|las|un|una)\s+/u', '', $name) ?? $name;
        $name = trim($name, " \t?¿!.");

        if ($name === '' || mb_strlen($name) < 3) {
            return null;
        }

        // Debe parecer nombre propio (letras), no una frase de documento.
        if (!preg_match('/^[\p{L}\s\.\'\-]+$/u', $name)) {
            return null;
        }

        return $name;
    }

    /**
     * Busca empleados por fragmentos de nombre/apellido (tolera typos: bas≈Baas).
     */
    private function findEmpleadosByNameQuery(string $query): \Illuminate\Support\Collection
    {
        $name = $this->extractPersonNameFromWhoIsQuery($query);
        if ($name === null) {
            return collect();
        }

        $tokens = array_values(array_filter(
            preg_split('/\s+/u', $this->foldAccents($name)) ?: [],
            function ($t) {
                $t = trim($t);

                return mb_strlen($t) >= 2
                    && !in_array($t, ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'e'], true);
            }
        ));

        if ($tokens === []) {
            return collect();
        }

        // Candidatos amplios (cualquier token); el filtro fino es en PHP.
        $candidatos = Empleados::query()
            ->with('puestoTrabajo:id_puesto_trabajo,nombre')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $like = '%' . $token . '%';
                    $q->orWhere(function ($w) use ($like) {
                        $w->whereRaw('LOWER(nombres) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(apellido_paterno) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(apellido_materno) LIKE ?', [$like]);
                    });
                }
            })
            ->orderBy('apellido_paterno')
            ->limit(80)
            ->get(['id_empleado', 'nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id', 'correo']);

        return $candidatos
            ->filter(function ($emp) use ($tokens) {
                $full = trim(implode(' ', array_filter([
                    $emp->nombres,
                    $emp->apellido_paterno,
                    $emp->apellido_materno,
                ])));

                return $this->employeeNameMatchesTokens($full, $tokens);
            })
            ->take(12)
            ->values();
    }

    /**
     * Todos los tokens deben aparecer en el nombre (contención o 1 typo por palabra).
     */
    private function employeeNameMatchesTokens(string $fullName, array $tokens): bool
    {
        $folded = $this->foldAccents($fullName);
        $parts = array_values(array_filter(preg_split('/\s+/u', $folded) ?: []));

        foreach ($tokens as $token) {
            $token = $this->foldAccents($token);
            if ($token === '') {
                continue;
            }

            if (str_contains($folded, $token)) {
                continue;
            }

            $ok = false;
            foreach ($parts as $part) {
                if ($part === $token || str_starts_with($part, $token) || str_starts_with($token, $part)) {
                    $ok = true;
                    break;
                }
                if (mb_strlen($token) >= 3 && mb_strlen($part) >= 3 && levenshtein($token, $part) <= 1) {
                    $ok = true;
                    break;
                }
            }

            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * "qué áreas existen", "dime las áreas", "quiero que me digas las áreas".
     * No confundir con "personas del área de TI".
     */
    private function wantsCompanyAreasList(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || !preg_match('/\b([aá]reas)\b/u', $q)) {
            // Plural "áreas" = catálogo. Singular "área de X" suele ser otra ruta.
            if (!preg_match('/^(qu[eé]\s+)?([aá]rea)\s*\??$/u', $q)) {
                return false;
            }
        }

        // Personas/empleados de un área → otra ruta.
        if ($this->wantsPeopleInAreaQuery($q)) {
            return false;
        }

        // Áreas de un documento/procedimiento → no es catálogo de empresa.
        if (preg_match('/\b(procedimiento|documento|folio|involucr|de este|de ese|aplican)\b/u', $q)) {
            return false;
        }

        // Listado explícito / catálogo de estructura.
        if (preg_match(
            '/\b(existen?|cu[aá]les(\s+son)?|lista(\s+de)?|listado(\s+de)?|'
            . 'todas(\s+las)?|hay)\b/u',
            $q
        )) {
            return true;
        }

        // Lenguaje natural: "dime/digas las áreas", "quiero las áreas", "áreas de la empresa".
        if (preg_match(
            '/\b(dime|digas|decir|mu[eé]strame|muestra|pasame|pásame|dame|quiero|necesito)\b.{0,50}\b([aá]reas)\b/u',
            $q
        )) {
            return true;
        }

        if (preg_match('/\b([aá]reas)\s+(de\s+la\s+)?(empresa|organizaci[oó]n|proser)\b/u', $q)) {
            return true;
        }

        return (bool) preg_match(
            '/^(no[,:]?\s+)?(quiero\s+(que\s+(me\s+)?)?)?(digas?\s+|dime\s+)?(las?\s+)?([aá]reas)\s*\??$/u',
            $q
        ) || (bool) preg_match('/^(qu[eé]\s+)?([aá]reas?)\s*\??$/u', $q);
    }

    /**
     * "qué personas son de Jurídico", "quiénes están en el área de TI".
     */
    private function wantsPeopleInAreaQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if (preg_match('/\b(procedimiento|documento|folio|pol[ií]tica)\b/u', $q)) {
            return false;
        }

        $pidePersonas = (bool) preg_match(
            '/\b(personas?|empleados?|gente|trabajan?|qui[eé]nes|quienes|'
            . 'qu[eé]\s+personas?|quién\s+est[aá]|quien\s+est[aá])\b/u',
            $q
        );

        if (!$pidePersonas) {
            return false;
        }

        if (preg_match('/\b([aá]rea)\b/u', $q)) {
            return true;
        }

        if (preg_match('/\b(ti|t\.?i\.?|tecnolog)\b/u', $q)) {
            return true;
        }

        return $this->findAreasMentionedInQuery($q)->isNotEmpty();
    }

    /**
     * "quiénes son los directores", "lista de directores", "directores de la empresa".
     */
    private function wantsCompanyDirectorsList(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || !preg_match('/\bdirectores?\b/u', $q)) {
            return false;
        }

        if (
            preg_match('/\b(procedimiento|documento|folio)\b/u', $q)
            && !preg_match('/\b(no tiene que ver|nada que ver|no es (de|un)|ya no)\b/u', $q)
        ) {
            return false;
        }

        return (bool) preg_match(
            '/\b(lista|listado|qui[eé]nes|quienes|cu[aá]les|todos|todas|'
            . 'empresa|unidad(es)?|[aá]reas?|negocio|esas|esos|dime|quiero|dame)\b/u',
            $q
        ) || (bool) preg_match(
            '/^(bueno[,:]?\s+|entonces\s+|y\s+)?(los?\s+)?directores?\s*\??$/u',
            $q
        );
    }

    /**
     * "qué es el corporativo", "qué es la unidad Construcción".
     */
    private function isBusinessUnitDefinitionQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        if (!preg_match('/\b(qu[eé]\s+es|qu[eé]\s+son|significa|explicame|expl[ií]came)\b/u', $q)) {
            return false;
        }

        if (preg_match('/\b(procedimiento|documento|folio|pol[ií]tica|lineamiento)\b/u', $q)) {
            return false;
        }

        if (preg_match('/\b(unidad(es)?(\s+de\s+negocio)?|corporativo|divisi[oó]n)\b/u', $q)) {
            return true;
        }

        return $this->findUnidadesMentionedInQuery($q)->isNotEmpty();
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

        // Lista de directores / áreas / personas por área / quién es [nombre] / qué es una unidad.
        if (
            $this->wantsCompanyDirectorsList($q)
            || $this->wantsCompanyAreasList($q)
            || $this->wantsPeopleInAreaQuery($q)
            || $this->wantsEmployeeByNameQuery($q)
            || $this->isBusinessUnitDefinitionQuery($q)
        ) {
            return true;
        }

        // Solo salir del directorio si hay folio o menciona explícitamente un documento.
        // NO usar matchesKnownDocumentName: palabras como "coordinador" coinciden con nombres
        // de procedimientos y mandaban mal la pregunta al RAG.
        if (
            preg_match('/\b(procedimiento|documento|folio|pol[ií]tica|lineamiento|elemento|proceso)\b/u', $q)
            || preg_match('/\b([a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/u', $q)
        ) {
            return false;
        }

        // "quién es el responsable de [tema]" (renta de maquinaria, gestionar trámites…):
        // es del documento SGC, no del directorio de personas.
        if (
            preg_match('/\b(qui[eé]n|quien).{0,60}\b(responsables?|encargad[oa]s?)\b/u', $q)
            && preg_match('/\b(de|del)\b/u', $q)
            && !preg_match('/\b(unidad|[aá]rea|empresa|departamento|puesto)\b/u', $q)
            && !preg_match(
                '/\b(coordinador(?:es|as)?|gerente(?:s)?|director(?:es|as)?|auxiliar(?:es)?|analista(?:s)?|jefe(?:s|as)?)\b/u',
                $q
            )
        ) {
            return false;
        }

        // "cómo se llama / se llamada / se llaman…" (tolera typos comunes)
        $pidePersona = (bool) preg_match(
            '/\b(c[oó]mo se llam\w*|qui[eé]n es|quien es|qui[eé]n ocupa|quien ocupa|'
            . 'nombre del|nombre de la|a cargo|qui[eé]nes son|quienes son|'
            . 'dime qui[eé]n|me puedes decir|puedes decir)\b/u',
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

        if ($pidePersona && ($hablaDePuesto || $hablaDeUnidad)) {
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

        // Unidades de la empresa (catálogo global, no las del procedimiento en foco).
        if (
            preg_match('/\b(unidades?|divisiones?)\b/u', $combined)
            && preg_match('/\b(empresa|negocio|proser|organizaci[oó]n|hay|cu[aá]les|todas|lista|listado|dime|decir)\b/u', $combined)
            && !preg_match('/\b(procedimiento|documento|de este|de ese|aplican)\b/u', $combined)
        ) {
            $unidades = UnidadNegocio::query()
                ->orderBy('nombre')
                ->get(['id_unidad_negocio', 'nombre']);

            if ($unidades->isEmpty()) {
                $msg = "No tengo unidades de negocio registradas en el directorio ahora mismo.";
            } else {
                $lineas = $unidades->map(fn ($u) => '- ' . $u->nombre)->implode("\n");
                $msg = "Estas son las **unidades de negocio** de la empresa (**{$unidades->count()}**):\n\n"
                    . $lineas
                    . "\n\nSi quieres los **directores** o un puesto concreto, dime el nombre "
                    . "(ej. Director de Construcción).";
            }

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_company_units',
                $startTime,
                $userId,
                $sessionId
            );
        }

        // "quién es Alberto Bas" → directorio de empleados (pedir aclaración si hace falta).
        if ($this->wantsEmployeeByNameQuery($combined) || $this->wantsEmployeeByNameQuery($originalQuery)) {
            $nombreBuscado = $this->extractPersonNameFromWhoIsQuery($originalQuery)
                ?? $this->extractPersonNameFromWhoIsQuery($combined)
                ?? 'esa persona';
            $empleados = $this->findEmpleadosByNameQuery($originalQuery);
            if ($empleados->isEmpty()) {
                $empleados = $this->findEmpleadosByNameQuery($combined);
            }

            if ($empleados->isEmpty()) {
                $msg = "No encontré a **{$nombreBuscado}** en el directorio de empleados.\n\n"
                    . "Para ubicarlo necesito un poco más de precisión:\n"
                    . "- **Nombre completo** (nombre y apellidos)\n"
                    . "- O el **puesto** / **área** (ej. Auxiliar de TI, Jurídico)\n\n"
                    . "Así lo busco en la estructura de la empresa, sin amarrarme a un documento.";

                return $this->buildDirectoryChatResponse(
                    $originalQuery,
                    $msg,
                    'directory_employee_not_found',
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            if ($empleados->count() > 1) {
                $lineas = $empleados->map(function ($emp) {
                    $nombre = trim(implode(' ', array_filter([
                        $emp->nombres,
                        $emp->apellido_paterno,
                        $emp->apellido_materno,
                    ])));
                    $puesto = optional($emp->puestoTrabajo)->nombre ?? 'Puesto no registrado';

                    return "- **{$nombre}** — {$puesto}";
                })->implode("\n");

                $msg = "Encontré **{$empleados->count()}** personas parecidas a **{$nombreBuscado}**:\n\n"
                    . $lineas
                    . "\n\n¿Cuál te interesa? Copia el **nombre completo** o dime el **puesto**.";

                return $this->buildDirectoryChatResponse(
                    $originalQuery,
                    $msg,
                    'directory_employee_clarify',
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            $emp = $empleados->first();
            $nombre = trim(implode(' ', array_filter([
                $emp->nombres,
                $emp->apellido_paterno,
                $emp->apellido_materno,
            ])));
            $puesto = optional($emp->puestoTrabajo)->nombre ?? 'Puesto no registrado';
            $puestoId = (int) ($emp->puesto_trabajo_id ?? 0);

            $catalogState = $puestoId > 0 ? [
                'mode' => 'by_puesto',
                'puesto_ids' => [$puestoId],
                'puesto_nombres' => [$puesto],
                'label' => 'puesto(s): ' . $puesto,
            ] : null;

            $msg = "**{$nombre}** aparece en el directorio como **{$puesto}**.\n\n"
                . "¿Qué necesitas de esta persona?\n"
                . "- **qué procedimientos tiene asignados**\n"
                . "- o dime si buscas **área**, **jefe** u otro dato";

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_employee_by_name',
                $startTime,
                $userId,
                $sessionId,
                $catalogState
            );
        }

        // Personas del área (directorio), NO del procedimiento en foco.
        if ($this->wantsPeopleInAreaQuery($combined)) {
            $areas = $this->findAreasMentionedInQuery($combined);
            if ($areas->isEmpty()) {
                $msg = "¿De qué **área** quieres las personas? (ej. Jurídico, TI, Calidad).";

                return $this->buildDirectoryChatResponse(
                    $originalQuery,
                    $msg,
                    'directory_people_need_area',
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            // Evitar mezclar demasiadas áreas homónimas: las 3 más específicas (nombre corto).
            $areas = $areas->take(3)->values();
            $areaIds = $areas->pluck('id_area')->map(fn ($id) => (int) $id)->all();
            $areaLabel = $areas->pluck('nombre')->implode(', ');
            $puestoIds = $this->puestoIdsForAreaIds($areaIds);

            if (empty($puestoIds)) {
                $msg = "No encontré puestos vinculados al área **{$areaLabel}** en el directorio.";

                return $this->buildDirectoryChatResponse(
                    $originalQuery,
                    $msg,
                    'directory_people_area_empty_puestos',
                    $startTime,
                    $userId,
                    $sessionId
                );
            }

            $puestos = PuestoTrabajo::query()
                ->whereIn('id_puesto_trabajo', $puestoIds)
                ->orderBy('nombre')
                ->get(['id_puesto_trabajo', 'nombre']);
            $empleados = Empleados::whereIn('puesto_trabajo_id', $puestoIds)
                ->whereNull('deleted_at')
                ->orderBy('apellido_paterno')
                ->limit(60)
                ->get(['nombres', 'apellido_paterno', 'apellido_materno', 'puesto_trabajo_id']);
            $map = $puestos->keyBy('id_puesto_trabajo');

            $catalogState = [
                'mode' => 'by_area',
                'area_ids' => $areaIds,
                'area_nombres' => $areas->pluck('nombre')->values()->all(),
                'puesto_ids' => $puestoIds,
                'label' => 'área ' . $areaLabel,
            ];

            if ($empleados->isEmpty()) {
                $lineas = $puestos->take(25)->map(fn ($p) => '- ' . $p->nombre)->implode("\n");
                $msg = "En el área **{$areaLabel}** no hay personas asignadas en el directorio ahora mismo.\n\n"
                    . "Puestos del área:\n{$lineas}";
            } else {
                $lineas = $empleados->map(function ($emp) use ($map) {
                    $nombre = trim(implode(' ', array_filter([
                        $emp->nombres,
                        $emp->apellido_paterno,
                        $emp->apellido_materno,
                    ])));
                    $puesto = optional($map->get($emp->puesto_trabajo_id))->nombre ?? 'Puesto';

                    return "- **{$nombre}** — {$puesto}";
                })->implode("\n");

                $msg = "Según el directorio, estas son las **personas del área {$areaLabel}** "
                    . "(**{$empleados->count()}**):\n\n"
                    . $lineas
                    . "\n\nEsto es de la **empresa**, no de un procedimiento concreto.";
            }

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_people_by_area',
                $startTime,
                $userId,
                $sessionId,
                $catalogState
            );
        }

        // Áreas de la empresa (catálogo Area), no procedimientos ni PDF.
        if ($this->wantsCompanyAreasList($combined)) {
            $areas = Area::query()
                ->orderBy('nombre')
                ->get(['id_area', 'nombre']);

            if ($areas->isEmpty()) {
                $msg = "No tengo áreas registradas en el directorio ahora mismo.";
            } else {
                $lineas = $areas->map(fn ($a) => '- ' . $a->nombre)->implode("\n");
                $msg = "Estas son las **áreas** registradas (**{$areas->count()}**):\n\n"
                    . $lineas
                    . "\n\nPuedes pedir **procedimientos de [área]** o **quién ocupa** un puesto.";
            }

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_company_areas',
                $startTime,
                $userId,
                $sessionId
            );
        }

        // Directores (organigrama): "quiénes son los directores", "lista de directores", etc.
        if ($this->wantsCompanyDirectorsList($combined)) {
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

        // "qué es el corporativo" → unidad de negocio, no un procedimiento con esa etiqueta.
        if ($this->isBusinessUnitDefinitionQuery($combined)) {
            $unidadesHit = $this->findUnidadesMentionedInQuery($combined);
            if ($unidadesHit->isEmpty() && preg_match('/\bcorporativo\b/u', $combined)) {
                $unidadesHit = UnidadNegocio::query()
                    ->whereRaw('LOWER(nombre) LIKE ?', ['%corporativo%'])
                    ->get(['id_unidad_negocio', 'nombre']);
            }

            $todas = UnidadNegocio::query()
                ->orderBy('nombre')
                ->get(['id_unidad_negocio', 'nombre']);

            if ($unidadesHit->isEmpty() && $todas->isEmpty()) {
                $msg = "No tengo unidades de negocio registradas en el directorio ahora mismo.";
            } elseif ($unidadesHit->isEmpty()) {
                $lineas = $todas->map(fn ($u) => '- ' . $u->nombre)->implode("\n");
                $msg = "**Corporativo / unidad** forma parte de la estructura de PROSER.\n\n"
                    . "Estas son las **unidades de negocio** registradas:\n\n" . $lineas
                    . "\n\nSi quieres, pregunta por los **directores** o por un puesto concreto.";
            } else {
                $nombre = $unidadesHit->first()->nombre;
                $otras = $todas->filter(
                    fn ($u) => (int) $u->id_unidad_negocio !== (int) $unidadesHit->first()->id_unidad_negocio
                )->map(fn ($u) => $u->nombre)->implode(', ');

                $msg = "**{$nombre}** es una **unidad de negocio** de PROSER "
                    . "(estructura de la empresa, no un procedimiento).\n\n"
                    . ($otras !== '' ? "Otras unidades: {$otras}.\n\n" : '')
                    . "Puedes preguntarme por los **directores**, un **puesto** de esa unidad "
                    . "o los procedimientos que aplican a **{$nombre}**.";
            }

            return $this->buildDirectoryChatResponse(
                $originalQuery,
                $msg,
                'directory_unit_definition',
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

        // Sin puestos reconocidos → pedir más información (no buscar un PDF al azar).
        if ($puestos->isEmpty()) {
            $msg = "Para decirte **quién ocupa ese puesto** necesito un poco más de precisión.\n\n"
                . "Indica, por ejemplo:\n"
                . "- El **nombre del puesto** (ej. Coordinador de Tecnologías de la Información, Analista de Calidad)\n"
                . "- O si buscas el responsable de un **procedimiento**, su nombre o folio\n\n"
                . "Así te respondo directo desde el directorio, sin amarrarme a un documento.";

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
            . "\n\nSi quieres ver los procedimientos de ese puesto, escribe:\n"
            . "- **qué procedimientos tienen asignados** (responsable + relacionados)\n"
            . "- **propios del puesto** (solo donde figura como responsable)";

        return $this->buildDirectoryChatResponse(
            $originalQuery,
            $msg,
            'directory_people',
            $startTime,
            $userId,
            $sessionId,
            $catalogState
        );
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
            return "Para darte el responsable correcto necesito ubicar el documento. Dime:\n\n- **Nombre** del procedimiento\n- **Tema** o de qué trata\n\nO su **folio** si lo tienes.";
        }

        if (preg_match('/\b(procedimiento|lineamiento|manual|documento)\b/u', $normalized)) {
            return "Con gusto. ¿Qué necesitas?\n\n"
                . "1. **Mis procedimientos** (según tu puesto)\n"
                . "2. Un documento por **nombre o folio**\n"
                . "3. Listado por **área** (ej. Compras, Jurídico)\n\n"
                . "Dime el número o escribe lo que buscas.";
        }

        return "¿En qué te ayudo?\n\n"
            . "1. **Mis procedimientos**\n"
            . "2. **Directorio** (puestos / unidades)\n"
            . "3. Un **documento** (nombre o folio)\n"
            . "4. Listado por **área**\n\n"
            . "Responde con el número o una frase corta.";
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
     * Cierre suave: el documento en foco no respondió, pero el hilo se conserva.
     * Nunca digas que borraste el contexto ni exijas folio.
     */
    private function buildKeepThreadClarifyMessage(string $query, ?string $titulo): string
    {
        $q = mb_strtolower($query);

        if ($this->isPracticalOperationalQuery($q)) {
            return "Sigo tu duda (factura / a quién enviarla). **No borré** la conversación.\n\n"
                . "Para orientarte bien, ayúdame con una de estas:\n"
                . "- ¿Es una factura de **gasto/proveedor** (ej. Telcel) o de **cobro a cliente**?\n"
                . "- ¿De qué **unidad** o área eres?\n\n"
                . "Con eso te digo el **área/puesto** o el procedimiento que aplica, sin que necesites el folio.";
        }

        if ($titulo) {
            return "En **{$titulo}** no viene esa respuesta con claridad.\n\n"
                . "**No borré** el hilo. Reformula en una frase (qué necesitas hacer) "
                . "o dime el **área/puesto** involucrado; si conoces folio o nombre del documento, también sirve.";
        }

        return "No pude resolverlo con el documento anterior.\n\n"
            . "**No borré** la conversación. Cuéntame de nuevo la duda en una frase "
            . "(qué quieres hacer / a quién enviar / qué área) y te ayudo a aterrizarla.";
    }

    /**
     * @deprecated Conservado por compatibilidad; preferir buildKeepThreadClarifyMessage.
     */
    private function buildNotFoundInElementoMessage(string $query, ?string $titulo): string
    {
        return $this->buildKeepThreadClarifyMessage($query, $titulo);
    }

    /**
     * Saludo corto / casual ("hola", "hola my friend") sin pregunta de fondo.
     */
    private function isGreetingOnly(string $query): bool
    {
        $q = trim($query);
        if ($q === '' || !preg_match('/^(hola|holi|buenos\s+d[ií]as|buenas\s+tardes|buenas\s+noches|hi|hello|hey|start|inicio)\b/iu', $q)) {
            return false;
        }

        $rest = preg_replace(
            '/^(hola|holi|hi|hello|hey|buenos\s+d[ií]as|buenas\s+tardes|buenas\s+noches|start|inicio)[,!]?\s*/iu',
            '',
            $q
        ) ?? '';
        $rest = preg_replace(
            '/\b(my\s+friend|amigo|amiga|amigos|qu[eé]\s+tal|que\s+tal|c[oó]mo\s+est[aá]s|como\s+estas)\b/iu',
            '',
            $rest
        ) ?? '';
        $rest = trim($rest, " \t,.!?¿¡");

        return $rest === '' || mb_strlen($rest) < 3;
    }

    /**
     * Quejas de hilo: "no te acuerdas", "no borres", "no me sé el nombre".
     */
    private function isContextMetaComplaint(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        return (bool) preg_match(
            '/\b('
            . 'no te acuerdas|no te acuerda|no te acord|hace rato te (dije|pregunt)|'
            . 'porque borras|por qu[eé] borras|no borres|no me borres|'
            . 'no me s[eé] el (nombre|folio)|no s[eé] el (nombre|folio)|'
            . 'no me se el (nombre|folio)|no se el (nombre|folio)'
            . ')\b/u',
            $q
        ) || (bool) preg_match('/^(nono|no\s*no)\b/u', $q);
    }

    private function buildContextMetaResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $q = mb_strtolower($query);
        $history = $this->getConversationHistory($sessionId, 6);
        $lastUser = '';
        foreach (array_reverse($history) as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $prev = mb_strtolower((string) ($msg['content'] ?? ''));
                if ($prev !== $q && !$this->isContextMetaComplaint($prev) && !$this->isGreetingOnly($prev)) {
                    $lastUser = trim((string) $msg['content']);
                    break;
                }
            }
        }

        if (preg_match('/\b(borras|borres|borr[eé])\b/u', $q)) {
            $msg = "**No borro** el historial de la plática al fallar una búsqueda; solo suelto un documento en foco si ya no aplica.\n\n"
                . "Sigue con tu duda en una frase (ej. factura Telcel → a qué área enviarla) y la retomo.";
        } elseif (preg_match('/\b(nombre|folio)\b/u', $q)) {
            $msg = "No necesitas el **nombre ni el folio** para empezar.\n\n"
                . "Descríbeme qué quieres hacer (ej. *tengo una factura de Telcel y no sé a quién enviarla*) "
                . "y te oriento al área/procedimiento.";
        } elseif ($lastUser !== '') {
            $snip = mb_strlen($lastUser) > 140 ? mb_substr($lastUser, 0, 140) . '…' : $lastUser;
            $msg = "Sí, te acuerdas bien: veníamos con esto — *«{$snip}»*.\n\n"
                . "¿Seguimos con esa duda? Confirma o aclara un detalle (área, tipo de factura, a quién enviar).";
        } else {
            $msg = "Sigo el hilo de esta sesión. ¿Me recuerdas la duda en una frase? "
                . "(qué quieres hacer / a quién enviar / qué área)";
        }

        return [
            'response' => $msg,
            'method' => 'conversation_meta_context',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => [],
            'cached' => false,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'conversation_meta_context',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * Dudas operativas cotidianas: factura, a quién enviar, departamento, correo…
     */
    private function isPracticalOperationalQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));

        $hablaOperacion = (bool) preg_match(
            '/\b(factura|facturas|telcel|proveedor|gasto|cobranza|cobro|'
            . 'departamento|[aá]rea|correo|email|enviarla|enviarlo|enviar|mandar|mando)\b/u',
            $q
        );

        $pideAyuda = (bool) preg_match(
            '/\b(tengo|qu[eé] hago|que hago|a qui[eé]n|a quien|d[oó]nde|donde|'
            . 'no s[eé]|nose|no se|ayud|orient|env[ií]o|mandarle)\b/u',
            $q
        );

        return $hablaOperacion && $pideAyuda;
    }

    private function docFocusLooksRelevantToPracticalQuery(?array $cachedContext, string $query): bool
    {
        if (!$cachedContext || empty($cachedContext['title'])) {
            return false;
        }

        $title = $this->foldAccents((string) $cachedContext['title']);

        return str_contains($title, 'factur')
            || str_contains($title, 'cobr')
            || str_contains($title, 'pago')
            || str_contains($title, 'egreso')
            || str_contains($title, 'proveedor')
            || str_contains($title, 'cuenta por pagar')
            || str_contains($title, 'estimacion');
    }

    /**
     * Aísla 2 casos:
     * - talk: decidir/aclarar solo con la plática (sin buscar archivos aún)
     * - search: intención clara → buscar en archivos por el prompt
     */
    private function resolveQueryLane(
        string $cleanQuery,
        string $searchQuery,
        ?array $cachedContext,
        ?string $sessionId
    ): string {
        $hasDocFocus = $cachedContext && !empty($cachedContext['id']);

        // Carril SEARCH: señales claras de documento / contenido SGC.
        if (
            preg_match('/\b([a-z]{2,}\d{1,4}[-_][a-z0-9-]+)\b/iu', $cleanQuery)
            || $this->looksLikeSgcDocumentAsk($cleanQuery, $searchQuery)
            || ($hasDocFocus && $this->isShortDocumentFollowUp($cleanQuery))
            || ($hasDocFocus && $this->isElementoResponsableMetaQuery($cleanQuery))
            || $this->isMyProceduresQuery($cleanQuery)
        ) {
            return 'search';
        }

        // Carril TALK: duda práctica abierta, plática incompleta, o tema aislado en curso.
        $topic = \Cache::get($this->getTalkTopicKey($sessionId));
        if (is_array($topic) && !empty($topic['open'])) {
            // El usuario está respondiendo una aclaración del hilo → seguir en TALK
            // hasta que la IA emita [[SEARCH:...]].
            if (!$this->looksLikeSgcDocumentAsk($cleanQuery, $searchQuery)) {
                return 'talk';
            }
        }

        if ($this->isPracticalOperationalQuery($cleanQuery)) {
            return 'talk';
        }

        if ($this->isOpenConversationalTurn($cleanQuery)) {
            return 'talk';
        }

        // Con PDF en foco y pregunta corta de contenido → archivos.
        if ($hasDocFocus) {
            return 'search';
        }

        // Por defecto: si no hay blanco claro, primero decidir por plática.
        if (!$hasDocFocus && mb_strlen($cleanQuery) < 120 && !preg_match('/\b(folio|procedimiento|documento)\b/u', mb_strtolower($cleanQuery))) {
            return 'talk';
        }

        return 'search';
    }

    /**
     * Turno abierto / incompleto: la IA debe decidir o preguntar, no disparar RAG.
     */
    private function isOpenConversationalTurn(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || mb_strlen($q) < 8) {
            return true;
        }

        return (bool) preg_match(
            '/\b(qu[eé] hago|que hago|no s[eé]|nose|ayudame|ayúdame|orientame|oríéntame|'
            . 'a qui[eé]n|a quien|c[oó]mo le hago|como le hago|por ejemplo|'
            . 'ahorita tengo|tengo una|mi duda|me puedes ayudar)\b/u',
            $q
        );
    }

    private function getTalkTopicKey(?string $sessionId): string
    {
        return 'chat_talk_topic_' . ($sessionId ?: 'guest');
    }

    /**
     * Carril TALK: IA con historial, sin RAG. Puede devolver texto o [[SEARCH: …]].
     *
     * @return array{response?: array, search_query?: string}
     */
    private function runTalkLaneDecision(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        ?array $cachedContext
    ): array {
        if (!$this->usePaidAI) {
            $msg = $this->buildKeepThreadClarifyMessage($query, $cachedContext['title'] ?? null);

            return [
                'response' => [
                    'response' => $msg,
                    'method' => 'talk_lane_fallback',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => [],
                    'search_details' => ['lane' => 'talk'],
                    'cached' => false,
                    'document' => null,
                    'analytics_id' => $this->logAnalytics(
                        $query,
                        $msg,
                        'talk_lane_fallback',
                        $startTime,
                        $userId,
                        $sessionId
                    ),
                ],
            ];
        }

        // Plática casual / fuera de alcance duro: respuesta corta, sin interrogatorio.
        if ($this->isCasualChitchatQuery($query)) {
            return $this->buildCasualTalkResponse($query, $startTime, $userId, $sessionId);
        }

        // Cortar de inmediato temas ajenos (Bimbo, etc.) sin llamar a la IA ni a RAG.
        if ($this->isOutOfScopeTopicQuery($query)) {
            $msg = $this->formatTopicNotInDatabaseMessage($query);
            \Cache::put($this->getTalkTopicKey($sessionId), [
                'open' => false,
                'not_found' => true,
                'updated_at' => now()->toDateTimeString(),
            ], 900);

            return [
                'not_found' => true,
                'response' => [
                    'response' => $msg,
                    'method' => 'talk_not_found',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'document' => null,
                    'chips' => $this->defaultTalkRouteChips(),
                    'analytics_id' => $this->logAnalytics(
                        $query,
                        $msg,
                        'talk_not_found',
                        $startTime,
                        $userId,
                        $sessionId
                    ),
                ],
            ];
        }

        $history = $this->getConversationHistory($sessionId, 10);
        $topic = \Cache::get($this->getTalkTopicKey($sessionId));
        $clarifyCount = is_array($topic) ? (int) ($topic['clarify_count'] ?? 0) : 0;
        $topicLabelPrev = is_array($topic) ? (string) ($topic['label'] ?? '') : '';

        // "sí" / confirmación sobre jefe u ofrecimiento previo → buscar ya.
        $forcedSearch = $this->resolveTalkAffirmationSearch(
            $query,
            $topicLabelPrev,
            $history,
            is_array($topic) ? $topic : null
        );
        if ($forcedSearch !== null) {
            \Cache::put($this->getTalkTopicKey($sessionId), [
                'open' => false,
                'last_search' => $forcedSearch,
                'updated_at' => now()->toDateTimeString(),
            ], 900);

            return ['search_query' => $forcedSearch];
        }

        // Ya aclaramos una vez y el usuario no sabe más → rutas, no más preguntas.
        $qFold = $this->foldAccents(mb_strtolower($query));
        $userStuck = (bool) preg_match(
            '/\b(no se|nose|no se cual|por eso te pregunto|tu dime|dime tu|'
            . 'ayudame|ayudame tu|no tengo idea|no se cual es)\b/u',
            $qFold
        );
        if ($clarifyCount >= 1 && ($userStuck || $this->isVagueAffirmation($query))) {
            return [
                'response' => $this->buildTalkRouteChipsPayload(
                    $query,
                    $startTime,
                    $userId,
                    $sessionId,
                    $topicLabelPrev
                ),
            ];
        }
        if ($clarifyCount >= 2) {
            return [
                'response' => $this->buildTalkRouteChipsPayload(
                    $query,
                    $startTime,
                    $userId,
                    $sessionId,
                    $topicLabelPrev
                ),
            ];
        }

        $topicHint = is_array($topic)
            ? ('Tema aislado en curso: ' . json_encode($topic, JSON_UNESCAPED_UNICODE)
                . " | Aclaraciones ya hechas: {$clarifyCount}. Si clarify_count>=1, NO preguntes otra vez.")
            : 'Sin tema aislado aún. Máximo 1 pregunta de aclaración.';

        try {
            $raw = $this->paidAIService->generateTalkDecision(
                $query,
                $history,
                $topicHint,
                $this->activeConversationMode
            );
        } catch (\Throwable $e) {
            \Log::warning('Talk lane AI failed', ['error' => $e->getMessage()]);
            $msg = $this->buildKeepThreadClarifyMessage($query, $cachedContext['title'] ?? null);

            return [
                'response' => [
                    'response' => $msg,
                    'method' => 'talk_lane_error',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'document' => null,
                    'chips' => $this->defaultTalkRouteChips(),
                ],
            ];
        }

        $raw = trim((string) $raw);

        // No existe en BD / fuera de PROSER: [[NOT_FOUND: motivo]]
        if (preg_match('/\[\[\s*NOT_FOUND\s*(?::\s*(.+?))?\s*\]\]/iu', $raw, $m)) {
            $motivo = trim($m[1] ?? '');
            \Cache::put($this->getTalkTopicKey($sessionId), [
                'open' => false,
                'not_found' => true,
                'updated_at' => now()->toDateTimeString(),
            ], 900);

            $msg = $this->formatTopicNotInDatabaseMessage($query, $motivo);

            return [
                'not_found' => true,
                'response' => [
                    'response' => $msg,
                    'method' => 'talk_not_found',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'sources' => [],
                    'search_details' => ['lane' => 'talk', 'not_found' => true],
                    'cached' => false,
                    'document' => null,
                    'chips' => $this->defaultTalkRouteChips(),
                    'analytics_id' => $this->logAnalytics(
                        $query,
                        $msg,
                        'talk_not_found',
                        $startTime,
                        $userId,
                        $sessionId
                    ),
                ],
            ];
        }

        // Listo para archivos: [[SEARCH: consulta refinada]]
        if (preg_match('/\[\[\s*SEARCH\s*:\s*(.+?)\s*\]\]/iu', $raw, $m)) {
            $refined = trim($m[1]);

            if ($this->isOutOfScopeTopicQuery($refined) || $this->isOutOfScopeTopicQuery($query)) {
                $msg = $this->formatTopicNotInDatabaseMessage($query);
                \Cache::put($this->getTalkTopicKey($sessionId), [
                    'open' => false,
                    'not_found' => true,
                    'updated_at' => now()->toDateTimeString(),
                ], 900);

                return [
                    'not_found' => true,
                    'response' => [
                        'response' => $msg,
                        'method' => 'talk_not_found',
                        'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                        'document' => null,
                        'chips' => $this->defaultTalkRouteChips(),
                        'analytics_id' => $this->logAnalytics(
                            $query,
                            $msg,
                            'talk_not_found',
                            $startTime,
                            $userId,
                            $sessionId
                        ),
                    ],
                ];
            }

            \Cache::put($this->getTalkTopicKey($sessionId), [
                'open' => false,
                'last_search' => $refined,
                'updated_at' => now()->toDateTimeString(),
            ], 900);

            return ['search_query' => $refined];
        }

        // Detectar empresas/temas ajenos en la plática antes de seguir preguntando.
        if ($this->isOutOfScopeTopicQuery($query)) {
            $msg = $this->formatTopicNotInDatabaseMessage($query);
            \Cache::put($this->getTalkTopicKey($sessionId), [
                'open' => false,
                'not_found' => true,
                'updated_at' => now()->toDateTimeString(),
            ], 900);

            return [
                'not_found' => true,
                'response' => [
                    'response' => $msg,
                    'method' => 'talk_not_found',
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'document' => null,
                    'chips' => $this->defaultTalkRouteChips(),
                    'analytics_id' => $this->logAnalytics(
                        $query,
                        $msg,
                        'talk_not_found',
                        $startTime,
                        $userId,
                        $sessionId
                    ),
                ],
            ];
        }

        // Sigue en plática: guardar tema abierto + documento propuesto (para un "sí").
        $text = trim(preg_replace('/\[\[\s*(SEARCH|NOT_FOUND)\s*:.*?\]\]/iu', '', $raw) ?? $raw);
        if ($text === '') {
            $text = "Para aterrizar: ¿es un **gasto/proveedor** o un **cobro a cliente**? "
                . "Con una pista ya busco el procedimiento.";
        }

        $pendingDoc = $this->extractProposedDocumentFromText($text);
        $topicLabel = $pendingDoc
            ?: ($topicLabelPrev !== ''
                ? $topicLabelPrev
                : (mb_strlen($query) > 80 ? mb_substr($query, 0, 80) . '…' : $query));
        $nextClarify = $clarifyCount + 1;
        \Cache::put($this->getTalkTopicKey($sessionId), [
            'open' => true,
            'label' => $topicLabel,
            'pending_confirm_doc' => $pendingDoc,
            'clarify_count' => $nextClarify,
            'updated_at' => now()->toDateTimeString(),
        ], 900);

        $chips = $pendingDoc
            ? [
                ['label' => 'Sí, ese', 'query' => 'sí'],
                [
                    'label' => mb_strlen($pendingDoc) > 36 ? mb_substr($pendingDoc, 0, 34) . '…' : $pendingDoc,
                    'query' => $pendingDoc,
                ],
                ['label' => 'No, otro', 'query' => 'ese no es'],
            ]
            : $this->defaultTalkRouteChips();

        // Segunda aclaración sin documento propuesto: cortar a rutas.
        if ($nextClarify >= 2 && $pendingDoc === null) {
            return [
                'response' => $this->buildTalkRouteChipsPayload(
                    $query,
                    $startTime,
                    $userId,
                    $sessionId,
                    $topicLabel,
                    $text
                ),
            ];
        }

        return [
            'response' => [
                'response' => $text,
                'method' => 'talk_lane_decision',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'sources' => [],
                'search_details' => ['lane' => 'talk', 'clarify_count' => $nextClarify],
                'cached' => false,
                'document' => null,
                'chips' => $chips,
                'analytics_id' => $this->logAnalytics(
                    $query,
                    $text,
                    'talk_lane_decision',
                    $startTime,
                    $userId,
                    $sessionId
                ),
            ],
        ];
    }

    private function defaultTalkRouteChips(): array
    {
        return [
            ['label' => 'Cuentas por pagar', 'query' => 'procedimiento de cuentas por pagar'],
            ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
            ['label' => 'Quién es mi jefe', 'query' => 'quién es mi jefe'],
        ];
    }

    private function isCasualChitchatQuery(string $query): bool
    {
        $q = $this->foldAccents(mb_strtolower(trim($query)));
        if ($q === '') {
            return false;
        }

        return (bool) preg_match(
            '/\b(va a llover|que es una computadora|qui[eé]n te (creo|hizo|programo)|'
            . 'eres (un )?robot|tienes novia|cuantos a[nñ]os tienes|'
            . 'cuenta un chiste|como estas|c[oó]mo est[aá]s)\b/u',
            $q
        );
    }

    private function buildCasualTalkResponse(
        string $query,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $msg = "Esa va fuera de mi chamba.\n\n"
            . "Yo te ayudo con lo de **Proser**: procedimientos, áreas, puestos o quién ocupa un cargo.\n"
            . "¿Qué necesitas revisar?";

        return [
            'response' => [
                'response' => $msg,
                'method' => 'talk_casual',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                'document' => null,
                'chips' => $this->defaultTalkRouteChips(),
                'analytics_id' => $this->logAnalytics(
                    $query,
                    $msg,
                    'talk_casual',
                    $startTime,
                    $userId,
                    $sessionId
                ),
            ],
        ];
    }

    /**
     * Confirmar un ofrecimiento del hilo (sí → documento propuesto / jefe / factura).
     */
    private function resolveTalkAffirmationSearch(
        string $query,
        string $topicLabel,
        array $history,
        ?array $topic = null
    ): ?string {
        $q = $this->foldAccents(mb_strtolower(trim($query)));
        $isYes = $this->isVagueAffirmation($query)
            || (bool) preg_match('/\b(contin[uú]a|sigue|busca(lo)?|adelante|ese mismo|el mismo)\b/u', $q);
        if (!$isYes) {
            // "soy analista de …" mientras el hilo es de jefe → buscar jefe con ese puesto.
            if (preg_match('/\bsoy\s+(.+)$/u', $q, $m)) {
                $puesto = trim($m[1]);
                if ($puesto !== '' && (
                    str_contains($this->foldAccents(mb_strtolower($topicLabel)), 'jefe')
                    || $this->historyMentionsBoss($history)
                )) {
                    return 'quién es el jefe del puesto ' . $puesto;
                }
            }

            return null;
        }

        // Prioridad: documento que Bob acaba de proponer ("¿Te refieres a X?").
        $pending = $this->extractPendingDocumentConfirm($history, $topic ?? []);
        if ($pending !== null && $pending !== '') {
            return $pending;
        }

        $blob = $this->foldAccents(mb_strtolower($topicLabel . ' ' . $this->historyBlob($history)));
        if (preg_match('/\b(jefe|jefa|mi jefe|superior)\b/u', $blob)) {
            return 'quién es mi jefe';
        }
        if (preg_match('/\b(factura|telcel|cuenta por pagar|proveedor|cobro)\b/u', $blob)) {
            return 'procedimiento de cuentas por pagar factura proveedor';
        }

        // Nunca concatenar el label si es ruido de rechazo ("pero ese no es").
        if (
            $topicLabel !== ''
            && !$this->looksLikeRejectionOrNoiseLabel($topicLabel)
            && preg_match('/\b(procedimiento|documento|folio)\b/u', $blob)
        ) {
            return 'procedimiento ' . $topicLabel;
        }

        // Último recurso: intención original del usuario en el historial.
        $original = $this->extractOriginalDocumentIntentFromHistory($history);
        if ($original !== null) {
            return $original;
        }

        return null;
    }

    /**
     * Extrae el documento propuesto en frases tipo «¿Te refieres al "X"?».
     */
    private function extractProposedDocumentFromText(string $text): ?string
    {
        $text = trim(strip_tags($text));
        if ($text === '') {
            return null;
        }

        $patterns = [
            '/te refieres a[l]?\s+[«"“]?[*_]{0,2}([^»"”*_?\n]{5,140})[*_]{0,2}[»"”]?/iu',
            '/ser[aá]\s+[«"“]?[*_]{0,2}([^»"”*_?\n]{5,140})[*_]{0,2}[»"”]?/iu',
            '/encontr[eé]\s+[«"“]?[*_]{0,2}([^»"”*_?\n]{5,140})[*_]{0,2}[»"”]?/iu',
            '/documento\s+[«"“]([^»"”]{5,140})[»"”]/iu',
            '/\*\*([^*]{5,140})\*\*/u',
        ];

        foreach ($patterns as $i => $pattern) {
            if (!preg_match($pattern, $text, $m)) {
                continue;
            }
            $cand = trim($m[1], " \t\n\r\0\x0B.?!,;:«»\"“”");
            $cand = preg_replace('/\s+/u', ' ', $cand) ?? $cand;
            // El patrón **...** solo cuenta si parece nombre de procedimiento.
            if ($i === 4 && !preg_match('/\b(procedimiento|solicitud|campamento|proceso|instructivo|manual)\b/iu', $cand)) {
                continue;
            }
            if ($this->looksLikeRejectionOrNoiseLabel($cand)) {
                continue;
            }
            if (mb_strlen($cand) >= 5) {
                return $cand;
            }
        }

        return null;
    }

    private function extractPendingDocumentConfirm(array $history, array $topic = []): ?string
    {
        $fromTopic = trim((string) ($topic['pending_confirm_doc'] ?? ''));
        if ($fromTopic !== '' && !$this->looksLikeRejectionOrNoiseLabel($fromTopic)) {
            return $fromTopic;
        }

        $label = trim((string) ($topic['label'] ?? ''));
        if (
            $label !== ''
            && !$this->looksLikeRejectionOrNoiseLabel($label)
            && preg_match('/\b(procedimiento|solicitud|proceso|instructivo|manual)\b/iu', $label)
        ) {
            return $label;
        }

        for ($i = count($history) - 1; $i >= 0; $i--) {
            $role = (string) ($history[$i]['role'] ?? '');
            $content = (string) ($history[$i]['content'] ?? '');
            if ($role !== 'assistant') {
                continue;
            }
            $doc = $this->extractProposedDocumentFromText($content);
            if ($doc !== null) {
                return $doc;
            }
        }

        return null;
    }

    private function looksLikeRejectionOrNoiseLabel(string $label): bool
    {
        $q = $this->foldAccents(mb_strtolower(trim($label)));
        if ($q === '' || mb_strlen($q) < 4) {
            return true;
        }

        return (bool) preg_match(
            '/\b(pero ese no es|ese no es|esa no es|no eso|me perdi|contin[uú]a con lo que|'
            . 'no se|nose|ok|si|sip|dale)\b/u',
            $q
        )
            || mb_strlen($q) < 12 && preg_match('/\b(ese|esa|esto|eso)\b/u', $q);
    }

    /**
     * Recupera la primera pregunta del usuario que pedía un procedimiento/documento.
     */
    private function extractOriginalDocumentIntentFromHistory(array $history): ?string
    {
        foreach ($history as $turn) {
            if (($turn['role'] ?? '') !== 'user') {
                continue;
            }
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '' || $this->isVagueAffirmation($content)) {
                continue;
            }
            if ($this->looksLikeRejectionOrNoiseLabel($content)) {
                continue;
            }
            $fold = $this->foldAccents(mb_strtolower($content));
            if (preg_match('/\b(procedimiento|solicitud|documento|proceso|instructivo|campamento)\b/u', $fold)) {
                return $content;
            }
        }

        return null;
    }

    private function historyMentionsBoss(array $history): bool
    {
        return (bool) preg_match('/\b(jefe|jefa|superior)\b/u', $this->historyBlob($history));
    }

    private function historyBlob(array $history): string
    {
        $parts = [];
        foreach (array_slice($history, -6) as $turn) {
            $parts[] = (string) ($turn['content'] ?? $turn['message'] ?? '');
        }

        return implode(' ', $parts);
    }

    private function buildTalkRouteChipsPayload(
        string $query,
        $startTime,
        $userId,
        $sessionId,
        string $topicLabel = '',
        string $prefix = ''
    ): array {
        $chips = $this->defaultTalkRouteChips();
        $about = $topicLabel !== '' ? " sobre «{$topicLabel}»" : '';
        $msg = trim($prefix);
        if ($msg !== '') {
            $msg .= "\n\n";
        }
        $msg .= "Para no dar vueltas{$about}, elige una ruta y te llevo directo:\n\n"
            . "1. **Cuentas por pagar** (facturas / proveedores)\n"
            . "2. **Mis procedimientos** (según tu puesto)\n"
            . "3. **Quién es mi jefe** (directorio)\n\n"
            . "O escribe con tus palabras qué necesitas.";

        \Cache::put($this->getTalkTopicKey($sessionId), [
            'open' => false,
            'routed' => true,
            'label' => $topicLabel,
            'updated_at' => now()->toDateTimeString(),
        ], 900);

        return [
            'response' => $msg,
            'method' => 'talk_route_chips',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['lane' => 'talk', 'routed' => true],
            'cached' => false,
            'document' => null,
            'chips' => $chips,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'talk_route_chips',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
    }

    /**
     * Empresas/temas ajenos a Proser (ej. Bimbo) o consultas que no deben ir a RAG.
     */
    private function isOutOfScopeTopicQuery(string $query): bool
    {
        $q = $this->foldAccents(mb_strtolower(trim($query)));
        if ($q === '') {
            return false;
        }

        // Si habla de Proser / SGC, está en alcance.
        if (preg_match('/\b(proser|sgc)\b/u', $q)) {
            return false;
        }

        // Otras empresas / marcas frecuentes que no están en la BD.
        if (preg_match(
            '/\b(bimbo|grupo\s+bimbo|coca[\s-]?cola|pepsi|walmart|cemex|femsa|telmex)\b/u',
            $q
        )) {
            return true;
        }

        return false;
    }

    private function formatTopicNotInDatabaseMessage(string $query, string $motivo = ''): string
    {
        $snip = trim($query);
        $snip = mb_strlen($snip) > 80 ? mb_substr($snip, 0, 80) . '…' : $snip;

        // No mostrar jerga técnica (BD, SGC, RAG…) al usuario.
        $motivoLimpio = trim($motivo);
        if (
            $motivoLimpio !== ''
            && preg_match('/\b(bd|base de datos|rag|sgc|pdf|folio|api)\b/iu', $motivoLimpio)
        ) {
            $motivoLimpio = '';
        }

        $msg = "No encontré esa información en el sistema.\n\n"
            . "Sobre *«{$snip}»* no tengo datos disponibles aquí"
            . " (este asistente solo cubre información de **Proser**).\n\n";

        if ($motivoLimpio !== '') {
            $msg .= "{$motivoLimpio}\n\n";
        }

        $msg .= "Si quieres, puedo ayudarte con lo que sí hay: procedimientos, unidades, áreas, "
            . "puestos o quién ocupa un cargo en Proser.";

        return $msg;
    }

    private function buildTopicNotInDatabaseResponse(
        string $query,
        string $refined,
        $startTime,
        $userId,
        $sessionId
    ): array {
        $msg = $this->formatTopicNotInDatabaseMessage(
            $refined !== '' ? $refined : $query
        );

        return [
            'response' => $msg,
            'method' => 'topic_not_in_database',
            'response_time_ms' => round((microtime(true) - $startTime) * 1000),
            'sources' => [],
            'search_details' => ['not_found' => true],
            'cached' => false,
            'document' => null,
            'analytics_id' => $this->logAnalytics(
                $query,
                $msg,
                'topic_not_in_database',
                $startTime,
                $userId,
                $sessionId
            ),
        ];
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
     * Preguntas de metadatos del elemento en foco: áreas involucradas o puestos de un área.
     * Se responden desde BD para no inventar catálogos (Calidad, Administración…).
     */
    private function isElementoAreaPuestoMetaQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return false;
        }

        // Catálogo de áreas / personas por área → directorio, no metadatos del PDF.
        if ($this->wantsCompanyAreasList($q) || $this->wantsPeopleInAreaQuery($q)) {
            return false;
        }

        $asksAreas = (bool) preg_match(
            '/\b(áreas?|areas?)\b.{0,40}\b(involucr|particip|entran|relacion)|'
            . 'involucr\w*\s+(áreas?|areas?)|'
            . 'se\s+involucran\s+(áreas?|areas?)|'
            . '(qu[eé]|que)\s+(áreas?|areas?)\s+(involucr|particip|tiene|tienen|'
            . 'hay\s+en|del\s+(documento|procedimiento|elemento))/u',
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
            . 'quiénes\s+(participan|entran)|quienes\s+(participan|entran)|'
            . '^(dame\s+|lista\s+(de\s+)?|los?\s+)?puestos?\s*\??$/u',
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
            '/\b(qui[eé]n|quien|cu[aá]l).{0,60}\b(responsables?|encargad[oa]s?)\b|'
            . '\b(responsables?|encargad[oa]s?)\s+(del|de\s+l[ao]s?)\s+'
            . '(procedimiento|elemento|documento|proceso)\b|'
            . '\b(responsable|encargado)\s+de\b/u',
            $q
        );
    }

    /**
     * Extrae el responsable desde el texto del Word (sección 9).
     */
    private function extractResponsableFromDocumentText(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $patterns = [
            '/RESPONSABLE\s+DEL\s+ELEMENTO\s*:?\s*(?:\d+\.\d+\.?\s*)?([^\n\r]{3,90}?)(?=RESPONSABLE\s*:|REVIS[OÓ]|AUTORIZ|PARTICIP|\d+\.\s*[A-ZÁÉÍÓÚÑ]|$)/iu',
            '/RESPONSABLE\s+DE(?:L)?\s+PROCEDIMIENTO\s*:?\s*(?:\d+\.\d+\.?\s*)?([\p{L}][\p{L}\s\.]{2,70}?)(?=PARTICIP|REVIS|AUTORIZ|RESPONSABLE\s*:|$)/iu',
            '/\b9\.\s*RESPONSABLE[^\n:]{0,60}:\s*(?:\d+\.\d+\.?\s*)?([A-ZÁÉÍÓÚÑ][\p{L}\s\.]{2,70}?)(?=RESPONSABLE\s*:|REVIS|AUTORIZ|PARTICIP|$)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $text, $m)) {
                continue;
            }
            $name = trim(preg_replace('/\s+/u', ' ', $m[1]) ?? '');
            $name = trim($name, " \t\n\r\0\x0B.:;-");
            // Cortar basura pegada ("Coordinador de ComprasRESPONSABLE").
            if (preg_match('/^([\p{L}][\p{L}\s\.]+?)(?=RESPONSABLE|REVIS|AUTORIZ|PARTICIP|$)/iu', $name, $cut)) {
                $name = trim($cut[1]);
            }
            if (mb_strlen($name) >= 5 && mb_strlen($name) <= 80
                && preg_match('/\b(coordinador|gerente|director|auxiliar|analista|jefe|residente|encargado)/iu', $name)
            ) {
                return $name;
            }
        }

        return null;
    }

    private function resolveElementoResponsableNombre($elemento): array
    {
        $elemento->loadMissing([
            'puestoResponsable:id_puesto_trabajo,nombre',
            'wordDocument:id,elemento_id,contenido_texto',
        ]);

        $fromBd = optional($elemento->puestoResponsable)->nombre;
        if ($fromBd) {
            return ['nombre' => $fromBd, 'fuente' => 'bd'];
        }

        $text = (string) optional($elemento->wordDocument)->contenido_texto;
        $fromDoc = $this->extractResponsableFromDocumentText($text);
        if ($fromDoc) {
            return ['nombre' => $fromDoc, 'fuente' => 'documento'];
        }

        return ['nombre' => null, 'fuente' => 'ninguna'];
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

        if (!empty($resolved['nombre'])) {
            $fuenteNota = $resolved['fuente'] === 'documento'
                ? ' (según la sección **Responsable del elemento** del documento)'
                : '';
            $msg = "El responsable de **{$nombreDoc}** es el **{$resolved['nombre']}**{$fuenteNota}.";
        } else {
            $msg = "En **{$nombreDoc}** no hay un puesto marcado como responsable "
                . "ni en la ficha ni en la sección del documento.";
            if ($rels->isNotEmpty()) {
                $msg .= "\n\nSí hay puestos **relacionados**:\n"
                    . $rels->take(12)->map(fn ($p) => '- ' . $p)->implode("\n");
                if ($rels->count() > 12) {
                    $msg .= "\n- … y " . ($rels->count() - 12) . ' más';
                }
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
            '/puestos?\s+(relacionados|vinculados|involucrados)|'
            . 'quiénes\s+(participan|entran)|quienes\s+(participan|entran)|'
            . '^(dame\s+|lista\s+(de\s+)?|los?\s+)?puestos?\s*\??$/u',
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
            $history = $this->getConversationHistory($sessionId);

            // 2. MEDIR TIEMPO IA
            $aiStartTime = microtime(true);

            try {
                // 3. GENERAR RESPUESTA OPENAI
                $aiResponse = $this->paidAIService->generateResponse(
                    $query,
                    $context,
                    (int) config('services.ai.chat_timeout', 90),
                    $history,
                    $elemento,
                    $this->activeConversationMode ?? 'procedimientos'
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

                $chatTimeout = (int) config('services.ai.chat_timeout', 90);
                if (
                    $aiElapsed >= $chatTimeout ||
                    str_contains($aiException->getMessage(), 'timeout') ||
                    str_contains($aiException->getMessage(), 'timed out')
                ) {
                    \Log::warning('IA de pago tardó demasiado, fallback a datos', [
                        'elapsed' => $aiElapsed,
                        'timeout' => $chatTimeout,
                    ]);
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
                $aiResponse = $this->paidAIService->generateResponse(
                    $query,
                    $this->applyToneInstruction(),
                    (int) config('services.ai.chat_timeout', 90),
                    $history,
                    null,
                    $this->activeConversationMode
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
                $aiElapsed = microtime(true) - $aiStartTime;
                $chatTimeout = (int) config('services.ai.chat_timeout', 90);

                if (
                    $aiElapsed >= $chatTimeout ||
                    strpos($aiException->getMessage(), 'timeout') !== false ||
                    strpos($aiException->getMessage(), 'timed out') !== false
                ) {
                    Log::warning('IA de pago tardó demasiado, usando respuesta genérica', [
                        'elapsed' => $aiElapsed,
                        'timeout' => $chatTimeout,
                    ]);
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
            $elementos = $searchResults['elementos'] ?? collect();
            // Si hay un documento nombrado con claridad, no volcar lista de "Coincidencias".
            $top = $elementos instanceof \Illuminate\Support\Collection ? $elementos->first() : null;
            if (
                $top
                && (
                    !empty($top->named_match)
                    || $this->namedMatchStrength($query, $top) >= 40
                    || (($top->fused_score ?? 0) >= 10)
                )
            ) {
                $nombre = $top->nombre_elemento ?? 'este procedimiento';
                $folio = trim((string) ($top->folio_elemento ?? ''));
                $tag = $folio !== '' ? " ({$folio})" : '';
                $response = "Encontré **{$nombre}**{$tag}.\n\n"
                    . "Ábrelo con el botón o pregunta qué necesitas saber de este procedimiento "
                    . "(objetivo, pasos, responsables, etc.).";
                $searchResults['elementos'] = collect([$top]);
            } else {
                $response = $this->generateContextualResponse($query, $searchResults, $intent);
            }
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
        if (
            !isset($searchResults['elementos'])
            || $searchResults['elementos']->isEmpty()
        ) {
            return $this->generateNoResultsResponse($query, $intent);
        }

        return "**Coincidencias:**\n\n"
            . $this->buildElementoSummarySection($searchResults['elementos'], $intent);
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
        $response = "Ahora mismo no pude recuperar la información. Intenta de nuevo en un momento "
            . "o reformula la pregunta (folio, nombre del documento, puesto o área).";

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
     * Recupera el historial de chat formateado para enviarlo a la IA (multi-turno).
     */
    private function getConversationHistory($sessionId, $limit = 10)
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
            $userText = trim((string) $chat->query);
            $assistantText = trim(strip_tags((string) $chat->response));
            if ($userText !== '') {
                $history[] = [
                    'role' => 'user',
                    'content' => mb_strlen($userText) > 500 ? mb_substr($userText, 0, 500) . '…' : $userText,
                ];
            }
            if ($assistantText !== '') {
                $history[] = [
                    'role' => 'assistant',
                    'content' => mb_strlen($assistantText) > 1000
                        ? mb_substr($assistantText, 0, 1000) . '…'
                        : $assistantText,
                ];
            }
        }

        return $history;
    }

    public function getActiveConversationMode(?string $sessionId, ?string $userId): ?string
    {
        $mode = \Cache::get($this->getConversationModeKey($sessionId, $userId));

        return in_array($mode, ['empresa', 'procedimientos'], true) ? $mode : null;
    }

    private function getConversationModeKey(?string $sessionId, ?string $userId): string
    {
        return 'chat_conversation_mode_' . ($sessionId ?: ('u_' . ($userId ?: 'guest')));
    }

    /**
     * Seguimiento corto con documento en foco: priorizar IA, no organigrama.
     */
    private function isShortDocumentFollowUp(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if ($q === '' || mb_strlen($q) > 80) {
            return false;
        }

        if (
            $this->wantsCompanyAreasList($q)
            || $this->wantsCompanyDirectorsList($q)
            || $this->wantsPeopleInAreaQuery($q)
            || $this->wantsEmployeeByNameQuery($q)
        ) {
            return false;
        }

        return (bool) preg_match(
            '/^(dame\s+|lista\s+(de\s+)?|los?\s+|las?\s+|y\s+|entonces\s+|ok\s+pero\s+|pero\s+)?'
            . '(puestos?|áreas?|areas?|responsable|responsables|objetivo|alcance|'
            . 'riesgos?|definiciones?|pasos|actividades|unidades?'
            . '|qui[eé]n\s+(ser[ií]a\s+)?(el\s+)?responsables?'
            . '|quien\s+(seria\s+)?(el\s+)?responsables?'
            . '|a\s+qui[eé]n(\s+se\s+la)?(\s+envi|\s+mand))'
            . '(\s+vinculados?)?(\s+de\s+(este|ese|el)\s+(documento|procedimiento))?\s*\??$/u',
            $q
        ) || $this->isElementoAreaPuestoMetaQuery($q);
    }

    private function looksLikeSgcDocumentAsk(string $cleanQuery, string $searchQuery): bool
    {
        $q = mb_strtolower($cleanQuery . ' ' . $searchQuery);

        return (bool) preg_match(
            '/\b(procedimiento|documento|folio|pol[ií]tica|lineamiento|'
            . 'objetivo|alcance|riesgos?|mis procedimientos)\b/u',
            $q
        );
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
        \Cache::forget($this->getConversationModeKey($sessionId, $userId));
        \Cache::forget($this->getTalkTopicKey($sessionId));
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

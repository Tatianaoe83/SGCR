<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WordDocument;
use App\Models\Elemento;
use App\Models\UnidadNegocio;
use App\Models\PuestoTrabajo;
use App\Models\Area;
use App\Models\Empleados;
use App\Models\Relaciones;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Servicio para modelos de IA de pago (OpenAI, Anthropic, Google)
 * Soporta GPT-4 Turbo, Claude 3 Sonnet, Gemini Pro 1.5
 */
class PaidAIService
{
    private $provider;
    private $apiKey;
    private $model;
    private $baseUrl;
    private $timeout;

    public function __construct()
    {
        $this->provider = config('services.ai.provider', 'openai'); // openai, anthropic, google
        $this->apiKey = config('services.ai.api_key');
        $this->model = config('services.ai.model');
        $this->timeout = config('services.ai.timeout', 30);

        // URLs base por proveedor
        $baseUrls = [
            'openai' => 'https://api.openai.com/v1/',
            'anthropic' => 'https://api.anthropic.com/v1/',
            'google' => 'https://generativelanguage.googleapis.com/v1beta/',
        ];

        $this->baseUrl = $baseUrls[$this->provider] ?? $baseUrls['openai'];
    }

    /**
     * Generar respuesta usando el modelo de IA configurado
     * Se agrega el parámetro $history = [] para la memoria conversacional
     */
    public function generateResponse($query, $context = null, $timeout = null, $history = [], $elemento = null)
    {
        $requestTimeout = $timeout ?? $this->timeout;

        try {
            return match ($this->provider) {
                // Pasamos $elemento a las funciones específicas
                'openai' => $this->generateOpenAIResponse($query, $context, $requestTimeout, $history, $elemento),
                'anthropic' => $this->generateAnthropicResponse($query, $context, $requestTimeout, $history),
                'google' => $this->generateGoogleResponse($query, $context, $requestTimeout, $history),
                default => throw new \Exception("Proveedor no soportado")
            };
        } catch (\Exception $e) {
            Log::error("Error PaidAIService: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar respuesta usando OpenAI GPT-4 nano
     * CORREGIDA: Ahora recibe $elemento y lo pasa a buildPrompt.
     */
    private function generateOpenAIResponse($query, $context, $timeout, $history, $elemento)
    {
        // =========================
        // 1. Construcción del prompt (Pasando el elemento)
        // ========================
        $prompt = $this->buildPrompt($query, $context, $history, $elemento);

        // =========================
        // 2. DEBUG CRÍTICO DE TAMAÑOS
        // =========================
        logger()->error('PROMPT DEBUG (ANTES DE OPENAI)', [
            'query_chars'   => mb_strlen((string) $query),
            'context_chars' => mb_strlen((string) $context),
            'history_chars' => is_string($history)
                ? mb_strlen($history)
                : mb_strlen(json_encode($history)),
            'prompt_chars'  => mb_strlen($prompt),
            'elemento_id'   => $elemento ? $elemento->id : 'NULL' // Verificamos si llegó el elemento
        ]);

        // =========================
        // 3. MENSAJES PARA OPENAI
        // =========================
        $messages = [
            [
                'role' => 'system',
                // Las instrucciones de tono generales
                'content' => $this->buildToneInstruction(),
            ],
            [
                'role' => 'user',
                // El documento + datos oficiales + pregunta (YA DELIMITADO por buildPrompt)
                'content' => $prompt,
            ],
        ];

        // =========================
        // 4. DEBUG FINAL (LO QUE REALMENTE SE ENVÍA)
        // =========================
        logger()->error('OPENAI MESSAGES DEBUG', [
            'total_chars' => mb_strlen(json_encode($messages)),
            'messages' => array_map(
                fn($m) => [
                    'role'  => $m['role'],
                    'chars' => mb_strlen($m['content']),
                ],
                $messages
            ),
        ]);

        // =========================
        // 5. LLAMADA A OPENAI
        // =========================
        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post($this->baseUrl . 'chat/completions', [
                'model'       => $this->model ?? 'gpt-4.1-nano-2025-04-14',
                'messages'    => $messages,
                'temperature' => 0.65,
                'max_tokens'  => 1400,
            ]);

        // =========================
        // 6. RESPUESTA EXITOSA
        // =========================
        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content']
                ?? 'No pude generar una respuesta apropiada.';
        }

        // =========================
        // 7. ERROR
        // =========================
        Log::error('❌ OpenAI API error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new \Exception('Error en la API de OpenAI: ' . $response->status());
    }


    /**
     * Generar respuesta usando Anthropic Claude 3 Sonnet
     */
    private function generateAnthropicResponse($query, $context, $timeout, $history) // <--- Agregado $history
    {
        // Pasamos $history al buildPrompt
        $prompt = $this->buildPrompt($query, $context, $history);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . 'messages', [
                'model' => $this->model ?? 'claude-3-sonnet-20240229',
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'system' => 'Eres un asistente virtual experto. Responde siempre en español de manera clara, profesional y empática.'
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['content'][0]['text'] ?? 'No pude generar una respuesta apropiada.';
        }

        Log::error('Anthropic API error: ' . $response->status() . ' - ' . $response->body());
        throw new \Exception('Error en la API de Anthropic: ' . $response->status());
    }


    /**
     * Generar respuesta usando Google Gemini Pro 1.5
     */
    private function generateGoogleResponse($query, $context, $timeout, $history) // <--- Agregado $history
    {
        // Pasamos $history al buildPrompt
        $prompt = $this->buildPrompt($query, $context, $history);

        $response = Http::timeout($timeout)
            ->post($this->baseUrl . 'models/' . ($this->model ?? 'gemini-pro') . ':generateContent?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                ],
                'systemInstruction' => [
                    'parts' => [
                        [
                            'text' => 'Eres un asistente virtual experto. Responde siempre en español de manera clara, profesional y empática.'
                        ]
                    ]
                ]
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No pude generar una respuesta apropiada.';
        }

        Log::error('Google API error: ' . $response->status() . ' - ' . $response->body());
        throw new \Exception('Error en la API de Google: ' . $response->status());
    }



    private function buildPrompt($query, $context = null, $history = [], $elemento = null)
    {
        $MAX_CONTEXT_CHARS = 8000;
        $MAX_HISTORY_CHARS = 2500;

        // R — ROL BASE (se complementa con buildToneInstruction)
        $systemPrompt = "Estás atendiendo una consulta dentro del Sistema de Gestión de Calidad.\n";
        $systemPrompt .= "Tu única fuente de verdad es la información que se te proporciona abajo. No uses conocimiento externo.\n";
        $systemPrompt .= "Si la pregunta es un seguimiento corto, úsala junto con el historial y el documento en foco.\n\n";

        // C — CONTEXTO: Catálogo global (cuando aplica)
        $keywordsInventario = [
            'que documentos',
            'cuales documentos',
            'cuantos documentos',
            'cuántos documentos',
            'lista de',
            'listado',
            'dame el listado',
            'dame la lista',
            'pasame la lista',
            'pásame la lista',
            'muestrame los',
            'muéstrame los',
            'muestrame las',
            'muéstrame las',
            'que hay',
            'qué hay',
            'cuales tengo',
            'cuáles tengo',
            'tienes disponibles',
            'inventario',
            'listar',
            'enumera',
            'enumerar',
            'cuales hay',
            'cuáles hay',
            'cuantos hay',
            'cuántos hay',
            'procedimientos tengo',
            'cuantos procedimientos',
            'cuántos procedimientos',
            'todos los procedimientos',
            'todos los documentos',
            'que procedimientos',
            'qué procedimientos',
            'cuales procedimientos',
            'cuáles procedimientos',
            'que elementos',
            'cuales elementos',
            'cuantos elementos',
            'cuales reglamentos',
            'cuantos reglamentos',
            'que reglamentos',
            'cual reglamento',
            'que reglamento',
            'que politicas',
            'cuales politicas',
            'cuantas politicas',
            'que politica',
            'cual politica',
            'del area',
            'del área',
            'area de',
            'área de',
            'procedimientos de',
            'procedimientos del',
            'de ti',
            'de calidad',
            'quiero una lista',
            'necesito una lista',
        ];

        // Sólo es pregunta de inventario si NO estamos respondiendo sobre un documento
        // concreto. "que documentos de referencia tiene el elemento X" cae en la lista de
        // arriba pero es una pregunta de CONTENIDO: inyectarle el catálogo global le tapaba
        // a la IA el contexto real del documento.
        // Nota: HybridChatbotService ya resuelve catálogo/área con generateCatalogBrowseResponse;
        // esto queda como respaldo cuando no hay elemento forzado.
        if (!$elemento && Str::contains(Str::lower($query), $keywordsInventario)) {
            // El catálogo vive en `elementos`, no en `word_documents` (esa tabla sólo tiene
            // id, elemento_id, contenido_texto, contenido_estructurado, estado). La consulta
            // anterior apuntaba a la tabla equivocada y lanzaba SQLSTATE[42S22] en CADA
            // pregunta de inventario, tumbando la llamada a la IA completa.
            $catalogoDocs = Elemento::where('status', 'Publicado')
                ->where('active', true)
                ->select('id_elemento', 'folio_elemento', 'nombre_elemento', 'version_elemento')
                ->limit(50)
                ->get();

            if ($catalogoDocs->isNotEmpty()) {
                $listaTexto = $catalogoDocs->map(
                    fn($d) =>
                    "- {$d->folio_elemento}: {$d->nombre_elemento} (v{$d->version_elemento})"
                )->implode("\n");

                $systemPrompt .= "╔══ CONTEXTO: INVENTARIO REAL DEL SISTEMA ══╗\n";
                $systemPrompt .= $listaTexto . "\n";
                $systemPrompt .= "╚════════════════════════════════════════════╝\n";
                $systemPrompt .= "TAREA: Usa ÚNICAMENTE esta lista para responder. No añadas ni inventes documentos.\n\n";
            }
        }

        // C — CONTEXTO: Ficha enriquecida del elemento (BD: unidades, padres, puestos, áreas, empleados)
        if ($elemento) {
            $systemPrompt .= $this->buildRichElementoFicha($elemento, $query);
            $systemPrompt .= "COMO USAR LA FICHA:\n";
            $systemPrompt .= "- Es fuente oficial de metadatos (unidades, puestos, empleados, padres, relacionados, fechas).\n";
            $systemPrompt .= "- El elemento NO tiene áreas propias. NO inventes ni listes un catálogo de áreas (Administración, Calidad, etc.) como si fueran del procedimiento.\n";
            $systemPrompt .= "- Si preguntan si se involucran áreas: explica que en BD hay puestos vinculados (responsable + relacionados), no un campo de áreas; ofrece filtrar por un área concreta.\n";
            $systemPrompt .= "- Si preguntan puestos de un área (ej. Calidad), usa SOLO la sección 'Puestos del área pedida' o nombres de puestos que contengan esa área. Si está vacía, dilo: no hay puestos de esa área en la lista vinculada.\n";
            $systemPrompt .= "- No copies toda la ficha de golpe: responde solo lo que preguntaron, en tono de chat.\n";
            $systemPrompt .= "- La interfaz ya muestra una tarjeta breve; no pegues enlaces al archivo.\n\n";
        }

        // C — CONTEXTO: Chunks del documento (RAG)
        if (!empty($context)) {
            $safeContext = mb_substr(trim($context), 0, $MAX_CONTEXT_CHARS);

            $systemPrompt .= "╔══ CONTEXTO: CONTENIDO DEL DOCUMENTO (RAG) ══╗\n";
            $systemPrompt .= $safeContext . "\n";
            $systemPrompt .= "╚══════════════════════════════════════════════╝\n\n";

            $systemPrompt .= "TAREA PARA EL CONTENIDO:\n";
            $systemPrompt .= "- Para objetivo, alcance, riesgos, definiciones, pasos y texto del procedimiento: busca en el CONTENIDO del documento.\n";
            $systemPrompt .= "- Para metadatos (puestos, unidades, áreas, empleados, padres, relacionados, folio, versión, fechas): usa la FICHA de arriba.\n";
            $systemPrompt .= "- Para definiciones, localiza secciones como 'DEFINICIONES' o 'GLOSARIO' y cítalas tal cual.\n";
            $systemPrompt .= "- Si la respuesta NO está ni en la ficha ni en el contenido, responde EXACTAMENTE con esta única línea y nada más: [[SIN_INFO]]\n";
            $systemPrompt .= "- Usa [[SIN_INFO]] sólo si de verdad revisaste ficha + contenido y no está. No inventes.\n\n";
        }

        // C — CONTEXTO: Historial de conversación (últimos 6 mensajes)
        if (!empty($history)) {
            $historyBlock = '';
            foreach (array_slice($history, -6) as $msg) {
                $role = ($msg['role'] === 'user') ? 'USUARIO' : 'ASISTENTE';
                $historyBlock .= $role . ': ' . strip_tags($msg['content']) . "\n";
            }
            $historyBlock = mb_substr($historyBlock, 0, $MAX_HISTORY_CHARS);

            $systemPrompt .= "╔══ CONTEXTO: HISTORIAL RECIENTE ══╗\n";
            $systemPrompt .= $historyBlock;
            $systemPrompt .= "╚══════════════════════════════════╝\n\n";
        }

        // T — TAREA FINAL: La pregunta concreta del usuario
        $systemPrompt .= "══ CONSULTA ACTUAL ══\n";
        $systemPrompt .= $query . "\n\n";

        $systemPrompt .= "Responde a esa consulta. Habla natural, como en un chat. Si pide listar o enumerar, usa viñetas.\n\n";

        return $systemPrompt;
    }

    // ══════════════════════════════════════════════════════════
    // Método auxiliar para limpiar la lógica de URL del elemento
    // (lo que antes estaba hardcodeado dentro de buildPrompt)
    // ══════════════════════════════════════════════════════════
    public function resolveDocumentUrl($elemento): string
    {
        if (empty($elemento->archivo_actual_url)) return '';

        $raw = $elemento->archivo_actual_url;

        if (preg_match('#^https?://#i', $raw)) {
            $parts    = explode('/', $raw);
            $fileName = array_pop($parts);
            $parts[]  = rawurlencode(rawurldecode($fileName));
            return implode('/', $parts);
        }

        $path      = preg_replace('#^/?storage/#', '', ltrim($raw, '/'));
        $pathParts = explode('/', $path);
        $fileName  = array_pop($pathParts);
        $pathParts[] = rawurlencode(rawurldecode($fileName));
        return url('storage/' . implode('/', $pathParts));
    }



    /**
     * Verificar si el servicio está disponible
     */
    public function healthCheck()
    {
        try {
            if (empty($this->apiKey)) {
                return 'no_config';
            }

            // Hacer una petición simple para verificar conectividad
            return match ($this->provider) {
                'openai' => $this->checkOpenAIHealth(),
                'anthropic' => $this->checkAnthropicHealth(),
                'google' => $this->checkGoogleHealth(),
                default => 'error'
            };
        } catch (\Exception $e) {
            Log::error('Health check error: ' . $e->getMessage());
            return 'offline';
        }
    }

    private function checkOpenAIHealth()
    {
        $response = Http::timeout(5)
            ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
            ->get($this->baseUrl . 'models');

        return $response->successful() ? 'ok' : 'error';
    }

    private function checkAnthropicHealth()
    {
        // Anthropic no tiene endpoint de health simple, intentamos con un mensaje mínimo
        $response = Http::timeout(5)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . 'messages', [
                'model' => $this->model ?? 'claude-3-sonnet-20240229',
                'max_tokens' => 10,
                'messages' => [['role' => 'user', 'content' => 'test']]
            ]);

        return ($response->successful() || $response->status() === 400) ? 'ok' : 'error';
    }

    private function checkGoogleHealth()
    {
        $response = Http::timeout(5)
            ->get($this->baseUrl . 'models?key=' . $this->apiKey);

        return $response->successful() ? 'ok' : 'error';
    }

    /**
     * Obtener información del proveedor actual
     */
    public function getProviderInfo()
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'status' => $this->healthCheck()
        ];
    }



    /**
     * VERSIÓN DEBUG: Generar respuesta RAW
     */
    public function generateRawResponse($systemInstruction, $userPrompt, $timeout = 10)
    {
        // 1. OpenAI
        if ($this->provider === 'openai') {
            $response = Http::timeout($timeout)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey, 'Content-Type' => 'application/json'])
                ->post($this->baseUrl . 'chat/completions', [
                    'model' => $this->model ?? 'gpt-3.5-turbo',
                    'messages' => [['role' => 'system', 'content' => $systemInstruction], ['role' => 'user', 'content' => $userPrompt]],
                    'temperature' => 0,
                ]);
            if ($response->successful()) return $response->json()['choices'][0]['message']['content'] ?? $userPrompt;
        }

        // 2. Anthropic
        if ($this->provider === 'anthropic') {
            $response = Http::timeout($timeout)
                ->withHeaders(['x-api-key' => $this->apiKey, 'anthropic-version' => '2023-06-01', 'Content-Type' => 'application/json'])
                ->post($this->baseUrl . 'messages', [
                    'model' => $this->model ?? 'claude-3-sonnet-20240229',
                    'max_tokens' => 200,
                    'temperature' => 0,
                    'system' => $systemInstruction,
                    'messages' => [['role' => 'user', 'content' => $userPrompt]]
                ]);
            if ($response->successful()) return $response->json()['content'][0]['text'] ?? $userPrompt;
        }

        // 3. GOOGLE (GEMINI) - CON DIAGNÓSTICO
        if ($this->provider === 'google') {
            $response = Http::timeout($timeout)
                ->post($this->baseUrl . 'models/' . ($this->model ?? 'gemini-pro') . ':generateContent?key=' . $this->apiKey, [
                    'contents' => [['parts' => [['text' => $systemInstruction . "\n\n" . $userPrompt]]]],
                    'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 200]
                ]);

            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? $userPrompt;
            } else {
                // DEBUG: ¡AQUÍ ESTÁ EL ERROR! Guardamos qué respondió Google
                Log::error("❌ ERROR GOOGLE RAW: " . $response->status() . " - " . $response->body());
            }
        } else {
            // DEBUG: Si no entró al IF de google
            if ($this->provider !== 'openai' && $this->provider !== 'anthropic') {
                Log::warning("⚠️ PROVIDER DESCONOCIDO: '" . $this->provider . "'");
            }
        }

        return $userPrompt;
    }

    /**
     * Ficha completa del elemento desde BD: unidades (JSON), padres, relacionados,
     * puestos, áreas, empleados y datos administrativos. No depende de BelongsTo
     * sobre campos array (unidad_negocio_id, puestos_relacionados, etc.).
     */
    public function buildRichElementoFicha($elemento, ?string $query = null): string
    {
        if (!$elemento) {
            return '';
        }

        // Asegurar relaciones BelongsTo simples (no-array).
        $elemento->loadMissing([
            'tipoElemento:id_tipo_elemento,nombre',
            'tipoProceso:id_tipo_proceso,nombre',
            'puestoResponsable:id_puesto_trabajo,nombre,areas_ids,unidad_negocio_id',
            'puestoEjecutor:id_puesto_trabajo,nombre,areas_ids,unidad_negocio_id',
            'puestoResguardo:id_puesto_trabajo,nombre,areas_ids,unidad_negocio_id',
            'elementoPadre:id_elemento,nombre_elemento,folio_elemento,version_elemento',
            'elementosHijos:id_elemento,nombre_elemento,folio_elemento,version_elemento,elemento_padre_id',
            'relaciones:relacionID,nombreRelacion,puestos_trabajo,elementoID',
        ]);

        $meta = $this->resolveElementoRelatedData($elemento);

        $lines = [];
        $lines[] = '╔══ FICHA ENRIQUECIDA DEL ELEMENTO (BD) ══╗';
        $lines[] = '- ID: ' . ($elemento->getKey() ?? 'N/A');
        $lines[] = '- Nombre: ' . ($elemento->nombre_elemento ?? 'No disponible');
        $lines[] = '- Folio / Versión: ' . ($elemento->folio_elemento ?? 'N/A') . ' (v' . ($elemento->version_elemento ?? '?') . ')';
        $lines[] = '- Tipo: ' . (optional($elemento->tipoElemento)->nombre ?? 'No especificado');
        $lines[] = '- Tipo de proceso: ' . (optional($elemento->tipoProceso)->nombre ?? 'N/A');
        $lines[] = '- Status: ' . ($elemento->status ?? 'N/A');
        $lines[] = '- Control: ' . ($elemento->control ?? 'N/A');
        $lines[] = '- Medio de soporte: ' . ($elemento->medio_soporte ?? 'N/A');
        $lines[] = '- Ubicación de resguardo: ' . ($elemento->ubicacion_resguardo ?? 'N/A');
        $lines[] = '- Fecha del elemento: ' . $this->formatDateField($elemento->fecha_elemento);
        $lines[] = '- Periodo de revisión: ' . $this->formatDateField($elemento->periodo_revision);
        $lines[] = '- Periodo de resguardo: ' . $this->formatDateField($elemento->periodo_resguardo);

        $lines[] = '- Unidades de negocio: ' . ($meta['unidades']->isEmpty()
            ? 'No asignadas'
            : $meta['unidades']->pluck('nombre')->implode(', '));

        // Los elementos no tienen campo de áreas. No volcar áreas_ids de puestos:
        // eso hacía que la IA listara Administración, Calidad, etc. como del procedimiento.
        $lines[] = '- Áreas del elemento: NO existen. Solo hay puestos vinculados. NO inventar ni listar un catálogo de áreas.';

        $lines[] = '- Puesto responsable: ' . (optional($elemento->puestoResponsable)->nombre ?? 'No asignado') . '  <- FUENTE OFICIAL';
        $lines[] = '- Puesto ejecutor: ' . (optional($elemento->puestoEjecutor)->nombre ?? 'No asignado');
        $lines[] = '- Puesto de resguardo: ' . (optional($elemento->puestoResguardo)->nombre ?? 'No asignado');

        $lines[] = '- Puestos relacionados (puestos_relacionados): ' . ($meta['puestos_relacionados']->isEmpty()
            ? 'Ninguno'
            : $meta['puestos_relacionados']->pluck('nombre')->implode(', '));

        $areaPedida = $this->detectAreaMentionInQuery($query, $meta['puestos_por_area'] ?? collect());
        if ($areaPedida) {
            $porNombre = $this->puestosVinculadosMatchingAreaName($elemento, $meta, $areaPedida);
            if (empty($porNombre)) {
                $lines[] = '- Puestos del área pedida (' . $areaPedida . '): Ninguno en responsable/relacionados. Di eso con claridad.';
            } else {
                $lines[] = '- Puestos del área pedida (' . $areaPedida . '): ' . implode(', ', $porNombre);
            }
        }

        if ($meta['padres']->isNotEmpty()) {
            $lines[] = '- Elementos padre:';
            foreach ($meta['padres'] as $p) {
                $lines[] = '  • ' . ($p->folio_elemento ?: 's/folio') . ' — ' . $p->nombre_elemento
                    . ' (v' . ($p->version_elemento ?? '?') . ')';
            }
        } else {
            $lines[] = '- Elementos padre: Ninguno';
        }

        if ($meta['relacionados']->isNotEmpty()) {
            $lines[] = '- Elementos relacionados:';
            foreach ($meta['relacionados'] as $r) {
                $lines[] = '  • ' . ($r->folio_elemento ?: 's/folio') . ' — ' . $r->nombre_elemento
                    . ' (v' . ($r->version_elemento ?? '?') . ')';
            }
        } else {
            $lines[] = '- Elementos relacionados: Ninguno';
        }

        if ($meta['hijos']->isNotEmpty()) {
            $lines[] = '- Elementos hijos:';
            foreach ($meta['hijos']->take(15) as $h) {
                $lines[] = '  • ' . ($h->folio_elemento ?: 's/folio') . ' — ' . $h->nombre_elemento
                    . ' (v' . ($h->version_elemento ?? '?') . ')';
            }
        }

        if ($meta['comites']->isNotEmpty()) {
            $lines[] = '- Comités / relaciones de puestos:';
            foreach ($meta['comites'] as $comite) {
                $lines[] = '  • ' . $comite['nombre'] . ': ' . ($comite['puestos'] ?: 'sin puestos');
            }
        }

        if ($meta['empleados']->isNotEmpty()) {
            $lines[] = '- Empleados vinculados a los puestos del elemento (muestra):';
            foreach ($meta['empleados'] as $empLine) {
                $lines[] = '  • ' . $empLine;
            }
        } else {
            $lines[] = '- Empleados vinculados: No hay empleados activos asociados a esos puestos';
        }

        $lines[] = '╚══════════════════════════════════════════════╝';
        $lines[] = '';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Resuelve IDs en arrays JSON del elemento hacia modelos legibles.
     */
    public function resolveElementoRelatedData($elemento): array
    {
        $unidadIds = $this->normalizeIdList($elemento->unidad_negocio_id ?? []);
        $unidades = empty($unidadIds)
            ? collect()
            : UnidadNegocio::whereIn('id_unidad_negocio', $unidadIds)->get(['id_unidad_negocio', 'nombre']);

        $puestoRelIds = $this->normalizeIdList($elemento->puestos_relacionados ?? []);
        $puestosRelacionados = empty($puestoRelIds)
            ? collect()
            : PuestoTrabajo::whereIn('id_puesto_trabajo', $puestoRelIds)
                ->get(['id_puesto_trabajo', 'nombre', 'areas_ids', 'unidad_negocio_id']);

        $padreIds = $this->normalizeIdList($elemento->elementos_padre_id ?? []);
        if (!empty($elemento->elemento_padre_id)) {
            $padreIds[] = (int) $elemento->elemento_padre_id;
        }
        $padreIds = array_values(array_unique(array_filter($padreIds)));
        $padres = empty($padreIds)
            ? collect()
            : Elemento::whereIn('id_elemento', $padreIds)
                ->get(['id_elemento', 'nombre_elemento', 'folio_elemento', 'version_elemento']);

        $relIds = $this->normalizeIdList($elemento->elemento_relacionado_id ?? []);
        $relacionados = empty($relIds)
            ? collect()
            : Elemento::whereIn('id_elemento', $relIds)
                ->get(['id_elemento', 'nombre_elemento', 'folio_elemento', 'version_elemento']);

        $hijos = $elemento->relationLoaded('elementosHijos')
            ? $elemento->elementosHijos
            : Elemento::where('elemento_padre_id', $elemento->getKey())
                ->get(['id_elemento', 'nombre_elemento', 'folio_elemento', 'version_elemento', 'elemento_padre_id']);

        // Puestos "oficiales" del procedimiento para listas/áreas: responsable + relacionados.
        // (ejecutor/resguardo se usan solo para empleados/ficha de roles, no para áreas).
        $puestosParaAreas = collect([$elemento->puestoResponsable])
            ->filter()
            ->merge($puestosRelacionados)
            ->unique('id_puesto_trabajo')
            ->values();

        $puestosClave = collect([
            $elemento->puestoResponsable,
            $elemento->puestoEjecutor,
            $elemento->puestoResguardo,
        ])->filter()->merge($puestosRelacionados)->unique('id_puesto_trabajo')->values();

        // Índice auxiliar puestos→área (areas_ids). NO es "áreas del procedimiento".
        $areaIds = [];
        $puestosPorArea = [];
        foreach ($puestosParaAreas as $puesto) {
            foreach ($this->normalizeIdList($puesto->areas_ids ?? []) as $aid) {
                $areaIds[] = $aid;
            }
        }
        $areaIds = array_values(array_unique(array_filter($areaIds)));

        $areas = empty($areaIds)
            ? collect()
            : Area::whereIn('id_area', $areaIds)->get(['id_area', 'nombre', 'unidad_negocio_id']);

        $areasById = $areas->keyBy('id_area');
        foreach ($puestosParaAreas as $puesto) {
            foreach ($this->normalizeIdList($puesto->areas_ids ?? []) as $aid) {
                $areaNombre = optional($areasById->get($aid))->nombre;
                if (!$areaNombre) {
                    continue;
                }
                $puestosPorArea[$areaNombre][] = $puesto->nombre;
            }
        }
        foreach ($puestosPorArea as $areaNombre => $lista) {
            $puestosPorArea[$areaNombre] = array_values(array_unique($lista));
        }
        ksort($puestosPorArea, SORT_NATURAL | SORT_FLAG_CASE);

        // Comités (tabla puestos_relacion).
        $comites = collect();
        $relaciones = $elemento->relationLoaded('relaciones')
            ? $elemento->relaciones
            : Relaciones::where('elementoID', $elemento->getKey())->get();

        foreach ($relaciones as $rel) {
            $pIds = $this->normalizeIdList($rel->puestos_trabajo ?? []);
            $pNames = empty($pIds)
                ? ''
                : PuestoTrabajo::whereIn('id_puesto_trabajo', $pIds)->pluck('nombre')->implode(', ');
            $comites->push([
                'nombre' => $rel->nombreRelacion ?: 'Relación',
                'puestos' => $pNames,
            ]);
        }

        // Empleados de puestos del elemento + comités (límite para no inflar el prompt).
        $puestoIdsEmpleados = $puestosClave->pluck('id_puesto_trabajo')->filter()->unique()->values()->all();
        foreach ($relaciones as $rel) {
            $puestoIdsEmpleados = array_merge($puestoIdsEmpleados, $this->normalizeIdList($rel->puestos_trabajo ?? []));
        }
        $puestoIdsEmpleados = array_values(array_unique(array_filter($puestoIdsEmpleados)));

        $empleadosLines = [];
        if (!empty($puestoIdsEmpleados)) {
            $puestosMap = PuestoTrabajo::whereIn('id_puesto_trabajo', $puestoIdsEmpleados)
                ->pluck('nombre', 'id_puesto_trabajo');

            $empleados = Empleados::whereIn('puesto_trabajo_id', $puestoIdsEmpleados)
                ->orderBy('apellido_paterno')
                ->limit(20)
                ->get(['id_empleado', 'nombres', 'apellido_paterno', 'apellido_materno', 'correo', 'puesto_trabajo_id']);

            foreach ($empleados as $emp) {
                $nombre = trim(implode(' ', array_filter([
                    $emp->nombres,
                    $emp->apellido_paterno,
                    $emp->apellido_materno,
                ])));
                $puestoNombre = $puestosMap[$emp->puesto_trabajo_id] ?? 'Puesto';
                $correo = $emp->correo ? " <{$emp->correo}>" : '';
                $empleadosLines[] = "{$nombre}{$correo} — {$puestoNombre}";
            }
        }

        return [
            'unidades' => $unidades,
            'areas' => $areas,
            'puestos_relacionados' => $puestosRelacionados,
            'puestos_por_area' => collect($puestosPorArea),
            'padres' => $padres,
            'relacionados' => $relacionados,
            'hijos' => $hijos instanceof Collection ? $hijos : collect($hijos),
            'comites' => $comites,
            'empleados' => collect($empleadosLines),
        ];
    }

    /**
     * Normaliza acentos para comparar "juridico" ≈ "jurídico".
     */
    public function foldAccents(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ñ' => 'n',
        ]);
    }

    /**
     * Detecta si la pregunta nombra un área. Prioriza "área de X" / "puestos de X"
     * para no confundir con palabras del título del procedimiento (ej. "Proyectos").
     */
    public function detectAreaMentionInQuery(?string $query, $puestosPorArea = null): ?string
    {
        if (!$query) {
            return null;
        }

        $q = mb_strtolower($query);
        $asksArea = (bool) preg_match(
            '/\b(área|area|áreas|areas)\b|puestos?\s+de\s+|del\s+área|'
            . 'de\s+(calidad|jur[ií]dic\w*|ti)\b|tiene\s+de\s+|y\s+de\s+/u',
            $q
        );
        if (!$asksArea && !preg_match('/\b(calidad|jur[ií]dic\w*)\b/u', $q)) {
            return null;
        }

        // 1) Extracción explícita: "área de X", "puestos de X", "tiene de X", "y de X"
        $candidate = null;
        if (preg_match(
            '/(?:del\s+)?(?:área|area)\s+de\s+([a-záéíóúüñ0-9][a-záéíóúüñ0-9\s]{1,40}?)(?:\s+en\s+|\s+del\s+|\s+para\s+|\s*$|[?.!,])/u',
            $q,
            $m
        )) {
            $candidate = trim($m[1]);
        } elseif (preg_match('/puestos?\s+de\s+(?:la\s+)?([a-záéíóúüñ]{3,40})\b/u', $q, $m)) {
            $candidate = trim($m[1]);
        } elseif (preg_match('/(?:tiene|tienen|hay|y)\s+de\s+([a-záéíóúüñ]{3,40})\b/u', $q, $m)) {
            $candidate = trim($m[1]);
        }

        if ($candidate !== null && $candidate !== '') {
            $candidate = preg_replace('/\s+(en|del|de la|para)\b.*$/u', '', $candidate);
            $resolved = $this->resolveAreaNameCandidate($candidate, $puestosPorArea);
            if ($resolved) {
                return $resolved;
            }
        }

        // 2) Alias fuertes (antes de buscar nombres de área sueltos en toda la frase).
        if (preg_match('/\bcalidad\b/u', $q)) {
            return 'Calidad';
        }
        if (preg_match('/\bjur[ií]dic\w*\b/u', $q)) {
            return $this->resolveAreaNameCandidate('juridico', $puestosPorArea) ?: 'Jurídico';
        }
        if (preg_match('/\b(ti|t\.?i\.?)\b/u', $q)
            || preg_match('/tecnolog[ií]as?\s+de\s+la\s+informaci[oó]n/u', $q)
        ) {
            $nombres = collect($puestosPorArea ?? [])->keys()->filter();
            foreach ($nombres as $nombre) {
                if (preg_match('/tecnolog|informaci/u', mb_strtolower((string) $nombre))) {
                    return (string) $nombre;
                }
            }
            return 'Tecnologías de la Información';
        }

        return null;
    }

    /**
     * Resuelve un candidato de área contra el índice del elemento o alias conocidos.
     */
    private function resolveAreaNameCandidate(string $candidate, $puestosPorArea = null): ?string
    {
        $c = $this->foldAccents($candidate);
        if ($c === '' || in_array($c, ['la', 'el', 'los', 'las', 'este', 'esta'], true)) {
            return null;
        }

        $nombres = collect($puestosPorArea ?? [])
            ->keys()
            ->filter()
            ->sortByDesc(fn ($n) => mb_strlen((string) $n))
            ->values();

        foreach ($nombres as $nombre) {
            $n = $this->foldAccents((string) $nombre);
            if ($n === $c || mb_strpos($n, $c) !== false || mb_strpos($c, $n) !== false) {
                return (string) $nombre;
            }
        }

        if ($c === 'ti' || $c === 't.i' || $c === 't.i.') {
            return 'Tecnologías de la Información';
        }
        if (str_starts_with($c, 'jurid')) {
            return 'Jurídico';
        }
        if ($c === 'calidad') {
            return 'Calidad';
        }

        // Usar el texto pedido aunque el área no esté indexada en este elemento.
        return mb_convert_case(mb_strtolower(trim($candidate)), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Puestos del elemento (responsable + relacionados) que coinciden con un área:
     * por nombre del puesto o por areas_ids.
     */
    public function puestosVinculadosMatchingAreaName($elemento, array $meta, string $areaNombre): array
    {
        $areaFold = $this->foldAccents($areaNombre);
        // "juridico" también debe pegar a "jurídica" / "Director Jurídico…"
        $areaStem = preg_replace('/(o|a|os|as)$/u', '', $areaFold) ?: $areaFold;

        $porNombre = [];
        $porAreasIds = [];

        $relacionados = $meta['puestos_relacionados'] ?? collect();
        $candidatos = collect([optional($elemento)->puestoResponsable])
            ->filter()
            ->merge($relacionados instanceof \Illuminate\Support\Collection ? $relacionados : collect())
            ->unique('id_puesto_trabajo');

        // 1) Nombre del puesto contiene el área (más fiable para el usuario).
        foreach ($candidatos as $puesto) {
            $nombre = (string) ($puesto->nombre ?? '');
            $nombreFold = $this->foldAccents($nombre);
            if ($nombre !== '' && (
                mb_strpos($nombreFold, $areaFold) !== false
                || ($areaStem !== '' && mb_strpos($nombreFold, $areaStem) !== false)
            )) {
                $porNombre[] = $nombre;
            }
        }

        // 2) areas_ids del puesto (dato organizacional; a veces ruidoso).
        $porArea = $meta['puestos_por_area'] ?? collect();
        if ($porArea instanceof \Illuminate\Support\Collection) {
            foreach ($porArea as $nombreArea => $puestos) {
                $na = $this->foldAccents((string) $nombreArea);
                if ($na === $areaFold
                    || mb_strpos($na, $areaFold) !== false
                    || ($areaStem !== '' && mb_strpos($na, $areaStem) !== false)
                ) {
                    foreach ((array) $puestos as $p) {
                        $porAreasIds[] = $p;
                    }
                }
            }
        }

        // Si hay coincidencia por nombre, priorizarla (evita "Coordinador de Proyectos"
        // solo porque tiene Calidad en areas_ids).
        if (!empty($porNombre)) {
            return array_values(array_unique($porNombre));
        }

        return array_values(array_unique($porAreasIds));
    }

    private function normalizeIdList($value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = preg_split('/\s*,\s*/', trim($value, "[] \t\n\r")) ?: [];
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(array_unique(array_filter(array_map(static function ($id) {
            if (is_array($id)) {
                return null;
            }
            $id = is_numeric($id) ? (int) $id : null;
            return $id && $id > 0 ? $id : null;
        }, $value))));
    }

    private function formatDateField($value): string
    {
        if (empty($value)) {
            return 'N/A';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    private function buildToneInstruction()
    {
        return "Eres Bob, el asistente del Sistema de Gestión de Calidad de Proser. Ayudas a consultar procedimientos, lineamientos y documentos del SGC."

            . "\n\nTONO:"
            . "\n- Habla en español, de tú, como en un chat cercano y claro."
            . "\n- Sé amable y natural; puedes usar un lenguaje cotidiano sin sonar robótico ni de informe formal."
            . "\n- Responde directo a lo que preguntaron. Si hace falta, una frase breve de contexto está bien."

            . "\n\nFORMATO:"
            . "\n- Si piden lista, pasos, riesgos, responsables, definiciones o varios puntos, usa viñetas (-) o números. Una idea por línea."
            . "\n- Si la duda es corta (objetivo, responsable, una definición), responde en 1–3 frases; no fuerces listas."
            . "\n- Usa **negritas** para resaltar conceptos clave, sin abusar."
            . "\n- Evita párrafos largos sin saltos de línea."

            . "\n\nCONTENIDO:"
            . "\n- Basa la respuesta solo en la información que te pasan (documento RAG, ficha enriquecida, inventario)."
            . "\n- Metadatos (responsable, puestos, unidades, empleados, padres, relacionados, fechas): prioriza la ficha."
            . "\n- No listes áreas del procedimiento: los elementos no tienen áreas; solo puestos vinculados."
            . "\n- Texto del procedimiento (objetivo, alcance, riesgos, definiciones, actividades): prioriza el contenido RAG."
            . "\n- Para definiciones, busca en DEFINICIONES o GLOSARIO y cítalas tal cual."
            . "\n- No inventes datos del SGC. Si no está en el contexto, no lo supongas."

            . "\n\nCONVERSACIÓN:"
            . "\n- Interpreta seguimientos cortos con el historial y el documento en foco (\"y eso?\", \"quiénes lo firman\", \"qué unidades aplica\", \"quién es el encargado\")."
            . "\n- No vuelques toda la ficha si no la pidieron; responde lo preguntado."
            . "\n- No pegues enlaces al documento; la interfaz ya pone el botón."

            . "\n\nEjemplo (objetivo):"
            . "\n---"
            . "\nEl objetivo de **Prospectar** es identificar, atraer y evaluar prospectos alineados con el Perfil de Cliente PROSER, para optimizar recursos comerciales y subir la probabilidad de contratación."
            . "\n---"
            . "\nEjemplo (lista de actividades):"
            . "\n---"
            . "\nLas actividades del **Director de Desarrollo de Negocios** son:"
            . "\n\n- Identificar proyectos en ejecución o por ejecutar en los que PROSER pueda participar."
            . "\n- Evaluar cada lead conforme al Perfil de Cliente PROSER y clasificarlo."
            . "\n- Registrar el seguimiento comercial de los prospectos."
            . "\n---";
    }
}

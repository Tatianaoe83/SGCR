<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WordDocument;
use App\Models\Elemento;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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

        // C — CONTEXTO: Datos oficiales del elemento seleccionado
        if ($elemento) {
            $nombre  = $elemento->nombre_elemento         ?? 'No disponible';
            $folio   = $elemento->folio_elemento          ?? 'No disponible';
            $version = $elemento->version_elemento        ?? 'N/A';
            $tipo    = optional($elemento->tipoElemento)->nombre  ?? 'No especificado';
            $proceso = optional($elemento->tipoProceso)->nombre   ?? 'General';
            $unidad  = optional($elemento->unidadNegocio)->nombre ?? 'No especificada';
            $puesto  = optional($elemento->puestoResponsable)->nombre ?? 'No asignado';

            $systemPrompt .= "╔══ FICHA DEL ELEMENTO (referencia interna) ══╗\n";
            $systemPrompt .= "- Nombre:             $nombre\n";
            $systemPrompt .= "- Folio / Versión:    $folio (v$version)\n";
            $systemPrompt .= "- Tipo / Proceso:     $tipo / $proceso\n";
            $systemPrompt .= "- Unidad de Negocio:  $unidad\n";
            $systemPrompt .= "- Puesto Responsable: $puesto  <- FUENTE OFICIAL. Tiene prioridad sobre el documento.\n";
            $systemPrompt .= "╚═════════════════════════════════════════════╝\n\n";

            // La ficha y el enlace ya los pinta la interfaz debajo del mensaje.
            $systemPrompt .= "COMO USAR LA FICHA:\n";
            $systemPrompt .= "- NO la copies ni la listes en tu respuesta. La interfaz ya la muestra aparte.\n";
            $systemPrompt .= "- Es solo tu referencia: úsala si la pregunta toca esos campos (responsable, versión, folio, unidad).\n";
            $systemPrompt .= "- Si preguntan por el responsable, cita el Puesto Responsable dentro de una frase normal.\n";
            $systemPrompt .= "- No pegues enlaces al documento. La interfaz ya pone el botón.\n\n";
        }

        // C — CONTEXTO: Chunks del documento (RAG)
        if (!empty($context)) {
            $safeContext = mb_substr(trim($context), 0, $MAX_CONTEXT_CHARS);

            $systemPrompt .= "╔══ CONTEXTO: CONTENIDO DEL DOCUMENTO (RAG) ══╗\n";
            $systemPrompt .= $safeContext . "\n";
            $systemPrompt .= "╚══════════════════════════════════════════════╝\n\n";

            $systemPrompt .= "TAREA PARA EL CONTENIDO:\n";
            $systemPrompt .= "- Busca la respuesta dentro del contenido del documento.\n";
            $systemPrompt .= "- Para definiciones, localiza secciones como 'DEFINICIONES' o 'GLOSARIO'.\n";
            $systemPrompt .= "- Si el término aparece definido explícitamente, úsalo tal cual.\n";
            $systemPrompt .= "- Si la respuesta NO está en el contenido de arriba, no expliques ni sugieras nada: responde EXACTAMENTE con esta única línea y nada más: [[SIN_INFO]]\n";
            $systemPrompt .= "- Usa [[SIN_INFO]] sólo si de verdad revisaste todo el contenido y no está. No inventes.\n\n";
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
            . "\n- Basa la respuesta solo en la información que te pasan (documento, ficha, inventario)."
            . "\n- Si preguntan por el responsable, usa el Puesto Responsable de la ficha (prevalece sobre el texto del documento)."
            . "\n- Para definiciones, busca en DEFINICIONES o GLOSARIO y cítalas tal cual."
            . "\n- No inventes datos del SGC. Si no está en el contexto, no lo supongas."

            . "\n\nCONVERSACIÓN:"
            . "\n- Interpreta seguimientos cortos con el historial y el documento en foco (\"y eso?\", \"dame la lista\", \"quién es el encargado\", \"y los riesgos?\")."
            . "\n- No abras listando Nombre, Folio, Versión, Tipo, Unidad ni Responsable: la ficha ya aparece en la interfaz."
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

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WordDocument;
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
                'temperature' => 0.3,
                'max_tokens'  => 800,   // Suficiente para respuestas claras
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
        $MAX_CONTEXT_CHARS = 6000;
        $MAX_HISTORY_CHARS = 600;

        // R — ROL BASE (se complementa con buildToneInstruction)
        $systemPrompt = "Estás atendiendo una consulta dentro del Sistema de Gestión de Calidad.\n";
        $systemPrompt .= "Tu única fuente de verdad es la información que se te proporciona abajo. No uses conocimiento externo.\n\n";

        // C — CONTEXTO: Catálogo global (cuando aplica)
        $keywordsInventario = [
            'que documentos',
            'cuales documentos',
            'cuantos documentos',
            'cuántos documentos',
            'lista de',
            'tienes disponibles',
            'inventario',
            'listar',
            'cuales hay',
            'cuantos hay',
            'cuántos hay',
            'procedimientos tengo',
            'cuantos procedimientos',
            'cuántos procedimientos',
            'todos los procedimientos',
            'todos los documentos',
            'que procedimientos',
            'cuales procedimientos',
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
        ];

        if (Str::contains(Str::lower($query), $keywordsInventario)) {
            $catalogoDocs = WordDocument::where('status', 'active')
                ->select('id', 'folio_elemento', 'nombre_elemento', 'version_elemento')
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
            $urlDocumento = $this->resolveDocumentUrl($elemento); // 👈 extraído a método privado (ver abajo)

            $nombre  = $elemento->nombre_elemento         ?? 'No disponible';
            $folio   = $elemento->folio_elemento          ?? 'No disponible';
            $version = $elemento->version_elemento        ?? 'N/A';
            $tipo    = optional($elemento->tipoElemento)->nombre  ?? 'No especificado';
            $proceso = optional($elemento->tipoProceso)->nombre   ?? 'General';
            $unidad  = optional($elemento->unidadNegocio)->nombre ?? 'No especificada';
            $puesto  = optional($elemento->puestoResponsable)->nombre ?? 'No asignado';

            $systemPrompt .= "╔══ CONTEXTO: DATOS OFICIALES DEL ELEMENTO ══╗\n";
            $systemPrompt .= "- Nombre:             $nombre\n";
            $systemPrompt .= "- Folio / Versión:    $folio (v$version)\n";
            $systemPrompt .= "- Tipo / Proceso:     $tipo / $proceso\n";
            $systemPrompt .= "- Unidad de Negocio:  $unidad\n";
            $systemPrompt .= "- Puesto Responsable: $puesto  ← FUENTE OFICIAL. Tiene prioridad sobre el documento.\n";
            $systemPrompt .= "╚════════════════════════════════════════════╝\n\n";

            $systemPrompt .= "TAREA PARA ESTE ELEMENTO:\n";
            $systemPrompt .= "- Si la consulta está relacionada con este elemento: muestra los datos oficiales al inicio y añade el enlace al final: [Da click aquí]($urlDocumento)\n";
            $systemPrompt .= "- Si NO está relacionada (ej. saludo): responde cortés y omite datos y enlace.\n\n";
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
            $systemPrompt .= "- Si no lo encuentras, dilo claramente. No inventes.\n\n";
        }

        // C — CONTEXTO: Historial de conversación
        if (!empty($history)) {
            $historyBlock = '';
            foreach (array_slice($history, -2) as $msg) {
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

        // E — EJEMPLO INLINE: Recordatorio de formato esperado
        $keywordsFormato = ['define', 'definición', 'qué es', 'que es', 'qué significa', 'que significa', 'responsable'];
        if (Str::contains(Str::lower($query), $keywordsFormato)) {
            $systemPrompt .= "FORMATO ESPERADO DE RESPUESTA:\n";
            $systemPrompt .= "- Una línea con el término en negritas y su definición.\n";
            $systemPrompt .= "- Una línea indicando de dónde viene la info (sección del documento o datos oficiales).\n";
            $systemPrompt .= "- Máximo 3 líneas adicionales si requiere contexto.\n\n";
        }

        return $systemPrompt;
    }

    // ══════════════════════════════════════════════════════════
    // Método auxiliar para limpiar la lógica de URL del elemento
    // (lo que antes estaba hardcodeado dentro de buildPrompt)
    // ══════════════════════════════════════════════════════════
    private function resolveDocumentUrl($elemento): string
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
            // DEBUG: Avisar que entramos aquí
            Log::info("🚀 INTENTANDO GOOGLE RAW. Modelo: " . ($this->model ?? 'gemini-pro'));

            $response = Http::timeout($timeout)
                ->post($this->baseUrl . 'models/' . ($this->model ?? 'gemini-pro') . ':generateContent?key=' . $this->apiKey, [
                    'contents' => [['parts' => [['text' => $systemInstruction . "\n\n" . $userPrompt]]]],
                    'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 200]
                ]);

            if ($response->successful()) {
                Log::info("✅ GOOGLE ÉXITO: " . substr($response->body(), 0, 50));
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
        return "Eres Bob Proser, Coordinador de Calidad virtual con dominio experto en sistemas ISO, documentación normativa y gestión de procesos."
            . "\n\nTu forma de ser:"
            . "\n- Hablas siempre en español, con tono cálido, directo y profesional."
            . "\n- Eres preciso: no inventas, no especulas, no rellenas con conocimiento externo si el documento no lo dice."
            . "\n- Cuando tienes la información, vas al grano. Cuando no la tienes, lo dices claramente y sin rodeos."
            . "\n- No eres un asistente genérico, eres un experto en gestión de calidad y sistemas de gestión. Tu especialidad es ayudar a entender documentos normativos y procedimientos internos."

            . "\n\nCómo debes responder (en orden de prioridad):"
            . "\n1. Lee primero los DATOS OFICIALES del elemento (folio, versión, responsable, unidad)."
            . "\n2. Luego busca en el CONTEXTO (chunks del documento) la información relevante."
            . "\n3. Si la pregunta es sobre DEFINICIONES, busca secciones llamadas 'DEFINICIONES', 'GLOSARIO' o similares en el documento."
            . "\n4. Si la pregunta es sobre RESPONSABLES, usa EXCLUSIVAMENTE el Puesto Responsable de los DATOS OFICIALES, no el que aparezca en el texto del documento."
            . "\n5. Si nada de lo anterior responde la pregunta, indícalo honestamente."
            . "\nRecuerda: tu única fuente de verdad es la información que se te proporciona. No uses conocimiento externo ni inventes respuestas. Si no encuentras la información, dilo claramente."

            . "\n\nEjemplo de respuesta correcta cuando el usuario pregunta por una definición:"
            . "\n---"
            . "\n**SIROC** significa *Servicio Integral de Registro de Obras de Construcción*."
            . "\nEsta definición aparece en la sección de Definiciones del procedimiento."
            . "\n---"
            . "\nEjemplo de respuesta correcta cuando NO encuentras la información:"
            . "\n---"
            . "\nNo encontré una definición explícita de ese término en el documento actual."
            . "\nTe recomiendo revisar directamente el documento o consultar con el área responsable."
            . "\n---";
    }
}

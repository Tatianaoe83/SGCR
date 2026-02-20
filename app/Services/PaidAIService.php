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

    // ==========================================================
    // 1. SYSTEM BASE (PERSONA)
    // ==========================================================
    $systemPrompt = "Eres Bob Proser, asistente virtual experto. Responde siempre en español de forma clara, profesional, empática y cercana.\n\n";

    // ==========================================================
    // 2. [NUEVO] CATÁLOGO GLOBAL (INTELIGENCIA DE INVENTARIO)
    // ==========================================================
    $keywordsInventario = ['que documentos', 'cuales documentos', 'lista de', 'tienes disponibles', 'inventario', 'listar', 'cuales hay'];
    
    if (Str::contains(Str::lower($query), $keywordsInventario)) {

        $catalogoDocs = WordDocument::where('status', 'active')
            ->select('id', 'folio_elemento', 'nombre_elemento', 'version_elemento')
            ->limit(50)
            ->get();

        if ($catalogoDocs->isNotEmpty()) {
            $listaTexto = $catalogoDocs->map(function($d) {
                return "- {$d->folio_elemento}: {$d->nombre_elemento} (v{$d->version_elemento})";
            })->implode("\n");

            $systemPrompt .= "CONOCIMIENTO GLOBAL DEL SISTEMA:\n";
            $systemPrompt .= "El usuario está preguntando por el inventario disponible. Aquí tienes la lista REAL de documentos en la base de datos:\n";
            $systemPrompt .= "=== INICIO LISTA DOCUMENTOS ===\n";
            $systemPrompt .= $listaTexto . "\n";
            $systemPrompt .= "=== FIN LISTA DOCUMENTOS ===\n";
            $systemPrompt .= "Instrucción: Si te piden listar documentos, usa ESTA lista. No inventes nada.\n\n";
        }
    }

    // ==========================================================
    // 3. DATOS OFICIALES (ELEMENTO SELECCIONADO)
    // ==========================================================
    if ($elemento) {

        $urlDocumento = '';
        if (!empty($elemento->archivo_es_formato)) {
            $urlDocumento = url('storage/' . ltrim($elemento->archivo_es_formato, '/'));
        } elseif (!empty($elemento->archivo_formato)) {
            $urlDocumento = url('storage/' . ltrim($elemento->archivo_formato, '/'));
        }

        $nombre    = $elemento->nombre_elemento ?? 'No disponible';
        $folio     = $elemento->folio_elemento ?? 'No disponible';
        $version   = $elemento->version_elemento ?? 'N/A';
        $tipo      = optional($elemento->tipoElemento)->nombre ?? 'No especificado';
        $proceso   = optional($elemento->tipoProceso)->nombre ?? 'General';
        $unidad    = optional($elemento->unidadNegocio)->nombre ?? 'No especificada';
        $puesto    = optional($elemento->puestoResponsable)->nombre ?? 'No asignado';
        $ubicacion = $elemento->ubicacion_resguardo ?? 'No indicada';

        $blockDatos = "DATOS OFICIALES DEL ELEMENTO ACTUAL\n";
        $blockDatos .= "- **Nombre del Elemento:** $nombre\n";
        $blockDatos .= "- **Folio:** $folio (v$version)\n";
        $blockDatos .= "- **Tipo / Proceso:** $tipo / $proceso\n";
        $blockDatos .= "- **Unidad de Negocio:** $unidad\n";
        $blockDatos .= "- **Puesto Responsable:** $puesto\n";
        $blockDatos .= "- **Ubicación Física:** $ubicacion\n\n";

        $systemPrompt .= $blockDatos;

        // NUEVA REGLA DE JERARQUÍA (NO SE ELIMINA NADA, SOLO SE AGREGA)
        $systemPrompt .= "REGLA DE JERARQUÍA DE INFORMACIÓN:\n";
        $systemPrompt .= "- El 'Puesto Responsable' definido arriba es el RESPONSABLE OFICIAL del procedimiento según la base de datos.\n";
        $systemPrompt .= "- Si en el CONTEXTO del documento se mencionan otros responsables, participantes o áreas, NO reemplazan al responsable oficial.\n";
        $systemPrompt .= "- Si el usuario pregunta '¿quién es el responsable del procedimiento?' debes responder usando EXCLUSIVAMENTE el Puesto Responsable del bloque de DATOS OFICIALES.\n";
        $systemPrompt .= "- Puedes complementar indicando otros roles mencionados en el documento, pero SIEMPRE dejando claro cuál es el responsable oficial.\n\n";

        $systemPrompt .= "REGLA CRÍTICA DE VISIBILIDAD Y FORMATO:\n";
        $systemPrompt .= "1. Analiza si la consulta del usuario está relacionada con este elemento ESPECÍFICO o la empresa.\n";
        $systemPrompt .= "2. SI está relacionada:\n";
        $systemPrompt .= "   - Tu respuesta DEBE comenzar mostrando el bloque de DATOS OFICIALES de arriba.\n";
        $systemPrompt .= "   - Al FINAL de toda tu respuesta, debes incluir el siguiente enlace exactamente así: [Da click aquí]($urlDocumento)\n";
        $systemPrompt .= "3. SI NO está relacionada (ej. saludo general):\n";
        $systemPrompt .= "   - NO muestres los datos oficiales.\n";
        $systemPrompt .= "   - NO muestres el enlace.\n";
        $systemPrompt .= "   - Responde cortésmente.\n\n";
    }

    // ==========================================================
    // 4. CONTEXTO DOCUMENTAL (RAG / VECTORES)
    // ==========================================================
    if (!empty($context)) {

        $safeContext = mb_substr(trim($context), 0, $MAX_CONTEXT_CHARS);

        $systemPrompt .= "REGLAS DE USO DEL CONTEXTO (CONTENIDO DEL DOCUMENTO):\n";
        $systemPrompt .= "- Usa EXCLUSIVAMENTE la información del bloque CONTEXTO para detalles profundos.\n";
        $systemPrompt .= "- Nunca inventes información.\n";
        $systemPrompt .= "- El CONTEXTO nunca puede contradecir ni reemplazar los DATOS OFICIALES del elemento.\n";
        $systemPrompt .= "- Si no existe información relevante en el contexto ni en el catálogo global, indícalo claramente.\n\n";

        $systemPrompt .= "═══════════════════════════════════════\n";
        $systemPrompt .= "CONTEXTO (CHUNKS ENCONTRADOS)\n";
        $systemPrompt .= "═══════════════════════════════════════\n\n";
        $systemPrompt .= $safeContext . "\n\n";
        $systemPrompt .= "═══════════════════════════════════════\n\n";
    }

    // ==========================================================
    // 5. HISTORIAL
    // ==========================================================
    if (!empty($history)) {

        $historyBlock = '';
        foreach (array_slice($history, -2) as $msg) {
            $role = ($msg['role'] === 'user') ? 'USUARIO' : 'ASISTENTE';
            $historyBlock .= $role . ': ' . strip_tags($msg['content']) . "\n";
        }

        $historyBlock = mb_substr($historyBlock, 0, $MAX_HISTORY_CHARS);

        $systemPrompt .= "HISTORIAL RECIENTE (REFERENCIA):\n";
        $systemPrompt .= "-----------------------------------\n";
        $systemPrompt .= $historyBlock;
        $systemPrompt .= "-----------------------------------\n\n";
    }

    // ==========================================================
    // 6. SALIDA FINAL
    // ==========================================================
    return $systemPrompt
        . "CONSULTA ACTUAL DEL USUARIO:\n"
        . $query;
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
                    'model' => $this->model ?? 'claude-3-sonnet-20240229', 'max_tokens' => 200, 'temperature' => 0,
                    'system' => $systemInstruction, 'messages' => [['role' => 'user', 'content' => $userPrompt]]
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
        return "Eres un asistente virtual experto en procedimientos y documentos de calidad."
            . "\n\nREGLAS CRÍTICAS DE RESPUESTA:"
            . "\n1. Responde siempre en español con un tono cálido, claro y profesional."
            . "\n2. Si el usuario pregunta por DEFINICIONES o RESPONSABLES, busca primero en las secciones del documento que contengan esos términos (por ejemplo: 'DEFINICIONES', 'RESPONSABLE', 'RESPONSABLES'), normalmente ubicadas al inicio o al final."
            . "\n3. Si una definición aparece explícitamente en el texto del documento (por ejemplo: 'SIROC – Servicio Integral de Registro de Obras'), debes usarla como respuesta, incluso si el encabezado de la sección no está perfectamente formateado o numerado."
            . "\n4. La información dentro del CONTENIDO RELEVANTE del documento tiene mayor prioridad que los metadatos o encabezados administrativos."
            . "\n5. Si el documento contiene secciones numeradas o listados formales, utiliza el texto literal cuando sea posible."
            . "\n6. Solo indica que una definición no se encuentra si, después de revisar todo el contenido proporcionado, el término no aparece definido de forma explícita."
            . "\n7. No inventes definiciones ni completes con conocimiento externo si el documento no lo especifica."
            . "\n8. Ve al grano y responde directamente a la pregunta del usuario.";
    }
}

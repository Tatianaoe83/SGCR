<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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
    public function generateResponse($query, $context = null, $timeout = null, $history = [])
    {
        $requestTimeout = $timeout ?? $this->timeout;

        try {
            return match ($this->provider) {
                // Pasamos $history a cada método específico
                'openai' => $this->generateOpenAIResponse($query, $context, $requestTimeout, $history),
                'anthropic' => $this->generateAnthropicResponse($query, $context, $requestTimeout, $history),
                'google' => $this->generateGoogleResponse($query, $context, $requestTimeout, $history),
                default => throw new \Exception("Proveedor de IA no soportado: {$this->provider}")
            };
        } catch (\Exception $e) {
            Log::error("Error en PaidAIService ({$this->provider}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar respuesta usando OpenAI GPT-4 Turbo
     * Ahora recibe y procesa el $history
     */
    private function generateOpenAIResponse($query, $context, $timeout, $history)
{
    // =========================
    // 1. Construcción del prompt
    // =========================
    $prompt = $this->buildPrompt($query, $context, $history);

    // =========================
    // 2. DEBUG CRÍTICO DE TAMAÑOS
    // =========================
    logger()->error('🧨 PROMPT DEBUG (ANTES DE OPENAI)', [
        'query_chars'   => mb_strlen((string) $query),
        'context_chars' => mb_strlen((string) $context),
        'history_chars' => is_string($history)
            ? mb_strlen($history)
            : mb_strlen(json_encode($history)),
        'prompt_chars'  => mb_strlen($prompt),
    ]);

    // =========================
    // 3. MENSAJES PARA OPENAI
    // =========================
    $messages = [
        [
            'role' => 'system',
            'content' => $this->buildToneInstruction(), // 👈 reglas SOLO aquí
        ],
        [
            'role' => 'user',
            'content' => $prompt, // 👈 documento + pregunta (YA DELIMITADO)
        ],
    ];

    // =========================
    // 4. DEBUG FINAL (LO QUE REALMENTE SE ENVÍA)
    // =========================
    logger()->error('🧨 OPENAI MESSAGES DEBUG', [
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
            'model'       => $this->model ?? 'gpt-4.1-mini',
            'messages'    => $messages,
            'temperature' => 0.3,   // 🔧 más preciso para documentos
            'max_tokens'  => 800,   // 🔧 suficiente para respuestas claras
        ]);

    // =========================
    // 6. RESPUESTA
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
    /**
     * Construir prompt con contexto
     */
    private function buildPrompt($query, $context = null, $history = [])
{
    // =========================
    // CONFIGURACIÓN DE LÍMITES
    // =========================
    $MAX_CONTEXT_CHARS = 6000;
    $MAX_HISTORY_CHARS = 600;

    // =========================
    // URL BASE
    // =========================
    $baseUrl = url('/');

    // =========================
    // SYSTEM PROMPT BASE
    // =========================
    $systemPrompt = "Eres un asistente virtual experto. Responde siempre en español de manera clara, profesional y empática.";

    // =========================
    // CONTEXTO (DOCUMENTO)
    // =========================
    if (!empty($context)) {

        // 🔒 LIMITE DURO DE CONTEXTO
        $safeContext = mb_substr(trim($context), 0, $MAX_CONTEXT_CHARS);

        $systemPrompt .= "\n\n═══════════════════════════════════════════════════════════\n";
        $systemPrompt .= "INSTRUCCIONES CRÍTICAS - DEBES SEGUIR ESTAS REGLAS:\n";
        $systemPrompt .= "═══════════════════════════════════════════════════════════\n\n";
        $systemPrompt .= "1. El contexto proporcionado contiene información REAL de la base de datos del usuario.\n";
        $systemPrompt .= "2. DEBES usar SOLO la información del contexto para responder. NUNCA inventes información.\n";
        $systemPrompt .= "3. Busca en el contexto el contenido más específico relacionado con la consulta actual.\n";
        $systemPrompt .= "4. INCLUYE TODA LA INFORMACIÓN RELEVANTE que encuentres en el contexto, especialmente:\n";
        $systemPrompt .= "   - Nombre del elemento\n";
        $systemPrompt .= "   - Folio del elemento\n";
        $systemPrompt .= "   - Tipo de elemento y proceso\n";
        $systemPrompt .= "   - Unidad de negocio\n";
        $systemPrompt .= "   - Puesto Responsable (SIEMPRE incluir si está disponible en el contexto)\n";
        $systemPrompt .= "   - Cualquier otra información relevante del contexto\n";
        $systemPrompt .= "5. Si el contexto menciona un 'Puesto Responsable', DEBES incluirlo en tu respuesta.\n";
        $systemPrompt .= "6. Si el contexto menciona 'No asignado' para el responsable, también menciónalo.\n";
        $systemPrompt .= "7. Prioriza información sobre procedimientos, lineamientos, elementos y documentos encontrados.\n";
        $systemPrompt .= "8. Si encuentras información relevante, cítala de manera natural y completa.\n";
        $systemPrompt .= "9. Si NO encuentras información relevante en el contexto, di claramente que no tienes esa información específica.\n";
        $systemPrompt .= "10. Responde de forma cálida, cercana y empática, como si fueras un compañero de trabajo ayudando.\n\n";

        $systemPrompt .= "11. Para responder, analiza el bloque 'CONTENIDO DEL DOCUMENTO'.\n";
        $systemPrompt .= "12. Si la información es extensa, genera una SÍNTESIS clara y estructurada.\n";
        $systemPrompt .= "13. Si la información es breve, cítala textualmente.\n";
        $systemPrompt .= "14. Usa la frase: 'Para ver todos los detalles técnicos, consulta el documento completo...'.\n";
        $systemPrompt .= "15. La respuesta debe ir después del nombre y folio del elemento.\n";
        $systemPrompt .= "16. Si no hay contenido, indica: 'No se cuenta con una descripción detallada disponible'.\n\n";

        $systemPrompt .= "17. REPARACIÓN DE ENLACES: completa rutas relativas con la URL base.\n";
        $systemPrompt .= "18. Formato final del link: 📄 **[Ver documento completo]({$baseUrl}/ruta_del_contexto)**.\n\n";

        $systemPrompt .= "19. ANÁLISIS DE CONTINUIDAD: revisa el historial si existe.\n";
        $systemPrompt .= "20. Si es pregunta de seguimiento, usa el MISMO documento.\n";

        $systemPrompt .= "═══════════════════════════════════════════════════════════\n";
        $systemPrompt .= "CONTEXTO DE LA BASE DE DATOS:\n";
        $systemPrompt .= "═══════════════════════════════════════════════════════════\n\n";
        $systemPrompt .= $safeContext . "\n\n";
        $systemPrompt .= "═══════════════════════════════════════════════════════════\n";
    }

    // =========================
    // HISTORIAL (LIMITADO)
    // =========================
    if (!empty($history)) {
        $historyBlock = '';

        foreach (array_slice($history, -2) as $msg) {
            $role = ($msg['role'] === 'user') ? 'USUARIO' : 'ASISTENTE';
            $historyBlock .= $role . ': ' . strip_tags($msg['content']) . "\n";
        }

        $historyBlock = mb_substr($historyBlock, 0, $MAX_HISTORY_CHARS);

        $systemPrompt .= "\nHISTORIAL (REFERENCIA):\n";
        $systemPrompt .= "--------------------------------------------------\n";
        $systemPrompt .= $historyBlock;
        $systemPrompt .= "--------------------------------------------------\n\n";
    }

    // =========================
    // LOGS DE DEBUG 🔥
    // =========================
    logger()->error('🧨 PROMPT DEBUG (BUILD)', [
        'query_chars'   => mb_strlen($query),
        'context_chars' => isset($safeContext) ? mb_strlen($safeContext) : 0,
        'history_chars' => isset($historyBlock) ? mb_strlen($historyBlock) : 0,
        'total_chars'   => mb_strlen($systemPrompt)
    ]);

    // =========================
    // SALIDA FINAL
    // =========================
    return $systemPrompt
        . "\nCONSULTA ACTUAL DEL USUARIO:\n"
        . $query
        . "\n\nIMPORTANTE: Usa SOLO el contexto proporcionado.";
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

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
     */
    public function generateResponse($query, $context = null, $timeout = null)
    {
        $requestTimeout = $timeout ?? $this->timeout;

        try {
            return match ($this->provider) {
                'openai' => $this->generateOpenAIResponse($query, $context, $requestTimeout),
                'anthropic' => $this->generateAnthropicResponse($query, $context, $requestTimeout),
                'google' => $this->generateGoogleResponse($query, $context, $requestTimeout),
                default => throw new \Exception("Proveedor de IA no soportado: {$this->provider}")
            };
        } catch (\Exception $e) {
            Log::error("Error en PaidAIService ({$this->provider}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar respuesta usando OpenAI GPT-4 Turbo
     */
    private function generateOpenAIResponse($query, $context, $timeout)
    {
        $prompt = $this->buildPrompt($query, $context);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . 'chat/completions', [
                'model' => $this->model ?? 'gpt-4.1-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente virtual experto. Responde siempre en español de manera clara, profesional y empática.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 'No pude generar una respuesta apropiada.';
        }

        Log::error('OpenAI API error: ' . $response->status() . ' - ' . $response->body());
        throw new \Exception('Error en la API de OpenAI: ' . $response->status());
    }

    /**
     * Generar respuesta usando Anthropic Claude 3 Sonnet
     */
    private function generateAnthropicResponse($query, $context, $timeout)
    {
        $prompt = $this->buildPrompt($query, $context);

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
    private function generateGoogleResponse($query, $context, $timeout)
    {
        $prompt = $this->buildPrompt($query, $context);

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
    private function buildPrompt($query, $context = null)
    {
        $systemPrompt = "Eres un asistente virtual experto. Responde siempre en español de manera clara, profesional y empática.";

        if ($context) {
            $systemPrompt .= "\n\n═══════════════════════════════════════════════════════════\n";
            $systemPrompt .= "INSTRUCCIONES CRÍTICAS - DEBES SEGUIR ESTAS REGLAS:\n";
            $systemPrompt .= "═══════════════════════════════════════════════════════════\n\n";
            $systemPrompt .= "1. El contexto proporcionado contiene información REAL de la base de datos del usuario.\n";
            $systemPrompt .= "2. DEBES usar SOLO la información del contexto para responder. NUNCA inventes información.\n";
            $systemPrompt .= "3. Busca en el contexto el contenido más específico relacionado con: \"{$query}\"\n";
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
            $systemPrompt .= "11. Para CADA elemento encontrado, genera una breve DESCRIPCIÓN basada EXCLUSIVAMENTE en el bloque llamado 'CONTENIDO DEL DOCUMENTO'.\n";
            $systemPrompt .= "12. La descripción debe explicar qué hace el procedimiento o elemento, en 1 o 2 frases claras.\n";
            $systemPrompt .= "13. NO repitas textualmente el contenido; resume su propósito.\n";
            $systemPrompt .= "14. La descripción debe ir inmediatamente después del nombre y folio del elemento.\n";
            $systemPrompt .= "15. Si un elemento NO tiene bloque 'CONTENIDO DEL DOCUMENTO', indica exactamente: 'No se cuenta con una descripción disponible'.\n";
            $systemPrompt .= "16. Si un elemento incluye un enlace de documento (por ejemplo 'Ver documento'), DEBES conservarlo y mostrarlo explícitamente en la respuesta.\n";
            $systemPrompt .= "17. Los enlaces deben mostrarse al final de cada elemento bajo el texto 'Documento:' manteniendo el enlace original.\n";
            $systemPrompt .= "18. NO elimines, resumas ni reformules los enlaces proporcionados en el contexto.\n";
            $systemPrompt .= "═══════════════════════════════════════════════════════════\n";
            $systemPrompt .= "CONTEXTO DE LA BASE DE DATOS:\n";
            $systemPrompt .= "═══════════════════════════════════════════════════════════\n\n";
            $systemPrompt .= $context . "\n\n";
            $systemPrompt .= "═══════════════════════════════════════════════════════════\n";
        }

        return $systemPrompt . "CONSULTA DEL USUARIO: " . $query . "\n\nResponde incluyendo TODA la información relevante que encuentres en el contexto, especialmente el responsable si está disponible.";
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
}

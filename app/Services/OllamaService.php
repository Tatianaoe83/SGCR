<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaService
{
    private $baseUrl;
    private $model;
    private $timeout;
    
    public function __construct()
    {
        $baseUrl = config('services.ollama.base_url', 'https://c6f5cc547c97.ngrok-free.app');
        // Asegurar que la URL termine con /
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        
        $configuredModel = config('services.ollama.model', 'llama3.2:1b');
        
        // Validar que el modelo no sea 'llama3.2:latest' (no disponible)
        // Si es así, usar el modelo por defecto 'llama3.2:1b'
        if ($configuredModel === 'llama3.2:latest') {
            Log::warning('Modelo llama3.2:latest no disponible, usando llama3.2:1b por defecto');
            $this->model = 'llama3.2:1b';
        } else {
            $this->model = $configuredModel;
        }
        
        // Sin límite de tiempo para las peticiones a Ollama (0 = sin límite)
        $this->timeout = 0;
    }

    public function generateResponse($query, $context = null)
    {
        $prompt = $this->buildPrompt($query, $context);
        
        try {
            // Preparar headers, incluyendo el header necesario para ngrok
            $headers = [];
            if (strpos($this->baseUrl, 'ngrok') !== false) {
                $headers['ngrok-skip-browser-warning'] = 'true';
                $headers['User-Agent'] = 'Mozilla/5.0 (compatible; Laravel-Ollama/1.0)';
            }
            
            // Timeout de 0 significa sin límite de tiempo, pero usar un valor razonable para HostGator
            $timeout = $this->timeout > 0 ? $this->timeout : 120;
            $http = Http::timeout($timeout)
                ->connectTimeout(30)
                ->retry(2, 100);
            
            if (!empty($headers)) {
                $http = $http->withHeaders($headers);
            }
            
            // Intentar primero con verificación SSL, si falla, intentar sin verificación
            try {
                $response = $http->post("{$this->baseUrl}api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'num_predict' => 300,
                        'top_k' => 40,
                        'top_p' => 0.9
                    ]
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $sslException) {
                // Si falla con SSL, intentar sin verificación SSL (útil para ngrok)
                Log::warning('Generate response con SSL falló, intentando sin verificación SSL: ' . $sslException->getMessage());
                $response = $http->withoutVerifying()->post("{$this->baseUrl}api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'num_predict' => 300,
                        'top_k' => 40,
                        'top_p' => 0.9
                    ]
                ]);
            }

            if ($response->successful()) {
                $data = $response->json();
                return $data['response'] ?? 'No pude generar una respuesta apropiada.';
            } else {
                Log::error('Ollama API error: ' . $response->status() . ' - ' . $response->body());
                throw new \Exception('Error en la API de Ollama: ' . $response->status());
            }
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Ollama service - Error de conexión: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Ollama service error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function healthCheck()
    {
        try {
            $headers = [];
            if (strpos($this->baseUrl, 'ngrok') !== false) {
                // Headers necesarios para ngrok
                $headers['ngrok-skip-browser-warning'] = 'true';
                $headers['User-Agent'] = 'Mozilla/5.0 (compatible; Laravel-Ollama/1.0)';
            }
            
            // Aumentar timeout para HostGator (puede tener latencia más alta)
            // También aumentar connectTimeout para conexiones más lentas
            $http = Http::timeout(15)
                ->connectTimeout(10)
                ->retry(1, 500);
            
            if (!empty($headers)) {
                $http = $http->withHeaders($headers);
            }
            
            // Intentar primero con verificación SSL, si falla, intentar sin verificación
            try {
                $response = $http->get("{$this->baseUrl}api/tags");
            } catch (\Illuminate\Http\Client\ConnectionException $sslException) {
                // Si falla con SSL, intentar sin verificación SSL (útil para ngrok)
                Log::warning('Health check con SSL falló, intentando sin verificación SSL: ' . $sslException->getMessage());
                $response = $http->withoutVerifying()->get("{$this->baseUrl}api/tags");
            }
            
            if ($response->successful()) {
                return 'ok';
            } else {
                Log::warning('Ollama health check falló: ' . $response->status() . ' - ' . $response->body());
                return 'error';
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Ollama health check - Error de conexión: ' . $e->getMessage());
            return 'offline';
        } catch (\Exception $e) {
            Log::warning('Ollama health check - Error general: ' . $e->getMessage());
            return 'offline';
        }
    }

    private function buildPrompt($query, $context = null)
    {
        $systemPrompt = "Eres un asistente virtual. Responde de manera concisa en español.";
        
        if ($context) {
            $systemPrompt .= "\n\nContexto: " . $context;
        }
        
        return $systemPrompt . "\n\n" . $query;
    }

    public function getAvailableModels()
    {
        try {
            $headers = [];
            if (strpos($this->baseUrl, 'ngrok') !== false) {
                $headers['ngrok-skip-browser-warning'] = 'true';
                $headers['User-Agent'] = 'Mozilla/5.0 (compatible; Laravel-Ollama/1.0)';
            }
            
            $http = Http::timeout(15)
                ->connectTimeout(10)
                ->retry(1, 500);
            
            if (!empty($headers)) {
                $http = $http->withHeaders($headers);
            }
            
            // Intentar primero con verificación SSL, si falla, intentar sin verificación
            try {
                $response = $http->get("{$this->baseUrl}api/tags");
            } catch (\Illuminate\Http\Client\ConnectionException $sslException) {
                // Si falla con SSL, intentar sin verificación SSL (útil para ngrok)
                Log::warning('Get models con SSL falló, intentando sin verificación SSL: ' . $sslException->getMessage());
                $response = $http->withoutVerifying()->get("{$this->baseUrl}api/tags");
            }
                
            if ($response->successful()) {
                $data = $response->json();
                return collect($data['models'] ?? [])->pluck('name');
            }
            
            return collect();
        } catch (\Exception $e) {
            Log::error('Error getting Ollama models: ' . $e->getMessage());
            return collect();
        }
    }
}

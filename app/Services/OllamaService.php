<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaService
{
    private $baseUrl;
    private $username;
    private $password;
    private $model;
    private $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.base_url');
        $this->username = config('services.ollama.username');
        $this->password = config('services.ollama.password');
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
            // Timeout de 0 significa sin límite de tiempo
            $httpClient = Http::timeout($this->timeout)
                ->connectTimeout(30)
                ->retry(2, 100);
            
            // Agregar autenticación básica si está configurada
            if ($this->username && $this->password) {
                $httpClient->withBasicAuth($this->username, $this->password);
            }
            
            $response = $httpClient->post("{$this->baseUrl}api/generate", [
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

            if ($response->successful()) {
                $data = $response->json();
                return $data['response'] ?? 'No pude generar una respuesta apropiada.';
            } else {
                Log::error('Ollama API error: ' . $response->status() . ' - ' . $response->body());
                throw new \Exception('Error en la API de Ollama: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Ollama service error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function healthCheck()
    {
        try {
            $httpClient = Http::timeout(5);
            
            // Agregar autenticación básica si está configurada
            if ($this->username && $this->password) {
                $httpClient->withBasicAuth($this->username, $this->password);
            }
            
            $response = $httpClient->get("{$this->baseUrl}api/tags");
            return $response->successful() ? 'ok' : 'error';
        } catch (\Exception $e) {
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
            $httpClient = Http::timeout(10);
            
            // Agregar autenticación básica si está configurada
            if ($this->username && $this->password) {
                $httpClient->withBasicAuth($this->username, $this->password);
            }
            
            $response = $httpClient->get("{$this->baseUrl}api/tags");
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
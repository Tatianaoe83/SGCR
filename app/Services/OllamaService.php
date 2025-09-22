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
        $this->baseUrl = config('services.ollama.base_url', 'http://192.168.2.7:11433/');
        $this->model = config('services.ollama.model', 'llama3.2:latest');
        $this->timeout = config('services.ollama.timeout', 30);
    }

    public function generateResponse($query, $context = null)
    {
        $prompt = $this->buildPrompt($query, $context);
        
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->post("{$this->baseUrl}api/generate", [
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
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}api/tags");
                
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
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}api/tags");
                
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

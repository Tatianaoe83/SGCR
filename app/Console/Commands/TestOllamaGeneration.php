<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OllamaService;
use App\Services\HybridChatbotService;
use Illuminate\Support\Facades\Http;

class TestOllamaGeneration extends Command
{
    protected $signature = 'ollama:test-generation {query=Hola}';
    protected $description = 'Probar generación de respuesta paso a paso para diagnosticar problemas';

    public function handle()
    {
        $query = $this->argument('query');
        $this->info("🧪 Probando generación con query: '{$query}'");
        
        // Test 1: HTTP directo
        $this->info("\n1. Prueba HTTP directa...");
        try {
            $baseUrl = config('services.ollama.base_url');
            $model = config('services.ollama.model');
            $timeout = config('services.ollama.timeout');
            
            $this->line("URL: {$baseUrl}api/generate");
            $this->line("Model: {$model}");
            $this->line("Timeout: {$timeout}s");
            
            $payload = [
                'model' => $model,
                'prompt' => "Eres un asistente virtual. Responde brevemente: {$query}",
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 50
                ]
            ];
            
            $this->line("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
            
            $start = microtime(true);
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->post("{$baseUrl}api/generate", $payload);
            $responseTime = round((microtime(true) - $start) * 1000);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("✅ HTTP directo exitoso ({$responseTime}ms)");
                $this->line("Respuesta: " . ($data['response'] ?? 'N/A'));
            } else {
                $this->error("❌ HTTP directo falló: " . $response->status());
                $this->line("Body: " . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Excepción HTTP directo: " . $e->getMessage());
            return 1;
        }

        // Test 2: OllamaService
        $this->info("\n2. Prueba con OllamaService...");
        try {
            $ollamaService = new OllamaService();
            $start = microtime(true);
            $response = $ollamaService->generateResponse($query);
            $responseTime = round((microtime(true) - $start) * 1000);
            
            $this->info(" OllamaService exitoso ({$responseTime}ms)");
            $this->line("Respuesta: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''));
        } catch (\Exception $e) {
            $this->error(" OllamaService falló: " . $e->getMessage());
            
            // Análisis detallado del error
            if (strpos($e->getMessage(), 'cURL error 28') !== false) {
                $this->warn(" Análisis del error cURL 28:");
                if (strpos($e->getMessage(), '0 bytes received') !== false) {
                    $this->line("- El servidor no está devolviendo datos");
                    $this->line("- Posible causa: modelo no cargado o consulta muy compleja");
                    $this->line("- Sugerencia: probar con un modelo más ligero");
                }
            }
            return 1;
        }

        // Test 3: HybridChatbotService completo
        $this->info("\n3. Prueba con HybridChatbotService completo...");
        try {
            $chatbotService = new HybridChatbotService();
            $start = microtime(true);
            $result = $chatbotService->processQuery($query);
            $responseTime = round((microtime(true) - $start) * 1000);
            
            $this->info("✅ HybridChatbotService exitoso ({$responseTime}ms)");
            $this->line("Método usado: " . $result['method']);
            $this->line("Respuesta: " . substr($result['response'], 0, 100) . (strlen($result['response']) > 100 ? '...' : ''));
            
            if (isset($result['error']) && $result['error']) {
                $this->warn("⚠️  Se reportó error: " . $result['error_type']);
            }
        } catch (\Exception $e) {
            $this->error("❌ HybridChatbotService falló: " . $e->getMessage());
            return 1;
        }

        $this->info("\n🎉 Todas las pruebas completadas exitosamente!");
        return 0;
    }
}

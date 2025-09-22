<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;

class TestOllamaConnection extends Command
{
    protected $signature = 'ollama:test-connection {--timeout=5}';
    protected $description = 'Probar la conectividad con el servicio Ollama y diagnosticar problemas';

    public function handle()
    {
        $this->info('🔍 Iniciando diagnóstico de conectividad con Ollama...');
        $timeout = $this->option('timeout');
        
        // Test 1: Conectividad básica
        $this->info("\n1. Probando conectividad básica...");
        $baseUrl = config('services.ollama.base_url', 'http://192.168.2.7:11433/');
        $this->line("URL: {$baseUrl}");
        
        try {
            $start = microtime(true);
            $response = Http::timeout($timeout)->get("{$baseUrl}api/tags");
            $responseTime = round((microtime(true) - $start) * 1000);
            
            if ($response->successful()) {
                $this->info("✅ Conectividad exitosa ({$responseTime}ms)");
                $data = $response->json();
                $models = collect($data['models'] ?? [])->pluck('name');
                $this->line("Modelos disponibles: " . $models->implode(', '));
            } else {
                $this->error("❌ Error HTTP: " . $response->status());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error de conectividad: " . $e->getMessage());
            return 1;
        }

        // Test 2: Configuración del servicio
        $this->info("\n2. Verificando configuración del servicio...");
        $configTimeout = config('services.ollama.timeout', 30);
        $configModel = config('services.ollama.model', 'llama3.2:latest');
        $this->line("Timeout configurado: {$configTimeout}s");
        $this->line("Modelo configurado: {$configModel}");

        // Test 3: Test del servicio Ollama
        $this->info("\n3. Probando servicio OllamaService...");
        try {
            $ollamaService = new OllamaService();
            $healthStatus = $ollamaService->healthCheck();
            $this->line("Estado de salud: {$healthStatus}");
            
            if ($healthStatus === 'ok') {
                $this->info("✅ Servicio OllamaService funcionando correctamente");
            } else {
                $this->warn("⚠️  Servicio OllamaService reporta problemas");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error en OllamaService: " . $e->getMessage());
        }

        // Test 4: Test de generación simple (opcional)
        if ($this->confirm('¿Quieres probar una generación de respuesta simple? (puede tardar)')) {
            $this->info("\n4. Probando generación de respuesta...");
            try {
                $start = microtime(true);
                $ollamaService = new OllamaService();
                $response = $ollamaService->generateResponse('Hola, ¿cómo estás?');
                $responseTime = round((microtime(true) - $start) * 1000);
                
                $this->info("✅ Respuesta generada exitosamente ({$responseTime}ms)");
                $this->line("Respuesta: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''));
            } catch (\Exception $e) {
                $this->error("❌ Error generando respuesta: " . $e->getMessage());
                
                // Análisis del error
                if (strpos($e->getMessage(), 'cURL error 28') !== false) {
                    $this->warn("\n💡 Sugerencias para resolver el timeout:");
                    $this->line("- Aumentar OLLAMA_TIMEOUT en .env (actual: {$configTimeout}s)");
                    $this->line("- Verificar que el servidor Ollama no esté sobrecargado");
                    $this->line("- Probar con consultas más simples");
                    $this->line("- Verificar la red entre el servidor web y Ollama");
                }
            }
        }

        $this->info("\n🎉 Diagnóstico completado.");
        return 0;
    }
}

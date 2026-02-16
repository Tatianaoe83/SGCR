<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;

class TestOllamaConnection extends Command
{
    protected $signature = 'ollama:test-connection {--timeout=30} {--connect-timeout=10}';
    protected $description = 'Probar la conectividad con el servicio Ollama y diagnosticar problemas';

    public function handle()
    {
        $this->info(' Iniciando diagnóstico de conectividad con Ollama...');
        $timeout = (int) $this->option('timeout');
        $connectTimeout = (int) $this->option('connect-timeout');
        
        // Test 0: Información de configuración
        $this->info("\n0. Verificando configuración...");
        $baseUrl = config('services.ollama.base_url', 'https://c6f5cc547c97.ngrok-free.app');
        $configTimeout = config('services.ollama.timeout', 120);
        $configModel = config('services.ollama.model', 'llama3.2:1b');
        
        $this->line("URL base: {$baseUrl}");
        $this->line("Timeout configurado (servicio): {$configTimeout}s");
        $this->line("Timeout conexión (test): {$connectTimeout}s");
        $this->line("Timeout total (test): {$timeout}s");
        $this->line("Modelo configurado: {$configModel}");
        
        // Test 0.1: Verificar URL válida
        $parsedUrl = parse_url($baseUrl);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            $this->error("❌ URL inválida: {$baseUrl}");
            return 1;
        }
        
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? 'http') === 'https' ? 443 : 80);
        
        // Test 0.2: Resolución DNS (si está disponible en el servidor)
        $this->line("\n0.1. Verificando resolución DNS...");
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $this->warn("⚠️  No se pudo resolver el DNS para: {$host}");
            $this->line("   Esto puede indicar problemas de red o DNS.");
        } else {
            $this->info("✅ DNS resuelto: {$host} -> {$ip}");
        }
        
        // Test 1: Conectividad básica
        $this->info("\n1. Probando conectividad básica...");
        $this->line("Endpoint: {$baseUrl}api/tags");
        
        try {
            $start = microtime(true);
            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->retry(1, 100)
                ->get("{$baseUrl}api/tags");
            $responseTime = round((microtime(true) - $start) * 1000);
            
            if ($response->successful()) {
                $this->info("✅ Conectividad exitosa ({$responseTime}ms)");
                $data = $response->json();
                $models = collect($data['models'] ?? [])->pluck('name');
                $this->line("Modelos disponibles: " . $models->implode(', '));
            } else {
                $this->error("❌ Error HTTP: " . $response->status());
                $this->line("Respuesta: " . substr($response->body(), 0, 200));
                return 1;
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMsg = $e->getMessage();
            $this->error("❌ Error de conectividad: " . $errorMsg);
            
            // Análisis detallado del error
            $this->warn("\n💡 Análisis del error:");
            
            if (strpos($errorMsg, 'cURL error 7') !== false || strpos($errorMsg, 'Failed to connect') !== false) {
                $this->line("   - Tipo: Error de conexión (cURL error 7)");
                $this->line("   - Posibles causas:");
                $this->line("     • El servidor Ollama no está ejecutándose en {$host}:{$port}");
                $this->line("     • El firewall está bloqueando el puerto {$port}");
                $this->line("     • Problemas de red entre este servidor y {$host}");
                $this->line("     • El dominio '{$host}' no es accesible desde este servidor");
                $this->line("\n   Sugerencias:");
                $this->line("   - Verificar que Ollama esté ejecutándose: curl {$baseUrl}api/tags");
                $this->line("   - Probar conectividad: telnet {$host} {$port} (o nc -zv {$host} {$port})");
                $this->line("   - Verificar firewall/rutas de red");
                $this->line("   - Intentar con un timeout mayor: --timeout=60 --connect-timeout=30");
            } elseif (strpos($errorMsg, 'cURL error 28') !== false || strpos($errorMsg, 'timeout') !== false) {
                $this->line("   - Tipo: Timeout de conexión");
                $this->line("   - El servidor tardó más de {$connectTimeout}s en responder");
                $this->line("\n   Sugerencias:");
                $this->line("   - Aumentar connect-timeout: --connect-timeout=30");
                $this->line("   - Verificar latencia de red");
            } else {
                $this->line("   - Tipo: Error desconocido");
                $this->line("   - Mensaje completo: {$errorMsg}");
            }
            
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Error inesperado: " . $e->getMessage());
            $this->line("Tipo: " . get_class($e));
            return 1;
        }

        // Test 2: Test del servicio Ollama
        $this->info("\n2. Probando servicio OllamaService...");
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

        // Test 3: Test de generación simple (opcional)
        if ($this->confirm('¿Quieres probar una generación de respuesta simple? (puede tardar)')) {
            $this->info("\n3. Probando generación de respuesta...");
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

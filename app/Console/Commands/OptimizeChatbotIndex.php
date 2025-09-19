<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeChatbotIndex extends Command
{
    protected $signature = 'chatbot:optimize-index {--period=7days}';
    protected $description = 'Optimiza el índice inteligente del chatbot';

    public function handle()
    {
        $this->info('🚀 Iniciando optimización del índice inteligente...');
        
        $period = $this->option('period');
        $startDate = match($period) {
            '24hours' => now()->subDay(),
            '7days' => now()->subWeek(),
            '30days' => now()->subMonth(),
            default => now()->subWeek()
        };
        
        // 1. Identificar consultas frecuentes sin índice
        $this->identifyFrequentQueries($startDate);
        
        // 2. Limpiar índices de baja confianza
        $this->cleanLowConfidenceIndexes();
        
        // 3. Consolidar índices similares
        $this->consolidateSimilarIndexes();
        
        // 4. Actualizar estadísticas
        $this->updateStatistics($startDate);
        
        $this->info('✅ Optimización completada');
    }

    private function identifyFrequentQueries($startDate)
    {
        $this->info('📊 Identificando consultas frecuentes...');
        
        $frequentQueries = ChatbotAnalytics::where('created_at', '>=', $startDate)
            ->where('response_method', 'ollama')
            ->whereHas('feedback', function($query) {
                $query->where('helpful', true);
            })
            ->groupBy('normalized_query')
            ->havingRaw('COUNT(*) >= 3') // Al menos 3 consultas positivas
            ->selectRaw('normalized_query, response, COUNT(*) as frequency')
            ->orderByDesc('frequency')
            ->get();
        
        foreach ($frequentQueries as $query) {
            $existing = SmartIndex::where('normalized_query', $query->normalized_query)->first();
            
            if (!$existing) {
                app(SmartIndexingService::class)->addToIndex(
                    $query->normalized_query,
                    $query->response,
                    'verified',
                    true
                );
                
                $this->line("✅ Agregado al índice: {$query->normalized_query}");
            }
        }
    }

    private function cleanLowConfidenceIndexes()
    {
        $this->info('🧹 Limpiando índices de baja confianza...');
        
        $lowConfidenceCount = SmartIndex::where('confidence_score', '<', 0.3)
            ->where('usage_count', '<', 2)
            ->where('created_at', '<', now()->subDays(30))
            ->count();
        
        SmartIndex::where('confidence_score', '<', 0.3)
            ->where('usage_count', '<', 2)
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
        
        $this->line("🗑️ Eliminados {$lowConfidenceCount} índices de baja confianza");
    }

    private function consolidateSimilarIndexes()
    {
        $this->info('🔄 Consolidando índices similares...');
        
        $indexes = SmartIndex::where('confidence_score', '>=', 0.5)->get();
        $consolidated = 0;
        
        foreach ($indexes as $index) {
            $similar = SmartIndex::where('id', '!=', $index->id)
                ->where('confidence_score', '>=', 0.5)
                ->get()
                ->filter(function($other) use ($index) {
                    similar_text($index->normalized_query, $other->normalized_query, $percent);
                    return $percent > 90; // 90% similitud
                });
            
            if ($similar->count() > 0) {
                // Consolidar en el índice con mayor uso
                $master = $similar->push($index)->sortByDesc('usage_count')->first();
                $others = $similar->reject(function($item) use ($master) {
                    return $item->id === $master->id;
                });
                
                foreach ($others as $other) {
                    $master->usage_count += $other->usage_count;
                    $master->confidence_score = max($master->confidence_score, $other->confidence_score);
                    
                    // Agregar variaciones de consulta
                    $variations = array_merge(
                        $master->similar_queries ?? [],
                        [$other->original_query, $other->normalized_query]
                    );
                    $master->similar_queries = array_unique($variations);
                    $master->save();
                    
                    $other->delete();
                    $consolidated++;
                }
            }
        }
        
        $this->line("🔗 Consolidados {$consolidated} índices similares");
    }

    private function updateStatistics($startDate)
    {
        $this->info('📈 Actualizando estadísticas...');
        
        $stats = [
            'total_indexes' => SmartIndex::count(),
            'high_confidence' => SmartIndex::where('confidence_score', '>=', 0.8)->count(),
            'verified_indexes' => SmartIndex::where('verified', true)->count(),
            'cache_hit_rate' => $this->calculateCacheHitRate($startDate),
            'average_response_time' => ChatbotAnalytics::where('created_at', '>=', $startDate)
                ->avg('response_time_ms'),
        ];
        
        $this->table(['Métrica', 'Valor'], [
            ['Total de Índices', $stats['total_indexes']],
            ['Alta Confianza', $stats['high_confidence']],
            ['Verificados', $stats['verified_indexes']],
            ['Tasa de Cache Hit', $stats['cache_hit_rate'] . '%'],
            ['Tiempo Promedio (ms)', round($stats['average_response_time'], 2)],
        ]);
    }

    private function calculateCacheHitRate($startDate)
    {
        $total = ChatbotAnalytics::where('created_at', '>=', $startDate)->count();
        if ($total == 0) return 0;
        
        $cached = ChatbotAnalytics::where('created_at', '>=', $startDate)
            ->where('response_method', 'smart_index')
            ->count();
            
        return round(($cached / $total) * 100, 2);
    }
}

<?php

namespace App\Services;

use App\Models\SmartIndex;
use App\Models\ChatbotAnalytics;

class SmartIndexingService
{
    private $nlpProcessor;
    
    public function __construct()
    {
        $this->nlpProcessor = new NLPProcessor();
    }

    public function findBestMatch($query, $userId = null)
    {
        $startTime = microtime(true);
        
        // 1. Normalizar query
        $normalizedQuery = $this->nlpProcessor->normalize($query);
        
        // 2. Extraer características
        $keywords = $this->nlpProcessor->extractKeywords($normalizedQuery);
        $entities = $this->nlpProcessor->extractEntities($normalizedQuery);
        
        // 3. Buscar coincidencias exactas o muy similares
        $exactMatch = $this->findExactMatch($normalizedQuery);
        if ($exactMatch && $exactMatch->confidence_score >= 0.8) {
            $exactMatch->incrementUsage();
            $this->logAnalytics($query, $exactMatch, 'smart_index', microtime(true) - $startTime, $userId);
            return $exactMatch->response;
        }
        
        // 4. Buscar coincidencias semánticas
        $semanticMatch = $this->findSemanticMatch($keywords, $entities, $normalizedQuery);
        if ($semanticMatch && $semanticMatch['score'] >= 0.75) {
            $match = $semanticMatch['index'];
            $match->incrementUsage();
            $this->logAnalytics($query, $match, 'smart_index', microtime(true) - $startTime, $userId, $semanticMatch['score']);
            return $match->response;
        }
        
        return null; // No se encontró coincidencia confiable
    }

    private function findExactMatch($normalizedQuery)
    {
        return SmartIndex::where('normalized_query', $normalizedQuery)
            ->highConfidence()
            ->orderByDesc('usage_count')
            ->first();
    }

    private function findSemanticMatch($keywords, $entities, $query)
    {
        $potentialMatches = SmartIndex::highConfidence()
            ->where('usage_count', '>=', 2) // Al menos usado 2 veces
            ->get();

        $bestMatch = null;
        $bestScore = 0;

        foreach ($potentialMatches as $index) {
            $score = $this->calculateSimilarityScore($keywords, $entities, $query, $index);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $index;
            }
        }

        return $bestMatch ? ['index' => $bestMatch, 'score' => $bestScore] : null;
    }

    private function calculateSimilarityScore($keywords, $entities, $query, $index)
    {
        $score = 0;
        
        // Similitud de keywords (40% del peso)
        $keywordSimilarity = $this->calculateKeywordSimilarity($keywords, $index->keywords);
        $score += $keywordSimilarity * 0.4;
        
        // Similitud de entidades (30% del peso)
        $entitySimilarity = $this->calculateEntitySimilarity($entities, $index->entities);
        $score += $entitySimilarity * 0.3;
        
        // Similitud de texto completo (30% del peso)
        $textSimilarity = $this->calculateTextSimilarity($query, $index->normalized_query);
        $score += $textSimilarity * 0.3;
        
        return $score;
    }

    private function calculateKeywordSimilarity($keywords1, $keywords2)
    {
        if (empty($keywords1) || empty($keywords2)) return 0;
        
        $intersection = array_intersect($keywords1, $keywords2);
        $union = array_unique(array_merge($keywords1, $keywords2));
        
        return count($intersection) / count($union); // Jaccard similarity
    }

    private function calculateEntitySimilarity($entities1, $entities2)
    {
        if (empty($entities1) || empty($entities2)) return 0;
        
        $score = 0;
        $totalTypes = 0;
        
        foreach ($entities1 as $type => $values1) {
            if (isset($entities2[$type])) {
                $values2 = $entities2[$type];
                $intersection = array_intersect($values1, $values2);
                $union = array_unique(array_merge($values1, $values2));
                $score += count($intersection) / count($union);
                $totalTypes++;
            }
        }
        
        return $totalTypes > 0 ? $score / $totalTypes : 0;
    }

    private function calculateTextSimilarity($text1, $text2)
    {
        similar_text($text1, $text2, $percent);
        return $percent / 100;
    }

    public function addToIndex($query, $response, $method = 'ollama', $userFeedback = null)
    {
        $normalizedQuery = $this->nlpProcessor->normalize($query);
        $keywords = $this->nlpProcessor->extractKeywords($normalizedQuery);
        $entities = $this->nlpProcessor->extractEntities($normalizedQuery);
        
        $confidenceScore = match($method) {
            'ollama' => 0.6, // Confianza media para respuestas generadas
            'verified' => 1.0, // Confianza máxima para respuestas verificadas
            default => 0.5
        };
        
        if ($userFeedback === true) {
            $confidenceScore = min(1.0, $confidenceScore + 0.2);
        } elseif ($userFeedback === false) {
            $confidenceScore = max(0.0, $confidenceScore - 0.3);
        }
        
        SmartIndex::create([
            'original_query' => $query,
            'normalized_query' => $normalizedQuery,
            'keywords' => $keywords,
            'entities' => $entities,
            'response' => $response,
            'confidence_score' => $confidenceScore,
            'auto_generated' => $method !== 'verified'
        ]);
    }

    private function logAnalytics($query, $index, $method, $responseTime, $userId, $similarityScore = null)
    {
        ChatbotAnalytics::create([
            'user_id' => $userId,
            'query' => $query,
            'normalized_query' => $index->normalized_query ?? $query,
            'response_method' => $method,
            'response' => $index->response ?? $index,
            'response_time_ms' => round($responseTime * 1000),
            'matched_keywords' => $index->keywords ?? [],
            'similarity_score' => $similarityScore,
            'session_id' => session()->getId()
        ]);
    }
}

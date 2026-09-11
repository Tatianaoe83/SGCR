<?php

namespace App\Services;

use App\Models\SmartIndex;
use App\Models\ChatbotAnalytics;
use Illuminate\Support\Facades\Log;

class SmartIndexingService
{
    /** Score mínimo para considerar candidato (anti-basura: 3 no basta). */
    private const MIN_SCORE_CANDIDATE = 4;

    /** Veces con score alto antes de servir la respuesta cacheada. */
    private const MIN_HITS_TO_VERIFY = 3;

    /** Confianza mínima para marcar verified. */
    private const MIN_CONFIDENCE_VERIFY = 0.78;

    private $nlpProcessor;

    public function __construct()
    {
        $this->nlpProcessor = new NLPProcessor();
    }

    public function findBestMatch($query, $userId = null)
    {
        $startTime = microtime(true);

        $normalizedQuery = $this->nlpProcessor->normalize($query);
        $keywords = $this->nlpProcessor->extractKeywords($normalizedQuery);
        $entities = $this->nlpProcessor->extractEntities($normalizedQuery);

        $exactMatch = $this->findExactMatch($normalizedQuery);
        if ($exactMatch && (float) $exactMatch->confidence_score >= self::MIN_CONFIDENCE_VERIFY) {
            $exactMatch->incrementUsage();
            $analyticsId = $this->logAnalytics($query, $exactMatch, 'smart_index', microtime(true) - $startTime, $userId);

            return [
                'response' => $exactMatch->response,
                'analytics_id' => $analyticsId,
                'method' => 'smart_index',
            ];
        }

        $semanticMatch = $this->findSemanticMatch($keywords, $entities, $normalizedQuery);
        if ($semanticMatch && $semanticMatch['score'] >= 0.75) {
            $match = $semanticMatch['index'];
            $match->incrementUsage();
            $analyticsId = $this->logAnalytics(
                $query,
                $match,
                'smart_index',
                microtime(true) - $startTime,
                $userId,
                $semanticMatch['score']
            );

            return [
                'response' => $match->response,
                'analytics_id' => $analyticsId,
                'method' => 'smart_index',
            ];
        }

        return null;
    }

    /**
     * Aprendizaje conservador desde feedback de usuario (preguntas abiertas).
     * Un solo 5★ no publica la respuesta: exige varios votos altos + umbral.
     *
     * @return array{action:string, verified?:bool, confidence?:float, id?:int}
     */
    public function recordOpenAnswerFeedback(
        ChatbotAnalytics $analytics,
        ?int $score,
        bool $helpful
    ): array {
        $method = (string) $analytics->response_method;

        if ($method === 'smart_index') {
            $index = SmartIndex::where('normalized_query', $this->nlpProcessor->normalize((string) $analytics->query))
                ->orderByDesc('verified')
                ->orderByDesc('confidence_score')
                ->first();
            if (!$index) {
                $index = SmartIndex::where('response', $analytics->response)->first();
            }
            if ($index) {
                if ($score !== null && $score <= 2) {
                    $this->applyNegativeVote($index, (int) $score);
                } else {
                    $index->updateConfidence($helpful);
                }

                return [
                    'action' => 'updated_existing_index',
                    'verified' => (bool) $index->verified,
                    'confidence' => (float) $index->confidence_score,
                    'id' => (int) $index->id,
                ];
            }
        }

        if (!$this->isOpenAnswerMethod($method)) {
            return ['action' => 'skipped_method'];
        }

        $query = trim((string) $analytics->query);
        $response = trim(strip_tags((string) $analytics->response));
        if (!$this->isEligibleOpenQuery($query) || mb_strlen($response) < 80) {
            return ['action' => 'skipped_quality'];
        }

        $score = $score ?? ($helpful ? 3 : 1);

        if ($score <= 2) {
            return $this->demoteOpenAnswer($query, $score);
        }

        // Score 3 = ruido / tibio: no aprender (usuarios libres).
        if ($score < self::MIN_SCORE_CANDIDATE) {
            return ['action' => 'ignored_mid_score'];
        }

        return $this->promoteOpenAnswerCandidate($query, $response, $score);
    }

    public function addToIndex($query, $response, $method = 'ollama', $userFeedback = null)
    {
        $normalizedQuery = $this->nlpProcessor->normalize($query);
        $keywords = $this->nlpProcessor->extractKeywords($normalizedQuery);
        $entities = $this->nlpProcessor->extractEntities($normalizedQuery);

        $confidenceScore = match ($method) {
            'ollama' => 0.6,
            'verified' => 1.0,
            default => 0.5
        };

        if ($userFeedback === true) {
            $confidenceScore = min(1.0, $confidenceScore + 0.2);
        } elseif ($userFeedback === false) {
            $confidenceScore = max(0.0, $confidenceScore - 0.3);
        }

        $entry = SmartIndex::query()->firstOrNew([
            'normalized_query' => $normalizedQuery,
        ]);

        $entry->original_query = $query;
        $entry->keywords = $keywords;
        $entry->entities = $entities;
        $entry->response = mb_substr((string) $response, 0, 8000);
        $entry->confidence_score = max((float) ($entry->confidence_score ?? 0), $confidenceScore);
        $entry->auto_generated = $method !== 'verified';
        if ($method === 'verified' || $userFeedback === true) {
            // verified solo si el método lo pide explícitamente; el path abierto usa promoteOpenAnswerCandidate.
            if ($method === 'verified') {
                $entry->verified = true;
            }
        }
        if (!$entry->exists) {
            $entry->usage_count = 1;
        }
        $entry->save();

        return $entry;
    }

    private function isOpenAnswerMethod(string $method): bool
    {
        return (bool) preg_match('/^(paid_ai|ollama)/i', $method);
    }

    private function isEligibleOpenQuery(string $query): bool
    {
        $q = mb_strtolower(trim($query));
        if (mb_strlen($q) < 12) {
            return false;
        }

        $words = preg_split('/\s+/u', $q) ?: [];
        if (count($words) < 3) {
            return false;
        }

        // Charla / navegación: no cachear como "respuesta abierta".
        if (preg_match('/^(hola|holi|gracias|ok|okay|sí|si|no|ayúdame|help)\b/u', $q)) {
            return false;
        }
        if (preg_match('/\b(lista|listado|mis procedimientos|unidades|áreas|areas)\b/u', $q)
            && preg_match('/\b(lista|listado|muéstrame|muestrame|dame)\b/u', $q)
        ) {
            return false;
        }

        return true;
    }

    private function promoteOpenAnswerCandidate(string $query, string $response, int $score): array
    {
        $normalized = $this->nlpProcessor->normalize($query);
        $entry = SmartIndex::query()->firstOrNew(['normalized_query' => $normalized]);

        $meta = is_array($entry->similar_queries) ? $entry->similar_queries : [];
        $meta['source'] = 'user_feedback_open';
        $meta['positive_count'] = (int) ($meta['positive_count'] ?? 0) + 1;
        $meta['scores'] = array_values(array_slice(array_merge($meta['scores'] ?? [], [$score]), -12));
        $meta['updated_at'] = now()->toIso8601String();

        $entry->original_query = $query;
        $entry->keywords = $this->nlpProcessor->extractKeywords($normalized);
        $entry->entities = $this->nlpProcessor->extractEntities($normalized);
        // Preferir la respuesta del voto más alto reciente.
        if (!$entry->exists || $score >= 5 || mb_strlen($response) > mb_strlen((string) $entry->response)) {
            $entry->response = mb_substr($response, 0, 8000);
        }
        $entry->auto_generated = true;
        $entry->similar_queries = $meta;
        $entry->last_used_at = now();

        $boost = 0.40 + (0.10 * min(5, $meta['positive_count'])) + ($score >= 5 ? 0.08 : 0.0);
        $entry->confidence_score = min(0.95, max((float) ($entry->confidence_score ?? 0), $boost));

        if (
            $meta['positive_count'] >= self::MIN_HITS_TO_VERIFY
            && $entry->confidence_score >= self::MIN_CONFIDENCE_VERIFY
            && $this->averageScore($meta['scores']) >= 4.0
        ) {
            $entry->verified = true;
        } else {
            // Nunca verificar con un solo click.
            if (!$entry->exists) {
                $entry->verified = false;
            }
        }

        if (!$entry->exists) {
            $entry->usage_count = 0;
        }

        $entry->save();

        Log::info('SmartIndex candidato abierto', [
            'id' => $entry->id,
            'positive_count' => $meta['positive_count'],
            'verified' => $entry->verified,
            'confidence' => $entry->confidence_score,
            'query' => mb_substr($query, 0, 120),
        ]);

        return [
            'action' => $entry->verified ? 'verified' : 'candidate',
            'verified' => (bool) $entry->verified,
            'confidence' => (float) $entry->confidence_score,
            'id' => (int) $entry->id,
        ];
    }

    private function demoteOpenAnswer(string $query, int $score): array
    {
        $normalized = $this->nlpProcessor->normalize($query);
        $entry = SmartIndex::where('normalized_query', $normalized)->first();
        if (!$entry) {
            return ['action' => 'demote_noop'];
        }

        $this->applyNegativeVote($entry, $score);

        return [
            'action' => 'demoted',
            'verified' => (bool) $entry->verified,
            'confidence' => (float) $entry->confidence_score,
            'id' => (int) $entry->id,
        ];
    }

    private function applyNegativeVote(SmartIndex $entry, int $score): void
    {
        $meta = is_array($entry->similar_queries) ? $entry->similar_queries : [];
        $meta['negative_count'] = (int) ($meta['negative_count'] ?? 0) + 1;
        $meta['scores'] = array_values(array_slice(array_merge($meta['scores'] ?? [], [$score]), -12));
        $entry->similar_queries = $meta;
        $entry->confidence_score = max(0.0, (float) $entry->confidence_score - 0.2);
        $entry->verified = false;

        if ($entry->auto_generated && $entry->confidence_score < 0.25) {
            $entry->delete();

            return;
        }

        $entry->save();
    }

    private function averageScore(array $scores): float
    {
        $scores = array_values(array_filter($scores, fn ($s) => is_numeric($s)));
        if ($scores === []) {
            return 0.0;
        }

        return array_sum($scores) / count($scores);
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
            ->where('usage_count', '>=', 2)
            ->limit(80)
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

        $keywordSimilarity = $this->calculateKeywordSimilarity($keywords, $index->keywords ?? []);
        $score += $keywordSimilarity * 0.4;

        $entitySimilarity = $this->calculateEntitySimilarity($entities, $index->entities ?? []);
        $score += $entitySimilarity * 0.3;

        $textSimilarity = $this->calculateTextSimilarity($query, $index->normalized_query);
        $score += $textSimilarity * 0.3;

        return $score;
    }

    private function calculateKeywordSimilarity($keywords1, $keywords2)
    {
        if (empty($keywords1) || empty($keywords2)) {
            return 0;
        }

        $intersection = array_intersect($keywords1, $keywords2);
        $union = array_unique(array_merge($keywords1, $keywords2));

        return count($union) ? count($intersection) / count($union) : 0;
    }

    private function calculateEntitySimilarity($entities1, $entities2)
    {
        if (empty($entities1) || empty($entities2)) {
            return 0;
        }

        $score = 0;
        $totalTypes = 0;

        foreach ($entities1 as $type => $values1) {
            if (isset($entities2[$type])) {
                $values2 = $entities2[$type];
                $intersection = array_intersect($values1, $values2);
                $union = array_unique(array_merge($values1, $values2));
                $score += count($union) ? count($intersection) / count($union) : 0;
                $totalTypes++;
            }
        }

        return $totalTypes > 0 ? $score / $totalTypes : 0;
    }

    private function calculateTextSimilarity($text1, $text2)
    {
        similar_text((string) $text1, (string) $text2, $percent);

        return $percent / 100;
    }

    private function logAnalytics($query, $index, $method, $responseTime, $userId, $similarityScore = null)
    {
        try {
            return ChatbotAnalytics::create([
                'user_id' => $userId,
                'query' => $query,
                'normalized_query' => $index->normalized_query ?? $query,
                'response_method' => $method,
                'response' => $index->response ?? $index,
                'response_time_ms' => round($responseTime * 1000),
                'matched_keywords' => $index->keywords ?? [],
                'similarity_score' => $similarityScore,
                'session_id' => session()->getId() ?: ('smart_' . uniqid()),
            ])->id;
        } catch (\Throwable $e) {
            Log::warning('SmartIndex analytics: ' . $e->getMessage());

            return null;
        }
    }
}

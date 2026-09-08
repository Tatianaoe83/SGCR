<?php

namespace App\Services;

use App\Models\ChatbotAnalytics;
use App\Models\ChatbotFeedback;
use App\Models\ChatbotLexicon;
use App\Models\Empleados;
use App\Models\PuestoTrabajo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Extrae términos cotidianos de chatbot_analytics / feedback y los guarda en chatbot_lexicon.
 */
class ChatbotLexiconLearner
{
    private const MIN_HITS_AUTO = 3;
    private const MIN_CONFIDENCE_AUTO = 0.72;
    private const CURSOR_KEY = 'bob_lexicon_last_analytics_id';

    public function __construct(
        private ChatbotLexiconService $lexicon,
        private PaidAIService $paidAI
    ) {
    }

    /**
     * @return array{created:int, updated:int, activated:int, scanned:int}
     */
    public function learn(?int $analyticsId = null, int $days = 30): array
    {
        if (!Schema::hasTable('chatbot_lexicon') || !Schema::hasTable('chatbot_analytics')) {
            return ['created' => 0, 'updated' => 0, 'activated' => 0, 'scanned' => 0];
        }

        $stats = ['created' => 0, 'updated' => 0, 'activated' => 0, 'scanned' => 0];
        $known = array_flip($this->lexicon->knownTermsFlat());
        $employeeTokens = $this->employeeNameTokens();
        $puestoRaices = $this->puestoRaices();

        $rows = $this->analyticsQuery($analyticsId, $days)->get();
        $stats['scanned'] = $rows->count();

        $buckets = [];
        foreach ($rows as $row) {
            $weight = $this->weightFor($row);
            foreach ($this->extractCandidates($row, $known) as $candidate) {
                $key = $candidate['category'] . '|' . $candidate['term'];
                if (!isset($buckets[$key])) {
                    $buckets[$key] = $candidate;
                    $buckets[$key]['hits'] = 0;
                    $buckets[$key]['examples'] = [];
                }
                $buckets[$key]['hits'] += $weight;
                $buckets[$key]['last_analytics_id'] = (int) $row->id;
                $buckets[$key]['examples'][] = mb_substr((string) $row->query, 0, 160);
                $buckets[$key]['examples'] = array_slice(array_unique($buckets[$key]['examples']), 0, 5);
            }
        }

        uasort($buckets, fn ($a, $b) => ($b['hits'] ?? 0) <=> ($a['hits'] ?? 0));
        $buckets = array_slice($buckets, 0, 120, true);

        foreach ($buckets as $candidate) {
            $classified = $this->classifyCandidate($candidate, $employeeTokens, $puestoRaices);
            if ($classified === null) {
                continue;
            }

            $result = $this->upsertEntry($classified);
            $stats[$result]++;
        }

        $this->enrichTopColloquialWithAi($buckets);
        $stats['activated'] += $this->activateRipeEntries();
        $this->lexicon->forgetCache();

        if ($analyticsId === null && $rows->isNotEmpty()) {
            Cache::put(self::CURSOR_KEY, (int) $rows->max('id'), 86400 * 40);
        }

        return $stats;
    }

    private function analyticsQuery(?int $analyticsId, int $days)
    {
        $q = ChatbotAnalytics::query()->with('feedback');

        if ($analyticsId) {
            return $q->where('id', $analyticsId);
        }

        $from = now()->subDays(max(1, $days));
        $cursor = (int) Cache::get(self::CURSOR_KEY, 0);

        return $q->where(function ($inner) use ($from, $cursor) {
            $inner->where('created_at', '>=', $from);
            if ($cursor > 0) {
                $inner->orWhere('id', '>', $cursor);
            }
        })->orderBy('id')->limit(250);
    }

    private function weightFor(ChatbotAnalytics $row): int
    {
        $weight = 1;
        $feedback = $row->feedback;
        if ($feedback instanceof ChatbotFeedback) {
            $weight += $feedback->helpful ? 3 : 1;
        }
        $method = (string) $row->response_method;
        if (in_array($method, ['no_content_found', 'conversation_clarification', 'politicas_ocultas'], true)) {
            $weight += 1;
        }

        return $weight;
    }

    private function extractCandidates(ChatbotAnalytics $row, array $known): array
    {
        $query = trim((string) $row->query);
        if ($query === '') {
            return [];
        }

        $folded = $this->lexicon->fold($query);
        $method = (string) $row->response_method;
        $out = [];

        if (
            str_starts_with($method, 'conversation_greeting')
            || preg_match('/^(hola|holi|holis|buenas|hey|qué tal|que tal)\b/u', $folded)
        ) {
            if (str_word_count($folded) <= 6) {
                $term = mb_substr($folded, 0, 40);
                if ($term !== '' && !isset($known[$term])) {
                    $out[] = [
                        'category' => ChatbotLexicon::CAT_GREETING,
                        'term' => $term,
                        'mapped_to' => null,
                        'confidence' => 0.8,
                    ];
                }
            }
        }

        $ngrams = $this->ngrams($folded, 1, 4);
        foreach ($ngrams as $gram) {
            if (isset($known[$gram]) || mb_strlen($gram) < 3) {
                continue;
            }
            $words = explode(' ', $gram);
            if (count($words) >= 2) {
                $out[] = [
                    'category' => ChatbotLexicon::CAT_COLLOQUIAL,
                    'term' => $gram,
                    'mapped_to' => null,
                    'confidence' => 0.4,
                ];
            } else {
                $out[] = [
                    'category' => ChatbotLexicon::CAT_SYNONYM,
                    'term' => $gram,
                    'mapped_to' => null,
                    'confidence' => 0.4,
                ];
            }
        }

        if (str_contains($method, 'chitchat') || $method === 'conversation_only') {
            $wc = str_word_count($folded);
            if ($wc >= 1 && $wc <= 5 && !isset($known[$folded])) {
                $out[] = [
                    'category' => ChatbotLexicon::CAT_CHITCHAT,
                    'term' => mb_substr($folded, 0, 40),
                    'mapped_to' => $this->guessChitChatCategory($folded),
                    'confidence' => 0.75,
                ];
            }
        }

        if (in_array($method, ['no_content_found', 'conversation_clarification'], true)) {
            foreach ($this->ngrams($folded, 1, 1) as $gram) {
                if (isset($known[$gram]) || mb_strlen($gram) < 4) {
                    continue;
                }
                $out[] = [
                    'category' => ChatbotLexicon::CAT_SEARCH_STOPWORD,
                    'term' => $gram,
                    'mapped_to' => null,
                    'confidence' => 0.45,
                ];
            }
        }

        if (preg_match('/^(me puedes|me podrias|podrias decirme|quiero saber|necesito saber|quisiera saber|me gustaria saber)\b/u', $folded, $m)) {
            $pre = $m[1];
            if (!isset($known[$this->lexicon->fold($pre)])) {
                $out[] = [
                    'category' => ChatbotLexicon::CAT_DIRECTORY_PREAMBLE,
                    'term' => $pre,
                    'mapped_to' => null,
                    'confidence' => 0.7,
                ];
            }
        }

        if (
            str_contains($method, 'elemento')
            || str_contains($method, 'document')
            || $method === 'paid_ai'
        ) {
            $wc = str_word_count($folded);
            if ($wc <= 8) {
                $aspect = $this->guessFollowupAspect($folded);
                if ($aspect) {
                    $out[] = [
                        'category' => ChatbotLexicon::CAT_FOLLOWUP_ASPECT,
                        'term' => $aspect['term'],
                        'mapped_to' => $aspect['aspect'],
                        'confidence' => 0.8,
                    ];
                }
                if (preg_match('/\b(explica|sigue|continua|detalla|profundiza|mas)\w*\b/u', $folded, $vm)) {
                    $verb = $this->lexicon->fold($vm[1]);
                    if (!isset($known[$verb])) {
                        $out[] = [
                            'category' => ChatbotLexicon::CAT_FOLLOWUP_VERB,
                            'term' => $verb,
                            'mapped_to' => 'seguimiento',
                            'confidence' => 0.75,
                        ];
                    }
                }
            }
        }

        if (
            str_contains($method, 'catalog')
            || $method === 'generate_catalog'
            || preg_match('/\b(procedimientos? de|listado de)\b/u', $folded)
        ) {
            if (preg_match('/\b(?:de|del|sobre)\s+([a-z]{4,20}(?:\s+[a-z]{4,20})?)\b/u', $folded, $tm)) {
                $hint = $this->lexicon->fold($tm[1]);
                if (!isset($known[$hint]) && !preg_match('/^(procedimiento|documento|elemento)/u', $hint)) {
                    $out[] = [
                        'category' => ChatbotLexicon::CAT_NO_RESULT_HINT,
                        'term' => $hint,
                        'mapped_to' => $hint,
                        'confidence' => 0.55,
                    ];
                }
            }
        }

        return $out;
    }

    private function classifyCandidate(array $candidate, array $employeeTokens, array $puestoRaices): ?array
    {
        $term = $this->lexicon->fold((string) ($candidate['term'] ?? ''));
        if ($term === '' || mb_strlen($term) < 3) {
            return null;
        }

        $category = $candidate['category'];
        $hits = (int) ($candidate['hits'] ?? 1);

        if ($category === ChatbotLexicon::CAT_GREETING) {
            $candidate['confidence'] = max((float) $candidate['confidence'], 0.78);
            $candidate['status'] = $hits >= 2 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_SYNONYM) {
            $typo = $this->closestCanonicalWord($term);
            if ($typo) {
                $candidate['category'] = ChatbotLexicon::CAT_TECHNICAL;
                $candidate['mapped_to'] = $typo['target'];
                $candidate['confidence'] = $typo['score'];
                $candidate['status'] = $typo['score'] >= 0.82 && $hits >= 2
                    ? ChatbotLexicon::STATUS_ACTIVE
                    : ChatbotLexicon::STATUS_PENDING;

                return $candidate;
            }

            if (isset($puestoRaices[$term]) && !isset($this->lexicon->defaultGenericRoles()[$term])) {
                $candidate['category'] = ChatbotLexicon::CAT_GENERIC_ROLE;
                $candidate['mapped_to'] = $term;
                $candidate['label'] = 'los ' . $term . 'es';
                $candidate['meta'] = ['pattern' => '/\b' . preg_quote($term, '/') . '\w*\b/u'];
                $candidate['confidence'] = 0.8;
                $candidate['status'] = $hits >= 2 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

                return $candidate;
            }

            if (
                $hits >= 4
                && !isset($employeeTokens[$term])
                && preg_match('/\b(correo|email|directorio|quien|ocupa|llama)\b/u', implode(' ', $candidate['examples'] ?? []))
            ) {
                $candidate['category'] = ChatbotLexicon::CAT_EMAIL_STOPWORD;
                $candidate['mapped_to'] = null;
                $candidate['confidence'] = 0.7;
                $candidate['status'] = $hits >= 5 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

                return $candidate;
            }

            $matrixHit = $this->closestMatrixTerm($term);
            if ($matrixHit && $matrixHit['score'] >= 0.84) {
                $candidate['category'] = ChatbotLexicon::CAT_MATRIX_ROLE;
                $candidate['mapped_to'] = $matrixHit['target'];
                $candidate['confidence'] = $matrixHit['score'];
                $candidate['status'] = $hits >= 3 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

                return $candidate;
            }

            return null;
        }

        if ($category === ChatbotLexicon::CAT_COLLOQUIAL) {
            $mapped = $this->closestPhrase($term);
            if (!$mapped || !$this->lexicon->isSafeColloquialInjection($term, $mapped['target'])) {
                return null;
            }
            $candidate['mapped_to'] = $mapped['target'];
            $candidate['confidence'] = $mapped['score'];
            $candidate['status'] = $mapped['score'] >= self::MIN_CONFIDENCE_AUTO && $hits >= self::MIN_HITS_AUTO
                ? ChatbotLexicon::STATUS_ACTIVE
                : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_CHITCHAT) {
            $cat = (string) ($candidate['mapped_to'] ?: $this->guessChitChatCategory($term));
            if (!in_array($cat, ['queja', 'risa', 'cortesia', 'saludo', 'despedida'], true)) {
                return null;
            }
            $candidate['mapped_to'] = $cat;
            $candidate['confidence'] = max((float) $candidate['confidence'], 0.75);
            $candidate['status'] = $hits >= 2 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_FOLLOWUP_ASPECT) {
            $aspect = $this->lexicon->fold((string) ($candidate['mapped_to'] ?? ''));
            $allowed = ['riesgos', 'evidencias', 'objetivo', 'alcance', 'responsable', 'definiciones', 'actividades', 'registros', 'controles'];
            if (!in_array($aspect, $allowed, true)) {
                return null;
            }
            $candidate['status'] = $hits >= 2 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_FOLLOWUP_VERB) {
            $candidate['mapped_to'] = 'seguimiento';
            $candidate['status'] = $hits >= 3 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_SEARCH_STOPWORD) {
            if (isset($employeeTokens[$term]) || isset($puestoRaices[$term])) {
                return null;
            }
            $candidate['status'] = $hits >= 6 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;
            $candidate['confidence'] = 0.6;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_DIRECTORY_PREAMBLE) {
            $candidate['status'] = $hits >= 2 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        if ($category === ChatbotLexicon::CAT_NO_RESULT_HINT) {
            $candidate['status'] = $hits >= 5 ? ChatbotLexicon::STATUS_ACTIVE : ChatbotLexicon::STATUS_PENDING;

            return $candidate;
        }

        return null;
    }

    private function guessChitChatCategory(string $folded): string
    {
        if (preg_match('/\b(gracias|perfecto|vale|ok|sale|listo|de acuerdo|entendido)\b/u', $folded)) {
            return 'cortesia';
        }
        if (preg_match('/\b(adios|bye|chao|chau|nos vemos|hasta luego)\b/u', $folded)) {
            return 'despedida';
        }
        if (preg_match('/\b(jaja|jeje|xd|lol)\b/u', $folded)) {
            return 'risa';
        }
        if (preg_match('/\b(como estas|que tal|todo bien)\b/u', $folded)) {
            return 'saludo';
        }
        if (preg_match('/\b(no|nel|nop|mal|equivoc|cancela|olvidalo|otra cosa)\b/u', $folded)) {
            return 'queja';
        }

        return 'cortesia';
    }

    private function guessFollowupAspect(string $folded): ?array
    {
        $map = [
            'objetivo' => 'objetivo',
            'objetivos' => 'objetivo',
            'alcance' => 'alcance',
            'responsable' => 'responsable',
            'responsables' => 'responsable',
            'riesgo' => 'riesgos',
            'riesgos' => 'riesgos',
            'evidencia' => 'evidencias',
            'evidencias' => 'evidencias',
            'definicion' => 'definiciones',
            'definiciones' => 'definiciones',
            'glosario' => 'definiciones',
            'paso' => 'actividades',
            'pasos' => 'actividades',
            'actividad' => 'actividades',
            'actividades' => 'actividades',
            'registro' => 'registros',
            'registros' => 'registros',
            'control' => 'controles',
            'controles' => 'controles',
        ];
        foreach ($map as $word => $aspect) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $folded)) {
                return ['term' => $word, 'aspect' => $aspect];
            }
        }

        return null;
    }

    private function closestCanonicalWord(string $term): ?array
    {
        $best = null;
        $bestScore = 0.0;
        $targets = array_merge(
            array_keys($this->lexicon->defaultWordMap()),
            array_values($this->lexicon->defaultWordMap()),
            ChatbotLexiconService::CANONICAL_TARGETS
        );
        foreach (array_unique($targets) as $target) {
            $t = $this->lexicon->fold((string) $target);
            if ($t === '' || abs(mb_strlen($t) - mb_strlen($term)) > 3) {
                continue;
            }
            similar_text($term, $t, $pct);
            $score = $pct / 100;
            if (levenshtein($term, $t) === 1 && mb_strlen($term) >= 5) {
                $score = max($score, 0.9);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $t;
            }
        }

        if ($bestScore < 0.78 || $best === null || $best === $term) {
            return null;
        }

        $mapped = $this->lexicon->defaultWordMap()[$best] ?? $best;

        return ['target' => $mapped, 'score' => round($bestScore, 3)];
    }

    private function closestPhrase(string $term): ?array
    {
        $best = null;
        $bestScore = 0.0;
        foreach ($this->lexicon->defaultPhraseMap() as $from => $to) {
            similar_text($term, $this->lexicon->fold($from), $pct);
            $score = $pct / 100;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $to;
            }
        }
        if ($bestScore < 0.90 || $best === null) {
            return null;
        }

        return ['target' => $best, 'score' => round($bestScore, 3)];
    }

    private function closestMatrixTerm(string $term): ?array
    {
        $best = null;
        $bestScore = 0.0;
        foreach ($this->lexicon->defaultMatrixRoleTerms() as $target) {
            similar_text($term, $this->lexicon->fold($target), $pct);
            $score = $pct / 100;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $target;
            }
        }
        if ($bestScore < 0.84 || $best === null || $best === $term) {
            return null;
        }

        return ['target' => $best, 'score' => round($bestScore, 3)];
    }

    private function classifyWithAi(string $term, array $examples): ?array
    {
        if (empty(config('services.ai.api_key'))) {
            return null;
        }

        $prompt = "Clasifica este término de usuario del chatbot SGC.\n"
            . "Término: {$term}\n"
            . "Ejemplos: " . implode(' | ', array_slice($examples, 0, 3)) . "\n"
            . "Responde SOLO JSON: {\"mapped_to\":\"objetivo|alcance|responsable|riesgos|definiciones|listado|directorio|correo|null\",\"confidence\":0.0}\n"
            . "mapped_to null si no es un sinónimo claro de esos conceptos.";

        try {
            $raw = $this->paidAI->generateRawResponse(
                'Eres un clasificador. No inventes. JSON válido.',
                $prompt,
                12
            );
            if (!is_string($raw) || $raw === $prompt) {
                return null;
            }
            if (!preg_match('/\{.*\}/s', $raw, $m)) {
                return null;
            }
            $data = json_decode($m[0], true);
            $mapped = trim((string) ($data['mapped_to'] ?? ''));
            $conf = (float) ($data['confidence'] ?? 0);
            $allowed = array_map(fn ($t) => $this->lexicon->fold($t), ChatbotLexiconService::CANONICAL_TARGETS);
            if ($mapped === '' || $mapped === 'null' || !in_array($this->lexicon->fold($mapped), $allowed, true)) {
                return null;
            }
            if ($conf < 0.7) {
                return null;
            }

            return ['target' => $this->lexicon->fold($mapped), 'score' => min(0.95, $conf)];
        } catch (\Throwable $e) {
            Log::info('Lexicon AI skip: ' . $e->getMessage());

            return null;
        }
    }

    private function upsertEntry(array $data): string
    {
        $term = mb_substr($this->lexicon->fold((string) $data['term']), 0, 191);
        $entry = ChatbotLexicon::query()->firstOrNew([
            'category' => $data['category'],
            'term' => $term,
        ]);

        $isNew = !$entry->exists;
        $hits = (int) ($data['hits'] ?? 1);
        $entry->hits = $isNew ? $hits : $entry->hits + $hits;
        $entry->mapped_to = $data['mapped_to'] ?? $entry->mapped_to;
        $entry->label = $data['label'] ?? $entry->label;
        $entry->meta = $data['meta'] ?? $entry->meta;
        $entry->confidence = max((float) $entry->confidence, (float) ($data['confidence'] ?? 0));
        $entry->source = 'learned';
        $entry->last_analytics_id = $data['last_analytics_id'] ?? $entry->last_analytics_id;
        $entry->last_seen_at = now();

        $wanted = $data['status'] ?? ChatbotLexicon::STATUS_PENDING;
        if ($entry->status !== ChatbotLexicon::STATUS_REJECTED) {
            if ($entry->status !== ChatbotLexicon::STATUS_ACTIVE) {
                $entry->status = $wanted;
            }
        }

        $examples = $data['examples'] ?? [];
        $meta = $entry->meta ?? [];
        $meta['examples'] = array_slice(array_unique(array_merge($meta['examples'] ?? [], $examples)), 0, 8);
        $entry->meta = $meta;
        $entry->save();

        return $isNew ? 'created' : 'updated';
    }

    private function activateRipeEntries(): int
    {
        $ripe = ChatbotLexicon::query()
            ->where('status', ChatbotLexicon::STATUS_PENDING)
            ->where('hits', '>=', self::MIN_HITS_AUTO)
            ->where('confidence', '>=', self::MIN_CONFIDENCE_AUTO)
            ->whereNotNull('mapped_to')
            ->get();

        $n = 0;
        foreach ($ripe as $row) {
            $row->status = ChatbotLexicon::STATUS_ACTIVE;
            $row->save();
            $n++;
        }

        $greetings = ChatbotLexicon::query()
            ->where('status', ChatbotLexicon::STATUS_PENDING)
            ->where('category', ChatbotLexicon::CAT_GREETING)
            ->where('hits', '>=', 2)
            ->get();
        foreach ($greetings as $row) {
            $row->status = ChatbotLexicon::STATUS_ACTIVE;
            $row->save();
            $n++;
        }

        return $n;
    }

    private function enrichTopColloquialWithAi(array $buckets): void
    {
        if (empty(config('services.ai.api_key'))) {
            return;
        }

        $top = [];
        foreach ($buckets as $candidate) {
            if (($candidate['category'] ?? '') !== ChatbotLexicon::CAT_COLLOQUIAL) {
                continue;
            }
            if ((int) ($candidate['hits'] ?? 0) < 2) {
                continue;
            }
            $top[] = $candidate;
            if (count($top) >= 8) {
                break;
            }
        }

        foreach ($top as $candidate) {
            $term = $this->lexicon->fold((string) $candidate['term']);
            $exists = ChatbotLexicon::query()
                ->where('category', ChatbotLexicon::CAT_COLLOQUIAL)
                ->where('term', $term)
                ->whereNotNull('mapped_to')
                ->exists();
            if ($exists) {
                continue;
            }
            $ai = $this->classifyWithAi($term, $candidate['examples'] ?? []);
            if (!$ai) {
                continue;
            }
            $candidate['mapped_to'] = $ai['target'];
            $candidate['confidence'] = $ai['score'];
            $candidate['status'] = $ai['score'] >= self::MIN_CONFIDENCE_AUTO
                ? ChatbotLexicon::STATUS_ACTIVE
                : ChatbotLexicon::STATUS_PENDING;
            $this->upsertEntry($candidate);
        }
    }

    private function ngrams(string $folded, int $min, int $max): array
    {
        $noise = [
            'de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'y', 'o', 'en', 'a', 'al',
            'que', 'se', 'es', 'son', 'me', 'mi', 'tu', 'su', 'por', 'para', 'con', 'sin',
            'el', 'lo', 'le', 'les', 'te', 'nos', 'ya', 'si', 'no', 'ok', 'va', 'hay',
        ];
        $words = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', $folded) ?: [],
            fn ($w) => mb_strlen($w) >= 2 && !in_array($w, $noise, true)
        ));
        $words = array_slice($words, 0, 12);
        $out = [];
        $n = count($words);
        for ($size = $min; $size <= $max; $size++) {
            for ($i = 0; $i <= $n - $size; $i++) {
                $out[] = implode(' ', array_slice($words, $i, $size));
            }
        }

        return array_values(array_unique($out));
    }

    private function employeeNameTokens(): array
    {
        $tokens = [];
        try {
            Empleados::query()
                ->select(['nombres', 'apellido_paterno', 'apellido_materno'])
                ->orderBy('id_empleado')
                ->limit(1500)
                ->get()
                ->each(function ($e) use (&$tokens) {
                    foreach ([$e->nombres, $e->apellido_paterno, $e->apellido_materno] as $part) {
                        foreach (preg_split('/[^\p{L}]+/u', $this->lexicon->fold((string) $part)) ?: [] as $t) {
                            if (mb_strlen($t) >= 3) {
                                $tokens[$t] = true;
                            }
                        }
                    }
                });
        } catch (\Throwable $e) {
            return [];
        }

        return $tokens;
    }

    private function puestoRaices(): array
    {
        $raices = [];
        try {
            foreach (PuestoTrabajo::query()->pluck('nombre') as $nombre) {
                $fold = $this->lexicon->fold((string) $nombre);
                $first = explode(' ', $fold)[0] ?? '';
                if (mb_strlen($first) >= 4) {
                    $raices[$first] = true;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $raices;
    }
}

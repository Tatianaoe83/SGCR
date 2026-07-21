<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Genera embeddings (vectores) de texto usando la API del proveedor de IA configurado
 * y calcula similitud coseno. Es la base de la búsqueda semántica del chatbot: en vez de
 * decidir por palabras específicas, se compara el significado de la pregunta contra el de
 * los documentos.
 *
 * Degradación limpia: si la API falla, embed() devuelve null y el llamador cae a la
 * búsqueda por keyword existente.
 */
class EmbeddingService
{
    private $provider;
    private $apiKey;
    private $model;
    private $baseUrl;
    private $timeout;

    // Caché largo: el embedding de un mismo texto nunca cambia (para un modelo dado).
    private const CACHE_TTL = 86400; // 24h

    public function __construct()
    {
        $this->provider = config('services.ai.provider', 'openai');
        $this->apiKey = config('services.ai.api_key');
        $this->model = config('services.ai.embed_model', 'text-embedding-3-small');
        $this->timeout = config('services.ai.timeout', 30);

        $baseUrls = [
            'openai' => 'https://api.openai.com/v1/',
        ];
        $this->baseUrl = $baseUrls[$this->provider] ?? $baseUrls['openai'];
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Embedding de un texto. Cacheado por hash del texto+modelo.
     * Devuelve null si no hay API key, el texto está vacío, o la API falla.
     */
    public function embed(string $text): ?array
    {
        $text = trim($text);
        if ($text === '' || empty($this->apiKey)) {
            return null;
        }

        $cacheKey = 'embed_' . md5($this->model . '|' . $text);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($text) {
            $vectors = $this->requestEmbeddings([$text]);
            return $vectors[0] ?? null;
        });
    }

    /**
     * Embeddings de varios textos en una sola llamada (para el backfill).
     * Devuelve array indexado igual que la entrada; posición null si algo falló.
     * No usa caché porque se llama sobre contenido único de documentos.
     */
    public function embedBatch(array $texts): array
    {
        $texts = array_values(array_map('trim', $texts));
        if (empty($texts) || empty($this->apiKey)) {
            return array_fill(0, count($texts), null);
        }

        // La API rechaza cadenas vacías; las mandamos como espacio y luego anulamos.
        $sanitized = array_map(fn($t) => $t === '' ? ' ' : $t, $texts);
        $vectors = $this->requestEmbeddings($sanitized);

        $result = [];
        foreach ($texts as $i => $original) {
            $result[$i] = ($original === '') ? null : ($vectors[$i] ?? null);
        }
        return $result;
    }

    /**
     * Similitud coseno entre dos vectores. Rango -1..1 (para embeddings normalizados, 0..1).
     */
    public function cosine(?array $a, ?array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        foreach ($a as $i => $va) {
            $vb = $b[$i];
            $dot += $va * $vb;
            $normA += $va * $va;
            $normB += $vb * $vb;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Llamada cruda a la API de embeddings. Devuelve lista de vectores en el orden de entrada.
     */
    private function requestEmbeddings(array $inputs): array
    {
        try {
            if ($this->provider !== 'openai') {
                Log::warning("EmbeddingService: proveedor '{$this->provider}' no soportado para embeddings.");
                return [];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . 'embeddings', [
                    'model' => $this->model,
                    'input' => $inputs,
                ]);

            if (!$response->successful()) {
                Log::error('EmbeddingService API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return [];
            }

            $data = $response->json('data') ?? [];
            // La API respeta el orden, pero ordenamos por 'index' por seguridad.
            usort($data, fn($x, $y) => ($x['index'] ?? 0) <=> ($y['index'] ?? 0));

            return array_map(fn($row) => $row['embedding'] ?? null, $data);
        } catch (\Throwable $e) {
            Log::error('EmbeddingService excepción: ' . $e->getMessage());
            return [];
        }
    }
}

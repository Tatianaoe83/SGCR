<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Illuminate\Support\Str;

class WordDocument extends Model
{
    use HasFactory, Searchable;

    protected $table = 'word_documents';

    protected $fillable = [
        'contenido_texto'
    ];

    protected $casts = [
        // No hay campos que necesiten casting especial
    ];

    /**
     * Scout: Indexar todos los documentos que tengan contenido
     */
    public function shouldBeSearchable()
    {
        return !empty($this->contenido_texto);
    }

    /**
     * Scout: Configurar datos para indexación en Algolia
     */
    public function toSearchableArray()
    {
        // Limitar contenido para no exceder el límite de 10KB de Algolia
        $content = $this->getNormalizedContent();
        $maxContentLength = 8000; // Dejar espacio para otros campos
        
        if (strlen($content) > $maxContentLength) {
            $content = substr($content, 0, $maxContentLength) . '...';
        }
        
        return [
            'id' => $this->id,
            'title' => 'Documento ' . $this->id,
            'content' => $content,
            'keywords' => array_slice($this->extractKeywords(), 0, 10), // Máximo 10 keywords
            'content_length' => strlen($this->contenido_texto ?? ''),
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
            // Campos adicionales para Algolia
            'objectID' => $this->id, // Requerido por Algolia
            '_tags' => ['documento', 'word'], // Para filtrado
        ];
    }

    /**
     * Obtener contenido normalizado para búsqueda
     */
    public function getNormalizedContent()
    {
        if (!$this->contenido_texto) {
            return '';
        }

        // Normalizar contenido: minúsculas, sin caracteres especiales
        $content = strtolower($this->contenido_texto);
        $content = preg_replace('/[^\w\s\ñáéíóúü]/u', ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }

    /**
     * Extraer keywords automáticamente del contenido
     */
    public function extractKeywords()
    {
        $content = $this->getNormalizedContent();
        
        if (empty($content)) {
            return [];
        }

        // Palabras vacías en español
        $stopWords = [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son',
            'con', 'para', 'como', 'las', 'del', 'los', 'una', 'al', 'pero', 'sus', 'le', 'ya', 'todo', 'esta', 'fue',
            'han', 'ser', 'su', 'hacer', 'otros', 'puede', 'tiene', 'más', 'muy', 'hasta', 'desde', 'cuando', 'entre'
        ];

        // Extraer palabras de 3+ caracteres
        $words = explode(' ', $content);
        $keywords = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 3 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }

        // Obtener las palabras más frecuentes (máximo 20)
        $wordCounts = array_count_values($keywords);
        arsort($wordCounts);
        
        return array_keys(array_slice($wordCounts, 0, 20));
    }

    /**
     * Dividir documentos largos en chunks inteligentes
     */
    public function getIntelligentChunks()
    {
        if (!$this->contenido_texto) {
            return [];
        }

        $content = $this->contenido_texto;
        $maxChunkSize = 500; // Caracteres por chunk
        $chunks = [];

        // Si el documento es corto, devolver como un solo chunk
        if (strlen($content) <= $maxChunkSize) {
            return [
                [
                    'content' => $content,
                    'position' => 0,
                    'size' => strlen($content)
                ]
            ];
        }

        // Dividir por párrafos primero
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $currentChunk = '';
        $chunkPosition = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            
            if (empty($paragraph)) {
                continue;
            }

            // Si agregar este párrafo excede el tamaño máximo, guardar chunk actual
            if (strlen($currentChunk . $paragraph) > $maxChunkSize && !empty($currentChunk)) {
                $chunks[] = [
                    'content' => trim($currentChunk),
                    'position' => $chunkPosition,
                    'size' => strlen($currentChunk)
                ];
                
                $chunkPosition += strlen($currentChunk);
                $currentChunk = '';
            }

            $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
        }

        // Agregar el último chunk si no está vacío
        if (!empty($currentChunk)) {
            $chunks[] = [
                'content' => trim($currentChunk),
                'position' => $chunkPosition,
                'size' => strlen($currentChunk)
            ];
        }

        return $chunks;
    }

    /**
     * Configurar el nombre del índice en Algolia
     */
    public function searchableAs()
    {
        return 'word_documents_index';
    }

    /**
     * Configurar qué campos usar para búsqueda en Algolia
     */
    public function getScoutKey()
    {
        return $this->id;
    }

    /**
     * Obtener el valor de la clave Scout
     */
    public function getScoutKeyName()
    {
        return 'id';
    }
    /**
     * Obtener atributo title para el chatbot
     */
    public function getTitleAttribute()
    {
        return 'Documento ' . $this->id;
    }

    /**
     * Observer para auto-indexación
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-indexar cuando se actualiza el contenido
        static::updated(function ($wordDocument) {
            $original = $wordDocument->getOriginal();
            
            // Si cambió el contenido, actualizar en el índice
            if ($original['contenido_texto'] !== $wordDocument->contenido_texto) {
                if (!empty($wordDocument->contenido_texto)) {
                    $wordDocument->searchable();
                } else {
                    $wordDocument->unsearchable();
                }
            }
        });

        // Remover del índice al eliminar el registro
        static::deleting(function ($wordDocument) {
            $wordDocument->unsearchable();
        });
    }

    /**
     * Buscar documentos con scoring avanzado
     */
    public static function searchWithAdvancedScoring($query, $limit = 10)
    {
        try {
            // Usar Scout si está disponible
            $scoutResults = static::search($query)->take($limit)->get();
            
            if ($scoutResults->isNotEmpty()) {
                return $scoutResults->map(function ($doc) use ($query) {
                    return [
                        'document' => $doc,
                        'score' => $doc->calculateRelevanceScore($query),
                        'matched_chunks' => $doc->findMatchedChunks($query)
                    ];
                })->sortByDesc('score')->values();
            }
        } catch (\Exception $e) {
            \Log::warning('Scout search failed, using fallback: ' . $e->getMessage());
        }

        // Fallback: búsqueda manual
        return static::manualSearch($query, $limit);
    }

    /**
     * Búsqueda manual como fallback
     */
    public static function manualSearch($query, $limit = 10)
    {
        $normalizedQuery = strtolower(trim($query));
        
        return static::where(function ($queryBuilder) use ($normalizedQuery) {
                $queryBuilder->whereRaw('LOWER(contenido_texto) LIKE ?', ["%{$normalizedQuery}%"]);
            })
            ->take($limit)
            ->get()
            ->map(function ($doc) use ($query) {
                return [
                    'document' => $doc,
                    'score' => $doc->calculateRelevanceScore($query),
                    'matched_chunks' => $doc->findMatchedChunks($query)
                ];
            })
            ->sortByDesc('score')
            ->values();
    }

    /**
     * Calcular score de relevancia
     */
    public function calculateRelevanceScore($query)
    {
        $score = 0;
        $normalizedQuery = strtolower(trim($query));
        $queryWords = explode(' ', $normalizedQuery);
        
        // Score por coincidencias en el título
        $title = strtolower($this->title ?? '');
        foreach ($queryWords as $word) {
            if (strpos($title, $word) !== false) {
                $score += 10; // Peso alto para títulos
            }
        }

        // Score por coincidencias en el contenido
        $content = $this->getNormalizedContent();
        foreach ($queryWords as $word) {
            $occurrences = substr_count($content, $word);
            $score += $occurrences * 2; // Peso medio para contenido
        }

        // Score por keywords
        $keywords = $this->extractKeywords();
        foreach ($queryWords as $word) {
            if (in_array($word, $keywords)) {
                $score += 5; // Peso medio para keywords
            }
        }

        // Bonus por documento más reciente
        $daysSinceCreated = now()->diffInDays($this->created_at);
        $score += max(0, 10 - $daysSinceCreated); // Hasta 10 puntos por ser reciente

        return $score;
    }

    /**
     * Encontrar chunks que coinciden con la consulta
     */
    public function findMatchedChunks($query)
    {
        $normalizedQuery = strtolower(trim($query));
        $queryWords = explode(' ', $normalizedQuery);
        $chunks = $this->getIntelligentChunks();
        $matchedChunks = [];

        foreach ($chunks as $chunk) {
            $chunkContent = strtolower($chunk['content']);
            $matches = 0;
            
            foreach ($queryWords as $word) {
                if (strpos($chunkContent, $word) !== false) {
                    $matches++;
                }
            }

            if ($matches > 0) {
                $matchedChunks[] = [
                    'content' => $chunk['content'],
                    'position' => $chunk['position'],
                    'size' => $chunk['size'],
                    'matches' => $matches,
                    'relevance' => $matches / count($queryWords)
                ];
            }
        }

        // Ordenar por relevancia
        usort($matchedChunks, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        return array_slice($matchedChunks, 0, 3); // Máximo 3 chunks más relevantes
    }
}

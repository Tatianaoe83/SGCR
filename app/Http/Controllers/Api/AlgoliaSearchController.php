<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WordDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Scout\Scout;
use Algolia\AlgoliaSearch\Api\SearchClient;

class AlgoliaSearchController extends Controller
{
    private $algolia;

    public function __construct()
    {
        // Inicializar cliente de Algolia si está configurado
        if (config('scout.driver') === 'algolia') {
            $this->algolia = SearchClient::create(
                config('scout.algolia.id'),
                config('scout.algolia.secret')
            );
        }
    }

    /**
     * Obtener información del índice de Algolia
     */
    public function indexInfo(): JsonResponse
    {
        try {
            if (!$this->algolia) {
                return response()->json([
                    'error' => 'Algolia no está configurado',
                    'driver' => config('scout.driver')
                ], 400);
            }

            try {
                // En Algolia 4.x no existe getStatistics, usaremos search para obtener info básica
                $searchResult = $this->algolia->searchSingleIndex('word_documents_index', [
                    'query' => '',
                    'hitsPerPage' => 0
                ]);
                $settings = $this->algolia->getSettings('word_documents_index');
            } catch (\Exception $e) {
                // Si hay error con Algolia, devolver datos básicos
                return response()->json([
                    'success' => true,
                    'index_name' => 'word_documents_index',
                    'statistics' => [
                        'total_records' => 0,
                        'size_bytes' => 0,
                        'last_build_time' => null,
                    ],
                    'settings' => [
                        'searchable_attributes' => [],
                        'attributes_for_faceting' => [],
                        'custom_ranking' => [],
                    ],
                    'app_id' => config('scout.algolia.id'),
                    'note' => 'Índice no inicializado o sin datos',
                ]);
            }

            return response()->json([
                'success' => true,
                'index_name' => 'word_documents_index',
                'statistics' => [
                    'total_records' => $searchResult['nbHits'] ?? 0,
                    'size_bytes' => 0, // No disponible en v4.x directamente
                    'last_build_time' => null, // No disponible en v4.x directamente
                ],
                'settings' => [
                    'searchable_attributes' => $settings['searchableAttributes'] ?? [],
                    'attributes_for_faceting' => $settings['attributesForFaceting'] ?? [],
                    'custom_ranking' => $settings['customRanking'] ?? [],
                ],
                'app_id' => config('scout.algolia.id'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener información del índice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar documentos usando Algolia
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:500',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:0',
            'filters' => 'nullable|string',
        ]);

        try {
            $query = $request->input('query');
            $limit = $request->input('limit', 20);
            $page = $request->input('page', 0);
            $filters = $request->input('filters', '');

            // Realizar búsqueda usando Scout
            $searchQuery = WordDocument::search($query);
            
            if ($filters) {
                $searchQuery->where($filters);
            }

            $results = $searchQuery
                ->take($limit)
                ->get();

            // Si Algolia está disponible, obtener información adicional
            $algoliaResults = null;
            if ($this->algolia) {
                try {
                    $algoliaResults = $this->algolia->searchSingleIndex('word_documents_index', [
                        'query' => $query,
                        'hitsPerPage' => $limit,
                        'page' => $page,
                        'filters' => $filters,
                        'attributesToHighlight' => ['title', 'content', 'keywords'],
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error en búsqueda Algolia: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => [
                    'total' => $results->count(),
                    'data' => $results->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'title' => $doc->title,
                            'content_preview' => substr($doc->getNormalizedContent(), 0, 200) . '...',
                            'keywords' => $doc->extractKeywords(),
                            'chunks_count' => count($doc->getIntelligentChunks()),
                            'created_at' => $doc->created_at,
                            'updated_at' => $doc->updated_at,
                        ];
                    }),
                ],
                'algolia_info' => $algoliaResults ? [
                    'total_hits' => $algoliaResults['nbHits'],
                    'processing_time_ms' => $algoliaResults['processingTimeMS'],
                    'page' => $algoliaResults['page'],
                    'pages' => $algoliaResults['nbPages'],
                ] : null,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los documentos indexados
     */
    public function indexedDocuments(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);
            $page = $request->input('page', 0);

            if ($this->algolia) {
                try {
                    // Usar Algolia para obtener todos los documentos
                    $results = $this->algolia->searchSingleIndex('word_documents_index', [
                        'query' => '',
                        'hitsPerPage' => $limit,
                        'page' => $page,
                        'attributesToRetrieve' => ['id', 'title', 'content', 'keywords', 'created_at', 'updated_at'],
                    ]);

                    return response()->json([
                        'success' => true,
                        'source' => 'algolia',
                        'total' => $results['nbHits'],
                        'data' => collect($results['hits'])->map(function ($hit) {
                            return [
                                'id' => $hit['id'] ?? $hit['objectID'],
                                'title' => $hit['title'] ?? 'Sin título',
                                'content_preview' => isset($hit['content']) ? substr($hit['content'], 0, 200) . '...' : 'Sin contenido',
                                'keywords' => $hit['keywords'] ?? [],
                                'created_at' => $hit['created_at'] ?? null,
                                'updated_at' => $hit['updated_at'] ?? null,
                                'algolia_object_id' => $hit['objectID'],
                            ];
                        }),
                        'pagination' => [
                            'current_page' => $results['page'],
                            'per_page' => $results['hitsPerPage'],
                            'total_pages' => $results['nbPages'],
                        ],
                    ]);
                } catch (\Exception $e) {
                    // Si falla Algolia, usar fallback de base de datos
                    \Log::warning('Error accediendo a Algolia, usando fallback: ' . $e->getMessage());
                }
            }
            
            // Fallback: usar base de datos directamente
            $documents = WordDocument::whereNotNull('contenido_texto')
                    ->where('contenido_texto', '!=', '')
                    ->skip($page * $limit)
                    ->take($limit)
                    ->get();

                $total = WordDocument::whereNotNull('contenido_texto')
                    ->where('contenido_texto', '!=', '')
                    ->count();

            return response()->json([
                'success' => true,
                'source' => 'database',
                'total' => $total,
                'data' => $documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'content_preview' => substr($doc->getNormalizedContent(), 0, 200) . '...',
                        'keywords' => $doc->extractKeywords(),
                        'chunks_count' => count($doc->getIntelligentChunks()),
                        'created_at' => $doc->created_at,
                        'updated_at' => $doc->updated_at,
                    ];
                }),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_pages' => ceil($total / $limit),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener documentos indexados',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reindexar todos los documentos
     */
    public function reindex(): JsonResponse
    {
        try {
            $documents = WordDocument::whereNotNull('contenido_texto')
                ->where('contenido_texto', '!=', '')
                ->get();

            $indexed = 0;
            foreach ($documents as $document) {
                if ($document->shouldBeSearchable()) {
                    $document->searchable();
                    $indexed++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reindexación completada',
                'total_documents' => $documents->count(),
                'indexed_documents' => $indexed,
                'driver' => config('scout.driver'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error durante la reindexación',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración actual de Scout/Algolia
     */
    public function configuration(): JsonResponse
    {
        return response()->json([
            'scout' => [
                'driver' => config('scout.driver'),
                'prefix' => config('scout.prefix'),
                'queue' => config('scout.queue'),
                'chunk_size' => config('scout.chunk.searchable'),
            ],
            'algolia' => [
                'app_id' => config('scout.algolia.id'),
                'configured' => !empty(config('scout.algolia.id')) && !empty(config('scout.algolia.secret')),
                'index_settings' => config('scout.algolia.index-settings'),
            ],
            'models' => [
                'WordDocument' => [
                    'index_name' => (new WordDocument)->searchableAs(),
                    'total_records' => WordDocument::count(),
                    'searchable_records' => WordDocument::whereNotNull('contenido_texto')->where('contenido_texto', '!=', '')->count(),
                ],
            ],
        ]);
    }
}

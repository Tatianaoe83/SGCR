<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HybridChatbotService;
use App\Services\SmartIndexingService;
use App\Models\ChatbotAnalytics;
use App\Models\ChatbotFeedback;

class ChatbotController extends Controller
{
    private $hybridService;
    
    public function __construct()
    {
        $this->hybridService = new HybridChatbotService();
    }

    /**
     * @api {post} /api/chatbot/query Procesar consulta del chatbot
     * @apiName ProcessQuery
     * @apiGroup Chatbot
     * 
     * @apiParam {String} message Mensaje del usuario
     * @apiParam {String} [session_id] ID de sesión
     * 
     * @apiSuccess {String} response Respuesta generada
     * @apiSuccess {String} method Método usado (smart_index|ollama|fallback)
     * @apiSuccess {Number} response_time_ms Tiempo de respuesta en ms
     * @apiSuccess {Boolean} cached Indica si fue respuesta cacheada
     */
    public function query(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'session_id' => 'nullable|string|max:80'
        ]);
        
        // Intentar obtener el usuario de diferentes formas
        $userId = null;
        
        // 1. Intentar desde el request (si hay middleware de auth o token)
        if ($request->user()) {
            $userId = $request->user()->id;
        }
        // 2. Intentar desde Sanctum directamente si hay token Bearer
        elseif ($request->bearerToken()) {
            try {
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
                if ($token && $token->tokenable) {
                    $userId = $token->tokenable->id;
                }
            } catch (\Exception $e) {
                // Si falla, continuar sin user_id
            }
        }
        // 3. Intentar desde la sesión web (si está disponible)
        elseif (auth()->check()) {
            $userId = auth()->id();
        }
       
        $result = $this->hybridService->processQuery(
            $request->input('message'),
            $userId,
            $request->input('session_id')
        );

        // Garantizar analytics_id para que el front siempre pueda mostrar calificación.
        if (is_array($result) && empty($result['analytics_id']) && !empty($result['response'])) {
            try {
                $result['analytics_id'] = \App\Models\ChatbotAnalytics::create([
                    'user_id' => $userId,
                    'query' => $request->input('message'),
                    'normalized_query' => strtolower(trim((string) $request->input('message'))),
                    'response_method' => $result['method'] ?? 'untracked',
                    'response' => is_string($result['response'])
                        ? $result['response']
                        : json_encode($result['response']),
                    'response_time_ms' => $result['response_time_ms'] ?? null,
                    'session_id' => $request->input('session_id') ?: session()->getId(),
                ])->id;
            } catch (\Throwable $e) {
                // Si falla el log, la respuesta igual se entrega (sin score).
            }
        }
        
        return response()->json($result);
    }

    /**
     * @api {post} /api/chatbot/feedback Enviar feedback sobre respuesta
     * @apiName SendFeedback
     * @apiGroup Chatbot
     */
    public function feedback(Request $request)
    {
        $request->validate([
            'analytics_id' => 'required|exists:chatbot_analytics,id',
            'helpful' => 'nullable|boolean',
            'score' => 'nullable|integer|min:1|max:5',
            'session_id' => 'nullable|string|max:80',
            'comment' => 'nullable|string|max:500',
            'improvement_suggestion' => 'nullable|in:more_detailed,more_accurate,faster,other'
        ]);

        $analytics = ChatbotAnalytics::findOrFail($request->analytics_id);

        $score = $request->input('score');
        $helpful = $request->has('helpful')
            ? (bool) $request->boolean('helpful')
            : ($score !== null ? ((int) $score) >= 3 : null);

        if ($helpful === null && $score === null) {
            return response()->json(['error' => 'Indica helpful o score.'], 422);
        }

        if ($helpful === null) {
            $helpful = ((int) $score) >= 3;
        }

        try {
            ChatbotFeedback::updateOrCreate(
                ['analytics_id' => $analytics->id],
                [
                    'helpful' => $helpful,
                    'score' => $score,
                    'session_id' => $request->input('session_id') ?: $analytics->session_id,
                    'user_id' => auth()->id() ?: $analytics->user_id,
                    'comment' => $request->input('comment'),
                    'improvement_suggestion' => $request->input('improvement_suggestion'),
                ]
            );
        } catch (\Throwable $e) {
            \Log::error('No se pudo guardar chatbot_feedback: ' . $e->getMessage());

            return response()->json([
                'error' => 'No se pudo guardar la calificación.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        $analytics->update([
            'user_satisfied' => $helpful,
            'similarity_score' => $score !== null
                ? round(((int) $score) / 5, 3)
                : $analytics->similarity_score,
        ]);

        // Aprendizaje conservador: preguntas abiertas (paid_ai) + ajuste de índice existente.
        // Score 1–2 castiga; 4–5 suma candidato; solo con varios votos altos se verifica.
        $learn = ['action' => 'none'];
        try {
            $learn = app(SmartIndexingService::class)->recordOpenAnswerFeedback(
                $analytics,
                $score !== null ? (int) $score : null,
                $helpful
            );
        } catch (\Throwable $e) {
            \Log::warning('Aprendizaje smart_index omitido: ' . $e->getMessage());
        }

        // Léxico: solo con señal no-basura (score>=3 o helpful). Evita alimentar con 1★.
        if ($helpful || ((int) $score) >= 3) {
            \App\Jobs\AprenderLexicoBobJob::dispatch($analytics->id);
        }

        return response()->json([
            'status' => 'feedback_recorded',
            'helpful' => $helpful,
            'score' => $score,
            'learning' => $learn,
        ]);
    }

    /**
     * Historial de la conversación por session_id (caché de plática en la sesión).
     */
    public function history(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string|max:80',
        ]);

        $sessionId = $request->input('session_id');
        $userId = auth()->id();

        $rows = ChatbotAnalytics::query()
            ->where('session_id', $sessionId)
            ->when($userId, fn ($q) => $q->where(function ($qq) use ($userId) {
                $qq->where('user_id', $userId)->orWhereNull('user_id');
            }))
            ->orderBy('id')
            ->limit(40)
            ->get(['id', 'query', 'response', 'response_method', 'created_at']);

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = [
                'role' => 'user',
                'content' => $row->query,
                'at' => optional($row->created_at)->toIso8601String(),
            ];
            $messages[] = [
                'role' => 'assistant',
                'content' => $row->response,
                'analytics_id' => $row->id,
                'method' => $row->response_method,
                'at' => optional($row->created_at)->toIso8601String(),
            ];
        }

        return response()->json([
            'session_id' => $sessionId,
            'messages' => $messages,
        ]);
    }

    /**
     * Autocompletado: folios, documentos, puestos, áreas, unidades, personas.
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:120',
            'limit' => 'nullable|integer|min:3|max:12',
        ]);

        $items = $this->hybridService->suggestSearch(
            $request->input('q'),
            (int) ($request->input('limit') ?: 8)
        );

        return response()->json([
            'query' => $request->input('q'),
            'suggestions' => $items,
        ]);
    }

    /**
     * @api {get} /api/chatbot/analytics Obtener analytics del chatbot
     */
    public function analytics(Request $request)
    {
        $this->authorize('view-analytics'); // Middleware de autorización
        
        $period = $request->input('period', '7days');
        $startDate = match($period) {
            '24hours' => now()->subDay(),
            '7days' => now()->subWeek(),
            '30days' => now()->subMonth(),
            default => now()->subWeek()
        };
        
        $analytics = [
            'total_queries' => ChatbotAnalytics::where('created_at', '>=', $startDate)->count(),
            'method_distribution' => ChatbotAnalytics::where('created_at', '>=', $startDate)
                ->groupBy('response_method')
                ->selectRaw('response_method, count(*) as count')
                ->pluck('count', 'response_method'),
            'average_response_time' => ChatbotAnalytics::where('created_at', '>=', $startDate)
                ->avg('response_time_ms'),
            'satisfaction_rate' => $this->calculateSatisfactionRate($startDate),
            'top_queries' => $this->getTopQueries($startDate),
            'cache_hit_rate' => $this->calculateCacheHitRate($startDate)
        ];
        
        return response()->json($analytics);
    }

    private function calculateSatisfactionRate($startDate)
    {
        $totalFeedback = ChatbotFeedback::whereHas('analytics', function($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->count();
        
        if ($totalFeedback == 0) return null;
        
        $positiveFeedback = ChatbotFeedback::whereHas('analytics', function($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->where('helpful', true)->count();
        
        return round(($positiveFeedback / $totalFeedback) * 100, 2);
    }

    private function getTopQueries($startDate)
    {
        return ChatbotAnalytics::where('created_at', '>=', $startDate)
            ->groupBy('normalized_query')
            ->selectRaw('normalized_query, count(*) as count')
            ->orderByDesc('count')
            ->take(10)
            ->get();
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

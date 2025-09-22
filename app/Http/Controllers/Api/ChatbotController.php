<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HybridChatbotService;
use App\Services\SmartIndexingService;
use App\Models\ChatbotAnalytics;
use App\Models\ChatbotFeedback;
use App\Models\SmartIndex;

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
            'session_id' => 'nullable|string|max:50'
        ]);
        
       
        $result = $this->hybridService->processQuery(
            $request->input('message'),
            $request->user()->id ?? null,
            $request->input('session_id')
        );
        
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
            'helpful' => 'required|boolean',
            'comment' => 'nullable|string|max:500',
            'improvement_suggestion' => 'nullable|in:more_detailed,more_accurate,faster,other'
        ]);

        $analytics = ChatbotAnalytics::find($request->analytics_id);
        
        // Crear feedback
        ChatbotFeedback::create([
            'analytics_id' => $request->analytics_id,
            'helpful' => $request->helpful,
            'comment' => $request->comment,
            'improvement_suggestion' => $request->improvement_suggestion
        ]);
        
        // Actualizar confianza del índice si aplicable
        if ($analytics->response_method === 'smart_index') {
            $smartIndex = SmartIndex::where('response', $analytics->response)->first();
            if ($smartIndex) {
                $smartIndex->updateConfidence($request->helpful);
            }
        } elseif ($analytics->response_method === 'ollama' && $request->helpful) {
            // Si la respuesta de Ollama fue útil, agregarla al índice con mayor confianza
            app(SmartIndexingService::class)->addToIndex(
                $analytics->query, 
                $analytics->response, 
                'verified',
                true
            );
        }
        
        return response()->json(['status' => 'feedback_recorded']);
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

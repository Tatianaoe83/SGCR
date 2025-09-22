<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotRateLimit
{
    public function handle($request, Closure $next)
    {
        $key = 'chatbot:' . ($request->user()->id ?? $request->ip());
        
        // Límites diferenciados
        $limits = [
            'authenticated' => ['limit' => 30, 'minutes' => 1], // 30 por minuto para autenticados
            'guest' => ['limit' => 10, 'minutes' => 1], // 10 por minuto para invitados
        ];
        
        $limit = $request->user() ? $limits['authenticated'] : $limits['guest'];
        
        if (RateLimiter::tooManyAttempts($key, $limit['limit'])) {
            return response()->json([
                'error' => 'Límite de consultas excedido',
                'limit' => $limit['limit'],
                'retry_after' => RateLimiter::availableIn($key),
                'message' => $request->user() 
                    ? 'Has excedido el límite de 30 consultas por minuto' 
                    : 'Has excedido el límite de 10 consultas por minuto. Registrate para más consultas.'
            ], 429);
        }
        
        RateLimiter::hit($key, $limit['minutes'] * 60);
        
        // Agregar headers de rate limit
        $response = $next($request);
        $response->headers->add([
            'X-RateLimit-Limit' => $limit['limit'],
            'X-RateLimit-Remaining' => $limit['limit'] - RateLimiter::attempts($key),
            'X-RateLimit-Reset' => now()->addSeconds(RateLimiter::availableIn($key))->timestamp,
        ]);
        
        return $response;
    }
}

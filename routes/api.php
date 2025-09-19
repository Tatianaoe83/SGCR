<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampoRequeridoController;
use App\Http\Controllers\Api\ChatbotController;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para campos requeridos de tipos de elementos
Route::prefix('campos-requeridos')->group(function () {
    Route::get('/tipo-elemento/{tipoElementoId}', [CampoRequeridoController::class, 'show']);
    Route::post('/tipo-elemento', [CampoRequeridoController::class, 'store']);
    Route::put('/tipo-elemento/{tipoElementoId}', [CampoRequeridoController::class, 'update']);
    Route::delete('/tipo-elemento/{tipoElementoId}', [CampoRequeridoController::class, 'destroy']);
});

Route::prefix('chatbot')->group(function () {
    Route::post('/query', [ChatbotController::class, 'query'])
        ->middleware(['throttle:chatbot']);
    
    Route::post('/feedback', [ChatbotController::class, 'feedback'])
        ->middleware(['auth:sanctum']);
    
    Route::get('/analytics', [ChatbotController::class, 'analytics'])
        ->middleware(['auth:sanctum', 'can:view-analytics']);
    
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'ollama_status' => app(OllamaService::class)->healthCheck(),
            'database_status' => 'connected',
            'cache_status' => Cache::has('health_check') ? 'ok' : 'warning'
        ]);
    });
});


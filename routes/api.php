<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampoRequeridoController;

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

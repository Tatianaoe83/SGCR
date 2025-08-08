<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\UnidadNegocioController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\PuestoTrabajoController;
use App\Http\Controllers\EmpleadosController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', 'login');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/community/users-tabs', [MemberController::class, 'indexTabs'])->name('users-tabs');
    Route::get('/community/users-tiles', [MemberController::class, 'indexTiles'])->name('users-tiles');
    Route::get('/community/profile', function () {
    return view('pages/community/profile');
    })->name('profile');
    Route::get('/community/feed', function () {
        return view('pages/community/feed');
    })->name('feed');  
    
    // Rutas para divisiones
    Route::resource('divisions', DivisionController::class);
    
    // Rutas para unidades de negocio
    Route::resource('unidades-negocios', UnidadNegocioController::class);

    // Rutas para areas
    Route::resource('area', AreaController::class);
    
    // Rutas para puestos de trabajo
    Route::resource('puestos-trabajo', PuestoTrabajoController::class);
    
    // Rutas adicionales para puestos de trabajo
    Route::get('puestos-trabajo/export/excel', [PuestoTrabajoController::class, 'export'])->name('puestos-trabajo.export');
    Route::get('puestos-trabajo/template/download', [PuestoTrabajoController::class, 'downloadTemplate'])->name('puestos-trabajo.template');
    Route::get('puestos-trabajo/import/form', [PuestoTrabajoController::class, 'importForm'])->name('puestos-trabajo.import.form');
    Route::post('puestos-trabajo/import', [PuestoTrabajoController::class, 'import'])->name('puestos-trabajo.import');

    // Rutas para empleados
    Route::resource('empleados', EmpleadosController::class);

});

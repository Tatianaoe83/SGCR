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
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\TipoProcesoController;
use App\Http\Controllers\ElementoController;
use App\Http\Controllers\TipoElementoController;
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
    // Route::get('/community/users-tabs', [MemberController::class, 'indexTabs'])->name('users-tabs');
    // Route::get('/community/users-tiles', [MemberController::class, 'indexTiles'])->name('users-tiles');
    // Route::get('/community/profile', function () {
    // return view('pages/community/profile');
    // })->name('profile');
    // Route::get('/community/feed', function () {
    //     return view('pages/community/feed');
    // })->name('feed');  
    
    // Rutas para divisiones
    Route::resource('divisions', DivisionController::class);
    
    // Rutas para unidades de negocio
    Route::resource('unidades-negocios', UnidadNegocioController::class);

    // Rutas para areas
    Route::resource('area', AreaController::class);
    
    // Rutas para puestos de trabajo
    Route::resource('puestos-trabajo', PuestoTrabajoController::class);
    
    // Rutas para cascada de división -> unidad -> área
    Route::get('puestos-trabajo/unidades-negocio/{division_id}', [PuestoTrabajoController::class, 'getUnidadesNegocio'])->name('puestos-trabajo.unidades-negocio');
    Route::get('puestos-trabajo/areas/{unidad_negocio_id}', [PuestoTrabajoController::class, 'getAreas'])->name('puestos-trabajo.areas');
    
    // Rutas adicionales para puestos de trabajo
    Route::get('puestos-trabajo/export/excel', [PuestoTrabajoController::class, 'export'])->name('puestos-trabajo.export');
    Route::get('puestos-trabajo/template/download', [PuestoTrabajoController::class, 'downloadTemplate'])->name('puestos-trabajo.template');
    Route::get('puestos-trabajo/import/form', [PuestoTrabajoController::class, 'importForm'])->name('puestos-trabajo.import.form');
    Route::post('puestos-trabajo/import', [PuestoTrabajoController::class, 'import'])->name('puestos-trabajo.import');

    // Rutas para empleados
    Route::resource('empleados', EmpleadosController::class);
    Route::get('empleados/export/excel', [EmpleadosController::class, 'export'])->name('empleados.export');
    Route::get('empleados/template/download', [EmpleadosController::class, 'downloadTemplate'])->name('empleados.template');
    Route::get('empleados/import/form', [EmpleadosController::class, 'importForm'])->name('empleados.import.form');
    Route::post('empleados/import', [EmpleadosController::class, 'import'])->name('empleados.import');
    Route::post('empleados/check-puesto-changes', [EmpleadosController::class, 'checkPuestoChanges'])->name('empleados.check-puesto-changes');
    Route::post('empleados/confirm-import', [EmpleadosController::class, 'confirmImport'])->name('empleados.confirm-import');

    // Rutas para roles y permisos
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    
    // Rutas para gestión de usuarios
    Route::resource('users', UserManagementController::class);

    // Rutas para tipo de procesos
    Route::resource('tipoProceso', TipoProcesoController::class);

    // Rutas para tipos de elementos
    Route::resource('tipo-elementos', TipoElementoController::class);

    // Rutas para elementos
    Route::resource('elementos', ElementoController::class);

    

});

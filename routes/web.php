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
use App\Http\Controllers\MatrizController;
use App\Http\Controllers\TipoElementoController;
use App\Http\Controllers\CuerpoCorreoController;
use App\Http\Controllers\FileConvertController;
use App\Http\Controllers\WordDocumentController;
use App\Mail\AccesoMail;

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
    Route::get('puestos-trabajo/unidades-negocio/{division_id}', [PuestoTrabajoController::class, 'getUnidadesNegocio']);
    Route::get('puestos-trabajo/areas/{unidad_negocio_id}', [PuestoTrabajoController::class, 'getAreas']);
    Route::get('puestos-trabajo/por-area/{area_id}', [PuestoTrabajoController::class, 'getPuestos']);
    //Route::get('puestos-trabajo/jefes', [PuestoTrabajoController::class, 'getJefes'])->name('puestos-trabajo.jefes');

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
    Route::get('empleados/puesto-trabajo/{id}/details', [EmpleadosController::class, 'getPuestoTrabajoDetails'])->name('empleados.puesto-trabajo.details');
    Route::post('empleados/email-preview', [EmpleadosController::class, 'getEmailPreview'])->name('empleados.email-preview');

    // Rutas para roles y permisos
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);

    // Rutas para gestión de usuarios
    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/send-credentials', [UserManagementController::class, 'sendCredentials'])->name('users.send-credentials');

    // Rutas para tipo de procesos
    Route::resource('tipoProceso', TipoProcesoController::class);

    // Rutas para tipos de elementos
    Route::resource('tipo-elementos', TipoElementoController::class);

    // Rutas adicionales para campos requeridos de tipos de elementos
    Route::get('tipo-elementos/{id}/campos-requeridos', [TipoElementoController::class, 'getCamposRequeridos'])->name('tipo-elementos.campos-requeridos');
    Route::post('tipo-elementos/{id}/campos-requeridos', [TipoElementoController::class, 'guardarCamposRequeridos'])->name('tipo-elementos.guardar-campos');

    // Rutas para elementos
    Route::get('elementos/data', [ElementoController::class, 'data'])->name('elementos.data');
    Route::get('elementos/template/download', [ElementoController::class, 'downloadTemplate'])->name('elementos.template');
    Route::get('elementos/import/form', [ElementoController::class, 'importForm'])->name('elementos.import.form');
    Route::post('elementos/import', [ElementoController::class, 'import'])->name('elementos.import');
    Route::get('tipos-elemento/{id}/campos-obligatorios', [ElementoController::class, 'mandatoryData'])->name('elementos.mandatory');
    Route::resource('elementos', ElementoController::class);


    // Rutas para matriz
    Route::get('/matriz', [MatrizController::class, 'index'])->name('matriz.index');
    Route::post('/matriz/generar', [MatrizController::class, 'buscarElementos'])->name('matriz.generar');
    Route::post('/matriz/general', [MatrizController::class, 'matrizGeneral'])->name('matriz.matrizgeneral');
    Route::post('/matriz/filtro', [MatrizController::class, 'matrizFiltro'])->name('matriz.matrizgeneral2');
    Route::post('/matriz/export', [MatrizController::class, 'export'])->name('matriz.export');
    Route::post('/matriz/export2', [MatrizController::class, 'exportJob'])->name('matriz.export2');

    // Rutas para cuerpos de correo
    Route::resource('cuerpos-correo', CuerpoCorreoController::class);
    Route::post('/cuerpos-correo/{id}/editor', [CuerpoCorreoController::class, 'updateEditor'])->name('cuerpos-correo.updateEditor');
    Route::post('/cuerpos-correo/{id}/toggle-status', [CuerpoCorreoController::class, 'toggleStatus'])->name('cuerpos-correo.toggleStatus');
    Route::get('/cuerpos-correo/{id}/duplicate', [CuerpoCorreoController::class, 'duplicate'])->name('cuerpos-correo.duplicate');
    Route::get('/cuerpos-correo/{id}/export', [CuerpoCorreoController::class, 'export'])->name('cuerpos-correo.export');
    Route::get('/cuerpos-correo/{id}/variable-stats', [CuerpoCorreoController::class, 'getVariableStats'])->name('cuerpos-correo.variableStats');
    Route::post('/cuerpos-correo/{id}/validate', [CuerpoCorreoController::class, 'validateTemplate'])->name('cuerpos-correo.validate');
    Route::get('/cuerpos-correo/{id}/preview', [CuerpoCorreoController::class, 'previewTemplate'])->name('cuerpos-correo.previewTemplate');
    Route::get('/preview/{tipo}', [CuerpoCorreoController::class, 'preview'])->name('cuerpos-correo.preview');

    // Rutas para documentos Word
    Route::resource('word-documents', WordDocumentController::class);
    Route::get('word-documents/{wordDocument}/descargar', [WordDocumentController::class, 'descargar'])->name('word-documents.descargar');
    Route::post('word-documents/{wordDocument}/reprocesar', [WordDocumentController::class, 'reprocesar'])->name('word-documents.reprocesar');
    Route::get('word-documents/filtrar', [WordDocumentController::class, 'filtrar'])->name('word-documents.filtrar');

    Route::resource('files', FileConvertController::class);
    Route::post('/convertFile', [FileConvertController::class, 'convertWordToPdf'])->name('files.convert');
});

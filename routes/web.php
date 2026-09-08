<?php

use App\Http\Controllers\Api\ChatbotController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\UnidadNegocioController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ControlCambioController;
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
use App\Http\Controllers\PropuestaMejoraController;
use App\Http\Controllers\MapaProcesosController;
use App\Http\Controllers\NotificacionController;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\WordDocument;
use App\Models\Elemento;
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

// Prueba de envio de correo. Ruta temporal: eliminar cuando se resuelva.
// El destinatario esta fijo a proposito para que no se pueda usar como relay.
Route::get('mail-test', function () {
    $destino = 'econg@proser.com.mx';
    $mailer = config('mail.default');

    // Enmascara las credenciales en base64 que Symfony vuelca tras los desafios 334.
    $ocultarCredenciales = function (?string $debug): array {
        if (! $debug) {
            return [];
        }

        $lineas = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $debug) ?: []),
            fn ($linea) => $linea !== ''
        ));

        $esperaCredencial = false;

        foreach ($lineas as $i => $linea) {
            if ($esperaCredencial && str_contains($linea, '> ')) {
                $lineas[$i] = preg_replace('/> \S+$/', '> [REDACTADO]', $linea);
                $esperaCredencial = false;
                continue;
            }

            if (preg_match('/< 33[45]\s/', $linea)) {
                $esperaCredencial = true;
            }
        }

        return $lineas;
    };

    $configuracion = [
        'Destino' => $destino,
        'Mailer' => $mailer,
        'Host' => config("mail.mailers.$mailer.host"),
        'Puerto' => config("mail.mailers.$mailer.port"),
        'Encriptacion' => config("mail.mailers.$mailer.encryption"),
        'Usuario' => config("mail.mailers.$mailer.username"),
        'Remitente' => config('mail.from.address'),
        'EHLO' => config("mail.mailers.$mailer.local_domain") ?: '(sin definir)',
        'Config cacheada' => app()->configurationIsCached() ? 'si' : 'no',
        'Entorno' => app()->environment(),
        'Momento' => now()->toDateTimeString(),
    ];

    $error = null;
    $messageId = null;
    $dialogo = [];

    try {
        $enviado = Mail::raw(
            'Prueba de envio desde SGCR - ' . now()->toDateTimeString(),
            fn ($mensaje) => $mensaje->to($destino)->subject('Prueba SMTP - SGCR')
        );

        $messageId = $enviado?->getMessageId();
        $dialogo = $ocultarCredenciales($enviado?->getDebug());
    } catch (\Throwable $e) {
        $error = $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }

    $filas = '';
    foreach ($configuracion as $clave => $valor) {
        $filas .= '<tr><th>' . e($clave) . '</th><td>' . e((string) $valor) . '</td></tr>';
    }

    $lineasHtml = '';
    foreach ($dialogo as $linea) {
        $clase = str_starts_with($linea, '>') ? 'cliente' : 'servidor';

        if (preg_match('/^< [45]\d\d/', $linea)) {
            $clase = 'falla';
        }

        $lineasHtml .= '<div class="' . $clase . '">' . e($linea) . '</div>';
    }

    if ($error !== null) {
        $estado = '<p class="estado error">No se pudo enviar</p><p class="detalle">' . e($error) . '</p>';
    } else {
        $estado = '<p class="estado ok">Aceptado por el servidor de correo</p>'
            . '<p class="detalle">Message-ID: ' . e($messageId ?? 'sin id') . '</p>'
            . '<p class="nota">El servidor lo acepto. La entrega final ocurre despues y no es visible aqui.</p>';
    }

    $seccionDialogo = $lineasHtml === ''
        ? '<p class="nota">Sin dialogo SMTP: el transport no hablo con ningun servidor.</p>'
        : '<pre class="dialogo">' . $lineasHtml . '</pre>';

    $html = <<<HTML
    <!doctype html>
    <html lang="es">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prueba de correo - SGCR</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; padding: 2rem 1rem; background: #f8fafc; color: #1e293b;
               font: 14px/1.5 ui-sans-serif, system-ui, sans-serif; }
        main { max-width: 820px; margin: 0 auto; }
        h1 { font-size: 1.25rem; margin: 0 0 1.5rem; }
        section { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
                  padding: 1.25rem; margin-bottom: 1rem; }
        h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em;
             color: #64748b; margin: 0 0 .85rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .4rem 0; border-bottom: 1px solid #f1f5f9;
                 vertical-align: top; }
        th { width: 11rem; font-weight: 500; color: #64748b; }
        td { font-family: ui-monospace, monospace; }
        .estado { margin: 0; font-size: 1rem; font-weight: 600; }
        .estado.ok { color: #15803d; }
        .estado.error { color: #b91c1c; }
        .detalle { margin: .4rem 0 0; font-family: ui-monospace, monospace; font-size: .85rem; }
        .nota { margin: .6rem 0 0; color: #64748b; font-size: .85rem; }
        .dialogo { margin: 0; padding: .9rem; background: #0f172a; border-radius: 6px;
                   overflow-x: auto; font-size: .78rem; line-height: 1.6; }
        .dialogo div { white-space: pre; font-family: ui-monospace, monospace; }
        .cliente { color: #7dd3fc; }
        .servidor { color: #86efac; }
        .falla { color: #fca5a5; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e2e8f0; }
            section { background: #1e293b; border-color: #334155; }
            th, td { border-color: #334155; }
        }
    </style>
    </head>
    <body>
    <main>
        <h1>Prueba de envio de correo</h1>
        <section><h2>Resultado</h2>{$estado}</section>
        <section><h2>Configuracion efectiva</h2><table>{$filas}</table></section>
        <section><h2>Dialogo SMTP</h2>{$seccionDialogo}</section>
    </main>
    </body>
    </html>
    HTML;

    return response($html, $error === null ? 200 : 500);
})->name('mail.test');

// Ruta pública para revisión de documento (sin middleware)
Route::get('/revision-documento/{id}/{firma}', [ElementoController::class, 'revisarDocumento'])->name('revision.documento')->middleware('signed');
Route::post('/revision-documento/{firma}/firmar', [ElementoController::class, 'updateFirmaStatus'])->name('firmas.updateStatus');
Route::post('/firmas/{firma}/frecuencia', [ElementoController::class, 'cambiarFrecuencia']);

Route::middleware(['auth'])->group(function () {

    Route::post('/chatbot/query', [ChatbotController::class, 'query'])
        ->middleware('throttle:chatbot')
        ->name('chatbot.query');
    Route::post('/chatbot/feedback', [ChatbotController::class, 'feedback'])
        ->name('chatbot.feedback');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/mapa-procesos', [MapaProcesosController::class, 'index'])->name('mapa-procesos.index');
    Route::get('/mapa-procesos/{id}/procedimientos', [MapaProcesosController::class, 'procedimientosDelProceso'])->name('mapa-procesos.procedimientos');
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
    Route::get('division/data', [DivisionController::class, 'data'])->name('divisions.data');

    // Rutas para unidades de negocio
    Route::get('unidades-negocios/data', [UnidadNegocioController::class, 'data'])->name('unidades-negocios.data');
    Route::resource('unidades-negocios', UnidadNegocioController::class);


    // Rutas para areas
    Route::get('area/data', [AreaController::class, 'data'])->name('area.data');
    Route::resource('area', AreaController::class);

    // Rutas para puestos de trabajo
    Route::get('puestos-trabajo/data', [PuestoTrabajoController::class, 'data'])->name('puestos-trabajo.data');
    Route::resource('puestos-trabajo', PuestoTrabajoController::class);

    // Rutas para cascada de división -> unidad -> área
    Route::get('puestos-trabajo/unidades-negocio/{division_id}', [PuestoTrabajoController::class, 'getUnidadesNegocio']);
    Route::get('puestos-trabajo/areas/{unidad_negocio_id}', [PuestoTrabajoController::class, 'getAreas']);
    Route::get('puestos-trabajo/jefes', [PuestoTrabajoController::class, 'getPuestos']);
    //Route::get('puestos-trabajo/jefes', [PuestoTrabajoController::class, 'getJefes'])->name('puestos-trabajo.jefes');

    // Rutas adicionales para puestos de trabajo
    Route::get('puestos-trabajo/export/excel', [PuestoTrabajoController::class, 'export'])->name('puestos-trabajo.export');
    Route::get('puestos-trabajo/template/download', [PuestoTrabajoController::class, 'downloadTemplate'])->name('puestos-trabajo.template');
    Route::get('puestos-trabajo/import/form', [PuestoTrabajoController::class, 'importForm'])->name('puestos-trabajo.import.form');
    Route::post('puestos-trabajo/import', [PuestoTrabajoController::class, 'import'])->name('puestos-trabajo.import');

    // Rutas para empleados
    Route::get('empleados/data', [EmpleadosController::class, 'data'])->name('empleados.data');
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
    Route::get('users/data', [UserManagementController::class, 'data'])->name('usuarios.data');
    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/send-credentials', [UserManagementController::class, 'sendCredentials'])->name('users.send-credentials');

    // Rutas para tipo de procesos
    Route::resource('tipoProceso', TipoProcesoController::class);

    // Rutas para tipos de elementos
    Route::resource('tipo-elementos', TipoElementoController::class);

    // Rutas adicionales para campos requeridos de tipos de elementos
    Route::get('tipo-elementos/{id}/campos-requeridos', [TipoElementoController::class, 'getCamposRequeridos'])->name('tipo-elementos.campos-requeridos');
    Route::post('tipo-elementos/{id}/campos-requeridos', [TipoElementoController::class, 'guardarCamposRequeridos'])->name('tipo-elementos.guardar-campos');

    // Rutas para notificaciones de la campana
    Route::post('notificaciones/rechazos/{elemento}/leer', [NotificacionController::class, 'marcarRechazoLeido'])
        ->name('notificaciones.rechazos.leer');

    // Rutas para elementos
    Route::get('/elementos/nombres', [ElementoController::class, 'getEmpleadosNombre']);
    Route::post('/elementos/firmas', [ElementoController::class, 'storeFirmas'])->name('elementos.firmas.store');
    Route::get('/elementos/buscar', [ElementoController::class, 'buscarPuestoRelacion']);
    Route::post('/elementos/validar-duplicado', [ElementoController::class, 'validarDuplicado'])->name('elementos.validar-duplicado');
    Route::get('elementos/data', [ElementoController::class, 'data'])->name('elementos.data');
    Route::get('elementos/template/download', [ElementoController::class, 'downloadTemplate'])->name('elementos.template');
    Route::get('elementos/import/form', [ElementoController::class, 'importForm'])->name('elementos.import.form');
    Route::post('elementos/import', [ElementoController::class, 'import'])->name('elementos.import');
    Route::get('tipos-elemento/{id}/campos-obligatorios', [ElementoController::class, 'mandatoryData'])->name('elementos.mandatory');
    Route::get('elementos/{id}/info', [ElementoController::class, 'info'])->name('elementos.info');
    Route::get('/elementos/tipos/{tipo}', [ElementoController::class, 'getElementosPorTipo']);
    Route::post('elementos/{elemento}/reiniciar-flujo', [ElementoController::class, 'reiniciarFlujoFirmas'])->name('elementos.reiniciar-flujo');
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

    /* Route::resource('files', FileConvertController::class);
    Route::post('/convertFile', [FileConvertController::class, 'convertWordToPdf'])->name('files.convert'); */

    Route::get('/control-cambios/export', [ControlCambioController::class, 'export'])->name('control-cambios.export');
    Route::resource('control-cambios', ControlCambioController::class);

    // Propuestas de mejora
    Route::get('/propuestas/elementos', [PropuestaMejoraController::class, 'getElementos'])->name('propuestas.elementos');
    Route::post('/propuestas/mejora', [PropuestaMejoraController::class, 'store']);
    Route::get('/propuestas/{propuesta}/revision', [PropuestaMejoraController::class, 'revision'])->name('propuestas.revision');
    Route::post('/propuestas/{propuesta}/aprobar', [PropuestaMejoraController::class, 'aprobar'])->name('propuestas.aprobar');
    Route::post('/propuestas/{propuesta}/rechazar', [PropuestaMejoraController::class, 'rechazar'])->name('propuestas.rechazar');
    Route::resource('propuesta_mejora', PropuestaMejoraController::class);

    Route::get('/forzar-lectura', function () {
    // 1. Buscamos el último documento (el que acabas de subir)
    $documento = WordDocument::latest('id')->first();
    if (!$documento) return "No hay documentos.";

    $elemento = Elemento::find($documento->elemento_id);
    
    // 2. Arreglamos la ruta para Windows (XAMPP es delicado con las barras / y \)
    $rutaRelativa = $elemento->archivo_es_formato;
    // Si ya se convirtió a PDF, intentamos buscar el original asumiendo extensión .docx
    if (str_ends_with($rutaRelativa, '.pdf')) {
        return "ERROR: El archivo ya se convirtió a PDF y el original se borró. Sube uno nuevo.";
    }

    $rutaCompleta = storage_path('app/public/' . $rutaRelativa);
    $rutaCompleta = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaCompleta); // Fix Windows

    if (!file_exists($rutaCompleta)) return "Archivo no encontrado en: $rutaCompleta";

    echo "<h1>Diagnóstico de Extracción</h1>";
    echo "<strong>Archivo:</strong> $rutaCompleta <br>";

    // 3. INTENTO 1: ZIP MANUAL (El que necesitamos que funcione)
    $textoZip = "";
    try {
        $zip = new ZipArchive;
        if ($zip->open($rutaCompleta) === TRUE) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xmlData = $zip->getFromIndex($index);
                $textoZip = strip_tags($xmlData); // Limpieza básica
                echo "<p style='color:green'>✅ ÉXITO ZIP: Se encontraron " . strlen($textoZip) . " caracteres.</p>";
            } else {
                echo "<p style='color:red'>❌ ERROR ZIP: No se halló word/document.xml</p>";
            }
            $zip->close();
        } else {
            echo "<p style='color:red'>❌ ERROR ZIP: No se pudo abrir el archivo (¿Permisos?)</p>";
        }
    } catch (Exception $e) {
        echo "Excepción ZIP: " . $e->getMessage();
    }

    // 4. Guardar en Base de Datos (Si funcionó)
    if (!empty($textoZip)) {
        // Sanitizar (Tu función anti-errores SQL)
        $textoFinal = mb_scrub($textoZip, 'UTF-8');
        
        $documento->update([
            'contenido_texto' => $textoFinal,
            'estado' => 'procesado'
        ]);
        
        echo "<h3>✅ ¡Guardado en BD!</h3>";
        echo "<div style='background:#f0f0f0; padding:10px; border:1px solid #ccc; max-height:300px; overflow:auto;'>";
        echo nl2br(substr($textoFinal, 0, 2000)) . "...";
        echo "</div>";
    } else {
        echo "<h3>⚠️ Sigue vacío... El archivo podría ser una imagen.</h3>";
    }
});
});

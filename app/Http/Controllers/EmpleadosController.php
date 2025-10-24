<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use Illuminate\Http\Request;
use App\Models\PuestoTrabajo;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmpleadosImport;
use Illuminate\Http\RedirectResponse;
use App\Exports\EmpleadosExport;
use App\Exports\EmpleadosTemplateExport;
use App\Mail\AccesoMail;
use Illuminate\Support\Facades\Mail;

class EmpleadosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $empleados = Empleados::all();
        return view('empleados.index', compact('empleados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $puestosTrabajo = PuestoTrabajo::all();
        $esPreview = true;
        return view('empleados.create', compact('puestosTrabajo', 'esPreview'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {


            $request->validate([
                'nombres' => 'required|string|max:255',
                'apellido_paterno' => 'required|string|max:255',
                'apellido_materno' => 'required|string|max:255',
                'puesto_trabajo_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo',
                'correo' => 'nullable|email|unique:empleados,correo',
                'telefono' => 'nullable|string|max:20',
                'fecha_ingreso' => 'required|date',
                'fecha_nacimiento' => 'required|date|before:today',
            ]);

            $empleados = Empleados::create($request->all());

            $mensaje = 'Empleado creado correctamente.';

            // Solo crear usuario y enviar correo si tiene correo electrónico
            if (!empty($empleados->correo)) {
                // Cargar la relación del puesto para el correo
                $empleados->load('puestoTrabajo');

                // Generar contraseña automática
                $contrasena = $empleados->generarContrasenaAutomatica();


                // Crear usuario en la tabla users
                $user = \App\Models\User::create([
                    'name' => $empleados->nombres . ' ' . $empleados->apellido_paterno . ' ' . $empleados->apellido_materno,
                    'email' => $empleados->correo,
                    'password' => \Hash::make($contrasena),
                    'email_verified_at' => now(),
                ]);


                // Enviar correo con credenciales
                try {


                    Mail::to($empleados->correo)->send(new AccesoMail($empleados, $contrasena));
                    $mensaje = 'Empleado creado correctamente y correo de credenciales enviado.';
                } catch (\Exception $e) {
                    $mensaje = 'Empleado creado correctamente, pero hubo un error al enviar el correo: ' . $e->getMessage();
                    \Log::error('Error al enviar correo', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
            }



            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensaje
                ]);
            }

            return redirect()->route('empleados.index')->with('success', $mensaje);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación en store', ['errors' => $e->errors()]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors()))
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error en store method', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error interno: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $empleados = Empleados::findOrFail($id);
        $puestosTrabajo = PuestoTrabajo::all();
        return view('empleados.show', compact('empleados', 'puestosTrabajo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $empleados = Empleados::findOrFail($id);
        $puestosTrabajo = PuestoTrabajo::all();
        return view('empleados.edit', compact('empleados', 'puestosTrabajo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'puesto_trabajo_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo',
            'correo' => 'nullable|email|unique:empleados,correo,' . $id . ',id_empleado',
            'telefono' => 'nullable|string|max:20',
            'fecha_ingreso' => 'nullable|date',
            'fecha_nacimiento' => 'nullable|date|before:today',
        ]);

        $empleados = Empleados::findOrFail($id);
        $empleados->update($request->all());

        $mensaje = 'Empleado actualizado correctamente.';

        // Solo enviar correo si tiene correo electrónico
        if (!empty($empleados->correo)) {
            // Cargar la relación del puesto para el correo
            $empleados->load('puestoTrabajo');

            // Generar nueva contraseña automática
            $contrasena = $empleados->generarContrasenaAutomatica();

            // Enviar correo con credenciales actualizadas
            try {
                Mail::to($empleados->correo)->send(new AccesoMail($empleados, $contrasena));
                $mensaje = 'Empleado actualizado correctamente y correo de credenciales enviado.';
            } catch (\Exception $e) {
                $mensaje = 'Empleado actualizado correctamente, pero hubo un error al enviar el correo: ' . $e->getMessage();
            }
        }

        return redirect()->route('empleados.index')->with('success', $mensaje);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $empleados = Empleados::findOrFail($id);
        $empleados->delete();
        return redirect()->route('empleados.index')->with('success', 'Empleado eliminado correctamente');
    }

    public function export()
    {
        return Excel::download(new EmpleadosExport, 'empleados-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Descargar plantilla de Excel
     */
    public function downloadTemplate()
    {
        return Excel::download(new EmpleadosTemplateExport, 'plantilla-empleados.xlsx');
    }

    /**
     * Mostrar formulario de importación
     */
    public function importForm(): View
    {
        return view('empleados.import');
    }

    /**
     * Importar datos desde Excel
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new EmpleadosImport, $request->file('file'));

            return redirect()->route('empleados.index')
                ->with('success', 'Datos importados exitosamente.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $row = $failure->row();
                $attribute = $failure->attribute();
                $errors = $failure->errors();

                $errorMessages[] = "Fila $row - $attribute: " . implode(', ', $errors);
            }

            return redirect()->back()
                ->with('error', 'Errores de validación: ' . implode('; ', $errorMessages))
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al importar los datos: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Verificar cambios de puesto antes de importar
     */
    public function checkPuestoChanges(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray([], $file);

            // Log para debugging - solo información básica


            if (empty($data) || empty($data[0])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo está vacío'
                ]);
            }

            // Asegurar que los headers sean strings y convertirlos a minúsculas
            $headers = [];
            foreach ($data[0] as $header) {
                if (is_string($header)) {
                    $headers[] = strtolower(trim($header));
                } else {
                    $headers[] = strtolower(trim((string) $header));
                }
            }

            $rows = array_slice($data, 1);

            $changes = [];
            $empleados = Empleados::with('puestoTrabajo')->get();



            foreach ($rows as $index => $row) {
                try {
                    // Verificar que la fila sea válida
                    if (!is_array($row) || count($row) < count($headers)) {
                        continue;
                    }

                    // Asegurar que todos los valores de la fila sean strings
                    $rowProcessed = [];
                    foreach ($row as $cellIndex => $cellValue) {
                        if (is_array($cellValue)) {
                            $rowProcessed[] = implode(' ', $cellValue);
                        } else {
                            $rowProcessed[] = (string) $cellValue;
                        }
                    }

                    $rowData = array_combine($headers, $rowProcessed);

                    // Buscar empleado por correo
                    $correo = trim($rowData['correo'] ?? '');
                    $nuevoPuesto = trim($rowData['puesto_de_trabajo'] ?? '');

                    if (!empty($correo) && !empty($nuevoPuesto)) {
                        $empleado = $empleados->where('correo', $correo)->first();

                        if ($empleado && $empleado->puestoTrabajo) {
                            $puestoActual = $empleado->puestoTrabajo->nombre ?? '';

                            if ($puestoActual !== $nuevoPuesto) {
                                $changes[] = [
                                    'empleado' => $empleado->nombres . ' ' . $empleado->apellido_paterno . ' ' . $empleado->apellido_materno,
                                    'puesto_actual' => $puestoActual,
                                    'puesto_nuevo' => $nuevoPuesto,
                                    'correo' => $correo
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Error procesando fila $index: " . $e->getMessage());
                    continue;
                }
            }

            if (empty($changes)) {
                // No hay cambios, proceder directamente con la importación
                return response()->json([
                    'success' => true,
                    'has_changes' => false,
                    'message' => 'No se detectaron cambios de puesto'
                ]);
            }

            return response()->json([
                'success' => true,
                'has_changes' => true,
                'changes' => $changes,
                'message' => 'Se detectaron cambios de puesto que requieren confirmación'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en checkPuestoChanges: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el archivo: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Confirmar importación con cambios de puesto
     */
    public function confirmImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new EmpleadosImport, $request->file('file'));

            return redirect()->route('empleados.index')
                ->with('success', 'Datos importados exitosamente con los cambios de puesto confirmados.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $row = $failure->row();
                $attribute = $failure->attribute();
                $errors = $failure->errors();

                $errorMessages[] = "Fila $row - $attribute: " . implode(', ', $errors);
            }

            return redirect()->back()
                ->with('error', 'Errores de validación: ' . implode('; ', $errorMessages))
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al importar los datos: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Obtener detalles de un puesto de trabajo
     */
    public function getPuestoTrabajoDetails($id)
    {
        $puestoTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio', 'area'])->find($id);

        if (!$puestoTrabajo) {
            return response()->json(['error' => 'Puesto de trabajo no encontrado'], 404);
        }

        return response()->json([
            'division' => $puestoTrabajo->division ? $puestoTrabajo->division->nombre : 'No especificada',
            'unidad_negocio' => $puestoTrabajo->unidadNegocio ? $puestoTrabajo->unidadNegocio->nombre : 'No especificada',
            'area' => $puestoTrabajo->area ? $puestoTrabajo->area->nombre : 'No especificada'
        ]);
    }

    /**
     * Obtener preview del correo de credenciales
     */
    public function getEmailPreview(Request $request)
    {
        try {


            $request->validate([
                'nombres' => 'required|string',
                'apellido_paterno' => 'required|string',
                'apellido_materno' => 'required|string',
                'correo' => 'nullable|email',
                'puesto_trabajo_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo',
                'fecha_ingreso' => 'nullable|date'
            ]);



            // Verificar si hay correo electrónico
            if (empty($request->correo)) {
                return response()->json([
                    'error' => 'No se puede generar preview sin correo electrónico'
                ], 400);
            }

            // Crear un objeto temporal para generar la contraseña
            $empleadoTemporal = new Empleados();
            $empleadoTemporal->nombres = $request->nombres;
            $empleadoTemporal->apellido_paterno = $request->apellido_paterno;
            $empleadoTemporal->apellido_materno = $request->apellido_materno;
            $empleadoTemporal->correo = $request->correo;
            $empleadoTemporal->fecha_ingreso = $request->fecha_ingreso;
            $empleadoTemporal->puesto_trabajo_id = $request->puesto_trabajo_id;


            // Cargar la relación del puesto
            $puestoTrabajo = PuestoTrabajo::find($request->puesto_trabajo_id);
            if ($puestoTrabajo) {
                $empleadoTemporal->setRelation('puestoTrabajo', $puestoTrabajo);
            } else {
                \Log::warning('Puesto de trabajo no encontrado', ['id' => $request->puesto_trabajo_id]);
            }

            $contrasena = $empleadoTemporal->generarContrasenaAutomatica();
            //dd($contrasena, $empleadoTemporal->nombres = $request->nombres);


            // Obtener el template del correo
            $cuerpoCorreo = \App\Models\CuerpoCorreo::where('tipo', 'acceso')->first();

            if (!$cuerpoCorreo) {
                \Log::error('Template de correo no encontrado');
                return response()->json(['error' => 'Template de correo no encontrado'], 404);
            }



            // Reemplazar placeholders
            $htmlContent = $cuerpoCorreo->cuerpo_html;
            $htmlContent = str_replace('{{nombre}}', $empleadoTemporal->nombres . ' ' . $empleadoTemporal->apellido_paterno . ' ' . $empleadoTemporal->apellido_materno, $htmlContent);
            $htmlContent = str_replace('{{correo}}', $empleadoTemporal->correo, $htmlContent);
            $htmlContent = str_replace('{{contraseña}}', $contrasena, $htmlContent);
            $htmlContent = str_replace('{{puesto}}', $empleadoTemporal->puestoTrabajo->nombre ?? 'No especificado', $htmlContent);
            $htmlContent = str_replace('{{fecha_ingreso}}', $empleadoTemporal->fecha_ingreso ? \Carbon\Carbon::parse($empleadoTemporal->fecha_ingreso)->format('d/m/Y') : 'No especificada', $htmlContent);
            $htmlContent = str_replace('{{link}}', route('login'), $htmlContent);

            return response()->json([
                'html_content' => $htmlContent,
                'contrasena' => $contrasena,
                'asunto' => $cuerpoCorreo->nombre ?? 'Credenciales de Acceso'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Error de validación: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors()))], 422);
        } catch (\Exception $e) {
            \Log::error('Error en getEmailPreview', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }
}

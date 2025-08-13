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
        return view('empleados.create', compact('puestosTrabajo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $empleados = Empleados::create($request->all());
        return redirect()->route('empleados.index')->with('success', 'Empleado creado correctamente');
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
        $empleados = Empleados::findOrFail($id);
        $empleados->update($request->all());
        return redirect()->route('empleados.index')->with('success', 'Empleado actualizado correctamente');
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
            \Log::info('Datos del archivo Excel:', [
                'total_sheets' => count($data),
                'first_sheet_rows' => count($data[0] ?? [])
            ]);
            
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
}

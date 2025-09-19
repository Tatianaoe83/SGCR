<?php

namespace App\Http\Controllers;

use App\Exports\ElementosExport;
use App\Imports\ElementosImport;
use App\Models\Elemento;
use App\Models\TipoElemento;
use App\Models\TipoProceso;
use App\Models\UnidadNegocio;
use App\Models\PuestoTrabajo;
use App\Models\Division;
use App\Models\Area;
use App\Models\CampoRequeridoTipoElemento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\WordDocument;
use App\Jobs\ProcesarDocumentoWordJob;
use Maatwebsite\Excel\Facades\Excel;

class ElementoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $elementos = Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'unidadNegocio',
            'puestoResponsable',
            'puestoEjecutor',
            'puestoResguardo'
        ])->paginate(10);

        return view('elementos.index', compact('elementos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $tiposElemento = TipoElemento::all();
        $tiposProceso = TipoProceso::all();
        $unidadesNegocio = UnidadNegocio::all();
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio', 'area'])->get();
        $elementos = Elemento::all();
        $divisions = Division::all();
        $areas = Area::all();

        // Arrays vacíos para el formulario de creación
        $puestosRelacionados = [];
        $elementosPadre = [];
        $elementosRelacionados = [];

        return view('elementos.create', compact(
            'tiposElemento',
            'tiposProceso',
            'unidadesNegocio',
            'puestosTrabajo',
            'elementos',
            'divisions',
            'areas',
            'puestosRelacionados',
            'elementosPadre',
            'elementosRelacionados'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo_formato' => 'file|mimes:doc,docx,pdf,xls,xlsx|max:' . config('word-documents.max_file_size_kb'),
        ], [
            'archivo_formato.file' => 'El archivo seleccionado no es válido.',
            'archivo_formato.mimes' => 'Solo se permiten archivos .doc, .docx, .pdf, .xls y .xlsx.',
            'archivo_formato.max' => 'El archivo no puede ser mayor a ' . config('word-documents.max_file_size_kb') . ' KB.',
        ]);

        $data = $request->all();

        // Tratar valores null - convertir strings vacíos y 'null' a null
        foreach ($data as $key => $value) {
            if ($value === '' || $value === 'null' || $value === null) {
                $data[$key] = null;
            }
        }

        // Procesar arrays de relaciones
        $data['puestos_relacionados'] = $request->input('puestos_relacionados', []);
        $data['nombres_relacion'] = $request->input('nombres_relacion', []);
        $data['elementos_padre'] = $request->input('elementos_padre', []);
        $data['elemento_padre_id'] = $request->input('elemento_padre_id');
        $data['elementos_relacionados'] = $request->input('elementos_relacionados', []);

        // Convertir checkboxes a boolean
        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');

        // Manejar archivos según el tipo de elemento
        if ($request->hasFile('archivo_formato')) {
            $archivo = $request->file('archivo_formato');
            $extension = strtolower($archivo->getClientOriginalExtension());
            
            // Para elementos de tipo formato (tipo_elemento_id == 1)
            if ($data['tipo_elemento_id'] == 1) {
                // Verificación adicional para documentos Word
                if (!in_array($extension, ['doc', 'docx'])) {
                    return redirect()->back()
                        ->with('error', 'Para elementos de formato, solo se aceptan archivos .doc y .docx.')
                        ->withInput();
                }
                
                // Verificación del tamaño del archivo
                $tamañoArchivo = $archivo->getSize();
                $tamañoMaximoKB = config('word-documents.max_file_size_kb') * 1024;
                if ($tamañoArchivo > $tamañoMaximoKB) {
                    return redirect()->back()
                        ->with('error', 'El archivo es demasiado grande. Tamaño máximo: ' . config('word-documents.max_file_size_kb') . ' KB.')
                        ->withInput();
                }
                
                // Guardar archivo con nombre único
                $nombreArchivo = time() . '_' . uniqid() . '.' . $extension;
                $data['archivo_formato'] = $archivo->storeAs('elementos/formato', $nombreArchivo, 'public');
            } else {
                // Para otros tipos de elementos, guardar archivo normal
                $data['archivo_formato'] = $archivo->store('elementos/formato', 'public');
            }
        }

        // Crear el elemento
        $elemento = Elemento::create($data);

        // Procesar documento Word si es necesario
        if ($request->hasFile('archivo_formato') && $data['tipo_elemento_id'] == 1) {
            try {
                // Crear registro en BD para procesamiento
                $documento = WordDocument::create([
                    'elemento_id' => $elemento->id_elemento,
                    'contenido_markdown' => $request->contenido_markdown ?: null,
                    'estado' => 'pendiente'
                ]);

                // Procesar archivo de forma asíncrona
                ProcesarDocumentoWordJob::dispatch($documento);

                $mensaje = 'Elemento creado exitosamente. Documento Word subido y se está procesando en segundo plano.';
                if ($extension === 'doc') {
                    $mensaje .= ' Nota: Los archivos .doc pueden tener limitaciones en la extracción de contenido.';
                }

                return redirect()->route('word-documents.index')
                    ->with('success', $mensaje);

            } catch (\Exception $e) {
                Log::error('Error al procesar documento Word: ' . $e->getMessage());
                
                return redirect()->back()
                    ->with('error', 'Error al procesar el documento Word: ' . $e->getMessage())
                    ->withInput();
            }
        }

        // Para archivos normales o sin archivo
        return redirect()->route('elementos.index')
            ->with('success', 'Elemento creado exitosamente.');
    }

    public function mandatoryData($id)
    {
        $campos = CampoRequeridoTipoElemento::where('tipo_elemento_id', $id)
            ->obligatorios()
            ->orderBy('orden')
            ->get(['campo_nombre', 'campo_label']);

        return response()->json($campos);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): View
    {
        $elemento = Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'unidadNegocio',
            'puestoResponsable',
            'puestoEjecutor',
            'puestoResguardo',
            'elementoPadre',
            'elementoRelacionado',
            'elementosHijos'
        ])->findOrFail($id);

        // Obtener puestos relacionados
        $puestosRelacionados = collect();
        if ($elemento->puestos_relacionados) {
            $puestosRelacionados = PuestoTrabajo::whereIn('id_puesto_trabajo', $elemento->puestos_relacionados)->get();
        }

        // Obtener elemento padre
        $elementoPadre = null;
        if ($elemento->elemento_padre_id) {
            $elementoPadre = Elemento::find($elemento->elemento_padre_id);
        }

        // Obtener elementos relacionados
        $elementosRelacionados = collect();
        if ($elemento->elementos_relacionados) {
            $elementosRelacionados = Elemento::whereIn('id_elemento', $elemento->elementos_relacionados)->get();
        }

        return view('elementos.show', compact(
            'elemento',
            'puestosRelacionados',
            'elementoPadre',
            'elementosRelacionados'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $elemento = Elemento::findOrFail($id);
        $tiposElemento = TipoElemento::all();
        $tiposProceso = TipoProceso::all();
        $unidadesNegocio = UnidadNegocio::all();
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio', 'area'])->get();
        $elementos = Elemento::where('id_elemento', '!=', $id)->get();
        $divisions = Division::all();
        $areas = Area::all();

        // Preparar arrays para el formulario de edición
        $puestosRelacionados = $elemento->puestos_relacionados ?? [];
        $elementoPadreId = $elemento->elemento_padre_id;
        $elementosRelacionados = $elemento->elementos_relacionados ?? [];

        return view('elementos.edit', compact(
            'elemento',
            'tiposElemento',
            'tiposProceso',
            'unidadesNegocio',
            'puestosTrabajo',
            'elementos',
            'divisions',
            'areas',
            'puestosRelacionados',
            'elementoPadreId',
            'elementosRelacionados'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Elemento $elemento): RedirectResponse
    {
        /*$request->validate([
            'tipo_elemento_id' => 'required|exists:tipo_elementos,id_tipo_elemento',
            'nombre_elemento' => 'required|string|max:255',
            'tipo_proceso_id' => 'required|exists:tipo_procesos,id_tipo_proceso',
            'unidad_negocio_id' => 'required|exists:unidad_negocios,id_unidad_negocio',
            'ubicacion_eje_x' => 'required|integer',
            'control' => 'required|in:interno,externo',
            'folio_elemento' => 'required|string|max:255',
            'version_elemento' => 'required|numeric|min:0.1|max:99.9',
            'fecha_elemento' => 'required|date',
            'periodo_revision' => 'required|date',
            'puesto_responsable_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo',
            'es_formato' => 'required|in:si,no',
            'archivo_formato' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'puesto_ejecutor_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo',
            'puesto_resguardo_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo',
            'medio_soporte' => 'required|in:digital,fisico',
            'ubicacion_resguardo' => 'required|string|max:255',
            'periodo_resguardo' => 'required|date',
            'puestos_relacionados' => 'nullable|array',
            'puestos_relacionados.*' => 'exists:puesto_trabajos,id_puesto_trabajo',
            'elementos_padre' => 'nullable|array',
            'elementos_padre.*' => 'exists:elementos,id_elemento',
            'elementos_relacionados' => 'nullable|array',
            'elementos_relacionados.*' => 'exists:elementos,id_elemento',
            'correo_implementacion' => 'boolean',
            'correo_agradecimiento' => 'boolean',
        ]);*/

        $data = $request->all();

        // Manejar archivos
        if ($request->hasFile('archivo_formato')) {
            // Eliminar archivo anterior si existe
            if ($elemento->archivo_formato) {
                Storage::disk('public')->delete($elemento->archivo_formato);
            }
            $data['archivo_formato'] = $request->file('archivo_formato')->store('elementos/formato', 'public');
        }

        if ($request->hasFile('archivo_agradecimiento')) {
            // Eliminar archivo anterior si existe
            if ($elemento->archivo_agradecimiento) {
                Storage::disk('public')->delete($elemento->archivo_agradecimiento);
            }
            $data['archivo_agradecimiento'] = $request->file('archivo_agradecimiento')->store('elementos/agradecimiento', 'public');
        }

        // Procesar arrays de relaciones
        $data['puestos_relacionados'] = $request->input('puestos_relacionados', []);
        $data['elemento_padre_id'] = $request->input('elemento_padre_id');
        $data['elementos_relacionados'] = $request->input('elementos_relacionados', []);

        // Procesar correos
        $data['usuarios_correo'] = $request->input('usuarios_correo', []);
        $data['correos_libres'] = array_filter($request->input('correos_libres', []), function($correo) {
            return !empty(trim($correo));
        });

        // Convertir checkboxes a boolean
        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');

        $elemento->update($data);

        return redirect()->route('elementos.index')
            ->with('success', 'Elemento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Elemento $elemento): RedirectResponse
    {
        // Eliminar archivos si existen
        if ($elemento->archivo_formato) {
            Storage::disk('public')->delete($elemento->archivo_formato);
        }

        if ($elemento->archivo_agradecimiento) {
            Storage::disk('public')->delete($elemento->archivo_agradecimiento);
        }

        $elemento->delete();

        return redirect()->route('elementos.index')
            ->with('success', 'Elemento eliminado exitosamente.');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ElementosExport, 'plantilla-elementos.xlsx');
    }

    /* public function export()
    {
        return Excel::download(new Elemento, 'elementos.xlsx');
    } */

    public function importForm(): View
    {
        return view('elementos.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {

            $import = new \App\Imports\ElementosImport();
            \Excel::import($import, $request->file('file'));

            return back()->with('success', "Import listo. El archivo se procesó correctamente.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {

            $detalles = [];

            foreach ($e->failures() as $failure) {
                $detalles[] = [
                    'fila' => $failure->row(),
                    'columna' => $failure->attribute(),
                    'errores' => $failure->errors(),
                    'valores' => $failure->values(),
                ];
            }

            return back()
                ->with('error', 'Se encontraron errores de validación en el archivo.')
                ->with('errores_import', $detalles)
                ->withInput();
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error al importar los datos: ' . $e->getMessage())
                ->withInput();
        }
    }
}

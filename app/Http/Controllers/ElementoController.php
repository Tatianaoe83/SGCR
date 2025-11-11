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
use App\Services\ConvertWordPdfService;
use Ilovepdf\Ilovepdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ElementoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sgc.access');
    }

    /**
     * Display a listing of the resource.
     */

    public function index(): View
    {
        $tipos = TipoElemento::pluck('nombre', 'id_tipo_elemento');
        return view('elementos.index', compact('tipos'));
    }


    public function data(Request $request)
    {
        try {
            $query = Elemento::with([
                'tipoElemento:id_tipo_elemento,nombre',
                'tipoProceso:id_tipo_proceso,nombre',
                'puestoResponsable:id_puesto_trabajo,nombre',
            ]);

            // Aplicar filtro por tipo de elemento si se proporciona un valor válido
            $tipo = $request->input('tipo');
            if (!empty($tipo) && $tipo !== '') {
                $query->where('tipo_elemento_id', $tipo);
            }

            return DataTables::of($query)
                ->addColumn('tipo', function($e) {
                    return $e->tipoElemento ? $e->tipoElemento->nombre : 'N/A';
                })
                ->addColumn('proceso', function($e) {
                    return $e->tipoProceso ? $e->tipoProceso->nombre : 'N/A';
                })
                ->addColumn('responsable', function($e) {
                    return $e->puestoResponsable ? $e->puestoResponsable->nombre : 'N/A';
                })
                ->addColumn('estado', function ($e) {
                    try {
                        $semaforo = $e->textoSemaforo;
                        return "<span class='px-2 py-1 rounded-full text-white {$semaforo['color']}'>
                            {$semaforo['texto']}
                        </span>";
                    } catch (\Exception $ex) {
                        return "<span class='px-2 py-1 rounded-full text-white bg-gray-500'>
                            Sin fecha
                        </span>";
                    }
                })
                ->addColumn('periodo_revision', function ($e) {
                    if (!$e->periodo_revision) {
                        return 'Sin fecha';
                    }
                    try {
                        return \Carbon\Carbon::parse($e->periodo_revision)->format('d/m/Y');
                    } catch (\Exception $ex) {
                        return 'Sin fecha';
                    }
                })
                ->addColumn('acciones', function ($e) {
                    $showUrl = route('elementos.show', $e->id_elemento);
                    $editUrl = route('elementos.edit', $e->id_elemento);
                    $deleteUrl = route('elementos.destroy', $e->id_elemento);

                    return '
                    <div class="flex items-center justify-center gap-1">
                        <a href="' . $showUrl . '" 
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-slate-600 hover:bg-slate-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1" 
                           title="Ver detalles">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                        <a href="' . $editUrl . '" 
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" 
                           title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <form method="POST" action="' . $deleteUrl . '" 
                              onsubmit="return confirm(\'¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.\')" 
                              class="inline-block">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" 
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-rose-600 hover:bg-rose-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-1" 
                                    title="Eliminar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    ';
                })
                ->rawColumns(['acciones', 'estado'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error en ElementoController@data: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
        $maxFileSizeKB = config('word-documents.file_settings.max_file_size_kb', 5120);

        $elementos = Elemento::all();

        $rules = [
            'tipo_elemento_id' => 'required|exists:tipo_elementos,id_tipo_elemento',
            'nombre_elemento' => 'required|string|max:255',
            'archivo_formato' => 'file|mimes:docx,pdf,xls,xlsx|max:' . $maxFileSizeKB,
            'archivo_es_formato' => 'file|mimes:docx,pdf,xls,xlsx|max:' . $maxFileSizeKB,
        ];

        $request->validate($rules);

        $data = $request->all();

        $puestos = $request->input('puestos_relacionados',  null);
        $data['puestos_relacionados'] = !empty($puestos) ? $puestos : null;
        $adicionales = $request->input('nombres_relacion', null);
        $adicionales = array_filter($adicionales, fn($v) => $v !== null && $v !== '');
        $data['nombres_relacion'] = !empty($adicionales) ? $adicionales : null;
        $elementoPadre = $request->input('elementos_padre', null);
        $data['elementos_padre'] = !empty($elementoPadre) ? $elementoPadre : null;
        $relacionados = $request->input('elementos_relacionados', null);
        $data['elemento_relacionado_id'] = !empty($relacionados) ? $relacionados : null;
        $unidades = $request->input('unidad_negocio_id', null);
        $data['unidad_negocio_id'] = !empty($unidades) ? $unidades : null;

        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');

        $rutaGeneral = null;
        $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];

        if ($request->hasFile('archivo_es_formato')) {
            $archivo = $request->file('archivo_es_formato');
            $extension = strtolower($archivo->getClientOriginalExtension());

            if (!in_array($extension, $permitidos)) {
                return redirect()->back()
                    ->withInput()
                    ->with('swal_error', 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $baseName = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
            $baseName = Str::slug($baseName, '-');
            $nombreArchivoEsFormato = $baseName . '_' . uniqid() . '.' . $extension;

            $rutaGeneral = $archivo->storeAs('archivos/elementos', $nombreArchivoEsFormato, 'public');
            $data['archivo_es_formato'] = $rutaGeneral;
        }

        if ($request->hasFile('archivo_formato')) {
            $archivoEsFormato = $request->file('archivo_formato');
            $extension = strtolower($archivoEsFormato->getClientOriginalExtension());

            if (!in_array($extension, $permitidos)) {
                return redirect()->back()
                    ->withInput()
                    ->with('swal_error', 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $nombreArchivoEsFormato = pathinfo($archivoEsFormato->getClientOriginalName(), PATHINFO_FILENAME);
            $nombreArchivoEsFormato = Str::slug($nombreArchivoEsFormato, '-') . '_' . uniqid() . '.' . $extension; // Agregar sufijo único

            $rutaArchivoEsFormato = $archivoEsFormato->storeAs('archivos/formato', $nombreArchivoEsFormato, 'public');
            $data['archivo_formato'] = $rutaArchivoEsFormato;
        }

        $elemento = Elemento::create($data);

        if ($rutaGeneral && $data['tipo_elemento_id'] == 2) {
            $documento = WordDocument::create([
                'elemento_id' => $elemento->id_elemento,
                'estado' => 'pendiente'
            ]);

            ProcesarDocumentoWordJob::dispatch($documento, $rutaGeneral)->delay(now()->addSeconds(5));
        }

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

        $unidadNegocio = collect();

        if (!empty($elemento->unidad_negocio_id)) {
            $ids = is_array($elemento->unidad_negocio_id)
                ? $elemento->unidad_negocio_id
                : [$elemento->unidad_negocio_id];

            $unidadNegocio = UnidadNegocio::whereIn('id_unidad_negocio', $ids)->get();
        }

        return view('elementos.show', compact(
            'elemento',
            'puestosRelacionados',
            'elementoPadre',
            'elementosRelacionados',
            'unidadNegocio'
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
        $correoImplementacion = $elemento->correo_implementacion ?? false;
        $correoAgradecimiento = $elemento->correo_agradecimiento ?? false;
        $puestosRelacionados = $elemento->puestos_relacionados ?? [];
        $elementoPadreId = $elemento->elemento_padre_id;
        $elementosRelacionados = json_decode($elemento->elemento_relacionado_id ?? '[]');

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
            'elementosRelacionados',
            'correoImplementacion',
            'correoAgradecimiento'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Elemento $elemento): RedirectResponse
    {
      
        $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];
           // dd ($request->all());
            
        // Manejar archivo del formato del elemento
        if ($request->hasFile('archivo_es_formato')) {
           // dd ('aqui archivo es formato');
           
            $file = $request->file('archivo_es_formato');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $permitidos)) {
                return redirect()->back()
                    ->withInput()
                    ->with('swal_error', 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $fechaNow = now()->format('d-m-Y-h-i-a');
            $nombreBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '-');
            $fileName = $nombreBase . '-' . $fechaNow . '.' . $extension;
            $newPath = $file->storeAs('archivos/elementos', $fileName, 'public');

            // Borrar archivo anterior si existe
            if ($elemento->archivo_es_formato && Storage::disk('public')->exists($elemento->archivo_es_formato)) {
                Storage::disk('public')->delete($elemento->archivo_es_formato);
               
            }

            $elemento->update(['archivo_es_formato' => $newPath]);

            $tipoElementoId = $request->input('tipo_elemento_id', $elemento->tipo_elemento_id);
            if ((int) $tipoElementoId === 2) {
              
              
                $documento = WordDocument::updateOrCreate(
                    ['elemento_id' => $elemento->id_elemento],
                    ['estado' => 'pendiente', 'error_mensaje' => null, 'contenido_texto' => null]
                );

                ProcesarDocumentoWordJob::dispatch($documento, $newPath)->delay(now()->addSeconds(5));
            }
        }

        // Manejar archivo del elemento (archivo_formato)
        if ($request->hasFile('archivo_formato')) {
            $archivo = $request->file('archivo_formato');
            $extension = strtolower($archivo->getClientOriginalExtension());

            if (!in_array($extension, $permitidos)) {
                return redirect()->back()
                    ->withInput()
                    ->with('swal_error', 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $baseName = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
            $baseName = Str::slug($baseName, '-');
            $nombreArchivo = $baseName . '_' . uniqid() . '.' . $extension;
            $rutaArchivo = $archivo->storeAs('archivos/formato', $nombreArchivo, 'public');

            // Borrar archivo anterior si existe
            if ($elemento->archivo_formato && Storage::disk('public')->exists($elemento->archivo_formato)) {
                Storage::disk('public')->delete($elemento->archivo_formato);
            }

            $elemento->update(['archivo_formato' => $rutaArchivo]);
        }

        // Preparar solo los campos fillable del modelo
        $fillable = [
            'tipo_elemento_id',
            'nombre_elemento',
            'tipo_proceso_id',
            'unidad_negocio_id',
            'ubicacion_eje_x',
            'control',
            'folio_elemento',
            'version_elemento',
            'fecha_elemento',
            'periodo_revision',
            'puesto_responsable_id',
            'puesto_ejecutor_id',
            'puesto_resguardo_id',
            'medio_soporte',
            'ubicacion_resguardo',
            'periodo_resguardo',
            'es_formato',
            'elemento_padre_id',
            'elemento_relacionado_id',
            'puestos_relacionados',
            'nombres_relacion',
            'correo_implementacion',
            'correo_agradecimiento'
        ];

        $data = $request->only($fillable);

        // Mantener el valor actual si el select viene vacío por Select2/placeholder
        $data['tipo_proceso_id'] = $request->filled('tipo_proceso_id')
            ? $request->input('tipo_proceso_id')
            : $elemento->tipo_proceso_id;

        // Procesar campos que pueden ser null o vacíos
        $data['elemento_padre_id'] = $request->input('elemento_padre_id') ?: null;
        
        // Procesar elemento_relacionado_id (array)
        $elementosRelacionados = $request->input('elemento_relacionado_id', null);
        $data['elemento_relacionado_id'] = !empty($elementosRelacionados) ? $elementosRelacionados : null;

        // Procesar unidad_negocio_id (array)
        $unidades = $request->input('unidad_negocio_id', null);
        $data['unidad_negocio_id'] = !empty($unidades) ? $unidades : null;

        // Procesar puestos_relacionados (array)
        $puestos = $request->input('puestos_relacionados', null);
        $data['puestos_relacionados'] = !empty($puestos) ? $puestos : null;

        // Procesar nombres_relacion (array)
        $adicionales = $request->input('nombres_relacion', null);
        if ($adicionales) {
            $adicionales = array_filter($adicionales, fn($v) => $v !== null && $v !== '');
            $data['nombres_relacion'] = !empty($adicionales) ? $adicionales : null;
        } else {
            $data['nombres_relacion'] = null;
        }

        // Procesar checkboxes
        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');

        // Actualizar solo los campos que realmente tienen valores
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

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
        $query = Elemento::with([
            'tipoElemento:id_tipo_elemento,nombre',
            'tipoProceso:id_tipo_proceso,nombre',
            'puestoResponsable:id_puesto_trabajo,nombre',
        ]);

        if ($request->filled('tipo')) {
            $query->where('tipo_elemento_id', $request->tipo);
        }

        return DataTables::of($query)
            ->addColumn('tipo', fn($e) => $e->tipoElemento->nombre ?? 'N/A')
            ->addColumn('proceso', fn($e) => $e->tipoProceso->nombre ?? 'N/A')
            ->addColumn('responsable', fn($e) => $e->puestoResponsable->nombre ?? 'N/A')
            ->addColumn('estado', function ($e) {
                $semaforo = $e->textoSemaforo;

                return "<span class='px-2 py-1 rounded-full text-white {$semaforo['color']}'>
                {$semaforo['texto']}
            </span>";
            })

            ->addColumn('periodo_revision', function ($e) {
                if (!$e->periodo_revision) {
                    return 'Sin fecha';
                }

                return \Carbon\Carbon::parse($e->periodo_revision)->format('d/m/Y');
            })
            ->addColumn('acciones', function ($e) {
                $showUrl = route('elementos.show', $e->id_elemento);
                $editUrl = route('elementos.edit', $e->id_elemento);
                $deleteUrl = route('elementos.destroy', $e->id_elemento);

                return '
                <div class="flex space-x-2 justify-center">
                    <a href="' . $showUrl . '" class="btn bg-slate-600 hover:bg-slate-500 text-white px-3 py-1 rounded-md text-xs">
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="' . $editUrl . '" class="btn bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded-md text-xs">
                        <i class="fa fa-edit"></i>
                    </a>
                    <form method="POST" action="' . $deleteUrl . '" onsubmit="return confirm(\'¿Seguro que deseas eliminar este elemento?\')" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn bg-rose-600 hover:bg-rose-500 text-white px-3 py-1 rounded-md text-xs">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                </div>
            ';
            })
            ->rawColumns(['acciones', 'estado'])
            ->make(true);
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

        if ($rutaGeneral && $data['tipo_elemento_id'] == 1) {
            $documento = WordDocument::create([
                'elemento_id' => $elemento->id_elemento,
                'estado' => 'pendiente'
            ]);

            ProcesarDocumentoWordJob::dispatch($documento, $rutaGeneral);
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
        $data = $request->all();
        $rutaAnterior = $elemento->archivo_formato;

        if ($request->hasFile('archivo_formato')) {
            $file = $request->file('archivo_formato');

            $fechaNow   = now()->format('d-m-Y-h-i-a');
            $nombreBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '-');
            $extension  = $file->getClientOriginalExtension();

            $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];
            if (!in_array($extension, $permitidos)) {
                return redirect()->back()
                    ->withInput()
                    ->with('swal_error', 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $fileName   = $nombreBase . '-' . $fechaNow . '.' . $extension;

            $newPath = $file->storeAs('elementos/formato', $fileName, 'public');

            // Borrar archivo anterior si existe
            if ($rutaAnterior && Storage::disk('public')->exists($rutaAnterior)) {
                Storage::disk('public')->delete($rutaAnterior);
            }

            $elemento->update(['archivo_formato' => $newPath]);

            if (isset($data['tipo_elemento_id']) && $data['tipo_elemento_id'] == 1) {
                $documento = WordDocument::updateOrCreate(
                    ['elemento_id' => $elemento->id_elemento],
                    ['estado' => 'pendiente', 'error_mensaje' => null, 'contenido_texto' => null]
                );

                ProcesarDocumentoWordJob::dispatch($documento, $newPath);
            }
        }

        // Manejar archivo de agradecimiento
        if ($request->hasFile('archivo_agradecimiento')) {
            if ($elemento->archivo_agradecimiento) {
                Storage::disk('public')->delete($elemento->archivo_agradecimiento);
            }
            $data['archivo_agradecimiento'] = $request->file('archivo_agradecimiento')
                ->store('elementos/agradecimiento', 'public');
        }

        // Procesar arrays de relaciones
        $puestos = $request->input('puestos_relacionados',  null);
        $data['puestos_relacionados'] = !empty($puestos) ? $puestos : null;
        $data['elemento_padre_id'] = $request->input('elemento_padre_id');
        $elementosRelacionados = $request->input('elementos_relacionados', null);
        $data['elementos_relacionados'] = !empty($elementosRelacionados) ? $elementosRelacionados : null;

        // Procesar correos
        $data['usuarios_correo'] = $request->input('usuarios_correo', []);
        $data['correos_libres'] = array_filter(
            $request->input('correos_libres', []),
            fn($c) => !empty(trim($c))
        );

        // Checkboxes
        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');
        $adicionales = $request->input('nombres_relacion', null);
        $adicionales = array_filter($adicionales, fn($v) => $v !== null && $v !== '');
        $data['nombres_relacion'] = !empty($adicionales) ? $adicionales : null;

        //ignoramos el dato de archivo formato para evitar que mande un archivo tmp
        unset($data['archivo_formato']);

        $elemento->update($data);

        return redirect()->route('elementos.index')
            ->with('success', 'Elemento actualizado exitosamente. El documento será procesado.');
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

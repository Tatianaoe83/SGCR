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
use App\Models\Empleados;
use App\Models\Relaciones;
use App\Services\ConvertWordPdfService;
use App\Services\UserPuestoService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Ilovepdf\Ilovepdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ElementoController extends Controller
{

    private UserPuestoService $userPuestoService;

    public function __construct(UserPuestoService $userPuestoService)
    {
        $this->userPuestoService = $userPuestoService;

        $this->middleware('permission:elementos.view')->only([
            'index',
            'data',
            'show',
            'info'
        ]);

        $this->middleware('permission:elementos.create')->only([
            'create',
            'store'
        ]);

        $this->middleware('permission:elementos.edit')->only([
            'edit',
            'update',
        ]);
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
            $user = auth()->user();
            $query = Elemento::with([
                'tipoElemento:id_tipo_elemento,nombre',
                'tipoProceso:id_tipo_proceso,nombre',
                'puestoResponsable:id_puesto_trabajo,nombre',
            ]);

            if ($user && !$user->hasAnyRole('Super Administrador', 'Administrador')) {
                $puestoUsuarioId = $this->userPuestoService->obtenerPuesto($user);
                $query->visibleParaPuesto($puestoUsuarioId);
            }

            $tipo = $request->input('tipo');
            if (!empty($tipo) && $tipo !== '') {
                $query->where('tipo_elemento_id', $tipo);
            }

            return DataTables::of($query)
                ->addColumn('tipo', function ($e) {
                    return $e->tipoElemento ? $e->tipoElemento->nombre : 'N/A';
                })
                ->addColumn('proceso', function ($e) {
                    return $e->tipoProceso ? $e->tipoProceso->nombre : 'N/A';
                })
                ->addColumn('responsable', function ($e) {
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
                    $elementoId = $e->id_elemento;

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
                        <a href="' . route('elementos.info', $elementoId) . '" 
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1" 
                           title="Información">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio'])->get();
        $elementos = Elemento::all();
        $divisions = Division::all();
        $areas = Area::all();
        $empleados = Empleados::with('puestoTrabajo')->get();

        // Arrays vacíos para el formulario de creación
        $puestosRelacionados = [];
        $elementosPadre = [];
        $elementosRelacionados = [];

        $grupos = [];

        foreach ($puestosTrabajo as $puesto) {
            $division = $puesto->division->nombre ?? 'Sin División';
            $unidad = $puesto->unidadNegocio->nombre ?? 'Sin Unidad de Negocio';
            $area = $puesto->area->nombre ?? 'Sin Área';

            $grupos[$division][$unidad][$area][] = [
                'id' => $puesto->id_puesto_trabajo,
                'nombre' => $puesto->nombre,
            ];
        }

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
            'elementosRelacionados',
            'grupos',
            'empleados'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request): RedirectResponse
    {
        $maxFileSizeKB = config('word-documents.file_settings.max_file_size_kb', 5120);

        $request->validate([
            'tipo_elemento_id' => 'required|exists:tipo_elementos,id_tipo_elemento',
            'nombre_elemento' => 'required|string|max:255',
            'unidad_negocio_id' => 'nullable|array',
            'unidad_negocio_id.*' => 'integer|exists:unidad_negocios,id_unidad_negocio',
            'archivo_formato' => 'nullable|file|mimes:docx,pdf,xls,xlsx|max:' . $maxFileSizeKB,
            'archivo_es_formato' => 'nullable|file|mimes:docx,pdf,xls,xlsx|max:' . $maxFileSizeKB,
        ]);

        $data = $request->only([
            'tipo_elemento_id',
            'nombre_elemento',
            'tipo_proceso_id',
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
            'elemento_relacionado_id',
        ]);

        $data['unidad_negocio_id'] = $request->filled('unidad_negocio_id')
            ? array_map('intval', $request->input('unidad_negocio_id'))
            : null;

        $data['elemento_padre_id'] = $request->filled('elemento_padre_id')
            ? (int) $request->input('elemento_padre_id')
            : null;

        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');

        $puestos = $request->input('puestos_relacionados');
        $data['puestos_relacionados'] = (is_array($puestos) && count($puestos) > 0)
            ? array_map('intval', $puestos)
            : null;

        $rutaGeneral = null;
        $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];

        $storeFile = function (string $key, string $dir) use ($request, $permitidos) {
            if (!$request->hasFile($key)) return null;

            $file = $request->file($key);
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $permitidos, true)) {
                abort(422, 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '-');
            $name = $base . '_' . uniqid() . '.' . $ext;

            return $file->storeAs($dir, $name, 'public');
        };

        $rutaGeneral = $storeFile('archivo_es_formato', 'archivos/elementos');
        if ($rutaGeneral) $data['archivo_es_formato'] = $rutaGeneral;

        $rutaFormato = $storeFile('archivo_formato', 'archivos/formato');
        if ($rutaFormato) $data['archivo_formato'] = $rutaFormato;

        $elemento = Elemento::create($data);
        //dd($data);
        if ($request->has('nombres_relacion') && $request->has('puesto_id')) {
            $nombres = (array) $request->input('nombres_relacion');
            $puestosIds = (array) $request->input('puesto_id');

            foreach ($nombres as $index => $nombreRelacion) {
                $puestosRel = $puestosIds[$index] ?? [];

                if (is_string($puestosRel)) $puestosRel = explode(',', $puestosRel);

                $puestosRel = array_values(array_filter(array_map('intval', (array) $puestosRel)));

                if (!empty($puestosRel)) {
                    Relaciones::updateOrCreate(
                        [
                            'elementoID' => $elemento->id_elemento,
                            'nombreRelacion' => $nombreRelacion ?: 'Sin nombre',
                        ],
                        [
                            'puestos_trabajo' => json_encode($puestosRel),
                        ]
                    );
                }
            }
        }

        if ($rutaGeneral && (int)$data['tipo_elemento_id'] === 2) {
            $documento = WordDocument::create([
                'elemento_id' => $elemento->id_elemento,
                'estado' => 'pendiente'
            ]);

            ProcesarDocumentoWordJob::dispatch($documento, $rutaGeneral)->delay(now()->addSeconds(5));
        }

        return redirect()->route('elementos.index')->with('success', 'Elemento creado exitosamente.');
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
        if (!empty($elemento->puestos_relacionados) && is_array($elemento->puestos_relacionados)) {
            $puestosRelacionados = PuestoTrabajo::whereIn(
                'id_puesto_trabajo',
                $elemento->puestos_relacionados
            )->get();
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
     * Mostrar información completa del elemento (historial, recordatorios, período)
     */
    public function info(string $id): View
    {
        $elemento = Elemento::with([
            'tipoElemento',
            'puestoResponsable',
        ])->findOrFail($id);

        // Pestaña por defecto 'historial'
        $tab = 'historial';

        // Aquí puedes agregar la lógica para obtener los datos reales
        $historial = [];
        $recordatorios = [];

        $fechaRevision = Carbon::parse($elemento->periodo_revision);
        $hoy = Carbon::now();

        $daysLeft = round($hoy->diffInDays($fechaRevision, false));
        $monthsLeft = round($hoy->diffInMonths($fechaRevision, false));
        //dd($daysLeft, $monthsLeft);

        return view('elementos.info', compact('elemento', 'historial', 'recordatorios', 'tab', 'daysLeft', 'monthsLeft'));
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
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio'])->get();
        $elementos = Elemento::where('id_elemento', '!=', $id)->get();
        $divisions = Division::all();
        $areas = Area::all();

        $elementoID = $elemento->id_elemento;
        $grupos = [];

        foreach ($puestosTrabajo as $puestos) {
            $division = $puestos->division->nombre ?? 'Sin División';
            $unidad = $puestos->unidadNegocio->nombre ?? 'Sin Unidad de Negocio';
            $area = $puestos->area->nombre ?? 'Sin Área';

            $grupos[$division][$unidad][$area][] = [
                'id' => $puestos->id_puesto_trabajo,
                'nombre' => $puestos->nombre,
            ];
        }

        $relaciones = Relaciones::where('elementoID', $elemento->id_elemento)->get();

        $nombresRelacion = [];
        $puestosIds = [];
        $relacionIds = [];

        foreach ($relaciones as $r) {
            $nombresRelacion[] = $r->nombreRelacion;
            $puestosIds[] = $r->puestos_trabajo ?? [];
            $relacionIds[] = $r->relacionID;
        }

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
            'correoAgradecimiento',
            'grupos',
            'nombresRelacion',
            'puestosIds',
            'relacionIds',
            'elementoID'
        ));
    }

    public function buscarPuestoRelacion(Request $request)
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $puestos = Relaciones::where('nombreRelacion', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($relacion) {
                $puestosIds = is_array($relacion->puestos_trabajo)
                    ? $relacion->puestos_trabajo
                    : json_decode($relacion->puestos_trabajo, true);

                $puestosData = PuestoTrabajo::whereIn('id_puesto_trabajo', $puestosIds ?? [])
                    ->get(['id_puesto_trabajo', 'nombre'])
                    ->map(fn($p) => [
                        'id' => $p->id_puesto_trabajo,
                        'nombre' => $p->nombre,
                    ]);

                return [
                    'id' => $relacion->relacionID,
                    'nombre' => $relacion->nombreRelacion,
                    'puestos' => $puestosData,
                ];
            });

        return response()->json($puestos);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Elemento $elemento): RedirectResponse
    {
        $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];

        if ($request->hasFile('archivo_es_formato')) {

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

            // Borrar archivo anterior
            if ($elemento->archivo_es_formato && Storage::disk('public')->exists($elemento->archivo_es_formato)) {
                Storage::disk('public')->delete($elemento->archivo_es_formato);
            }

            $elemento->update(['archivo_es_formato' => $newPath]);

            // Si es tipo 2, mandar job
            $tipoElementoId = $request->input('tipo_elemento_id', $elemento->tipo_elemento_id);
            if ((int) $tipoElementoId === 2) {

                $documento = WordDocument::updateOrCreate(
                    ['elemento_id' => $elemento->id_elemento],
                    ['estado' => 'pendiente', 'error_mensaje' => null, 'contenido_texto' => null]
                );

                ProcesarDocumentoWordJob::dispatch($documento, $newPath)->delay(now()->addSeconds(5));
            }
        }

        if ($request->hasFile('archivo_formato')) {

            $archivo = $request->file('archivo_formato');
            $extension = strtolower($archivo->getClientOriginalExtension());

            if (!in_array($extension, $permitidos)) {
                return redirect()->back()
                    ->withInput()
                    ->with('swal_error', 'Archivo no válido. Solo se permiten: ' . implode(', ', $permitidos));
            }

            $baseName = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME), '-');
            $nombreArchivo = $baseName . '_' . uniqid() . '.' . $extension;

            $rutaArchivo = $archivo->storeAs('archivos/formato', $nombreArchivo, 'public');

            // Borrar archivo anterior
            if ($elemento->archivo_formato && Storage::disk('public')->exists($elemento->archivo_formato)) {
                Storage::disk('public')->delete($elemento->archivo_formato);
            }

            $elemento->update(['archivo_formato' => $rutaArchivo]);
        }

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
            'correo_agradecimiento',
        ];

        $data = $request->only($fillable);

        $data['tipo_proceso_id'] = $request->filled('tipo_proceso_id')
            ? $request->input('tipo_proceso_id')
            : $elemento->tipo_proceso_id;

        $data['elemento_padre_id'] = $request->input('elemento_padre_id') ?: null;

        $data['elemento_relacionado_id'] = $request->input('elemento_relacionado_id') ?: null;

        $unidades = $request->input('unidad_negocio_id', null);
        if (!empty($unidades) && is_array($unidades)) {
            $unidades = array_map('intval', $unidades);
            $data['unidad_negocio_id'] = $unidades;
        } else {
            $data['unidad_negocio_id'] = null;
        }

        $puestos = $request->input('puestos_relacionados');
        $data['puestos_relacionados'] = !empty($puestos) ? array_map('intval', $puestos) : null;

        $data['correo_implementacion'] = $request->has('correo_implementacion');
        $data['correo_agradecimiento'] = $request->has('correo_agradecimiento');

        $elemento->update($data);

        $nombres = $request->input('nombres_relacion', []);
        $puestosPorRelacion = $request->input('puesto_id', []);

        if ($request->has('nombres_relacion') && $request->has('puesto_id') && $request->has('relacion_id')) {
            $relacionIdsForm = $request->input('relacion_id', []);
            $nombres = $request->input('nombres_relacion', []);
            $puestosPorRelacion = $request->input('puesto_id', []);

            foreach ($nombres as $index => $nombreRelacion) {

                $puestos = $puestosPorRelacion[$index] ?? [];
                $puestos = array_map('intval', (array)$puestos);

                $idRelacion = $relacionIdsForm[$index] ?? null;

                if ($idRelacion) {
                    Relaciones::where('relacionID', $idRelacion)->update([
                        'nombreRelacion' => $nombreRelacion ?: 'Sin nombre',
                        'puestos_trabajo' => $puestos,
                    ]);
                } else {
                    Relaciones::create([
                        'elementoID' => $elemento->id_elemento,
                        'nombreRelacion' => $nombreRelacion ?: 'Sin nombre',
                        'puestos_trabajo' => $puestos,
                    ]);
                }
            }
        }

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

    /**
     * Mostrar vista de revisión de documento (pública, sin autenticación)
     */
    public function revisarDocumento(string $id): View
    {
        $elemento = Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'puestoResponsable',
            'puestoEjecutor',
            'puestoResguardo',
            'wordDocument'
        ])->findOrFail($id);

        // Obtener archivos adjuntos
        $archivosAdjuntos = [];

        if ($elemento->archivo_es_formato) {
            $archivosAdjuntos[] = [
                'nombre' => basename($elemento->archivo_es_formato),
                'ruta' => $elemento->archivo_es_formato,
                'tamaño' => Storage::disk('public')->exists($elemento->archivo_es_formato)
                    ? Storage::disk('public')->size($elemento->archivo_es_formato)
                    : 0,
                'tipo' => pathinfo($elemento->archivo_es_formato, PATHINFO_EXTENSION)
            ];
        }

        if ($elemento->archivo_formato) {
            $archivosAdjuntos[] = [
                'nombre' => basename($elemento->archivo_formato),
                'ruta' => $elemento->archivo_formato,
                'tamaño' => Storage::disk('public')->exists($elemento->archivo_formato)
                    ? Storage::disk('public')->size($elemento->archivo_formato)
                    : 0,
                'tipo' => pathinfo($elemento->archivo_formato, PATHINFO_EXTENSION)
            ];
        }

        // Obtener contenido del documento si existe
        $contenidoDocumento = $elemento->wordDocument->contenido_texto ?? null;

        return view('elementos.revision', compact(
            'elemento',
            'archivosAdjuntos',
            'contenidoDocumento'
        ));
    }

    public function getElementosPorTipo($tipo)
    {
        $excludeId = request('exclude');

        return Elemento::where('tipo_elemento_id', $tipo)
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id_elemento', '!=', $excludeId);
            })
            ->select('id_elemento', 'nombre_elemento', 'folio_elemento', 'tipo_elemento_id')
            ->orderBy('nombre_elemento')
            ->get();
    }
}

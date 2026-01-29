<?php

namespace App\Http\Controllers;

use App\Exports\ElementosExport;
use App\Jobs\EnviarFirmaMail;
use App\Jobs\EnviarFirmaRespuestaMail;
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
use App\Models\ControlCambio;
use App\Models\Empleados;
use App\Models\Firmas;
use App\Models\Relaciones;
use App\Services\FirmasReminderService;
use App\Services\UserPuestoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
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
                ->addColumn('status', function ($e) {
                    return match ($e->status) {
                        'Publicado' => '
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold
                                    rounded-full bg-green-100 text-green-800">
                            Publicado
                        </span>',

                        'En Firmas' => '
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold
                                    rounded-full bg-yellow-100 text-yellow-800">
                            En firmas
                        </span>',

                        'Rechazado' => '
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold
                                    rounded-full bg-red-100 text-red-800">
                            Rechazado
                        </span>',

                        default => '
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold
                                    rounded-full bg-gray-100 text-gray-700">
                            En Proceso
                        </span>',
                    };
                })
                ->addColumn('acciones', function ($e) {
                    $showUrl = route('elementos.show', $e->id_elemento);
                    $editUrl = route('elementos.edit', $e->id_elemento);
                    $deleteUrl = route('elementos.destroy', $e->id_elemento);
                    $elementoId = $e->id_elemento;
                    $user = auth()->user();

                    $html = '<div class="flex items-center justify-center gap-1">';

                    if ($user && $user->can('elementos.view')) {
                        $html .= '
                        <a href="' . $showUrl . '" 
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-slate-600 hover:bg-slate-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1" 
                           title="Ver detalles">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>';
                    }

                    if ($user && $user->can('elementos.edit')) {
                        $html .= '
                        <a href="' . $editUrl . '" 
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" 
                           title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>';
                    }

                    if ($user && $user->can('elementos.info')) {
                        $html .= '
                        <a href="' . route('elementos.info', $elementoId) . '" 
                           class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1" 
                           title="Información">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </a>';
                    }

                    if ($user && $user->can('elementos.delete')) {
                        $html .= '
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
                        </form>';
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['acciones', 'estado', 'status'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Error en ElementoController@data: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
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

        $puestosRelacionados = [];
        $elementosPadre = [];
        $elementosRelacionados = [];

        $grupos = [];

        foreach ($puestosTrabajo as $puesto) {
            $division = $puesto->division->nombre ?? 'Sin División';
            $unidad   = $puesto->unidadNegocio->nombre ?? 'Sin Unidad de Negocio';

            $grupos[$division][$unidad][] = [
                'id'     => $puesto->id_puesto_trabajo,
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
        $maxFileSizeKB = (int) config('word-documents.file_settings.max_file_size_kb', 5120);
        $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];

        $request->validate([
            'tipo_elemento_id'   => 'required|exists:tipo_elementos,id_tipo_elemento',
            'nombre_elemento'    => 'required|string|max:255',

            'participantes'      => 'nullable|array',
            'participantes.*'    => 'integer|exists:empleados,id_empleado',

            'responsables'       => 'nullable|array',
            'responsables.*'     => 'integer|exists:empleados,id_empleado',

            'unidad_negocio_id'      => 'nullable|array',
            'unidad_negocio_id.*'    => 'integer',

            'puestos_relacionados'   => 'nullable|array',
            'puestos_relacionados.*' => 'integer',

            'elemento_relacionado_id'      => 'nullable|array',
            'elemento_relacionado_id.*'    => 'integer',

            'elemento_padre_id'      => 'nullable|integer',

            'archivo_formato'    => 'nullable|file|mimes:docx,pdf,xls,xlsx|max:' . $maxFileSizeKB,
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
            'elemento_padre_id',
            'unidad_negocio_id',
            'puestos_relacionados',
            'elemento_relacionado_id',
        ]);

        $data['correo_implementacion'] = $request->boolean('correo_implementacion');
        $data['correo_agradecimiento'] = $request->boolean('correo_agradecimiento');

        $data['unidad_negocio_id'] = $this->intArrayOrNull($request->input('unidad_negocio_id', []));
        $data['puestos_relacionados'] = $this->intArrayOrNull($request->input('puestos_relacionados', []));
        $data['elemento_relacionado_id'] = $this->intArrayOrNull($request->input('elemento_relacionado_id', []));

        if ($ruta = $this->storeUploadedFile($request, 'archivo_formato', 'archivos/formato', $permitidos)) {
            $data['archivo_formato'] = $ruta;
        }

        $rutaGeneral = $this->storeUploadedFile($request, 'archivo_es_formato', 'archivos/elementos', $permitidos);
        if ($rutaGeneral) {
            $data['archivo_es_formato'] = $rutaGeneral;
        }

        $participantes = $this->intArray($request->input('participantes', []));
        $responsables  = $this->intArray($request->input('responsables', []));
        $reviso = $this->intArray($request->input('reviso', []));
        $autorizo = $this->intArray($request->input('autorizo', []));

        $elemento = null;
        $firmaIds = [];

        DB::transaction(function () use (
            $data,
            $rutaGeneral,
            $participantes,
            $responsables,
            $autorizo,
            $reviso,
            $request,
            $elemento,
            &$firmaIds
        ) {
            $elemento = Elemento::create($data);

            $firmaIds = $this->crearFirmas(
                $elemento->id_elemento,
                $participantes,
                $responsables,
                $autorizo,
                $reviso
            );

            foreach ($firmaIds as $firmaId) {
                Log::info("Preparando correo para firma ID: {$firmaId}");
                EnviarFirmaMail::dispatch($firmaId)->afterCommit();
            }

            $this->insertRelacionesComites($request, $elemento->id_elemento);

            if ($rutaGeneral && (int) $data['tipo_elemento_id'] === 2) {
                $documento = WordDocument::create([
                    'elemento_id' => $elemento->id_elemento,
                    'estado'      => 'pendiente',
                ]);

                ProcesarDocumentoWordJob::dispatch($documento, $rutaGeneral)
                    ->delay(now()->addSeconds(5));
            }

            $this->crearControlCambio($elemento->id_elemento);
        });

        return redirect()
            ->route('elementos.index')
            ->with('success', 'Elemento creado correctamente.');
    }

    private function crearControlCambio(int $idElemento): void
    {
        $año = (int) now()->format('y');
        $baseAño = $año * 1000;

        $ultimoFolio = ControlCambio::where('FolioCambio', 'like', 'GC' . $baseAño . '%')
            ->select(DB::raw('MAX(CAST(SUBSTRING(FolioCambio, 3) AS UNSIGNED)) as max_folio'))
            ->value('max_folio');

        $consecutivo = $ultimoFolio
            ? ($ultimoFolio - $baseAño) + 1
            : 1;

        $folioNumerico = $baseAño + $consecutivo;

        ControlCambio::create([
            'id_elemento' => $idElemento,
            'FolioCambio' => 'GC' . $folioNumerico,
        ]);
    }

    private function intArray(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return array_values(
            array_filter(
                array_map('intval', (array) $value),
                fn(int $n) => $n > 0
            )
        );
    }

    private function storeUploadedFile(Request $request, string $key, string $dir, array $permitidos, string $disk = 'public'): ?string
    {
        if (!$request->hasFile($key)) {
            return null;
        }

        $file = $request->file($key);
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $permitidos, true)) {
            abort(422, 'Archivo no válido.');
        }

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '-');
        $name = $base . '_' . uniqid() . '.' . $ext;

        return $file->storeAs($dir, $name, $disk);
    }

    private function crearFirmas(
        int $elementoId,
        array $participantes,
        array $responsables,
        array $autorizo,
        array $reviso
    ): array {
        $map = [
            'Participante' => $participantes,
            'Responsable'  => $responsables,
            'Reviso' => $reviso,
            'Autorizo' => $autorizo,
        ];

        $ids = array_values(array_unique(array_merge($participantes, $responsables, $autorizo, $reviso)));
        if (!$ids) return [];

        $empleados = Empleados::whereIn('id_empleado', $ids)
            ->get(['id_empleado', 'puesto_trabajo_id', 'correo'])
            ->keyBy('id_empleado');


        $now = now();
        $nextReminder = $now->copy()->addWeek()->addSeconds(rand(0, 300));

        $rows = [];
        foreach ($map as $tipo => $lista) {
            foreach ($lista as $empleadoId) {
                $empleado = $empleados->get($empleadoId);
                if (!$empleado || !$empleado->puesto_trabajo_id) continue;

                $rows[] = [
                    'elemento_id'      => $elementoId,
                    'empleado_id'      => (int) $empleado->id_empleado,
                    'puestoTrabajo_id' => (int) $empleado->puesto_trabajo_id,
                    'tipo'             => $tipo,
                    'estatus'          => 'Pendiente',
                    'next_reminder_at' => $nextReminder
                ];
            }
        }

        if (!$rows) return [];

        Firmas::insert($rows);

        return Firmas::where('elemento_id', $elementoId)
            ->where('estatus', 'Pendiente')
            ->pluck('id')
            ->toArray();
    }

    private function insertRelacionesComites(Request $request, int $elementoId): void
    {
        if (!$request->has('nombres_relacion') || !$request->has('puesto_id')) {
            return;
        }

        $nombres    = (array) $request->input('nombres_relacion');
        $puestosIds = (array) $request->input('puesto_id');

        foreach ($nombres as $index => $nombreRelacion) {
            $puestosRel = $this->intArray($puestosIds[$index] ?? []);

            if (!$puestosRel) {
                continue;
            }

            Relaciones::updateOrCreate(
                [
                    'elementoID'     => $elementoId,
                    'nombreRelacion' => $nombreRelacion ?: 'Sin nombre',
                ],
                [
                    'puestos_trabajo' => $puestosRel,
                ]
            );
        }
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

        $firmas = Firmas::with(['empleado', 'puestoTrabajo'])
            ->where('elemento_id', $elemento->id_elemento)
            ->whereIn('tipo', ['Participante', 'Responsable', 'Reviso', 'Autorizo'])
            ->get();

        return view('elementos.show', compact(
            'elemento',
            'puestosRelacionados',
            'elementoPadre',
            'elementosRelacionados',
            'unidadNegocio',
            'firmas'
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

        $firmasPendientes = Firmas::with(['empleado', 'puestoTrabajo'])
            ->where('elemento_id', $elemento->id_elemento)
            ->where('estatus', 'Pendiente')
            ->get();

        $firmasHistorial = Firmas::with(['empleado', 'puestoTrabajo'])
            ->where('elemento_id', $elemento->id_elemento)
            ->whereIn('estatus', ['Aprobado', 'Rechazado'])
            ->orderBy('fecha', 'asc')
            ->get();

        // Pestaña por defecto 'historial'
        $tab = 'historial';

        // Aquí puedes agregar la lógica para obtener los datos reales
        $historial = [];
        $recordatorios = [];

        $fechaRevision = Carbon::parse($elemento->periodo_revision);
        $hoy = Carbon::now();

        $daysLeft = round($hoy->diffInDays($fechaRevision, false));
        $monthsLeft = round($hoy->diffInMonths($fechaRevision, false));

        return view('elementos.info', compact('elemento', 'firmasPendientes', 'firmasHistorial', 'historial', 'recordatorios', 'tab', 'daysLeft', 'monthsLeft'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $elemento = Elemento::findOrFail($id);

        $tiposElemento   = TipoElemento::all();
        $tiposProceso    = TipoProceso::all();
        $unidadesNegocio = UnidadNegocio::all();
        $empleados = Empleados::with('puestoTrabajo')->get();
        $puestosTrabajo = PuestoTrabajo::with([
            'division',
            'unidadNegocio',
        ])->get();

        $elementos  = Elemento::where('id_elemento', '!=', $id)->get();
        $divisions  = Division::all();
        $areas      = Area::all();

        $elementoID = $elemento->id_elemento;
        $grupos = [];

        foreach ($puestosTrabajo as $puesto) {
            $division = $puesto->division->nombre ?? 'Sin División';
            $unidad   = $puesto->unidadNegocio->nombre ?? 'Sin Unidad de Negocio';

            $grupos[$division][$unidad][] = [
                'id'     => $puesto->id_puesto_trabajo,
                'nombre' => $puesto->nombre,
            ];
        }

        $relaciones = Relaciones::where('elementoID', $elementoID)->get();

        $nombresRelacion = [];
        $puestosIds      = [];
        $relacionIds     = [];

        foreach ($relaciones as $r) {
            $nombresRelacion[] = $r->nombreRelacion;
            $puestosIds[]      = $r->puestos_trabajo ?? [];
            $relacionIds[]     = $r->relacionID;
        }

        //dd($puestosIds);

        $firmas = Firmas::where('elemento_id', $elementoID)->get();

        $participantesIds = $firmas
            ->where('tipo', 'Participante')
            ->pluck('empleado_id')
            ->toArray();

        $responsablesIds = $firmas
            ->where('tipo', 'Responsable')
            ->pluck('empleado_id')
            ->toArray();

        $autorizoIds = $firmas
            ->where('tipo', 'Autorizo')
            ->pluck('empleado_id')
            ->toArray();

        $revisoIds = $firmas
            ->where('tipo', 'Reviso')
            ->pluck('empleado_id')
            ->toArray();

        $correoImplementacion = (bool) ($elemento->correo_implementacion ?? false);
        $correoAgradecimiento = (bool) ($elemento->correo_agradecimiento ?? false);

        $puestosRelacionados = $elemento->puestos_relacionados ?? [];
        $elementoPadreId     = $elemento->elemento_padre_id;

        $elementosRelacionados = ($elemento->elemento_relacionado_id ?? '[]');

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
            'elementoID',
            'participantesIds',
            'responsablesIds',
            'autorizoIds',
            'revisoIds',
            'empleados'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Elemento $elemento): RedirectResponse
    {
        $maxFileSizeKB = (int) config('word-documents.file_settings.max_file_size_kb', 5120);
        $permitidos = ['docx', 'pdf', 'xls', 'xlsx'];

        \Log::info('DEBUG request all', $request->all());

        $request->validate([
            'tipo_elemento_id'   => 'required|exists:tipo_elementos,id_tipo_elemento',
            'nombre_elemento'    => 'required|string|max:255',

            'participantes'      => 'nullable|array',
            'participantes.*'    => 'integer|exists:empleados,id_empleado',

            'responsables'       => 'nullable|array',
            'responsables.*'     => 'integer|exists:empleados,id_empleado',

            'unidad_negocio_id'      => 'nullable|array',
            'unidad_negocio_id.*'    => 'integer',

            'puestos_relacionados'   => 'nullable|array',
            'puestos_relacionados.*' => 'integer',

            'elemento_relacionado_id'      => 'nullable|array',
            'elemento_relacionado_id.*'    => 'integer',

            'elemento_padre_id' => 'nullable|integer',

            'archivo_formato'    => 'nullable|file|mimes:docx,pdf,xls,xlsx|max:' . $maxFileSizeKB,
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
            'elemento_padre_id',
        ]);

        $data['correo_implementacion'] = $request->boolean('correo_implementacion');
        $data['correo_agradecimiento'] = $request->boolean('correo_agradecimiento');

        $data['unidad_negocio_id']       = $this->intArrayOrNull($request->input('unidad_negocio_id', []));
        $data['puestos_relacionados']    = $this->intArrayOrNull($request->input('puestos_relacionados', []));
        $data['elemento_relacionado_id'] = $this->intArrayOrNull($request->input('elemento_relacionado_id', []));

        $rutaFormato = $this->replaceUploadedFile(
            $request,
            'archivo_formato',
            'archivos/formato',
            $permitidos,
            $elemento->archivo_formato
        );

        if ($rutaFormato) {
            $data['archivo_formato'] = $rutaFormato;
        }

        $rutaGeneral = $this->replaceUploadedFile(
            $request,
            'archivo_es_formato',
            'archivos/elementos',
            $permitidos,
            $elemento->archivo_es_formato
        );

        if ($rutaGeneral) {
            $data['archivo_es_formato'] = $rutaGeneral;
        }

        $participantes = $this->intArray($request->input('participantes', []));
        $responsables  = $this->intArray($request->input('responsables', []));
        $reviso  = $this->intArray($request->input('reviso', []));
        $autorizo  = $this->intArray($request->input('autorizo', []));

        DB::transaction(function () use (
            $data,
            $rutaGeneral,
            $participantes,
            $reviso,
            $autorizo,
            $responsables,
            $request,
            $elemento
        ) {

            $elemento->update($data);

            $this->updateFirmas(
                $elemento->id_elemento,
                $participantes,
                $responsables,
                $reviso,
                $autorizo
            );

            $this->insertRelacionesComites(
                $request,
                $elemento->id_elemento
            );

            if ($rutaGeneral && (int) $data['tipo_elemento_id'] === 2) {
                $documento = WordDocument::updateOrCreate(
                    ['elemento_id' => $elemento->id_elemento],
                    [
                        'estado'          => 'pendiente',
                        'error_mensaje'   => null,
                        'contenido_texto' => null,
                    ]
                );

                ProcesarDocumentoWordJob::dispatch($documento, $rutaGeneral)
                    ->delay(now()->addSeconds(5));
            }
        });

        return redirect()
            ->route('elementos.index')
            ->with('success', 'Elemento actualizado exitosamente.');
    }

    private function replaceUploadedFile(
        Request $request,
        string $key,
        string $dir,
        array $permitidos,
        ?string $oldPath,
        string $disk = 'public'
    ): ?string {

        if (!$request->hasFile($key)) {
            return null;
        }

        $file = $request->file($key);

        if (!$file->isValid()) {
            throw new RuntimeException('Archivo subido inválido.');
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $permitidos, true)) {
            throw new InvalidArgumentException('Archivo no válido.');
        }

        if ($oldPath && Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->delete($oldPath);
        }

        $base = Str::slug(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            '-'
        );

        $name = $base . '-' . now()->format('YmdHis') . '.' . $ext;

        return $file->storeAs($dir, $name, $disk);
    }

    private function updateFirmas(
        int $elementoId,
        array $participantes,
        array $responsables,
        array $reviso,
        array $autorizo
    ): void {

        $map = [
            'Participante' => $participantes,
            'Responsable'  => $responsables,
            'Reviso'       => $reviso,
            'Autorizo'     => $autorizo,
        ];

        $finalKeys = [];

        foreach ($map as $tipo => $lista) {
            foreach ($lista as $empleadoId) {
                $finalKeys[] = $empleadoId . '|' . $tipo;
            }
        }

        $firmasActuales = Firmas::where('elemento_id', $elementoId)->get();

        foreach ($firmasActuales as $firma) {
            $key = $firma->empleado_id . '|' . $firma->tipo;

            if (!in_array($key, $finalKeys, true)) {
                $firma->delete();
            }
        }

        $ids = array_values(array_unique(array_merge(
            $participantes,
            $responsables,
            $reviso,
            $autorizo
        )));

        if (empty($ids)) {
            return;
        }

        $empleados = Empleados::whereIn('id_empleado', $ids)
            ->get(['id_empleado', 'puesto_trabajo_id', 'correo'])
            ->keyBy('id_empleado');

        $existentes = Firmas::where('elemento_id', $elementoId)
            ->get(['empleado_id', 'tipo'])
            ->map(fn($f) => $f->empleado_id . '|' . $f->tipo)
            ->toArray();

        foreach ($map as $tipo => $lista) {
            foreach ($lista as $empleadoId) {

                $empleado = $empleados->get($empleadoId);

                if (!$empleado || !$empleado->puesto_trabajo_id) {
                    continue;
                }

                $key = $empleadoId . '|' . $tipo;

                if (in_array($key, $existentes, true)) {
                    continue;
                }

                $firma = Firmas::create([
                    'elemento_id'      => $elementoId,
                    'empleado_id'      => (int) $empleado->id_empleado,
                    'puestoTrabajo_id' => (int) $empleado->puesto_trabajo_id,
                    'tipo'             => $tipo,
                    'estatus'          => 'Pendiente',
                ]);

                EnviarFirmaMail::dispatch($firma->id);
            }
        }
    }

    private function intArrayOrNull(mixed $value): ?array
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        $arr = array_values(
            array_filter(
                array_map('intval', (array) $value),
                fn(int $n) => $n > 0
            )
        );

        return empty($arr) ? null : $arr;
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
            Excel::import($import, $request->file('file'));

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
     * Mostrar vista de revisión de documento (pública, sin autenticación)
     */
    public function revisarDocumento(string $elementoId, string $firmaId): View|Response
    {
        $firma = Firmas::with([
            'empleado',
            'puestoTrabajo',
            'elemento.tipoElemento',
            'elemento.tipoProceso',
            'elemento.puestoResponsable',
            'elemento.puestoEjecutor',
            'elemento.puestoResguardo',
            'elemento.wordDocument',
        ])->findOrFail($firmaId);

        $elemento = $firma->elemento;

        $elementoCerrado = in_array($elemento->status, ['Rechazado', 'Publicado'], true);
        $firmaCerrada    = $firma->estatus !== 'Pendiente';

        if ($elementoCerrado || $firmaCerrada) {
            return response()
                ->view('pages.utility.404', [], 404);
        }

        $archivosAdjuntos = [];

        if (!empty($elemento->archivo_es_formato)) {
            $archivosAdjuntos[] = [
                'nombre' => basename($elemento->archivo_es_formato),
                'ruta'   => $elemento->archivo_es_formato,
                'tamaño' => Storage::disk('public')->exists($elemento->archivo_es_formato)
                    ? Storage::disk('public')->size($elemento->archivo_es_formato)
                    : 0,
                'tipo'   => pathinfo($elemento->archivo_es_formato, PATHINFO_EXTENSION),
            ];
        }

        $contenidoDocumento = $elemento->wordDocument->contenido_texto ?? null;

        return view('elementos.revision', compact(
            'elemento',
            'firma',
            'archivosAdjuntos',
            'contenidoDocumento'
        ));
    }

    public function updateFirmaStatus(Request $request, string $firmaId): JsonResponse
    {
        return DB::transaction(function () use ($request, $firmaId) {

            $data = $request->validate([
                'estatus' => ['required', 'in:Aprobado,Rechazado'],
                'comentario_rechazo' => ['nullable', 'string', 'max:1000', 'required_if:estatus,Rechazado'],
            ]);

            $firma = Firmas::with('elemento')
                ->where('id', $firmaId)
                ->lockForUpdate()
                ->firstOrFail();

            $firma->update([
                'estatus' => $data['estatus'],
                'fecha' => now(),
                'comentario_rechazo' => $data['estatus'] === 'Rechazado'
                    ? $data['comentario_rechazo']
                    : null,
            ]);

            $firmas = Firmas::where('elemento_id', $firma->elemento_id)
                ->whereIn('tipo', ['Responsable', 'Participante'])
                ->lockForUpdate()
                ->get();

            $hasRejected = $firmas->contains(fn($f) => $f->estatus === 'Rechazado');
            $hasPending  = $firmas->contains(fn($f) => $f->estatus === 'Pendiente');

            $allApproved = $firmas->isNotEmpty()
                && $firmas->every(fn($f) => $f->estatus === 'Aprobado');

            if ($hasRejected) {
                $firma->elemento->update(['status' => 'Rechazado']);
            } elseif ($allApproved && !$hasPending) {
                $firma->elemento->update(['status' => 'Publicado']);
            } else {
                $firma->elemento->update(['status' => 'En Firmas']);
            }

            $evento = match ($firma->estatus) {
                'Aprobado'  => 'aprobado',
                'Rechazado' => 'rechazado',
            };

            EnviarFirmaRespuestaMail::dispatch(
                $firma->id,
                $evento
            );

            return response()->json([
                'ok' => true,
                'estatus' => $firma->estatus,
                'message' => 'Firma actualizada correctamente',
            ]);
        });
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

    /* public function cambiarTimerRecordatorio(
        Request $request,
        Elemento $elemento,
        Firmas $firma,
        FirmasReminderService $service
    ) {
        if ((int) $firma->elemento_id !== (int) $elemento->getKey()) {
            abort(403, 'La firma no pertenece a este elemento');
        }

        if ($elemento->status === 'Rechazado') {
            abort(403, 'El elemento fue rechazado; no se permiten recordatorios.');
        }

        if ($elemento->status === 'Publicado') {
            abort(403, 'El elemento ya fue publicado; no se permiten recordatorios.');
        }

        $data = $request->validate([
            'timer' => ['required', 'in:Diario,Semanal,Cada3Días'],
        ]);

        $service->setManualTimerFromNow($firma, $data['timer']);

        return response()->json([
            'message' => 'Timer de recordatorio actualizado correctamente.',
        ]);
    } */

    public function cambiarFrecuencia(Request $request, Firmas $firma)
    {
        $request->validate([
            'frecuencia' => 'required|in:Diario,Cada3Días,Semanal'
        ]);

        $firma->timer_recordatorio = $request->frecuencia;
        $firma->next_reminder_at = $firma->calcularSiguienteRecordatorio(now());
        $firma->next_reminder_at = $firma->next_reminder_at->setTime(9, 0, 0);
        $firma->save();

        return response()->json(['ok' => true]);
    }
}

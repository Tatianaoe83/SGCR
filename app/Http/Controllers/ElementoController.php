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
use App\Services\FirmaWorkFlowService;
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
                ->addColumn('id_elemento', function ($e) {
                    return $e->id_elemento ?? 'N/A';
                })
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

    private function normalizeIds(array $arr): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', array_filter($arr)),
            static fn(int $n) => $n > 0
        )));
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
        $rules = $this->getElementoValidationRules();
        $rules['folio_elemento'] = 'required|string|max:255|unique:elementos,folio_elemento';
        $rules['archivo_es_formato'] = 'nullable|file|mimes:docx,doc,pdf|max:' . $this->getMaxFileSizeKB();

        $request->validate($rules);

        $data = $this->prepareElementoData($request);
        $permitidos = $this->getAllowedFileExtensions();

        if ($ruta = $this->storeUploadedFile($request, 'archivo_formato', 'archivos/formato', $permitidos)) {
            $data['archivo_formato'] = $ruta;
        }

        $rutaGeneral = $this->storeUploadedFile($request, 'archivo_es_formato', 'archivos/elementos', $permitidos);
        if ($rutaGeneral) {
            $data['archivo_es_formato'] = $rutaGeneral;
        }

        [$participantes, $responsables, $reviso, $autorizo, $ordenPrioridades] = $this->extractFirmasData($request);

        $elemento = DB::transaction(function () use (
            $data,
            $rutaGeneral,
            $participantes,
            $responsables,
            $autorizo,
            $reviso,
            $ordenPrioridades,
            $request
        ) {
            $elemento = Elemento::create($data);

            $this->crearFirmas(
                $elemento->id_elemento,
                $participantes,
                $responsables,
                $autorizo,
                $reviso,
                $ordenPrioridades
            );

            $this->insertRelacionesComites($request, $elemento->id_elemento);

            if ($rutaGeneral && (int) $data['tipo_elemento_id'] === 2) {
                $documento = WordDocument::create([
                    'elemento_id' => $elemento->id_elemento,
                    'estado' => 'pendiente',
                ]);

                ProcesarDocumentoWordJob::dispatch($documento, $rutaGeneral)
                    ->delay(now()->addSeconds(5));
            }

            $this->crearControlCambio($elemento->id_elemento);

            return $elemento;
        });

        app(FirmaWorkFlowService::class)->dispatchPendingForElemento($elemento->id_elemento);

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
        return $this->normalizeIds(
            is_string($value) ? explode(',', $value) : (array) $value
        );
    }

    private function storeUploadedFile(
        Request $request,
        string $key,
        string $dir,
        array $permitidos,
        ?string $oldPath = null,
        string $disk = 'public',
        bool $deleteOldNow = true
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

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '-');
        $name = $base . '_' . now()->format('YmdHis') . '_' . Str::random(6) . '.' . $ext;

        $newPath = $file->storeAs($dir, $name, $disk);

        if ($deleteOldNow && $oldPath && Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->delete($oldPath);
        }

        return $newPath;
    }

    private function crearFirmas(
        int $elementoId,
        array $participantes,
        array $responsables,
        array $autorizo,
        array $reviso,
        array $ordenPrioridades = []
    ): void {
        $map = $this->buildFirmasMap($participantes, $responsables, $reviso, $autorizo);

        if (
            isset($ordenPrioridades['Participante']) || isset($ordenPrioridades['Responsable'])
            || isset($ordenPrioridades['Reviso']) || isset($ordenPrioridades['Autorizo'])
        ) {
            $prioridadPorTipo = $ordenPrioridades;
        } else {
            $prioridadPorTipo = $this->prioridadPorTipo($ordenPrioridades);
        }

        $ids = $this->extractUniqueIds($map);

        if (empty($ids)) {
            return;
        }

        $empleados = Empleados::whereIn('id_empleado', $ids)
            ->get(['id_empleado', 'puesto_trabajo_id'])
            ->keyBy('id_empleado');

        $rows = $this->buildFirmasRows($elementoId, $map, $empleados, $prioridadPorTipo);

        if (!empty($rows)) {
            Firmas::insert($rows);
        }
    }

    private function parsePrioridadesFirmas(?string $raw): array
    {
        if (!$raw) return [];

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) return [];

            return array_values(array_filter(array_map(
                fn($v) => is_string($v) ? trim($v) : null,
                $decoded
            )));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function prioridadPorTipo(array $orden): array
    {
        $mapFrontToDb = [
            'Participantes' => 'Participante',
            'Responsables'  => 'Responsable',
            'Revisó'        => 'Reviso',
            'Reviso'        => 'Reviso',
            'Autorizó'      => 'Autorizo',
            'Autorizo'      => 'Autorizo',
        ];

        $prioridades = [];
        $pos = 1;

        foreach ($orden as $tipoFront) {
            $tipoDb = $mapFrontToDb[$tipoFront] ?? null;
            if (!$tipoDb) continue;

            if (!isset($prioridades[$tipoDb])) {
                $prioridades[$tipoDb] = $pos++;
            }
        }

        return $prioridades;
    }

    private function insertRelacionesComites(Request $request, int $elementoId): void
    {
        $nombres = $request->input('nombres_relacion');
        $puestosIds = $request->input('puesto_id');

        if (!$nombres || !$puestosIds) {
            return;
        }

        foreach ((array) $nombres as $index => $nombreRelacion) {
            $puestosRel = $this->intArray($puestosIds[$index] ?? []);

            if (empty($puestosRel)) {
                continue;
            }

            Relaciones::updateOrCreate(
                [
                    'elementoID' => $elementoId,
                    'nombreRelacion' => $nombreRelacion ?: 'Sin nombre',
                ],
                ['puestos_trabajo' => $puestosRel]
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
            'tipoElemento:id_tipo_elemento,nombre',
            'tipoProceso:id_tipo_proceso,nombre',
            'puestoResponsable:id_puesto_trabajo,nombre',
            'puestoEjecutor:id_puesto_trabajo,nombre',
            'puestoResguardo:id_puesto_trabajo,nombre',
            'elementoPadre:id_elemento,nombre_elemento,folio_elemento',
        ])->findOrFail($id);

        $puestosRelacionados = empty($elemento->puestos_relacionados)
            ? collect()
            : PuestoTrabajo::whereIn('id_puesto_trabajo', (array) $elemento->puestos_relacionados)
            ->get(['id_puesto_trabajo', 'nombre']);

        $elementosRelacionados = empty($elemento->elemento_relacionado_id)
            ? collect()
            : Elemento::whereIn('id_elemento', (array) $elemento->elemento_relacionado_id)
            ->get(['id_elemento', 'nombre_elemento', 'folio_elemento']);

        $unidadNegocio = empty($elemento->unidad_negocio_id)
            ? collect()
            : UnidadNegocio::whereIn('id_unidad_negocio', (array) $elemento->unidad_negocio_id)
            ->get(['id_unidad_negocio', 'nombre']);

        $firmas = Firmas::with([
            'empleado:id_empleado,nombres,apellido_paterno',
            'puestoTrabajo:id_puesto_trabajo,nombre'
        ])
            ->where('elemento_id', $elemento->id_elemento)
            ->whereIn('tipo', ['Participante', 'Responsable', 'Reviso', 'Autorizo'])
            ->orderBy('prioridad')
            ->get();

        return view('elementos.show', compact(
            'elemento',
            'puestosRelacionados',
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
            'tipoElemento:id_tipo_elemento,nombre',
            'puestoResponsable:id_puesto_trabajo,nombre',
        ])->findOrFail($id);

        $firmas = Firmas::with([
            'empleado:id_empleado,nombres,apellido_paterno,apellido_materno',
            'puestoTrabajo:id_puesto_trabajo,nombre'
        ])
            ->where('elemento_id', $elemento->id_elemento)
            ->whereIn('estatus', ['Pendiente', 'Aprobado', 'Rechazado'])
            ->orderBy('prioridad', 'asc')
            ->get();

        $siguienteFirmaId = optional($firmas->firstWhere('estatus', 'Pendiente'))->id;

        $daysLeft = null;
        $monthsLeft = null;

        if ($elemento->periodo_revision) {
            $fechaRevision = Carbon::parse($elemento->periodo_revision);
            $daysLeft = (int) round(now()->diffInDays($fechaRevision, false));
            $monthsLeft = (int) round(now()->diffInMonths($fechaRevision, false));
        }

        return view('elementos.info', compact(
            'elemento',
            'firmas',
            'siguienteFirmaId',
            'daysLeft',
            'monthsLeft'
        ))->with('tab', 'historial');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $elemento = Elemento::findOrFail($id);
        $elementoID = $elemento->id_elemento;

        $tiposElemento = TipoElemento::select('id_tipo_elemento', 'nombre')->get();
        $tiposProceso = TipoProceso::select('id_tipo_proceso', 'nombre', 'nivel')->get();
        $unidadesNegocio = UnidadNegocio::select('id_unidad_negocio', 'nombre')->get();
        $empleados = Empleados::with('puestoTrabajo:id_puesto_trabajo,nombre')->get();
        $divisions = Division::select('id_division', 'nombre')->get();
        $areas = Area::select('id_area', 'nombre')->get();

        $puestosTrabajo = PuestoTrabajo::with([
            'division:id_division,nombre',
            'unidadNegocio:id_unidad_negocio,nombre',
        ])->get(['id_puesto_trabajo', 'nombre', 'division_id', 'unidad_negocio_id']);

        $elementos = Elemento::where('id_elemento', '!=', $id)
            ->select('id_elemento', 'nombre_elemento', 'folio_elemento', 'tipo_elemento_id')
            ->get();

        $grupos = [];
        foreach ($puestosTrabajo as $puesto) {
            $division = $puesto->division->nombre ?? 'Sin División';
            $unidad = $puesto->unidadNegocio->nombre ?? 'Sin Unidad de Negocio';

            $grupos[$division][$unidad][] = [
                'id' => $puesto->id_puesto_trabajo,
                'nombre' => $puesto->nombre,
            ];
        }

        $relaciones = Relaciones::where('elementoID', $elementoID)
            ->get(['relacionID', 'nombreRelacion', 'puestos_trabajo']);

        $nombresRelacion = $relaciones->pluck('nombreRelacion')->toArray();
        $puestosIds = $relaciones->pluck('puestos_trabajo')->toArray();
        $relacionIds = $relaciones->pluck('relacionID')->toArray();

        $firmas = Firmas::where('elemento_id', $elementoID)
            ->get(['empleado_id', 'tipo', 'prioridad']);

        $firmasPorTipo = $firmas->groupBy('tipo');

        $participantesIds = $firmasPorTipo->get('Participante', collect())->pluck('empleado_id')->toArray();
        $responsablesIds = $firmasPorTipo->get('Responsable', collect())->pluck('empleado_id')->toArray();
        $autorizoIds = $firmasPorTipo->get('Autorizo', collect())->pluck('empleado_id')->toArray();
        $revisoIds = $firmasPorTipo->get('Reviso', collect())->pluck('empleado_id')->toArray();

        $firmasPorTipo = Firmas::where('elemento_id', $elementoID)
            ->select('tipo', DB::raw('MIN(prioridad) as prio'))
            ->groupBy('tipo')
            ->orderBy('prio')
            ->get();

        $mapDbToFront = [
            'Participante' => 'Participantes',
            'Responsable'  => 'Responsables',
            'Reviso'       => 'Revisó',
            'Autorizo'     => 'Autorizó',
        ];

        $prioridadesFirmas = $firmasPorTipo
            ->map(fn($f) => $mapDbToFront[$f->tipo] ?? null)
            ->filter()
            ->values()
            ->all();

        $prioridadesFirmasJson = json_encode($prioridadesFirmas, JSON_UNESCAPED_UNICODE);

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
            'empleados',
            'prioridadesFirmasJson'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Elemento $elemento): RedirectResponse
    {
        $rules = $this->getElementoValidationRules();
        $request->validate($rules);

        $data = $this->prepareElementoData($request);
        $permitidos = $this->getAllowedFileExtensions();

        $oldGeneral = $elemento->archivo_es_formato;
        $oldFormato = $elemento->archivo_formato;

        $pathsToDeleteAfterCommit = [];
        $newGeneral = null;
        $newFormato = null;

        $newFormato = $this->storeUploadedFile(
            $request,
            'archivo_formato',
            'archivos/formato',
            $permitidos,
            $oldFormato,
            'public',
            false
        );
        if ($newFormato) {
            $data['archivo_formato'] = $newFormato;
            if ($oldFormato) $pathsToDeleteAfterCommit[] = $oldFormato;
        }

        $newGeneral = $this->storeUploadedFile(
            $request,
            'archivo_es_formato',
            'archivos/elementos',
            $permitidos,
            $oldGeneral,
            'public',
            false
        );
        if ($newGeneral) {
            $data['archivo_es_formato'] = $newGeneral;
            if ($oldGeneral) $pathsToDeleteAfterCommit[] = $oldGeneral;
        }

        try {
            DB::transaction(function () use ($data, $newGeneral, $request, $elemento) {
                $elemento->update($data);

                $this->insertRelacionesComites($request, $elemento->id_elemento);

                if ($newGeneral) {
                    $existing = WordDocument::where('elemento_id', $elemento->id_elemento)->first();
                    if ($existing) {
                        $existing->delete();
                    }

                    if ((int) ($data['tipo_elemento_id'] ?? $elemento->tipo_elemento_id) === 2) {
                        $documento = WordDocument::create([
                            'elemento_id' => $elemento->id_elemento,
                            'estado' => 'pendiente',
                            'error_mensaje' => null,
                            'contenido_texto' => null,
                            'contenido_estructurado' => null,
                        ]);

                        ProcesarDocumentoWordJob::dispatch($documento, $newGeneral)
                            ->delay(now()->addSeconds(5))
                            ->afterCommit();
                    }
                }

                $this->crearControlCambio($elemento->id_elemento);
            });

            DB::afterCommit(function () use ($pathsToDeleteAfterCommit) {
                foreach (array_unique($pathsToDeleteAfterCommit) as $oldPath) {
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
            });
        } catch (\Throwable $e) {
            foreach ([$newGeneral, $newFormato] as $p) {
                if ($p && Storage::disk('public')->exists($p)) {
                    Storage::disk('public')->delete($p);
                }
            }
            throw $e;
        }

        return redirect()
            ->route('elementos.index')
            ->with('success', 'Elemento actualizado exitosamente.');
    }

    private function buildFirmasMap(array $participantes, array $responsables, array $reviso, array $autorizo): array
    {
        return [
            'Participante' => $this->normalizeIds($participantes),
            'Responsable' => $this->normalizeIds($responsables),
            'Reviso' => $this->normalizeIds($reviso),
            'Autorizo' => $this->normalizeIds($autorizo),
        ];
    }

    private function extractUniqueIds(array $map): array
    {
        return array_values(array_unique(array_merge(...array_values($map))));
    }

    private function buildFirmasRows(int $elementoId, array $map, $empleados, array $prioridadPorTipo): array
    {
        $rows = [];
        foreach ($map as $tipo => $lista) {
            foreach ($lista as $empleadoId) {
                $empleado = $empleados->get($empleadoId);
                if (!$empleado || !$empleado->puesto_trabajo_id) continue;

                $rows[] = [
                    'elemento_id' => $elementoId,
                    'empleado_id' => (int)$empleado->id_empleado,
                    'puestoTrabajo_id' => (int)$empleado->puesto_trabajo_id,
                    'tipo' => $tipo,
                    'prioridad' => $prioridadPorTipo[$tipo] ?? 99,
                    'estatus' => 'Pendiente',
                    'next_reminder_at' => $this->calculateNextReminder(),
                    'email_sent_at' => null,
                ];
            }
        }
        return $rows;
    }

    private function calculateNextReminder(): Carbon
    {
        return now()->addWeek()->setTime(9, 0, 0)->addSeconds(rand(0, 300));
    }

    private function intArrayOrNull(mixed $value): ?array
    {
        if (is_null($value)) {
            return null;
        }

        $arr = $this->intArray($value);
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
            ->get(['relacionID', 'nombreRelacion', 'puestos_trabajo'])
            ->map(function ($relacion) {
                $puestosIds = is_array($relacion->puestos_trabajo)
                    ? $relacion->puestos_trabajo
                    : json_decode($relacion->puestos_trabajo, true) ?? [];

                if (empty($puestosIds)) {
                    return [
                        'id' => $relacion->relacionID,
                        'nombre' => $relacion->nombreRelacion,
                        'puestos' => [],
                    ];
                }

                $puestosData = PuestoTrabajo::whereIn('id_puesto_trabajo', $puestosIds)
                    ->get(['id_puesto_trabajo', 'nombre'])
                    ->map(fn($p) => ['id' => $p->id_puesto_trabajo, 'nombre' => $p->nombre]);

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
            'empleado:id_empleado,nombres,apellido_paterno',
            'puestoTrabajo:id_puesto_trabajo,nombre',
            'elemento' => function ($query) {
                $query->with([
                    'tipoElemento:id_tipo_elemento,nombre',
                    'tipoProceso:id_tipo_proceso,nombre',
                    'puestoResponsable:id_puesto_trabajo,nombre',
                    'puestoEjecutor:id_puesto_trabajo,nombre',
                    'puestoResguardo:id_puesto_trabajo,nombre',
                    'wordDocument:id,elemento_id,contenido_texto',
                ]);
            }
        ])->where('id', $firmaId)
            ->where('elemento_id', $elementoId)
            ->firstOrFail();

        $elemento = $firma->elemento;

        if (in_array($elemento->status, ['Rechazado', 'Publicado'], true) || $firma->estatus !== 'Pendiente') {
            return response()->view('pages.utility.404', [], 404);
        }

        $archivosAdjuntos = [];
        if ($elemento->archivo_es_formato && Storage::disk('public')->exists($elemento->archivo_es_formato)) {
            $archivosAdjuntos[] = [
                'nombre' => basename($elemento->archivo_es_formato),
                'ruta' => $elemento->archivo_es_formato,
                'tamaño' => Storage::disk('public')->size($elemento->archivo_es_formato),
                'tipo' => pathinfo($elemento->archivo_es_formato, PATHINFO_EXTENSION),
            ];
        }

        return view('elementos.revision', compact(
            'elemento',
            'firma',
            'archivosAdjuntos'
        ))->with('contenidoDocumento', $elemento->wordDocument->contenido_texto ?? null);
    }

    public function updateFirmaStatus(Request $request, string $firmaId): JsonResponse
    {
        $tiposValidos = ['Participante', 'Responsable', 'Reviso', 'Autorizo'];

        return DB::transaction(function () use ($request, $firmaId, $tiposValidos) {
            $data = $request->validate([
                'estatus' => ['required', 'in:Aprobado,Rechazado'],
                'comentario_rechazo' => ['nullable', 'string', 'max:1000', 'required_if:estatus,Rechazado'],
            ]);

            $firma = Firmas::with('elemento:id_elemento,status')
                ->where('id', $firmaId)
                ->lockForUpdate()
                ->firstOrFail();

            $elementoId = (int) $firma->elemento_id;

            Firmas::where('elemento_id', $elementoId)
                ->where('empleado_id', $firma->empleado_id)
                ->whereIn('tipo', $tiposValidos)
                ->where('estatus', 'Pendiente')
                ->update([
                    'estatus' => $data['estatus'],
                    'fecha' => now(),
                    'comentario_rechazo' => $data['estatus'] === 'Rechazado' ? $data['comentario_rechazo'] : null,
                ]);

            $firmas = Firmas::where('elemento_id', $elementoId)
                ->whereIn('tipo', $tiposValidos)
                ->pluck('estatus');

            $newStatus = $firmas->contains('Rechazado') ? 'Rechazado'
                : ($firmas->isNotEmpty() && $firmas->every(fn($e) => $e === 'Aprobado') ? 'Publicado' : 'En Firmas');

            $firma->elemento->update(['status' => $newStatus]);

            $evento = $data['estatus'] === 'Aprobado' ? 'aprobado' : 'rechazado';
            EnviarFirmaRespuestaMail::dispatch((int) $firma->id, $evento);

            DB::afterCommit(function () use ($elementoId) {
                app(FirmaWorkFlowService::class)->dispatchPendingForElemento($elementoId);
            });

            return response()->json([
                'ok' => true,
                'estatus' => $data['estatus'],
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

    private function getMaxFileSizeKB(): int
    {
        return (int) config('word-documents.file_settings.max_file_size_kb', 5120);
    }

    private function getAllowedFileExtensions(): array
    {
        return ['docx', 'doc', 'pdf', 'xls', 'xlsx', 'zip'];
    }

    private function getElementoValidationRules(): array
    {
        $maxFileSizeKB = $this->getMaxFileSizeKB();

        return [
            'tipo_elemento_id' => 'required|exists:tipo_elementos,id_tipo_elemento',
            'nombre_elemento' => 'required|string|max:255',

            'participantes' => 'nullable|array',
            'participantes.*' => 'integer|exists:empleados,id_empleado',

            'responsables' => 'nullable|array',
            'responsables.*' => 'integer|exists:empleados,id_empleado',

            'reviso' => 'nullable|array',
            'reviso.*' => 'integer|exists:empleados,id_empleado',

            'autorizo' => 'nullable|array',
            'autorizo.*' => 'integer|exists:empleados,id_empleado',

            'unidad_negocio_id' => 'nullable|array',
            'unidad_negocio_id.*' => 'integer',

            'puestos_relacionados' => 'nullable|array',
            'puestos_relacionados.*' => 'integer',

            'elemento_relacionado_id' => 'nullable|array',
            'elemento_relacionado_id.*' => 'integer',

            'elemento_padre_id' => 'nullable|integer',
            'prioridades_firmas' => 'nullable|json',

            'archivo_formato' => 'nullable|file|mimes:docx,doc,pdf,xls,xlsx,zip|max:' . $maxFileSizeKB,
        ];
    }

    private function prepareElementoData(Request $request): array
    {
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

        return $data;
    }

    private function extractFirmasData(Request $request, bool $isUpdate = false): array
    {
        $participantes = $this->intArray($request->input('participantes', []));
        $responsables = $this->intArray($request->input('responsables', []));
        $reviso = $this->intArray($request->input('reviso', []));
        $autorizo = $this->intArray($request->input('autorizo', []));

        if ($isUpdate) {
            $ordenPrioridades = $this->parsePrioridadesFirmas($request->input('prioridades_firmas'));
        } else {
            $ordenPrioridades = [
                'Participante' => (int) $request->input('prioridad_participantes', 99),
                'Responsable' => (int) $request->input('prioridad_responsables', 99),
                'Reviso' => (int) $request->input('prioridad_reviso', 99),
                'Autorizo' => (int) $request->input('prioridad_autorizo', 99),
            ];
        }

        return [$participantes, $responsables, $reviso, $autorizo, $ordenPrioridades];
    }
}

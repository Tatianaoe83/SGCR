<?php

namespace App\Http\Controllers;

use App\Models\TipoElemento;
use App\Models\CampoRequeridoTipoElemento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class TipoElementoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tipo-elemento.view')->only(['index', 'show']);
        $this->middleware('permission:tipo-elemento.create')->only(['create', 'store']);
        $this->middleware('permission:tipo-elemento.edit')->only(['edit', 'update']);
        $this->middleware('permission:tipo-elemento.destroy')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $tiposElemento = TipoElemento::withCount('elementos')
            ->with(['camposRequeridos' => function ($query) {
                $query->where('es_requerido', true);
            }])
            ->get();

        // Campos de la tabla elementos que se mostrarán como checkboxes
        $camposElementos = $this->getCamposElementos();

        return view('tipo-elementos.index', compact('tiposElemento', 'camposElementos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // Campos de la tabla elementos que se mostrarán como checkboxes
        $camposElementos = $this->getCamposElementos();

        return view('tipo-elementos.create', compact('camposElementos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_elementos,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'campos_requeridos' => 'nullable|array',
            'campos_requeridos.*' => 'string'
        ]);

        $tipoElemento = TipoElemento::create($request->only(['nombre', 'descripcion']));

        // Guardar campos requeridos si se proporcionaron
        if ($request->has('campos_requeridos') && is_array($request->campos_requeridos)) {
            $this->guardarCamposRequeridosInterno($tipoElemento->id_tipo_elemento, $request->campos_requeridos);
        }

        return redirect()->route('tipo-elementos.index')
            ->with('success', 'Tipo de elemento creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): View
    {
        $tipoElemento = TipoElemento::with('elementos')->findOrFail($id);
        return view('tipo-elementos.show', compact('tipoElemento'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $tipoElemento = TipoElemento::with('camposRequeridos')->findOrFail($id);

        // Todos los campos posibles
        $camposElementos = $this->getCamposElementos();

        $camposSeleccionados = $tipoElemento->camposRequeridos
            ->where('es_requerido', true)
            ->pluck('campo_nombre')
            ->toArray();

        return view('tipo-elementos.edit', compact(
            'tipoElemento',
            'camposElementos',
            'camposSeleccionados'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoElemento $tipoElemento): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_elementos,nombre,' . $tipoElemento->id_tipo_elemento . ',id_tipo_elemento',
            'descripcion' => 'nullable|string|max:1000',
            'campos_requeridos' => 'nullable|array',
            'campos_requeridos.*' => 'string'
        ]);

        $tipoElemento->update($request->only(['nombre', 'descripcion']));

        // Guardar campos requeridos si se proporcionaron
        if ($request->has('campos_requeridos') && is_array($request->campos_requeridos)) {
            $this->guardarCamposRequeridosInterno($tipoElemento->id_tipo_elemento, $request->campos_requeridos);
        }

        return redirect()->route('tipo-elementos.index')
            ->with('success', 'Tipo de elemento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoElemento $tipoElemento): RedirectResponse
    {
        if ($tipoElemento->elementos()->count() > 0) {
            return redirect()->route('tipo-elementos.index')
                ->with('error', 'No se puede eliminar el tipo de elemento porque tiene elementos asociados.');
        }

        $tipoElemento->delete();

        return redirect()->route('tipo-elementos.index')
            ->with('success', 'Tipo de elemento eliminado exitosamente.');
    }

    /**
     * Obtener campos requeridos de un tipo de elemento
     */
    public function getCamposRequeridos(string $id): JsonResponse
    {
        $tipoElemento = TipoElemento::findOrFail($id);
        $campos = $tipoElemento->camposRequeridos()->orderBy('orden')->get();

        return response()->json($campos);
    }

    /**
     * Guardar campos requeridos para un tipo de elemento
     */
    public function guardarCamposRequeridos(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'campos' => 'required|array',
            'campos.*.campo_nombre' => 'required|string',
            'campos.*.campo_label' => 'required|string',
            'campos.*.es_requerido' => 'boolean',
            'campos.*.es_obligatorio' => 'boolean',
            'campos.*.orden' => 'integer'
        ]);

        $tipoElemento = TipoElemento::findOrFail($id);

        // Eliminar campos existentes
        $tipoElemento->camposRequeridos()->delete();

        // Insertar nuevos campos
        $campos = collect($request->campos)->map(function ($campo, $index) use ($id) {
            return [
                'tipo_elemento_id' => $id,
                'campo_nombre' => $campo['campo_nombre'],
                'campo_label' => $campo['campo_label'],
                'es_requerido' => $campo['es_requerido'] ?? false,
                'es_obligatorio' => $campo['es_obligatorio'] ?? false,
                'orden' => $campo['orden'] ?? $index,
                'created_at' => now(),
                'updated_at' => now()
            ];
        })->toArray();

        CampoRequeridoTipoElemento::insert($campos);

        return response()->json([
            'message' => 'Campos requeridos guardados exitosamente',
            'campos' => $tipoElemento->fresh()->camposRequeridos()->orderBy('orden')->get()
        ]);
    }

    /**
     * Método privado para obtener los campos de elementos disponibles
     */
    private function getCamposElementos(): array
    {
        return [
            'nombre_elemento' => 'Nombre del Elemento',
            'tipo_proceso_id' => 'Tipo de Proceso',
            'unidad_negocio_id' => 'Unidad de Negocio',
            'ubicacion_eje_x' => 'Ubicación Eje X',
            'control' => 'Control',
            'folio_elemento' => 'Folio del Elemento',
            'version_elemento' => 'Versión del Elemento',
            'fecha_elemento' => 'Fecha del Elemento',
            'periodo_revision' => 'Período de Revisión',
            'puesto_responsable_id' => 'Puesto Responsable',
            'puestos_relacionados' => 'Puestos Relacionados',
            'es_formato' => '¿Es Formato?',
            'archivo_es_formato' => 'Archivo Elemento',
            //'archivo_formato' => 'Archivo de Formato',
            'puesto_ejecutor_id' => 'Puesto Ejecutor',
            'puesto_resguardo_id' => 'Puesto de Resguardo',
            'medio_soporte' => 'Medio de Soporte',
            'ubicacion_resguardo' => 'Ubicación de Resguardo',
            'periodo_resguardo' => 'Período de Resguardo',
            'elemento_padre_id' => 'Elemento Padre',
            'elemento_relacionado_id' => 'Elemento Relacionado',
            'correo_implementacion' => 'Correo de Implementación',
            'correo_agradecimiento' => 'Correo de Agradecimiento'
        ];
    }

    /**
     * Método privado para guardar campos requeridos internamente
     */
    private function guardarCamposRequeridosInterno(int $tipoElementoId, array $camposRequeridos): void
    {
        // Definir todos los campos disponibles con sus etiquetas
        $camposDisponibles = $this->getCamposElementos();

        // Eliminar campos existentes
        CampoRequeridoTipoElemento::where('tipo_elemento_id', $tipoElementoId)->delete();

        // Preparar campos para inserción
        $camposParaInsertar = [];
        $orden = 0;

        foreach ($camposDisponibles as $campoNombre => $campoLabel) {
            $esRequerido = in_array($campoNombre, $camposRequeridos);

            $camposParaInsertar[] = [
                'tipo_elemento_id' => $tipoElementoId,
                'campo_nombre' => $campoNombre,
                'campo_label' => $campoLabel,
                'es_requerido' => $esRequerido,
                'es_obligatorio' => $esRequerido, // Los campos requeridos son obligatorios
                'orden' => $orden++,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insertar todos los campos
        if (!empty($camposParaInsertar)) {
            CampoRequeridoTipoElemento::insert($camposParaInsertar);
        }
    }
}

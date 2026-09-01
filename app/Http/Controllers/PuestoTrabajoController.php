<?php

namespace App\Http\Controllers;

use App\Http\Requests\PuestoTrabajoRequest;
use App\Models\PuestoTrabajo;
use App\Models\Division;
use App\Models\UnidadNegocio;
use App\Models\Area;
use App\Exports\PuestosTrabajoExport;
use App\Exports\PuestosTrabajoTemplateExport;
use App\Imports\PuestosTrabajoImport;
use App\Models\Empleados;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PuestoTrabajoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:puestos-trabajo.view')->only([
            'index',
            'show',
            'getUnidadesNegocio',
            'getAreas',
            'getPuestos',
        ]);

        $this->middleware('permission:puestos-trabajo.create')->only([
            'create',
            'store',
        ]);

        $this->middleware('permission:puestos-trabajo.edit')->only([
            'edit',
            'update',
        ]);

        $this->middleware('permission:puestos-trabajo.delete')->only([
            'destroy',
        ]);

        $this->middleware('permission:puestos-trabajo.export')->only([
            'export',
            'downloadTemplate',
        ]);

        $this->middleware('permission:puestos-trabajo.import')->only([
            'importForm',
            'import',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('puestos-trabajo.index');
    }

    public function data()
    {
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio'])
            ->select([
                'id_puesto_trabajo',
                'nombre',
                'division_id',
                'unidad_negocio_id',
                'unidades_negocio_ids',
                'areas_ids',
                'created_at',
                'is_global'
            ]);

        return datatables()->of($puestosTrabajo)
            ->editColumn(
                'created_at',
                fn($puesto) =>
                Carbon::parse($puesto->created_at)->format('d/m/Y g:i a')
            )
            ->addColumn(
                'division',
                fn($puesto) =>
                $puesto->is_global ? 'Todas' : ($puesto->division?->nombre ?? 'N/A')
            )
            ->addColumn('unidadNegocio', function ($puesto) {
                if ($puesto->is_global) {
                    return '<span class="inline-block px-2 py-1 text-xs font-semibold bg-[#E8EEF5] text-[#021D49] rounded">
                            Todas
                        </span>';
                }

                $unidades = $puesto->unidadesNegocio;

                if ($unidades->isEmpty()) {
                    return '<span class="text-slate-400 italic">Sin unidad de negocio</span>';
                }

                return $unidades->map(fn($unidad) => '<span class="inline-block px-2 py-1 mr-1 mb-1 text-xs font-semibold bg-[#E8EEF5] text-[#021D49] rounded">'
                    . e($unidad->nombre) .
                    '</span>')->implode('');
            })
            ->addColumn('areas', function ($puesto) {
                if ($puesto->is_global) {
                    return '<span class="inline-block px-2 py-1 text-xs font-semibold bg-[#E8EEF5] text-[#021D49] rounded">
                            Todas
                        </span>';
                }
                if (!$puesto->areas || $puesto->areas->isEmpty()) {
                    return '<span class="text-slate-400 italic">Sin área</span>';
                }
                return $puesto->areas->map(function ($area) {
                    return '<span class="inline-block px-2 py-1 mr-1 mb-1 text-xs font-semibold bg-[#E8EEF5] text-[#021D49] rounded">'
                        . e($area->nombre) .
                        '</span>';
                })->implode('');
            })
            ->addColumn(
                'acciones',
                fn($puesto) =>
                view('puestos-trabajo.partials-actions', compact('puesto'))->render()
            )
            ->rawColumns(['unidadNegocio', 'areas', 'acciones'])
            ->make(true);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $divisions = Division::all();
        $puestos = PuestoTrabajo::orderBy('nombre', 'asc')->get(['id_puesto_trabajo', 'nombre']);
        $nivelesMultiunidad = PuestoTrabajo::NIVELES_MULTIUNIDAD;

        return view('puestos-trabajo.create', compact('divisions', 'puestos', 'nivelesMultiunidad'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PuestoTrabajoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        PuestoTrabajo::create([
            'nombre' => $datos['nombre'],
            'division_id' => $datos['division_id'],
            'unidades_negocio_ids' => $datos['unidades_negocio_ids'],
            'areas_ids' => $datos['areas_ids'],
            'puesto_trabajo_id' => $datos['puesto_trabajo_id'] ?? null,
        ]);

        return redirect()->route('puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $puestoTrabajo = PuestoTrabajo::findOrFail($id);
        $puestoTrabajo->load(['division', 'unidadNegocio', 'puestosTrabajos']);
        return view('puestos-trabajo.show', compact('puestoTrabajo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $puestoTrabajo = PuestoTrabajo::findOrFail($id);
        $divisions = Division::all();

        // El formulario abre con su unidad y sus áreas ya seleccionadas: se renderizan
        // aquí en vez de pedirlas por fetch, que es donde se perdía la preselección.
        $unidadesSeleccionadas = $puestoTrabajo->unidades_negocio_ids ?: [];

        $unidadesNegocio = UnidadNegocio::where('division_id', $puestoTrabajo->division_id)
            ->orderBy('nombre')
            ->get();

        $areas = Area::with('unidadNegocio')
            ->whereIn('unidad_negocio_id', $unidadesSeleccionadas)
            ->orderBy('nombre')
            ->get();

        $puestos = PuestoTrabajo::where('id_puesto_trabajo', '!=', $puestoTrabajo->id_puesto_trabajo)
            ->orderBy('nombre')
            ->get(['id_puesto_trabajo', 'nombre']);
        $nivelesMultiunidad = PuestoTrabajo::NIVELES_MULTIUNIDAD;

        return view('puestos-trabajo.edit', compact(
            'puestoTrabajo',
            'divisions',
            'unidadesNegocio',
            'unidadesSeleccionadas',
            'areas',
            'puestos',
            'nivelesMultiunidad'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PuestoTrabajoRequest $request, $id): RedirectResponse
    {
        $puestoTrabajo = PuestoTrabajo::findOrFail($id);
        $datos = $request->validated();

        $cambios = [
            'nombre' => $datos['nombre'],
            'puesto_trabajo_id' => $datos['puesto_trabajo_id'] ?? null,
        ];

        // Un puesto global aplica a toda la organización: su estructura no se toca.
        if (!$puestoTrabajo->isGlobal()) {
            $cambios += [
                'division_id' => $datos['division_id'],
                'unidades_negocio_ids' => $datos['unidades_negocio_ids'],
                'areas_ids' => $datos['areas_ids'],
            ];
        }

        $puestoTrabajo->update($cambios);

        return redirect()->route('puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource in storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $puestoTrabajo = PuestoTrabajo::findOrFail($id);
        $puestoTrabajo->delete();

        return redirect()->route('puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo eliminado exitosamente.');
    }

    /**
     * Exportar puestos de trabajo a Excel
     */
    public function export()
    {
        return Excel::download(new PuestosTrabajoExport, 'puestos-trabajo-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Descargar plantilla de Excel
     */
    public function downloadTemplate()
    {
        return Excel::download(new PuestosTrabajoTemplateExport, 'plantilla-puestos-trabajo.xlsx');
    }

    /**
     * Mostrar formulario de importación
     */
    public function importForm(): View
    {
        return view('puestos-trabajo.import');
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
            Excel::import(new PuestosTrabajoImport, $request->file('file'));

            return redirect()->route('puestos-trabajo.index')
                ->with('success', 'Datos importados exitosamente.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return redirect()->back()
                ->with('error', 'Error de validación: ' . $e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al importar los datos: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Obtener unidades de negocio por división
     */
    public function getUnidadesNegocio($division_id)
    {
        $unidadesNegocio = UnidadNegocio::where('division_id', $division_id)->get();
        return response()->json($unidadesNegocio);
    }

    /**
     * Obtener áreas de una o varias unidades de negocio.
     * Acepta un id o una lista separada por comas: /areas/3,7,12
     */
    public function getAreas($unidad_negocio_id)
    {
        $ids = collect(explode(',', (string) $unidad_negocio_id))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([]);
        }

        $areas = Area::with('unidadNegocio')
            ->whereIn('unidad_negocio_id', $ids)
            ->orderBy('nombre')
            ->get();

        return response()->json($areas);
    }
}

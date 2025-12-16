<?php

namespace App\Http\Controllers;

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
            ->addColumn(
                'unidadNegocio',
                fn($puesto) =>
                $puesto->is_global ? 'Todas' : ($puesto->unidadNegocio?->nombre ?? 'N/A')
            )
            ->addColumn('areas', function ($puesto) {
                if ($puesto->is_global) {
                    return '<span class="inline-block px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-700 rounded">
                            Todas
                        </span>';
                }
                if (!$puesto->areas || $puesto->areas->isEmpty()) {
                    return '<span class="text-slate-400 italic">Sin área</span>';
                }
                return $puesto->areas->map(function ($area) {
                    return '<span class="inline-block px-2 py-1 mr-1 mb-1 text-xs font-semibold bg-purple-100 text-purple-700 rounded">'
                        . e($area->nombre) .
                        '</span>';
                })->implode('');
            })
            ->addColumn(
                'acciones',
                fn($puesto) =>
                view('puestos-trabajo.partials-actions', compact('puesto'))->render()
            )
            ->rawColumns(['areas', 'acciones'])
            ->make(true);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $divisions = Division::all();
        $puestos = PuestoTrabajo::orderBy('nombre', 'asc')->get(['id_puesto_trabajo', 'nombre']);
        return view('puestos-trabajo.create', compact('divisions', 'puestos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id_division',
            'unidad_negocio_id' => 'required|exists:unidad_negocios,id_unidad_negocio',
            'areas_ids' => 'required|array|min:1',
            'areas_ids.*' => 'exists:area,id_area',
        ]);

        PuestoTrabajo::create([
            'nombre' => $request->nombre,
            'division_id' => $request->division_id,
            'unidad_negocio_id' => $request->unidad_negocio_id,
            'areas_ids' => $request->areas_ids,
            'puesto_trabajo_id' => $request->puesto_trabajo_id,
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
        $puestoTrabajo->load(['division', 'unidadNegocio']);
        return view('puestos-trabajo.show', compact('puestoTrabajo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $puestoTrabajo = PuestoTrabajo::findOrFail($id);
        $divisions = Division::all();
        $unidadesNegocio = UnidadNegocio::all();
        $areas = Area::all();
        $puestos = PuestoTrabajo::all();
        return view('puestos-trabajo.edit', compact('puestoTrabajo', 'divisions', 'unidadesNegocio', 'areas', 'puestos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $puestoTrabajo = PuestoTrabajo::findOrFail($id);

        $puestoTrabajo->update([
            'nombre' => $request->nombre,
            'division_id' => $request->division_id,
            'unidad_negocio_id' => $request->unidad_negocio_id,
            'areas_ids' => $request->areas_ids,
            'puesto_trabajo_id' => $request->puesto_trabajo_id,
        ]);

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
     * Obtener áreas por unidad de negocio
     */
    public function getAreas($unidad_negocio_id)
    {
        $areas = Area::where('unidad_negocio_id', $unidad_negocio_id)->get();
        return response()->json($areas);
    }
}

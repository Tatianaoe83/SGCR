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
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio', 'area'])->paginate(10);
        return view('puestos-trabajo.index', compact('puestosTrabajo',));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $divisions = Division::all();
        return view('puestos-trabajo.create', compact('divisions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        /* $request->validate([
            'nombre' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id_division',
            'unidad_negocio_id' => 'required|exists:unidad_negocios,id_unidad_negocio',
            'area_id' => 'required|exists:area,id_area',
            'puesto_trabajo_id' => 'required|exists:puesto_trabajos,id_puesto_trabajo'
        ]); */

        PuestoTrabajo::create([
            'nombre' => $request->nombre,
            'division_id' => $request->division_id,
            'unidad_negocio_id' => $request->unidad_negocio_id,
            'area_id' => $request->area_id,
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
        $puestoTrabajo->load(['division', 'unidadNegocio', 'area', 'jefes']);
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
            'area_id' => $request->area_id,
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

    public function getPuestos($area_id)
    {
        $puestos = PuestoTrabajo::where('area_id', $area_id)
            ->orderBy('nombre', 'asc')
            ->get(['id_puesto_trabajo', 'nombre']);

        return response()->json($puestos);
    }
}

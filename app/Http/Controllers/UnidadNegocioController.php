<?php

namespace App\Http\Controllers;

use App\Models\UnidadNegocio;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UnidadNegocioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:unidades-negocios.view')->only(['index', 'show']);
        $this->middleware('permission:unidades-negocios.create')->only(['create', 'store']);
        $this->middleware('permission:unidades-negocios.edit')->only(['edit', 'update']);
        $this->middleware('permission:unidades-negocios.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('unidades-negocios.index');
    }

    public function data()
    {
        $unidades = UnidadNegocio::with('division')
            ->select([
                'id_unidad_negocio',
                'division_id',
                'nombre',
                'created_at'
            ]);

        return datatables()->of($unidades)
            ->editColumn('created_at', function ($u) {
                return Carbon::parse($u->created_at)
                    ->format('d/m/Y g:i a');
            })
            ->addColumn('division', fn($u) => $u->division?->nombre ?? 'N/A')
            ->addColumn('acciones', function ($u) {
                return view('unidades-negocios.partials-actions', compact('u'))->render();
            })
            ->rawColumns(['acciones'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $divisions = Division::all();
        return view('unidades-negocios.create', compact('divisions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id_division',
            'nombre' => 'required|string|max:255|unique:unidad_negocios'
        ]);

        UnidadNegocio::create($request->all());

        return redirect()->route('unidades-negocios.index')
            ->with('success', 'Unidad de negocio creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $unidadNegocio = UnidadNegocio::with('division')->findOrFail($id);
        return view('unidades-negocios.show', compact('unidadNegocio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $unidadNegocio = UnidadNegocio::findOrFail($id);
        $divisions = Division::all();
        return view('unidades-negocios.edit', compact('unidadNegocio', 'divisions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id_division',
            'nombre' => 'required|string|max:255|unique:unidad_negocios,nombre'
        ]);

        $unidadNegocio = UnidadNegocio::findOrFail($id);
        $unidadNegocio->update($request->all());

        return redirect()->route('unidades-negocios.index')
            ->with('success', 'Unidad de negocio actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $unidadNegocio = UnidadNegocio::findOrFail($id);
        $unidadNegocio->delete();

        return redirect()->route('unidades-negocios.index')
            ->with('success', 'Unidad de negocio eliminada exitosamente.');
    }
}

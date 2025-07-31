<?php

namespace App\Http\Controllers;

use App\Models\UnidadNegocio;
use App\Models\Division;
use Illuminate\Http\Request;

class UnidadNegocioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unidadesNegocio = UnidadNegocio::with('division')->get();
        return view('unidades-negocios.index', compact('unidadesNegocio'));
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
            'division_id' => 'required|exists:divisions,id',
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
            'division_id' => 'required|exists:divisions,id',
            'nombre' => 'required|string|max:255|unique:unidad_negocios,nombre,' . $id
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
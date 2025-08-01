<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\UnidadNegocio;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $areas = Area::with('unidadNegocio')->get();
        return view('area.index', compact('areas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $unidadesNegocio = UnidadNegocio::all();
        return view('area.create', compact('unidadesNegocio'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:area'
        ]);

        Area::create($request->all());

        return redirect()->route('area.index')
            ->with('success', 'Area creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $area = Area::findOrFail($id);
        return view('area.show', compact('area'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $area = Area::findOrFail($id);
        $unidadNegocio = UnidadNegocio::all();
        return view('area.edit', compact('area', 'unidadNegocio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'unidad_negocio_id' => 'required|exists:unidad_negocios,id_unidad_negocio',
            'nombre' => 'required|string|max:255|unique:area,nombre'
        ]);

        $area = Area::findOrFail($id);
        $area->update($request->all());

        return redirect()->route('area.index')
            ->with('success', 'Area actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return redirect()->route('area.index')
            ->with('success', 'Area eliminada exitosamente.');
    }
}

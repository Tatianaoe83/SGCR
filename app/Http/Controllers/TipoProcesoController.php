<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoProceso;

class TipoProcesoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tipoProcesos = TipoProceso::all();
        return view('TipoProceso.index', compact('tipoProcesos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('TipoProceso.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'nivel' => 'required|numeric|min:0|max:99.9',
        ]);

        $tipoProceso = TipoProceso::create($request->all());
        return redirect()->route('tipoProceso.index')->with('success', 'Tipo de proceso creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoProceso = TipoProceso::findOrFail($id);
        return view('TipoProceso.show', compact('tipoProceso'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $tipoProceso = TipoProceso::findOrFail($id);
        return view('TipoProceso.edit', compact('tipoProceso'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'nivel' => 'required|numeric|min:0|max:99.9',
        ]);

        $tipoProceso = TipoProceso::findOrFail($id);
        $tipoProceso->update($request->all());
        return redirect()->route('tipoProceso.index')->with('success', 'Tipo de proceso actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tipoProceso = TipoProceso::findOrFail($id);
        $tipoProceso->delete();
        return redirect()->route('tipoProceso.index')->with('success', 'Tipo de proceso eliminado exitosamente');
    }
}

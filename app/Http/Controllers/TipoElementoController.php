<?php

namespace App\Http\Controllers;

use App\Models\TipoElemento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoElementoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $tiposElemento = TipoElemento::withCount('elementos')->paginate(10);
        return view('tipo-elementos.index', compact('tiposElemento'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('tipo-elementos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_elementos,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        TipoElemento::create($request->all());

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
        $tipoElemento = TipoElemento::findOrFail($id);
        return view('tipo-elementos.edit', compact('tipoElemento'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoElemento $tipoElemento): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_elementos,nombre,' . $tipoElemento->id_tipo_elemento . ',id_tipo_elemento',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $tipoElemento->update($request->all());

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
}

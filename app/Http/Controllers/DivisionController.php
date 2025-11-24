<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:divisions.view')->only(['index', 'show']);
        $this->middleware('permission:divisions.create')->only(['create', 'store']);
        $this->middleware('permission:divisions.edit')->only(['edit', 'update']);
        $this->middleware('permission:divisions.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $divisions = Division::all();
        return view('divisions.index', compact('divisions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('divisions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:divisions'
        ]);

        Division::create($request->all());

        return redirect()->route('divisions.index')
            ->with('success', 'División creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $division = Division::findOrFail($id);
        return view('divisions.show', compact('division'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $division = Division::findOrFail($id);
        return view('divisions.edit', compact('division'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:divisions,nombre'
        ]);

        $division = Division::findOrFail($id);
        $division->update($request->all());

        return redirect()->route('divisions.index')
            ->with('success', 'División actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $division = Division::findOrFail($id);
        $division->delete();

        return redirect()->route('divisions.index')
            ->with('success', 'División eliminada exitosamente.');
    }
}

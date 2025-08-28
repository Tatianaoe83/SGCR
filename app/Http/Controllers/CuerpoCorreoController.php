<?php

namespace App\Http\Controllers;

use App\Models\CuerpoCorreo;
use App\Services\PlantillaCorreoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CuerpoCorreoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $cuerpos = CuerpoCorreo::orderBy('tipo')->orderBy('nombre')->paginate(10);
        return view('cuerpos-correo.index', compact('cuerpos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $tipos = CuerpoCorreo::getTipos();
        $variables = PlantillaCorreoService::getAllVariables();
        return view('cuerpos-correo.create', compact('tipos', 'variables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'cuerpo_html' => 'required|string',
            'cuerpo_texto' => 'required|string',
            'tipo' => 'required|in:acceso,implementacion,agradecimiento',
            'activo' => 'boolean'
        ]);

        // Validar que todas las variables requeridas estén presentes
        $variablesFaltantes = PlantillaCorreoService::validarVariables($request->cuerpo_html, $request->tipo);
        
        if (!empty($variablesFaltantes)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['cuerpo_html' => 'Faltan las siguientes variables requeridas: ' . implode(', ', $variablesFaltantes)]);
        }

        CuerpoCorreo::create($request->all());

        return redirect()->route('cuerpos-correo.index')
            ->with('success', 'Cuerpo de correo creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): View
    {
        $cuerpo = CuerpoCorreo::findOrFail($id);
        $variables = PlantillaCorreoService::getVariablesPorTipo($cuerpo->tipo);
        $vistaPrevia = PlantillaCorreoService::generarVistaPrevia($cuerpo->cuerpo_html, $cuerpo->tipo);
        
        return view('cuerpos-correo.show', compact('cuerpo', 'variables', 'vistaPrevia'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $cuerpo = CuerpoCorreo::findOrFail($id);
        $tipos = CuerpoCorreo::getTipos();
        $variables = PlantillaCorreoService::getAllVariables();
        return view('cuerpos-correo.edit', compact('cuerpo', 'tipos', 'variables'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'cuerpo_html' => 'required|string',
            'cuerpo_texto' => 'required|string',
            'tipo' => 'required|in:acceso,implementacion,agradecimiento',
            'activo' => 'boolean'
        ]);

        // Validar que todas las variables requeridas estén presentes
        $variablesFaltantes = PlantillaCorreoService::validarVariables($request->cuerpo_html, $request->tipo);
        
        if (!empty($variablesFaltantes)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['cuerpo_html' => 'Faltan las siguientes variables requeridas: ' . implode(', ', $variablesFaltantes)]);
        }

        $cuerpo = CuerpoCorreo::findOrFail($id);
        $cuerpo->update($request->all());

        return redirect()->route('cuerpos-correo.index')
            ->with('success', 'Cuerpo de correo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $cuerpo = CuerpoCorreo::findOrFail($id);
        $cuerpo->delete();

        return redirect()->route('cuerpos-correo.index')
            ->with('success', 'Cuerpo de correo eliminado exitosamente.');
    }

    /**
     * Obtener cuerpos por tipo
     */
    public function getPorTipo($tipo)
    {
        $cuerpos = CuerpoCorreo::porTipo($tipo)->activos()->get();
        return response()->json($cuerpos);
    }

    /**
     * Generar vista previa del correo
     */
    public function vistaPrevia(Request $request)
    {
        $request->validate([
            'cuerpo_html' => 'required|string',
            'tipo' => 'required|in:acceso,implementacion,agradecimiento'
        ]);

        $vistaPrevia = PlantillaCorreoService::generarVistaPrevia($request->cuerpo_html, $request->tipo);
        
        return response()->json([
            'html' => $vistaPrevia
        ]);
    }

    /**
     * Obtener plantilla de ejemplo
     */
    public function getPlantillaEjemplo(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:acceso,implementacion,agradecimiento'
        ]);

        $html = PlantillaCorreoService::getPlantillaEjemplo($request->tipo);
        $texto = PlantillaCorreoService::getPlantillaTextoEjemplo($request->tipo);
        
        return response()->json([
            'html' => $html,
            'texto' => $texto
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Division;
use App\Models\Elemento;
use App\Models\PuestoTrabajo;
use App\Models\UnidadNegocio;
use Illuminate\Http\Request;

class MatrizController extends Controller
{
    public function index()
    {

        $unidades = UnidadNegocio::all();
        $divisiones = Division::all();
        $areas = Area::all();
        $puestosTrabajo = PuestoTrabajo::with(['division', 'unidadNegocio', 'area'])->get();

        return view('matriz.index', compact('unidades', 'divisiones', 'areas', 'puestosTrabajo'));
    }

    public function buscarElementos(Request $request)
    {
        $puestosRelacionados = $request->input('puestos_relacionados', []);

        if (empty($puestosRelacionados)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Debes seleccionar al menos un puesto.'
            ]);
        }

        $elementos = Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'unidadNegocio',
            'puestoResponsable',
            'puestoEjecutor',
            'puestoResguardo',
            'elementoPadre',
            'elementoRelacionado'
        ])
            ->whereHas('tipoElemento', function ($query) {
                $query->where('nombre', 'Procedimiento');
            })
            ->where(function ($q) use ($puestosRelacionados) {
                foreach ($puestosRelacionados as $puestoId) {
                    $q->orWhereJsonContains('puestos_relacionados', (string) $puestoId);
                }
            })
            ->get();

        return response()->json([
            'status' => 'ok',
            'data'   => $elementos
        ]);
    }
}

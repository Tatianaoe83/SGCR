<?php

namespace App\Http\Controllers;

use App\Models\ControlCambio;
use App\Models\Elemento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropuestaMejoraController extends Controller
{
    public function getElementos(Request $request)
    {
        $elementos = Elemento::where('tipo_elemento_id', $request->tipo_id)
            ->select('id_elemento as id', 'nombre_elemento as nombre')
            ->orderBy('nombre_elemento')
            ->get();

        return response()->json($elementos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'        => 'required|string|max:255',
            'elemento_id'   => 'required|integer|exists:elementos,id_elemento',
            'descripcion'   => 'required|string',
            'justificacion' => 'required|string',
        ]);

        $año = (int) now()->format('y');
        $baseAño = $año * 1000;

        $ultimoFolio = ControlCambio::where('FolioCambio', 'like', 'GC' . $año . '%')
            ->select(DB::raw('MAX(CAST(SUBSTRING(FolioCambio, 3) AS UNSIGNED)) as max_folio'))
            ->value('max_folio');

        $consecutivo = $ultimoFolio ? ($ultimoFolio - $baseAño) + 1 : 1;
        $folioNumerico = $baseAño + $consecutivo;

        ControlCambio::create([
            'id_elemento'     => $request->elemento_id,
            'FolioCambio'     => 'GC' . $folioNumerico,
            'Descripcion'     => $request->titulo,
            'RedaccionCambio' => $request->descripcion,
            'Justificacion'   => $request->justificacion,
        ]);

        return response()->json(['success' => true, 'message' => 'Propuesta enviada correctamente.']);
    }
}

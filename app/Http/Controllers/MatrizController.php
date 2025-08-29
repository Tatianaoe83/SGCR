<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Division;
use App\Models\Elemento;
use App\Models\PuestoTrabajo;
use App\Models\UnidadNegocio;
use App\Exports\MatrizExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    public function matrizGeneral()
    {
        $puestos = PuestoTrabajo::pluck('nombre', 'id_puesto_trabajo');

        $elementos = Elemento::with([
            'tipoElemento',
            'tipoProceso',
        ])->whereHas('tipoElemento', function ($query) {
            $query->where('nombre', 'Procedimiento');
        })->get();

        $data = $elementos->map(function ($el) use ($puestos) {
            $fila = [
                'Proceso'       => $el->tipoProceso->nombre ?? 'N/A',
                'Folio'         => $el->folio_elemento ?? 'N/A',
                'Procedimiento' => $el->nombre_elemento ?? 'N/A',
            ];

            dd($puestos);

            foreach ($puestos as $id => $nombre) {
                $fila[$nombre] = '';
            }

            $asignar = function (&$campo, $valor) {
                $campo = $campo === '' ? $valor : $campo . '-' . $valor;
            };

            if ($el->puesto_responsable_id && isset($puestos[$el->puesto_responsable_id])) {
                $asignar($fila[$puestos[$el->puesto_responsable_id]], 'R');
            }

            if ($el->puesto_ejecutor_id && isset($puestos[$el->puesto_ejecutor_id])) {
                $asignar($fila[$puestos[$el->puesto_ejecutor_id]], 'E');
            }

            if ($el->puesto_resguardo_id && isset($puestos[$el->puesto_resguardo_id])) {
                $asignar($fila[$puestos[$el->puesto_resguardo_id]], 'A');
            }

            $relacionados = is_array($el->puestos_relacionados)
                ? $el->puestos_relacionados
                : json_decode($el->puestos_relacionados, true);

            if ($relacionados) {
                foreach ($relacionados as $id) {
                    if (isset($puestos[$id])) {
                        $asignar($fila[$puestos[$id]], 'PR');
                    }
                }
            }

            $puestosAdicionales = [];

            $adicionales = is_array($el->nombres_relacion)
                ? $el->nombres_relacion
                : json_decode($el->nombres_relacion, true);

            if ($adicionales) {
                foreach ($adicionales as $adds) {
                    $asignar($fila[$puestos[$adds]], 'PM');
                }
            }

            return $fila;
        });

        return response()->json([
            'status' => 'ok',
            'puestos' => array_values($puestos->toArray()),
            'data'   => $data
        ]);
    }


    /**
     * Exportar matriz a Excel
     */
    public function export(Request $request)
    {
        $puestosRelacionados = $request->input('puestos_relacionados', []);

        if (empty($puestosRelacionados)) {
            return redirect()->back()->with('error', 'Debes seleccionar al menos un puesto para exportar.');
        }

        $filename = 'matriz-elementos-' . date('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new MatrizExport($puestosRelacionados), $filename);
    }
}

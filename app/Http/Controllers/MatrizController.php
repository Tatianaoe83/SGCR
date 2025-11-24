<?php

namespace App\Http\Controllers;

use App\Exports\MatrizExport;
use App\Exports\MatrizFiltroExport;
use App\Models\Area;
use App\Models\Division;
use App\Models\Elemento;
use App\Models\PuestoTrabajo;
use App\Models\Relaciones;
use App\Models\TipoElemento;
use App\Models\UnidadNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
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
            'elementoRelacionado',
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
        $elementos = Elemento::with([
            'tipoElemento',
            'tipoProceso',
            'relaciones'
        ])->whereHas('tipoElemento', function ($q) {
            $q->where('nombre', 'Procedimiento');
        })->get();

        if ($elementos->isEmpty()) {
            return response()->json([
                'status' => 'ok',
                'puestos' => [],
                'data' => [],
            ]);
        }

        $idsPuestos = [];
        $puestosAdicionales = [];

        foreach ($elementos as $el) {
            foreach (
                [
                    $el->puesto_responsable_id,
                    $el->puesto_ejecutor_id,
                    $el->puesto_resguardo_id
                ] as $pid
            ) {
                if (!empty($pid)) $idsPuestos[] = $pid;
            }

            $relacionados = is_array($el->puestos_relacionados)
                ? $el->puestos_relacionados
                : json_decode($el->puestos_relacionados, true);

            if (!empty($relacionados)) {
                $idsPuestos = array_merge($idsPuestos, $relacionados);
            }

            if ($el->relaciones->isNotEmpty()) {
                foreach ($el->relaciones as $rel) {
                    if (!empty($rel->nombreRelacion)) {
                        $puestosAdicionales[] = trim($rel->nombreRelacion);
                    }
                }
            }
        }

        $puestos = [];
        if (!empty($idsPuestos)) {
            $puestos = PuestoTrabajo::whereIn('id_puesto_trabajo', array_unique($idsPuestos))
                ->pluck('nombre', 'id_puesto_trabajo')
                ->toArray();
        }

        $puestosFinales = array_values(array_unique(
            array_merge(array_values($puestos), $puestosAdicionales)
        ));

        $data = $elementos->map(function ($el) use ($puestos, $puestosFinales) {
            $fila = [
                'Proceso'       => $el->tipoProceso->nombre ?? 'N/A',
                'Folio'         => $el->folio_elemento ?? 'N/A',
                'Procedimiento' => $el->nombre_elemento ?? 'N/A',
            ];

            foreach ($puestosFinales as $nombre) {
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

            if ($el->relaciones->isNotEmpty()) {
                foreach ($el->relaciones as $rel) {
                    $nombreRelacion = $rel->nombreRelacion ?? null;
                    if (!empty($nombreRelacion)) {
                        $asignar($fila[$nombreRelacion], 'PM');
                    }
                }
            }

            return $fila;
        });

        $columnasConDatos = [];
        foreach ($data as $fila) {
            foreach ($fila as $columna => $valor) {
                if (!in_array($columna, ['Proceso', 'Folio', 'Procedimiento']) && !empty($valor)) {
                    $columnasConDatos[$columna] = true;
                }
            }
        }

        $data = $data->map(function ($fila) use ($columnasConDatos) {
            return collect($fila)
                ->filter(function ($valor, $columna) use ($columnasConDatos) {
                    return in_array($columna, ['Proceso', 'Folio', 'Procedimiento']) || isset($columnasConDatos[$columna]);
                })
                ->toArray();
        });

        return response()->json([
            'status' => 'ok',
            'puestosAdicionales' => array_values(array_unique($puestosAdicionales)),
            'puestos' => $puestosFinales,
            'data'   => $data->values(),
        ]);
    }

    public function matrizFiltro(Request $request)
    {
        $puestosIds = $request->input('puestos_relacionados', []);

        if (empty($puestosIds) || !is_array($puestosIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Debes seleccionar al menos un puesto.'
            ], 422);
        }

        $tipoElemento = TipoElemento::where('nombre', 'Procedimiento')->first();
        if (!$tipoElemento) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se encontró el tipo de elemento "Procedimiento".'
            ]);
        }

        $elementos = Elemento::with('tipoProceso')
            ->where('tipo_elemento_id', $tipoElemento->id_tipo_elemento)
            ->where(function ($q) use ($puestosIds) {
                foreach ($puestosIds as $id) {
                    $q->orWhere('puesto_responsable_id', $id)
                        ->orWhere('puesto_ejecutor_id', $id)
                        ->orWhere('puesto_resguardo_id', $id)
                        ->orWhereRaw('JSON_CONTAINS(COALESCE(puestos_relacionados, "[]"), ?)', [json_encode($id)]);
                }
            })
            ->get();

        $relaciones = Relaciones::with('elemento.tipoProceso')
            ->whereHas('elemento', function ($q) use ($tipoElemento) {
                $q->where('tipo_elemento_id', $tipoElemento->id_tipo_elemento);
            })
            ->where(function ($q) use ($puestosIds) {
                foreach ($puestosIds as $id) {
                    $q->orWhereRaw('JSON_CONTAINS(puestos_trabajo, ?)', [json_encode($id)]);
                }
            })
            ->get();

        $resultado = [];

        foreach ($elementos as $elemento) {
            foreach ($puestosIds as $pid) {
                $participacion = [];

                if ($elemento->puesto_responsable_id == $pid) $participacion[] = 'R';
                if ($elemento->puesto_ejecutor_id == $pid) $participacion[] = 'E';
                if ($elemento->puesto_resguardo_id == $pid) $participacion[] = 'A';

                $relacionados = $elemento->puestos_relacionados ?? [];

                if (in_array((string)$pid, $relacionados) || in_array((int)$pid, $relacionados)) {
                    $participacion[] = 'PR';
                }

                if (!empty($participacion)) {
                    $puestoNombre = \App\Models\PuestoTrabajo::find($pid)->nombre ?? 'Desconocido';
                    $resultado[] = [
                        'Proceso'        => $elemento->tipoProceso->nombre ?? 'Sin proceso',
                        'Folio'          => $elemento->folio_elemento ?? '-',
                        'Procedimiento'  => $elemento->nombre_elemento ?? '-',
                        'Puesto'         => $puestoNombre,
                        'Participacion'  => implode('-', array_unique($participacion))
                    ];
                }
            }
        }

        foreach ($relaciones as $relacion) {
            $el = $relacion->elemento;
            foreach ($puestosIds as $pid) {
                $puestosRelacion = $relacion->puestos_trabajo ?? [];
                if (in_array($pid, $puestosRelacion)) {
                    $puestoNombre = \App\Models\PuestoTrabajo::find($pid)->nombre ?? 'Desconocido';
                    $resultado[] = [
                        'Proceso'        => $el->tipoProceso->nombre ?? 'Sin proceso',
                        'Folio'          => $el->folio_elemento ?? '-',
                        'Procedimiento'  => $el->nombre_elemento ?? '-',
                        'Puesto'         => $puestoNombre,
                        'Participacion'  => 'PM'
                    ];
                }
            }
        }

        $agrupado = [];

        foreach ($resultado as $fila) {
            $key = $fila['Folio'] . '-' . $fila['Puesto'];

            if (!isset($agrupado[$key])) {
                $agrupado[$key] = $fila;
            } else {
                $existentes = explode('-', $agrupado[$key]['Participacion']);
                $nuevas = explode('-', $fila['Participacion']);
                $agrupado[$key]['Participacion'] = implode('-', array_unique(array_merge($existentes, $nuevas)));
            }
        }

        $resultado = array_values($agrupado);

        usort($resultado, function ($a, $b) {
            $cmp = strcmp($a['Puesto'], $b['Puesto']);
            if ($cmp !== 0) return $cmp;

            return strcmp((string)$a['Folio'], (string)$b['Folio']);
        });

        return response()->json([
            'status' => 'ok',
            'modo'   => 'participacion',
            'data'   => $resultado,
            'legend' => [
                'R'  => 'Responsable',
                'E'  => 'Ejecutor',
                'A'  => 'Resguardo',
                'PR' => 'Relacionado',
                'PM' => 'Adicional'
            ]
        ]);
    }

    /**
     * Exportar matriz a Excel
     */
    public function export(Request $request)
    {
        $puestos = $request->input('puestos', []);
        $data = $request->input('data', []);
        $puestosAdicionales = $request->input('puestosAdicionales', []);

        if (empty($puestos) && empty($data)) {
            return redirect()->back()->with('error', 'Vacío');
        }

        $filename = 'matriz.xlsx';

        return Excel::download(new MatrizExport($puestos, $data, $puestosAdicionales), $filename);
    }

    public function exportJob(Request $request)
    {
        $data = $request->input('data', []);

        if (empty($data)) {
            return redirect()->back()->with('error', 'Vacío');
        }

        $filename = 'matrizporpuesto.xlsx';

        return Excel::download(new MatrizFiltroExport($data), $filename);
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\MatrizExport;
use App\Exports\MatrizFiltroExport;
use App\Models\Area;
use App\Models\Division;
use App\Models\Elemento;
use App\Models\PuestoTrabajo;
use App\Models\UnidadNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $puestos = PuestoTrabajo::pluck('nombre', 'id_puesto_trabajo')->toArray();

        $elementos = Elemento::with([
            'tipoElemento',
            'tipoProceso',
        ])->whereHas('tipoElemento', function ($query) {
            $query->where('nombre', 'Procedimiento');
        })->get();

        $puestosAdicionales = [];
        foreach ($elementos as $el) {
            $adicionales = is_array($el->nombres_relacion)
                ? $el->nombres_relacion
                : json_decode($el->nombres_relacion, true);

            if ($adicionales) {
                foreach ($adicionales as $nombre) {
                    $puestosAdicionales[] = $nombre;
                }
            }
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

            $adicionales = is_array($el->nombres_relacion)
                ? $el->nombres_relacion
                : json_decode($el->nombres_relacion, true);

            if ($adicionales) {
                foreach ($adicionales as $nombre) {
                    $asignar($fila[$nombre], 'PM');
                }
            }

            return $fila;
        });

        return response()->json([
            'status' => 'ok',
            'puestosAdicionales' => $puestosAdicionales,
            'puestos' => $puestosFinales,
            'data'   => $data
        ]);
    }

    public function matrizFiltro(Request $request)
    {
        $entrada = $request->input('puestos_relacionados', []);
        if (empty($entrada) || !is_array($entrada)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Debes proporcionar al menos un puesto (puestos_relacionados[]).'
            ], 422);
        }

        $rawInputs = Arr::wrap($entrada);

        $ids = [];
        $nombresEntrada = [];
        foreach ($rawInputs as $v) {
            if ($v === null || $v === '') continue;
            if (is_numeric($v)) $ids[] = (int)$v;
            else $nombresEntrada[] = (string)$v;
        }

        if (!empty($nombresEntrada)) {
            $idsPorNombre = PuestoTrabajo::whereIn('nombre', $nombresEntrada)
                ->pluck('id_puesto_trabajo')->map(fn($x) => (int)$x)->all();
            $ids = array_merge($ids, $idsPorNombre);
        }
        $ids = array_values(array_filter(array_unique($ids), fn($x) => $x > 0));
        if (empty($ids) && empty($nombresEntrada)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver ningún puesto válido.'
            ], 422);
        }

        $catalogo = PuestoTrabajo::pluck('nombre', 'id_puesto_trabajo')->toArray();
        $nombresDeIds = array_values(array_filter(array_map(fn($id) => $catalogo[$id] ?? null, $ids)));

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
            ->whereHas('tipoElemento', fn($q) => $q->where('nombre', 'Procedimiento'))
            ->where(function ($q) use ($ids, $nombresDeIds) {
                foreach ($ids as $id) {
                    $q->orWhere('puesto_responsable_id', $id)
                        ->orWhere('puesto_ejecutor_id', $id)
                        ->orWhere('puesto_resguardo_id', $id)
                        ->orWhereJsonContains('puestos_relacionados', $id)
                        ->orWhereJsonContains('puestos_relacionados', (string)$id);
                }
                foreach ($nombresDeIds as $nombre) {
                    $q->orWhereRaw("JSON_CONTAINS(nombres_relacion, JSON_QUOTE(?), '$')", [$nombre]);
                }
                // foreach ($nombresEntrada as $nombreRaw) {
                //     $q->orWhereRaw("JSON_CONTAINS(nombres_relacion, JSON_QUOTE(?), '$')", [$nombreRaw]);
                // }
            })
            ->get();

        $data = [];
        foreach ($elementos as $el) {
            /*$relacionados = is_array($el->puestos_relacionados)
                 ? $el->puestos_relacionados
                : json_decode($el->puestos_relacionados, true);
            $relacionados = $relacionados ?: [];
            $relNum = array_map(fn($v) => is_numeric($v) ? (int)$v : $v, $relacionados);
            $relStr = array_map(fn($v) => (string)$v, $relacionados);

            $adicionales = is_array($el->nombres_relacion)
                ? $el->nombres_relacion
                : json_decode($el->nombres_relacion, true);
            $adicionales = $adicionales ?: []; */

            $relacionados = $el->puestos_relacionados ?? [];
            $relacionados = array_filter($relacionados, fn($v) => is_scalar($v));

            $relNum = array_map(fn($v) => is_numeric($v) ? (int)$v : $v, $relacionados);
            $relStr = array_map(fn($v) => (string)$v, $relacionados);

            $adicionales = $el->nombres_relacion ?? [];
            $adicionales = array_filter($adicionales, fn($v) => is_scalar($v));

            foreach ($ids as $id) {
                $participa = [];

                if ((int)$el->puesto_responsable_id === $id) $participa[] = 'R';
                if ((int)$el->puesto_ejecutor_id === $id)   $participa[] = 'E';
                if ((int)$el->puesto_resguardo_id === $id)  $participa[] = 'A';

                if (in_array($id, $relNum, true) || in_array((string)$id, $relStr, true)) {
                    $participa[] = 'PR';
                }

                $nombreId = $catalogo[$id] ?? null;
                if ($nombreId && in_array($nombreId, $adicionales, true)) {
                    $participa[] = 'PM';
                }

                if (!empty($participa)) {
                    $data[] = [
                        'Proceso'       => $el->tipoProceso->nombre ?? 'N/A',
                        'Folio'         => $el->folio_elemento ?? 'N/A',
                        'Procedimiento' => $el->nombre_elemento ?? 'N/A',
                        'Puesto'        => $catalogo[$id] ?? ('ID ' . $id),
                        'Participacion' => implode('-', array_values(array_unique($participa)))
                    ];
                }
            }
        }

        usort($data, function ($a, $b) {
            return [$a['Proceso'], $a['Folio'], $a['Puesto']] <=> [$b['Proceso'], $b['Folio'], $b['Puesto']];
        });

        return response()->json([
            'status' => 'ok',
            'data'   => array_values($data),
            'filtro' => [
                'puestos_ids'     => $ids,
                'puestos_nombres' => $nombresDeIds,
            ],
            'modo'  => 'participacion',
            'legend' => ['R' => 'Responsable', 'E' => 'Ejecutor', 'A' => 'Resguardo', 'PR' => 'Relacionado', 'PM' => 'Adicional'],
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

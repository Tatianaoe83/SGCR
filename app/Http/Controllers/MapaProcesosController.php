<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Support\Collection;

class MapaProcesosController extends Controller
{
    private const INDUSTRIAL_LAYOUT = [
        ['tipo' => 'shared', 'folio' => 'IND01'],
        ['tipo' => 'split',  'con' => 'IND02', 'ag' => 'IND04'],
        ['tipo' => 'shared', 'folio' => 'IND03'],
        ['tipo' => 'split',  'con' => 'IND04', 'ag' => 'IND02'],
        ['tipo' => 'split',  'con' => 'IND05', 'ag' => null],
        ['tipo' => 'shared', 'folio' => 'IND06'],
    ];

    public function index()
    {
        $procesos = Elemento::whereHas('tipoElemento', function ($query) {
            $query->where('nombre', 'Proceso');
        })
            ->where('status', 'Publicado')
            ->with(['tipoProceso'])
            ->orderBy('ubicacion_eje_x')
            ->orderBy('folio_elemento')
            ->get();

        $estrategicos = collect();
        $clave = [
            'construccion' => collect(),
            'industrial'   => ['columnas' => []],
            'otros'        => collect(),
        ];
        $apoyoAdm = collect();
        $apoyoOp = collect();

        foreach ($procesos->groupBy('tipo_proceso_id') as $items) {
            $tipo = $items->first()->tipoProceso;
            $nombre = strtolower($tipo?->nombre ?? '');
            $sorted = $items->sortBy([
                ['ubicacion_eje_x', 'asc'],
                ['folio_elemento', 'asc'],
            ])->values();

            if (str_contains($nombre, 'estratég') || str_contains($nombre, 'estrateg')) {
                $estrategicos = $sorted;
                continue;
            }

            if (str_contains($nombre, 'clave')) {
                $construccion = $sorted->filter(
                    fn($p) =>
                    str_starts_with(strtoupper($p->folio_elemento ?? ''), 'PC')
                )->values();

                $industrial = $sorted->filter(
                    fn($p) =>
                    str_starts_with(strtoupper($p->folio_elemento ?? ''), 'IND')
                )->values();

                $otros = $sorted->filter(
                    fn($p) =>
                    !str_starts_with(strtoupper($p->folio_elemento ?? ''), 'PC') &&
                        !str_starts_with(strtoupper($p->folio_elemento ?? ''), 'IND')
                )->values();

                $clave = [
                    'construccion' => $construccion,
                    'industrial'   => $this->buildIndustrialLayout($industrial),
                    'otros'        => $otros,
                ];

                continue;
            }

            if (str_contains($nombre, 'apoyo') && (str_contains($nombre, 'adm') || str_contains($nombre, 'admin'))) {
                $apoyoAdm = $sorted;
                continue;
            }

            if (str_contains($nombre, 'apoyo')) {
                $apoyoOp = $sorted;
            }
        }

        $puestoIdDelUsuario = null;

        if (auth()->check()) {
            $empleado = Empleados::where('correo', auth()->user()->email)
                ->whereNull('deleted_at')
                ->first();

            $puestoIdDelUsuario = $empleado?->puesto_trabajo_id;
        }

        $procesosDestacados = collect();

        // Solo buscar procesos si el usuario tiene un puesto asignado
        if ($puestoIdDelUsuario) {
            $procedimientosHijos = Elemento::where('status', 'Publicado')
                ->where('tipo_elemento_id', 2)
                ->where(function ($q) use ($puestoIdDelUsuario) {
                    $q->where('puesto_responsable_id', $puestoIdDelUsuario)
                        ->orWhere('puesto_ejecutor_id', $puestoIdDelUsuario)
                        ->orWhere('puesto_resguardo_id', $puestoIdDelUsuario)
                        ->orWhereJsonContains('puestos_relacionados', $puestoIdDelUsuario);
                })
                ->pluck('nombre_elemento', 'id_elemento')
                ->toArray();

            $procesosDestacados = Elemento::whereHas(
                'tipoElemento',
                fn($q) => $q->where('nombre', 'Proceso')
            )
                ->where('status', 'Publicado')
                ->where(function ($query) use ($puestoIdDelUsuario) {
                    $query->whereHas('elementosHijos', function ($q) use ($puestoIdDelUsuario) {
                        $q->where(function ($inner) use ($puestoIdDelUsuario) {
                            $inner->where('puesto_responsable_id', $puestoIdDelUsuario)
                                ->orWhereJsonContains('puestos_relacionados', $puestoIdDelUsuario);
                        })
                            ->orWhereHas(
                                'relaciones',
                                fn($r) => $r->whereJsonContains('puestos_trabajo', $puestoIdDelUsuario)
                            );
                    })
                        ->orWhereHas('elementosRelacionados', function ($q) use ($puestoIdDelUsuario) {
                            $q->where(function ($inner) use ($puestoIdDelUsuario) {
                                $inner->where('puesto_responsable_id', $puestoIdDelUsuario)
                                    ->orWhereJsonContains('puestos_relacionados', $puestoIdDelUsuario);
                            })
                                ->orWhereHas(
                                    'relaciones',
                                    fn($r) => $r->whereJsonContains('puestos_trabajo', $puestoIdDelUsuario)
                                );
                        });
                })
                ->pluck('id_elemento')
                ->all();
        } else {
            \Log::debug('Sin puesto asignado para el usuario');
        }

        return view('mapa-procesos.index', compact(
            'estrategicos',
            'clave',
            'apoyoAdm',
            'apoyoOp',
            'procesosDestacados'
        ));
    }

    public function procedimientosDelProceso($id)
    {
        $proceso = Elemento::with('tipoProceso')->findOrFail($id);

        $relacionados = Elemento::with('tipoElemento')
            ->where(function ($query) use ($id) {
                $query->where('elemento_padre_id', $id)
                    ->orWhereJsonContains('elemento_relacionado_id', $id);
            })
            ->where('status', 'Publicado')
            ->whereHas('tipoElemento', function ($q) {
                $q->where('nombre', '!=', 'Proceso');
            })
            ->get(['id_elemento', 'nombre_elemento', 'folio_elemento', 'status', 'version_elemento', 'tipo_elemento_id'])
            ->map(fn($e) => [
                'id'      => $e->id_elemento,
                'folio'   => $e->folio_elemento ?? '',
                'nombre'  => $e->nombre_elemento,
                'status'  => $e->status ?? '',
                'version' => $e->version_elemento,
                'tipo'    => $e->tipoElemento?->nombre ?? '',
                'url'     => route('elementos.info', $e->id_elemento),
            ]);

        return response()->json([
            'proceso' => [
                'id'     => $proceso->id_elemento,
                'folio'  => $proceso->folio_elemento ?? '',
                'nombre' => $proceso->nombre_elemento,
                'tipo'   => $proceso->tipoProceso?->nombre ?? '',
            ],
            'relacionados' => $relacionados->values(),
        ]);
    }

    private function buildIndustrialLayout(Collection $procesos): array
    {
        $porFolio = $procesos->keyBy(function ($proceso) {
            return strtoupper(trim($proceso->folio_elemento ?? ''));
        });

        $columnas = [];

        foreach (self::INDUSTRIAL_LAYOUT as $bloque) {
            if ($bloque['tipo'] === 'shared') {
                $proceso = $porFolio->get($bloque['folio']);

                if (!$proceso) {
                    continue;
                }

                $columnas[] = [
                    'tipo'    => 'shared',
                    'proceso' => $proceso,
                ];

                continue;
            }

            $procesoCon = !empty($bloque['con']) ? $porFolio->get($bloque['con']) : null;
            $procesoAg = !empty($bloque['ag']) ? $porFolio->get($bloque['ag']) : null;

            if (!$procesoCon && !$procesoAg) {
                continue;
            }

            $columnas[] = [
                'tipo' => 'split',
                'con'  => $procesoCon,
                'ag'   => $procesoAg,
            ];
        }

        return [
            'columnas' => $columnas,
        ];
    }
}

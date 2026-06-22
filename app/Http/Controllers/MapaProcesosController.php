<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Support\Collection;

class MapaProcesosController extends Controller
{
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

        $estrategicos = [];
        $construccionItems = collect();
        $industrialItems = collect();
        $claveOtrosItems = collect();
        $apoyoAdmItems = collect();
        $apoyoOpItems = collect();

        foreach ($procesos->groupBy('tipo_proceso_id') as $items) {
            $tipo = $items->first()->tipoProceso;
            $nombre = strtolower($tipo?->nombre ?? '');
            $sorted = $items->sortBy([
                ['ubicacion_eje_x', 'asc'],
                ['folio_elemento', 'asc'],
            ])->values();

            if (str_contains($nombre, 'estratég') || str_contains($nombre, 'estrateg')) {
                $estrategicos = $this->buildColumnLayout($sorted);
                continue;
            }

            if (str_contains($nombre, 'clave')) {
                if (str_contains($nombre, 'industrial')) {
                    $industrialItems = $industrialItems->merge($sorted);
                } elseif (str_contains($nombre, 'construc')) {
                    $construccionItems = $construccionItems->merge($sorted);
                } else {
                    $construccionItems = $construccionItems->merge(
                        $sorted->filter(
                            fn($p) => str_starts_with(strtoupper($p->folio_elemento ?? ''), 'PC')
                        )
                    );
                    $industrialItems = $industrialItems->merge(
                        $sorted->filter(
                            fn($p) => str_starts_with(strtoupper($p->folio_elemento ?? ''), 'IND')
                        )
                    );
                    $claveOtrosItems = $claveOtrosItems->merge(
                        $sorted->filter(
                            fn($p) =>
                            !str_starts_with(strtoupper($p->folio_elemento ?? ''), 'PC') &&
                            !str_starts_with(strtoupper($p->folio_elemento ?? ''), 'IND')
                        )
                    );
                }
                continue;
            }

            if (str_contains($nombre, 'apoyo') && (str_contains($nombre, 'adm') || str_contains($nombre, 'admin'))) {
                $apoyoAdmItems = $sorted;
                continue;
            }

            if (str_contains($nombre, 'apoyo')) {
                $apoyoOpItems = $sorted;
            }
        }

        $clave = [
            'construccion' => $this->buildColumnLayout($construccionItems),
            'industrial'   => $this->buildIndustrialColumnLayout($industrialItems)['columnas'],
            'otros'        => $this->buildColumnLayout($claveOtrosItems),
        ];

        $apoyoAdm = $this->buildApoyoDualRowLayout($apoyoAdmItems);
        $apoyoOp = $this->buildApoyoDualRowLayout($apoyoOpItems);

        $mapaMaxEjeX = $this->resolveMapaMaxEjeX([
            $estrategicos,
            $clave['construccion'],
            $clave['industrial'],
            $clave['otros'],
        ]);

        $estrategicos = $this->normalizeColumnLayout($estrategicos, $mapaMaxEjeX);
        $clave['construccion'] = $this->normalizeColumnLayout($clave['construccion'], $mapaMaxEjeX);
        $clave['industrial'] = $this->normalizeIndustrialColumnLayout($clave['industrial'], $mapaMaxEjeX);
        $clave['otros'] = $this->normalizeColumnLayout($clave['otros'], $mapaMaxEjeX);

        $puestoIdDelUsuario = null;

        if (auth()->check()) {
            $empleado = Empleados::where('correo', auth()->user()->email)
                ->whereNull('deleted_at')
                ->first();

            $puestoIdDelUsuario = $empleado?->puesto_trabajo_id;
        }

        $procesosDestacados = [];

        if ($puestoIdDelUsuario) {
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
            'procesosDestacados',
            'mapaMaxEjeX'
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

    /**
     * Layout industrial CON/AG: Y=0 compartido, Y=1 CON, Y=2 AG.
     */
    private function buildIndustrialColumnLayout(Collection $procesos): array
    {
        if ($procesos->isEmpty()) {
            return ['columnas' => []];
        }

        $columnas = $procesos
            ->groupBy(fn ($p) => (int) ($p->ubicacion_eje_x ?? 0))
            ->sortKeys()
            ->map(function (Collection $items, $x) {
                $porY = $items->keyBy(fn ($p) => (int) ($p->ubicacion_eje_y ?? 0));

                $shared = $porY->get(0);
                $con    = $porY->get(1);
                $ag     = $porY->get(2);

                if ($shared && ! $con && ! $ag) {
                    return [
                        'tipo'    => 'shared',
                        'x'       => (int) $x,
                        'proceso' => $shared,
                    ];
                }

                return [
                    'tipo' => 'split',
                    'x'    => (int) $x,
                    'con'  => $con,
                    'ag'   => $ag,
                ];
            })
            ->values()
            ->all();

        return ['columnas' => $columnas];
    }

    private function normalizeIndustrialColumnLayout(array $columnas, int $maxX): array
    {
        if ($maxX <= 0 || empty($columnas)) {
            return $columnas;
        }

        $byX = collect($columnas)->keyBy('x');
        $normalized = [];

        for ($x = 1; $x <= $maxX; $x++) {
            $normalized[] = $byX->get($x, [
                'tipo' => 'split',
                'x'    => $x,
                'con'  => null,
                'ag'   => null,
            ]);
        }

        return $normalized;
    }

    /**
     * Layout de apoyo: dos filas (superior e inferior) según ubicacion_eje_y.
     * Y=0 o Y=1 → fila superior; Y=2 → fila inferior.
     */
    private function buildApoyoDualRowLayout(Collection $procesos): array
    {
        if ($procesos->isEmpty()) {
            return [
                'maxX'     => 0,
                'superior' => [],
                'inferior' => [],
            ];
        }

        $superiorItems = $procesos->filter(
            fn ($p) => (int) ($p->ubicacion_eje_y ?? 0) !== 2
        );
        $inferiorItems = $procesos->filter(
            fn ($p) => (int) ($p->ubicacion_eje_y ?? 0) === 2
        );

        $maxX = max(
            (int) ($superiorItems->max('ubicacion_eje_x') ?? 0),
            (int) ($inferiorItems->max('ubicacion_eje_x') ?? 0)
        );

        return [
            'maxX'     => $maxX,
            'superior' => $this->normalizeColumnLayout($this->buildColumnLayout($superiorItems), $maxX),
            'inferior' => $this->normalizeColumnLayout($this->buildColumnLayout($inferiorItems), $maxX),
        ];
    }

    /**
     * Agrupa procesos por ubicacion_eje_x en columnas.
     * Varios procesos con el mismo X se apilan verticalmente en la misma columna.
     */
    private function buildColumnLayout(Collection $procesos): array
    {
        if ($procesos->isEmpty()) {
            return [];
        }

        return $procesos
            ->groupBy(fn($p) => (int) ($p->ubicacion_eje_x ?? 0))
            ->sortKeys()
            ->map(fn($items, $x) => [
                'x'        => (int) $x,
                'procesos' => $items->sortBy([
                    ['ubicacion_eje_y', 'asc'],
                    ['folio_elemento', 'asc'],
                ])->values(),
            ])
            ->values()
            ->all();
    }

    private function resolveMapaMaxEjeX(array $layouts): int
    {
        $max = 0;

        foreach ($layouts as $layout) {
            foreach ($layout as $col) {
                $max = max($max, (int) ($col['x'] ?? 0));
            }
        }

        return $max;
    }

    /**
     * Rellena columnas vacías del 1 al maxX para alinear todas las filas del mapa.
     */
    private function normalizeColumnLayout(array $columnas, int $maxX): array
    {
        if ($maxX <= 0 || empty($columnas)) {
            return $columnas;
        }

        $byX = collect($columnas)->keyBy('x');
        $normalized = [];

        for ($x = 1; $x <= $maxX; $x++) {
            $normalized[] = $byX->get($x, [
                'x'        => $x,
                'procesos' => collect(),
            ]);
        }

        return $normalized;
    }
}

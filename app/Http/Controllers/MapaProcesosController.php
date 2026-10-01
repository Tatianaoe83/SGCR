<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Elemento;
use App\Models\Empleados;
use Illuminate\Support\Collection;

class MapaProcesosController extends Controller
{
    /**
     * Orden, siglas y colores tomados del "Mapa de Procesos VO.BO." (PPTX oficial).
     */
    private const GRUPOS = [
        'pe' => [
            'sigla'     => 'PE',
            'pre'       => null,
            'titulo'    => 'Procesos Estratégicos',
            'kicker'    => 'Procesos estratégicos',
            'titulo_linea' => 'Procesos Estratégicos',
            'banda'     => "Procesos\nEstratégicos",
            'color'     => '#1F4E78',
            'borde'     => '#153651',
        ],
        'dyc' => [
            'sigla'     => 'D&C',
            'pre'       => 'Procesos clave',
            'titulo'    => 'División Diseño y Construcción',
            'kicker'    => 'Procesos clave',
            'titulo_linea' => 'División Diseño y Construcción',
            'banda'     => 'División Diseño y Construcción',
            'color'     => '#D97732',
            'borde'     => '#9E4E1F',
        ],
        'ind' => [
            'sigla'     => 'IND',
            'pre'       => 'Procesos clave',
            'titulo'    => 'División Industrial',
            'kicker'    => 'Procesos clave',
            'titulo_linea' => 'División Industrial',
            'banda'     => 'División Industrial',
            'color'     => '#16827A',
            'borde'     => '#0D544F',
        ],
        'paa' => [
            'sigla'     => 'PAA',
            'pre'       => null,
            'titulo'    => 'Procesos Administrativos de Apoyo',
            'kicker'    => 'Procesos de apoyo',
            'titulo_linea' => 'Administrativos de Apoyo',
            'banda'     => "Procesos\nAdministrativos\nde Apoyo",
            'color'     => '#607D8B',
            'borde'     => '#3F555F',
        ],
        'poa' => [
            'sigla'     => 'POA',
            'pre'       => null,
            'titulo'    => 'Procesos Operativos de Apoyo',
            'kicker'    => 'Procesos de apoyo',
            'titulo_linea' => 'Operativos de Apoyo',
            'banda'     => "Procesos\nOperativos\nde Apoyo",
            'color'     => '#39424D',
            'borde'     => '#222931',
        ],
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

        $puestoIdDelUsuario = $this->puestoDelUsuario();
        $procesosDestacados = $this->procesosRelacionadosConPuesto($puestoIdDelUsuario);

        $porGrupo = array_map(fn() => collect(), self::GRUPOS);

        foreach ($procesos as $proceso) {
            $clave = $this->grupoDelProceso($proceso);

            if ($clave !== null) {
                $porGrupo[$clave]->push($proceso);
            }
        }

        $procedimientos = $this->procedimientosPorProceso(
            collect($porGrupo)->flatten(1)->pluck('id_elemento')->all(),
            $puestoIdDelUsuario
        );

        $grupos = [];

        foreach (self::GRUPOS as $clave => $config) {
            // Algunos procesos están dados de alta una vez por unidad de negocio con el mismo folio;
            // en el mapa se muestran una sola vez y se juntan sus procedimientos.
            $unicos = $porGrupo[$clave]
                ->groupBy(fn($p) => strtoupper(trim($p->folio_elemento ?? '')) ?: 'id-' . $p->id_elemento)
                ->map(function (Collection $mismos) use ($procedimientos, $procesosDestacados) {
                    $principal = $mismos->first();
                    $ids = $mismos->pluck('id_elemento')->all();

                    $principal->procedimientos_mapa = collect($ids)
                        ->flatMap(fn($id) => $procedimientos[$id] ?? [])
                        ->unique('id')
                        ->sortBy('folio', SORT_NATURAL)
                        ->values()
                        ->all();
                    $principal->destacado_mapa = count(array_intersect($ids, $procesosDestacados)) > 0;

                    return $principal;
                })
                ->sortBy(fn($p) => $p->folio_elemento ?? '', SORT_NATURAL)
                ->values();

            $grupos[$clave] = $config + [
                'clave'               => $clave,
                'procesos'            => $unicos,
                'total_procedimientos' => $unicos->sum(fn($p) => count($p->procedimientos_mapa)),
            ];
        }

        $totales = [
            'grupos'         => collect($grupos)->filter(fn($g) => $g['procesos']->isNotEmpty())->count(),
            'procesos'       => collect($grupos)->sum(fn($g) => $g['procesos']->count()),
            'procedimientos' => collect($grupos)->sum('total_procedimientos'),
        ];

        $hayDestacados = collect($grupos)->contains(
            fn($g) => $g['procesos']->contains(fn($p) => $p->destacado_mapa)
        );

        return view('mapa-procesos.index', compact('grupos', 'totales', 'hayDestacados'));
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
                'url'     => route('elementos.show', $e->id_elemento),
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

    private function grupoDelProceso(Elemento $proceso): ?string
    {
        $tipo = mb_strtolower($proceso->tipoProceso?->nombre ?? '');
        $folio = strtoupper(trim($proceso->folio_elemento ?? ''));

        if (str_contains($tipo, 'estrat') || str_starts_with($folio, 'PE')) {
            return 'pe';
        }

        if (str_contains($tipo, 'clave')) {
            return str_contains($tipo, 'industrial') || str_contains($folio, 'IND') ? 'ind' : 'dyc';
        }

        if (str_starts_with($folio, 'PC-IND') || str_starts_with($folio, 'IND')) {
            return 'ind';
        }

        if (str_starts_with($folio, 'PC')) {
            return 'dyc';
        }

        if (str_contains($tipo, 'apoyo')) {
            return str_contains($tipo, 'admin') ? 'paa' : 'poa';
        }

        if (str_starts_with($folio, 'PAA')) {
            return 'paa';
        }

        if (str_starts_with($folio, 'POA')) {
            return 'poa';
        }

        return null;
    }

    /**
     * @return array<int, array<int, array{id:int, folio:string, nombre:string, area:string, url:string, destacado:bool}>>
     */
    private function procedimientosPorProceso(array $procesoIds, ?int $puestoId): array
    {
        if ($procesoIds === []) {
            return [];
        }

        $hijos = Elemento::with(['puestoResponsable', 'tipoElemento'])
            ->where(function ($q) use ($procesoIds) {
                $q->whereIn('elemento_padre_id', $procesoIds);

                foreach ($procesoIds as $id) {
                    $q->orWhereRaw(
                        'JSON_CONTAINS(IF(JSON_VALID(elemento_relacionado_id), elemento_relacionado_id, \'[]\'), ?)',
                        [json_encode((int) $id)]
                    )->orWhereRaw(
                        'JSON_CONTAINS(IF(JSON_VALID(elemento_relacionado_id), elemento_relacionado_id, \'[]\'), ?)',
                        [json_encode((string) $id)]
                    );
                }
            })
            ->where('status', 'Publicado')
            ->whereHas('tipoElemento', fn($q) => $q->where('nombre', '!=', 'Proceso'))
            ->get();

        $enMapa = array_flip(array_map('intval', $procesoIds));

        $areaIds = $hijos
            ->flatMap(fn($h) => (array) ($h->puestoResponsable?->areas_ids ?? []))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $areas = $areaIds ? Area::whereIn('id_area', $areaIds)->pluck('nombre', 'id_area') : collect();

        $resultado = [];

        foreach ($hijos as $hijo) {
            $areaId = collect((array) ($hijo->puestoResponsable?->areas_ids ?? []))->first();
            $relacionados = array_map('intval', (array) ($hijo->puestos_relacionados ?? []));

            $documento = [
                'id'        => $hijo->id_elemento,
                'folio'     => $hijo->folio_elemento ?? '',
                'nombre'    => $hijo->nombre_elemento,
                'version'   => $hijo->version_elemento,
                'tipo'      => $hijo->tipoElemento?->nombre ?? '',
                'area'      => $areaId ? ($areas[$areaId] ?? '') : '',
                'url'       => route('elementos.show', $hijo->id_elemento),
                'destacado' => $puestoId !== null
                    && ((int) $hijo->puesto_responsable_id === $puestoId || in_array($puestoId, $relacionados, true)),
            ];

            $destinos = array_map('intval', (array) ($hijo->elemento_relacionado_id ?? []));
            $destinos[] = (int) $hijo->elemento_padre_id;

            foreach (array_unique($destinos) as $procesoId) {
                if (isset($enMapa[$procesoId])) {
                    $resultado[$procesoId][] = $documento;
                }
            }
        }

        return $resultado;
    }

    private function puestoDelUsuario(): ?int
    {
        if (!auth()->check()) {
            return null;
        }

        $empleado = Empleados::where('correo', auth()->user()->email)
            ->whereNull('deleted_at')
            ->first();

        return $empleado?->puesto_trabajo_id ? (int) $empleado->puesto_trabajo_id : null;
    }

    private function procesosRelacionadosConPuesto(?int $puestoId): array
    {
        if (!$puestoId) {
            return [];
        }

        $coincidePuesto = function ($q) use ($puestoId) {
            $q->where(function ($inner) use ($puestoId) {
                $inner->where('puesto_responsable_id', $puestoId)
                    ->orWhereJsonContains('puestos_relacionados', $puestoId);
            })
                ->orWhereHas(
                    'relaciones',
                    fn($r) => $r->whereJsonContains('puestos_trabajo', $puestoId)
                );
        };

        return Elemento::whereHas('tipoElemento', fn($q) => $q->where('nombre', 'Proceso'))
            ->where('status', 'Publicado')
            ->where(function ($query) use ($coincidePuesto) {
                $query->whereHas('elementosHijos', $coincidePuesto)
                    ->orWhereHas('elementosRelacionados', $coincidePuesto);
            })
            ->pluck('id_elemento')
            ->all();
    }
}

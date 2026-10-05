@php
    $anio = now()->year;
    $navPills = [
        ['id' => 'mapa',   'label' => 'Mapa',   'color' => '#002060'],
        ['id' => 'indice', 'label' => 'Índice', 'color' => '#002060'],
    ];
    foreach ($grupos as $g) {
        $navPills[] = ['id' => $g['clave'], 'label' => $g['sigla'], 'color' => $g['color']];
    }
    $colsLineas = function (int $n) {
        if ($n <= 0) {
            return 1;
        }
        return $n <= 7 ? $n : (int) ceil($n / 2);
    };
    $sinProcesos = collect($grupos)->every(fn($g) => $g['procesos']->isEmpty());
@endphp

<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-9xl mx-auto py-4">

        @if($sinProcesos)
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow p-16 text-center">
            <p class="text-gray-400 text-sm">No hay procesos registrados en el sistema.</p>
        </div>
        @else

        <div class="pm-sheet">
            <div class="pm-scroll">
                <div class="pm-canvas">

                    {{-- ═══════════════ MAPA ═══════════════ --}}
                    <section class="pm-view" data-view="mapa">
                        <header class="pm-head">
                            <div>
                                <h1 class="pm-title pm-title--map">MAPA DE PROCESOS</h1>
                                <p class="pm-subtitle">Sistema de Gestión de Calidad &nbsp;·&nbsp; SGC</p>
                            </div>
                            <img src="{{ asset('images/Logo-azul.png') }}" alt="PROSER Grupo Constructor" class="pm-logo">
                        </header>

                        @include('mapa-procesos.partials.nav', ['activo' => 'mapa'])

                        <div class="pm-map">
                            <div class="pm-side"><span>Requisitos del cliente</span></div>

                            <div class="pm-rows">
                                {{-- Estratégicos --}}
                                @php($g = $grupos['pe'])
                                <div class="pm-row">
                                    <div class="pm-row-label" style="--c:{{ $g['color'] }}">
                                        <span>Procesos<br>Estratégicos</span>
                                    </div>
                                    <div class="pm-row-body">
                                        @include('mapa-procesos.partials.map-cards', ['g' => $g, 'variante' => 'lg'])
                                    </div>
                                </div>

                                {{-- Claves --}}
                                <div class="pm-row">
                                    <div class="pm-row-label pm-row-label--clave">
                                        <span>Procesos<br><b>Claves</b></span>
                                    </div>
                                    <div class="pm-row-body pm-row-body--stack">
                                        @foreach(['dyc' => 'tall', 'ind' => 'md'] as $claveDiv => $variante)
                                        @php($g = $grupos[$claveDiv])
                                        <div class="pm-division">
                                            <div class="pm-division-title" style="--c:{{ $g['borde'] }}">{{ $g['banda'] }}</div>
                                            @include('mapa-procesos.partials.map-cards', ['g' => $g, 'variante' => $variante])
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Administrativos de apoyo --}}
                                @php($g = $grupos['paa'])
                                <div class="pm-row">
                                    <div class="pm-row-label" style="--c:{{ $g['color'] }}">
                                        <span>Procesos<br><b>Administrativos</b><br>de Apoyo</span>
                                    </div>
                                    <div class="pm-row-body">
                                        @include('mapa-procesos.partials.map-cards', ['g' => $g, 'variante' => 'md'])
                                    </div>
                                </div>

                                {{-- Operativos de apoyo --}}
                                @php($g = $grupos['poa'])
                                <div class="pm-row">
                                    <div class="pm-row-label" style="--c:{{ $g['color'] }}">
                                        <span>Procesos<br><b>Operativos</b><br>de Apoyo</span>
                                    </div>
                                    <div class="pm-row-body">
                                        @include('mapa-procesos.partials.map-cards', ['g' => $g, 'variante' => 'md'])
                                    </div>
                                </div>
                            </div>

                            <div class="pm-side"><span>Satisfacción del cliente</span></div>
                        </div>

                        <div class="pm-motto">
                            <span>Construyendo confianza, entregando excelencia</span>
                            <small>Mapa de Procesos SGC &nbsp;·&nbsp; {{ $anio }}</small>
                        </div>

                        @if($hayDestacados)
                        <p class="pm-legend"><span class="pm-legend-swatch"></span> Los procesos con contorno dorado y ★ están relacionados con tu puesto.</p>
                        @endif
                    </section>

                    {{-- ═══════════════ ÍNDICE ═══════════════ --}}
                    <section class="pm-view" data-view="indice" hidden>
                        <div class="pm-topline" style="--c:#002060"></div>
                        <header class="pm-head">
                            <div>
                                <p class="pm-kicker" style="--c:#D97732">Sistema de Gestión de Calidad &nbsp;·&nbsp; {{ $anio }}</p>
                                <h1 class="pm-title">LÍNEAS DE ACCIÓN</h1>
                            </div>
                            <img src="{{ asset('images/Logo-azul.png') }}" alt="PROSER Grupo Constructor" class="pm-logo">
                        </header>

                        @include('mapa-procesos.partials.nav', ['activo' => 'indice'])

                        <div class="pm-kpis">
                            <div class="pm-kpi" style="--c:#002060"><b>{{ $totales['grupos'] }}</b><span>grupos de procesos</span></div>
                            <div class="pm-kpi" style="--c:#D97732"><b>{{ $totales['procesos'] }}</b><span>procesos</span></div>
                            <div class="pm-kpi" style="--c:#16827A"><b>{{ $totales['procedimientos'] }}</b><span>procedimientos</span></div>
                        </div>

                        <div class="pm-groups" style="--cols: {{ count($grupos) }}">
                            @foreach($grupos as $g)
                            <button type="button" class="pm-group" style="--c:{{ $g['color'] }}" onclick="pmShow('{{ $g['clave'] }}')">
                                <div class="pm-group-head">
                                    <span class="pm-group-sigla">{{ $g['sigla'] }}</span>
                                    @if($g['pre'])
                                    <span class="pm-group-pre">{{ $g['pre'] }}</span>
                                    @endif
                                    <span class="pm-group-title">{{ $g['titulo'] }}</span>
                                </div>
                                <div class="pm-group-body">
                                    <p><b>{{ $g['procesos']->count() }}</b> procesos</p>
                                    <p><b>{{ $g['total_procedimientos'] }}</b> procedimientos</p>
                                    <span class="pm-group-bar"></span>
                                </div>
                            </button>
                            @endforeach
                        </div>

                        <p class="pm-foot">Mapa de Procesos SGC &nbsp;·&nbsp; {{ $anio }}</p>
                    </section>

                    {{-- ═══════════════ LÍNEAS DE ACCIÓN POR GRUPO ═══════════════ --}}
                    @foreach($grupos as $g)
                    <section class="pm-view" data-view="{{ $g['clave'] }}" hidden style="--c:{{ $g['color'] }}">
                        <div class="pm-topline"></div>
                        <header class="pm-head">
                            <div>
                                <p class="pm-kicker">Líneas de acción &nbsp;·&nbsp; {{ $g['kicker'] }}</p>
                                <h1 class="pm-title pm-title--line">{{ $g['titulo_linea'] }}</h1>
                            </div>
                            <img src="{{ asset('images/Logo-azul.png') }}" alt="PROSER Grupo Constructor" class="pm-logo">
                        </header>

                        @include('mapa-procesos.partials.nav', ['activo' => $g['clave']])

                        @if($g['procesos']->isEmpty())
                        <p class="pm-empty">No hay procesos publicados en este grupo.</p>
                        @else
                        <div class="pm-lines" style="--cols: {{ $colsLineas($g['procesos']->count()) }}">
                            @foreach($g['procesos'] as $p)
                            <div class="pm-line" id="pm-proceso-{{ $p->id_elemento }}">
                                @php($tagHead = $p->accesible_mapa ? 'a' : 'div')
                                <{{ $tagHead }}
                                    @if($p->accesible_mapa) href="{{ route('elementos.show', $p->id_elemento) }}" @else title="No tienes acceso a este proceso" aria-disabled="true" @endif
                                    class="pm-line-head {{ $p->destacado_mapa ? 'is-mine' : '' }} {{ $p->accesible_mapa ? '' : 'is-locked' }}">
                                    <span class="pm-folio">{{ $p->folio_elemento }}</span>
                                    <span class="pm-name">{{ $p->nombre_elemento }}</span>
                                </{{ $tagHead }}>

                                <div class="pm-tree">
                                    @forelse($p->procedimientos_mapa as $proc)
                                    @php($tagProc = $proc['accesible'] ? 'a' : 'div')
                                    <{{ $tagProc }}
                                        @if($proc['accesible']) href="{{ $proc['url'] }}" title="{{ $proc['tipo'] }}" @else title="No tienes acceso a este documento" aria-disabled="true" @endif
                                        class="pm-proc {{ $proc['destacado'] ? 'is-mine' : '' }} {{ $proc['accesible'] ? '' : 'is-locked' }}">
                                        @if(!$proc['accesible'])
                                        <span class="pm-proc-version">🔒 Sin acceso</span>
                                        @elseif($proc['version'])
                                        <span class="pm-proc-version">v{{ $proc['version'] }}</span>
                                        @endif
                                        <span class="pm-proc-folio">{{ $proc['folio'] }}</span>
                                        <span class="pm-proc-name">{{ $proc['nombre'] }}</span>
                                        @if($proc['destacado'] || $proc['area'])
                                        <span class="pm-proc-area">
                                            @if($proc['destacado'])<em>★ Relacionado contigo</em>@if($proc['area']) &nbsp;·&nbsp; @endif @endif{{ $proc['area'] }}
                                        </span>
                                        @endif
                                    </{{ $tagProc }}>
                                    @empty
                                    <p class="pm-proc-empty">Sin documentos publicados</p>
                                    @endforelse
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <p class="pm-foot">Mapa de Procesos SGC &nbsp;·&nbsp; {{ $anio }}</p>
                    </section>
                    @endforeach

                </div>
            </div>
        </div>
        @endif
    </div>

    <style>
        .pm-sheet {
            --navy: #002060;
            --yellow: #FFDA85;
            --muted: #6A737D;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
            font-family: Tahoma, Verdana, Segoe, sans-serif;
            color: #2B2B2B;
            overflow: hidden;
        }

        .pm-sheet,
        .pm-sheet * {
            font-family: Tahoma, Verdana, Segoe, sans-serif !important;
        }

        .pm-scroll {
            overflow-x: auto;
        }

        .pm-canvas {
            min-width: 1180px;
            padding: 0 28px 20px;
        }

        .pm-view[hidden] {
            display: none !important;
        }

        .pm-topline {
            height: 6px;
            margin: 0 -28px;
            background: var(--c);
        }

        /* ─── Encabezado ─── */
        .pm-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding-top: 20px;
        }

        .pm-title {
            margin: 0;
            color: var(--navy);
            font-weight: 800;
            font-size: 34px;
            line-height: 1.1;
            letter-spacing: .3px;
        }

        .pm-title--map {
            font-size: 40px;
        }

        .pm-title--line {
            font-size: 30px;
        }

        .pm-subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 15px;
            letter-spacing: 1.2px;
        }

        .pm-kicker {
            margin: 0 0 4px;
            color: var(--c, var(--navy));
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .pm-logo {
            height: 56px;
            width: auto;
            flex-shrink: 0;
        }

        /* ─── Navegación tipo píldoras ─── */
        .pm-nav {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin: 14px 0 20px;
            padding-top: 12px;
            border-top: 1px solid #E5E7EB;
        }

        .pm-pill {
            min-width: 86px;
            padding: 6px 14px;
            border: 1px solid #D5DAE1;
            border-radius: 9999px;
            background: #fff;
            color: var(--pc);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
        }

        .pm-pill:hover {
            border-color: var(--pc);
        }

        .pm-pill.is-active {
            background: var(--pc);
            border-color: var(--pc);
            color: #fff;
        }

        /* ─── Mapa ─── */
        .pm-map {
            display: grid;
            grid-template-columns: 56px minmax(0, 1fr) 56px;
            gap: 10px;
        }

        .pm-side {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: var(--navy);
        }

        .pm-side span {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .pm-rows {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .pm-row {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 6px;
        }

        .pm-row-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 0;
            border-radius: 6px;
            background: var(--c);
        }

        .pm-row-label--clave {
            background: linear-gradient(180deg, #D97732 0%, #D97732 38%, #16827A 62%, #16827A 100%);
        }

        .pm-row-label span {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
            text-transform: uppercase;
        }

        .pm-row-label b {
            font-size: 12px;
        }

        .pm-row-label--clave b {
            font-size: 18px;
            letter-spacing: 1px;
        }

        .pm-row-body {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 6px;
            background: #F1F3F5;
        }

        .pm-row-body--stack {
            flex-direction: column;
            align-items: stretch;
            gap: 4px;
        }

        .pm-division-title {
            margin: 2px 0 6px;
            color: var(--c);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .pm-cards {
            display: grid;
            grid-template-columns: repeat(var(--cols), minmax(0, 1fr));
            gap: 12px;
            width: 100%;
        }

        .pm-card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 8px 10px;
            border: 1px solid var(--b);
            border-radius: 6px;
            background: var(--c);
            color: #fff;
            text-align: center;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, filter .15s;
        }

        .pm-card:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 6px 14px rgba(0, 0, 0, .18);
        }

        .pm-card--lg {
            min-height: 80px;
        }

        .pm-card--md {
            min-height: 58px;
        }

        .pm-card--tall {
            min-height: 92px;
            border-radius: 12px;
        }

        .pm-card .pm-folio,
        .pm-line-head .pm-folio {
            color: var(--yellow);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .pm-card .pm-name {
            font-size: 11.5px;
            line-height: 1.25;
        }

        .pm-card--tall .pm-folio {
            font-size: 11.5px;
        }

        .pm-card--tall .pm-name {
            font-size: 10px;
        }

        .pm-card.is-mine,
        .pm-line-head.is-mine {
            box-shadow: 0 0 0 3px var(--yellow), 0 4px 10px rgba(0, 0, 0, .15);
        }

        .pm-card.is-mine::after,
        .pm-line-head.is-mine::after {
            content: '★';
            position: absolute;
            top: -9px;
            right: -7px;
            display: grid;
            place-items: center;
            width: 20px;
            height: 20px;
            border-radius: 9999px;
            background: var(--yellow);
            color: var(--navy);
            font-size: 11px;
        }

        .pm-empty-row,
        .pm-empty {
            margin: 0;
            color: #9AA1A9;
            font-size: 12px;
            font-style: italic;
        }

        .pm-motto {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            padding: 7px 16px;
            border-radius: 6px;
            background: var(--navy);
            color: #fff;
        }

        .pm-motto span {
            font-size: 14px;
            font-style: italic;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .pm-motto small {
            position: absolute;
            right: 16px;
            font-size: 10px;
            opacity: .85;
        }

        .pm-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .pm-legend-swatch {
            width: 26px;
            height: 14px;
            border-radius: 4px;
            background: #1F4E78;
            box-shadow: 0 0 0 2px var(--yellow);
        }

        /* ─── Índice ─── */
        .pm-kpis {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 18px;
            margin-top: 8px;
        }

        .pm-kpi {
            display: flex;
            flex-direction: column;
            padding: 14px 16px;
            border-left: 5px solid var(--c);
            border-radius: 6px;
            background: #F4F6FA;
        }

        .pm-kpi b {
            color: var(--c);
            font-size: 32px;
            line-height: 1.1;
        }

        .pm-kpi span {
            font-size: 12px;
        }

        .pm-groups {
            display: grid;
            grid-template-columns: repeat(var(--cols), minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .pm-group {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #D5DAE1;
            border-radius: 12px;
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }

        .pm-group:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(0, 0, 0, .1);
        }

        .pm-group-head {
            display: flex;
            flex-direction: column;
            min-height: 120px;
            padding: 14px 14px 18px;
            background: var(--c);
            color: #fff;
        }

        .pm-group-sigla {
            color: var(--yellow);
            font-size: 34px;
            font-weight: 800;
            line-height: 1.1;
        }

        .pm-group-pre {
            margin-top: 6px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .pm-group-title {
            margin-top: 4px;
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pm-group-pre + .pm-group-title {
            margin-top: 0;
        }

        .pm-group-body {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 160px;
            padding: 16px 14px 22px;
        }

        .pm-group-body p {
            margin: 0;
            font-size: 11px;
        }

        .pm-group-body b {
            color: var(--c);
            font-size: 18px;
            margin-right: 4px;
        }

        .pm-group-bar {
            height: 4px;
            margin-top: 18px;
            border-radius: 2px;
            background: var(--c);
        }

        /* ─── Líneas de acción ─── */
        .pm-lines {
            display: grid;
            grid-template-columns: repeat(var(--cols), minmax(0, 1fr));
            gap: 28px 14px;
            align-items: start;
        }

        .pm-line-head {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            width: 100%;
            min-height: 64px;
            padding: 10px 12px;
            border: 0;
            border-radius: 10px;
            background: var(--c);
            color: #fff;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: filter .15s;
        }

        .pm-line-head:hover {
            filter: brightness(1.1);
        }

        .pm-line-head .pm-folio {
            font-size: 15px;
        }

        .pm-line-head .pm-name {
            font-size: 13px;
            line-height: 1.25;
        }

        .pm-tree {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 18px 0 0 26px;
        }

        .pm-proc {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1px;
            padding: 16px 10px 8px 16px;
            border: 1px solid #D5DAE1;
            border-radius: 6px;
            background: #fff;
            color: #2B2B2B;
            text-decoration: none;
            transition: box-shadow .15s, border-color .15s;
        }

        .pm-proc:hover {
            border-color: var(--c);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }

        /* barra de color interna */
        .pm-proc > .pm-proc-folio::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 14px;
            bottom: 8px;
            width: 3px;
            border-radius: 2px;
            background: var(--c);
        }

        /* conector vertical + punto */
        .pm-proc::before {
            content: '';
            position: absolute;
            left: -21px;
            top: -13px;
            height: calc(50% + 13px);
            width: 2px;
            background: var(--c);
        }

        .pm-proc:not(:last-child)::before {
            height: calc(100% + 13px);
        }

        .pm-proc:first-child::before {
            top: -18px;
            height: calc(50% + 18px);
        }

        .pm-proc:first-child:not(:last-child)::before {
            height: calc(100% + 18px);
        }

        .pm-proc::after {
            content: '';
            position: absolute;
            left: -25px;
            top: 50%;
            width: 10px;
            height: 10px;
            margin-top: -5px;
            border-radius: 9999px;
            background: var(--c);
        }

        .pm-proc.is-locked,
        .pm-line-head.is-locked {
            cursor: not-allowed;
        }

        .pm-proc.is-locked {
            background: #F7F8FA;
        }

        .pm-proc.is-locked .pm-proc-name,
        .pm-proc.is-locked .pm-proc-folio {
            opacity: .55;
        }

        .pm-proc.is-locked:hover {
            border-color: #D5DAE1;
            box-shadow: none;
        }

        .pm-line-head.is-locked:hover {
            filter: none;
        }

        .pm-proc-version {
            position: absolute;
            top: 5px;
            right: 8px;
            color: #7F7F7F;
            font-size: 9.5px;
        }

        .pm-proc.is-mine .pm-proc-version {
            color: #9A7300;
        }

        .pm-proc-folio {
            padding-right: 34px;
            color: var(--c);
            font-size: 13px;
            font-weight: 800;
        }

        .pm-proc-name {
            font-size: 13px;
            line-height: 1.3;
        }

        .pm-proc-area {
            margin-top: 2px;
            color: #7F7F7F;
            font-size: 10.5px;
            font-weight: 700;
        }

        .pm-line {
            scroll-margin-top: 90px;
            border-radius: 10px;
        }

        .pm-line.is-target {
            animation: pm-target 2.2s ease-out;
        }

        @keyframes pm-target {
            0%, 40% {
                box-shadow: 0 0 0 4px var(--yellow);
                background: #FFFBEB;
            }

            100% {
                box-shadow: 0 0 0 4px transparent;
                background: transparent;
            }
        }

        .pm-proc-area em {
            color: #9A7300;
            font-style: normal;
        }

        .pm-proc.is-mine {
            border: 2px solid #D4A017;
            background: #FFF6D6;
        }

        .pm-proc.is-mine::after,
        .pm-proc.is-mine > .pm-proc-folio::before {
            background: #D4A017;
        }

        .pm-proc-empty {
            margin: 0;
            color: #9AA1A9;
            font-size: 11px;
            font-style: italic;
        }

        .pm-foot {
            margin: 28px 0 0;
            color: #9AA1A9;
            font-size: 13px;
            text-align: right;
        }
    </style>

    <script>
        const PM_VIEWS = @json(array_column($navPills, 'id'));

        function pmShow(view) {
            if (!PM_VIEWS.includes(view)) view = 'mapa';
            document.querySelectorAll('.pm-view').forEach(el => {
                el.hidden = el.dataset.view !== view;
            });
            document.querySelectorAll('.pm-pill').forEach(el => {
                el.classList.toggle('is-active', el.dataset.view === view);
            });
            if (location.hash.slice(1) !== view) {
                history.replaceState(null, '', view === 'mapa' ? location.pathname : '#' + view);
            }
        }

        function pmGoToProceso(view, id) {
            pmShow(view);
            const destino = document.getElementById('pm-proceso-' + id);
            if (!destino) return;
            document.querySelectorAll('.pm-line.is-target').forEach(el => el.classList.remove('is-target'));
            destino.scrollIntoView({ behavior: 'smooth', block: 'start' });
            void destino.offsetWidth;
            destino.classList.add('is-target');
            destino.addEventListener('animationend', () => destino.classList.remove('is-target'), { once: true });
        }

        pmShow(location.hash.slice(1) || 'mapa');
        window.addEventListener('hashchange', () => pmShow(location.hash.slice(1)));
    </script>
</x-app-layout>

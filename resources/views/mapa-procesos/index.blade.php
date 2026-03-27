<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-9xl mx-auto">

        <div class="mb-6 mt-5">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Mapa de Procesos</h1>
        </div>

        @if($estrategicos->isEmpty() && $apoyoAdm->isEmpty() && $apoyoOp->isEmpty() && collect($clave['construccion'])->isEmpty() && empty($clave['industrial']['columnas']) && collect($clave['otros'])->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow p-16 text-center">
            <p class="text-gray-400 text-sm">No hay procesos registrados en el sistema.</p>
        </div>
        @else

        <div class="sgc-map rounded-2xl overflow-hidden shadow-xl border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <div class="sgc-grid" style="min-width:900px;">

                    <div class="sgc-sidebar sgc-sidebar-left">
                        <span class="sgc-sidebar-text">Requisitos de Clientes</span>
                    </div>

                    <div class="sgc-bands">
                        <div class="sgc-band sgc-band--bordered"
                            style="--band:#0f172a; --sub:#1e293b; --area:#f0f4ff; --from:#1e3a8a; --to:#2563eb; --sep:#c7d7fd; --txt:#1e3a8a;">
                            <div class="sgc-band-label">
                                <span class="sgc-band-label-text">Procesos<br>Estratégicos</span>
                            </div>
                            <div class="sgc-band-body">
                                <div class="sgc-row">
                                    <div class="sgc-chips-wrap">
                                        @foreach($estrategicos as $pidx => $p)
                                        <button type="button"
                                            class="sgc-chip sgc-chip--mapcard sgc-chip--mapcard-lg {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                            title="{{ $p->nombre_elemento }}">
                                            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sgc-band sgc-band--bordered"
                            style="--band:#1e3a8a; --sub:#1e40af; --area:#eff6ff; --from:#1d4ed8; --to:#3b82f6; --sep:#bfdbfe; --txt:#1d4ed8;">
                            <div class="sgc-band-label">
                                <span class="sgc-band-label-text">Procesos<br>Clave</span>
                            </div>
                            <div class="sgc-band-body">

                                @if($clave['otros']->isNotEmpty())
                                <div class="sgc-row {{ ($clave['construccion']->isNotEmpty() || !empty($clave['industrial']['columnas'])) ? 'sgc-row--sep' : '' }}">
                                    <div class="sgc-chips-wrap">
                                        @foreach($clave['otros'] as $pidx => $p)
                                        <button type="button"
                                            class="sgc-chip sgc-chip--mapcard sgc-chip--mapcard-md {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                            title="{{ $p->nombre_elemento }}">
                                            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                @if($clave['construccion']->isNotEmpty())
                                <div class="sgc-division {{ !empty($clave['industrial']['columnas']) ? 'sgc-division--sep' : '' }}">
                                    <div class="sgc-div-label">
                                        <span class="sgc-div-label-text">División<br>Construcción</span>
                                    </div>
                                    <div class="sgc-div-body">
                                        <div class="sgc-subrow">
                                            <div class="sgc-unit-label">
                                                <span class="sgc-unit-label-text">Edificación, Vías Terrestres,<br>Construcción Hotelera (ED, VT)</span>
                                            </div>
                                            <div style="background:var(--area); padding:14px 18px; min-height:82px; flex:1; display:flex; align-items:center;">
                                                <div class="sgc-chips-wrap">
                                                    @foreach($clave['construccion'] as $pidx => $p)
                                                    <button type="button"
                                                        class="sgc-chip sgc-chip--construction {{ $pidx === 0 ? 'sgc-chip--construction-first' : '' }} {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                                        style="z-index:{{ $pidx + 1 }};"
                                                        onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                                        title="{{ $p->nombre_elemento }}">
                                                        <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                                        <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                                    </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if(!empty($clave['industrial']['columnas']))
                                <div class="sgc-division">
                                    <div class="sgc-div-label">
                                        <span class="sgc-div-label-text">División<br>Industrial</span>
                                    </div>

                                    <div class="sgc-div-body">
                                        <div class="sgc-industrial-layout">
                                            <div class="sgc-industrial-units">
                                                <div class="sgc-industrial-unit">
                                                    <span class="sgc-unit-label-text sgc-unit-label-text--industrial">Con-cretos (CON)</span>
                                                </div>
                                                <div class="sgc-industrial-unit">
                                                    <span class="sgc-unit-label-text sgc-unit-label-text--industrial">Agre-gados (AG)</span>
                                                </div>
                                            </div>

                                            <div class="sgc-industrial-track">
                                                @foreach($clave['industrial']['columnas'] as $colIdx => $col)
                                                @if($col['tipo'] === 'shared')
                                                @php($p = $col['proceso'])
                                                <div class="sgc-industrial-col sgc-industrial-col--shared">
                                                    <button type="button"
                                                        class="sgc-chip sgc-chip--industrial sgc-chip--industrial-shared {{ $colIdx === 0 ? 'sgc-chip--first' : '' }} {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                                        style="z-index:{{ $colIdx + 1 }};"
                                                        onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                                        title="{{ $p->nombre_elemento }}">
                                                        <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                                        <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                                    </button>
                                                </div>
                                                @else
                                                <div class="sgc-industrial-col sgc-industrial-col--split">
                                                    <div class="sgc-industrial-slot">
                                                        @if($col['con'])
                                                        @php($p = $col['con'])
                                                        <button type="button"
                                                            class="sgc-chip sgc-chip--industrial {{ $colIdx === 0 ? 'sgc-chip--first' : '' }} {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                                            style="z-index:{{ $colIdx + 1 }};"
                                                            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                                            title="{{ $p->nombre_elemento }}">
                                                            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                                            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                                        </button>
                                                        @endif
                                                    </div>

                                                    <div class="sgc-industrial-slot">
                                                        @if($col['ag'])
                                                        @php($p = $col['ag'])
                                                        <button type="button"
                                                            class="sgc-chip sgc-chip--industrial {{ $colIdx === 0 ? 'sgc-chip--first' : '' }} {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                                            style="z-index:{{ $colIdx + 1 }};"
                                                            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                                            title="{{ $p->nombre_elemento }}">
                                                            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                                            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </div>

                        <div class="sgc-band sgc-band--bordered"
                            style="--band:#14532d; --sub:#166534; --area:#f0fdf4; --from:#15803d; --to:#22c55e; --sep:#bbf7d0; --txt:#15803d;">
                            <div class="sgc-band-label">
                                <span class="sgc-band-label-text">Procesos<br>Administrativos de Apoyo</span>
                            </div>
                            <div class="sgc-band-body">
                                <div class="sgc-row">
                                    <div class="sgc-chips-wrap">
                                        @foreach($apoyoAdm as $pidx => $p)
                                        <button type="button"
                                            class="sgc-chip sgc-chip--mapcard sgc-chip--mapcard-md {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                            title="{{ $p->nombre_elemento }}">
                                            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sgc-band"
                            style="--band:#581c87; --sub:#6b21a8; --area:#faf5ff; --from:#7e22ce; --to:#a855f7; --sep:#e9d5ff; --txt:#7e22ce;">
                            <div class="sgc-band-label">
                                <span class="sgc-band-label-text">Procesos<br>Operativos de Apoyo</span>
                            </div>
                            <div class="sgc-band-body">
                                <div class="sgc-row">
                                    <div class="sgc-chips-wrap">
                                        @foreach($apoyoOp as $pidx => $p)
                                        <button type="button"
                                            class="sgc-chip sgc-chip--mapcard sgc-chip--mapcard-md {{ in_array($p->id_elemento, $procesosDestacados) ? 'sgc-chip--highlight' : '' }}"
                                            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                                            title="{{ $p->nombre_elemento }}">
                                            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                                            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="sgc-sidebar sgc-sidebar-right">
                        <span class="sgc-sidebar-text">Satisfacción del Cliente</span>
                    </div>

                </div>
            </div>
        </div>

        @endif
    </div>

    <div id="mapaModal" class="fixed inset-0 z-50 hidden modal-backdrop" aria-modal="true" role="dialog">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-panel bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden" style="max-height:88vh;">

                <div id="mmHeader" class="px-6 pt-6 pb-5 flex-shrink-0 modal-header">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span id="mmFolio" class="modal-folio-badge"></span>
                                <span id="mmTipo" class="modal-tipo-badge"></span>
                            </div>
                            <h2 id="mmNombre" class="text-base font-bold text-white leading-snug"></h2>
                        </div>
                        <button onclick="closeModal()" class="modal-close-btn">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div id="mmLoading" class="flex flex-col items-center justify-center py-14 gap-3">
                        <div class="modal-spinner"></div>
                        <span class="text-xs text-gray-400 font-medium">Cargando documentos…</span>
                    </div>
                    <div id="mmContent" class="hidden">
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">Documentos relacionados</p>
                        <div id="mmList" class="flex flex-col gap-2"></div>
                        <div id="mmEmpty" class="hidden text-center py-12">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                                <svg class="h-7 w-7 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">Sin documentos relacionados</p>
                            <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">Este proceso aún no tiene documentos asignados.</p>
                        </div>
                    </div>
                    <div id="mmError" class="hidden text-center py-12">
                        <p class="text-sm text-red-400 font-medium">Error al cargar. Intenta de nuevo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sgc-map {
            background: #fff;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .dark .sgc-map {
            background: #0f172a;
        }

        .sgc-grid {
            display: flex;
        }

        .sgc-sidebar {
            width: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 50%, #0f172a 100%);
        }

        .sgc-sidebar-text {
            writing-mode: vertical-rl;
            color: rgba(255, 255, 255, 0.75);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 20px 0;
            user-select: none;
        }

        .sgc-sidebar-left .sgc-sidebar-text {
            transform: rotate(180deg);
        }

        .sgc-sidebar-right .sgc-sidebar-text {
            transform: none;
        }

        .sgc-bands {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .sgc-band {
            display: flex;
        }

        .sgc-band--bordered {
            border-bottom: 2px solid rgba(0, 0, 0, 0.12);
        }

        .sgc-band-label {
            width: 32px;
            flex-shrink: 0;
            background: var(--band);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sgc-band-label-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: rgba(255, 255, 255, 0.9);
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: normal;
            text-align: center;
            padding: 12px 0;
            user-select: none;
        }

        .sgc-band-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .sgc-row {
            background: var(--area);
            padding: 16px 20px;
            min-height: 92px;
            flex: 1;
            display: flex;
            align-items: center;
        }

        .sgc-row--sep {
            border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        }

        .sgc-division {
            flex: 1;
            display: flex;
        }

        .sgc-division--sep {
            border-bottom: 2px solid rgba(0, 0, 0, 0.12);
        }

        .sgc-div-label {
            width: 24px;
            flex-shrink: 0;
            background: var(--sub);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sgc-div-label-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: rgb(255, 255, 255);
            font-size: 7.5px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: normal;
            text-align: center;
            padding: 8px 0;
            user-select: none;
        }

        .sgc-div-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .sgc-subrow {
            flex: 1;
            display: flex;
            align-items: stretch;
        }

        .sgc-subrow--sep {
            border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        }

        .sgc-unit-label {
            width: 22px;
            flex-shrink: 0;
            background: var(--band);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sgc-unit-label-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: rgb(255, 255, 255);
            font-size: 7px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 6px 0;
            user-select: none;
        }

        .sgc-subrow .sgc-chips-wrap {
            background: var(--area);
            padding: 14px 18px;
            min-height: 82px;
            flex: 1;
        }

        .sgc-chips-wrap {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .sgc-chips-wrap::-webkit-scrollbar {
            height: 3px;
        }

        .sgc-chips-wrap::-webkit-scrollbar-track {
            background: transparent;
        }

        .sgc-chips-wrap::-webkit-scrollbar-thumb {
            background: var(--sep);
            border-radius: 2px;
        }

        .sgc-chip {
            position: relative;
            min-width: 130px;
            max-width: 175px;
            min-height: 68px;
            padding: 10px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex-shrink: 0;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, var(--from) 0%, var(--to) 100%);
            clip-path: polygon(20px 0%, calc(100% - 20px) 0%, 100% 50%, calc(100% - 20px) 100%, 20px 100%, 0% 50%);
            transition: filter 0.18s ease, transform 0.16s ease, box-shadow 0.18s ease;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15);
            overflow: hidden;
        }

        .sgc-chip--first {
            clip-path: polygon(0% 0%, calc(100% - 20px) 0%, 100% 50%, calc(100% - 20px) 100%, 0% 100%);
        }

        .sgc-chip:hover {
            filter: brightness(1.18) saturate(1.15) drop-shadow(0 4px 12px rgba(0, 0, 0, 0.25));
            transform: scaleY(1.07) translateY(-1px);
            z-index: 9999 !important;
        }

        .sgc-chip:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.9);
            outline-offset: -3px;
        }

        .sgc-chip:active {
            transform: scaleY(1.03) translateY(0px);
        }

        .sgc-chip-folio {
            font-size: 10px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.85);
            display: block;
            line-height: 1;
            letter-spacing: 0.05em;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            width: 100%;
        }

        .sgc-chip-name {
            font-size: 9px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.97);
            display: block;
            margin-top: 4px;
            line-height: 1.35;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            width: 100%;
        }

        .sgc-industrial-layout {
            flex: 1;
            display: flex;
            min-height: 164px;
        }

        .sgc-industrial-units {
            width: 22px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: #7e963f;
            position: relative;
        }

        .sgc-industrial-units::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            border-top: 2px solid rgba(0, 0, 0, 0.35);
            transform: translateY(-1px);
        }

        .sgc-industrial-unit {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sgc-unit-label-text--industrial {
            color: #ffffff;
        }

        .sgc-industrial-track {
            position: relative;
            flex: 1;
            display: flex;
            align-items: stretch;
            gap: 10px;
            overflow-x: auto;
            background: #d9ddc8;
            padding: 14px 18px;
            min-height: 164px;
        }

        .sgc-industrial-track::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            border-top: 2px solid rgba(0, 0, 0, 0.35);
            transform: translateY(-1px);
            pointer-events: none;
            z-index: 0;
        }

        .sgc-industrial-track::-webkit-scrollbar {
            height: 4px;
        }

        .sgc-industrial-track::-webkit-scrollbar-track {
            background: transparent;
        }

        .sgc-industrial-track::-webkit-scrollbar-thumb {
            background: #aebc7a;
            border-radius: 999px;
        }

        .sgc-industrial-col {
            position: relative;
            z-index: 1;
            flex: 0 0 auto;
            min-height: 164px;
        }

        .sgc-industrial-col--shared {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sgc-industrial-col--split {
            display: grid;
            grid-template-rows: 1fr 1fr;
            min-width: max-content;
        }

        .sgc-industrial-slot {
            min-height: 82px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sgc-chip--industrial {
            background: linear-gradient(180deg, #dce9b6 0%, #bfd181 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.55),
                0 2px 6px rgba(0, 0, 0, 0.18);
        }

        .sgc-chip--industrial .sgc-chip-folio {
            color: #111827;
            font-size: 11px;
            font-weight: 900;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .sgc-chip--industrial .sgc-chip-name {
            color: #111827;
            font-size: 10px;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .sgc-chip--industrial-shared {
            min-height: 150px;
        }

        .dark .sgc-chip {
            filter: brightness(0.85) saturate(0.9);
        }

        .dark .sgc-chip:hover {
            filter: brightness(1.1) saturate(1.1);
        }

        .dark .sgc-chip--industrial {
            filter: none;
        }

        .modal-backdrop {
            background: rgba(10, 15, 30, 0.65);
            backdrop-filter: blur(8px);
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
        }

        .modal-folio-badge {
            background: rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.1em;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .modal-tipo-badge {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.65);
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .modal-close-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }

        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .modal-spinner {
            width: 28px;
            height: 28px;
            border: 3px solid #e2e8f0;
            border-top-color: #3b82f6;
            border-radius: 50%;
        }

        .modal-ver-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #2563eb;
            text-decoration: none;
            transition: color 0.15s, gap 0.15s;
        }

        .modal-ver-btn:hover {
            color: #1d4ed8;
            gap: 8px;
        }

        .dark .modal-ver-btn {
            color: #60a5fa;
        }

        .s-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .s-green {
            background: #dcfce7;
            color: #15803d;
        }

        .s-yellow {
            background: #fef9c3;
            color: #92400e;
        }

        .s-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .s-gray {
            background: #f1f5f9;
            color: #475569;
        }

        .s-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .sgc-chip--mapcard {
            clip-path: none;
            border: 1px solid #c8c1a4;
            border-radius: 0;
            background:
                linear-gradient(180deg, #f6f0da 0%, #ece3c2 100%);
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.65),
                inset -2px -2px 0 rgba(0, 0, 0, 0.06),
                0 3px 6px rgba(0, 0, 0, 0.28);
            padding: 10px 14px;
            min-width: 170px;
            max-width: 220px;
            min-height: 74px;
            justify-content: center;
            transform: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
        }

        .sgc-chip--mapcard:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.72),
                inset -2px -2px 0 rgba(0, 0, 0, 0.05),
                0 5px 10px rgba(0, 0, 0, 0.3);
        }

        .sgc-chip--mapcard:active {
            transform: translateY(0);
        }

        .sgc-chip--mapcard .sgc-chip-folio {
            color: #111111;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: 0.01em;
            text-transform: uppercase;
            margin-bottom: 4px;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .sgc-chip--mapcard .sgc-chip-name {
            color: #111111;
            font-size: 10px;
            font-weight: 900;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: 0.01em;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .sgc-chip--mapcard-lg {
            min-width: 205px;
            max-width: 245px;
            min-height: 90px;
            padding: 12px 16px;
        }

        .sgc-chip--mapcard-lg .sgc-chip-folio {
            font-size: 13px;
        }

        .sgc-chip--mapcard-lg .sgc-chip-name {
            font-size: 11px;
            line-height: 1.18;
        }

        .sgc-chip--mapcard-md {
            min-width: 150px;
            max-width: 205px;
            min-height: 82px;
        }

        .sgc-chip--mapcard-md .sgc-chip-name {
            font-size: 10px;
        }

        .dark .sgc-chip--mapcard {
            background:
                linear-gradient(180deg, #efe6c8 0%, #dfd2aa 100%);
            border-color: #b6aa82;
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.45),
                inset -2px -2px 0 rgba(0, 0, 0, 0.08),
                0 3px 8px rgba(0, 0, 0, 0.4);
        }

        .dark .sgc-chip--mapcard .sgc-chip-folio,
        .dark .sgc-chip--mapcard .sgc-chip-name {
            color: #111111;
        }

        .sgc-chip--construction {
            min-width: 150px;
            max-width: 190px;
            min-height: 72px;
            padding: 10px 22px;
            background: linear-gradient(180deg, #6796d1 0%, #4f7fbd 100%);
            clip-path: polygon(18px 0%, calc(100% - 18px) 0%, 100% 50%, calc(100% - 18px) 100%, 18px 100%, 0% 50%);
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.28),
                inset -2px -2px 0 rgba(0, 0, 0, 0.08),
                0 3px 6px rgba(0, 0, 0, 0.24);
            transition: transform 0.16s ease, filter 0.16s ease, box-shadow 0.16s ease;
        }

        .sgc-chip--construction-first {
            clip-path: polygon(0% 0%, calc(100% - 18px) 0%, 100% 50%, calc(100% - 18px) 100%, 0% 100%);
        }

        .sgc-chip--construction:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, 0.34),
                inset -2px -2px 0 rgba(0, 0, 0, 0.07),
                0 5px 10px rgba(0, 0, 0, 0.28);
            z-index: 9999 !important;
        }

        .sgc-chip--construction:active {
            transform: translateY(0);
        }

        .sgc-chip--construction .sgc-chip-folio {
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 0.01em;
            text-transform: uppercase;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .sgc-chip--construction .sgc-chip-name {
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            line-height: 1.15;
            text-transform: uppercase;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .sgc-chip--highlight {
            position: relative !important;
            z-index: 8 !important;
            border: 1.5px solid rgba(251, 146, 60, 0.7) !important;
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.15), 0 6px 16px rgba(251, 146, 60, 0.35), 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        }

        .sgc-chip--highlight::after {
            content: '';
            pointer-events: none;
        }

        /* Para mapcard (sin clip-path) también borde */
        .sgc-chip--mapcard.sgc-chip--highlight {
            border: 1.5px solid rgba(251, 146, 60, 0.7) !important;
            border-radius: 4px;
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.12), 0 6px 16px rgba(251, 146, 60, 0.3), 0 2px 4px rgba(0, 0, 0, 0.15) !important;
        }

        .sgc-chip--construction.sgc-chip--highlight {
            border: 1.5px solid rgba(251, 146, 60, 0.65);
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.12), 0 6px 16px rgba(251, 146, 60, 0.32), 0 2px 4px rgba(0, 0, 0, 0.18) !important;
        }

        .sgc-chip--industrial.sgc-chip--highlight {
            border: 1.5px solid rgba(251, 146, 60, 0.65);
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.12), 0 6px 16px rgba(251, 146, 60, 0.3), 0 2px 4px rgba(0, 0, 0, 0.15) !important;
        }

        .dark .sgc-chip--construction {
            background: linear-gradient(180deg, #5e88bd 0%, #456fa7 100%);
        }

        @media (max-width: 1024px) {
            .sgc-grid {
                min-width: 700px !important;
            }

            .sgc-sidebar {
                width: 24px;
            }

            .sgc-band-label {
                width: 24px;
            }

            .sgc-band-label-text {
                font-size: 7px;
                padding: 8px 0;
            }

            .sgc-div-label {
                width: 20px;
            }

            .sgc-div-label-text {
                font-size: 6.5px;
                padding: 6px 0;
            }

            .sgc-row {
                padding: 12px 16px;
                min-height: 80px;
            }

            .sgc-chips-wrap {
                gap: 6px;
            }

            .sgc-chip {
                min-width: 110px;
                max-width: 150px;
                min-height: 60px;
                padding: 8px 18px;
            }

            .sgc-chip-folio {
                font-size: 9px;
            }

            .sgc-chip-name {
                font-size: 8px;
                margin-top: 3px;
            }

            .sgc-unit-label {
                width: 18px;
            }

            .sgc-unit-label-text {
                font-size: 6px;
                padding: 4px 0;
            }

            .sgc-subrow .sgc-chips-wrap {
                padding: 12px 16px;
                min-height: 72px;
            }

            .sgc-industrial-layout {
                min-height: 140px;
            }

            .sgc-industrial-units {
                width: 18px;
            }

            .sgc-industrial-track {
                min-height: 140px;
                padding: 12px 16px;
                gap: 8px;
            }

            .sgc-industrial-col {
                min-height: 140px;
            }

            .sgc-chip--industrial {
                min-width: 100px;
                max-width: 130px;
                min-height: 55px;
                padding: 8px 14px;
            }

            .sgc-chip--industrial .sgc-chip-folio {
                font-size: 10px;
            }

            .sgc-chip--industrial .sgc-chip-name {
                font-size: 9px;
            }

            .sgc-chip--industrial-shared {
                min-height: 130px;
            }

            .sgc-chip--mapcard-lg {
                min-width: 170px;
                max-width: 210px;
                min-height: 78px;
                padding: 10px 12px;
            }

            .sgc-chip--mapcard-lg .sgc-chip-folio {
                font-size: 11px;
            }

            .sgc-chip--mapcard-lg .sgc-chip-name {
                font-size: 9px;
            }

            .sgc-chip--mapcard-md {
                min-width: 130px;
                max-width: 170px;
                min-height: 72px;
            }
        }

        @media (max-width: 768px) {
            .sgc-grid {
                min-width: 100% !important;
            }

            .sgc-sidebar {
                width: 0;
                padding: 0;
                display: none;
            }

            .sgc-sidebar-text {
                display: none;
            }

            .sgc-band {
                flex-direction: column;
            }

            .sgc-band-label {
                width: 100%;
                height: 32px;
                writing-mode: horizontal-tb;
            }

            .sgc-band-label-text {
                writing-mode: horizontal-tb;
                transform: none;
                font-size: 11px;
                padding: 6px 0;
                letter-spacing: 0.08em;
            }

            .sgc-band-body {
                width: 100%;
            }

            .sgc-div-label {
                width: 100%;
                height: 28px;
                writing-mode: horizontal-tb;
            }

            .sgc-div-label-text {
                writing-mode: horizontal-tb;
                transform: none;
                font-size: 9px;
                padding: 4px 0;
            }

            .sgc-division {
                flex-direction: column;
            }

            .sgc-row {
                padding: 10px 12px;
                min-height: 70px;
            }

            .sgc-chips-wrap {
                gap: 4px;
                padding-bottom: 1px;
            }

            .sgc-chip {
                min-width: 90px;
                max-width: 120px;
                min-height: 52px;
                padding: 6px 12px;
            }

            .sgc-chip-folio {
                font-size: 8px;
            }

            .sgc-chip-name {
                font-size: 7px;
                margin-top: 2px;
                line-height: 1.2;
            }

            .sgc-unit-label {
                width: 100%;
                height: 24px;
                writing-mode: horizontal-tb;
            }

            .sgc-unit-label-text {
                writing-mode: horizontal-tb;
                transform: none;
                font-size: 7px;
                padding: 2px 0;
                white-space: normal;
                white-space: pre-wrap;
            }

            .sgc-subrow {
                flex-direction: column;
            }

            .sgc-subrow .sgc-chips-wrap {
                padding: 10px 12px;
                min-height: 70px;
            }

            .sgc-industrial-layout {
                flex-direction: column;
                min-height: auto;
            }

            .sgc-industrial-units {
                width: 100%;
                height: 48px;
                flex-direction: row;
            }

            .sgc-industrial-units::after {
                content: '';
                position: absolute;
                left: 50%;
                right: auto;
                top: auto;
                bottom: 0;
                border-top: none;
                border-left: 2px solid rgba(0, 0, 0, 0.35);
                transform: translateX(-1px);
                width: 0;
                height: 100%;
            }

            .sgc-industrial-unit {
                flex: 1;
                border-right: 2px solid rgba(0, 0, 0, 0.35);
            }

            .sgc-industrial-unit:last-child {
                border-right: none;
            }

            .sgc-industrial-track {
                width: 100%;
                min-height: auto;
                flex-direction: column;
                padding: 10px 12px;
                gap: 6px;
                background: #d9ddc8;
            }

            .sgc-industrial-track::before {
                display: none;
            }

            .sgc-industrial-col {
                width: 100%;
                min-height: auto;
                flex: 1;
            }

            .sgc-industrial-col--shared {
                min-height: 55px;
            }

            .sgc-industrial-col--split {
                display: flex;
                flex-direction: row;
                grid-template-rows: none;
                min-width: auto;
                gap: 6px;
            }

            .sgc-industrial-slot {
                flex: 1;
                min-height: 55px;
                border: none;
                padding: 0;
                border-right: 2px solid rgba(0, 0, 0, 0.35);
            }

            .sgc-industrial-slot:last-child {
                border-right: none;
            }

            .sgc-chip--industrial {
                min-width: 80px;
                max-width: 110px;
                min-height: 48px;
                padding: 6px 10px;
                background: linear-gradient(180deg, #dce9b6 0%, #bfd181 100%);
            }

            .sgc-chip--industrial .sgc-chip-folio {
                font-size: 9px;
            }

            .sgc-chip--industrial .sgc-chip-name {
                font-size: 7px;
                font-weight: 700;
            }

            .sgc-chip--industrial-shared {
                min-height: 50px;
            }

            .sgc-chip--mapcard {
                min-width: 130px;
                max-width: 170px;
                min-height: 68px;
                padding: 8px 10px;
            }

            .sgc-chip--mapcard .sgc-chip-folio {
                font-size: 10px;
            }

            .sgc-chip--mapcard .sgc-chip-name {
                font-size: 8px;
            }

            .sgc-chip--mapcard-lg {
                min-width: 130px;
                max-width: 170px;
                min-height: 68px;
                padding: 8px 10px;
            }

            .sgc-chip--mapcard-lg .sgc-chip-folio {
                font-size: 10px;
            }

            .sgc-chip--mapcard-lg .sgc-chip-name {
                font-size: 8px;
            }

            .sgc-chip--mapcard-md {
                min-width: 110px;
                max-width: 150px;
                min-height: 62px;
            }

            .sgc-chip--mapcard-md .sgc-chip-name {
                font-size: 8px;
            }
        }

        @media (max-width: 480px) {
            .sgc-band-label-text {
                font-size: 9px;
            }

            .sgc-div-label-text {
                font-size: 8px;
            }

            .sgc-row {
                padding: 8px 10px;
                min-height: 60px;
            }

            .sgc-chips-wrap {
                gap: 3px;
            }

            .sgc-chip {
                min-width: 75px;
                max-width: 100px;
                min-height: 45px;
                padding: 5px 10px;
            }

            .sgc-chip-folio {
                font-size: 7px;
            }

            .sgc-chip-name {
                font-size: 6px;
            }

            .sgc-industrial-track {
                padding: 8px 10px;
                gap: 4px;
            }

            .sgc-industrial-col--shared {
                min-height: 45px;
            }

            .sgc-industrial-slot {
                min-height: 45px;
            }

            .sgc-chip--industrial {
                min-width: 70px;
                max-width: 95px;
                min-height: 42px;
                padding: 5px 8px;
            }

            .sgc-chip--industrial .sgc-chip-folio {
                font-size: 8px;
            }

            .sgc-chip--industrial .sgc-chip-name {
                font-size: 6px;
            }

            .sgc-chip--mapcard {
                min-width: 100px;
                max-width: 130px;
                min-height: 55px;
                padding: 6px 8px;
            }

            .sgc-chip--mapcard-lg {
                min-width: 100px;
                max-width: 130px;
                min-height: 55px;
            }

            .sgc-chip--mapcard-md {
                min-width: 90px;
                max-width: 120px;
                min-height: 50px;
            }
        }
    </style>

    <script>
        const _modal = document.getElementById('mapaModal');

        function openModal(id, nombre, folio, url) {
            document.getElementById('mmFolio').textContent = folio;
            document.getElementById('mmNombre').textContent = nombre;
            document.getElementById('mmTipo').textContent = '';
            document.getElementById('mmLoading').classList.remove('hidden');
            document.getElementById('mmContent').classList.add('hidden');
            document.getElementById('mmError').classList.add('hidden');
            document.getElementById('mmList').innerHTML = '';
            document.getElementById('mmEmpty').classList.add('hidden');
            _modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            fetch(`/mapa-procesos/${id}/procedimientos`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => {
                    if (!r.ok) throw r;
                    return r.json();
                })
                .then(data => {
                    document.getElementById('mmTipo').textContent = data.proceso.tipo || '';
                    renderList(data.relacionados || []);
                    document.getElementById('mmLoading').classList.add('hidden');
                    document.getElementById('mmContent').classList.remove('hidden');
                })
                .catch(() => {
                    document.getElementById('mmLoading').classList.add('hidden');
                    document.getElementById('mmError').classList.remove('hidden');
                });
        }

        function closeModal() {
            _modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Click afuera: verificar que el click NO fue dentro del panel
        _modal.addEventListener('click', function(e) {
            const panel = _modal.querySelector('.modal-panel');
            if (panel && !panel.contains(e.target)) {
                closeModal();
            }
        });

        // ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !_modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        function renderList(items) {
            const list = document.getElementById('mmList');
            const empty = document.getElementById('mmEmpty');
            if (!items.length) {
                empty.classList.remove('hidden');
                return;
            }
            list.innerHTML = items.map(item => `
        <a href="${esc(item.url)}"
           class="doc-item flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-blue-200 dark:hover:border-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
            <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-bold text-gray-400">${esc(item.folio)}</span>
                    <span class="text-xs text-gray-300 dark:text-gray-600">v${esc(String(item.version))}</span>
                </div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors">${esc(item.nombre)}</p>
                <span class="text-xs text-gray-400">${esc(item.tipo)}</span>
            </div>
            <span class="flex-shrink-0 s-badge ${badgeClass(item.status)}">${esc(item.status)}</span>
        </a>
    `).join('');
        }

        function badgeClass(s) {
            return {
                'Publicado': 's-green',
                'En Revisión': 's-yellow',
                'Rechazado': 's-red',
                'Borrador': 's-gray'
            } [s] || 's-blue';
        }

        function esc(v) {
            if (v == null) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</x-app-layout>
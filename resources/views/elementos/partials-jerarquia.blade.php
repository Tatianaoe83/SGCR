@php
    $rol = $jerarquia['rol'] ?? 'otro';
    $proceso = $jerarquia['proceso'] ?? null;
    $procedimiento = $jerarquia['procedimiento'] ?? null;
    $arbol = $jerarquia['arbol'] ?? collect();
    $evidenciasDeEste = $jerarquia['evidenciasDeEste'] ?? collect();
    $aquiId = (int) $elemento->id_elemento;
    $pasos = [];
    if ($proceso) {
        $pasos[] = ['key' => 'proceso', 'label' => 'Proceso', 'nodo' => $proceso, 'activo' => $rol === 'proceso'];
    }
    if ($procedimiento && $rol !== 'proceso') {
        $pasos[] = ['key' => 'procedimiento', 'label' => 'Procedimiento', 'nodo' => $procedimiento, 'activo' => $rol === 'procedimiento'];
    }
    if ($rol === 'evidencia') {
        $pasos[] = ['key' => 'evidencia', 'label' => 'Evidencia', 'nodo' => $elemento, 'activo' => true];
    }
    $totalPasos = count($pasos);
    $mostrarEvidenciasComoPaso = $rol === 'procedimiento';
@endphp

<div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm ring-1 ring-[#021D49]/15">
    <div class="px-5 py-3 bg-[#021D49] text-white flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold tracking-wide">
            Ubicación en el SGC
        </h3>
        <span class="inline-flex items-center rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium text-white">
            {{ $elemento->etiquetaJerarquia() }}
        </span>
    </div>

    @if($totalPasos > 0)
        <nav class="px-5 py-4" aria-label="Jerarquía del documento">
            <ol class="space-y-0">
                @foreach($pasos as $i => $paso)
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                {{ $paso['activo']
                                    ? 'bg-brand-navy text-white'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $i + 1 }}
                            </span>
                            @if(!$loop->last || $mostrarEvidenciasComoPaso)
                                <span class="w-px flex-1 min-h-[1.25rem] bg-gray-200 dark:bg-gray-600"></span>
                            @endif
                        </div>
                        <div class="{{ $loop->last && !$mostrarEvidenciasComoPaso ? 'pb-0' : 'pb-4' }} min-w-0 flex-1">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                {{ $paso['label'] }}
                            </p>
                            @include('elementos.partials-jerarquia-nodo', ['nodo' => $paso['nodo'], 'aquiId' => $aquiId])
                        </div>
                    </li>
                @endforeach

                @if($mostrarEvidenciasComoPaso)
                    <li class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $totalPasos + 1 }}
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                    Evidencias ligadas
                                </p>
                                <span class="text-xs text-gray-400">{{ $evidenciasDeEste->count() }}</span>
                            </div>
                            @if($evidenciasDeEste->isEmpty())
                                <p class="text-sm text-gray-500">Este procedimiento aún no tiene evidencias ligadas.</p>
                            @else
                                <div class="max-h-64 overflow-y-auto pr-0.5">
                                    @include('elementos.partials-jerarquia-evidencia-tabla', ['evidencias' => $evidenciasDeEste])
                                </div>
                            @endif
                        </div>
                    </li>
                @endif
            </ol>
        </nav>
    @endif

    @if($rol === 'proceso' && $arbol->isNotEmpty())
        <div class="border-t border-gray-200 dark:border-gray-700">
            <div class="px-5 py-3 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    Procedimientos de este proceso
                </p>
                <span class="text-xs text-gray-400">{{ $arbol->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700/80">
                @foreach($arbol as $rama)
                    @php $proc = $rama['elemento']; $evs = $rama['evidencias']; @endphp
                    <li class="px-5 py-3">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <a href="{{ route('elementos.show', $proc->id_elemento) }}"
                               class="font-medium text-brand-navy hover:underline dark:text-blue-300">
                                {{ $proc->nombre_elemento }}
                            </a>
                            @if($proc->folio_elemento)
                                <span class="font-mono text-xs text-gray-500">{{ $proc->folio_elemento }}</span>
                            @endif
                            <span class="text-xs text-gray-400">
                                {{ $evs->count() }} {{ $evs->count() === 1 ? 'evidencia' : 'evidencias' }}
                            </span>
                        </div>
                        @if($evs->isNotEmpty())
                            <div class="mt-2 max-h-48 overflow-y-auto">
                                @include('elementos.partials-jerarquia-evidencia-tabla', ['evidencias' => $evs])
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($rol === 'otro' && $arbol->isNotEmpty())
        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-4">
            <p class="text-sm font-semibold mb-2">Documentos que pertenecen a este</p>
            <ul class="space-y-1">
                @foreach($arbol as $rama)
                    <li>
                        <a href="{{ route('elementos.show', $rama['elemento']->id_elemento) }}"
                           class="text-sm text-brand-navy hover:underline">
                            {{ $rama['elemento']->nombre_elemento }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

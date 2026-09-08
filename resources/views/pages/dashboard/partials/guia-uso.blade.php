{{--
    Tips breves de Bob.
    variant: modal | header | compact (chips + guía expandible) | sidebar
--}}
@php
    $variant = $variant ?? 'modal';
    $isCompact = $variant === 'compact';
    $isHeader = $variant === 'header';
    $isSidebar = $variant === 'sidebar';
    $isModal = !$isHeader && !$isSidebar && !$isCompact;

    $quickChips = [
        ['label' => 'Mis procedimientos', 'query' => 'mis procedimientos'],
        ['label' => 'Por folio', 'query' => 'PAA08-PR05'],
        ['label' => 'Objetivo y alcance', 'query' => 'objetivo y alcance'],
        ['label' => 'Directorio', 'query' => 'directorio'],
        ['label' => 'Riesgos', 'query' => 'riesgos'],
        ['label' => 'Evidencias', 'query' => 'evidencias'],
        ['label' => 'Procedimientos de Calidad', 'query' => 'procedimientos de Calidad'],
    ];
@endphp

@if ($isCompact)
    <div class="bob-tips-chips" role="list" aria-label="Ejemplos de consulta">
        @foreach ($quickChips as $chip)
            <button type="button" class="bob-tip-chip" data-chip="{{ $chip['query'] }}" role="listitem">
                {{ $chip['label'] }}
            </button>
        @endforeach
    </div>

    <p class="bob-tips-hint-bar">
        Toca un ejemplo para enviarlo · Usa el <strong class="text-slate-700 dark:text-slate-200">folio</strong> cuando lo tengas (ej. PAA08-PR05)
    </p>

    <div class="guia-header-grid">
        <div class="guia-card guia-card--ask">
            <div class="guia-card-head">
                <span class="guia-card-icon" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <span class="guia-card-title">Cómo preguntar</span>
            </div>
            <ul class="guia-card-list">
                <li><strong>Documento</strong> <span class="guia-tag">PAA08-PR05</span> o el tema</li>
                <li><strong>Detalle</strong> objetivo, alcance, responsable</li>
                <li><strong>Listados</strong> mis procedimientos, por área</li>
                <li><strong>Personas</strong> quién ocupa un puesto</li>
            </ul>
        </div>

        <div class="guia-card guia-card--help">
            <div class="guia-card-head">
                <span class="guia-card-icon" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </span>
                <span class="guia-card-title">Si te pierdes</span>
            </div>
            <ul class="guia-card-list">
                <li><span class="guia-tag">ese no es</span> suelta el doc incorrecto</li>
                <li><span class="guia-tag">me perdí</span> <span class="guia-tag">volvamos</span></li>
                <li><span class="guia-tag">olvida</span> empieza de cero</li>
                <li><span class="guia-tag">sí</span> confirma · usa los botones</li>
            </ul>
        </div>

        <div class="guia-card guia-card--avoid">
            <div class="guia-card-head">
                <span class="guia-card-icon" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <span class="guia-card-title">Qué evitar</span>
            </div>
            <ul class="guia-card-list">
                <li>Temas fuera del SGC</li>
                <li>Varias preguntas a la vez</li>
                <li>Pedir redactar o firmar</li>
            </ul>
        </div>
    </div>
@else

<div id="guiaContenido" @class([
    'leading-relaxed min-w-0',
    'h-full min-h-0 overflow-y-auto px-3 sm:px-6 py-4 sm:py-5 text-sm' => $isModal,
    'px-3 py-3 pb-4 break-words text-sm' => $isSidebar,
    'text-[13px]' => $isHeader,
])>

    @if ($isModal)
        <p class="text-[13px] text-slate-700 dark:text-slate-300 mb-4">
            Bob responde solo con el <strong class="text-slate-900 dark:text-slate-100">SGC</strong>:
            procedimientos publicados y directorio.
            Lo más seguro es el <strong class="text-slate-900 dark:text-slate-100">folio</strong>
            (ej. <span class="font-mono text-[12px]">PAA08-PR05</span>);
            si no lo tienes, di el tema y usa los botones.
        </p>
    @endif

    <div @class([
        'grid gap-4 min-w-0',
        'md:grid-cols-2 md:gap-5' => $isModal,
        'grid-cols-1 gap-2.5' => $isSidebar,
        'guia-header-grid gap-3' => $isHeader,
    ])>

        {{-- Cómo preguntar --}}
        <div @class(['space-y-3 min-w-0', 'xl:col-span-1' => $isHeader])>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Cómo preguntar
            </div>
            <ul @class([
                'space-y-2.5 min-w-0',
                'text-[13px]' => $isModal || $isHeader,
                'text-[11px] space-y-2' => $isSidebar,
            ])>
                <li class="flex gap-2 min-w-0">
                    <span class="shrink-0 mt-0.5 h-5 w-5 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-100 text-[11px] font-bold flex items-center justify-center">1</span>
                    <div class="min-w-0">
                        <span class="font-semibold text-slate-900 dark:text-slate-100">Documento</span>
                        <span class="block font-mono text-[12px] text-slate-600 dark:text-slate-300 mt-0.5 break-all">PAA08-PR05 · necesito algo de pagos</span>
                    </div>
                </li>
                <li class="flex gap-2 min-w-0">
                    <span class="shrink-0 mt-0.5 h-5 w-5 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-100 text-[11px] font-bold flex items-center justify-center">2</span>
                    <div class="min-w-0">
                        <span class="font-semibold text-slate-900 dark:text-slate-100">Detalle</span>
                        <span class="block font-mono text-[12px] text-slate-600 dark:text-slate-300 mt-0.5 break-words">objetivo · alcance · en bullets · responsable</span>
                    </div>
                </li>
                <li class="flex gap-2 min-w-0">
                    <span class="shrink-0 mt-0.5 h-5 w-5 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-100 text-[11px] font-bold flex items-center justify-center">3</span>
                    <div class="min-w-0">
                        <span class="font-semibold text-slate-900 dark:text-slate-100">Listados</span>
                        <span class="block font-mono text-[12px] text-slate-600 dark:text-slate-300 mt-0.5 break-words">mis procedimientos · procedimientos de Calidad</span>
                    </div>
                </li>
                <li class="flex gap-2 min-w-0">
                    <span class="shrink-0 mt-0.5 h-5 w-5 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-100 text-[11px] font-bold flex items-center justify-center">4</span>
                    <div class="min-w-0">
                        <span class="font-semibold text-slate-900 dark:text-slate-100">Personas</span>
                        <span class="block font-mono text-[12px] text-slate-600 dark:text-slate-300 mt-0.5 break-words">quién ocupa Coordinador de TI · lista los directores</span>
                    </div>
                </li>
            </ul>

            @if ($isHeader)
                <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white/80 dark:bg-slate-800/80 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Atajos</div>
                    <p class="text-[12px] text-slate-700 dark:text-slate-200 leading-relaxed">
                        <span class="font-mono text-slate-900 dark:text-slate-100">sí</span> confirma ·
                        toca los <strong class="text-slate-900 dark:text-slate-100">botones</strong> bajo la respuesta
                    </p>
                </div>
            @endif
        </div>

        {{-- Recuperación --}}
        <div @class(['space-y-3 min-w-0', 'xl:col-span-1' => $isHeader])>
            <div @class([
                'rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 space-y-2 min-w-0',
                'p-3' => !$isSidebar,
                'p-2.5' => $isSidebar,
                'bg-white/80 dark:bg-slate-800/80' => $isHeader,
            ])>
                <div @class([
                    'font-semibold text-slate-900 dark:text-slate-100',
                    'text-[13px]' => $isModal || $isHeader,
                    'text-[12px]' => $isSidebar,
                ])>Si te pierdes o Bob se equivoca</div>
                <ul @class([
                    'space-y-1.5 text-slate-700 dark:text-slate-200 min-w-0',
                    'text-[12px]' => $isModal || $isHeader,
                    'text-[11px] leading-snug' => $isSidebar,
                ])>
                    <li><span class="font-mono text-slate-900 dark:text-slate-100">ese no es</span> — suelta el documento incorrecto</li>
                    <li><span class="font-mono text-slate-900 dark:text-slate-100">me perdí</span> / <span class="font-mono text-slate-900 dark:text-slate-100">volvamos</span> — retoma el tema</li>
                    <li><span class="font-mono text-slate-900 dark:text-slate-100">olvida</span> — empieza de cero</li>
                    <li>Di el <strong class="text-slate-900 dark:text-slate-100">folio</strong> (ej. <span class="font-mono">abre PAA08-PR05</span>) o el tema</li>
                </ul>
                @if (!$isHeader)
                    <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed pt-1 border-t border-slate-200 dark:border-slate-600">
                        Una sola idea por mensaje. Si cambia de documento solo, escribe
                        <span class="font-mono text-slate-900 dark:text-slate-100">ese no es</span>
                        y vuelve a pedir el tuyo.
                    </p>
                @endif
            </div>

            @if (!$isHeader)
                <div @class([
                    'rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-800',
                    'p-3' => !$isSidebar,
                    'p-2.5' => $isSidebar,
                ])>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Atajos</div>
                    <p @class([
                        'text-slate-700 dark:text-slate-200 leading-relaxed',
                        'text-[12px]' => $isModal,
                        'text-[11px] leading-snug' => $isSidebar,
                    ])>
                        <span class="font-mono text-slate-900 dark:text-slate-100">sí</span> confirma ·
                        toca los <strong class="text-slate-900 dark:text-slate-100">botones</strong> bajo la respuesta
                    </p>
                </div>
            @endif
        </div>

        {{-- Qué evitar --}}
        <div @class(['min-w-0', 'xl:col-span-1' => $isHeader])>
            <div @class([
                'rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-slate-800 min-w-0',
                'p-3' => !$isSidebar,
                'p-2.5' => $isSidebar,
                'h-full' => $isHeader,
            ])>
                <div @class([
                    'font-semibold text-rose-800 dark:text-rose-300 mb-1.5',
                    'text-[13px]' => $isModal || $isHeader,
                    'text-[12px] mb-1' => $isSidebar,
                ])>Qué evitar</div>
                <ul @class([
                    'space-y-1 list-disc pl-4 marker:text-rose-500 dark:marker:text-rose-400 text-slate-700 dark:text-slate-200',
                    'text-[12px]' => $isModal || $isHeader,
                    'text-[11px] leading-snug' => $isSidebar,
                ])>
                    <li>Sueldos, clima, opiniones o cosas fuera del SGC</li>
                    <li>Varias preguntas en el mismo mensaje</li>
                    <li>Pedir que redacte o firme documentos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endif

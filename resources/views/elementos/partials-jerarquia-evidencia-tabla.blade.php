@php
    $evidencias = $evidencias ?? collect();
    $conEncabezado = $conEncabezado ?? true;
@endphp
@if($evidencias->isNotEmpty())
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">
        @if($conEncabezado)
            <div class="hidden sm:grid grid-cols-[8.5rem_minmax(0,1fr)_auto] gap-3 px-3 py-2 bg-gray-50 dark:bg-gray-900/50 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                <span>Folio</span>
                <span>Nombre</span>
                <span class="text-right">Acciones</span>
            </div>
        @endif
        @foreach($evidencias as $ev)
            @include('elementos.partials-jerarquia-evidencia')
        @endforeach
    </div>
@endif

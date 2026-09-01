@php
    $archivo = $ev->archivoDescarga();
    $nombreArchivo = $archivo
        ? ($archivo['nombre'] . ($archivo['extension'] ? '.' . $archivo['extension'] : ''))
        : null;
@endphp
<div class="grid grid-cols-1 sm:grid-cols-[8.5rem_minmax(0,1fr)_auto] gap-2 sm:gap-3 items-center px-3 py-2.5 {{ $loop->first ? '' : 'border-t border-gray-100 dark:border-gray-700/80' }}">
    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
        {{ $ev->folio_elemento ?: '—' }}
    </span>
    <a href="{{ route('elementos.show', $ev->id_elemento) }}"
       class="min-w-0 truncate text-sm font-medium text-gray-900 hover:text-brand-navy dark:text-gray-100 dark:hover:text-blue-300"
       title="{{ $ev->nombre_elemento }}">
        {{ $ev->nombre_elemento }}
    </a>
    <div class="flex items-center justify-end gap-1.5">
        <a href="{{ route('elementos.show', $ev->id_elemento) }}"
           class="btn-icon-muted"
           title="Ver ficha">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </a>
        @if($archivo)
            <a href="{{ $archivo['url'] }}"
               download="{{ $nombreArchivo }}"
               target="_blank"
               rel="noopener"
               class="btn-icon"
               title="Descargar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </a>
        @else
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-300 dark:text-gray-600"
                  title="Sin archivo para descargar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </span>
        @endif
    </div>
</div>

@if($nodo)
    <div class="mt-0.5 min-w-0">
        @if((int) $nodo->id_elemento === $aquiId)
            <p class="font-semibold text-gray-900 dark:text-gray-100 leading-snug">
                {{ $nodo->nombre_elemento }}
            </p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                @if($nodo->folio_elemento)
                    <span class="font-mono text-xs text-gray-500">{{ $nodo->folio_elemento }}</span>
                @endif
                <span class="badge-status badge-neutral">Estás aquí</span>
            </div>
        @else
            <a href="{{ route('elementos.show', $nodo->id_elemento) }}"
               class="font-medium text-brand-navy hover:underline dark:text-blue-300 leading-snug">
                {{ $nodo->nombre_elemento }}
            </a>
            @if($nodo->folio_elemento)
                <p class="mt-0.5 font-mono text-xs text-gray-500">{{ $nodo->folio_elemento }}</p>
            @endif
        @endif
    </div>
@endif


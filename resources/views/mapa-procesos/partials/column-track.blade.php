@props([
    'columnas' => [],
    'chipClass' => 'sgc-chip sgc-chip--mapcard sgc-chip--mapcard-md',
    'chipFirstClass' => '',
    'procesosDestacados' => [],
    'mapaMaxEjeX' => 0,
])

<div class="sgc-col-track" @if($mapaMaxEjeX > 0) style="--sgc-cols: {{ $mapaMaxEjeX }};" @endif>
    @foreach($columnas as $colIdx => $col)
    @php
        $procesos = $col['procesos'] ?? collect();
        $isEmpty = $procesos->isEmpty();
        $isStack = !$isEmpty && $procesos->count() > 1;
    @endphp
    <div class="sgc-col {{ $isStack ? 'sgc-col--stack' : '' }} {{ $isEmpty ? 'sgc-col--empty' : '' }}">
        @foreach($procesos as $pIdx => $p)
        @php
            $isFirst = $colIdx === 0 && $pIdx === 0;
            $highlighted = in_array($p->id_elemento, $procesosDestacados, true);
            $isIndustrialShared = str_contains($chipClass, 'industrial')
                && $procesos->count() === 1
                && (int) ($p->ubicacion_eje_y ?? 0) === 0;
        @endphp
        <button type="button"
            class="{{ $chipClass }} {{ $isFirst && $chipFirstClass ? $chipFirstClass : '' }} {{ $isIndustrialShared ? 'sgc-chip--industrial-shared' : '' }} {{ $highlighted ? 'sgc-chip--highlight' : '' }}"
            style="z-index:{{ $colIdx + $pIdx + 1 }};"
            onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
            title="{{ $highlighted ? $p->nombre_elemento . ' — Este proceso tiene relación contigo' : $p->nombre_elemento }}">
            <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
            <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
        </button>
        @endforeach
    </div>
    @endforeach
</div>

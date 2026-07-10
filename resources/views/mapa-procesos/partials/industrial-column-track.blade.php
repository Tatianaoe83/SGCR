@props([
    'columnas' => [],
    'chipClass' => 'sgc-chip sgc-chip--industrial',
    'chipFirstClass' => 'sgc-chip--first',
    'procesosDestacados' => [],
    'mapaMaxEjeX' => 0,
])

<div class="sgc-industrial-track">
    <div class="sgc-col-track sgc-col-track--industrial" @if($mapaMaxEjeX > 0) style="--sgc-cols: {{ $mapaMaxEjeX }};" @endif>
        @foreach($columnas as $colIdx => $col)
        @php
            $tipo = $col['tipo'] ?? 'split';
            $isFirst = $colIdx === 0;
        @endphp

        @if($tipo === 'shared')
        @php
            $p = $col['proceso'] ?? null;
            $highlighted = $p && in_array($p->id_elemento, $procesosDestacados, true);
        @endphp
        <div class="sgc-col sgc-industrial-col sgc-industrial-col--shared {{ !$p ? 'sgc-col--empty' : '' }}">
            @if($p)
            <button type="button"
                class="{{ $chipClass }} {{ $isFirst && $chipFirstClass ? $chipFirstClass : '' }} sgc-chip--industrial-shared {{ $highlighted ? 'sgc-chip--highlight' : '' }}"
                style="z-index:{{ $colIdx + 1 }};"
                onclick="openModal({{ $p->id_elemento }}, @js($p->nombre_elemento), @js($p->folio_elemento ?? ''), '{{ route('elementos.show', $p->id_elemento) }}')"
                title="{{ $highlighted ? $p->nombre_elemento . ' — Este proceso tiene relación contigo' : $p->nombre_elemento }}">
                <span class="sgc-chip-folio">{{ $p->folio_elemento }}</span>
                <span class="sgc-chip-name">{{ $p->nombre_elemento }}</span>
            </button>
            @endif
        </div>
        @else
        @php
            $con = $col['con'] ?? null;
            $ag = $col['ag'] ?? null;
            $isEmpty = !$con && !$ag;
        @endphp
        <div class="sgc-col sgc-industrial-col sgc-industrial-col--split {{ $isEmpty ? 'sgc-col--empty' : '' }}">
            <div class="sgc-industrial-slot">
                @if($con)
                @php $highlightedCon = in_array($con->id_elemento, $procesosDestacados, true); @endphp
                <button type="button"
                    class="{{ $chipClass }} {{ $highlightedCon ? 'sgc-chip--highlight' : '' }}"
                    style="z-index:{{ $colIdx + 1 }};"
                    onclick="openModal({{ $con->id_elemento }}, @js($con->nombre_elemento), @js($con->folio_elemento ?? ''), '{{ route('elementos.show', $con->id_elemento) }}')"
                    title="{{ $highlightedCon ? $con->nombre_elemento . ' — Este proceso tiene relación contigo' : $con->nombre_elemento }}">
                    <span class="sgc-chip-folio">{{ $con->folio_elemento }}</span>
                    <span class="sgc-chip-name">{{ $con->nombre_elemento }}</span>
                </button>
                @endif
            </div>
            <div class="sgc-industrial-slot">
                @if($ag)
                @php $highlightedAg = in_array($ag->id_elemento, $procesosDestacados, true); @endphp
                <button type="button"
                    class="{{ $chipClass }} {{ $highlightedAg ? 'sgc-chip--highlight' : '' }}"
                    style="z-index:{{ $colIdx + 1 }};"
                    onclick="openModal({{ $ag->id_elemento }}, @js($ag->nombre_elemento), @js($ag->folio_elemento ?? ''), '{{ route('elementos.show', $ag->id_elemento) }}')"
                    title="{{ $highlightedAg ? $ag->nombre_elemento . ' — Este proceso tiene relación contigo' : $ag->nombre_elemento }}">
                    <span class="sgc-chip-folio">{{ $ag->folio_elemento }}</span>
                    <span class="sgc-chip-name">{{ $ag->nombre_elemento }}</span>
                </button>
                @endif
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>

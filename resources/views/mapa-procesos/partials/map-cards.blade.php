@if($g['procesos']->isEmpty())
<p class="pm-empty-row">Sin procesos publicados.</p>
@else
<div class="pm-cards" style="--cols: {{ $g['procesos']->count() }}">
    @foreach($g['procesos'] as $p)
    <button type="button"
        class="pm-card pm-card--{{ $variante }} {{ $p->destacado_mapa ? 'is-mine' : '' }}"
        style="--c: {{ $g['color'] }}; --b: {{ $g['borde'] }}"
        onclick="pmGoToProceso('{{ $g['clave'] }}', {{ $p->id_elemento }})"
        title="{{ $p->destacado_mapa ? $p->nombre_elemento . ' — Este proceso tiene relación contigo' : $p->nombre_elemento }}">
        <span class="pm-folio">{{ $p->folio_elemento }}</span>
        <span class="pm-name">{{ $p->nombre_elemento }}</span>
    </button>
    @endforeach
</div>
@endif

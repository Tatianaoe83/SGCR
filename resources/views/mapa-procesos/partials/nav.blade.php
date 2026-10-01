<nav class="pm-nav" aria-label="Secciones del mapa de procesos">
    @foreach($navPills as $pill)
    <button type="button"
        class="pm-pill {{ $pill['id'] === $activo ? 'is-active' : '' }}"
        style="--pc: {{ $pill['color'] }}"
        data-view="{{ $pill['id'] }}"
        onclick="pmShow('{{ $pill['id'] }}')">{{ $pill['label'] }}</button>
    @endforeach
</nav>

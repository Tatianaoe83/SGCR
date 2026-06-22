@props([
    'half' => ['maxX' => 0, 'superior' => [], 'inferior' => []],
    'label' => '',
    'theme' => 'adm',
    'chipClass' => 'sgc-chip sgc-chip--mapcard sgc-chip--mapcard-md',
    'procesosDestacados' => [],
])

<div class="sgc-apoyo-half sgc-apoyo-half--{{ $theme }}">
    <div class="sgc-apoyo-half-label">
        <span class="sgc-apoyo-half-label-text">{!! nl2br(e($label)) !!}</span>
    </div>
    <div class="sgc-apoyo-half-body">
        <div class="sgc-track-row sgc-apoyo-row">
            <div class="sgc-track-area sgc-track-area--apoyo">
                @include('mapa-procesos.partials.column-track', [
                    'columnas' => $half['superior'] ?? [],
                    'chipClass' => $chipClass,
                    'procesosDestacados' => $procesosDestacados,
                    'mapaMaxEjeX' => $half['maxX'] ?? 0,
                ])
            </div>
        </div>
        <div class="sgc-track-row sgc-track-row--sep sgc-apoyo-row">
            <div class="sgc-track-area sgc-track-area--apoyo">
                @include('mapa-procesos.partials.column-track', [
                    'columnas' => $half['inferior'] ?? [],
                    'chipClass' => $chipClass,
                    'procesosDestacados' => $procesosDestacados,
                    'mapaMaxEjeX' => $half['maxX'] ?? 0,
                ])
            </div>
        </div>
    </div>
</div>

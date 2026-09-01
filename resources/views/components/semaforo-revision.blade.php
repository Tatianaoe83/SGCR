@props(['fecha', 'showInfo' => true, 'size' => 'sm'])

@php
    if (!$fecha) {
        $estado = 'sin_fecha';
        $clase = 'badge-status badge-neutral';
        $texto = 'Sin fecha';
        $info = '';
        $icono = '';
    } else {
        $hoy = now();
        $diferencia = $hoy->diffInMonths($fecha, false);
        
        if ($diferencia <= 2) {
            $estado = 'rojo';
            $clase = 'badge-status badge-danger';
            $texto = 'Crítico';
            $info = '⚠️ Revisión crítica';
            $icono = 'text-red-600 dark:text-red-400';
        } elseif ($diferencia >= 4 && $diferencia <= 6) {
            $estado = 'amarillo';
            $clase = 'badge-status badge-warning';
            $texto = 'Advertencia';
            $info = '⚠️ Revisión próxima';
            $icono = 'text-yellow-600 dark:text-yellow-400';
        } elseif ($diferencia >= 6 && $diferencia <= 12) {
            $estado = 'verde';
            $clase = 'badge-status badge-success';
            $texto = 'Normal';
            $info = '✅ Revisión programada';
            $icono = 'text-green-600 dark:text-green-400';
        } else {
            $estado = 'azul';
            $clase = 'badge-status badge-info';
            $texto = 'Lejano';
            $info = '📅 Revisión lejana';
            $icono = 'text-blue-600 dark:text-blue-400';
        }
    }
    
    $sizeClasses = [
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2.5 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
        'lg' => 'px-4 py-1.5 text-base'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];
@endphp

<div class="inline-flex items-center space-x-2">
    <span class="{{ $clase }}">
        {{ $texto }}
    </span>
    
    @if($showInfo && $fecha && $info)
        <span class="{{ $icono }} text-xs">
            {{ $info }}
        </span>
    @endif
</div>

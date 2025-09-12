@props(['fecha', 'showInfo' => true, 'size' => 'sm'])

@php
    if (!$fecha) {
        $estado = 'sin_fecha';
        $clase = 'bg-gray-500 text-white';
        $texto = 'Sin fecha';
        $info = '';
        $icono = '';
    } else {
        $hoy = now();
        $diferencia = $hoy->diffInMonths($fecha, false);
        
        if ($diferencia <= 2) {
            $estado = 'rojo';
            $clase = 'bg-red-500 text-white';
            $texto = 'Crítico';
            $info = '⚠️ Revisión crítica';
            $icono = 'text-red-600 dark:text-red-400';
        } elseif ($diferencia >= 4 && $diferencia <= 6) {
            $estado = 'amarillo';
            $clase = 'bg-yellow-500 text-black';
            $texto = 'Advertencia';
            $info = '⚠️ Revisión próxima';
            $icono = 'text-yellow-600 dark:text-yellow-400';
        } elseif ($diferencia >= 6 && $diferencia <= 12) {
            $estado = 'verde';
            $clase = 'bg-green-500 text-white';
            $texto = 'Normal';
            $info = '✅ Revisión programada';
            $icono = 'text-green-600 dark:text-green-400';
        } else {
            $estado = 'azul';
            $clase = 'bg-blue-500 text-white';
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
    <span class="inline-flex items-center {{ $sizeClass }} font-medium rounded-full {{ $clase }}">
        {{ $texto }}
    </span>
    
    @if($showInfo && $fecha && $info)
        <span class="{{ $icono }} text-xs">
            {{ $info }}
        </span>
    @endif
</div>

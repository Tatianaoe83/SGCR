@php
    $user = auth()->user();
    $firmasPendientes = [];
    $totalPendientes = 0;

    if ($user) {
        $empleado = App\Models\Empleados::where('correo', $user->email)->first();
        
        if ($empleado) {
            // Obtener todas las firmas pendientes en prioridad actual para este empleado
            $query = App\Models\Firmas::where('empleado_id', $empleado->id_empleado)
                ->where('estatus', 'Pendiente')
                ->where('is_active', true)
                ->with(['elemento', 'elemento.tipoElemento']);
            
            // Agrupar por elemento y filtrar solo los en prioridad actual
            $todasLasFirmas = $query->get();
            
            foreach ($todasLasFirmas as $firma) {
                $prioridadMinima = App\Models\Firmas::obtenerPrioridadMinimaPendiente($firma->elemento_id);
                
                // Solo mostrar si está en la prioridad actual
                if ($prioridadMinima !== null && $firma->prioridad === $prioridadMinima) {
                    $firmasPendientes[] = $firma;
                }
            }
            
            $totalPendientes = count($firmasPendientes);
        }
    }
@endphp

<div class="relative" x-data="{ abierto: false }">
    <!-- Botón de Campana -->
    <button
        @click="abierto = !abierto"
        @click.outside="abierto = false"
        class="relative inline-flex items-center justify-center w-9 h-9 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
        title="Firmas pendientes">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        @if($totalPendientes > 0)
        <span class="absolute top-0 right-0 inline-flex items-center justify-center w-4 h-4 text-xs font-bold text-white bg-red-600 rounded-full leading-none">
            {{ $totalPendientes }}
        </span>
        @endif
    </button>

    <!-- Dropdown de Notificaciones -->
    <div x-show="abierto"
        x-transition
        class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
        
        <!-- Header del Dropdown -->
        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                Firmas Pendientes
            </h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                {{ $totalPendientes }} documento(s) en espera
            </p>
        </div>

        <!-- Lista de Firmas -->
        <div class="max-h-80 overflow-y-auto">
            @forelse($firmasPendientes as $firma)
            <a href="{{ URL::temporarySignedRoute('revision.documento', now()->addDays(7), ['id' => $firma->elemento_id, 'firma' => $firma->id]) }}"
                class="flex items-start gap-2 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700/50 last:border-b-0">
                
                <!-- Ícono del tipo de elemento -->
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-7 h-7 rounded-md bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                        {{ $firma->elemento->nombre_elemento }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        {{ $firma->elemento->tipoElemento->nombre ?? 'Documento' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        @if($firma->prioridad === 1)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                Responsable
                            </span>
                        @elseif($firma->prioridad === 2)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Revisor
                            </span>
                        @elseif($firma->prioridad === 3)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                Autorizador
                            </span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300">
                                P{{ $firma->prioridad }}
                            </span>
                        @endif
                    </p>
                </div>

                <!-- Ícono Ir -->
                <div class="flex-shrink-0 mt-1">
                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>
            @empty
            <div class="px-3 py-6 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Sin firmas pendientes
                </p>
            </div>
            @endforelse
        </div>
    </div>
</div>

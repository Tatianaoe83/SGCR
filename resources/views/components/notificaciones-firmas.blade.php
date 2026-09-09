@php
    $user = auth()->user();
    $firmasPendientes = [];
    $totalPendientes = 0;

    if ($user) {
        // Un mismo correo puede tener varios registros de empleado (distintos puestos)
        $empleadoIds = App\Models\Empleados::where('correo', $user->email)->pluck('id_empleado');

        if ($empleadoIds->isNotEmpty()) {
            // Obtener todas las firmas pendientes en prioridad actual para este empleado
            $query = App\Models\Firmas::whereIn('empleado_id', $empleadoIds)
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

    // Documentos rechazados que el usuario aún no marca como leídos
    $servicioNotificaciones = app(App\Services\NotificacionFirmaService::class);
    $rechazos = $servicioNotificaciones->rechazos($user);
    $totalRechazos = $rechazos->count();
    $totalNotificaciones = $totalPendientes + $totalRechazos;
    $idsRechazos = $rechazos->map(fn($r) => $r['elemento']->id_elemento)->values();

    // Abre en Rechazados si hay alguno; si no, en Por firmar
    $pestanaInicial = $totalRechazos > 0 ? 'rechazados' : 'pendientes';
@endphp

<div class="relative"
    x-data="notificacionesFirmas({{ $totalNotificaciones }}, {{ $idsRechazos->toJson() }}, '{{ url('notificaciones/rechazos') }}', '{{ $pestanaInicial }}')"
    @click.outside="if (abierto) abierto = false"
    @keydown.escape.window="if (abierto) abierto = false"
    @sgc-modal-closing.window="ignoreNextClicks()">
    <!-- Botón de Campana -->
    <button
        @click.stop="toggle()"
        type="button"
        class="sgc-icon-btn"
        title="Firmas pendientes"
        :aria-expanded="abierto">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 8-3 8h18s-3-1-3-8M13.7 21a2 2 0 01-3.4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span x-show="total > 0" x-cloak class="sgc-ping" aria-hidden="true"></span>
        <span class="sr-only" x-text="total > 0 ? (total + ' notificaciones') : 'Sin notificaciones'"></span>
    </button>

    <!-- Dropdown de Notificaciones -->
    <div x-show="abierto"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        style="display: none;"
        class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
        @click.stop>

        <!-- Header del Dropdown -->
        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                Notificaciones
            </h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5"
                x-text="total === 1 ? '1 documento por atender' : total + ' documentos por atender'">
            </p>
        </div>

        <!-- Pestañas -->
        <div class="flex border-b border-gray-200 dark:border-gray-700" role="tablist">
            <button type="button"
                role="tab"
                @click="pestana = 'rechazados'"
                :aria-selected="pestana === 'rechazados'"
                :class="pestana === 'rechazados'
                    ? 'border-red-500 text-gray-900 dark:text-white'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-semibold border-b-2 transition-colors">
                Rechazados
                <span class="inline-flex items-center justify-center min-w-[16px] px-1 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200"
                    x-text="pendientesRechazos"></span>
            </button>

            <button type="button"
                role="tab"
                @click="pestana = 'pendientes'"
                :aria-selected="pestana === 'pendientes'"
                :class="pestana === 'pendientes'
                    ? 'border-amber-500 text-gray-900 dark:text-white'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 text-xs font-semibold border-b-2 transition-colors">
                Por firmar
                <span class="inline-flex items-center justify-center min-w-[16px] px-1 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200">
                    {{ $totalPendientes }}
                </span>
            </button>
        </div>

        <!-- Documentos rechazados -->
        <div x-show="pestana === 'rechazados'" x-cloak class="max-h-80 overflow-y-auto">
            @foreach($rechazos as $rechazo)
            @php
                $elementoRechazado = $rechazo['elemento'];
            @endphp
            <div x-show="!leidos.includes({{ $elementoRechazado->id_elemento }})"
                x-cloak
                class="flex items-start gap-2 px-3 py-2 border-l-2 border-l-red-500 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-b-gray-200 dark:border-b-gray-700">

                <a href="{{ route('elementos.info', $elementoRechazado->id_elemento) }}" class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                        {{ $elementoRechazado->nombre_elemento }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        {{ $elementoRechazado->tipoElemento->nombre ?? 'Documento' }}
                    </p>
                </a>

                <!-- Marcar como leída -->
                <button type="button"
                    @click.stop="marcarLeido({{ $elementoRechazado->id_elemento }})"
                    class="flex-shrink-0 p-1 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-200 dark:hover:text-gray-200 dark:hover:bg-gray-600 transition-colors"
                    title="Marcar como leída">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            @endforeach

            <div x-show="pendientesRechazos === 0" x-cloak class="px-3 py-6 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Sin documentos rechazados
                </p>
            </div>
        </div>

        <!-- Firmas pendientes -->
        <div x-show="pestana === 'pendientes'" x-cloak class="max-h-80 overflow-y-auto">
            @forelse($firmasPendientes as $firma)
            <a href="{{ URL::temporarySignedRoute('revision.documento', now()->addDays(7), ['id' => $firma->elemento_id, 'firma' => $firma->id]) }}"
                class="flex items-start gap-2 px-3 py-2 border-l-2 border-l-amber-500 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-b-gray-200 dark:border-b-gray-700 last:border-b-0">

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

@once
<script>
    function notificacionesFirmas(totalInicial, idsRechazos, urlBase, pestanaInicial) {
        return {
            abierto: false,
            pestana: pestanaInicial || 'pendientes',
            total: totalInicial,
            leidos: [],
            idsRechazos: idsRechazos || [],
            urlBase: urlBase,
            // Evita que un click-through al cerrar otro modal abra el panel
            _ignoreClickUntil: 0,

            get pendientesRechazos() {
                return this.idsRechazos.length - this.leidos.length;
            },

            toggle() {
                if (Date.now() < this._ignoreClickUntil) {
                    return;
                }
                this.abierto = !this.abierto;
            },

            ignoreNextClicks(ms = 350) {
                this._ignoreClickUntil = Date.now() + ms;
                this.abierto = false;
            },

            marcarLeido(elementoId) {
                if (this.leidos.includes(elementoId)) {
                    return Promise.resolve();
                }

                const url = this.urlBase + '/' + elementoId + '/leer';
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                .then(response => response.ok ? response.json() : Promise.reject(response))
                .then(data => {
                    this.leidos.push(elementoId);
                    this.total = data.pendientes ?? Math.max(0, this.total - 1);
                })
                .catch(() => {
                    console.error('No se pudo marcar la notificación como leída.');
                });
            },
        };
    }
</script>
@endonce

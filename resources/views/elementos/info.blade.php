<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <!-- Page header -->
        <div class="mt-10 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 dark:text-gray-100">
                    Información del elemento
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('elementos.show', $elemento->id_elemento) }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium
                  bg-gray-900 text-white hover:bg-gray-800
                  dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    Ver detalles
                </a>

                <a href="{{ route('elementos.index') }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium
                  border border-gray-300 text-gray-700 hover:bg-gray-100
                  dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                    Volver
                </a>
            </div>
        </div>

        <!-- Información del Elemento -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $elemento->nombre_elemento }}
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $elemento->tipoElemento->nombre ?? 'Sin tipo asignado' }}
                    </p>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                     bg-gray-100 text-gray-700
                     dark:bg-gray-800 dark:text-gray-300">
                    Elemento
                </span>
            </div>
        </div>

        <!-- Pestañas -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Navegación de pestañas -->
            <div class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex" aria-label="Tabs">
                    <button onclick="showTab('historial')"
                        id="tab-historial"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-3 transition-all duration-200 relative group"
                        data-tab="historial">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Historial de seguimiento</span>
                        </div>
                    </button>
                    <button onclick="showTab('periodo')"
                        id="tab-periodo"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-3 transition-all duration-200 relative group"
                        data-tab="periodo">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Periodo de revisión</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Contenido de las pestañas -->
            <div class="p-6">
                <!-- Pestaña: Historial de seguimiento -->
                <div id="content-historial" class="tab-content hidden">
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            Historial de firmas
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Seguimiento del proceso de aprobación
                        </p>
                    </div>

                    <div class="relative space-y-6">
                        @forelse($firmas as $index => $firma)
                        @php
                        $esSiguiente = isset($siguienteFirmaId) && (int) $siguienteFirmaId === (int) $firma->id;
                        $esPendiente = $firma->estatus === 'Pendiente';
                        $esAprobado = $firma->estatus === 'Aprobado';
                        $esRechazado = $firma->estatus === 'Rechazado';
                        @endphp

                        <div class="relative pl-12">
                            <!-- Timeline line -->
                            @if($index < count($firmas) - 1)
                            <span class="absolute left-[23px] top-12 bottom-0 w-0.5 
                                {{ $esAprobado ? 'bg-green-200 dark:bg-green-800' : 'bg-gray-200 dark:bg-gray-700' }}">
                            </span>
                            @endif

                            <!-- Card -->
                            <div class="rounded-xl border-2 
                                {{ $esSiguiente ? 'border-indigo-400 dark:border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900' }}
                                shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
                                
                                <!-- Header -->
                                <div class="px-6 py-4 {{ $esSiguiente ? 'bg-gradient-to-r from-indigo-100 to-transparent dark:from-indigo-900/30' : 'bg-gray-50 dark:bg-gray-800/50' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $firma->empleado->nombres }}
                                                {{ $firma->empleado->apellido_paterno }}
                                                {{ $firma->empleado->apellido_materno }}
                                            </h3>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $firma->tipo }}
                                            </p>
                                        </div>

                                        <div class="flex flex-col items-end gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
                                                {{ $esAprobado ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : '' }}
                                                {{ $esRechazado ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : '' }}
                                                {{ $esPendiente ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : '' }}">
                                                {{ $firma->estatus }}
                                            </span>
                                            
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                Prioridad {{ $firma->prioridad }}
                                            </span>

                                            @if($esSiguiente)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                                bg-indigo-600 text-white shadow-sm">
                                                ⚡ Siguiente
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="px-6 py-4 space-y-4">
                                    <!-- Email status -->
                                    <div class="flex items-center justify-between py-3 px-4 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Estado del correo
                                        </span>
                                        <span class="inline-flex items-center gap-2 text-sm font-medium
                                            {{ !empty($firma->email_sent_at) ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400' }}">
                                            @if(!empty($firma->email_sent_at))
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Enviado el {{ $firma->email_sent_at->format('d/m/Y') }}
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Pendiente de envío
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Recordatorio (solo si está pendiente) -->
                                    @if($esPendiente)
                                    <div class="rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-950/30 p-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                        Frecuencia de recordatorio
                                                    </p>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $firma->last_reminder_at ? 'Último: ' . $firma->last_reminder_at->format('d/m/Y') : 'Sin recordatorios enviados' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <select
                                                class="rounded-lg border-gray-300 dark:border-gray-600
                                                    bg-white dark:bg-gray-800
                                                    text-gray-900 dark:text-gray-100
                                                    text-sm font-medium
                                                    px-4 py-2
                                                    focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                                    shadow-sm"
                                                onchange="cambiarFrecuencia({{ $firma->id }}, this.value)">
                                                <option value="Semanal" {{ ($firma->timer_recordatorio ?? 'Semanal') === 'Semanal' ? 'selected' : '' }}>
                                                    📅 Semanal
                                                </option>
                                                <option value="Cada3Días" {{ $firma->timer_recordatorio === 'Cada3Días' ? 'selected' : '' }}>
                                                    ⏰ Cada 3 días
                                                </option>
                                                <option value="Diario" {{ $firma->timer_recordatorio === 'Diario' ? 'selected' : '' }}>
                                                    🔔 Diario
                                                </option>
                                            </select>
                                        </div>

                                        @if($firma->next_reminder_at)
                                        <div class="mt-3 pt-6 border-t border-indigo-200 dark:border-indigo-800">
                                            <p class="text-xs text-indigo-700 dark:text-indigo-300 font-medium">
                                                ⏱️ Próximo recordatorio: {{ $firma->next_reminder_at->format('d/m/Y') }}
                                            </p>
                                        </div>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Rechazo -->
                                    @if($esRechazado && $firma->comentario_rechazo)
                                    <div class="rounded-lg border-2 border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-950/30 p-4">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-semibold text-red-900 dark:text-red-100">
                                                    Motivo del rechazo
                                                </p>
                                                <p class="mt-2 text-sm text-red-800 dark:text-red-200 leading-relaxed">
                                                    {{ $firma->comentario_rechazo }}
                                                </p>

                                                @php
                                                $evidencias = is_array($firma->evidencia_rechazo_path) ? $firma->evidencia_rechazo_path : [];
                                                $evidencias = array_values(array_filter(array_map(fn($p) => is_string($p) ? trim($p) : '', $evidencias)));
                                                @endphp

                                                @if(!empty($evidencias))
                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    @foreach($evidencias as $i => $path)
                                                    @php
                                                    $isUrl = \Illuminate\Support\Str::startsWith($path, ['http://', 'https://']);
                                                    $url = $isUrl ? $path : \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                                                    @endphp
                                                    <a href="{{ $url }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg
                                                            bg-red-100 dark:bg-red-900/40
                                                            text-red-700 dark:text-red-300
                                                            text-sm font-medium
                                                            hover:bg-red-200 dark:hover:bg-red-900/60
                                                            transition-colors duration-150">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        Ver evidencia {{ $i + 1 }}
                                                    </a>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                No hay firmas registradas
                            </p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                El historial aparecerá aquí cuando se registren firmas
                            </p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Pestaña: Periodo de revisión -->
                <div id="content-periodo" class="tab-content hidden space-y-6">

                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                Período de revisión
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Control de vencimiento del procedimiento
                            </p>
                        </div>
                        <button class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                {{ $daysLeft <= 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                : ($monthsLeft <= 1 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                : ($monthsLeft <= 12 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400')) }}">
                                @if($daysLeft <= 0)
                                    Vencido
                                    @elseif($monthsLeft <=1)
                                    Próximo
                                    @elseif($monthsLeft <=12)
                                    Vigente
                                    @else
                                    Lejano
                                    @endif
                                    </span>

                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        @if($daysLeft <= 0)
                                            El período de revisión ya venció
                                            @elseif($monthsLeft <=1)
                                            Próxima revisión en {{ $daysLeft }} días
                                            @else
                                            {{ $daysLeft }} días restantes ({{ $monthsLeft }} meses)
                                            @endif
                                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Crítico ≤ 2 meses</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Advertencia 4–6 meses</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Normal 6–12 meses</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Lejano &gt; 1 año</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">

                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Fecha de inicio</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Fecha de fin</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if($elemento->periodo_revision)
                                {{ \Carbon\Carbon::parse($elemento->periodo_revision)->format('d/m/Y') }}
                                @else
                                Sin fecha
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Responsable</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $elemento->puestoResponsable->nombre ?? 'No asignado' }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Recordatorios</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                Activos
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Función para mostrar la pestaña seleccionada
        function showTab(tabName) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remover estilos activos de todas las pestañas
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove(
                    'border-blue-500',
                    'text-blue-600',
                    'dark:text-blue-400',
                    'bg-white',
                    'dark:bg-gray-800'
                );
                button.classList.add(
                    'border-transparent',
                    'text-gray-500',
                    'hover:text-gray-700',
                    'hover:border-gray-300',
                    'dark:text-gray-400',
                    'dark:hover:text-gray-300'
                );
            });

            // Mostrar el contenido seleccionado
            const content = document.getElementById('content-' + tabName);
            if (content) {
                content.classList.remove('hidden');
            }

            // Activar la pestaña seleccionada
            const button = document.getElementById('tab-' + tabName);
            if (button) {
                button.classList.remove(
                    'border-transparent',
                    'text-gray-500',
                    'hover:text-gray-700',
                    'hover:border-gray-300',
                    'dark:text-gray-400',
                    'dark:hover:text-gray-300'
                );
                button.classList.add(
                    'border-blue-500',
                    'text-blue-600',
                    'dark:text-blue-400',
                    'bg-white',
                    'dark:bg-gray-800'
                );
            }

            // Usar hash para mantener el estado sin mostrar en la URL
            window.location.hash = tabName;
        }

        // Mostrar la pestaña inicial basada en el hash o el valor del servidor
        document.addEventListener('DOMContentLoaded', function() {
            // Primero intentar leer del hash
            let tab = window.location.hash.replace('#', '');
            // Si no hay hash, usar el valor del servidor
            if (!tab || !['historial', 'recordatorios', 'periodo'].includes(tab)) {
                tab = '{{ $tab }}';
            }
            showTab(tab);
        });

        // Escuchar cambios en el hash para navegación con botones del navegador
        window.addEventListener('hashchange', function() {
            let tab = window.location.hash.replace('#', '');
            if (['historial', 'recordatorios', 'periodo'].includes(tab)) {
                showTab(tab);
            }
        });
    </script>
    <script>
        function cambiarFrecuencia(firmaId, frecuencia) {
            Swal.fire({
                title: '¿Cambiar periodicidad?',
                text: 'Se modificará la frecuencia de los correos de recordatorio.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#dc2626',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                fetch(`/firmas/${firmaId}/frecuencia`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            frecuencia
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al actualizar la frecuencia');
                        }
                        return response.json();
                    })
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: 'La periodicidad fue actualizada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar la frecuencia',
                            timer: 1500,
                        });
                    });
            });
        }
    </script>
</x-app-layout>
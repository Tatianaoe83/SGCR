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
                    @if($firmasPendientes->count())
                    <div class="mb-8">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                            Pendientes por firmar
                        </h3>

                        <div class="space-y-3">
                            @foreach($firmasPendientes as $firma)
                            <div
                                class="flex flex-col gap-3
                                rounded-lg border border-gray-200 dark:border-gray-700
                                px-5 py-4 bg-white dark:bg-gray-900
                                shadow-sm hover:shadow-md transition">

                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $firma->empleado->nombres }} {{ $firma->empleado->apellido_paterno }}
                                    </p>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Firma pendiente
                                    </span>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                                    <div class="flex items-center gap-2">
                                        <label
                                            for="timer-firma-{{ $firma->id }}"
                                            class="text-xs font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                            Recordatorio
                                        </label>

                                        <select
                                            id="timer-firma-{{ $firma->id }}"
                                            class="text-xs rounded-md border border-gray-300 dark:border-gray-600
                                            bg-white dark:bg-gray-800
                                            text-gray-700 dark:text-gray-200
                                            px-3 py-1.5
                                            focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                            onchange="cambiarFrecuencia({{ $firma->id }}, this.value)">

                                            <option value="Semanal"
                                                {{ ($firma->timer_recordatorio ?? 'Semanal') === 'Semanal' ? 'selected' : '' }}>
                                                Semanal
                                            </option>

                                            <option value="Cada3Días"
                                                {{ $firma->timer_recordatorio === 'Cada3Días' ? 'selected' : '' }}>
                                                3 días
                                            </option>

                                            <option value="Diario"
                                                {{ $firma->timer_recordatorio === 'Diario' ? 'selected' : '' }}>
                                                Diario
                                            </option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-300">
                                        <div class="flex flex-col">
                                            <span class="uppercase tracking-wide text-[10px] text-gray-400">
                                                Último recordatorio
                                            </span>
                                            <span class="font-mono">
                                                {{ ($firma->last_reminder_at)->format('Y-m-d') ?? '—' }}
                                            </span>
                                        </div>

                                        <div class="flex flex-col">
                                            <span class="uppercase tracking-wide text-[10px] text-gray-400">
                                                Próximo recordatorio
                                            </span>
                                            <span class="font-mono text-indigo-600 dark:text-indigo-400">
                                                {{ ($firma->next_reminder_at)->format('Y-m-d') ?? '—' }}
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Historial del procedimiento
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Seguimiento de participantes y responsables
                        </p>
                    </div>

                    <div class="space-y-6">
                        @forelse($firmasHistorial as $firma)

                        <div class="relative pl-10">

                            <span class="absolute left-4 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></span>

                            <span class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full
                                @if($firma->estatus === 'Aprobado')
                                    bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400
                                @elseif($firma->estatus === 'Rechazado')
                                    bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400
                                @else
                                    bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400
                                @endif
                            ">
                                @if($firma->estatus === 'Aprobado')
                                ✓
                                @elseif($firma->estatus === 'Rechazado')
                                ✕
                                @else
                                !
                                @endif
                            </span>

                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-5 py-4">

                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $firma->empleado->nombres }}
                                            {{ $firma->empleado->apellido_paterno }}
                                            {{ $firma->empleado->apellido_materno }}
                                        </p>

                                        <span class="mt-1 inline-block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $firma->tipo }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                            @if($firma->estatus === 'Aprobado')
                                                bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300
                                            @elseif($firma->estatus === 'Rechazado')
                                                bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                            @else
                                                bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                            @endif
                                        ">
                                            {{ $firma->estatus }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>
                                        {{ $firma->fecha
                                            ? \Carbon\Carbon::parse($firma->fecha)->locale('es')->translatedFormat('d M Y')
                                            : 'Sin fecha'
                                        }}
                                    </span>
                                    <span>
                                        {{ $firma->fecha
                                            ? \Carbon\Carbon::parse($firma->fecha)->format('h:i A')
                                            : 'Sin hora'
                                        }}
                                    </span>
                                </div>

                                @if($firma->estatus === 'Rechazado' && $firma->comentario_rechazo)
                                <div class="mt-4 rounded-md border border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/20 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">
                                        Motivo del rechazo
                                    </p>
                                    <p class="mt-1 text-sm text-red-700 dark:text-red-300 leading-relaxed">
                                        {{ $firma->comentario_rechazo }}
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>

                        @empty

                        {{-- ESTADO VACÍO --}}
                        <div class="flex flex-col items-center justify-center py-16 text-center">

                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full
                                bg-gray-100 text-gray-500
                                dark:bg-gray-800 dark:text-gray-400">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Nadie ha firmado este procedimiento
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
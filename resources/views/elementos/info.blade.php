<x-app-layout>
    @php
    $firmadosCount = $firmados->count();
    $pendientesCount = $pendientes->count();
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="mt-10 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 dark:text-gray-100">
                    Información del elemento
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('elementos.show', $elemento->id_elemento) }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-gray-900 text-white hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    Ver detalles
                </a>

                <a href="{{ route('elementos.index') }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                    Volver
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $elemento->nombre_elemento }}
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $elemento->tipoElemento->nombre ?? 'Sin tipo asignado' }}
                    </p>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    {{ $elemento->status ?? 'Sin estado' }}
                </span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex flex-wrap" aria-label="Tabs">
                    <button type="button"
                        onclick="showTab('historial')"
                        id="tab-historial"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 relative group"
                        data-tab="historial">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8m-8 5h8M5 7h.01M5 12h.01M5 17h.01"></path>
                            </svg>
                            <span>Historial</span>
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full text-xs bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                {{ $firmadosCount }}
                            </span>
                        </div>
                    </button>

                    <button type="button"
                        onclick="showTab('pendientes')"
                        id="tab-pendientes"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 relative group"
                        data-tab="pendientes">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Pendientes</span>
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full text-xs bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                {{ $pendientesCount }}
                            </span>
                        </div>
                    </button>

                    <button type="button"
                        onclick="showTab('periodo')"
                        id="tab-periodo"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 relative group"
                        data-tab="periodo">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Periodo de revisión</span>
                        </div>
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <div id="content-historial" class="tab-content hidden">
                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                Historial de firmas realizadas
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Solo se muestran firmas completadas, aprobadas o rechazadas.
                            </p>
                        </div>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $firmadosCount }} {{ $firmadosCount === 1 ? 'movimiento registrado' : 'movimientos registrados' }}
                        </div>
                    </div>

                    @if($firmados->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Aún no hay firmas completadas
                        </p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Cuando alguien apruebe o rechace, aparecerá aquí en orden cronológico.
                        </p>
                    </div>
                    @else
                    <div class="relative">
                        <div class="absolute left-5 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                        <div class="space-y-6">
                            @foreach($firmados as $firma)
                            @php
                            $esAprobado = $firma->estatus === 'Aprobado';
                            $esRechazado = $firma->estatus === 'Rechazado';
                            $fechaMovimiento = $firma->fecha_movimiento;

                            $nombreFirmante = trim(collect([
                            optional($firma->empleado)->nombres,
                            optional($firma->empleado)->apellido_paterno,
                            optional($firma->empleado)->apellido_materno,
                            ])->filter()->implode(' '));

                            $evidencias = is_array($firma->evidencia_rechazo_path) ? $firma->evidencia_rechazo_path : [];
                            $evidencias = array_values(array_filter(array_map(
                            fn ($p) => is_string($p) ? trim($p) : '',
                            $evidencias
                            )));
                            @endphp

                            <div class="relative pl-14">
                                <div class="rounded-xl border {{ $esAprobado ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800' }} bg-white dark:bg-gray-900 shadow-sm">
                                    <div class="p-5">
                                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                                        {{ $nombreFirmante ?: 'Firmante no disponible' }}
                                                    </h3>

                                                    @if($firma->empleado && $firma->empleado->trashed())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                        Inactivo
                                                    </span>
                                                    @endif

                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                                                                {{ $esAprobado ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                                        @if($esAprobado)
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        @else
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        @endif
                                                        {{ $firma->estatus }}
                                                    </span>
                                                </div>

                                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                    <span>{{ $firma->tipo ?? 'Sin rol definido' }}</span>
                                                    <span class="text-gray-300 dark:text-gray-600">•</span>
                                                    <span>{{ optional($firma->puestoTrabajo)->nombre ?? 'Sin puesto' }}</span>
                                                    <span class="text-gray-300 dark:text-gray-600">•</span>
                                                    <span>Prioridad {{ $firma->prioridad ?? 'N/D' }}</span>
                                                </div>
                                            </div>

                                            <div class="lg:text-right">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $fechaMovimiento ? $fechaMovimiento->format('d/m/Y') : 'Sin fecha' }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $fechaMovimiento ? $fechaMovimiento->format('H:i') . ' hrs' : 'Hora no disponible' }}
                                                </div>
                                                @if($fechaMovimiento)
                                                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                    {{ $fechaMovimiento->locale('es')->diffForHumans() }}
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if($esRechazado && $firma->comentario_rechazo)
                                        <div class="mt-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-100 dark:bg-red-950/20 p-4">
                                            <p class="text-sm font-semibold text-red-900 dark:text-white">
                                                Motivo del rechazo
                                            </p>
                                            <p class="mt-1 text-sm text-red-800 dark:text-white">
                                                {{ $firma->comentario_rechazo }}
                                            </p>

                                            @if(!empty($evidencias))
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach($evidencias as $i => $path)
                                                @php
                                                $isUrl = \Illuminate\Support\Str::startsWith($path, ['http://', 'https://']);
                                                $url = $isUrl ? $path : \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                                                @endphp

                                                <a href="{{ $url }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 text-xs font-medium hover:bg-red-200 dark:hover:bg-red-900/60 transition-colors duration-150">
                                                    Evidencia {{ $i + 1 }}
                                                </a>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <div id="content-pendientes" class="tab-content hidden overflow-visible">
                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                Firmas pendientes
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Aquí se administran los firmantes que aún no responden y su frecuencia de recordatorio.
                            </p>
                        </div>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $pendientesCount }} {{ $pendientesCount === 1 ? 'firma pendiente' : 'firmas pendientes' }}
                        </div>
                    </div>

                    @if($pendientes->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            No hay firmantes pendientes
                        </p>
                    </div>
                    @else
                    <div class="space-y-4 overflow-visible">
                        @foreach($pendientes as $firma)
                        @php
                        $esSiguiente = isset($siguienteFirmaId) && (int) $siguienteFirmaId === (int) $firma->id;

                        $nombrePendiente = trim(collect([
                        optional($firma->empleado)->nombres,
                        optional($firma->empleado)->apellido_paterno,
                        optional($firma->empleado)->apellido_materno,
                        ])->filter()->implode(' '));

                        $ultimoCorreo = $firma->email_sent_at ? \Carbon\Carbon::parse($firma->email_sent_at) : null;
                        $proximoRecordatorio = $firma->next_reminder_at ? \Carbon\Carbon::parse($firma->next_reminder_at) : null;
                        $frecuenciaActual = $firma->timer_recordatorio ?? 'Semanal';
                        @endphp

                        <div class="relative overflow-visible rounded-xl border {{ $esSiguiente ? 'border-indigo-300 dark:border-indigo-700' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-900 shadow-sm">
                            <div class="p-5 overflow-visible">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 overflow-visible">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $nombrePendiente ?: 'Firmante no disponible' }}
                                            </h3>

                                            @if($firma->empleado && $firma->empleado->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                Inactivo
                                            </span>
                                            @endif

                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300">
                                                Pendiente
                                            </span>

                                            @if($esSiguiente)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-600 text-white">
                                                Siguiente
                                            </span>
                                            @endif
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600 dark:text-gray-400">
                                            <span>{{ $firma->tipo ?? 'Sin rol definido' }}</span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                            <span>{{ optional($firma->puestoTrabajo)->nombre ?? 'Sin puesto' }}</span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                            <span>Prioridad {{ $firma->prioridad ?? 'N/D' }}</span>
                                        </div>

                                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2">
                                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                    Último correo
                                                </p>
                                                <p class="mt-1 text-gray-900 dark:text-gray-100">
                                                    {{ $ultimoCorreo ? $ultimoCorreo->format('d/m/Y') : 'Sin envío registrado' }}
                                                </p>
                                            </div>

                                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2">
                                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                    Próximo recordatorio
                                                </p>
                                                <p class="mt-1 text-gray-900 dark:text-gray-100">
                                                    {{ $proximoRecordatorio ? $proximoRecordatorio->format('d/m/Y') : 'No programado' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        id="recordatorio_panel_{{ $firma->id }}"
                                        class="top-full right-0 z-[9999] mt-2 w-72 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xl">
                                        <div class="p-4">
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                Frecuencia de recordatorio
                                            </label>

                                            <select
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                data-current="{{ $frecuenciaActual }}"
                                                onchange="cambiarFrecuencia({{ $firma->id }}, this.value, this)">
                                                <option value="Semanal" {{ $frecuenciaActual === 'Semanal' ? 'selected' : '' }}>
                                                    Semanal
                                                </option>
                                                <option value="Cada3Días" {{ $frecuenciaActual === 'Cada3Días' ? 'selected' : '' }}>
                                                    Cada 3 días
                                                </option>
                                                <option value="Diario" {{ $frecuenciaActual === 'Diario' ? 'selected' : '' }}>
                                                    Diario
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

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
                    </div>

                    @if(!$elemento->periodo_revision)
                    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-6 py-12 text-center">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Este elemento no tiene período de revisión definido.
                        </p>
                    </div>
                    @else
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
                                            Próxima revisión en {{ floor($daysLeft) }} días
                                            @else
                                            {{ floor($daysLeft) }} días restantes ({{ floor($monthsLeft) }} meses)
                                            @endif
                                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Crítico hasta 2 meses</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Próximo hasta 1 mes</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Vigente hasta 12 meses</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Lejano más de 1 año</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Fecha actual</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Fecha de revisión</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::parse($elemento->periodo_revision)->format('d/m/Y') }}
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
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

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

            const content = document.getElementById('content-' + tabName);
            if (content) {
                content.classList.remove('hidden');
            }

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

            window.location.hash = tabName;
        }

        document.addEventListener('DOMContentLoaded', function() {
            let tab = window.location.hash.replace('#', '');

            if (!tab || !['historial', 'pendientes', 'periodo'].includes(tab)) {
                tab = '{{ $tab }}';
            }

            showTab(tab);
        });

        window.addEventListener('hashchange', function() {
            let tab = window.location.hash.replace('#', '');

            if (['historial', 'pendientes', 'periodo'].includes(tab)) {
                showTab(tab);
            }
        });
    </script>

    <script>
        function closeAllReminderPanels() {
            document.querySelectorAll('.recordatorio-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
        }

        function toggleRecordatorioPanel(event, firmaId) {
            event.preventDefault();
            event.stopPropagation();

            const panel = document.getElementById(`recordatorio_panel_${firmaId}`);
            if (!panel) {
                return;
            }

            const isHidden = panel.classList.contains('hidden');
            closeAllReminderPanels();

            if (isHidden) {
                panel.classList.remove('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.recordatorio-wrapper')) {
                closeAllReminderPanels();
            }
        });

        function cambiarFrecuencia(firmaId, frecuencia, selectEl) {
            const frecuenciaAnterior = selectEl.dataset.current || 'Semanal';

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
                    selectEl.value = frecuenciaAnterior;
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
                        selectEl.dataset.current = frecuencia;
                        closeAllReminderPanels();

                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: 'La periodicidad fue actualizada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    })
                    .catch(() => {
                        selectEl.value = frecuenciaAnterior;

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar la frecuencia',
                            timer: 1800,
                        });
                    });
            });
        }
    </script>
</x-app-layout>
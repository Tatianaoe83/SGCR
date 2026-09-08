<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Detalles del Elemento
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">

                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $elemento->nombre_elemento ?? 'Sin nombre de elemento' }}
                        </h1>
                        <div class="flex items-center gap-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $elemento->tipoElemento->nombre ?? 'Sin tipo de elemento' }}
                            </p>

                            @php
                                $statusClasses = match ($elemento->status) {
                                    'Publicado' => 'badge-status badge-success',
                                    'En Firmas' => 'badge-status badge-warning',
                                    'Rechazado' => 'badge-status badge-danger',
                                    'Obsoleto' => 'badge-status badge-neutral',
                                    default => 'badge-status badge-neutral',
                                };
                            @endphp

                            <span class="{{ $statusClasses }}">
                                {{ $elemento->status ?? 'Sin estatus' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('elementos.edit', $elemento->id_elemento) }}"
                            class="btn-primary">
                            Editar
                        </a>
                        <a href="{{ route('elementos.index') }}"
                            class="btn-secondary">
                            Volver
                        </a>
                    </div>
                </div>

                <div class="p-6 space-y-6">

                    <div class="space-y-6">
                        @include('elementos.partials-documento')
                        @include('elementos.partials-jerarquia')
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Información Básica
                        </h3>

                        @php
                            $tipoProcesoTexto = $elemento->tipoProceso->nombre
                                ?? ($elemento->elementoPadre
                                    ? $elemento->elementoPadre->nombre_elemento . ' - ' . $elemento->elementoPadre->folio_elemento
                                    : null)
                                ?? 'Sin tipo de proceso';

                            $infoBasica = [
                                'Tipo de Proceso' => $tipoProcesoTexto,
                                'Control' => ucfirst($elemento->control ?? 'Sin dato'),
                                'Folio' => $elemento->folio_elemento ?? 'Sin folio',
                                'Versión' => $elemento->version_elemento ?? 'Sin Versión',
                                'Ubicacion Eje X' => $elemento->ubicacion_eje_x ?? 'Sin dato',
                            ];
                        @endphp

                        {{-- Unidad de Negocio como badges --}}
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-sm text-gray-500 whitespace-nowrap">
                                Unidad de Negocio
                            </span>

                            <div class="flex flex-wrap gap-2 justify-end">
                                @forelse ($unidadNegocio as $unidad)
                                            <span class="badge-status badge-info">
                                                {{ $unidad->nombre }}
                                            </span>
                                @empty
                                    <span class="text-sm text-gray-400">
                                        Sin Unidades de Negocio
                                    </span>
                                @endforelse
                            </div>
                        </div>

                        @foreach ($infoBasica as $label => $value)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">{{ $label }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $value ?? 'N/A' }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Fechas y Responsables
                        </h3>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Periodo de Revisión</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $elemento->periodo_revision?->format('d/m/Y') ?? 'Sin fecha' }}
                                </span>
                                @if($elemento->periodo_revision)
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $elemento->clase_semaforo }}">
                                        {{ is_array($elemento->texto_semaforo) ? $elemento->texto_semaforo['texto'] : $elemento->texto_semaforo }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-between items-center gap-3">
                            <span class="text-sm text-gray-500">Puesto Responsable</span>
                            <div class="flex items-center gap-2 justify-end">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $elemento->puestoResponsable->nombre ?? 'N/A' }}
                                </span>
                                <span class="badge-role badge-role-r">R</span>
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Fecha del Elemento</span>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->fecha_elemento ? $elemento->fecha_elemento?->format('d/m/Y') : 'Sin fecha' }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-3">
                            <span class="text-sm text-gray-500">Puesto Ejecutor</span>
                            <div class="flex items-center gap-2 justify-end">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $elemento->puestoEjecutor->nombre ?? 'N/A' }}
                                </span>
                                <span class="badge-role badge-role-e">E</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center gap-3">
                            <span class="text-sm text-gray-500">Puesto de Resguardo</span>
                            <div class="flex items-center gap-2 justify-end">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $elemento->puestoResguardo->nombre ?? 'N/A' }}
                                </span>
                                <span class="badge-role badge-role-a">A</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-gray-500">Puestos relacionados</span>
                                @if($puestosRelacionados->isNotEmpty())
                                    <span class="text-xs tabular-nums text-gray-400">{{ $puestosRelacionados->count() }}</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap content-start gap-2 max-h-36 overflow-y-auto pr-1">
                                @forelse ($puestosRelacionados as $puesto)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="badge-status badge-info">{{ $puesto->nombre }}</span>
                                        <span class="badge-role badge-role-pr">PR</span>
                                    </span>
                                @empty
                                    <span class="text-sm text-gray-400">Sin puestos relacionados</span>
                                @endforelse
                            </div>
                            @if($puestosRelacionados->count() > 12)
                                <p class="text-[11px] text-gray-400">Desplaza para ver los {{ $puestosRelacionados->count() }} puestos.</p>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Medio de Soporte</span>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($elemento->medio_soporte) ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Ubicación de Resguardo</span>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->ubicacion_resguardo ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Periodo de Resguardo</span>
                            <span
                                class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->periodo_resguardo ? $elemento->periodo_resguardo->format('d/m/Y') : 'Sin fecha' }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Relacionado y Archivos
                        </h3>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">¿Es formato?</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ ucfirst($elemento->es_formato) }}
                                </span>
                            </div>
                        </div>

                        @if($elemento->es_formato === 'si')
                            @php
                                $archivosFormato = $elemento->archivos_formato_detalle;
                                // Un color por tipo, con la extension escrita: el color no es el unico indicador.
                                $estiloPorTipo = [
                                    'pdf'  => ['PDF', 'badge-status badge-danger'],
                                    'doc'  => ['DOC', 'badge-status badge-info'],
                                    'docx' => ['DOC', 'badge-status badge-info'],
                                    'xls'  => ['XLS', 'badge-status badge-success'],
                                    'xlsx' => ['XLS', 'badge-status badge-success'],
                                ];
                            @endphp

                            {{-- Tarjeta compacta: cabecera fija y filas de 32px separadas por hairline. --}}
                            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-3 py-1.5 dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Archivos del formato
                                    </span>
                                    <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-gray-200 px-1.5 text-[11px] font-semibold tabular-nums text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $archivosFormato->count() }}
                                    </span>
                                </div>

                                @if($archivosFormato->isEmpty())
                                    <p class="px-3 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                                        Sin archivos de evidencia
                                    </p>
                                @else
                                    <ul class="max-h-44 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700/60">
                                        @foreach($archivosFormato as $archivo)
                                            @php
                                                [$etiqueta, $colorBadge] = $estiloPorTipo[$archivo['extension']]
                                                    ?? [strtoupper(substr($archivo['extension'], 0, 4)) ?: 'ARC',
                                                        'badge-status badge-neutral'];
                                            @endphp

                                            <li class="group flex items-center gap-2.5 px-3 py-1.5 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <span class="shrink-0 {{ $colorBadge }}">
                                                    {{ $etiqueta }}
                                                </span>

                                                @if($archivo['existe'])
                                                    <a href="{{ $archivo['url'] }}" target="_blank" rel="noopener noreferrer"
                                                        class="min-w-0 flex-1 truncate text-[13px] font-medium text-gray-800 hover:text-brand-navy hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy dark:text-gray-100 dark:hover:text-blue-300"
                                                        title="{{ $archivo['nombre'] }}">
                                                        {{ $archivo['nombre'] }}
                                                    </a>

                                                    <span class="shrink-0 text-[11px] tabular-nums text-gray-400 dark:text-gray-500">
                                                        {{ $archivo['tamano'] }}
                                                    </span>

                                                    <a href="{{ $archivo['url'] }}" download
                                                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded text-gray-400 transition-colors hover:bg-violet-50 hover:text-brand-navy focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-navy dark:hover:bg-violet-500/15 dark:hover:text-blue-300"
                                                        aria-label="Descargar {{ $archivo['nombre'] }}">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-gray-500 line-through dark:text-gray-400"
                                                        title="{{ $archivo['nombre'] }}">
                                                        {{ $archivo['nombre'] }}
                                                    </span>
                                                    <span class="shrink-0 text-[11px] font-medium text-amber-600 dark:text-amber-400">
                                                        No encontrado
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @else
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Archivos del Formato</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Sin formato asociado
                                </span>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Correo Implementación</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $elemento->correo_implementacion ? 'Sí' : 'No' }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Correo Agradecimiento</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $elemento->correo_agradecimiento ? 'Sí' : 'No' }}
                            </span>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Firmas del Procedimiento
                        </h3>

                        <div class="space-y-3">
                            @foreach($firmas as $firma)
                                <div class="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">

                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ optional($firma->empleado)->nombres }}
                                            {{ optional($firma->empleado)->apellido_paterno }}
                                            {{ optional($firma->empleado)->apellido_materno }}
                                            @if($firma->empleado && $firma->empleado->trashed())
                                                <span
                                                    class="ml-2 badge-status badge-neutral">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ optional($firma->puestoTrabajo)->nombre ?? 'Sin puesto' }} ·
                                            {{ $firma->tipo }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span
                                            @if($firma->estatus === 'Aprobado')
                                                class="badge-status badge-success"
                                            @elseif($firma->estatus === 'Rechazado')
                                                class="badge-status badge-danger"
                                            @else
                                                class="badge-status badge-warning"
                                            @endif>
                                            {{ $firma->estatus }}
                                        </span>

                                        <span class="text-xs text-gray-500">
                                            {{ $firma->fecha ? \Carbon\Carbon::parse($firma->fecha)->format('d M Y · h:i A') : 'Sin firma' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- @if($firma->estatus === 'Rechazado' && $firma->comentario_rechazo)
                                <div class="text-sm text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 px-4 py-2 rounded-lg">
                                    <strong>Motivo del Rechazo:</strong> {{ $firma->comentario_rechazo }}
                                </div>
                                @endif -->
                            @endforeach
                        </div>
                    </div>
                </div>
                </div>

                <div
                    class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 flex justify-between">
                    <span>Creado: {{ $elemento->created_at->format('d/m/Y H:i') }}</span>
                    <span>Última Actualización: {{ $elemento->updated_at->format('d/m/Y H:i') }}</span>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
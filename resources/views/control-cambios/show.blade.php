<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Control de Cambios
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Folio del cambio:
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ $cambios->FolioCambio }}
                    </span>
                </p>
            </div>

            <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                @if($cambios->Prioridad === 'Alta') bg-red-100 text-red-700
                @elseif($cambios->Prioridad === 'Media') bg-yellow-100 text-yellow-700
                @else bg-green-100 text-green-700
                @endif">
                Prioridad:  {{ $cambios->Prioridad ?? 'N/A' }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Elemento afectado
                </h2>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-500">Nombre Elemento</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $cambios->elemento->nombre_elemento ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-500">Folio Elemento</span>
                        <p class="font-medium">
                            {{ $cambios->elemento->folio_elemento ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-500">Estado Elemento</span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                            {{ $cambios->elemento->status }}
                        </span>
                    </div>

                    <div>
                        <span class="text-gray-500">Responsable Elemento</span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                            {{ $cambios->elemento->puestoResponsable->nombre }}
                        </span>
                    </div>

                    <div>
                        <span class="text-gray-500">Creado</span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                            {{ ($cambios->elemento->created_at)->format('Y-m-d') ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Tipo Proceso</span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                            {{ $cambios->elemento->tipoProceso->nombre ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Tipo Elemento</span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                            {{ $cambios->elemento->tipoElemento->nombre ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Detalle del cambio
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Naturaleza</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $cambios->Naturaleza ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-500">Afectación</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $cambios->Afectacion ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-500">Estado actual</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $cambios->DetalleStatus ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-gray-500">Seguimiento</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $cambios->Seguimiento ?? 'N/A' }}
                        </p>
                    </div>
                </div>

                <div>
                    <span class="text-gray-500 text-sm">Descripción</span>
                    <p class="mt-1 text-gray-800 dark:text-gray-200 leading-relaxed">
                        {{ $cambios->Descripcion ?? 'N/A' }}
                    </p>
                </div>

                <div>
                    <span class="text-gray-500 text-sm">Redacción del cambio</span>
                    <p class="mt-1 text-gray-800 dark:text-gray-200 leading-relaxed">
                        {{ $cambios->RedaccionCambio ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                Historial del cambio
            </h2>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ $cambios->HistorialStatus ?? 'Sin historial registrado.' }}
            </p>
        </div>

    </div>
</x-app-layout>
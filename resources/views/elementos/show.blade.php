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
                            'Publicado' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            'En Firmas' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            'Rechazado' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            };
                            @endphp

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                {{ $elemento->status ?? 'Sin estatus' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('elementos.edit', $elemento->id_elemento) }}"
                            class="px-4 py-2 text-sm rounded-lg bg-green-600 text-white hover:bg-green-700">
                            Editar
                        </a>
                        <a href="{{ route('elementos.index') }}"
                            class="px-4 py-2 text-sm rounded-lg bg-gray-500 text-white hover:bg-gray-600">
                            Volver
                        </a>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Información Básica
                        </h3>

                        @php
                        $infoBasica = [
                        'Tipo de Proceso' => $elemento->tipoProceso->nombre ?? 'Sin tipo de proceso',
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
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                           bg-blue-100 text-blue-800
                           dark:bg-blue-900/30 dark:text-blue-300">
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

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Puesto Responsable</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $elemento->puestoResponsable->nombre ?? 'N/A' }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Fecha del Elemento</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->fecha_elemento ? $elemento->fecha_elemento ?->format('d/m/Y') : 'Sin fecha' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Puesto Ejecutor</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->puestoEjecutor->nombre ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Puesto de Resguardo</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->puestoResguardo->nombre ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Medio de Soporte</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($elemento->medio_soporte) ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Ubicación de Resguardo</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->ubicacion_resguardo ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Periodo de Resguardo</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $elemento->periodo_resguardo ? $elemento->periodo_resguardo->format('d/m/Y') : 'Sin fecha' }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Documento del Elemento
                        </h3>

                        @php
                        $archivoMostrar = $elemento->archivo_actual;
                        $archivoMostrarUrl = $elemento->archivo_actual_url;
                        $extension = $archivoMostrar ? strtolower(pathinfo($archivoMostrar, PATHINFO_EXTENSION)) : null;
                        $esDocumentoOficial = $archivoMostrar === \App\Models\Elemento::normalizePathForPublicDisk($elemento->archivo_firmado);
                        @endphp

                        @if($archivoMostrar && $archivoMostrarUrl)
                        @if($extension === 'pdf')
                        <div class="mt-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-lg">
                            <div class="bg-gray-100 dark:bg-gray-900 px-4 py-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Vista previa del documento
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ strtoupper($extension) }}
                                </span>
                            </div>

                            <iframe
                                src="{{ $archivoMostrarUrl }}"
                                class="w-full"
                                style="height: 600px; border: 0;"
                                type="application/pdf">
                            </iframe>
                        </div>
                        @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <div class="mt-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <img
                                src="{{ $archivoMostrarUrl }}"
                                alt="Documento"
                                class="w-full h-auto">
                        </div>
                        @else
                        <div class="mt-4 text-center p-8 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Vista previa no disponible para este tipo de archivo
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Tipo: {{ strtoupper($extension) }}
                            </p>

                            <a
                                href="{{ $archivoMostrarUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                Abrir documento
                            </a>
                        </div>
                        @endif
                        @endif
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

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Archivo del Formato</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if($elemento->es_formato === 'si' && $elemento->archivo_formato)
                                <div>
                                    <a href="{{ Storage::url($elemento->archivo_formato) }}" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        Descargar archivo
                                    </a>
                                </div>
                                @else
                                Sin formato asociado
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Elemento al que pertenece</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if(!$elemento->elementoPadre)
                                No pertenece a ningún elemento
                                @else
                                <a href="{{ route('elementos.show', $elemento->elementoPadre->id_elemento) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $elemento->elementoPadre->nombre_elemento }}
                                </a>
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Elementos relacionados</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if(!$elemento->elementoRelacionado)
                                Sin elemento relacionado
                                @else
                                <a href="{{ route('elementos.show', $elemento->elementoRelacionado->id_elemento) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $elemento->elementoRelacionado->nombre_elemento }}
                                </a>
                                @endif
                            </span>
                        </div>

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

                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Elementos hijos</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if($elemento->elementosHijos->count() > 0)
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        @foreach($elemento->elementosHijos as $hijo)
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('elementos.show', $hijo->id_elemento) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $hijo->nombre_elemento }}
                                            </a>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">({{ $hijo->version_elemento }})</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @else
                                Sin elementos hijos
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 border-b pb-2">
                            Firmas del Procedimiento
                        </h3>

                        <div class="space-y-3">
                            @foreach($firmas as $firma)
                            <div class="flex items-center justify-between px-4 py-3 rounded-lg border
                                    @if($firma->estatus === 'Aprobado')
                                        border-green-200 bg-green-50 dark:bg-green-900/20
                                    @elseif($firma->estatus === 'Rechazado')
                                        border-red-200 bg-red-50 dark:bg-red-900/20
                                    @else
                                        border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20
                                    @endif">

                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ optional($firma->empleado)->nombres }}
                                        {{ optional($firma->empleado)->apellido_paterno }}
                                        {{ optional($firma->empleado)->apellido_materno }}
                                        @if($firma->empleado && $firma->empleado->trashed())
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            Inactivo
                                        </span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ optional($firma->puestoTrabajo)->nombre ?? 'Sin puesto' }} · {{ $firma->tipo }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 text-xs rounded-full font-medium
                                            @if($firma->estatus === 'Aprobado')
                                                bg-green-600 text-white
                                            @elseif($firma->estatus === 'Rechazado')
                                                bg-red-600 text-white
                                            @else
                                                bg-yellow-500 text-white
                                            @endif">
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

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 flex justify-between">
                    <span>Creado: {{ $elemento->created_at->format('d/m/Y H:i') }}</span>
                    <span>Última Actualización: {{ $elemento->updated_at->format('d/m/Y H:i') }}</span>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
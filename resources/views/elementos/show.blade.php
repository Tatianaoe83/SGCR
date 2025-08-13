<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalles del Elemento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                            {{ $elemento->nombre_elemento }}
                        </h1>
                        <div class="flex space-x-3">
                            <a href="{{ route('elementos.edit', $elemento->id_elemento) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Editar
                            </a>
                            <a href="{{ route('elementos.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                    <div class="col-span-full">
                        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Información Básica -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                            Información Básica
                                        </h3>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Elemento</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->tipoElemento->nombre ?? 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Elemento</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->nombre_elemento }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Proceso</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->tipoProceso->nombre ?? 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidad de Negocio</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->unidadNegocio->nombre ?? 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación en Eje X</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->ubicacion_eje_x }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Control</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($elemento->control) }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folio del Elemento</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->folio_elemento }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versión</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->version_elemento }}</p>
                                        </div>
                                    </div>

                                    <!-- Fechas y Responsabilidades -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                            Fechas y Responsabilidades
                                        </h3>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Elemento</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->fecha_elemento->format('d/m/Y') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Revisión</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->periodo_revision->format('d/m/Y') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Responsable</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->puestoResponsable->nombre ?? 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Ejecutor</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->puestoEjecutor->nombre ?? 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto de Resguardo</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->puestoResguardo->nombre ?? 'N/A' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medio de Soporte</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($elemento->medio_soporte) }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación de Resguardo</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->ubicacion_resguardo }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Resguardo</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->periodo_resguardo->format('d/m/Y') }}</p>
                                        </div>
                                    </div>

                                    <!-- Relaciones y Archivos -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                            Relaciones y Archivos
                                        </h3>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">¿Es Formato?</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($elemento->es_formato) }}</p>
                                        </div>

                                        @if($elemento->es_formato === 'si' && $elemento->archivo_formato)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo del Formato</label>
                                                <a href="{{ Storage::url($elemento->archivo_formato) }}" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Descargar archivo
                                                </a>
                                            </div>
                                        @endif

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elemento Padre</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                @if($elemento->elementoPadre)
                                                    <a href="{{ route('elementos.show', $elemento->elementoPadre->id_elemento) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                        {{ $elemento->elementoPadre->nombre_elemento }}
                                                    </a>
                                                @else
                                                    Sin elemento padre
                                                @endif
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elemento Relacionado</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                @if($elemento->elementoRelacionado)
                                                    <a href="{{ route('elementos.show', $elemento->elementoRelacionado->id_elemento) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                        {{ $elemento->elementoRelacionado->nombre_elemento }}
                                                    </a>
                                                @else
                                                    Sin elemento relacionado
                                                @endif
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo de IMPLEMENTACIÓN</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->correo_implementacion ? 'Sí' : 'No' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo de AGRADECIMIENTO</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($elemento->correo_agradecimiento) }}</p>
                                        </div>

                                        @if($elemento->correo_agradecimiento === 'si' && $elemento->archivo_agradecimiento)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo de Agradecimiento</label>
                                                <a href="{{ Storage::url($elemento->archivo_agradecimiento) }}" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Descargar archivo
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Elementos Hijos -->
                                    @if($elemento->elementosHijos->count() > 0)
                                        <div class="space-y-4">
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                                Elementos Hijos
                                            </h3>
                                            
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
                                    @endif
                                </div>

                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                                        <span>Creado: {{ $elemento->created_at->format('d/m/Y H:i') }}</span>
                                        <span>Actualizado: {{ $elemento->updated_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

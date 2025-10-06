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
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->fecha_elemento ? $elemento->fecha_elemento ?->format('d/m/Y') : 'Sin fecha' }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Revisión</label>
                                            <div class="flex items-center space-x-3">
                                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->periodo_revision ? $elemento->periodo_revision->format('d/m/Y') : 'Sin fecha' }}</p>
                                                @if($elemento->periodo_revision)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $elemento->clase_semaforo }}">
                                                    {{ $elemento->texto_semaforo }}
                                                </span>
                                                @endif
                                            </div>
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
                                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $elemento->periodo_resguardo ? $elemento->periodo_resguardo->format('d/m/Y') : 'Sin fecha' }}</p>
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

                    <!-- Sección de Correos -->
                    <div class="mt-8 hidden">
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
                            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Configuración de Correos</h2>
                            </header>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Usuarios del Sistema -->
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Usuarios del Sistema</h3>
                                        @if($elemento->usuarios_correo && count($elemento->usuarios_correo) > 0)
                                        <div class="space-y-2">
                                            @foreach($elemento->usuariosCorreo() as $user)
                                            <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                <div class="flex-shrink-0">
                                                    @if($user->profile_photo_url)
                                                    <img class="h-8 w-8 rounded-full" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                                                    @else
                                                    <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM8 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                    </div>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay usuarios seleccionados</p>
                                        @endif
                                    </div>

                                    <!-- Correos Adicionales -->
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Correos Adicionales</h3>
                                        @if($elemento->correos_libres && count($elemento->correos_libres) > 0)
                                        <div class="space-y-2">
                                            @foreach($elemento->correos_libres as $correo)
                                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <span class="text-sm text-blue-700 dark:text-blue-300">{{ $correo }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay correos adicionales configurados</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Resumen Total -->
                                <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                                    <h4 class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">Total de Correos Configurados</h4>
                                    <p class="text-sm text-green-700 dark:text-green-300">
                                        <span class="font-medium">{{ $elemento->getAllCorreosAttribute()->count() }}</span> correos recibirán notificaciones
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
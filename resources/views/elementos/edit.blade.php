<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Elemento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                        Editar Elemento: {{ $elemento->nombre_elemento }}
                    </h1>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                    <div class="col-span-full">
                        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                            <form action="{{ route('elementos.update', $elemento->id_elemento) }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6">
                                @csrf
                                @method('PUT')
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Tipo de Elemento -->
                                    <div>
                                        <label for="tipo_elemento_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Elemento *</label>
                                        <select name="tipo_elemento_id" id="tipo_elemento_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Seleccionar tipo</option>
                                            @foreach($tiposElemento as $tipo)
                                                <option value="{{ $tipo->id_tipo_elemento }}" {{ old('tipo_elemento_id', $elemento->tipo_elemento_id) == $tipo->id_tipo_elemento ? 'selected' : '' }}>
                                                    {{ $tipo->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('tipo_elemento_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Nombre del Elemento -->
                                    <div>
                                        <label for="nombre_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Elemento *</label>
                                        <input type="text" name="nombre_elemento" id="nombre_elemento" value="{{ old('nombre_elemento', $elemento->nombre_elemento) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('nombre_elemento')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Tipo de Proceso -->
                                    <div>
                                        <label for="tipo_proceso_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Proceso *</label>
                                        <select name="tipo_proceso_id" id="tipo_proceso_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Seleccionar proceso</option>
                                            @foreach($tiposProceso as $proceso)
                                                <option value="{{ $proceso->id_tipo_proceso }}" {{ old('tipo_proceso_id', $elemento->tipo_proceso_id) == $proceso->id_tipo_proceso ? 'selected' : '' }}>
                                                    {{ $proceso->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('tipo_proceso_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Unidad de Negocio -->
                                    <div>
                                        <label for="unidad_negocio_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidad de Negocio *</label>
                                        <select name="unidad_negocio_id" id="unidad_negocio_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Seleccionar unidad</option>
                                            @foreach($unidadesNegocio as $unidad)
                                                <option value="{{ $unidad->id_unidad_negocio }}" {{ old('unidad_negocio_id', $elemento->unidad_negocio_id) == $unidad->id_unidad_negocio ? 'selected' : '' }}>
                                                    {{ $unidad->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unidad_negocio_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Ubicación en Eje X -->
                                    <div>
                                        <label for="ubicacion_eje_x" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación en Eje X *</label>
                                        <input type="number" name="ubicacion_eje_x" id="ubicacion_eje_x" value="{{ old('ubicacion_eje_x', $elemento->ubicacion_eje_x) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('ubicacion_eje_x')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Control -->
                                    <div>
                                        <label for="control" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Control *</label>
                                        <select name="control" id="control" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="interno" {{ old('control', $elemento->control) == 'interno' ? 'selected' : '' }}>Interno</option>
                                            <option value="externo" {{ old('control', $elemento->control) == 'externo' ? 'selected' : '' }}>Externo</option>
                                        </select>
                                        @error('control')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Folio -->
                                    <div>
                                        <label for="folio_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folio del Elemento *</label>
                                        <input type="text" name="folio_elemento" id="folio_elemento" value="{{ old('folio_elemento', $elemento->folio_elemento) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('folio_elemento')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Versión -->
                                    <div>
                                        <label for="version_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versión *</label>
                                        <input type="number" name="version_elemento" id="version_elemento" value="{{ old('version_elemento', $elemento->version_elemento) }}" step="0.1" min="0.1" max="99.9" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('version_elemento')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Fecha del Elemento -->
                                    <div>
                                        <label for="fecha_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Elemento *</label>
                                        <input type="date" name="fecha_elemento" id="fecha_elemento" value="{{ old('fecha_elemento', $elemento->fecha_elemento->format('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('fecha_elemento')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Periodo de Revisión -->
                                    <div>
                                        <label for="periodo_revision" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Revisión *</label>
                                        <input type="date" name="periodo_revision" id="periodo_revision" value="{{ old('periodo_revision', $elemento->periodo_revision->format('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('periodo_revision')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Puesto Responsable -->
                                    <div>
                                        <label for="puesto_responsable_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Responsable *</label>
                                        <select name="puesto_responsable_id" id="puesto_responsable_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Seleccionar puesto</option>
                                            @foreach($puestosTrabajo as $puesto)
                                                <option value="{{ $puesto->id_puesto_trabajo }}" {{ old('puesto_responsable_id', $elemento->puesto_responsable_id) == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
                                                    {{ $puesto->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('puesto_responsable_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Es Formato -->
                                    <div>
                                        <label for="es_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">¿Es Formato? *</label>
                                        <select name="es_formato" id="es_formato" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="no" {{ old('es_formato', $elemento->es_formato) == 'no' ? 'selected' : '' }}>No</option>
                                            <option value="si" {{ old('es_formato', $elemento->es_formato) == 'si' ? 'selected' : '' }}>Sí</option>
                                        </select>
                                        @error('es_formato')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Archivo Formato -->
                                    <div id="archivo_formato_div" class="{{ $elemento->es_formato === 'si' ? '' : 'hidden' }}">
                                        <label for="archivo_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo del Formato</label>
                                        @if($elemento->archivo_formato)
                                            <div class="mt-1 mb-2">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Archivo actual: 
                                                    <a href="{{ Storage::url($elemento->archivo_formato) }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                        Ver archivo actual
                                                    </a>
                                                </p>
                                            </div>
                                        @endif
                                        <input type="file" name="archivo_formato" id="archivo_formato" accept=".pdf,.doc,.docx,.xls,.xlsx" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('archivo_formato')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Puesto Ejecutor -->
                                    <div>
                                        <label for="puesto_ejecutor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Ejecutor *</label>
                                        <select name="puesto_ejecutor_id" id="puesto_ejecutor_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Seleccionar puesto</option>
                                            @foreach($puestosTrabajo as $puesto)
                                                <option value="{{ $puesto->id_puesto_trabajo }}" {{ old('puesto_ejecutor_id', $elemento->puesto_ejecutor_id) == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
                                                    {{ $puesto->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('puesto_ejecutor_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Puesto Resguardo -->
                                    <div>
                                        <label for="puesto_resguardo_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto de Resguardo *</label>
                                        <select name="puesto_resguardo_id" id="puesto_resguardo_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Seleccionar puesto</option>
                                            @foreach($puestosTrabajo as $puesto)
                                                <option value="{{ $puesto->id_puesto_trabajo }}" {{ old('puesto_resguardo_id', $elemento->puesto_resguardo_id) == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
                                                    {{ $puesto->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('puesto_resguardo_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Medio de Soporte -->
                                    <div>
                                        <label for="medio_soporte" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medio de Soporte *</label>
                                        <select name="medio_soporte" id="medio_soporte" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="digital" {{ old('medio_soporte', $elemento->medio_soporte) == 'digital' ? 'selected' : '' }}>Digital</option>
                                            <option value="fisico" {{ old('medio_soporte', $elemento->medio_soporte) == 'fisico' ? 'selected' : '' }}>Físico</option>
                                        </select>
                                        @error('medio_soporte')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Ubicación de Resguardo -->
                                    <div>
                                        <label for="ubicacion_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación de Resguardo *</label>
                                        <input type="text" name="ubicacion_resguardo" id="ubicacion_resguardo" value="{{ old('ubicacion_resguardo', $elemento->ubicacion_resguardo) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('ubicacion_resguardo')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Periodo de Resguardo -->
                                    <div>
                                        <label for="periodo_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Resguardo *</label>
                                        <input type="date" name="periodo_resguardo" id="periodo_resguardo" value="{{ old('periodo_resguardo', $elemento->periodo_resguardo->format('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('periodo_resguardo')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Elemento Padre -->
                                    <div>
                                        <label for="elemento_padre_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elemento Padre</label>
                                        <select name="elemento_padre_id" id="elemento_padre_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Sin elemento padre</option>
                                            @foreach($elementos as $elem)
                                                <option value="{{ $elem->id_elemento }}" {{ old('elemento_padre_id', $elemento->elemento_padre_id) == $elem->id_elemento ? 'selected' : '' }}>
                                                    {{ $elem->nombre_elemento }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('elemento_padre_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Elemento Relacionado -->
                                    <div>
                                        <label for="elemento_relacionado_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elemento Relacionado</label>
                                        <select name="elemento_relacionado_id" id="elemento_relacionado_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">Sin elemento relacionado</option>
                                            @foreach($elementos as $elem)
                                                <option value="{{ $elem->id_elemento }}" {{ old('elemento_relacionado_id', $elemento->elemento_relacionado_id) == $elem->id_elemento ? 'selected' : '' }}>
                                                    {{ $elem->nombre_elemento }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('elemento_relacionado_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Correo Implementación -->
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="correo_implementacion" value="1" {{ old('correo_implementacion', $elemento->correo_implementacion) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Correo de IMPLEMENTACIÓN</span>
                                        </label>
                                        @error('correo_implementacion')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Correo Agradecimiento -->
                                    <div>
                                        <label for="correo_agradecimiento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo de AGRADECIMIENTO *</label>
                                        <select name="correo_agradecimiento" id="correo_agradecimiento" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="no" {{ old('correo_agradecimiento', $elemento->correo_agradecimiento) == 'no' ? 'selected' : '' }}>No</option>
                                            <option value="si" {{ old('correo_agradecimiento', $elemento->correo_agradecimiento) == 'si' ? 'selected' : '' }}>Sí</option>
                                        </select>
                                        @error('correo_agradecimiento')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Archivo Agradecimiento -->
                                    <div id="archivo_agradecimiento_div" class="{{ $elemento->correo_agradecimiento === 'si' ? '' : 'hidden' }}">
                                        <label for="archivo_agradecimiento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo de Agradecimiento</label>
                                        @if($elemento->archivo_agradecimiento)
                                            <div class="mt-1 mb-2">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Archivo actual: 
                                                    <a href="{{ Storage::url($elemento->archivo_agradecimiento) }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                        Ver archivo actual
                                                    </a>
                                                </p>
                                            </div>
                                        @endif
                                        <input type="file" name="archivo_agradecimiento" id="archivo_agradecimiento" accept=".pdf,.doc,.docx" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('archivo_agradecimiento')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end space-x-3">
                                    <a href="{{ route('elementos.show', $elemento->id_elemento) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Actualizar Elemento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar campos de archivo según selección
        document.getElementById('es_formato').addEventListener('change', function() {
            const archivoDiv = document.getElementById('archivo_formato_div');
            if (this.value === 'si') {
                archivoDiv.classList.remove('hidden');
            } else {
                archivoDiv.classList.add('hidden');
            }
        });

        document.getElementById('correo_agradecimiento').addEventListener('change', function() {
            const archivoDiv = document.getElementById('archivo_agradecimiento_div');
            if (this.value === 'si') {
                archivoDiv.classList.remove('hidden');
            } else {
                archivoDiv.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>

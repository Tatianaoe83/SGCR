<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $elemento->nombre_elemento }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('elementos.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                        </svg>

                        <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Tipo de Elemento Actual -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg rounded-lg border border-indigo-200 dark:border-indigo-800 mb-6">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-white text-indigo-600 font-bold text-lg shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-white">Tipo de Elemento</h3>
                        <p class="text-indigo-100 text-sm">El tipo de elemento no se puede modificar</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-inner">
                    <label for="tipo_elemento_id" class="block text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Tipo de Elemento
                        </span>
                    </label>
                    <!-- Campo hidden para enviar el valor en el formulario -->
                    <input type="hidden" name="tipo_elemento_id" value="{{ $elemento->tipo_elemento_id }}">
                    <select id="tipo_elemento_id" class="select2 block w-full border-2 border-indigo-300 dark:border-indigo-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg py-3 bg-gray-100 dark:bg-gray-900 cursor-not-allowed" disabled data-placeholder="Seleccionar tipo de elemento">
                        <option value="">Seleccionar tipo</option>
                        @foreach($tiposElemento as $tipo)
                        <option value="{{ $tipo->id_tipo_elemento }}" {{ old('tipo_elemento_id', $elemento->tipo_elemento_id) == $tipo->id_tipo_elemento ? 'selected' : '' }}>
                            {{ $tipo->nombre }}
                        </option>
                        @endforeach
                    </select>
                    @error('tipo_elemento_id')
                    <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Principal -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles del Elemento</h2>
            </header>
            <div class="p-6">
                <form action="{{ route('elementos.update', $elemento->id_elemento) }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6" id="form-save">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tipo_elemento_id" value="{{ $elemento->tipo_elemento_id }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Nombre del Elemento -->
                        <div data-campo>
                            <label for="nombre_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Elemento</label>
                            <input type="text" name="nombre_elemento" id="nombre_elemento" value="{{ old('nombre_elemento', $elemento->nombre_elemento) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('nombre_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tipo de Proceso -->
                        <div data-campo>
                            <label for="tipo_proceso_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Proceso</label>
                            <select name="tipo_proceso_id" id="tipo_proceso_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                        <div data-campo>
                            <label for="unidad_negocio_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidad de Negocio</label>
                            <select
                                name="unidad_negocio_id[]"
                                multiple
                                id="unidad_negocio_id"
                                class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @php
                                $unidadNegocioOld = old('unidad_negocio_id');
                                if ($unidadNegocioOld !== null) {
                                $unidadNegocioIds = is_array($unidadNegocioOld) ? $unidadNegocioOld : [$unidadNegocioOld];
                                } else {
                                $unidadNegocioValue = $elemento->unidad_negocio_id ?? null;

                                // Si es un string JSON, decodificarlo
                                if (is_string($unidadNegocioValue) && !empty($unidadNegocioValue)) {
                                $decoded = json_decode($unidadNegocioValue, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $unidadNegocioIds = $decoded;
                                } else {
                                $unidadNegocioIds = $unidadNegocioValue ? [(string) $unidadNegocioValue] : [];
                                }
                                } elseif (is_array($unidadNegocioValue)) {
                                $unidadNegocioIds = $unidadNegocioValue;
                                } else {
                                $unidadNegocioIds = $unidadNegocioValue ? [(string) $unidadNegocioValue] : [];
                                }
                                }
                                $unidadNegocioIds = array_map('strval', $unidadNegocioIds);
                                @endphp
                                @foreach($unidadesNegocio as $unidad)
                                <option
                                    value="{{ $unidad->id_unidad_negocio }}"
                                    @if(in_array((string) $unidad->id_unidad_negocio, $unidadNegocioIds)) selected @endif>
                                    {{ $unidad->nombre }}
                                </option>
                                @endforeach
                            </select>
                            @error('unidad_negocio_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Ubicación en Eje X -->
                        <div data-campo>
                            <label for="ubicacion_eje_x" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación en Eje X</label>
                            <input type="number" name="ubicacion_eje_x" id="ubicacion_eje_x" value="{{ old('ubicacion_eje_x',$elemento->ubicacion_eje_x) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('ubicacion_eje_x')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Control -->
                        <div data-campo>
                            <label for="control" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Control</label>
                            <select name="control" id="control" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="interno" {{ old('control', $elemento->control) == 'interno' ? 'selected' : '' }}>Interno</option>
                                <option value="externo" {{ old('control', $elemento->control) == 'externo' ? 'selected' : '' }}>Externo</option>
                            </select>
                            @error('control')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Folio -->
                        <div data-campo>
                            <label for="folio_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folio del Elemento</label>
                            <input type="text" name="folio_elemento" id="folio_elemento" value="{{ old('folio_elemento', $elemento->folio_elemento) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('folio_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Versión -->
                        <div data-campo>
                            <label for="version_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versión</label>
                            <input type="number" name="version_elemento" id="version_elemento" value="{{ old('version_elemento', $elemento->version_elemento) }}" step="0.1" min="0.1" max="99.9" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('version_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Fecha del Elemento -->
                        <div data-campo>
                            <label for="fecha_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Elemento</label>
                            <input type="date" name="fecha_elemento" id="fecha_elemento" value="{{ old('fecha_elemento', $elemento->fecha_elemento ? $elemento->fecha_elemento->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('fecha_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Periodo de Revisión -->
                        <div data-campo>
                            <label for="periodo_revision" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Revisión</label>
                            <input type="date" name="periodo_revision" id="periodo_revision" value="{{ old('periodo_revision', $elemento->periodo_revision ? $elemento->periodo_revision->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('periodo_revision')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <!-- Semáforo de Estado -->
                            <div id="semaforo-container" class="mt-2 {{ $elemento->periodo_revision ? '' : 'hidden' }}">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Estado:</span>
                                    <span id="estado-semaforo"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    </span>
                                    <span id="info-semaforo" class="text-xs ml-2"></span>
                                </div>
                            </div>

                        </div>

                        <!-- Puesto Responsable -->
                        <div data-campo>
                            <label for="puesto_responsable_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Responsable</label>
                            <select name="puesto_responsable_id" id="puesto_responsable_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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

                        <!-- Puesto Ejecutor -->
                        <div data-campo>
                            <label for="puesto_ejecutor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Ejecutor</label>
                            <select name="puesto_ejecutor_id" id="puesto_ejecutor_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                        <div data-campo>
                            <label for="puesto_resguardo_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto de Resguardo</label>
                            <select name="puesto_resguardo_id" id="puesto_resguardo_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                        <div data-campo>
                            <label for="medio_soporte" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medio de Soporte</label>
                            <select name="medio_soporte" id="medio_soporte" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="digital" {{ old('medio_soporte', $elemento->medio_soporte) == 'digital' ? 'selected' : '' }}>Digital</option>
                                <option value="fisico" {{ old('medio_soporte', $elemento->medio_soporte) == 'fisico' ? 'selected' : '' }}>Físico</option>
                            </select>
                            @error('medio_soporte')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Ubicación de Resguardo -->
                        <div data-campo>
                            <label for="ubicacion_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación de Resguardo</label>
                            <input type="text" name="ubicacion_resguardo" id="ubicacion_resguardo" value="{{ old('ubicacion_resguardo', $elemento->ubicacion_resguardo) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('ubicacion_resguardo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Periodo de Resguardo -->
                        <div data-campo>
                            <label for="periodo_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Resguardo</label>
                            <input type="date" name="periodo_resguardo" id="periodo_resguardo" value="{{ old('periodo_resguardo', $elemento->periodo_resguardo ? $elemento->periodo_resguardo->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('periodo_resguardo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Es Formato -->
                        <div data-campo>
                            <label for="es_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">¿Es Formato?</label>
                            <select name="es_formato" id="es_formato" class="form-select mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="no" {{ old('es_formato', $elemento->es_formato) == 'no' ? 'selected' : '' }}>No</option>
                                <option value="si" {{ old('es_formato', $elemento->es_formato) == 'si' ? 'selected' : '' }}>Sí</option>
                            </select>
                            @error('es_formato')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Archivo Formato -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            <div id="archivo_formato_div" class="hidden">
                                <label for="archivo_formato" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                    Archivo del Formato
                                </label>

                                @if($elemento->archivo_formato)
                                <div class="mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm text-green-800 dark:text-green-200 font-medium">Archivo existente</span>
                                        </div>
                                        <a href="{{ Storage::url($elemento->archivo_formato) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                            Ver archivo
                                        </a>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Sube un nuevo archivo para reemplazarlo</p>
                                </div>
                                @endif

                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-4-4m0 0a4 4 0 018 0m0 0a4 4 0 018 0m0 0a4 4 0 01-4 4m-4 4h.01M12 12v4m0 0l-2 2m2-2l2 2" />
                                    </svg>
                                    <input type="file" name="archivo_formato" id="archivo_formato"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx"
                                        class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">PDF, DOCX, XLSX</p>
                                </div>
                                @error('archivo_formato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="archivo_elemento_div">
                                <label for="archivo_es_formato" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                    Archivo del Elemento
                                </label>

                                @if($elemento->archivo_es_formato)
                                <div class="mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm text-green-800 dark:text-green-200 font-medium">Archivo existente</span>
                                        </div>
                                        <a href="{{ Storage::url($elemento->archivo_es_formato) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                            Ver archivo
                                        </a>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Sube un nuevo archivo para reemplazarlo</p>
                                </div>
                                @endif
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-4-4m0 0a4 4 0 018 0m0 0a4 4 0 018 0m0 0a4 4 0 01-4 4m-4 4h.01M12 12v4m0 0l-2 2m2-2l2 2" />
                                    </svg>
                                    <input type="file" name="archivo_es_formato" id="archivo_es_formato"
                                        accept=".docx"
                                        class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">DOCX</p>
                                </div>
                                @error('archivo_formato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Relaciones -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Relaciones del Elemento</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Elemento Padre (Único) -->
                            <div class="col-span-full" data-relacion="elemento_padre_id">
                                <label for="elemento_padre_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elemento al que pertenece</label>

                                <!-- Filtro por tipo de elemento -->
                                <div class="mb-3">
                                    <label for="filtro_tipo_elemento" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Filtrar por tipo de elemento</label>
                                    <select id="filtro_tipo_elemento" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-placeholder="Todos los tipos">
                                        <option value="">Todos los tipos</option>
                                        @foreach($tiposElemento as $tipo)
                                        <option value="{{ $tipo->id_tipo_elemento }}">{{ $tipo->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <input type="text" name="" id="IdElemento" class="hidden" value="{{ $elementoID }}">

                                <select name="elemento_padre_id" id="elemento_padre_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Seleccionar elemento padre</option>
                                    @foreach($elementos as $elemento)
                                    <option value="{{ $elemento->id_elemento }}"
                                        data-tipo="{{ $elemento->tipo_elemento_id }}"
                                        {{ old('elemento_padre_id', $elementoPadreId) == $elemento->id_elemento ? 'selected' : '' }}>
                                        {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
                                    </option>
                                    @endforeach
                                </select>

                                <!-- Contador de elementos disponibles -->
                                <div id="contador-elementos" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ count($elementos) }} elementos disponibles
                                </div>

                                @error('elemento_padre_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Elementos Relacionados (Múltiples) -->
                            <div class="col-span-full" data-relacion="elemento_relacionado_id">
                                <label for="elementos_relacionados[]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elementos Relacionados</label>
                                <select name="elemento_relacionado_id[]" id="elemento_relacionado_id"
                                    multiple
                                    class="select2-multiple mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($elementos as $e)
                                    <option value="{{ $e->id_elemento }}"
                                        {{ in_array($e->id_elemento, (array) old('elemento_relacionado_id', $elementosRelacionados)) ? 'selected' : '' }}>
                                        {{ $e->nombre_elemento }} - {{ $e->folio_elemento }}
                                    </option>
                                    @endforeach
                                </select>

                                @error('elementos_relacionados')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Puestos de Trabajo Relacionados (Múltiples) -->
                            <div class="col-span-full" data-relacion="puestos_relacionados">
                                <label for="puestos_relacionados[]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puestos de Trabajo Relacionados</label>

                                <!-- Filtros de búsqueda -->
                                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                        <!-- Filtro por División -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por División</label>
                                            <select id="filtro_division" class="select2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-placeholder="Todas las divisiones">
                                                <option value="">Todas las divisiones</option>
                                                @foreach($divisions ?? [] as $division)
                                                <option value="{{ $division->id_division }}">{{ $division->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Filtro por Unidad de Negocio -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por Unidad</label>
                                            <select id="filtro_unidad" class="select2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-placeholder="Todas las unidades">
                                                <option value="">Todas las unidades</option>
                                            </select>
                                        </div>

                                        <!-- Filtro por Área -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por Área</label>
                                            <select id="filtro_area" class="select2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-placeholder="Todas las áreas">
                                                <option value="">Todas las áreas</option>
                                            </select>
                                        </div>

                                        <!-- Búsqueda por texto -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar por nombre</label>
                                            <input type="text" id="busqueda_texto" placeholder="Buscar puestos..." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>

                                    <!-- Controles de selección -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <button type="button" id="select_all" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md">
                                                Seleccionar Todos
                                            </button>
                                            <button type="button" id="deselect_all" class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-md">
                                                Deseleccionar Todos
                                            </button>
                                            <span id="contador_seleccionados" class="text-sm text-gray-600 dark:text-gray-400">
                                                0 puestos seleccionados
                                            </span>
                                        </div>
                                        <button type="button" id="limpiar_filtros" class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded-md">
                                            Limpiar Filtros
                                        </button>
                                    </div>
                                </div>

                                <!-- Lista de puestos de trabajo -->
                                <div class="max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg">
                                    <div id="lista_puestos" class="p-4 space-y-2">
                                        @foreach($puestosTrabajo as $puesto)
                                        <label class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-600 rounded cursor-pointer">
                                            <input type="checkbox"
                                                name="puestos_relacionados[]"
                                                value="{{ $puesto->id_puesto_trabajo }}"
                                                class="puesto-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                data-division="{{ $puesto->division->id_division ?? '' }}"
                                                data-unidad="{{ $puesto->unidadNegocio->id_unidad_negocio ?? '' }}"
                                                data-area="{{ $puesto->area->id_area ?? '' }}"
                                                data-nombre="{{ strtolower($puesto->nombre) }}"
                                                {{ in_array($puesto->id_puesto_trabajo, (array) old('puestos_relacionados', (array) $puestosRelacionados)) ? 'checked' : '' }}>
                                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">{{ $puesto->nombre }}</span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    - {{ $puesto->division->nombre ?? 'Sin división' }} /
                                                    {{ $puesto->unidadNegocio->nombre ?? 'Sin unidad' }} /
                                                    {{ $puesto->area->nombre ?? 'Sin área' }}
                                                </span>
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                @error('puestos_relacionados')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Campos adicionales para puestos relacionados -->
                    <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-600">
                        <h4 class="text-sm font-medium text-purple-800 dark:text-purple-200 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Información Adicional de Relación
                        </h4>

                        <!-- Contenedor de campos de nombre -->
                        <div id="campos_nombre_container" class="flex flex-col gap-2">

                            @php
                            $total = count($nombresRelacion);
                            @endphp

                            @if ($total > 0)
                            @foreach ($nombresRelacion as $i => $nombre)
                            <input type="hidden" name="relacion_id[{{ $i }}]" value="{{ $relacionIds[$i] }}">
                            <div class="flex items-center gap-2 campo-relacion fila-relacion">
                                <input
                                    name="nombres_relacion[{{ $i }}]"
                                    type="text"
                                    placeholder="Escribe el comité"
                                    value="{{ $nombre }}"
                                    class="input-relacion border border-gray-300 rounded-md px-2 py-2 text-sm
                    focus:ring-2 focus:ring-purple-500 focus:border-purple-500">

                                <select
                                    class="form-select select2 campo-relacion w-[350px]"
                                    name="puesto_id[{{ $i }}][]"
                                    multiple required>
                                    <option></option>

                                    @foreach ($grupos as $division => $unidades)
                                    <optgroup label="{{ $division }}">
                                        @foreach ($unidades as $unidad => $areas)
                                        @foreach ($areas as $area => $puestos)
                                    <optgroup label="&nbsp;&nbsp;{{ $unidad }} → {{ $area }}">
                                        @foreach ($puestos as $puesto)
                                        <option value="{{ $puesto['id'] }}"
                                            @if(in_array($puesto['id'], (array) ($puestosIds[$i] ?? []))) selected @endif>
                                            {{ $puesto['nombre'] }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                    @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>

                                <button
                                    type="button"
                                    class="btn-agregar-nombre px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                                    +
                                </button>
                            </div>
                            @endforeach

                            @else
                            <div class="flex items-center gap-3 fila-relacion">
                                <input
                                    name="nombres_relacion[0]"
                                    type="text"
                                    placeholder="Escribe el comité"
                                    class="input-relacion border border-gray-300 rounded-md px-2 py-2 text-sm
                focus:ring-2 focus:ring-purple-500 focus:border-purple-500">

                                <select
                                    class="form-select select2 campo-relacion w-[350px]"
                                    name="puesto_id[0][]"
                                    multiple required>
                                    <option></option>

                                    @foreach ($grupos as $division => $unidades)
                                    <optgroup label="{{ $division }}">
                                        @foreach ($unidades as $unidad => $areas)
                                        @foreach ($areas as $area => $puestos)
                                    <optgroup label="&nbsp;&nbsp;{{ $unidad }} → {{ $area }}">
                                        @foreach ($puestos as $puesto)
                                        <option value="{{ $puesto['id'] }}">{{ $puesto['nombre'] }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                    @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>

                            </div>
                            @endif
                        </div>
                    </div>
                    <!-- Sección de Configuraciones Adicionales -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Configuraciones Adicionales</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


                            <!-- Correo Implementación -->
                            <div data-relacion="correo_implementacion">
                                <label class="flex items-center">
                                    <input type="checkbox" name="correo_implementacion"
                                        value="1"
                                        {{ old('correo_implementacion', $correoImplementacion) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Correo de IMPLEMENTACIÓN</span>
                                </label>
                                @error('correo_implementacion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Correo Agradecimiento -->
                            <div data-relacion="correo_agradecimiento">
                                <label class="flex items-center">
                                    <input type="checkbox" name="correo_agradecimiento"
                                        value="1"
                                        {{ old('correo_agradecimiento', $correoAgradecimiento) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Correo de AGRADECIMIENTO</span>
                                </label>
                                @error('correo_agradecimiento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end space-x-2">
                        <a href="{{ route('elementos.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                            Cancelar
                        </a>
                        <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                            Actualizar Elemento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .required-outline {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, .5) !important;
        }

        .select2-container--default .select2-selection.required-outline {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, .5) !important;
        }

        .archivo-seleccionado {
            background-color: #00c444ff !important;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
    </style>
    <script>
        function initSelect2() {
            $('select.select2:not(.no-select2)').select2({
                placeholder: "Seleccionar opción",
                allowClear: true,
                width: "100%",
                closeOnSelect: false
            });

            $('select.select2-multiple').select2({
                placeholder: "Seleccionar opciones",
                allowClear: true,
                width: "100%",
                closeOnSelect: true,
                selectionCssClass: "select2--large",
                dropdownCssClass: "select2--large",
            });
        }
    </script>
    <script>
        function initFiltroElementoPadre() {
            const filtro = document.getElementById("filtro_tipo_elemento");
            const select = document.getElementById("elemento_padre_id");
            const contador = document.getElementById("contador-elementos");
            const idElemento = document.getElementById("IdElemento").value;

            if (!filtro || !select) return;

            const opcionesOriginales = select.innerHTML;

            async function aplicarFiltro() {
                const tipo = filtro.value;

                const seleccionadoOriginal = select.dataset.selected ?? select.value;

                if (!tipo) {
                    select.innerHTML = opcionesOriginales;

                    if (seleccionadoOriginal) {
                        const opt = select.querySelector(`option[value="${seleccionadoOriginal}"]`);
                        if (opt) opt.selected = true;
                    }

                    actualizarContador();
                    return;
                }

                select.innerHTML = `<option value="">Cargando...</option>`;

                try {
                    const res = await fetch(`/elementos/tipos/${tipo}?exclude=${idElemento}`);
                    const data = await res.json();

                    let html = `<option value="">Seleccionar elemento padre</option>`;

                    data.forEach(el => {
                        html += `
                    <option value="${el.id_elemento}" data-tipo="${el.tipo_elemento_id}">
                        ${el.nombre_elemento} - ${el.folio_elemento}
                    </option>
                `;
                    });

                    select.innerHTML = html;

                    if (seleccionadoOriginal) {
                        const opt = select.querySelector(`option[value="${seleccionadoOriginal}"]`);
                        if (opt) opt.selected = true;
                    }

                    actualizarContador();

                } catch (e) {
                    console.error("Error:", e);
                    select.innerHTML = `<option value="">Error al cargar elementos</option>`;
                }
            }

            function actualizarContador() {
                const etiqueta = filtro.options[filtro.selectedIndex]?.text || "";
                const total = select.querySelectorAll("option[data-tipo]").length;

                contador.textContent = filtro.value ?
                    `${total} elementos de tipo "${etiqueta}" disponibles` :
                    `${total} elementos disponibles`;
            }

            setTimeout(() => {
                select.dataset.selected = select.value;
            }, 50);

            filtro.addEventListener("change", aplicarFiltro);

            setTimeout(() => {
                if (select.value) {
                    const opt = select.querySelector(`option[value="${select.value}"]`);
                    if (opt) {
                        filtro.value = opt.dataset.tipo;
                        filtro.dispatchEvent(new Event("change"));
                    }
                }
            }, 120);
        }

        document.addEventListener("DOMContentLoaded", initFiltroElementoPadre);
    </script>
    <script>
        function initAutocompleteRelaciones() {
            document.addEventListener("focusin", function(e) {
                if (!e.target.matches(".input-relacion")) return;

                const input = e.target;
                if (input.dataset.init) return;
                input.dataset.init = "1";

                new autoComplete({
                    selector: () => input,
                    placeHolder: "Buscar comité",
                    debounce: 250,
                    data: {
                        src: async () => {
                            if (!input.value.trim()) return [];
                            const res = await fetch(`/elementos/buscar?q=${encodeURIComponent(input.value)}`);
                            return await res.json();
                        },
                        keys: ["nombre"]
                    },
                    resultItem: {
                        highlight: true,
                        element: (item, data) => {
                            item.innerHTML = `<span>${data.match}</span>
                        <small class="text-gray-400 ml-2">(${data.value.puestos.length} puestos)</small>`;
                        }
                    },
                    events: {
                        input: {
                            selection: event => {
                                const sel = event.detail.selection.value;
                                input.value = sel.nombre;

                                const select = input.closest(".fila-relacion").querySelector("select.select2");
                                $(select).val(sel.puestos.map(p => p.id.toString())).trigger("change");
                            }
                        }
                    }
                });
            });
        }

        document.addEventListener("DOMContentLoaded", initAutocompleteRelaciones);
    </script>
    <script>
        function initFilasRelacion() {
            const container = document.getElementById("campos_nombre_container");
            if (!container) return;

            document.addEventListener("click", e => {
                if (!e.target.classList.contains("btn-agregar-nombre")) return;

                const index = container.querySelectorAll(".campo-relacion").length;
                const selectHTML =
                    container.querySelector("select.select2")?.innerHTML
                    .replace(/selected="selected"/g, "")
                    .replace(/selected/g, "") || "";

                const html = `
        <div class="flex items-center gap-3 campo-relacion fila-relacion">
            <input name="nombres_relacion[${index}]" type="text"
                placeholder="Buscar comité"
                class="input-relacion border border-gray-300 rounded-md px-2 py-2 text-sm">
            <select class="select2" name="puesto_id[${index}][]" multiple required>
                ${selectHTML}
            </select>
            <button type="button" class="btn-eliminar-nombre px-3 py-2 bg-red-600 text-white rounded-md text-sm">X</button>
        </div>`;

                container.insertAdjacentHTML("beforeend", html);
                initSelect2();
            });

            document.addEventListener("click", e => {
                if (e.target.classList.contains("btn-eliminar-nombre")) {
                    e.target.closest(".campo-relacion").remove();
                }
            });
        }

        document.addEventListener("DOMContentLoaded", initFilasRelacion);
    </script>
    <script>
        function initFiltroPuestos() {
            const division = document.getElementById("filtro_division");
            const unidad = document.getElementById("filtro_unidad");
            const area = document.getElementById("filtro_area");
            const texto = document.getElementById("busqueda_texto");

            function aplicar() {
                document.querySelectorAll(".puesto-checkbox").forEach(chk => {
                    const ok =
                        (!division?.value || chk.dataset.division == division.value) &&
                        (!unidad?.value || chk.dataset.unidad == unidad.value) &&
                        (!area?.value || chk.dataset.area == area.value) &&
                        (!texto?.value || chk.dataset.nombre.toLowerCase().includes(texto.value.toLowerCase()));

                    chk.closest("label").style.display = ok ? "flex" : "none";
                });
            }

            async function cargarUnidades(divisionId) {
                if (!unidad) return;
                if (!divisionId) {
                    unidad.innerHTML = `<option value="">Todas las unidades</option>`;
                    aplicar();
                    return;
                }

                const res = await fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`);
                const data = await res.json();

                unidad.innerHTML = `<option value="">Todas las unidades</option>`;
                data.forEach(u => unidad.insertAdjacentHTML(
                    "beforeend",
                    `<option value="${u.id_unidad_negocio}">${u.nombre}</option>`
                ));
            }

            async function cargarAreas(unidadId) {
                if (!area) return;
                if (!unidadId) {
                    area.innerHTML = `<option value="">Todas las áreas</option>`;
                    aplicar();
                    return;
                }

                const res = await fetch(`/puestos-trabajo/areas/${unidadId}`);
                const data = await res.json();

                area.innerHTML = `<option value="">Todas las áreas</option>`;
                data.forEach(a => area.insertAdjacentHTML(
                    "beforeend",
                    `<option value="${a.id_area}">${a.nombre}</option>`
                ));
            }

            division?.addEventListener("change", () => {
                cargarUnidades(division.value);
                unidad.value = "";
                area.value = "";
                aplicar();
            });

            unidad?.addEventListener("change", () => {
                cargarAreas(unidad.value);
                area.value = "";
                aplicar();
            });

            area?.addEventListener("change", aplicar);
            texto?.addEventListener("input", aplicar);
        }

        document.addEventListener("DOMContentLoaded", initFiltroPuestos);
    </script>
    <script>
        function initCamposObligatorios() {

            const tipoSelect = document.getElementById("tipo_elemento_id");
            const form = document.getElementById("form-save");

            if (!tipoSelect) return;

            let camposObligatorios = [];

            function limpiarRequeridos() {
                document.querySelectorAll("[required]").forEach(el => el.removeAttribute("required"));

                document.querySelectorAll("label span.text-red-500").forEach(span => span.remove());
                document.querySelectorAll(".required-outline").forEach(el => el.classList.remove("required-outline"));
            }

            function marcarRequerido(el) {
                if (!el) return;
                el.setAttribute("required", "required");

                const label = el.closest("label") || el.closest("div")?.querySelector("label");
                if (label && !label.innerHTML.includes("*")) {
                    label.insertAdjacentHTML("beforeend", ` <span class="text-red-500">*</span>`);
                }
            }

            function ocultarTodosLosCampos() {
                document.querySelectorAll("[data-campo], [data-relacion]").forEach(div => {
                    div.classList.add("hidden");
                });
            }

            function mostrarCampo(nombre) {
                const baseName = nombre.replace(/\[\]$/, "");
                let selector = `[name="${baseName}"], [name="${baseName}[]"]`;
                const els = document.querySelectorAll(selector);

                els.forEach(el => {
                    const wrapperCampo = el.closest("[data-campo]");
                    const wrapperRelacion = document.querySelector(`[data-relacion="${nombre}"]`);

                    if (wrapperCampo) wrapperCampo.classList.remove("hidden");
                    if (wrapperRelacion) wrapperRelacion.classList.remove("hidden");
                });

                return els;
            }

            function actualizarRestriccionArchivo() {
                const archivoElementoInput = document.getElementById('archivo_es_formato');
                const tiposArchivoElemento = document.getElementById('tipos-archivo-elemento');

                if (!archivoElementoInput || !tiposArchivoElemento) return;

                const esProcedimiento = tipoSelect.value === "2";
                if (esProcedimiento) {
                    archivoElementoInput.accept = ".doc";
                    tiposArchivoElemento.textContent = "DOC";
                } else {
                    archivoElementoInput.accept = ".pdf,.doc,.docx,.xls,.xlsx";
                    tiposArchivoElemento.textContent = "PDF, DOCX, XLSX";
                }
            }

            async function cargarCampos(tipoId) {
                try {
                    const res = await fetch(`/tipos-elemento/${tipoId}/campos-obligatorios`);
                    camposObligatorios = await res.json();

                    limpiarRequeridos();
                    ocultarTodosLosCampos();

                    camposObligatorios.forEach(campo => {
                        const elementos = mostrarCampo(campo.campo_nombre);

                        elementos.forEach(el => {
                            if (campo.campo_nombre === "archivo_formato") return;
                            if (campo.campo_nombre === "archivo_es_formato") return;

                            if (campo.obligatorio) {
                                marcarRequerido(el);
                            }
                        });
                    });

                    document.getElementById("archivo_elemento_div")?.classList.remove("hidden");

                    const esFormato = document.getElementById("es_formato");
                    const divArchivoFormato = document.getElementById("archivo_formato_div");

                    if (esFormato && esFormato.value === "si") {
                        divArchivoFormato?.classList.remove("hidden");
                    } else {
                        divArchivoFormato?.classList.add("hidden");
                    }

                    actualizarRestriccionArchivo();

                } catch (err) {
                    console.error("Error cargando campos obligatorios:", err);
                }
            }

            tipoSelect.addEventListener("change", () => {
                if (tipoSelect.value) cargarCampos(tipoSelect.value);
            });

            if (tipoSelect.value) {
                setTimeout(() => cargarCampos(tipoSelect.value), 80);
            }

            form.addEventListener("submit", () => {
                document.querySelectorAll(".hidden [required]").forEach(el => el.removeAttribute("required"));
            });
        }

        document.addEventListener("DOMContentLoaded", initCamposObligatorios);
    </script>
    <script>
        function initSemaforo() {
            const input = document.getElementById("periodo_revision");
            const semCont = document.getElementById("semaforo-container");
            const estado = document.getElementById("estado-semaforo");
            const info = document.getElementById("info-semaforo");

            if (!input) return;

            function actualizar() {
                const fecha = input.value;
                if (!fecha) {
                    semCont.classList.add("hidden");
                    return;
                }

                const hoy = new Date();
                const f = new Date(fecha);
                const meses = (f.getFullYear() - hoy.getFullYear()) * 12 + (f.getMonth() - hoy.getMonth());

                let cls, txt, inf, icon;
                if (meses <= 2) {
                    cls = "bg-red-500 text-white";
                    txt = "Crítico";
                    inf = "⚠️ Revisión crítica";
                    icon = "text-red-600";
                } else if (meses <= 6) {
                    cls = "bg-yellow-500 text-black";
                    txt = "Advertencia";
                    inf = "⚠️ Revisión próxima";
                    icon = "text-yellow-600";
                } else if (meses <= 12) {
                    cls = "bg-green-500 text-white";
                    txt = "Normal";
                    inf = "✅ Revisión programada";
                    icon = "text-green-600";
                } else {
                    cls = "bg-blue-500 text-white";
                    txt = "Lejano";
                    inf = "📅 Revisión lejana";
                    icon = "text-blue-600";
                }

                estado.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cls}`;
                estado.textContent = txt;
                info.innerHTML = `<span class="${icon}">${inf}</span>`;
                semCont.classList.remove("hidden");
            }

            actualizar();
            input.addEventListener("change", actualizar);
            input.addEventListener("input", actualizar);
        }

        document.addEventListener("DOMContentLoaded", initSemaforo);
    </script>
    <script>
        function initCorreosLibres() {

            const btnAgregar = document.getElementById("agregar-correo");
            const container = document.getElementById("correos-libres-container");
            const vista = document.getElementById("vista-previa-correos");

            if (!container || !vista) return;

            function actualizarVistaPrevia() {
                let correos = [];

                document.querySelectorAll('input[name="usuarios_correo[]"]:checked').forEach(chk => {
                    const email = chk.nextElementSibling
                        ?.querySelector(".text-gray-500")
                        ?.textContent
                        ?.split(" - ")[1];

                    if (email) correos.push(email);
                });

                container.querySelectorAll('input[name="correos_libres[]"]').forEach(input => {
                    if (input.value.trim()) correos.push(input.value.trim());
                });

                if (correos.length === 0) {
                    vista.innerHTML = `<p class="italic">Selecciona usuarios o agrega correos para ver la vista previa</p>`;
                    return;
                }

                vista.innerHTML = correos
                    .map(c => `<span class="inline-block bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs mr-2 mb-1">${c}</span>`)
                    .join("");
            }

            if (btnAgregar) {
                btnAgregar.addEventListener("click", () => {
                    const row = document.createElement("div");
                    row.className = "flex items-center gap-2 mb-2";
                    row.innerHTML = `
                <input type="email" name="correos_libres[]" placeholder="correo@ejemplo.com"
                    class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 
                    rounded-md shadow-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="button" class="btn-eliminar-correo px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">-</button>
            `;
                    container.appendChild(row);
                    actualizarVistaPrevia();
                });
            }

            container.addEventListener("click", e => {
                if (e.target.classList.contains("btn-eliminar-correo")) {
                    e.target.closest(".flex")?.remove();
                    actualizarVistaPrevia();
                }
            });

            document.querySelectorAll('input[name="usuarios_correo[]"]').forEach(chk => {
                chk.addEventListener("change", actualizarVistaPrevia);
            });

            container.addEventListener("input", actualizarVistaPrevia);

            actualizarVistaPrevia();
        }

        document.addEventListener("DOMContentLoaded", initCorreosLibres);
    </script>

    <script>
        document.addEventListener("change", function(e) {
            if (!e.target.matches('input[type="file"]')) return;
            const c = e.target.closest(".border-dashed");
            if (!c) return;
            if (e.target.files.length > 0) c.classList.add("archivo-seleccionado");
            else c.classList.remove("archivo-seleccionado");
        });
    </script>
</x-app-layout>
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

                                @foreach($unidadesNegocio as $unidad)
                                <option
                                    value="{{ $unidad->id_unidad_negocio }}"
                                    @if(in_array((string) $unidad->id_unidad_negocio, old('unidad_negocio_id', $elemento->unidad_negocio_id ?? []))) selected @endif>
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
                                            d="M3 7h4l3 3h11v8a2 2 0 01-2 2H3a2 2 0 01-2-2V7z" />
                                    </svg>
                                    <input type="file" name="archivo_es_formato" id="archivo_es_formato"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx"
                                        class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                    <p id="tipos-archivo-elemento" class="mt-2 text-xs text-gray-500 dark:text-gray-400">PDF, DOCX, XLSX</p>
                                </div>
                                @error('archivo_es_formato')
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
                                    <select id="filtro_tipo_elemento" class="select2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-placeholder="Todos los tipos">
                                        <option value="">Todos los tipos</option>
                                        @foreach($tiposElemento as $tipo)
                                        <option value="{{ $tipo->id_tipo_elemento }}">{{ $tipo->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <select name="elemento_padre_id" id="elemento_padre_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                                                {{ in_array($puesto->id_puesto_trabajo, old('puestos_relacionados', $puestosRelacionados)) ? 'checked' : '' }}>
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

                                <!-- Campos adicionales para puestos relacionados -->
                                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-3">Información Adicional de Relación</h4>

                                    <!-- Contenedor de campos de nombre -->
                                    <div id="campos_nombre_container">
                                        <div class="flex items-center gap-2 mb-2">
                                            <input type="text" name="nombres_relacion[]" placeholder="Nombre" class="flex-1 border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 px-3 py-2 transition-colors duration-200">
                                            <button type="button" class="btn-agregar-nombre px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors duration-200 flex items-center cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                @error('puestos_relacionados')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
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
    </style>

    <script>
        // Inicializar Select2 en todos los campos select
        $(document).ready(function() {
            // Inicializar Select2 en campos simples
            $('.select2').select2({
                placeholder: 'Seleccionar opción',
                allowClear: true,
                width: '100%'
            });

            // Evitar limpiar el valor del tipo de proceso (para que no se envíe vacío)
            const $tipoProceso = $('#tipo_proceso_id');
            if ($tipoProceso.length) {
                $tipoProceso.select2({
                    placeholder: 'Seleccionar proceso',
                    allowClear: false,
                    width: '100%'
                });
            }

            $('.select2-multiple').select2({
                placeholder: 'Seleccionar opciones',
                allowClear: true,
                width: '100%',
                closeOnSelect: true,
                selectionCssClass: 'select2--large',
                dropdownCssClass: 'select2--large'
            });

            // Inicializar Select2 en campos múltiples
            $('.select2-multiple').select2({
                placeholder: 'Seleccionar opciones',
                allowClear: true,
                width: '100%',
                closeOnSelect: true,
                selectionCssClass: 'select2--large',
                dropdownCssClass: 'select2--large'
            });

            // Mostrar/ocultar campos de archivo según selección
            const esFormato = document.getElementById('es_formato');
            if (esFormato) {
                esFormato.addEventListener('change', function() {
                    const archivoDiv = document.getElementById('archivo_formato_div');
                    const archivoInput = document.getElementById('archivo_formato');
                    const tipoElementoSelect = document.getElementById('tipo_elemento_id');
                    const tipoElementoSeleccionado = tipoElementoSelect ? tipoElementoSelect.value : '';
                    const esProcedimiento = tipoElementoSeleccionado === '2'; // ID del tipo "Procedimiento"
                    const esFormatoWrapper = this.closest('[data-campo]');
                    const esFormatoVisible = esFormatoWrapper && !esFormatoWrapper.classList.contains('hidden');

                    // Solo procesar si el campo es_formato está visible
                    if (!esFormatoVisible) {
                        archivoDiv.classList.add('hidden');
                        archivoInput.required = false;
                        return;
                    }

                    if (this.value === 'si') {
                        archivoDiv.classList.remove('hidden');
                        archivoInput.required = true;
                        const mensajeAyuda = document.getElementById('mensaje-ayuda');
                        if (esProcedimiento) {
                            archivoInput.accept = '.doc,.docx';
                            if (mensajeAyuda) {
                                mensajeAyuda.textContent = 'Formato permitido: .DOCX. Los archivos no deben contener imágenes.';
                                mensajeAyuda.className = 'mensaje-ayuda mt-1 text-sm text-orange-600 dark:text-orange-400';
                            }
                        } else {
                            archivoInput.accept = '.pdf,.doc,.docx,.xls,.xlsx';
                            if (mensajeAyuda) {
                                mensajeAyuda.textContent = 'Formatos permitidos: PDF, DOCX, XLS, XLSX';
                                mensajeAyuda.className = 'mensaje-ayuda mt-1 text-sm text-gray-500 dark:text-gray-400';
                            }
                        }
                    } else {
                        archivoDiv.classList.add('hidden');
                        archivoInput.required = false;
                    }
                });
            }

            // Funcionalidad del buscador de puestos de trabajo
            {
                const filtroDivision = document.getElementById('filtro_division');
                const filtroUnidad = document.getElementById('filtro_unidad');
                const filtroArea = document.getElementById('filtro_area');
                const busquedaTexto = document.getElementById('busqueda_texto');
                const selectAllBtn = document.getElementById('select_all');
                const deselectAllBtn = document.getElementById('deselect_all');
                const limpiarFiltrosBtn = document.getElementById('limpiar_filtros');
                const contadorSeleccionados = document.getElementById('contador_seleccionados');
                const puestosCheckboxes = document.querySelectorAll('.puesto-checkbox');

                    // Función para actualizar contador
                    function actualizarContador() {
                        const seleccionados = document.querySelectorAll('.puesto-checkbox:checked').length;
                        if (contadorSeleccionados) {
                            contadorSeleccionados.textContent = `${seleccionados} puestos seleccionados`;
                        }
                    }

                    // Función para aplicar filtros
                    function aplicarFiltros() {
                        const divisionId = filtroDivision ? filtroDivision.value : '';
                        const unidadId = filtroUnidad ? filtroUnidad.value : '';
                        const areaId = filtroArea ? filtroArea.value : '';
                        const texto = busquedaTexto ? busquedaTexto.value.toLowerCase().trim() : '';

                        document.querySelectorAll('.puesto-checkbox').forEach(checkbox => {
                            const division = checkbox.dataset.division;
                            const unidad = checkbox.dataset.unidad;
                            const area = checkbox.dataset.area;
                            const nombre = checkbox.dataset.nombre || "";

                            let mostrar = true;

                            if (divisionId && division !== divisionId) mostrar = false;
                            if (unidadId && unidad !== unidadId) mostrar = false;
                            if (areaId && area !== areaId) mostrar = false;
                            if (texto && !nombre.includes(texto)) mostrar = false;

                            const label = checkbox.closest('label');
                            if (label) {
                                label.style.display = mostrar ? 'flex' : 'none';
                            }
                        });
                    }

                    // Cargar unidades de negocio según división
                    function cargarUnidades(divisionId) {
                        if (!filtroUnidad) return;
                        if (!divisionId) {
                            filtroUnidad.innerHTML = '<option value="">Todas las unidades</option>';
                            return;
                        }

                        fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                            .then(response => response.json())
                            .then(data => {
                                filtroUnidad.innerHTML = '<option value="">Todas las unidades</option>';
                                data.forEach(unidad => {
                                    const option = document.createElement('option');
                                    option.value = unidad.id_unidad_negocio;
                                    option.textContent = unidad.nombre;
                                    filtroUnidad.appendChild(option);
                                });
                            });
                    }

                    // Cargar áreas según unidad de negocio
                    function cargarAreas(unidadId) {
                        if (!filtroArea) return;
                        if (!unidadId) {
                            filtroArea.innerHTML = '<option value="">Todas las áreas</option>';
                            return;
                        }

                        fetch(`/puestos-trabajo/areas/${unidadId}`)
                            .then(response => response.json())
                            .then(data => {
                                filtroArea.innerHTML = '<option value="">Todas las áreas</option>';
                                data.forEach(area => {
                                    const option = document.createElement('option');
                                    option.value = area.id_area;
                                    option.textContent = area.nombre;
                                    filtroArea.appendChild(option);
                                });
                            });
                    }

                    // Event listeners para filtros
                    if (filtroDivision) {
                        filtroDivision.addEventListener('change', function() {
                            cargarUnidades(this.value);
                            if (filtroUnidad) filtroUnidad.value = '';
                            if (filtroArea) filtroArea.value = '';
                            aplicarFiltros();
                        });
                    }

                    if (filtroUnidad) {
                        filtroUnidad.addEventListener('change', function() {
                            cargarAreas(this.value);
                            if (filtroArea) filtroArea.value = '';
                            aplicarFiltros();
                        });
                    }

                    if (filtroArea) filtroArea.addEventListener('change', aplicarFiltros);
                    if (busquedaTexto) busquedaTexto.addEventListener('input', aplicarFiltros);

                    // Botón seleccionar todos
                    if (selectAllBtn) {
                        selectAllBtn.addEventListener('click', function() {
                            const checkboxesVisibles = document.querySelectorAll('.puesto-checkbox');
                            checkboxesVisibles.forEach(checkbox => {
                                const label = checkbox.closest('label');
                                if (label && label.style.display !== 'none') {
                                    checkbox.checked = true;
                                }
                            });
                            actualizarContador();
                        });
                    }

                    // Botón deseleccionar todos
                    if (deselectAllBtn) {
                        deselectAllBtn.addEventListener('click', function() {
                            puestosCheckboxes.forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            actualizarContador();
                        });
                    }

                    // Botón limpiar filtros
                    if (limpiarFiltrosBtn) {
                        limpiarFiltrosBtn.addEventListener('click', function() {
                            if (filtroDivision) filtroDivision.value = '';
                            if (filtroUnidad) filtroUnidad.value = '';
                            if (filtroArea) filtroArea.value = '';
                            if (busquedaTexto) busquedaTexto.value = '';
                            cargarUnidades('');
                            cargarAreas('');
                            aplicarFiltros();
                        });
                    }

                    // Actualizar contador cuando cambien los checkboxes
                    puestosCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', actualizarContador);
                    });

                    // Inicializar contador
                    actualizarContador();

                // Funcionalidad para agregar campos de nombre
                document.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-agregar-nombre')) {
                        const container = document.getElementById('campos_nombre_container');
                        const nuevoCampo = document.createElement('div');
                        nuevoCampo.className = 'flex items-center gap-2 mb-2';
                        nuevoCampo.innerHTML = `
                        <input type="text" name="nombres_relacion[]" placeholder="Nombre" class="flex-1 border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                        <button type="button" class="btn-eliminar-nombre px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm cursor-pointer">
                            -
                        </button>
                    `;
                        container.appendChild(nuevoCampo);
                    }

                    if (e.target.closest('.btn-eliminar-nombre')) {
                        const fila = e.target.closest('.flex');
                        if (fila) fila.remove();
                    }
                });
            }

            // Trigger inicial para mostrar/ocultar campos de archivo - ya se maneja en cargarCampos()
            // No hacer nada aquí para evitar conflictos con la lógica de campos obligatorios

            // Event listener para el cambio del tipo de elemento
            const tipoElemento = document.getElementById('tipo_elemento_id');
            if (tipoElemento) {
                tipoElemento.addEventListener('change', function() {
                    if (esFormato && esFormato.value === 'si') {
                        esFormato.dispatchEvent(new Event('change'));
                    }
                    // Actualizar restricción de archivo del elemento según tipo
                    actualizarRestriccionArchivoElemento();
                });
            }

            // Función para actualizar restricción de archivo del elemento según tipo
            function actualizarRestriccionArchivoElemento() {
                const archivoElementoInput = document.getElementById('archivo_es_formato');
                const tiposArchivoElemento = document.getElementById('tipos-archivo-elemento');
                const tipoElementoSelect = document.getElementById('tipo_elemento_id');
                
                if (!archivoElementoInput || !tiposArchivoElemento || !tipoElementoSelect) return;
                
                const tipoElementoSeleccionado = tipoElementoSelect.value;
                const esProcedimiento = tipoElementoSeleccionado === '1'; // ID del tipo "Procedimiento"
                
                if (esProcedimiento) {
                    archivoElementoInput.accept = '.doc';
                    tiposArchivoElemento.textContent = 'DOC';
                } else {
                    archivoElementoInput.accept = '.pdf,.doc,.docx,.xls,.xlsx';
                    tiposArchivoElemento.textContent = 'PDF, DOCX, XLSX';
                }
            }

            // Aplicar restricción al cargar la página
            actualizarRestriccionArchivoElemento();
        });
    </script>
    <script>
        $(function() {
            const $tipo = $('#tipo_elemento_id');
            const form = document.getElementById('form-save');
            let camposObligatorios = [];

            function limpiarRequeridos() {
                document.querySelectorAll('input, select, textarea').forEach(el => {
                    el.removeAttribute('required');
                    el.classList.remove('required-outline');

                    const $el = $(el);
                    if ($el.data('select2')) {
                        $el.next('.select2-container')
                            .find('.select2-selection')
                            .removeClass('required-outline');
                    }

                    const label = el.closest('label') || el.closest('div')?.querySelector('label');
                    if (label) {
                        label.innerHTML = label.innerHTML.replace(/\s*<span class="text-red-500">\*<\/span>/, '');
                    }
                });

                document.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                    chk.classList.remove('required-outline');
                    chk.setCustomValidity('');
                    chk.onchange = null;
                });
            }

            function marcarRequerido(el, obligatorio = true) {
                if (!el) return;
                const name = el.getAttribute("name");

                if (el.type === "checkbox" && name && name.endsWith("[]")) {
                    const group = document.querySelectorAll(`[name="${name}"]`);
                    if (group.length > 0) {
                        if (obligatorio) {
                            group.forEach(chk => {
                                chk.classList.add("required-outline");
                                chk.onchange = () => {
                                    const algunoMarcado = [...group].some(c => c.checked);
                                    group.forEach(c => {
                                        c.setCustomValidity(algunoMarcado ? "" : "Debes seleccionar al menos un puesto.");
                                    });
                                };
                            });
                            const algunoMarcado = [...group].some(c => c.checked);
                            group.forEach(c => {
                                c.setCustomValidity(algunoMarcado ? "" : "Debes seleccionar al menos un puesto.");
                            });
                        } else {
                            group.forEach(chk => {
                                chk.classList.remove("required-outline");
                                chk.setCustomValidity("");
                                chk.onchange = null;
                            });
                        }
                    }
                    return;
                }

                if (obligatorio) {
                    el.setAttribute('required', 'required');
                } else {
                    el.removeAttribute('required');
                }

                const label = el.closest('label') || el.closest('div')?.querySelector('label');
                if (label) {
                    if (obligatorio && !label.innerHTML.includes('*')) {
                        label.insertAdjacentHTML('beforeend', ' <span class="text-red-500">*</span>');
                    }
                    if (!obligatorio) {
                        label.innerHTML = label.innerHTML.replace(/\s*<span class="text-red-500">\*<\/span>/, '');
                    }
                }

                el.classList.remove('required-outline');
            }

            async function cargarCampos(tipoId) {
                try {
                    const res = await fetch(`/tipos-elemento/${tipoId}/campos-obligatorios`);
                    camposObligatorios = await res.json();

                    limpiarRequeridos();

                    document.querySelectorAll('[data-campo], [data-relacion]').forEach(div => {
                        div.classList.add('hidden');
                        div.querySelectorAll('input, select, textarea').forEach(input => {
                            input.removeAttribute('required');
                            input.classList.remove('required-outline');
                        });
                    });

                    camposObligatorios.forEach(campo => {
                        const baseName = campo.campo_nombre.replace(/\[\]$/, '');
                        const selector = `[name="${baseName}"], [name="${baseName}[]"]`;
                        const els = document.querySelectorAll(selector);

                        if (els.length > 0) {
                            els.forEach(el => {
                                const wrapper = el.closest('[data-campo]');
                                const wrapperRelacion = document.querySelector(`[data-relacion="${campo.campo_nombre}"]`);

                                if (wrapper) wrapper.classList.remove('hidden');
                                if (wrapperRelacion) wrapperRelacion.classList.remove('hidden');

                                if (!el.closest('.hidden')) {
                                    marcarRequerido(el, campo.obligatorio);
                                }
                            });
                        } else {
                            console.warn('No se encontró el input para:', campo.campo_nombre);
                        }
                    });
                    
                    // Asegurar que el archivo del elemento siempre esté visible
                    const archivoElementoDiv = document.getElementById('archivo_elemento_div');
                    if (archivoElementoDiv) {
                        archivoElementoDiv.classList.remove('hidden');
                    }
                    
                    // Asegurar que el archivo del formato esté visible solo si es_formato está visible y es "si"
                    const esFormatoValue = document.getElementById('es_formato');
                    const archivoFormatoDiv = document.getElementById('archivo_formato_div');
                    const esFormatoWrapper = esFormatoValue ? esFormatoValue.closest('[data-campo]') : null;
                    const esFormatoVisible = esFormatoWrapper && !esFormatoWrapper.classList.contains('hidden');
                    
                    if (esFormatoValue && esFormatoVisible && esFormatoValue.value === 'si' && archivoFormatoDiv) {
                        archivoFormatoDiv.classList.remove('hidden');
                    } else if (archivoFormatoDiv) {
                        // Asegurar que esté oculto si es_formato no es visible
                        archivoFormatoDiv.classList.add('hidden');
                    }
                    
                    // Actualizar restricción de archivo del elemento según tipo
                    const archivoElementoInput = document.getElementById('archivo_es_formato');
                    const tiposArchivoElemento = document.getElementById('tipos-archivo-elemento');
                    const tipoElementoSeleccionado = tipoId;
                    const esProcedimiento = tipoElementoSeleccionado === '2';
                    if (archivoElementoInput && tiposArchivoElemento) {
                        if (esProcedimiento) {
                            archivoElementoInput.accept = '.doc';
                            tiposArchivoElemento.textContent = 'DOC';
                        } else {
                            archivoElementoInput.accept = '.pdf,.doc,.docx,.xls,.xlsx';
                            tiposArchivoElemento.textContent = 'PDF, DOCX, XLSX';
                        }
                    }
                } catch (e) {
                    console.error('Error cargando campos obligatorios:', e);
                } finally {
                    // Asegurar que el archivo del elemento siempre esté visible (incluso si hay error)
                    const archivoElementoDiv = document.getElementById('archivo_elemento_div');
                    if (archivoElementoDiv) {
                        archivoElementoDiv.classList.remove('hidden');
                    }
                    
                    // Asegurar que el archivo del formato esté visible solo si es_formato está visible y es "si"
                    const esFormatoValue = document.getElementById('es_formato');
                    const archivoFormatoDiv = document.getElementById('archivo_formato_div');
                    const esFormatoWrapper = esFormatoValue ? esFormatoValue.closest('[data-campo]') : null;
                    const esFormatoVisible = esFormatoWrapper && !esFormatoWrapper.classList.contains('hidden');
                    
                    if (esFormatoValue && esFormatoVisible && esFormatoValue.value === 'si' && archivoFormatoDiv) {
                        archivoFormatoDiv.classList.remove('hidden');
                    } else if (archivoFormatoDiv) {
                        // Asegurar que esté oculto si es_formato no es visible
                        archivoFormatoDiv.classList.add('hidden');
                    }
                    
                    // Actualizar restricción de archivo del elemento según tipo
                    const archivoElementoInput = document.getElementById('archivo_es_formato');
                    const tiposArchivoElemento = document.getElementById('tipos-archivo-elemento');
                    const tipoElementoSelect = document.getElementById('tipo_elemento_id');
                    if (archivoElementoInput && tiposArchivoElemento && tipoElementoSelect) {
                        const tipoElementoSeleccionado = tipoElementoSelect.value;
                        const esProcedimiento = tipoElementoSeleccionado === '2';
                        if (esProcedimiento) {
                            archivoElementoInput.accept = '.doc';
                            tiposArchivoElemento.textContent = 'DOC';
                        } else {
                            archivoElementoInput.accept = '.pdf,.doc,.docx,.xls,.xlsx';
                            tiposArchivoElemento.textContent = 'PDF, DOCX, XLSX';
                        }
                    }
                }
            }

            $tipo.on('change', function() {
                const tipoId = this.value;
                if (tipoId) cargarCampos(tipoId);
                else {
                    limpiarRequeridos();
                    // Asegurar que el archivo del elemento siempre esté visible
                    const archivoElementoDiv = document.getElementById('archivo_elemento_div');
                    if (archivoElementoDiv) {
                        archivoElementoDiv.classList.remove('hidden');
                    }
                    // Asegurar que el archivo del formato esté visible solo si es_formato está visible y es "si"
                    const esFormatoValue = document.getElementById('es_formato');
                    const archivoFormatoDiv = document.getElementById('archivo_formato_div');
                    const esFormatoWrapper = esFormatoValue ? esFormatoValue.closest('[data-campo]') : null;
                    const esFormatoVisible = esFormatoWrapper && !esFormatoWrapper.classList.contains('hidden');
                    
                    if (esFormatoValue && esFormatoVisible && esFormatoValue.value === 'si' && archivoFormatoDiv) {
                        archivoFormatoDiv.classList.remove('hidden');
                    } else if (archivoFormatoDiv) {
                        // Asegurar que esté oculto si es_formato no es visible
                        archivoFormatoDiv.classList.add('hidden');
                    }
                }
            });

            if ($tipo.val()) $tipo.trigger('change');
            
            // Asegurar que el archivo del elemento siempre esté visible al cargar
            const archivoElementoDivInit = document.getElementById('archivo_elemento_div');
            if (archivoElementoDivInit) {
                archivoElementoDivInit.classList.remove('hidden');
            }
            
            // Actualizar restricción de archivo del elemento según tipo al cargar
            setTimeout(function() {
                const tipoElementoSelect = document.getElementById('tipo_elemento_id');
                if (tipoElementoSelect) {
                    const archivoElementoInput = document.getElementById('archivo_es_formato');
                    const tiposArchivoElemento = document.getElementById('tipos-archivo-elemento');
                    if (archivoElementoInput && tiposArchivoElemento) {
                        const tipoElementoSeleccionado = tipoElementoSelect.value;
                        const esProcedimiento = tipoElementoSeleccionado === '2';
                        if (esProcedimiento) {
                            archivoElementoInput.accept = '.doc';
                            tiposArchivoElemento.textContent = 'DOC';
                        } else {
                            archivoElementoInput.accept = '.pdf,.doc,.docx,.xls,.xlsx';
                            tiposArchivoElemento.textContent = 'PDF, DOCX, XLSX';
                        }
                    }
                }
            }, 100);
            
            // Asegurar que el archivo del formato esté visible solo si es_formato está visible y es "si" al cargar
            const esFormatoInit = document.getElementById('es_formato');
            const archivoFormatoDivInit = document.getElementById('archivo_formato_div');
            const esFormatoWrapperInit = esFormatoInit ? esFormatoInit.closest('[data-campo]') : null;
            const esFormatoVisibleInit = esFormatoWrapperInit && !esFormatoWrapperInit.classList.contains('hidden');
            
            if (esFormatoInit && esFormatoVisibleInit && esFormatoInit.value === 'si' && archivoFormatoDivInit) {
                archivoFormatoDivInit.classList.remove('hidden');
            } else if (archivoFormatoDivInit) {
                // Asegurar que esté oculto si es_formato no es visible
                archivoFormatoDivInit.classList.add('hidden');
            }

            form.addEventListener('submit', () => {
                document.querySelectorAll('.hidden [required]').forEach(el => {
                    el.removeAttribute('required');
                });

                document.querySelectorAll('.hidden input[type="checkbox"]').forEach(chk => {
                    chk.removeAttribute('required');
                    chk.setCustomValidity('');
                });
            });
        });
    </script>
    <script>
        // Funcionalidad del semáforo
        document.addEventListener('DOMContentLoaded', function() {
            const periodoRevisionInput = document.getElementById('periodo_revision');
            const semaforoContainer = document.getElementById('semaforo-container');
            const estadoSemaforo = document.getElementById('estado-semaforo');
            const infoSemaforo = document.getElementById('info-semaforo');

            function actualizarSemaforo() {
                const fecha = periodoRevisionInput.value;
                if (!fecha) {
                    semaforoContainer.classList.add('hidden');
                    return;
                }

                const hoy = new Date();
                const fechaRevision = new Date(fecha);
                const diferenciaMeses = (fechaRevision.getFullYear() - hoy.getFullYear()) * 12 +
                    (fechaRevision.getMonth() - hoy.getMonth());

                let clase, texto, info, icono;

                if (diferenciaMeses <= 2) {
                    clase = 'bg-red-500 text-white';
                    texto = 'Crítico';
                    info = '⚠️ Revisión crítica';
                    icono = 'text-red-600 dark:text-red-400';
                } else if (diferenciaMeses <= 6) {
                    clase = 'bg-yellow-500 text-black';
                    texto = 'Advertencia';
                    info = '⚠️ Revisión próxima';
                    icono = 'text-yellow-600 dark:text-yellow-400';
                } else if (diferenciaMeses <= 12) {
                    clase = 'bg-green-500 text-white';
                    texto = 'Normal';
                    info = '✅ Revisión programada';
                    icono = 'text-green-600 dark:text-green-400';
                } else {
                    clase = 'bg-blue-500 text-white';
                    texto = 'Lejano';
                    info = '📅 Revisión lejana';
                    icono = 'text-blue-600 dark:text-blue-400';
                }

                estadoSemaforo.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${clase}`;
                estadoSemaforo.textContent = texto;

                if (infoSemaforo) {
                    infoSemaforo.innerHTML = `<span class="${icono}">${info}</span>`;
                }

                semaforoContainer.classList.remove('hidden');
            }

            actualizarSemaforo();
            periodoRevisionInput.addEventListener('change', actualizarSemaforo);
            periodoRevisionInput.addEventListener('input', actualizarSemaforo);

            // Funcionalidad de correos libres
            const agregarCorreoBtn = document.getElementById('agregar-correo');
            const correosContainer = document.getElementById('correos-libres-container');

            if (agregarCorreoBtn) {
                agregarCorreoBtn.addEventListener('click', function() {
                    const nuevoCampo = document.createElement('div');
                    nuevoCampo.className = 'flex items-center gap-2 mb-2';
                    nuevoCampo.innerHTML = `
                        <input type="email" name="correos_libres[]" placeholder="correo@ejemplo.com" 
                               class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                        <button type="button" class="btn-eliminar-correo px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                            -
                        </button>
                    `;
                    correosContainer.appendChild(nuevoCampo);
                    actualizarVistaPrevia();
                });
            }

            // Eliminar campos de correo
            correosContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-eliminar-correo')) {
                    e.target.closest('.flex').remove();
                    actualizarVistaPrevia();
                }
            });

            // Actualizar vista previa de correos
            function actualizarVistaPrevia() {
                const usuariosSeleccionados = document.querySelectorAll('input[name="usuarios_correo[]"]:checked');
                const correosLibres = document.querySelectorAll('input[name="correos_libres[]"]');

                const vistaPrevia = document.getElementById('vista-previa-correos');
                let correos = [];

                // Agregar correos de usuarios seleccionados
                usuariosSeleccionados.forEach(checkbox => {
                    const email = checkbox.nextElementSibling.querySelector('.text-gray-500').textContent.split(' - ')[1];
                    correos.push(email);
                });

                // Agregar correos libres
                correosLibres.forEach(input => {
                    if (input.value.trim()) {
                        correos.push(input.value.trim());
                    }
                });

                if (correos.length === 0) {
                    vistaPrevia.innerHTML = '<p class="italic">Selecciona usuarios o agrega correos para ver la vista previa</p>';
                } else {
                    const listaCorreos = correos.map(correo => `<span class="inline-block bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs mr-2 mb-1">${correo}</span>`).join('');
                    vistaPrevia.innerHTML = listaCorreos;
                }
            }

            // Eventos para actualizar vista previa
            document.querySelectorAll('input[name="usuarios_correo[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', actualizarVistaPrevia);
            });

            document.querySelectorAll('input[name="correos_libres[]"]').forEach(input => {
                input.addEventListener('input', actualizarVistaPrevia);
            });

            // Inicializar vista previa
            actualizarVistaPrevia();

            // Funcionalidad del filtro por tipo de elemento
            const filtroTipoElemento = document.getElementById('filtro_tipo_elemento');
            const selectElementoPadre = document.getElementById('elemento_padre_id');
            const contadorElementos = document.getElementById('contador-elementos');

            function aplicarFiltro() {
                const tipoSeleccionado = filtroTipoElemento.value;
                const opciones = selectElementoPadre.querySelectorAll('option[data-tipo]');
                let elementosDisponibles = 0;

                console.log('Aplicando filtro para tipo:', tipoSeleccionado);

                // Ocultar/mostrar opciones según el filtro
                opciones.forEach(opcion => {
                    const tipoOpcion = opcion.getAttribute('data-tipo');

                    if (tipoSeleccionado === '' || tipoOpcion === tipoSeleccionado) {
                        opcion.style.display = '';
                        opcion.disabled = false;
                        elementosDisponibles++;
                    } else {
                        opcion.style.display = 'none';
                        opcion.disabled = true;
                    }
                });

                // Actualizar contador
                if (contadorElementos) {
                    if (tipoSeleccionado === '') {
                        contadorElementos.textContent = `${elementosDisponibles} elementos disponibles`;
                    } else {
                        const tipoNombre = filtroTipoElemento.options[filtroTipoElemento.selectedIndex].text;
                        contadorElementos.textContent = `${elementosDisponibles} elementos de tipo "${tipoNombre}" disponibles`;
                    }
                }

                // Si hay una opción seleccionada que no coincide con el filtro, deseleccionarla
                if (selectElementoPadre.value && tipoSeleccionado !== '') {
                    const opcionSeleccionada = selectElementoPadre.querySelector(`option[value="${selectElementoPadre.value}"]`);
                    if (opcionSeleccionada && opcionSeleccionada.getAttribute('data-tipo') !== tipoSeleccionado) {
                        selectElementoPadre.value = '';
                        console.log('Elemento deseleccionado por no coincidir con el filtro');
                    }
                }

                // Forzar actualización de Select2 si está inicializado
                if (selectElementoPadre.classList.contains('select2-hidden-accessible')) {
                    $(selectElementoPadre).trigger('change');
                }
            }

            if (filtroTipoElemento && selectElementoPadre) {
                // Aplicar filtro al cambiar el tipo
                filtroTipoElemento.addEventListener('change', aplicarFiltro);

                // Si hay un elemento padre seleccionado, preseleccionar su tipo en el filtro
                if (selectElementoPadre.value) {
                    const opcionSeleccionada = selectElementoPadre.querySelector(`option[value="${selectElementoPadre.value}"]`);
                    if (opcionSeleccionada) {
                        const tipoElemento = opcionSeleccionada.getAttribute('data-tipo');
                        filtroTipoElemento.value = tipoElemento;
                        console.log('Preseleccionando tipo:', tipoElemento);
                    }
                }

                // Aplicar filtro inicial
                aplicarFiltro();
            }
        });
    </script>
    @if(session('swal_error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "{{ session('swal_error') }}",
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false,
            position: 'top-end',
        });
    </script>
    @endif
</x-app-layout>
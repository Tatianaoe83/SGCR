    <x-app-layout>
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

            <!-- Page header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

                <!-- Left: Title -->
                <div class="mb-4 sm:mb-0">
                    <!-- Main Title -->
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Nuevo Elemento</h1>
                </div>

                <!-- Right: Actions -->
                <div class="flex flex-wrap items-center space-x-2">
                    <a href="{{ route('elementos.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                        <span class="btn bg-red-500 hover:bg-red-600 text-white">
                            <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                                <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                            </svg>

                            <span class="xs:block ml-2">Volver</span>
                    </a>
                </div>

            </div>

            <!-- Selección de Tipo de Elemento - PASO 1 -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg rounded-lg border border-indigo-200 dark:border-indigo-800 mb-6">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-white text-indigo-600 font-bold text-lg shadow-md">
                                1
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-white">Selecciona el Tipo de Elemento</h3>
                            <p class="text-indigo-100 text-sm">Primero elige qué tipo de elemento deseas crear</p>
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
                        <select name="tipo_elemento_id" id="tipo_elemento_id" class="select2 block w-full border-2 border-indigo-300 dark:border-indigo-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg py-3" data-placeholder="Seleccionar tipo de elemento">
                            <option value="">Seleccionar tipo</option>
                            @foreach($tiposElemento as $tipo)
                            <option value="{{ $tipo->id_tipo_elemento }}" {{ old('tipo_elemento_id') == $tipo->id_tipo_elemento ? 'selected' : '' }}>
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

            <!-- Form Principal - PASO 2 -->
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-600 text-white font-bold text-sm shadow-md">
                                2
                            </div>
                        </div>
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100 ml-3">Completa la Información del Elemento</h2>
                    </div>
                </header>
                <div class="p-6">
                    <form action="{{ route('elementos.store') }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6" id="form-save">
                        @csrf

                        <!-- Campo hidden para tipo_elemento_id que se sincroniza con el select del Paso 1 -->
                        <input type="hidden" name="tipo_elemento_id" id="tipo_elemento_id_hidden" value="{{ old('tipo_elemento_id') }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Nombre del Elemento -->
                            <div data-campo>
                                <label for="nombre_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Elemento</label>
                                <input type="text" name="nombre_elemento" id="nombre_elemento" value="{{ old('nombre_elemento') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                                    <option value="{{ $proceso->id_tipo_proceso }}" {{ old('tipo_proceso_id') == $proceso->id_tipo_proceso ? 'selected' : '' }}>
                                        {{ $proceso->nivel }} - {{ $proceso->nombre }}
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
                                <select name="unidad_negocio_id[]" multiple id="unidad_negocio_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Seleccionar unidad</option>
                                    @foreach($unidadesNegocio as $unidad)
                                    <option value="{{ $unidad->id_unidad_negocio }}" {{ old('unidad_negocio_id') == $unidad->id_unidad_negocio ? 'selected' : '' }}>
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
                                <input type="number" name="ubicacion_eje_x" id="ubicacion_eje_x" value="{{ old('ubicacion_eje_x') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('ubicacion_eje_x')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Control -->
                            <div data-campo>
                                <label for="control" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Control</label>
                                <select name="control" id="control" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="interno" {{ old('control') == 'interno' ? 'selected' : '' }}>Interno</option>
                                    <option value="externo" {{ old('control') == 'externo' ? 'selected' : '' }}>Externo</option>
                                </select>
                                @error('control')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Folio -->
                            <div data-campo>
                                <label for="folio_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folio del Elemento</label>
                                <input type="text" name="folio_elemento" id="folio_elemento" value="{{ old('folio_elemento') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('folio_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Versión -->
                            <div data-campo>
                                <label for="version_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versión</label>
                                <input type="number" name="version_elemento" id="version_elemento" value="{{ old('version_elemento', '1.0') }}" step="0.1" min="0.1" max="99.9" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('version_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Fecha del Elemento -->
                            <div data-campo>
                                <label for="fecha_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Elemento</label>
                                <input type="date" name="fecha_elemento" id="fecha_elemento" value="{{ old('fecha_elemento') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('fecha_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Periodo de Revisión -->
                            <div data-campo>
                                <label for="periodo_revision" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Revisión</label>
                                <input type="date" name="periodo_revision" id="periodo_revision" value="{{ old('periodo_revision') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('periodo_revision')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <!-- Semáforo de Estado -->
                                <div id="semaforo-container" class="mt-2 hidden">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Estado:</span>
                                        <div id="semaforo-dinamico"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Puesto Responsable -->
                            <div data-campo>
                                <label for="puesto_responsable_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puesto Responsable</label>
                                <select name="puesto_responsable_id" id="puesto_responsable_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Seleccionar puesto</option>
                                    @foreach($puestosTrabajo as $puesto)
                                    <option value="{{ $puesto->id_puesto_trabajo }}" {{ old('puesto_responsable_id') == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
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
                                    <option value="{{ $puesto->id_puesto_trabajo }}" {{ old('puesto_ejecutor_id') == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
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
                                    <option value="{{ $puesto->id_puesto_trabajo }}" {{ old('puesto_resguardo_id') == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
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
                                    <option value="digital" {{ old('medio_soporte') == 'digital' ? 'selected' : '' }}>Digital</option>
                                    <option value="fisico" {{ old('medio_soporte') == 'fisico' ? 'selected' : '' }}>Físico</option>
                                </select>
                                @error('medio_soporte')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Ubicación de Resguardo -->
                            <div data-campo>
                                <label for="ubicacion_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación de Resguardo</label>
                                <input type="text" name="ubicacion_resguardo" id="ubicacion_resguardo" value="{{ old('ubicacion_resguardo') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('ubicacion_resguardo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Periodo de Resguardo -->
                            <div data-campo>
                                <label for="periodo_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Resguardo</label>
                                <input type="date" name="periodo_resguardo" id="periodo_resguardo" value="{{ old('periodo_resguardo') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('periodo_resguardo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Es Formato -->
                            <div data-campo>
                                <label for="es_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">¿Es Formato?</label>
                                <select name="es_formato" id="es_formato" class="form-select mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="no" {{ old('es_formato') == 'no' ? 'selected' : '' }}>No</option>
                                    <option value="si" {{ old('es_formato') == 'si' ? 'selected' : '' }}>Sí</option>
                                </select>
                                @error('es_formato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Archivos Formato y Elemento -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div id="archivo_formato_div" class="hidden" data-campo>
                                    <label for="archivo_formato" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                        Archivo del Formato
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-4-4m0 0a4 4 0 018 0m0 0a4 4 0 018 0m0 0a4 4 0 01-4 4m-4 4h.01M12 12v4m0 0l-2 2m2-2l2 2" />
                                        </svg>
                                        <input type="file" name="archivo_formato" id="archivo_formato"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx"
                                            class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                        <p id="mensaje" class="mt-2 text-xs text-gray-500 dark:text-gray-400">PDF, DOCX, XLSX</p>
                                    </div>
                                    @error('archivo_formato')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div id="archivo_elemento_div" data-campo>
                                    <label for="archivo_es_formato" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                        Archivo del Elemento
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7h4l3 3h11v8a2 2 0 01-2 2H3a2 2 0 01-2-2V7z" />
                                        </svg>
                                        <input type="file" name="archivo_es_formato" id="archivo_es_formato"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx"
                                            class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                        <p id="mensaje2" class="mt-2 text-xs text-gray-500 dark:text-gray-400">PDF, DOCX, XLSX</p>
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
                                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Elemento al que pertenece</h4>
                                                <p class="text-sm text-blue-600 dark:text-blue-400">Selecciona al elemento que pertenece</p>
                                            </div>
                                        </div>

                                        <!-- Filtro por tipo de elemento -->
                                        <div class="mb-4">
                                            <label for="filtro_tipo_elemento" class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                                </svg>
                                                Filtrar por tipo de elemento
                                            </label>
                                            <select id="filtro_tipo_elemento" class="w-full border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                                <option value="">Todos los tipos</option>
                                                @foreach($tiposElemento as $tipo)
                                                <option value="{{ $tipo->id_tipo_elemento }}">{{ $tipo->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="relative">
                                            <select name="elemento_padre_id" id="elemento_padre_id" class="select2 block w-full border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                                <option value="">Seleccionar elemento padre</option>
                                                @foreach($elementos as $elemento)
                                                <option value="{{ $elemento->id_elemento }}"
                                                    data-tipo="{{ $elemento->tipo_elemento_id }}"
                                                    {{ old('elemento_padre_id') == $elemento->id_elemento ? 'selected' : '' }}>
                                                    {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
                                                </option>
                                                @endforeach
                                            </select>

                                            <!-- Contador de elementos disponibles -->
                                            <div id="contador-elementos" class="mt-3 flex items-center justify-between text-sm">
                                                <span class="text-blue-600 dark:text-blue-400 font-medium">Elementos disponibles:</span>
                                                <span class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full font-semibold">
                                                    {{ count($elementos) }} elementos
                                                </span>
                                            </div>
                                        </div>

                                        @error('elemento_padre_id')
                                        <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Elementos Relacionados (Múltiples) -->
                                <div class="col-span-full" data-relacion="elemento_relacionado_id">
                                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700 p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="text-lg font-semibold text-green-900 dark:text-green-100">Elementos Relacionados</h4>
                                                <p class="text-sm text-green-600 dark:text-green-400">Selecciona múltiples elementos relacionados</p>
                                            </div>
                                        </div>

                                        <!-- Filtro por tipo de elemento -->
                                        <div class="mb-4">
                                            <label for="filtro_tipo_elemento_relacionados" class="block text-sm font-medium text-green-800 dark:text-green-200 mb-2 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                                </svg>
                                                Filtrar por tipo de elemento
                                            </label>
                                            <select id="filtro_tipo_elemento_relacionados" class="select2 w-full border-green-300 dark:border-green-600 dark:bg-green-800 dark:text-green-200 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 transition-colors duration-200" data-placeholder="Todos los tipos">
                                                <option value="">Todos los tipos</option>
                                                @foreach($tiposElemento as $tipo)
                                                <option value="{{ $tipo->id_tipo_elemento }}">{{ $tipo->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="relative">
                                            <select name="elemento_relacionado_id[]" id="elemento_relacionado_id" multiple class="select2-multiple block w-full border-green-300 dark:border-green-600 dark:bg-green-800 dark:text-green-200 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                                                @foreach($elementos as $elemento)
                                                <option value="{{ $elemento->id_elemento }}"
                                                    data-tipo="{{ $elemento->tipo_elemento_id }}"
                                                    {{ in_array($elemento->id_elemento, old('elemento_relacionado_id', [])) ? 'selected' : '' }}>
                                                    {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
                                                </option>
                                                @endforeach
                                            </select>

                                            <!-- Contador de elementos relacionados disponibles -->
                                            <div id="contador-elementos-relacionados" class="mt-3 flex items-center justify-between text-sm">
                                                <span class="text-green-600 dark:text-green-400 font-medium">Elementos disponibles:</span>
                                                <span class="bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 px-3 py-1 rounded-full font-semibold">
                                                    {{ count($elementos) }} elementos
                                                </span>
                                            </div>
                                        </div>

                                        @error('elemento_relacionado_id')
                                        <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Puestos de Trabajo Relacionados (Múltiples) -->
                                <div class="col-span-full" data-relacion="puestos_relacionados">
                                    <div class="bg-gradient-to-r from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-lg border border-purple-200 dark:border-purple-700 p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="text-lg font-semibold text-purple-900 dark:text-purple-100">Puestos de Trabajo Relacionados</h4>
                                                <p class="text-sm text-purple-600 dark:text-purple-400">Selecciona los puestos de trabajo relacionados</p>
                                            </div>
                                        </div>

                                        <!-- Filtros de búsqueda -->
                                        <div class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-600">
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                                <!-- Filtro por División -->
                                                <div>
                                                    <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                        </svg>
                                                        Filtrar por División
                                                    </label>
                                                    <select id="filtro_division" class="select2 w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200" data-placeholder="Todas las divisiones">
                                                        <option value="">Todas las divisiones</option>
                                                        @foreach($divisions ?? [] as $division)
                                                        <option value="{{ $division->id_division }}">{{ $division->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Filtro por Unidad de Negocio -->
                                                <div>
                                                    <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                        </svg>
                                                        Filtrar por Unidad
                                                    </label>
                                                    <select id="filtro_unidad" class="select2 w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200" data-placeholder="Todas las unidades">
                                                        <option value="">Todas las unidades</option>
                                                    </select>
                                                </div>

                                                <!-- Filtro por Área -->
                                                <div>
                                                    <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        Filtrar por Área
                                                    </label>
                                                    <select id="filtro_area" class="select2 w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200" data-placeholder="Todas las áreas">
                                                        <option value="">Todas las áreas</option>
                                                    </select>
                                                </div>

                                                <!-- Búsqueda por texto -->
                                                <div>
                                                    <label class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                        </svg>
                                                        Buscar por nombre
                                                    </label>
                                                    <input type="text" id="busqueda_texto" placeholder="Buscar puestos..." class="w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
                                                </div>
                                            </div>

                                            <!-- Controles de selección -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <button type="button" id="select_all" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg font-medium transition-colors duration-200 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        Seleccionar Todos
                                                    </button>
                                                    <button type="button" id="deselect_all" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg font-medium transition-colors duration-200 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        Deseleccionar Todos
                                                    </button>
                                                    <span id="contador_seleccionados" class="text-sm text-purple-600 dark:text-purple-400 font-medium bg-purple-100 dark:bg-purple-800 px-3 py-1 rounded-full">
                                                        0 puestos seleccionados
                                                    </span>
                                                </div>
                                                <button type="button" id="limpiar_filtros" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg font-medium transition-colors duration-200 flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                    </svg>
                                                    Limpiar Filtros
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Lista de puestos de trabajo -->
                                        <div class="max-h-96 overflow-y-auto border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-purple-900/20">
                                            <div id="lista_puestos" class="p-4 space-y-2">
                                                @foreach($puestosTrabajo as $puesto)
                                                <label class="flex items-center p-3 hover:bg-purple-50 dark:hover:bg-purple-800/30 rounded-lg cursor-pointer transition-colors duration-200 border border-transparent hover:border-purple-200 dark:hover:border-purple-600">
                                                    <input type="checkbox" name="puestos_relacionados[]" value="{{ $puesto->id_puesto_trabajo }}"
                                                        class="puesto-checkbox rounded border-purple-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
                                                        data-division="{{ $puesto->division->id_division ?? '' }}"
                                                        data-unidad="{{ $puesto->unidadNegocio->id_unidad_negocio ?? '' }}"
                                                        data-area="{{ $puesto->area->id_area ?? '' }}"
                                                        data-nombre="{{ strtolower($puesto->nombre) }}"
                                                        {{ in_array($puesto->id_puesto_trabajo, old('puestos_relacionados', [])) ? 'checked' : '' }}>
                                                    <span class="ml-3 text-sm">
                                                        <span class="font-medium text-purple-900 dark:text-purple-100">{{ $puesto->nombre }}</span>
                                                        <span class="text-purple-600 dark:text-purple-400 ml-2">
                                                            {{ $puesto->division->nombre ?? 'Sin división' }} /
                                                            {{ $puesto->unidadNegocio->nombre ?? 'Sin unidad' }} /
                                                            {{ $puesto->area->nombre ?? 'Sin área' }}
                                                        </span>
                                                    </span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @error('puestos_relacionados')
                                    <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                    @enderror
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
                                <div id="campos_nombre_container" class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <input
                                            name="nombres_relacion[0]"
                                            type="text"
                                            placeholder="Escribe el comité"
                                            class="input-relacion w-[300px] border border-gray-300 rounded-md px-2 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">

                                        <select
                                            class="form-select select2 campo-relacion"
                                            name="puesto_id[0][]"
                                            multiple="multiple"
                                            required>
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

                                        <button
                                            type="button"
                                            class="btn-agregar-nombre px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Configuraciones Adicionales -->
                            <div class="mt-8">
                                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-lg border border-amber-200 dark:border-amber-700 p-6">
                                    <div class="flex items-center mb-4">
                                        <div class="flex-shrink-0">
                                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">Configuraciones Adicionales</h3>
                                            <p class="text-sm text-amber-600 dark:text-amber-400">Configura las opciones de correo para este elemento</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Correo Implementación -->
                                        <div data-relacion="correo_implementacion" class="bg-white dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-600">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" name="correo_implementacion" value="1" {{ old('correo_implementacion') ? 'checked' : '' }} class="rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50">
                                                <span class="ml-3 text-sm font-medium text-amber-800 dark:text-amber-200">Correo de IMPLEMENTACIÓN</span>
                                            </label>
                                            @error('correo_implementacion')
                                            <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Correo Agradecimiento -->
                                        <div data-relacion="correo_agradecimiento" class="bg-white dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-600">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" name="correo_agradecimiento" value="1" {{ old('correo_agradecimiento') ? 'checked' : '' }} class="rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50">
                                                <span class="ml-3 text-sm font-medium text-amber-800 dark:text-amber-200">Correo de AGRADECIMIENTO</span>
                                            </label>
                                            @error('correo_agradecimiento')
                                            <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="flex items-center justify-end space-x-2 mt-4">
                                <a href="{{ route('elementos.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                    Cancelar
                                </a>
                                <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                    Crear Elemento
                                </button>
                            </div>
                        </div>
                    </form>
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

                [data-relacion],
                [data-campo] {
                    transition: all 0.3s ease;
                }

                [data-relacion].hidden,
                [data-campo].hidden {
                    opacity: 0;
                    max-height: 0;
                    overflow: hidden;
                    pointer-events: none;
                }

                .autoComplete_wrapper {
                    position: relative;
                    width: 220px;
                }

                .autoComplete_wrapper input {
                    background-color: #2a2640 !important;
                    border: 1px solid #6b5fc7 !important;
                    border-radius: 6px !important;
                    padding: 0.45rem 0.75rem !important;
                    font-size: 0.875rem !important;
                    color: #e5e7eb !important;
                    width: 100% !important;
                    height: 38px !important;
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                }

                .autoComplete_wrapper input::placeholder {
                    color: #a5a3c2 !important;
                }

                .autoComplete_wrapper input:focus {
                    border-color: #a78bfa !important;
                    box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.25) !important;
                    outline: none !important;
                }

                .autoComplete_wrapper::before {
                    display: none !important;
                }

                .autoComplete_wrapper>ul {
                    margin-top: 4px !important;
                    background-color: #1f1b33 !important;
                    border: 1px solid #4b4375 !important;
                    border-radius: 6px !important;
                    padding: 6px 0 !important;
                    font-size: 0.875rem !important;
                    overflow: hidden;
                    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35) !important;
                    animation: fadeIn 0.18s ease-out;
                }

                .autoComplete_result {
                    padding: 0.55rem 0.75rem !important;
                    color: #e5e7eb !important;
                    cursor: pointer;
                    transition: background-color 0.15s ease;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .autoComplete_result:hover {
                    background-color: #3b3361 !important;
                }

                .autoComplete_highlighted {
                    color: #c4b5fd !important;
                }

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-4px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
            <script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/autoComplete.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.addEventListener('focusin', e => {
                        if (!e.target.matches('input[name^="nombres_relacion"]')) return;

                        const input = e.target;
                        if (input.dataset.autocompleteInitialized) return;
                        input.dataset.autocompleteInitialized = true;

                        new autoComplete({
                            selector: () => input,
                            placeHolder: "Buscar comité",
                            debounce: 300,
                            data: {
                                src: async () => {
                                    const query = input.value.trim();
                                    if (!query) return [];
                                    try {
                                        const res = await fetch(`/elementos/buscar?q=${encodeURIComponent(query)}`);
                                        if (!res.ok) return [];
                                        const data = await res.json();
                                        return data.map(r => ({
                                            nombre: r.nombre,
                                            puestos: r.puestos
                                        }));
                                    } catch (err) {
                                        console.error("Error en autocomplete:", err);
                                        return [];
                                    }
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
                                        const feedback = event.detail;
                                        input.value = feedback.selection.value.nombre;

                                        const wrapper = input.closest('.flex');
                                        const select = wrapper.querySelector('select[name^="puesto_id"]');

                                        if (select && feedback.selection.value.puestos.length) {
                                            const ids = feedback.selection.value.puestos.map(p => p.id);

                                            $(select)
                                                .val(ids)
                                                .trigger('change');
                                        }
                                    }
                                }
                            }
                        });
                    });
                });
            </script>

            <script>
                $(function() {
                    // Inicializar Select2
                    $('.select2').select2({
                        placeholder: 'Seleccionar opción',
                        allowClear: true,
                        width: '100%'
                    });
                    $('.select2-multiple').select2({
                        placeholder: 'Seleccionar opciones',
                        allowClear: true,
                        width: '100%'
                    });

                    $('.select2').select2({
                        placeholder: "Selecciona un puesto",
                        width: "100%"
                    });

                    const filtroDivision = $('#filtro_division');
                    const filtroUnidad = $('#filtro_unidad');
                    const filtroArea = $('#filtro_area');
                    const busquedaTexto = $('#busqueda_texto');
                    const selectAllBtn = $('#select_all');
                    const deselectAllBtn = $('#deselect_all');
                    const limpiarFiltrosBtn = $('#limpiar_filtros');
                    const puestosCheckboxes = $('.puesto-checkbox');
                    const contadorSeleccionados = $('#contador_seleccionados');
                    const camposContainer = $('#campos_nombre_container');
                    const periodoRevisionInput = $('#periodo_revision');
                    const semaforoContainer = $('#semaforo-container');
                    const semaforoDinamico = $('#semaforo-dinamico');

                    // Actualizar contador
                    function actualizarContador() {
                        const seleccionados = $('.puesto-checkbox:checked').length;
                        contadorSeleccionados.text(`${seleccionados} puestos seleccionados`);
                    }

                    // Aplicar filtros
                    function aplicarFiltros() {
                        const divisionId = filtroDivision.val();
                        const unidadId = filtroUnidad.val();
                        const areaId = filtroArea.val();
                        const texto = busquedaTexto.val().toLowerCase().trim();

                        $('.puesto-checkbox').each(function() {
                            const c = $(this);
                            const label = c.closest('label');
                            let mostrar = true;

                            if (divisionId && c.data('division') != divisionId) mostrar = false;
                            if (unidadId && c.data('unidad') != unidadId) mostrar = false;
                            if (areaId && c.data('area') != areaId) mostrar = false;
                            if (texto && !c.data('nombre').includes(texto)) mostrar = false;

                            label.toggle(mostrar);
                        });
                    }

                    // Cargar unidades
                    function cargarUnidades(divisionId) {
                        if (!divisionId) {
                            filtroUnidad.html('<option value="">Todas las unidades</option>').trigger('change');
                            return;
                        }
                        fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                            .then(r => r.json())
                            .then(data => {
                                let html = '<option value="">Todas las unidades</option>';
                                data.forEach(u => html += `<option value="${u.id_unidad_negocio}">${u.nombre}</option>`);
                                filtroUnidad.html(html).trigger('change');
                            });
                    }

                    // Cargar áreas
                    function cargarAreas(unidadId) {
                        if (!unidadId) {
                            filtroArea.html('<option value="">Todas las áreas</option>').trigger('change');
                            return;
                        }
                        fetch(`/puestos-trabajo/areas/${unidadId}`)
                            .then(r => r.json())
                            .then(data => {
                                let html = '<option value="">Todas las áreas</option>';
                                data.forEach(a => html += `<option value="${a.id_area}">${a.nombre}</option>`);
                                filtroArea.html(html).trigger('change');
                            });
                    }

                    // Actualizar semáforo
                    function actualizarSemaforo() {
                        const fecha = periodoRevisionInput.val();
                        if (!fecha) {
                            semaforoContainer.addClass('hidden');
                            return;
                        }

                        const hoy = new Date();
                        const fechaRev = new Date(fecha);
                        const diffMeses = (fechaRev.getFullYear() - hoy.getFullYear()) * 12 + (fechaRev.getMonth() - hoy.getMonth());

                        let clase, texto, info, icono;
                        if (diffMeses <= 2) {
                            clase = 'bg-red-500 text-white';
                            texto = 'Crítico';
                            info = '⚠️ Revisión crítica';
                            icono = 'text-red-600';
                        } else if (diffMeses <= 6) {
                            clase = 'bg-yellow-500 text-black';
                            texto = 'Advertencia';
                            info = '⚠️ Revisión próxima';
                            icono = 'text-yellow-600';
                        } else if (diffMeses <= 12) {
                            clase = 'bg-green-500 text-white';
                            texto = 'Normal';
                            info = '✅ Revisión programada';
                            icono = 'text-green-600';
                        } else {
                            clase = 'bg-blue-500 text-white';
                            texto = 'Lejano';
                            info = '📅 Revisión lejana';
                            icono = 'text-blue-600';
                        }

                        semaforoDinamico.html(`
                <div class="inline-flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${clase}">
                        ${texto}
                    </span>
                    <span class="${icono} text-xs">${info}</span>
                </div>
            `);
                        semaforoContainer.removeClass('hidden');
                    }

                    filtroDivision.on('change', function() {
                        cargarUnidades(this.value);
                        filtroUnidad.val('').trigger('change');
                        filtroArea.val('').trigger('change');
                        aplicarFiltros();
                    });
                    filtroUnidad.on('change', function() {
                        cargarAreas(this.value);
                        filtroArea.val('').trigger('change');
                        aplicarFiltros();
                    });
                    filtroArea.on('change', aplicarFiltros);
                    busquedaTexto.on('input keyup', aplicarFiltros);

                    selectAllBtn.on('click', function() {
                        $('.puesto-checkbox:visible').prop('checked', true);
                        actualizarContador();
                    });
                    deselectAllBtn.on('click', function() {
                        puestosCheckboxes.prop('checked', false);
                        actualizarContador();
                    });
                    limpiarFiltrosBtn.on('click', function() {
                        filtroDivision.val('').trigger('change');
                        filtroUnidad.val('').trigger('change');
                        filtroArea.val('').trigger('change');
                        busquedaTexto.val('');
                        cargarUnidades('');
                        cargarAreas('');
                        aplicarFiltros();
                    });

                    puestosCheckboxes.on('change', actualizarContador);
                    periodoRevisionInput.on('change input', actualizarSemaforo);

                    $(document).on('click', '.btn-agregar-nombre', function() {
                        const container = $('#campos_nombre_container');
                        const index = container.find('.campo-relacion').length;

                        const selectOpciones = container.find('select.select2').first().html();

                        const nuevo = `
        <div class="flex items-center gap-3 campo-relacion">

            <input name="nombres_relacion[${index}]"
                type="text"
                placeholder="Escribe el nombre"
                class="input-relacion w-[300px] border border-gray-300 rounded-md px-2 py-2 text-sm">

            <select name="puesto_id[${index}][]" class="form-select select2" multiple required>
                ${selectOpciones}
            </select>

            <button type="button"
                class="btn-eliminar-nombre px-3 py-2 bg-red-600 text-white rounded-lg">x</button>
        </div>`;

                        container.append(nuevo);

                        container.find('.select2').last().select2();
                    });

                    $(document).on('click', '.btn-eliminar-nombre', function() {
                        $(this).closest('.campo-relacion').remove();
                    });


                    actualizarContador();
                    aplicarFiltros();
                    actualizarSemaforo();
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
                            const esFormato = document.getElementById('es_formato');

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
                                        const wrapperRelacion = document.querySelector(`[data-relacion="${baseName}"]`);

                                        let tieneDatos = true;
                                        if (el.tagName === 'SELECT') {
                                            tieneDatos = el.options.length > 1;
                                        } else if (el.type === 'checkbox' && el.name.endsWith('[]')) {
                                            const checkboxes = document.querySelectorAll(`[name="${el.name}"]`);
                                            tieneDatos = checkboxes.length > 0;
                                        }

                                        if (!tieneDatos) {
                                            marcarRequerido(el, false);
                                            el.classList.remove('required-outline');
                                            return;
                                        }

                                        if (wrapper && wrapper.id !== 'archivo_formato_div') {
                                            wrapper.classList.remove('hidden');
                                        }
                                        if (wrapperRelacion) {
                                            wrapperRelacion.classList.remove('hidden');
                                        }

                                        if (!el.closest('.hidden')) {
                                            marcarRequerido(el, campo.obligatorio);
                                        }

                                        if (esFormato && esFormato.value !== 'si') {
                                            esFormato.value = 'si';
                                            $(esFormato).trigger('change');
                                        }
                                    });
                                } else {
                                    console.warn('No se encontró el input para:', campo.campo_nombre);
                                }
                            });

                            const archivoFormato = document.getElementById('archivo_formato');
                            const archivoElemento = document.getElementById('archivo_es_formato');
                            const mensajeFormato = document.getElementById('mensaje');
                            const mensajeElemento = document.getElementById('mensaje2');

                            if (tipoId == 2) {
                                if (archivoFormato) archivoFormato.accept = ".docx";
                                if (archivoElemento) archivoElemento.accept = ".docx";
                                if (mensajeFormato) mensajeFormato.textContent = "DOCX";
                                if (mensajeElemento) mensajeElemento.textContent = "DOCX";
                            } else {
                                if (archivoFormato) archivoFormato.accept = ".pdf,.doc,.docx,.xls,.xlsx";
                                if (archivoElemento) archivoElemento.accept = ".pdf,.doc,.docx,.xls,.xlsx";
                                if (mensajeFormato) mensajeFormato.textContent = "PDF, DOCX, XLSX";
                                if (mensajeElemento) mensajeElemento.textContent = "PDF, DOCX, XLSX";
                            }

                            const archivoElementoDiv = document.getElementById('archivo_elemento_div');
                            if (archivoElementoDiv) archivoElementoDiv.classList.remove('hidden');

                            const esFormatoValue = document.getElementById('es_formato');
                            const archivoFormatoDiv = document.getElementById('archivo_formato_div');

                            if (archivoFormatoDiv) {
                                const esFormatoEsRequerido = camposObligatorios.some(campo =>
                                    campo.campo_nombre === 'es_formato'
                                );

                                if (esFormatoEsRequerido && esFormatoValue && esFormatoValue.value === 'si') {
                                    archivoFormatoDiv.classList.remove('hidden');
                                } else {
                                    archivoFormatoDiv.classList.add('hidden');
                                }
                            }

                        } catch (e) {
                            console.error('Error cargando campos obligatorios:', e);
                        }
                    }

                    $tipo.on('change', function() {
                        const tipoId = this.value;
                        // Sincronizar el valor con el campo hidden dentro del formulario
                        const hiddenField = document.getElementById('tipo_elemento_id_hidden');
                        if (hiddenField) {
                            hiddenField.value = tipoId;
                        }
                        if (tipoId) cargarCampos(tipoId);
                        else {
                            limpiarRequeridos();
                            // Asegurar que el archivo del elemento siempre esté visible
                            const archivoElementoDiv = document.getElementById('archivo_elemento_div');
                            if (archivoElementoDiv) {
                                archivoElementoDiv.classList.remove('hidden');
                            }
                            // Ocultar archivo_formato_div si no hay tipo seleccionado
                            const archivoFormatoDiv = document.getElementById('archivo_formato_div');
                            if (archivoFormatoDiv) {
                                archivoFormatoDiv.classList.add('hidden');
                            }
                        }
                    });

                    // Asegurar que el archivo del elemento esté visible al cargar la página
                    const archivoElementoDivInit = document.getElementById('archivo_elemento_div');
                    if (archivoElementoDivInit) {
                        archivoElementoDivInit.classList.remove('hidden');
                    }

                    // Funcionalidad para mostrar/ocultar archivo_formato_div según es_formato
                    // Solo se muestra si es_formato es requerido Y el valor es "si"
                    function toggleArchivoFormato() {
                        const esFormato = document.getElementById('es_formato');
                        const archivoFormatoDiv = document.getElementById('archivo_formato_div');

                        if (esFormato && archivoFormatoDiv) {
                            // Verificar si el campo es_formato es requerido (está visible)
                            const esFormatoWrapper = esFormato.closest('[data-campo]');
                            const esFormatoEsRequerido = esFormatoWrapper && !esFormatoWrapper.classList.contains('hidden');

                            if (esFormatoEsRequerido && esFormato.value === 'si') {
                                archivoFormatoDiv.classList.remove('hidden');
                            } else {
                                archivoFormatoDiv.classList.add('hidden');
                            }
                        }
                    }

                    // Agregar listener al cambio de es_formato
                    const esFormatoSelect = document.getElementById('es_formato');
                    if (esFormatoSelect) {
                        esFormatoSelect.addEventListener('change', toggleArchivoFormato);
                        // Ejecutar al cargar la página si ya tiene un valor
                        toggleArchivoFormato();
                    }

                    // Inicializar el campo hidden con el valor actual del select
                    const hiddenField = document.getElementById('tipo_elemento_id_hidden');
                    if (hiddenField && $tipo.val()) {
                        hiddenField.value = $tipo.val();
                    }

                    if ($tipo.val()) $tipo.trigger('change');

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

                    function actualizarSemaforo() {
                        const fecha = periodoRevisionInput.value;
                        //console.log('fecha', fecha);
                        if (!fecha) {
                            semaforoContainer.classList.add('hidden');
                            return;
                        }

                        const hoy = new Date();

                        const fechaRevision = new Date(fecha);

                        const diferenciaMeses = (fechaRevision.getFullYear() - hoy.getFullYear()) * 12 +
                            (fechaRevision.getMonth() - hoy.getMonth());

                        let estado, clase, texto, info, icono;

                        if (diferenciaMeses <= 2) {
                            estado = 'rojo';
                            clase = 'bg-red-500 text-white';
                            texto = 'Crítico';
                            info = '⚠️ Revisión crítica';
                            icono = 'text-red-600 dark:text-red-400';
                        } else if (diferenciaMeses <= 6) {
                            estado = 'amarillo';
                            clase = 'bg-yellow-500 text-black';
                            texto = 'Advertencia';
                            info = '⚠️ Revisión próxima';
                            icono = 'text-yellow-600 dark:text-yellow-400';
                        } else if (diferenciaMeses <= 12) {
                            estado = 'verde';
                            clase = 'bg-green-500 text-white';
                            texto = 'Normal';
                            info = '✅ Revisión programada';
                            icono = 'text-green-600 dark:text-green-400';
                        } else {
                            estado = 'azul';
                            clase = 'bg-blue-500 text-white';
                            texto = 'Lejano';
                            info = '📅 Revisión lejana';
                            icono = 'text-blue-600 dark:text-blue-400';
                        }

                        // Crear el semáforo dinámicamente
                        const semaforoDinamico = document.getElementById('semaforo-dinamico');
                        semaforoDinamico.innerHTML = `
                        <div class="inline-flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${clase}">
                                ${texto}
                            </span>
                            <span class="${icono} text-xs">
                                ${info}
                            </span>
                        </div>
                    `;

                        semaforoContainer.classList.remove('hidden');
                    }

                    periodoRevisionInput.addEventListener('change', actualizarSemaforo);
                    periodoRevisionInput.addEventListener('input', actualizarSemaforo);


                    // Función reutilizable para aplicar filtros por tipo de elemento
                    function crearFiltroElementos(config) {
                        const {
                            filtroId,
                            selectId,
                            contadorId,
                            esMultiple = false
                        } = config;

                        const filtro = document.getElementById(filtroId);
                        const select = document.getElementById(selectId);
                        const contador = document.getElementById(contadorId);

                        if (!filtro || !select) return;

                        // Guardamos el HTML original del select (todas las opciones del Blade)
                        const opcionesOriginales = select.innerHTML;

                        function aplicarFiltro() {
                            const tipoSeleccionado = filtro.value;

                            // Si no hay tipo seleccionado:
                            if (!tipoSeleccionado) {
                                // Restaurar opciones originales
                                select.innerHTML = opcionesOriginales;

                                if (contador) {
                                    const total = select.querySelectorAll('option[data-tipo]').length;
                                    contador.textContent = `${total} elementos disponibles`;
                                }

                                if (select.classList.contains('select2-hidden-accessible')) {
                                    $(select).trigger('change');
                                }

                                return;
                            }

                            // Con tipo seleccionado, mostrar "Cargando..."
                            select.innerHTML = '<option value="">Cargando...</option>';
                            if (select.classList.contains('select2-hidden-accessible')) {
                                $(select).trigger('change');
                            }

                            fetch(`/elementos/tipos/${tipoSeleccionado}`)
                                .then(res => res.json())
                                .then(data => {
                                    let html = '';

                                    if (!esMultiple) {
                                        html = '<option value="">Seleccionar elemento padre</option>';
                                    }

                                    data.forEach(el => {
                                        html += `
                        <option value="${el.id_elemento}" data-tipo="${el.tipo_elemento_id}">
                            ${el.nombre_elemento} - ${el.folio_elemento}
                        </option>
                    `;
                                    });

                                    select.innerHTML = html;

                                    if (contador) {
                                        const tipoNombre = filtro.options[filtro.selectedIndex].text;
                                        contador.textContent = `${data.length} elementos de tipo "${tipoNombre}" disponibles`;
                                    }

                                    if (select.classList.contains('select2-hidden-accessible')) {
                                        $(select).trigger('change');
                                    }

                                    if (!esMultiple && select.value) {
                                        const opcion = select.querySelector(`option[value="${select.value}"]`);
                                        if (!opcion) select.value = '';
                                    }
                                })
                                .catch(err => {
                                    console.error('Error al cargar elementos:', err);
                                    select.innerHTML = '<option value="">Error al cargar elementos</option>';
                                    if (select.classList.contains('select2-hidden-accessible')) {
                                        $(select).trigger('change');
                                    }
                                    if (contador) contador.textContent = '0 elementos';
                                });
                        }

                        // Evento del filtro
                        filtro.addEventListener('change', aplicarFiltro);

                        // NO apliques filtro inicial si el select de filtro está vacío
                        if (filtro.value) {
                            aplicarFiltro();
                        }
                    }


                    // Inicializar filtros
                    crearFiltroElementos({
                        filtroId: 'filtro_tipo_elemento',
                        selectId: 'elemento_padre_id',
                        contadorId: 'contador-elementos',
                        esMultiple: false
                    });

                    crearFiltroElementos({
                        filtroId: 'filtro_tipo_elemento_relacionados',
                        selectId: 'elemento_relacionado_id',
                        contadorId: 'contador-elementos-relacionados',
                        esMultiple: true
                    });
                });
            </script>
            @if(session('swal_error'))
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ session('swal_error') }}",
                    timer: 3000,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
            </script>
            @endif

            <style>
                .archivo-seleccionado {
                    background-color: #00c444ff !important;
                    transition: background-color 0.3s ease, border-color 0.3s ease;
                }
            </style>

            <script>
                document.addEventListener('change', function(e) {
                    if (e.target.matches('input[type="file"]')) {
                        const input = e.target;
                        const container = input.closest('.border-dashed');
                        if (!container) return;

                        if (input.files && input.files.length > 0) {
                            container.classList.add('archivo-seleccionado');
                        } else {
                            container.classList.remove('archivo-seleccionado');
                        }
                    }
                });
            </script>
    </x-app-layout>
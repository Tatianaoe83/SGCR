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

                        <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Crear Nuevo Elemento</h2>
            </header>
            <div class="p-6">
                <form action="{{ route('elementos.store') }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6" id="form-save">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Elemento -->
                        <div>
                            <label for="tipo_elemento_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Elemento</label>
                            <select name="tipo_elemento_id" id="tipo_elemento_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Seleccionar tipo</option>
                                @foreach($tiposElemento as $tipo)
                                <option value="{{ $tipo->id_tipo_elemento }}" {{ old('tipo_elemento_id') == $tipo->id_tipo_elemento ? 'selected' : '' }}>
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
                            <label for="nombre_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Elemento</label>
                            <input type="text" name="nombre_elemento" id="nombre_elemento" value="{{ old('nombre_elemento') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('nombre_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tipo de Proceso -->
                        <div>
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
                        <div>
                            <label for="unidad_negocio_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidad de Negocio</label>
                            <select name="unidad_negocio_id" id="unidad_negocio_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                        <div>
                            <label for="ubicacion_eje_x" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación en Eje X</label>
                            <input type="number" name="ubicacion_eje_x" id="ubicacion_eje_x" value="{{ old('ubicacion_eje_x') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('ubicacion_eje_x')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Control -->
                        <div>
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
                        <div>
                            <label for="folio_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folio del Elemento</label>
                            <input type="text" name="folio_elemento" id="folio_elemento" value="{{ old('folio_elemento') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('folio_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Versión -->
                        <div>
                            <label for="version_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versión</label>
                            <input type="number" name="version_elemento" id="version_elemento" value="{{ old('version_elemento', '1.0') }}" step="0.1" min="0.1" max="99.9" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('version_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Fecha del Elemento -->
                        <div>
                            <label for="fecha_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Elemento</label>
                            <input type="date" name="fecha_elemento" id="fecha_elemento" value="{{ old('fecha_elemento') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('fecha_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Periodo de Revisión -->
                        <div>
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
                        <div>
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
                        <div>
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
                        <div>
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
                        <div>
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
                        <div>
                            <label for="ubicacion_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación de Resguardo</label>
                            <input type="text" name="ubicacion_resguardo" id="ubicacion_resguardo" value="{{ old('ubicacion_resguardo') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('ubicacion_resguardo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Periodo de Resguardo -->
                        <div>
                            <label for="periodo_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Resguardo</label>
                            <input type="date" name="periodo_resguardo" id="periodo_resguardo" value="{{ old('periodo_resguardo') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('periodo_resguardo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Es Formato -->
                        <div>
                            <label for="es_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">¿Es Formato?</label>
                            <select name="es_formato" id="es_formato" class="form-select mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="no" {{ old('es_formato') == 'no' ? 'selected' : '' }}>No</option>
                                <option value="si" {{ old('es_formato') == 'si' ? 'selected' : '' }}>Sí</option>
                            </select>
                            @error('es_formato')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Archivo Formato -->
                        <div id="archivo_formato_div" class="hidden">
                            <label for="archivo_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo del Formato</label>
                            <input type="file" name="archivo_formato" id="archivo_formato" accept=".pdf,.doc,.docx,.xls,.xlsx" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX</p>
                            @error('archivo_formato')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Sección de Relaciones -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Relaciones del Elemento</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Elemento Padre (Único) -->
                            <div class="col-span-full">
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
                                            <p class="text-sm text-blue-600 dark:text-blue-400">Selecciona el elemento padre de este elemento</p>
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
                            <div class="col-span-full">
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
                                        <select id="filtro_tipo_elemento_relacionados" class="w-full border-green-300 dark:border-green-600 dark:bg-green-800 dark:text-green-200 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
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
                            <div class="col-span-full">
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
                                                <select id="filtro_division" class="w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
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
                                                <select id="filtro_unidad" class="w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
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
                                                <select id="filtro_area" class="w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
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

                                    <!-- Campos adicionales para puestos relacionados -->
                                    <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-600">
                                        <h4 class="text-sm font-medium text-purple-800 dark:text-purple-200 mb-3 flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Información Adicional de Relación
                                        </h4>

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
                                    <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                    @enderror
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
                                    <div class="bg-white dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-600">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" name="correo_implementacion" value="1" {{ old('correo_implementacion') ? 'checked' : '' }} class="rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50">
                                            <span class="ml-3 text-sm font-medium text-amber-800 dark:text-amber-200">Correo de IMPLEMENTACIÓN</span>
                                        </label>
                                        @error('correo_implementacion')
                                        <p class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Correo Agradecimiento -->
                                    <div class="bg-white dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-600">
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
                document.getElementById('es_formato').addEventListener('change', function() {

                    const archivoDiv = document.getElementById('archivo_formato_div');
                    const archivoInput = document.getElementById('archivo_formato');
                    const tipoElementoSelect = document.getElementById('tipo_elemento_id');
                    const tipoElementoSeleccionado = tipoElementoSelect.value;
                    const esProcedimiento = tipoElementoSeleccionado === '1';



                    // Función para actualizar contador
                    function actualizarContador() {
                        const seleccionados = document.querySelectorAll('.puesto-checkbox:checked').length;
                        contadorSeleccionados.textContent = `${seleccionados} puestos seleccionados`;
                    }

                    // Función para aplicar filtros
                    function aplicarFiltros() {
                        const divisionId = filtroDivision.value;
                        const unidadId = filtroUnidad.value;
                        const areaId = filtroArea.value;
                        const texto = busquedaTexto.value.toLowerCase();

                        puestosCheckboxes.forEach(checkbox => {
                            const division = checkbox.dataset.division;
                            const unidad = checkbox.dataset.unidad;
                            const area = checkbox.dataset.area;
                            const nombre = checkbox.dataset.nombre;

                            let mostrar = true;

                            // Filtro por división
                            if (divisionId && division !== divisionId) {
                                mostrar = false;
                            }

                            // Filtro por unidad
                            if (unidadId && unidad !== unidadId) {
                                mostrar = false;
                            }

                            // Filtro por área
                            if (areaId && area !== areaId) {
                                mostrar = false;
                            }

                            // Filtro por texto
                            if (texto && !nombre.includes(texto)) {
                                mostrar = false;
                            }

                            // Mostrar/ocultar checkbox
                            const label = checkbox.closest('label');
                            if (mostrar) {
                                label.style.display = 'flex';
                            } else {
                                label.style.display = 'none';
                            }
                        });
                    }

                    // Cargar unidades de negocio según división
                    function cargarUnidades(divisionId) {
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
                    filtroDivision.addEventListener('change', function() {
                        cargarUnidades(this.value);
                        filtroUnidad.value = '';
                        filtroArea.value = '';
                        aplicarFiltros();
                    });

                    filtroUnidad.addEventListener('change', function() {
                        cargarAreas(this.value);
                        filtroArea.value = '';
                        aplicarFiltros();
                    });

                    filtroArea.addEventListener('change', aplicarFiltros);
                    busquedaTexto.addEventListener('input', aplicarFiltros);

                    // Botón seleccionar todos
                    selectAllBtn.addEventListener('click', function() {
                        const checkboxesVisibles = document.querySelectorAll('.puesto-checkbox');
                        checkboxesVisibles.forEach(checkbox => {
                            const label = checkbox.closest('label');
                            if (label.style.display !== 'none') {
                                checkbox.checked = true;
                            }
                        });
                        actualizarContador();
                    });

                    // Botón deseleccionar todos
                    deselectAllBtn.addEventListener('click', function() {
                        puestosCheckboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        actualizarContador();
                    });

                    // Botón limpiar filtros
                    limpiarFiltrosBtn.addEventListener('click', function() {
                        filtroDivision.value = '';
                        filtroUnidad.value = '';
                        filtroArea.value = '';
                        busquedaTexto.value = '';
                        cargarUnidades('');
                        cargarAreas('');
                        aplicarFiltros();
                    });

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

                    if (this.value === 'si') {
                        archivoDiv.classList.remove('hidden');
                        archivoInput.required = true;

                        // Si es Procedimiento, restringir a solo archivos .doc
                        if (esProcedimiento) {
                            archivoInput.accept = '.doc,.docx';
                            // Actualizar el mensaje de ayuda
                            const mensajeAyuda = archivoDiv.querySelector('p');
                            if (mensajeAyuda) {
                                mensajeAyuda.textContent = 'Formato permitido: .DOCX Los archivos no deben contener imágenes.';
                                mensajeAyuda.className = 'mt-1 text-sm text-orange-600 dark:text-orange-400';
                            }
                        } else {
                            archivoInput.accept = '.pdf,.doc,.docx,.xls,.xlsx';
                            // Restaurar mensaje original
                            const mensajeAyuda = archivoDiv.querySelector('p');
                            if (mensajeAyuda) {
                                mensajeAyuda.textContent = 'Formatos permitidos: PDF, DOCX, XLS, XLSX';
                                mensajeAyuda.className = 'mt-1 text-sm text-gray-500 dark:text-gray-400';
                            }
                        }
                    } else {
                        archivoDiv.classList.add('hidden');
                        archivoInput.required = false;
                    }
                });

                // Funcionalidad del buscador de puestos de trabajo
                const filtroDivision = document.getElementById('filtro_division');
                const filtroUnidad = document.getElementById('filtro_unidad');
                const filtroArea = document.getElementById('filtro_area');
                const busquedaTexto = document.getElementById('busqueda_texto');
                const selectAllBtn = document.getElementById('select_all');
                const deselectAllBtn = document.getElementById('deselect_all');
                const limpiarFiltrosBtn = document.getElementById('limpiar_filtros');
                const contadorSeleccionados = document.getElementById('contador_seleccionados');
                const puestosCheckboxes = document.querySelectorAll('.puesto-checkbox');

                // Trigger inicial para mostrar/ocultar campos de archivo
                const esFormatoSelect = document.getElementById('es_formato');
                if (esFormatoSelect) {
                    console.log('esFormatoSelect', esFormatoSelect);
                    esFormatoSelect.dispatchEvent(new Event('change'));
                }

                // Event listener para el cambio del tipo de elemento
                document.getElementById('tipo_elemento_id').addEventListener('change', function() {
                    const esFormatoSelect = document.getElementById('es_formato');
                    if (esFormatoSelect && esFormatoSelect.value === 'si') {
                        esFormatoSelect.dispatchEvent(new Event('change'));
                    }
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(function() {
                const $tipo = $('#tipo_elemento_id');
                const form = document.getElementById('form-save');

                // Limpiar todos los requeridos visuales y reales
                function limpiarRequeridos() {
                    document.querySelectorAll('input, select, textarea').forEach(el => {
                        el.removeAttribute('required');
                        el.classList.remove('required-outline');
                        el.style.borderColor = '';
                        el.style.boxShadow = '';

                        const $el = $(el);
                        if ($el.data('select2')) {
                            $el.next('.select2-container').find('.select2-selection').removeClass('required-outline')
                                .css({
                                    borderColor: '',
                                    boxShadow: ''
                                });
                        }

                        const label = el.closest('div')?.querySelector('label');
                        if (label) label.innerHTML = label.innerHTML.replace(/\s*<span class="text-red-500">\*<\/span>/, '');
                    });
                }

                // Marcar input como requerido (soloVisual = true => solo marca visualmente)
                function marcarRequerido(el, soloVisual = false) {
                    if (!el) return;

                    const esGrupoPuestos = el.name === 'puestos_relacionados[]' || el.name === 'elemento_relacionado_id[]';
                    const archivoOculto = el.id === 'archivo_formato' && el.closest('#archivo_formato_div.hidden');
                    const archivoFormato = el.id === 'archivo_formato';

                    if (!soloVisual && !esGrupoPuestos && !archivoOculto && !archivoFormato) {
                        el.setAttribute('required', 'required');
                    }

                    const label = el.closest('label') || el.closest('div')?.querySelector('label');
                    if (label && !label.innerHTML.includes('*') && !archivoFormato) {
                        label.insertAdjacentHTML('beforeend', ' <span class="text-red-500">*</span>');
                    }

                    const $el = $(el);
                    if ($el.data('select2')) {
                        $el.next('.select2-container').find('.select2-selection').addClass('required-outline');
                    } else {
                        el.classList.add('required-outline');
                    }
                }

                // Al cambiar tipo de elemento: cargar campos obligatorios
                $tipo.on('change', async function() {
                    const tipoId = this.value;
                    limpiarRequeridos();
                    if (!tipoId) return;

                    try {
                        const res = await fetch(`/tipos-elemento/${tipoId}/campos-obligatorios`);
                        const campos = await res.json();

                        campos.forEach(campo => {
                            const els = document.querySelectorAll(`[name="${campo.campo_nombre}[]"]`);

                            if (els.length > 0) {
                                els.forEach(el => {
                                    if (campo.obligatorio) {
                                        marcarRequerido(el, false); // required real
                                    } else {
                                        marcarRequerido(el, true); // solo visual
                                    }
                                });
                            } else {
                                const ele = document.querySelector(`[name="${campo.campo_nombre}"]`);
                                if (ele) {
                                    ele.classList.remove('border-gray-300', 'dark:border-gray-600', 'focus:ring-indigo-500', 'focus:border-indigo-500');
                                    if (campo.obligatorio) marcarRequerido(ele, false);
                                    else marcarRequerido(ele, true);
                                } else {
                                    console.warn('No se encontró el input para:', campo.campo_nombre);
                                }
                            }
                        });
                    } catch (e) {
                        console.error('Error cargando campos obligatorios:', e);
                    }
                });

                // Validación submit
                form.addEventListener('submit', async function(e) {
                    const tipoId = $tipo.val();
                    if (!tipoId) return;

                    try {
                        const res = await fetch(`/tipos-elemento/${tipoId}/campos-obligatorios`);
                        const campos = await res.json();

                        for (const campo of campos) {
                            if (campo.tipo === 'checkbox_multiple' && campo.obligatorio) {
                                const checkboxes = document.querySelectorAll(`[name="${campo.campo_nombre}[]"]`);
                                const algunoMarcado = Array.from(checkboxes).some(ch => ch.checked);

                                if (!algunoMarcado) {
                                    e.preventDefault();
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: `${campo.label} es obligatorio.`,
                                        showConfirmButton: false,
                                        timerProgressBar: true,
                                        timer: 1500,
                                        position: 'top-right'
                                    });
                                    checkboxes.forEach(ch => ch.classList.add('required-outline'));
                                    break;
                                }
                            }
                        }

                    } catch (err) {
                        console.error('Error validando campos antes de submit:', err);
                    }
                });

                // Trigger inicial si hay valor
                if ($tipo.val()) $tipo.trigger('change');
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

                    function aplicarFiltro() {
                        const tipoSeleccionado = filtro.value;
                        const opciones = select.querySelectorAll('option[data-tipo]');
                        let elementosDisponibles = 0;

                        console.log(`Aplicando filtro ${filtroId} para tipo:`, tipoSeleccionado);

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
                        if (contador) {
                            if (tipoSeleccionado === '') {
                                contador.textContent = `${elementosDisponibles} elementos disponibles`;
                            } else {
                                const tipoNombre = filtro.options[filtro.selectedIndex].text;
                                contador.textContent = `${elementosDisponibles} elementos de tipo "${tipoNombre}" disponibles`;
                            }
                        }

                        // Si es select único y hay una opción seleccionada que no coincide con el filtro, deseleccionarla
                        if (!esMultiple && select.value && tipoSeleccionado !== '') {
                            const opcionSeleccionada = select.querySelector(`option[value="${select.value}"]`);
                            if (opcionSeleccionada && opcionSeleccionada.getAttribute('data-tipo') !== tipoSeleccionado) {
                                select.value = '';
                                console.log('Elemento deseleccionado por no coincidir con el filtro');
                            }
                        }

                        // Forzar actualización de Select2 si está inicializado
                        if (select.classList.contains('select2-hidden-accessible')) {
                            $(select).trigger('change');
                        }
                    }

                    // Aplicar filtro al cambiar el tipo
                    filtro.addEventListener('change', aplicarFiltro);

                    // Si es select único y hay un elemento seleccionado, preseleccionar su tipo en el filtro
                    if (!esMultiple && select.value) {
                        const opcionSeleccionada = select.querySelector(`option[value="${select.value}"]`);
                        if (opcionSeleccionada) {
                            const tipoElemento = opcionSeleccionada.getAttribute('data-tipo');
                            filtro.value = tipoElemento;
                            console.log(`Preseleccionando tipo en ${filtroId}:`, tipoElemento);
                        }
                    }

                    // Aplicar filtro inicial
                    aplicarFiltro();
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
</x-app-layout>
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

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles del Elemento</h2>
            </header>
            <div class="p-6">
                <form action="{{ route('elementos.update', $elemento->id_elemento) }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6" id="form-save">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Elemento -->
                        <div>
                            <label for="tipo_elemento_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Elemento</label>
                            <select name="tipo_elemento_id" id="tipo_elemento_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                            <label for="nombre_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Elemento</label>
                            <input type="text" name="nombre_elemento" id="nombre_elemento" value="{{ old('nombre_elemento', $elemento->nombre_elemento) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                            <label for="unidad_negocio_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unidad de Negocio</label>
                            <select name="unidad_negocio_id" id="unidad_negocio_id" class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                            <label for="ubicacion_eje_x" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación en Eje X</label>
                            <input type="number" name="ubicacion_eje_x" id="ubicacion_eje_x" value="{{ old('ubicacion_eje_x',$elemento->ubicacion_eje_x) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('ubicacion_eje_x')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Control -->
                        <div>
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
                        <div>
                            <label for="folio_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folio del Elemento</label>
                            <input type="text" name="folio_elemento" id="folio_elemento" value="{{ old('folio_elemento', $elemento->folio_elemento) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('folio_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Versión -->
                        <div>
                            <label for="version_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versión</label>
                            <input type="number" name="version_elemento" id="version_elemento" value="{{ old('version_elemento', $elemento->version_elemento) }}" step="0.1" min="0.1" max="99.9" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('version_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Fecha del Elemento -->
                        <div>
                            <label for="fecha_elemento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Elemento</label>
                            <input type="date" name="fecha_elemento" id="fecha_elemento" value="{{ old('fecha_elemento', $elemento->fecha_elemento ? $elemento->fecha_elemento->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('fecha_elemento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Periodo de Revisión -->
                        <div>
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
                        <div>
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
                        <div>
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
                        <div>
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
                        <div>
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
                        <div>
                            <label for="ubicacion_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación de Resguardo</label>
                            <input type="text" name="ubicacion_resguardo" id="ubicacion_resguardo" value="{{ old('ubicacion_resguardo', $elemento->ubicacion_resguardo) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('ubicacion_resguardo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Periodo de Resguardo -->
                        <div>
                            <label for="periodo_resguardo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periodo de Resguardo</label>
                            <input type="date" name="periodo_resguardo" id="periodo_resguardo" value="{{ old('periodo_resguardo', $elemento->periodo_resguardo ? $elemento->periodo_resguardo->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('periodo_resguardo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Es Formato -->
                        <div>
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
                        <div id="archivo_formato_div" class="{{ $elemento->archivo_formato ? '' : 'hidden' }}">
                            <label for="archivo_formato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Archivo del Formato
                            </label>

                            {{-- Input para subir archivo nuevo --}}
                            <input type="file" name="archivo_formato" id="archivo_formato"
                                accept=".pdf,.doc,.docx,.xls,.xlsx"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                            {{-- Texto de ayuda --}}
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="mensaje-ayuda">
                                Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX
                            </p>

                            {{-- Si ya existe archivo, mostrar link de descarga --}}
                            @if(!empty($elemento->archivo_formato))
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <span class="font-medium">Archivo actual:</span>
                                <a href="{{ Storage::url($elemento->archivo_formato) }}"
                                    target="_blank"
                                    class="inline-block px-3 py-1 rounded-md text-sm font-semibold
                                        bg-indigo-600 text-white hover:bg-indigo-700
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1
                                        transition hover:scale-105 transition-all">
                                    Visualizar
                                </a>
                            </p>

                            @endif

                            {{-- Errores de validación --}}
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
                                <label for="elemento_padre_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elemento al que pertenece</label>

                                <!-- Filtro por tipo de elemento -->
                                <div class="mb-3">
                                    <label for="filtro_tipo_elemento" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Filtrar por tipo de elemento</label>
                                    <select id="filtro_tipo_elemento" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                            <div class="col-span-full">
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
                            <div class="col-span-full">
                                <label for="puestos_relacionados[]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Puestos de Trabajo Relacionados</label>

                                <!-- Filtros de búsqueda -->
                                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                        <!-- Filtro por División -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por División</label>
                                            <select id="filtro_division" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">Todas las divisiones</option>
                                                @foreach($divisions ?? [] as $division)
                                                <option value="{{ $division->id_division }}">{{ $division->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Filtro por Unidad de Negocio -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por Unidad</label>
                                            <select id="filtro_unidad" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">Todas las unidades</option>
                                            </select>
                                        </div>

                                        <!-- Filtro por Área -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por Área</label>
                                            <select id="filtro_area" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                            <div>
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
                            <div>
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
                    const esProcedimiento = tipoElementoSeleccionado === '1'; // ID del tipo "Procedimiento"

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

                    if (this.value === 'si') {
                        archivoDiv.classList.remove('hidden');
                        archivoInput.required = true;

                        const tipoElementoSelect = document.getElementById('tipo_elemento_id');
                        const esProcedimiento = tipoElementoSelect && tipoElementoSelect.value === '1';

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

            // Trigger inicial para mostrar/ocultar campos de archivo
            if (esFormato) {
                esFormato.dispatchEvent(new Event('change'));
            }

            // Event listener para el cambio del tipo de elemento
            const tipoElemento = document.getElementById('tipo_elemento_id');
            if (tipoElemento) {
                tipoElemento.addEventListener('change', function() {
                    if (esFormato && esFormato.value === 'si') {
                        esFormato.dispatchEvent(new Event('change'));
                    }
                });
            }
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
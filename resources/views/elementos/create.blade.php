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
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
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
                            <form action="{{ route('elementos.store') }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6">
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
                                        <!-- Elementos Padre (Múltiples) -->
                                        <div class="col-span-full">
                                            <label for="elementos_padre[]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elementos al que pertenece</label>
                                            <select name="elementos_padre[]" id="elementos_padre" multiple class="select2-multiple mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                @foreach($elementos as $elemento)
                                                    <option value="{{ $elemento->id_elemento }}" {{ in_array($elemento->id_elemento, old('elementos_padre', [])) ? 'selected' : '' }}>
                                                        {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
                                                    </option>
                                                @endforeach
                                            </select>
                                           
                                            @error('elementos_padre')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Elementos Relacionados (Múltiples) -->
                                        <div class="col-span-full">
                                            <label for="elementos_relacionados[]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elementos Relacionados</label>
                                            <select name="elementos_relacionados[]" id="elementos_relacionados" multiple class="select2-multiple mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                                @foreach($elementos as $elemento)
                                                    <option value="{{ $elemento->id_elemento }}" {{ in_array($elemento->id_elemento, old('elementos_relacionados', [])) ? 'selected' : '' }}>
                                                        {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
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
                                                             <input type="checkbox" name="puestos_relacionados[]" value="{{ $puesto->id_puesto_trabajo }}" 
                                                                    class="puesto-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                                    data-division="{{ $puesto->division->id_division ?? '' }}"
                                                                    data-unidad="{{ $puesto->unidadNegocio->id_unidad_negocio ?? '' }}"
                                                                    data-area="{{ $puesto->area->id_area ?? '' }}"
                                                                    data-nombre="{{ strtolower($puesto->nombre) }}"
                                                                    {{ in_array($puesto->id_puesto_trabajo, old('puestos_relacionados', [])) ? 'checked' : '' }}>
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
                                                         <input type="text" name="nombres_relacion[]" placeholder="Nombre" class="flex-1 border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                                                         <button type="button" class="btn-agregar-nombre px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm">
                                                             +
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
                                                <input type="checkbox" name="correo_implementacion" value="1" {{ old('correo_implementacion') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Correo de IMPLEMENTACIÓN</span>
                                            </label>
                                            @error('correo_implementacion')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Correo Agradecimiento -->
                                        <div>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="correo_agradecimiento" value="1" {{ old('correo_agradecimiento') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
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
                                        Crear Elemento
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
                closeOnSelect: false,
                selectionCssClass: 'select2--large',
                dropdownCssClass: 'select2--large'
            });

            // Mostrar/ocultar campos de archivo según selección
            document.getElementById('es_formato').addEventListener('change', function() {
                
                const archivoDiv = document.getElementById('archivo_formato_div');
                const archivoInput = document.getElementById('archivo_formato');
                
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
                    if (e.target.classList.contains('btn-agregar-nombre')) {
                        const container = document.getElementById('campos_nombre_container');
                        const nuevoCampo = document.createElement('div');
                        nuevoCampo.className = 'flex items-center gap-2 mb-2';
                        nuevoCampo.innerHTML = `
                            <input type="text" name="nombres_relacion[]" placeholder="Nombre" class="flex-1 border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                            <button type="button" class="btn-eliminar-nombre px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
                                -
                            </button>
                        `;
                        container.appendChild(nuevoCampo);
                    }
                    
                    if (e.target.classList.contains('btn-eliminar-nombre')) {
                        e.target.closest('.flex').remove();
                    }
                });
                
                if (this.value === 'si') {
                    archivoDiv.classList.remove('hidden');
                    archivoInput.required = true;
                } else {
                    archivoDiv.classList.add('hidden');
                    archivoInput.required = false;
                }
            });

            // Trigger inicial para mostrar/ocultar campos de archivo
            const esFormatoSelect = document.getElementById('es_formato');
            if (esFormatoSelect) {
                console.log('esFormatoSelect', esFormatoSelect);
                esFormatoSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</x-app-layout>

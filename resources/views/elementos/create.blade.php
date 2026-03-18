<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                    Nuevo Elemento
                </h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a
                    href="{{ route('elementos.index') }}"
                    class="btn bg-red-500 hover:bg-red-600 text-white flex items-center">
                    <svg class="w-4 h-4 fill-current opacity-80 shrink-0" viewBox="0 0 16 16">
                        <path
                            d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="ml-2">Volver</span>
                </a>
            </div>
        </div>

        <!-- Horizontal Stepper -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="px-8 py-6">
                <div class="flex items-center justify-between max-w-3xl mx-auto">
                    <!-- Step 1 -->
                    <div class="wizard-step flex flex-col items-center cursor-pointer group" data-step="1">
                        <div class="step-circle relative flex items-center justify-center w-12 h-12 rounded-full font-bold text-base transition-all duration-300 bg-indigo-600 text-white shadow-lg">
                            <span class="step-number">1</span>
                            <svg class="step-check hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 transition-colors">Tipo de Elemento</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Selecciona el tipo</p>
                        </div>
                    </div>

                    <!-- Connector 1 -->
                    <div class="step-line flex-1 h-1 mx-4 bg-gray-200 dark:bg-gray-700 rounded-full transition-all duration-300"></div>

                    <!-- Step 2 -->
                    <div class="wizard-step flex flex-col items-center cursor-pointer group" data-step="3">
                        <div class="step-circle relative flex items-center justify-center w-12 h-12 rounded-full font-bold text-base transition-all duration-300 bg-gray-300 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            <span class="step-number">2</span>
                            <svg class="step-check hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 transition-colors">Detalles del Elemento</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Completa la información</p>
                        </div>
                    </div>

                    <!-- Connector 2 -->
                    <div class="step-line flex-1 h-1 mx-4 bg-gray-200 dark:bg-gray-700 rounded-full transition-all duration-300"></div>

                    <!-- Step 3 -->
                    <div class="wizard-step flex flex-col items-center cursor-pointer group" data-step="2">
                        <div class="step-circle relative flex items-center justify-center w-12 h-12 rounded-full font-bold text-base transition-all duration-300 bg-gray-300 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            <span class="step-number">3</span>
                            <svg class="step-check hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 transition-colors">Firmas del Documento</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Asigna responsables</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Content Container -->
        <div class="max-w-5xl mx-auto">
            <form
                id="form-save"
                action="{{ route('elementos.store') }}"
                method="POST"
                enctype="multipart/form-data">
                @csrf

                <!-- PASO 1: Tipo de Elemento -->
                <div class="wizard-content step-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 opacity-0 transform translate-y-4 transition-all duration-300">
                    <div class="p-8">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Selecciona el Tipo de Elemento</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                Elige qué tipo de elemento deseas crear
                            </p>
                        </div>

                        <div>
                            <label for="tipo_elemento_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo de Elemento
                            </label>

                            <select
                                name="tipo_elemento_id"
                                id="tipo_elemento_id"
                                class="select2 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                data-placeholder="Seleccionar tipo de elemento">
                                <option value="">Seleccionar tipo</option>
                                @foreach($tiposElemento as $tipo)
                                <option
                                    value="{{ $tipo->id_tipo_elemento }}"
                                    {{ old('tipo_elemento_id') == $tipo->id_tipo_elemento ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                                @endforeach
                            </select>

                            @error('tipo_elemento_id')
                            <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-end mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" onclick="nextStep()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                                Siguiente
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: Firmas / Responsables -->
                <div class="wizard-content step-3 hidden opacity-0 transform translate-y-4 transition-all duration-300 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-8">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Firmas del Documento</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                Arrastra las tarjetas para cambiar el orden de las firmas
                            </p>
                        </div>

                        <div data-relacion="esfirma">
                            <div id="sortable-firmas" class="space-y-4">
                                <!-- Participantes -->
                                <div class="firma-card bg-gray-50 dark:bg-gray-900/50 rounded-lg p-5 cursor-move hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700" data-tipo="participantes">
                                    <div class="flex items-center gap-3 mb-3">
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 drag-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                        <div class="flex-1">
                                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                <span class="orden-badge bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 px-2.5 py-0.5 rounded-full text-xs font-bold">1</span>
                                                Participantes
                                            </label>
                                        </div>
                                    </div>
                                    <select name="participantes[]" multiple class="select2 w-full" data-static="true" data-placeholder="Selecciona participantes">
                                        @foreach ($empleados as $e)
                                        <option value="{{ $e->id_empleado }}">
                                            {{ $e->nombres }} {{ $e->apellido_paterno }} — {{ $e->puestoTrabajo->nombre ?? 'Sin puesto' }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="prioridad_participantes" value="1">
                                </div>

                                <!-- Responsables -->
                                <div class="firma-card bg-gray-50 dark:bg-gray-900/50 rounded-lg p-5 cursor-move hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700" data-tipo="responsables">
                                    <div class="flex items-center gap-3 mb-3">
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 drag-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                        <div class="flex-1">
                                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                <span class="orden-badge bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 px-2.5 py-0.5 rounded-full text-xs font-bold">2</span>
                                                Responsables
                                            </label>
                                        </div>
                                    </div>
                                    <select name="responsables[]" multiple class="select2 w-full" data-static="true" data-placeholder="Selecciona responsables">
                                        @foreach ($empleados as $e)
                                        <option value="{{ $e->id_empleado }}">
                                            {{ $e->nombres }} {{ $e->apellido_paterno }} — {{ $e->puestoTrabajo->nombre ?? 'Sin puesto' }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="prioridad_responsables" value="2">
                                </div>

                                <!-- Revisó -->
                                <div class="firma-card bg-gray-50 dark:bg-gray-900/50 rounded-lg p-5 cursor-move hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700" data-tipo="reviso">
                                    <div class="flex items-center gap-3 mb-3">
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 drag-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                        <div class="flex-1">
                                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                <span class="orden-badge bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 px-2.5 py-0.5 rounded-full text-xs font-bold">3</span>
                                                Revisó
                                            </label>
                                        </div>
                                    </div>
                                    <select name="reviso[]" multiple class="select2 w-full" data-static="true" data-placeholder="Selecciona quien revisa">
                                        @foreach ($empleados as $e)
                                        <option value="{{ $e->id_empleado }}">
                                            {{ $e->nombres }} {{ $e->apellido_paterno }} — {{ $e->puestoTrabajo->nombre ?? 'Sin puesto' }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="prioridad_reviso" value="3">
                                </div>

                                <!-- Autorizó -->
                                <div class="firma-card bg-gray-50 dark:bg-gray-900/50 rounded-lg p-5 cursor-move hover:shadow-md transition-all duration-200 border border-gray-200 dark:border-gray-700" data-tipo="autorizo">
                                    <div class="flex items-center gap-3 mb-3">
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 drag-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                        <div class="flex-1">
                                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                                <span class="orden-badge bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 px-2.5 py-0.5 rounded-full text-xs font-bold">4</span>
                                                Autorizó
                                            </label>
                                        </div>
                                    </div>
                                    <select name="autorizo[]" multiple class="select2 w-full" data-static="true" data-placeholder="Selecciona quien autoriza">
                                        @foreach ($empleados as $e)
                                        <option value="{{ $e->id_empleado }}">
                                            {{ $e->nombres }} {{ $e->apellido_paterno }} — {{ $e->puestoTrabajo->nombre ?? 'Sin puesto' }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="prioridad_autorizo" value="4">
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" onclick="prevStep()" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Anterior
                            </button>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('elementos.index') }}" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors duration-200">
                                    Cancelar
                                </a>
                                <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Crear Elemento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: Formulario Principal -->
                <div class="wizard-content step-2 hidden opacity-0 transform translate-y-4 transition-all duration-300 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-8">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Detalles del Elemento</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                Completa toda la información requerida
                            </p>
                        </div>
                        <!-- Hidden sincronizado con Paso 1 -->
                        <input
                            type="hidden"
                            id="tipo_elemento_id_hidden"
                            value="{{ old('tipo_elemento_id') }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre del Elemento -->
                            <div data-campo>
                                <label
                                    for="nombre_elemento"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nombre del Elemento
                                </label>
                                <input
                                    type="text"
                                    name="nombre_elemento"
                                    id="nombre_elemento"
                                    value="{{ old('nombre_elemento') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('nombre_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tipo de Proceso -->
                            <div data-campo>
                                <label
                                    for="tipo_proceso_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tipo de Proceso
                                </label>
                                <select
                                    name="tipo_proceso_id"
                                    id="tipo_proceso_id"
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar proceso">
                                    <option value="">Seleccionar proceso</option>
                                    @foreach($tiposProceso as $proceso)
                                    <option
                                        value="{{ $proceso->id_tipo_proceso }}"
                                        {{ old('tipo_proceso_id') == $proceso->id_tipo_proceso ? 'selected' : '' }}>
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
                                <label
                                    for="unidad_negocio_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Unidad de Negocio
                                </label>
                                <select
                                    name="unidad_negocio_id[]"
                                    id="unidad_negocio_id"
                                    multiple
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar unidad">
                                    <option value="">Seleccionar unidad</option>
                                    @foreach($unidadesNegocio as $unidad)
                                    <option
                                        value="{{ $unidad->id_unidad_negocio }}"
                                        {{ old('unidad_negocio_id') == $unidad->id_unidad_negocio ? 'selected' : '' }}>
                                        {{ $unidad->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('unidad_negocio_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Ubicación Eje X -->
                            <div data-campo>
                                <label
                                    for="ubicacion_eje_x"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Ubicación en Eje X
                                </label>
                                <input
                                    type="number"
                                    name="ubicacion_eje_x"
                                    id="ubicacion_eje_x"
                                    value="{{ old('ubicacion_eje_x') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('ubicacion_eje_x')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Control -->
                            <div data-campo>
                                <label
                                    for="control"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Control
                                </label>
                                <select
                                    name="control"
                                    id="control"
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar control">
                                    <option value="interno" {{ old('control') == 'interno' ? 'selected' : '' }}>
                                        Interno
                                    </option>
                                    <option value="externo" {{ old('control') == 'externo' ? 'selected' : '' }}>
                                        Externo
                                    </option>
                                </select>
                                @error('control')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Folio -->
                            <div data-campo>
                                <label
                                    for="folio_elemento"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Folio del Elemento
                                </label>
                                <input
                                    type="text"
                                    name="folio_elemento"
                                    id="folio_elemento"
                                    value="{{ old('folio_elemento') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('folio_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Versión -->
                            <div data-campo>
                                <label
                                    for="version_elemento"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Versión
                                </label>
                                <input
                                    type="number"
                                    name="version_elemento"
                                    id="version_elemento"
                                    value="{{ old('version_elemento', '1') }}"
                                    step="1"
                                    min="1"
                                    max="100"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('version_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Fecha del Elemento -->
                            <div data-campo>
                                <label
                                    for="fecha_elemento"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Fecha del Elemento
                                </label>
                                <input
                                    type="date"
                                    name="fecha_elemento"
                                    id="fecha_elemento"
                                    value="{{ old('fecha_elemento') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('fecha_elemento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Periodo de Revisión -->
                            <div data-campo>
                                <label
                                    for="periodo_revision"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Periodo de Revisión
                                </label>
                                <input
                                    type="date"
                                    name="periodo_revision"
                                    id="periodo_revision"
                                    value="{{ old('periodo_revision', now()->addYears(2)->format('Y-m-d')) }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('periodo_revision')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <!-- Semáforo -->
                                <div id="semaforo-container" class="mt-2 hidden">
                                    <div class="flex items-center space-x-2">
                                        <span
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Estado:
                                        </span>
                                        <div id="semaforo-dinamico"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Puesto Responsable -->
                            <div data-campo>
                                <label
                                    for="puesto_responsable_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Puesto Responsable
                                </label>
                                <select
                                    name="puesto_responsable_id"
                                    id="puesto_responsable_id"
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar puesto">
                                    <option value="">Seleccionar puesto</option>
                                    @foreach($puestosTrabajo as $puesto)
                                    <option
                                        value="{{ $puesto->id_puesto_trabajo }}"
                                        {{ old('puesto_responsable_id') == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
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
                                <label
                                    for="puesto_ejecutor_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Puesto Ejecutor
                                </label>
                                <select
                                    name="puesto_ejecutor_id"
                                    id="puesto_ejecutor_id"
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar puesto">
                                    <option value="">Seleccionar puesto</option>
                                    @foreach($puestosTrabajo as $puesto)
                                    <option
                                        value="{{ $puesto->id_puesto_trabajo }}"
                                        {{ old('puesto_ejecutor_id') == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
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
                                <label
                                    for="puesto_resguardo_id"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Puesto de Resguardo
                                </label>
                                <select
                                    name="puesto_resguardo_id"
                                    id="puesto_resguardo_id"
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar puesto">
                                    <option value="">Seleccionar puesto</option>
                                    @foreach($puestosTrabajo as $puesto)
                                    <option
                                        value="{{ $puesto->id_puesto_trabajo }}"
                                        {{ old('puesto_resguardo_id') == $puesto->id_puesto_trabajo ? 'selected' : '' }}>
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
                                <label
                                    for="medio_soporte"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Medio de Soporte
                                </label>
                                <select
                                    name="medio_soporte"
                                    id="medio_soporte"
                                    class="select2 mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    data-placeholder="Seleccionar medio">
                                    <option value="digital" {{ old('medio_soporte') == 'digital' ? 'selected' : '' }}>
                                        Digital
                                    </option>
                                    <option value="fisico" {{ old('medio_soporte') == 'fisico' ? 'selected' : '' }}>
                                        Físico
                                    </option>
                                </select>
                                @error('medio_soporte')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Ubicación de Resguardo -->
                            <div data-campo>
                                <label
                                    for="ubicacion_resguardo"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Ubicación de Resguardo
                                </label>
                                <input
                                    type="text"
                                    name="ubicacion_resguardo"
                                    id="ubicacion_resguardo"
                                    value="{{ old('ubicacion_resguardo') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('ubicacion_resguardo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Periodo de Resguardo -->
                            <div data-campo>
                                <label
                                    for="periodo_resguardo"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Periodo de Resguardo
                                </label>
                                <input
                                    type="date"
                                    name="periodo_resguardo"
                                    id="periodo_resguardo"
                                    value="{{ old('periodo_resguardo') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('periodo_resguardo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Es Formato -->
                            <div>
                                <label
                                    for="es_formato"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ¿Es Formato?
                                </label>
                                <select
                                    name="es_formato"
                                    id="es_formato"
                                    class="select2 mt-1 block w-full ..."
                                    data-placeholder="Seleccionar opción">
                                    <option value="no" {{ old('es_formato', 'no') == 'no' ? 'selected' : '' }}>No</option>
                                    <option value="si" {{ old('es_formato', 'no') == 'si' ? 'selected' : '' }}>Sí</option>
                                </select>
                                @error('es_formato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Archivos (Formato y Elemento) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <!-- Archivo Formato -->
                                <div id="archivo_formato_div" class="{{ old('es_formato', 'no') == 'si' ? '' : 'hidden' }}">
                                    <label
                                        for="archivo_formato"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                        Archivo del Formato
                                    </label>
                                    <div
                                        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-8 h-8 text-indigo-500 mb-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 16a4 4 0 01-4-4m0 0a4 4 0 018 0m0 0a4 4 0 018 0m0 0a4 4 0 01-4 4m-4 4h.01M12 12v4m0 0l-2 2m2-2l2 2" />
                                        </svg>
                                        <input
                                            type="file"
                                            name="archivo_formato"
                                            id="archivo_formato"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx"
                                            class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                        <p id="mensaje"
                                            class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            PDF, DOCX, XLSX
                                        </p>
                                    </div>
                                    @error('archivo_formato')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Archivo Elemento -->
                                <div id="archivo_elemento_div" data-campo>
                                    <label
                                        for="archivo_es_formato"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                        Archivo del Elemento
                                    </label>
                                    <div
                                        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-8 h-8 text-indigo-500 mb-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 7h4l3 3h11v8a2 2 0 01-2 2H3a2 2 0 01-2-2V7z" />
                                        </svg>
                                        <input
                                            type="file"
                                            name="archivo_es_formato"
                                            id="archivo_es_formato"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx"
                                            class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 cursor-pointer">
                                        <p id="mensaje2"
                                            class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            DOCX
                                        </p>
                                    </div>
                                    @error('archivo_es_formato')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Relaciones -->
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Relaciones del Elemento
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Elemento Padre -->
                                <div class="col-span-full" data-relacion="elemento_padre_id">
                                    <div
                                        class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4
                                                    class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                                    Elemento al que pertenece
                                                </h4>
                                                <p
                                                    class="text-sm text-blue-600 dark:text-blue-400">
                                                    Selecciona al elemento que pertenece
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Filtro tipo elemento padre -->
                                        <div class="mb-4">
                                            <label
                                                for="filtro_tipo_elemento"
                                                class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                                </svg>
                                                Filtrar por tipo de elemento
                                            </label>
                                            <select
                                                id="filtro_tipo_elemento"
                                                class="select2 w-full border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                                data-placeholder="Todos los tipos">
                                                <option value="">Todos los tipos</option>
                                                @foreach($tiposElemento as $tipo)
                                                <option value="{{ $tipo->id_tipo_elemento }}">
                                                    {{ $tipo->nombre }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="relative">
                                            <select
                                                name="elemento_padre_id"
                                                id="elemento_padre_id"
                                                class="select2 block w-full border-blue-300 dark:border-blue-600 dark:bg-blue-800 dark:text-blue-200 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                                data-placeholder="Seleccionar elemento padre">
                                                <option value="">Seleccionar elemento padre</option>
                                                @foreach($elementosPublicados as $elemento)
                                                <option
                                                    value="{{ $elemento->id_elemento }}"
                                                    data-tipo="{{ $elemento->tipo_elemento_id }}"
                                                    {{ old('elemento_padre_id') == $elemento->id_elemento ? 'selected' : '' }}>
                                                    {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
                                                </option>
                                                @endforeach
                                            </select>

                                            <div
                                                id="contador-elementos"
                                                class="mt-3 flex items-center justify-between text-sm">
                                                <span
                                                    class="text-blue-600 dark:text-blue-400 font-medium">
                                                    Elementos disponibles:
                                                </span>
                                                <span
                                                    class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full font-semibold">
                                                    {{ count($elementos) }} elementos
                                                </span>
                                            </div>
                                        </div>

                                        @error('elemento_padre_id')
                                        <p
                                            class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Elementos Relacionados -->
                                <div class="col-span-full" data-relacion="elemento_relacionado_id">
                                    <div
                                        class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700 p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4
                                                    class="text-lg font-semibold text-green-900 dark:text-green-100">
                                                    Elementos Relacionados
                                                </h4>
                                                <p
                                                    class="text-sm text-green-600 dark:text-green-400">
                                                    Selecciona múltiples elementos relacionados
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Filtro tipo elemento relacionados -->
                                        <div class="mb-4">
                                            <label
                                                for="filtro_tipo_elemento_relacionados"
                                                class="block text-sm font-medium text-green-800 dark:text-green-200 mb-2 flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                                </svg>
                                                Filtrar por tipo de elemento
                                            </label>
                                            <select
                                                id="filtro_tipo_elemento_relacionados"
                                                class="select2 w-full border-green-300 dark:border-green-600 dark:bg-green-800 dark:text-green-200 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 transition-colors duración-200"
                                                data-placeholder="Todos los tipos">
                                                <option value="">Todos los tipos</option>
                                                @foreach($tiposElemento as $tipo)
                                                <option value="{{ $tipo->id_tipo_elemento }}">
                                                    {{ $tipo->nombre }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="relative">
                                            <select
                                                name="elemento_relacionado_id[]"
                                                id="elemento_relacionado_id"
                                                multiple
                                                class="select2-multiple block w-full border-green-300 dark:border-green-600 dark:bg-green-800 dark:text-green-200 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 transition-colors duration-200"
                                                data-placeholder="Seleccionar elementos relacionados">
                                                @foreach($elementosPublicados as $elemento)
                                                <option
                                                    value="{{ $elemento->id_elemento }}"
                                                    data-tipo="{{ $elemento->tipo_elemento_id }}"
                                                    {{ in_array($elemento->id_elemento, old('elemento_relacionado_id', [])) ? 'selected' : '' }}>
                                                    {{ $elemento->nombre_elemento }} - {{ $elemento->folio_elemento }}
                                                </option>
                                                @endforeach
                                            </select>

                                            <div
                                                id="contador-elementos-relacionados"
                                                class="mt-3 flex items-center justify-between text-sm">
                                                <span
                                                    class="text-green-600 dark:text-green-400 font-medium">
                                                    Elementos disponibles:
                                                </span>
                                                <span
                                                    class="bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200 px-3 py-1 rounded-full font-semibold">
                                                    {{ count($elementos) }} elementos
                                                </span>
                                            </div>
                                        </div>

                                        @error('elemento_relacionado_id')
                                        <p
                                            class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">
                                            {{ $message }}
                                        </p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Puestos Relacionados -->
                                <div class="col-span-full" data-relacion="puestos_relacionados">
                                    <div
                                        class="bg-gradient-to-r from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-lg border border-purple-200 dark:border-purple-700 p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4
                                                    class="text-lg font-semibold text-purple-900 dark:text-purple-100">
                                                    Puestos de Trabajo Relacionados
                                                </h4>
                                                <p
                                                    class="text-sm text-purple-600 dark:text-purple-400">
                                                    Selecciona los puestos de trabajo relacionados
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Filtros puestos -->
                                        <div
                                            class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-600">
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                                <!-- División -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                        Filtrar por División
                                                    </label>
                                                    <select
                                                        id="filtro_division"
                                                        class="select2 w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                                        data-placeholder="Todas las divisiones">
                                                        <option value="">Todas las divisiones</option>
                                                        @foreach($divisions ?? [] as $division)
                                                        <option value="{{ $division->id_division }}">
                                                            {{ $division->nombre }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Unidad -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                        Filtrar por Unidad
                                                    </label>
                                                    <select
                                                        id="filtro_unidad"
                                                        class="select2 w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                                        data-placeholder="Todas las unidades">
                                                        <option value="">Todas las unidades</option>
                                                    </select>
                                                </div>

                                                <!-- Área -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        Filtrar por Área
                                                    </label>
                                                    <select
                                                        id="filtro_area"
                                                        class="select2 w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                                        data-placeholder="Todas las áreas">
                                                        <option value="">Todas las áreas</option>
                                                    </select>
                                                </div>

                                                <!-- Texto -->
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-purple-700 dark:text-purple-300 mb-2 flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                        Buscar por nombre
                                                    </label>
                                                    <input
                                                        type="text"
                                                        id="busqueda_texto"
                                                        placeholder="Buscar puestos..."
                                                        class="w-full border-purple-300 dark:border-purple-600 dark:bg-purple-800 dark:text-purple-200 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                                </div>
                                            </div>

                                            <!-- Controles selección -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <button
                                                        type="button"
                                                        id="select_all"
                                                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg font-medium flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Seleccionar Todos
                                                    </button>

                                                    <button
                                                        type="button"
                                                        id="deselect_all"
                                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg font-medium flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round"
                                                                stroke-linejoin="round" stroke-width="2"
                                                                d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Deseleccionar Todos
                                                    </button>

                                                    <span
                                                        id="contador_seleccionados"
                                                        class="text-sm text-purple-600 dark:text-purple-400 font-medium bg-purple-100 dark:bg-purple-800 px-3 py-1 rounded-full">
                                                        0 puestos seleccionados
                                                    </span>
                                                </div>

                                                <button
                                                    type="button"
                                                    id="limpiar_filtros"
                                                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg font-medium flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round" stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Limpiar Filtros
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Lista de puestos -->
                                        <div
                                            class="max-h-96 overflow-y-auto border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-purple-900/20">
                                            <div id="lista_puestos" class="p-4 space-y-2">
                                                @foreach($puestosTrabajo as $puesto)
                                                <label
                                                    class="flex items-center p-3 hover:bg-purple-50 dark:hover:bg-purple-800/30 rounded-lg cursor-pointer transition-colors duration-200 border border-transparent hover:border-purple-200 dark:hover:border-purple-600">
                                                    <input
                                                        type="checkbox"
                                                        name="puestos_relacionados[]"
                                                        value="{{ $puesto->id_puesto_trabajo }}"
                                                        class="puesto-checkbox rounded border-purple-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50"
                                                        data-division="{{ $puesto->division->id_division ?? '' }}"
                                                        data-unidad="{{ $puesto->unidadNegocio->id_unidad_negocio ?? '' }}"
                                                        data-areas="@json($puesto->areas->pluck(" id_area"))"
                                                        data-nombre="{{ strtolower($puesto->nombre) }}"
                                                        {{ in_array($puesto->id_puesto_trabajo, old('puestos_relacionados', [])) ? 'checked' : '' }}>
                                                    <span class="ml-3 text-sm">
                                                        <span
                                                            class="font-medium text-purple-900 dark:text-purple-100">
                                                            {{ $puesto->nombre }}
                                                        </span>
                                                        <div class="flex flex-wrap gap-1 text-xs">
                                                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                                                                {{ $puesto->division->nombre ?? 'Sin división' }}
                                                            </span>
                                                            <span
                                                                class="px-2 py-0.5 rounded text-xs bg-orange-100 text-orange-800">
                                                                {{ $puesto->unidadNegocio->nombre ?? 'Sin unidad' }}
                                                            </span>
                                                            @foreach ($puesto->areas as $area)
                                                            <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800">
                                                                {{ $area->nombre }}
                                                            </span>
                                                            @endforeach
                                                        </div>
                                                    </span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    @error('puestos_relacionados')
                                    <p
                                        class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">
                                        {{ $message }}
                                    </p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Campos adicionales de relación (comités) -->
                            <div
                                class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-600">
                                <h4
                                    class="text-sm font-medium text-purple-800 dark:text-purple-200 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Información Adicional de Relación
                                </h4>

                                <div id="campos_nombre_container" class="space-y-2">
                                    <div class="flex items-center gap-3 campo-relacion fila-relacion">
                                        <input
                                            name="nombres_relacion[0]"
                                            type="text"
                                            placeholder="Buscar comité"
                                            class="input-relacion border border-gray-300 rounded-md px-2 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">

                                        <select
                                            class="form-select select2 campo-relacion"
                                            name="puesto_id[0][]"
                                            multiple
                                            data-placeholder="Selecciona puestos">
                                            <option></option>

                                            @foreach ($grupos as $division => $unidades)
                                            <optgroup label="{{ $division }}">
                                                @foreach ($unidades as $unidad => $puestos)
                                            <optgroup label="&nbsp;&nbsp;{{ $unidad }}">
                                                @foreach ($puestos as $puesto)
                                                <option
                                                    value="{{ $puesto['id'] }}"
                                                    @selected(in_array($puesto['id'], $puestosIds[0] ?? []))>
                                                    {{ $puesto['nombre'] }}
                                                </option>
                                                @endforeach
                                            </optgroup>
                                            @endforeach
                                            </optgroup>
                                            @endforeach
                                        </select>

                                        <button
                                            type="button"
                                            class="btn-agregar-nombre px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuraciones Adicionales -->
                            <div class="mt-8">
                                <div
                                    class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-lg border border-amber-200 dark:border-amber-700 p-6">
                                    <div class="flex items-center mb-4">
                                        <div class="flex-shrink-0">
                                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3
                                                class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                                                Configuraciones Adicionales
                                            </h3>
                                            <p
                                                class="text-sm text-amber-600 dark:text-amber-400">
                                                Configura las opciones de correo para este elemento
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Correo Implementación -->
                                        <div
                                            data-relacion="correo_implementacion"
                                            class="bg-white dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-600">
                                            <label class="flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="correo_implementacion"
                                                    value="1"
                                                    {{ old('correo_implementacion') ? 'checked' : '' }}
                                                    class="rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50">
                                                <span
                                                    class="ml-3 text-sm font-medium text-amber-800 dark:text-amber-200">
                                                    Correo de IMPLEMENTACIÓN
                                                </span>
                                            </label>
                                            @error('correo_implementacion')
                                            <p
                                                class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>

                                        <!-- Correo Agradecimiento -->
                                        <div
                                            data-relacion="correo_agradecimiento"
                                            class="bg-white dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-600">
                                            <label class="flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="correo_agradecimiento"
                                                    value="1"
                                                    {{ old('correo_agradecimiento') ? 'checked' : '' }}
                                                    class="rounded border-amber-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50">
                                                <span
                                                    class="ml-3 text-sm font-medium text-amber-800 dark:text-amber-200">
                                                    Correo de AGRADECIMIENTO
                                                </span>
                                            </label>
                                            @error('correo_agradecimiento')
                                            <p
                                                class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-md">
                                                {{ $message }}
                                            </p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="flex justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" onclick="prevStep()" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Anterior
                                </button>
                                <button type="button" onclick="nextStep()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                                    Siguiente
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.7/dist/autoComplete.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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

        .archivo-seleccionado {
            background-color: #00c444ff !important;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        /* --- SWEETALERT2 PERSONALIZADO (LIGHT & DARK) --- */

        /* Loader siempre violeta (tu color de marca) */
        .swal2-popup.colored-loader .swal2-loader {
            border-color: #8b5cf6 transparent #8b5cf6 transparent !important;
        }

        /* Estilos Light (Por defecto) */
        .swal2-popup.colored-loader {
            background-color: #ffffff !important;
        }

        .swal2-popup.colored-loader .swal2-title {
            color: #4c1d95 !important;
            /* Violet-900 */
        }

        .swal2-popup.colored-loader .swal2-html-container {
            color: #4b5563 !important;
            /* Gray-600 */
        }

        /* Estilos Dark (Se activan si existe la clase .dark en el body o html) */
        :is(.dark) .swal2-popup.colored-loader {
            background-color: #1f2937 !important;
            /* Slate-800 */
            border: 1px solid #374151 !important;
            /* Slate-700 border opcional */
        }

        :is(.dark) .swal2-popup.colored-loader .swal2-title {
            color: #a78bfa !important;
            /* Violet-400 (más claro para fondo oscuro) */
        }

        :is(.dark) .swal2-popup.colored-loader .swal2-html-container {
            color: #d1d5db !important;
            /* Gray-300 */
        }

        .sortable-ghost {
            opacity: 0.4;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .sortable-drag {
            opacity: 1;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: rotate(2deg);
        }

        .firma-card {
            transition: all 0.2s ease;
        }

        .firma-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .drag-handle {
            cursor: grab;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    </style>

    <!-- Inicialización de Sortable para las firmas -->
    <script>
        function initSortableFirmas() {
            var el = document.getElementById('sortable-firmas');
            if (!el) return;

            var sortable = Sortable.create(el, {
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function() {
                    actualizarOrdenFirmas();
                }
            });

            function actualizarOrdenFirmas() {
                var cards = el.querySelectorAll('.firma-card');
                cards.forEach(function(card, index) {
                    var prioridad = index + 1;
                    var badge = card.querySelector('.orden-badge');
                    var input = card.querySelector('input[type="hidden"]');

                    if (badge) badge.textContent = prioridad;
                    if (input) input.value = prioridad;

                    card.style.transform = 'scale(1)';
                });
            }

            actualizarOrdenFirmas();
        }

        document.addEventListener('DOMContentLoaded', initSortableFirmas);
    </script>

    <!-- Errores -->
    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar el elemento',
                html: `
                <div style="text-align:left">
                    <ul style="padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li style="margin-bottom:6px;">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            `,
                confirmButtonText: 'Revisar',
                confirmButtonColor: '#7c3aed',
                background: '#ffffff',
            });
        });
    </script>
    @endif

    <!-- Autocomplete Comités -->
    <script>
        function initAutocompleteComites() {
            document.addEventListener('focusin', function onFocusIn(e) {
                if (!e.target.matches('input[name^="nombres_relacion"]')) return;

                var input = e.target;
                if (input.dataset.autocompleteInitialized) return;
                input.dataset.autocompleteInitialized = '1';

                new autoComplete({
                    selector: function selector() {
                        return input;
                    },
                    placeHolder: 'Buscar comité',
                    debounce: 300,
                    data: {
                        src: async function src() {
                            var query = input.value.trim();
                            if (!query) return [];
                            try {
                                var res = await fetch('/elementos/buscar?q=' + encodeURIComponent(query));
                                if (!res.ok) return [];
                                var data = await res.json();
                                return data.map(function mapResult(r) {
                                    return {
                                        nombre: r.nombre,
                                        puestos: r.puestos,
                                    };
                                });
                            } catch (err) {
                                console.error('Error en autocomplete:', err);
                                return [];
                            }
                        },
                        keys: ['nombre'],
                    },
                    resultItem: {
                        highlight: false,
                        element: function element(item, data) {
                            item.className =
                                'flex justify-between items-center px-3 py-2 ' +
                                'text-sm text-gray-200 hover:bg-purple-600 cursor-pointer';
                            item.innerHTML =
                                '<span>' + data.match + '</span>' +
                                '<small class="text-gray-400 ml-2">(' + data.value.puestos.length + ' puestos)</small>';
                        },
                    },
                    events: {
                        input: {
                            selection: function selection(event) {
                                var feedback = event.detail;
                                input.value = feedback.selection.value.nombre;

                                var wrapper = input.closest('.fila-relacion');
                                var select = wrapper ? wrapper.querySelector('select[name^="puesto_id"]') : null;

                                if (select && feedback.selection.value.puestos.length) {
                                    var ids = feedback.selection.value.puestos.map(function mapPuesto(p) {
                                        return p.id;
                                    });
                                    $(select).val(ids).trigger('change');
                                }
                            },
                        },
                    },
                });
            });
        }

        document.addEventListener('DOMContentLoaded', initAutocompleteComites);
    </script>

    <!-- Select2 Init -->
    <script>
        function initSelect2Base() {
            $('.select2').each(function eachSelect2() {
                var placeholder = $(this).data('placeholder') || 'Seleccionar opción';
                $(this).select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                });
            });

            $('.select2-multiple').each(function eachSelect2Multiple() {
                var placeholder = $(this).data('placeholder') || 'Seleccionar opciones';
                $(this).select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                });
            });
        }

        $(document).ready(initSelect2Base);
    </script>

    <!-- Filtrado de puestos relacionados -->
    <script>
        function initFiltrosPuestos() {
            var filtroDivision = $('#filtro_division');
            var filtroUnidad = $('#filtro_unidad');
            var filtroArea = $('#filtro_area');
            var busquedaTexto = $('#busqueda_texto');
            var selectAllBtn = $('#select_all');
            var deselectAllBtn = $('#deselect_all');
            var limpiarFiltrosBtn = $('#limpiar_filtros');
            var puestosCheckboxes = $('.puesto-checkbox');
            var contadorSeleccionados = $('#contador_seleccionados');

            function actualizarContador() {
                var seleccionados = $('.puesto-checkbox:checked').length;
                contadorSeleccionados.text(seleccionados + ' puestos seleccionados');
            }

            function aplicarFiltros() {
                var divisionId = filtroDivision.val();
                var unidadId = filtroUnidad.val();
                var areaId = filtroArea.val();
                var texto = (busquedaTexto.val() || '').toLowerCase().trim();

                $('.puesto-checkbox').each(function eachCheckbox() {
                    var c = $(this);
                    var label = c.closest('label');
                    var mostrar = true;

                    if (divisionId && c.data('division') != divisionId) mostrar = false;
                    if (unidadId && c.data('unidad') != unidadId) mostrar = false;
                    if (areaId) {
                        var areasPuesto = c.data('areas') || [];
                        if (!areasPuesto.map(String).includes(String(areaId))) {
                            mostrar = false;
                        }
                    }
                    if (texto && String(c.data('nombre') || '').indexOf(texto) === -1) mostrar = false;

                    label.toggle(mostrar);
                });
            }

            function cargarUnidades(divisionId) {
                if (!divisionId) {
                    filtroUnidad.html('<option value="">Todas las unidades</option>').trigger('change');
                    return;
                }

                fetch('/puestos-trabajo/unidades-negocio/' + divisionId)
                    .then(function toJson(r) {
                        return r.json();
                    })
                    .then(function render(data) {
                        var html = '<option value="">Todas las unidades</option>';
                        data.forEach(function eachUnidad(u) {
                            html += '<option value="' + u.id_unidad_negocio + '">' + u.nombre + '</option>';
                        });
                        filtroUnidad.html(html).trigger('change');
                    });
            }

            function cargarAreas(unidadId) {
                if (!unidadId) {
                    filtroArea.html('<option value="">Todas las áreas</option>').trigger('change');
                    return;
                }

                fetch('/puestos-trabajo/areas/' + unidadId)
                    .then(function toJson(r) {
                        return r.json();
                    })
                    .then(function render(data) {
                        var html = '<option value="">Todas las áreas</option>';
                        data.forEach(function eachArea(a) {
                            html += '<option value="' + a.id_area + '">' + a.nombre + '</option>';
                        });
                        filtroArea.html(html).trigger('change');
                    });
            }

            function onDivisionChange() {
                var value = this.value;
                cargarUnidades(value);
                filtroUnidad.val('').trigger('change');
                filtroArea.val('').trigger('change');
                aplicarFiltros();
            }

            function onUnidadChange() {
                var value = this.value;
                cargarAreas(value);
                filtroArea.val('').trigger('change');
                aplicarFiltros();
            }

            function onSelectAllClick() {
                $('.puesto-checkbox:visible').prop('checked', true);
                actualizarContador();
            }

            function onDeselectAllClick() {
                puestosCheckboxes.prop('checked', false);
                actualizarContador();
            }

            function onLimpiarFiltrosClick() {
                filtroDivision.val('').trigger('change');
                filtroUnidad.val('').trigger('change');
                filtroArea.val('').trigger('change');
                busquedaTexto.val('');
                cargarUnidades('');
                cargarAreas('');
                aplicarFiltros();
            }

            filtroDivision.on('change', onDivisionChange);
            filtroUnidad.on('change', onUnidadChange);

            filtroArea.on('change', aplicarFiltros);
            busquedaTexto.on('input keyup', aplicarFiltros);

            selectAllBtn.on('click', onSelectAllClick);
            deselectAllBtn.on('click', onDeselectAllClick);
            limpiarFiltrosBtn.on('click', onLimpiarFiltrosClick);

            puestosCheckboxes.on('change', actualizarContador);

            actualizarContador();
            aplicarFiltros();
        }

        $(document).ready(initFiltrosPuestos);
    </script>

    <!-- Agregador de input comites -->
    <script>
        function initComitesFilas() {
            function onAgregarNombreClick() {
                var container = $('#campos_nombre_container');
                var index = container.find('.campo-relacion').length;
                var selectOpciones = container.find('select.select2').first().html();

                var nuevo =
                    '<div class="flex items-center gap-3 campo-relacion">' +
                    '<input ' +
                    'name="nombres_relacion[' + index + ']" ' +
                    'type="text" ' +
                    'placeholder="Buscar comité" ' +
                    'class="input-relacion border border-gray-300 rounded-md px-2 py-2 text-sm"' +
                    '>' +
                    '<select ' +
                    'name="puesto_id[' + index + '][]" ' +
                    'class="form-select select2" ' +
                    'multiple ' +
                    'required ' +
                    'data-placeholder="Selecciona puestos"' +
                    '>' +
                    selectOpciones +
                    '</select>' +
                    '<button ' +
                    'type="button" ' +
                    'class="btn-eliminar-nombre px-3 py-2 bg-red-600 text-white rounded-lg"' +
                    '>x</button>' +
                    '</div>';

                container.append(nuevo);

                container.find('select.select2').last().select2({
                    placeholder: 'Selecciona puestos',
                    allowClear: true,
                    width: '100%',
                });
            }

            function onEliminarNombreClick() {
                $(this).closest('.campo-relacion').remove();
            }

            $(document).on('click', '.btn-agregar-nombre', onAgregarNombreClick);
            $(document).on('click', '.btn-eliminar-nombre', onEliminarNombreClick);
        }

        $(document).ready(initComitesFilas);
    </script>

    <!-- Campos Obligatorios -->
    <script>
        function initCamposObligatorios() {
            var $tipo = $('#tipo_elemento_id');
            var camposObligatorios = [];

            function resetDinamicos() {
                document
                    .querySelectorAll(
                        '[data-campo]:not([data-ignore="true"]):not([data-static="true"]), ' +
                        '[data-relacion]:not([data-ignore="true"]):not([data-static="true"])'
                    )
                    .forEach(function(div) {
                        div.classList.add('hidden');
                        div.dataset.required = '0';
                        div.dataset.label = '';

                        div.querySelectorAll(
                            'input:not([data-static="true"]), ' +
                            'select:not([data-static="true"]), ' +
                            'textarea:not([data-static="true"])'
                        ).forEach(function(input) {
                            input.removeAttribute('required');
                            if (typeof input.setCustomValidity === 'function') input.setCustomValidity('');
                        });
                    });

                ['participantes', 'responsables', 'reviso', 'autorizo'].forEach(function(nombreCampo) {
                    var el = document.querySelector('select[name="' + nombreCampo + '[]"]');
                    if (!el) return;
                    if (typeof el.setCustomValidity === 'function') el.setCustomValidity('');
                    $(el).off('change');
                    delete el.dataset.firmaRequired;
                });
            }

            function setVisibleAndMeta(wrapper, visible, isRequired, label) {
                if (!wrapper) return;
                if (visible) wrapper.classList.remove('hidden');
                else wrapper.classList.add('hidden');

                wrapper.dataset.required = isRequired ? '1' : '0';
                if (label) wrapper.dataset.label = label;
            }

            async function cargarCampos(tipoId) {
                try {
                    var res = await fetch('/tipos-elemento/' + tipoId + '/campos-obligatorios');
                    camposObligatorios = await res.json();

                    resetDinamicos();

                    var inputIdRegistro = document.querySelector('input[name="id"]');
                    var esModoCreacion = !inputIdRegistro || inputIdRegistro.value === '';

                    var opcionesOriginales = $('#elemento_padre_id').data('original-options');
                    var hayElementosPrevios = opcionesOriginales ?
                        opcionesOriginales.filter('option[value!=""]').length > 0 :
                        document.querySelectorAll('#elemento_padre_id option[value]:not([value=""])').length > 0;

                    var camposExcluidosAlCrear = ['elemento_padre_id', 'elemento_relacionado_id'];

                    camposObligatorios.forEach(function(campo) {
                        var baseName = String(campo.campo_nombre || '').replace(/\[\]$/, '');
                        if (!baseName) return;

                        var label = campo.campo_label || baseName;
                        var esObligatorio = campo.obligatorio !== false; // si tu API no manda "obligatorio", esto queda true

                        if (esModoCreacion && !hayElementosPrevios && camposExcluidosAlCrear.includes(baseName)) {
                            esObligatorio = false;
                        }

                        if (baseName === 'esfirma') {
                            var bloqueFirmas = document.querySelector('[data-relacion="esfirma"]');
                            setVisibleAndMeta(bloqueFirmas, !!esObligatorio, !!esObligatorio, label);
                            return;
                        }

                        var wrapperRelacion = document.querySelector('[data-relacion="' + baseName + '"]');
                        setVisibleAndMeta(wrapperRelacion, !!esObligatorio, !!esObligatorio, label);

                        var selector = '[name="' + baseName + '"], [name="' + baseName + '[]"]';
                        var els = document.querySelectorAll(selector);
                        if (!els.length) return;

                        els.forEach(function(el) {
                            if (!el || el.dataset.static === 'true') return;

                            var wrapper = el.closest('[data-campo]');
                            setVisibleAndMeta(wrapper, !!esObligatorio, !!esObligatorio, label);

                            if (!esObligatorio) {
                                el.removeAttribute('required');
                                if (typeof el.setCustomValidity === 'function') el.setCustomValidity('');
                            }
                        });
                    });
                } catch (e) {
                    console.error('Error cargando campos obligatorios:', e);
                }
            }

            function onTipoChange() {
                var tipoId = this.value;
                var hiddenField = document.getElementById('tipo_elemento_id_hidden');
                if (hiddenField) hiddenField.value = tipoId;

                if (tipoId) cargarCampos(tipoId);
                else resetDinamicos();
            }

            $tipo.on('change', onTipoChange);

            if ($tipo.val()) $tipo.trigger('change');
            else resetDinamicos();
        }

        $(document).ready(initCamposObligatorios);
    </script>

    <!-- Semaforo -->
    <script>
        function initSemaforo() {
            var periodoRevisionInput = document.getElementById('periodo_revision');
            var semaforoContainer = document.getElementById('semaforo-container');
            var semaforoDinamico = document.getElementById('semaforo-dinamico');

            function actualizarSemaforo() {
                var fecha = periodoRevisionInput.value;
                if (!fecha) {
                    semaforoContainer.classList.add('hidden');
                    return;
                }

                var hoy = new Date();
                var fechaRevision = new Date(fecha);

                var diffMeses =
                    (fechaRevision.getFullYear() - hoy.getFullYear()) * 12 +
                    (fechaRevision.getMonth() - hoy.getMonth());

                var clase, texto, info, icono;

                if (diffMeses <= 2) {
                    clase = 'bg-red-500 text-white';
                    texto = 'Crítico';
                    info = '⚠️ Revisión crítica';
                    icono = 'text-red-600 dark:text-red-400';
                } else if (diffMeses <= 6) {
                    clase = 'bg-yellow-500 text-black';
                    texto = 'Advertencia';
                    info = '⚠️ Revisión próxima';
                    icono = 'text-yellow-600 dark:text-yellow-400';
                } else if (diffMeses <= 12) {
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

                semaforoDinamico.innerHTML =
                    '<div class="inline-flex items-center space-x-2">' +
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + clase + '">' +
                    texto +
                    '</span>' +
                    '<span class="' + icono + ' text-xs">' +
                    info +
                    '</span>' +
                    '</div>';

                semaforoContainer.classList.remove('hidden');
            }

            if (periodoRevisionInput) {
                periodoRevisionInput.addEventListener('change', actualizarSemaforo);
                periodoRevisionInput.addEventListener('input', actualizarSemaforo);

                if (periodoRevisionInput.value) {
                    actualizarSemaforo();
                }
            }
        }

        document.addEventListener('DOMContentLoaded', initSemaforo);
    </script>

    <!-- Filtro de elementos por tipo -->
    <script>
        function initFiltroElementosPorTipo() {

            function filtrarSelectPorTipo({
                filtroTipoId,
                selectId,
                contadorId,
                isMultiple = false
            }) {
                var $filtro = $(filtroTipoId);
                var $select = $(selectId);
                var $contador = $(contadorId);

                if (!$select.data('original-options')) {
                    $select.data(
                        'original-options',
                        $select.find('option').clone(true)
                    );
                }

                function actualizarContador() {
                    var total = $select.find('option[value!=""]').length;
                    $contador.text(total + ' elementos');
                }

                $filtro.on('change', function() {
                    var tipoSeleccionado = $(this).val();
                    var $originales = $select.data('original-options');

                    $select.empty();

                    if (!isMultiple) {
                        $select.append('<option value="">Seleccionar opción</option>');
                    }

                    $originales.each(function() {
                        var $opt = $(this);
                        var tipo = String($opt.data('tipo') || '');

                        if (!tipoSeleccionado || tipo === String(tipoSeleccionado)) {
                            $select.append($opt.clone(true));
                        }
                    });

                    $select.val(null).trigger('change');
                    actualizarContador();
                });

                actualizarContador();
            }

            filtrarSelectPorTipo({
                filtroTipoId: '#filtro_tipo_elemento',
                selectId: '#elemento_padre_id',
                contadorId: '#contador-elementos',
                isMultiple: false
            });

            filtrarSelectPorTipo({
                filtroTipoId: '#filtro_tipo_elemento_relacionados',
                selectId: '#elemento_relacionado_id',
                contadorId: '#contador-elementos-relacionados',
                isMultiple: true
            });
        }

        $(document).ready(initFiltroElementosPorTipo);
    </script>

    <!-- Cambios de color archivo seleccionado -->
    <script>
        function initArchivoSeleccionadoToggle() {
            document.addEventListener('change', function onChange(e) {
                if (!e.target.matches('input[type="file"]')) return;

                var input = e.target;
                var container = input.closest('.border-dashed');
                if (!container) return;

                if (input.files && input.files.length > 0) {
                    container.classList.add('archivo-seleccionado');
                } else {
                    container.classList.remove('archivo-seleccionado');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initArchivoSeleccionadoToggle);
    </script>

    <!-- Wizard Steps Navigation -->
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function mostrarCargandoGuardar() {
            Swal.fire({
                title: 'Guardando elemento…',
                html: 'Esto puede tardar unos minutos.<br>Espera, por favor.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        function initWizard() {
            showStep(1);

            document.querySelectorAll('.wizard-step').forEach(step => {
                step.addEventListener('click', function() {
                    const stepNum = parseInt(this.dataset.step, 10);
                    if (Number.isNaN(stepNum)) return;

                    if (stepNum <= currentStep) {
                        showStep(stepNum);
                        return;
                    }

                    if (stepNum === currentStep + 1) {
                        nextStep();
                    }
                });
            });

            const tipoElementoSelect = document.getElementById('tipo_elemento_id');
            const tipoElementoHidden = document.getElementById('tipo_elemento_id_hidden');
            if (tipoElementoSelect && tipoElementoHidden) {
                tipoElementoSelect.addEventListener('change', function() {
                    tipoElementoHidden.value = this.value;
                });
            }

            const form = document.getElementById('form-save');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const ok = await validarAntesDeEnviar();
                    if (!ok) {
                        return;
                    }

                    Swal.close();
                    mostrarCargandoGuardar();
                    this.submit();
                });
            }
        }

        async function nextStep() {
            if (currentStep >= totalSteps) return;
            const puedeAvanzar = await puedeAvanzarDesde(currentStep);
            if (!puedeAvanzar) return;
            showStep(currentStep + 1);
        }

        function prevStep() {
            if (currentStep > 1) showStep(currentStep - 1);
        }

        async function puedeAvanzarDesde(step) {
            if (step === 1) return validarPaso1();
            if (step === 2) return await validarPaso2Detalles();
            return true;
        }

        function validarPaso1() {
            const tipoElemento = document.getElementById('tipo_elemento_id');
            if (!tipoElemento || !tipoElemento.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor selecciona un tipo de elemento',
                    confirmButtonColor: '#4f46e5'
                });
                return false;
            }
            return true;
        }

        async function validarPaso2Detalles() {
            const faltantes = obtenerFaltantesFormulario('.wizard-content.step-2');

            if (faltantes.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos requeridos',
                    html: 'Completa los siguientes campos:<br><b>' + faltantes.join('<br>') + '</b>',
                    confirmButtonColor: '#4f46e5'
                });
                return false;
            }

            // Validar duplicados después de validar campos requeridos
            const resultadoDuplicado = await validarDuplicadoElemento();
            if (!resultadoDuplicado) return false;

            return true;
        }

        function validarPaso3Firmas() {
            const bloqueFirmas = document.querySelector('.wizard-content.step-3 [data-relacion="esfirma"]');
            const firmasVisibles = bloqueFirmas && !bloqueFirmas.classList.contains('hidden');

            if (!firmasVisibles) return true;

            const selects = [
                'participantes[]',
                'responsables[]',
                'reviso[]',
                'autorizo[]'
            ];

            let totalSeleccionados = 0;

            selects.forEach(name => {
                const select = document.querySelector(`select[name="${name}"]`);
                if (!select) return;

                const valores = $(select).val();
                if (Array.isArray(valores)) {
                    totalSeleccionados += valores.length;
                } else if (valores) {
                    totalSeleccionados += 1;
                }
            });

            if (totalSeleccionados === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Firmas requeridas',
                    text: 'Debes agregar al menos un firmante.',
                    confirmButtonColor: '#4f46e5'
                });
                return false;
            }

            return true;
        }

        async function validarAntesDeEnviar() {
            if (!validarPaso1()) return false;
            if (!validarPaso3Firmas()) return false;
            return true;
        }

        async function validarDuplicadoElemento() {
            const nombre = document.getElementById('nombre_elemento')?.value?.trim();
            const folio = document.getElementById('folio_elemento')?.value?.trim();
            const version = document.getElementById('version_elemento')?.value?.trim();

            if (!nombre || !folio || !version) {
                return true; // Si no están completos, se validará en backend
            }

            try {
                const response = await fetch('{{ route("elementos.validar-duplicado") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        nombre_elemento: nombre,
                        folio_elemento: folio,
                        version_elemento: version
                    })
                });

                const data = await response.json();

                // Si existe y la versión NO es válida (igual o menor)
                if (data.existe && !data.es_version_valida) {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Versión duplicada',
                        html: data.message,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#ef4444'
                    });
                    return false;
                }

                // Si la versión es válida, continuar sin mostrar alerta
                return true;
            } catch (error) {
                console.error('Error al validar duplicado:', error);
                // Si hay error en la validación, permitir continuar (se validará en backend)
                return true;
            }
        }

        function obtenerFaltantesFormulario(selector) {
            const cont = document.querySelector(selector);
            if (!cont) return [];

            const wrappers = cont.querySelectorAll(
                '[data-campo]:not([data-ignore="true"]):not([data-static="true"]), ' +
                '[data-relacion]:not([data-ignore="true"]):not([data-static="true"])'
            );

            const faltantes = [];

            wrappers.forEach(w => {
                if (w.classList.contains('hidden')) return;
                if (w.dataset.required !== '1') return;

                const ok = wrapperCumple(w);
                if (!ok) {
                    const label = (w.dataset.label || 'Campo requerido').trim();
                    faltantes.push(label);
                }
            });

            return Array.from(new Set(faltantes));
        }

        function wrapperCumple(wrapper) {
            const controls = Array.from(wrapper.querySelectorAll('input, select, textarea'))
                .filter(el => {
                    if (!el || el.disabled) return false;
                    if (el.dataset && el.dataset.static === 'true') return false;

                    const name = el.getAttribute('name');
                    if (!name) return false;

                    const type = (el.getAttribute('type') || '').toLowerCase();
                    if (type === 'hidden' || type === 'button' || type === 'submit' || type === 'reset') return false;

                    return true;
                });

            if (controls.length === 0) return false;

            const byName = new Map();
            controls.forEach(el => {
                const name = el.getAttribute('name');
                if (!byName.has(name)) byName.set(name, []);
                byName.get(name).push(el);
            });

            for (const [name, els] of byName.entries()) {
                const first = els[0];
                const tag = first.tagName.toLowerCase();
                const type = (first.getAttribute('type') || '').toLowerCase();

                if (type === 'checkbox' || type === 'radio') {
                    const anyChecked = els.some(i => i.checked);
                    if (!anyChecked) return false;
                    continue;
                }

                if (tag === 'select') {
                    const val = $(first).val();
                    if (Array.isArray(val)) {
                        const clean = val.filter(v => String(v).trim() !== '');
                        if (clean.length === 0) return false;
                    } else {
                        if (!val || String(val).trim() === '') return false;
                    }
                    continue;
                }

                if (type === 'file') {
                    const files = first.files;
                    if (!files || files.length === 0) return false;
                    continue;
                }

                const value = (first.value || '').trim();
                if (!value) return false;
            }

            return true;
        }

        function showStep(stepNum) {
            if (stepNum < 1 || stepNum > totalSteps) return;

            currentStep = stepNum;

            document.querySelectorAll('.wizard-content').forEach(content => {
                content.classList.add('hidden', 'opacity-0', 'translate-y-4');
            });

            const currentContent = document.querySelector(`.wizard-content.step-${stepNum}`);
            if (currentContent) {
                currentContent.classList.remove('hidden');
                setTimeout(() => {
                    currentContent.classList.remove('opacity-0', 'translate-y-4');
                    refreshSelect2In(currentContent);
                }, 50);
            }

            document.querySelectorAll('.wizard-step').forEach((step, index) => {
                const stepNumber = index + 1;
                const circle = step.querySelector('.step-circle');
                const title = step.querySelectorAll('p')[0];
                const subtitle = step.querySelectorAll('p')[1];
                const numberSpan = step.querySelector('.step-number');
                const checkIcon = step.querySelector('.step-check');

                if (stepNumber < currentStep) {
                    circle.className = 'step-circle relative flex items-center justify-center w-12 h-12 rounded-full font-bold text-base transition-all duration-300 bg-green-500 text-white shadow-lg';
                    title.className = 'text-sm font-semibold text-gray-900 dark:text-gray-100 transition-colors';
                    subtitle.className = 'text-xs text-gray-500 dark:text-gray-400 mt-0.5';
                    numberSpan.classList.add('hidden');
                    checkIcon.classList.remove('hidden');
                } else if (stepNumber === currentStep) {
                    circle.className = 'step-circle relative flex items-center justify-center w-12 h-12 rounded-full font-bold text-base transition-all duration-300 bg-indigo-600 text-white shadow-lg ring-4 ring-indigo-100 dark:ring-indigo-900/30';
                    title.className = 'text-sm font-semibold text-gray-900 dark:text-gray-100 transition-colors';
                    subtitle.className = 'text-xs text-gray-500 dark:text-gray-400 mt-0.5';
                    numberSpan.classList.remove('hidden');
                    checkIcon.classList.add('hidden');
                } else {
                    circle.className = 'step-circle relative flex items-center justify-center w-12 h-12 rounded-full font-bold text-base transition-all duration-300 bg-gray-300 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                    title.className = 'text-sm font-semibold text-gray-500 dark:text-gray-400 transition-colors';
                    subtitle.className = 'text-xs text-gray-400 dark:text-gray-500 mt-0.5';
                    numberSpan.classList.remove('hidden');
                    checkIcon.classList.add('hidden');
                }
            });

            const lines = document.querySelectorAll('.step-line');
            lines.forEach((line, index) => {
                if (index + 1 < currentStep) {
                    line.className = 'step-line flex-1 h-1 mx-4 bg-green-500 rounded-full transition-all duration-300';
                } else {
                    line.className = 'step-line flex-1 h-1 mx-4 bg-gray-200 dark:bg-gray-700 rounded-full transition-all duration-300';
                }
            });

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        document.addEventListener('DOMContentLoaded', initWizard);
    </script>
    <!-- Select2 Re Inicializa -->
    <script>
        function refreshSelect2In(container) {
            var $ctx = $(container || document);

            $ctx.find('select.select2, select.select2-multiple').each(function() {
                var $el = $(this);

                // Si sigue oculto, no sirve de nada
                if ($el.closest('.hidden').length) return;

                var placeholder = $el.data('placeholder') ||
                    ($el.prop('multiple') ? 'Seleccionar opciones' : 'Seleccionar opción');

                // Si ya estaba inicializado, destrúyelo para recalcular medidas
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }

                $el.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%'
                });

                // Forzar ancho real
                $el.next('.select2-container').css('width', '100%');

                // Forzar render del valor actual
                $el.trigger('change.select2');
            });
        }
    </script>

    <!-- Archivo Formato -->
    <script>
        $(function() {
            const $select = $('#es_formato');
            const $div = $('#archivo_formato_div');
            const $file = $('#archivo_formato');
            const $msg = $('#mensaje');

            if (!$select.length || !$div.length || !$file.length) return;

            function syncArchivoFormato() {
                const val = String($select.val() || 'no').toLowerCase();

                if (val === 'si') {
                    $div.removeClass('hidden').show();
                    $file.prop('required', true).prop('disabled', false);
                } else {
                    $div.addClass('hidden').hide();
                    $file.prop('required', false).prop('disabled', true).val('');

                    const container = $file.closest('.border-dashed');
                    if (container.length) container.removeClass('archivo-seleccionado');

                    if ($msg.length) $msg.text('PDF, DOCX, XLSX');
                }
            }

            $select.on('change', syncArchivoFormato);

            syncArchivoFormato();
        });
    </script>
</x-app-layout>
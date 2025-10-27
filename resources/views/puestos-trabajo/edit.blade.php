<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $puestoTrabajo->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('puestos-trabajo.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
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
            <div class="p-6">
                <form action="{{ route('puestos-trabajo.update', $puestoTrabajo->id_puesto_trabajo) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="nombre">Nombre del Puesto</label>
                            <input id="nombre" class="form-input w-full" type="text" name="nombre" value="{{ old('nombre', $puestoTrabajo->nombre) }}" required />
                            @error('nombre')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- División -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="division_id">División</label>
                            <select id="division_id" class="select2 form-select w-full" name="division_id" data-placeholder="Seleccionar División" required>
                                <option value="">Seleccionar División</option>
                                @foreach($divisions as $division)
                                <option value="{{ $division->id_division }}" {{ old('division_id', $puestoTrabajo->division_id) == $division->id_division ? 'selected' : '' }}>
                                    {{ $division->nombre }}
                                </option>
                                @endforeach
                            </select>
                            @error('division_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Unidad de Negocio -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="unidad_negocio_id">Unidad de Negocio</label>
                            <select id="unidad_negocio_id" class="select2 form-select w-full" name="unidad_negocio_id" data-placeholder="Seleccionar Unidad de Negocio" required>
                                <option value="">Seleccionar Unidad de Negocio</option>
                            </select>
                            @error('unidad_negocio_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Área -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="area_id">Área</label>
                            <select id="area_id" class="select2 form-select w-full" name="area_id" data-placeholder="Seleccionar Área" required>
                                <option value="">Seleccionar Área</option>
                            </select>
                            @error('area_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Jefe Directo -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Jefe directo</label>
                            <select id="empleado_id" name="empleado_id" class="select2 form-select w-full" data-placeholder="Selecciona el Jefe Directo" required>
                                <option value="" disabled>Selecciona el Jefe Directo</option>

                                @foreach($empleados as $empleado)
                                <option value="{{ $empleado->id_empleado }}"
                                    @selected($puestoTrabajo->empleado_id == $empleado->id_empleado)>
                                    {{ $empleado->nombres }} {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('puestos-trabajo.index') }}"
                                class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-purple-600 hover:bg-purple-700 text-white">
                                Actualizar Puesto de Trabajo
                            </button>
                        </div>

                    </div>

                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const divisionSelect = document.getElementById('division_id');
            const unidadNegocioSelect = document.getElementById('unidad_negocio_id');
            const areaSelect = document.getElementById('area_id');

            // Función para limpiar y deshabilitar un select
            function resetSelect(select, placeholder) {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.disabled = true;
                select.value = '';
            }

            // Función para cargar unidades de negocio
            function loadUnidadesNegocio(divisionId) {
                if (!divisionId) {
                    resetSelect(unidadNegocioSelect, 'Primero selecciona una División');
                    resetSelect(areaSelect, 'Primero selecciona una Unidad de Negocio');
                    return;
                }

                fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                    .then(response => response.json())
                    .then(data => {
                        unidadNegocioSelect.innerHTML = '<option value="">Seleccionar Unidad de Negocio</option>';
                        data.forEach(unidad => {
                            const option = document.createElement('option');
                            option.value = unidad.id_unidad_negocio;
                            option.textContent = unidad.nombre;

                            // Marcar como seleccionado si coincide con el valor actual
                            if (unidad.id_unidad_negocio == '{{ $puestoTrabajo->unidad_negocio_id }}') {
                                option.selected = true;
                            }

                            unidadNegocioSelect.appendChild(option);
                        });
                        unidadNegocioSelect.disabled = false;

                        // Cargar áreas si hay unidad de negocio seleccionada
                        if ('{{ $puestoTrabajo->unidad_negocio_id }}') {
                            loadAreas('{{ $puestoTrabajo->unidad_negocio_id }}');
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar unidades de negocio:', error);
                        resetSelect(unidadNegocioSelect, 'Error al cargar unidades de negocio');
                    });
            }

            // Función para cargar áreas
            function loadAreas(unidadNegocioId) {
                if (!unidadNegocioId) {
                    resetSelect(areaSelect, 'Primero selecciona una Unidad de Negocio');
                    return;
                }

                fetch(`/puestos-trabajo/areas/${unidadNegocioId}`)
                    .then(response => response.json())
                    .then(data => {
                        areaSelect.innerHTML = '<option value="">Seleccionar Área</option>';
                        data.forEach(area => {
                            const option = document.createElement('option');
                            option.value = area.id_area;
                            option.textContent = area.nombre;

                            // Marcar como seleccionado si coincide con el valor actual
                            if (area.id_area == '{{ $puestoTrabajo->area_id }}') {
                                option.selected = true;
                            }

                            areaSelect.appendChild(option);
                        });
                        areaSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error al cargar áreas:', error);
                        resetSelect(areaSelect, 'Error al cargar áreas');
                    });
            }

            // Event listeners
            divisionSelect.addEventListener('change', function() {
                loadUnidadesNegocio(this.value);
            });

            unidadNegocioSelect.addEventListener('change', function() {
                loadAreas(this.value);
            });

            // Cargar datos iniciales
            loadUnidadesNegocio('{{ $puestoTrabajo->division_id }}');
        });
    </script>
</x-app-layout>
<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Nuevo Puesto de Trabajo</h1>
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
        <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
            <div class="p-6">
                <form action="{{ route('puestos-trabajo.store') }}" method="POST">
                    @csrf

                    <div class="space-y-6">

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="nombre">Nombre del Puesto</label>
                            <input id="nombre" class="form-input w-full" type="text" name="nombre" value="{{ old('nombre') }}" required />
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
                                <option value="{{ $division->id_division }}" {{ old('division_id') == $division->id_division ? 'selected' : '' }}>
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
                            <select id="unidad_negocio_id" class="select2 form-select w-full" name="unidad_negocio_id" data-placeholder="Primero selecciona una División" required disabled>
                                <option value="">Primero selecciona una División</option>
                            </select>
                            @error('unidad_negocio_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Área -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="area_id">Área</label>
                            <select id="area_id" class="select2 form-select w-full" name="area_id" data-placeholder="Primero selecciona una Unidad de Negocio" required disabled>
                                <option value="">Primero selecciona una Unidad de Negocio</option>
                            </select>
                            @error('area_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Jefe Directo -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="puesto_id">Jefe Directo</label>
                            <select id="puesto_id" class="select2 form-select w-full" name="puesto_trabajo_id" data-placeholder="Primero selecciona una área" required disabled>
                                <option value="">Primero selecciona una área</option>
                            </select>
                            @error('puesto_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('puestos-trabajo.index') }}"
                            class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                            Cancelar
                        </a>
                        <button type="submit" class="btn bg-purple-600 hover:bg-purple-700 text-white">
                            Crear Puesto de Trabajo
                        </button>
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
            const puestoSelect = document.getElementById('puesto_id');
            console.log(puestoSelect);

            function resetSelect(select, placeholder) {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.disabled = true;
                if ($(select).data('select2')) {
                    $(select).val('').trigger('change.select2');
                }
            }

            function loadUnidadesNegocio(divisionId) {
                resetSelect(unidadNegocioSelect, 'Cargando unidades...');
                resetSelect(areaSelect, 'Primero selecciona una Unidad de Negocio');
                resetSelect(puestoSelect, 'Primero selecciona un Área');

                if (!divisionId) return;

                fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                    .then(res => res.json())
                    .then(data => {
                        unidadNegocioSelect.innerHTML = '<option value="">Seleccionar Unidad de Negocio</option>';
                        data.forEach(u => {
                            const opt = document.createElement('option');
                            opt.value = u.id_unidad_negocio;
                            opt.textContent = u.nombre;
                            unidadNegocioSelect.appendChild(opt);
                        });

                        unidadNegocioSelect.disabled = false;
                        $(unidadNegocioSelect).prop('disabled', false).trigger('change.select2');
                    })
                    .catch(err => {
                        console.error('Error al cargar unidades:', err);
                        resetSelect(unidadNegocioSelect, 'Error al cargar unidades');
                    });
            }

            function loadAreas(unidadNegocioId) {
                resetSelect(areaSelect, 'Cargando áreas...');
                resetSelect(puestoSelect, 'Primero selecciona un Área');

                if (!unidadNegocioId) return;

                fetch(`/puestos-trabajo/areas/${unidadNegocioId}`)
                    .then(res => res.json())
                    .then(data => {
                        areaSelect.innerHTML = '<option value="">Seleccionar Área</option>';
                        data.forEach(a => {
                            const opt = document.createElement('option');
                            opt.value = a.id_area;
                            opt.textContent = a.nombre;
                            areaSelect.appendChild(opt);
                        });

                        areaSelect.disabled = false;
                        $(areaSelect).prop('disabled', false).trigger('change.select2');
                    })
                    .catch(err => {
                        console.error('Error al cargar áreas:', err);
                        resetSelect(areaSelect, 'Error al cargar áreas');
                    });
            }

            function loadPuestos(areaId) {
                resetSelect(puestoSelect, 'Cargando puestos...');

                if (!areaId) return;

                fetch(`/puestos-trabajo/por-area/${areaId}`)
                    .then(res => res.json())
                    .then(result => {
                        puestoSelect.innerHTML = '<option value="">Seleccionar Puesto</option>';

                        if (Array.isArray(result) && result.length > 0) {
                            result.forEach(p => {
                                const opt = document.createElement('option');
                                opt.value = p.id_puesto_trabajo;
                                opt.textContent = p.nombre;
                                puestoSelect.appendChild(opt);
                            });

                            puestoSelect.disabled = false;
                            $(puestoSelect).prop('disabled', false).trigger('change.select2');
                        } else {
                            resetSelect(puestoSelect, 'Sin puestos disponibles');
                        }
                    })
                    .catch(err => {
                        console.error('Error al cargar puestos:', err);
                        resetSelect(puestoSelect, 'Error al cargar puestos');
                    });
            }

            $('#division_id').on('change', function() {
                const divisionId = $(this).val();
                loadUnidadesNegocio(divisionId);
            });

            $('#unidad_negocio_id').on('change', function() {
                const unidadId = $(this).val();
                loadAreas(unidadId);
            });

            $('#area_id').on('change', function() {
                const areaId = $(this).val();
                loadPuestos(areaId);
            });
        });
    </script>
</x-app-layout>
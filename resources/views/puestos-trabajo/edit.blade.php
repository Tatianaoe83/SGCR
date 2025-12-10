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
                            <input id="nombre" class="form-input w-full" type="text" name="nombre"
                                value="{{ old('nombre', $puestoTrabajo->nombre) }}" required />
                            @error('nombre')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- División -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="division_id">División</label>

                            @if($puestoTrabajo->is_global)
                            <select class="form-select w-full" disabled>
                                <option value="all" selected>Todas</option>
                            </select>
                            <input type="hidden" name="division_id" value="all">
                            @else
                            <select id="division_id" class="select2 form-select w-full" name="division_id" required>
                                <option value="">Seleccionar División</option>
                                @foreach($divisions as $division)
                                <option value="{{ $division->id_division }}"
                                    @selected(old('division_id', $puestoTrabajo->division_id) == $division->id_division)>
                                    {{ $division->nombre }}
                                </option>
                                @endforeach
                            </select>
                            @endif

                            @error('division_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Unidad de Negocio -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="unidad_negocio_id">Unidad de Negocio</label>

                            @if($puestoTrabajo->is_global)
                            <select class="form-select w-full" disabled>
                                <option value="all" selected>Todas</option>
                            </select>
                            <input type="hidden" name="unidad_negocio_id" value="all">
                            @else
                            <select id="unidad_negocio_id" class="select2 form-select w-full" name="unidad_negocio_id" required>
                                <option value="">Seleccionar Unidad de Negocio</option>
                            </select>
                            @endif

                            @error('unidad_negocio_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Área -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="area_id">Área</label>

                            @if($puestoTrabajo->is_global)
                            <select class="select2 form-select w-full" multiple disabled>
                                <option value="all" selected>Todas</option>
                            </select>

                            <input type="hidden" name="areas_ids[]" value="all">
                            @else
                            <select id="area_id" class="select2 form-select w-full" name="areas_ids[]" multiple required>
                                <option value="" disabled>Seleccionar Área</option>
                            </select>
                            @endif

                            @error('area_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Jefe Directo -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Jefe directo</label>
                            <select id="puesto_trabajo_id" name="puesto_trabajo_id" class="select2 form-select w-full">
                                <option value="" {{ $puestoTrabajo->puesto_trabajo_id ? '' : 'selected' }}>
                                    Selecciona el Jefe Directo
                                </option>

                                @foreach($puestos as $puesto)
                                <option value="{{ $puesto->id_puesto_trabajo }}"
                                    @selected($puestoTrabajo->puesto_trabajo_id == $puesto->id_puesto_trabajo)>
                                    {{ $puesto->nombre }}
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

    @if(!$puestoTrabajo->is_global)
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const divisionSelect = document.getElementById("division_id");
            const unidadSelect = document.getElementById("unidad_negocio_id");
            const areaSelect = document.getElementById("area_id");

            const defaultDivision = "{{ $puestoTrabajo->division_id }}";
            const defaultUnidad = "{{ $puestoTrabajo->unidad_negocio_id }}";
            let defaultAreas = @json($puestoTrabajo->areas_ids ?? []);

            function resetSelect(select, placeholder) {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                select.disabled = true;
                if ($(select).data("select2")) $(select).val("").trigger("change.select2");
            }

            function loadUnidades(divisionId) {
                resetSelect(unidadSelect, "Cargando unidades...");
                resetSelect(areaSelect, "Primero selecciona una Unidad de Negocio");

                if (!divisionId) return;

                fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                    .then(res => res.json())
                    .then(data => {
                        unidadSelect.innerHTML = '<option value="">Seleccionar Unidad de Negocio</option>';

                        data.forEach(u => {
                            const opt = document.createElement("option");
                            opt.value = u.id_unidad_negocio;
                            opt.textContent = u.nombre;

                            if (u.id_unidad_negocio == defaultUnidad) opt.selected = true;

                            unidadSelect.appendChild(opt);
                        });

                        unidadSelect.disabled = false;
                        $(unidadSelect).trigger("change.select2");

                        if (defaultUnidad && data.some(u => u.id_unidad_negocio == defaultUnidad)) {
                            loadAreas(defaultUnidad);
                        }
                    })
                    .catch(() => resetSelect(unidadSelect, "Error al cargar unidades"));
            }

            function loadAreas(unidadId) {
                resetSelect(areaSelect, "Cargando áreas...");

                if (!unidadId) return;

                fetch(`/puestos-trabajo/areas/${unidadId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            resetSelect(areaSelect, "Sin áreas disponibles");
                            return;
                        }

                        areaSelect.innerHTML = "<option value=''>Seleccionar Área</option>";

                        data.forEach(a => {
                            const opt = new Option(a.nombre, a.id_area);
                            if (defaultAreas.includes(a.id_area)) opt.selected = true;
                            areaSelect.appendChild(opt);
                        });

                        areaSelect.disabled = false;
                        $(areaSelect).trigger("change.select2");

                        const validIds = data.map(a => a.id_area);
                        defaultAreas = defaultAreas.filter(id => validIds.includes(id));
                    })
                    .catch(() => resetSelect(areaSelect, "Error al cargar áreas"));
            }

            $(divisionSelect).on("change", function() {
                defaultAreas = [];
                loadUnidades(this.value);
            });

            $(unidadSelect).on("change", function() {
                defaultAreas = [];
                loadAreas(this.value);
            });

            loadUnidades(defaultDivision);
        });
    </script>
    @endif
</x-app-layout>
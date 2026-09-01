<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $puestoTrabajo->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('puestos-trabajo.index') }}" class="btn-secondary">
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
                            <p class="text-xs text-slate-500 mt-1">
                                Si el puesto empieza con Director, Subdirector o Gerente podrá abarcar varias unidades de negocio.
                            </p>
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
                            @else
                            @php
                            $permiteMultiples = $puestoTrabajo->permiteMultiplesUnidades();
                            $unidadesMarcadas = old('unidades_negocio_ids', $unidadesSeleccionadas);
                            @endphp

                            <select id="unidad_negocio_id" class="select2 form-select w-full"
                                name="unidades_negocio_ids[]" @if($permiteMultiples) multiple @endif required>
                                @unless($permiteMultiples)
                                <option value="">Seleccionar Unidad de Negocio</option>
                                @endunless
                                @foreach($unidadesNegocio as $unidad)
                                <option value="{{ $unidad->id_unidad_negocio }}"
                                    @selected(in_array($unidad->id_unidad_negocio, $unidadesMarcadas))>
                                    {{ $unidad->nombre }}
                                </option>
                                @endforeach
                            </select>
                            <p id="unidad_negocio_hint" class="text-xs text-slate-500 mt-1 {{ $permiteMultiples ? '' : 'hidden' }}">
                                Puedes seleccionar varias unidades de negocio.
                            </p>
                            @endif

                            @error('unidades_negocio_ids')
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
                            @else
                            @php
                            $areasMarcadas = old('areas_ids', $puestoTrabajo->areas_ids ?? []);
                            @endphp

                            <select id="area_id" class="select2 form-select w-full" name="areas_ids[]" multiple required>
                                @if(count($unidadesSeleccionadas) > 1)
                                {{-- Con varias unidades se agrupa para saber de dónde viene cada área. --}}
                                @foreach($areas->groupBy(fn($area) => $area->unidadNegocio->nombre ?? 'Sin unidad') as $nombreUnidad => $grupo)
                                <optgroup label="{{ $nombreUnidad }}">
                                    @foreach($grupo as $area)
                                    <option value="{{ $area->id_area }}" @selected(in_array($area->id_area, $areasMarcadas))>
                                        {{ $area->nombre }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                                @else
                                @foreach($areas as $area)
                                <option value="{{ $area->id_area }}" @selected(in_array($area->id_area, $areasMarcadas))>
                                    {{ $area->nombre }}
                                </option>
                                @endforeach
                                @endif
                            </select>
                            @endif

                            @error('areas_ids')
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

                            @error('puesto_trabajo_id')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('puestos-trabajo.index') }}"
                                class="btn-secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn-primary">
                                Actualizar Puesto de Trabajo
                            </button>
                        </div>

                    </div>

                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const NIVELES_MULTIUNIDAD = @json($nivelesMultiunidad);

            const nombreInput = document.getElementById("nombre");
            const divisionSelect = document.getElementById("division_id");
            const unidadSelect = document.getElementById("unidad_negocio_id");
            const areaSelect = document.getElementById("area_id");
            const unidadHint = document.getElementById("unidad_negocio_hint");

            // Un puesto global no edita su estructura.
            if (!unidadSelect) return;

            // El nivel sale de la primera palabra del nombre del puesto.
            function nivelDesdeNombre(nombre) {
                const limpio = (nombre || "").trim()
                    .replace(/^sub\s*-\s*/i, "sub")
                    .replace(/^sub\s+(?=director)/i, "sub");

                return limpio.split(/\s+/)[0]
                    .toLowerCase()
                    .normalize("NFD")
                    .replace(/[̀-ͯ]/g, "")
                    .replace(/^directora$/, "director")
                    .replace(/^subdirectora$/, "subdirector")
                    .replace(/^gerenta$/, "gerente");
            }

            function nivelPermiteMultiples() {
                return NIVELES_MULTIUNIDAD.includes(nivelDesdeNombre(nombreInput.value));
            }

            function reinitSelect2(select) {
                if ($(select).data("select2")) {
                    $(select).select2("destroy");
                }
                $(select).select2({
                    theme: "default",
                    width: "100%",
                    placeholder: $(select).data("placeholder") || "Seleccione una opción",
                    allowClear: !select.multiple
                });
            }

            function resetSelect(select, placeholder) {
                select.innerHTML = select.multiple ? "" : `<option value="">${placeholder}</option>`;
                $(select).prop("disabled", true);
                $(select).data("placeholder", placeholder);

                // select2 fija placeholder y estado al inicializar: sin reinit seguiria
                // mostrando el texto anterior o "no se encontraron resultados".
                reinitSelect2(select);
                $(select).val(select.multiple ? [] : "").trigger("change.select2");
            }

            function valoresDe(select) {
                const valor = $(select).val();
                if (!valor) return [];
                return (Array.isArray(valor) ? valor : [valor]).filter(Boolean).map(String);
            }

            function unidadesSeleccionadas() {
                return valoresDe(unidadSelect);
            }

            // Cambiar de unidad conserva las areas ya elegidas que sigan siendo validas.
            // Solo cambiar de division las descarta todas.
            let conservarAreas = true;

            function aplicarNivel() {
                const permiteMultiples = nivelPermiteMultiples();
                unidadHint.classList.toggle("hidden", !permiteMultiples);

                if (unidadSelect.multiple === permiteMultiples) return;

                const previas = unidadesSeleccionadas();
                unidadSelect.multiple = permiteMultiples;

                const placeholderOption = unidadSelect.querySelector('option[value=""]');
                if (permiteMultiples && placeholderOption) {
                    placeholderOption.remove();
                } else if (!permiteMultiples && !placeholderOption && unidadSelect.options.length) {
                    unidadSelect.insertBefore(
                        new Option("Seleccionar Unidad de Negocio", ""),
                        unidadSelect.firstChild
                    );
                }

                reinitSelect2(unidadSelect);

                const conservadas = permiteMultiples ? previas : previas.slice(0, 1);
                $(unidadSelect).val(permiteMultiples ? conservadas : (conservadas[0] || "")).trigger("change");
            }

            function loadUnidades(divisionId) {
                resetSelect(unidadSelect, "Cargando unidades...");
                resetSelect(areaSelect, "Primero selecciona una Unidad de Negocio");

                if (!divisionId) return;

                fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                    .then(res => res.json())
                    .then(data => {
                        unidadSelect.innerHTML = unidadSelect.multiple ?
                            "" :
                            '<option value="">Seleccionar Unidad de Negocio</option>';

                        data.forEach(u => {
                            unidadSelect.appendChild(new Option(u.nombre, u.id_unidad_negocio));
                        });

                        $(unidadSelect).prop("disabled", false);
                        $(unidadSelect).data("placeholder", "Seleccionar Unidad de Negocio");
                        reinitSelect2(unidadSelect);
                        $(unidadSelect).val(unidadSelect.multiple ? [] : "").trigger("change");
                    })
                    .catch(() => resetSelect(unidadSelect, "Error al cargar unidades"));
            }

            function loadAreas(unidadesIds) {
                const previas = conservarAreas ? valoresDe(areaSelect) : [];
                conservarAreas = true;

                resetSelect(areaSelect, "Cargando áreas...");

                if (!unidadesIds.length) {
                    resetSelect(areaSelect, "Primero selecciona una Unidad de Negocio");
                    return;
                }

                fetch(`/puestos-trabajo/areas/${unidadesIds.join(",")}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            resetSelect(areaSelect, "Sin áreas disponibles");
                            return;
                        }

                        areaSelect.innerHTML = "";

                        // Con varias unidades se agrupa para saber de donde viene cada area.
                        if (unidadesIds.length > 1) {
                            const grupos = {};
                            data.forEach(a => {
                                const nombreUnidad = a.unidad_negocio?.nombre ?? "Sin unidad";
                                grupos[nombreUnidad] = grupos[nombreUnidad] || [];
                                grupos[nombreUnidad].push(a);
                            });

                            Object.keys(grupos).sort().forEach(nombreUnidad => {
                                const grupo = document.createElement("optgroup");
                                grupo.label = nombreUnidad;
                                grupos[nombreUnidad].forEach(a => {
                                    grupo.appendChild(new Option(a.nombre, a.id_area));
                                });
                                areaSelect.appendChild(grupo);
                            });
                        } else {
                            data.forEach(a => areaSelect.appendChild(new Option(a.nombre, a.id_area)));
                        }

                        $(areaSelect).prop("disabled", false);
                        $(areaSelect).data("placeholder", "Seleccionar Área");
                        reinitSelect2(areaSelect);

                        const validas = data.map(a => String(a.id_area));
                        $(areaSelect)
                            .val(previas.filter(id => validas.includes(id)))
                            .trigger("change.select2");
                    })
                    .catch(() => resetSelect(areaSelect, "Error al cargar áreas"));
            }

            nombreInput.addEventListener("input", aplicarNivel);

            $(divisionSelect).on("change", function() {
                conservarAreas = false;
                loadUnidades(this.value);
            });

            $(unidadSelect).on("change", function() {
                loadAreas(unidadesSeleccionadas());
            });

            // El estado inicial ya viene renderizado del servidor con su unidad y sus
            // areas marcadas: aqui solo se sincroniza el modo simple/multiple.
            aplicarNivel();
        });
    </script>
</x-app-layout>
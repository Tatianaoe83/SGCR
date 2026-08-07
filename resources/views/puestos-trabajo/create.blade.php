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
                            <select id="unidad_negocio_id" class="select2 form-select w-full" name="unidades_negocio_ids[]" data-placeholder="Primero selecciona una División" required disabled>
                                <option value="">Primero selecciona una División</option>
                            </select>
                            <p id="unidad_negocio_hint" class="text-xs text-slate-500 mt-1 hidden">
                                Puedes seleccionar varias unidades de negocio.
                            </p>
                            @error('unidades_negocio_ids')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Área -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="area_id">Área</label>
                            <select id="area_id" class="select2 form-select w-full" name="areas_ids[]" multiple data-placeholder="Primero selecciona una Unidad de Negocio" required disabled>
                            </select>
                            @error('areas_ids')
                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Jefe Directo -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="puesto_id">Jefe Directo</label>
                            <select id="puesto_id" class="select2 form-select w-full"
                                name="puesto_trabajo_id"
                                data-placeholder="Seleccionar Puesto">
                                <option value="">Seleccionar Puesto</option>
                                @foreach($puestos as $puesto)
                                <option value="{{ $puesto->id_puesto_trabajo }}">
                                    {{ $puesto->nombre }}
                                </option>
                                @endforeach
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

            const NIVELES_MULTIUNIDAD = @json($nivelesMultiunidad);

            const nombreInput = document.getElementById('nombre');
            const divisionSelect = document.getElementById('division_id');
            const unidadNegocioSelect = document.getElementById('unidad_negocio_id');
            const areaSelect = document.getElementById('area_id');
            const unidadHint = document.getElementById('unidad_negocio_hint');

            // El nivel sale de la primera palabra del nombre del puesto.
            function nivelDesdeNombre(nombre) {
                const limpio = (nombre || '').trim()
                    .replace(/^sub\s*-\s*/i, 'sub')
                    .replace(/^sub\s+(?=director)/i, 'sub');

                return limpio.split(/\s+/)[0]
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[̀-ͯ]/g, '')
                    .replace(/^directora$/, 'director')
                    .replace(/^subdirectora$/, 'subdirector')
                    .replace(/^gerenta$/, 'gerente');
            }

            function nivelPermiteMultiples() {
                return NIVELES_MULTIUNIDAD.includes(nivelDesdeNombre(nombreInput.value));
            }

            function reinitSelect2(select) {
                if ($(select).data('select2')) {
                    $(select).select2('destroy');
                }
                $(select).select2({
                    theme: 'default',
                    width: '100%',
                    placeholder: $(select).data('placeholder') || 'Seleccione una opción',
                    allowClear: !select.multiple
                });
            }

            function resetSelect(select, placeholder) {
                select.innerHTML = select.multiple ? '' : `<option value="">${placeholder}</option>`;
                $(select).prop('disabled', true);
                $(select).data('placeholder', placeholder);

                // select2 fija placeholder y estado al inicializar: sin reinit seguiria
                // mostrando el texto anterior o "no se encontraron resultados".
                reinitSelect2(select);
                $(select).val(select.multiple ? [] : '').trigger('change.select2');
            }

            function valoresDe(select) {
                const valor = $(select).val();
                if (!valor) return [];
                return (Array.isArray(valor) ? valor : [valor]).filter(Boolean).map(String);
            }

            function unidadesSeleccionadas() {
                return valoresDe(unidadNegocioSelect);
            }

            // Cambiar de unidad conserva las areas ya elegidas que sigan siendo validas.
            // Solo cambiar de division las descarta todas.
            let conservarAreas = true;

            function aplicarNivel() {
                const permiteMultiples = nivelPermiteMultiples();
                unidadHint.classList.toggle('hidden', !permiteMultiples);

                if (unidadNegocioSelect.multiple === permiteMultiples) return;

                const previas = unidadesSeleccionadas();
                unidadNegocioSelect.multiple = permiteMultiples;

                // El placeholder vacio solo aplica al modo simple.
                const placeholderOption = unidadNegocioSelect.querySelector('option[value=""]');
                if (permiteMultiples && placeholderOption) {
                    placeholderOption.remove();
                } else if (!permiteMultiples && !placeholderOption && unidadNegocioSelect.options.length) {
                    unidadNegocioSelect.insertBefore(
                        new Option('Seleccionar Unidad de Negocio', ''),
                        unidadNegocioSelect.firstChild
                    );
                }

                reinitSelect2(unidadNegocioSelect);

                const conservadas = permiteMultiples ? previas : previas.slice(0, 1);
                $(unidadNegocioSelect).val(permiteMultiples ? conservadas : (conservadas[0] || '')).trigger('change');
            }

            function loadUnidadesNegocio(divisionId) {
                resetSelect(unidadNegocioSelect, 'Cargando unidades...');
                resetSelect(areaSelect, 'Primero selecciona una Unidad de Negocio');

                if (!divisionId) {
                    resetSelect(unidadNegocioSelect, 'Primero selecciona una División');
                    return;
                }

                fetch(`/puestos-trabajo/unidades-negocio/${divisionId}`)
                    .then(res => res.json())
                    .then(data => {
                        unidadNegocioSelect.innerHTML = unidadNegocioSelect.multiple ?
                            '' :
                            '<option value="">Seleccionar Unidad de Negocio</option>';

                        data.forEach(u => {
                            unidadNegocioSelect.appendChild(new Option(u.nombre, u.id_unidad_negocio));
                        });

                        $(unidadNegocioSelect).prop('disabled', false);
                        $(unidadNegocioSelect).data('placeholder', 'Seleccionar Unidad de Negocio');
                        reinitSelect2(unidadNegocioSelect);
                        $(unidadNegocioSelect).val(unidadNegocioSelect.multiple ? [] : '').trigger('change.select2');
                    })
                    .catch(err => {
                        console.error('Error al cargar unidades:', err);
                        resetSelect(unidadNegocioSelect, 'Error al cargar unidades');
                    });
            }

            function loadAreas(unidadesIds) {
                const previas = conservarAreas ? valoresDe(areaSelect) : [];
                conservarAreas = true;

                resetSelect(areaSelect, 'Cargando áreas...');

                if (!unidadesIds.length) {
                    resetSelect(areaSelect, 'Primero selecciona una Unidad de Negocio');
                    return;
                }

                fetch(`/puestos-trabajo/areas/${unidadesIds.join(',')}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.length) {
                            resetSelect(areaSelect, 'Sin áreas disponibles');
                            return;
                        }

                        areaSelect.innerHTML = '';

                        // Con varias unidades se agrupa para saber de donde viene cada area.
                        if (unidadesIds.length > 1) {
                            const grupos = {};
                            data.forEach(a => {
                                const nombreUnidad = a.unidad_negocio?.nombre ?? 'Sin unidad';
                                grupos[nombreUnidad] = grupos[nombreUnidad] || [];
                                grupos[nombreUnidad].push(a);
                            });

                            Object.keys(grupos).sort().forEach(nombreUnidad => {
                                const grupo = document.createElement('optgroup');
                                grupo.label = nombreUnidad;
                                grupos[nombreUnidad].forEach(a => {
                                    grupo.appendChild(new Option(a.nombre, a.id_area));
                                });
                                areaSelect.appendChild(grupo);
                            });
                        } else {
                            data.forEach(a => areaSelect.appendChild(new Option(a.nombre, a.id_area)));
                        }

                        $(areaSelect).prop('disabled', false);
                        $(areaSelect).data('placeholder', 'Seleccionar Área');
                        reinitSelect2(areaSelect);

                        const validas = data.map(a => String(a.id_area));
                        $(areaSelect)
                            .val(previas.filter(id => validas.includes(id)))
                            .trigger('change.select2');
                    })
                    .catch(err => {
                        console.error('Error al cargar áreas:', err);
                        resetSelect(areaSelect, 'Error al cargar áreas');
                    });
            }

            nombreInput.addEventListener('input', aplicarNivel);

            $(divisionSelect).on('change', function() {
                conservarAreas = false;
                loadUnidadesNegocio($(this).val());
            });

            $(unidadNegocioSelect).on('change', function() {
                loadAreas(unidadesSeleccionadas());
            });

            aplicarNivel();
        });
    </script>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Elementos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mx-10 p-4 border border-gray-300 shadow-md rounded-lg bg-gray-800 text-white">
                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por División</label>
                            <select id="filtro_division" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Selecciona una opción</option>
                                @foreach($divisiones as $division)
                                <option value="{{ $division->id_division }}">{{ $division->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por Unidad</label>
                            <select id="filtro_unidad" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Selecciona una opción</option>
                                @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id_unidad_negocio }}">{{ $unidad->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por Área</label>
                            <select id="filtro_area" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Selecciona una opción</option>
                                @foreach($areas as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar por nombre</label>
                            <input type="text" id="busqueda_texto" placeholder="Buscar puestos..." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button type="button" id="select_all" class="cursor-pointer px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md">
                                Seleccionar Todos
                            </button>
                            <button type="button" id="deselect_all" class="cursor-pointer px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-md">
                                Deseleccionar Todos
                            </button>
                            <span id="contador_seleccionados" class="text-sm text-gray-600 dark:text-gray-400">
                                0 puestos seleccionados
                            </span>
                        </div>
                        <button type="button" id="limpiar_filtros" class="cursor-pointer px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded-md">
                            Limpiar Filtros
                        </button>
                    </div>
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
                    <div class="flex flex-row gap-3">
                        <div>
                            <button type="submit" id="btnGenerarMatriz" class="bg-orange-500 cursor-pointer hover:scale-105 transition-all font-medium px-4 py-2 rounded-lg">
                                Generar Matriz
                            </button>
                        </div>
                    </div>
                    <div id="contenedor_matriz" class="mt-6 hidden">
                        <h3 class="text-lg font-semibold mb-4 text-gray-700 dark:text-gray-300">Lista de Elementos seleccionados</h3>
                        <div id="tabla_matriz"></div>
                    </div>
                    <div id="loader" class="hidden flex flex-col items-center justify-center py-8">
                        <div class="w-12 h-12 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>

                        <p class="mt-4 text-sm text-gray-400 animate-pulse">
                            Generando matriz, por favor espera...
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        const filtroDivision = document.getElementById('filtro_division');
        const filtroUnidad = document.getElementById('filtro_unidad');
        const filtroArea = document.getElementById('filtro_area');
        const busquedaTexto = document.getElementById('busqueda_texto');

        const selectAllBtn = document.getElementById('select_all');
        const deselectAllBtn = document.getElementById('deselect_all');
        const limpiarBtn = document.getElementById('limpiar_filtros');
        const contador = document.getElementById('contador_seleccionados');

        const puestos = document.querySelectorAll('.puesto-checkbox');

        function actualizarContador() {
            const seleccionados = document.querySelectorAll('.puesto-checkbox:checked').length;
            contador.textContent = `${seleccionados} puestos seleccionados`;
        }

        function aplicarFiltros() {
            const divisionId = filtroDivision.value;
            const unidadId = filtroUnidad.value;
            const areaId = filtroArea.value;
            const texto = busquedaTexto.value.toLowerCase();

            let visibles = 0;

            puestos.forEach(cb => {
                const division = cb.dataset.division;
                const unidad = cb.dataset.unidad;
                const area = cb.dataset.area;
                const nombre = cb.dataset.nombre;

                let mostrar = true;
                if (divisionId && division !== divisionId) mostrar = false;
                if (unidadId && unidad !== unidadId) mostrar = false;
                if (areaId && area !== areaId) mostrar = false;
                if (texto && !nombre.includes(texto)) mostrar = false;

                const label = cb.closest('label');
                if (mostrar) {
                    label.style.display = 'flex';
                    visibles++;
                } else {
                    label.style.display = 'none';
                }
            });

            const lista = document.getElementById('lista_puestos');
            const mensaje = document.getElementById('mensaje_filtros');

            if (visibles === 0) {
                if (!mensaje) {
                    const aviso = document.createElement('p');
                    aviso.id = 'mensaje_filtros';
                    aviso.textContent = 'No hay datos con esos filtros';
                    aviso.className = 'text-center text-gray-400 dark:text-gray-500 py-6';
                    lista.appendChild(aviso);
                }
            } else {
                if (mensaje) mensaje.remove();
            }
        }


        filtroDivision.addEventListener('change', aplicarFiltros);
        filtroUnidad.addEventListener('change', aplicarFiltros);
        filtroArea.addEventListener('change', aplicarFiltros);
        busquedaTexto.addEventListener('input', aplicarFiltros);

        selectAllBtn.addEventListener('click', () => {
            puestos.forEach(cb => {
                if (cb.closest('label').style.display !== 'none') cb.checked = true;
            });
            actualizarContador();
        });

        deselectAllBtn.addEventListener('click', () => {
            puestos.forEach(cb => cb.checked = false);
            actualizarContador();
        });

        limpiarBtn.addEventListener('click', () => {
            filtroDivision.value = '';
            filtroUnidad.value = '';
            filtroArea.value = '';
            busquedaTexto.value = '';

            puestos.forEach(cb => cb.closest('label').style.display = 'flex');
            actualizarContador();
        });

        puestos.forEach(cb => cb.addEventListener('change', actualizarContador));

        actualizarContador();
    </script>
    <script>
        document.getElementById('btnGenerarMatriz').addEventListener('click', () => {
            const seleccionados = Array.from(document.querySelectorAll('.puesto-checkbox:checked'))
                .map(cb => cb.value);

            if (seleccionados.length === 0) {
                alert("Debes seleccionar al menos un puesto.");
                return;
            }

            const loader = document.getElementById("loader");
            const tabla = document.getElementById("tabla_matriz");
            const contenedor = document.getElementById("contenedor_matriz");

            loader.classList.remove("hidden");
            tabla.innerHTML = "";
            contenedor.classList.add("hidden");

            fetch("{{ route('matriz.generar') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        puestos_relacionados: seleccionados
                    })
                })
                .then(res => res.json())
                .then(res => {
                    setTimeout(() => {
                        loader.classList.add("hidden");
                        contenedor.classList.remove("hidden");

                        if (res.status !== "ok") {
                            tabla.innerHTML = `<p class="text-red-500">${res.message}</p>`;
                            return;
                        }

                        if (res.data.length === 0) {
                            tabla.innerHTML = `<p class="text-gray-500 dark:text-gray-400">No se encontraron elementos para los puestos seleccionados.</p>`;
                            return;
                        }

                        let html = `
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 dark:border-gray-700 rounded-lg text-sm">
                    <thead class="bg-gray-200 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 border">Nombre</th>
                            <th class="px-4 py-2 border">Tipo</th>
                            <th class="px-4 py-2 border">Proceso</th>
                            <th class="px-4 py-2 border">Unidad</th>
                            <th class="px-4 py-2 border">Responsable</th>
                            <th class="px-4 py-2 border">Ejecutor</th>
                            <th class="px-4 py-2 border">Resguardo</th>
                        </tr>
                    </thead>
                    <tbody>`;

                        res.data.forEach(e => {
                            html += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2 border">${e.nombre_elemento}</td>
                        <td class="px-4 py-2 border">${e.tipo_elemento?.nombre ?? "N/A"}</td>
                        <td class="px-4 py-2 border">${e.tipo_proceso?.nombre ?? "N/A"}</td>
                        <td class="px-4 py-2 border">${e.unidad_negocio?.nombre ?? "N/A"}</td>
                        <td class="px-4 py-2 border">${e.puesto_responsable?.nombre ?? "N/A"}</td>
                        <td class="px-4 py-2 border">${e.puesto_ejecutor?.nombre ?? "N/A"}</td>
                        <td class="px-4 py-2 border">${e.puesto_resguardo?.nombre ?? "N/A"}</td>
                    </tr>`;
                        });

                        html += `</tbody></table></div>`;
                        tabla.innerHTML = html;

                    }, 1500);
                })
                .catch(err => {
                    console.error(err);
                    loader.classList.add("hidden");
                    alert("Error al generar la matriz.");
                });
        });
    </script>
</x-app-layout>
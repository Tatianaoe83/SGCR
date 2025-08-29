<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Matriz de Elementos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mx-10 p-6 border border-gray-200 dark:border-gray-600 shadow-lg rounded-xl bg-white dark:bg-gradient-to-br dark:from-gray-800 dark:to-gray-900">
                <div class="flex flex-col gap-6">
                    <!-- Filtros -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                División
                            </label>
                            <select id="filtro_division" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                                <option value="">Todas las divisiones</option>
                                @foreach($divisiones as $division)
                                <option value="{{ $division->id_division }}">{{ $division->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                                </svg>
                                Unidad de Negocio
                            </label>
                            <select id="filtro_unidad" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                                <option value="">Todas las unidades</option>
                                @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id_unidad_negocio }}">{{ $unidad->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                </svg>
                                Área
                            </label>
                            <select id="filtro_area" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">
                                <option value="">Todas las áreas</option>
                                @foreach($areas as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                </svg>
                                Buscar por nombre
                            </label>
                            <input type="text" id="busqueda_texto" placeholder="Buscar puestos..."
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <!-- Controles de selección -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex flex-wrap items-center gap-4">
                            <button type="button" id="select_all"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Seleccionar Todos
                            </button>
                            <button type="button" id="deselect_all"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Deseleccionar Todos
                            </button>
                            <span id="contador_seleccionados" class="px-3 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg">
                                0 puestos seleccionados
                            </span>
                        </div>
                        <button type="button" id="limpiar_filtros"
                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Limpiar Filtros
                        </button>
                    </div>

                    <!-- Lista de puestos -->
                    <div class="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/30">
                        <div id="lista_puestos" class="p-4 space-y-2">
                            @foreach($puestosTrabajo as $puesto)
                            <label class="flex items-center p-3 hover:bg-gray-100 dark:hover:bg-gray-600/50 rounded-lg cursor-pointer transition-all duration-200 border border-transparent hover:border-gray-300 dark:hover:border-gray-500">
                                <input type="checkbox" name="puestos_relacionados[]" value="{{ $puesto->id_puesto_trabajo }}"
                                    class="puesto-checkbox w-5 h-5 rounded border-gray-400 text-orange-600 shadow-sm focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800"
                                    data-division="{{ $puesto->division->id_division ?? '' }}"
                                    data-unidad="{{ $puesto->unidadNegocio->id_unidad_negocio ?? '' }}"
                                    data-area="{{ $puesto->area->id_area ?? '' }}"
                                    data-nombre="{{ strtolower($puesto->nombre) }}"
                                    {{ in_array($puesto->id_puesto_trabajo, old('puestos_relacionados', [])) ? 'checked' : '' }}>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $puesto->nombre }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-2">
                                            {{ $puesto->division->nombre ?? 'Sin división' }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mr-2">
                                            {{ $puesto->unidadNegocio->nombre ?? 'Sin unidad' }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ $puesto->area->nombre ?? 'Sin área' }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Botón generar matriz -->
                    <div class="flex justify-center gap-4">
                        <button type="button" id="btnGenerarMatriz"
                            class="cursor-pointer bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 flex items-center gap-3">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Generar Matriz
                        </button>
                        <button id="matrizGeneral" class="cursor-pointer bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 flex items-center gap-3" type="button">Generar Matriz General</button>
                    </div>

                    <!-- Contenedor de la matriz -->
                    <div id="contenedor_matriz" class="mt-8 hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                                        <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Matriz de Elementos Generada
                                    </h3>
                                    <button type="button" id="btnExportarExcel"
                                        class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                        Exportar a Excel
                                    </button>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    Elementos encontrados para los puestos seleccionados
                                </p>
                            </div>
                            <div id="tabla_matriz" class="p-6"></div>
                        </div>
                    </div>

                    <!-- Loader -->
                    <div id="loader" class="hidden flex flex-col items-center justify-center py-12">
                        <div class="relative">
                            <div class="w-16 h-16 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 w-16 h-16 border-4 border-transparent border-t-orange-400 rounded-full animate-spin" style="animation-duration: 1.5s;"></div>
                        </div>
                        <p class="mt-6 text-lg font-medium text-gray-700 dark:text-gray-300 animate-pulse">
                            Generando matriz, por favor espera...
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Esto puede tomar unos segundos</p>
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

            // Actualizar el botón de export
            const btnExportar = document.getElementById('btnExportarExcel');
            if (btnExportar) {
                btnExportar.disabled = seleccionados === 0;
                btnExportar.classList.toggle('opacity-50', seleccionados === 0);
                btnExportar.classList.toggle('cursor-not-allowed', seleccionados === 0);
            }
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
                    aviso.className = 'text-center text-gray-500 dark:text-gray-400 py-6';
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
        document.getElementById('matrizGeneral').addEventListener('click', () => {
            /*       const seleccionados = Array.from(document.querySelectorAll('.puesto-checkbox:checked'))
                      .map(cb => cb.value);

                  if (seleccionados.length === 0) {
                      alert("Debes seleccionar al menos un puesto.");
                      return;
                  } */

            const loader = document.getElementById("loader");
            const tabla = document.getElementById("tabla_matriz");
            const contenedor = document.getElementById("contenedor_matriz");

            loader.classList.remove("hidden");
            tabla.innerHTML = "";
            contenedor.classList.add("hidden");

            fetch("{{ route('matriz.matrizgeneral') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    /* body: JSON.stringify({
                        puestos_relacionados: seleccionados
                    }) */
                })
                .then(res => res.json())
                .then(res => {
                    setTimeout(() => {
                        loader.classList.add("hidden");
                        contenedor.classList.remove("hidden");

                        if (res.status !== "ok") {
                            tabla.innerHTML = `<div class="text-center py-8">
                                <div class="text-red-500 text-lg font-medium">${res.message}</div>
                            </div>`;
                            return;
                        }

                        if (res.data.length === 0) {
                            tabla.innerHTML = `<div class="text-center py-8">
                                <div class="text-gray-500 dark:text-gray-400 text-lg">No se encontraron elementos para los puestos seleccionados.</div>
                                <div class="text-gray-400 dark:text-gray-500 text-sm mt-2">Intenta con otros puestos o verifica la configuración</div>
                            </div>`;
                            return;
                        }

                        let html = `
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500">Proceso</th>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500">Folio</th>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500">Procedimiento</th>`;

                        res.puestos.forEach(p => {
                            html += `<th class="px-6 py-3 text-xs font-medium text-gray-500 rotate-45 origin-bottom-left">${p}</th>`;
                        });

                        html += `</tr></thead><tbody>`;

                        // filas de elementos
                        res.data.forEach((e, index) => {
                            const rowClass = index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';

                            html += `<tr class="${rowClass}">
                    <td class="px-6 py-4">${e.Proceso}</td>
                    <td class="px-6 py-4">${e.Folio}</td>
                    <td class="px-6 py-4">${e.Procedimiento}</td>`;

                            res.puestos.forEach(p => {
                                html += `<td class="px-6 py-4 text-center">${e[p] || ""}</td>`;
                            });

                            html += `</tr>`;
                        });

                        html += `</tbody></table></div>`;
                        tabla.innerHTML = html;

                        // Guardar los puestos seleccionados para el export
                        window.puestosSeleccionados = seleccionados;

                    }, 1500);
                })
                .catch(err => {
                    console.error(err);
                    loader.classList.add("hidden");
                    alert("Error al generar la matriz.");
                });
        });

        // Función para exportar a Excel
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'btnExportarExcel') {
                const seleccionados = window.puestosSeleccionados || [];

                if (seleccionados.length === 0) {
                    alert("No hay puestos seleccionados para exportar.");
                    return;
                }

                // Crear un formulario temporal para enviar los datos
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('matriz.export') }}";

                // Agregar el token CSRF
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = "{{ csrf_token() }}";
                form.appendChild(csrfToken);

                // Agregar los puestos seleccionados
                seleccionados.forEach(puestoId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'puestos_relacionados[]';
                    input.value = puestoId;
                    form.appendChild(input);
                });

                // Agregar el formulario al DOM y enviarlo
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        });
    </script>
</x-app-layout>
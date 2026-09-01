<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 pb-8 w-full max-w-screen-2xl mx-auto">

        <!-- Page header -->
        <div class="mb-5 mt-4">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Matriz de Responsabilidades</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selecciona puestos y genera la matriz de elementos por responsabilidad</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-800/50 dark:text-green-300 px-4 py-3 rounded-xl mb-5 text-sm">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

            <!-- Panel de filtros y seleccion -->
            <div class="lg:col-span-2">

                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">División</label>
                            <select id="filtro_division" class="ui-select w-full sm:w-full">
                                <option value="">Todas las divisiones</option>
                                @foreach($divisiones as $division)
                                <option value="{{ $division->id_division }}">{{ $division->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Unidad de Negocio</label>
                            <select id="filtro_unidad" class="ui-select w-full sm:w-full">
                                <option value="">Todas las unidades</option>
                                @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id_unidad_negocio }}">{{ $unidad->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Área</label>
                            <select id="filtro_area" class="ui-select w-full sm:w-full">
                                <option value="">Todas las áreas</option>
                                @foreach($areas as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Buscar por nombre</label>
                            <div class="relative">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" id="busqueda_texto" placeholder="Buscar puesto..."
                                    class="ui-search-input">
                            </div>
                        </div>
                    </div>

                    <!-- Controles -->
                    <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="select_all"
                                class="btn-primary">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Seleccionar visibles
                            </button>
                            <button type="button" id="deselect_all"
                                class="btn-secondary">
                                Deseleccionar
                            </button>
                            <button type="button" id="limpiar_filtros"
                                class="btn-secondary">
                                Limpiar filtros
                            </button>
                        </div>
                        <span id="contador_seleccionados" class="badge-status badge-info">
                            0 seleccionados
                        </span>
                    </div>

                    <!-- Lista de puestos -->
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 mb-3">Puestos de Trabajo</h2>
                    <div class="max-h-96 overflow-y-auto -mx-1 px-1">
                        <div id="lista_puestos" class="p-3 space-y-1.5">
                            @foreach($puestosTrabajo as $puesto)
                            <label class="flex items-start gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl cursor-pointer transition border border-transparent hover:border-gray-200 dark:hover:border-gray-600 has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20 has-[:checked]:border-brand-navy/30">
                                <input type="checkbox" name="puestos_relacionados[]" value="{{ $puesto->id_puesto_trabajo }}"
                                    class="puesto-checkbox mt-0.5 w-5 h-5 rounded border-gray-300 text-brand-navy focus:ring-2 focus:ring-brand-navy"
                                    data-division="{{ $puesto->division->id_division ?? '' }}"
                                    data-unidad="{{ $puesto->unidad_negocio_id ?? '' }}"
                                    data-area="{{ $puesto->areas->isNotEmpty() ? $puesto->areas->pluck('id_area')->join(',') : ($puesto->area->id_area ?? '') }}"
                                    data-nombre="{{ strtolower($puesto->nombre) }}"
                                    {{ in_array($puesto->id_puesto_trabajo, old('puestos_relacionados', [])) ? 'checked' : '' }}>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $puesto->nombre }}</div>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        <span class="badge-status badge-info">
                                            {{ $puesto->division->nombre ?? 'Sin división' }}
                                        </span>
                                        <span class="badge-status badge-neutral">
                                            {{ $puesto->unidadNegocio->nombre ?? 'Sin unidad' }}
                                        </span>
                                        @forelse($puesto->areas as $area)
                                        <span class="badge-status badge-neutral">
                                            {{ $area->nombre }}
                                        </span>
                                        @empty
                                        <span class="badge-status badge-neutral">
                                            Sin área
                                        </span>
                                        @endforelse
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-4">Leyenda</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3 text-sm">
                    <div class="flex items-center gap-2"><span class="badge-role badge-role-r">R</span><span class="text-gray-600 dark:text-gray-300">Responsable</span></div>
                    <div class="flex items-center gap-2"><span class="badge-role badge-role-e">E</span><span class="text-gray-600 dark:text-gray-300">Ejecutor</span></div>
                    <div class="flex items-center gap-2"><span class="badge-role badge-role-a">A</span><span class="text-gray-600 dark:text-gray-300">Resguardo</span></div>
                    <div class="flex items-center gap-2"><span class="badge-role badge-role-pr">PR</span><span class="text-gray-600 dark:text-gray-300">Relacionado</span></div>
                    <div class="flex items-center gap-2"><span class="badge-role badge-role-pm">PM</span><span class="text-gray-600 dark:text-gray-300">Adicional</span></div>
                </div>
            </div>
        </div>

        <!-- Botones de generación -->
        <div class="flex flex-col sm:flex-row gap-3 mt-5">
            <button type="button" id="btnGenerarMatriz"
                class="btn-primary">
                Generar matriz con puestos seleccionados
            </button>
            <button type="button" id="matrizGeneral"
                class="btn-secondary">
                Generar matriz general
            </button>
        </div>

        <!-- Contenedor de la matriz -->
        <div id="contenedor_matriz" class="mt-6 hidden">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Matriz Generada</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Elementos encontrados para los puestos seleccionados</p>
                        </div>
                        <button type="button" id="btnExportarExcel"
                            class="btn-secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Exportar a Excel
                        </button>
                    </div>
                </div>
                <div id="tabla_matriz" class="p-6"></div>
            </div>
        </div>

        <!-- Loader -->
        <div id="loader" class="hidden flex flex-col items-center justify-center py-12">
            <div class="w-14 h-14 border-4 border-gray-200 dark:border-gray-600 border-t-brand-navy rounded-full animate-spin"></div>
            <p class="mt-5 text-sm font-medium text-gray-600 dark:text-gray-300">Generando matriz...</p>
        </div>

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Paginador client-side para la tabla de la matriz generada.
            function paginarMatriz(perPage = 10) {
                const cont = document.getElementById('tabla_matriz');
                const tbody = cont.querySelector('table tbody');
                if (!tbody) return;

                const rows = Array.from(tbody.querySelectorAll(':scope > tr'));
                const navPrevio = cont.querySelector('.matriz-paginacion');
                if (navPrevio) navPrevio.remove();

                if (rows.length <= perPage) return;

                const pages = Math.ceil(rows.length / perPage);
                let page = 1;

                const nav = document.createElement('div');
                nav.className = 'matriz-paginacion flex items-center justify-between gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700';
                const info = document.createElement('span');
                info.className = 'text-sm text-gray-500 dark:text-gray-400';
                const controles = document.createElement('div');
                controles.className = 'flex items-center gap-2';
                const prev = document.createElement('button');
                const next = document.createElement('button');
                const btnCls = 'px-3 py-1.5 rounded-lg text-sm font-semibold border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed transition';
                prev.className = btnCls;
                next.className = btnCls;
                prev.type = 'button';
                next.type = 'button';
                prev.textContent = 'Anterior';
                next.textContent = 'Siguiente';
                controles.appendChild(prev);
                controles.appendChild(next);
                nav.appendChild(info);
                nav.appendChild(controles);
                cont.appendChild(nav);

                function render() {
                    const inicio = (page - 1) * perPage;
                    const fin = page * perPage;
                    rows.forEach((r, i) => {
                        r.style.display = (i >= inicio && i < fin) ? '' : 'none';
                    });
                    info.textContent = `Página ${page} de ${pages} · ${rows.length} registros`;
                    prev.disabled = page === 1;
                    next.disabled = page === pages;
                }

                prev.addEventListener('click', () => { if (page > 1) { page--; render(); } });
                next.addEventListener('click', () => { if (page < pages) { page++; render(); } });

                render();
            }

            // Helper de alertas con SweetAlert2 (dark-aware).
            function matrizAlerta(icon, title, text) {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: icon,
                    title: title,
                    text: text || '',
                    confirmButtonColor: '#021D49',
                    confirmButtonText: 'Entendido',
                    background: isDark ? '#1f2937' : '#ffffff',
                    color: isDark ? '#e5e7eb' : '#374151',
                });
            }
        </script>

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

            // Modelo de puestos para calcular la cascada de filtros.
            const modeloPuestos = Array.from(puestos).map(cb => ({
                division: cb.dataset.division || '',
                unidades: cb.dataset.unidad ? cb.dataset.unidad.split(',').filter(Boolean) : [],
                areas: cb.dataset.area ? cb.dataset.area.split(',').filter(Boolean) : [],
            }));

            function actualizarContador() {
                const seleccionados = document.querySelectorAll('.puesto-checkbox:checked').length;
                contador.textContent = `${seleccionados} seleccionados`;
            }

            // Muestra/oculta las opciones de un select segun el conjunto de ids permitidos.
            function filtrarOpciones(select, idsPermitidos) {
                let valorSigueValido = false;
                Array.from(select.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    const permitido = idsPermitidos.has(opt.value);
                    opt.hidden = !permitido;
                    if (permitido && opt.value === select.value) valorSigueValido = true;
                });
                if (select.value !== '' && !valorSigueValido) select.value = '';
            }

            // Recalcula opciones de Unidad (segun division) y Area (segun division + unidad).
            function actualizarCascada() {
                const divId = filtroDivision.value;
                const uniId = filtroUnidad.value;

                const unidadesValidas = new Set();
                modeloPuestos.forEach(p => {
                    if (divId && p.division !== divId) return;
                    p.unidades.forEach(u => unidadesValidas.add(u));
                });
                filtrarOpciones(filtroUnidad, unidadesValidas);

                const uniActual = filtroUnidad.value; // puede haberse reseteado
                const areasValidas = new Set();
                modeloPuestos.forEach(p => {
                    if (divId && p.division !== divId) return;
                    if (uniActual && !p.unidades.includes(uniActual)) return;
                    p.areas.forEach(a => areasValidas.add(a));
                });
                filtrarOpciones(filtroArea, areasValidas);
            }

            function aplicarFiltros() {
                const divisionId = filtroDivision.value;
                const unidadId = filtroUnidad.value;
                const areaId = filtroArea.value;
                const texto = busquedaTexto.value.toLowerCase().trim();

                let visibles = 0;

                puestos.forEach(cb => {
                    const division = cb.dataset.division;
                    const unidades = cb.dataset.unidad ? cb.dataset.unidad.split(',') : [];
                    const areas = cb.dataset.area ? cb.dataset.area.split(',') : [];
                    const nombre = cb.dataset.nombre;

                    let mostrar = true;
                    if (divisionId && division !== divisionId) mostrar = false;
                    if (unidadId && !unidades.includes(unidadId)) mostrar = false;
                    if (areaId && !areas.includes(areaId)) mostrar = false;
                    if (texto && !nombre.includes(texto)) mostrar = false;

                    const label = cb.closest('label');
                    label.style.display = mostrar ? 'flex' : 'none';
                    if (mostrar) visibles++;
                });

                const lista = document.getElementById('lista_puestos');
                let mensaje = document.getElementById('mensaje_filtros');
                if (visibles === 0) {
                    if (!mensaje) {
                        mensaje = document.createElement('p');
                        mensaje.id = 'mensaje_filtros';
                        mensaje.textContent = 'No hay puestos con esos filtros';
                        mensaje.className = 'text-center text-sm text-gray-500 dark:text-gray-400 py-6';
                        lista.appendChild(mensaje);
                    }
                } else if (mensaje) {
                    mensaje.remove();
                }
            }

            filtroDivision.addEventListener('change', () => { actualizarCascada(); aplicarFiltros(); });
            filtroUnidad.addEventListener('change', () => { actualizarCascada(); aplicarFiltros(); });
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
                actualizarCascada();
                aplicarFiltros();
            });

            puestos.forEach(cb => cb.addEventListener('change', actualizarContador));

            actualizarCascada();
            actualizarContador();
        </script>

        <script>
            document.getElementById('matrizGeneral').addEventListener('click', () => {
                window.respMode = 'general';
                const loader = document.getElementById("loader");
                const tabla = document.getElementById("tabla_matriz");
                const contenedor = document.getElementById("contenedor_matriz");

                loader.classList.remove("hidden");
                tabla.innerHTML = "";
                contenedor.classList.add("hidden");
                loader.scrollIntoView({ behavior: 'smooth', block: 'center' });

                fetch("{{ route('matriz.matrizgeneral') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                    })
                    .then(res => res.json())
                    .then(res => {
                        setTimeout(() => {
                            loader.classList.add("hidden");
                            contenedor.classList.remove("hidden");
                            contenedor.scrollIntoView({ behavior: 'smooth', block: 'start' });

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
                                html += `<th class="px-6 py-3 text-xs font-medium text-gray-500">${p}</th>`;
                            });

                            html += `</tr></thead><tbody>`;

                            res.data.forEach((e, index) => {
                                const rowClass = index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';

                                html += `<tr class="${rowClass}">
                    <td class="px-6 py-4">${e.Proceso}</td>
                    <td class="px-6 py-4">${e.Folio}</td>
                    <td class="px-6 py-4">${e.Procedimiento}</td>`;

                                res.puestos.forEach(p => {
                                    const valor = e[p] || "";
                                    let badges = "";

                                    if (valor) {
                                        valor.split("-").forEach(v => {
                                            let color;
                                            switch (v) {
                                                case "R":
                                                    color = "badge-role badge-role-r";
                                                    break;
                                                case "E":
                                                    color = "badge-role badge-role-e";
                                                    break;
                                                case "A":
                                                    color = "badge-role badge-role-a";
                                                    break;
                                                case "PR":
                                                    color = "badge-role badge-role-pr";
                                                    break;
                                                case "PM":
                                                    color = "badge-role badge-role-pm";
                                                    break;
                                                default:
                                                    color = "badge-role badge-role-neutral";
                                            }

                                            badges += `<span class="${color} mr-1">${v}</span>`;
                                        });
                                    }

                                    html += `<td class="px-6 py-4 text-center">${badges}</td>`;
                                });


                                html += `</tr>`;
                            });

                            html += `</tbody></table></div>`;
                            tabla.innerHTML = html;
                            paginarMatriz();

                            // Guardar los puestos seleccionados para el export
                            window.respData = res.data;
                            window.respPuestos = res.puestos;
                            window.respPuestosAdicionales = res.puestosAdicionales || [];
                            window.puestosSeleccionados = res.seleccionados;

                        }, 1500);
                    })
                    .catch(err => {
                        console.error(err);
                        loader.classList.add("hidden");
                        matrizAlerta('error', 'Error', 'Ocurrió un error al generar la matriz general.');
                    });
            });
        </script>
        <script>
            function getSelectedPuestos() {
                return Array.from(document.querySelectorAll('.puesto-checkbox:checked'))
                    .map(cb => Number(cb.value))
                    .filter(n => Number.isInteger(n) && n > 0);
            }

            function badgeHtml(v) {
                const map = {
                    R: "badge-role badge-role-r",
                    E: "badge-role badge-role-e",
                    A: "badge-role badge-role-a",
                    PR: "badge-role badge-role-pr",
                    PM: "badge-role badge-role-pm",
                };
                const cls = map[v] || "badge-role badge-role-neutral";
                return `<span class="${cls} mr-1">${v}</span>`;
            }

            function renderMatriz(res) {
                const tabla = document.getElementById("tabla_matriz");
                const contenedor = document.getElementById("contenedor_matriz");

                if (res.status !== "ok") {
                    tabla.innerHTML = `<div class="text-center py-8">
        <div class="text-red-500 text-lg font-medium">${res.message ?? 'Error'}</div>
      </div>`;
                    contenedor.classList.remove("hidden");
                    return;
                }

                if (!res.data || res.data.length === 0) {
                    tabla.innerHTML = `<div class="text-center py-8">
        <div class="text-gray-500 dark:text-gray-400 text-lg">No se encontraron elementos para los criterios seleccionados.</div>
        <div class="text-gray-400 dark:text-gray-500 text-sm mt-2">Ajusta los filtros o verifica la configuración</div>
      </div>`;
                    contenedor.classList.remove("hidden");
                    return;
                }

                if (res.modo === "participacion") {
                    let html = `
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-xs font-medium text-gray-500">Proceso</th>
              <th class="px-6 py-3 text-xs font-medium text-gray-500">Folio</th>
              <th class="px-6 py-3 text-xs font-medium text-gray-500">Procedimiento</th>
              <th class="px-6 py-3 text-xs font-medium text-gray-500">Puesto</th>
              <th class="px-6 py-3 text-xs font-medium text-gray-500">Participación</th>
            </tr>
          </thead>
          <tbody>`;

                    res.data.forEach((e, index) => {
                        const rowClass = index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';
                        const badges = (e.Participacion || "")
                            .split("-")
                            .filter(Boolean)
                            .map(v => badgeHtml(v))
                            .join("");

                        html += `<tr class="${rowClass}">
          <td class="px-6 py-4">${e.Proceso ?? ""}</td>
          <td class="px-6 py-4">${e.Folio ?? ""}</td>
          <td class="px-6 py-4">${e.Procedimiento ?? ""}</td>
          <td class="px-6 py-4">${e.Puesto ?? ""}</td>
          <td class="px-6 py-4 text-center">${badges}</td>
        </tr>`;
                    });

                    html += `</tbody></table></div>`;
                    tabla.innerHTML = html;
                    paginarMatriz();

                    window.respMode = 'participacion';
                    window.respData = res.data;
                    window.respLegend = res.legend || {
                        R: 'Responsable',
                        E: 'Ejecutor',
                        A: 'Resguardo',
                        PR: 'Relacionado',
                        PM: 'Adicional'
                    };
                    window.respPuestos = undefined;
                    window.respPuestosAdicionales = [];
                    contenedor.classList.remove("hidden");
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

                (res.puestos || []).forEach(p => {
                    html += `<th class="px-6 py-3 text-xs font-medium text-gray-500 rotate-45 origin-bottom-left">${p}</th>`;
                });

                html += `</tr></thead><tbody>`;

                res.data.forEach((e, index) => {
                    const rowClass = index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';
                    html += `<tr class="${rowClass}">
        <td class="px-6 py-4">${e.Proceso}</td>
        <td class="px-6 py-4">${e.Folio}</td>
        <td class="px-6 py-4">${e.Procedimiento}</td>`;

                    (res.puestos || []).forEach(p => {
                        const valor = e[p] || "";
                        const badges = valor ?
                            valor.split("-").map(v => badgeHtml(v)).join("") :
                            "";
                        html += `<td class="px-6 py-4 text-center">${badges}</td>`;
                    });

                    html += `</tr>`;
                });

                html += `</tbody></table></div>`;
                tabla.innerHTML = html;
                paginarMatriz();

                window.respMode = 'matriz';
                window.respData = res.data;
                window.respPuestos = res.puestos;
                window.respPuestosAdicionales = res.puestosAdicionales || [];
                contenedor.classList.remove("hidden");
            }

            function showLoader() {
                const loader = document.getElementById("loader");
                loader.classList.remove("hidden");
                document.getElementById("tabla_matriz").innerHTML = "";
                document.getElementById("contenedor_matriz").classList.add("hidden");
                loader.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function hideLoaderAndShow() {
                document.getElementById("loader").classList.add("hidden");
                const cont = document.getElementById("contenedor_matriz");
                cont.classList.remove("hidden");
                cont.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            document.getElementById('btnGenerarMatriz').addEventListener('click', () => {
                window.respMode = 'participacion';
                const ids = getSelectedPuestos();
                if (ids.length === 0) {
                    matrizAlerta('warning', 'Sin puestos seleccionados', 'Selecciona al menos un puesto para generar la matriz.');
                    return;
                }
                showLoader();

                fetch("{{ route('matriz.matrizgeneral2') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            puestos_relacionados: ids
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        hideLoaderAndShow();
                        renderMatriz(res);
                    })
                    .catch(err => {
                        console.error(err);
                        hideLoaderAndShow();
                        matrizAlerta('error', 'Error', 'Ocurrió un error al generar la matriz por puestos.');
                    });
            });
        </script>
        <script>
            document.getElementById('btnExportarExcel').addEventListener('click', async () => {
                if (!window.respData) {
                    matrizAlerta('info', 'Genera la matriz primero', 'Debes generar una matriz antes de exportarla.');
                    return;
                }

                const isParticipacion = window.respMode === 'participacion';

                const url = isParticipacion ?
                    "{{ route('matriz.export2') }}" :
                    "{{ route('matriz.export') }}";

                const body = isParticipacion ? {
                    data: window.respData
                } : {
                    puestos: window.respPuestos,
                    data: window.respData,
                    puestosAdicionales: window.respPuestosAdicionales
                };

                try {
                    const res = await fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(body)
                    });

                    if (!res.ok) throw new Error('Respuesta HTTP no OK');

                    const blob = await res.blob();
                    const href = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = href;
                    a.download = isParticipacion ? "matriz_participacion.xlsx" : "matriz.xlsx";
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(href);
                } catch (e) {
                    console.error(e);
                    matrizAlerta('error', 'Error', 'Ocurrió un error al exportar a Excel.');
                }
            });
        </script>
</x-app-layout>
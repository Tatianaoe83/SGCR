<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <div class="mb-6 mt-8 space-y-4">
            <div>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Cuerpos de Correo</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestiona las plantillas de correo electrónico del sistema</p>
            </div>

            <div class="ui-toolbar bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md p-3">
                <select id="filter-tipo" class="ui-select">
                    <option value="">Todos los tipos</option>
                    @foreach(\App\Models\CuerpoCorreo::getTipos() as $key => $nombre)
                    <option value="{{ $key }}">{{ $nombre }}</option>
                    @endforeach
                </select>

                <select id="filter-estado" class="ui-select">
                    <option value="">Todos los estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>

                <div class="ui-search sm:ml-auto">
                    <span class="ui-search-icon">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="search" id="search-input" class="ui-search-input" placeholder="Buscar por nombre...">
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</p>
                <p class="mt-1 text-xl font-semibold text-[#021D49] dark:text-gray-100">{{ $cuerpos->total() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Activas</p>
                <p class="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $cuerpos->where('activo', true)->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Inactivas</p>
                <p class="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $cuerpos->where('activo', false)->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Tipos</p>
                <p class="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $cuerpos->pluck('tipo')->unique()->count() }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="relative">
            @include('partials.page-loader')
            <div id="table-content" class="opacity-0 transition-opacity duration-300 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
            <header class="px-4 sm:px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Cuerpos de Correo</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Mostrando {{ $cuerpos->count() }} de {{ $cuerpos->total() }} registros</span>
                </div>
            </header>
            <div class="overflow-x-auto">
                <table id="cuerpos-correoTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable(0)">
                                <div class="flex items-center space-x-1">
                                    <span>Nombre</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable(1)">
                                <div class="flex items-center space-x-1">
                                    <span>Tipo</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Asunto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable(3)">
                                <div class="flex items-center space-x-1">
                                    <span>Estado</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="table-body">
                        @forelse($cuerpos as $cuerpo)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150" data-tipo="{{ $cuerpo->tipo }}" data-activo="{{ $cuerpo->activo ? '1' : '0' }}">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $cuerpo->nombre }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status badge-info">
                                    {{ ucfirst($cuerpo->tipo_nombre) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div class="max-w-xs truncate" title="{{ $cuerpo->subject }}">
                                    {{ $cuerpo->subject ?: 'Sin asunto definido' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($cuerpo->activo)
                                <span class="badge-status badge-success">Activo</span>
                                @else
                                <span class="badge-status badge-neutral">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    @can('cuerpo-correo.view')
                                    <a href="{{ route('cuerpos-correo.show', $cuerpo->id_cuerpo) }}"
                                        class="btn-icon-muted"
                                        title="Ver">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('cuerpo-correo.edit')
                                    <a href="{{ route('cuerpos-correo.edit', $cuerpo->id_cuerpo) }}"
                                        class="btn-icon"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No hay plantillas de correo</h3>
                                    <p class="text-gray-500 dark:text-gray-400">No se encontraron plantillas de correo electrónico.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cuerpos->hasPages())
            <div class="mt-6">
                {{ $cuerpos->links() }}
            </div>
            @endif
            </div>
        </div>
    </div>
    </div>
    </div>

    <script>
        // Variables globales
        let currentSortColumn = -1;
        let currentSortDirection = 'asc';
        let allRows = [];
        let filteredRows = [];

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener todas las filas de la tabla
            const tableBody = document.getElementById('table-body');
            allRows = Array.from(tableBody.querySelectorAll('tr')).filter(row =>
                !row.querySelector('.flex-col.items-center') // Excluir fila vacía
            );
            filteredRows = [...allRows];

            // Event listeners para filtros
            document.getElementById('filter-tipo').addEventListener('change', filterTable);
            document.getElementById('filter-estado').addEventListener('change', filterTable);
            document.getElementById('search-input').addEventListener('input', filterTable);
        });

        // Función de filtrado
        function filterTable() {
            const tipoFilter = document.getElementById('filter-tipo').value;
            const estadoFilter = document.getElementById('filter-estado').value;
            const searchTerm = document.getElementById('search-input').value.toLowerCase();

            filteredRows = allRows.filter(row => {
                const tipo = row.getAttribute('data-tipo');
                const activo = row.getAttribute('data-activo');
                const nombre = row.querySelector('td:first-child .text-sm.font-medium').textContent.toLowerCase();

                const tipoMatch = !tipoFilter || tipo === tipoFilter;
                const estadoMatch = !estadoFilter || activo === estadoFilter;
                const searchMatch = !searchTerm || nombre.includes(searchTerm);

                return tipoMatch && estadoMatch && searchMatch;
            });

            updateTable();
        }

        // Función de ordenamiento
        function sortTable(columnIndex) {
            if (currentSortColumn === columnIndex) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = columnIndex;
                currentSortDirection = 'asc';
            }

            filteredRows.sort((a, b) => {
                let aValue, bValue;

                switch (columnIndex) {
                    case 0: // Nombre
                        aValue = a.querySelector('td:first-child .text-sm.font-medium').textContent;
                        bValue = b.querySelector('td:first-child .text-sm.font-medium').textContent;
                        break;
                    case 1: // Tipo
                        aValue = a.getAttribute('data-tipo');
                        bValue = b.getAttribute('data-tipo');
                        break;
                    case 3: // Estado
                        aValue = a.getAttribute('data-activo');
                        bValue = b.getAttribute('data-activo');
                        break;
                    default:
                        return 0;
                }

                if (aValue < bValue) return currentSortDirection === 'asc' ? -1 : 1;
                if (aValue > bValue) return currentSortDirection === 'asc' ? 1 : -1;
                return 0;
            });

            updateTable();
            updateSortIndicators();
        }

        // Actualizar tabla
        function updateTable() {
            const tableBody = document.getElementById('table-body');
            const emptyRow = tableBody.querySelector('.flex-col.items-center');

            // Limpiar tabla
            tableBody.innerHTML = '';

            if (filteredRows.length === 0) {
                // Mostrar mensaje de no resultados
                const emptyCell = document.createElement('tr');
                emptyCell.innerHTML = `
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Sin resultados</h3>
                            <p class="text-gray-500 dark:text-gray-400">No se encontraron plantillas que coincidan con los filtros aplicados.</p>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyCell);
            } else {
                // Agregar filas filtradas
                filteredRows.forEach(row => {
                    tableBody.appendChild(row.cloneNode(true));
                });
            }
        }

        // Actualizar indicadores de ordenamiento
        function updateSortIndicators() {
            // Limpiar todos los indicadores
            document.querySelectorAll('th svg').forEach(svg => {
                svg.style.transform = 'none';
                svg.style.opacity = '0.5';
            });

            // Activar indicador de columna actual
            if (currentSortColumn >= 0) {
                const currentTh = document.querySelectorAll('th')[currentSortColumn];
                const svg = currentTh.querySelector('svg');
                if (svg) {
                    svg.style.opacity = '1';
                    svg.style.transform = currentSortDirection === 'desc' ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        }

        // Función para mostrar preview rápido
        function showQuickPreview(id) {
            // Implementar modal de preview rápido
            window.open(`/cuerpos-correo/${id}`, '_blank');
        }
    </script>
</x-app-layout>
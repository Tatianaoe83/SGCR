<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Cuerpos de Correo</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gestiona las plantillas de correo electrónico del sistema</p>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Filtros -->
                <div class="flex gap-2">
                    <select id="filter-tipo" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los tipos</option>
                        @foreach(\App\Models\CuerpoCorreo::getTipos() as $key => $nombre)
                            <option value="{{ $key }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    
                    <select id="filter-estado" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>

                <!-- Botón de búsqueda -->
                <div class="relative">
                    <input type="text" id="search-input" placeholder="Buscar por nombre..." 
                           class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg pl-10 pr-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Plantillas</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $cuerpos->total() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Activas</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $cuerpos->where('activo', true)->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Inactivas</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $cuerpos->where('activo', false)->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Tipos</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $cuerpos->pluck('tipo')->unique()->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700">
            <header class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Cuerpos de Correo</h2>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>Mostrando {{ $cuerpos->count() }} de {{ $cuerpos->total() }} registros</span>
                    </div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Vista Previa
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable(4)">
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $cuerpo->nombre }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $cuerpo->id_cuerpo }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                            @if($cuerpo->tipo === 'acceso') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @elseif($cuerpo->tipo === 'implementacion') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($cuerpo->tipo === 'fecha_vencimiento') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @elseif($cuerpo->tipo === 'agradecimiento') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                            @endif">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $cuerpo->tipo_nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div class="max-w-xs truncate" title="{{ $cuerpo->subject }}">
                                    {{ $cuerpo->subject ?: 'Sin asunto definido' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="max-w-xs">
                                    <div class="truncate" title="{{ strip_tags($cuerpo->cuerpo_html) }}">
                                        {{ Str::limit(strip_tags($cuerpo->cuerpo_html), 60) }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ strlen(strip_tags($cuerpo->cuerpo_html)) }} caracteres
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($cuerpo->activo) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @endif">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            @if($cuerpo->activo)
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            @else
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            @endif
                                        </svg>
                                        {{ $cuerpo->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('cuerpos-correo.show', $cuerpo->id_cuerpo) }}" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 transition-colors duration-200"
                                       title="Vista previa">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Ver
                                    </a>
                                    <a href="{{ route('cuerpos-correo.edit', $cuerpo->id_cuerpo) }}" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800 transition-colors duration-200"
                                       title="Editar plantilla">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <button onclick="toggleStatus({{ $cuerpo->id_cuerpo }}, {{ $cuerpo->activo ? 'false' : 'true' }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md 
                                                   {{ $cuerpo->activo ? 'text-orange-700 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-200 dark:hover:bg-orange-800' : 'text-green-700 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800' }} transition-colors duration-200"
                                            title="{{ $cuerpo->activo ? 'Desactivar' : 'Activar' }}">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($cuerpo->activo)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            @endif
                                        </svg>
                                        {{ $cuerpo->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
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

                switch(columnIndex) {
                    case 0: // Nombre
                        aValue = a.querySelector('td:first-child .text-sm.font-medium').textContent;
                        bValue = b.querySelector('td:first-child .text-sm.font-medium').textContent;
                        break;
                    case 1: // Tipo
                        aValue = a.getAttribute('data-tipo');
                        bValue = b.getAttribute('data-tipo');
                        break;
                    case 4: // Estado
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
                    <td colspan="6" class="px-6 py-12 text-center">
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

        // Función para cambiar estado
        async function toggleStatus(id, newStatus) {
            if (!confirm(`¿Estás seguro de que quieres ${newStatus ? 'activar' : 'desactivar'} esta plantilla?`)) {
                return;
            }

            try {
                const response = await fetch(`/cuerpos-correo/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ activo: newStatus })
                });

                if (response.ok) {
                    // Recargar la página para actualizar el estado
                    window.location.reload();
                } else {
                    alert('Error al actualizar el estado de la plantilla');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al actualizar el estado de la plantilla');
            }
        }

        // Función para mostrar preview rápido
        function showQuickPreview(id) {
            // Implementar modal de preview rápido
            window.open(`/cuerpos-correo/${id}`, '_blank');
        }
    </script>
</x-app-layout>
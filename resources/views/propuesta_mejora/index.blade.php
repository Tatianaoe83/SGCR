<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 pb-8 w-full max-w-9xl mx-auto">

        <!-- Header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-4 mt-4">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Propuestas de Mejora</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestión y seguimiento de propuestas del sistema de gestión de calidad</p>
            </div>
        </div>

        <!-- Stat Cards -->
        @php
        $total = $propuestas->count();
        $pendientes = $propuestas->where('estatus', 'Pendiente')->count();
        $aprobadas = $propuestas->where('estatus', 'Aprobado')->count();
        $rechazadas = $propuestas->where('estatus', 'Rechazado')->count();
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</p>
                <p class="text-xl font-semibold text-[#021D49] dark:text-gray-100">{{ $total }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pendientes</p>
                <p class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $pendientes }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Aprobadas</p>
                <p class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $aprobadas }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Rechazadas</p>
                <p class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $rechazadas }}</p>
            </div>
        </div>

        <!-- Tabla -->
        <div class="relative bg-white dark:bg-gray-800 shadow-lg rounded-2xl border border-gray-200 dark:border-gray-700 table-container">

            <!-- Toolbar -->
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Propuestas</h2>
                <div class="ui-toolbar sm:w-auto">
                    <select id="filterEstatus" class="ui-select">
                        <option value="">Todos los estatus</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Rechazado">Rechazado</option>
                    </select>
                    <div class="ui-search">
                        <span class="ui-search-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input id="searchInput" type="search" class="ui-search-input" placeholder="Buscar propuesta...">
                    </div>
                </div>
            </div>

            <!-- Loader -->
            <div id="tableLoader" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-gray-800/70 rounded-2xl backdrop-blur-[2px]">
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin w-8 h-8 text-violet-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span class="text-sm text-violet-600 dark:text-violet-400 font-medium">Filtrando...</span>
                </div>
            </div>

            <div class="p-3">
                <div class="overflow-x-auto">
                    <table id="propuestasTable" class="table-auto w-full dataTable">
                        <thead class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left px-2">#</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left px-2">Título</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left px-2">Elemento</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left px-2">Fecha</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left px-2">Estatus</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-center px-2">Acciones</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($propuestas as $propuesta)
                            <tr class="hover:bg-violet-50/40 dark:hover:bg-violet-900/10 transition-colors duration-150">
                                <!-- ID -->
                                <td class="p-3 px-4">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 text-sm font-bold text-violet-700 dark:text-violet-300">
                                        {{ $propuesta->id_propuesta }}
                                    </span>
                                </td>
                                <!-- Título -->
                                <td class="p-3 px-4 text-sm">
                                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $propuesta->titulo }}</span>
                                </td>
                                <!-- Elemento -->
                                <td class="p-3 px-4 text-gray-600 dark:text-gray-400 text-md">
                                    {{ $propuesta->elemento->nombre_elemento ?? '—' }}
                                </td>

                                <!-- Fecha -->
                                <td class="p-3 px-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $propuesta->created_at ? $propuesta->created_at->format('d/m/Y') : '—' }}
                                </td>
                                <!-- Estatus -->
                                <td class="p-3 px-4">
                                    @if($propuesta->estatus === 'Pendiente')
                                    <span class="badge-status badge-warning">Pendiente</span>
                                    @elseif($propuesta->estatus === 'Aprobado')
                                    <span class="badge-status badge-success">Aprobado</span>
                                    @elseif($propuesta->estatus === 'Rechazado')
                                    <span class="badge-status badge-danger">Rechazado</span>
                                    @else
                                    <span class="text-gray-400 text-xs">{{ $propuesta->estatus ?? '—' }}</span>
                                    @endif
                                </td>
                                <!-- Acciones -->
                                <td class="p-3 px-4">
                                    <div class="flex items-center justify-center">
                                        <a href="{{ route('propuestas.revision', $propuesta->id_propuesta) }}"
                                            class="btn-icon-muted"
                                            title="Ver revisión">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <style>
        /* Ocultar controles nativos de DT que reemplazamos */
        #propuestasTable_wrapper .dataTables_filter,
        #propuestasTable_wrapper .dataTables_length {
            display: none !important;
        }

        /* Info y paginación */
        #propuestasTable_wrapper .dataTables_info {
            font-size: 0.8rem;
            color: #9ca3af;
            padding: 0.75rem 1rem;
        }

        #propuestasTable_wrapper .dataTables_paginate {
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: flex-end;
        }

        #propuestasTable_wrapper .dataTables_paginate .paginate_button {
            font-size: 0.8rem;
            color: #6b7280 !important;
            border: 1px solid transparent !important;
            border-radius: 0.5rem !important;
            padding: 0.3rem 0.6rem !important;
            margin: 0 1px;
            cursor: pointer;
            transition: all 0.15s;
        }

        #propuestasTable_wrapper .dataTables_paginate .paginate_button.current,
        #propuestasTable_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: linear-gradient(135deg, #021D49, #021D49) !important;
            border-color: #021D49 !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4) !important;
        }

        #propuestasTable_wrapper .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
            background: #f3f4f6 !important;
            border-color: #e5e7eb !important;
            color: #374151 !important;
        }

        #propuestasTable_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #d1d5db !important;
            cursor: default;
        }

        /* Separador filas */
        #propuestasTable tbody tr td {
            vertical-align: middle;
        }

        #propuestasTable thead th {
            border-bottom: none;
            background: transparent;
        }

        table.dataTable thead th,
        table.dataTable thead td {
            border-bottom: none !important;
        }

        table.dataTable.no-footer {
            border-bottom: none !important;
        }
    </style>

    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script>
        (function() {
            function waitForDT(cb) {
                if (window.jQuery && $.fn.DataTable) cb();
                else setTimeout(() => waitForDT(cb), 100);
            }

            waitForDT(function() {
                var table = $('#propuestasTable').DataTable({
                    responsive: true,
                    pageLength: 8,
                    order: [
                        [0, 'desc']
                    ],
                    columnDefs: [{
                            orderable: false,
                            targets: 5
                        },
                        {
                            searchable: false,
                            targets: [0, 5]
                        }
                    ],
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                    },
                    dom: 'rtip',
                    initComplete: function () {
                        $('#tableLoader').addClass('hidden');
                    },
                });

                function showLoader() {
                    $('#tableLoader').removeClass('hidden');
                }

                function hideLoader() {
                    $('#tableLoader').addClass('hidden');
                }

                // Búsqueda global custom
                var searchTimer;
                $('#searchInput').on('keyup', function() {
                    var val = this.value;
                    showLoader();
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        table.search(val).draw();
                        setTimeout(hideLoader, 180);
                    }, 300);
                });

                // Filtro por estatus (columna 5)
                $('#filterEstatus').on('change', function() {
                    showLoader();
                    var val = this.value;
                    setTimeout(function() {
                        table.column(4).search(val).draw();
                        setTimeout(hideLoader, 180);
                    }, 250);
                });
            });
        })();
    </script>
    @endpush
</x-app-layout>
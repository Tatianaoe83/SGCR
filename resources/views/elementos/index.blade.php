<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Elementos</h1>
            </div>
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('elementos.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Nuevo Elemento</span>
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- Contenedor principal -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Elementos</h2>
                
                <!-- Leyenda del Semáforo -->
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Leyenda del Semáforo:</span>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-500 text-white">Crítico</span>
                        <span class="text-gray-500 dark:text-gray-400">≤ 2 meses</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-500 text-black">Advertencia</span>
                        <span class="text-gray-500 dark:text-gray-400">4-6 meses</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-500 text-white">Normal</span>
                        <span class="text-gray-500 dark:text-gray-400">6-12 meses</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-500 text-white">Lejano</span>
                        <span class="text-gray-500 dark:text-gray-400">> 1 año</span>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- Filtro -->
                <div class="mb-4 flex items-center gap-3">
                    <label for="filtroTipo" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Filtrar por Tipo de Elemento:
                    </label>
                    <select id="filtroTipo" class="form-select w-64 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table id="elementosTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo Elemento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo Proceso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Responsable</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Versión</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periodo Revisión</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Los datos se cargan via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    @push('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <script>
        // Esperar a que todos los scripts estén completamente cargados
        (function() {
            function waitForDataTables(callback, maxAttempts) {
                maxAttempts = maxAttempts || 50; // Máximo 5 segundos (50 * 100ms)
                var attempts = 0;
                
                function check() {
                    attempts++;
                    if (typeof jQuery !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
                       
                        callback();
                    } else if (attempts < maxAttempts) {
                        setTimeout(check, 100);
                    }   else {
                        //console.error('DataTables no se cargó después de', maxAttempts * 100, 'ms');
                    }
                }
                check();
            }
            
            function initializeTable() {
                
                // Verificar que jQuery esté disponible
                if (typeof jQuery === 'undefined') {
                    console.error('jQuery no está disponible');
                    return;
                }
                
              
                // Verificar que la tabla existe
                if ($('#elementosTable').length === 0) {
                    console.error('No se encuentra la tabla con ID elementosTable');
                    return;
                }
                
                // Inicializar DataTable
                var tabla = $('#elementosTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('elementos.data') }}",
                    type: 'GET',
                    data: function(d) {
                        d.tipo = $('#filtroTipo').val() || '';
                       
                        return d;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error AJAX:', error);
                        console.error('Status:', xhr.status);
                        console.error('Status Text:', xhr.statusText);
                        console.error('Respuesta:', xhr.responseText);
                        console.error('Thrown:', thrown);
                        alert('Error al cargar los datos. Revisa la consola para más detalles.');
                    }
                },
                columns: [
                    { 
                        data: 'nombre_elemento', 
                        name: 'nombre_elemento',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'tipo', 
                        orderable: false, 
                        searchable: false,
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'proceso', 
                        orderable: false, 
                        searchable: false,
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'responsable', 
                        orderable: false, 
                        searchable: false,
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'version_elemento', 
                        name: 'version_elemento',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'periodo_revision', 
                        name: 'periodo_revision',
                        orderable: true,
                        searchable: false,
                        defaultContent: 'Sin fecha'
                    },
                    { 
                        data: 'estado', 
                        orderable: false, 
                        searchable: false,
                        defaultContent: '-'
                    },
                    { 
                        data: 'acciones', 
                        orderable: false, 
                        searchable: false,
                        defaultContent: '-'
                    }
                ],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
                    processing: "Procesando...",
                    loadingRecords: "Cargando...",
                    zeroRecords: "No se encontraron elementos",
                    emptyTable: "No hay datos disponibles en la tabla",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ elementos",
                    infoEmpty: "Mostrando 0 a 0 de 0 elementos",
                    infoFiltered: "(filtrado de _MAX_ elementos totales)"
                },
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
            });
            
        
            // Filtro por tipo
            $('#filtroTipo').on('change', function() {
             
                tabla.ajax.reload();
            });
            }
            
            // Esperar a que DataTables esté disponible y luego inicializar
            waitForDataTables(initializeTable);
        })();
    </script>
    @endpush

    <style>
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }
        
        .dark .dataTables_wrapper .dataTables_filter input {
            background-color: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }
        
        .dark .dataTables_wrapper .dataTables_length select {
            background-color: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 0.375rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #8b5cf6;
            color: white;
        }
        
        .dataTables_wrapper .dataTables_processing {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
        }
        
        .dark .dataTables_wrapper .dataTables_processing {
            background: rgba(31, 41, 55, 0.9);
            border-color: #4b5563;
        }
    </style>
</x-app-layout>

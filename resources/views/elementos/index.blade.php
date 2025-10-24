<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11 ">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Elementos</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('puestos-trabajo.export') }}" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Exportar Excel</span>
                </a>

                <a href="{{ route('elementos.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Nuevo Elemento</span>
                </a>
            </div>

        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 table-container">
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

            <div class="p-3">
                <div class="flex flex-wrap items-center justify-center mb-4 gap-3">
                    <label for="filtroTipo" class="text-gray-700 dark:text-gray-300 font-medium">Filtrado por Tipo de Elemento</label>
                    <select id="filtroTipo"
                        class="form-select w-52 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- DataTable -->
                <div class="overflow-x-auto">
                    <table id="elementosTable" class="table-auto w-full dataTable rounded-xl shadow-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo Elemento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo Proceso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Responsable</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Versión</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periodo Revisión</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Semáforo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Estilos globales para DataTables con soporte dark */
        .dataTables_wrapper {
            font-family: 'Inter', sans-serif;
        }

        /* Contenedor principal */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Tabla principal */
        .dataTable {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .dataTable thead th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 2px solid #e2e8f0;
            color: #374151;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 0.75rem;
            position: relative;
        }

        .dataTable tbody tr {
            transition: all 0.2s ease-in-out;
            border-bottom: 1px solid #f1f5f9;
        }

        .dataTable tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .dataTable tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Paginación mejorada */
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1.5rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #6b7280 !important;
            border: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%) !important;
            color: #374151 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            color: white !important;
            border-color: #8b5cf6;
            box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3), 0 2px 4px -1px rgba(139, 92, 246, 0.2);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        }

        /* Campo de búsqueda */
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            font-size: 0.875rem;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Selector de registros por página */
        .dataTables_wrapper .dataTables_length select {
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            font-size: 0.875rem;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .dataTables_wrapper .dataTables_length select:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        /* Información de la tabla */
        .dataTables_wrapper .dataTables_info {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 1rem;
        }

        /* Botones de acción mejorados */
        .dataTable .btn {
            transition: all 0.2s ease-in-out;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .dataTable .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Modo oscuro */
        .dark .dataTables_wrapper .dataTables_length,
        .dark .dataTables_wrapper .dataTables_filter,
        .dark .dataTables_wrapper .dataTables_info,
        .dark .dataTables_wrapper .dataTables_processing,
        .dark .dataTables_wrapper .dataTables_paginate {
            color: #9ca3af;
        }

        .dark .dataTable thead th {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            border-bottom: 2px solid #4b5563;
            color: #d1d5db;
        }

        .dark .dataTable tbody tr {
            border-bottom: 1px solid #374151;
        }

        .dark .dataTable tbody tr:hover {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        }

        .dark .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #9ca3af !important;
            border: 1px solid #4b5563;
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        }

        .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%) !important;
            color: #d1d5db !important;
        }

        .dark .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #4b5563;
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            color: #d1d5db;
        }

        .dark .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #8b5cf6;
        }

        .dark .dataTables_wrapper .dataTables_length select {
            border: 2px solid #4b5563;
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            color: #d1d5db;
        }

        .dark .dataTables_wrapper .dataTables_length select:focus {
            border-color: #8b5cf6;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: center;
                margin-bottom: 1rem;
            }

            .dataTables_wrapper .dataTables_paginate {
                text-align: center;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.5rem 0.75rem;
                margin: 0 0.125rem;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dataTable tbody tr {
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Scroll personalizado */
        .dataTables_wrapper .dataTables_scroll {
            border-radius: 0.75rem;
            overflow: hidden;
        }

        /* Estilos para el contenedor de la tabla */
        .table-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .dark .table-container {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        }

        /* Estilos específicos para la tabla de tipos de elementos */
        #tipoElementosTable {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        #tipoElementosTable thead th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 2px solid #e2e8f0;
            color: #374151;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 0.75rem;
            position: relative;
        }

        #tipoElementosTable tbody tr {
            transition: all 0.2s ease-in-out;
            border-bottom: 1px solid #f1f5f9;
        }

        #tipoElementosTable tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        #tipoElementosTable tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Estilos para las filas expandibles de campos requeridos */
        #tipoElementosTable tbody tr[id^="campos-requeridos-"] {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 4px solid #3b82f6;
        }

        #tipoElementosTable tbody tr[id^="campos-requeridos-"] td {
            padding: 1.5rem;
        }

        /* Estilos para los botones de acción */
        #tipoElementosTable .btn-action {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            margin: 0 0.25rem;
        }

        #tipoElementosTable .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Estilos para los checkboxes de campos requeridos */
        #tipoElementosTable input[type="checkbox"] {
            transform: scale(1.2);
            margin-right: 0.5rem;
        }

        /* Estilos para el botón "Ver Campos" */
        #tipoElementosTable .btn-ver-campos {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }

        #tipoElementosTable .btn-ver-campos:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        /* Estilos para los botones de marcar/desmarcar */
        #tipoElementosTable .btn-marcar-todos {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }

        #tipoElementosTable .btn-marcar-todos:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }

        #tipoElementosTable .btn-desmarcar-todos {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }

        #tipoElementosTable .btn-desmarcar-todos:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);
        }

        /* Estilos para el botón guardar */
        #tipoElementosTable .btn-guardar {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }

        #tipoElementosTable .btn-guardar:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        /* Estilos para el botón cerrar */
        #tipoElementosTable .btn-cerrar {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }

        #tipoElementosTable .btn-cerrar:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.3);
        }

        /* Modo oscuro */
        .dark #tipoElementosTable thead th {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            border-bottom: 2px solid #4b5563;
            color: #d1d5db;
        }

        .dark #tipoElementosTable tbody tr {
            border-bottom: 1px solid #374151;
        }

        .dark #tipoElementosTable tbody tr:hover {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        }

        .dark #tipoElementosTable tbody tr[id^="campos-requeridos-"] {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            border-left: 4px solid #60a5fa;
        }

        #modalGestionCampos {
            backdrop-filter: blur(4px);
        }

        #modalGestionCampos .bg-gray-500 {
            backdrop-filter: blur(2px);
        }

        #modalGestionCampos .bg-white,
        #modalGestionCampos .dark .bg-gray-800 {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        #modal-content input[type="checkbox"] {
            transform: scale(1.2);
            margin-right: 0.75rem;
            transition: all 0.2s ease-in-out;
        }

        #modal-content input[type="checkbox"]:checked {
            transform: scale(1.3);
        }

        #modal-content input[type="checkbox"]:focus {
            ring: 2px;
            ring-color: #3b82f6;
            ring-offset: 2px;
        }

        #modal-content .bg-gray-50,
        #modal-content .dark .bg-gray-700 {
            transition: all 0.2s ease-in-out;
            border: 1px solid transparent;
        }

        #modal-content .bg-gray-50:hover,
        #modal-content .dark .bg-gray-700:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        #modalGestionCampos button {
            transition: all 0.2s ease-in-out;
        }

        #modalGestionCampos button:hover {
            transform: translateY(-1px);
        }

        #modalGestionCampos button:active {
            transform: translateY(0);
        }

        /* Scrollbar personalizado para el modal */
        #modal-content .overflow-y-auto {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        #modal-content .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }

        #modal-content .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        #modal-content .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        #modal-content .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .dark #modal-content .overflow-y-auto {
            scrollbar-color: #475569 #374151;
        }

        .dark #modal-content .overflow-y-auto::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark #modal-content .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #475569;
        }

        .dark #modal-content .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        #modalGestionCampos:not(.hidden) .inline-block {
            animation: modalSlideIn 0.3s ease-out;
        }

        .notification-slide-in {
            animation: slideInRight 0.3s ease-out;
        }

        .notification-slide-out {
            animation: slideOutRight 0.3s ease-in;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    @push('scripts')
    <script>
        $(document).ready(function() {
            const tabla = $('#elementosTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('elementos.data') }}",
                    data: function(d) {
                        d.tipo = $('#filtroTipo').val();
                    }
                },
                layout: {
                    topStart: 'pageLength',
                    topEnd: 'search',
                    bottomStart: 'info',
                    bottomEnd: 'paging',
                },
                stripeClasses: [],
                autoWidth: false,
                columns: [{
                        data: 'nombre_elemento',
                        name: 'nombre_elemento'
                    },
                    {
                        data: 'tipo',
                        name: 'tipoElemento.nombre'
                    },
                    {
                        data: 'proceso',
                        name: 'tipoProceso.nombre'
                    },
                    {
                        data: 'responsable',
                        name: 'puestoResponsable.nombre'
                    },
                    {
                        data: 'version_elemento',
                        name: 'version_elemento'
                    },
                    {
                        data: 'periodo_revision',
                        name: 'periodo_revision'
                    },
                    {
                        data: 'estado',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'acciones',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
                },
                order: [
                    [0, 'asc']
                ],
            });

            $('#filtroTipo').on('change', function() {
                tabla.ajax.reload();
            });
        });
    </script>
    @endpush

</x-app-layout>
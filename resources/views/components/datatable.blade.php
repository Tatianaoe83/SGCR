@props(['tableId', 'columns' => [], 'orderColumn' => 0, 'orderDirection' => 'desc', 'pageLength' => 10])

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

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

<script>
    $(document).ready(function() {
        try {
            $('#{{ $tableId }}').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"ip>',
                pageLength: {{ $pageLength }},
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                order: [[{{ $orderColumn }}, '{{ $orderDirection }}']],
                columnDefs: [
                    {
                        targets: -1, // Última columna (acciones)
                        orderable: false,
                        searchable: false
                    }
                ],
                initComplete: function() {
                    // Agregar clases personalizadas a los elementos del DataTable
                    $('.dataTables_length select').addClass('form-select');
                    $('.dataTables_filter input').addClass('form-input');
                }
            });
        } catch (error) {
            console.error('Error inicializando DataTable para {{ $tableId }}:', error);
        }
    });
</script>

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
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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
</style> 
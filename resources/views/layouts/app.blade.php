<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('images/calidad-de-la-pagina.png') }}" type="image/x-icon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

        <!-- jQuery (requerido para Select2) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Editor Visual CSS -->
        <link href="{{ asset('css/editor-visual.css') }}" rel="stylesheet">

        <!-- Model Viewer para modelos 3D -->
        <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>

        <!-- Styles -->
        @livewireStyles        

        <script>
            // Configurar modo oscuro por defecto
            if (!('dark-mode' in localStorage)) {
                localStorage.setItem('dark-mode', 'true');
            }
            
            // Aplicar modo oscuro inmediatamente para evitar flash
            if (localStorage.getItem('dark-mode') === 'true') {
                document.documentElement.classList.add('dark');
                document.documentElement.style.colorScheme = 'dark';
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.colorScheme = 'light';
            }
        </script>
    </head>
    <body
        class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 {{ request()->routeIs('dashboard') ? 'overflow-hidden h-screen' : '' }}"
        :class="{ 'sidebar-expanded': sidebarExpanded }"
        x-data="{ sidebarOpen: false, sidebarExpanded: localStorage.getItem('sidebar-expanded') == 'true' }"
        x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value))"    
    >

        <script>
            if (localStorage.getItem('sidebar-expanded') == 'true') {
                document.querySelector('body').classList.add('sidebar-expanded');
            } else {
                document.querySelector('body').classList.remove('sidebar-expanded');
            }
        </script>

        <!-- Page wrapper -->
        @php $isDashboard = request()->routeIs('dashboard'); @endphp
        <div class="flex {{ $isDashboard ? 'h-screen overflow-hidden' : 'min-h-screen' }}">

            <x-app.sidebar :variant="$attributes['sidebarVariant']" />

            <!-- Content area -->
            <div class="relative flex flex-col flex-1 lg:ml-64 xl:ml-72 {{ $isDashboard ? 'min-w-0 overflow-hidden' : 'overflow-y-auto overflow-x-hidden' }} @if($attributes['background']){{ $attributes['background'] }}@endif">
                <x-app.header :variant="$attributes['headerVariant']" />
                <main class="flex-1 min-h-0 {{ $isDashboard ? 'overflow-hidden flex flex-col' : '' }}">
                    {{ $slot }}
                </main>

            </div>
        </div>

        @livewireScriptConfig

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Confirmacion de acciones destructivas con SweetAlert2.
            // Cualquier form con [data-confirm] muestra el dialogo en lugar del confirm() nativo.
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
                    return;
                }
                if (form.dataset.confirmed === 'true') {
                    return;
                }
                e.preventDefault();

                const isDark = document.documentElement.classList.contains('dark');

                Swal.fire({
                    title: form.dataset.confirmTitle || '¿Estás seguro?',
                    text: form.getAttribute('data-confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: form.dataset.confirmButton || 'Sí',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: false,
                    background: isDark ? '#1f2937' : '#ffffff',
                    color: isDark ? '#e5e7eb' : '#374151',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            }, true);
        </script>

        @stack('scripts')
    </body>
</html>
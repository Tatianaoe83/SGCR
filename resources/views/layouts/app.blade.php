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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

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
            // Preferencia: dark-mode (legado) + sgc-theme (propuesta)
            if (!('dark-mode' in localStorage)) {
                const sgc = localStorage.getItem('sgc-theme');
                localStorage.setItem('dark-mode', sgc === 'day' ? 'false' : 'true');
            }
            const isNight = localStorage.getItem('dark-mode') === 'true';
            const root = document.documentElement;
            if (isNight) {
                root.classList.add('dark');
                root.setAttribute('data-theme', 'night');
                root.style.colorScheme = 'dark';
            } else {
                root.classList.remove('dark');
                root.setAttribute('data-theme', 'day');
                root.style.colorScheme = 'light';
            }
        </script>
    </head>
    <body
        class="font-inter antialiased {{ request()->routeIs('dashboard') ? 'overflow-hidden h-screen' : '' }}"
        style="background-color: var(--bg); color: var(--text-2);"
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
            <div class="relative sgc-content flex flex-col flex-1 {{ $isDashboard ? 'min-w-0 overflow-hidden' : 'overflow-y-auto overflow-x-hidden' }} @if($attributes['background']){{ $attributes['background'] }}@endif">
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

        <script>
            // Mensajes flash de sesion como toast de SweetAlert2.
            document.addEventListener('DOMContentLoaded', function () {
                const flash = {!! json_encode(['success' => session('success'), 'error' => session('error'), 'warning' => session('warning'), 'info' => session('info')]) !!};

                const iconos = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
                const tipo = Object.keys(iconos).find(function (k) { return flash[k]; });
                if (!tipo) {
                    return;
                }

                const isDark = document.documentElement.classList.contains('dark');

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: iconos[tipo],
                    title: flash[tipo],
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: isDark ? '#1f2937' : '#ffffff',
                    color: isDark ? '#e5e7eb' : '#374151',
                });
            });
        </script>

        @stack('scripts')
    </body>
</html>
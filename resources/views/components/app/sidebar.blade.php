<div class="min-w-fit" x-data="{ 
    activeSection: @if(Route::is('divisions.*') || Route::is('unidades-negocios.*') || Route::is('area.*'))'empresa'@elseif(Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*'))'sgc'@elseif(Route::is('users.*') || Route::is('roles.*') || Route::is('permissions.*'))'usuarios'@elseif(Route::is('puestos-trabajo.*') || Route::is('empleados.*') || Route::is('matriz.*'))'usuarios'@elseif(Route::is('cuerpos-correo.*'))'sgc'@else'dashboard'@endif,
    secondaryMenu: {
        dashboard: [],
        empresa: ['Divisiones', 'Unidades de negocios', 'Areas'],
        sgc: ['Tipo de elementos', 'Tipo de procesos', 'Elementos', 'Cuerpos de correo'],
        usuarios: ['Puestos de trabajo', 'Empleados','Usuarios','Matriz de responsabilidades', 'Roles', 'Permisos']
    }
}">
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-gray-900/30 dark:bg-gray-900/50 z-40 lg:hidden transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        @click="sidebarOpen = false"
        aria-hidden="true"
        x-cloak></div>

    <!-- Sidebar lateral -->
    <aside
        id="sidebar"
        class="fixed top-0 left-0 bottom-0 z-50 w-64 lg:w-72 bg-gradient-to-b from-purple-700 via-purple-800 to-purple-900 dark:from-purple-800 dark:via-purple-900 dark:to-purple-950 transition-transform duration-300 ease-in-out shadow-2xl overflow-y-auto no-scrollbar"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false">

        <!-- Sidebar Header -->
        <div class="sticky top-0 z-10 bg-gradient-to-r from-purple-700 via-purple-800 to-purple-900 dark:from-purple-800 dark:via-purple-900 dark:to-purple-950 border-b border-purple-600/30 dark:border-purple-700/30 px-4 py-4">
            <div class="flex items-center justify-between">
                    <a class="block group" href="{{ route('dashboard') }}">
                    <div class="flex items-center space-x-2">
                        <img src="{{ asset('images/Logo-blanco.png') }}" alt="Logo de la aplicación" class="dark:block hidden transition-transform duration-300 group-hover:scale-105 w-32 h-8" style="filter: brightness(1.2);">
                        <img src="{{ asset('images/Logo-blanco.png') }}" alt="Logo de la aplicación" class="block dark:hidden transition-transform duration-300 group-hover:scale-105 w-40 h-10">
                        </div>
                    </a>
                <!-- Close button (mobile only) -->
                <button class="lg:hidden text-white hover:text-gray-200 dark:hover:text-white/80 transition-all duration-300" @click.stop="sidebarOpen = false" aria-label="Cerrar menú">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
            </div>
                </div>

        <!-- Sidebar Navigation -->
        <nav class="px-3 py-4 space-y-1">
            <!-- Dashboard -->
            @php
                $isDashboardActive = in_array(Request::segment(1), ['dashboard']);
            @endphp
            <a class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-white bg-white/20 dark:bg-white/15 hover:bg-white/25 dark:hover:bg-white/20 transition-all duration-200 @if($isDashboardActive){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-lg' }}@endif"
                            href="{{ route('dashboard') }}"
                @click="activeSection = 'dashboard'; sidebarOpen = false">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                            </svg>
                <span>Dashboard</span>
                        </a>

            <!-- Estructura de la empresa -->
                        @canany([
                            'divisions.view', 'divisions.create', 'divisions.edit', 'divisions.delete',
                            'unidades-negocios.view', 'unidades-negocios.create', 'unidades-negocios.edit', 'unidades-negocios.delete',
                            'areas.view', 'areas.create', 'areas.edit', 'areas.delete'
                        ])
                @php
                    $isEmpresaActive = in_array(Request::segment(1), ['divisions', 'unidades-negocios', 'area']);
                @endphp
                <div x-data="{ open: {{ $isEmpresaActive ? 'true' : 'false' }} }">
                    <button class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-200 @if($isEmpresaActive){{ 'bg-white/20 text-white' }}@endif"
                        @click="activeSection = 'empresa'; open = !open">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                            <span>Estructura de la empresa</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                            </button>
                    <div x-show="open" x-collapse class="mt-1 ml-4 space-y-1">
                        @canany(['divisions.view', 'divisions.create', 'divisions.edit', 'divisions.delete'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('divisions.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('divisions.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">División</span>
                            </a>
                        @endcanany
                        @canany(['unidades-negocios.view', 'unidades-negocios.create', 'unidades-negocios.edit', 'unidades-negocios.delete'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('unidades-negocios.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('unidades-negocios.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">Unidades de negocios</span>
                            </a>
                        @endcanany
                        @canany(['areas.view', 'areas.create', 'areas.edit', 'areas.delete'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('area.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('area.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">Áreas</span>
                            </a>
                        @endcanany
                    </div>
                </div>
                        @endcanany

            <!-- Usuarios -->
                        @canany([
                            'puestos-trabajo.view', 'puestos-trabajo.create', 'puestos-trabajo.edit', 'puestos-trabajo.delete',
                            'empleados.view', 'empleados.create', 'empleados.edit', 'empleados.delete',
                            'users.view', 'users.create', 'users.edit', 'users.delete',
                            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete'
                        ])
                @php
                    $isUsuariosActive = Route::is('puestos-trabajo.*') || Route::is('empleados.*') || Route::is('users.*') || Route::is('roles.*') || Route::is('permissions.*') || Route::is('matriz.*');
                @endphp
                <div x-data="{ open: {{ $isUsuariosActive ? 'true' : 'false' }} }">
                    <button class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-200 @if($isUsuariosActive){{ 'bg-white/20 text-white' }}@endif"
                        @click="activeSection = 'usuarios'; open = !open">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 0 1 6 0zM18 8a2 2 0 11-4 0 2 2 0 0 1 4 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                <span>Usuarios</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-1 ml-4 space-y-1">
                        @canany(['puestos-trabajo.view', 'puestos-trabajo.create', 'puestos-trabajo.edit', 'puestos-trabajo.delete'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('puestos-trabajo.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('puestos-trabajo.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">Puestos de Trabajo</span>
                            </a>
                        @endcanany
                        @canany(['empleados.view', 'empleados.create', 'empleados.edit', 'empleados.delete'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('empleados.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('empleados.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">Empleados</span>
                            </a>
                        @endcanany
                        @canany(['users.view', 'users.create', 'users.edit', 'users.delete'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('users.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('users.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">Usuarios</span>
                            </a>
                        @endcanany
                        @canany(['puestos-trabajo.view', 'empleados.view'])
                            <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('matriz.*')){{ 'bg-white/20 text-white' }}@endif"
                                href="{{ route('matriz.index') }}"
                                @click="sidebarOpen = false">
                                <span class="ml-8">Matriz de Responsabilidades</span>
                            </a>
                        @endcanany
                    </div>
                            </div>
                            @endcanany

            <!-- Estructura de la SGC -->
            @can('sgc.access')
                @php
                    $isSgcActive = Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*') || Route::is('cuerpos-correo.*');
                @endphp
                <div x-data="{ open: {{ $isSgcActive ? 'true' : 'false' }} }">
                    <button class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-200 @if($isSgcActive){{ 'bg-white/20 text-white' }}@endif"
                        @click="activeSection = 'sgc'; open = !open">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                            <span>Estructura de la SGC</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-1 ml-4 space-y-1">
                        <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('tipo-elementos.*')){{ 'bg-white/20 text-white' }}@endif"
                            href="{{ route('tipo-elementos.index') }}"
                            @click="sidebarOpen = false">
                            <span class="ml-8">Tipo de Elementos</span>
                        </a>
                        <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('tipoProceso.*')){{ 'bg-white/20 text-white' }}@endif"
                            href="{{ route('tipoProceso.index') }}"
                            @click="sidebarOpen = false">
                            <span class="ml-8">Tipo de Proceso</span>
                        </a>
                        <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('elementos.*')){{ 'bg-white/20 text-white' }}@endif"
                            href="{{ route('elementos.index') }}"
                            @click="sidebarOpen = false">
                            <span class="ml-8">Elementos</span>
                        </a>
                        <a class="flex items-center px-3 py-2 rounded-lg text-sm text-purple-100 hover:text-white hover:bg-white/15 dark:hover:bg-white/20 transition-all duration-200 @if(Route::is('cuerpos-correo.*')){{ 'bg-white/20 text-white' }}@endif"
                            href="{{ route('cuerpos-correo.index') }}"
                            @click="sidebarOpen = false">
                            <span class="ml-8">Cuerpos de Correo</span>
                        </a>
                    </div>
                </div>
            @endcan
        </nav>
    </aside>
</div>

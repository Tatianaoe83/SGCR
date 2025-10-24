<div class="min-w-fit" x-data="{ 
    sidebarOpen: false,
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
        class="fixed inset-0 bg-gray-900/30 dark:bg-gray-900/50 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak></div>

    <!-- Main Header with Integrated Secondary Navigation -->
    <div
        id="sidebar"
        class="fixed top-0 left-0 right-0 z-40 bg-gradient-to-r from-purple-700 via-purple-800 to-purple-900 dark:from-purple-800 dark:via-purple-900 dark:to-purple-950 transition-all duration-500 ease-in-out shadow-2xl backdrop-blur-lg bg-opacity-95 dark:bg-opacity-95"
        :class="sidebarOpen ? 'max-lg:translate-y-0' : 'max-lg:-translate-y-full'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false">

        <!-- Main Header Container -->
        <div class="px-6 py-4">

            <!-- Top Row: Logo, Navigation, and User Info -->
            <div class="flex items-center justify-between mb-4">

                <!-- Logo and Brand -->
                <div class="flex items-center space-x-6">
                    <a class="block group" href="{{ route('dashboard') }}">
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('images/Logo-blanco.png') }}" alt="Logo de la aplicación" class="dark:block hidden transition-transform duration-300 group-hover:scale-105" {{ $attributes }} style="width: 180px; height: 40px; filter: brightness(1.2);">
                            <img src="{{ asset('images/Logo-blanco.png') }}" alt="Logo de la aplicación" class="block dark:hidden transition-transform duration-300 group-hover:scale-105" {{ $attributes }} style="width: 180px; height: 40px;">
                        </div>
                    </a>

                    <!-- Main Navigation Links -->
                    <div class="flex items-center space-x-3">
                        <a class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 shadow-lg hover:shadow-purple-500/20 @if(in_array(Request::segment(1), ['dashboard'])){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30 scale-105' }}@endif"
                            href="{{ route('dashboard') }}"
                            @click="activeSection = 'dashboard'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        <button class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 hover:scale-105 shadow-lg @if(in_array(Request::segment(1), ['divisions', 'unidades-negocios', 'area'])){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@endif"
                            @click="activeSection = 'empresa'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                            <span>Estructura de la empresa</span>
                        </button>

                        <button class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 hover:scale-105 shadow-lg @if(Route::is('puestos-trabajo.*') || Route::is('empleados.*') || Route::is('users.*') || Route::is('roles.*') || Route::is('permissions.*')){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@elseif(Route::is('matriz.*')){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@endif"
                            @click="activeSection = 'usuarios'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 0 1 6 0zM18 8a2 2 0 11-4 0 2 2 0 0 1 4 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                            </svg>
                            <span>Usuarios</span>
                        </button>

                        <button class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 hover:scale-105 shadow-lg @if(Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*') || Route::is('cuerpos-correo.*')){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@endif"
                            @click="activeSection = 'sgc'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0 1 18 0z" />
                            </svg>
                            <span>Estructura de la SGC</span>
                        </button>
                    </div>
                </div>

                <!-- Header Elements (Right Side) -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications button -->
                    <div class="relative">
                        <x-dropdown-notifications align="right" />
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse shadow-lg"></div>
                    </div>

                    <!-- Dark mode toggle -->
                    <div class="relative">
                        <x-theme-toggle />
                    </div>

                    <!-- User button -->
                    <x-dropdown-profile align="right" />

                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden text-white hover:text-gray-200 dark:hover:text-white/80 transition-all duration-300 hover:scale-110" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                        <span class="sr-only">Toggle menu</span>
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Secondary Navigation Row -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <!-- Dashboard Section -->
                    <template x-if="activeSection === 'dashboard'">
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center space-x-2 bg-white/25 dark:bg-white/15 rounded-xl px-4 py-2 backdrop-blur-sm border border-white/30 shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                </svg>
                                <span class="text-white font-medium">Dashboard</span>
                            </div>
                        </div>
                    </template>

                    <!-- Estructura de la empresa Section -->
                    <template x-if="activeSection === 'empresa'">
                        <div class="flex items-center space-x-4">
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('divisions.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('divisions.index') }}"
                                @click="activeSection = 'empresa'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                </svg>
                                <span>División</span>
                            </a>
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('unidades-negocios.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('unidades-negocios.index') }}"
                                @click="activeSection = 'empresa'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                <span>Unidades de negocios</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('area.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('area.index') }}"
                                @click="activeSection = 'empresa'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                <span>Áreas</span>
                            </a>


                        </div>
                    </template>

                    <!-- Estructura de la SGC Section -->
                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center space-x-4">
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('tipo-elementos.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('tipo-elementos.index') }}"
                                @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                </svg>
                                <span>Tipo de Elementos</span>
                            </a>
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('tipoProceso.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('tipoProceso.index') }}"
                                @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                <span>Tipo de Proceso</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('elementos.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('elementos.index') }}"
                                @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                <span>Elementos</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('cuerpos-correo.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('cuerpos-correo.index') }}"
                                @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                <span>Cuerpos de Correo</span>
                            </a>


                        </div>
                    </template>

                    <!-- Usuarios Section -->

                    <template x-if="activeSection === 'usuarios'">
                        <div class="flex items-center space-x-4">
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('puestos-trabajo.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30 scale-105' }}@endif"
                                href="{{ route('puestos-trabajo.index') }}"
                                @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 0 1 6 0zM18 8a2 2 0 11-4 0 2 2 0 0 1 4 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                <span>Puestos de Trabajo</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('empleados.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30 scale-105' }}@endif"
                                href="{{ route('empleados.index') }}"
                                @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 0 1 6 0zM18 8a2 2 0 11-4 0 2 2 0 0 1 4 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                <span>Empleados</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('users.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('users.index') }}"
                                @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 0 1 6 0zM18 8a2 2 0 11-4 0 2 2 0 0 1 4 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                <span>Usuarios</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('matriz.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('matriz.index') }}"
                                @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 0 1 6 0zM18 8a2 2 0 11-4 0 2 2 0 0 1 4 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                <span>Matriz de Responsabilidades</span>
                            </a>

                            <!-- <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('roles.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('roles.index') }}"
                                @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>
                                <span>Roles</span>
                            </a>
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('permissions.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif"
                                href="{{ route('permissions.index') }}"
                                @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                                <span>Permisos</span>
                            </a> -->
                        </div>

                    </template>
                </div>

                <!-- Breadcrumb or additional info -->
                <!--<div class="flex items-center space-x-2 text-white/70 text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>SGCR</span>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="activeSection.charAt(0).toUpperCase() + activeSection.slice(1)"></span>
                </div>-->

            </div>
        </div>
    </div>

    <!-- Spacer to push content down -->
    <div class="h-32"></div>
</div>
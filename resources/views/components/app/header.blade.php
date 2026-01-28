<header class="sticky top-0 before:absolute before:inset-0 before:backdrop-blur-md before:bg-white/90 dark:before:bg-gray-800/90 before:-z-10 z-30 shadow-sm border-b border-gray-200 dark:border-gray-700/60"
    x-data="{
    activeSection:
        @if(Route::is('divisions.*') || Route::is('unidades-negocios.*') || Route::is('area.*'))'empresa'
        @elseif(Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*') || Route::is('cuerpos-correo.*') || Route::is('control-cambios.*'))'sgc'
        @elseif(Route::is('users.*') || Route::is('roles.*') || Route::is('permissions.*') || Route::is('puestos-trabajo.*') || Route::is('empleados.*') || Route::is('matriz.*'))'usuarios'
        @else
            'dashboard'
        @endif
    }">
    <div class="px-4 sm:px-6 lg:px-8">
        <!-- Top Row: Hamburger, Secondary Nav, and User Actions -->
        <div class="flex items-center justify-between h-16">
            <!-- Left side: Hamburger and Secondary Navigation -->
            <div class="flex items-center gap-4 flex-1 min-w-0">
                <!-- Hamburger button (mobile only) -->
                <button
                    class="text-gray-500 hover:text-gray-600 dark:hover:text-gray-400 lg:hidden flex-shrink-0"
                    @click.stop="sidebarOpen = !sidebarOpen"
                    aria-controls="sidebar"
                    :aria-expanded="sidebarOpen">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="5" width="16" height="2" />
                        <rect x="4" y="11" width="16" height="2" />
                        <rect x="4" y="17" width="16" height="2" />
                    </svg>
                </button>

                <!-- Secondary Navigation -->
                <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto no-scrollbar flex-1 min-w-0">
                    <!-- Dashboard Section -->
                    <template x-if="activeSection === 'dashboard'">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="flex items-center gap-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg px-3 py-1.5 border border-purple-200 dark:border-purple-800">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                </svg>
                                <span class="text-sm font-medium text-purple-700 dark:text-purple-300 whitespace-nowrap">Dashboard</span>
                            </div>
                        </div>
                    </template>

                    <!-- Estructura de la empresa Section -->
                    <template x-if="activeSection === 'empresa'">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            @canany(['divisions.view', 'divisions.create', 'divisions.edit', 'divisions.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('divisions.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('divisions.index') }}">
                                <span>División</span>
                            </a>
                            @endcanany
                            @canany(['unidades-negocios.view', 'unidades-negocios.create', 'unidades-negocios.edit', 'unidades-negocios.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('unidades-negocios.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('unidades-negocios.index') }}">
                                <span class="hidden sm:inline">Unidades de negocios</span>
                                <span class="sm:hidden">Unidades</span>
                            </a>
                            @endcanany
                            @canany(['areas.view', 'areas.create', 'areas.edit', 'areas.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('area.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('area.index') }}">
                                <span>Áreas</span>
                            </a>
                            @endcanany
                        </div>
                    </template>

                    <!-- Estructura de la SGC Section -->
                    @can('sgc.access')
                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('tipo-elementos.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('tipo-elementos.index') }}">
                                <span class="hidden sm:inline">Tipo de Elementos</span>
                                <span class="sm:hidden">Tipo Elem.</span>
                            </a>
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('tipoProceso.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('tipoProceso.index') }}">
                                <span class="hidden sm:inline">Tipo de Proceso</span>
                                <span class="sm:hidden">Tipo Proc.</span>
                            </a>
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('elementos.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('elementos.index') }}">
                                <span>Elementos</span>
                            </a>
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('cuerpos-correo.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('cuerpos-correo.index') }}">
                                <span class="hidden sm:inline">Cuerpos de Correo</span>
                                <span class="sm:hidden">Cuerpos</span>
                            </a>
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('control-cambios.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('control-cambios.index') }}">
                                <span class="hidden sm:inline">Control de Cambios</span>
                                <span class="sm:hidden">Cambios</span>
                            </a>
                        </div>
                    </template>
                    @endcan

                    <!-- Usuarios Section -->
                    <template x-if="activeSection === 'usuarios'">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            @canany(['puestos-trabajo.view', 'puestos-trabajo.create', 'puestos-trabajo.edit', 'puestos-trabajo.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('puestos-trabajo.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('puestos-trabajo.index') }}">
                                <span class="hidden sm:inline">Puestos de Trabajo</span>
                                <span class="sm:hidden">Puestos</span>
                            </a>
                            @endcanany
                            @canany(['empleados.view', 'empleados.create', 'empleados.edit', 'empleados.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('empleados.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('empleados.index') }}">
                                <span>Empleados</span>
                            </a>
                            @endcanany
                            @canany(['users.view', 'users.create', 'users.edit', 'users.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('users.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('users.index') }}">
                                <span>Usuarios</span>
                            </a>
                            @endcanany
                            @canany(['puestos-trabajo.view', 'empleados.view'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('matriz.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('matriz.index') }}">
                                <span class="hidden sm:inline">Matriz de Responsabilidades</span>
                                <span class="sm:hidden">Matriz</span>
                            </a>
                            @endcanany
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right side: Notifications, Theme Toggle, User -->
            <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                <!-- Notifications button -->
                <x-dropdown-notifications align="right" />

                <!-- Dark mode toggle -->
                <x-theme-toggle />

                <!-- Divider -->
                <hr class="w-px h-6 bg-gray-200 dark:bg-gray-700/60 border-none hidden sm:block" />

                <!-- User button -->
                <x-dropdown-profile align="right" />
            </div>
        </div>
    </div>
</header>
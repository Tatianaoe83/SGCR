<header class="sgc-topbar sticky top-0 shrink-0 z-30"
    x-data="{
    activeSection:
        @if(Route::is('divisions.*') || Route::is('unidades-negocios.*') || Route::is('area.*'))'empresa'
        @elseif(Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*') || Route::is('cuerpos-correo.*') || Route::is('control-cambios.*') || Route::is('propuesta_mejora.*'))'sgc'
        @elseif(Route::is('users.*') || Route::is('roles.*') || Route::is('permissions.*') || Route::is('puestos-trabajo.*') || Route::is('empleados.*') || Route::is('matriz.*'))'usuarios'
        @elseif(Route::is('mapa-procesos.*'))'mapa'
        @else
            'dashboard'
        @endif
    }">
    <style>
        .sgc-topbar {
            background: var(--topbar);
            border-bottom: 1px solid var(--border);
            transition: background .3s, border-color .3s;
        }
        .sgc-topbar-inner {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 26px;
        }
        .sgc-crumb {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            padding: 9px 16px;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text);
        }
        .sgc-crumb svg { color: var(--accent-2); width: 16px; height: 16px; flex-shrink: 0; }
        .sgc-top-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .sgc-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface-2);
            display: grid;
            place-items: center;
            cursor: pointer;
            color: var(--text-2);
            transition: color .16s, border-color .16s, background .16s;
            position: relative;
            flex-shrink: 0;
        }
        .sgc-icon-btn:hover {
            color: var(--text);
            border-color: var(--accent-2);
        }
        .sgc-icon-btn svg {
            width: 18px;
            height: 18px;
        }
        .sgc-icon-btn .sgc-ping {
            position: absolute;
            top: 9px;
            right: 10px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 6px var(--gold);
        }
        .sgc-top-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-2);
            transition: background .16s, color .16s;
            white-space: nowrap;
        }
        .sgc-top-link:hover,
        .sgc-top-link.is-active {
            color: var(--accent);
            background: color-mix(in srgb, var(--accent-2) 12%, transparent);
        }
        @media (max-width: 640px) {
            .sgc-topbar-inner { padding: 0 14px; height: 64px; }
        }
    </style>
    <div class="sgc-topbar-inner">
        <!-- Left side: Hamburger and Secondary Navigation -->
        <div class="flex items-center gap-4 flex-1 min-w-0">
            <!-- Hamburger button (mobile only) -->
            <!-- <button
                class="sgc-icon-btn lg:hidden"
                @click.stop="sidebarOpen = !sidebarOpen"
                aria-controls="sidebar"
                :aria-expanded="sidebarOpen">
                <span class="sr-only">Open sidebar</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>

            <!-- Secondary Navigation -->
            <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto no-scrollbar flex-1 min-w-0">
                <!-- Dashboard Section -->
                <template x-if="activeSection === 'dashboard'">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="sgc-crumb">
                            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                            </svg>
                            <span>Dashboard</span>
                        </div>
                    </div>
                </template>

                    <!-- Mapa de Procesos Section -->
                    <template x-if="activeSection === 'mapa'">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="sgc-crumb">
                                <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linejoin="round" d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>
                                </svg>
                                <span>Mapa de Procesos</span>
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
                    @php
                        $showSgcNav =
                            auth()->check() && (
                                auth()->user()->can('sgc.access') ||
                                auth()->user()->can('tipo-elemento.view') ||
                                auth()->user()->can('tipo-proceso.view') ||
                                auth()->user()->can('elementos.view') ||
                                auth()->user()->can('cuerpo-correo.view')
                            );
                    @endphp
                    @if($showSgcNav)
                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            @canany(['tipo-elemento.view', 'tipo-elemento.create', 'tipo-elemento.edit', 'tipo-elemento.destroy'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('tipo-elementos.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('tipo-elementos.index') }}">
                                <span class="hidden sm:inline">Tipo de Elementos</span>
                                <span class="sm:hidden">Tipo Elem.</span>
                            </a>
                            @endcanany

                            @canany(['tipo-proceso.view', 'tipo-proceso.create', 'tipo-proceso.edit', 'tipo-proceso.delete'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('tipoProceso.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('tipoProceso.index') }}">
                                <span class="hidden sm:inline">Tipo de Proceso</span>
                                <span class="sm:hidden">Tipo Proc.</span>
                            </a>
                            @endcanany

                            @canany(['elementos.view', 'elementos.create', 'elementos.edit', 'elementos.export'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('elementos.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('elementos.index') }}">
                                <span>Elementos</span>
                            </a>
                            @endcanany

                            @canany(['cuerpo-correo.view', 'cuerpo-correo.create', 'cuerpo-correo.edit', 'cuerpo-correo.export'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('cuerpos-correo.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('cuerpos-correo.index') }}">
                                <span class="hidden sm:inline">Cuerpos de Correo</span>
                                <span class="sm:hidden">Cuerpos</span>
                            </a>
                            @endcanany

                            @can(['control-cambios.view', 'control-cambios.edit'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('control-cambios.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('control-cambios.index') }}">
                                <span class="hidden sm:inline">Control de Cambios</span>
                                <span class="sm:hidden">Cambios</span>
                            </a>
                            @endcan

                            @can(['propuesta_mejora.view', 'propuesta_mejora.edit'])
                            <a class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all duration-200 whitespace-nowrap @if(Route::is('propuesta_mejora.*')){{ 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30' }}@endif"
                                href="{{ route('propuesta_mejora.index') }}">
                                <span class="hidden sm:inline">Propuestas de Mejora</span>
                                <span class="sm:hidden">Propuestas</span>
                            </a>
                            @endcan
                        </div>
                    </template>
                    @endif

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
            <div class="sgc-top-right">
                <x-notificaciones-firmas />
                <x-modal-suggets-change-control align="right"/>
                <x-theme-toggle />
                <x-dropdown-profile align="right" />
            </div>
    </div>
</header>
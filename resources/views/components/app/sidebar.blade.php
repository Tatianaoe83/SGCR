<div class="min-w-fit" x-data="{ 
    activeSection: @if(Route::is('mapa-procesos.*'))'mapa'@elseif(Route::is('divisions.*') || Route::is('unidades-negocios.*') || Route::is('area.*'))'empresa'@elseif(Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*') || Route::is('control-cambios.*') || Route::is('propuesta_mejora.*') || Route::is('cuerpos-correo.*'))'sgc'@elseif(Route::is('users.*') || Route::is('roles.*') || Route::is('permissions.*') || Route::is('puestos-trabajo.*') || Route::is('empleados.*') || Route::is('matriz.*'))'usuarios'@else'dashboard'@endif,
    secondaryMenu: {
        dashboard: [],
        empresa: ['Divisiones', 'Unidades de negocios', 'Areas'],
        sgc: ['Tipo de elementos', 'Tipo de procesos', 'Elementos', 'Cuerpos de correo', 'Control de Cambios', 'Propuesta de Mejora'],
        usuarios: ['Puestos de trabajo', 'Empleados','Usuarios','Matriz de responsabilidades', 'Roles', 'Permisos']
    }
}">
    <!-- Sidebar backdrop -->
    <div
        class="fixed inset-0 bg-gray-900/30 dark:bg-gray-900/50 z-20 lg:hidden transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        @click="sidebarOpen = false"
        aria-hidden="true"
        x-cloak></div>

    <!-- Sidebar lateral -->
    <aside
        id="sidebar"
        class="sgc-app-sidebar fixed top-0 left-0 bottom-0 z-30 w-72 max-w-[85vw] flex flex-col transition-transform duration-300 ease-in-out shadow-2xl overflow-hidden"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false">

        <!-- Sidebar Header / Logo (propuesta: brand padding 6px 10px 30px) -->
        <div class="sgc-app-sidebar-brand">
            <div class="flex items-center justify-between">
                <a class="block group" href="{{ route('dashboard') }}">
                    <img
                        src="{{ asset('images/Logo-blanco.png') }}"
                        alt="PROSER Grupo Constructor"
                        class="sgc-logo block object-contain transition-transform duration-300 group-hover:scale-[1.03]"
                        width="158"
                        height="40">
                </a>
                <!-- Close button -->
                <button class="lg:hidden text-white/80 hover:text-white transition-all duration-300" @click.stop="sidebarOpen = false" aria-label="Cerrar menú">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="sgc-nav flex-1 min-h-0 overflow-y-auto no-scrollbar">
            <!-- Dashboard -->
            @php
            $isDashboardActive = in_array(Request::segment(1), ['dashboard']);
            @endphp
            <a class="sgc-nav-item @if($isDashboardActive) is-active @endif"
                href="{{ route('dashboard') }}"
                @click="activeSection = 'dashboard'; sidebarOpen = false">
                <svg class="sgc-nav-ico" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
                <span>Dashboard</span>
            </a>

            <!-- Mapa de Procesos -->
            @php
            $isMapaActive = Route::is('mapa-procesos.*');
            @endphp
            <a class="sgc-nav-item @if($isMapaActive) is-active @endif"
                href="{{ route('mapa-procesos.index') }}"
                @click="activeSection = 'mapa'; sidebarOpen = false">
                <svg class="sgc-nav-ico" viewBox="0 0 24 24" fill="none"><path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 4v14M15 6v14" stroke="currentColor" stroke-width="1.7"/></svg>
                <span>Mapa de Procesos</span>
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
                <button class="sgc-nav-item w-full @if($isEmpresaActive) is-active @endif"
                    @click.stop="activeSection = 'empresa'; open = !open">
                    <svg class="sgc-nav-ico" viewBox="0 0 24 24" fill="none"><path d="M3 21V5l6-2 6 2 6-2v16M3 21h18M9 3v18M15 5v16" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    <span>Estructura de la empresa</span>
                    <svg class="sgc-nav-chev ml-auto transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="open" x-collapse class="mt-1 ml-3 space-y-1">
                    @canany(['divisions.view', 'divisions.create', 'divisions.edit', 'divisions.delete'])
                    <a class="sgc-nav-sub @if(Route::is('divisions.*')) is-active @endif"
                        href="{{ route('divisions.index') }}"
                        @click.stop="sidebarOpen = false">
                        División
                    </a>
                    @endcanany
                    @canany(['unidades-negocios.view', 'unidades-negocios.create', 'unidades-negocios.edit', 'unidades-negocios.delete'])
                    <a class="sgc-nav-sub @if(Route::is('unidades-negocios.*')) is-active @endif"
                        href="{{ route('unidades-negocios.index') }}"
                        @click.stop="sidebarOpen = false">
                        Unidades de negocios
                    </a>
                    @endcanany
                    @canany(['areas.view', 'areas.create', 'areas.edit', 'areas.delete'])
                    <a class="sgc-nav-sub @if(Route::is('area.*')) is-active @endif"
                        href="{{ route('area.index') }}"
                        @click.stop="sidebarOpen = false">
                        Áreas
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
                <button class="sgc-nav-item w-full @if($isUsuariosActive) is-active @endif"
                    @click.stop="activeSection = 'usuarios'; open = !open">
                    <svg class="sgc-nav-ico" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 19a5.5 5.5 0 0111 0M16 6.5a3 3 0 010 6M20.5 19a5 5 0 00-3.5-4.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <span>Usuarios</span>
                    <svg class="sgc-nav-chev ml-auto transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="open" x-collapse class="mt-1 ml-3 space-y-1">
                    @canany(['puestos-trabajo.view', 'puestos-trabajo.create', 'puestos-trabajo.edit', 'puestos-trabajo.delete'])
                    <a class="sgc-nav-sub @if(Route::is('puestos-trabajo.*')) is-active @endif"
                        href="{{ route('puestos-trabajo.index') }}"
                        @click.stop="sidebarOpen = false">
                        Puestos de Trabajo
                    </a>
                    @endcanany
                    @canany(['empleados.view', 'empleados.create', 'empleados.edit', 'empleados.delete','empleados.import','empleados.export'])
                    <a class="sgc-nav-sub @if(Route::is('empleados.*')) is-active @endif"
                        href="{{ route('empleados.index') }}"
                        @click.stop="sidebarOpen = false">
                        Empleados
                    </a>
                    @endcanany
                    @canany(['users.view', 'users.create', 'users.edit', 'users.delete'])
                    <a class="sgc-nav-sub @if(Route::is('users.*')) is-active @endif"
                        href="{{ route('users.index') }}"
                        @click.stop="sidebarOpen = false">
                        Usuarios
                    </a>
                    @endcanany
                    @can('matriz.acceso')
                    <a class="sgc-nav-sub @if(Route::is('matriz.*')) is-active @endif"
                        href="{{ route('matriz.index') }}"
                        @click.stop="sidebarOpen = false">
                        Matriz de Responsabilidades
                    </a>
                    @endcanany
                    @canany(['roles.view', 'roles.create', 'roles.edit', 'roles.delete'])
                    <a class="sgc-nav-sub @if(Route::is('roles.*')) is-active @endif"
                        href="{{ route('roles.index') }}"
                        @click.stop="sidebarOpen = false">
                        Roles
                    </a>
                    @endcanany
                    @canany(['permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete'])
                    <a class="sgc-nav-sub @if(Route::is('permissions.*')) is-active @endif"
                        href="{{ route('permissions.index') }}"
                        @click.stop="sidebarOpen = false">
                        Permisos
                    </a>
                    @endcanany
                </div>
            </div>
            @endcanany

            <!-- Estructura de la SGC -->
            @canany([
            'tipo-elemento.view', 'tipo-elemento.create', 'tipo-elemento.edit', 'tipo-elemento.destroy',
            'tipo-proceso.view', 'tipo-proceso.create', 'tipo-proceso.edit', 'tipo-proceso.delete',
            'elementos.view', 'elementos.create', 'elementos.edit', 'elementos.info',
            'cuerpo-correo.view', 'cuerpo-correo.create', 'cuerpo-correo.edit', 'cuerpo-correo.export',
            'control-cambios.view', 'control-cambios.edit',
            'propuesta_mejora.view', 'propuesta_mejora.edit'
            ])
            @php
            $isSgcActive = Route::is('tipoProceso.*') || Route::is('tipo-elementos.*') || Route::is('elementos.*') || Route::is('cuerpos-correo.*') || Route::is('control-cambios.*') || Route::is('propuesta_mejora.*');
            @endphp
            <div x-data="{ open: {{ $isSgcActive ? 'true' : 'false' }} }">
                <button class="sgc-nav-item w-full @if($isSgcActive) is-active @endif"
                    @click.stop="activeSection = 'sgc'; open = !open">
                    <svg class="sgc-nav-ico" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Estructura de la SGC</span>
                    <svg class="sgc-nav-chev ml-auto transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-show="open" x-collapse class="mt-1 ml-3 space-y-1">
                    @canany(['tipo-elemento.view', 'tipo-elemento.create', 'tipo-elemento.edit', 'tipo-elemento.destroy'])
                    <a class="sgc-nav-sub @if(Route::is('tipo-elementos.*')) is-active @endif"
                        href="{{ route('tipo-elementos.index') }}"
                        @click.stop="sidebarOpen = false">
                        Tipo de Elementos
                    </a>
                    @endcanany
                    @canany(['tipo-proceso.view', 'tipo-proceso.create', 'tipo-proceso.edit', 'tipo-proceso.delete'])
                    <a class="sgc-nav-sub @if(Route::is('tipoProceso.*')) is-active @endif"
                        href="{{ route('tipoProceso.index') }}"
                        @click.stop="sidebarOpen = false">
                        Tipo de Proceso
                    </a>
                    @endcanany
                    @canany(['elementos.view', 'elementos.create', 'elementos.edit', 'elementos.info'])
                    <a class="sgc-nav-sub @if(Route::is('elementos.*')) is-active @endif"
                        href="{{ route('elementos.index') }}"
                        @click.stop="sidebarOpen = false">
                        Elementos
                    </a>
                    @endcanany
                    @canany(['cuerpo-correo.view', 'cuerpo-correo.create', 'cuerpo-correo.edit', 'cuerpo-correo.export'])
                    <a class="sgc-nav-sub @if(Route::is('cuerpos-correo.*')) is-active @endif"
                        href="{{ route('cuerpos-correo.index') }}"
                        @click.stop="sidebarOpen = false">
                        Cuerpos de Correo
                    </a>
                    @endcanany
                    @canany(['control-cambios.view', 'control-cambios.edit'])
                    <a class="sgc-nav-sub @if(Route::is('control-cambios.*')) is-active @endif"
                        href="{{ route('control-cambios.index') }}"
                        @click.stop="sidebarOpen = false">
                        Control de Cambios
                    </a>
                    @endcanany
                    @canany(['propuesta_mejora.view', 'propuesta_mejora.edit'])
                    <a class="sgc-nav-sub @if(Route::is('propuesta_mejora.*')) is-active @endif"
                        href="{{ route('propuesta_mejora.index') }}"
                        @click.stop="sidebarOpen = false">
                        Propuesta de Mejora
                    </a>
                    @endcanany
                </div>
            </div>
            @endcanany
        </nav>
    </aside>

    <style>
        .sgc-app-sidebar {
            background: linear-gradient(178deg, var(--side-top), var(--side-bot));
            transition: background .3s;
            padding: 24px 16px;
        }
        .sgc-app-sidebar-brand {
            background: transparent;
            padding: 6px 10px 30px;
            border-bottom: none;
        }
        .sgc-logo {
            width: 158px;
            height: auto;
            max-width: 100%;
            display: block;
            opacity: .96;
        }
        .sgc-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0;
        }
        .sgc-nav-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 14px;
            border-radius: 9px;
            color: #b9c3da;
            font-size: 14px;
            font-weight: 600;
            transition: background .18s, color .18s;
            text-align: left;
        }
        .sgc-nav-item:hover {
            background: #ffffff10;
            color: #fff;
        }
        .sgc-nav-item.is-active {
            background: linear-gradient(90deg, #1E3A7A, #2E5CB8);
            color: #fff;
            box-shadow: 0 6px 16px #12275e66;
        }
        .sgc-nav-item.is-active::before {
            content: "";
            position: absolute;
            left: -16px;
            top: 9px;
            bottom: 9px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--gold);
        }
        .sgc-nav-ico {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: .8;
        }
        .sgc-nav-item.is-active .sgc-nav-ico { opacity: 1; }
        .sgc-nav-chev {
            width: 15px;
            height: 15px;
            opacity: .55;
            flex-shrink: 0;
            margin-left: auto;
        }
        .sgc-nav-sub {
            display: block;
            padding: 8px 12px 8px 44px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #9fabc6;
            transition: background .16s, color .16s;
        }
        .sgc-nav-sub:hover,
        .sgc-nav-sub.is-active {
            background: #ffffff12;
            color: #fff;
        }

        /* Desktop: sidebar fijo y visible, contenido desplazado */
        @media (min-width: 1024px) {
            #sidebar.sgc-app-sidebar {
                transform: none !important;
                width: 18rem;
                max-width: none;
            }
            .sgc-content { margin-left: 18rem; }
        }
        @media (min-width: 1280px) {
            #sidebar.sgc-app-sidebar { width: 20rem; }
            .sgc-content { margin-left: 20rem; }
        }

        @media (max-width: 480px) {
            .sgc-app-sidebar { padding: 16px 12px; }
            .sgc-app-sidebar-brand { padding: 4px 6px 20px; }
            .sgc-logo { width: 132px; }
            .sgc-nav-item { padding: 11px 12px; }
            .sgc-nav-item.is-active::before { left: -12px; }
        }
    </style>
</div>
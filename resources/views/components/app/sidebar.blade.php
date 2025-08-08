<div class="min-w-fit" x-data="{ 
    activeSection: @if(Route::is('divisions.*') || Route::is('unidades-negocios.*') || Route::is('area.*'))'empresa'@elseif(Route::is('sgc.*'))'sgc'@elseif(Route::is('usuarios.*'))'usuarios'@else'dashboard'@endif,
    secondaryMenu: {
        dashboard: [],
        empresa: ['Divisiones', 'Unidades de negocios', 'Areas'],
        sgc: ['Tipo de elementos'],
        usuarios: ['Usuarios']
    }
}">
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-gray-900/30 dark:bg-gray-900/50 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak
    ></div>

    <!-- Main Header with Integrated Secondary Navigation -->
    <div
        id="sidebar"
        class="fixed top-0 left-0 right-0 z-40 bg-gradient-to-r from-purple-700 via-purple-800 to-purple-900 dark:from-purple-800 dark:via-purple-900 dark:to-purple-950 transition-all duration-500 ease-in-out shadow-2xl backdrop-blur-lg bg-opacity-95 dark:bg-opacity-95"
        :class="sidebarOpen ? 'max-lg:translate-y-0' : 'max-lg:-translate-y-full'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
    >

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
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                        
                        <button class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 hover:scale-105 shadow-lg @if(in_array(Request::segment(1), ['divisions', 'unidades-negocios', 'area'])){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@endif" 
                                @click="activeSection = 'empresa'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            <span>Estructura de la empresa</span>
                        </button>

                        
                        <button class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 hover:scale-105 shadow-lg @if(in_array(Request::segment(1), ['usuarios'])){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@endif" 
                                @click="activeSection = 'usuarios'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                            </svg>
                            <span>Usuarios</span>
                        </button>

                        <button class="flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-purple-100 hover:text-white hover:bg-white/20 dark:hover:bg-white/25 transition-all duration-300 hover:scale-105 shadow-lg @if(in_array(Request::segment(1), ['sgc'])){{ 'bg-white text-purple-700 dark:text-purple-800 shadow-xl ring-2 ring-white/30' }}@endif" 
                                @click="activeSection = 'sgc'">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
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
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                <span>Divisiones</span>
                            </a>
                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('unidades-negocios.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif" 
                               href="{{ route('unidades-negocios.index') }}"
                               @click="activeSection = 'empresa'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                <span>Unidades de negocios</span>
                            </a>

                            <a class="group flex items-center space-x-2 px-4 py-2 text-sm font-medium text-purple-100 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 transform hover:scale-105 hover:-translate-y-0.5 border border-transparent hover:border-white/30 shadow-lg hover:shadow-purple-500/20 @if(Route::is('area.*')){{ 'text-white bg-white/25 border-white/40 shadow-xl scale-105' }}@endif" 
                            href="{{ route('area.index') }}"
                            @click="activeSection = 'empresa'">
                             <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                 <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                             </svg>
                             <span>Areas</span>
                         </a>

                        
                        </div>
                    </template>

                    <!-- Estructura de la SGC Section -->
                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('sgc.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="#"
                               @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Tipo de elementos</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('tipo-proceso.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="#"
                               @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Tipo de proceso</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('elementos.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="#"
                               @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Elementos</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeSection === 'sgc'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('cuerpos-correo.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="#"
                               @click="activeSection = 'sgc'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Cuerpos de correo</span>
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
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                </svg>
                                <span>Puestos de trabajo</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeSection === 'usuarios'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('empleados.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="{{ route('empleados.index') }}"
                               @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                </svg>
                                <span>Empleados</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeSection === 'usuarios'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('usuarios.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="#"
                               @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                </svg>
                                <span>Usuarios y contraseña</span>
                            </a>
                        </div>
                    </template>

                    <template x-if="activeSection === 'usuarios'">
                        <div class="flex items-center space-x-4">
                            <a class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/25 dark:hover:bg-white/30 rounded-xl transition-all duration-300 hover:scale-105 border border-transparent hover:border-white/30 shadow-lg @if(Route::is('matriz-responsabilidades.*')){{ 'bg-white text-purple-900 dark:text-purple-700 shadow-xl ring-2 ring-white/30' }}@endif" 
                               href="#"
                               @click="activeSection = 'usuarios'">
                                <svg class="w-4 h-4 transform transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                                </svg>
                                <span>Matriz de responsabilidades</span>
                            </a>
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
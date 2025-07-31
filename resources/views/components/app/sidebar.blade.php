<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-gray-900/30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak
    ></div>

    <!-- Horizontal Navigation Bar -->
    <div
        id="sidebar"
        class="fixed top-0 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-all duration-200 ease-in-out"
        :class="sidebarOpen ? 'max-lg:translate-y-0' : 'max-lg:-translate-y-full'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
    >

        <!-- Horizontal Navigation Container -->
        <div class="flex items-center justify-between px-4 py-3">
            
            <!-- Logo and Brand -->
            <div class="flex items-center space-x-4">
                <a class="block" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/Logo-blanco.png') }}" alt="Logo de la aplicación" class="dark:block hidden" {{ $attributes }} style="width: 180px; height: 40px; filter: brightness(1.1);">
                    <img src="{{ asset('images/Logo-azul.png') }}" alt="Logo de la aplicación" class="block dark:hidden" {{ $attributes }} style="width: 180px; height: 40px;">                    
                </a>
            </div>

            <!-- Horizontal Menu Items -->
            <div class="flex items-center space-x-1">
                <!-- Dashboard -->
                <a class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors @if(in_array(Request::segment(1), ['dashboard'])){{ 'bg-violet-500 text-white' }}@else{{ 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}@endif" href="{{ route('dashboard') }}">
                    <span>Dashboard</span>
                </a>

                <!-- Community Dropdown -->
                <div class="relative" x-data="{ openEmpresa: false }">
                    <button class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors @if(in_array(Request::segment(1), ['community'])){{ 'bg-violet-500 text-white' }}@else{{ 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}@endif" @click="openEmpresa = !openEmpresa">
                        <span>Estructura de la empresa</span>
                        <svg class="w-3 h-3 ml-1 @if(in_array(Request::segment(1), ['community'])){{ 'text-white' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" :class="openEmpresa ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50" 
                         x-show="openEmpresa" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 transform scale-95" 
                         x-transition:enter-end="opacity-100 transform scale-100" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="opacity-100 transform scale-100" 
                         x-transition:leave-end="opacity-0 transform scale-95"
                         @click.outside="openEmpresa = false">
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('divisions.*')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('divisions.index') }}">
                                Divisiones
                            </a>
                        </div>
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('unidades-negocios.*')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('unidades-negocios.index') }}">
                                Unidades de negocios
                            </a>
                        </div>
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('area.*')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('area.index') }}">
                                Areas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Estructura de la SGC -->
                <div class="relative" x-data="{ openSGC: false }">
                    <button class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors @if(in_array(Request::segment(1), ['sgc'])){{ 'bg-violet-500 text-white' }}@else{{ 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}@endif" @click="openSGC = !openSGC">
                        <span>Estructura de la SGC</span>
                        <svg class="w-3 h-3 ml-1 @if(in_array(Request::segment(1), ['sgc'])){{ 'text-white' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" :class="openSGC ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50" 
                         x-show="openSGC" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 transform scale-95" 
                         x-transition:enter-end="opacity-100 transform scale-100" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="opacity-100 transform scale-100" 
                         x-transition:leave-end="opacity-0 transform scale-95"
                         @click.outside="openSGC = false">
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" href="#">
                                Tipo de elementos
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Usuarios de la SGC -->
                <div class="relative" x-data="{ openUsuarios: false }">
                    <button class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors @if(in_array(Request::segment(1), ['usuarios'])){{ 'bg-violet-500 text-white' }}@else{{ 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}@endif" @click="openUsuarios = !openUsuarios">
                        <span>Usuarios</span>
                        <svg class="w-3 h-3 ml-1 @if(in_array(Request::segment(1), ['usuarios'])){{ 'text-white' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" :class="openUsuarios ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50" 
                         x-show="openUsuarios" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 transform scale-95" 
                         x-transition:enter-end="opacity-100 transform scale-100" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="opacity-100 transform scale-100" 
                         x-transition:leave-end="opacity-0 transform scale-95"
                         @click.outside="openUsuarios = false">
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" href="#">
                                Usuarios
                            </a>
                        </div>
                    </div>
                </div>
        </div>


            <!-- Header Elements (Right Side) -->
            <div class="flex items-center space-x-3">
                <!-- Notifications button -->
                <x-dropdown-notifications align="right" />

                <!-- Dark mode toggle -->
                <x-theme-toggle />                

                <!-- Divider -->
                <hr class="w-px h-6 bg-gray-200 dark:bg-gray-700/60 border-none" />

                <!-- User button -->
                <x-dropdown-profile align="right" />

                <!-- Mobile Menu Button -->
                <button class="lg:hidden text-gray-500 hover:text-gray-400" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                    <span class="sr-only">Toggle menu</span>
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
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
                   
                    <img src="{{ asset('images/logo-azul-png.svg') }}" alt="Logo de la aplicación" {{ $attributes }} style="width: 180px; height: 40px;">                    
                </a>
                
            </div>

            <!-- Horizontal Menu Items -->
            <div class="flex items-center space-x-1">
                <!-- Dashboard -->
                <a class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors @if(in_array(Request::segment(1), ['dashboard'])){{ 'bg-violet-500 text-white' }}@else{{ 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}@endif" href="{{ route('dashboard') }}">
                    <svg class="shrink-0 w-4 h-4 mr-2 @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-white' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M5.936.278A7.983 7.983 0 0 1 8 0a8 8 0 1 1-8 8c0-.722.104-1.413.278-2.064a1 1 0 1 1 1.932.516A5.99 5.99 0 0 0 2 8a6 6 0 1 0 6-6c-.53 0-1.045.076-1.548.21A1 1 0 1 1 5.936.278Z" />
                        <path d="M6.068 7.482A2.003 2.003 0 0 0 8 10a2 2 0 1 0-.518-3.932L3.707 2.293a1 1 0 0 0-1.414 1.414l3.775 3.775Z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- Community Dropdown -->
                <div class="relative" x-data="{ open: {{ in_array(Request::segment(1), ['community']) ? 1 : 0 }} }">
                    <button class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors @if(in_array(Request::segment(1), ['community'])){{ 'bg-violet-500 text-white' }}@else{{ 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}@endif" @click="open = !open">
                        <svg class="shrink-0 w-4 h-4 mr-2 @if(in_array(Request::segment(1), ['community'])){{ 'text-white' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path d="M12 1a1 1 0 1 0-2 0v2a3 3 0 0 0 3 3h2a1 1 0 1 0 0-2h-2a1 1 0 0 1-1-1V1ZM1 10a1 1 0 1 0 0 2h2a1 1 0 0 1 1 1v2a1 1 0 1 0 2 0v-2a3 3 0 0 0-3-3H1ZM5 0a1 1 0 0 1 1 1v2a3 3 0 0 1-3 3H1a1 1 0 0 1 0-2h2a1 1 0 0 0 1-1V1a1 1 0 0 1 1-1ZM12 13a1 1 0 0 1 1-1h2a1 1 0 1 0 0-2h-2a3 3 0 0 0-3 3v2a1 1 0 1 0 2 0v-2Z" />
                        </svg>
                        <span>Community</span>
                        <svg class="w-3 h-3 ml-1 @if(in_array(Request::segment(1), ['community'])){{ 'text-white' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block' : 'hidden'">
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('users-tabs')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('users-tabs') }}">
                                Users - Tabs
                            </a>
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('users-tiles')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('users-tiles') }}">
                                Users - Tiles
                            </a>
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('profile')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('profile') }}">
                                Profile
                            </a>
                            <a class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if(Route::is('feed')){{ 'text-violet-500 font-medium' }}@endif" href="{{ route('feed') }}">
                                Feed
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
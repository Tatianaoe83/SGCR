<header class="sticky top-0 shrink-0 before:absolute before:inset-0 before:backdrop-blur-md before:bg-white/90 dark:before:bg-gray-800/90 before:-z-10 z-30 shadow-sm border-b border-gray-200 dark:border-gray-700/60">
    <div class="px-4 sm:px-6 lg:px-8">
        <!-- Top Row: Hamburger y acciones de usuario -->
        <div class="flex items-center justify-between h-16">
            <!-- Left side: Hamburger -->
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

            </div>

            <!-- Right side: Notifications, Theme Toggle, User -->
            <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                <!-- Firmas Pendientes Notifications -->
                <x-notificaciones-firmas />

                <!-- <x-dropdown-notifications align="right" /> -->
                 <x-modal-suggets-change-control align="right"/>


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
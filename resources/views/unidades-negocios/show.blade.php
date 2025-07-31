<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Breadcrumbs -->
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Dashboard</a>
                    <span class="mx-2">></span>
                    <a href="{{ route('unidades-negocios.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Unidades de Negocio</a>
                    <span class="mx-2">></span>
                    <span class="text-gray-400 dark:text-gray-500">{{ $unidadNegocio->nombre }}</span>
                </div>
                
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $unidadNegocio->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('unidades-negocios.edit', $unidadNegocio->id) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Editar</span>
                </a>
                <a href="{{ route('unidades-negocios.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M7.001 3h2v4h4v2h-4v4H7.001v-4H3V7h4V3z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Content -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles de la Unidad de Negocio</h2>
            </header>
            <div class="p-6">

                <div class="space-y-6">

                    <!-- ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ID</label>
                        <p class="text-gray-800 dark:text-gray-100">{{ $unidadNegocio->id }}</p>
                    </div>

                    <!-- División -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">División</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $unidadNegocio->division->nombre }}</p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nombre</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $unidadNegocio->nombre }}</p>
                    </div>

                    <!-- Fecha de Creación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha de Creación</label>
                        <p class="text-gray-800 dark:text-gray-100">{{ $unidadNegocio->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                    <!-- Fecha de Actualización -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Última Actualización</label>
                        <p class="text-gray-800 dark:text-gray-100">{{ $unidadNegocio->updated_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                </div>

            </div>
        </div>

    </div>
</x-app-layout> 
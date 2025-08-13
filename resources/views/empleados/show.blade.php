<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $empleados->nombres }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('empleados.edit', $empleados->id_empleado) }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-blue-500 hover:bg-blue-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                        </svg>
                    <span class="hidden xs:block ml-2">Editar</span>
                </a>
                <a href="{{ route('empleados.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
                        </svg>
                    <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Content -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles del empleados</h2>
            </header>
            <div class="p-6">

                <div class="space-y-6">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nombre(s)</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $empleados->nombres }}</p>
                    </div>

                    <!-- Apellido Paterno -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Apellido Paterno</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $empleados->apellido_paterno }}</p>
                    </div>

                    <!-- Apellido Materno -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Apellido Materno</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $empleados->apellido_materno }}</p>
                    </div>

                    <!-- Puesto de Trabajo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Puesto de Trabajo</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $empleados->puestoTrabajo->nombre }}</p>
                    </div>

                    <!-- Correo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Correo</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $empleados->correo }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Teléfono</label>
                        <p class="text-gray-800 dark:text-gray-100 font-medium">{{ $empleados->telefono }}</p>
                    </div>

                    <!-- Fecha de Creación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha de Creación</label>
                        <p class="text-gray-800 dark:text-gray-100">{{ $empleados->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                    <!-- Fecha de Actualización -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Última Actualización</label>
                        <p class="text-gray-800 dark:text-gray-100">{{ $empleados->updated_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                </div>

            </div>
        </div>

    </div>
</x-app-layout> 
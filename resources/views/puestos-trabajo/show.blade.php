<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $puestoTrabajo->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('puestos-trabajo.edit', $puestoTrabajo->id_puesto_trabajo) }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-blue-500 hover:bg-blue-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                        </svg>
                        <span class="hidden xs:block ml-2">Editar</span>
                </a>

                <a href="{{ route('puestos-trabajo.index') }}"
                    class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                        </svg>
                        <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles del Puesto de Trabajo</h2>
            </header>
            <div class="p-6">

                <div class="space-y-6">

                    <!-- Información del Puesto -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">Información del Puesto</h3>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Nombre del Puesto</label>
                                <p class="text-slate-800 dark:text-slate-100 font-medium">{{ $puestoTrabajo->nombre }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información de la Estructura -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">Estructura Organizacional</h3>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">División</label>
                                <p class="text-slate-800 dark:text-slate-100 font-medium">{{ $puestoTrabajo->division->nombre ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Unidad de Negocio</label>
                                <p class="text-slate-800 dark:text-slate-100 font-medium">{{ $puestoTrabajo->unidadNegocio->nombre ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Área</label>
                                <p class="text-slate-800 dark:text-slate-100 font-medium">{{ $puestoTrabajo->area->nombre ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Jefe Directo</label>
                                @if($puestoTrabajo->jefes)
                                <p>{{ $puestoTrabajo->jefes->nombres }} {{ $puestoTrabajo->jefes->apellido_paterno }} {{ $puestoTrabajo->jefes->apellido_materno }}</p>
                                @else
                                <p class="text-slate-800 dark:text-slate-100 font-medium">No se ha asignado un jefe directo.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Fechas -->
                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">Información de Fechas</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Fecha de Creación</label>
                            <p class="text-slate-800 dark:text-slate-100">{{ $puestoTrabajo->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400">Última Actualización</label>
                            <p class="text-slate-800 dark:text-slate-100">{{ $puestoTrabajo->updated_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>
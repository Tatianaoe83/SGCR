<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11 ">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Puestos de Trabajo</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <!-- Exportar -->
                <a href="{{ route('puestos-trabajo.export') }}" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Exportar Excel</span>
                </a>

                <!-- Descargar Plantilla -->
                <a href="{{ route('puestos-trabajo.template') }}" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Descargar Plantilla</span>
                </a>

                <!-- Importar -->
                <a href="{{ route('puestos-trabajo.import.form') }}" class="btn bg-orange-500 hover:bg-orange-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Importar Excel</span>
                </a>

                <!-- Crear Nuevo -->
                <a href="{{ route('puestos-trabajo.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Nuevo Puesto de Trabajo</span>
                </a>
            </div>

        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 table-container">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Puestos de Trabajo</h2>
            </header>
            <div class="p-3">

                <!-- DataTable -->
                <div class="overflow-x-auto">
                    <table id="puestosTrabajoTable" class="table-auto w-full dataTable">
                        <thead class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">ID</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">Nombre del Puesto</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">División</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">Unidad de Negocio</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">Área</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">Creado</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-center">Acciones</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($puestosTrabajo as $puestoTrabajo)
                                <tr>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-left">{{ $puestoTrabajo->id_puesto_trabajo }}</div>
                                    </td>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->nombre }}</div>
                                    </td>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->division ? $puestoTrabajo->division->nombre : '-' }}</div>
                                    </td>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->unidadNegocio ? $puestoTrabajo->unidadNegocio->nombre : '-' }}</div>
                                    </td>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->area ? $puestoTrabajo->area->nombre : '-' }}</div>
                                    </td>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-left">{{ $puestoTrabajo->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="p-2 whitespace-nowrap">
                                        <div class="text-center">
                                            <div class="inline-flex" role="group">
                                                <a href="{{ route('puestos-trabajo.show', $puestoTrabajo->id_puesto_trabajo) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                            <tr>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">{{ $puestoTrabajo->id_puesto_trabajo }}</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->nombre }}</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    @if($puestoTrabajo->is_global)
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">Todas</div>
                                    @else
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->division->nombre }}</div>
                                    @endif
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    @if($puestoTrabajo->is_global)
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">Todas</div>
                                    @else
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $puestoTrabajo->unidadNegocio->nombre }}</div>
                                    @endif
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    @if($puestoTrabajo->is_global)
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">
                                        <span class="inline-block px-2 py-1 mr-1 mb-1 text-xs font-semibold bg-purple-100 text-purple-700 rounded">
                                            Todas
                                        </span>
                                    </div>
                                    @else
                                    <div class="text-left font-medium text-gray-800 dark:text-gray-100">

                                        @if($puestoTrabajo->areas && $puestoTrabajo->areas->count() > 0)
                                        @foreach($puestoTrabajo->areas as $area)
                                        <span class="inline-block px-2 py-1 mr-1 mb-1 text-xs font-semibold bg-purple-100 text-purple-700 rounded">
                                            {{ $area->nombre }}
                                        </span>
                                        @endforeach
                                        @else
                                        <span class="text-slate-400 italic">Sin área</span>
                                        @endif

                                    </div>
                                    @endif
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">{{ $puestoTrabajo->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="inline-flex" role="group">
                                            <a href="{{ route('puestos-trabajo.show', $puestoTrabajo->id_puesto_trabajo) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM1.5 8c0-3.6 2.9-6.5 6.5-6.5S14.5 4.4 14.5 8 11.6 14.5 8 14.5 1.5 11.6 1.5 8zM8 4.5c-1.9 0-3.5 1.6-3.5 3.5S6.1 11.5 8 11.5s3.5-1.6 3.5-3.5S9.9 4.5 8 4.5zM8 9.5c-.8 0-1.5-.7-1.5-1.5S7.2 6.5 8 6.5s1.5.7 1.5 1.5S8.8 9.5 8 9.5z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('puestos-trabajo.edit', $puestoTrabajo->id_puesto_trabajo) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                    <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('puestos-trabajo.destroy', $puestoTrabajo->id_puesto_trabajo) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este puesto de trabajo?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn bg-rose-500 hover:bg-rose-600 text-white">
                                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                        <path d="M6.602 11l1.497 1.497-1.497 1.497L5.105 12.497 3.608 11l1.497-1.497L5.105 8.006 6.602 6.51l1.497 1.497L9.596 6.51l1.497 1.497L10.099 8.006 11.596 9.503L10.099 11l-1.497-1.497L6.602 11z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">-</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">-</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">-</div>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <div class="text-center text-gray-500 dark:text-gray-400">No hay puestos de trabajo registrados</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">-</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-center">-</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-center">-</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <x-datatable tableId="puestosTrabajoTable" :orderColumn="0" orderDirection="desc" :pageLength="10" />
</x-app-layout>
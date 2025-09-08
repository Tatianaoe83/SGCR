<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11 ">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Elementos</h1>
            </div>

                         <!-- Right: Actions -->
             <div class="flex flex-wrap items-center space-x-2">    
                <a href="{{ route('puestos-trabajo.export') }}" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Exportar Excel</span>
                </a>

                <!-- Descargar Plantilla -->
             <!--   <a href="{{ route('elementos.template') }}" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Descargar Plantilla</span>
                </a>

                <!-- Importar -->
             <!--   <a href="{{ route('elementos.import.form') }}" class="btn bg-orange-500 hover:bg-orange-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Importar Excel</span>
                </a> -->

                   <!-- Crear Nuevo -->
                   <a href="{{ route('elementos.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Nuevo Elemento</span>
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
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Elementos</h2>
                
                <!-- Leyenda del Semáforo -->
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Leyenda del Semáforo:</span>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-500 text-white">Crítico</span>
                        <span class="text-gray-500 dark:text-gray-400">≤ 2 meses</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-500 text-black">Advertencia</span>
                        <span class="text-gray-500 dark:text-gray-400">4-6 meses</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-500 text-white">Normal</span>
                        <span class="text-gray-500 dark:text-gray-400">6-12 meses</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-500 text-white">Lejano</span>
                        <span class="text-gray-500 dark:text-gray-400">> 1 año</span>
                    </div>
                </div>
            </header>
            <div class="p-3">

                <!-- DataTable -->
                <div class="overflow-x-auto">
                    <table id="elementosTable" class="table-auto w-full dataTable">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Nombre
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Tipo
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Proceso
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Responsable
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Versión
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Periodo Revisión
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Estado Semáforo
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Acciones
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @forelse($elementos as $elemento)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $elemento->nombre_elemento }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->tipoElemento->nombre ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->tipoProceso->nombre ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->puestoResponsable->nombre ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->version_elemento }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->periodo_revision ? $elemento->periodo_revision->format('d/m/Y') : 'Sin fecha' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <x-semaforo-revision :fecha="$elemento->periodo_revision" :showInfo="false" :size="'sm'" />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-2">
                                                        <a href="{{ route('elementos.show', $elemento->id_elemento) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                                <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM1.5 8c0-3.6 2.9-6.5 6.5-6.5S14.5 4.4 14.5 8 11.6 14.5 8 14.5 1.5 11.6 1.5 8zM8 4.5c-1.9 0-3.5 1.6-3.5 3.5S6.1 11.5 8 11.5s3.5-1.6 3.5-3.5S9.9 4.5 8 4.5zM8 9.5c-.8 0-1.5-.7-1.5-1.5S7.2 6.5 8 6.5s1.5.7 1.5 1.5S8.8 9.5 8 9.5z" />
                                                            </svg>
                                                        </a>
                                                        <a href="{{ route('elementos.edit', $elemento->id_elemento) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                                <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                                                            </svg>
                                                        </a>
                                                        <form action="{{ route('elementos.destroy', $elemento->id_elemento) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este elemento?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn bg-rose-500 hover:bg-rose-600 text-white">
                                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                                    <path d="M6.602 11l1.497 1.497-1.497 1.497L5.105 12.497 3.608 11l1.497-1.497L5.105 8.006 6.602 6.51l1.497 1.497L9.596 6.51l1.497 1.497L10.099 8.006 11.596 9.503L10.099 11l-1.497-1.497L6.602 11z" />
                                                                </svg>
                                                            </button>
                                                        </form>

                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                                    No hay elementos registrados.
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4">
                                    {{ $elementos->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable tableId="elementosTable" :orderColumn="0" orderDirection="desc" :pageLength="10" />
</x-app-layout>
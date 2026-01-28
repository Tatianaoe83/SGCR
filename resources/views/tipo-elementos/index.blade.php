<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Tipo de Elementos</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('tipo-elementos.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Nuevo Tipo de Elemento</span>
                </a>
            </div>

        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- DataTable Container -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 table-container">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Tipo de Elementos</h2>
            </header>
            <div class="p-3">
                <div class="overflow-x-auto">
                    <table id="tipoElementosTable" class="table-auto w-full dataTable">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Nombre del Tipo de Elemento
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Elementos Asociados
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Campos Requeridos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($tiposElemento as $tipo)
                            <tr data-tipo-id="{{ $tipo->id_tipo_elemento }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $tipo->nombre }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $tipo->elementos_count }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col space-y-3">
                                        <!-- Badge de contador -->
                                        <div class="flex items-center justify-center">
                                            <span class="inline-flex items-center rounded-full bg-gradient-to-r from-amber-100 to-yellow-100 px-3 py-1.5 text-sm font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:from-amber-900/30 dark:to-yellow-900/30 dark:text-amber-300 dark:ring-amber-800/50">
                                                <svg class="w-4 h-4 mr-1.5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                </svg>
                                                <span id="contador-campos-{{ $tipo->id_tipo_elemento }}" class="font-bold">
                                                    {{ $tipo->camposRequeridos->count() ?? 0 }}
                                                </span>
                                                <span class="ml-1 text-xs">campos requeridos</span>
                                            </span>
                                        </div>

                                        <!-- Botón para gestionar campos -->
                                        <!--<div class="flex justify-center">
                                                <button 
                                                    onclick="toggleGestionCampos({{ $tipo->id_tipo_elemento }})"
                                                    class="inline-flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 hover:text-blue-800 focus:ring-4 focus:ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-800/40 dark:focus:ring-blue-800/30 transition-all duration-200">
                                                    <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                    Gestionar Campos
                                                </button>
                                            </div>
                                            
                                         
                                            @if($tipo->camposRequeridos->count() > 0)
                                                <div class="flex justify-center">
                                                    <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400">
                                                        <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        Configurado
                                                    </span>
                                                </div>
                                            @else
                                                <div class="flex justify-center">
                                                    <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                        <svg class="w-3 h-3 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        Sin configurar
                                                    </span>
                                                </div>
                                            @endif
                                        </div>-->
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('tipo-elementos.show', $tipo->id_tipo_elemento) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM1.5 8c0-3.6 2.9-6.5 6.5-6.5S14.5 4.4 14.5 8 11.6 14.5 8 14.5 1.5 11.6 1.5 8zM8 4.5c-1.9 0-3.5 1.6-3.5 3.5S6.1 11.5 8 11.5s3.5-1.6 3.5-3.5S9.9 4.5 8 4.5zM8 9.5c-.8 0-1.5-.7-1.5-1.5S7.2 6.5 8 6.5s1.5.7 1.5 1.5S8.8 9.5 8 9.5z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('tipo-elementos.edit', $tipo->id_tipo_elemento) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                                            </svg>
                                        </a>
                                        <form
                                            action="{{ route('tipo-elementos.destroy', $tipo->id_tipo_elemento) }}"
                                            method="POST"
                                            class="inline form-eliminar">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.form-eliminar').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: '¿Eliminar tipo de elemento?',
                        text: 'Esta acción no se puede deshacer',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
</x-app-layout>
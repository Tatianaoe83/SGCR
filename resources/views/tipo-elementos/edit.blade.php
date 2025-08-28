<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
    
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $tipoElemento->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('tipo-elementos.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
                        </svg>
                    <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

                     <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles del Tipo de Elemento</h2>
            </header>
                    <form action="{{ route('tipo-elementos.update', $tipoElemento->id_tipo_elemento) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <!-- Información básica del tipo de elemento -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Información del Tipo de Elemento
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nombre del Tipo *
                                    </label>
                                    <input type="text" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre', $tipoElemento->nombre) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                           required>
                                    @error('nombre')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Descripción
                                    </label>
                                    <textarea id="descripcion" 
                                              name="descripcion" 
                                              rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('descripcion', $tipoElemento->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de campos requeridos -->
                        <x-campos-requeridos :camposElementos="$camposElementos" :tipoId="$tipoElemento->id_tipo_elemento" />

                        <!-- Botones de acción -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('tipo-elementos.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Actualizar Tipo de Elemento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Cargar campos requeridos existentes al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarCamposExistentes();
        });

        async function cargarCamposExistentes() {
            try {
                const response = await fetch(`/tipo-elementos/{{ $tipoElemento->id_tipo_elemento }}/campos-requeridos`);
                if (response.ok) {
                    const campos = await response.json();
                    
                    // Marcar checkboxes según campos existentes
                    campos.forEach(campo => {
                        const checkbox = document.getElementById(`campo_${campo.campo_nombre}`);
                        if (checkbox) {
                            checkbox.checked = campo.es_requerido;
                        }
                    });
                }
            } catch (error) {
                console.log('No hay campos requeridos configurados para este tipo de elemento');
            }
        }

        function marcarTodosCampos() {
            const checkboxes = document.querySelectorAll('input[name="campos_requeridos[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function desmarcarTodosCampos() {
            const checkboxes = document.querySelectorAll('input[name="campos_requeridos[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
</x-app-layout>

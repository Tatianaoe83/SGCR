<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Tipo de Elemento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                            Editar Tipo de Elemento: {{ $tipoElemento->nombre }}
                        </h1>
                        <a href="{{ route('tipo-elementos.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Volver a la Lista
                        </a>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 p-6 lg:p-8">
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

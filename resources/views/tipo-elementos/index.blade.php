<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tipos de Elementos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                            Lista de Tipos de Elementos
                        </h1>
                        <a href="{{ route('tipo-elementos.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Crear Tipo de Elemento
                        </a>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                    @if(session('success'))
                        <div class="col-span-full">
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="col-span-full">
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="col-span-full">
                        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Nombre
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Descripción
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Elementos
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
                                            @forelse($tiposElemento as $tipo)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $tipo->nombre }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {{ Str::limit($tipo->descripcion, 100) ?? 'Sin descripción' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $tipo->elementos_count }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        <div class="flex flex-col space-y-2">
                                                            <button 
                                                                onclick="toggleCamposRequeridos({{ $tipo->id_tipo_elemento }})"
                                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                                Ver Campos
                                                            </button>
                                                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                                                <span id="contador-campos-{{ $tipo->id_tipo_elemento }}">0</span> campos requeridos
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div class="flex space-x-2">
                                                            <a href="{{ route('tipo-elementos.show', $tipo->id_tipo_elemento) }}" 
                                                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                                Ver
                                                            </a>
                                                            <a href="{{ route('tipo-elementos.edit', $tipo->id_tipo_elemento) }}" 
                                                               class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                                Editar
                                                            </a>
                                                            <form action="{{ route('tipo-elementos.destroy', $tipo->id_tipo_elemento) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                                        onclick="return confirm('¿Estás seguro de que quieres eliminar este tipo de elemento?')">
                                                                    Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <!-- Fila expandible para campos requeridos -->
                                                <tr id="campos-requeridos-{{ $tipo->id_tipo_elemento }}" class="hidden bg-gray-50 dark:bg-gray-700">
                                                    <td colspan="5" class="px-6 py-4">
                                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                                                Campos Requeridos para Elementos de Tipo: {{ $tipo->nombre }}
                                                            </h4>
                                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                                @foreach($camposElementos as $campo => $label)
                                                                    <div class="flex items-center space-x-3">
                                                                        <input 
                                                                            type="checkbox" 
                                                                            id="campo_{{ $tipo->id_tipo_elemento }}_{{ $campo }}"
                                                                            name="campos_requeridos[{{ $tipo->id_tipo_elemento }}][]"
                                                                            value="{{ $campo }}"
                                                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                                                                        >
                                                                        <label 
                                                                            for="campo_{{ $tipo->id_tipo_elemento }}_{{ $campo }}"
                                                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                                        >
                                                                            {{ $label }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <div class="mt-4 flex justify-between items-center">
                                                                <div class="flex space-x-2">
                                                                    <button 
                                                                        type="button"
                                                                        onclick="marcarTodosCampos({{ $tipo->id_tipo_elemento }})"
                                                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                                                        Marcar Todos
                                                                    </button>
                                                                    <button 
                                                                        type="button"
                                                                        onclick="desmarcarTodosCampos({{ $tipo->id_tipo_elemento }})"
                                                                        class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-sm">
                                                                        Desmarcar Todos
                                                                    </button>
                                                                </div>
                                                                <div class="flex space-x-3">
                                                                    <button 
                                                                        type="button"
                                                                        onclick="guardarCamposRequeridos({{ $tipo->id_tipo_elemento }})"
                                                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                                        Guardar Campos
                                                                    </button>
                                                                    <button 
                                                                        type="button"
                                                                        onclick="toggleCamposRequeridos({{ $tipo->id_tipo_elemento }})"
                                                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                                                        Cerrar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                                        No hay tipos de elementos registrados.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4">
                                    {{ $tiposElemento->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para manejar la funcionalidad de campos requeridos -->
    <script>
        function toggleCamposRequeridos(tipoId) {
            const fila = document.getElementById(`campos-requeridos-${tipoId}`);
            if (fila.classList.contains('hidden')) {
                fila.classList.remove('hidden');
                // Cargar campos requeridos existentes
                cargarCamposRequeridos(tipoId);
                // Agregar event listeners para los checkboxes
                agregarEventListenersCheckboxes(tipoId);
            } else {
                fila.classList.add('hidden');
            }
        }

        async function cargarCamposRequeridos(tipoId) {
            try {
                const response = await fetch(`/tipo-elementos/${tipoId}/campos-requeridos`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const campos = await response.json();
                
                // Marcar checkboxes según campos existentes
                let contadorRequeridos = 0;
                campos.forEach(campo => {
                    const checkbox = document.getElementById(`campo_${tipoId}_${campo.campo_nombre}`);
                    if (checkbox) {
                        checkbox.checked = campo.es_requerido;
                        if (campo.es_requerido) {
                            contadorRequeridos++;
                        }
                    }
                });
                
                // Actualizar contador
                actualizarContadorCampos(tipoId, contadorRequeridos);
            } catch (error) {
                console.error('Error al cargar campos requeridos:', error);
                // Si no hay campos configurados, no mostrar error
                if (error.message.includes('404')) {
                    console.log('No hay campos requeridos configurados para este tipo de elemento');
                    actualizarContadorCampos(tipoId, 0);
                }
            }
        }

        function actualizarContadorCampos(tipoId, contador) {
            const contadorElement = document.getElementById(`contador-campos-${tipoId}`);
            if (contadorElement) {
                contadorElement.textContent = contador;
            }
        }

        async function guardarCamposRequeridos(tipoId) {
            const checkboxes = document.querySelectorAll(`input[name="campos_requeridos[${tipoId}][]"]`);
            const campos = [];
            
            checkboxes.forEach((checkbox, index) => {
                const campoNombre = checkbox.value;
                const campoLabel = checkbox.nextElementSibling.textContent.trim();
                
                campos.push({
                    campo_nombre: campoNombre,
                    campo_label: campoLabel,
                    es_requerido: checkbox.checked,
                    es_obligatorio: false, // Por defecto no es obligatorio
                    orden: index
                });
            });
            
            try {
                const response = await fetch(`/tipo-elementos/${tipoId}/campos-requeridos`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        campos: campos
                    })
                });
                
                if (response.ok) {
                    alert('Campos requeridos guardados exitosamente');
                    // Opcional: cerrar la subtabla después de guardar
                    toggleCamposRequeridos(tipoId);
                } else {
                    alert('Error al guardar los campos requeridos');
                }
            } catch (error) {
                console.error('Error al guardar campos requeridos:', error);
                alert('Error al guardar los campos requeridos');
            }
        }

        // Función para marcar campos como requeridos por defecto (opcional)
        function marcarCamposPorDefecto(tipoId) {
            const camposObligatorios = [
                'nombre_elemento',
                'tipo_proceso_id',
                'unidad_negocio_id',
                'puesto_responsable_id'
            ];
            
            camposObligatorios.forEach(campo => {
                const checkbox = document.getElementById(`campo_${tipoId}_${campo}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        function marcarTodosCampos(tipoId) {
            const checkboxes = document.querySelectorAll(`#campos-requeridos-${tipoId} input[type="checkbox"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            actualizarContadorCampos(tipoId, checkboxes.length);
        }

        function desmarcarTodosCampos(tipoId) {
            const checkboxes = document.querySelectorAll(`#campos-requeridos-${tipoId} input[type="checkbox"]`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            actualizarContadorCampos(tipoId, 0);
        }

        function agregarEventListenersCheckboxes(tipoId) {
            const checkboxes = document.querySelectorAll(`#campos-requeridos-${tipoId} input[type="checkbox"]`);
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    actualizarContadorEnTiempoReal(tipoId);
                });
            });
        }

        function actualizarContadorEnTiempoReal(tipoId) {
            const checkboxes = document.querySelectorAll(`#campos-requeridos-${tipoId} input[type="checkbox"]:checked`);
            actualizarContadorCampos(tipoId, checkboxes.length);
        }

        // Cargar contadores iniciales cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarContadoresIniciales();
        });

        async function cargarContadoresIniciales() {
            const botonesVerCampos = document.querySelectorAll('button[onclick*="toggleCamposRequeridos"]');
            botonesVerCampos.forEach(boton => {
                const tipoId = boton.getAttribute('onclick').match(/\d+/)[0];
                cargarContadorInicial(tipoId);
            });
        }

        async function cargarContadorInicial(tipoId) {
            try {
                const response = await fetch(`/tipo-elementos/${tipoId}/campos-requeridos`);
                if (response.ok) {
                    const campos = await response.json();
                    const contadorRequeridos = campos.filter(campo => campo.es_requerido).length;
                    actualizarContadorCampos(tipoId, contadorRequeridos);
                }
            } catch (error) {
                // Si no hay campos configurados, el contador ya está en 0
                console.log(`No hay campos requeridos configurados para tipo ${tipoId}`);
            }
        }
    </script>
</x-app-layout>

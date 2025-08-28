@props(['camposElementos', 'tipoId' => null, 'readonly' => false])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        Configurar Campos Requeridos para Elementos
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Selecciona qué campos serán requeridos cuando se creen elementos de este tipo.
    </p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($camposElementos as $campo => $label)
            <div class="flex items-center space-x-3">
                <input type="checkbox" 
                       id="campo_{{ $tipoId ? $tipoId . '_' . $campo : $campo }}"
                       name="campos_requeridos[]"
                       value="{{ $campo }}"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                       @if(in_array($campo, ['nombre_elemento', 'tipo_proceso_id', 'unidad_negocio_id', 'puesto_responsable_id'])) checked @endif
                       @if($readonly) disabled @endif>
                <label for="campo_{{ $tipoId ? $tipoId . '_' . $campo : $campo }}" 
                       class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $label }}
                </label>
            </div>
        @endforeach
    </div>
    
    @if(!$readonly)
        <div class="mt-4 flex space-x-3">
            <button type="button" 
                    onclick="marcarTodosCampos()"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                Marcar Todos
            </button>
            <button type="button" 
                    onclick="desmarcarTodosCampos()"
                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-sm">
                Desmarcar Todos
            </button>
        </div>
    @endif
</div>

@if(!$readonly)
    <script>
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
@endif

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Crear Cuerpo de Correo') }}
        </h2>
    </x-slot>

    

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                            Crear Nuevo Cuerpo de Correo
                        </h1>
                        <a href="{{ route('cuerpos-correo.index') }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Volver
                        </a>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Crea un nuevo cuerpo de correo para comunicaciones automáticas
                    </p>
                </div>

                <div class="p-6">
                    <form action="{{ route('cuerpos-correo.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre -->
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nombre del Cuerpo *
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       id="nombre" 
                                       value="{{ old('nombre') }}"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
                                       placeholder="Ej: Correo de bienvenida para nuevos usuarios"
                                       required>
                                @error('nombre')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tipo -->
                            <div>
                                <label for="tipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Correo *
                                </label>
                                <select name="tipo" 
                                        id="tipo" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-gray-100"
                                        required>
                                    <option value="">Selecciona un tipo</option>
                                    @foreach($tipos as $valor => $nombre)
                                        <option value="{{ $valor }}" {{ old('tipo') == $valor ? 'selected' : '' }}>
                                            {{ $nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tipo')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="activo" 
                                       value="1" 
                                       {{ old('activo', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Cuerpo activo</span>
                            </label>
                        </div>

                        <!-- Información de Variables -->
                        <div id="info-variables" class="hidden p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                Variables Disponibles
                            </h3>
                            <div id="variables-tipo" class="text-sm text-blue-800 dark:text-blue-200"></div>
                            <div class="mt-3">
                                <button type="button" id="btn-plantilla-ejemplo" 
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 text-sm font-medium underline">
                                    Cargar plantilla de ejemplo
                                </button>
                            </div>
                        </div>

                        <!-- Editor Visual y Vista Previa -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Editor Visual -->
                            <div>
                                                                 <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                     Editor Visual * (Tiptap)
                                 </label>
                                 <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                     <p>Utiliza el editor Tiptap para crear tu correo con formato profesional. Las variables se insertan automáticamente.</p>
                                 </div>
                                
                                <!-- Barra de herramientas personalizada para variables -->
                                <div class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-t-lg p-2 mb-2">
                                    <button type="button" onclick="insertVariable()" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-lg transition-colors duration-200 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Insertar Variable
                                    </button>
                                </div>
                                
                                                                 <!-- Área de edición Tiptap -->
                                 <x-tiptap-editor 
                                     name="cuerpo_html" 
                                     id="cuerpo_html"
                                     :value="old('cuerpo_html')"
                                     :height="400"
                                     placeholder="Escribe tu correo aquí..."
                                 />
                                
                                @error('cuerpo_html')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Vista Previa -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Vista Previa</h3>
                                    <button type="button" id="btn-actualizar-vista-previa" 
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors duration-200">
                                        Actualizar Vista Previa
                                    </button>
                                </div>
                                <div id="vista-previa" class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700 min-h-64">
                                    <p class="text-gray-500 dark:text-gray-400 text-center">Selecciona un tipo y escribe el contenido para ver la vista previa</p>
                                </div>
                            </div>
                        </div>

                        <!-- Cuerpo Texto -->
                        <div>
                            <label for="cuerpo_texto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Cuerpo Texto Plano *
                            </label>
                            <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                <p>Versión en texto plano del cuerpo (sin HTML) - Se genera automáticamente</p>
                            </div>
                            <textarea name="cuerpo_texto" 
                                      id="cuerpo_texto" 
                                      rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
                                      placeholder="El texto plano se generará automáticamente..."
                                      readonly>{{ old('cuerpo_texto') }}</textarea>
                            @error('cuerpo_texto')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-3">
                            <button type="button" id="btn-limpiar" 
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                                Limpiar
                            </button>
                            <button type="submit" 
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 hover:scale-105">
                                Crear Cuerpo de Correo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para insertar variables -->
    <div id="modal-variables" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Insertar Variable
                    </h3>
                    <div id="variables-disponibles" class="space-y-2 mb-4">
                        <!-- Las variables se cargarán aquí dinámicamente -->
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModalVariables()" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

         <script>
         let editor; // Variable global para el editor Tiptap
         
         // Esperar a que Tiptap esté listo
         document.addEventListener('DOMContentLoaded', function() {
             // Buscar el editor Tiptap
             const tiptapWrapper = document.querySelector('[x-data*="tiptapEditor"]');
             if (tiptapWrapper) {
                 // Esperar a que Alpine.js esté listo
                 setTimeout(() => {
                     const tiptapInstance = tiptapWrapper.__x.$data;
                     if (tiptapInstance && tiptapInstance.editor) {
                         editor = tiptapInstance;
                         
                         // Configurar eventos del editor
                         const editorElement = document.getElementById('cuerpo_html');
                         if (editorElement) {
                             editorElement.addEventListener('tiptap:change', function() {
                                 actualizarCampos();
                                 actualizarVistaPrevia();
                             });
                         }
                     }
                 }, 100);
             }
         });

        const tipoSelect = document.getElementById('tipo');
        const infoVariables = document.getElementById('info-variables');
        const variablesTipo = document.getElementById('variables-tipo');
        const btnPlantillaEjemplo = document.getElementById('btn-plantilla-ejemplo');
        const btnActualizarVistaPrevia = document.getElementById('btn-actualizar-vista-previa');
        const btnLimpiar = document.getElementById('btn-limpiar');
        const vistaPrevia = document.getElementById('vista-previa');
        const cuerpoHtml = document.getElementById('cuerpo_html');
        const cuerpoTexto = document.getElementById('cuerpo_texto');
        const modalVariables = document.getElementById('modal-variables');

        // Variables disponibles por tipo
        const variables = @json($variables);

        // Función para insertar variable
        function insertVariable() {
            const tipo = tipoSelect.value;
            if (!tipo) {
                alert('Primero selecciona un tipo de correo');
                return;
            }

            const variablesDisponibles = document.getElementById('variables-disponibles');
            variablesDisponibles.innerHTML = '';

            Object.entries(variables[tipo]).forEach(([variable, descripcion]) => {
                const button = document.createElement('button');
                button.className = 'w-full text-left p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-sm';
                button.innerHTML = '<code class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">$' + variable + '</code> - ' + descripcion;
                button.onclick = () => insertarVariableEnEditor(variable);
                variablesDisponibles.appendChild(button);
            });

            modalVariables.classList.remove('hidden');
        }

        // Función para cerrar modal
        function cerrarModalVariables() {
            modalVariables.classList.add('hidden');
        }

                 // Función para insertar variable en el editor Tiptap
         function insertarVariableEnEditor(variable) {
             const variableText = '{{' + variable + '}}';
             if (editor) {
                 editor.insertContent('<span class="variable">' + variableText + '</span>');
             }
             cerrarModalVariables();
         }

        // Función para actualizar campos ocultos
        function actualizarCampos() {
            if (editor) {
                const htmlContent = editor.getContent();
                cuerpoHtml.value = htmlContent;
                
                // Generar texto plano automáticamente
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = htmlContent;
                cuerpoTexto.value = tempDiv.textContent || tempDiv.innerText || '';
            }
        }

        // Mostrar/ocultar información de variables según el tipo seleccionado
        tipoSelect.addEventListener('change', function() {
            const tipo = this.value;
            if (tipo) {
                const variablesTipoSeleccionado = variables[tipo];
                if (variablesTipoSeleccionado) {
                    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
                    Object.entries(variablesTipoSeleccionado).forEach(([variable, descripcion]) => {
                        html += '<div class="flex items-center gap-2">' +
                            '<code class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-sm font-mono">$' + variable + '</code>' +
                            '<span class="text-sm">' + descripcion + '</span>' +
                            '</div>';
                    });
                    html += '</div>';
                    variablesTipo.innerHTML = html;
                    infoVariables.classList.remove('hidden');
                }
            } else {
                infoVariables.classList.add('hidden');
            }
        });

        // Cargar plantilla de ejemplo
        btnPlantillaEjemplo.addEventListener('click', function() {
            const tipo = tipoSelect.value;
            if (!tipo) {
                alert('Primero selecciona un tipo de correo');
                return;
            }

            fetch('{{ route("cuerpos-correo.plantilla-ejemplo") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ tipo: tipo })
            })
            .then(response => response.json())
            .then(data => {
                if (editor) {
                    editor.setContent(data.html); // Usar setContent para reemplazar el contenido
                }
                actualizarCampos();
                actualizarVistaPrevia();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar la plantilla de ejemplo');
            });
        });

        // Actualizar vista previa
        btnActualizarVistaPrevia.addEventListener('click', actualizarVistaPrevia);

        function actualizarVistaPrevia() {
            const tipo = tipoSelect.value;
            const html = editor ? editor.getContent() : cuerpoHtml.value;
            
            if (!tipo || !html) {
                vistaPrevia.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Selecciona un tipo y escribe el contenido para ver la vista previa</p>';
                return;
            }

            fetch('{{ route("cuerpos-correo.vista-previa") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    tipo: tipo,
                    cuerpo_html: html
                })
            })
            .then(response => response.json())
            .then(data => {
                vistaPrevia.innerHTML = data.html;
            })
            .catch(error => {
                console.error('Error:', error);
                vistaPrevia.innerHTML = '<p class="text-red-500 text-center">Error al generar la vista previa</p>';
            });
        }

        // Limpiar formulario
        btnLimpiar.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que quieres limpiar el formulario?')) {
                document.querySelector('form').reset();
                if (editor) {
                    editor.setContent(''); // Limpiar el contenido del editor
                }
                infoVariables.classList.add('hidden');
                vistaPrevia.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Selecciona un tipo y escribe el contenido para ver la vista previa</p>';
                actualizarCampos();
            }
        });

        // Actualizar vista previa automáticamente al escribir (con debounce)
        let timeoutId;
        if (editor) {
            editor.on('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(actualizarVistaPrevia, 1000);
            });
        }

        // Mostrar información de variables si ya hay un tipo seleccionado
        if (tipoSelect.value) {
            tipoSelect.dispatchEvent(new Event('change'));
        }

        // Cerrar modal al hacer clic fuera
        modalVariables.addEventListener('click', function(e) {
            if (e.target === modalVariables) {
                cerrarModalVariables();
            }
        });

        // Actualizar campos antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function() {
            actualizarCampos();
        });
    </script>
</x-app-layout>

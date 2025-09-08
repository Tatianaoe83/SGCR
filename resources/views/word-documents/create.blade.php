<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Subir Documento Word') }}
            </h2>
            <a href="{{ route('word-documents.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('word-documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- Información del archivo -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                        Información sobre la subida de archivos
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Formatos soportados: .doc, .docx</li>
                                            <li>Tamaño máximo: 600 KB</li>
                                            <li>El contenido se extraerá automáticamente del documento</li>
                                            <li>Los metadatos se capturarán del archivo original</li>
                                            <li>El contenido se convertirá automáticamente a Markdown inteligente</li>
                                            <li>Puedes escribir contenido Markdown personalizado o usar el generado automáticamente</li>
                                            <li><strong>NUEVO:</strong> Mejor detección de tablas, numeración y estructura del documento</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advertencia sobre archivos .doc -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        Importante sobre archivos .doc
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>Los archivos .doc (formato antiguo) pueden tener limitaciones en la extracción automática de contenido. Si experimentas problemas, considera:</p>
                                        <ul class="list-disc list-inside space-y-1 mt-2">
                                            <li>Convertir el archivo a formato .docx antes de subirlo</li>
                                            <li>Usar el editor de Markdown para ingresar el contenido manualmente</li>
                                            <li>El sistema intentará métodos alternativos para extraer el contenido</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información sobre mejoras implementadas -->
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                        Mejoras Implementadas
                                    </h3>
                                    <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                                        <p>El sistema ahora detecta y preserva mejor:</p>
                                        <ul class="list-disc list-inside space-y-1 mt-2">
                                            <li><strong>Tablas:</strong> Encabezados y datos organizados en formato Markdown</li>
                                            <li><strong>Numeración:</strong> Puntos principales (1., 2.) y secundarios (1.1., 1.2.)</li>
                                            <li><strong>Listas:</strong> Con viñetas y numeradas, manteniendo la estructura</li>
                                            <li><strong>Títulos:</strong> Diferentes niveles de encabezados</li>
                                            <li><strong>Estructura:</strong> Mejor organización del contenido del documento</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selección de archivo -->
                        <div>
                            <label for="archivo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Archivo Word <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-word text-4xl text-blue-600 mb-4"></i>
                                    <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                        <label for="archivo" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Subir archivo</span>
                                            <input id="archivo" name="archivo" type="file" class="sr-only" accept=".doc,.docx" required>
                                        </label>
                                        <p class="pl-1">o arrastrar y soltar</p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        DOC, DOCX hasta 600KB
                                    </p>
                                </div>
                            </div>
                            @error('archivo')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Información del documento -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="tipo_documento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Documento
                                </label>
                                <input type="text" name="tipo_documento" id="tipo_documento" 
                                       value="{{ old('tipo_documento') }}"
                                       placeholder="Ej: Manual, Procedimiento, Política..."
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('tipo_documento')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Versión
                                </label>
                                <input type="text" name="version" id="version" 
                                       value="{{ old('version') }}"
                                       placeholder="Ej: 1.0, 2.1..."
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('version')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="autor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Autor
                                </label>
                                <input type="text" name="autor" id="autor" 
                                       value="{{ old('autor') }}"
                                       placeholder="Nombre del autor..."
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('autor')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Vista previa del archivo seleccionado -->
                        <div id="vista-previa" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Archivo Seleccionado
                            </label>
                            <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-file-word text-2xl text-blue-600 mr-3"></i>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" id="nombre-archivo"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" id="tamanio-archivo"></div>
                                    </div>
                                    <button type="button" id="quitar-archivo" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido en Markdown -->
                        <div>
                            <label for="contenido_markdown" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Contenido en Markdown <span class="text-gray-500 text-xs">(Opcional - se puede editar después)</span>
                            </label>
                            <div class="relative">
                                <textarea 
                                    name="contenido_markdown" 
                                    id="contenido_markdown" 
                                    rows="8"
                                    placeholder="Escribe o pega aquí el contenido en formato Markdown...&#10;&#10;Ejemplo:&#10;# Título Principal&#10;&#10;## Subtítulo&#10;&#10;Este es un párrafo de texto normal.&#10;&#10;- Elemento de lista 1&#10;- Elemento de lista 2&#10;&#10;**Texto en negrita** e *texto en cursiva*"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                                >{{ old('contenido_markdown') }}</textarea>
                                
                                <!-- Barra de herramientas Markdown -->
                                <div class="absolute top-2 right-2 flex space-x-1">
                                    <button type="button" onclick="insertMarkdown('**', '**')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded" title="Negrita">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" onclick="insertMarkdown('*', '*')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded" title="Cursiva">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" onclick="insertMarkdown('# ')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded" title="Título H1">
                                        <i class="fas fa-heading"></i>
                                    </button>
                                    <button type="button" onclick="insertMarkdown('- ')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded" title="Lista">
                                        <i class="fas fa-list-ul"></i>
                                    </button>
                                    <button type="button" onclick="insertMarkdown('`', '`')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded" title="Código">
                                        <i class="fas fa-code"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                El contenido se guardará en formato Markdown y podrás editarlo posteriormente. Si no lo completas, se generará automáticamente a partir del contenido del documento Word con formato Markdown inteligente.
                            </p>
                            @error('contenido_markdown')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('word-documents.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
                                <i class="fas fa-upload mr-2"></i>
                                Subir Documento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const inputArchivo = document.getElementById('archivo');
        const vistaPrevia = document.getElementById('vista-previa');
        const nombreArchivo = document.getElementById('nombre-archivo');
        const tamanioArchivo = document.getElementById('tamanio-archivo');
        const quitarArchivo = document.getElementById('quitar-archivo');

        // Función para formatear el tamaño del archivo
        function formatearTamanio(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Mostrar vista previa cuando se selecciona un archivo
        inputArchivo.addEventListener('change', function(e) {
            const archivo = e.target.files[0];
            if (archivo) {
                nombreArchivo.textContent = archivo.name;
                tamanioArchivo.textContent = formatearTamanio(archivo.size);
                vistaPrevia.classList.remove('hidden');
            }
        });

        // Quitar archivo seleccionado
        quitarArchivo.addEventListener('click', function() {
            inputArchivo.value = '';
            vistaPrevia.classList.add('hidden');
        });

        // Drag and drop
        const dropZone = document.querySelector('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-indigo-400', 'bg-indigo-50');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-indigo-400', 'bg-indigo-50');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const archivos = dt.files;
            
            if (archivos.length > 0) {
                inputArchivo.files = archivos;
                inputArchivo.dispatchEvent(new Event('change'));
            }
        }

        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const archivo = inputArchivo.files[0];
            
            if (!archivo) {
                e.preventDefault();
                alert('Por favor selecciona un archivo Word.');
                return;
            }

            // Validar tipo de archivo
            const tiposPermitidos = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!tiposPermitidos.includes(archivo.type) && !archivo.name.match(/\.(doc|docx)$/i)) {
                e.preventDefault();
                alert('Por favor selecciona un archivo Word válido (.doc o .docx).');
                return;
            }

            // Validar tamaño (configurable)
            const maxSize = {{ config('word-documents.max_file_size_kb', 600) }} * 1024; // KB en bytes
            if (archivo.size > maxSize) {
                e.preventDefault();
                alert('El archivo es demasiado grande. El tamaño máximo permitido es {{ config("word-documents.max_file_size_kb", 600) }}KB.');
                return;
            }
        });

        // Funciones para insertar Markdown
        function insertMarkdown(before, after = '') {
            const textarea = document.getElementById('contenido_markdown');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end);
            
            const newText = text.substring(0, start) + before + selectedText + after + text.substring(end);
            textarea.value = newText;
            
            // Reposicionar cursor
            if (selectedText.length > 0) {
                textarea.selectionStart = start + before.length;
                textarea.selectionEnd = start + before.length + selectedText.length;
            } else {
                textarea.selectionStart = start + before.length;
                textarea.selectionEnd = start + before.length;
            }
            
            textarea.focus();
        }
    </script>
    @endpush
</x-app-layout>

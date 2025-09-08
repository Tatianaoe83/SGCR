<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Documento Word') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('word-documents.show', $wordDocument) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-eye mr-2"></i>Ver
                </a>
                <a href="{{ route('word-documents.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('word-documents.update', $wordDocument) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Información del archivo actual -->
                        <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-file-word text-2xl text-blue-600 mr-3"></i>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $wordDocument->nombre_original }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $wordDocument->tamanio_formateado }} • Subido el {{ $wordDocument->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <a href="{{ route('word-documents.descargar', $wordDocument) }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Estado del documento -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        Estado del Documento
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $wordDocument->clase_estado }}">
                                            {{ $wordDocument->estado_formateado }}
                                        </span>
                                        @if($wordDocument->estado === 'error')
                                            <p class="mt-2">Error: {{ $wordDocument->error_mensaje }}</p>
                                            <a href="{{ route('word-documents.reprocesar', $wordDocument) }}" 
                                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 mt-2">
                                                <i class="fas fa-redo mr-2"></i>Reprocesar
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del documento -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="tipo_documento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Documento
                                </label>
                                <input type="text" name="tipo_documento" id="tipo_documento" 
                                       value="{{ old('tipo_documento', $wordDocument->tipo_documento) }}"
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
                                       value="{{ old('version', $wordDocument->version) }}"
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
                                       value="{{ old('autor', $wordDocument->autor) }}"
                                       placeholder="Nombre del autor..."
                                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('autor')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Contenido en Markdown -->
                        <div>
                            <label for="contenido_markdown" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Contenido en Markdown
                            </label>
                            <div class="relative">
                                <textarea 
                                    name="contenido_markdown" 
                                    id="contenido_markdown" 
                                    rows="8"
                                    placeholder="Escribe o pega aquí el contenido en formato Markdown..."
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                                >{{ old('contenido_markdown', $wordDocument->contenido_markdown) }}</textarea>
                                
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
                                El contenido se guardará en formato Markdown y podrás editarlo posteriormente.
                            </p>
                            @error('contenido_markdown')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Información del contenido -->
                        @if($wordDocument->contenido_texto)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Información del Contenido</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ $wordDocument->contenido_estructurado['total_lineas'] ?? 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Líneas</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ $wordDocument->contenido_estructurado['total_caracteres'] ?? 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Caracteres</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-yellow-600">
                                        {{ count($wordDocument->contenido_estructurado['parrafos'] ?? []) }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Párrafos</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600">
                                        {{ count($wordDocument->contenido_estructurado['titulos'] ?? []) }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Títulos</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Metadatos del archivo -->
                        @if($wordDocument->metadatos)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Metadatos del Archivo Original</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                @foreach($wordDocument->metadatos as $key => $value)
                                    @if($value)
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300 capitalize">
                                            {{ str_replace('_', ' ', $key) }}:
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-400 ml-2">{{ $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Botones de acción -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('word-documents.show', $wordDocument) }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
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

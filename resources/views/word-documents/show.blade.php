<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detalles del Documento') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('word-documents.edit', $wordDocument) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-edit mr-2"></i>Editar
                </a>
                <a href="{{ route('word-documents.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Información general del documento -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-16 w-16">
                                <i class="fas fa-file-word text-5xl text-blue-600"></i>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $wordDocument->nombre_original }}
                                </h3>
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $wordDocument->clase_estado }}">
                                        {{ $wordDocument->estado_formateado }}
                                    </span>
                                    <span>{{ $wordDocument->tamanio_formateado }}</span>
                                    <span>Subido el {{ $wordDocument->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('word-documents.descargar', $wordDocument) }}" 
                               class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                <i class="fas fa-download mr-2"></i>Descargar
                            </a>
                            @if($wordDocument->estado === 'error')
                            <form action="{{ route('word-documents.reprocesar', $wordDocument) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-redo mr-2"></i>Reprocesar
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información del documento -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Información del Documento</h3>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Documento</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $wordDocument->tipo_documento ?: 'Sin especificar' }}
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Versión</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $wordDocument->version ?: 'Sin especificar' }}
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Autor</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $wordDocument->autor ?: 'Sin especificar' }}
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $wordDocument->fecha_creacion ? $wordDocument->fecha_creacion->format('d/m/Y H:i') : 'Sin especificar' }}
                                    </dd>
                                </div>
                                
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Última Modificación</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $wordDocument->fecha_modificacion ? $wordDocument->fecha_modificacion->format('d/m/Y H:i') : 'Sin especificar' }}
                                    </dd>
                                </div>
                                
                                @if($wordDocument->estado === 'error')
                                <div>
                                    <dt class="text-sm font-medium text-red-500">Error</dt>
                                    <dd class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ $wordDocument->error_mensaje }}
                                    </dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Metadatos del archivo -->
                    @if($wordDocument->metadatos)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Metadatos del Archivo</h3>
                            <dl class="space-y-3">
                                @foreach($wordDocument->metadatos as $key => $value)
                                    @if($value)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 capitalize">
                                            {{ str_replace('_', ' ', $key) }}
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $value }}
                                        </dd>
                                    </div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Contenido del documento -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Contenido del Documento</h3>
                                @if($wordDocument->contenido_estructurado)
                                <div class="flex space-x-2">
                                    <button type="button" id="btn-texto" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                        Texto Completo
                                    </button>
                                    <button type="button" id="btn-estructurado" class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                        Vista Estructurada
                                    </button>
                                    <button type="button" id="btn-markdown" class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                        Markdown
                                    </button>
                                </div>
                                @endif
                            </div>

                            <!-- Vista de texto completo -->
                            <div id="vista-texto" class="space-y-4">
                                @if($wordDocument->contenido_texto)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                                        <pre class="whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $wordDocument->contenido_texto }}</pre>
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-file-alt text-4xl mb-4"></i>
                                        <p>No hay contenido disponible para mostrar</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Vista estructurada -->
                            @if($wordDocument->contenido_estructurado)
                            <div id="vista-estructurada" class="hidden space-y-6">
                                <!-- Estadísticas -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ $wordDocument->contenido_estructurado['total_lineas'] }}</div>
                                        <div class="text-sm text-blue-600">Líneas</div>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-green-600">{{ $wordDocument->contenido_estructurado['total_caracteres'] }}</div>
                                        <div class="text-sm text-green-600">Caracteres</div>
                                    </div>
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-yellow-600">{{ count($wordDocument->contenido_estructurado['parrafos']) }}</div>
                                        <div class="text-sm text-yellow-600">Párrafos</div>
                                    </div>
                                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg text-center">
                                        <div class="text-2xl font-bold text-purple-600">{{ count($wordDocument->contenido_estructurado['titulos']) }}</div>
                                        <div class="text-sm text-purple-600">Títulos</div>
                                    </div>
                                </div>

                                <!-- Títulos -->
                                @if(count($wordDocument->contenido_estructurado['titulos']) > 0)
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">Títulos Detectados</h4>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <ul class="space-y-2">
                                            @foreach($wordDocument->contenido_estructurado['titulos'] as $titulo)
                                            <li class="text-sm text-gray-700 dark:text-gray-300">
                                                <i class="fas fa-heading text-blue-500 mr-2"></i>{{ $titulo }}
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endif

                                <!-- Listas -->
                                @if(count($wordDocument->contenido_estructurado['listas']) > 0)
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">Listas Detectadas</h4>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <ul class="space-y-2">
                                            @foreach($wordDocument->contenido_estructurado['listas'] as $lista)
                                            <li class="text-sm text-gray-700 dark:text-gray-300">
                                                <i class="fas fa-list text-green-500 mr-2"></i>{{ $lista }}
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endif

                                <!-- Párrafos -->
                                @if(count($wordDocument->contenido_estructurado['parrafos']) > 0)
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">Párrafos</h4>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto">
                                        @foreach($wordDocument->contenido_estructurado['parrafos'] as $parrafo)
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3 pb-3 border-b border-gray-200 dark:border-gray-600">
                                            {{ $parrafo }}
                                        </p>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Vista de Markdown -->
                            <div id="vista-markdown" class="hidden space-y-4">
                                @if($wordDocument->contenido_markdown)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">Contenido en Markdown</h4>
                                            <button type="button" onclick="copiarMarkdown()" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                                <i class="fas fa-copy mr-1"></i>Copiar
                                            </button>
                                        </div>
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 max-h-96 overflow-y-auto">
                                            <pre class="whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $wordDocument->contenido_markdown }}</pre>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-markdown text-4xl mb-4"></i>
                                        <p>No hay contenido Markdown disponible</p>
                                        <p class="text-sm mt-2">Puedes agregar contenido en Markdown editando el documento</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const btnTexto = document.getElementById('btn-texto');
        const btnEstructurado = document.getElementById('btn-estructurado');
        const btnMarkdown = document.getElementById('btn-markdown');
        const vistaTexto = document.getElementById('vista-texto');
        const vistaEstructurada = document.getElementById('vista-estructurada');
        const vistaMarkdown = document.getElementById('vista-markdown');

        function cambiarVista(vistaActiva, btnActivo) {
            // Ocultar todas las vistas
            vistaTexto.classList.add('hidden');
            vistaEstructurada.classList.add('hidden');
            vistaMarkdown.classList.add('hidden');
            
            // Resetear todos los botones
            btnTexto.classList.remove('bg-blue-600', 'text-white');
            btnTexto.classList.add('bg-gray-300', 'text-gray-700');
            btnEstructurado.classList.remove('bg-blue-600', 'text-white');
            btnEstructurado.classList.add('bg-gray-300', 'text-gray-700');
            btnMarkdown.classList.remove('bg-blue-600', 'text-white');
            btnMarkdown.classList.add('bg-gray-300', 'text-gray-700');
            
            // Mostrar vista activa y activar botón
            vistaActiva.classList.remove('hidden');
            btnActivo.classList.add('bg-blue-600', 'text-white');
            btnActivo.classList.remove('bg-gray-300', 'text-gray-700');
        }

        if (btnTexto && btnEstructurado && btnMarkdown) {
            btnTexto.addEventListener('click', function() {
                cambiarVista(vistaTexto, btnTexto);
            });

            btnEstructurado.addEventListener('click', function() {
                cambiarVista(vistaEstructurada, btnEstructurado);
            });

            btnMarkdown.addEventListener('click', function() {
                cambiarVista(vistaMarkdown, btnMarkdown);
            });
        }

        // Función para copiar contenido Markdown
        function copiarMarkdown() {
            const contenido = `{{ $wordDocument->contenido_markdown }}`;
            navigator.clipboard.writeText(contenido).then(function() {
                // Mostrar notificación de éxito
                const boton = event.target.closest('button');
                const textoOriginal = boton.innerHTML;
                boton.innerHTML = '<i class="fas fa-check mr-1"></i>Copiado';
                boton.classList.add('text-green-600');
                
                setTimeout(() => {
                    boton.innerHTML = textoOriginal;
                    boton.classList.remove('text-green-600');
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                alert('Error al copiar el contenido');
            });
        }
    </script>
    @endpush
</x-app-layout>

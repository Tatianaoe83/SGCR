<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ver Cuerpo de Correo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                                {{ $cuerpo->nombre }}
                            </h1>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ \App\Services\PlantillaCorreoService::getDescripcionTipo($cuerpo->tipo) }}
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('cuerpos-correo.edit', $cuerpo->id_cuerpo) }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                </svg>
                                Editar
                            </a>
                            <a href="{{ route('cuerpos-correo.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 hover:scale-105 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Información General -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tipo de Correo</h3>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($cuerpo->tipo === 'acceso') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($cuerpo->tipo === 'implementacion') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @endif">
                                    {{ $cuerpo->tipo_nombre }}
                                </span>
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Estado</h3>
                            <p class="text-lg font-semibold">
                                @if($cuerpo->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                        Inactivo
                                    </span>
                                @endif
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha de Creación</h3>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $cuerpo->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <!-- Variables Disponibles -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            Variables Disponibles
                        </h3>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($variables as $variable => $descripcion)
                                    <div class="flex items-center gap-2">
                                        <code class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-sm font-mono">{{$variable}}</code>
                                        <span class="text-sm text-blue-800 dark:text-blue-200">{{ $descripcion }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Pestañas de Contenido -->
                    <div class="mb-8">
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button onclick="mostrarTab('html')" 
                                        id="tab-html"
                                        class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600">
                                    Cuerpo HTML
                                </button>
                                <button onclick="mostrarTab('texto')" 
                                        id="tab-texto"
                                        class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600">
                                    Cuerpo Texto
                                </button>
                                <button onclick="mostrarTab('previa')" 
                                        id="tab-previa"
                                        class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600">
                                    Vista Previa
                                </button>
                            </nav>
                        </div>

                        <!-- Contenido de las pestañas -->
                        <div class="mt-6">
                            <!-- Tab HTML -->
                            <div id="content-html" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Código HTML</h4>
                                        <button onclick="copiarAlPortapapeles('html-content')" 
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                            Copiar código
                                        </button>
                                    </div>
                                    <pre id="html-content" class="bg-white dark:bg-gray-800 p-4 rounded border text-sm overflow-x-auto text-gray-800 dark:text-gray-200">{{ $cuerpo->cuerpo_html }}</pre>
                                </div>
                            </div>

                            <!-- Tab Texto -->
                            <div id="content-texto" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Texto Plano</h4>
                                        <button onclick="copiarAlPortapapeles('texto-content')" 
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                            Copiar texto
                                        </button>
                                    </div>
                                    <div id="texto-content" class="bg-white dark:bg-gray-800 p-4 rounded border text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $cuerpo->cuerpo_texto }}</div>
                                </div>
                            </div>

                            <!-- Tab Vista Previa -->
                            <div id="content-previa" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Vista Previa con Datos de Ejemplo</h4>
                                    <div class="bg-white dark:bg-gray-800 p-6 rounded border min-h-64">
                                        {!! $vistaPrevia !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Adicional -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Información Adicional</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <div>
                                <span class="font-medium">Última actualización:</span> 
                                {{ $cuerpo->updated_at->format('d/m/Y H:i') }}
                            </div>
                            <div>
                                <span class="font-medium">ID del cuerpo:</span> 
                                {{ $cuerpo->id_cuerpo }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar pestañas
        function mostrarTab(tabName) {
            // Ocultar todos los contenidos
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
            
            // Remover clase activa de todos los botones
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('border-blue-500', 'text-blue-600', 'dark:border-blue-400', 'dark:text-blue-400'));
            
            // Mostrar contenido seleccionado
            document.getElementById(`content-${tabName}`).classList.remove('hidden');
            
            // Activar botón seleccionado
            document.getElementById(`tab-${tabName}`).classList.add('border-blue-500', 'text-blue-600', 'dark:border-blue-400', 'dark:text-blue-400');
        }

        // Función para copiar al portapapeles
        function copiarAlPortapapeles(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent || element.innerText;
            
            navigator.clipboard.writeText(text).then(function() {
                // Mostrar notificación temporal
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '¡Copiado!';
                button.classList.add('text-green-600');
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('text-green-600');
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                alert('Error al copiar al portapapeles');
            });
        }

        // Mostrar la primera pestaña por defecto
        document.addEventListener('DOMContentLoaded', function() {
            mostrarTab('html');
        });
    </script>
</x-app-layout>

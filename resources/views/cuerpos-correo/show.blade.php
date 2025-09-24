<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">
            <div class="mb-4 sm:mb-0">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('cuerpos-correo.index') }}" 
                       class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver a plantillas
                    </a>
                </div>
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold mt-2">
                    {{ $tpl->nombre }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Vista previa de la plantilla de correo
                </p>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('cuerpos-correo.edit', $tpl->id_cuerpo) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Editar Plantilla
                </a>
                
                <button onclick="downloadHTML()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar HTML
                </button>
            </div>
        </div>

        <!-- Información de la plantilla -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Información básica -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                        Información de la Plantilla
                    </h2>
                    
                    <div class="space-y-4">
                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                        @if($tpl->tipo === 'acceso') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($tpl->tipo === 'implementacion') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($tpl->tipo === 'fecha_vencimiento') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($tpl->tipo === 'agradecimiento') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                        @endif">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $tpl->tipo_nombre }}
                            </span>
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($tpl->activo) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @endif">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    @if($tpl->activo)
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    @else
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    @endif
                                </svg>
                                {{ $tpl->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        <!-- Asunto -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asunto</label>
                            <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 p-2 rounded border">
                                {{ $tpl->subject ?: 'Sin asunto definido' }}
                            </p>
                        </div>

                        <!-- Fechas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fechas</label>
                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                <div>Creado: {{ $tpl->created_at ? $tpl->created_at->format('d/m/Y H:i') : 'N/A' }}</div>
                                <div>Actualizado: {{ $tpl->updated_at ? $tpl->updated_at->format('d/m/Y H:i') : 'N/A' }}</div>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estadísticas</label>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                    <div class="font-bold text-blue-600 dark:text-blue-400" id="char-count">{{ strlen(strip_tags($tpl->cuerpo_html)) }}</div>
                                    <div class="text-blue-500 dark:text-blue-400">Caracteres</div>
                                </div>
                                <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                    <div class="font-bold text-green-600 dark:text-green-400" id="word-count">{{ str_word_count(strip_tags($tpl->cuerpo_html)) }}</div>
                                    <div class="text-green-500 dark:text-green-400">Palabras</div>
                                </div>
                                <div class="text-center p-2 bg-purple-50 dark:bg-purple-900/20 rounded">
                                    <div class="font-bold text-purple-600 dark:text-purple-400" id="variable-count">{{ count($tpl->vars) }}</div>
                                    <div class="text-purple-500 dark:text-purple-400">Variables</div>
                                </div>
                                <div class="text-center p-2 bg-orange-50 dark:bg-orange-900/20 rounded">
                                    <div class="font-bold text-orange-600 dark:text-orange-400" id="image-count">{{ substr_count($tpl->cuerpo_html, '<img') }}</div>
                                    <div class="text-orange-500 dark:text-orange-400">Imágenes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variables disponibles -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm mt-6">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                        Variables Disponibles
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Estas variables se reemplazarán automáticamente al enviar el correo:
                    </p>

                    <div class="space-y-2">
                        @forelse($tpl->vars as $var => $desc)
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                            <div class="flex items-center justify-between mb-1">
                                <code class="text-sm font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">
                                    {{ $var }}
                                </code>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ substr_count($tpl->cuerpo_html, $var) }} uso(s)
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $desc }}</p>
                        </div>
                        @empty
                        <div class="text-center py-4">
                            <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay variables definidas</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Vista previa -->
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                Vista Previa
                            </h2>
                            <div class="flex items-center space-x-2">
                                <!-- Selector de dispositivo -->
                                <div class="flex items-center space-x-1">
                                    <button onclick="setDevice('375px')" 
                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        📱
                                    </button>
                                    <button onclick="setDevice('768px')" 
                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        📱
                                    </button>
                                    <button onclick="setDevice('100%')" 
                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        💻
                                    </button>
                                </div>
                                
                                <!-- Botón de vista previa completa -->
                                <button onclick="openFullPreview()" 
                                        class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Abrir en nueva ventana
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div id="preview-container" class="mx-auto" style="max-width: 100%;">
                            <div class="rounded-lg overflow-hidden bg-white border shadow-sm">
                                <iframe id="preview-frame" 
                                        class="w-full block" 
                                        style="height: 600px; border: none;"
                                        srcdoc='@php
                                            $srcdoc = "<!doctype html><html><head><meta charset=\"utf-8\">
                                            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                                            <style>html,body{margin:0;padding:0;font-family:Arial,sans-serif}</style>
                                            </head><body>{$tpl->cuerpo_html}</body></html>";
                                            echo htmlspecialchars($srcdoc, ENT_QUOTES);
                                        @endphp'>
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para cambiar el tamaño del dispositivo
        function setDevice(width) {
            const container = document.getElementById('preview-container');
            const frame = document.getElementById('preview-frame');
            
            if (width === '100%') {
                container.style.maxWidth = '100%';
                frame.style.height = '600px';
            } else {
                container.style.maxWidth = width;
                frame.style.height = '500px';
            }
        }

        // Función para abrir vista previa completa
        function openFullPreview() {
            const html = @json($tpl->cuerpo_html);
            const previewWindow = window.open('', '_blank', 'width=800,height=600');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Vista Previa - {{ $tpl->nombre }}</title>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            font-family: Arial, sans-serif; 
                            background-color: #f5f5f5; 
                        }
                        .preview-container { 
                            max-width: 600px; 
                            margin: 0 auto; 
                            background: white; 
                            padding: 20px; 
                            border-radius: 8px; 
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                        }
                    </style>
                </head>
                <body>
                    <div class="preview-container">
                        ${html}
                    </div>
                </body>
                </html>
            `);
            previewWindow.document.close();
        }

        // Función para descargar HTML
        function downloadHTML() {
            const html = @json($tpl->cuerpo_html);
            const blob = new Blob([html], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = '{{ Str::slug($tpl->nombre) }}.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Resaltar variables en el iframe (si es posible)
            setTimeout(() => {
                try {
                    const frame = document.getElementById('preview-frame');
                    const frameDoc = frame.contentDocument || frame.contentWindow.document;
                    if (frameDoc) {
                        // Resaltar variables encontradas
                        const variables = @json(array_keys($tpl->vars));
                        variables.forEach(variable => {
                            const regex = new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g');
                            frameDoc.body.innerHTML = frameDoc.body.innerHTML.replace(regex, 
                                `<span style="background-color: #fff3cd; padding: 2px 4px; border-radius: 3px; border: 1px solid #ffeaa7;">${variable}</span>`
                            );
                        });
                    }
                } catch (e) {
                    // Ignorar errores de CORS
                    console.log('No se pudo resaltar variables debido a restricciones de CORS');
                }
            }, 1000);
        });
    </script>
</x-app-layout>

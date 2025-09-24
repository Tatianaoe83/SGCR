<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-screen-2xl mx-auto">

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
                    Vista Previa - {{ $tpl->nombre }}
        </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Previsualización de la plantilla de correo en diferentes dispositivos
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

                <button onclick="printPreview()" 
                        class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Imprimir
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Panel de información y variables -->
            <div class="lg:col-span-1">
                <!-- Información de la plantilla -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                        Información
                    </h2>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                        @if($tpl->tipo === 'acceso') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($tpl->tipo === 'implementacion') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($tpl->tipo === 'fecha_vencimiento') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($tpl->tipo === 'agradecimiento') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                        @endif">
                                {{ $tpl->tipo_nombre }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Estado</label>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                        @if($tpl->activo) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @endif">
                                {{ $tpl->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Asunto</label>
                            <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 p-2 rounded text-xs">
                                {{ $tpl->subject ?: 'Sin asunto definido' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Variables disponibles -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            Variables
                        </h2>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ count($tpl->vars) }}
                        </span>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Variables disponibles en esta plantilla:
                    </p>

                    <div class="space-y-2">
                        @forelse($tpl->vars as $var => $desc)
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                            <div class="flex items-center justify-between mb-1">
                                <code class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">
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
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay variables</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Vista previa principal -->
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <!-- Controles del dispositivo -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                Vista Previa
                            </h2>
                            
                            <div class="flex items-center space-x-2">
                                <!-- Selector de dispositivo -->
                                <div class="flex items-center space-x-1">
                            <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors {{ request('device', 'mobile') === 'mobile' ? 'bg-blue-50 border-blue-300 text-blue-700' : '' }}"
                                        onclick="setDevice('mobile')">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                Móvil
                            </button>
                            <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors {{ request('device') === 'tablet' ? 'bg-blue-50 border-blue-300 text-blue-700' : '' }}"
                                        onclick="setDevice('tablet')">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                Tablet
                            </button>
                            <button type="button"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors {{ request('device') === 'desktop' ? 'bg-blue-50 border-blue-300 text-blue-700' : '' }}"
                                        onclick="setDevice('desktop')">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        Escritorio
                                    </button>
                                </div>

                                <!-- Zoom -->
                                <div class="flex items-center space-x-1">
                                    <button onclick="changeZoom(-0.1)" 
                                            class="p-1.5 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <span class="px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300" id="zoom-level">100%</span>
                                    <button onclick="changeZoom(0.1)" 
                                            class="p-1.5 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                            </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor de vista previa -->
                    <div class="p-6 bg-gray-50 dark:bg-gray-900">
                        <div id="mailFrameWrap" class="mx-auto transition-all duration-300" style="max-width: 375px;">
                            <div class="rounded-lg overflow-hidden bg-white border shadow-lg">
                                <!-- Barra de navegación del dispositivo simulado -->
                                <div class="bg-gray-100 dark:bg-gray-800 px-4 py-2 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                                    <span id="device-info">iPhone 12 Pro</span>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                    </div>
                                </div>
                                
                                <iframe id="mailFrame"
                                    class="w-full block transition-all duration-300"
                                    style="height: 600px; border: none;"
                                referrerpolicy="no-referrer"
                                sandbox="allow-same-origin"
                                srcdoc='@php
              $srcdoc = "<!doctype html><html><head><meta charset=\"utf-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                                        <style>
                                            html,body{margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,sans-serif}
                                            body{line-height:1.6;color:#333}
                                            .variable{background-color:#fff3cd;padding:2px 4px;border-radius:3px;border:1px solid #ffeaa7;font-weight:bold}
                                        </style>
              </head><body>{$html}</body></html>";
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
        let currentZoom = 1;
        let currentDevice = 'mobile';

        function setDevice(device) {
            const wrap = document.getElementById('mailFrameWrap');
            const frame = document.getElementById('mailFrame');
            const deviceInfo = document.getElementById('device-info');
            
            currentDevice = device;
            
            switch(device) {
                case 'mobile':
                    wrap.style.maxWidth = '375px';
                    frame.style.height = '600px';
                    deviceInfo.textContent = 'iPhone 12 Pro';
                    break;
                case 'tablet':
                    wrap.style.maxWidth = '768px';
                    frame.style.height = '500px';
                    deviceInfo.textContent = 'iPad Pro';
                    break;
                case 'desktop':
                    wrap.style.maxWidth = '100%';
                    frame.style.height = '600px';
                    deviceInfo.textContent = 'Desktop';
                    break;
            }
            
            // Actualizar botones activos
            document.querySelectorAll('[onclick^="setDevice"]').forEach(btn => {
                btn.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-700');
                btn.classList.add('text-gray-700', 'dark:text-gray-300', 'bg-white', 'dark:bg-gray-700');
            });
            
            event.target.classList.remove('text-gray-700', 'dark:text-gray-300', 'bg-white', 'dark:bg-gray-700');
            event.target.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-700');
        }

        function changeZoom(delta) {
            currentZoom = Math.max(0.5, Math.min(2, currentZoom + delta));
            const wrap = document.getElementById('mailFrameWrap');
            wrap.style.transform = `scale(${currentZoom})`;
            document.getElementById('zoom-level').textContent = Math.round(currentZoom * 100) + '%';
        }

        function downloadHTML() {
            const html = @json($tpl->cuerpo_html);
            const blob = new Blob([html], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = '{{ Str::slug($tpl->nombre) }}-preview.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function printPreview() {
            const html = @json($tpl->cuerpo_html);
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Imprimir - {{ $tpl->nombre }}</title>
                    <meta charset="utf-8">
                    <style>
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            font-family: Arial, sans-serif; 
                            background-color: white;
                        }
                        @media print {
                            body { margin: 0; padding: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${html}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                            }, 1000);
                        }
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Establecer dispositivo inicial
            setDevice('mobile');
            
            // Resaltar variables en el iframe después de cargar
            setTimeout(() => {
                try {
                    const frame = document.getElementById('mailFrame');
                    const frameDoc = frame.contentDocument || frame.contentWindow.document;
                    if (frameDoc) {
                        const variables = @json(array_keys($tpl->vars));
                        variables.forEach(variable => {
                            const regex = new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g');
                            frameDoc.body.innerHTML = frameDoc.body.innerHTML.replace(regex, 
                                `<span class="variable">${variable}</span>`
                            );
                        });
                    }
                } catch (e) {
                    console.log('No se pudo resaltar variables debido a restricciones de CORS');
                }
            }, 1000);
        });
    </script>
</x-app-layout>
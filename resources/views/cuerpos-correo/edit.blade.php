<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor de correo: {{ $tpl->nombre }}</title>

    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" />

    <style>
        #editor-shell {
            height: calc(100vh - 14rem);
            min-height: 500px;
        }

        #gjs {
            height: 100% !important;
            min-height: 500px;
        }

        .gjs-cv-canvas,
        .gjs-frame-wrapper {
            height: 100% !important;
        }

        /* Estilos adicionales para GrapesJS */
        .gjs-editor {
            height: 100% !important;
        }

        .gjs-blocks-cs {
            background: #f8f9fa;
        }

        .gjs-blocks-c .gjs-block {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 5px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gjs-blocks-c .gjs-block:hover {
            background: #e9ecef;
            border-color: #007bff;
        }

        /* Asegurar que el contenedor tenga altura */
        .gjs-cv-canvas {
            min-height: 400px;
        }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-12 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-6 jusitfy-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    Editor de correo: {{ $tpl->nombre }}
                </h1>
            </div>

            <div class="flex gap-2">
                <button onclick="save()"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg shadow cursor-pointer transition-all duration-200 hover:scale-105">
                    Guardar
                </button>
                <a href="{{ route('cuerpos-correo.index') }}"
                    class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg cursor-pointer hover:scale-105 transition-all duration-200">
                    Volver
                </a>
            </div>

        </div>

        <!-- Panel de Variables y Herramientas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Variables disponibles -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Variables disponibles
            </h2>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ count($tpl->vars) }} variables
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Haz clic en cualquier variable para insertarla en el editor:
                    </p>

                    <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($tpl->vars as $var => $desc)
                        <div class="group cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200" 
                             onclick="insertVariable('{{ $var }}')"
                             title="Hacer clic para insertar">
                            <div class="flex items-center justify-between">
                                <code class="text-sm font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">
                        {{ $var }}
                                </code>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $desc }}</p>
                        </div>
                @empty
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay variables definidas para este tipo.</p>
                        </div>
                @endforelse
                    </div>
                </div>
            </div>

            <!-- Herramientas y Preview -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            Herramientas del Editor
                        </h2>
                        <div class="flex space-x-2">
                            <button onclick="previewTemplate()" 
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Vista previa
                            </button>
                            <button onclick="clearEditor()" 
                                    class="inline-flex items-center px-3 py-1.5 border border-red-300 dark:border-red-600 rounded-md text-sm font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas del contenido -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="char-count">0</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Caracteres</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="word-count">0</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Palabras</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="variable-count">0</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Variables</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400" id="image-count">0</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Imágenes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="editor-shell" class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
            <div id="gjs"></div>
            <div id="editor-fallback" style="display: none; padding: 20px; text-align: center; background: #f8f9fa;">
                <h3>Editor no disponible</h3>
                <p>El editor visual no se pudo cargar. Por favor, recarga la página o contacta al administrador.</p>
                <button onclick="location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Recargar página</button>
            </div>
        </div>
    </div>

    {{-- Scripts GrapesJS --}}
    <script src="https://unpkg.com/grapesjs@0.21.8/dist/grapes.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let editor;
        let availableVariables = @json($tpl->vars);

        // Función para inicializar el editor
        function initEditor() {
            console.log('Inicializando GrapesJS...');
            
            // Verificar que el contenedor existe
            const container = document.getElementById('gjs');
            if (!container) {
                console.error('No se encontró el contenedor #gjs');
                return false;
            }
            
            console.log('Contenedor encontrado:', container);
            
            try {
                editor = grapesjs.init({
            container: '#gjs',
                    height: '100%',
                    width: '100%',
            storageManager: {
                type: null
                    },
                    canvas: {
                        styles: [
                            'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css'
                        ]
                    },
                    deviceManager: {
                        devices: [
                            {
                                name: 'Desktop',
                                width: '',
                            },
                            {
                                name: 'Tablet',
                                width: '768px',
                                widthMedia: '992px',
                            },
                            {
                                name: 'Mobile',
                                width: '320px',
                                widthMedia: '768px',
                            }
                        ]
                    }
                });
                
                console.log('Editor inicializado:', editor);

                // Agregar bloques básicos
                const blockManager = editor.BlockManager;
                
                blockManager.add('text', {
                    label: 'Texto',
                    content: '<div style="padding: 10px;">Escribe tu texto aquí...</div>',
                    category: 'Básicos'
                });

                blockManager.add('header', {
                    label: 'Encabezado',
                    content: '<div style="background-color: #f8f9fa; padding: 20px; text-align: center;"><h1 style="color: #495057; margin: 0;">Título del Correo</h1></div>',
                    category: 'Estructura'
                });

                blockManager.add('button', {
                    label: 'Botón',
                    content: '<div style="text-align: center; margin: 20px 0;"><a href="#" style="display: inline-block; background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Hacer clic aquí</a></div>',
                    category: 'Elementos'
                });

                // Cargar contenido inicial
                try {
                    const initialContent = @json($tpl->cuerpo_html);
                    console.log('Contenido inicial:', initialContent);
                    if (initialContent && initialContent.trim() !== '') {
                        editor.setComponents(initialContent);
                    } else {
                        editor.setComponents('<div style="padding: 20px;"><h1>Bienvenido al Editor</h1><p>Comienza a crear tu plantilla de correo aquí.</p></div>');
                    }
                } catch (error) {
                    console.error('Error al cargar contenido inicial:', error);
                    editor.setComponents('<div style="padding: 20px;"><h1>Bienvenido al Editor</h1><p>Comienza a crear tu plantilla de correo aquí.</p></div>');
                }

                // Actualizar estadísticas
                setTimeout(() => {
                    updateStats();
                }, 1000);

                // Escuchar cambios
                editor.on('component:update', updateStats);
                editor.on('component:add', updateStats);
                editor.on('component:remove', updateStats);
                
                console.log('Editor completamente inicializado');
                return true;
                
            } catch (error) {
                console.error('Error al inicializar GrapesJS:', error);
                return false;
            }
        }

        // Verificar que GrapesJS esté disponible
        function checkGrapesJS() {
            if (typeof grapesjs === 'undefined') {
                console.error('GrapesJS no está disponible');
                return false;
            }
            console.log('GrapesJS está disponible');
            return true;
        }

        // Mostrar fallback si el editor no se carga
        function showFallback() {
            const editorContainer = document.getElementById('gjs');
            const fallback = document.getElementById('editor-fallback');
            
            if (editorContainer && fallback) {
                editorContainer.style.display = 'none';
                fallback.style.display = 'block';
            }
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, verificando GrapesJS...');
            
            // Verificar que GrapesJS esté disponible
            if (!checkGrapesJS()) {
                console.error('GrapesJS no se cargó correctamente');
                showFallback();
                return;
            }
            
            // Intentar inicializar inmediatamente
            if (!initEditor()) {
                // Si falla, intentar de nuevo después de un breve delay
                setTimeout(() => {
                    console.log('Reintentando inicialización...');
                    if (!initEditor()) {
                        showFallback();
                    }
                }, 500);
            }
        });

        // Función para insertar variables
        function insertVariable(variable) {
            const component = editor.getSelected();
            if (component) {
                component.append(`<span style="background-color: #e3f2fd; padding: 2px 6px; border-radius: 4px; font-weight: bold; color: #1976d2;">${variable}</span>`);
            } else {
                // Si no hay componente seleccionado, agregar al final
                const body = editor.DomComponents.getWrapper();
                body.append(`<div style="margin: 10px 0;"><span style="background-color: #e3f2fd; padding: 2px 6px; border-radius: 4px; font-weight: bold; color: #1976d2;">${variable}</span></div>`);
            }
            updateStats();
        }

        // Función para actualizar estadísticas
        function updateStats() {
            const html = editor.getHtml();
            const textContent = html.replace(/<[^>]*>/g, ''); // Remover HTML tags
            
            // Contar caracteres
            const charCount = textContent.length;
            document.getElementById('char-count').textContent = charCount.toLocaleString();
            
            // Contar palabras
            const wordCount = textContent.trim().split(/\s+/).filter(word => word.length > 0).length;
            document.getElementById('word-count').textContent = wordCount.toLocaleString();
            
            // Contar variables
            const variableCount = Object.keys(availableVariables).reduce((count, variable) => {
                const regex = new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g');
                const matches = html.match(regex);
                return count + (matches ? matches.length : 0);
            }, 0);
            document.getElementById('variable-count').textContent = variableCount;
            
            // Contar imágenes
            const imageCount = (html.match(/<img[^>]*>/gi) || []).length;
            document.getElementById('image-count').textContent = imageCount;
        }

        // Función para limpiar editor
        function clearEditor() {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esto eliminará todo el contenido del editor. Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, limpiar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    editor.setComponents('');
                    updateStats();
                    Swal.fire('¡Limpiado!', 'El editor ha sido limpiado.', 'success');
                }
            });
        }

        // Función para vista previa
        function previewTemplate() {
            const html = editor.getHtml() + '<style>' + editor.getCss() + '</style>';
            const previewWindow = window.open('', '_blank', 'width=800,height=600');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Vista Previa - {{ $tpl->nombre }}</title>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f5f5f5; }
                        .preview-container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
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

        // Función para guardar
        async function save() {
            const html = editor.getHtml() + '<style>' + editor.getCss() + '</style>';
            
            // Mostrar indicador de carga
            Swal.fire({
                title: 'Guardando...',
                text: 'Por favor espera mientras se guarda la plantilla.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
            const resp = await fetch("{{ route('cuerpos-correo.updateEditor', $tpl->id_cuerpo) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    html
                })
            });

                Swal.close();

                if (resp.ok) {
                    Swal.fire({
                        title: '¡Guardado!',
                        text: 'La plantilla fue guardada correctamente en la base de datos.',
                        icon: 'success',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                    window.location.href = "{{ route('cuerpos-correo.index') }}";
                    });
                } else {
                    throw new Error('Error al guardar');
                }
            } catch (error) {
                Swal.close();
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un problema al guardar la plantilla. Intenta de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        }

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S para guardar
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                save();
            }
            
            // Ctrl+P para vista previa
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                previewTemplate();
            }
        });

        // Auto-guardado cada 5 minutos
        setInterval(() => {
            if (editor) {
                console.log('Auto-guardado...');
                // Aquí podrías implementar auto-guardado si es necesario
            }
        }, 300000); // 5 minutos
    </script>
</x-app-layout>
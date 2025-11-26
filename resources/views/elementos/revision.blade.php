<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Revisión de Documento - SGCR</title>
    <link rel="icon" href="{{ asset('images/calidad-de-la-pagina.png') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Prevenir descarga y clic derecho en el iframe */
        .documento-frame {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .documento-frame iframe {
            pointer-events: auto;
        }
        
        /* Prevenir selección de texto en el documento */
        .prose {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Revisión de Documento</h1>
                    <p class="text-sm text-gray-600 mt-1">Revise el documento y los archivos adjuntos antes de firmar o rechazar</p>
                </div>
                <div class="flex items-center text-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-sm">{{ now()->format('d \d\e F, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Documento Principal -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <!-- Header del Documento -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $elemento->nombre_elemento }}</h2>
                                    <p class="text-sm text-gray-600 mb-4">
                                        {{ $elemento->tipoElemento->nombre ?? 'Documento' }}
                                    </p>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span class="text-sm">{{ $elemento->puestoResponsable->nombre ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                                    Pendiente de firma
                                </span>
                            </div>
                        </div>

                        <!-- Contenido del Documento -->
                        <div class="p-6">
                            @php
                                $archivoPrincipal = null;
                                $tipoArchivo = null;
                                if ($elemento->archivo_es_formato) {
                                    $archivoPrincipal = $elemento->archivo_es_formato;
                                    $tipoArchivo = strtolower(pathinfo($archivoPrincipal, PATHINFO_EXTENSION));
                                } elseif ($elemento->archivo_formato) {
                                    $archivoPrincipal = $elemento->archivo_formato;
                                    $tipoArchivo = strtolower(pathinfo($archivoPrincipal, PATHINFO_EXTENSION));
                                }
                            @endphp
                            
                            @if($archivoPrincipal && $tipoArchivo === 'pdf')
                                <div class="w-full documento-frame" style="height: 600px; position: relative;">
                                    <iframe 
                                        src="{{ Storage::url($archivoPrincipal) }}#toolbar=0&navpanes=0" 
                                        class="w-full h-full border-0 rounded-lg"
                                        title="Vista previa del documento">
                                    </iframe>
                                </div>
                            @elseif($archivoPrincipal && in_array($tipoArchivo, ['docx', 'doc']))
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-4 text-sm text-gray-500">El documento no se puede visualizar en este formato</p>
                                </div>
                            @elseif($contenidoDocumento)
                                <div class="prose max-w-none">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ strtoupper($elemento->nombre_elemento) }}</h3>
                                    <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $contenidoDocumento }}</div>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-4 text-sm text-gray-500">No hay contenido disponible para este documento</p>
                                </div>
                            @endif
                        </div>

                        <!-- Botones de Acción -->
                        <div class="p-6 border-t border-gray-200 flex justify-between gap-4">
                            <button type="button" onclick="rechazarDocumento()" class="flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Rechazar Documento
                            </button>
                            <button type="button" onclick="firmarDocumento()" class="flex items-center px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Firmar Documento
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Archivos Adjuntos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Archivos Adjuntos</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ count($archivosAdjuntos) }} archivo(s) adjunto(s)</p>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse($archivosAdjuntos as $archivo)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center flex-1 min-w-0">
                                        <svg class="w-8 h-8 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ \Illuminate\Support\Str::limit($archivo['nombre'], 30) }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ number_format($archivo['tamaño'] / 1024 / 1024, 1) }} MB • {{ strtoupper($archivo['tipo']) }}
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::url($archivo['ruta']) }}" download class="ml-3 p-2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">No hay archivos adjuntos</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Información -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Información</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Tipo de documento</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $elemento->tipoElemento->nombre ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Remitente</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $elemento->puestoResponsable->nombre ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Fecha de envío</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $elemento->created_at->format('d \d\e F, Y') }}</p>
                            </div>
                            @if($elemento->folio_elemento)
                            <div>
                                <p class="text-sm font-medium text-gray-700">Folio</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $elemento->folio_elemento }}</p>
                            </div>
                            @endif
                            @if($elemento->version_elemento)
                            <div>
                                <p class="text-sm font-medium text-gray-700">Versión</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $elemento->version_elemento }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prevenir clic derecho y descarga
        document.addEventListener('contextmenu', function(e) {
            // Prevenir clic derecho en el contenedor del documento y en el contenido del documento
            if (e.target.closest('.documento-frame') || e.target.closest('.prose')) {
                e.preventDefault();
                return false;
            }
        });

        // Prevenir atajos de teclado para descargar
        document.addEventListener('keydown', function(e) {
            // Prevenir Ctrl+S, Ctrl+P, etc. en el iframe
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'S' || e.key === 'P')) {
                if (e.target.closest('iframe') || e.target.closest('.prose')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        function rechazarDocumento() {
            if (confirm('¿Está seguro de que desea rechazar este documento?')) {
                // Aquí puedes agregar la lógica para rechazar el documento
                alert('Documento rechazado');
            }
        }

        function firmarDocumento() {
            if (confirm('¿Está seguro de que desea firmar este documento?')) {
                // Aquí puedes agregar la lógica para firmar el documento
                alert('Documento firmado');
            }
        }
    </script>
</body>
</html>


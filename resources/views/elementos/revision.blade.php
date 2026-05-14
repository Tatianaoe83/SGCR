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
                    <p class="text-sm text-gray-600 mt-1">Revise el documento y los archivos adjuntos antes de firmar o
                        rechazar</p>
                </div>
                <div class="flex items-center text-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
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
                                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                                        {{ $elemento->nombre_elemento }}
                                    </h2>
                                    <p class="text-sm text-gray-600 mb-4">
                                        {{ $elemento->tipoElemento->nombre ?? 'Documento' }}
                                    </p>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
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
                                $archivoMostrar = $elemento->archivo_actual;
                                $archivoMostrarUrl = $elemento->archivo_actual_url;
                                $extension = $archivoMostrar ? strtolower(pathinfo($archivoMostrar, PATHINFO_EXTENSION)) : null;
                                $esDocumentoOficial = $archivoMostrar === $elemento->archivo_firmado;
                            @endphp

                            @if($archivoMostrar && $archivoMostrarUrl)
                                @if($extension === 'pdf')
                                    <div class="w-full documento-frame" style="height: 600px; position: relative;">
                                        <iframe src="{{ $archivoMostrarUrl }}#toolbar=0&navpanes=0"
                                            class="w-full h-full border-0 rounded-lg" title="Vista previa del documento">
                                        </iframe>
                                    </div>
                                @elseif(in_array($extension, ['doc', 'docx']))
                                    <div class="text-center py-12 bg-blue-50 rounded-lg border border-blue-200">
                                        <svg class="mx-auto h-12 w-12 text-blue-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <p class="mt-4 text-sm text-blue-700 font-medium">Documento Word detectado</p>
                                        <p class="mt-2 text-sm text-blue-600">Por favor, descarga el archivo para revisarlo
                                            completamente.</p>
                                        <a href="{{ $archivoMostrarUrl }}"
                                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
                                            download>
                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Descargar Documento
                                        </a>
                                    </div>
                                @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                    <div class="rounded-lg overflow-hidden border-2 border-gray-200">
                                        <img src="{{ $archivoMostrarUrl }}" alt="Documento" class="w-full h-auto">
                                    </div>
                                @else
                                    <div class="text-center py-12">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <p class="mt-4 text-sm text-gray-500">Formato no soportado para vista previa
                                            ({{ strtoupper($extension ?? 'desconocido') }})</p>
                                        <a href="{{ $archivoMostrarUrl }}"
                                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                                            download>
                                            Descargar Documento
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <p class="mt-4 text-sm text-gray-500">No hay documento disponible para revisión</p>
                                </div>
                            @endif
                        </div>

                        <!-- Botones de Acción -->
                        <div class="p-6 border-t border-gray-200 flex justify-between gap-4">
                            <button type="button" onclick="rechazar()"
                                class="flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Rechazar Documento
                            </button>
                            <button type="button" onclick="aprobar()"
                                class="flex items-center px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
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
                            <p class="text-sm text-gray-600 mt-1">{{ count($archivosAdjuntos) }} archivo(s) adjunto(s)
                            </p>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse($archivosAdjuntos as $archivo)
                                <div
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center flex-1 min-w-0">
                                        <svg class="w-8 h-8 text-gray-400 mr-3 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ \Illuminate\Support\Str::limit($archivo['nombre'], 30) }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ number_format($archivo['tamaño'] / 1024 / 1024, 1) }} MB •
                                                {{ strtoupper($archivo['tipo']) }}
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::disk('public')->url($archivo['ruta']) }}" download
                                        class="ml-3 p-2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
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
                                <p class="text-sm text-gray-900 mt-1">
                                    {{ $elemento->puestoResponsable->nombre ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Fecha de envío</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $elemento->created_at->format('d \d\e F, Y') }}
                                </p>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @php
        $tieneFirmaElectronica = \Illuminate\Support\Facades\DB::table('firmas_electronicas')
            ->where('empleado_id', $firma->empleado_id)
            ->exists();
    @endphp
    <script>
        window.__TIENE_FIRMA_ELECTRONICA__ = @json($tieneFirmaElectronica);
    </script>

    @php
        // Variables for the annotation overlay
        $pdfParaAnotacion = $elemento->archivo_markdown ?? $elemento->archivo_es_formato ?? null;
        $extParaAnotacion = $pdfParaAnotacion ? strtolower(pathinfo($pdfParaAnotacion, PATHINFO_EXTENSION)) : null;
        $urlParaAnotacion = ($pdfParaAnotacion && \Illuminate\Support\Facades\Storage::disk('public')->exists($pdfParaAnotacion))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($pdfParaAnotacion)
            : null;
    @endphp
    <script>
        window.__PDF_URL__ = @json($urlParaAnotacion);
        window.__ES_PDF__ = @json($extParaAnotacion === 'pdf');
    </script>

    <script>
        document.addEventListener('contextmenu', function (e) {
            if (e.target.closest('.documento-frame') || e.target.closest('.prose')) {
                e.preventDefault();
                return false;
            }
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p' || e.key === 'S' || e.key === 'P')) {
                if (e.target.closest('iframe') || e.target.closest('.prose')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
    <script>
        function aprobar() {
            const isDark = document.documentElement.classList.contains('dark');
            const tieneFirma = Boolean(window.__TIENE_FIRMA_ELECTRONICA__);

            // Mostrar modal de comentario opcional
            Swal.fire({
                title: 'Aceptar documento',
                width: 500,
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#e5e7eb' : '#111827',
                html: `
                    <div class="text-left space-y-4">
                        <p class="text-sm ${isDark ? 'text-gray-300' : 'text-gray-700'}">
                            Agregar un comentario (opcional)
                        </p>
                        <textarea id="swal_comentario_aceptacion" rows="4"
                            class="w-full rounded-xl border ${isDark ? 'border-slate-700 bg-slate-900 text-gray-100' : 'border-gray-200 bg-gray-50 text-gray-900'} px-3 py-2.5 text-sm placeholder-gray-400 outline-none focus:ring-2 focus:ring-green-100 focus:border-green-300 resize-none transition-all"
                            placeholder="Escribe aquí tus comentarios..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-2xl p-0 overflow-hidden',
                    title: 'text-lg font-semibold px-6 pt-6 ' + (isDark ? 'text-gray-100' : 'text-gray-900'),
                    htmlContainer: 'px-6 pb-6 pt-3',
                    actions: 'px-6 pb-6 pt-0 flex gap-3 justify-end',
                    confirmButton: 'rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700',
                    cancelButton: 'rounded-xl px-4 py-2.5 text-sm font-semibold border ' +
                        (isDark ?
                            'bg-slate-800 text-gray-100 border-slate-700 hover:bg-slate-700' :
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        )
                },
                didOpen: () => {
                    document.getElementById('swal_comentario_aceptacion').focus();
                },
                preConfirm: () => {
                    const comentario = (document.getElementById('swal_comentario_aceptacion').value || '').trim();
                    return { comentario: comentario || null };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const comentario = result.value.comentario;
                    
                    if (tieneFirma) {
                        // Ya tiene firma guardada, enviar directamente
                        enviarFirma('Aprobado', comentario);
                        return;
                    }

                    mostrarModalFirmaPrimeraVez(comentario);
                }
            });
        }

        function mostrarModalFirmaPrimeraVez(comentario = null) {
            const isDark = document.documentElement.classList.contains('dark');

            let hasStroke = false;
            let drawing = false;
            let lastX = 0;
            let lastY = 0;

            const canvasId = 'swal_signature_canvas';

            Swal.fire({
                title: 'Firma electrónica',
                width: 760,
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#e5e7eb' : '#111827',
                html: `
                <div class="text-left space-y-4">
                    <p class="text-sm ${isDark ? 'text-gray-200' : 'text-gray-700'}">
                        Primera vez firmando. Dibuja tu firma para guardarla y reutilizarla en futuras aprobaciones.
                    </p>

                    <div class="rounded-2xl border ${isDark ? 'border-slate-700 bg-slate-900/40' : 'border-gray-200 bg-gray-50'} p-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div class="text-xs ${isDark ? 'text-gray-300' : 'text-gray-600'}">Firma aquí</div>
                            <button type="button" id="swal_sig_clear"
                                class="rounded-xl px-3 py-2 text-xs font-semibold border
                                ${isDark ? 'bg-slate-800 text-gray-100 border-slate-700 hover:bg-slate-700' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}">
                                Borrar
                            </button>
                        </div>

                        <div class="rounded-xl overflow-hidden border ${isDark ? 'border-slate-700 bg-slate-950' : 'border-gray-200 bg-white'}">
                            <canvas id="${canvasId}" class="w-full" style="height:220px;"></canvas>
                        </div>

                        <p class="mt-3 text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'}">
                            Se guardará como tu firma electrónica.
                        </p>
                    </div>
                </div>
            `,
                showCancelButton: true,
                confirmButtonText: 'Guardar y firmar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-2xl p-0 overflow-hidden',
                    title: 'text-lg font-semibold px-6 pt-6 ' + (isDark ? 'text-gray-100' : 'text-gray-900'),
                    htmlContainer: 'px-6 pb-6 pt-3',
                    actions: 'px-6 pb-6 pt-0 flex gap-3 justify-end',
                    confirmButton: 'rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800',
                    cancelButton: 'rounded-xl px-4 py-2.5 text-sm font-semibold border ' +
                        (isDark ?
                            'bg-slate-800 text-gray-100 border-slate-700 hover:bg-slate-700' :
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        ),
                    validationMessage: (isDark ?
                        'mt-3 text-left text-sm text-red-200 bg-red-900/30 border border-red-800 rounded-xl px-4 py-3' :
                        'mt-3 text-left text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl px-4 py-3'
                    )
                },
                didOpen: () => {
                    const canvas = document.getElementById(canvasId);
                    const clearBtn = document.getElementById('swal_sig_clear');
                    const ctx = canvas.getContext('2d');

                    const resize = () => {
                        const rect = canvas.getBoundingClientRect();
                        const dpr = window.devicePixelRatio || 1;

                        canvas.width = Math.floor(rect.width * dpr);
                        canvas.height = Math.floor(rect.height * dpr);

                        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                        ctx.lineWidth = 4.5; // AUMENTADO de 2.2 a 4.5 para firmas más gruesas y visibles
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        ctx.strokeStyle = '#0F41D2';
                    };

                    resize();

                    const getPos = (ev) => {
                        const r = canvas.getBoundingClientRect();
                        return {
                            x: ev.clientX - r.left,
                            y: ev.clientY - r.top
                        };
                    };

                    const start = (ev) => {
                        drawing = true;
                        const p = getPos(ev);
                        lastX = p.x;
                        lastY = p.y;
                    };

                    const move = (ev) => {
                        if (!drawing) return;
                        const p = getPos(ev);

                        ctx.beginPath();
                        ctx.moveTo(lastX, lastY);
                        ctx.lineTo(p.x, p.y);
                        ctx.stroke();

                        lastX = p.x;
                        lastY = p.y;
                        hasStroke = true;
                    };

                    const end = () => {
                        drawing = false;
                    };

                    canvas.addEventListener('pointerdown', start);
                    canvas.addEventListener('pointermove', move);
                    canvas.addEventListener('pointerup', end);
                    canvas.addEventListener('pointercancel', end);
                    canvas.addEventListener('pointerleave', end);

                    clearBtn.addEventListener('click', () => {
                        const rect = canvas.getBoundingClientRect();
                        ctx.clearRect(0, 0, rect.width, rect.height);
                        hasStroke = false;
                    });

                    window.addEventListener('resize', resize);

                    Swal.getPopup()._getSignatureFile = () => {
                        return new Promise((resolve) => {
                            canvas.toBlob((blob) => {
                                if (!blob) return resolve(null);
                                resolve(new File([blob], 'firma.png', {
                                    type: 'image/png'
                                }));
                            }, 'image/png', 1);
                        });
                    };
                },
                preConfirm: async () => {
                    if (!hasStroke) {
                        Swal.showValidationMessage('La firma es obligatoria la primera vez');
                        return false;
                    }

                    const file = await Swal.getPopup()._getSignatureFile();
                    if (!(file instanceof File)) {
                        Swal.showValidationMessage('No se pudo generar la firma. Intenta de nuevo.');
                        return false;
                    }

                    return {
                        firmaFile: file
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarFirma('Aprobado', comentario, null, result.value.firmaFile);
                }
            });
        }

        function rechazar() {
            if (window.__ES_PDF__ && window.__PDF_URL__) {
                activarModoAnotacion();
            } else {
                rechazarConEvidencias();
            }
        }

        function rechazarConEvidencias() {
            const isDark = document.documentElement.classList.contains('dark');

            let evidenciaFiles = [];

            const humanSize = (bytes) => {
                const units = ['B', 'KB', 'MB', 'GB'];
                let i = 0;
                let n = bytes;
                while (n >= 1024 && i < units.length - 1) {
                    n /= 1024;
                    i++;
                }
                return `${n.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
            };

            const renderFiles = () => {
                const list = document.getElementById('swal_files_list');
                const empty = document.getElementById('swal_files_empty');
                const counter = document.getElementById('swal_files_counter');

                if (!list || !empty || !counter) return;

                counter.textContent = `${evidenciaFiles.length} archivo(s)`;

                if (evidenciaFiles.length === 0) {
                    empty.classList.remove('hidden');
                    list.classList.add('hidden');
                    list.innerHTML = '';
                    return;
                }

                empty.classList.add('hidden');
                list.classList.remove('hidden');

                list.innerHTML = evidenciaFiles
                    .map((f, idx) => {
                        const isImg = f.type && f.type.startsWith('image/');
                        const name = f.name || `archivo_${idx + 1}`;
                        const meta = `${(f.type || 'archivo')} • ${humanSize(f.size)}`;

                        return `
                    <div class="flex items-center gap-3 rounded-lg border ${isDark ? 'border-slate-700 bg-slate-900/40' : 'border-gray-200 bg-white'} px-3 py-2">
                        <div class="shrink-0">
                            ${isImg
                                ? `<img data-idx="${idx}" class="h-10 w-10 rounded-md object-cover border ${isDark ? 'border-slate-700' : 'border-gray-200'}" />`
                                : `<div class="h-10 w-10 rounded-md border ${isDark ? 'border-slate-700 bg-slate-800' : 'border-gray-200 bg-gray-50'} flex items-center justify-center text-xs ${isDark ? 'text-gray-200' : 'text-gray-600'}">
                                            ${String(name.split('.').pop() || 'FILE').slice(0, 4).toUpperCase()}
                                       </div>`
                            }
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium ${isDark ? 'text-gray-100' : 'text-gray-900'} truncate">${name}</p>
                            <p class="text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'} truncate">${meta}</p>
                        </div>

                        <button
                            type="button"
                            data-remove="${idx}"
                            class="rounded-lg px-2 py-1 text-xs font-semibold border
                                   ${isDark ? 'bg-slate-800 text-gray-100 border-slate-700 hover:bg-slate-700' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}"
                        >
                            Quitar
                        </button>
                    </div>
                `;
                    })
                    .join('');

                evidenciaFiles.forEach((f, idx) => {
                    if (f.type && f.type.startsWith('image/')) {
                        const img = list.querySelector(`img[data-idx="${idx}"]`);
                        if (!img) return;
                        const url = URL.createObjectURL(f);
                        img.src = url;
                        img.onload = () => URL.revokeObjectURL(url);
                    }
                });

                list.querySelectorAll('button[data-remove]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const i = Number(btn.getAttribute('data-remove'));
                        if (Number.isNaN(i)) return;
                        evidenciaFiles.splice(i, 1);
                        renderFiles();
                    });
                });
            };

            const addFiles = (files) => {
                const arr = Array.from(files || []).filter(Boolean);
                if (arr.length === 0) return;

                const maxFiles = 8;
                const available = Math.max(0, maxFiles - evidenciaFiles.length);
                const toAdd = arr.slice(0, available);

                evidenciaFiles = evidenciaFiles.concat(toAdd);
                renderFiles();
            };

            Swal.fire({
                title: 'Rechazar documento',
                width: 760,
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#e5e7eb' : '#111827',
                html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium ${isDark ? 'text-gray-200' : 'text-gray-700'} mb-2">Motivo del rechazo</label>
                    <textarea
                        id="swal_comentario"
                        maxlength="1000"
                        class="w-full min-h-[120px] rounded-xl border ${isDark ? 'border-slate-700 bg-slate-900/40 text-gray-100' : 'border-gray-200 bg-slate-50 text-gray-900'}
                               px-4 py-3 text-sm outline-none focus:ring-4 ${isDark ? 'focus:ring-red-900/30 focus:border-red-400' : 'focus:ring-red-100 focus:border-red-400'}"
                        placeholder="Escribe el motivo del rechazo..."
                    ></textarea>
                    <p class="mt-2 text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'}">Sé claro y específico. Esto quedará registrado.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium ${isDark ? 'text-gray-200' : 'text-gray-700'}">Evidencia (obligatoria)</label>
                        <span class="text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'}">
                            <span id="swal_files_counter">0 archivo(s)</span> • Arrastra, selecciona o pega imágenes
                        </span>
                    </div>

                    <input id="swal_evidencias" type="file" class="hidden" multiple />

                    <div
                        id="swal_dropzone"
                        class="rounded-xl border border-dashed ${isDark ? 'border-slate-700 bg-slate-900/40 hover:bg-slate-900/60' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'}
                               p-4 transition"
                        style="user-select:none"
                        tabindex="0"
                    >
                        <div class="flex items-start gap-4">
                            <div class="mt-1 h-10 w-10 shrink-0 rounded-lg border ${isDark ? 'border-slate-700 bg-slate-800' : 'border-gray-200 bg-slate-50'}
                                        flex items-center justify-center">
                                <svg class="h-5 w-5 ${isDark ? 'text-gray-200' : 'text-gray-500'}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-8m0 0l-3 3m3-3l3 3M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <p class="text-sm font-medium ${isDark ? 'text-gray-100' : 'text-gray-900'}">Agrega evidencias</p>
                                <p class="text-xs ${isDark ? 'text-gray-300' : 'text-gray-600'} mt-1">
                                    Puedes subir cualquier archivo. Pega (Ctrl+V) para capturas/imagenes. Máx 10MB por archivo.
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        id="swal_btn_select"
                                        class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800"
                                    >
                                        Seleccionar archivos
                                    </button>

                                    <button
                                        type="button"
                                        id="swal_btn_clear_all"
                                        class="rounded-lg px-3 py-2 text-xs font-semibold border
                                               ${isDark ? 'bg-slate-800 text-gray-100 border-slate-700 hover:bg-slate-700' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'}"
                                    >
                                        Limpiar
                                    </button>

                                    <span class="text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'}">Límite sugerido: 8 archivos</span>
                                </div>

                                <div id="swal_files_empty" class="mt-3 text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'}">
                                    No has agregado evidencia todavía.
                                </div>

                                <div id="swal_files_list" class="mt-3 hidden space-y-2"></div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-2 text-xs ${isDark ? 'text-gray-300' : 'text-gray-500'}">
                        Tip: si tu evidencia es una captura, hazla y pégala aquí con Ctrl+V.
                    </p>
                </div>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-2xl p-0 overflow-hidden',
                    title: 'text-lg font-semibold px-6 pt-6 ' + (isDark ? 'text-gray-100' : 'text-gray-900'),
                    htmlContainer: 'px-6 pb-6 pt-3',
                    actions: 'px-6 pb-6 pt-0 flex gap-3 justify-end',
                    confirmButton: 'rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700',
                    cancelButton: 'rounded-xl px-4 py-2.5 text-sm font-semibold border ' +
                        (isDark ?
                            'bg-slate-800 text-gray-100 border-slate-700 hover:bg-slate-700' :
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
                        ),
                    validationMessage: (isDark ?
                        'mt-3 text-left text-sm text-red-200 bg-red-900/30 border border-red-800 rounded-xl px-4 py-3' :
                        'mt-3 text-left text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl px-4 py-3'
                    )
                },
                didOpen: () => {
                    const inputComentario = document.getElementById('swal_comentario');
                    const inputFiles = document.getElementById('swal_evidencias');
                    const dropzone = document.getElementById('swal_dropzone');
                    const btnSelect = document.getElementById('swal_btn_select');
                    const btnClearAll = document.getElementById('swal_btn_clear_all');

                    const prevent = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    };

                    btnSelect.addEventListener('click', () => inputFiles.click());
                    btnClearAll.addEventListener('click', () => {
                        evidenciaFiles = [];
                        inputFiles.value = '';
                        renderFiles();
                    });

                    inputFiles.addEventListener('change', () => {
                        addFiles(inputFiles.files);
                        inputFiles.value = '';
                    });

                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
                        dropzone.addEventListener(evt, prevent);
                    });

                    dropzone.addEventListener('dragover', () => {
                        dropzone.classList.add('ring-4', isDark ? 'ring-red-900/30' : 'ring-red-100', 'border-red-300');
                    });

                    dropzone.addEventListener('dragleave', () => {
                        dropzone.classList.remove('ring-4', isDark ? 'ring-red-900/30' : 'ring-red-100', 'border-red-300');
                    });

                    dropzone.addEventListener('drop', (e) => {
                        dropzone.classList.remove('ring-4', isDark ? 'ring-red-900/30' : 'ring-red-100', 'border-red-300');
                        const files = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : null;
                        if (files) addFiles(files);
                    });

                    const onPaste = (e) => {
                        const items = e.clipboardData && e.clipboardData.items ? Array.from(e.clipboardData.items) : [];
                        const fileItems = items.filter(i => i.kind === 'file');

                        fileItems.forEach((it) => {
                            const f = it.getAsFile();
                            if (!f) return;

                            const ext = (f.type && f.type.includes('/')) ? f.type.split('/')[1] : 'bin';
                            const name = `evidencia_${new Date().toISOString().replace(/[:.]/g, '-')}.${ext}`;
                            addFiles([new File([f], name, {
                                type: f.type || 'application/octet-stream'
                            })]);
                        });
                    };

                    dropzone.addEventListener('paste', onPaste);
                    inputComentario.addEventListener('paste', onPaste);

                    Swal.getPopup()._getEvidencias = () => evidenciaFiles;

                    renderFiles();
                    setTimeout(() => inputComentario.focus(), 50);
                },
                preConfirm: () => {
                    const comentario = (document.getElementById('swal_comentario').value || '').trim();
                    const files = (Swal.getPopup()._getEvidencias && Swal.getPopup()._getEvidencias()) || [];

                    if (!comentario) {
                        Swal.showValidationMessage('El motivo del rechazo es obligatorio');
                        return false;
                    }

                    if (!files || files.length === 0) {
                        Swal.showValidationMessage('Debes agregar al menos una evidencia');
                        return false;
                    }

                    const maxBytesEach = 10 * 1024 * 1024;
                    for (const f of files) {
                        if (f.size > maxBytesEach) {
                            Swal.showValidationMessage(`Un archivo excede 10MB: ${f.name}`);
                            return false;
                        }
                    }

                    return {
                        comentario,
                        files
                    };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    enviarFirma('Rechazado', result.value.comentario, result.value.files);
                }
            });
        }

        function confirmarAccion(titulo, texto, estatus) {
            Swal.fire({
                title: titulo,
                text: texto,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#111827'
            }).then(result => {
                if (result.isConfirmed) {
                    enviarFirma(estatus);
                }
            });
        }

        function enviarFirma(estatus, comentario = null, evidenciaFiles = null, firmaFile = null, annotations = null) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Registrando tu firma',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const url = "{{ route('firmas.updateStatus', $firma->id) }}";

            const fd = new FormData();
            fd.append('estatus', estatus);

            if (comentario !== null) {
                if (estatus === 'Aprobado') {
                    fd.append('comentario_aceptacion', comentario);
                } else if (estatus === 'Rechazado') {
                    fd.append('comentario_rechazo', comentario);
                }
            }

            if (estatus === 'Rechazado') {
                if (annotations && annotations.length > 0) {
                    // New annotation-based rejection
                    fd.append('annotations', JSON.stringify(annotations));
                    if (comentario) fd.append('general_comment', comentario);
                    // Remove legacy comentario_rechazo if set (avoid duplicate)
                    fd.delete('comentario_rechazo');
                } else {
                    // Legacy rejection with evidencias + comment
                    const files = Array.isArray(evidenciaFiles) ? evidenciaFiles : [];
                    files.forEach((f) => fd.append('evidencias[]', f));
                }
            }

            if (estatus === 'Aprobado' && (firmaFile instanceof File)) {
                fd.append('firma', firmaFile);
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: fd,
            })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        const first422 =
                            data && data.errors ?
                                Object.values(data.errors).flat()[0] :
                                null;

                        throw new Error(first422 || data.message || 'Ocurrió un error inesperado');
                    }

                    return data;
                })
                .then(() => {
                    if (estatus === 'Aprobado') window.__TIENE_FIRMA_ELECTRONICA__ = true;

                    Swal.fire({
                        icon: 'success',
                        title: 'Firma registrada',
                        text: 'La acción se realizó correctamente',
                        confirmButtonColor: '#16a34a'
                    }).then(() => window.location.href = '/');
                })
                .catch((err) => {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Acción no permitida',
                        text: err.message,
                        confirmButtonColor: '#dc2626'
                    });
                });
        }
    </script>
    {{-- ================================================================
    ANNOTATION MODE OVERLAY
    ================================================================ --}}
    <div id="ann-overlay" class="fixed inset-0" style="display:none;z-index:9999;font-family:Inter,sans-serif;">
        <div style="display:flex;width:100%;height:100%;overflow:hidden;">

        {{-- Left: scrollable PDF viewer --}}
        <div class="overflow-y-auto" id="ann-pdf-scroll" style="background:#0e0f13;flex:0 0 calc(100vw - 380px);width:calc(100vw - 380px);min-width:0;height:100%;">
            {{-- Sticky top bar --}}
            <div class="sticky top-0 z-10 flex items-center gap-3 px-6 py-3 border-b" style="background:rgba(14,15,19,0.96);backdrop-filter:blur(8px);border-color:rgba(255,255,255,0.06);">
                <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse flex-shrink-0"></span>
                <span class="text-[10px] font-bold tracking-widest uppercase" style="color:rgba(255,255,255,0.35);">Modo anotación</span>
                <span style="color:rgba(255,255,255,0.12);font-size:10px;">—</span>
                <span class="text-xs" style="color:rgba(255,255,255,0.3);">Haz clic en el documento para marcar errores</span>
            </div>
            <div class="py-8 px-6">
                <div>
                    <div id="ann-pdf-loading" class="flex flex-col items-center justify-center h-64 gap-3" style="color:rgba(255,255,255,0.25);">
                        <svg class="animate-spin h-6 w-6" style="color:rgba(255,255,255,0.15);" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm">Cargando documento…</span>
                    </div>
                    <div id="ann-pdf-pages" class="space-y-12"></div>
                </div>
            </div>
        </div>

        {{-- Right: controls panel --}}
        <div class="bg-white flex flex-col border-l border-gray-100" style="flex:0 0 380px;width:380px;height:100%;overflow:hidden;">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase text-red-500">Rechazando</span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 leading-tight">Anotar documento</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Marca errores en el PDF y describe cada uno</p>
                    </div>
                    <button onclick="cerrarModoAnotacion()"
                        class="flex-shrink-0 p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all -mt-0.5"
                        title="Cerrar (Esc)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Instruction hint --}}
            <div class="px-6 py-3 flex items-start gap-3 border-b border-amber-100 bg-amber-50">|
                <div class="text-xs text-amber-700 leading-relaxed space-y-1">
                    <p><strong>Para agregar:</strong> Haz clic en el PDF para colocar un marcador numerado.</p>
                    <p><strong>Para borrar:</strong> Haz clic nuevamente sobre el marcador (cambiará a gris al pasar el mouse).</p>
                </div>
            </div>

            {{-- Scrollable body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-6">

                {{-- General comment (optional) --}}
                <div>
                    <label class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 mb-2">
                        Comentario general
                        <span class="text-xs font-normal text-gray-400">(opcional)</span>
                    </label>
                    <textarea id="ann-general-comment" rows="3"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-sm text-gray-900 placeholder-gray-400 outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none transition-all"
                        placeholder="Observación general sobre el documento…"></textarea>
                </div>

                {{-- Annotations list --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-700">Anotaciones</span>
                            <span id="ann-count"
                                class="inline-flex items-center justify-center min-w-[22px] h-[22px] rounded-full text-[11px] font-bold bg-gray-900 text-white px-1.5">0</span>
                        </div>
                        <button type="button" onclick="limpiarAnotaciones()"
                            class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                            Limpiar todo
                        </button>
                    </div>

                    <div id="ann-empty-hint"
                        class="flex flex-col items-center justify-center py-8 border-2 border-dashed border-gray-200 rounded-2xl">
                        <div class="w-10 h-10 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Sin anotaciones</p>
                        <p class="text-xs text-gray-400 mt-1">Haz clic en el documento para agregar</p>
                    </div>

                    <div id="ann-list" class="hidden space-y-2"></div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="p-5 border-t border-gray-100 space-y-2">
                <button type="button" onclick="confirmarRechazoAnnotations()"
                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700 active:scale-[0.98] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Confirmar rechazo
                </button>
                <button type="button" onclick="cerrarModoAnotacion()"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50 active:scale-[0.98] transition-all">
                    Cancelar
                </button>
            </div>
        </div>
        </div>{{-- /inner flex wrapper --}}
    </div>

    {{-- Annotation comment popover --}}
    <div id="ann-popover" class="fixed bg-white rounded-2xl border border-gray-100 p-4"
        style="display:none;z-index:10000;width:308px;box-shadow:0 24px 64px rgba(0,0,0,.16),0 4px 20px rgba(0,0,0,.08);">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-600 flex-shrink-0">
                    <span id="ann-popover-num" class="text-white text-[11px] font-bold">?</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">Nueva anotación</span>
            </div>
            <button type="button" onclick="cancelarAnotacionPendiente()"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <textarea id="ann-popover-text" rows="3"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none transition-all"
            placeholder="Describe el error en este punto…"></textarea>
        <div class="flex gap-2 mt-3">
            <button type="button" onclick="confirmarAnotacionPendiente()"
                class="flex-1 rounded-xl bg-gray-900 text-white text-sm py-2.5 font-semibold hover:bg-gray-800 active:scale-[0.98] transition-all">
                Agregar
            </button>
            <button type="button" onclick="cancelarAnotacionPendiente()"
                class="flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <p class="mt-2.5 text-center" style="font-size:10px;color:#9ca3af;">
            <kbd style="font-family:monospace;background:#f3f4f6;border-radius:4px;padding:1px 5px;font-size:10px;color:#6b7280;">Ctrl</kbd>
            <span style="margin:0 2px;">+</span>
            <kbd style="font-family:monospace;background:#f3f4f6;border-radius:4px;padding:1px 5px;font-size:10px;color:#6b7280;">Enter</kbd>
            para agregar rápido
        </p>
    </div>

    <script>
        // ================================================================
        // ANNOTATION MODE
        // ================================================================
        const annState = {
            annotations: [],
            pendingClick: null,
            pdfDoc: null,
            pages: [],
        };

        async function activarModoAnotacion() {
            annState.annotations = [];
            annState.pendingClick = null;
            annState.pdfDoc = null;
            annState.pages = [];
            renderAnnotationList();
            const overlay = document.getElementById('ann-overlay');
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
            await cargarPdfParaAnotacion(window.__PDF_URL__);
        }

        function cerrarModoAnotacion() {
            document.getElementById('ann-overlay').style.display = 'none';
            document.body.style.overflow = '';
            ocultarPopover();
        }

        function limpiarAnotaciones() {
            if (annState.annotations.length === 0) return;
            annState.annotations = [];
            renderAnnotationList();
            annState.pages.forEach(p => redibujarPagina(p));
        }

        // ── PDF loading ──────────────────────────────────────────────────

        function loadPdfJs() {
            if (typeof pdfjsLib !== 'undefined') return Promise.resolve();
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                s.onload = resolve;
                s.onerror = () => reject(new Error('No se pudo cargar PDF.js'));
                document.head.appendChild(s);
            });
        }

        async function cargarPdfParaAnotacion(url) {
            const pagesEl = document.getElementById('ann-pdf-pages');
            const loadEl = document.getElementById('ann-pdf-loading');
            pagesEl.innerHTML = '';
            loadEl.classList.remove('hidden');

            try {
                await loadPdfJs();
                pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                annState.pdfDoc = await pdfjsLib.getDocument({ url, withCredentials: false }).promise;
                loadEl.classList.add('hidden');

                for (let p = 1; p <= annState.pdfDoc.numPages; p++) {
                    const wrapper = await renderPdfPage(p);
                    pagesEl.appendChild(wrapper);
                }
            } catch (err) {
                loadEl.classList.add('hidden');
                pagesEl.innerHTML = `
                    <div class="text-center py-10 space-y-3">
                        <p class="text-red-300 text-sm">No se pudo cargar el PDF para anotaciones.</p>
                        <button onclick="cerrarModoAnotacion();rechazarConEvidencias()"
                            class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-100 transition-colors">
                            Usar método alternativo
                        </button>
                    </div>`;
            }
        }

        async function renderPdfPage(pageNum) {
            const page = await annState.pdfDoc.getPage(pageNum);
            const scrollW = document.getElementById('ann-pdf-scroll').clientWidth - 48; // 24px padding each side
            const baseVp = page.getViewport({ scale: 1 });
            const scale = Math.min(scrollW / baseVp.width, 2);
            const vp = page.getViewport({ scale });

            // Wrapper
            const wrapper = document.createElement('div');
            wrapper.style.cssText = `position:relative;display:block;width:${vp.width}px;margin:0 auto;border-radius:6px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.7),0 2px 8px rgba(0,0,0,.4);`;

            // Page label
            const lbl = document.createElement('div');
            lbl.style.cssText = 'position:absolute;top:-26px;left:0;font-size:10px;color:rgba(255,255,255,0.22);font-weight:600;letter-spacing:0.1em;text-transform:uppercase;';
            lbl.textContent = `Página ${pageNum}`;
            wrapper.appendChild(lbl);

            // PDF rendering canvas
            const pdfCanvas = document.createElement('canvas');
            pdfCanvas.width = vp.width;
            pdfCanvas.height = vp.height;
            pdfCanvas.style.display = 'block';
            wrapper.appendChild(pdfCanvas);

            // Annotation overlay canvas (same size, absolute on top)
            const annCanvas = document.createElement('canvas');
            annCanvas.width = vp.width;
            annCanvas.height = vp.height;
            annCanvas.style.cssText = `
                position:absolute;top:0;left:0;
                width:${vp.width}px;height:${vp.height}px;
                cursor:crosshair;
            `;
            annCanvas.dataset.page = pageNum;
            wrapper.appendChild(annCanvas);

            // Render PDF content
            await page.render({ canvasContext: pdfCanvas.getContext('2d'), viewport: vp }).promise;

            const pageInfo = {
                num: pageNum,
                canvas: annCanvas,
                width: vp.width,
                height: vp.height,
            };
            annState.pages.push(pageInfo);
            annCanvas.addEventListener('click', (e) => onAnnotationClick(e, pageInfo));
            annCanvas.addEventListener('mousemove', (e) => onAnnotationMouseMove(e, pageInfo));

            return wrapper;
        }

        // ── Click / marker handling ──────────────────────────────────────

        function markerRadius(canvas) {
            return Math.max(canvas.width * 0.013, 7);
        }

        function hitTestMarkers(px, py, pageInfo) {
            // Returns the global annotation index if the point hits a marker, else -1
            const r = markerRadius(pageInfo.canvas) * 1.6; // slightly larger hit area
            return annState.annotations.reduce((found, ann, idx) => {
                if (found !== -1 || ann.page !== pageInfo.num) return found;
                const cx = (ann.x_pct / 100) * pageInfo.canvas.width;
                const cy = (ann.y_pct / 100) * pageInfo.canvas.height;
                const dist = Math.sqrt((px - cx) ** 2 + (py - cy) ** 2);
                return dist <= r ? idx : found;
            }, -1);
        }

        function onAnnotationClick(e, pageInfo) {
            if (annState.pendingClick) ocultarPopover();

            const rect = pageInfo.canvas.getBoundingClientRect();
            const px = (e.clientX - rect.left) * (pageInfo.canvas.width / rect.width);
            const py = (e.clientY - rect.top) * (pageInfo.canvas.height / rect.height);

            // Check if clicking on an existing marker → delete it
            const hitIdx = hitTestMarkers(px, py, pageInfo);
            if (hitIdx !== -1) {
                eliminarAnotacion(hitIdx);
                return;
            }

            annState.pendingClick = {
                page: pageInfo.num,
                x_pct: (px / pageInfo.canvas.width) * 100,
                y_pct: (py / pageInfo.canvas.height) * 100,
                pageInfo,
            };

            drawMarkerOnCanvas(pageInfo.canvas, px, py, '?', '#6b7280');
            mostrarPopover(e.clientX, e.clientY);
        }

        function onAnnotationMouseMove(e, pageInfo) {
            const rect = pageInfo.canvas.getBoundingClientRect();
            const px = (e.clientX - rect.left) * (pageInfo.canvas.width / rect.width);
            const py = (e.clientY - rect.top) * (pageInfo.canvas.height / rect.height);
            const hit = hitTestMarkers(px, py, pageInfo);
            
            pageInfo.canvas.style.cursor = hit !== -1 ? 'pointer' : 'crosshair';
            
            // Redibujar con efecto hover si pasamos sobre un marcador
            if (hit !== -1) {
                redibujarPagina(pageInfo, hit);
            } else if (annState.annotations.some(a => a.page === pageInfo.num)) {
                redibujarPagina(pageInfo, -1);
            }
        }

        function mostrarToastBorrado() {
            const existing = document.getElementById('ann-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'ann-toast';
            toast.style.cssText = [
                'position:fixed',
                'bottom:28px',
                'left:50%',
                'transform:translateX(-50%) translateY(8px)',
                'z-index:10001',
                'background:#111827',
                'color:#fff',
                'font-size:13px',
                'font-weight:500',
                'padding:10px 18px',
                'border-radius:999px',
                'display:flex',
                'align-items:center',
                'gap:8px',
                'opacity:0',
                'transition:opacity .18s ease, transform .18s ease',
                'pointer-events:none',
            ].join(';');
            toast.innerHTML = `
                <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Anotación eliminada
            `;
            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(-50%) translateY(0)';
            });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(8px)';
                setTimeout(() => toast.remove(), 200);
            }, 1800);
        }

        function drawMarkerOnCanvas(canvas, cx, cy, label, color, isHovered = false) {
            const ctx = canvas.getContext('2d');
            let r = Math.max(canvas.width * 0.013, 7);
            
            // Aumentar tamaño ligeramente si está en hover
            if (isHovered) {
                r = r * 1.25;
            }

            ctx.save();
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
            
            // Borde más prominente en hover
            ctx.strokeStyle = 'white';
            ctx.lineWidth = isHovered ? 2.5 : 2;
            ctx.stroke();

            ctx.fillStyle = 'white';
            ctx.font = `bold ${Math.round(r * 0.9)}px helvetica, arial, sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, cx, cy);
            ctx.restore();
        }

        function redibujarPagina(pageInfo, hoveredIdx = -1) {
            const ctx = pageInfo.canvas.getContext('2d');
            ctx.clearRect(0, 0, pageInfo.canvas.width, pageInfo.canvas.height);

            annState.annotations
                .filter(a => a.page === pageInfo.num)
                .forEach(ann => {
                    const globalIdx = annState.annotations.indexOf(ann);
                    const cx = (ann.x_pct / 100) * pageInfo.canvas.width;
                    const cy = (ann.y_pct / 100) * pageInfo.canvas.height;
                    // Cambiar color a gris si está en hover
                    const color = hoveredIdx === globalIdx ? '#9ca3af' : '#dc2626';
                    const isHovered = hoveredIdx === globalIdx;
                    drawMarkerOnCanvas(pageInfo.canvas, cx, cy, String(globalIdx + 1), color, isHovered);
                });
        }

        function redibujarTodas() {
            annState.pages.forEach(redibujarPagina);
        }

        // ── Popover ──────────────────────────────────────────────────────

        function mostrarPopover(clientX, clientY) {
            const pop = document.getElementById('ann-popover');
            const ta = document.getElementById('ann-popover-text');
            const numEl = document.getElementById('ann-popover-num');
            ta.value = '';
            ta.classList.remove('border-red-300');
            if (numEl) numEl.textContent = annState.annotations.length + 1;
            pop.style.display = 'block';

            const pw = 308, ph = 240;
            const vw = window.innerWidth, vh = window.innerHeight;
            let top = clientY + 14;
            let left = clientX + 14;

            if (left + pw > vw - 10) left = clientX - pw - 14;
            if (top + ph > vh - 10) top = clientY - ph - 14;

            pop.style.top = Math.max(8, top) + 'px';
            pop.style.left = Math.max(8, left) + 'px';
            setTimeout(() => ta.focus(), 40);
        }

        function ocultarPopover() {
            const pop = document.getElementById('ann-popover');
            if (pop) pop.style.display = 'none';
            if (annState.pendingClick) {
                const pi = annState.pendingClick.pageInfo;
                annState.pendingClick = null;
                if (pi) redibujarPagina(pi);
            }
            annState.pendingClick = null;
        }

        function confirmarAnotacionPendiente() {
            const ta = document.getElementById('ann-popover-text');
            const text = (ta.value || '').trim();
            if (!text) {
                ta.classList.add('border-red-300');
                ta.focus();
                return;
            }
            if (!annState.pendingClick) return;

            const ann = {
                page: annState.pendingClick.page,
                x_pct: annState.pendingClick.x_pct,
                y_pct: annState.pendingClick.y_pct,
                content: text,
            };
            const pi = annState.pendingClick.pageInfo;
            annState.pendingClick = null;
            document.getElementById('ann-popover').style.display = 'none';

            annState.annotations.push(ann);
            redibujarTodas();
            renderAnnotationList();
        }

        function cancelarAnotacionPendiente() { ocultarPopover(); }

        function eliminarAnotacion(idx) {
            annState.annotations.splice(idx, 1);
            redibujarTodas();
            renderAnnotationList();
            mostrarToastBorrado();
        }

        // ── Annotation list rendering ────────────────────────────────────

        function renderAnnotationList() {
            const list = document.getElementById('ann-list');
            const empty = document.getElementById('ann-empty-hint');
            const count = document.getElementById('ann-count');

            count.textContent = annState.annotations.length;

            if (annState.annotations.length === 0) {
                list.classList.add('hidden');
                empty.classList.remove('hidden');
                return;
            }
            empty.classList.add('hidden');
            list.classList.remove('hidden');

            list.innerHTML = annState.annotations.map((ann, idx) => `
                <div class="flex items-start gap-3 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 hover:border-gray-200 transition-all">
                    <span class="mt-0.5 flex-shrink-0 flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-[11px] font-bold text-white shadow-sm">
                        ${idx + 1}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-semibold text-gray-400 tracking-wide uppercase mb-1">Pág. ${ann.page}</p>
                        <p class="text-sm text-gray-700 break-words leading-relaxed">${_escHtml(ann.content)}</p>
                    </div>
                    <button type="button" onclick="eliminarAnotacion(${idx})" title="Eliminar anotación"
                        class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-600 transition-all mt-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            `).join('');
        }

        function _escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Confirm / send rejection ─────────────────────────────────────

        function confirmarRechazoAnnotations() {
            if (annState.annotations.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin anotaciones',
                    text: 'Debes agregar al menos una anotación antes de rechazar.',
                    confirmButtonColor: '#dc2626',
                });
                return;
            }
            const generalComment = (document.getElementById('ann-general-comment').value || '').trim();
            cerrarModoAnotacion();
            enviarFirma('Rechazado', generalComment || null, null, null, annState.annotations);
        }

        // ── Keyboard shortcuts ───────────────────────────────────────────

        document.addEventListener('keydown', (e) => {
            const overlay = document.getElementById('ann-overlay');
            const popover = document.getElementById('ann-popover');
            const overlayOpen = overlay.style.display !== 'none';

            if (!overlayOpen) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                if (popover.style.display !== 'none') {
                    cancelarAnotacionPendiente();
                } else {
                    cerrarModoAnotacion();
                }
            }

            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                if (popover.style.display !== 'none') {
                    e.preventDefault();
                    confirmarAnotacionPendiente();
                }
            }
        });
    </script>
</body>

</html>
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
                            <button type="button" onclick="rechazar()" class="flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Rechazar Documento
                            </button>
                            <button type="button" onclick="aprobar()" class="flex items-center px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @php
    $tieneFirmaElectronica = \Illuminate\Support\Facades\DB::table('firmas_electronicas')
    ->where('empleado_id', $firma->empleado_id)
    ->exists();
    @endphp
    <script>
        window.__TIENE_FIRMA_ELECTRONICA__ = @json($tieneFirmaElectronica);
    </script>

    <script>
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('.documento-frame') || e.target.closest('.prose')) {
                e.preventDefault();
                return false;
            }
        });

        document.addEventListener('keydown', function(e) {
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
            const tieneFirma = Boolean(window.__TIENE_FIRMA_ELECTRONICA__);

            if (tieneFirma) {
                confirmarAccion(
                    'Firmar documento',
                    '¿Confirmas que deseas firmar este documento?',
                    'Aprobado'
                );
                return;
            }

            mostrarModalFirmaPrimeraVez();
        }

        function mostrarModalFirmaPrimeraVez() {
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
                        ctx.lineWidth = 2.2;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        ctx.strokeStyle = isDark ? '#e5e7eb' : '#111827';
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
                    enviarFirma('Aprobado', null, null, result.value.firmaFile);
                }
            });
        }

        function rechazar() {
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
                            ${
                                isImg
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
                confirmButtonText: 'Rechazar',
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

        function enviarFirma(estatus, comentario = null, evidenciaFiles = null, firmaFile = null) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Registrando tu firma',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const url = "{{ route('firmas.updateStatus', $firma->id) }}";

            const fd = new FormData();
            fd.append('estatus', estatus);

            if (comentario !== null) fd.append('comentario_rechazo', comentario);

            if (estatus === 'Rechazado') {
                const files = Array.isArray(evidenciaFiles) ? evidenciaFiles : [];
                files.forEach((f) => fd.append('evidencias[]', f));
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
</body>

</html>
@props([
    'previewUrl' => '',
    'downloadUrl' => null,
    'downloadName' => 'documento.pdf',
    'title' => 'Vista previa del documento',
    'badge' => null,
])

@php
    $downloadUrl = $downloadUrl ?? $previewUrl;
    $baseUrl = strtok($previewUrl, '#');
@endphp

<div class="pdf-preview-root mt-4 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
    data-pdf-root
    data-pdf-base="{{ $baseUrl }}">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 min-w-0">
            @if($badge)
                <span class="h-2 w-2 rounded-full bg-red-500 flex-shrink-0"></span>
            @endif
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $title }}</span>
        </div>
        <span class="text-xs text-gray-400 flex-shrink-0">PDF</span>
    </div>

    {{-- Barra de herramientas --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-1">
            <button type="button" data-pdf-zoom-out
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Alejar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                </svg>
            </button>
            <span data-pdf-zoom-label
                class="inline-flex items-center justify-center min-w-[3.25rem] px-2 h-8 text-xs font-medium text-gray-700 dark:text-gray-300 tabular-nums select-none">100%</span>
            <button type="button" data-pdf-zoom-in
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Acercar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
            <button type="button" data-pdf-zoom-reset
                class="inline-flex items-center justify-center h-8 px-2.5 ml-1 rounded-md text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Restablecer zoom">
                Ajustar
            </button>
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ $downloadUrl }}" download="{{ $downloadName }}"
                class="inline-flex items-center gap-1.5 h-8 px-3 rounded-md text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Descargar PDF">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar
            </a>
            <button type="button" data-pdf-print
                class="inline-flex items-center gap-1.5 h-8 px-3 rounded-md text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Imprimir">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
            <a href="{{ $baseUrl }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 h-8 px-3 rounded-md text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors"
                title="Abrir en nueva pestaña">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Abrir
            </a>
        </div>
    </div>

    {{-- Visor --}}
    <div class="bg-gray-100 dark:bg-gray-900/40 overflow-hidden" data-pdf-viewport style="height: 600px;">
        <div data-pdf-spacer>
            <div data-pdf-scaler>
                <iframe data-pdf-frame
                    src="{{ $baseUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH"
                    style="display: block; border: 0; width: 100%; height: 600px;"
                    title="{{ $title }}">
                </iframe>
            </div>
        </div>
    </div>
</div>

<style>
    [data-pdf-viewport] {
        overflow: hidden;
    }

    [data-pdf-viewport].is-zoomed {
        overflow: auto;
    }

    [data-pdf-scaler] {
        transform-origin: top left;
    }
</style>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-pdf-root]').forEach(function (root) {
                var viewport = root.querySelector('[data-pdf-viewport]');
                var spacer = root.querySelector('[data-pdf-spacer]');
                var scaler = root.querySelector('[data-pdf-scaler]');
                var iframe = root.querySelector('[data-pdf-frame]');
                var label = root.querySelector('[data-pdf-zoom-label]');
                var baseUrl = root.dataset.pdfBase;

                if (!viewport || !spacer || !scaler || !iframe || !baseUrl) return;

                var zoom = 100;
                var minZoom = 50;
                var maxZoom = 200;
                var step = 10;
                var baseHeight = 600;

                function applyZoom() {
                    var scale = zoom / 100;
                    var baseWidth = viewport.clientWidth;

                    if (zoom === 100) {
                        viewport.classList.remove('is-zoomed');
                        scaler.style.transform = 'none';
                        scaler.style.width = '';
                        scaler.style.height = '';
                        spacer.style.width = '';
                        spacer.style.height = '';
                        iframe.style.width = '100%';
                        iframe.style.height = baseHeight + 'px';
                        iframe.removeAttribute('scrolling');
                    } else {
                        viewport.classList.add('is-zoomed');
                        iframe.setAttribute('scrolling', 'no');

                        iframe.style.width = baseWidth + 'px';
                        iframe.style.height = baseHeight + 'px';
                        scaler.style.width = baseWidth + 'px';
                        scaler.style.height = baseHeight + 'px';
                        scaler.style.transform = 'scale(' + scale + ')';

                        spacer.style.width = Math.ceil(baseWidth * scale) + 'px';
                        spacer.style.height = Math.ceil(baseHeight * scale) + 'px';
                    }

                    if (label) label.textContent = zoom + '%';
                }

                var btnOut = root.querySelector('[data-pdf-zoom-out]');
                var btnIn = root.querySelector('[data-pdf-zoom-in]');
                var btnReset = root.querySelector('[data-pdf-zoom-reset]');
                var btnPrint = root.querySelector('[data-pdf-print]');

                if (btnOut) btnOut.addEventListener('click', function () {
                    zoom = Math.max(minZoom, zoom - step);
                    applyZoom();
                });

                if (btnIn) btnIn.addEventListener('click', function () {
                    zoom = Math.min(maxZoom, zoom + step);
                    applyZoom();
                });

                if (btnReset) btnReset.addEventListener('click', function () {
                    zoom = 100;
                    viewport.scrollTop = 0;
                    viewport.scrollLeft = 0;
                    applyZoom();
                });

                if (btnPrint) btnPrint.addEventListener('click', function () {
                    try {
                        if (iframe.contentWindow) {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                            return;
                        }
                    } catch (e) { /* fallback */ }

                    var win = window.open(baseUrl, '_blank');
                    if (win) {
                        win.addEventListener('load', function () {
                            win.focus();
                            win.print();
                        });
                    }
                });

                window.addEventListener('resize', applyZoom);
                applyZoom();
            });
        });
    </script>
    @endpush
@endonce

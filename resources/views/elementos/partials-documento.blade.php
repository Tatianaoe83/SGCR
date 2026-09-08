@php
    $archivoMostrar = $elemento->archivo_actual;
    $archivoMostrarUrl = $elemento->archivo_actual_url;
    $extension = $archivoMostrar ? strtolower(pathinfo($archivoMostrar, PATHINFO_EXTENSION)) : null;
    $esDocumentoOficial = $archivoMostrar === $elemento->archivo_firmado;

    $firmaRechazadaDoc = ($elemento->status === 'Rechazado' && isset($firmas))
        ? $firmas->firstWhere('estatus', 'Rechazado')
        : null;
    $urlAnotado = ($firmaRechazadaDoc
        && $firmaRechazadaDoc->anotaciones_pdf_path
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($firmaRechazadaDoc->anotaciones_pdf_path))
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($firmaRechazadaDoc->anotaciones_pdf_path)
        : null;
    $previewUrl = $urlAnotado ?? $archivoMostrarUrl;
@endphp

<section class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 shadow-sm ring-1 ring-[#021D49]/15">
    <div class="px-5 py-3 bg-[#021D49] text-white flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold tracking-wide">
            Documento del elemento
        </h3>
        @if($previewUrl)
            <a href="{{ $previewUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1 text-xs font-medium hover:bg-white/20">
                Abrir
            </a>
        @endif
    </div>

    <div class="p-4 sm:p-5">
        @if($archivoMostrar && $archivoMostrarUrl)
            @if($extension === 'pdf')
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            @if($urlAnotado)
                                <span class="h-2 w-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Documento con
                                    anotaciones de rechazo</span>
                            @else
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Vista previa del
                                    documento</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            @if($urlAnotado)
                                <a href="{{ $urlAnotado }}" download
                                    class="inline-flex items-center gap-1 text-xs text-red-600 dark:text-red-400 hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Descargar anotado
                                </a>
                            @endif
                            <span class="text-xs text-gray-400">PDF</span>
                        </div>
                    </div>

                    <iframe src="{{ $previewUrl }}#toolbar=0&navpanes=0" class="w-full"
                        style="height: 640px; border: 0;" type="application/pdf">
                    </iframe>

                    @if($urlAnotado && $firmaRechazadaDoc && ($firmaRechazadaDoc->anotaciones_rechazo || $firmaRechazadaDoc->comentario_rechazo))
                        <div class="border-t border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800/80 px-5 py-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 select-none">
                                    Observaciones</p>
                                @if($firmaRechazadaDoc->anotaciones_rechazo)
                                    <span class="text-xs tabular-nums text-gray-400">{{ count($firmaRechazadaDoc->anotaciones_rechazo) }}
                                        nota(s)</span>
                                @endif
                            </div>

                            @if($firmaRechazadaDoc->comentario_rechazo)
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic leading-relaxed border-l-2 border-gray-200 dark:border-gray-600 pl-3">
                                    "{{ $firmaRechazadaDoc->comentario_rechazo }}"
                                </p>
                            @endif

                            @if($firmaRechazadaDoc->anotaciones_rechazo)
                                <div class="divide-y divide-gray-50 dark:divide-gray-700/40">
                                    @foreach($firmaRechazadaDoc->anotaciones_rechazo as $i => $ann)
                                        <div class="flex items-start gap-3 py-2.5">
                                            <span class="mt-0.5 flex-shrink-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold">{{ $i + 1 }}</span>
                                            <div class="min-w-0 flex-1">
                                                <span class="text-xs text-gray-400 dark:text-gray-500 mr-2">Pág.&nbsp;{{ $ann['page'] }}</span>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $ann['content'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <img src="{{ $archivoMostrarUrl }}" alt="Documento" class="w-full h-auto">
                </div>
            @else
                <div class="text-center p-8 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Vista previa no disponible para este tipo de archivo
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Tipo: {{ strtoupper($extension) }}
                    </p>

                    <a href="{{ $archivoMostrarUrl }}" target="_blank" rel="noopener noreferrer"
                        class="mt-4 btn-primary">
                        Abrir documento
                    </a>
                </div>
            @endif
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Este elemento aún no tiene un documento cargado.
            </p>
        @endif
    </div>
</section>

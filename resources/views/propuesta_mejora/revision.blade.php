<x-app-layout>
    @php
    $esPendiente = $propuesta->estatus === 'Pendiente';
    $esAprobado = $propuesta->estatus === 'Aprobado';
    $esRechazado = $propuesta->estatus === 'Rechazado';

    // Badges dinámicos para Light y Dark mode
    $badgeStyle = match ($propuesta->estatus) {
    'Aprobado' => 'bg-green-100 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
    'Rechazado' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
    default => 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:border-yellow-500/20',
    };
    $dotClass = match ($propuesta->estatus) {
    'Aprobado' => 'bg-green-500 dark:bg-green-400',
    'Rechazado' => 'bg-red-500 dark:bg-red-400',
    default => 'bg-yellow-500 dark:bg-yellow-500',
    };
    @endphp

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200 py-10 px-4 sm:px-6 lg:px-8 font-sans pb-32 transition-colors duration-300">
        <div class="max-w-6xl mx-auto">

            {{-- Encabezado --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 dark:bg-slate-900 dark:text-indigo-300 text-xs font-bold uppercase tracking-widest border border-indigo-200 dark:border-indigo-500/20">
                        PROPUESTA #{{ $propuesta->getKey() }}
                    </span>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                        {{ $propuesta->estatus }}
                    </span>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight tracking-tight mb-2">
                    Revisión de Propuesta de Mejora
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base max-w-3xl leading-relaxed">
                    {{ $propuesta->titulo }}
                </p>
            </div>

            <form id="form-decision" method="POST">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    {{-- COLUMNA IZQUIERDA (Principal) --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- Justificación --}}
                        <div class="bg-white dark:bg-slate-900/40 rounded-3xl border border-slate-200 dark:border-slate-700/60 p-6 md:p-8 shadow-lg dark:shadow-2xl dark:shadow-black/40 hover:shadow-xl dark:hover:shadow-black/50 transition-all duration-300 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 w-40 h-40 rounded-full -mr-20 -mt-20 blur-3xl group-hover:scale-125 transition-transform duration-500"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-3 mb-6">
                                    <h3 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">Justificación de la Propuesta</h3>
                                </div>
                                <div class="space-y-3">
                                    @if($propuesta->justificacion)
                                        <div class="rounded-xl p-4 border border-slate-200/50 dark:border-slate-700/40 leading-relaxed text-sm text-slate-700 dark:text-slate-200">
                                            <p>{{ $propuesta->justificacion }}</p>
                                        </div>
                                    @else
                                        <div class="rounded-xl p-4 border border-dashed border-slate-300 dark:border-slate-600 text-center">
                                            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">No se proporcionó una justificación detallada para esta propuesta.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Comentarios --}}
                        <div class="bg-white dark:bg-slate-900/30 rounded-3xl border border-slate-200 dark:border-slate-700/60 p-6 md:p-8 shadow-lg dark:shadow-2xl dark:shadow-black/40 hover:shadow-xl dark:hover:shadow-black/50 transition-all duration-300 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-500/5 dark:bg-emerald-400/10 rounded-full -mr-20 -mt-20 blur-3xl group-hover:scale-125 transition-transform duration-500"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-3 mb-6">
                                    <h3 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">Comentario</h3>
                                </div>

                                @if($esPendiente)
                                <div class="space-y-3">
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Agrega tu comentario sobre esta propuesta:</p>
                                    <textarea
                                        id="comentario-revisor"
                                        name="comentario"
                                        rows="4"
                                        placeholder="Escribe aquí tu evaluación, observaciones o recomendaciones..."
                                        class="w-full rounded-xl border border-emerald-300/50 dark:border-emerald-600/30 bg-white dark:bg-slate-900/70 px-4 py-3 text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500 transition-all resize-none shadow-inner dark:shadow-inner dark:shadow-black/20 focus:shadow-emerald-500/20 backdrop-blur-sm">{{ old('comentario') }}</textarea>
                                </div>
                                @else
                                @if($propuesta->comentario)
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 pb-3 border-b border-emerald-200/50 dark:border-emerald-500/20">
                                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm font-bold text-emerald-900 dark:text-emerald-300">Resolución Oficial</span>
                                        <span class="ml-auto text-xs bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 px-3 py-1 rounded-lg font-medium">Respuesta</span>
                                    </div>
                                    <div class="bg-gradient-to-br from-emerald-50/50 to-emerald-100/30 dark:from-emerald-500/10 dark:to-emerald-600/5 border border-emerald-200/40 dark:border-emerald-500/20 rounded-xl p-4 shadow-sm">
                                        <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">{{ $propuesta->comentario }}</p>
                                    </div>
                                </div>
                                @else
                                <div class="flex flex-col items-center justify-center py-6 text-center">
                                    <div class="w-14 h-14 rounded-full bg-slate-200/50 dark:bg-slate-700/50 flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No hay comentario registrado para esta propuesta.</p>
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>

                    </div>

                    {{-- COLUMNA DERECHA (Sidebar) --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- Solicitante --}}
                        <div class="bg-white dark:bg-slate-900/30 rounded-3xl border border-slate-200 dark:border-slate-700/60 p-6 shadow-lg dark:shadow-2xl dark:shadow-black/40 hover:shadow-xl dark:hover:shadow-black/50 transition-all duration-300 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 dark:bg-blue-400/10 rounded-full -mr-16 -mt-16 blur-3xl group-hover:scale-125 transition-transform duration-500"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-2 mb-5">
                                    <h3 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">Solicitante</h3>
                                </div>
                                <div class="flex items-center gap-4 mt-3 bg-white dark:bg-slate-900/70 rounded-xl p-3 border border-slate-200/50 dark:border-slate-700/40">
                                    <div class="overflow-hidden flex-1">
                                        <p class="text-sm font-bold text-slate-800 dark:text-white truncate">
                                            {{ trim(($propuesta->empleado?->nombres ?? '') . ' ' . ($propuesta->empleado?->apellido_paterno ?? '') . ' ' . ($propuesta->empleado?->apellido_materno ?? '')) ?: 'Usuario Desconocido' }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-1">
                                            {{ $propuesta->empleado?->correo ?? 'Sin correo asignado' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Elementos Relacionados --}}
                        <div class="bg-white dark:bg-slate-900/30 rounded-3xl border border-slate-200 dark:border-slate-700/60 p-6 shadow-lg dark:shadow-2xl dark:shadow-black/40 hover:shadow-xl dark:hover:shadow-black/50 transition-all duration-300 relative overflow-hidden group">
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-2 mb-5">
                                    <h3 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">Elemento Relacionado</h3>
                                </div>

                                <div class="flex items-center justify-between bg-white dark:bg-slate-900/70 border border-slate-200/60 dark:border-slate-700/50 rounded-xl p-3 hover:border-purple-300 dark:hover:border-purple-500/40 transition-all cursor-pointer group/item shadow-sm overflow-hidden mt-2">
                                    <div class="flex items-center gap-3 overflow-hidden flex-1">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate group-hover/item:text-purple-600 dark:group-hover/item:text-purple-400 transition-colors">
                                                {{ $propuesta->elemento?->nombre_elemento ?? 'Documentación_v2.pdf' }}
                                            </p>
                                            @if($propuesta->elemento?->version)
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">v{{ $propuesta->elemento->version }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('elementos.show', $propuesta->elemento?->getKey()) }}" target="_blank" class="text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 p-2 transition-colors ml-2 flex-shrink-0 group-hover/item:scale-110">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($esPendiente)
    <div class="fixed bottom-0 left-0 w-full bg-slate-100/95 dark:bg-slate-900/95 backdrop-blur-md p-4 z-50 transition-colors duration-300">
        <div class="max-w-6xl mx-auto flex justify-end gap-3 sm:gap-4">

            {{-- Rechazar --}}
            <button type="button" onclick="submitDecision('rechazar')"
                class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-red-500 hover:bg-red-600 dark:bg-red-500 dark:hover:bg-red-600 text-white text-sm font-bold uppercase tracking-wider flex items-center gap-2 transition-all shadow-sm active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Rechazar
            </button>

            {{-- Aprobar --}}
            <button type="button" onclick="submitDecision('aprobar')"
                class="px-5 py-2.5 rounded-xl bg-green-500 hover:bg-green-600 dark:bg-green-500 dark:hover:bg-green-600 text-white text-sm font-extrabold uppercase tracking-wider flex items-center gap-2 transition-all shadow-md shadow-green-500/20 dark:shadow-green-500/20 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                Aprobar Propuesta
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function submitDecision(tipo) {
            const config = {
                aprobar: {
                    title: '¿Aprobar Propuesta?',
                    text: 'Esta acción marcará la propuesta como aprobada.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'Sí, Aprobar',
                    cancelButtonColor: '#6b7280',
                    cancelButtonText: 'Cancelar'
                },
                rechazar: {
                    title: '¿Rechazar Propuesta?',
                    text: 'Esta acción marcará la propuesta como rechazada.',
                    icon: 'warning',
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Sí, Rechazar',
                    cancelButtonColor: '#6b7280',
                    cancelButtonText: 'Cancelar'
                }
            };

            Swal.fire({
                ...config[tipo],
                showCancelButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form-decision');
                    form.action = tipo === 'aprobar' ?
                        '{{ route("propuestas.aprobar", $propuesta->getKey()) }}' :
                        '{{ route("propuestas.rechazar", $propuesta->getKey()) }}';
                    form.submit();
                }
            });
        }
    </script>
    @endif

</x-app-layout>
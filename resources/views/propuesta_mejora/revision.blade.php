<x-app-layout>
    @php
    $esPendiente = $propuesta->estatus === 'Pendiente';

    $badgeStyle = match ($propuesta->estatus) {
    'Aprobado' => 'bg-green-100 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
    'Rechazado' => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
    default => 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:border-yellow-500/20',
    };
    $dotClass = match ($propuesta->estatus) {
    'Aprobado' => 'bg-green-500',
    'Rechazado' => 'bg-red-500',
    default => 'bg-yellow-500',
    };
    $nombreSolicitante = trim(($propuesta->empleado?->nombres ?? '') . ' ' . ($propuesta->empleado?->apellido_paterno ?? '') . ' ' . ($propuesta->empleado?->apellido_materno ?? '')) ?: 'Usuario Desconocido';
    $iniciales = strtoupper(mb_substr($propuesta->empleado?->nombres ?? 'U', 0, 1) . mb_substr($propuesta->empleado?->apellido_paterno ?? '', 0, 1));
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 py-5 w-full max-w-6xl mx-auto">

        {{-- Encabezado --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    <a href="{{ route('propuesta_mejora.index') }}" class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-500 hover:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors" title="Volver">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <span class="px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300 text-xs font-bold uppercase tracking-wider border border-violet-200 dark:border-violet-500/20">
                        Propuesta #{{ $propuesta->getKey() }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white leading-tight truncate">Revisión de Propuesta de Mejora</h1>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                {{ $propuesta->estatus }}
            </span>
        </div>

        <form id="form-decision" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- COLUMNA PRINCIPAL --}}
                <div class="lg:col-span-2 space-y-5">

                    {{-- Titulo de la propuesta --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Propuesta</h3>
                        </div>
                        <p class="text-base font-semibold text-slate-800 dark:text-slate-100 leading-snug">{{ $propuesta->titulo }}</p>
                    </div>

                    {{-- Justificación --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Justificación</h3>
                        </div>
                        @if($propuesta->justificacion)
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{{ $propuesta->justificacion }}</p>
                        @else
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">No se proporcionó una justificación detallada.</p>
                        @endif
                    </div>

                    {{-- Comentario --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.4-3.5A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Comentario del Revisor</h3>
                        </div>

                        @if($esPendiente)
                        <textarea
                            id="comentario-revisor"
                            name="comentario"
                            rows="4"
                            placeholder="Escribe tu evaluación, observaciones o recomendaciones..."
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 px-4 py-3 text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition resize-none">{{ old('comentario') }}</textarea>
                        @elseif($propuesta->comentario)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700 p-4">
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{{ $propuesta->comentario }}</p>
                        </div>
                        @else
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">No hay comentario registrado.</p>
                        @endif
                    </div>
                </div>

                {{-- SIDEBAR --}}
                <div class="lg:col-span-1 space-y-5">

                    {{-- Solicitante --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Solicitante</h3>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold shrink-0">
                                {{ $iniciales }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ $nombreSolicitante }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $propuesta->empleado?->correo ?? 'Sin correo' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Elemento --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Elemento Relacionado</h3>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-violet-500 flex items-center justify-center text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $propuesta->elemento?->nombre_elemento ?? 'Sin elemento' }}</p>
                                @if($propuesta->elemento?->version)
                                <p class="text-xs text-slate-500 dark:text-slate-400">v{{ $propuesta->elemento->version }}</p>
                                @endif
                            </div>
                            @if($propuesta->elemento)
                            <a href="{{ route('elementos.show', $propuesta->elemento?->getKey()) }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-400 hover:text-violet-600 hover:bg-slate-100 dark:hover:bg-slate-700 dark:hover:text-violet-400 transition-colors shrink-0" title="Abrir elemento">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            @endif
                        </div>
                    </div>

                    {{-- Decisión / Estado --}}
                    @if($esPendiente)
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Decisión</h3>
                        <div class="space-y-2.5">
                            <button type="button" onclick="submitDecision('aprobar')"
                                class="w-full px-4 py-2.5 rounded-xl bg-green-500 hover:bg-green-600 text-white text-sm font-bold flex items-center justify-center gap-2 transition-all shadow-sm active:scale-[0.98]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Aprobar Propuesta
                            </button>
                            <button type="button" onclick="submitDecision('rechazar')"
                                class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-700 hover:bg-red-50 dark:hover:bg-red-500/10 border border-slate-300 dark:border-slate-600 hover:border-red-300 text-red-600 dark:text-red-400 text-sm font-bold flex items-center justify-center gap-2 transition-all active:scale-[0.98]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Rechazar
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Resolución</h3>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $badgeStyle }}">
                            <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                            Propuesta {{ $propuesta->estatus }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if($esPendiente)
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function submitDecision(tipo) {
            const isDark = document.documentElement.classList.contains('dark');
            const config = {
                aprobar: {
                    title: '¿Aprobar Propuesta?',
                    text: 'Esta acción marcará la propuesta como aprobada.',
                    icon: 'success',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'Sí, Aprobar',
                },
                rechazar: {
                    title: '¿Rechazar Propuesta?',
                    text: 'Esta acción marcará la propuesta como rechazada.',
                    icon: 'warning',
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Sí, Rechazar',
                }
            };

            Swal.fire({
                ...config[tipo],
                showCancelButton: true,
                cancelButtonColor: '#6b7280',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                background: isDark ? '#1f2937' : '#ffffff',
                color: isDark ? '#e5e7eb' : '#374151',
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

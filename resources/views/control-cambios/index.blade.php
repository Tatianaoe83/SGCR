<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 pb-8 w-full max-w-9xl mx-auto">

        <div class="sm:flex sm:justify-between sm:items-center mb-4 mt-4">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Control de Cambios</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Registro y seguimiento de cambios del sistema</p>
            </div>

            <div>
                <a href="{{ route('control-cambios.export') }}"
                    class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exportar Excel
                </a>
            </div>
        </div>

        <div class="relative">
            @include('partials.page-loader')
            <div id="table-content" class="opacity-0 transition-opacity duration-300 bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <header class="px-4 sm:px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center gap-3">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Control de Cambios</h2>
                    <div class="ui-toolbar sm:ml-auto sm:w-auto">
                        <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $cambios->count() }} registros</span>
                        <div class="ui-search">
                            <span class="ui-search-icon">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input type="search" id="cc-search" class="ui-search-input" placeholder="Buscar...">
                        </div>
                    </div>
                </header>
                <div class="overflow-x-auto">
                    @if ($cambios->count() === 0)
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Sin registros</h3>
                        <p class="text-gray-500 dark:text-gray-400">No hay registros de control de cambios.</p>
                    </div>
                    @else
                    <table id="control-cambios-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Folio</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Naturaleza</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Afectación</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Prioridad</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Elemento</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($cambios as $cambio)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors duration-150">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-[#E8EEF5] text-[#021D49] dark:bg-[#021D49]/40 dark:text-[#A3B9D4] tabular-nums">
                                        {{ $cambio->FolioCambio ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $cambio->Naturaleza ?? '—' }}
                                </td>

                                <td class="px-6 py-3">
                                    @if($cambio->Afectacion)
                                    <span class="badge-status badge-neutral">
                                        {{ $cambio->Afectacion }}
                                    </span>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="px-6 py-3 whitespace-nowrap">
                                    @if($cambio->Prioridad)
                                    <span class="badge-status
                                        @if($cambio->Prioridad == 1) badge-neutral
                                        @elseif($cambio->Prioridad == 2) badge-info
                                        @elseif($cambio->Prioridad == 3) badge-warning
                                        @else badge-danger
                                        @endif">
                                        @if($cambio->Prioridad == 1) Baja
                                        @elseif($cambio->Prioridad == 2) Media
                                        @elseif($cambio->Prioridad == 3) Alta
                                        @else Crítica
                                        @endif
                                    </span>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <div class="max-w-xs truncate" title="{{ $cambio->elemento->nombre_elemento ?? '' }}">
                                        {{ $cambio->elemento->nombre_elemento ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('control-cambios.show', $cambio->id) }}"
                                            class="btn-icon-muted"
                                            title="Ver">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <a href="{{ route('control-cambios.edit', $cambio->id) }}"
                                            class="btn-icon"
                                            title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
                @if ($cambios->hasPages())
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $cambios->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.getElementById('cc-search')?.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#control-cambios-table tbody tr').forEach(function(row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Cambio Realizado!',
            text: '{{ session("success") }}',
            confirmButtonColor: '#021D49',
            confirmButtonText: 'Aceptar'
        });
    </script>
    @endif
</x-app-layout>
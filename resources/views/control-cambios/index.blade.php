<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Control de Cambios</h1>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="overflow-x-auto">
                    @if ($cambios->count() === 0)
                    <div class="p-8 text-center text-gray-500">
                        No hay registros de control de cambios.
                    </div>
                    @else
                    <table class="min-w-full border-separate border-spacing-y-2">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr class="text-xs uppercase text-gray-400 dark:text-gray-400">
                                <th class="px-6 py-2 text-left">Folio</th>
                                <th class="px-6 py-2 text-left">Naturaleza</th>
                                <th class="px-6 py-2 text-left">Afectación</th>
                                <th class="px-6 py-2 text-left">Prioridad</th>
                                <th class="px-6 py-2 text-left">Elemento</th>
                                <th class="px-6 py-2 text-left">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($cambios as $cambio)
                            <tr class="bg-white dark:bg-gray-800 shadow-sm rounded-lg hover:shadow-md transition">
                                <td class="px-6 py-4 font-semibold">
                                    {{ $cambio->FolioCambio ?? '—' }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $cambio->Naturaleza ?? '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs">
                                        {{ $cambio->Afectacion ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    @if($cambio->Prioridad)
                                    <span class="px-2 py-1 text-xs rounded-md
                                        @if($cambio->Prioridad <= 2)
                                            bg-green-50 text-green-700
                                        @elseif($cambio->Prioridad <= 4)
                                            bg-yellow-50 text-yellow-700
                                        @else
                                            bg-red-50 text-red-700
                                        @endif">
                                        {{ $cambio->Prioridad }}
                                    </span>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ $cambio->elemento->nombre_elemento ?? '—' }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-start gap-2">

                                        <a href="{{ route('control-cambios.show', $cambio->id) }}"
                                            class="group w-9 h-9 flex items-center justify-center rounded-xl
                                            bg-indigo-50 text-indigo-600
                                            shadow-sm
                                            hover:bg-indigo-100 hover:shadow-md hover:-translate-y-[1px]
                                            focus:outline-none focus:ring-2 focus:ring-indigo-300
                                            transition-all duration-200"
                                            title="Ver">
                                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                c4.478 0 8.268 2.943 9.542 7
                                                -1.274 4.057-5.064 7-9.542 7
                                                -4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <a href="{{ route('control-cambios.edit', $cambio->id) }}"
                                            class="group w-9 h-9 flex items-center justify-center rounded-xl
                                            bg-violet-50 text-violet-600
                                            shadow-sm
                                            hover:bg-violet-100 hover:shadow-md hover:-translate-y-[1px]
                                            focus:outline-none focus:ring-2 focus:ring-violet-300
                                            transition-all duration-200"
                                            title="Editar">
                                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5h6m2 0a2 2 0 012 2v10a2 2 0 01-2 2H7
                                                    a2 2 0 01-2-2V7a2 2 0 012-2h2" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M18.414 2.586a2 2 0 010 2.828L9.828 14H7v-2.828
                                                    l8.586-8.586a2 2 0 012.828 0z" />
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
            </div>
        </div>
    </div>
</x-app-layout>
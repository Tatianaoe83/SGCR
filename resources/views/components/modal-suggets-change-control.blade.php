@props([
'align' => 'right'
])

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    class="relative">

    <button
        @click="open = true"
        class="relative inline-flex items-center justify-center w-9 h-9 rounded-full
               text-gray-600 dark:text-gray-300
               hover:bg-gray-100 dark:hover:bg-gray-700
               focus:outline-none focus:ring-2 focus:ring-purple-500">

        <svg xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 3a7 7 0 00-4 12.74V18a1 1 0 001 1h6a1 1 0 001-1v-2.26A7 7 0 0012 3z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 21h6" />
        </svg>

        <span class="absolute top-1 right-1 w-2 h-2 bg-purple-500 rounded-full"></span>
    </button>

    <!-- OVERLAY -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm"
        @click="open = false"
        x-cloak>
    </div>

    <!-- MODAL -->
    <div
        x-show="open"
        class="fixed inset-0 z-[70] flex items-center justify-center px-4"
        x-cloak>

        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-6 scale-95"
            @click.stop
            class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl
                   border border-gray-200 dark:border-gray-700 overflow-hidden">

            <!-- HEADER -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30
                                flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3a7 7 0 00-4 12.74V18a1 1 0 001 1h6a1 1 0 001-1v-2.26A7 7 0 0012 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Sugerencia de mejora
                    </h3>
                </div>

                <button
                    @click="open = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- BODY -->
            <div class="px-6 py-5 space-y-4 text-sm text-gray-700 dark:text-gray-300">
                <p>
                    Usa este espacio para proponer mejoras sobre los elementos del SGC.
                    La sugerencia será revisada antes de generar un control de cambios.
                </p>

                <div
                    class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700
                           border border-dashed border-gray-300 dark:border-gray-600
                           text-center text-gray-500">
                    Aquí irá el formulario de sugerencia
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button
                    @click="open = false"
                    class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300
                           border border-gray-300 dark:border-gray-600
                           hover:bg-gray-100 dark:hover:bg-gray-700">
                    Cancelar
                </button>

                <button
                    class="px-5 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700">
                    Enviar sugerencia
                </button>
            </div>

        </div>
    </div>
</div>
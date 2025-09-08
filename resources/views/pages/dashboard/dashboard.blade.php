<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-10">

            <!-- Left: Title -->
            <div class="sm:mb-0">
                <main class="m">
                    <div class="bg-gray-900 rounded-lg shadow-xl h-full flex flex-col">
                        <div class="p-4 border-b border-gray-700 flex items-center">
                            <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full mr-4">
                                <span class="flex h-full w-full items-center justify-center rounded-full bg-purple-700">
                                    <span class="fas fa-user text-white">smart_toy</span>
                                </span>
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold">Asistente Virtual</h2>
                                <p class="text-sm text-green-400 flex items-center"><span class="material-icons text-xs mr-1">circle</span> En línea</p>
                            </div>
                        </div>
                        <div class="flex-1 p-6 overflow-y-auto space-y-4">
                            <div class="flex items-start gap-3">
                                <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                    <span class="flex h-full w-full items-center justify-center rounded-full bg-purple-700">
                                        <span class="material-icons text-white">smart_toy</span>
                                    </span>
                                </span>
                                <div class="bg-gray-800 p-3 rounded-lg max-w-lg">
                                    <p class="text-sm">¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy? Puedes preguntarme sobre la estructura de la empresa, documentos SGC o gestión de usuarios.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 justify-end">
                                <div class="bg-purple-600 p-3 rounded-lg max-w-lg">
                                    <p class="text-sm">Hola, necesito encontrar el manual de calidad</p>
                                </div>
                                <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                    <span class="w-10 h-10 rounded-full bg-indigo-200 text-indigo-800 flex items-center justify-center">TO</span>
                                </span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full">
                                    <span class="flex h-full w-full items-center justify-center rounded-full bg-purple-700">
                                        <span class="material-icons text-white">smart_toy</span>
                                    </span>
                                </span>
                                <div class="bg-gray-800 p-3 rounded-lg max-w-lg">
                                    <p class="text-sm">Claro, estoy buscando el manual de calidad. Un momento por favor...</p>
                                    <p class="text-sm mt-2">Aquí está el documento que buscas:</p>
                                    <div class="mt-2 bg-gray-700 p-3 rounded-lg flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="material-icons text-red-400 mr-3">picture_as_pdf</span>
                                            <div>
                                                <p class="font-semibold text-sm">Manual_Calidad_v3.pdf</p>
                                                <p class="text-xs text-gray-400">1.2 MB</p>
                                            </div>
                                        </div>
                                        <button class="p-2 rounded-full hover:bg-gray-600">
                                            <span class="material-icons">download</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 border-t border-gray-700">
                            <div class="flex items-center gap-3 focus-within:ring-2 focus-within:ring-purple-500">
                                <input type="text" placeholder="Preguntame..." class="rounded-md">
                                <button
                                    type="submit"
                                    class="bg-purple-600 text-white rounded-md px-4 py-2 w-fit hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer hover:scale-105 transition-all">
                                    Enviar
                                </button>

                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
</x-app-layout>
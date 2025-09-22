<x-app-layout>
    <div class="px-3 sm:px-6 lg:px-8 py-8 w-full max-w-screen-2xl mx-auto">

        <h1 class="text-3xl font-bold text-gray-200 mb-4 py-4">
            Preview
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <section class="flex lg:col-span-2">
                <div class="bg-slate-800/40 border border-slate-600 rounded-xl shadow-sm p-3 w-full flex flex-col">

                    <div class="flex items-center justify-between px-2 py-1">

                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="text-xs px-2 py-1 border border-slate-600 rounded cursor-pointer hover:scale-105 transition-all duration-300"
                                onclick="setDevice('375px','420px')">
                                Móvil
                            </button>
                            <button type="button"
                                class="text-xs px-2 py-1 border border-slate-600 rounded cursor-pointer hover:scale-105 transition-all duration-300"
                                onclick="setDevice('768px','420px')">
                                Tablet
                            </button>
                            <button type="button"
                                class="text-xs px-2 py-1 border border-slate-600 rounded cursor-pointer hover:scale-105 transition-all duration-300"
                                onclick="setDevice('100%','420PX')">
                                Web
                            </button>
                        </div>
                    </div>

                    <div id="mailFrameWrap" class="mx-auto mt-2 w-full" style="max-width: 100%;">
                        <div class="rounded-lg overflow-hidden bg-white border">
                            <iframe
                                id="mailFrame"
                                class="w-full block"
                                style="height:420px; overflow:auto;"
                                referrerpolicy="no-referrer"
                                sandbox="allow-same-origin"
                                srcdoc='@php
              $srcdoc = "<!doctype html><html><head><meta charset=\"utf-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                <style>html,body{margin:0;padding:0}</style>
              </head><body>{$html}</body></html>";
              echo htmlspecialchars($srcdoc, ENT_QUOTES);
            @endphp'>
                            </iframe>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="border border-slate-600 rounded-xl shadow-sm p-3 w-full lg:col-span-1">
                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                        <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100 mb-2">
                            Variables disponibles
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                            Estas variables se reemplazarán automáticamente al enviar el correo:
                        </p>

                        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @forelse($tpl->vars as $var => $desc)
                            <li class="flex flex-col">
                                <span class="font-bold text-md px-2 py-1 bg-slate-200 dark:bg-slate-700 rounded text-center">
                                    {{ $var }}
                                </span>
                                <span class="font-mono text-xs px-2 py-1 bg-slate-200 dark:bg-slate-700 rounded text-center">
                                    {{ $desc }}
                                </span>
                            </li>
                            @empty
                            <li class="col-span-3 text-sm text-slate-500 dark:text-slate-400">
                                No hay variables definidas para este tipo.
                            </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <script>
        function setDevice(width, height) {
            const wrap = document.getElementById('mailFrameWrap');
            const frame = document.getElementById('mailFrame');
            wrap.style.maxWidth = width;
            frame.style.height = height;
        }
    </script>

</x-app-layout>
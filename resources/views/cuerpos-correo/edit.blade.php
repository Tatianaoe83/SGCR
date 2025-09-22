<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor de correo: {{ $tpl->nombre }}</title>

    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" />

    <style>
        #editor-shell {
            height: calc(100vh - 14rem);
        }

        #gjs,
        .gjs-cv-canvas,
        .gjs-frame-wrapper {
            height: 100% !important;
        }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-12 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-6 jusitfy-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                    Editor de correo: {{ $tpl->nombre }}
                </h1>
            </div>

            <div class="flex gap-2">
                <button onclick="save()"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg shadow cursor-pointer transition-all duration-200 hover:scale-105">
                    Guardar
                </button>
                <a href="{{ route('cuerpos-correo.index') }}"
                    class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg cursor-pointer hover:scale-105 transition-all duration-200">
                    Volver
                </a>
            </div>

        </div>

        <div class="border border-gray-200 dark:border-slate-700 rounded-lg p-4">
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

        <div id="editor-shell" class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
            <div id="gjs"></div>
        </div>
    </div>

    {{-- Scripts GrapesJS --}}
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-preset-newsletter"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const editor = grapesjs.init({
            container: '#gjs',
            plugins: ['gjs-preset-newsletter'],
            storageManager: {
                type: null
            }
        });

        editor.setComponents(@json($tpl->cuerpo_html));

        async function save() {
            const html = editor.getHtml() + '<style>' + editor.getCss() + '</style>';
            const resp = await fetch("{{ route('cuerpos-correo.updateEditor', $tpl->id_cuerpo) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    html
                })
            });
            Swal.fire({
                title: resp.ok ? '¡Guardado!' : 'Error',
                text: resp.ok ?
                    'La plantilla fue guardada correctamente en la base de datos.' : 'Ocurrió un problema al guardar la plantilla. Intenta de nuevo.',
                icon: resp.ok ? 'success' : 'error',
                timer: resp.ok ? 2000 : null,
                timerProgressBar: resp.ok
            }).then((result) => {
                if (resp.ok) {
                    window.location.href = "{{ route('cuerpos-correo.index') }}";
                }
            });
        }
    </script>
</x-app-layout>
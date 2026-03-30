@props(['align' => 'right'])

<div
    x-data="{
        open: false,
        tipoId: '',
        elementos: [],
        loadingElementos: false,
        submitting: false,
        errors: {},
        form: {
            titulo: '',
            elemento_id: '',
            justificacion: ''
        },
        async cargarElementos() {
            this.form.elemento_id = '';
            this.elementos = [];
            if (!this.tipoId) return;
            this.loadingElementos = true;
            const res = await fetch(`/propuestas/elementos?tipo_id=${this.tipoId}`);
            this.elementos = await res.json();
            this.loadingElementos = false;
        },
        async enviar() {
            this.errors = {};
            this.submitting = true;
            const res = await fetch('/propuestas/mejora', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify(this.form)
            });
            const data = await res.json();
            this.submitting = false;
            if (res.ok && data.success) {
                this.open = false;
                this.form = { titulo: '', elemento_id: '', justificacion: '' };
                this.tipoId = '';
                this.elementos = [];
                Swal.fire({
                    icon: 'success',
                    title: '¡Propuesta enviada!',
                    text: data.message,
                    confirmButtonColor: '#8b5cf6',
                    confirmButtonText: 'Aceptar'
                });
            } else if (res.status === 422) {
                this.errors = data.errors ?? {};
            }
        }
    }"
    @keydown.escape.window="open = false"
    class="relative">

    {{-- BOTÓN: campana --}}
    <button
        @click="open = true"
        class="relative inline-flex items-center justify-center w-9 h-9 rounded-full
               text-gray-600 dark:text-gray-300
               hover:bg-gray-100 dark:hover:bg-gray-700
               focus:outline-none focus:ring-2 focus:ring-purple-500"
        title="Propuesta de mejora">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span class="absolute top-1 right-1 w-2 h-2 bg-purple-500 rounded-full"></span>
    </button>

    {{-- OVERLAY --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm"
        @click="open = false"
        x-cloak>
    </div>

    {{-- MODAL --}}
    <div x-show="open" class="fixed inset-0 z-[70] flex items-center justify-center px-4" x-cloak>
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            @click.stop
            class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

            {{-- HEADER --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-tight">Propuesta de mejora</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Será revisada antes de generar un control de cambio</p>
                    </div>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            {{-- BODY --}}
            <div class="px-6 py-5 space-y-4 max-h-[65vh] overflow-y-auto">

                {{-- Título --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Título <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="form.titulo"
                        placeholder="Ej. Actualización de procedimiento de compras"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" />
                    <template x-if="errors.titulo">
                        <p class="text-xs text-red-500 mt-1" x-text="errors.titulo[0]"></p>
                    </template>
                </div>

                {{-- Tipo de Elemento (solo filtro, no se guarda por separado) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Tipo de elemento <span class="text-red-500">*</span>
                    </label>
                    <select x-model="tipoId" @change="cargarElementos()"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                        <option value="">— Selecciona un tipo —</option>
                        @foreach($tiposElemento as $tipo)
                            <option value="{{ $tipo->id_tipo_elemento }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nombre del Elemento --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Elemento <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select x-model="form.elemento_id" :disabled="!tipoId || loadingElementos"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value=""
                                x-text="loadingElementos ? 'Cargando...' : (tipoId ? '— Selecciona un elemento —' : '— Primero elige un tipo —')">
                            </option>
                            <template x-for="el in elementos" :key="el.id">
                                <option :value="el.id" x-text="el.nombre"></option>
                            </template>
                        </select>
                        <template x-if="loadingElementos">
                            <div class="absolute right-3 top-2.5">
                                <svg class="animate-spin w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                    <template x-if="errors.elemento_id">
                        <p class="text-xs text-red-500 mt-1" x-text="errors.elemento_id[0]"></p>
                    </template>
                </div>

                {{-- Justificación --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Justificación <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="form.justificacion" rows="3"
                        placeholder="¿Por qué es necesario este cambio?"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 transition resize-none"></textarea>
                    <template x-if="errors.justificacion">
                        <p class="text-xs text-red-500 mt-1" x-text="errors.justificacion[0]"></p>
                    </template>
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button @click="open = false"
                    class="px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>
                <button @click="enviar()" :disabled="submitting"
                    class="px-5 py-2 rounded-lg text-sm bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                    <template x-if="submitting">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                    </template>
                    <span x-text="submitting ? 'Enviando...' : 'Enviar propuesta'"></span>
                </button>
            </div>

        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endpush
@endonce

    <x-app-layout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Editar Control de Cambios
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Modificación del registro asociado al elemento
                </p>
            </div>

            <form action="{{ route('control-cambios.update', $cambios->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                    <aside class="lg:col-span-4 lg:sticky lg:top-24 self-start">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">

                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Datos del elemento
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Información de referencia
                                </p>
                            </div>

                            <div class="p-6 grid grid-cols-1 gap-4 text-sm grid grid-cols-2 gap-4">

                                <div>
                                    <label class="text-xs text-gray-500">Nombre Elemento</label>
                                    <div class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $cambios->elemento->nombre_elemento ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Folio Elemento</label>
                                    <div class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700 break-words">
                                        {{ $cambios->elemento->folio_elemento ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Estatus</label>
                                    <div class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $cambios->elemento->status ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Tipo de elemento</label>
                                    <div class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $cambios->elemento->tipoElemento->nombre ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Tipo de proceso</label>
                                    <div class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $cambios->elemento->tipoProceso->nombre ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-500">Responsable Elemento</label>
                                    <div class="mt-1 p-2 rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $cambios->elemento->puestoResponsable->nombre ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    @php
                    $folio = $cambios->FolioCambio ?? '';

                    preg_match('/^([A-Z]+)(\d+)/', $folio, $matches);

                    $abreviatura = $matches[1] ?? '—';
                    $numero = $matches[2] ?? 0;

                    $anioCorto = intval(substr($numero, 0, 2));
                    $anioBase = ($anioCorto * 1000);

                    $consecutivo = intval(substr($numero, -3));

                    $sumaAnio = $anioBase + $consecutivo;
                    @endphp

                    <section class="lg:col-span-8">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700">

                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Folio de Cambio
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Desglose automático del folio asignado
                                </p>
                            </div>

                            <div class="p-6 space-y-6 text-sm">

                                <div>
                                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">
                                        Folio de cambio
                                    </label>
                                    <div
                                        class="p-4 rounded-lg border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700
                           text-gray-900 dark:text-gray-100
                           font-semibold text-center text-base">
                                        {{ $folio }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                                    <div>
                                        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">
                                            Abreviatura
                                        </label>
                                        <div
                                            class="p-3 h-12 flex items-center justify-center
                               rounded-lg border border-gray-300 dark:border-gray-600
                               bg-gray-50 dark:bg-gray-700
                               text-gray-900 dark:text-gray-100">
                                            {{ $abreviatura }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">
                                            Año base + 000
                                        </label>
                                        <div
                                            class="p-3 h-12 flex items-center justify-center
                               rounded-lg border border-gray-300 dark:border-gray-600
                               bg-gray-50 dark:bg-gray-700
                               text-gray-900 dark:text-gray-100">
                                            {{ $anioBase }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">
                                            Consecutivo
                                        </label>
                                        <div
                                            class="p-3 h-12 flex items-center justify-center
                               rounded-lg border border-gray-300 dark:border-gray-600
                               bg-gray-50 dark:bg-gray-700
                               text-gray-900 dark:text-gray-100">
                                            {{ $consecutivo }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">
                                            Suma año total
                                        </label>
                                        <div
                                            class="p-3 h-12 flex items-center justify-center
                               rounded-lg border border-gray-300 dark:border-gray-600
                               bg-gray-100 dark:bg-gray-600
                               text-gray-900 dark:text-gray-100 font-semibold">
                                            {{ $sumaAnio }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="lg:col-span-8 space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Información del cambio
                                </h2>
                            </div>

                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">

                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        Naturaleza
                                    </label>
                                    <select name="Naturaleza"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="" disabled
                                            @selected(empty($cambios->Naturaleza))>
                                            Selecciona una naturaleza
                                        </option>

                                        @foreach([
                                        'Propuesta de Mejora',
                                        'Auditoria Interna',
                                        'Auditoria Externa',
                                        'Revision Programada del SGC',
                                        'Por Indicacion',
                                        'Actualizacion del Elemento'
                                        ] as $n)
                                        <option value="{{ $n }}" @selected($cambios->Naturaleza === $n)>
                                            {{ $n }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        Afectación
                                    </label>
                                    <select name="Afectacion"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="" disabled
                                            @selected(empty($cambios->Afectacion))>
                                            Selecciona una afectación
                                        </option>

                                        @foreach(['Nuevo','Mejora','Eliminado'] as $a)
                                        <option value="{{ $a }}" @selected($cambios->Afectacion === $a)>
                                            {{ $a }}
                                        </option>
                                        @endforeach
                                    </select>

                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        Prioridad
                                    </label>
                                    <select name="Prioridad"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="" disabled
                                            @selected(is_null($cambios->Prioridad))>
                                            Selecciona una prioridad
                                        </option>

                                        <option value="1" @selected($cambios->Prioridad == 1)>1</option>
                                        <option value="2" @selected($cambios->Prioridad == 2)>2</option>
                                        <option value="3" @selected($cambios->Prioridad == 3)>3</option>
                                        <option value="4" @selected($cambios->Prioridad == 4)>4</option>
                                    </select>

                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Detalles del cambio
                                </h2>
                            </div>

                            <div class="p-6 space-y-5">

                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        Descripción
                                    </label>
                                    <textarea name="Descripcion" rows="3"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ $cambios->Descripcion }}</textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                            Estado del cambio
                                        </label>
                                        <textarea name="DetalleCambio" rows="4"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ $cambios->DetalleStatus }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                            Redacción del cambio
                                        </label>
                                        <textarea name="RedaccionCambio" rows="4"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ $cambios->RedaccionCambio }}</textarea>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                            Seguimiento
                                        </label>
                                        <textarea name="Seguimiento" rows="3"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ $cambios->Seguimiento }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">
                                            Historial
                                        </label>
                                        <textarea name="HistorialStatus" rows="3"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ $cambios->HistorialStatus }}</textarea>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('control-cambios.show', $cambios->id) }}"
                                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Cancelar
                            </a>

                            <button type="submit"
                                class="px-5 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700">
                                Guardar cambios
                            </button>
                        </div>

                    </section>
                </div>
            </form>
        </div>
    </x-app-layout>
@props(['value' => 0])

<div data-campo id="ubicacion-eje-y-wrapper">
    <label
        for="ubicacion_eje_y"
        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Ubicación en Eje Y
    </label>
    <select
        name="ubicacion_eje_y"
        id="ubicacion_eje_y"
        data-initial-value="{{ old('ubicacion_eje_y', $value) }}"
        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </select>
    <input type="hidden" id="ubicacion_eje_y_fallback" value="0" disabled>
    <p id="ubicacion_eje_y_help" class="mt-1 text-xs text-gray-500 dark:text-gray-400"></p>
    @error('ubicacion_eje_y')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
        (function () {
            var OPCIONES = {
                industrial: [
                    { value: '0', label: 'Ambas filas (compartido)' },
                    { value: '1', label: 'Concretos (CON)' },
                    { value: '2', label: 'Agregados (AG)' },
                ],
                apoyo: [
                    { value: '0', label: 'Fila superior' },
                    { value: '2', label: 'Fila inferior' },
                ],
            };

            var AYUDAS = {
                industrial: 'División Industrial: compartido ocupa ambas filas; CON y AG van en filas separadas del mapa.',
                apoyo: 'Procesos de apoyo: el mapa tiene dos filas por sección (superior e inferior).',
            };

            window.actualizarUbicacionEjeY = function () {
                var $tipo = window.jQuery ? jQuery('#tipo_proceso_id') : null;
                var $select = document.getElementById('ubicacion_eje_y');
                var $wrapper = document.getElementById('ubicacion-eje-y-wrapper');
                var $help = document.getElementById('ubicacion_eje_y_help');
                var $fallback = document.getElementById('ubicacion_eje_y_fallback');

                if (!$select || !$wrapper || !$tipo || !$tipo.length) return;

                var selected = $tipo.find('option:selected');
                var mode = selected.data('mapa-y') || 'none';
                var currentVal = String($select.value || $select.dataset.initialValue || '0');

                if (mode === 'none') {
                    $wrapper.classList.add('hidden');
                    $select.disabled = true;
                    $select.removeAttribute('name');
                    if ($fallback) {
                        $fallback.disabled = false;
                        $fallback.setAttribute('name', 'ubicacion_eje_y');
                        $fallback.value = '0';
                    }
                    if ($help) $help.textContent = '';
                    return;
                }

                if (mode !== 'none') {
                    $wrapper.classList.remove('hidden');
                }

                $select.disabled = false;
                $select.setAttribute('name', 'ubicacion_eje_y');
                if ($fallback) {
                    $fallback.disabled = true;
                    $fallback.removeAttribute('name');
                }

                var options = OPCIONES[mode] || [];
                $select.innerHTML = '';
                options.forEach(function (opt) {
                    var option = document.createElement('option');
                    option.value = opt.value;
                    option.textContent = opt.label;
                    $select.appendChild(option);
                });

                var validValues = options.map(function (o) { return o.value; });
                var newVal = validValues.indexOf(currentVal) !== -1 ? currentVal : '0';
                if (mode === 'apoyo' && currentVal === '1') {
                    newVal = '0';
                }
                $select.value = newVal;

                if ($help) {
                    $help.textContent = AYUDAS[mode] || '';
                }
            };

            function bindUbicacionEjeY() {
                if (!window.jQuery) return;
                var $tipo = jQuery('#tipo_proceso_id');
                $tipo.off('change.ubicacionEjeY select2:select.ubicacionEjeY')
                    .on('change.ubicacionEjeY select2:select.ubicacionEjeY', window.actualizarUbicacionEjeY);
                window.actualizarUbicacionEjeY();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindUbicacionEjeY);
            } else {
                bindUbicacionEjeY();
            }
        })();
    </script>
    @endpush
@endonce

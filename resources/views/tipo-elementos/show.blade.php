<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalles del Tipo de Elemento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                            Tipo de Elemento: {{ $tipoElemento->nombre }}
                        </h1>
                        <div class="flex space-x-3">
                            <a href="{{ route('tipo-elementos.edit', $tipoElemento->id_tipo_elemento) }}" 
                               class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Editar
                            </a>
                            <a href="{{ route('tipo-elementos.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Volver a la Lista
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 p-6 lg:p-8 space-y-6">
                    
                    <!-- Información básica -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Información del Tipo de Elemento
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre del Tipo
                                </label>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $tipoElemento->nombre }}
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción
                                </label>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $tipoElemento->descripcion ?? 'Sin descripción' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Campos requeridos -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Campos Requeridos para Elementos
                            </h3>
                            <button onclick="toggleCamposRequeridos()" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                <span id="toggle-text">Ver Campos</span>
                            </button>
                        </div>
                        
                        <div id="campos-requeridos-section" class="hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="campos-container">
                                <!-- Los campos se cargarán dinámicamente -->
                            </div>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span id="contador-campos">0</span> campos marcados como requeridos
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="editarCamposRequeridos()" 
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        Editar Campos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Elementos asociados -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Elementos Asociados
                        </h3>
                        
                        @if($tipoElemento->elementos->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Nombre del Elemento
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Tipo de Proceso
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Unidad de Negocio
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($tipoElemento->elementos as $elemento)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $elemento->nombre_elemento }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->tipoProceso->nombre ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $elemento->unidadNegocio->nombre ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="{{ route('elementos.show', $elemento->id_elemento) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                        Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                No hay elementos asociados a este tipo de elemento.
                            </p>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCamposRequeridos() {
            const section = document.getElementById('campos-requeridos-section');
            const toggleText = document.getElementById('toggle-text');
            
            if (section.classList.contains('hidden')) {
                section.classList.remove('hidden');
                toggleText.textContent = 'Ocultar Campos';
                cargarCamposRequeridos();
            } else {
                section.classList.add('hidden');
                toggleText.textContent = 'Ver Campos';
            }
        }

        async function cargarCamposRequeridos() {
            try {
                const response = await fetch(`/tipo-elementos/{{ $tipoElemento->id_tipo_elemento }}/campos-requeridos`);
                if (response.ok) {
                    const campos = await response.json();
                    mostrarCamposRequeridos(campos);
                } else {
                    mostrarCamposRequeridos([]);
                }
            } catch (error) {
                console.error('Error al cargar campos requeridos:', error);
                mostrarCamposRequeridos([]);
            }
        }

        function mostrarCamposRequeridos(campos) {
            const container = document.getElementById('campos-container');
            const contador = document.getElementById('contador-campos');
            
            if (campos.length === 0) {
                container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-4 col-span-full">No hay campos requeridos configurados para este tipo de elemento.</p>';
                contador.textContent = '0';
                return;
            }

            const camposDisponibles = {
                'nombre_elemento': 'Nombre del Elemento',
                'tipo_proceso_id': 'Tipo de Proceso',
                'unidad_negocio_id': 'Unidad de Negocio',
                'ubicacion_eje_x': 'Ubicación Eje X',
                'control': 'Control',
                'folio_elemento': 'Folio del Elemento',
                'version_elemento': 'Versión del Elemento',
                'fecha_elemento': 'Fecha del Elemento',
                'periodo_revision': 'Período de Revisión',
                'puesto_responsable_id': 'Puesto Responsable',
                'puestos_relacionados': 'Puestos Relacionados',
                'es_formato': 'Es Formato',
                'archivo_formato': 'Archivo de Formato',
                'puesto_ejecutor_id': 'Puesto Ejecutor',
                'puesto_resguardo_id': 'Puesto de Resguardo',
                'medio_soporte': 'Medio de Soporte',
                'ubicacion_resguardo': 'Ubicación de Resguardo',
                'periodo_resguardo': 'Período de Resguardo',
                'elemento_padre_id': 'Elemento Padre',
                'elemento_relacionado_id': 'Elemento Relacionado',
                'correo_implementacion': 'Correo de Implementación',
                'correo_agradecimiento': 'Correo de Agradecimiento'
            };

            let contadorRequeridos = 0;
            let html = '';

            Object.entries(camposDisponibles).forEach(([campo, label]) => {
                const campoConfig = campos.find(c => c.campo_nombre === campo);
                const esRequerido = campoConfig ? campoConfig.es_requerido : false;
                
                if (esRequerido) {
                    contadorRequeridos++;
                }

                html += `
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full ${esRequerido ? 'bg-green-500' : 'bg-gray-300'}"></div>
                        <span class="text-sm font-medium ${esRequerido ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'}">
                            ${label}
                        </span>
                    </div>
                `;
            });

            container.innerHTML = html;
            contador.textContent = contadorRequeridos;
        }

        function editarCamposRequeridos() {
            window.location.href = "{{ route('tipo-elementos.edit', $tipoElemento->id_tipo_elemento) }}";
        }
    </script>
</x-app-layout>

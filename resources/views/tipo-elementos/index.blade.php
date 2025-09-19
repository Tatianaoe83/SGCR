<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Tipo de Elementos</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('tipo-elementos.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Nuevo Tipo de Elemento</span>
                </a>
            </div>

        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- DataTable Container -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 table-container">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Lista de Tipo de Elementos</h2>
            </header>
            <div class="p-3">
                <div class="overflow-x-auto">
                    <table id="tipoElementosTable" class="table-auto w-full dataTable">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Nombre
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Descripción
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Elementos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Campos Requeridos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($tiposElemento as $tipo)
                            <tr data-tipo-id="{{ $tipo->id_tipo_elemento }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $tipo->nombre }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ Str::limit($tipo->descripcion, 100) ?? 'Sin descripción' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $tipo->elementos_count }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col space-y-3">
                                        <!-- Badge de contador -->
                                        <div class="flex items-center justify-center">
                                            <span class="inline-flex items-center rounded-full bg-gradient-to-r from-amber-100 to-yellow-100 px-3 py-1.5 text-sm font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:from-amber-900/30 dark:to-yellow-900/30 dark:text-amber-300 dark:ring-amber-800/50">
                                                <svg class="w-4 h-4 mr-1.5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                </svg>
                                                <span id="contador-campos-{{ $tipo->id_tipo_elemento }}" class="font-bold">
                                                    {{ $tipo->camposRequeridos->count() ?? 0 }}
                                                </span>
                                                <span class="ml-1 text-xs">campos requeridos</span>
                                            </span>
                                        </div>

                                        <!-- Botón para gestionar campos -->
                                        <!--<div class="flex justify-center">
                                                <button 
                                                    onclick="toggleGestionCampos({{ $tipo->id_tipo_elemento }})"
                                                    class="inline-flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 hover:text-blue-800 focus:ring-4 focus:ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-800/40 dark:focus:ring-blue-800/30 transition-all duration-200">
                                                    <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                    Gestionar Campos
                                                </button>
                                            </div>
                                            
                                         
                                            @if($tipo->camposRequeridos->count() > 0)
                                                <div class="flex justify-center">
                                                    <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400">
                                                        <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        Configurado
                                                    </span>
                                                </div>
                                            @else
                                                <div class="flex justify-center">
                                                    <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                        <svg class="w-3 h-3 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        Sin configurar
                                                    </span>
                                                </div>
                                            @endif
                                        </div>-->
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('tipo-elementos.show', $tipo->id_tipo_elemento) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM1.5 8c0-3.6 2.9-6.5 6.5-6.5S14.5 4.4 14.5 8 11.6 14.5 8 14.5 1.5 11.6 1.5 8zM8 4.5c-1.9 0-3.5 1.6-3.5 3.5S6.1 11.5 8 11.5s3.5-1.6 3.5-3.5S9.9 4.5 8 4.5zM8 9.5c-.8 0-1.5-.7-1.5-1.5S7.2 6.5 8 6.5s1.5.7 1.5 1.5S8.8 9.5 8 9.5z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('tipo-elementos.edit', $tipo->id_tipo_elemento) }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                <path d="M11.7.3c-.4-.4-1-.4-1.4 0l-10 10c-.2.2-.3.4-.3.7v4c0 .6.4 1 1 1h4c.3 0 .5-.1.7-.3l10-10c.4-.4.4-1 0-1.4l-4-4zM12.6 9H7.4l6.2-6.2L12.6 9z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('tipo-elementos.destroy', $tipo->id_tipo_elemento) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este tipo de elemento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn bg-rose-500 hover:bg-rose-600 text-white">
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                                                    <path d="M6.602 11l1.497 1.497-1.497 1.497L5.105 12.497 3.608 11l1.497-1.497L5.105 8.006 6.602 6.51l1.497 1.497L9.596 6.51l1.497 1.497L10.099 8.006 11.596 9.503L10.099 11l-1.497-1.497L6.602 11z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DataTable Component -->
        <x-datatable
            tableId="tipoElementosTable"
            :orderColumn="0"
            orderDirection="asc"
            :pageLength="25" />

        <!-- Modal para gestionar campos requeridos -->
        <div id="modalGestionCampos" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Gestionar Campos Requeridos
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400" id="modal-description">
                                        Selecciona los campos que serán requeridos para este tipo de elemento.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido del modal -->
                        <div class="mt-6" id="modal-content">
                            <!-- Los campos se cargarán dinámicamente aquí -->
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="btn-guardar-campos" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Guardar Cambios
                        </button>
                        <button type="button" onclick="cerrarModalGestionCampos()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript para gestionar campos requeridos -->
        <script>
            let tipoElementoActual = null;

            function toggleGestionCampos(tipoId) {
                tipoElementoActual = tipoId;
                abrirModalGestionCampos();
                cargarCamposRequeridos(tipoId);
            }

            function abrirModalGestionCampos() {
                document.getElementById('modalGestionCampos').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function cerrarModalGestionCampos() {
                document.getElementById('modalGestionCampos').classList.add('hidden');
                document.body.style.overflow = 'auto';
                tipoElementoActual = null;
            }

            async function cargarCamposRequeridos(tipoId) {
                try {
                    const response = await fetch(`/tipo-elementos/${tipoId}/campos-requeridos`);
                    const campos = response.ok ? await response.json() : [];

                    const modalContent = document.getElementById('modal-content');
                    const modalTitle = document.getElementById('modal-title');

                    // Obtener el nombre del tipo de elemento
                    const nombreTipo = document.querySelector(`tr[data-tipo-id="${tipoId}"] td:first-child`).textContent.trim();
                    modalTitle.textContent = `Gestionar Campos Requeridos - ${nombreTipo}`;

                    // Generar el HTML para los campos
                    const camposHTML = generarHTMLCampos(campos, tipoId);
                    modalContent.innerHTML = camposHTML;

                    // Marcar checkboxes según campos existentes
                    marcarCamposExistentes(campos);

                } catch (error) {
                    console.error('Error al cargar campos requeridos:', error);
                    document.getElementById('modal-content').innerHTML = '<p class="text-red-500">Error al cargar los campos requeridos.</p>';
                }
            }

            function generarHTMLCampos(campos, tipoId) {
                const camposDisponibles = @json($camposElementos);
                let html = `
                    <div class="mt-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Campos Disponibles</h4>
                            <div class="flex space-x-2">
                                <button type="button" onclick="marcarTodosCampos()" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200 focus:ring-4 focus:ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700 dark:hover:bg-green-800/40 dark:focus:ring-green-800/30 transition-all duration-200">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Marcar Todos
                                </button>
                                <button type="button" onclick="desmarcarTodosCampos()" class="inline-flex items-center px-3 py-2 text-sm font-medium text-yellow-700 bg-yellow-100 border border-yellow-300 rounded-md hover:bg-yellow-200 focus:ring-4 focus:ring-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-700 dark:hover:bg-yellow-800/40 dark:focus:ring-yellow-800/30 transition-all duration-200">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Desmarcar Todos
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                `;

                Object.entries(camposDisponibles).forEach(([campo, label]) => {
                    const campoExistente = campos.find(c => c.campo_nombre === campo);
                    const isChecked = campoExistente ? campoExistente.es_requerido : false;

                    html += `
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                            <input 
                                type="checkbox" 
                                id="campo_${tipoId}_${campo}"
                                name="campos_requeridos[${tipoId}][]"
                                value="${campo}"
                                ${isChecked ? 'checked' : ''}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                            >
                            <label 
                                for="campo_${tipoId}_${campo}"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer flex-1"
                            >
                                ${label}
                            </label>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;

                return html;
            }

            function marcarCamposExistentes(campos) {
                campos.forEach(campo => {
                    const checkbox = document.getElementById(`campo_${tipoElementoActual}_${campo.campo_nombre}`);
                    if (checkbox) {
                        checkbox.checked = campo.es_requerido;
                    }
                });
            }

            function marcarTodosCampos() {
                const checkboxes = document.querySelectorAll('#modal-content input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
            }

            function desmarcarTodosCampos() {
                const checkboxes = document.querySelectorAll('#modal-content input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }

            // Event listener para el botón guardar
            document.getElementById('btn-guardar-campos').addEventListener('click', async function() {
                if (!tipoElementoActual) return;

                await guardarCamposRequeridos(tipoElementoActual);
            });

            async function guardarCamposRequeridos(tipoId) {
                const checkboxes = document.querySelectorAll('#modal-content input[type="checkbox"]');
                const campos = [];

                checkboxes.forEach((checkbox, index) => {
                    const campoNombre = checkbox.value;
                    const campoLabel = checkbox.nextElementSibling.textContent.trim();

                    campos.push({
                        campo_nombre: campoNombre,
                        campo_label: campoLabel,
                        es_requerido: checkbox.checked,
                        es_obligatorio: false,
                        orden: index
                    });
                });

                try {
                    const response = await fetch(`/tipo-elementos/${tipoId}/campos-requeridos`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            campos: campos
                        })
                    });

                    if (response.ok) {
                        // Mostrar mensaje de éxito
                        mostrarNotificacion('Campos requeridos guardados exitosamente', 'success');

                        // Actualizar contador en la tabla
                        const contadorElement = document.getElementById(`contador-campos-${tipoId}`);
                        if (contadorElement) {
                            const camposRequeridos = campos.filter(c => c.es_requerido).length;
                            contadorElement.textContent = camposRequeridos;
                        }

                        // Cerrar modal
                        cerrarModalGestionCampos();

                        // Recargar la página para actualizar los indicadores de estado
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);

                    } else {
                        mostrarNotificacion('Error al guardar los campos requeridos', 'error');
                    }
                } catch (error) {
                    console.error('Error al guardar campos requeridos:', error);
                    mostrarNotificacion('Error al guardar los campos requeridos', 'error');
                }
            }

            function mostrarNotificacion(mensaje, tipo) {
                // Crear notificación
                const notificacion = document.createElement('div');
                notificacion.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
                    tipo === 'success' 
                        ? 'bg-green-500 text-white' 
                        : 'bg-red-500 text-white'
                }`;

                notificacion.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            ${tipo === 'success' 
                                ? '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />'
                                : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />'
                            }
                        </svg>
                        <span>${mensaje}</span>
                    </div>
                `;

                document.body.appendChild(notificacion);

                // Mostrar notificación
                setTimeout(() => {
                    notificacion.classList.remove('translate-x-full');
                }, 100);

                // Ocultar notificación después de 3 segundos
                setTimeout(() => {
                    notificacion.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(notificacion);
                    }, 300);
                }, 3000);
            }

            // Cerrar modal al hacer clic fuera de él
            document.getElementById('modalGestionCampos').addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalGestionCampos();
                }
            });

            // Cerrar modal con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    cerrarModalGestionCampos();
                }
            });
        </script>
    </div>
</x-app-layout>
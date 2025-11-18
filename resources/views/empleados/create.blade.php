<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Nuevo Empleado</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('empleados.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                        </svg>
                        <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Crear Nuevo Empleado</h2>
            </header>
            <div class="p-6">

                <form action="/empleados" method="POST" id="formCrearEmpleado">
                    @csrf

                    <div class="space-y-6">

                        <!-- Nombre -->
                        <div>
                            <label for="nombres" class="block text-sm font-medium mb-2">Nombre(s) del Empleado</label>
                            <input
                                id="nombres"
                                name="nombres"
                                type="text"
                                class="form-input w-full"
                                value="{{ old('nombres') }}"
                                placeholder="Ingrese el nombre del empleado"
                                required />
                            @error('nombres')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- apellido paterno -->
                        <div>
                            <label for="apellido_paterno" class="block text-sm font-medium mb-2">Apellido Paterno del Empleado</label>
                            <input
                                id="apellido_paterno"
                                name="apellido_paterno"
                                type="text"
                                class="form-input w-full"
                                value="{{ old('apellido_paterno') }}"
                                placeholder="Ingrese el apellido del empleado"
                                required />
                            @error('apellido_paterno')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- apellido materno -->

                        <div>
                            <label for="apellido_materno" class="block text-sm font-medium mb-2">Apellido Materno del Empleado</label>
                            <input
                                id="apellido_materno"
                                name="apellido_materno"
                                type="text"
                                class="form-input w-full"
                                value="{{ old('apellido_materno') }}"
                                placeholder="Ingrese el apellido materno del empleado"
                                required />
                            @error('apellido_materno')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- puesto de trabajo -->
                        <div>
                            <label for="puesto_trabajo_id" class="block text-sm font-medium mb-2">Puesto de Trabajo</label>
                            <select id="puesto_trabajo_id" name="puesto_trabajo_id" class="select2 form-input w-full" data-placeholder="Seleccione un puesto de trabajo" required>
                                <option value="">Seleccione un puesto de trabajo</option>
                                @foreach($puestosTrabajo as $puestoTrabajo)
                                <option value="{{ $puestoTrabajo->id_puesto_trabajo }}" {{ old('puesto_trabajo_id') == $puestoTrabajo->id_puesto_trabajo ? 'selected' : '' }}>
                                    {{ $puestoTrabajo->nombre }}
                                </option>
                                @endforeach
                            </select>
                            @error('puesto_trabajo_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Información del puesto seleccionado -->
                        <div id="puesto-info" class="hidden bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Información del Puesto Seleccionado</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">División</label>
                                    <p id="puesto-division" class="text-sm text-gray-800 dark:text-gray-200">-</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Unidad de Negocio</label>
                                    <p id="puesto-unidad" class="text-sm text-gray-800 dark:text-gray-200">-</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Área</label>
                                    <p id="puesto-area" class="text-sm text-gray-800 dark:text-gray-200">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- correo -->
                        <div>
                            <label for="correo" class="block text-sm font-medium mb-2">Correo del Empleado</label>
                            <input
                                id="correo"
                                name="correo"
                                type="email"
                                class="form-input w-full"
                                value="{{ old('correo') }}"
                                placeholder="Ingrese el correo del empleado" />
                            @error('correo')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- telefono -->
                        <div>
                            <label for="telefono" class="block text-sm font-medium mb-2">Teléfono del Empleado</label>
                            <input
                                id="telefono"
                                name="telefono"
                                type="tel"
                                class="form-input w-full"
                                value="{{ old('telefono') }}"
                                placeholder="Ingrese el teléfono del empleado" />
                            @error('telefono')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- fecha de ingreso -->
                        <div>
                            <label for="fecha_ingreso" class="block text-sm font-medium mb-2">Fecha de Ingreso</label>
                            <input
                                id="fecha_ingreso"
                                name="fecha_ingreso"
                                type="date"
                                class="form-input w-full"
                                value="{{ old('fecha_ingreso') }}"
                                required />
                            @error('fecha_ingreso')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- fecha de nacimiento -->
                        <div>
                            <label for="fecha_nacimiento" class="block text-sm font-medium mb-2">Fecha de Nacimiento</label>
                            <input
                                id="fecha_nacimiento"
                                name="fecha_nacimiento"
                                type="date"
                                class="form-input w-full"
                                value="{{ old('fecha_nacimiento') }}"
                                required />
                            @error('fecha_nacimiento')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('empleados.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                Cancelar
                            </a>
                            <button type="button" id="btnCrearEmpleado" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                Crear Empleado
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </div>

    <!-- Modal para preview del correo -->
    <div id="emailPreviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <!-- Header del modal -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Vista Previa del Correo de Credenciales</h3>
                    <button id="closeEmailModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del modal -->
                <div class="mb-4">
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Se enviará un correo con las credenciales de acceso
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                    <p>Contraseña generada: <span id="generatedPassword" class="font-mono font-bold"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview del correo -->
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                        <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300" id="emailSubject">Asunto del correo</h4>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800">
                            <div id="emailContent" class="prose dark:prose-invert max-w-none">
                                <!-- El contenido del correo se cargará aquí -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones del modal -->
                <div class="flex justify-end space-x-3">
                    <button id="cancelEmail" type="button" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                        Cancelar
                    </button>
                    <button id="confirmEmail" type="button" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Enviar Correo y Crear Empleado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const puestoSelect = document.getElementById('puesto_trabajo_id');
            const puestoInfo = document.getElementById('puesto-info');
            const puestoDivision = document.getElementById('puesto-division');
            const puestoUnidad = document.getElementById('puesto-unidad');
            const puestoArea = document.getElementById('puesto-area');
            const form = document.getElementById('formCrearEmpleado');
            const btnCrearEmpleado = document.getElementById('btnCrearEmpleado');

            // Verificar que el formulario esté configurado correctamente
            if (form) {
                console.log('Formulario encontrado:', form);
                console.log('Form action original:', form.action);

                // Asegurar que la URL sea correcta
                if (!form.action.includes('/empleados')) {
                    form.action = '/empleados';
                    console.log('Form action corregida a:', form.action);
                }
            } else {
                console.error('No se encontró el formulario con ID formCrearEmpleado');
            }

            // Cargar información inicial si hay un valor seleccionado
            if (puestoSelect && puestoSelect.value) {
                loadPuestoInfo(puestoSelect.value);
            }

            if (puestoSelect) {
                puestoSelect.addEventListener('change', function() {
                    if (this.value) {
                        loadPuestoInfo(this.value);
                    } else {
                        hidePuestoInfo();
                    }
                });
            }

            // Event listener para el botón de crear empleado
            if (btnCrearEmpleado) {
                btnCrearEmpleado.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Validar campos requeridos
                    const nombres = document.getElementById('nombres').value;
                    const apellidoPaterno = document.getElementById('apellido_paterno').value;
                    const apellidoMaterno = document.getElementById('apellido_materno').value;
                    const puestoId = document.getElementById('puesto_trabajo_id').value;
                    const fechaIngreso = document.getElementById('fecha_ingreso').value;
                    const fecheNacimiento = document.getElementById('fecha_nacimiento').value;

                    if (!nombres || !apellidoPaterno || !apellidoMaterno || !puestoId || !fechaIngreso || !fecheNacimiento) {
                        Swal.fire({
                            title: 'Campos Requeridos',
                            text: 'Por favor complete todos los campos obligatorios',
                            icon: 'warning',
                            confirmButtonText: 'Aceptar'
                        });
                        return;
                    }

                    // Obtener correo (puede estar vacío)
                    const correo = document.getElementById('correo').value;

                    // Si no hay correo, crear empleado directamente
                    if (!correo || correo.trim() === '') {
                        crearEmpleadoSinCorreo(nombres, apellidoPaterno, apellidoMaterno);
                    } else {
                        // Si hay correo, mostrar modal de credenciales
                        generarYCrearEmpleado(nombres, apellidoPaterno, apellidoMaterno, correo);
                    }
                });
            }

            // Función para crear empleado sin correo
            function crearEmpleadoSinCorreo(nombres, apellidoPaterno, apellidoMaterno) {
                // Mostrar confirmación
                Swal.fire({
                    title: 'Crear Empleado',
                    text: `¿Está seguro de crear el empleado ${nombres} ${apellidoPaterno} ${apellidoMaterno} sin correo electrónico?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Crear Empleado',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crear empleado directamente con AJAX sin enviar correo (no hay correo)
                        crearEmpleadoConAjax(false);
                    }
                });
            }

            // Función única para generar credenciales y mostrar modal
            function generarYCrearEmpleado(nombres, apellidoPaterno, apellidoMaterno, correo) {
                // Obtener datos del formulario
                const data = {
                    nombres: nombres,
                    apellido_paterno: apellidoPaterno,
                    apellido_materno: apellidoMaterno,
                    correo: correo,
                    puesto_trabajo_id: document.getElementById('puesto_trabajo_id').value,
                    fecha_ingreso: document.getElementById('fecha_ingreso').value || null,
                    telefono: document.getElementById('telefono').value || null,
                    fecha_nacimiento: document.getElementById('fecha_nacimiento').value || null
                };

                console.log('Datos a enviar:', data); // Debug

                // Mostrar loading
                Swal.fire({
                    title: 'Generando credenciales...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar petición para obtener credenciales
                fetch('/empleados/email-preview', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close(); // Cerrar loading

                        if (data.error) {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error al generar credenciales: ' + data.error,
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                            return;
                        }

                        // Mostrar modal único con credenciales
                        Swal.fire({
                            title: 'Credenciales que se enviarán al empleado',
                            html: `
                            <div class="text-left">
                                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                                    <h4 class="font-semibold text-blue-800 mb-3">Información del Usuario</h4>
                                    <div class="space-y-2">
                                        <p><strong>Nombre:</strong> ${nombres} ${apellidoPaterno} ${apellidoMaterno}</p>
                                        <p><strong>Correo/Usuario:</strong> ${correo}</p>
                                        <p><strong>Contraseña temporal:</strong> <code class="bg-gray-200 px-2 py-1 rounded font-mono">${data.contrasena}</code></p>
                                    </div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm text-yellow-800 font-medium mb-1">Información importante:</p>
                                            <ul class="text-sm text-yellow-700 space-y-1">
                                                <li>• Estas credenciales se enviarán por correo al empleado</li>
                                                <li>• Guarda esta información de forma segura</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `,
                            icon: 'info',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonColor: '#10b981',
                            denyButtonColor: '#3b82f6',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Crear Empleado y Enviar Correo',
                            denyButtonText: 'Crear Empleado sin Enviar Correo',
                            cancelButtonText: 'Cancelar',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            reverseButtons: true,
                            width: '550px'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Crear empleado y enviar correo
                                crearEmpleadoConAjax(true);
                            } else if (result.isDenied) {
                                // Crear empleado sin enviar correo
                                crearEmpleadoConAjax(false);
                            }
                        });
                    })
                    .catch(error => {
                        Swal.close(); // Cerrar loading
                        console.error('Error al generar credenciales:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al generar credenciales',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    });
            }

            // Función para crear empleado con AJAX
            function crearEmpleadoConAjax(enviarCorreo = true) {
                console.log('crearEmpleadoConAjax iniciado', enviarCorreo);
                console.log('Form action:', form.action);
                console.log('Current URL:', window.location.href);
                console.log('Form data:', new FormData(form));

                // Verificar si la URL del formulario es correcta
                const formAction = form.action;
                if (!formAction.includes('/empleados')) {
                    console.error('URL del formulario incorrecta:', formAction);
                    Swal.fire({
                        title: 'Error de Configuración',
                        text: 'La URL del formulario no es correcta. Por favor recarga la página.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }

                // Mostrar loading
                Swal.fire({
                    title: 'Creando empleado...',
                    text: 'Por favor espera mientras se procesa la información',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Obtener datos del formulario
                const formData = new FormData(form);

                // Agregar parámetro para indicar si se debe enviar correo
                formData.append('enviar_correo', enviarCorreo ? '1' : '0');

                // Enviar petición AJAX
                const url = form.action;
                console.log('Enviando petición a:', url);

                fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data;

                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            throw new Error(`El servidor devolvió HTML en lugar de JSON. Status: ${response.status}. Respuesta: ${text.substring(0,200)}`);
                        }

                        if (response.status === 422) {
                            const mensaje = data.message || 'Error de validación.';
                            Swal.fire({
                                title: 'Error de Validación',
                                text: mensaje,
                                icon: 'warning',
                                confirmButtonText: 'Aceptar'
                            });
                            throw new Error(mensaje);
                        }

                        if (!response.ok) {
                            const mensaje = data.message || `Error HTTP ${response.status}`;
                            Swal.fire({
                                title: 'Error del Servidor',
                                text: mensaje,
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                            throw new Error(mensaje);
                        }

                        return data;
                    })
                    .then(data => {
                        Swal.close();

                        if (data.success) {
                            const mensajeExito = data.message || (
                                enviarCorreo ?
                                'El empleado ha sido creado y se ha enviado el correo con las credenciales.' :
                                'El empleado ha sido creado exitosamente.'
                            );
                            Swal.fire({
                                title: '¡Empleado creado exitosamente!',
                                text: mensajeExito,
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                window.location.href = "{{ route('empleados.index') }}";
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Hubo un error al crear el empleado. Por favor intenta nuevamente.',
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error al crear empleado:', error);
                        Swal.fire({
                            title: 'Error',
                            text: error.message || 'Ha ocurrido un error inesperado.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    });
            }

            function loadPuestoInfo(puestoId) {
                fetch(`/empleados/puesto-trabajo/${puestoId}/details`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error:', data.error);
                            hidePuestoInfo();
                            return;
                        }

                        puestoDivision.textContent = data.division;
                        puestoUnidad.textContent = data.unidad_negocio;
                        puestoArea.textContent = data.area;
                        showPuestoInfo();
                    })
                    .catch(error => {
                        console.error('Error al cargar información del puesto:', error);
                        hidePuestoInfo();
                    });
            }

            function showPuestoInfo() {
                puestoInfo.classList.remove('hidden');
            }

            function hidePuestoInfo() {
                puestoInfo.classList.add('hidden');
                puestoDivision.textContent = '-';
                puestoUnidad.textContent = '-';
                puestoArea.textContent = '-';
            }

        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("input[required], select[required], textarea[required]").forEach(el => {
                const name = el.getAttribute("name");

                if (name === "correo" || name === "telefono") return;

                let label = el.closest("div")?.querySelector("label");
                if (label && !label.innerHTML.includes("*")) {
                    label.innerHTML += ' <span class="text-red-500">*</span>';
                }
            });
        });
    </script>

</x-app-layout>
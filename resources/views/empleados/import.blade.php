<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11 ">
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Importar Empleados</h1>
            </div>
            <div class="flex flex-wrap items-center space-x-2">
                
                    <a href="{{ route('empleados.index') }}" 
                       class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                       
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
                        </svg>
                        <span class="ml-2">Volver</span>
                    </span>
                </a>
                
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Instructions Card -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
            <div class="flex items-start space-x-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mt-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">Instrucciones de Importación</h3>
                    <ul class="text-blue-700 dark:text-blue-300 space-y-1 text-sm">
                        <li>• Descarga la plantilla de Excel para ver el formato correcto</li>
                        <li>• Completa los datos en la plantilla siguiendo el formato</li>
                        <li>• Asegúrate de que los puestos de trabajo existan en el sistema</li>
                        <li>• Los campos marcados con * son obligatorios</li>
                        <li>• El archivo debe ser en formato .xlsx o .xls</li>
                        <li>• Si se detectan cambios de puesto, se te pedirá confirmación</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
            <div class="p-6">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="space-y-6">
                        
                        <!-- File Upload -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="file">Archivo Excel</label>
                            <input id="file" class="form-input w-full" type="file" name="file" accept=".xlsx,.xls" required />
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Formatos permitidos: .xlsx, .xls
                            </p>
                            @error('file')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Template Download -->
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                            <h4 class="font-medium text-slate-800 dark:text-slate-200 mb-2">¿No tienes la plantilla?</h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                Descarga la plantilla de Excel para ver el formato correcto de los datos.
                            </p>
                            <a href="{{ route('empleados.template') }}" 
                               class="btn bg-blue-500 hover:bg-blue-600 text-white">
                                <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
                                </svg>
                                <span class="ml-2">Descargar Plantilla</span>
                            </a>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('empleados.index') }}" 
                               class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                                Cancelar
                            </a>
                            <button type="submit" id="submitBtn" class="btn bg-purple-600 hover:bg-purple-700 text-white">
                                <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
                                </svg>
                                <span class="ml-2">Verificar e Importar</span>
                            </button>
                        </div>
                        
                    </div>
                    
                </form>
            </div>
        </div>
        
    </div>

    <!-- Modal de Confirmación de Cambios de Puesto -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-slate-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Confirmar Cambios de Puesto
                    </h3>
                    <button onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Se han detectado los siguientes cambios de puesto en el archivo de importación:
                    </p>
                    
                    <div id="changesList" class="space-y-3 max-h-64 overflow-y-auto">
                        <!-- Los cambios se mostrarán aquí dinámicamente -->
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button onclick="closeConfirmModal()" 
                            class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                        Cancelar
                    </button>
                    <button onclick="confirmImport()" 
                            class="btn bg-green-600 hover:bg-green-700 text-white">
                        Confirmar e Importar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Carga -->
    <div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-slate-800">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
                    <svg class="animate-spin h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mt-4">
                    Procesando archivo...
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Verificando cambios de puesto...
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentFile = null;
        let hasChanges = false;

        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            handleImport();
        });

        function handleImport() {
            const fileInput = document.getElementById('file');
            const submitBtn = document.getElementById('submitBtn');

            console.log(fileInput.files[0].name);
            
            if (!fileInput.files[0]) {
                alert('Por favor selecciona un archivo');
                return;
            }

            currentFile = fileInput.files[0];
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="animate-spin w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="ml-2">Verificando...</span>';
            
            showLoadingModal();

            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("empleados.check-puesto-changes") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                hideLoadingModal();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16"><path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/></svg><span class="ml-2">Verificar e Importar</span>';
                
                if (data.success) {
                    if (data.has_changes) {
                        hasChanges = true;
                        showConfirmModal(data.changes);
                    } else {
                        // No hay cambios, proceder directamente
                        hasChanges = false;
                        proceedWithImport();
                    }
                } else {
                    console.log(data);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                hideLoadingModal();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16"><path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/></svg><span class="ml-2">Verificar e Importar</span>';
                console.error('Error:', error);
                alert('Error al procesar el archivo');
            });
        }

        function showConfirmModal(changes) {
            const changesList = document.getElementById('changesList');
            changesList.innerHTML = '';
            
            changes.forEach(change => {
                const changeItem = document.createElement('div');
                changeItem.className = 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3';
                changeItem.innerHTML = `
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="font-medium text-yellow-800 dark:text-yellow-200">${change.empleado}</p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                Puesto actual: <span class="font-medium">${change.puesto_actual}</span>
                            </p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                Nuevo puesto: <span class="font-medium">${change.puesto_nuevo}</span>
                            </p>
                        </div>
                    </div>
                `;
                changesList.appendChild(changeItem);
            });
            
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function showLoadingModal() {
            document.getElementById('loadingModal').classList.remove('hidden');
        }

        function hideLoadingModal() {
            document.getElementById('loadingModal').classList.add('hidden');
        }

        function confirmImport() {
            if (!currentFile) {
                alert('No hay archivo para importar');
                return;
            }

            closeConfirmModal();
            showLoadingModal();

            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("empleados.confirm-import") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                hideLoadingModal();
                if (data) {
                    // Si no hay redirección, mostrar mensaje
                    alert('Importación completada');
                    window.location.reload();
                }
            })
            .catch(error => {
                hideLoadingModal();
                console.error('Error:', error);
                alert('Error al importar los datos');
            });
        }

        function proceedWithImport() {
            if (!currentFile) {
                alert('No hay archivo para importar');
                return;
            }

            showLoadingModal();

            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("empleados.import") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                hideLoadingModal();
                if (data) {
                    // Si no hay redirección, mostrar mensaje
                    alert('Importación completada');
                    window.location.reload();
                }
            })
            .catch(error => {
                hideLoadingModal();
                console.error('Error:', error);
                alert('Error al importar los datos');
            });
        }
    </script>
</x-app-layout>

<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11 ">
            <div class="mb-4 sm:mb-0">
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Importar Puestos de Trabajo</h1>
            </div>
            <div class="flex flex-wrap items-center space-x-2">

                <a href="{{ route('elementos.index') }}"
                    class="btn border-slate-200 hover:border-slate-300 text-slate-600">

                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
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
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">Instrucciones de Importación</h3>
                    <ul class="text-blue-700 dark:text-blue-300 space-y-1 text-sm">
                        <li>• Descarga la plantilla de Excel para ver el formato correcto</li>
                        <li>• Completa los datos en la plantilla siguiendo el formato</li>
                        <li>• Asegúrate de que las nombres, unidades de negocio, tipo de elemento existan en el sistema</li>
                        <li>• Los campos marcados con * son obligatorios</li>
                        <li>• El archivo debe ser en formato .xlsx o .xls</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
            <div class="p-6">
                <form action="{{ route('elementos.import') }}" method="POST" enctype="multipart/form-data">
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
                            <a href="{{ route('elementos.template') }}"
                                class="btn bg-blue-500 hover:bg-blue-600 text-white">
                                <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                                </svg>
                                <span class="ml-2">Descargar Plantilla</span>
                            </a>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('elementos.index') }}"
                                class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-purple-600 hover:bg-purple-700 text-white">
                                <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                                </svg>
                                <span class="ml-2">Importar Datos</span>
                            </button>
                        </div>

                    </div>

                </form>
            </div>
        </div>

    </div>
</x-app-layout>
<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <!-- Breadcrumbs -->
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Dashboard</a>
                    <span class="mx-2">></span>
                    <a href="{{ route('divisions.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Divisiones</a>
                    <span class="mx-2">></span>
                    <span class="text-gray-400 dark:text-gray-500">Nueva División</span>
                </div>
                
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Nueva División</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('divisions.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M7.001 3h2v4h4v2h-4v4H7.001v-4H3V7h4V3z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Crear Nueva División</h2>
            </header>
            <div class="p-6">

                <form action="{{ route('divisions.store') }}" method="POST">
                    @csrf

                    <div class="space-y-6">

                        <!-- Unidad de Negocio -->
                        <div>
                            <label for="unidad_negocio_id" class="block text-sm font-medium mb-2">Unidad de Negocio</label>
                            <select id="unidad_negocio_id" name="unidad_negocio_id" class="form-input w-full" required>
                                <option value="">Seleccione una unidad de negocio</option>
                                @foreach($unidadesNegocio as $unidadNegocio)
                                    <option value="{{ $unidadNegocio->id }}" {{ old('unidad_negocio_id') == $unidadNegocio->id ? 'selected' : '' }}>
                                        {{ $unidadNegocio->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unidad_negocio_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Nombre -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium mb-2">Nombre de la División</label>
                            <input 
                                id="nombre" 
                                name="nombre" 
                                type="text" 
                                class="form-input w-full" 
                                value="{{ old('nombre') }}"
                                placeholder="Ingrese el nombre de la división"
                                required
                            />
                            @error('nombre')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('divisions.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                Crear División
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </div>
</x-app-layout> 
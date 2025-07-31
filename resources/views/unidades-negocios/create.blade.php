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
                    <a href="{{ route('unidades-negocios.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Unidades de Negocio</a>
                    <span class="mx-2">></span>
                    <span class="text-gray-400 dark:text-gray-500">Nueva Unidad de Negocio</span>
                </div>
                
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Nueva Unidad de Negocio</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('unidades-negocios.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
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
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Crear Nueva Unidad de Negocio</h2>
            </header>
            <div class="p-6">

                <form action="{{ route('unidades-negocios.store') }}" method="POST">
                    @csrf

                    <div class="space-y-6">

                        <!-- División -->
                        <div>
                            <label for="division_id" class="block text-sm font-medium mb-2">División</label>
                            <select 
                                id="division_id" 
                                name="division_id" 
                                class="form-input w-full" 
                                required
                            >
                                <option value="">Seleccione una división</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id_division }}" {{ old('division_id') == $division->id_division ? 'selected' : '' }}>
                                        {{ $division->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('division_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium mb-2">Nombre de la Unidad de Negocio</label>
                            <input 
                                id="nombre" 
                                name="nombre" 
                                type="text" 
                                class="form-input w-full" 
                                value="{{ old('nombre') }}"
                                placeholder="Ingrese el nombre de la unidad de negocio"
                                required
                            />
                            @error('nombre')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('unidades-negocios.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                Crear Unidad de Negocio
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </div>
</x-app-layout> 
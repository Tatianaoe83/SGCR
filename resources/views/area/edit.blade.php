<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                
                
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $area->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('area.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                    <span class="btn bg-red-500 hover:bg-red-600 text-white">
                        <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z"/>
                    </svg>
                    <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>

        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles Area</h2>
            </header>
            <div class="p-6">

                <form action="{{ route('area.update', $area->id_area) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Unidad de Negocio -->
                        <div>
                            <label for="unidad_negocio_id" class="block text-sm font-medium mb-2">Unidad de Negocio</label>
                            <select 
                                id="unidad_negocio_id" 
                                name="unidad_negocio_id" 
                                class="select2 form-input w-full" 
                                data-placeholder="Seleccione una unidad de negocio"
                                required
                            >
                                <option value="">Seleccione una unidad de negocio</option>
                                @foreach($unidadNegocio as $unidadNegocio)
                                    <option value="{{ $unidadNegocio->id_unidad_negocio }}" {{ old('unidad_negocio_id', $area->unidad_negocio_id) == $unidadNegocio->id_unidad_negocio ? 'selected' : '' }}>
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
                            <label for="nombre" class="block text-sm font-medium mb-2">Nombre Area</label>
                            <input 
                                id="nombre" 
                                name="nombre" 
                                type="text" 
                                class="form-input w-full" 
                                value="{{ old('nombre', $area->nombre) }}"
                                placeholder="Ingrese el nombre Area"
                                required
                            />
                            @error('nombre')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('area.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                Actualizar Area
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </div>
</x-app-layout> 
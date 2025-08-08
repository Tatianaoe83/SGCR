<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $puestoTrabajo->nombre }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('puestos-trabajo.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
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
            <div class="p-6">
                <form action="{{ route('puestos-trabajo.update', $puestoTrabajo->id_puesto_trabajo) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-6">
                        
                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="nombre">Nombre del Puesto</label>
                            <input id="nombre" class="form-input w-full" type="text" name="nombre" value="{{ old('nombre', $puestoTrabajo->nombre) }}" required />
                            @error('nombre')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- División -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="division_id">División</label>
                            <select id="division_id" class="form-select w-full" name="division_id" required>
                                <option value="">Seleccionar División</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id_division }}" {{ old('division_id', $puestoTrabajo->division_id) == $division->id_division ? 'selected' : '' }}>
                                        {{ $division->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('division_id')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Unidad de Negocio -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="unidad_negocio_id">Unidad de Negocio</label>
                            <select id="unidad_negocio_id" class="form-select w-full" name="unidad_negocio_id" required>
                                <option value="">Seleccionar Unidad de Negocio</option>
                                @foreach($unidadesNegocio as $unidad)
                                    <option value="{{ $unidad->id_unidad_negocio }}" {{ old('unidad_negocio_id', $puestoTrabajo->unidad_negocio_id) == $unidad->id_unidad_negocio ? 'selected' : '' }}>
                                        {{ $unidad->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unidad_negocio_id')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Área -->
                        <div>
                            <label class="block text-sm font-medium mb-2" for="area_id">Área</label>
                            <select id="area_id" class="form-select w-full" name="area_id" required>
                                <option value="">Seleccionar Área</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id_area }}" {{ old('area_id', $puestoTrabajo->area_id) == $area->id_area ? 'selected' : '' }}>
                                        {{ $area->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('area_id')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('puestos-trabajo.index') }}" 
                               class="btn border-slate-200 hover:border-slate-300 text-slate-600">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-purple-600 hover:bg-purple-700 text-white">
                                Actualizar Puesto de Trabajo
                            </button>
                        </div>
                        
                    </div>
                    
                </form>
            </div>
        </div>
        
    </div>
</x-app-layout>

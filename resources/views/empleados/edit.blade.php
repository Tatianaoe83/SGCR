<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
    
                <!-- Main Title -->
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $empleados->nombres }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('empleados.index') }}" class="btn border-slate-200 hover:border-slate-300 text-slate-600">
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
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Detalles del Empleado</h2>
            </header>
            <div class="p-6">

                <form action="{{ route('empleados.update', $empleados->id_empleado) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">

                           <!-- Nombre -->
                           <div>
                            <label for="nombres" class="block text-sm font-medium mb-2">Nombre(s) del Empleado</label>
                            <input 
                                id="nombres" 
                                name="nombres" 
                                type="text" 
                                class="form-input w-full" 
                                value="{{ $empleados->nombres }}"
                                placeholder="Ingrese el nombre del empleado"
                                required
                            />
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
                                value="{{ $empleados->apellido_paterno }}"
                                placeholder="Ingrese el apellido del empleado"
                                required
                            />
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
                                value="{{ $empleados->apellido_materno }}"
                                placeholder="Ingrese el apellido materno del empleado"
                                required
                            />
                            @error('apellido_materno')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- puesto de trabajo -->  
                        <div>
                            <label for="puesto_trabajo_id" class="block text-sm font-medium mb-2">Puesto de Trabajo</label>
                            <select id="puesto_trabajo_id" name="puesto_trabajo_id" class="form-input w-full" required>
                              <option value="">Seleccione un puesto de trabajo</option>
                              @foreach($puestosTrabajo as $puestoTrabajo)
                                <option value="{{ $puestoTrabajo->id_puesto_trabajo }}" {{ $empleados->puesto_trabajo_id == $puestoTrabajo->id_puesto_trabajo ? 'selected' : '' }}>
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
                                value="{{ $empleados->correo }}"
                                placeholder="Ingrese el correo del empleado"
                                required
                            />
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
                                value="{{ $empleados->telefono }}"
                                placeholder="Ingrese el teléfono del empleado"
                                required
                            />
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
                                value="{{ $empleados->fecha_ingreso }}"
                            />
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
                                value="{{ $empleados->fecha_nacimiento }}"
                            />
                            @error('fecha_nacimiento')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('empleados.index') }}" class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300">
                                Cancelar
                            </a>
                            <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                Actualizar Empleado
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const puestoSelect = document.getElementById('puesto_trabajo_id');
            const puestoInfo = document.getElementById('puesto-info');
            const puestoDivision = document.getElementById('puesto-division');
            const puestoUnidad = document.getElementById('puesto-unidad');
            const puestoArea = document.getElementById('puesto-area');

            // Cargar información inicial si hay un valor seleccionado
            if (puestoSelect.value) {
                loadPuestoInfo(puestoSelect.value);
            }

            puestoSelect.addEventListener('change', function() {
                if (this.value) {
                    loadPuestoInfo(this.value);
                } else {
                    hidePuestoInfo();
                }
            });

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
</x-app-layout> 
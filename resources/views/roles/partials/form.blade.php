@php
    $seleccionados = collect(old('permissions', $seleccionados ?? []))->map(fn($id) => (int) $id)->all();
    $grupos = $permissions->groupBy(fn($permission) => \Illuminate\Support\Str::before($permission->name, '.'))->sortKeys();
@endphp

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nombre del Rol
        </label>
        <input type="text" name="name" id="name" value="{{ old('name', $role->name ?? '') }}" required
            placeholder="ej: Coordinador de Calidad"
            class="form-input w-full @error('name') border-red-500 @enderror">
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    @if($permissions->count() > 0)
    <div x-data="{
            total: {{ $permissions->count() }},
            seleccionados: {{ count($seleccionados) }},
            recalcular() {
                this.seleccionados = this.$el.querySelectorAll('input[name=\'permissions[]\']:checked').length;
            },
            marcarTodos(valor) {
                this.$el.querySelectorAll('input[name=\'permissions[]\']').forEach(el => el.checked = valor);
                this.recalcular();
            },
            marcarGrupo(grupo, valor) {
                this.$el.querySelectorAll('input[data-grupo=\'' + grupo + '\']').forEach(el => el.checked = valor);
                this.recalcular();
            }
        }" @change="recalcular()">

        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div>
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permisos del Rol</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="seleccionados"></span> de <span x-text="total"></span> seleccionados
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary text-xs" @click="marcarTodos(true)">Seleccionar todo</button>
                <button type="button" class="btn-secondary text-xs" @click="marcarTodos(false)">Limpiar</button>
            </div>
        </div>

        <div class="space-y-3">
            @foreach($grupos as $grupo => $permisosGrupo)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                x-data="{ abierto: true }">
                <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/40">
                    <button type="button" class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-100"
                        @click="abierto = !abierto">
                        <svg class="w-4 h-4 transition-transform" :class="abierto ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        {{ ucfirst(str_replace(['-', '_'], ' ', $grupo)) }}
                        <span class="badge-status badge-neutral">{{ $permisosGrupo->count() }}</span>
                    </button>
                    <div class="flex items-center gap-2 text-xs">
                        <button type="button" class="text-blue-600 dark:text-blue-400 hover:underline"
                            @click="marcarGrupo('{{ $grupo }}', true)">Todos</button>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <button type="button" class="text-gray-500 dark:text-gray-400 hover:underline"
                            @click="marcarGrupo('{{ $grupo }}', false)">Ninguno</button>
                    </div>
                </div>
                <div x-show="abierto" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 p-4">
                        @foreach($permisosGrupo as $permission)
                        <label for="permission_{{ $permission->id }}"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer">
                            <input type="checkbox" name="permissions[]" id="permission_{{ $permission->id }}"
                                value="{{ $permission->id }}"
                                data-grupo="{{ $grupo }}"
                                @checked(in_array($permission->id, $seleccionados))
                                class="form-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @error('permissions')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    @else
    <div class="text-center py-8 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay permisos disponibles.</p>
        @can('permissions.create')
        <a href="{{ route('permissions.create') }}" class="btn-primary mt-3">Crear Permisos</a>
        @endcan
    </div>
    @endif

    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('roles.index') }}" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">{{ $textoBoton }}</button>
    </div>
</div>

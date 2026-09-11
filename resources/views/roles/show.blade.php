@php
    $grupos = $role->permissions->groupBy(fn($permission) => \Illuminate\Support\Str::before($permission->name, '.'))->sortKeys();
@endphp

<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 pt-3 pb-8 w-full max-w-5xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-5">
            <div class="flex items-center gap-3 mb-4 sm:mb-0">
                <a href="{{ route('roles.index') }}"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    title="Volver">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $role->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $role->permissions->count() }} permisos · {{ $role->users()->count() }} usuarios
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center space-x-2">
                @can('roles.edit')
                <a href="{{ route('roles.edit', $role) }}" class="btn-primary">Editar Rol</a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Información -->
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Información</h2>
                </header>
                <dl class="p-5 space-y-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Nombre</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $role->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Guard</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $role->guard_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Usuarios asignados</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $role->users()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Creado</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $role->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Última actualización</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $role->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Permisos -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Permisos asignados</h2>
                    <span class="badge-status badge-info">{{ $role->permissions->count() }}</span>
                </header>

                <div class="p-5">
                    @forelse($grupos as $grupo => $permisosGrupo)
                    <div class="mb-4 last:mb-0">
                        <h3 class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            {{ ucfirst(str_replace(['-', '_'], ' ', $grupo)) }}
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($permisosGrupo as $permission)
                                <span class="badge-status badge-info">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-10">
                        <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Este rol no tiene permisos asignados.</p>
                        @can('roles.edit')
                        <a href="{{ route('roles.edit', $role) }}" class="btn-primary mt-4">Asignar permisos</a>
                        @endcan
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('roles.index') }}" class="btn-secondary">Volver a la lista</a>
            @can('roles.delete')
            <form action="{{ route('roles.destroy', $role) }}" method="POST"
                data-confirm="El rol &quot;{{ $role->name }}&quot; se eliminará de forma permanente."
                data-confirm-title="¿Eliminar rol?"
                data-confirm-button="Sí, eliminar">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Eliminar Rol</button>
            </form>
            @endcan
        </div>
    </div>
</x-app-layout>

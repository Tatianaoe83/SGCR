<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-5xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="flex items-center gap-3 mb-4 sm:mb-0">
                <a href="{{ route('permissions.index') }}"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    title="Volver">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $permission->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Módulo {{ \Illuminate\Support\Str::before($permission->name, '.') }} · {{ $permission->roles->count() }} roles
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center space-x-2">
                @can('permissions.edit')
                <a href="{{ route('permissions.edit', $permission) }}" class="btn-primary">Editar Permiso</a>
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
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Módulo</dt>
                        <dd class="mt-1">
                            <span class="badge-status badge-neutral">
                                {{ \Illuminate\Support\Str::before($permission->name, '.') }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Guard</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->guard_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Creado</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Última actualización</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Roles -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Roles que usan este permiso</h2>
                    <span class="badge-status badge-info">{{ $permission->roles->count() }}</span>
                </header>

                <div class="p-5">
                    @forelse($permission->roles as $role)
                    <div class="flex items-center justify-between px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 mb-2 last:mb-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</span>
                        @can('roles.view')
                        <a href="{{ route('roles.show', $role) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            Ver rol
                        </a>
                        @endcan
                    </div>
                    @empty
                    <div class="text-center py-10">
                        <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Este permiso no está asignado a ningún rol.</p>
                        @can('roles.view')
                        <a href="{{ route('roles.index') }}" class="btn-primary mt-4">Ver Roles</a>
                        @endcan
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('permissions.index') }}" class="btn-secondary">Volver a la lista</a>
            @can('permissions.delete')
            <form action="{{ route('permissions.destroy', $permission) }}" method="POST"
                data-confirm="El permiso &quot;{{ $permission->name }}&quot; se eliminará de forma permanente."
                data-confirm-title="¿Eliminar permiso?"
                data-confirm-button="Sí, eliminar">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Eliminar Permiso
                </button>
            </form>
            @endcan
        </div>
    </div>
</x-app-layout>

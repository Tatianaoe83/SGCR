<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalles del Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <a href="{{ route('users.index') }}" class="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                            </a>
                            <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                                Usuario: {{ $user->name }}
                            </h1>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Editar Usuario
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                    <div class="col-span-1 md:col-span-2">
                        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <!-- Información del Usuario -->
                                    <div>
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                                            Información del Usuario
                                        </h3>
                                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-1">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->email }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</dt>
                                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Última Actualización</dt>
                                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->updated_at->format('d/m/Y H:i') }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Verificación de Email</dt>
                                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                    @if($user->email_verified_at)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                            Verificado
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            No verificado
                                                        </span>
                                                    @endif
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <!-- Roles del Usuario -->
                                    <div>
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                                            Roles Asignados
                                        </h3>
                                        @if($user->roles->count() > 0)
                                            <div class="space-y-2">
                                                @foreach($user->roles as $role)
                                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{ $role->name }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                            Asignado
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-8">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Sin roles</h3>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Este usuario no tiene roles asignados.</p>
                                                <div class="mt-6">
                                                    <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                        Asignar Roles
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Permisos del Usuario -->
                                <div class="mt-8">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                                        Permisos del Usuario
                                    </h3>
                                    @php
                                        $userPermissions = $user->getAllPermissions();
                                    @endphp
                                    
                                    @if($userPermissions->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach($userPermissions as $permission)
                                                <div class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                                        {{ $permission->name }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <p class="text-gray-500 dark:text-gray-400">Este usuario no tiene permisos directos asignados.</p>
                                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Los permisos se obtienen a través de los roles asignados.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Acciones -->
                                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between items-center">
                                        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Volver a la Lista
                                        </a>
                                        <div class="flex space-x-3">
                                            <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                Editar Usuario
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Crear Nuevo Permiso') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <a href="{{ route('permissions.index') }}" class="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <h1 class="text-2xl font-medium text-gray-900 dark:text-gray-100">
                            Crear Nuevo Permiso
                        </h1>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                    <div class="col-span-1 md:col-span-2">
                        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                            <form action="{{ route('permissions.store') }}" method="POST" class="p-6">
                                @csrf
                                
                                <div class="space-y-6">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Nombre del Permiso
                                        </label>
                                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                               placeholder="ej: users.create, roles.edit, etc."
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Usa un formato descriptivo como "modulo.accion" (ej: users.create, roles.edit)
                                        </p>
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="guard_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Guard del Permiso
                                        </label>
                                        <select name="guard_name" id="guard_name" required
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="web" {{ old('guard_name') == 'web' ? 'selected' : '' }}>Web (web)</option>
                                            <option value="api" {{ old('guard_name') == 'api' ? 'selected' : '' }}>API (api)</option>
                                        </select>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            El guard determina cómo se autentica el usuario
                                        </p>
                                        @error('guard_name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <a href="{{ route('permissions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Cancelar
                                        </a>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Crear Permiso
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

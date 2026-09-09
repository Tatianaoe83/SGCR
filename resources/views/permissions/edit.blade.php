<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-3xl mx-auto">

        <!-- Page header -->
        <div class="mb-8 mt-11">
            <div class="flex items-center gap-3">
                <a href="{{ route('permissions.index') }}"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    title="Volver">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Editar Permiso</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $permission->name }} · {{ $permission->roles->count() }} roles lo usan
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700">
            <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Datos del Permiso</h2>
            </header>

            <form action="{{ route('permissions.update', $permission) }}" method="POST" class="p-5">
                @csrf
                @method('PUT')
                @include('permissions.partials.form', ['textoBoton' => 'Actualizar Permiso'])
            </form>
        </div>
    </div>
</x-app-layout>

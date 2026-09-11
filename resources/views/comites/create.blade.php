<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 pt-3 pb-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-5">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Nuevo Comité</h1>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-wrap items-center space-x-2">
                <a href="{{ route('comites.index') }}" class="btn-secondary">
                    <svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 11.4L4.6 7 6 5.6l3 3 3-3L11.4 7 9 9.4V11.4z" />
                    </svg>
                    <span class="hidden xs:block ml-2">Volver</span>
                </a>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
            <div class="p-6">
                <form action="{{ route('comites.store') }}" method="POST">
                    @csrf
                    @include('comites.partials.form', ['comite' => null, 'submitLabel' => 'Crear Comité'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

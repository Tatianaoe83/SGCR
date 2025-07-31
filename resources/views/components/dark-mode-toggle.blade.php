@props(['class' => ''])

<button 
    type="button" 
    data-dark-mode-toggle
    class="btn bg-slate-150 hover:bg-slate-200 text-slate-600 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300 {{ $class }}"
    title="Cambiar modo oscuro"
>
    <span data-dark-mode-icon>
        <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
            <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM8 2c3.3 0 6 2.7 6 6s-2.7 6-6 6V2z"/>
        </svg>
    </span>
    <span data-dark-mode-text class="hidden sm:block ml-2">Modo Oscuro</span>
</button> 
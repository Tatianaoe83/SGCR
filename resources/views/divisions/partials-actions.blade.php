<div class="flex items-center justify-center gap-1">

    {{-- VER --}}
    <a href="{{ route('divisions.show', $division->id_division) }}"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md
              bg-slate-600 hover:bg-slate-700 text-white transition"
        title="Ver">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5
                     c4.478 0 8.268 2.943 9.542 7
                     -1.274 4.057-5.064 7-9.542 7
                     -4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    </a>

    {{-- EDITAR --}}
    <a href="{{ route('divisions.edit', $division->id_division) }}"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md
              bg-indigo-600 hover:bg-indigo-700 text-white transition"
        title="Editar">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11
                     a2 2 0 002 2h11
                     a2 2 0 002-2v-5
                     m-1.414-9.414
                     a2 2 0 112.828 2.828
                     L11.828 15H9v-2.828
                     l8.586-8.586z" />
        </svg>
    </a>

    {{-- ELIMINAR --}}
    <form action="{{ route('divisions.destroy', $division->id_division) }}"
        method="POST"
        onsubmit="return confirm('¿Eliminar esta división?')"
        class="inline-block">
        @csrf
        @method('DELETE')

        <button type="submit"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md
                       bg-rose-600 hover:bg-rose-700 text-white transition"
            title="Eliminar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142
                         A2 2 0 0116.138 21H7.862
                         a2 2 0 01-1.995-1.858
                         L5 7m5 4v6m4-6v6
                         m1-10V4a1 1 0 00-1-1h-4
                         a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </form>
</div>
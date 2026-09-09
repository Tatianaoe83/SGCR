<div>
    <input type="checkbox" name="light-switch" id="light-switch" class="light-switch sr-only" />
    <label class="sgc-icon-btn" for="light-switch" title="Cambiar tema">
        {{-- Luna: visible en modo día (para pasar a noche) --}}
        <svg class="dark:hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        {{-- Sol: visible en modo noche (para pasar a día) --}}
        <svg class="hidden dark:block" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6L19 19M19 5l-1.4 1.4M6.4 17.6L5 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <span class="sr-only">Cambiar modo claro / oscuro</span>
    </label>
</div>

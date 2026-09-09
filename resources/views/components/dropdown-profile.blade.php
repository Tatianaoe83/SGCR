@props([
    'align' => 'right'
])

@php
    $fullName = trim((string) (Auth::user()->name ?? ''));
    $parts = preg_split('/\s+/u', $fullName) ?: [];
    $initials = '';
    if (count($parts) >= 2) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    } elseif ($fullName !== '') {
        $initials = mb_strtoupper(mb_substr($fullName, 0, 2));
    } else {
        $initials = 'U';
    }
    $displayName = mb_strtoupper($fullName);
@endphp

<div x-data="{ open: false }" class="sgc-user-chip relative inline-flex">
    <button
        type="button"
        class="sgc-user-chip-btn"
        aria-haspopup="true"
        @click.prevent="open = !open"
        :aria-expanded="open"
    >
        <span class="sgc-user-avatar">{{ $initials }}</span>
        <span class="sgc-user-name">{{ $displayName }}</span>
        <svg class="sgc-user-chev" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
    <div
        class="origin-top-right z-10 absolute top-full min-w-44 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1 {{$align === 'right' ? 'right-0' : 'left-0'}}"
        style="background: var(--surface); border: 1px solid var(--border);"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        x-show="open"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-out duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
    >
        <div class="pt-0.5 pb-2 px-3 mb-1" style="border-bottom: 1px solid var(--border);">
            <div class="font-medium" style="color: var(--text);">{{ $fullName }}</div>
            <div class="text-xs italic" style="color: var(--text-3);">{{ Auth::user()->roles->pluck('name')->implode(', ') }}</div>
        </div>
        <ul>
            <li>
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <a class="font-medium text-sm flex items-center py-1 px-3"
                        style="color: var(--accent);"
                        href="{{ route('logout') }}"
                        @click.prevent="$root.submit();"
                        @focus="open = true"
                        @focusout="open = false"
                    >
                        {{ __('Cerrar sesión') }}
                    </a>
                </form>
            </li>
        </ul>
    </div>
</div>

<style>
    .sgc-user-chip-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 6px 14px 6px 6px;
        border-radius: 11px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        cursor: pointer;
        transition: border-color .16s, background .16s;
    }
    .sgc-user-chip-btn:hover {
        border-color: var(--accent-2);
    }
    .sgc-user-avatar {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        flex-shrink: 0;
        background: linear-gradient(145deg, var(--navy-700), var(--blue));
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 800;
        color: #fff;
        box-shadow: 0 3px 10px #12275e55;
        letter-spacing: .3px;
    }
    .sgc-user-name {
        font-size: 12.5px;
        font-weight: 700;
        letter-spacing: .3px;
        color: var(--text);
        max-width: 14rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    @media (max-width: 880px) {
        .sgc-user-name { display: none; }
    }
    .sgc-user-chev {
        width: 14px;
        height: 14px;
        color: var(--text-2);
        flex-shrink: 0;
    }
</style>

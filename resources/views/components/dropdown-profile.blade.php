@props([
    'align' => 'right'
])

<div x-data="{ open: false }" class="flex items-center space-x-3 bg-purple-600/40 dark:bg-purple-950/80 rounded-xl px-4 py-2 backdrop-blur-sm border border-white/40 shadow-lg relative inline-flex">
    <button
        class="inline-flex justify-center items-center group"
        aria-haspopup="true"
        @click.prevent="open = !open"
        :aria-expanded="open"                        
    >
        <img class="w-8 h-8 rounded-full bg-white dark:bg-purple-100 ring-2 ring-white/60" src="{{ Auth::user()->profile_photo_url }}" width="32" height="32" alt="{{ Auth::user()->name }}" />
        <div class="flex items-center truncate">
            <span class="text-white dark:text-white text-sm font-semibold ml-2">{{ Auth::user()->name }}</span>
            <svg class="w-4 h-4 text-white dark:text-purple-100" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </div>
    </button>
    <div
        class="origin-top-right z-10 absolute top-full min-w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1 {{$align === 'right' ? 'right-0' : 'left-0'}}"                
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
    
        <div class="pt-0.5 pb-2 px-3 mb-1 border-b border-gray-200 dark:border-gray-700/60">
            <div class="font-medium text-gray-800 dark:text-gray-100">{{ Auth::user()->name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 italic">{{ Auth::user()->roles->pluck('name')->implode(', ') }}</div>
        </div>
        <ul>
            <!-- <li>
                <a class="font-medium text-sm text-violet-500 hover:text-violet-600 dark:hover:text-violet-400 flex items-center py-1 px-3" href="{{ route('profile.show') }}" @click="open = false" @focus="open = true" @focusout="open = false">Settings</a>
            </li> -->
            <li>
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <a class="font-medium text-sm text-violet-500 hover:text-violet-600 dark:hover:text-violet-400 flex items-center py-1 px-3"
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
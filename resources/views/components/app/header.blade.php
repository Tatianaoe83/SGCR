<header class="sgc-topbar sticky top-0 shrink-0 z-30">
    <style>
        .sgc-topbar {
            background: var(--topbar);
            border-bottom: 1px solid var(--border);
            transition: background .3s, border-color .3s;
        }
        .sgc-topbar-inner {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 26px;
        }
        .sgc-crumb {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            padding: 9px 16px;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text);
        }
        .sgc-crumb svg { color: var(--accent-2); width: 16px; height: 16px; flex-shrink: 0; }
        .sgc-top-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .sgc-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface-2);
            display: grid;
            place-items: center;
            cursor: pointer;
            color: var(--text-2);
            transition: color .16s, border-color .16s, background .16s;
            position: relative;
            flex-shrink: 0;
        }
        .sgc-icon-btn:hover {
            color: var(--text);
            border-color: var(--accent-2);
        }
        .sgc-icon-btn svg {
            width: 18px;
            height: 18px;
        }
        .sgc-icon-btn .sgc-ping {
            position: absolute;
            top: 9px;
            right: 10px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 6px var(--gold);
        }
        .sgc-top-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-2);
            transition: background .16s, color .16s;
            white-space: nowrap;
        }
        .sgc-top-link:hover,
        .sgc-top-link.is-active {
            color: var(--accent);
            background: color-mix(in srgb, var(--accent-2) 12%, transparent);
        }
        @media (max-width: 640px) {
            .sgc-topbar-inner { padding: 0 14px; height: 64px; }
        }
    </style>
    <div class="sgc-topbar-inner">
        <!-- Left side: Hamburger -->
        <div class="flex items-center gap-4 flex-1 min-w-0">
            <button
                class="sgc-icon-btn"
                @click.stop="sidebarOpen = !sidebarOpen"
                aria-controls="sidebar"
                :aria-expanded="sidebarOpen">
                <span class="sr-only">Abrir menu</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>

            <!-- Right side: Notifications, Theme Toggle, User -->
            <div class="sgc-top-right">
                <x-notificaciones-firmas />
                <x-modal-suggets-change-control align="right"/>
                <x-theme-toggle />
                <x-dropdown-profile align="right" />
            </div>
    </div>
</header>
@php
    $bobSuggestions = [
        [
            'label' => 'Quiero un procedimiento…',
            'query' => 'Explícame el procedimiento ',
            'tone' => 'blue',
            'icon' => 'doc',
            'mode' => 'fill',
        ],
        [
            'label' => 'Ver procesos',
            'query' => 'qué procesos hay',
            'tone' => 'blue',
            'icon' => 'nodes',
            'mode' => 'send',
        ],
        [
            'label' => 'Estructura de la empresa',
            'query' => 'cómo está organizada la empresa',
            'tone' => 'blue',
            'icon' => 'nodes',
            'mode' => 'send',
        ],
        [
            'label' => 'Busco quién ocupa…',
            'query' => '¿Quién ocupa el puesto de ',
            'tone' => 'gold',
            'icon' => 'users',
            'mode' => 'fill',
        ],
    ];
@endphp

<x-app-layout>
    <div id="bobChatPage" class="bob-sgc-theme flex flex-col min-h-[calc(100dvh-4rem)]">
        <div class="relative flex-1 min-h-0 flex flex-col px-0 sm:px-4 sm:py-4 h-full">
            <div class="relative mx-auto w-full flex-1 min-h-0 flex flex-col h-full max-w-[1180px]">
                <div id="bobChatShell" class="bob-chat-shell bob-panel flex-1 min-h-0 flex flex-col rounded-none sm:rounded-2xl overflow-hidden">
                    <div id="bobChatMain" class="bob-chat-main flex-1 min-w-0 min-h-0 flex flex-col">
                        <div class="bob-panel-head flex items-start justify-between gap-3 px-4 sm:px-6 pt-4 sm:pt-5 pb-3 shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3 min-w-0 flex-wrap">
                                    <h1 class="bob-panel-title truncate">ASISTENTE</h1>
                                    <span class="bob-status-pill">
                                        <span class="bob-status-dot"></span>
                                        Conectado
                                    </span>
                                </div>
                                <div class="bob-panel-sub">BOB · v2.1.1</div>
                            </div>

                            <button
                                type="button"
                                id="btnGuiaUso"
                                class="bob-tips-btn shrink-0"
                                title="Tips de uso">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
                                    <path stroke-linecap="round" stroke-width="2" d="M12 16v-4M12 8h.01"/>
                                </svg>
                                Tips
                            </button>
                        </div>

                        <div
                            id="chatContainer"
                            class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-3 sm:px-6 py-2 sm:py-3 space-y-3 sm:space-y-4"
                            style="-webkit-overflow-scrolling: touch; scroll-behavior: smooth; overscroll-behavior: contain;">

                            <div id="bobEmptyState" class="bob-hero">
                                <h2 class="bob-hero-title">¿En qué te ayudo hoy?</h2>
                                <p class="bob-hero-lead">Soy Bob, tu guía del SGC: procedimientos, procesos, áreas, unidades, puestos y personas. Escribe para autocompletar o elige un camino.</p>

                                <div class="bob-quick-grid">
                                    @foreach ($bobSuggestions as $suggestion)
                                        <button
                                            type="button"
                                            class="bob-quick {{ $suggestion['tone'] === 'gold' ? 'is-gold' : '' }}"
                                            data-chip="{{ $suggestion['query'] }}"
                                            data-chip-mode="{{ $suggestion['mode'] ?? 'fill' }}">
                                            <span class="bob-q-ico {{ $suggestion['tone'] === 'gold' ? 'gold' : '' }}" aria-hidden="true">
                                                @if ($suggestion['icon'] === 'nodes')
                                                    <svg viewBox="0 0 24 24" fill="none"><circle cx="6" cy="6" r="2.4" stroke="currentColor" stroke-width="1.7"/><circle cx="18" cy="6" r="2.4" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="18" r="2.4" stroke="currentColor" stroke-width="1.7"/><path d="M6 8.4v2.1a2 2 0 002 2h8a2 2 0 002-2V8.4M12 12.5v3.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                                @elseif ($suggestion['icon'] === 'shield')
                                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l7 3v5.5c0 4-3 6.6-7 8-4-1.4-7-4-7-8V6l7-3z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 11.8l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                @elseif ($suggestion['icon'] === 'users')
                                                    <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 19a5.5 5.5 0 0111 0M16 6.5a3 3 0 010 6M20.5 19a5 5 0 00-3.5-4.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                                @else
                                                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 3h9l4 4v14H6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 3v5h5M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                                @endif
                                            </span>
                                            <span class="bob-q-txt">{{ $suggestion['label'] }}</span>
                                            <span class="bob-q-arrow" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="bob-tips-strip">
                                    <div class="bob-tips-strip-head">
                                        <div>
                                            <div class="bob-tips-strip-title">Tips para mejores respuestas</div>
                                            <p class="bob-tips-strip-sub">Atajos útiles mientras chateas con Bob</p>
                                        </div>
                                        <button type="button" class="bob-tips-strip-link" data-open-tips="1">Ver guía completa</button>
                                    </div>
                                    <div class="bob-tips-strip-grid">
                                        <div class="bob-tip-mini">
                                            <strong>Autocompletar</strong>
                                            Escribe 2+ letras: folio, puesto, área o persona
                                        </div>
                                        <div class="bob-tip-mini">
                                            <strong>Estructura</strong>
                                            Unidades, áreas, puestos y quién los ocupa
                                        </div>
                                        <div class="bob-tip-mini">
                                            <strong>Documentos</strong>
                                            Procesos, procedimientos, pasos u objetivo
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <div class="bob-composer shrink-0 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                        <div class="bob-composer-stack">
                            <div id="bobSuggestBox" class="bob-suggest-box hidden" role="listbox" aria-label="Sugerencias de búsqueda"></div>
                            <div class="bob-input-wrap">
                                <input
                                    type="text"
                                    id="messageInput"
                                    placeholder="Escribe folio, puesto, área o persona…"
                                    autocomplete="off"
                                    enterkeyhint="send"
                                    class="bob-message-input"
                                    aria-autocomplete="list"
                                    aria-controls="bobSuggestBox" />

                                <button
                                    id="micButton"
                                    type="button"
                                    class="bob-mic hidden sm:grid"
                                    title="Hablar">
                                    <svg id="micIcon" viewBox="0 0 24 24" fill="none">
                                        <rect x="9" y="3" width="6" height="11" rx="3" stroke="currentColor" stroke-width="1.8"/>
                                        <path d="M5 11a7 7 0 0014 0M12 18v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button
                            id="sendButton"
                            type="button"
                            class="bob-send-btn"
                            aria-label="Enviar">
                            <span class="hidden sm:inline">Enviar</span>
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l16-8-6 16-3-6-7-2z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Guía de uso (móvil / tablet) --}}
    <div
        id="guiaModal"
        class="hidden fixed inset-0 z-50 items-center justify-center p-3 sm:p-3 lg:pl-[16.5rem] xl:pl-[18.5rem]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="guiaModalTitle">
        <div id="guiaModalOverlay" class="guia-overlay absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div class="guia-panel relative w-full max-w-3xl max-h-[min(90dvh,100%)] flex flex-col rounded-2xl sm:rounded-3xl shadow-lg overflow-hidden"
             style="background: var(--surface); border: 1px solid var(--border);">
            <div class="flex items-start justify-between gap-3 px-4 sm:px-5 py-3 sm:py-4 shrink-0"
                 style="border-bottom: 1px solid var(--border);">
                <div class="min-w-0">
                    <h2 id="guiaModalTitle" class="text-lg font-semibold" style="color: var(--text);">Tips</h2>
                    <p class="mt-0.5 text-xs" style="color: var(--text-3);">
                        Cómo sacarle provecho a Bob (rápido)
                    </p>
                </div>
                <button
                    type="button"
                    id="guiaModalClose"
                    class="h-9 w-9 shrink-0 rounded-xl flex items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent-2)] transition-colors cursor-pointer"
                    style="border: 1px solid var(--border); color: var(--text-2); background: var(--surface-2);"
                    aria-label="Cerrar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 min-h-0">
                @include('pages.dashboard.partials.guia-uso')
            </div>
        </div>
    </div>

    <style>
        .typing-indicator {
            animation: typing 1s infinite ease-in-out;
            opacity: 0.6;
        }

        @keyframes typing {
            0% {
                transform: translateY(0);
                opacity: 0.5;
            }

            50% {
                transform: translateY(-3px);
                opacity: 1;
            }

            100% {
                transform: translateY(0);
                opacity: 0.5;
            }
        }

        /* Modal guía de uso: entrada/salida */
        .guia-overlay {
            animation: guiaFadeIn 180ms ease-out;
        }

        .guia-panel {
            animation: guiaZoomIn 220ms cubic-bezier(0.34, 1.3, 0.64, 1);
        }

        #guiaModal.is-closing .guia-overlay {
            animation: guiaFadeOut 150ms ease-in forwards;
        }

        #guiaModal.is-closing .guia-panel {
            animation: guiaZoomOut 150ms ease-in forwards;
        }

        @keyframes guiaFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes guiaFadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        @keyframes guiaZoomIn {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(12px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes guiaZoomOut {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .guia-overlay,
            .guia-panel,
            #guiaModal.is-closing .guia-overlay,
            #guiaModal.is-closing .guia-panel {
                animation-duration: 1ms;
            }
        }

        .chip-hint {
            margin: 0 0 0.5rem;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #021D49;
        }

        .chip-suggestion {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            border-radius: 9999px;
            border: 2px solid #021D49;
            background: #021D49;
            color: #ffffff !important;
            padding: 0.4rem 0.9rem;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            box-shadow: 0 1px 2px rgba(2, 29, 73, 0.18);
            cursor: pointer;
            white-space: normal;
            text-align: left;
        }

        .chip-suggestion:hover {
            background: #032a6b;
            border-color: #fbbf24;
        }

        .chat-doc-open {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            border-radius: 0.5rem;
            border: 2px solid #021D49;
            background: #021D49;
            color: #ffffff !important;
            padding: 0.25rem 0.65rem;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .chat-doc-open:hover {
            background: #032a6b;
            border-color: #fbbf24;
            color: #ffffff !important;
        }

        .chat-feedback-btn {
            border-radius: 0.375rem;
            border: 1px solid var(--border, #A7A8A9);
            background: var(--surface, #ffffff);
            color: var(--text, #021D49);
            padding: 0.25rem 0.55rem;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1.2;
            min-width: 1.75rem;
        }
        .chat-feedback-btn:hover {
            border-color: var(--green, #2FA06E);
            color: var(--green, #2FA06E);
        }
        .chat-feedback-btn.is-active {
            border-color: var(--green, #2FA06E);
            color: var(--green, #2FA06E);
            background: #2fa06e1a;
        }
        .chat-score-wrap {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 4px;
            padding-top: 10px;
            border-top: 1px solid var(--border, #A7A8A9);
        }
        .chat-score-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }
        .chat-score-row .chat-score-label {
            margin-right: 2px;
            color: var(--text, #021D49);
            font-weight: 700;
            font-size: 12px;
        }
        .chat-score-hint {
            font-size: 11px;
            color: var(--text-3, #8A93A8);
            font-weight: 500;
        }
        .chat-score-thanks {
            font-size: 12px;
            font-weight: 700;
            color: var(--green, #2FA06E);
        }
        .dark .chat-score-thanks {
            color: #fcd34d;
        }

        .dark .chip-hint {
            color: #fcd34d;
        }

        .dark .chat-score-wrap {
            border-top-color: rgba(251, 191, 36, 0.35);
        }
        .dark .chat-score-row .chat-score-label {
            color: #fcd34d;
        }
        .dark .chat-score-hint {
            color: #cbd5e1;
        }
        .dark .chat-feedback-btn {
            background: #fbbf24;
            border-color: #fbbf24;
            color: #021D49 !important;
        }
        .dark .chat-feedback-btn:hover,
        .dark .chat-feedback-btn.is-active {
            background: #fcd34d;
            border-color: #fcd34d;
            color: #021D49 !important;
        }

        .dark .chip-suggestion,
        .dark .chat-doc-open {
            background: #fbbf24;
            border-color: #fbbf24;
            color: #021D49 !important;
        }

        .dark .chip-suggestion:hover,
        .dark .chat-doc-open:hover {
            background: #fcd34d;
            border-color: #fcd34d;
            color: #021D49 !important;
        }

        #chatContainer .chat-bubble,
        #chatContainer .chat-bubble > div {
            min-width: 0;
            max-width: 100%;
        }

        #chatContainer .prose,
        #chatContainer .prose p,
        #chatContainer .prose li,
        #chatContainer .prose td,
        #chatContainer .prose th {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        #chatContainer .prose pre,
        #chatContainer .prose table {
            display: block;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #chatContainer .prose img {
            max-width: 100%;
            height: auto;
        }

        #bobChatPage {
            height: calc(100dvh - 4rem);
            max-height: calc(100dvh - 4rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #bobChatPage > .relative,
        #bobChatPage .mx-auto,
        #bobChatShell,
        #bobChatMain {
            min-height: 0;
        }

        #bobChatShell,
        #bobChatMain {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
        }

        #chatContainer {
            flex: 1 1 auto;
            min-height: 0;
        }

        /* ===== Tema PROSER: hereda tokens globales :root / html.dark ===== */
        .bob-sgc-theme {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', Inter, system-ui, sans-serif;
            transition: background .3s, color .3s;
        }

        #bobChatMain.bob-chat-main {
            flex: 1 1 0%;
            min-height: 0;
        }

        .bob-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--panel-shadow);
            transition: background .3s, border-color .3s;
        }

        .bob-card {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .bob-card-soft {
            background: var(--surface-2);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .bob-muted { color: var(--text-3); }
        .bob-text { color: var(--text); }
        .bob-text-2 { color: var(--text-2); }

        .bob-panel-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--text);
        }

        @media (min-width: 640px) {
            .bob-panel-title { font-size: 1.5rem; }
        }

        .bob-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--green);
            background: #2fa06e1a;
            border: 1px solid #2fa06e40;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .bob-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 8px var(--green);
        }

        .bob-panel-sub {
            font-size: 12px;
            color: var(--text-3);
            margin-top: 5px;
            font-weight: 600;
            letter-spacing: .6px;
        }

        .bob-tips-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--gold-txt);
            background: #c6a15b1a;
            border: 1px solid #c6a15b55;
            padding: 9px 16px;
            border-radius: 22px;
            cursor: pointer;
            transition: background .18s, transform .18s;
        }

        .bob-tips-btn:hover {
            background: #c6a15b2b;
            transform: translateY(-1px);
        }

        .bob-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 28px 12px 24px;
        }

        @media (min-width: 640px) {
            .bob-hero { padding: 40px 20px 32px; }
        }

        .bob-hero-title {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -.3px;
            line-height: 1.15;
            background: linear-gradient(92deg, var(--text) 35%, var(--accent-2) 135%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (min-width: 640px) {
            .bob-hero-title { font-size: 1.95rem; }
        }

        .bob-hero-lead {
            font-size: 14.5px;
            color: var(--text-2);
            max-width: 440px;
            margin-top: 12px;
            line-height: 1.6;
        }

        .bob-quick-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            width: 100%;
            max-width: 620px;
            margin-top: 28px;
            text-align: left;
        }

        @media (min-width: 640px) {
            .bob-quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .bob-quick {
            display: flex;
            align-items: center;
            gap: 14px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 18px;
            cursor: pointer;
            transition: border-color .2s, transform .2s, box-shadow .2s;
            position: relative;
            overflow: hidden;
            color: var(--text);
        }

        .bob-quick::after {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent-2);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform .2s;
        }

        .bob-quick.is-gold::after { background: var(--gold); }

        .bob-quick:hover {
            border-color: var(--accent-2);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -18px #12275e55;
        }

        .bob-quick:hover::after { transform: scaleY(1); }

        .bob-q-ico {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: #3d6ed01f;
            border: 1px solid #3d6ed040;
            color: var(--blue);
        }

        .bob-q-ico.gold {
            background: #c6a15b1f;
            border-color: #c6a15b45;
            color: var(--gold);
        }

        .bob-q-ico svg { width: 20px; height: 20px; }

        .bob-q-txt {
            flex: 1 1 auto;
            min-width: 0;
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.35;
        }

        .bob-q-arrow {
            margin-left: auto;
            color: var(--text-3);
            transition: color .2s, transform .2s;
            flex-shrink: 0;
        }

        .bob-q-arrow svg { width: 16px; height: 16px; display: block; }

        .bob-quick:hover .bob-q-arrow {
            color: var(--accent-2);
            transform: translateX(3px);
        }

        .bob-tips-strip {
            width: 100%;
            max-width: 620px;
            margin-top: 22px;
            text-align: left;
            border: 1px solid #c6a15b45;
            background: linear-gradient(135deg, #c6a15b14 0%, var(--surface-2) 70%);
            border-radius: 14px;
            padding: 14px 16px;
        }

        .dark .bob-tips-strip {
            border-color: #c6a15b40;
            background: linear-gradient(135deg, #c6a15b18 0%, var(--surface-2) 75%);
        }

        .bob-tips-strip-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .bob-tips-strip-title {
            font-size: 13px;
            font-weight: 800;
            color: var(--gold-txt);
        }

        .bob-tips-strip-sub {
            font-size: 11.5px;
            color: var(--text-3);
            margin-top: 2px;
        }

        .bob-tips-strip-link {
            font-size: 11px;
            font-weight: 700;
            color: var(--accent-2);
            background: transparent;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
            flex-shrink: 0;
        }

        .bob-tips-strip-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        @media (min-width: 768px) {
            .bob-tips-strip-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        .bob-tip-mini {
            border: 1px solid var(--border);
            background: var(--surface);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.4;
            color: var(--text-2);
        }

        .bob-tip-mini strong {
            display: block;
            color: var(--text);
            margin-bottom: 2px;
            font-size: 12px;
        }

        .bob-composer {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
            padding: 16px 16px 14px;
            border-top: 1px solid var(--border);
        }

        .bob-composer-stack {
            flex: 1;
            min-width: 0;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .bob-suggest-box {
            position: absolute;
            left: 0;
            right: 0;
            bottom: calc(100% + 6px);
            z-index: 40;
            max-height: 240px;
            overflow-y: auto;
            border-radius: 12px;
            border: 1px solid var(--border, #A7A8A9);
            background: var(--surface, #ffffff);
            box-shadow: 0 10px 28px rgba(2, 29, 73, 0.18);
            padding: 6px;
        }
        .bob-suggest-box.hidden { display: none; }
        .bob-suggest-item {
            width: 100%;
            text-align: left;
            border: 0;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            color: var(--text, #021D49);
        }
        .bob-suggest-item:hover,
        .bob-suggest-item.is-active {
            background: #3d6ed014;
        }
        .bob-suggest-label {
            display: block;
            font-size: 13px;
            font-weight: 650;
            line-height: 1.25;
        }
        .bob-suggest-hint {
            display: block;
            font-size: 11px;
            color: var(--text-3, #8A93A8);
            margin-top: 2px;
        }
        .dark .bob-suggest-box {
            background: #0b1b3a;
            border-color: rgba(251, 191, 36, 0.35);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        }
        .dark .bob-suggest-item:hover,
        .dark .bob-suggest-item.is-active {
            background: rgba(251, 191, 36, 0.16);
        }
        .dark .bob-suggest-label { color: #f8fafc; }
        .dark .bob-suggest-hint { color: #cbd5e1; }

        @media (min-width: 640px) {
            .bob-composer { padding: 18px 22px 18px; }
        }

        .bob-input-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0 10px 0 16px;
            height: 52px;
            transition: border-color .18s, box-shadow .18s;
            min-width: 0;
        }

        .bob-input-wrap:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px #3d6ed030;
        }

        .bob-message-input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            height: 100%;
            color: var(--text);
            font-size: 14.5px;
            min-width: 0;
        }

        .bob-message-input::placeholder { color: var(--text-3); }

        .bob-mic {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            place-items: center;
            color: var(--text-2);
            cursor: pointer;
            transition: color .16s, background .16s;
            border: none;
            background: transparent;
            flex-shrink: 0;
        }

        .bob-mic:hover {
            color: var(--accent-2);
            background: #3d6ed014;
        }

        .bob-mic svg { width: 18px; height: 18px; }

        .bob-send-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            height: 52px;
            min-width: 52px;
            padding: 0 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14.5px;
            font-weight: 700;
            letter-spacing: .3px;
            color: #fff;
            background: linear-gradient(135deg, var(--green), var(--green-lt));
            box-shadow: 0 8px 22px -6px #2fa06eaa;
            transition: transform .18s, box-shadow .18s;
            flex-shrink: 0;
        }

        @media (min-width: 640px) {
            .bob-send-btn { padding: 0 26px; }
        }

        .bob-send-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px -6px #2fa06ecc;
        }

        .bob-send-btn svg { width: 17px; height: 17px; }

        #bobEmptyState.is-hidden {
            display: none !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>

    <script>
        const chatContainer = document.getElementById('chatContainer');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const ai3dModel = document.getElementById('ai3dModel');
        const processingStatus = document.getElementById('processingStatus');
        const overlayStatus = document.getElementById('overlayStatus');
        const processingBar = document.getElementById('processingBar');
        const micButton = document.getElementById('micButton');
        const micIcon = document.getElementById('micIcon');

        let modelViewer = null;
        let recognition = null;
        let isRecording = false;

        const BASE_PLACEHOLDER = 'Escribe tu consulta o completa una sugerencia...';
        const SESSION_KEY = 'bob_chat_session_id';
        let SESSION_ID = sessionStorage.getItem(SESSION_KEY);
        if (!SESSION_ID) {
            SESSION_ID = 'sess_' + Math.random().toString(36).substring(2, 15) + '_' + Date.now();
            sessionStorage.setItem(SESSION_KEY, SESSION_ID);
        }

        marked.setOptions({
            breaks: true,
            gfm: true
        });

        function animateCharacter(state) {
            if (!modelViewer) {
                modelViewer = ai3dModel?.querySelector('model-viewer');
            }

            if (!processingStatus || !overlayStatus || !processingBar) return;

            switch (state) {
                case 'thinking':
                    processingStatus.textContent = 'Buscando en el SGC...';
                    overlayStatus.textContent = 'THINKING';
                    overlayStatus.className = 'text-yellow-400 text-xs font-mono';
                    processingBar.style.width = '60%';
                    break;
                case 'speaking':
                    processingStatus.textContent = 'Respondiendo...';
                    overlayStatus.textContent = 'SPEAKING';
                    overlayStatus.className = 'text-green-400 text-xs font-mono';
                    processingBar.style.width = '100%';
                    break;
                case 'idle':
                default:
                    processingStatus.textContent = 'Listo';
                    overlayStatus.textContent = 'IDLE';
                    overlayStatus.className = 'text-blue-400 text-xs font-mono';
                    processingBar.style.width = '20%';
                    break;
            }
        }

        function renderMarkdownSafe(md) {
            const html = marked.parse(md ?? '');
            const cleaned = DOMPurify.sanitize(html, {
                USE_PROFILES: {
                    html: true
                }
            });

            const temp = document.createElement('div');
            temp.innerHTML = cleaned;

            // Mejorar enlaces a PDFs con diseño moderno
            const pdfLinks = temp.querySelectorAll('a[href*=".pdf"]');
            pdfLinks.forEach(link => {
                link.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30 border border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400 hover:from-red-100 hover:to-rose-100 dark:hover:from-red-900/50 dark:hover:to-rose-900/50 font-semibold text-sm transition-all duration-200 shadow-sm hover:shadow-md';
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');

                const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                icon.setAttribute('class', 'w-4 h-4 flex-shrink-0');
                icon.setAttribute('width', '16');
                icon.setAttribute('height', '16');
                icon.setAttribute('fill', 'none');
                icon.setAttribute('stroke', 'currentColor');
                icon.setAttribute('stroke-width', '1.8');
                icon.setAttribute('viewBox', '0 0 24 24');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-6.375a2.25 2.25 0 00-.659-1.591l-3.375-3.375a2.25 2.25 0 00-1.591-.659H6.75A2.25 2.25 0 004.5 4.5v15A2.25 2.25 0 006.75 21.75h10.5a2.25 2.25 0 002.25-2.25V14.25M13.5 3.75V7.5a.75.75 0 00.75.75H18m-10.5 5.25h9m-9 3h6" />';

                link.innerHTML = '';
                link.insertBefore(icon, link.firstChild);
                link.appendChild(document.createTextNode('Ver Documento'));
            });

            // Mejorar tablas
            const tables = temp.querySelectorAll('table');
            tables.forEach(table => {
                table.className = 'w-full min-w-[28rem] rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm';
                const wrap = document.createElement('div');
                wrap.className = 'overflow-x-auto -mx-1 px-1';
                table.parentNode.insertBefore(wrap, table);
                wrap.appendChild(table);
                const thead = table.querySelector('thead');
                if (thead) {
                    thead.className = 'bg-slate-100 dark:bg-slate-800';
                    thead.querySelectorAll('th').forEach(th => {
                        th.className = 'px-4 py-2 text-left font-semibold text-slate-900 dark:text-slate-100 text-sm';
                    });
                }
                table.querySelectorAll('tbody tr').forEach((tr, idx) => {
                    tr.className = idx % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-950/50';
                    tr.querySelectorAll('td').forEach(td => {
                        td.className = 'px-4 py-2 text-sm text-slate-700 dark:text-slate-300 border-t border-slate-200 dark:border-slate-700';
                    });
                });
            });

            // Mejorar bloques de código
            const codeBlocks = temp.querySelectorAll('pre');
            codeBlocks.forEach(pre => {
                const code = pre.querySelector('code');
                pre.className = 'rounded-lg bg-slate-950 dark:bg-slate-950 border border-slate-800 p-4 overflow-x-auto shadow-md';
                if (code) {
                    code.className = 'text-slate-100 text-xs font-mono leading-relaxed';
                }
            });

            return temp.innerHTML;
        }

        // Ficha del documento consultado. Va aparte del texto para que la respuesta suene natural.
        function buildDocumentCard(doc) {
            const card = document.createElement('div');
            card.className = 'rounded-xl bob-card-soft px-3 py-2.5 min-w-0 overflow-hidden';

            const head = document.createElement('div');
            head.className = 'flex items-start justify-between gap-3';

            const titles = document.createElement('div');
            titles.className = 'min-w-0';

            const label = document.createElement('p');
            label.className = 'text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500';
            label.textContent = 'Documento consultado';

            const name = document.createElement('p');
            name.className = 'text-xs font-semibold text-slate-900 dark:text-slate-100 truncate';
            name.textContent = doc.nombre ?? 'Documento';

            titles.append(label, name);
            head.appendChild(titles);

            if (doc.url) {
                const link = document.createElement('a');
                link.href = doc.url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = 'Abrir';
                link.className = 'chat-doc-open shrink-0';
                head.appendChild(link);
            }

            card.appendChild(head);

            const facts = [
                doc.folio ? (doc.version ? `${doc.folio} · v${doc.version}` : doc.folio) : null,
                doc.tipo,
                doc.unidad,
                doc.responsable ? `Responsable: ${doc.responsable}` : null,
            ].filter(Boolean);

            if (facts.length) {
                const meta = document.createElement('div');
                meta.className = 'mt-2 flex flex-wrap gap-1.5';
                facts.forEach(fact => {
                    const pill = document.createElement('span');
                    pill.className = 'rounded-md bob-card px-2 py-0.5 text-[10px] bob-text-2';
                    pill.textContent = fact;
                    meta.appendChild(pill);
                });
                card.appendChild(meta);
            }

            return card;
        }

        function addMessage(message, isUser = false, meta = {}) {
            const time = new Date().toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });

            const wrapper = document.createElement('div');
            wrapper.className = `flex items-start gap-2 sm:gap-3 chat-bubble min-w-0 max-w-full ${isUser ? 'flex-row-reverse' : ''}`;

            const avatar = `
                <div class="hidden sm:flex h-10 w-10 rounded-2xl bob-card shadow-sm items-center justify-center bob-text-2 flex-shrink-0">
                    ${
                        isUser
                            ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                               </svg>`
                            : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                               </svg>`
                    }
                </div>
            `;

            const borderAccent = isUser ? 'border-emerald-400' : 'border-amber-400';
            const who = isUser ? 'Yo' : 'Bob';

            wrapper.innerHTML = `
                ${avatar}
                <div class="min-w-0 w-full max-w-full sm:max-w-3xl ${isUser ? 'sm:ml-auto' : ''}">
                    <div class="rounded-2xl bob-card shadow-sm overflow-hidden">
                        <div class="px-3 sm:px-4 py-3 border-l-4 ${borderAccent} rounded-2xl">
                            <div class="prose dark:prose-invert max-w-none text-[13px] sm:text-sm leading-relaxed bob-text">
                                ${renderMarkdownSafe(message)}
                            </div>

                            <div data-doc class="mt-3 min-w-0"></div>

                            <div data-chips class="mt-3 hidden min-w-0"></div>

                            <div data-feedback class="mt-3 min-w-0"></div>

                            <div class="mt-2 flex items-center justify-between gap-2 text-[10px] bob-muted">
                                <span class="font-mono shrink-0">${time} • ${who}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const docBox = wrapper.querySelector('[data-doc]');
            if (!isUser && meta.document) {
                docBox.appendChild(buildDocumentCard(meta.document));
            }

            // Chips de sugerencia: al tocar, envían esa consulta.
            // Backend puede mandar string o { label, query }.
            const chipsBox = wrapper.querySelector('[data-chips]');
            if (!isUser && chipsBox && Array.isArray(meta.chips) && meta.chips.length) {
                chipsBox.className = 'mt-3 pt-3';
                chipsBox.style.borderTop = '1px solid var(--border)';
                chipsBox.classList.remove('hidden');

                const hint = document.createElement('p');
                hint.className = 'chip-hint';
                hint.textContent = 'Continuar (completa si termina en …)';
                chipsBox.appendChild(hint);

                const row = document.createElement('div');
                row.className = 'flex flex-wrap gap-2';

                meta.chips.forEach(chip => {
                    let label = '';
                    let query = '';
                    let mode = '';
                    if (chip && typeof chip === 'object') {
                        label = String(chip.label ?? chip.text ?? chip.query ?? '').trim();
                        query = String(chip.query ?? chip.label ?? chip.text ?? '').trim();
                        mode = String(chip.mode ?? '').trim().toLowerCase();
                    } else {
                        label = String(chip ?? '').trim();
                        query = label;
                    }
                    if (!label && !query) {
                        return;
                    }
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = label || query;
                    btn.className = 'chip-suggestion';
                    btn.addEventListener('click', () => {
                        applyChipChoice(query || label, mode);
                    });
                    row.appendChild(btn);
                });
                chipsBox.appendChild(row);
            }

            // Feedback: score 1-5 (se guarda en chatbot_feedback + analytics).
            const feedbackBox = wrapper.querySelector('[data-feedback]');
            if (!isUser && feedbackBox && meta.analyticsId) {
                const wrap = document.createElement('div');
                wrap.className = 'chat-score-wrap';
                const row = document.createElement('div');
                row.className = 'chat-score-row';
                const label = document.createElement('span');
                label.className = 'chat-score-label';
                label.textContent = 'Califica esta respuesta';
                row.appendChild(label);
                for (let s = 1; s <= 5; s++) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'chat-feedback-btn';
                    btn.textContent = String(s);
                    btn.title = 'Calificar ' + s + '/5';
                    btn.addEventListener('click', () => sendChatFeedback(meta.analyticsId, s, feedbackBox));
                    row.appendChild(btn);
                }
                const hint = document.createElement('span');
                hint.className = 'chat-score-hint';
                hint.textContent = '1 malo · 5 excelente';
                wrap.appendChild(row);
                wrap.appendChild(hint);
                feedbackBox.appendChild(wrap);
            }

            chatContainer.appendChild(wrapper);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function chipShouldFill(query, mode) {
            if (mode === 'fill') return true;
            if (mode === 'send') return false;
            const q = String(query || '');
            return /…$|\.\.\.$|\s$/.test(q) || /\b(de|procedimiento|puesto)\s*$/i.test(q);
        }

        function applyChipChoice(query, mode) {
            const text = String(query || '');
            if (!text) return;
            messageInput.value = text;
            if (chipShouldFill(text, mode)) {
                messageInput.focus();
                const len = messageInput.value.length;
                messageInput.setSelectionRange(len, len);
                return;
            }
            sendMessage();
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start gap-2 sm:gap-3 chat-bubble min-w-0 max-w-full';
            typingDiv.innerHTML = `
                <div class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center bob-card bob-text-2 flex-shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="min-w-0 rounded-2xl bob-card shadow-sm px-3 sm:px-4 py-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex space-x-1 shrink-0">
                            <div class="w-2 h-2 rounded-full typing-indicator" style="background: var(--text);"></div>
                            <div class="w-2 h-2 rounded-full typing-indicator" style="background: var(--text); animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 rounded-full typing-indicator" style="background: var(--text); animation-delay: 0.4s;"></div>
                        </div>
                        <span class="bob-text-2 text-sm truncate">Buscando en el SGC...</span>
                    </div>
                </div>
            `;
            chatContainer.appendChild(typingDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) typingIndicator.remove();
        }

        async function getAIResponse(userMessage) {
            try {
                const response = await fetch('/chatbot/query', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: userMessage,
                        session_id: SESSION_ID
                    }),
                });

                if (response.status === 429) {
                    const errorData = await response.json();
                    return {
                        response: errorData.error || 'Límite de consultas alcanzado. Intenta en unos momentos.'
                    };
                }

                if (!response.ok) {
                    if (response.status === 401) {
                        return {
                            response: 'Sesión no válida para este endpoint. Recarga la página.'
                        };
                    }
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                if (!data?.response) throw new Error('No se recibió respuesta válida');

                return data;
            } catch (error) {
                console.error('Error al obtener respuesta de IA:', error);
                return {
                    response: 'Hubo un problema de conexión. Intenta reformular tu pregunta.'
                };
            }
        }

        function sendChatFeedback(analyticsId, scoreOrHelpful, groupEl) {
            if (!analyticsId || (groupEl && groupEl.dataset.feedbackSent === '1')) {
                return;
            }
            if (groupEl) {
                groupEl.dataset.feedbackSent = '1';
                const scoreTxt = typeof scoreOrHelpful === 'number' ? ` · ${scoreOrHelpful}/5` : '';
                groupEl.innerHTML = `<span class="chat-score-thanks">Gracias${scoreTxt}</span>`;
            }

            const payload = {
                analytics_id: analyticsId,
                session_id: SESSION_ID,
            };
            if (typeof scoreOrHelpful === 'number') {
                payload.score = scoreOrHelpful;
                payload.helpful = scoreOrHelpful >= 3;
            } else {
                payload.helpful = !!scoreOrHelpful;
            }

            // Envío en segundo plano: la UI ya confirmó al usuario.
            fetch('/chatbot/feedback', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
                keepalive: true,
            }).catch((e) => {
                console.error('Error enviando feedback:', e);
            });
        }

        async function restoreSessionHistory() {
            try {
                const res = await fetch(`/chatbot/history?session_id=${encodeURIComponent(SESSION_ID)}`, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                const msgs = Array.isArray(data.messages) ? data.messages : [];
                if (!msgs.length) return;

                const emptyState = document.getElementById('bobEmptyState');
                if (emptyState) emptyState.classList.add('is-hidden');

                msgs.forEach(m => {
                    if (m.role === 'user') {
                        addMessage(m.content || '', true);
                    } else {
                        addMessage(m.content || '', false, {
                            analyticsId: m.analytics_id,
                        });
                    }
                });
            } catch (e) {
                console.warn('No se pudo restaurar historial de sesión', e);
            }
        }

        async function sendMessage() {
            const message = (messageInput.value || '').trim();
            if (!message) return;

            const emptyState = document.getElementById('bobEmptyState');
            if (emptyState) emptyState.classList.add('is-hidden');

            addMessage(message, true);
            messageInput.value = '';

            animateCharacter('thinking');
            showTypingIndicator();

            try {
                const data = await getAIResponse(message);
                removeTypingIndicator();

                animateCharacter('speaking');
                addMessage(data.response, false, {
                    chips: data.chips,
                    analyticsId: data.analytics_id,
                    document: data.document,
                });

                setTimeout(() => animateCharacter('idle'), 1200);
            } catch (error) {
                console.error('Error en sendMessage:', error);
                removeTypingIndicator();
                animateCharacter('idle');
                addMessage('Lo siento, hubo un error al procesar tu mensaje. Por favor intenta nuevamente.', false);
            }
        }

        function initVoiceRecognition() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                console.warn('Tu navegador no soporta reconocimiento de voz');
                micButton.style.display = 'none';
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();

            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'es-ES';

            recognition.onstart = function() {
                isRecording = true;
                micButton.classList.add('bg-red-500/30', 'animate-pulse');
                micIcon.classList.remove('text-slate-500');
                micIcon.classList.add('text-red-400');
                messageInput.placeholder = 'Escuchando...';
            };

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                messageInput.value = transcript;
                messageInput.placeholder = BASE_PLACEHOLDER;
            };

            recognition.onerror = function(event) {
                console.error('Error en reconocimiento de voz:', event.error);
                stopVoiceRecognition();

                let errorMessage = 'Error en el micrófono';
                if (event.error === 'no-speech') errorMessage = 'No se detectó habla. Intenta nuevamente.';
                else if (event.error === 'not-allowed') errorMessage = 'Permiso de micrófono denegado. Por favor, permite el acceso al micrófono.';
                else if (event.error === 'network') errorMessage = 'Error de red. Verifica tu conexión.';

                addMessage(errorMessage, false);
            };

            recognition.onend = function() {
                stopVoiceRecognition();
            };
        }

        function toggleVoiceRecognition() {
            if (!recognition) initVoiceRecognition();
            if (!recognition) return;

            if (isRecording) {
                recognition.stop();
            } else {
                try {
                    recognition.start();
                } catch (error) {
                    console.error('Error al iniciar reconocimiento:', error);
                    addMessage('No se pudo iniciar el reconocimiento de voz. Verifica los permisos del micrófono.', false);
                }
            }
        }

        function stopVoiceRecognition() {
            isRecording = false;
            micButton.classList.remove('bg-red-500/30', 'animate-pulse');
            micIcon.classList.remove('text-red-400');
            micIcon.classList.add('text-slate-500');
            messageInput.placeholder = BASE_PLACEHOLDER;
        }

        function fitBobChatToKeyboard() {
            const page = document.getElementById('bobChatPage');
            if (!page || !window.visualViewport) {
                return;
            }
            const vv = window.visualViewport;
            const keyboardOpen = (window.innerHeight - vv.height) > 80;
            if (!keyboardOpen) {
                page.style.height = '';
                page.style.maxHeight = '';
                return;
            }
            const top = page.getBoundingClientRect().top;
            const height = Math.max(240, Math.round(vv.height - top));
            page.style.height = height + 'px';
            page.style.maxHeight = height + 'px';
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', fitBobChatToKeyboard);
            window.visualViewport.addEventListener('scroll', fitBobChatToKeyboard);
        }
        window.addEventListener('resize', fitBobChatToKeyboard);
        messageInput.addEventListener('focus', () => {
            setTimeout(fitBobChatToKeyboard, 300);
        });
        messageInput.addEventListener('blur', () => {
            setTimeout(fitBobChatToKeyboard, 300);
        });

        sendButton.addEventListener('click', sendMessage);
        micButton.addEventListener('click', toggleVoiceRecognition);

        const suggestBox = document.getElementById('bobSuggestBox');
        let suggestTimer = null;
        let suggestItems = [];
        let suggestActive = -1;
        let suggestSeq = 0;

        function hideSuggestBox() {
            if (!suggestBox) return;
            suggestBox.classList.add('hidden');
            suggestBox.innerHTML = '';
            suggestItems = [];
            suggestActive = -1;
        }

        function renderSuggestBox(items) {
            if (!suggestBox) return;
            suggestItems = Array.isArray(items) ? items : [];
            suggestActive = suggestItems.length ? 0 : -1;
            if (!suggestItems.length) {
                hideSuggestBox();
                return;
            }
            suggestBox.innerHTML = '';
            suggestItems.forEach((item, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bob-suggest-item' + (idx === suggestActive ? ' is-active' : '');
                btn.setAttribute('role', 'option');
                btn.innerHTML = `<span class="bob-suggest-label"></span><span class="bob-suggest-hint"></span>`;
                btn.querySelector('.bob-suggest-label').textContent = item.label || item.query || '';
                btn.querySelector('.bob-suggest-hint').textContent = item.hint || item.type || '';
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    applyChipChoice(item.query || item.label || '', item.mode || 'send');
                    hideSuggestBox();
                });
                suggestBox.appendChild(btn);
            });
            suggestBox.classList.remove('hidden');
        }

        async function fetchSuggestions(term) {
            const seq = ++suggestSeq;
            try {
                const res = await fetch(`/chatbot/suggest?q=${encodeURIComponent(term)}&limit=8`, {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (seq !== suggestSeq) return;
                renderSuggestBox(Array.isArray(data.suggestions) ? data.suggestions : []);
            } catch (e) {
                if (seq === suggestSeq) hideSuggestBox();
            }
        }

        messageInput.addEventListener('input', () => {
            const term = (messageInput.value || '').trim();
            clearTimeout(suggestTimer);
            if (term.length < 2) {
                hideSuggestBox();
                return;
            }
            suggestTimer = setTimeout(() => fetchSuggestions(term), 220);
        });

        messageInput.addEventListener('keydown', (event) => {
            if (suggestBox && !suggestBox.classList.contains('hidden') && suggestItems.length) {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    suggestActive = (suggestActive + 1) % suggestItems.length;
                    [...suggestBox.children].forEach((el, i) => el.classList.toggle('is-active', i === suggestActive));
                    return;
                }
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    suggestActive = (suggestActive - 1 + suggestItems.length) % suggestItems.length;
                    [...suggestBox.children].forEach((el, i) => el.classList.toggle('is-active', i === suggestActive));
                    return;
                }
                if (event.key === 'Escape') {
                    hideSuggestBox();
                    return;
                }
                if (event.key === 'Tab' && suggestActive >= 0) {
                    event.preventDefault();
                    const item = suggestItems[suggestActive];
                    applyChipChoice(item.query || item.label || '', item.mode || 'fill');
                    hideSuggestBox();
                    return;
                }
            }
            if (event.key === 'Enter') {
                hideSuggestBox();
                sendMessage();
            }
        });

        messageInput.addEventListener('blur', () => {
            setTimeout(hideSuggestBox, 180);
        });

        // Sugerencias iniciales: llenan el input (o envían si mode=send).
        document.getElementById('bobChatShell').addEventListener('click', (event) => {
            const chip = event.target.closest('[data-chip]');
            if (!chip) return;
            const text = chip.dataset.chip || '';
            if (!text) return;
            const mode = chip.dataset.chipMode || '';

            if (chip.classList.contains('bob-quick') || chip.classList.contains('bob-suggestion-card')) {
                applyChipChoice(text, mode || 'fill');
                return;
            }

            applyChipChoice(text, mode);
        });

        window.addEventListener('load', () => {
            if (window.matchMedia('(min-width: 640px)').matches) {
                messageInput.focus();
            }
            animateCharacter('idle');
            initVoiceRecognition();
            fitBobChatToKeyboard();
            restoreSessionHistory();
        });

        // Modal de guía de uso (respaldo).
        const guiaModal = document.getElementById('guiaModal');
        const btnGuiaUso = document.getElementById('btnGuiaUso');

        function openGuiaModal() {
            guiaModal.classList.remove('hidden', 'is-closing');
            guiaModal.classList.add('flex');
            if (!document.body.classList.contains('overflow-hidden')) {
                guiaModal.dataset.unlockBody = '1';
                document.body.classList.add('overflow-hidden');
            }
            document.getElementById('guiaModalClose').focus();
        }

        function closeGuiaModal() {
            if (guiaModal.classList.contains('hidden')) return;

            guiaModal.classList.add('is-closing');

            guiaModal.querySelector('.guia-panel').addEventListener('animationend', () => {
                guiaModal.classList.add('hidden');
                guiaModal.classList.remove('flex', 'is-closing');
                if (guiaModal.dataset.unlockBody === '1') {
                    document.body.classList.remove('overflow-hidden');
                    delete guiaModal.dataset.unlockBody;
                }
                btnGuiaUso.focus();
            }, { once: true });
        }

        btnGuiaUso?.addEventListener('click', openGuiaModal);
        document.getElementById('guiaModalClose').addEventListener('click', closeGuiaModal);
        document.getElementById('guiaModalOverlay').addEventListener('click', closeGuiaModal);
        document.addEventListener('click', (event) => {
            const tipLink = event.target.closest('[data-open-tips]');
            if (!tipLink) return;
            openGuiaModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !guiaModal.classList.contains('hidden')) closeGuiaModal();
        });

    </script>
</x-app-layout>
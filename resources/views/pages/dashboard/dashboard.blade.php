<x-app-layout>
    <div id="bobChatPage" class="bg-slate-100 dark:bg-slate-950 flex flex-col min-h-[calc(100dvh-4rem)]">
        <div class="relative flex-1 min-h-0 flex flex-col px-0 sm:px-4 sm:py-4 h-full">
            <div
                class="pointer-events-none absolute inset-0 hidden sm:block opacity-[0.25] dark:opacity-[0.14]"
                style="background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,.22) 1px, transparent 0); background-size: 22px 22px;"></div>

            <div class="relative mx-auto w-full flex-1 min-h-0 flex flex-col h-full">
                <div id="bobChatShell" class="flex-1 min-h-0 flex flex-col rounded-none sm:rounded-3xl bg-white dark:bg-slate-900 border-0 sm:border border-slate-200 dark:border-slate-700 shadow-none sm:shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-2 px-3 py-3 sm:p-5 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shrink-0">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <h1 class="text-base sm:text-xl font-semibold text-slate-900 dark:text-slate-100 truncate">ASISTENTE</h1>
                                <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Conectado
                                </span>
                            </div>
                            <div class="mt-0.5 text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">BOB • v2.1.1</div>
                        </div>

                        <button
                            type="button"
                            id="btnGuiaUso"
                            class="inline-flex items-center gap-1 text-xs sm:text-[14px] font-bold h-8 px-3 sm:px-5 rounded-2xl bg-slate-900 text-white shadow-sm hover:bg-slate-800 active:bg-slate-950 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300 dark:active:bg-amber-500 shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Tips
                        </button>
                    </div>

                    <div
                        id="chatContainer"
                        class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-3 sm:px-5 py-3 sm:py-5 space-y-3 sm:space-y-4 bg-slate-50 dark:bg-slate-950/40"
                        style="-webkit-overflow-scrolling: touch; scroll-behavior: smooth; overscroll-behavior: contain;">
                        <div class="flex items-start gap-3 chat-bubble min-w-0">
                            <div class="hidden sm:flex h-10 w-10 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm items-center justify-center text-slate-700 dark:text-slate-200 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 w-full max-w-full">
                                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                                    <div class="px-3 sm:px-4 py-3 border-l-4 border-amber-400 rounded-2xl">
                                        <div class="text-[13px] sm:text-sm text-slate-900 dark:text-slate-100 leading-relaxed">
                                            <div class="font-semibold text-slate-900 dark:text-slate-100">Hola, soy Bob, asistente del SGC de Proser.</div>
                                            <div class="mt-1 text-slate-700 dark:text-slate-200">
                                                Puedes plantear tu consulta con tus propias palabras. Reviso la información registrada en el SGC: procedimientos, tu puesto y el directorio. Si un dato no está registrado, te lo indico; no invento personas ni folios. ¿En qué puedo orientarte?
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-3 sm:p-4 shrink-0 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                            <div class="flex-1 relative min-w-0">
                                <input
                                    type="text"
                                    id="messageInput"
                                    placeholder="Escribe tu consulta..."
                                    autocomplete="off"
                                    enterkeyhint="send"
                                    class="w-full min-w-0 h-11 sm:h-12 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 sm:px-4 pr-3 sm:pr-14 text-base sm:text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-amber-300" />

                                <button
                                    id="micButton"
                                    type="button"
                                    class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 h-9 w-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm items-center justify-center text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100"
                                    title="Hablar">
                                    <svg id="micIcon" class="w-5 h-5 text-slate-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                    </svg>
                                </button>
                            </div>

                            <button
                                id="sendButton"
                                type="button"
                                class="h-11 w-11 sm:h-12 sm:w-auto sm:px-5 rounded-2xl bg-slate-900 text-white shadow-sm hover:bg-slate-800 active:bg-slate-950 inline-flex items-center justify-center gap-2 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300 dark:active:bg-amber-500 shrink-0"
                                aria-label="Enviar">
                                <span class="hidden sm:inline text-sm font-semibold">Enviar</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Guía de uso --}}
    <div
        id="guiaModal"
        class="hidden fixed inset-0 z-50 items-center justify-center p-3 sm:p-3 lg:pl-[16.5rem] xl:pl-[18.5rem]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="guiaModalTitle">
        <div id="guiaModalOverlay" class="guia-overlay absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div class="guia-panel relative w-full max-w-3xl max-h-[min(90dvh,100%)] flex flex-col rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-lg overflow-hidden">
            <div class="flex items-start justify-between gap-3 px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
                <div class="min-w-0">
                    <h2 id="guiaModalTitle" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Tips</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Cómo sacarle provecho a Bob (rápido)
                    </p>
                </div>
                <button
                    type="button"
                    id="guiaModalClose"
                    class="h-9 w-9 shrink-0 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 transition-colors cursor-pointer"
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
            border: 1px solid #A7A8A9;
            background: #ffffff;
            color: #021D49;
            padding: 0.15rem 0.5rem;
            font-size: 10px;
            font-weight: 600;
        }

        .dark .chip-hint {
            color: #fcd34d;
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
        #bobChatShell {
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        #chatContainer {
            flex: 1 1 auto;
            min-height: 0;
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

        const BASE_PLACEHOLDER = 'Escribe tu consulta...';
        const SESSION_ID = 'sess_' + Math.random().toString(36).substring(2, 15) + '_' + Date.now();

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
            card.className = 'rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-2.5 min-w-0 overflow-hidden';

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
                    pill.className = 'rounded-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2 py-0.5 text-[10px] text-slate-600 dark:text-slate-300';
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
                <div class="hidden sm:flex h-10 w-10 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm items-center justify-center text-slate-700 dark:text-slate-200 flex-shrink-0">
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
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                        <div class="px-3 sm:px-4 py-3 border-l-4 ${borderAccent} rounded-2xl">
                            <div class="prose dark:prose-invert max-w-none text-[13px] sm:text-sm leading-relaxed text-slate-900 dark:text-slate-100">
                                ${renderMarkdownSafe(message)}
                            </div>

                            <div data-doc class="mt-3 min-w-0"></div>

                            <div data-chips class="mt-3 hidden min-w-0"></div>

                            <div class="mt-2 flex items-center justify-between gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="font-mono shrink-0">${time} • ${who}</span>
                                <div data-feedback class="flex items-center gap-2 min-w-0"></div>
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
                chipsBox.className = 'mt-3 pt-3 border-t border-slate-200 dark:border-slate-700';
                chipsBox.classList.remove('hidden');

                const hint = document.createElement('p');
                hint.className = 'chip-hint';
                hint.textContent = 'Continuar con';
                chipsBox.appendChild(hint);

                const row = document.createElement('div');
                row.className = 'flex flex-wrap gap-2';

                meta.chips.forEach(chip => {
                    let label = '';
                    let query = '';
                    if (chip && typeof chip === 'object') {
                        label = String(chip.label ?? chip.text ?? chip.query ?? '').trim();
                        query = String(chip.query ?? chip.label ?? chip.text ?? '').trim();
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
                        messageInput.value = query || label;
                        sendMessage();
                    });
                    row.appendChild(btn);
                });
                chipsBox.appendChild(row);
            }

            // Feedback solo en respuestas con analytics registrado.
            const feedbackBox = wrapper.querySelector('[data-feedback]');
            if (!isUser && meta.analyticsId) {
                const up = document.createElement('button');
                up.type = 'button';
                up.title = 'Respuesta útil';
                up.textContent = 'Útil';
                up.className = 'chat-feedback-btn';
                const down = document.createElement('button');
                down.type = 'button';
                down.title = 'Respuesta no útil';
                down.textContent = 'No útil';
                down.className = 'chat-feedback-btn';
                up.addEventListener('click', () => sendChatFeedback(meta.analyticsId, true, feedbackBox));
                down.addEventListener('click', () => sendChatFeedback(meta.analyticsId, false, feedbackBox));
                feedbackBox.append(up, down);
            }

            chatContainer.appendChild(wrapper);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start gap-2 sm:gap-3 chat-bubble min-w-0 max-w-full';
            typingDiv.innerHTML = `
                <div class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 flex-shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="min-w-0 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm px-3 sm:px-4 py-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex space-x-1 shrink-0">
                            <div class="w-2 h-2 bg-slate-900 dark:bg-slate-100 rounded-full typing-indicator"></div>
                            <div class="w-2 h-2 bg-slate-900 dark:bg-slate-100 rounded-full typing-indicator" style="animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 bg-slate-900 dark:bg-slate-100 rounded-full typing-indicator" style="animation-delay: 0.4s;"></div>
                        </div>
                        <span class="text-slate-700 dark:text-slate-200 text-sm truncate">Buscando en el SGC...</span>
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

        async function sendChatFeedback(analyticsId, helpful, groupEl) {
            try {
                await fetch('/chatbot/feedback', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        analytics_id: analyticsId,
                        helpful
                    }),
                });
            } catch (e) {
                console.error('Error enviando feedback:', e);
            }
            if (groupEl) groupEl.innerHTML = '<span class="text-[10px] text-slate-400">Gracias por tu opinión</span>';
        }

        async function sendMessage() {
            const message = (messageInput.value || '').trim();
            if (!message) return;

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

        window.addEventListener('load', () => {
            if (window.matchMedia('(min-width: 640px)').matches) {
                messageInput.focus();
            }
            animateCharacter('idle');
            initVoiceRecognition();
            fitBobChatToKeyboard();
        });

        sendButton.addEventListener('click', sendMessage);
        micButton.addEventListener('click', toggleVoiceRecognition);

        messageInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') sendMessage();
        });

        // Chips del saludo estático (data-chip): delegación para enviar la consulta.
        chatContainer.addEventListener('click', (event) => {
            const chip = event.target.closest('[data-chip]');
            if (!chip) return;
            messageInput.value = chip.dataset.chip;
            sendMessage();
        });

        // Modal de guía de uso.
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

        btnGuiaUso.addEventListener('click', openGuiaModal);
        document.getElementById('guiaModalClose').addEventListener('click', closeGuiaModal);
        document.getElementById('guiaModalOverlay').addEventListener('click', closeGuiaModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !guiaModal.classList.contains('hidden')) closeGuiaModal();
        });

    </script>
</x-app-layout>
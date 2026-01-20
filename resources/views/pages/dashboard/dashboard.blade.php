<x-app-layout>
    <div class="bg-slate-100 dark:bg-slate-950 min-h-[calc(100dvh-4rem)]">
        <div class="relative px-2 sm:px-4 py-4">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.25] dark:opacity-[0.14]"
                style="background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,.22) 1px, transparent 0); background-size: 22px 22px;"></div>

            <div class="relative mx-auto max-w-7xl">
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-4">
                    <main class="min-h-0">
                        <div class="h-[calc(100dvh-6.5rem)] sm:h-[calc(100dvh-7.5rem)] min-h-[420px] sm:min-h-[520px] flex flex-col rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="flex items-start justify-between p-5 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shrink-0">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h1 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-slate-100 truncate">ASISTENTE</h1>
                                        <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Conectado
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">BOB • v1</div>
                                </div>
                            </div>

                            <div
                                id="chatContainer"
                                class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-5 py-5 space-y-4 bg-slate-50 dark:bg-slate-950/40"
                                style="-webkit-overflow-scrolling: touch; scroll-behavior: smooth; overscroll-behavior: contain;">
                                <div class="flex items-start gap-3 chat-bubble">
                                    <div class="hidden sm:flex h-10 w-10 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm items-center justify-center text-slate-700 dark:text-slate-200 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="w-full">
                                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
                                            <div class="px-4 py-3 border-l-4 border-amber-400 rounded-2xl">
                                                <div class="text-sm text-slate-900 dark:text-slate-100 leading-relaxed">
                                                    <div class="text-slate-900 dark:text-slate-100">Hola! Soy Bob de Proser.</div>
                                                    <div class="mt-1 text-slate-700 dark:text-slate-200">
                                                        ¿Podemos construirlo? Sí podemos. <br />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 relative">
                                        <input
                                            type="text"
                                            id="messageInput"
                                            placeholder="Ingresar comando o consulta..."
                                            class="w-full h-12 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 pr-4 sm:pr-14 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-amber-300" />

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
                                        class="h-12 px-4 sm:px-5 rounded-2xl bg-slate-900 text-white shadow-sm hover:bg-slate-800 active:bg-slate-950 flex items-center gap-2 dark:bg-amber-400 dark:text-slate-950 dark:hover:bg-amber-300 dark:active:bg-amber-500">
                                        <span class="text-sm font-semibold">Enviar</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </main>

                    <aside class="hidden lg:block min-h-0">
                        <div class="lg:sticky lg:top-20">
                            <div class="h-[calc(100dvh-6.5rem)] sm:h-[calc(100dvh-7.5rem)] min-h-[500px] flex flex-col rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                                <div class="bg-amber-400 text-slate-950 font-semibold text-xs tracking-widest px-5 py-3 flex items-center justify-between shrink-0">
                                    <span>BOB PROSER</span>
                                    <div class="h-2 w-2 bg-slate-900 rounded-full"></div>
                                </div>

                                <div class="p-5 space-y-4 overflow-y-auto min-h-0">
                                    <!-- 3D card (más pequeño) -->
                                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 p-3">
                                        <div id="aiCharacter" class="flex items-center justify-center">
                                            <div
                                                id="ai3dModel"
                                                class="relative w-full max-w-[260px] h-40 rounded-2xl overflow-hidden bg-gradient-to-b from-slate-950 to-slate-800 border border-slate-200 dark:border-slate-700">
                                                <model-viewer
                                                    src="{{ asset('images/robot_playground.glb') }}"
                                                    alt="BOB Assistant"
                                                    auto-rotate
                                                    camera-controls
                                                    shadow-intensity="1"
                                                    exposure="0.9"
                                                    environment-image="neutral"
                                                    style="width: 100%; height: 100%; background: transparent;"
                                                    loading="eager"
                                                    reveal="auto"
                                                    animation-name="idle"
                                                    autoplay></model-viewer>

                                                <div class="hidden" id="aiStatusOverlay">
                                                    <span id="overlayStatus">IDLE</span>
                                                    <div id="processingBar" style="width: 20%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="text-xs font-semibold text-slate-900 dark:text-slate-100">ESTADO DEL SISTEMA</div>
                                            <div class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">EN LÍNEA</div>
                                        </div>

                                        <div class="mt-3 space-y-3">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-slate-500 dark:text-slate-400">CPU Load</span>
                                                <span class="text-slate-700 dark:text-slate-200">42%</span>
                                            </div>
                                            <div class="w-full h-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full w-[42%] bg-amber-400"></div>
                                            </div>

                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-slate-500 dark:text-slate-400">Memory Usage</span>
                                                <span class="text-slate-700 dark:text-slate-200">1.2 GB</span>
                                            </div>
                                            <div class="w-full h-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full w-[55%] bg-slate-900 dark:bg-slate-200"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hidden">
                                        <span id="processingStatus">STBY</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
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

        const BASE_PLACEHOLDER = 'Escribe tu consulta de obra...';
        const SESSION_ID = 'dashboard_session_' + Date.now();

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
                    processingStatus.textContent = 'Procesando...';
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
            return DOMPurify.sanitize(html, {
                USE_PROFILES: {
                    html: true
                }
            });
        }

        function addMessage(message, isUser = false) {
            const time = new Date().toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });

            const wrapper = document.createElement('div');
            wrapper.className = `flex items-start gap-3 chat-bubble ${isUser ? 'flex-row-reverse' : ''}`;

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
                <div class="max-w-3xl w-full">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
                        <div class="px-4 py-3 border-l-4 ${borderAccent} rounded-2xl">
                            <div class="prose dark:prose-invert max-w-none text-[13px] sm:text-sm leading-relaxed text-slate-900 dark:text-slate-100">
                                ${renderMarkdownSafe(message)}
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-3 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="font-mono">${time} • ${who}</span>
                                <button
                                    class="text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 transition"
                                    title="Copiar"
                                    type="button"
                                    data-copy="1"
                                >
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const copyBtn = wrapper.querySelector('button[data-copy="1"]');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    const text = wrapper.querySelector('.prose')?.innerText ?? '';
                    navigator.clipboard.writeText(text);
                });
            }

            chatContainer.appendChild(wrapper);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start gap-3 chat-bubble';
            typingDiv.innerHTML = `
                <div class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 flex-shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm px-4 py-3 max-w-md">
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-slate-900 dark:bg-slate-100 rounded-full typing-indicator"></div>
                            <div class="w-2 h-2 bg-slate-900 dark:bg-slate-100 rounded-full typing-indicator" style="animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 bg-slate-900 dark:bg-slate-100 rounded-full typing-indicator" style="animation-delay: 0.4s;"></div>
                        </div>
                        <span class="text-slate-700 dark:text-slate-200 text-sm">Procesando...</span>
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
                        session_id: 'dashboard_session_' + Date.now()
                    }),
                });

                if (response.status === 429) {
                    const errorData = await response.json();
                    return `${errorData.error || 'Límite de consultas alcanzado. Intenta en unos momentos.'}`;
                }

                const data = await response.json();

                if (data?.response) {
                    let methodInfo = '';
                    if (data.method === 'smart_index') {
                        methodInfo = '\n\n<span class="inline-flex items-center rounded-md border border-amber-300/20 bg-amber-400/10 px-2 py-0.5 text-[10px] text-amber-700 font-mono">INDEX</span>';
                    } else if (data.method === 'ollama') {
                        methodInfo = '\n\n<span class="inline-flex items-center rounded-md border border-sky-300/20 bg-sky-400/10 px-2 py-0.5 text-[10px] text-sky-700 font-mono">LLM</span>';
                    } else if (data.method === 'fallback') {
                        methodInfo = '\n\n<span class="inline-flex items-center rounded-md border border-slate-300/20 bg-slate-200/60 px-2 py-0.5 text-[10px] text-slate-700 font-mono">FB</span>';
                    }
                    return data.response + methodInfo;
                }

                if (!response.ok) {
                    if (response.status === 401) {
                        return 'Sesión no válida para este endpoint. Recarga la página.';
                    }
                    throw new Error(`HTTP ${response.status}`);
                }

                throw new Error('No se recibió respuesta válida');
            } catch (error) {
                console.error('Error al obtener respuesta de IA:', error);
                return 'Lo siento, hubo un problema de conexión. Mi sistema de respaldo está procesando tu consulta... ¿Podrías intentar reformular tu pregunta?';
            }
        }

        async function sendMessage() {
            const message = (messageInput.value || '').trim();
            if (!message) return;

            addMessage(message, true);
            messageInput.value = '';

            animateCharacter('thinking');
            showTypingIndicator();

            try {
                const aiResponse = await getAIResponse(message);
                removeTypingIndicator();

                animateCharacter('speaking');
                addMessage(aiResponse, false);

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

        window.addEventListener('load', () => {
            messageInput.focus();
            animateCharacter('idle');
            initVoiceRecognition();
        });

        sendButton.addEventListener('click', sendMessage);
        micButton.addEventListener('click', toggleVoiceRecognition);

        messageInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') sendMessage();
        });
    </script>
</x-app-layout>
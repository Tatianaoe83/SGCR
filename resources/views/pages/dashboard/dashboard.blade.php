<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 relative overflow-y-auto md:overflow-hidden">

        <div class="container mx-auto max-w-8xl min-h-0 md:h-screen flex flex-col md:flex-row lg:flex-row p-1 sm:p-1.5 md:p-4 relative z-10 gap-1 sm:gap-1.5 md:gap-4 md:overflow-hidden">
            <!-- Left Sidebar: AI Control Panel -->
            <div class="w-full md:w-72 lg:w-80 flex flex-row md:flex-col space-x-1.5 md:space-x-0 md:space-y-4 order-2 md:order-1 overflow-x-auto md:overflow-y-auto md:max-h-screen pb-1 md:pb-0 flex-shrink-0">
                <!-- AI Avatar Section -->
                <div class="bg-gradient-to-br from-cyan-900/40 via-blue-900/40 to-purple-900/40 backdrop-blur-xl rounded-lg sm:rounded-xl md:rounded-2xl lg:rounded-3xl p-1.5 sm:p-2 md:p-5 lg:p-6 border border-cyan-400/20 shadow-2xl flex-shrink-0 min-w-[140px] sm:min-w-[160px] md:min-w-0 flex flex-col md:block">
                    <div class="text-center mb-1.5 sm:mb-2 md:mb-4">
                        <div class="inline-flex items-center space-x-1 sm:space-x-1.5 md:space-x-2 bg-cyan-400/20 rounded-full px-1.5 sm:px-2 md:px-4 py-0.5 sm:py-0.5 md:py-2 mb-1 sm:mb-1.5 md:mb-3">
                            <div class="w-1 h-1 sm:w-1.5 md:w-2 sm:h-1 md:h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                            <span class="text-cyan-300 text-[9px] sm:text-[10px] md:text-sm font-mono">ARIA-7</span>
                        </div>
                        <h2 class="text-xs sm:text-sm md:text-xl font-bold bg-gradient-to-r from-cyan-300 to-purple-300 bg-clip-text text-transparent hidden sm:block md:block">Asistente Neural</h2>
                    </div>
                    
                    <!-- 3D Model Container -->
                    <div id="aiCharacter" class="relative flex justify-center mb-1 sm:mb-1.5 md:mb-4">
                        <div id="ai3dModel" class="relative w-20 h-20 sm:w-24 sm:h-24 md:w-48 md:h-48 lg:w-56 lg:h-56 rounded-lg sm:rounded-xl md:rounded-2xl lg:rounded-3xl overflow-hidden border border-cyan-400/30 shadow-2xl bg-gradient-to-br from-cyan-900/20 to-purple-900/20">
                            <model-viewer 
                                src="{{ asset('images/robot_playground.glb') }}" 
                                alt="ARIA-7 Neural Assistant" 
                                auto-rotate 
                                camera-controls 
                                shadow-intensity="1" 
                                exposure="0.8"
                                environment-image="neutral"
                                style="width: 100%; height: 100%; background: transparent;"
                                loading="eager"
                                reveal="auto"
                                animation-name="idle"
                                autoplay>
                            </model-viewer>
                            
                            <!-- Holographic Effects -->
                            <div class="absolute inset-0 pointer-events-none">
                                
                                
                                <!-- Status HUD -->
                                <div id="aiStatusOverlay" class="absolute bottom-0.5 sm:bottom-1 md:bottom-4 left-0.5 sm:left-1 md:left-4 right-0.5 sm:right-1 md:right-4 bg-black/80 backdrop-blur-sm rounded sm:rounded-lg md:rounded-xl p-1 sm:p-1.5 md:p-3 border border-cyan-400/30">
                                    <div class="flex items-center justify-between mb-0.5 sm:mb-1 md:mb-2">
                                        <span class="text-cyan-300 text-[8px] sm:text-[9px] md:text-xs font-mono hidden sm:inline">ESTADO</span>
                                        <span id="overlayStatus" class="text-green-400 text-[8px] sm:text-[9px] md:text-xs font-mono">IDLE</span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-0.5 sm:h-1 md:h-2 overflow-hidden">
                                        <div id="processingBar" class="bg-gradient-to-r from-cyan-400 to-blue-500 h-0.5 sm:h-1 md:h-2 rounded-full transition-all duration-500" style="width: 20%"></div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <!-- System Status Panel -->
                <div class="bg-gradient-to-br from-slate-900/60 via-blue-900/40 to-slate-900/60 backdrop-blur-xl rounded-lg sm:rounded-xl md:rounded-2xl p-1.5 sm:p-2 md:p-5 border border-blue-400/20 flex-shrink-0 min-w-[140px] sm:min-w-[160px] md:min-w-0">
                    <h3 class="text-[10px] sm:text-xs md:text-lg font-semibold text-blue-300 mb-1.5 sm:mb-2 md:mb-4 flex items-center">
                        <svg class="w-3 h-3 sm:w-3.5 md:w-5 sm:h-3 md:h-5 mr-1 sm:mr-1.5 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="hidden sm:inline">Sistema</span>
                        <span class="sm:hidden text-[9px]">Sist.</span>
                    </h3>
                    <div class="space-y-1 sm:space-y-1.5 md:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-[9px] sm:text-[10px] md:text-sm">CPU</span>
                            <div class="flex items-center space-x-0.5 sm:space-x-1 md:space-x-2">
                                <div class="w-8 sm:w-10 md:w-16 h-0.5 sm:h-1 md:h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="w-3/4 h-full bg-gradient-to-r from-green-400 to-emerald-500 rounded-full"></div>
                                </div>
                                <span class="text-green-400 text-[9px] sm:text-[10px] md:text-xs">78%</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-[9px] sm:text-[10px] md:text-sm">Mem</span>
                            <div class="flex items-center space-x-0.5 sm:space-x-1 md:space-x-2">
                                <div class="w-8 sm:w-10 md:w-16 h-0.5 sm:h-1 md:h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="w-1/2 h-full bg-gradient-to-r from-blue-400 to-cyan-500 rounded-full"></div>
                                </div>
                                <span class="text-blue-400 text-[9px] sm:text-[10px] md:text-xs">52%</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-[9px] sm:text-[10px] md:text-sm">Proc</span>
                            <span id="processingStatus" class="text-cyan-400 text-[9px] sm:text-[10px] md:text-xs font-mono">STBY</span>
                        </div>
                    </div>
                </div>

            
            </div>

            <!-- Main Chat Interface -->
            <div class="flex-1 flex flex-col order-1 md:order-2 min-h-0 md:max-h-screen md:overflow-hidden">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-slate-900/80 via-blue-900/60 to-slate-900/80 backdrop-blur-xl rounded-t-lg sm:rounded-t-xl md:rounded-t-2xl lg:rounded-t-3xl p-2 sm:p-2.5 md:p-5 lg:p-6 border-t border-l border-r border-cyan-400/20 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4">
                            <div class="w-9 h-9 sm:w-10 md:w-12 sm:h-9 md:h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg sm:rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 sm:w-5 md:w-6 sm:h-5 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-cyan-300 via-blue-300 to-purple-300 bg-clip-text text-transparent">ARIA Neural Chat</h1>
                                <p class="text-cyan-400 text-[11px] sm:text-xs md:text-sm flex items-center">
                                    <span class="w-1.5 h-1.5 sm:w-1.5 md:w-2 sm:h-1.5 md:h-2 bg-green-400 rounded-full mr-1 sm:mr-1.5 md:mr-2 animate-pulse"></span>
                                    <span class="hidden md:inline">Advanced AI • Quantum Processing v3.0</span>
                                    <span class="md:hidden text-[10px] sm:text-xs">AI Avanzada v3.0</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="chatContainer" class="flex-1 bg-gradient-to-br from-slate-900/40 via-blue-900/20 to-slate-900/40 backdrop-blur-xl p-2 sm:p-2.5 md:p-5 lg:p-6 overflow-y-auto space-y-2 sm:space-y-2.5 md:space-y-4 border-l border-r border-cyan-400/20 min-h-0">
                    <!-- Welcome Message -->
                    <div class="flex items-start space-x-2 sm:space-x-2.5 md:space-x-3 chat-bubble">
                        <div class="w-8 h-8 sm:w-9 md:w-10 sm:h-8 md:h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg sm:rounded-xl md:rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-600/60 via-blue-600/60 to-purple-600/60 backdrop-blur-sm rounded-lg sm:rounded-xl md:rounded-3xl rounded-tl-lg p-2.5 sm:p-3 md:p-5 max-w-[85%] sm:max-w-md md:max-w-lg border border-cyan-400/30 shadow-xl">
                            <p class="text-white leading-relaxed text-sm sm:text-sm md:text-base">¡Sistemas neurales activados! Soy ARIA-7, tu asistente de inteligencia artificial cuántica. Mi red neuronal está optimizada y lista para procesar cualquier consulta con precisión avanzada.</p>
                            <span class="text-cyan-200 text-[10px] sm:text-xs mt-1.5 sm:mt-2 md:mt-3 block font-mono">TIMESTAMP: NOW • NEURAL_RESPONSE</span>
                        </div>
                    </div>
                </div>

                <!-- Input Interface -->
                <div class="chatbot-container bg-gradient-to-r from-slate-900/80 via-blue-900/60 to-slate-900/80 backdrop-blur-xl rounded-b-lg sm:rounded-b-xl md:rounded-b-2xl lg:rounded-b-3xl p-2 sm:p-2.5 md:p-5 lg:p-6 border-b border-l border-r border-cyan-400/20 flex-shrink-0">
                    <div class="flex items-center space-x-1.5 sm:space-x-2 md:space-x-4 mb-1.5 sm:mb-2 md:mb-4">
                        <div class="flex-1 relative">
                            
                            <input 
                                type="text" 
                                id="messageInput" 
                                placeholder="Escribe tu consulta..." 
                                class="w-full px-3 sm:px-3.5 md:px-6 py-2 sm:py-2.5 md:py-4 pr-10 sm:pr-11 md:pr-14 bg-slate-800/50 border border-cyan-400/30 rounded-lg sm:rounded-xl md:rounded-2xl text-white placeholder-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 backdrop-blur-sm shadow-inner text-sm sm:text-sm md:text-base"
                                onkeypress="handleKeyPress(event)"
                            >
                            <button 
                                id="micButton"
                                onclick="toggleVoiceRecognition()"
                                class="absolute right-2.5 sm:right-3 md:right-4 top-1/2 transform -translate-y-1/2 p-1.5 sm:p-1.5 md:p-2 rounded-lg active:bg-cyan-400/30 hover:bg-cyan-400/20 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-cyan-400 touch-manipulation"
                                title="Haz clic para hablar"
                            >
                                <svg id="micIcon" class="w-5 h-5 sm:w-5 md:w-5 sm:h-5 md:h-5 text-cyan-400/60 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                            </button>
                        </div>
                        <button 
                            onclick="sendMessage()" 
                            class="bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 active:from-cyan-700 active:to-blue-800 text-white p-2.5 sm:p-3 md:p-4 rounded-lg sm:rounded-xl md:rounded-2xl transition-all duration-300 transform active:scale-95 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-400 shadow-lg touch-manipulation min-w-[40px] min-h-[40px] sm:min-w-[44px] sm:min-h-[44px] flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 sm:w-5 md:w-5 sm:h-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>


    <script>
        const chatContainer = document.getElementById('chatContainer');
        const messageInput = document.getElementById('messageInput');
        const aiCharacter = document.getElementById('aiCharacter');
        const ai3dModel = document.getElementById('ai3dModel');
        const processingStatus = document.getElementById('processingStatus');
        const overlayStatus = document.getElementById('overlayStatus');
        const processingBar = document.getElementById('processingBar');
        const micButton = document.getElementById('micButton');
        const micIcon = document.getElementById('micIcon');
        let modelViewer = null;
        let recognition = null;
        let isRecording = false;

        // Chat híbrido - Las respuestas se obtienen del backend

        function animateCharacter(state) {
            // Controlar el model-viewer
            if (!modelViewer) {
                modelViewer = ai3dModel.querySelector('model-viewer');
            }

            switch(state) {
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
                    processingStatus.textContent = 'Listo';
                    overlayStatus.textContent = 'IDLE';
                    overlayStatus.className = 'text-blue-400 text-xs font-mono';
                    processingBar.style.width = '20%';
                    break;
            }
        }

        function addMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start space-x-2 sm:space-x-2.5 md:space-x-3 chat-bubble ${isUser ? 'flex-row-reverse space-x-reverse' : ''}`;
            
            const time = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            
            messageDiv.innerHTML = `
                <div class="w-8 h-8 sm:w-9 md:w-10 sm:h-8 md:h-10 ${isUser ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-blue-500 to-purple-600'} rounded-lg sm:rounded-xl md:rounded-2xl flex items-center justify-center flex-shrink-0">
                    ${isUser ? 
                        '<svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>' :
                        '<svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>' 
                    }
                </div>
                <div class="${isUser ? 'bg-gradient-to-r from-green-600/80 to-emerald-600/80' : 'bg-gradient-to-r from-blue-600/80 to-purple-600/80'} backdrop-blur-sm text-white rounded-lg sm:rounded-xl md:rounded-2xl ${isUser ? 'rounded-tr-md' : 'rounded-tl-md'} p-2.5 sm:p-3 md:p-4 max-w-[85%] sm:max-w-sm md:max-w-md border border-white/20">
                    <p class="text-sm sm:text-sm md:text-base leading-relaxed">${message}</p>
                    <span class="text-gray-200 text-[10px] sm:text-xs mt-1 sm:mt-1.5 md:mt-2 block">${time} • ${isUser ? 'Usuario' : 'AI Response'}</span>
                </div>
            `;
            
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start space-x-2 sm:space-x-2.5 md:space-x-3 chat-bubble';
            typingDiv.innerHTML = `
                <div class="w-8 h-8 sm:w-9 md:w-10 sm:h-8 md:h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg sm:rounded-xl md:rounded-2xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="bg-gradient-to-r from-blue-600/60 to-purple-600/60 backdrop-blur-sm rounded-lg sm:rounded-xl md:rounded-2xl rounded-tl-md p-2.5 sm:p-3 md:p-4 max-w-[85%] sm:max-w-sm md:max-w-md border border-white/20">
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-1.5 h-1.5 sm:w-2 md:w-2 sm:h-1.5 md:h-2 bg-white rounded-full typing-indicator"></div>
                            <div class="w-1.5 h-1.5 sm:w-2 md:w-2 sm:h-1.5 md:h-2 bg-white rounded-full typing-indicator" style="animation-delay: 0.2s;"></div>
                            <div class="w-1.5 h-1.5 sm:w-2 md:w-2 sm:h-1.5 md:h-2 bg-white rounded-full typing-indicator" style="animation-delay: 0.4s;"></div>
                        </div>
                        <span class="text-white text-xs sm:text-xs md:text-sm">ARIA está procesando...</span>
                    </div>
                </div>
            `;
            chatContainer.appendChild(typingDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        async function getAIResponse(userMessage) {
            try {
                const response = await fetch('/api/chatbot/query', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: userMessage,
                        session_id: 'dashboard_session_' + Date.now()
                    })
                });

                if (response.status === 429) {
                    const errorData = await response.json();
                    return `⚠️ ${errorData.error || 'Límite de consultas alcanzado. Intenta en unos momentos.'}`;
                }

                const data = await response.json();
                
                if (data.response) {
                    // Agregar información sobre el método usado
                    let methodInfo = '';
                    if (data.method === 'smart_index') {
                        console.log('smart_index');
                        methodInfo = ' 🚀';
                    } else if (data.method === 'ollama') {
                        console.log('ollama');
                        methodInfo = ' 🤖';
                    } else if (data.method === 'fallback') {
                        console.log('fallback');
                        methodInfo = ' ⚡';
                    }
                    
                    return data.response + methodInfo;
                }
                
                throw new Error('No se recibió respuesta válida');
                
            } catch (error) {
                console.error('Error al obtener respuesta de IA:', error);
                return 'Lo siento, hubo un problema de conexión. Mi sistema de respaldo está procesando tu consulta... ¿Podrías intentar reformular tu pregunta?';
            }
        }

        async function sendMessage() {
            const message = messageInput.value.trim();
            if (message === '') return;
            
            // Add user message
            addMessage(message, true);
            messageInput.value = '';
            
            // Animate character thinking
            animateCharacter('thinking');
            
            // Show typing indicator
            showTypingIndicator();
            
            try {
                // Get AI response
                const aiResponse = await getAIResponse(message);
                
                // Remove typing indicator and show response
                removeTypingIndicator();
                animateCharacter('speaking');
                addMessage(aiResponse, false);
                
                // Return to idle state
                setTimeout(() => {
                    animateCharacter('idle');
                }, 2000);
                
            } catch (error) {
                console.error('Error en sendMessage:', error);
                removeTypingIndicator();
                animateCharacter('idle');
                addMessage('Lo siento, hubo un error al procesar tu mensaje. Por favor intenta nuevamente.', false);
            }
        }


        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Voice Recognition Functions
        function initVoiceRecognition() {
            // Verificar si el navegador soporta reconocimiento de voz
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
                micIcon.classList.remove('text-cyan-400/60');
                micIcon.classList.add('text-red-400');
                messageInput.placeholder = 'Escuchando...';
            };

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                messageInput.value = transcript;
                messageInput.placeholder = 'Ingresa tu consulta neural...';
            };

            recognition.onerror = function(event) {
                console.error('Error en reconocimiento de voz:', event.error);
                stopVoiceRecognition();
                
                let errorMessage = 'Error en el micrófono';
                if (event.error === 'no-speech') {
                    errorMessage = 'No se detectó habla. Intenta nuevamente.';
                } else if (event.error === 'not-allowed') {
                    errorMessage = 'Permiso de micrófono denegado. Por favor, permite el acceso al micrófono.';
                } else if (event.error === 'network') {
                    errorMessage = 'Error de red. Verifica tu conexión.';
                }
                
                addMessage('⚠️ ' + errorMessage, false);
            };

            recognition.onend = function() {
                stopVoiceRecognition();
            };
        }

        function toggleVoiceRecognition() {
            if (!recognition) {
                initVoiceRecognition();
                if (!recognition) return;
            }

            if (isRecording) {
                recognition.stop();
            } else {
                try {
                    recognition.start();
                } catch (error) {
                    console.error('Error al iniciar reconocimiento:', error);
                    addMessage('⚠️ No se pudo iniciar el reconocimiento de voz. Verifica los permisos del micrófono.', false);
                }
            }
        }

        function stopVoiceRecognition() {
            isRecording = false;
            micButton.classList.remove('bg-red-500/30', 'animate-pulse');
            micIcon.classList.remove('text-red-400');
            micIcon.classList.add('text-cyan-400/60');
            messageInput.placeholder = 'Ingresa tu consulta neural...';
        }

        // Initialize
        window.onload = function() {
            messageInput.focus();
            animateCharacter('idle');
            initVoiceRecognition();
        };
    </script>
</x-app-layout>
<x-app-layout>
    <div class="h-screen bg-gray-100 relative overflow-hidden">

        <div class="container mx-auto max-w-8xl h-screen flex flex-col md:flex-row lg:flex-row p-0.5 sm:p-1 md:p-4 relative z-10 gap-0.5 sm:gap-1 md:gap-4 overflow-hidden" style="display: flex !important;">
            <!-- Left Sidebar: AI Control Panel -->
            <div class="hidden md:flex md:w-72 lg:w-80 md:flex-col md:space-y-4 md:order-1 md:overflow-y-auto md:max-h-screen md:flex-shrink-0">
                <!-- AI Avatar Section -->
                <div class="bg-gradient-to-br from-cyan-900/40 via-blue-900/40 to-purple-900/40 backdrop-blur-xl rounded-lg sm:rounded-xl md:rounded-2xl lg:rounded-3xl p-1 sm:p-1.5 md:p-5 lg:p-6 border border-cyan-400/20 shadow-2xl flex-shrink-0 min-w-[120px] sm:min-w-[140px] md:min-w-0 flex flex-col md:block">
                    <div class="text-center mb-1 sm:mb-1.5 md:mb-4">
                        <div class="inline-flex items-center space-x-0.5 sm:space-x-1 md:space-x-2 bg-cyan-400/20 rounded-full px-1 sm:px-1.5 md:px-4 py-0.5 sm:py-0.5 md:py-2 mb-0.5 sm:mb-1 md:mb-3">
                            <div class="w-0.5 h-0.5 sm:w-1 md:w-2 sm:h-0.5 md:h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                            <span class="text-cyan-300 text-[8px] sm:text-[9px] md:text-sm font-mono">Proserito</span>
                        </div>
                        <h2 class="text-[10px] sm:text-xs md:text-xl font-bold bg-gradient-to-r from-cyan-300 to-purple-300 bg-clip-text text-transparent hidden sm:block md:block">Asistente Neural</h2>
                    </div>

                <!-- Header with decorative band -->
                <div class="relative mb-4">
                    <div class="h-8 bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-400 border-2 border-black" style="background: repeating-linear-gradient(45deg, #facc15, #facc15 10px, #000 10px, #000 20px);"></div>
                    <div class="absolute -top-2 left-4 w-12 h-12 bg-slate-500 rounded-lg flex items-center justify-center shadow-lg z-10">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="bg-yellow-400 rounded-lg px-4 py-2 mt-2 text-center">
                        <span class="text-white font-bold text-sm">BOB ACTIVO</span>
                    </div>
                </div>

                <!-- AI Avatar Section -->
                <div class="bg-transparent rounded-lg sm:rounded-xl md:rounded-2xl lg:rounded-3xl p-1 sm:p-1.5 md:p-5 lg:p-6 flex-shrink-0 min-w-[120px] sm:min-w-[140px] md:min-w-0 flex flex-col md:block">
                    <!-- 3D Model Container -->
                    <div id="aiCharacter" class="relative flex justify-center mb-0.5 sm:mb-1 md:mb-4">
                        <div id="ai3dModel" class="relative w-16 h-16 sm:w-20 md:w-48 md:h-48 lg:w-56 lg:h-56 rounded-lg sm:rounded-xl md:rounded-2xl lg:rounded-3xl overflow-hidden border border-cyan-400/30 shadow-2xl bg-gradient-to-br from-cyan-900/20 to-purple-900/20">
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
                            
                            <!-- Verification badge -->
                            <div class="absolute bottom-2 right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center shadow-lg z-10">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>

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
                <div class="bg-slate-700 rounded-lg sm:rounded-xl md:rounded-2xl p-1 sm:p-1.5 md:p-5 shadow-lg flex-shrink-0 min-w-[120px] sm:min-w-[140px] md:min-w-0">
                    <div class="flex justify-between items-center mb-1 sm:mb-1.5 md:mb-4">
                        <h3 class="text-[9px] sm:text-[10px] md:text-lg font-semibold text-white">
                            <span class="hidden sm:inline">ESTADO DEL SISTEMA</span>
                            <span class="sm:hidden text-[9px]">ESTADO</span>
                        </h3>
                        <span class="bg-yellow-400 text-black text-[8px] sm:text-[9px] md:text-xs font-bold px-2 py-1 rounded">EN LÍNEA</span>
                    </div>
                    <div class="space-y-0.5 sm:space-y-1 md:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-white text-[8px] sm:text-[9px] md:text-sm">CPU</span>
                            <div class="flex-1 ml-2">
                                <div class="w-full h-2 sm:h-2.5 md:h-3 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-400 rounded-full" style="width: 65%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-white text-[8px] sm:text-[9px] md:text-sm">MEM</span>
                            <div class="flex-1 ml-2">
                                <div class="w-full h-2 sm:h-2.5 md:h-3 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-400 rounded-full" style="width: 45%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Panel -->
                <div class="bg-white rounded-lg sm:rounded-xl md:rounded-2xl p-1 sm:p-1.5 md:p-5 shadow-lg flex-shrink-0 min-w-[120px] sm:min-w-[140px] md:min-w-0 flex items-center justify-between">
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <svg class="w-4 h-4 sm:w-5 md:w-6 sm:h-4 md:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-[9px] sm:text-[10px] md:text-base font-bold text-black"></h3>
                            <p class="text-gray-500 text-[8px] sm:text-[9px] md:text-xs">CONEXIONES</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 sm:w-5 md:w-6 sm:h-4 md:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

            
            </div>

            <!-- Main Chat Interface -->
            <div class="flex-1 flex flex-col min-h-0 h-full overflow-hidden" style="display: flex !important; flex-direction: column !important;">
                <!-- Chat Header -->
                <div class="bg-white rounded-t-lg sm:rounded-t-xl md:rounded-t-2xl lg:rounded-t-3xl p-1.5 sm:p-2 md:p-5 lg:p-6 shadow-lg flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-1.5 sm:space-x-2 md:space-x-4 flex-1 min-w-0">
                            <div class="w-8 h-8 sm:w-9 md:w-12 sm:h-8 md:h-12 bg-slate-500 rounded-lg sm:rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 md:w-6 sm:h-4 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h1 class="text-sm sm:text-lg md:text-2xl font-bold text-black truncate">ASISTENTE</h1>
                                <p class="text-gray-500 text-[10px] sm:text-xs md:text-sm flex items-center">
                                    <span class="hidden md:inline truncate">BOB AI CONNECTED</span>
                                    <span class="md:hidden text-[9px] sm:text-xs truncate">BOB AI</span>
                                    <span class="w-1.5 h-1.5 sm:w-2 md:w-2.5 sm:h-1.5 md:h-2.5 bg-yellow-400 rounded-full mr-1 sm:mr-1.5 md:mr-2 ml-1 sm:ml-1.5 md:ml-2 flex-shrink-0"></span>
                                <!-- <h1 class="text-sm sm:text-lg md:text-2xl font-bold bg-gradient-to-r from-cyan-300 via-blue-300 to-purple-300 bg-clip-text text-transparent truncate">ARIA Neural Chat</h1> -->
                                <p class="text-cyan-400 text-[10px] sm:text-xs md:text-sm flex items-center">
                                    <span class="w-1 h-1 sm:w-1.5 md:w-2 sm:h-1 md:h-2 bg-green-400 rounded-full mr-1 sm:mr-1.5 md:mr-2 animate-pulse flex-shrink-0"></span>
                                    <span class="hidden md:inline truncate">IA Avanzada</span>
                                    <span class="md:hidden text-[9px] sm:text-xs truncate">AI Avanzada v3.0</span>
                                </p>
                            </div>
                        </div>
                        <!-- Logo Proser en móvil (versión pequeña) -->
                        <div class="flex-shrink-0 sm:hidden ml-1">
                            <img src="{{ asset('images/Logo-blanco.png') }}" alt="Proser" class="h-5 w-auto object-contain opacity-80">
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="chatContainer" class="flex-1 bg-white px-1.5 pt-1.5 pb-0 sm:px-2 sm:pt-2 sm:pb-0 md:px-5 md:pt-5 md:pb-0 lg:px-6 lg:pt-6 lg:pb-0 overflow-y-auto space-y-1.5 sm:space-y-2 md:space-y-4 min-h-0 overscroll-contain" style="flex: 1 1 auto; min-height: 0;">
                    <!-- Welcome Message -->
                    <div class="flex items-start space-x-2 sm:space-x-2.5 md:space-x-3 chat-bubble">
                        <div class="w-8 h-8 sm:w-9 md:w-10 sm:h-8 md:h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="bg-white rounded-lg sm:rounded-xl md:rounded-2xl p-2.5 sm:p-3 md:p-5 max-w-[85%] sm:max-w-md md:max-w-lg border-l-4 border-yellow-400 shadow-md relative">
                            <p class="text-gray-800 leading-relaxed text-sm sm:text-sm md:text-base">¡Hola! Soy <strong>Bob</strong>. ¿Podemos construirlo? ¡Sí podemos! ¿Qué reporte o consulta de obra deseas procesar hoy?</p>
                            <span class="text-gray-400 text-[10px] sm:text-xs mt-1.5 sm:mt-2 md:mt-3 block">TIMESTAMP: 08:00 AM</span>
                        <div class="bg-gradient-to-br from-cyan-600/60 via-blue-600/60 to-purple-600/60 backdrop-blur-sm rounded-lg sm:rounded-xl md:rounded-3xl rounded-tl-lg p-2.5 sm:p-3 md:p-5 max-w-[85%] sm:max-w-md md:max-w-lg border border-cyan-400/30 shadow-xl">
                            <p class="text-white leading-relaxed text-sm sm:text-sm md:text-base">¡Hola! Soy Proserito, tu asistente de inteligencia artificial. Estoy listo para resolver tu duda.</p>
                            <!--  <span class="text-cyan-200 text-[10px] sm:text-xs mt-1.5 sm:mt-2 md:mt-3 block font-mono">Ahora</span> -->
                        </div>
                    </div>
                </div>

                <!-- Input Interface -->
                <div class="chatbot-container bg-white rounded-b-lg sm:rounded-b-xl md:rounded-b-2xl lg:rounded-b-3xl px-1.5 pt-0 pb-1.5 sm:px-2 sm:pt-0 sm:pb-2 md:px-5 md:pt-0 md:pb-5 lg:px-6 lg:pt-0 lg:pb-6 shadow-lg flex-shrink-0 z-20 relative" style="display: block !important; visibility: visible !important; opacity: 1 !important;">
                    <div class="flex items-center space-x-1 sm:space-x-1.5 md:space-x-4 mb-1 sm:mb-1.5 md:mb-4">
                        <div class="flex-1 relative flex items-center">
                            <div class="absolute left-2 sm:left-3 md:left-4 w-6 h-6 sm:w-7 md:w-8 sm:h-6 md:h-8 bg-yellow-400 rounded flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="messageInput" 
                                placeholder="Ingresar comando o consulta..." 
                                class="w-full pl-10 sm:pl-12 md:pl-16 pr-20 sm:pr-24 md:pr-28 py-1.5 sm:py-2 md:py-4 bg-white border border-gray-300 rounded-lg sm:rounded-xl md:rounded-2xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 shadow-inner text-xs sm:text-sm md:text-base"
                                onkeypress="handleKeyPress(event)"
                            >
                            <button 
                                id="micButton"
                                onclick="toggleVoiceRecognition()"
                                class="absolute right-12 sm:right-14 md:right-20 top-1/2 transform -translate-y-1/2 p-1 sm:p-1.5 md:p-2 rounded-lg active:bg-gray-200 hover:bg-gray-100 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-yellow-400 touch-manipulation"
                                title="Haz clic para hablar"
                            >
                                <svg id="micIcon" class="w-4 h-4 sm:w-4 md:w-5 sm:h-4 md:h-5 text-gray-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <div class="flex-1 relative">

                            <input
                                type="text"
                                id="messageInput"
                                placeholder="Escribe tu consulta..."
                                class="w-full px-2 sm:px-3 md:px-6 py-1.5 sm:py-2 md:py-4 pr-8 sm:pr-10 md:pr-14 bg-slate-800/50 border border-cyan-400/30 rounded-lg sm:rounded-xl md:rounded-2xl text-white placeholder-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 backdrop-blur-sm shadow-inner text-xs sm:text-sm md:text-base"
                                onkeypress="handleKeyPress(event)">
                            <button
                                id="micButton"
                                onclick="toggleVoiceRecognition()"
                                class="absolute right-2 sm:right-2.5 md:right-4 top-1/2 transform -translate-y-1/2 p-1 sm:p-1.5 md:p-2 rounded-lg active:bg-cyan-400/30 hover:bg-cyan-400/20 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-cyan-400 touch-manipulation"
                                title="Haz clic para hablar">
                                <svg id="micIcon" class="w-4 h-4 sm:w-4 md:w-5 sm:h-4 md:h-5 text-cyan-400/60 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                            </button>
                        </div>
                        <button 
                            onclick="sendMessage()" 
                            class="bg-slate-500 hover:bg-slate-600 active:bg-slate-700 text-white p-2 sm:p-2.5 md:p-4 rounded-lg sm:rounded-xl md:rounded-2xl transition-all duration-300 transform active:scale-95 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-yellow-400 shadow-lg touch-manipulation min-w-[36px] min-h-[36px] sm:min-w-[40px] sm:min-h-[40px] md:min-w-[44px] md:min-h-[44px] flex items-center justify-center"
                        >
                        <button
                            onclick="sendMessage()"
                            class="bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 active:from-cyan-700 active:to-blue-800 text-white p-2 sm:p-2.5 md:p-4 rounded-lg sm:rounded-xl md:rounded-2xl transition-all duration-300 transform active:scale-95 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-400 shadow-lg touch-manipulation min-w-[36px] min-h-[36px] sm:min-w-[40px] sm:min-h-[40px] md:min-w-[44px] md:min-h-[44px] flex items-center justify-center">
                            <svg class="w-4 h-4 sm:w-4 md:w-5 sm:h-4 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <style>
        /* Asegurar que el contenedor de chat tenga scroll independiente */
        #chatContainer {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
            overscroll-behavior: contain;
        }

        /* Prevenir scroll del body en móviles */
        body {
            overflow: hidden;
            height: 100vh;
            position: fixed;
            width: 100%;
        }

        /* Asegurar que el input se mantenga visible siempre */
        .chatbot-container {
            position: relative;
            background: inherit;
            flex-shrink: 0 !important;
            min-height: fit-content;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
        }

        /* Garantizar que el input siempre esté visible en móvil */
        @media (max-width: 768px) {
            .chatbot-container {
                position: relative !important;
                bottom: auto !important;
                width: 100% !important;
                z-index: 999 !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                min-height: 60px !important;
                max-height: none !important;
                overflow: visible !important;
            }

            /* Asegurar que el contenedor principal no corte el input */
            .flex.flex-col.min-h-0 {
                overflow: visible !important;
            }

            /* Asegurar que el chatContainer no ocupe todo el espacio */
            #chatContainer {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                max-height: calc(100vh - 200px) !important;
            }
        }

        /* Mejorar el scroll en móviles */
        @media (max-width: 768px) {
            #chatContainer {
                -webkit-overflow-scrolling: touch;
                overscroll-behavior-y: contain;
            }

            /* Optimizar espacios en móvil */
            .container {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }

            /* Reducir tamaño de burbujas de chat en móvil */
            .chat-bubble {
                margin-bottom: 0.5rem;
            }

            /* Ajustar mensajes para mejor legibilidad */
            .chat-bubble p {
                line-height: 1.4;
            }
        }

        /* Forzar visibilidad del input */
        #messageInput {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
            height: auto !important;
        }

        .chatbot-container input,
        .chatbot-container button {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* En móvil, forzar aún más la visibilidad */
        @media (max-width: 768px) {
            #messageInput {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                min-height: 40px !important;
                position: relative !important;
            }

            .chatbot-container input,
            .chatbot-container button {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            .chatbot-container>div {
                display: flex !important;
                visibility: visible !important;
            }
        }

        /* Asegurar que el logo se vea bien */
        img[alt="Proser"] {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        /* Estilos adicionales para el diseño tipo Bob */
        .chat-bubble .bg-white {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Ajustar el contenedor principal para mejor espaciado */
        .container.mx-auto {
            gap: 1rem;
        }

        /* Mejorar la apariencia de las barras de progreso */
        .bg-yellow-400 {
            box-shadow: 0 0 4px rgba(250, 204, 21, 0.5);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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

            const time = new Date().toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });

            messageDiv.innerHTML = `
                <div class="w-8 h-8 sm:w-9 md:w-10 sm:h-8 md:h-10 ${isUser ? 'bg-green-500' : 'bg-blue-500'} rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                    ${isUser ? 
                        '<svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>' :
                        '<svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>' 
                <div class="group flex items-start gap-2 sm:gap-3 md:gap-4 chat-bubble ${isUser ? 'flex-row-reverse' : ''}">

                    <!-- Avatar -->
                    <div class="
                    relative w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11
                    rounded-xl md:rounded-2xl
                    flex items-center justify-center
                    shadow-lg ring-1 ring-white/20
                    ${isUser
                        ? 'bg-gradient-to-br from-emerald-500 to-green-600'
                        : 'bg-gradient-to-br from-blue-500 to-purple-600'}
                    ">
                    ${isUser
                        ? `
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>`
                        : `
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>`
                    }
                    </div>

                    <!-- Bubble -->
                    <div class="
                    relative max-w-[90%] sm:max-w-[75%] md:max-w-[65%]
                    px-3 py-2.5 sm:px-4 sm:py-3 md:px-5 md:py-4
                    rounded-2xl md:rounded-3xl
                    border border-white/15
                    shadow-xl backdrop-blur-xl
                    transition-all duration-200 ease-out
                    group-hover:shadow-2xl
                    ${isUser
                        ? 'bg-gradient-to-br from-emerald-600/80 to-green-600/70 rounded-tr-md'
                        : 'bg-gradient-to-br from-blue-600/80 to-purple-600/70 rounded-tl-md'}
                    ">

                    <!-- Accent line -->
                    <span class="
                        absolute top-0 ${isUser ? 'right-3' : 'left-3'}
                        h-1 w-10 rounded-full
                        ${isUser ? 'bg-emerald-300/70' : 'bg-cyan-300/70'}
                    "></span>

                    <!-- Content -->
                    <div class="prose prose-invert max-w-none text-[13px] sm:text-sm md:text-base leading-relaxed">
                        ${marked.parse(message)}
                    </div>

                    <!-- Footer -->
                    <div class="mt-2 flex items-center justify-between gap-3 opacity-70 text-[10px] sm:text-xs">
                        <span class="font-mono">
                        ${time} • ${isUser ? 'Usuario' : 'ARIA-7'}
                        </span>

                        <!-- Actions (solo hover) -->
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            class="hover:text-cyan-300 transition"
                            title="Copiar"
                            onclick="navigator.clipboard.writeText(this.closest('.chat-bubble').innerText)">
                            ⧉
                        </button>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="${isUser ? 'bg-green-500 text-white' : 'bg-white border-l-4 border-yellow-400'} rounded-lg sm:rounded-xl md:rounded-2xl ${isUser ? 'rounded-tr-md' : 'rounded-tl-md'} p-2.5 sm:p-3 md:p-4 max-w-[85%] sm:max-w-sm md:max-w-md shadow-md">
                    <p class="text-sm sm:text-sm md:text-base leading-relaxed ${isUser ? 'text-white' : 'text-gray-800'}">${message}</p>
                    <span class="${isUser ? 'text-gray-200' : 'text-gray-400'} text-[10px] sm:text-xs mt-1 sm:mt-1.5 md:mt-2 block">${time} • ${isUser ? 'Usuario' : 'AI Response'}</span>
                </div>
            `;
            
                `;

            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start space-x-2 sm:space-x-2.5 md:space-x-3 chat-bubble';
            typingDiv.innerHTML = `
                <div class="w-8 h-8 sm:w-9 md:w-10 sm:h-8 md:h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                    <svg class="w-4 h-4 sm:w-5 md:w-5 sm:h-4 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="bg-white border-l-4 border-yellow-400 rounded-lg sm:rounded-xl md:rounded-2xl rounded-tl-md p-2.5 sm:p-3 md:p-4 max-w-[85%] sm:max-w-sm md:max-w-md shadow-md">
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-1.5 h-1.5 sm:w-2 md:w-2 sm:h-1.5 md:h-2 bg-gray-600 rounded-full typing-indicator"></div>
                            <div class="w-1.5 h-1.5 sm:w-2 md:w-2 sm:h-1.5 md:h-2 bg-gray-600 rounded-full typing-indicator" style="animation-delay: 0.2s;"></div>
                            <div class="w-1.5 h-1.5 sm:w-2 md:w-2 sm:h-1.5 md:h-2 bg-gray-600 rounded-full typing-indicator" style="animation-delay: 0.4s;"></div>
                        </div>
                        <span class="text-gray-800 text-xs sm:text-xs md:text-sm">Bob está procesando...</span>
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
                    credentials: 'include',
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
                messageInput.placeholder = 'Ingresar comando o consulta...';
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
            messageInput.placeholder = 'Ingresar comando o consulta...';
        }

        // Initialize
        window.onload = function() {
            messageInput.focus();
            animateCharacter('idle');
            initVoiceRecognition();
        };

        marked.setOptions({
            breaks: true,
            gfm: true,
        });
    </script>
</x-app-layout>
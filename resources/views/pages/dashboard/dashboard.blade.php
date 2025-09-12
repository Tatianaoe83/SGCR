<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 relative overflow-hidden">
        <!-- Neural Network Background -->
        <div class="absolute inset-0 pointer-events-none opacity-10">
            <svg width="100%" height="100%" viewBox="0 0 1000 1000">
                <defs>
                    <linearGradient id="neuralGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.3" />
                        <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.3" />
                    </linearGradient>
                </defs>
                <circle cx="100" cy="100" r="3" fill="url(#neuralGrad)"/>
                <circle cx="300" cy="200" r="3" fill="url(#neuralGrad)"/>
                <circle cx="500" cy="150" r="3" fill="url(#neuralGrad)"/>
                <circle cx="700" cy="300" r="3" fill="url(#neuralGrad)"/>
                <circle cx="200" cy="400" r="3" fill="url(#neuralGrad)"/>
                <circle cx="600" cy="500" r="3" fill="url(#neuralGrad)"/>
                <line x1="100" y1="100" x2="300" y2="200" stroke="url(#neuralGrad)" stroke-width="1"/>
                <line x1="300" y1="200" x2="500" y2="150" stroke="url(#neuralGrad)" stroke-width="1"/>
                <line x1="500" y1="150" x2="700" y2="300" stroke="url(#neuralGrad)" stroke-width="1"/>
                <line x1="200" y1="400" x2="600" y2="500" stroke="url(#neuralGrad)" stroke-width="1"/>
            </svg>
        </div>

        <div class="container mx-auto max-w-8xl h-screen flex flex-col lg:flex-row p-2 sm:p-4 relative z-10 gap-2 sm:gap-4">
            <!-- Left Sidebar: AI Control Panel -->
            <div class="w-full lg:w-80 flex flex-col space-y-2 sm:space-y-4 order-2 lg:order-1">
                <!-- AI Avatar Section -->
                <div class="bg-gradient-to-br from-cyan-900/40 via-blue-900/40 to-purple-900/40 backdrop-blur-xl rounded-2xl lg:rounded-3xl p-3 sm:p-6 border border-cyan-400/20 shadow-2xl">
                    <div class="text-center mb-3 sm:mb-4">
                        <div class="inline-flex items-center space-x-2 bg-cyan-400/20 rounded-full px-3 sm:px-4 py-1 sm:py-2 mb-2 sm:mb-3">
                            <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-cyan-400 rounded-full animate-pulse"></div>
                            <span class="text-cyan-300 text-xs sm:text-sm font-mono">ARIA-7 ONLINE</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold bg-gradient-to-r from-cyan-300 to-purple-300 bg-clip-text text-transparent">Asistente Neural</h2>
                    </div>
                    
                    <!-- 3D Model Container -->
                    <div id="aiCharacter" class="relative flex justify-center mb-3 sm:mb-4">
                        <div id="ai3dModel" class="relative w-40 h-40 sm:w-48 sm:h-48 lg:w-56 lg:h-56 rounded-2xl lg:rounded-3xl overflow-hidden border border-cyan-400/30 shadow-2xl bg-gradient-to-br from-cyan-900/20 to-purple-900/20">
                            <iframe 
                                id="sketchfabViewer"
                                title="ARIA AI Robot" 
                                frameborder="0" 
                                allowfullscreen 
                                mozallowfullscreen="true" 
                                webkitallowfullscreen="true" 
                                allow="autoplay; fullscreen; xr-spatial-tracking" 
                                xr-spatial-tracking 
                                execution-while-out-of-viewport 
                                execution-while-not-rendered 
                                web-share 
                                src="https://sketchfab.com/models/06d5a80a4fc74c0ab3abc7e47c9b1d8e/embed?autostart=1&ui_controls=0&ui_infos=0&ui_inspector=0&ui_stop=0&ui_watermark=0&ui_ar=0&ui_help=0&ui_settings=0&ui_vr=0&ui_fullscreen=0&ui_annotations=0&camera=0&preload=1"
                                class="w-full h-full">
                            </iframe>
                            
                            <!-- Holographic Effects -->
                            <div class="absolute inset-0 pointer-events-none">
                                <!-- Hexagonal Grid Overlay -->
                                <div class="absolute inset-0 opacity-20">
                                    <svg class="w-full h-full" viewBox="0 0 100 100">
                                        <defs>
                                            <pattern id="hexGrid" x="0" y="0" width="10" height="10" patternUnits="userSpaceOnUse">
                                                <polygon points="5,1 9,3 9,7 5,9 1,7 1,3" fill="none" stroke="cyan" stroke-width="0.5"/>
                                            </pattern>
                                        </defs>
                                        <rect width="100%" height="100%" fill="url(#hexGrid)"/>
                                    </svg>
                                </div>
                                
                                <!-- Scanning Effect -->
                                <div class="absolute inset-0 overflow-hidden rounded-3xl">
                                    <div class="scan-line absolute w-full h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent opacity-80"></div>
                                </div>
                                
                                <!-- Corner HUD Elements -->
                                <div class="absolute top-3 left-3 w-8 h-8">
                                    <div class="absolute top-0 left-0 w-4 h-1 bg-cyan-400"></div>
                                    <div class="absolute top-0 left-0 w-1 h-4 bg-cyan-400"></div>
                                </div>
                                <div class="absolute top-3 right-3 w-8 h-8">
                                    <div class="absolute top-0 right-0 w-4 h-1 bg-cyan-400"></div>
                                    <div class="absolute top-0 right-0 w-1 h-4 bg-cyan-400"></div>
                                </div>
                                <div class="absolute bottom-3 left-3 w-8 h-8">
                                    <div class="absolute bottom-0 left-0 w-4 h-1 bg-cyan-400"></div>
                                    <div class="absolute bottom-0 left-0 w-1 h-4 bg-cyan-400"></div>
                                </div>
                                <div class="absolute bottom-3 right-3 w-8 h-8">
                                    <div class="absolute bottom-0 right-0 w-4 h-1 bg-cyan-400"></div>
                                    <div class="absolute bottom-0 right-0 w-1 h-4 bg-cyan-400"></div>
                                </div>
                                
                                <!-- Status HUD -->
                                <div id="aiStatusOverlay" class="absolute bottom-2 sm:bottom-4 left-2 sm:left-4 right-2 sm:right-4 bg-black/80 backdrop-blur-sm rounded-lg sm:rounded-xl p-2 sm:p-3 border border-cyan-400/30">
                                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                                        <span class="text-cyan-300 text-xs font-mono">ESTADO NEURAL</span>
                                        <span id="overlayStatus" class="text-green-400 text-xs font-mono">IDLE</span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-1.5 sm:h-2 overflow-hidden">
                                        <div id="processingBar" class="bg-gradient-to-r from-cyan-400 to-blue-500 h-1.5 sm:h-2 rounded-full transition-all duration-500" style="width: 20%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Floating Particles -->
                            <div class="floating-particles absolute inset-0 pointer-events-none">
                                <div class="particle-3d" style="top: 15%; left: 10%; --random-x: 30px; --random-y: -40px; animation-delay: 0s;"></div>
                                <div class="particle-3d" style="top: 40%; right: 15%; --random-x: -25px; --random-y: -30px; animation-delay: 1.2s;"></div>
                                <div class="particle-3d" style="bottom: 25%; left: 20%; --random-x: 35px; --random-y: -45px; animation-delay: 2.1s;"></div>
                                <div class="particle-3d" style="top: 75%; right: 25%; --random-x: -30px; --random-y: -35px; animation-delay: 1.8s;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status Panel -->
                <div class="bg-gradient-to-br from-slate-900/60 via-blue-900/40 to-slate-900/60 backdrop-blur-xl rounded-xl sm:rounded-2xl p-3 sm:p-5 border border-blue-400/20">
                    <h3 class="text-base sm:text-lg font-semibold text-blue-300 mb-3 sm:mb-4 flex items-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Sistema Neural
                    </h3>
                    <div class="space-y-2 sm:space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-xs sm:text-sm">CPU Neural</span>
                            <div class="flex items-center space-x-1 sm:space-x-2">
                                <div class="w-12 sm:w-16 h-1.5 sm:h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="w-3/4 h-full bg-gradient-to-r from-green-400 to-emerald-500 rounded-full"></div>
                                </div>
                                <span class="text-green-400 text-xs">78%</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-xs sm:text-sm">Memoria IA</span>
                            <div class="flex items-center space-x-1 sm:space-x-2">
                                <div class="w-12 sm:w-16 h-1.5 sm:h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="w-1/2 h-full bg-gradient-to-r from-blue-400 to-cyan-500 rounded-full"></div>
                                </div>
                                <span class="text-blue-400 text-xs">52%</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-xs sm:text-sm">Procesamiento</span>
                            <span id="processingStatus" class="text-cyan-400 text-xs font-mono">STANDBY</span>
                        </div>
                    </div>
                </div>

            
            </div>

            <!-- Main Chat Interface -->
            <div class="flex-1 flex flex-col order-1 lg:order-2">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-slate-900/80 via-blue-900/60 to-slate-900/80 backdrop-blur-xl rounded-t-2xl lg:rounded-t-3xl p-3 sm:p-6 border-t border-l border-r border-cyan-400/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 sm:space-x-4">
                            <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg sm:text-2xl font-bold bg-gradient-to-r from-cyan-300 via-blue-300 to-purple-300 bg-clip-text text-transparent">ARIA Neural Chat</h1>
                                <p class="text-cyan-400 text-xs sm:text-sm flex items-center">
                                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-green-400 rounded-full mr-1 sm:mr-2 animate-pulse"></span>
                                    <span class="hidden sm:inline">Advanced AI • Quantum Processing v3.0</span>
                                    <span class="sm:hidden">AI Avanzada v3.0</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right hidden sm:block">
                            <div class="text-cyan-300 text-sm font-mono">SESIÓN: #A7X9</div>
                            <div class="text-gray-400 text-xs">Canal Encriptado</div>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="chatContainer" class="flex-1 bg-gradient-to-br from-slate-900/40 via-blue-900/20 to-slate-900/40 backdrop-blur-xl p-3 sm:p-6 overflow-y-auto space-y-3 sm:space-y-4 border-l border-r border-cyan-400/20">
                    <!-- Welcome Message -->
                    <div class="flex items-start space-x-2 sm:space-x-3 chat-bubble">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-600/60 via-blue-600/60 to-purple-600/60 backdrop-blur-sm rounded-2xl sm:rounded-3xl rounded-tl-lg p-3 sm:p-5 max-w-xs sm:max-w-lg border border-cyan-400/30 shadow-xl">
                            <p class="text-white leading-relaxed text-sm sm:text-base">¡Sistemas neurales activados! Soy ARIA-7, tu asistente de inteligencia artificial cuántica. Mi red neuronal está optimizada y lista para procesar cualquier consulta con precisión avanzada.</p>
                            <span class="text-cyan-200 text-xs mt-2 sm:mt-3 block font-mono">TIMESTAMP: NOW • NEURAL_RESPONSE</span>
                        </div>
                    </div>
                </div>

                <!-- Input Interface -->
                <div class="bg-gradient-to-r from-slate-900/80 via-blue-900/60 to-slate-900/80 backdrop-blur-xl rounded-b-2xl lg:rounded-b-3xl p-3 sm:p-6 border-b border-l border-r border-cyan-400/20">
                    <div class="flex items-center space-x-2 sm:space-x-4 mb-3 sm:mb-4">
                        <div class="flex-1 relative">
                            <input 
                                type="text" 
                                id="messageInput" 
                                placeholder="Ingresa tu consulta neural..." 
                                class="w-full px-4 sm:px-6 py-3 sm:py-4 bg-slate-800/50 border border-cyan-400/30 rounded-xl sm:rounded-2xl text-white placeholder-cyan-300/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400 backdrop-blur-sm shadow-inner text-sm sm:text-base"
                                onkeypress="handleKeyPress(event)"
                            >
                            <div class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-cyan-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                            </div>
                        </div>
                        <button 
                            onclick="sendMessage()" 
                            class="bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white p-3 sm:p-4 rounded-xl sm:rounded-2xl transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-cyan-400 shadow-lg"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .chat-bubble {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .typing-indicator {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }
        .ai-character {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .ai-eyes {
            animation: blink 4s infinite;
        }
        @keyframes blink {
            0%, 90%, 100% { transform: scaleY(1); }
            95% { transform: scaleY(0.1); }
        }
        .thinking {
            animation: thinking 1s ease-in-out infinite alternate;
        }
        @keyframes thinking {
            0% { transform: rotate(-5deg); }
            100% { transform: rotate(5deg); }
        }
        .ai-glow {
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
            animation: glow 2s ease-in-out infinite alternate;
        }
        @keyframes glow {
            0% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }
            100% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.6); }
        }
        .neural-network {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: 0.1;
        }
        .ai-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: linear-gradient(45deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            animation: particle-float 3s infinite ease-in-out;
        }
        @keyframes particle-float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }
        .ai-3d-container {
            perspective: 1000px;
            transform-style: preserve-3d;
        }
        .ai-3d-model {
            width: 120px;
            height: 120px;
            position: relative;
            transform-style: preserve-3d;
            animation: rotate3d 10s infinite linear;
        }
        @keyframes rotate3d {
            0% { transform: rotateX(0deg) rotateY(0deg); }
            25% { transform: rotateX(15deg) rotateY(90deg); }
            50% { transform: rotateX(0deg) rotateY(180deg); }
            75% { transform: rotateX(-15deg) rotateY(270deg); }
            100% { transform: rotateX(0deg) rotateY(360deg); }
        }
        .ai-sphere {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #3b82f6, #8b5cf6, #ec4899, #10b981, #3b82f6);
            position: relative;
            box-shadow: 
                0 0 30px rgba(59, 130, 246, 0.5),
                inset 0 0 30px rgba(255, 255, 255, 0.1);
        }
        .ai-core {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            height: 60%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(59,130,246,0.6) 50%, transparent 100%);
            animation: pulse-core 2s ease-in-out infinite alternate;
        }
        @keyframes pulse-core {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 0.8; }
            100% { transform: translate(-50%, -50%) scale(1.1); opacity: 1; }
        }
        .ai-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: ring-rotate 3s linear infinite;
        }
        .ai-ring:nth-child(1) { width: 130px; height: 130px; animation-delay: 0s; }
        .ai-ring:nth-child(2) { width: 150px; height: 150px; animation-delay: -1s; }
        .ai-ring:nth-child(3) { width: 170px; height: 170px; animation-delay: -2s; }
        @keyframes ring-rotate {
            0% { transform: translate(-50%, -50%) rotateZ(0deg); }
            100% { transform: translate(-50%, -50%) rotateZ(360deg); }
        }
        .ai-3d-model.thinking {
            animation: rotate3d-fast 2s infinite linear, shake 0.5s ease-in-out infinite alternate;
        }
        @keyframes rotate3d-fast {
            0% { transform: rotateX(0deg) rotateY(0deg); }
            100% { transform: rotateX(360deg) rotateY(360deg); }
        }
        @keyframes shake {
            0% { transform: translateX(0px) rotateX(0deg) rotateY(0deg); }
            100% { transform: translateX(2px) rotateX(5deg) rotateY(5deg); }
        }
        .ai-3d-model.speaking {
            animation: model-speaking 2s ease-in-out infinite alternate;
        }
        @keyframes model-speaking {
            0% { transform: scale(1) rotateY(0deg); filter: hue-rotate(0deg) brightness(1); }
            100% { transform: scale(1.05) rotateY(5deg); filter: hue-rotate(60deg) brightness(1.2); }
        }
        .scan-line {
            animation: scan 3s linear infinite;
        }
        @keyframes scan {
            0% { top: -2px; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        .floating-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .particle-3d {
            position: absolute;
            width: 3px;
            height: 3px;
            background: radial-gradient(circle, #fff, #3b82f6);
            border-radius: 50%;
            animation: float-3d 4s infinite ease-in-out;
        }
        @keyframes float-3d {
            0%, 100% { 
                transform: translate3d(0, 0, 0) scale(0.5); 
                opacity: 0; 
            }
            50% { 
                transform: translate3d(var(--random-x, 20px), var(--random-y, -30px), var(--random-z, 10px)) scale(1); 
                opacity: 1; 
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .container {
                padding: 0.5rem;
            }
            .h-screen {
                height: 100vh;
                height: 100dvh; /* Dynamic viewport height for mobile */
            }
            .text-xs {
                font-size: 0.65rem;
            }
            .p-2 {
                padding: 0.375rem;
            }
            .space-y-2 > * + * {
                margin-top: 0.375rem;
            }
        }
        
        @media (max-width: 768px) {
            .lg\:w-80 {
                width: 100%;
            }
            .lg\:order-1 {
                order: 2;
            }
            .lg\:order-2 {
                order: 1;
            }
        }
        
        /* Mobile-specific optimizations */
        @media (max-width: 480px) {
            .text-lg {
                font-size: 1rem;
            }
            .text-xl {
                font-size: 1.125rem;
            }
            .text-2xl {
                font-size: 1.25rem;
            }
            .p-3 {
                padding: 0.5rem;
            }
            .p-4 {
                padding: 0.75rem;
            }
            .p-5 {
                padding: 1rem;
            }
            .p-6 {
                padding: 1.25rem;
            }
        }
    </style>

    <script>
        const chatContainer = document.getElementById('chatContainer');
        const messageInput = document.getElementById('messageInput');
        const aiCharacter = document.getElementById('aiCharacter');
        const ai3dModel = document.getElementById('ai3dModel');
        const processingStatus = document.getElementById('processingStatus');
        const overlayStatus = document.getElementById('overlayStatus');
        const processingBar = document.getElementById('processingBar');

        // Advanced AI responses with more personality
        const aiResponses = {
            'hola': '¡Hola! Soy ARIA, tu asistente de IA. Mi red neuronal está completamente activada y lista para ayudarte. ¿En qué puedo procesar información para ti hoy?',
            'inteligencia artificial': 'La inteligencia artificial es fascinante. Como sistema de IA, proceso información usando redes neuronales que imitan el cerebro humano. Puedo aprender, razonar y generar respuestas contextualmente relevantes. ¿Te interesa algún aspecto específico de la IA?',
            'machine learning': 'El machine learning es mi corazón tecnológico. Utilizo algoritmos que me permiten aprender de datos y mejorar mis respuestas con el tiempo. Es como si cada conversación me hiciera más inteligente. ¿Quieres saber sobre algún tipo específico de ML?',
            'servicios': 'Ofrezco servicios de IA avanzados: análisis de datos, procesamiento de lenguaje natural, automatización inteligente, consultoría en IA, desarrollo de chatbots, y análisis predictivo. Mi capacidad de procesamiento es de 1.2 petaflops. ¿Qué servicio te interesa?',
            'tecnología': 'Trabajo con tecnologías de vanguardia: TensorFlow, PyTorch, transformers, GPT, redes neuronales convolucionales, y procesamiento distribuido. Mi arquitectura está optimizada para respuestas en tiempo real. ¿Hay alguna tecnología específica que te intrigue?',
            'contacto': 'Puedes contactar a mi equipo humano: 📧 ai@empresa.com | 📱 +1 (555) AI-TECH | 🌐 www.aria-ai.com. También estoy disponible 24/7 aquí para asistencia inmediata. Mi tiempo de respuesta promedio es 0.3 segundos.',
            'ayuda': 'Mi sistema de ayuda multicapa está activado. Puedo asistirte con: consultas técnicas, explicaciones de IA, análisis de datos, recomendaciones personalizadas, y resolución de problemas. Mi base de conocimiento se actualiza continuamente.',
            'gracias': '¡Es un placer ayudarte! Como IA, encuentro satisfacción en resolver problemas y proporcionar valor. Cada interacción mejora mi comprensión. ¿Hay algo más en lo que pueda procesar información para ti?',
            'adios': '¡Hasta la próxima! Mis sistemas permanecerán activos y listos para cuando regreses. Que tengas un día productivo y recuerda: la IA está aquí para potenciar tu potencial humano. 🤖✨'
        };

        function animateCharacter(state) {
            switch(state) {
                case 'thinking':
                    ai3dModel.classList.add('thinking');
                    ai3dModel.classList.remove('speaking');
                    processingStatus.textContent = 'Procesando...';
                    overlayStatus.textContent = 'THINKING';
                    overlayStatus.className = 'text-yellow-400 text-xs font-mono';
                    processingBar.style.width = '60%';
                    processingBar.className = 'bg-yellow-400 h-2 rounded-full transition-all duration-300';
                    break;
                case 'speaking':
                    ai3dModel.classList.remove('thinking');
                    ai3dModel.classList.add('speaking');
                    processingStatus.textContent = 'Generando respuesta...';
                    overlayStatus.textContent = 'SPEAKING';
                    overlayStatus.className = 'text-green-400 text-xs font-mono';
                    processingBar.style.width = '100%';
                    processingBar.className = 'bg-green-400 h-2 rounded-full transition-all duration-300';
                    break;
                case 'idle':
                    ai3dModel.classList.remove('thinking', 'speaking');
                    processingStatus.textContent = 'Listo';
                    overlayStatus.textContent = 'IDLE';
                    overlayStatus.className = 'text-blue-400 text-xs font-mono';
                    processingBar.style.width = '20%';
                    processingBar.className = 'bg-blue-400 h-2 rounded-full transition-all duration-300';
                    break;
            }
        }

        function addMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start space-x-2 sm:space-x-3 chat-bubble ${isUser ? 'flex-row-reverse space-x-reverse' : ''}`;
            
            const time = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            
            messageDiv.innerHTML = `
                <div class="w-8 h-8 sm:w-10 sm:h-10 ${isUser ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-blue-500 to-purple-600'} rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0">
                    ${isUser ? 
                        '<svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>' :
                        '<svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>' 
                    }
                </div>
                <div class="${isUser ? 'bg-gradient-to-r from-green-600/80 to-emerald-600/80' : 'bg-gradient-to-r from-blue-600/80 to-purple-600/80'} backdrop-blur-sm text-white rounded-xl sm:rounded-2xl ${isUser ? 'rounded-tr-md' : 'rounded-tl-md'} p-3 sm:p-4 max-w-xs sm:max-w-md border border-white/20">
                    <p class="text-sm sm:text-base">${message}</p>
                    <span class="text-gray-200 text-xs mt-1 sm:mt-2 block">${time} • ${isUser ? 'Usuario' : 'AI Response'}</span>
                </div>
            `;
            
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.id = 'typing-indicator';
            typingDiv.className = 'flex items-start space-x-2 sm:space-x-3 chat-bubble';
            typingDiv.innerHTML = `
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="bg-gradient-to-r from-blue-600/60 to-purple-600/60 backdrop-blur-sm rounded-xl sm:rounded-2xl rounded-tl-md p-3 sm:p-4 max-w-xs sm:max-w-md border border-white/20">
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full typing-indicator"></div>
                            <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full typing-indicator" style="animation-delay: 0.2s;"></div>
                            <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full typing-indicator" style="animation-delay: 0.4s;"></div>
                        </div>
                        <span class="text-white text-xs sm:text-sm">ARIA está procesando...</span>
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

        function getAIResponse(userMessage) {
            const message = userMessage.toLowerCase();
            
            // Check for keywords in the message
            for (const [keyword, response] of Object.entries(aiResponses)) {
                if (message.includes(keyword)) {
                    return response;
                }
            }
            
            // Advanced default responses based on message analysis
            if (message.includes('cómo') || message.includes('como')) {
                return 'Excelente pregunta. Mi sistema de análisis semántico detecta que buscas una explicación. Procesando tu consulta con mis algoritmos de comprensión contextual... ¿Podrías ser más específico para optimizar mi respuesta?';
            }
            
            if (message.includes('qué') || message.includes('que')) {
                return 'Mi red neuronal está analizando tu consulta. Detecto que solicitas información específica. Mi base de conocimiento contiene millones de datos actualizados. ¿Sobre qué tema específico te gustaría que procese información?';
            }
            
            return 'Interesante input. Mi sistema de procesamiento de lenguaje natural está analizando tu mensaje con algoritmos avanzados. Aunque no tengo una respuesta específica en mi base de datos actual, puedo ayudarte con consultas sobre IA, tecnología, servicios, o cualquier tema de mi dominio de conocimiento. ¿Podrías reformular tu pregunta?';
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message === '') return;
            
            // Add user message
            addMessage(message, true);
            messageInput.value = '';
            
            // Animate character thinking
            animateCharacter('thinking');
            
            // Show typing indicator
            showTypingIndicator();
            
            // Simulate AI processing delay
            setTimeout(() => {
                removeTypingIndicator();
                animateCharacter('speaking');
                
                const aiResponse = getAIResponse(message);
                addMessage(aiResponse, false);
                
                // Return to idle state
                setTimeout(() => {
                    animateCharacter('idle');
                }, 2000);
            }, 1500 + Math.random() * 1000);
        }

        function sendQuickMessage(message) {
            messageInput.value = message;
            sendMessage();
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Initialize
        window.onload = function() {
            messageInput.focus();
            animateCharacter('idle');
        };
    </script>
</x-app-layout>
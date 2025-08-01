<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(1deg); }
            50% { transform: translateY(-10px) rotate(0deg); }
            75% { transform: translateY(-5px) rotate(-1deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 40px rgba(147, 51, 234, 0.6);
                transform: scale(1.02);
            }
        }
        
        @keyframes slide-in-left {
            from { 
                transform: translateX(-100%) scale(0.8); 
                opacity: 0; 
            }
            to { 
                transform: translateX(0) scale(1); 
                opacity: 1; 
            }
        }
        
        @keyframes slide-in-right {
            from { 
                transform: translateX(100%) scale(0.8); 
                opacity: 0; 
            }
            to { 
                transform: translateX(0) scale(1); 
                opacity: 1; 
            }
        }
        
        @keyframes fade-in-up {
            from { 
                transform: translateY(30px) scale(0.95); 
                opacity: 0; 
            }
            to { 
                transform: translateY(0) scale(1); 
                opacity: 1; 
            }
        }
        
        @keyframes rotate-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes bounce-gentle {
            0%, 100% { transform: scale(1) rotate(0deg); }
            25% { transform: scale(1.03) rotate(1deg); }
            50% { transform: scale(1.05) rotate(0deg); }
            75% { transform: scale(1.03) rotate(-1deg); }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        @keyframes ripple {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(4); opacity: 0; }
        }
        
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        .floating { animation: float 8s ease-in-out infinite; }
        .pulse-glow { animation: pulse-glow 4s ease-in-out infinite; }
        .slide-in-left { animation: slide-in-left 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .slide-in-right { animation: slide-in-right 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .fade-in-up { animation: fade-in-up 1s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .rotate-slow { animation: rotate-slow 25s linear infinite; }
        .bounce-gentle { animation: bounce-gentle 3s ease-in-out infinite; }
        .shimmer { 
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #66C0EA 0%, #667EEA 25%, #9066EA 50%, #8654f3 75%, #804af5 100%);
            background-size: 400% 400%;
            animation: gradient-shift 20s ease infinite;
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .glass-effect {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        
        .input-focus-effect {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
        }
        
        .input-focus-effect:focus {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
        }
        
        .input-focus-effect::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #8b5cf6, #3b82f6);
            transition: width 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .input-focus-effect:focus::after {
            width: 100%;
        }
        
        .button-hover-effect {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        
        .button-hover-effect:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.5);
        }
        
        .button-hover-effect::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .button-hover-effect:hover::before {
            left: 100%;
        }
        
        .button-hover-effect::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .button-hover-effect:active::after {
            width: 300px;
            height: 300px;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: particle-float 10s infinite linear;
        }
        
        @keyframes particle-float {
            0% { 
                transform: translateY(100vh) rotate(0deg) scale(0); 
                opacity: 0; 
            }
            10% { 
                opacity: 1; 
                transform: translateY(90vh) rotate(36deg) scale(1);
            }
            90% { 
                opacity: 1; 
                transform: translateY(10vh) rotate(324deg) scale(1);
            }
            100% { 
                transform: translateY(-100px) rotate(360deg) scale(0); 
                opacity: 0; 
            }
        }
        
        .form-group {
            transition: all 0.3s ease;
        }
        
        .form-group:hover {
            transform: translateX(5px);
        }
        
        .checkbox-custom {
            position: relative;
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .checkbox-custom:checked {
            background: linear-gradient(135deg, #8b5cf6, #3b82f6);
            border-color: transparent;
            transform: scale(1.1);
        }
        
        .checkbox-custom:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .loading-dots {
            display: inline-block;
        }
        
        .loading-dots::after {
            content: '';
            animation: loading-dots 1.5s infinite;
        }
        
        @keyframes loading-dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }
        
        .text-gradient {
            background: linear-gradient(135deg, #8b5cf6, #3b82f6, #06b6d4);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s ease infinite;
        }
        
        .card-hover {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 1024px) {
            .gradient-bg {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .slide-in-right {
                animation: fade-in-up 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            }
        }
    </style>
</head>
<body class="font-inter antialiased overflow-x-hidden">
    <!-- Animated Background Particles -->
    <div id="particles" class="fixed inset-0 pointer-events-none z-0"></div>
    
    <div class="min-h-screen flex relative z-10">
        <!-- Left Section - Enhanced Gradient Background with Animation -->
        <div class="hidden lg:flex lg:w-1/2 gradient-bg relative overflow-hidden">
            <!-- Animated Logo -->
            <div class="absolute top-8 left-8 slide-in-left">
                <div class="flex items-center text-white">
                    <img src="{{ asset('images/Logo-blanco.png') }}" alt="Logo de la aplicación" class="w-48 h-12 hover:scale-110 transition-transform duration-300"> 
                </div>
            </div>
            
            <!-- Enhanced Central Illustration - Centered -->
            <div class="flex items-center justify-center h-full w-full px-8">
                <div class="text-center text-white fade-in-up max-w-2xl mx-auto">
                    <!-- Main Illustration with Enhanced Effects -->
                    <div class="relative mb-12 flex justify-center">
                        <img src="{{ asset('images/icons_ilus.png') }}" alt="Ilustración del sistema" class="w-80 h-80 object-contain floating pulse-glow">
                        
                        <!-- Floating Elements Around Image -->
                        <div class="absolute top-0 left-0 w-16 h-12 glass-effect rounded-lg border border-white/50 floating" style="animation-delay: 0.5s;"></div>
                        <div class="absolute top-2 left-2 w-12 h-8 bg-white/40 rounded floating" style="animation-delay: 1s;"></div>
                        <div class="absolute top-8 right-4 text-white/80 text-sm font-mono bounce-gentle" style="animation-delay: 1.5s;">&lt;/&gt;</div>
                        <div class="absolute bottom-8 left-4 w-0 h-0 border-l-8 border-r-8 border-b-12 border-transparent border-b-white/40 floating" style="animation-delay: 2s;"></div>
                        <div class="absolute bottom-4 right-8 bounce-gentle" style="animation-delay: 2.5s;">
                            <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                            <div class="w-2 h-2 bg-blue-300 rounded-full mt-1 ml-1"></div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Description Text with Typing Effect -->
                    <div class="max-w-xl mx-auto">
                        <p class="text-lg leading-relaxed fade-in-up text-shadow-lg" style="animation-delay: 0.8s;">
                            Sistema para monitorear y gestionar la calidad y los riesgos operativos facilitando la información del cumplimiento normativo, la mejora continua para la toma de decisiones informadas a través del análisis de datos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Enhanced Login Form -->
        <div class="flex-1 flex items-center justify-center bg-white px-4 sm:px-6 lg:px-8 slide-in-right">
            <div class="max-w-md w-full space-y-8">
                <!-- Enhanced Header -->
                <div class="text-center fade-in-up" style="animation-delay: 0.3s;">
                    <h2 class="text-3xl font-bold mb-2 text-gradient">
                        Sistema de Control de Calidad y Gestión de Riesgos
                    </h2>
                    <p class="text-gray-600 text-lg">Ingresa a tu cuenta</p>
                </div>
                
                <!-- Enhanced Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    @if (session('status'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg fade-in-up card-hover" style="animation-delay: 0.4s;">
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    <!-- Enhanced Email Field -->
                    <div class="form-group fade-in-up" style="animation-delay: 0.5s;">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <div class="relative group">
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email', 'tordonez@proser.com.mx') }}"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none input-focus-effect bg-transparent transition-all duration-300"
                             
                                required 
                                autofocus
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 fade-in-up">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Enhanced Password Field -->
                    <div class="form-group fade-in-up" style="animation-delay: 0.6s;">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative group">
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                value="12345678"
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none input-focus-effect bg-transparent transition-all duration-300 pr-12"
                               
                                required 
                                autocomplete="current-password"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center hover:scale-110 transition-transform duration-200"
                                onclick="togglePassword()"
                            >
                                <svg class="h-5 w-5 text-gray-400 hover:text-blue-500 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 fade-in-up">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Enhanced Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between fade-in-up" style="animation-delay: 0.7s;">
                        <div class="flex items-center group">
                            <input 
                                id="remember_me" 
                                type="checkbox" 
                                name="remember" 
                                class="checkbox-custom"
                                checked
                            />
                            <label for="remember_me" class="ml-3 block text-sm text-gray-700 group-hover:text-blue-600 transition-colors duration-200 cursor-pointer">
                                Recordarme
                            </label>
                        </div>
                        
                    </div>
                    
                    <!-- Enhanced Login Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-medium py-4 px-6 rounded-lg button-hover-effect fade-in-up relative overflow-hidden"
                        style="animation-delay: 0.8s;"
                        id="loginButton"
                    >
                        <span class="relative z-10">Ingresar</span>
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-blue-600 opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                    </button>
                </form>
                
              
                
                <!-- Validation Errors -->
                <x-validation-errors class="mt-4 fade-in-up" style="animation-delay: 1s;" />
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            
            // Add ripple effect
            const button = event.currentTarget;
            const ripple = document.createElement('div');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(59, 130, 246, 0.3)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.left = '50%';
            ripple.style.top = '50%';
            ripple.style.width = '20px';
            ripple.style.height = '20px';
            ripple.style.marginLeft = '-10px';
            ripple.style.marginTop = '-10px';
            
            button.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        }
        
        // Create animated particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 80;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particle.style.opacity = Math.random() * 0.5 + 0.3;
                particlesContainer.appendChild(particle);
            }
        }
        
        // Enhanced form interactions
        function enhanceFormInteractions() {
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            const loginButton = document.getElementById('loginButton');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('scale-105');
                    this.parentElement.style.transform = 'translateX(5px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('scale-105');
                    this.parentElement.style.transform = 'translateX(0)';
                });
                
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.classList.add('border-green-500');
                    } else {
                        this.classList.remove('border-green-500');
                    }
                });
            });
            
            // Login button loading state
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const button = loginButton;
                const originalText = button.querySelector('span').textContent;
                
                button.disabled = true;
                button.querySelector('span').innerHTML = 'Ingresando<span class="loading-dots"></span>';
                button.style.opacity = '0.7';
                
                // Re-enable after 3 seconds (in case of error)
                setTimeout(() => {
                    button.disabled = false;
                    button.querySelector('span').textContent = originalText;
                    button.style.opacity = '1';
                }, 3000);
            });
        }
        
        // Add scroll-triggered animations
        function addScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.fade-in-up').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                observer.observe(el);
            });
        }
        
        // Initialize all enhancements
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            enhanceFormInteractions();
            addScrollAnimations();
            
            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && document.activeElement.tagName === 'INPUT') {
                    const form = document.querySelector('form');
                    if (form) {
                        form.dispatchEvent(new Event('submit'));
                    }
                }
            });
        });
    </script>
    
    @livewireScriptConfig
</body>
</html>

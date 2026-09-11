<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#03193a">
    <title>Iniciar sesión · {{ config('app.name', 'Sistema de Gestión de Calidad') }}</title>
    <link rel="icon" href="{{ asset('images/calidad-de-la-pagina.png') }}" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <style>
        :root {
            --emer: #34D399;
            --cyan: #38BDF8;
            --ink: #0f2350;
            --text: #46536b;
            --muted: #5f6b82;
            --line: #d6dde9;
            --danger: #be123c;
            --ease: cubic-bezier(.2, .7, .2, 1);

            /* Escala vertical: todo se ajusta al alto de la pantalla */
            --space: clamp(14px, 3.2vh, 30px);
            --card-pad-y: clamp(24px, 4.6vh, 40px);
            --card-pad-x: clamp(26px, 2.8vw, 38px);
            --field-gap: clamp(12px, 2vh, 18px);
            --input-h: clamp(44px, 6.2vh, 52px);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        [hidden] { display: none !important; }

        html, body { height: 100%; }

        body {
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
            color: #eef4ff;
            background: #02132e;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Fondo */
        .scene {
            position: fixed; inset: 0; overflow: hidden; z-index: 0;
            background:
                radial-gradient(100% 100% at 80% 12%, rgba(22, 84, 156, .28) 0%, rgba(10, 58, 125, 0) 48%),
                linear-gradient(155deg, #04234a 0%, #03193a 48%, #020e26 76%, #01081a 100%);
        }
        .aurora { position: absolute; border-radius: 50%; filter: blur(96px); pointer-events: none; }
        .aurora-1 {
            width: 620px; height: 620px; left: -140px; top: -160px; opacity: .28;
            background: radial-gradient(circle, #2f74e6 0%, rgba(47, 116, 230, 0) 70%);
            animation: drift-a 22s var(--ease) infinite;
        }
        .aurora-2 {
            width: 560px; height: 560px; right: -120px; bottom: -160px; opacity: .22;
            background: radial-gradient(circle, #12a4d6 0%, rgba(18, 164, 214, 0) 70%);
            animation: drift-b 26s var(--ease) infinite;
        }
        .aurora-3 {
            width: 420px; height: 420px; right: 26%; top: -120px; opacity: .13;
            background: radial-gradient(circle, #7c5cf0 0%, rgba(124, 92, 240, 0) 70%);
            animation: drift-a 30s var(--ease) infinite reverse;
        }
        @keyframes drift-a { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(40px, 30px); } }
        @keyframes drift-b { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-36px, -26px); } }

        .blueprint {
            position: absolute; inset: 0; pointer-events: none; opacity: .3;
            background-image:
                linear-gradient(rgba(130, 170, 240, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(130, 170, 240, .07) 1px, transparent 1px);
            background-size: 46px 46px;
            -webkit-mask-image: radial-gradient(120% 90% at 40% 45%, #000 30%, transparent 78%);
            mask-image: radial-gradient(120% 90% at 40% 45%, #000 30%, transparent 78%);
        }
        .watermark { position: absolute; left: -40px; bottom: -60px; width: 340px; opacity: .05; pointer-events: none; }
        .vignette {
            position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(120% 100% at 50% 42%, transparent 55%, rgba(1, 9, 26, .55) 100%);
        }

        /* Layout: una sola pantalla, sin scroll */
        .wrap {
            position: relative; z-index: 2;
            height: 100vh; height: 100dvh;
            display: grid; grid-template-columns: minmax(0, 1fr) minmax(360px, 420px);
            grid-template-rows: auto auto; align-content: center;
            align-items: center; column-gap: clamp(32px, 6vw, 96px); row-gap: clamp(16px, 3.6vh, 40px);
            max-width: 1180px; margin: 0 auto;
            padding: clamp(20px, 4vh, 44px) clamp(24px, 4vw, 56px);
        }

        .brand { grid-column: 1 / -1; display: flex; justify-content: center; }
        .logo { height: clamp(44px, 7.2vh, 66px); width: auto; display: block; }

        .hero {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: var(--space); text-align: center; min-width: 0;
        }
        .emblem {
            width: min(100%, 460px, max(150px, calc(100vh - 300px)));
            width: min(100%, 460px, max(150px, calc(100dvh - 300px)));
            aspect-ratio: 1;
        }
        .emblem img {
            width: 100%; height: 100%; display: block;
            filter: drop-shadow(0 24px 60px rgba(15, 90, 190, .35));
            animation: hover-float 9s ease-in-out infinite;
        }
        @keyframes hover-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .accent-rule {
            width: 56px; height: 3px; border-radius: 3px; margin: 0 auto clamp(10px, 2vh, 18px);
            background: linear-gradient(90deg, var(--cyan), var(--emer));
        }
        .pitch h2 {
            font-family: Sora, Inter, sans-serif; font-weight: 700; letter-spacing: -.01em;
            font-size: clamp(22px, min(2.9vw, 4.6vh), 38px); line-height: 1.14; color: #f3f7ff; text-wrap: balance;
        }

        /* Tarjeta */
        .auth { display: flex; align-items: center; justify-content: center; min-width: 0; }
        .card {
            position: relative; width: 100%; max-width: 420px;
            padding: var(--card-pad-y) var(--card-pad-x) calc(var(--card-pad-y) - 6px);
            background: linear-gradient(180deg, #f8fafc 0%, #ecf0f6 100%);
            border: 1px solid rgba(18, 32, 64, .08); border-radius: 24px;
            box-shadow: 0 40px 90px -34px rgba(0, 0, 0, .55), inset 0 1px 0 rgba(255, 255, 255, .9);
            color: var(--ink);
        }
        .card::before {
            content: ""; position: absolute; inset: 0; border-radius: inherit; padding: 1px; pointer-events: none;
            background: linear-gradient(160deg, rgba(255, 255, 255, .9), rgba(255, 255, 255, 0) 42%);
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
        }
        .badge {
            display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600;
            color: #047857; background: rgba(52, 211, 153, .14); border: 1px solid rgba(16, 157, 110, .32);
            padding: 5px 11px; border-radius: 999px; margin-bottom: clamp(10px, 1.8vh, 16px);
        }
        .badge-dot {
            width: 7px; height: 7px; border-radius: 50%; background: var(--emer); box-shadow: 0 0 10px var(--emer);
            animation: pulse-dot 2.4s ease-in-out infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: .45; } }
        .card h1 { font-family: Sora, Inter, sans-serif; font-weight: 700; font-size: clamp(21px, 3vh, 24px); line-height: 1.2; color: var(--ink); }
        .card-sub { color: var(--muted); font-size: 14.5px; margin-top: 6px; }

        .alert {
            display: flex; gap: 10px; align-items: flex-start;
            margin-top: clamp(14px, 2.2vh, 20px); padding: 10px 13px; border-radius: 12px; font-size: 13.5px; line-height: 1.45;
        }
        .alert svg { flex-shrink: 0; margin-top: 1px; }
        .alert-success { color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; }
        .alert-error { color: #9f1239; background: #fff1f2; border: 1px solid #fecdd3; }

        .login-form { margin-top: clamp(16px, 2.8vh, 26px); }
        .field { margin-bottom: var(--field-gap); }
        .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text); margin-bottom: 7px; letter-spacing: .01em; }
        .control { position: relative; }
        .control input {
            width: 100%; height: var(--input-h); border-radius: 13px; border: 1px solid var(--line);
            background: #fff; color: var(--ink); font: inherit; font-size: 15px; padding: 0 46px 0 15px;
            transition: border-color .18s, box-shadow .18s;
        }
        .control input::placeholder { color: #94a0b8; }
        .control input:hover { border-color: #b9c4d6; }
        .control input:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 4px rgba(52, 211, 153, .2); }
        .control input:-webkit-autofill { -webkit-text-fill-color: var(--ink); -webkit-box-shadow: 0 0 0 1000px #fff inset; }
        .control input:-webkit-autofill:focus { -webkit-box-shadow: 0 0 0 1000px #fff inset, 0 0 0 4px rgba(52, 211, 153, .2); }
        .control input[aria-invalid="true"] { border-color: #f43f5e; }
        .control input[aria-invalid="true"]:focus { box-shadow: 0 0 0 4px rgba(244, 63, 94, .16); }

        .control-icon {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #8a97b0; display: flex; pointer-events: none; transition: color .18s;
        }
        .control:focus-within .control-icon { color: #10b981; }
        .toggle-pass {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            width: 38px; height: 38px; border: 0; border-radius: 10px; background: transparent;
            color: #8a97b0; display: grid; place-items: center; cursor: pointer; transition: color .15s, background .15s;
        }
        .toggle-pass:hover { color: #334155; background: #f1f5f9; }
        .toggle-pass:focus-visible { outline: 2px solid #2563eb; outline-offset: 1px; }
        .toggle-pass .icon-hide { display: none; }
        .toggle-pass[aria-pressed="true"] .icon-show { display: none; }
        .toggle-pass[aria-pressed="true"] .icon-hide { display: block; }

        /* Cloudflare Turnstile: mismo ancho y radio que los inputs */
        .captcha { margin-bottom: var(--field-gap); }
        .captcha-box {
            position: relative; width: 100%; height: 65px; border-radius: 13px; overflow: hidden;
            background: linear-gradient(90deg, #eef2f7 0%, #f8fafc 50%, #eef2f7 100%);
            background-size: 200% 100%; animation: skeleton 1.4s ease-in-out infinite;
            box-shadow: inset 0 0 0 1px var(--line);
        }
        .captcha-box.is-ready { animation: none; background: #fafafa; }
        .captcha-box .cf-turnstile { width: 100%; height: 100%; }
        .captcha-box iframe { display: block; width: 100% !important; }
        @keyframes skeleton { to { background-position: -200% 0; } }

        .field-error { display: flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12.5px; color: var(--danger); }

        .form-row { display: flex; align-items: center; margin: 2px 0 clamp(16px, 2.6vh, 24px); }
        .remember { display: inline-flex; align-items: center; gap: 9px; font-size: 13.5px; color: var(--text); cursor: pointer; user-select: none; }
        .remember input { width: 17px; height: 17px; accent-color: #10b981; cursor: pointer; }
        .remember input:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

        .btn-submit {
            position: relative; overflow: hidden; width: 100%; height: calc(var(--input-h) + 2px); border: 0; border-radius: 13px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            color: #fff; font-family: Sora, Inter, sans-serif; font-size: 15.5px; font-weight: 600; letter-spacing: .01em;
            background: linear-gradient(100deg, #1d4ed8 0%, #2f86e6 52%, #22c1dc 100%);
            box-shadow: 0 16px 34px -12px rgba(34, 150, 220, .75);
            transition: transform .08s, filter .18s, box-shadow .18s;
        }
        .btn-submit:hover { filter: brightness(1.07); box-shadow: 0 18px 38px -12px rgba(34, 150, 220, .85); }
        .btn-submit:active { transform: translateY(1px); }
        .btn-submit:focus-visible { outline: 3px solid #93c5fd; outline-offset: 3px; }
        .btn-submit::after {
            content: ""; position: absolute; top: 0; left: -140%; width: 60%; height: 100%; transform: skewX(-20deg);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .35), transparent);
        }
        .btn-submit:hover::after { animation: sheen .9s var(--ease); }
        @keyframes sheen { to { left: 160%; } }
        .btn-submit[aria-busy="true"] { cursor: progress; filter: saturate(.85); }
        .btn-spinner { display: none; width: 18px; height: 18px; animation: spin .8s linear infinite; }
        .btn-submit[aria-busy="true"] .btn-spinner { display: block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .card-foot { margin-top: clamp(14px, 2.4vh, 22px); text-align: center; font-size: 12px; color: #6b7891; }

        /* Entrada escalonada */
        .reveal { opacity: 0; transform: translateY(16px); animation: rise .8s var(--ease) forwards; }
        .emblem.reveal { transform: translateY(10px) scale(.965); }
        .d1 { animation-delay: .05s; }
        .d2 { animation-delay: .16s; }
        .d3 { animation-delay: .28s; }
        .d4 { animation-delay: .38s; }
        .d5 { animation-delay: .46s; }
        .d6 { animation-delay: .54s; }
        .d7 { animation-delay: .62s; }
        @keyframes rise { to { opacity: 1; transform: none; } }

        /* Tablet */
        @media (max-width: 1024px) and (min-width: 768px) {
            .wrap { grid-template-columns: minmax(0, 1fr) minmax(350px, 390px); column-gap: clamp(24px, 4vw, 48px); }
            :root { --card-pad-x: 24px; }
        }

        /* Pantallas bajas (laptops 1366x600, ventanas reducidas) */
        @media (max-height: 680px) and (min-width: 768px) {
            .badge { display: none; }
            .card-foot { margin-top: 12px; }
        }
        @media (max-height: 600px) and (min-width: 768px) {
            .card-foot { display: none; }
        }

        /* Móvil: solo logo + tarjeta, aquí sí se permite scroll */
        @media (max-width: 767px), (max-height: 500px) {
            body { overflow-x: hidden; overflow-y: auto; }
            .wrap {
                height: auto; min-height: 100vh; min-height: 100dvh;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: 32px; padding: 36px 14px;
            }
            .hero { display: none; }
            .logo { height: clamp(50px, 14vw, 60px); }
            .auth { width: 100%; }
            .card { max-width: 440px; padding: 30px 20px 22px; border-radius: 20px; }
            :root { --input-h: 50px; --field-gap: 16px; }
            .badge { display: inline-flex; }
            .watermark { width: 220px; opacity: .04; }
            .aurora-3 { display: none; }
        }
        @media (max-width: 374px) {
            .card { padding: 26px 16px 20px; }
            .card h1 { font-size: 20px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .aurora, .emblem img, .badge-dot, .captcha-box, .btn-submit:hover::after { animation: none; }
            .reveal, .emblem.reveal { animation: none; opacity: 1; transform: none; }
        }
    </style>
</head>

<body>
    <div class="scene" aria-hidden="true">
        <div class="aurora aurora-1"></div>
        <div class="aurora aurora-2"></div>
        <div class="aurora aurora-3"></div>
        <div class="blueprint"></div>
        <svg class="watermark" viewBox="0 0 210 300" xmlns="http://www.w3.org/2000/svg">
            <g fill="#cfe0ff">
                <path d="M8 300 L44 300 L74 52 L38 52 Z" />
                <path d="M60 300 L96 300 L120 96 L84 96 Z" />
                <path d="M112 300 L148 300 L172 12 L136 12 Z" />
                <path d="M164 300 L200 300 L214 128 L178 128 Z" />
            </g>
        </svg>
        <div class="vignette"></div>
    </div>

    <main class="wrap">
        <header class="brand">
            <img class="logo reveal d1" src="{{ asset('images/Logo-blanco.png') }}" alt="PROSER Grupo Constructor" width="272" height="66">
        </header>

        <section class="hero">
            <div class="emblem reveal d2">
                <img src="{{ asset('images/login-emblem.svg') }}" alt="" width="460" height="460">
            </div>
            <div class="pitch reveal d3">
                <div class="accent-rule"></div>
                <h2>Construyendo Confianza,<br>Entregando Excelencia</h2>
            </div>
        </section>

        <section class="auth" aria-labelledby="login-title">
            <div class="card reveal d2">
                <div class="reveal d3">
                    <span class="badge"><span class="badge-dot"></span>Acceso seguro</span>
                    <h1 id="login-title">Sistema de Gestión de Calidad</h1>
                    <p class="card-sub">Ingresa a tu cuenta para continuar</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M8 12l3 3 5-6" /></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any() && !$errors->hasAny(['email', 'password', 'cf-turnstile-response']))
                    <div class="alert alert-error" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 8v5M12 16h.01" /></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="login-form" id="loginForm">
                    @csrf

                    <div class="field reveal d4">
                        <label for="email">Correo electrónico</label>
                        <div class="control">
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                placeholder="nombre@proser.com.mx" autocomplete="username" inputmode="email"
                                spellcheck="false" required autofocus
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                            <span class="control-icon" aria-hidden="true">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6 9-6" /></svg>
                            </span>
                        </div>
                        @error('email')
                            <p class="field-error" id="email-error" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 8v5M12 16h.01" /></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="field reveal d5">
                        <label for="password">Contraseña</label>
                        <div class="control">
                            <input id="password" type="password" name="password" placeholder="••••••••"
                                autocomplete="current-password" required
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            <button type="button" class="toggle-pass" id="togglePass" aria-controls="password" aria-pressed="false" aria-label="Mostrar contraseña">
                                <svg class="icon-show" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" /><circle cx="12" cy="12" r="3" /></svg>
                                <svg class="icon-hide" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-7 0-11-7-11-7a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 5c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19" /><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" /><path d="M1 1l22 22" /></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="field-error" id="password-error" role="alert">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 8v5M12 16h.01" /></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @if (config('services.turnstile.key'))
                        <div class="captcha reveal d6">
                            <div class="captcha-box" id="captchaBox">
                                <div class="cf-turnstile"
                                    data-sitekey="{{ config('services.turnstile.key') }}"
                                    data-size="flexible"
                                    data-theme="light"
                                    data-language="es"
                                    data-callback="onTurnstileSuccess"
                                    data-error-callback="onTurnstileLoaded"></div>
                            </div>
                            @error('cf-turnstile-response')
                                <p class="field-error" id="captcha-error" role="alert">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 8v5M12 16h.01" /></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="field-error" id="captcha-pending" role="alert" hidden>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 8v5M12 16h.01" /></svg>
                                Completa la verificación de seguridad.
                            </p>
                        </div>
                    @endif

                    <div class="reveal d7">
                        <div class="form-row">
                            <label class="remember">
                                <input type="checkbox" name="remember" id="remember_me" @checked(old('remember'))>
                                Recordarme
                            </label>
                        </div>

                        <button class="btn-submit" type="submit" id="loginButton">
                            <svg class="btn-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                            <span class="btn-label">Ingresar</span>
                        </button>
                    </div>
                </form>

                <p class="card-foot">PROSER Grupo Constructor · Mérida, Yucatán · {{ date('Y') }}</p>
            </div>
        </section>
    </main>

    <script>
        window.onTurnstileLoaded = function () {
            const box = document.getElementById('captchaBox');
            if (box) box.classList.add('is-ready');
        };

        window.onTurnstileSuccess = function () {
            window.onTurnstileLoaded();
            ['captcha-pending', 'captcha-error'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.hidden = true;
            });
        };

        (function () {
            const form = document.getElementById('loginForm');
            const pass = document.getElementById('password');
            const toggle = document.getElementById('togglePass');
            const button = document.getElementById('loginButton');
            const label = button.querySelector('.btn-label');

            toggle.addEventListener('click', function () {
                const show = pass.type === 'password';
                pass.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', String(show));
                toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
                pass.focus({ preventScroll: true });
            });

            document.querySelectorAll('.control input').forEach(function (input) {
                input.addEventListener('input', function () {
                    if (input.getAttribute('aria-invalid') === 'true') {
                        input.removeAttribute('aria-invalid');
                        const err = document.getElementById(input.id + '-error');
                        if (err) err.hidden = true;
                    }
                });
            });

            form.addEventListener('submit', function (e) {
                if (button.getAttribute('aria-busy') === 'true') {
                    e.preventDefault();
                    return;
                }
                const token = form.querySelector('[name="cf-turnstile-response"]');
                if (document.getElementById('captchaBox') && (!token || !token.value)) {
                    e.preventDefault();
                    document.getElementById('captcha-pending').hidden = false;
                    return;
                }
                button.setAttribute('aria-busy', 'true');
                label.textContent = 'Ingresando…';
            });

            // Restaura el botón si el navegador regresa desde caché (botón Atrás)
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    button.removeAttribute('aria-busy');
                    label.textContent = 'Ingresar';
                }
            });

            @if ($errors->has('email') || $errors->has('password'))
                document.getElementById('{{ $errors->has('email') ? 'email' : 'password' }}').focus();
            @endif
        })();
    </script>
</body>

</html>

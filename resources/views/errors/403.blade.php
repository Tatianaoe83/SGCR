<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Sin permiso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-neutral-50 flex items-center justify-center px-4">
    <div class="text-center max-w-sm">
        <p class="text-6xl font-semibold text-neutral-300 tracking-tight">403</p>
        <p class="mt-4 text-neutral-600 text-sm leading-relaxed">
            No tienes permiso para acceder a este recurso.
        </p>
        <a href="{{ route('dashboard') }}"
           class="mt-8 inline-flex items-center justify-center gap-2 h-11 px-5 rounded-lg bg-neutral-900 text-white text-sm font-medium hover:bg-neutral-800 transition-colors">
            Regresar al Dashboard
        </a>
    </div>
</body>
</html>

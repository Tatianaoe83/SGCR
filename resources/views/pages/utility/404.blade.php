<!DOCTYPE html>
<html class="light" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>404 Page Not Found</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "#ec1313",
              "primary-hover": "#c91010",
              "background-light": "#f8f6f6",
              "background-dark": "#221010",
              "text-main": "#1b0d0d",
              "text-muted": "#6b5c5c",
            },
            fontFamily: {
              "display": ["Plus Jakarta Sans", "sans-serif"]
            },
            borderRadius: {
              "DEFAULT": "0.375rem",
              "lg": "0.5rem",
              "xl": "0.75rem",
              "2xl": "1rem",
              "full": "9999px"
            },
          },
        },
      }
    </script>
  </head>
  <body class="bg-background-light dark:bg-background-dark text-text-main dark:text-[#fcf8f8] font-display antialiased min-h-screen flex flex-col overflow-x-hidden transition-colors duration-200">
    <main class="flex-grow flex flex-col items-center justify-center p-6 relative">
      <div aria-hidden="true" class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-primary/5 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
      </div>
      <div class="layout-content-container flex flex-col items-center max-w-2xl w-full z-10 text-center animate-fade-in-up">
        <!-- Icon/Warning Visual -->
        <div class="mb-6 size-20 rounded-full bg-primary/10 flex items-center justify-center text-primary shadow-sm ring-1 ring-primary/20">
          <span class="material-symbols-outlined text-[40px]">broken_image</span>
        </div>
        <h1 class="text-primary tracking-tighter text-[120px] md:text-[160px] font-extrabold leading-none select-none drop-shadow-sm"> 404 </h1>
        <h2 class="text-text-main dark:text-[#fcf8f8] text-2xl md:text-3xl font-bold leading-tight tracking-tight mt-2 px-4"> Oops! La firma ya ha sido procesada. </h2>
        <p class="text-text-muted dark:text-[#bcaaaa] text-base md:text-lg font-normal leading-relaxed max-w-lg mt-4 px-4"> Este documento ha sido inahabilitado por su procesamiento. </p>
        <div class="flex flex-col sm:flex-row gap-4 mt-10 w-full sm:w-auto px-4">
          <button onclick="window.location.href='/'" 
          class="flex items-center justify-center gap-2 min-w-[160px] h-12 px-6 bg-primary hover:bg-primary-hover text-white text-base font-bold rounded-lg shadow-lg shadow-primary/20 transition-all transform active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-background-dark">
            <span class="material-symbols-outlined text-[20px]">login</span>
            <span>Regresar al Login</span>
          </button>
        </div>
      </div>
    </main>

    <style>
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translate3d(0, 20px, 0);
        }

        to {
          opacity: 1;
          transform: translate3d(0, 0, 0);
        }
      }

      .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
      }
    </style>
  </body>
</html>
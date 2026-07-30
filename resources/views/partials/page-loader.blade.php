{{-- Loader de tabla: overlay absoluto dentro del card (que debe ser relative).
     Solo se muestra al ENTRAR a la vista (cambio de ruta), no al paginar (mismo path). --}}
<div id="page-loader"
    class="absolute inset-0 z-20 flex items-center justify-center bg-white dark:bg-gray-800 transition-opacity duration-300 rounded-lg">
    <div class="flex flex-col items-center gap-4">
        <div class="w-12 h-12 rounded-full border-4 border-gray-300 dark:border-gray-600 border-t-blue-500 animate-spin"></div>
        <span class="text-sm text-gray-500 dark:text-gray-400">Cargando...</span>
    </div>
</div>

<script>
    (function () {
        var path = window.location.pathname;
        var mismaVista = sessionStorage.getItem('loaderLastPath') === path;
        var loader = document.getElementById('page-loader');

        // Paginacion / misma vista: ocultar loader de inmediato (sin flash).
        if (mismaVista && loader) {
            loader.style.display = 'none';
        } else {
            sessionStorage.setItem('loaderLastPath', path);
        }

        function revelarContenido() {
            var content = document.getElementById('table-content');
            if (content) content.classList.remove('opacity-0');
        }

        if (mismaVista) {
            // Contenido aun no parseado: revelar cuando el DOM este listo.
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', revelarContenido);
            } else {
                revelarContenido();
            }
        } else {
            // Primera entrada: mostrar loader hasta que cargue todo.
            window.addEventListener('load', function () {
                setTimeout(function () {
                    revelarContenido();
                    if (loader) {
                        loader.style.opacity = '0';
                        setTimeout(function () { loader.style.display = 'none'; }, 300);
                    }
                }, 300);
            });
        }
    })();
</script>

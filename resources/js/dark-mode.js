// Dark mode toggle functionality
class DarkModeToggle {
    constructor() {
        this.darkMode = localStorage.getItem('dark-mode') === 'true';
        this.init();
    }

    init() {
        // Aplicar el modo oscuro al cargar la página
        this.applyDarkMode();
        
        // Agregar event listeners para los botones de toggle
        this.addEventListeners();
        
        // Detectar cambios en el sistema
        this.detectSystemPreference();
    }

    applyDarkMode() {
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
            document.documentElement.style.colorScheme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.colorScheme = 'light';
        }
    }

    toggle() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('dark-mode', this.darkMode);
        this.applyDarkMode();
        
        // Disparar evento personalizado para que otros componentes puedan reaccionar
        document.dispatchEvent(new CustomEvent('darkModeChanged', {
            detail: { darkMode: this.darkMode }
        }));
    }

    addEventListeners() {
        // Buscar el checkbox del toggle
        const lightSwitch = document.getElementById('light-switch');
        
        if (lightSwitch) {
            // Establecer el estado inicial del checkbox
            lightSwitch.checked = this.darkMode;
            
            // Agregar event listener
            lightSwitch.addEventListener('change', (e) => {
                this.toggle();
            });
        }

        // Buscar todos los botones de toggle de modo oscuro
        const toggleButtons = document.querySelectorAll('[data-dark-mode-toggle]');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
                this.updateToggleButton(button);
            });
        });

        // Actualizar el estado inicial de los botones
        toggleButtons.forEach(button => {
            this.updateToggleButton(button);
        });
    }

    updateToggleButton(button) {
        const icon = button.querySelector('[data-dark-mode-icon]');
        const text = button.querySelector('[data-dark-mode-text]');
        
        if (this.darkMode) {
            // Modo oscuro activo
            if (icon) {
                icon.innerHTML = `
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM8 12c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z"/>
                    </svg>
                `;
            }
            if (text) {
                text.textContent = 'Modo Claro';
            }
        } else {
            // Modo claro activo
            if (icon) {
                icon.innerHTML = `
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM8 2c3.3 0 6 2.7 6 6s-2.7 6-6 6V2z"/>
                    </svg>
                `;
            }
            if (text) {
                text.textContent = 'Modo Oscuro';
            }
        }
    }

    detectSystemPreference() {
        // Detectar preferencia del sistema si no hay configuración guardada
        if (localStorage.getItem('dark-mode') === null) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.darkMode = prefersDark;
            localStorage.setItem('dark-mode', this.darkMode);
            this.applyDarkMode();
        }

        // Escuchar cambios en la preferencia del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (localStorage.getItem('dark-mode') === null) {
                this.darkMode = e.matches;
                localStorage.setItem('dark-mode', this.darkMode);
                this.applyDarkMode();
            }
        });
    }

    // Método público para obtener el estado actual
    isDarkMode() {
        return this.darkMode;
    }

    // Método público para establecer el modo oscuro
    setDarkMode(enabled) {
        this.darkMode = enabled;
        localStorage.setItem('dark-mode', this.darkMode);
        this.applyDarkMode();
        
        // Actualizar el checkbox
        const lightSwitch = document.getElementById('light-switch');
        if (lightSwitch) {
            lightSwitch.checked = this.darkMode;
        }
        
        // Actualizar todos los botones de toggle
        const toggleButtons = document.querySelectorAll('[data-dark-mode-toggle]');
        toggleButtons.forEach(button => {
            this.updateToggleButton(button);
        });
    }
}

// Inicializar el toggle de modo oscuro cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.darkModeToggle = new DarkModeToggle();
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeToggle;
} 
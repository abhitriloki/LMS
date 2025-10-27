import './bootstrap';
import Alpine from 'alpinejs';

// Make Alpine available globally BEFORE Livewire loads
window.Alpine = Alpine;

// Dark mode component - MUST be defined before Alpine.start()
Alpine.data('darkMode', () => ({
    dark: localStorage.getItem('darkMode') === 'true',
    
    init() {
        this.updateTheme();
    },
    
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('darkMode', this.dark);
        this.updateTheme();
    },
    
    updateTheme() {
        if (this.dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}));

// Start Alpine - Livewire will use this instance
Alpine.start();

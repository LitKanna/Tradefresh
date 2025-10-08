// Sydney Markets B2B - Main Application JavaScript

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Initialize Laravel Echo for WebSocket connections - MATCHES GIST PATTERN
window.Echo = new Echo({
    broadcaster: 'pusher',  // Using pusher driver for Reverb
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 9090,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 9090,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Alpine.js initialization (if using npm packages)
// import Alpine from 'alpinejs';
// window.Alpine = Alpine;
// Alpine.start();

// CSRF Token setup for AJAX requests
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios = {
        defaults: {
            headers: {
                common: {
                    'X-CSRF-TOKEN': token.content
                }
            }
        }
    };
}

// Global utility functions
window.showToast = function(message, type = 'success') {
    const event = new CustomEvent('toast-message', {
        detail: { message, type }
    });
    window.dispatchEvent(event);
};

// Dark mode initialization
if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Ready');
});

// Livewire integration (if available)
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:initialized', () => {
        console.log('Livewire initialized');
    });
}

console.log('Sydney Markets B2B App Initialized');
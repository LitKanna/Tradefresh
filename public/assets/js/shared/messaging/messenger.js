/**
 * Unified Messaging System JavaScript
 * Shared by Buyer and Vendor dashboards
 * Minimal JS - Livewire handles everything else
 */

document.addEventListener('livewire:initialized', () => {
    // Auto-scroll chat to bottom (only unavoidable DOM manipulation)
    Livewire.on('scroll-chat-to-bottom', () => {
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
});

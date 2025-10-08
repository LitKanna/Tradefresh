// Enterprise Core - Business application utilities
(function() {
    'use strict';

    window.EnterpriseCore = {
        // Form validation helpers
        validateForm: function(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;

            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('error');
                    isValid = false;
                } else {
                    input.classList.remove('error');
                }
            });

            return isValid;
        },

        // Show loading state
        showLoading: function(element) {
            if (element) {
                element.classList.add('loading');
                element.disabled = true;
            }
        },

        // Hide loading state
        hideLoading: function(element) {
            if (element) {
                element.classList.remove('loading');
                element.disabled = false;
            }
        },

        // Toast notifications
        showToast: function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
    };

    // Auto-focus first input
    document.addEventListener('DOMContentLoaded', function() {
        const firstInput = document.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput && !firstInput.value) {
            firstInput.focus();
        }
    });
})();
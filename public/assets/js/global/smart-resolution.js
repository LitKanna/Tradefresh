// Smart Resolution Adapter
(function() {
    'use strict';

    // Detect screen resolution and apply appropriate scaling
    function applyResolutionScaling() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        const dpr = window.devicePixelRatio || 1;

        let scale = 1;

        // Determine scale based on viewport width
        if (width <= 1366) {
            scale = 0.85;
        } else if (width >= 1920 && width < 2560) {
            scale = 1;
        } else if (width >= 2560 && width < 3840) {
            scale = 1.15;
        } else if (width >= 3840) {
            scale = 1.3;
        }

        // Apply scale to root element
        document.documentElement.style.setProperty('--scale-factor', scale);
        document.documentElement.style.setProperty('--viewport-width', width + 'px');
        document.documentElement.style.setProperty('--viewport-height', height + 'px');
        document.documentElement.style.setProperty('--device-pixel-ratio', dpr);

        // Add resolution class to body
        document.body.classList.remove('res-hd', 'res-fhd', 'res-qhd', 'res-4k');

        if (width <= 1366) {
            document.body.classList.add('res-hd');
        } else if (width >= 1920 && width < 2560) {
            document.body.classList.add('res-fhd');
        } else if (width >= 2560 && width < 3840) {
            document.body.classList.add('res-qhd');
        } else if (width >= 3840) {
            document.body.classList.add('res-4k');
        }
    }

    // Apply on load
    applyResolutionScaling();

    // Reapply on resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(applyResolutionScaling, 150);
    });
})();
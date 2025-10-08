// Screen Adapter - Responsive scaling utility
(function() {
    'use strict';

    function adaptToScreen() {
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        // Set CSS variables for use in stylesheets
        document.documentElement.style.setProperty('--vw', vw + 'px');
        document.documentElement.style.setProperty('--vh', vh + 'px');

        // Adaptive font sizing
        let baseFontSize = 16;
        if (vw < 1366) {
            baseFontSize = 14;
        } else if (vw >= 2560) {
            baseFontSize = 18;
        } else if (vw >= 3840) {
            baseFontSize = 20;
        }

        document.documentElement.style.fontSize = baseFontSize + 'px';

        console.log(`Screen adapted: ${vw}x${vh}, base font: ${baseFontSize}px`);
    }

    // Initialize
    adaptToScreen();

    // Handle resize
    window.addEventListener('resize', adaptToScreen);

    // Handle orientation change
    window.addEventListener('orientationchange', adaptToScreen);
})();
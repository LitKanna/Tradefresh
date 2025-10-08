import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Main application assets
                'resources/css/app.css',
                'resources/js/app.js',

                // Optional: Add page-specific bundles for optimization
                // 'resources/css/buyer-dashboard.css',
                // 'resources/js/buyer-dashboard.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    let extType = info[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
            },
        },
        // CSS optimization
        cssCodeSplit: true,
        minify: 'esbuild',
        sourcemap: process.env.NODE_ENV === 'development',
    },
});
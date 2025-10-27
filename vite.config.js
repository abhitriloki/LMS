import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        hmr: {
            host: 'localhost',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Minify assets in production
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Optimize chunk splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': [
                        'alpinejs',
                    ],
                    'charts': [
                        'chart.js',
                    ],
                },
            },
        },
        // Set chunk size warning limit
        chunkSizeWarningLimit: 1000,
        // Enable CSS code splitting
        cssCodeSplit: true,
        // Optimize assets
        assetsInlineLimit: 4096,
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs', 'chart.js'],
    },
});

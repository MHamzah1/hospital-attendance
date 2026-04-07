import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    let appUrl;

    try {
        appUrl = new URL(env.APP_URL || 'http://localhost:8000');
    } catch {
        appUrl = new URL('http://localhost:8000');
    }

    const hmrHost = appUrl.hostname;

    // Allow access from all LAN segments
    const allowedOrigins = [
        appUrl.origin,
        'http://192.168.100.50',
        'https://192.168.100.50',
        'http://192.168.10.*',
        'https://192.168.10.*',
        'http://192.168.20.*',
        'https://192.168.20.*',
    ];

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.jsx',
                refresh: true,
            }),
            react(),
        ],
        server: {
            host: '0.0.0.0',
            origin: `http://${hmrHost}:5173`,
            cors: true,
            hmr: {
                host: hmrHost,
                protocol: appUrl.protocol === 'https:' ? 'wss' : 'ws',
            },
        },
    };
});

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
            cors: {
                origin: appUrl.origin,
            },
            hmr: {
                host: hmrHost,
                protocol: appUrl.protocol === 'https:' ? 'wss' : 'ws',
            },
        },
    };
});

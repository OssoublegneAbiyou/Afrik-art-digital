import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            // comic-reader.js : chargé uniquement par le lecteur de récits dessinés.
            input: ['resources/css/app.css', 'resources/js/app.jsx', 'resources/js/comic-reader.js'],
            refresh: true,
        }),
    ],
});

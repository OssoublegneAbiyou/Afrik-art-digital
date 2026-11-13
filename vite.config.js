import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            // comic-reader.js : chargé uniquement par le lecteur de récits dessinés.
            // animation-player.js : chargé seulement par les pages qui affichent un lecteur ou une carte d'animation.
            input: ['resources/css/app.css', 'resources/js/app.jsx', 'resources/js/comic-reader.js', 'resources/js/animation-player.js'],
            refresh: true,
        }),
    ],
});

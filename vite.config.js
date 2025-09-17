import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
        input: [
            //'resources/css/calendar.css',
            'resources/js/app.jsx'], 
        refresh: true,
        }),
        react(),
    ],
    server: {
        host: '192.168.56.100', // Cho phép truy cập từ tất cả IP
        port: 5173,  // Port mặc định, có thể đổi nếu cần
        cors: true,     // bật CORS
    }, 
});

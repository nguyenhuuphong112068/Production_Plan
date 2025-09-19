import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig(() => {
  return {
    plugins: [
      laravel({
        input: ['resources/js/app.jsx'],
        refresh: true,
      }),
      react(),
    ],
    server: {
      host: process.env.VITE_HOST || 'localhost',
      port: parseInt(process.env.VITE_PORT) || 5173,
    },
  };
});


// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import react from '@vitejs/plugin-react';

// export default defineConfig({
//     plugins: [
//         laravel({
//             input: [
//                 'resources/js/app.jsx',
//             ],
//             refresh: true,
//         }),
//         react(),
//     ],
//     server: {
//         host: '192.168.56.100', // Cho phép truy cập từ LAN (VM, máy khác)
//         port: 5173,
//         cors: true,  // Cho phép request từ XAMPP (http://192.168.56.100)
//         hmr: {
//             host: '127.0.0.1', // Đảm bảo HMR cũng hoạt động khi truy cập từ ngoài
//         }
//     },
// });

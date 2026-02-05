import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    strictPort: false,
    hmr: {
      protocol: 'ws',
      host: 'localhost',
      port: 5173,
    },
  },
  build: {
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        dead_code: true,
      },
      mangle: true,
    },
    rollupOptions: {
      output: {
        manualChunks: (id) => {
          // Vendor chunks
          if (id.includes('node_modules/react-dom')) {
            return 'react-dom-vendor';
          }
          if (id.includes('node_modules/react') && !id.includes('react-dom')) {
            return 'react-vendor';
          }
          if (id.includes('node_modules/@reduxjs') || id.includes('node_modules/redux')) {
            return 'redux-vendor';
          }
          if (id.includes('node_modules/react-router')) {
            return 'router-vendor';
          }
          // Application chunks
          if (id.includes('src/pages')) {
            return 'pages';
          }
          if (id.includes('src/components')) {
            return 'components';
          }
          if (id.includes('src/store')) {
            return 'store';
          }
          if (id.includes('src/services')) {
            return 'services';
          }
        },
      },
    },
    chunkSizeWarningLimit: 600,
    sourcemap: false,
    cssCodeSplit: true,
    target: 'es2017',
  },
  optimizeDeps: {
    include: [
      'react',
      'react-dom',
      'react-router-dom',
      '@reduxjs/toolkit',
      'react-redux',
    ],
    exclude: ['@vite/client'],
  },
})

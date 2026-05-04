import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const target = env.VITE_API_ORIGIN || 'http://127.0.0.1';

  return {
    plugins: [react()],
    server: {
      proxy: {
        '/legacy-api': {
          target,
          changeOrigin: true,
          rewrite: (p) => p.replace(/^\/legacy-api/, ''),
        },
      },
    },
    build: {
      outDir: 'dist',
    },
  };
});

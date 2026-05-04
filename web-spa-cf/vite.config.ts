import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

const CSS_MARKER = 'href="/template/dabao/css/style.css?v=20260412g"';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const target = env.VITE_API_ORIGIN || 'http://127.0.0.1';
  const tmplOrigin = (env.VITE_TEMPLATE_ASSET_ORIGIN || '').trim().replace(/\/$/, '');

  const injectTemplateCss = (): { name: string; transformIndexHtml: (html: string) => string } => ({
    name: 'inject-dabao-css-origin',
    transformIndexHtml(html) {
      const href = tmplOrigin
        ? `${tmplOrigin}/template/dabao/css/style.css?v=20260412g`
        : '/template/dabao/css/style.css?v=20260412g';
      if (!html.includes(CSS_MARKER)) {
        return html;
      }
      return html.replace(CSS_MARKER, `href="${href}"`);
    },
  });

  return {
    plugins: [react(), injectTemplateCss()],
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

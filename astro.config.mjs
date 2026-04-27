// @ts-check
import { defineConfig } from 'astro/config';

import tailwindcss from '@tailwindcss/vite';

import sitemap from '@astrojs/sitemap';

// https://astro.build/config
export default defineConfig({
  devToolbar: { enabled: false },
  site: 'https://suconsulting.pe',
  vite: {
    plugins: [tailwindcss()],
    server: {
      proxy: {
        '/backend': 'http://127.0.0.1:8080'
      }
    }
  },
  integrations: [sitemap()]
});

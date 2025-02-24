// vite.config.(mjs | js) con "type": "module" en package.json

import { defineConfig } from "vite"
import laravel from "laravel-vite-plugin"

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.scss",
        "resources/js/custom/store.js",
        "resources/js/plugins/jquery-jvectormap-2.0.5.min.js",
        "resources/js/plugins/jquery-jvectormap-world-mill-en.js",
        "resources/js/custom/chart-active.js",
        "resources/js/main.js",
        "resources/js/app.js",
        "resources/js/custom/app-chat.js",
        "resources/js/custom/app-email.js",
        "resources/js/plugins/flatpickr.js",
        "resources/js/custom/calander-init.js",
        'resources/js/custom/map-active.js',
        'resources/js/custom/app-todo.js',
        'resources/js/plugins/jquery.mousewheel.js',
        'resources/css/plugins/select2.min.css',
        'resources/js/plugins/Select2.min.js',
      ],
      refresh: true,
    }),
  ],
  resolve: {
    alias: {
      'jquery': 'jquery/dist/jquery.min.js' // Alias para jQuery
    }
  }
});

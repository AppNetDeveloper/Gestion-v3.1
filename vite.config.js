// vite.config.js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

// Para tener __dirname en ESM:
import { fileURLToPath } from "url";
import { dirname, resolve } from "path";
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

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
    alias: [
      // 1) Import CSS de SimpleBar: ruta física absoluta
      {
        find: /^simplebar\/dist\/simplebar\.css$/,
        replacement: resolve(
          __dirname,
          "node_modules/simplebar/dist/simplebar.css"
        ),
      },
      // 2) Import JS de SimpleBar: bundle ESM real
      {
        find: /^simplebar$/,
        replacement: resolve(
          __dirname,
          "node_modules/simplebar/dist/index.mjs"
        ),
      },
      // 3) Tu alias de jQuery
      {
        find: "jquery",
        replacement: "jquery/dist/jquery.min.js",
      },
    ],
  },
  optimizeDeps: {
    include: [
      // para dev, que Vite preprocese ambos antes de servir
      "simplebar/dist/index.mjs",
      "simplebar/dist/simplebar.css",
    ],
  },
  build: {
    commonjsOptions: {
      include: [/node_modules/],
      transformMixedEsModules: true,
    },
  },
});

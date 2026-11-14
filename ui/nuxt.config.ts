// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  modules: ["@nuxt/ui"],

  css: ["~/assets/css/main.css"],
  colorMode: {
    preference: "light",
    fallback: "light",
  },
  runtimeConfig: {
    public: {
      apiBase: "http://localhost:8000",
    },
  },
  // File watching for Docker on macOS
  vite: {
    server: {
      watch: {
        usePolling: true,
        interval: 500,
      },
    },
  },

  watchers: {
    chokidar: {
      usePolling: true,
      interval: 500,
    },
  },
});

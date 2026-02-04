import path from 'path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  base: './',
  build: {
    outDir: '../public/ui',
    emptyOutDir: true,
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-charts': ['apexcharts', 'vue3-apexcharts'],
          'vendor-utils': ['xlsx', 'dayjs', 'zod'],
          'vendor-ui': ['lucide-vue-next', 'radix-vue', 'clsx', 'tailwind-merge'],
        }
      }
    }
  },
})

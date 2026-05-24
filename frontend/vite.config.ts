import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import Components from 'unplugin-vue-components/vite'
import AutoImport from 'unplugin-auto-import/vite'
import { ComponentResolver } from 'unplugin-vue-components/types'

// Custom resolver for our design system components
const designSystemResolver: ComponentResolver = (name) => {
  if (name.startsWith('Ch')) {
    return {
      name,
      from: '@/design-system',
    }
  }
}

// Custom resolver for our composables
const composableResolver = (name: string) => {
  if (/^use[A-Z]\w*/.test(name)) {
    return {
      name,
      from: '@/design-system',
    }
  }
}

// https://vite.dev/config/
export default defineConfig({
  base: './', // Use relative paths for built assets
  plugins: [
    vue(),
    vueDevTools(),
    Components({
      dirs: ['src/design-system/components'],
      extensions: ['vue'],
      dts: 'src/design-system/components.d.ts',
      resolvers: [designSystemResolver],
      include: [/\.vue$/, /\.vue\?vue/],
    }),
    AutoImport({
      dts: 'src/design-system/auto-imports.d.ts',
      resolvers: [composableResolver],
      imports: ['vue'],
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://www.onechurch.com',
        changeOrigin: true,
        rewrite: (path: string) => path.replace(/^\/api/, ''),
      },
    },
  },
  build: {
    outDir: '../public',
    emptyOutDir: true,
  }
})

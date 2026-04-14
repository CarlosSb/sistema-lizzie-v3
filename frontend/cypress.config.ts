import { defineConfig } from 'cypress'

export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:5173', // Adjust if Vite runs on different port
    setupNodeEvents(on, cypress) {
      // implement background tasks
      return cycle
    },
  },
}
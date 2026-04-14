import { ref, computed, onMounted, watch } from 'vue'

export function useTheme() {
  const theme = ref('light')
  const isDark = computed(() => theme.value === 'dark')

  // Initialize theme from localStorage or system preference
  const initTheme = () => {
    const savedTheme = localStorage.getItem('theme')
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

    if (savedTheme) {
      theme.value = savedTheme
    } else if (systemPrefersDark) {
      theme.value = 'dark'
    }

    applyTheme()
  }

  // Apply theme to document
  const applyTheme = () => {
    document.documentElement.setAttribute('data-theme', theme.value)
    localStorage.setItem('theme', theme.value)
  }

  // Toggle theme
  const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    applyTheme()
  }

  // Set specific theme
  const setTheme = (newTheme) => {
    theme.value = newTheme
    applyTheme()
  }

  // Listen for system theme changes
  const watchSystemTheme = () => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

    const handleChange = (e) => {
      if (!localStorage.getItem('theme')) {
        theme.value = e.matches ? 'dark' : 'light'
        applyTheme()
      }
    }

    mediaQuery.addEventListener('change', handleChange)

    return () => mediaQuery.removeEventListener('change', handleChange)
  }

  onMounted(() => {
    initTheme()
    const cleanup = watchSystemTheme()

    // Cleanup on unmount
    return cleanup
  })

  return {
    theme,
    isDark,
    toggleTheme,
    setTheme
  }
}
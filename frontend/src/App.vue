<script setup>
import { computed } from 'vue'
import { RouterView } from 'vue-router'
import { NConfigProvider, NMessageProvider, NNotificationProvider, NDialogProvider, darkTheme } from 'naive-ui'
import { useAuthStore } from './stores/auth'
import { useTheme } from './composables/useTheme'
import Layout from './components/layout/Layout.vue'

const authStore = useAuthStore()
const { isDark } = useTheme()

// Professional theme overrides that complement our design system
const themeOverrides = computed(() => ({
  common: {
    primaryColor: '#1a56db',
    primaryColorHover: '#1e40af',
    primaryColorPressed: '#1e3a8a',
    borderRadius: '0.5rem',
    fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
    fontSize: '1rem',
    successColor: '#059669',
    warningColor: '#d97706',
    errorColor: '#dc2626',

    // Enhanced colors
    primaryColorSuppl: '#3b82f6',
    infoColor: '#1a56db',
    infoColorHover: '#1e40af',
    infoColorPressed: '#1e3a8a',
    infoColorSuppl: '#3b82f6',

    // Text colors
    textColor1: '#0f172a',
    textColor2: '#334155',
    textColor3: '#64748b',

    // Background colors
    bodyColor: '#f8fafc',
    cardColor: '#ffffff',
    modalColor: '#ffffff',
    popoverColor: '#ffffff',

    // Border colors
    borderColor: '#e2e8f0',
    dividerColor: '#e2e8f0',

    // Box shadow
    boxShadow1: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
    boxShadow2: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
    boxShadow3: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
  },

  // Component-specific overrides
  Button: {
    borderRadiusTiny: 'var(--radius-sm)',
    borderRadiusSmall: 'var(--radius-sm)',
    borderRadiusMedium: 'var(--radius-md)',
    borderRadiusLarge: 'var(--radius-lg)',
    fontSizeTiny: 'var(--text-xs)',
    fontSizeSmall: 'var(--text-sm)',
    fontSizeMedium: 'var(--text-sm)',
    fontSizeLarge: 'var(--text-base)',
    heightTiny: '28px',
    heightSmall: '32px',
    heightMedium: '40px',
    heightLarge: '44px',
    fontWeight: 'var(--font-medium)',
  },

  Input: {
    borderRadius: 'var(--radius-lg)',
    fontSizeMedium: 'var(--text-sm)',
    heightMedium: '40px',
  },

  Card: {
    borderRadius: 'var(--radius-xl)',
    boxShadow: 'var(--shadow-sm)',
  },

  Menu: {
    itemTextColor: 'var(--gray-600)',
    itemHoverColor: 'var(--primary-600)',
    itemActiveColor: 'var(--primary-600)',
    itemIconColor: 'var(--gray-500)',
    itemIconActiveColor: 'var(--primary-600)',
    itemIconHoverColor: 'var(--primary-600)',
    itemHeight: '44px',
    borderRadius: 'var(--radius-lg)',
  },

  Table: {
    borderRadius: 'var(--radius-lg)',
    thColor: 'var(--gray-50)',
    tdColor: 'var(--gray-25)',
    thTextColor: 'var(--gray-700)',
    tdTextColor: 'var(--gray-900)',
    borderColor: 'var(--gray-200)',
  },

  Modal: {
    borderRadius: 'var(--radius-xl)',
  },

  Drawer: {
    borderRadius: 'var(--radius-xl)',
  },

  Select: {
    peers: {
      InternalSelection: {
        borderRadius: 'var(--radius-lg)',
        heightMedium: '40px',
      }
    }
  }
}))
</script>

<template>
  <NConfigProvider :theme="isDark ? darkTheme : null" :theme-overrides="themeOverrides">
    <NMessageProvider>
      <NNotificationProvider>
        <NDialogProvider>
          <!-- Use new professional layout -->
          <Layout v-if="authStore.isAuthenticated">
            <RouterView />
          </Layout>

          <!-- Show router view directly for login/auth pages -->
          <RouterView v-else />
        </NDialogProvider>
      </NNotificationProvider>
    </NMessageProvider>
  </NConfigProvider>
</template>


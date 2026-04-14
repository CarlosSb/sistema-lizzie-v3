<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'

const sidebarCollapsed = ref(false)
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
  if (isMobile.value) {
    sidebarCollapsed.value = true
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<template>
  <div class="app-layout">
    <!-- Sidebar -->
    <Sidebar
      v-model:collapsed="sidebarCollapsed"
      :class="{ 'sidebar-mobile': isMobile }"
    />

    <!-- Overlay for mobile -->
    <div
      v-if="isMobile && !sidebarCollapsed"
      class="sidebar-overlay"
      @click="sidebarCollapsed = true"
    />

    <!-- Main Content -->
    <div class="main-content" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <!-- Header -->
      <Header @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />

      <!-- Page Content -->
      <main class="page-content">
        <div class="page-container">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
}

.main-content {
  flex: 1;
  margin-left: 0;
  transition: margin-left var(--transition-normal);
  display: flex;
  flex-direction: column;
}

.page-content {
  flex: 1;
  padding: var(--space-6);
  overflow-y: auto;
}

.page-container {
  max-width: 100%;
  margin: 0 auto;
}

.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: calc(var(--z-modal-backdrop) - 1);
  backdrop-filter: blur(4px);
}

/* Mobile styles */
@media (max-width: var(--bp-tablet)) {
  .main-content {
    margin-left: 0;
  }

  .page-content {
    padding: var(--space-4);
  }
}

@media (min-width: var(--bp-tablet)) {
  .main-content {
    margin-left: 280px;
  }
}

@media (max-width: var(--bp-medium)) {
  .page-content {
    padding: var(--space-3);
  }
}

@media (max-width: var(--bp-mobile)) {
  .page-content {
    padding: var(--space-3) var(--space-2);
  }

  .page-container {
    padding: 0;
  }
}

@media (max-width: var(--bp-small)) {
  .page-content {
    padding: var(--space-2);
  }

  .sidebar-overlay {
    backdrop-filter: blur(2px);
  }
}

/* Dark theme */
[data-theme="dark"] .app-layout {
  background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-800) 100%);
}

[data-theme="dark"] .page-content {
  background-color: transparent;
}

[data-theme="dark"] .sidebar-overlay {
  background-color: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(12px);
}
</style>
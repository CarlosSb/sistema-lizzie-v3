<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'ghost', 'danger'].includes(value)
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['sm', 'default', 'lg'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  block: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'button'
  }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
  const classes = ['btn']

  // Variant classes
  classes.push(`btn-${props.variant}`)

  // Size classes
  if (props.size !== 'default') {
    classes.push(`btn-${props.size}`)
  }

  // Block class
  if (props.block) {
    classes.push('btn-block')
  }

  // Loading state
  if (props.loading) {
    classes.push('opacity-75', 'cursor-wait')
  }

  return classes.join(' ')
})

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<template>
  <button
    :class="buttonClasses"
    :disabled="disabled || loading"
    :type="type"
    @click="handleClick"
  >
    <span v-if="loading" class="loading-spinner mr-2"></span>
    <slot />
  </button>
</template>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-family: var(--font-body);
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
  user-select: none;
  position: relative;
  overflow: hidden;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

.btn-primary {
  background-color: #1a56db;
  color: white;
  border-color: #1a56db;
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.btn-primary:hover:not(:disabled) {
  background-color: #1e40af;
  border-color: #1e40af;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  transform: translateY(-1px);
}

.btn-primary:active {
  transform: translateY(0);
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.btn-secondary {
  background-color: transparent;
  color: #1a56db;
  border-color: #1a56db;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #eff6ff;
  border-color: #1e40af;
  color: #1e40af;
}

.btn-ghost {
  background-color: transparent;
  color: #1a56db;
  border-color: transparent;
}

.btn-ghost:hover:not(:disabled) {
  background-color: #eff6ff;
  color: #1e40af;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
  border-color: #dc2626;
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.btn-danger:hover:not(:disabled) {
  background-color: #b91c1c;
  border-color: #b91c1c;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  transform: translateY(-1px);
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-lg {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.btn-block {
  width: 100%;
}

.loading-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Ripple effect */
.btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
  pointer-events: none;
}

.btn:active::before {
  width: 300px;
  height: 300px;
}

/* ===== MOBILE ADJUSTMENTS ===== */
@media (max-width: 768px) {
  .btn {
    min-height: 44px; /* iOS touch target minimum */
    padding: 0.75rem 1rem;
    font-size: 16px; /* Prevent zoom on iOS */
  }

  .btn-sm {
    min-height: 40px;
    padding: 0.5rem 0.75rem;
    font-size: 14px;
  }

  .btn-lg {
    min-height: 48px;
    padding: 0.875rem 1.25rem;
  }
}
</style>
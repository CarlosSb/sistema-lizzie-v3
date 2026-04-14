<script setup>
import { computed } from 'vue'

const props = defineProps({
  hover: {
    type: Boolean,
    default: false
  },
  padding: {
    type: String,
    default: 'default',
    validator: (value) => ['none', 'sm', 'default', 'lg'].includes(value)
  },
  shadow: {
    type: String,
    default: 'sm',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl'].includes(value)
  }
})

const cardClasses = computed(() => {
  const classes = ['card']

  // Shadow classes
  if (props.shadow !== 'sm') {
    classes.push(`shadow-${props.shadow}`)
  }

  // Hover effect
  if (props.hover) {
    classes.push('hover-lift')
  }

  return classes.join(' ')
})

const bodyClasses = computed(() => {
  const classes = ['card-body']

  // Padding classes
  if (props.padding !== 'default') {
    classes.push(`p-${props.padding === 'none' ? '0' : props.padding === 'sm' ? '3' : props.padding === 'lg' ? '6' : '4'}`)
  }

  return classes.join(' ')
})
</script>

<template>
  <div :class="cardClasses">
    <div v-if="$slots.header" class="card-header">
      <slot name="header" />
    </div>

    <div v-if="$slots.default" :class="bodyClasses">
      <slot />
    </div>

    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
.card {
  background-color: var(--gray-50);
  border-radius: var(--radius-xl);
  border: 1px solid var(--gray-200);
  overflow: hidden;
  transition: all var(--transition-normal);
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  padding: var(--space-6);
  border-bottom: 1px solid var(--gray-200);
  background-color: var(--gray-25);
}

.card-body {
  padding: var(--space-6);
}

.card-footer {
  padding: var(--space-6);
  border-top: 1px solid var(--gray-200);
  background-color: var(--gray-25);
}

/* Shadow variants */
.shadow-none {
  box-shadow: none;
}

.shadow-sm {
  box-shadow: var(--shadow-sm);
}

.shadow-md {
  box-shadow: var(--shadow-md);
}

.shadow-lg {
  box-shadow: var(--shadow-lg);
}

.shadow-xl {
  box-shadow: var(--shadow-xl);
}

/* Hover effect */
.hover-lift {
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}

/* Dark theme */
[data-theme="dark"] .card {
  background-color: #1e293b;
  border-color: #334155;
}

/* ===== MOBILE ADJUSTMENTS ===== */
@media (max-width: 768px) {
  .card {
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  }

  .card-body {
    padding: 1rem;
  }

  .card-header,
  .card-footer {
    padding: 1rem;
  }
}

[data-theme="dark"] .card-header,
[data-theme="dark"] .card-footer {
  background-color: var(--gray-700);
  border-color: var(--gray-600);
}
</style>
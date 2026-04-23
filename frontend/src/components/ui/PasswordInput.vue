<script setup lang="ts">
import { ref, computed } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

interface Props {
  modelValue: string
  placeholder?: string
  disabled?: boolean
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  disabled: false,
  class: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const showPassword = ref(false)

const inputType = computed(() => showPassword.value ? 'text' : 'password')

const togglePassword = () => {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <div class="relative">
    <Input
      :type="inputType"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="class"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <Button
      type="button"
      variant="ghost"
      size="icon"
      class="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8 hover:bg-transparent"
      @click="togglePassword"
      :disabled="disabled"
      aria-label="Mostrar senha"
      :aria-pressed="showPassword"
    >
      <Eye v-if="showPassword" class="h-4 w-4 text-muted-foreground" />
      <EyeOff v-else class="h-4 w-4 text-muted-foreground" />
    </Button>
  </div>
</template>
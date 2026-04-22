<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = withDefaults(
  defineProps<{
    page: number
    lastPage: number
    disabled?: boolean
  }>(),
  { disabled: false }
)

const emit = defineEmits<{
  (e: 'update:page', value: number): void
}>()

const canPrev = computed(() => props.page > 1)
const canNext = computed(() => props.page < props.lastPage)

const goPrev = () => {
  if (!canPrev.value || props.disabled) return
  emit('update:page', props.page - 1)
}

const goNext = () => {
  if (!canNext.value || props.disabled) return
  emit('update:page', props.page + 1)
}
</script>

<template>
  <div v-if="lastPage > 1" class="flex items-center gap-3 p-1 bg-card border rounded-xl shadow-sm">
    <Button variant="ghost" size="sm" class="h-9 rounded-lg font-bold text-xs" :disabled="disabled || !canPrev" @click="goPrev">
      <ChevronLeft class="w-4 h-4 mr-1" />
      Anterior
    </Button>

    <div class="px-3 text-xs font-bold text-muted-foreground">
      Página {{ page }} de {{ lastPage }}
    </div>

    <Button variant="ghost" size="sm" class="h-9 rounded-lg font-bold text-xs" :disabled="disabled || !canNext" @click="goNext">
      Próxima
      <ChevronRight class="w-4 h-4 ml-1" />
    </Button>
  </div>
</template>


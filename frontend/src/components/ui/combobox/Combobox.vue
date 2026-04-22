<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

interface Props {
  modelValue?: string
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  disabled?: boolean
  items: { value: string; label: string }[]
}

interface Emits {
  (e: 'update:modelValue', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select...',
  searchPlaceholder: 'Search...',
  emptyText: 'No item found.',
  disabled: false,
  items: () => [],
})

const emits = defineEmits<Emits>()

const open = ref(false)
const value = computed({
  get: () => props.modelValue || '',
  set: (val) => emits('update:modelValue', val),
})

function selectItem(selectedLabel: string) {
  const item = props.items.find(i => i.label === selectedLabel)
  if (item) {
    value.value = item.value === value.value ? '' : item.value
    open.value = false
  }
}
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :disabled="disabled"
        class="w-full justify-between"
      >
        {{ props.items.find(item => item.value === value)?.label || placeholder }}
        <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-full p-0" align="start">
      <Command>
        <CommandInput :placeholder="searchPlaceholder" />
        <CommandList>
          <CommandEmpty>{{ emptyText }}</CommandEmpty>
          <CommandGroup>
            <CommandItem
              v-for="item in props.items"
              :key="item.value"
              :value="item.label"
              @select="(ev) => selectItem(String(ev.detail.value ?? ''))"
            >
              <CheckIcon
                :class="cn(
                  'mr-2 h-4 w-4',
                  value === item.value ? 'opacity-100' : 'opacity-0',
                )"
              />
              {{ item.label }}
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
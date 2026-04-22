<script setup lang="ts">
import type { PopoverTriggerProps } from "reka-ui"
import { PopoverTrigger, useForwardProps } from "reka-ui"

const props = defineProps<PopoverTriggerProps>()
const delegatedProps = useForwardProps(props)
</script>

<template>
  <PopoverTrigger v-bind="delegatedProps">
    <slot />
  </PopoverTrigger>
</template>
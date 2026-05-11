<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'

interface Props {
  pdfUrl?: string
  pdfBlob?: Blob
  width?: number | string
  height?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  width: '100%',
  height: '100%',
})

const emit = defineEmits<{
  loaded: [numPages: number]
  error: [error: Error]
  pageChanged: [pageNumber: number]
}>()

const objectUrl = ref('')
const error = ref('')

const revokeObjectUrl = () => {
  if (objectUrl.value && objectUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(objectUrl.value)
  }
  objectUrl.value = ''
}

const loadPdf = async (urlOrBlob: string | Blob | undefined) => {
  error.value = ''
  revokeObjectUrl()

  if (!urlOrBlob) return

  try {
    objectUrl.value = typeof urlOrBlob === 'string'
      ? urlOrBlob
      : URL.createObjectURL(urlOrBlob)

    emit('loaded', 1)
  } catch (err) {
    const normalizedError = err instanceof Error ? err : new Error(String(err))
    error.value = normalizedError.message || 'Erro ao carregar PDF'
    emit('error', normalizedError)
  }
}

watch(() => props.pdfUrl, (newUrl) => {
  loadPdf(newUrl || props.pdfBlob)
})

watch(() => props.pdfBlob, (newBlob) => {
  loadPdf(newBlob || props.pdfUrl)
}, { immediate: true })

onBeforeUnmount(() => {
  revokeObjectUrl()
})

defineExpose({
  loadPdf,
  nextPage: () => emit('pageChanged', 1),
  prevPage: () => emit('pageChanged', 1),
  goToPage: () => emit('pageChanged', 1),
  zoomIn: () => undefined,
  zoomOut: () => undefined,
  resetZoom: () => undefined,
})

const dimensionToCss = (value: number | string) => {
  return typeof value === 'number' ? `${value}px` : value
}
</script>

<template>
  <div class="pdf-viewer" :style="{ width: dimensionToCss(props.width), height: dimensionToCss(props.height) }">
    <div v-if="error" class="error-overlay">
      <div class="error-message">
        <p>{{ error }}</p>
        <button @click="loadPdf(props.pdfUrl || props.pdfBlob)" class="retry-button">
          Tentar Novamente
        </button>
      </div>
    </div>

    <iframe
      v-else-if="objectUrl"
      :src="objectUrl"
      class="pdf-frame"
      title="Preview do PDF"
    />

    <div v-else class="empty-state">
      <p>Carregando PDF...</p>
    </div>
  </div>
</template>

<style scoped>
.pdf-viewer {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  position: relative;
  overflow: hidden;
}

.pdf-frame {
  width: 100%;
  height: 100%;
  border: 0;
  display: block;
  background: white;
}

.empty-state,
.error-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.95);
  z-index: 10;
}

.error-message {
  text-align: center;
  padding: 20px;
}

.error-message p {
  color: #dc2626;
  margin-bottom: 16px;
  font-weight: 500;
}

.retry-button {
  padding: 8px 16px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.retry-button:hover {
  background: #1d4ed8;
}
</style>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'

// Configure PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.min.js`

interface Props {
  pdfUrl?: string
  pdfBlob?: Blob
  width?: number
  height?: number
  scale?: number
}

const props = withDefaults(defineProps<Props>(), {
  width: 800,
  height: 600,
  scale: 1.0
})

const emit = defineEmits<{
  loaded: [numPages: number]
  error: [error: Error]
  pageChanged: [pageNumber: number]
}>()

const canvasRef = ref<HTMLCanvasElement>()
const pdfDocument = ref<pdfjsLib.PDFDocumentProxy>()
const currentPage = ref(1)
const numPages = ref(0)
const scale = ref(props.scale)
const loading = ref(false)
const error = ref<string>('')

const loadPdf = async (urlOrBlob: string | Blob | undefined) => {
  if (!urlOrBlob) return
  try {
    loading.value = true
    error.value = ''

    let pdfData: ArrayBuffer | Uint8Array

    if (typeof urlOrBlob === 'string') {
      // Load from URL
      const response = await fetch(urlOrBlob)
      pdfData = await response.arrayBuffer()
    } else {
      // Load from Blob
      pdfData = await urlOrBlob.arrayBuffer()
    }

    pdfDocument.value = await pdfjsLib.getDocument({ data: pdfData }).promise
    numPages.value = pdfDocument.value.numPages

    emit('loaded', numPages.value)
    await renderPage(currentPage.value)

  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erro ao carregar PDF'
    emit('error', err instanceof Error ? err : new Error(String(err)))
  } finally {
    loading.value = false
  }
}

const renderPage = async (pageNumber: number) => {
  if (!pdfDocument.value || !canvasRef.value) return

  try {
    const page = await pdfDocument.value.getPage(pageNumber)
    const viewport = page.getViewport({ scale: scale.value })

    const canvas = canvasRef.value
    const context = canvas.getContext('2d')

    if (!context) return

    canvas.height = viewport.height
    canvas.width = viewport.width

    const renderContext = {
      canvasContext: context,
      viewport: viewport,
      canvas: canvas
    }

    await page.render(renderContext).promise
  } catch (err) {
    console.error('Erro ao renderizar página:', err)
  }
}

const nextPage = () => {
  if (currentPage.value < numPages.value) {
    currentPage.value++
    renderPage(currentPage.value)
    emit('pageChanged', currentPage.value)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    renderPage(currentPage.value)
    emit('pageChanged', currentPage.value)
  }
}

const goToPage = (pageNumber: number) => {
  if (pageNumber >= 1 && pageNumber <= numPages.value) {
    currentPage.value = pageNumber
    renderPage(currentPage.value)
    emit('pageChanged', currentPage.value)
  }
}

const zoomIn = () => {
  scale.value = Math.min(scale.value + 0.25, 3.0)
  renderPage(currentPage.value)
}

const zoomOut = () => {
  scale.value = Math.max(scale.value - 0.25, 0.5)
  renderPage(currentPage.value)
}

const resetZoom = () => {
  scale.value = 1.0
  renderPage(currentPage.value)
}

// Watch for prop changes
watch(() => props.pdfUrl, (newUrl: string | undefined) => {
  if (newUrl) {
    loadPdf(newUrl)
  }
})

watch(() => props.pdfBlob, (newBlob: Blob | undefined) => {
  if (newBlob) {
    loadPdf(newBlob)
  }
})

onMounted(() => {
  loadPdf(props.pdfUrl || props.pdfBlob)
})

// Expose methods for parent components
defineExpose({
  loadPdf,
  nextPage,
  prevPage,
  goToPage,
  zoomIn,
  zoomOut,
  resetZoom
})
</script>

<template>
  <div class="pdf-viewer" :style="{ width: props.width + 'px', height: props.height + 'px' }">
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Carregando PDF...</p>
    </div>

    <div v-else-if="error" class="error-overlay">
      <div class="error-message">
        <p>{{ error }}</p>
        <button @click="loadPdf(props.pdfUrl || props.pdfBlob)" class="retry-button">
          Tentar Novamente
        </button>
      </div>
    </div>

    <div v-else class="viewer-container">
      <!-- Toolbar -->
      <div class="toolbar">
        <div class="page-controls">
          <button @click="prevPage" :disabled="currentPage <= 1" class="nav-button">
            ‹ Anterior
          </button>
          <span class="page-info">
            Página {{ currentPage }} de {{ numPages }}
          </span>
          <button @click="nextPage" :disabled="currentPage >= numPages" class="nav-button">
            Próxima ›
          </button>
        </div>

        <div class="zoom-controls">
          <button @click="zoomOut" class="zoom-button">−</button>
          <span class="zoom-level">{{ Math.round(scale * 100) }}%</span>
          <button @click="zoomIn" class="zoom-button">+</button>
          <button @click="resetZoom" class="reset-zoom">100%</button>
        </div>
      </div>

      <!-- Canvas -->
      <div class="canvas-container" :style="{ height: (props.height - 60) + 'px' }">
        <canvas
          ref="canvasRef"
          class="pdf-canvas"
          :style="{ maxWidth: '100%', maxHeight: '100%' }"
        ></canvas>
      </div>
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

.loading-overlay,
.error-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  z-index: 10;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.retry-button:hover {
  background: #2563eb;
}

.viewer-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.page-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-button {
  padding: 6px 12px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.nav-button:disabled {
  background: #d1d5db;
  cursor: not-allowed;
}

.nav-button:hover:not(:disabled) {
  background: #2563eb;
}

.page-info {
  font-weight: 500;
  color: #374151;
  min-width: 120px;
  text-align: center;
}

.zoom-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.zoom-button {
  width: 32px;
  height: 32px;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
}

.zoom-button:hover {
  background: #4b5563;
}

.zoom-level {
  font-weight: 500;
  color: #374151;
  min-width: 50px;
  text-align: center;
}

.reset-zoom {
  padding: 6px 12px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.reset-zoom:hover {
  background: #059669;
}

.canvas-container {
  flex: 1;
  overflow: auto;
  padding: 16px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.pdf-canvas {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border-radius: 4px;
}
</style>
<script setup lang="ts">
import { ref } from 'vue'
import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'
// @ts-ignore
import html2pdf from 'html2pdf.js'

interface Props {
  pedido: any
  cliente: any
  mode: 'complete' | 'summary'
  showButton?: boolean
  preview?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  pdfGenerated: [mode: 'complete' | 'summary']
  previewRequested: [mode: 'complete' | 'summary']
  closePreview: []
}>()

const pdfRef = ref<any>(null)

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
}

const previewPDF = () => {
  emit('previewRequested', props.mode)
}

const generatePDF = async () => {
  if (!pdfRef.value) return

  const options = {
    margin: 10,
    filename: 'pedido-' + props.pedido.id_pedido + '-' + props.mode + '.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: {
      scale: 2,
      onclone: (clonedDoc: Document) => {
        // Remove all stylesheets to avoid unsupported color functions
        clonedDoc.querySelectorAll('link[rel="stylesheet"], style').forEach(el => el.remove());
        // Set inline styles from computed styles
        clonedDoc.querySelectorAll('*').forEach(el => {
          const htmlEl = el as HTMLElement;
          const computed = window.getComputedStyle(el);
          htmlEl.style.cssText = '';
          htmlEl.style.backgroundColor = computed.backgroundColor;
          htmlEl.style.color = computed.color;
          htmlEl.style.fontSize = computed.fontSize;
          htmlEl.style.fontFamily = computed.fontFamily;
          htmlEl.style.fontWeight = computed.fontWeight;
          htmlEl.style.textAlign = computed.textAlign;
          htmlEl.style.padding = computed.padding;
          htmlEl.style.margin = computed.margin;
          htmlEl.style.border = computed.border;
          htmlEl.style.borderRadius = computed.borderRadius;
          htmlEl.style.boxShadow = computed.boxShadow;
          htmlEl.style.width = computed.width;
          htmlEl.style.height = computed.height;
          htmlEl.style.display = computed.display;
          htmlEl.style.position = computed.position;
          htmlEl.style.lineHeight = computed.lineHeight;
          // Add more if needed
        });
      }
    },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  }

  try {
    const pdfBlob = await html2pdf().set(options).from(pdfRef.value as HTMLElement).outputPdf('blob')
    printPdfBlob(pdfBlob)
  } catch (error) {
    console.error('Erro ao gerar PDF com html2pdf.js, tentando fallback:', error)
    // Fallback with jsPDF + html2canvas
    const canvas = await html2canvas(pdfRef.value as HTMLElement, options.html2canvas)
    const imgData = canvas.toDataURL(options.image.type, options.image.quality)
    const pdf = new jsPDF('portrait', 'mm', 'a4')
    const imgWidth = pdf.internal.pageSize.getWidth()
    const imgHeight = (canvas.height * imgWidth) / canvas.width
    pdf.addImage(imgData, options.image.type.toUpperCase(), 0, 0, imgWidth, imgHeight)
    const pdfBlob = pdf.output('blob')
    printPdfBlob(pdfBlob)
  }

  function printPdfBlob(blob: Blob) {
    const url = URL.createObjectURL(blob)
    const iframe = document.createElement('iframe')
    iframe.style.display = 'none'
    iframe.src = url
    document.body.appendChild(iframe)
    // Use setTimeout since onload doesn't fire for PDF blobs in most browsers
    setTimeout(() => {
      try {
        iframe.contentWindow?.print()
      } catch (error) {
        console.error('Erro ao imprimir PDF via iframe, tentando fallback:', error)
        // Fallback: open in new tab
        const newWindow = window.open(url, '_blank')
        newWindow?.focus()
        setTimeout(() => {
          newWindow?.print()
        }, 500)
      }
      document.body.removeChild(iframe)
      URL.revokeObjectURL(url)
    }, 500)
  }

  emit('pdfGenerated', props.mode)
}

const sumQuantidades = (item: any) => {
  const sizes = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12', 'lisa']
  return sizes.reduce((acc, key) => acc + (Number(item[key]) || 0), 0)
}

const getSizeBadges = (item: any) => {
  const sizes = [
    { key: 'tam_pp', label: 'PP' },
    { key: 'tam_p', label: 'P' },
    { key: 'tam_m', label: 'M' },
    { key: 'tam_g', label: 'G' },
    { key: 'tam_u', label: 'U' },
    { key: 'tam_rn', label: 'RN' },
    { key: 'lisa', label: 'LISA' },
    { key: 'ida_1', label: '1' },
    { key: 'ida_2', label: '2' },
    { key: 'ida_3', label: '3' },
    { key: 'ida_4', label: '4' },
    { key: 'ida_6', label: '6' },
    { key: 'ida_8', label: '8' },
    { key: 'ida_10', label: '10' },
    { key: 'ida_12', label: '12' },
  ]
  return sizes.filter(s => item[s.key] > 0).map(s => `${s.label}:${item[s.key]}`)
}


</script>

<template>
  <div v-if="preview" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-screen overflow-auto mx-4">
      <div class="flex justify-between items-center p-4 border-b">
        <h2 class="text-lg font-semibold">Preview do PDF</h2>
        <button @click="$emit('closePreview')" class="text-gray-500 hover:text-gray-700">&times;</button>
      </div>
      <div class="p-4">
        <div ref="pdfRef" class="print-container bg-white text-black p-8 max-w-4xl mx-auto">
          <!-- Header -->
          <div class="border-b-2 border-gray-800 pb-4 mb-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <!-- Logo placeholder -->
                <div class="w-16 h-16 bg-gray-200 border border-gray-300 flex items-center justify-center mr-4">
                  <span class="text-xs text-gray-500">LOGO</span>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-800">Sistema Lizzie</h1>
                  <p class="text-sm text-gray-600">Sistema de Gestão Empresarial</p>
                </div>
              </div>
              <div class="text-right">
                <h2 class="text-xl font-semibold">Pedido #{{ pedido.id_pedido }}</h2>
                <p class="text-sm text-gray-600">Data: {{ pedido.data_pedido }}</p>
                <p class="text-sm text-gray-600">Status: {{ pedido.status === 1 ? 'ABERTO' : pedido.status === 2 ? 'PENDENTE' : pedido.status === 3 ? 'CANCELADO' : 'CONCLUÍDO' }}</p>
              </div>
            </div>
          </div>

          <!-- Client Info -->
          <div v-if="mode === 'complete'" class="mb-8">
            <h3 class="section-header">Dados do Cliente</h3>
            <div class="info-box">
              <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                  <p><strong>Nome/Razão Social:</strong> {{ cliente?.nome_fantasia || cliente?.razao_social || pedido.razao_social }}</p>
                  <p><strong>Responsável:</strong> {{ cliente?.responsavel }}</p>
                  <p><strong>CPF/CNPJ:</strong> {{ cliente?.cpf_cnpj }}</p>
                </div>
                <div class="space-y-2">
                  <p><strong>Email:</strong> {{ cliente?.email }}</p>
                  <p><strong>Contato:</strong> {{ cliente?.contato_1 }}</p>
                  <p><strong>Endereço:</strong> {{ cliente?.endereco }}, {{ cliente?.bairro }}</p>
                  <p><strong>Cidade/Estado:</strong> {{ cliente?.cidade }}, {{ cliente?.estado }} - {{ cliente?.cep }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="mb-8">
            <h3 class="section-header">Itens do Pedido</h3>
            <div class="overflow-hidden border border-gray-300 rounded-lg">
              <table class="table w-full border-collapse">
                <thead>
                  <tr>
                    <th class="p-3 text-left">Produto</th>
                    <th class="p-3 text-center">Quantidade</th>
                    <th class="p-3 text-center">Tamanhos</th>
                    <th class="p-3 text-right">Valor Unitário</th>
                    <th class="p-3 text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="item in pedido.itens" :key="item.produto + '-' + item.referencia">
                    <tr>
                      <td class="p-3">{{ item.produto }} - {{ item.referencia }}</td>
                      <td class="p-3 text-center">{{ sumQuantidades(item) }}</td>
                      <td class="p-3 text-center">{{ getSizeBadges(item).join(' | ') }}</td>
                      <td class="p-3 text-right">{{ formatCurrency(item.total_item / sumQuantidades(item)) }}</td>
                      <td class="p-3 text-right font-medium">{{ formatCurrency(item.total_item) }}</td>
                    </tr>
                  </template>
                  <!-- Total Row -->
                  <tr class="bg-gray-100 font-bold">
                    <td colspan="4" class="p-3 text-right font-bold text-lg">Total do Pedido:</td>
                    <td class="p-3 text-right font-bold text-lg">{{ formatCurrency(pedido.total_pedido) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Order Summary and Payment -->
          <div class="mb-8">
            <h3 class="section-header">Resumo do Pedido e Forma de Pagamento</h3>
            <div class="info-box">
              <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                  <p><strong>Status:</strong> {{ pedido.status === 1 ? 'ABERTO' : pedido.status === 2 ? 'PENDENTE' : pedido.status === 3 ? 'CANCELADO' : 'CONCLUÍDO' }}</p>
                  <p><strong>Forma de Pagamento:</strong> {{ pedido.forma_pag }}</p>
                </div>

                <div class="space-y-1">
                  <p v-if="pedido.ped_desconto > 0"><strong class="text-gray-700">Subtotal:</strong> {{ formatCurrency(pedido.total_pedido + pedido.ped_desconto) }}</p>
                  <p v-if="pedido.ped_desconto > 0"><strong class="text-gray-700">Desconto:</strong> {{ formatCurrency(pedido.ped_desconto) }}</p>
                  <p><strong class="text-gray-700 text-lg">Total:</strong> {{ formatCurrency(pedido.total_pedido) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Information -->
          <div v-if="mode === 'complete'" class="mb-8">
            <h3 class="section-header">Informações Adicionais</h3>
            <div class="grid grid-cols-1 gap-6">
              <div v-if="pedido.obs_pedido" class="space-y-2">
                <h4 class="font-semibold">Observações do Pedido:</h4>
                <p class="text-sm info-box">{{ pedido.obs_pedido }}</p>
              </div>
              <div v-if="pedido.obs_entrega" class="space-y-2">
                <h4 class="font-semibold">Observações de Entrega:</h4>
                <p class="text-sm info-box">{{ pedido.obs_entrega }}</p>
              </div>
            </div>
          </div>

          <!-- Signatures -->
          <div class="signature-section">
            <h3 class="section-header">Assinaturas</h3>
            <div class="grid grid-cols-2 gap-12">
              <div class="text-center">
                <div class="signature-line"></div>
                <p class="text-sm font-medium">Cliente</p>
                <p class="text-xs text-gray-600">{{ cliente?.nome_fantasia || cliente?.razao_social || pedido.razao_social }}</p>
              </div>
              <div class="text-center">
                <div class="signature-line"></div>
                <p class="text-sm font-medium">Sistema Lizzie</p>
                <p class="text-xs text-gray-600">Representante Autorizado</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="text-center text-xs text-gray-600 mt-12 pt-6 border-t-2 border-gray-300">
            <p class="font-medium">Documento gerado em {{ new Date().toLocaleString('pt-BR') }}</p>
            <p class="mt-1">Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.</p>
          </div>
        </div>
      </div>
      <div class="flex justify-end space-x-4 p-4 border-t">
        <button @click="$emit('closePreview')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Fechar</button>
        <button @click="generatePDF" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Imprimir</button>
      </div>
    </div>
  </div>
  <div v-else ref="pdfRef" class="print-container bg-white text-black p-8 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="border-b-2 border-gray-800 pb-4 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <!-- Logo placeholder -->
          <div class="w-16 h-16 bg-gray-200 border border-gray-300 flex items-center justify-center mr-4">
            <span class="text-xs text-gray-500">LOGO</span>
          </div>
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Sistema Lizzie</h1>
            <p class="text-sm text-gray-600">Sistema de Gestão Empresarial</p>
          </div>
        </div>
        <div class="text-right">
          <h2 class="text-xl font-semibold">Pedido #{{ pedido.id_pedido }}</h2>
          <p class="text-sm text-gray-600">Data: {{ pedido.data_pedido }}</p>
          <p class="text-sm text-gray-600">Status: {{ pedido.status === 1 ? 'ABERTO' : pedido.status === 2 ? 'PENDENTE' : pedido.status === 3 ? 'CANCELADO' : 'CONCLUÍDO' }}</p>
        </div>
      </div>
    </div>

    <!-- Client Info -->
    <div v-if="mode === 'complete'" class="mb-8">
      <h3 class="section-header">Dados do Cliente</h3>
      <div class="info-box">
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <p><strong>Nome/Razão Social:</strong> {{ cliente?.nome_fantasia || cliente?.razao_social || pedido.razao_social }}</p>
            <p><strong>Responsável:</strong> {{ cliente?.responsavel }}</p>
            <p><strong>CPF/CNPJ:</strong> {{ cliente?.cpf_cnpj }}</p>
          </div>
          <div class="space-y-2">
            <p><strong>Email:</strong> {{ cliente?.email }}</p>
            <p><strong>Contato:</strong> {{ cliente?.contato_1 }}</p>
            <p><strong>Endereço:</strong> {{ cliente?.endereco }}, {{ cliente?.bairro }}</p>
            <p><strong>Cidade/Estado:</strong> {{ cliente?.cidade }}, {{ cliente?.estado }} - {{ cliente?.cep }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="mb-8">
      <h3 class="section-header">Itens do Pedido</h3>
      <div class="overflow-hidden border border-gray-300 rounded-lg">
        <table class="table w-full border-collapse">
          <thead>
            <tr>
              <th class="p-3 text-left">Produto</th>
              <th class="p-3 text-center">Quantidade</th>
              <th class="p-3 text-center">Tamanhos</th>
              <th class="p-3 text-right">Valor Unitário</th>
              <th class="p-3 text-right">Total</th>
            </tr>
          </thead>
          <tbody>
             <template v-for="item in pedido.itens" :key="item.produto + '-' + item.referencia">
              <tr>
                <td class="p-3">{{ item.produto }} - {{ item.referencia }}</td>
                <td class="p-3 text-center">{{ sumQuantidades(item) }}</td>
                <td class="p-3 text-center">{{ getSizeBadges(item).join(' | ') }}</td>
                <td class="p-3 text-right">{{ formatCurrency(item.total_item / sumQuantidades(item)) }}</td>
                <td class="p-3 text-right font-medium">{{ formatCurrency(item.total_item) }}</td>
              </tr>
            </template>
            <!-- Total Row -->
            <tr class="bg-gray-100 font-bold">
              <td colspan="4" class="p-3 text-right font-bold text-lg">Total do Pedido:</td>
              <td class="p-3 text-right font-bold text-lg">{{ formatCurrency(pedido.total_pedido) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
     </div>

     <!-- Order Summary and Payment -->
    <div class="mb-8">
      <h3 class="section-header">Resumo do Pedido e Forma de Pagamento</h3>
      <div class="info-box">
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <p><strong>Status:</strong> {{ pedido.status === 1 ? 'ABERTO' : pedido.status === 2 ? 'PENDENTE' : pedido.status === 3 ? 'CANCELADO' : 'CONCLUÍDO' }}</p>
            <p><strong>Forma de Pagamento:</strong> {{ pedido.forma_pag }}</p>
          </div>

          <div class="space-y-1">
            <p v-if="pedido.ped_desconto > 0"><strong class="text-gray-700">Subtotal:</strong> {{ formatCurrency(pedido.total_pedido + pedido.ped_desconto) }}</p>
            <p v-if="pedido.ped_desconto > 0"><strong class="text-gray-700">Desconto:</strong> {{ formatCurrency(pedido.ped_desconto) }}</p>
            <p><strong class="text-gray-700 text-lg">Total:</strong> {{ formatCurrency(pedido.total_pedido) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Additional Information -->
    <div v-if="mode === 'complete'" class="mb-8">
      <h3 class="section-header">Informações Adicionais</h3>
      <div class="grid grid-cols-1 gap-6">
        <div v-if="pedido.obs_pedido" class="space-y-2">
          <h4 class="font-semibold">Observações do Pedido:</h4>
          <p class="text-sm info-box">{{ pedido.obs_pedido }}</p>
        </div>
        <div v-if="pedido.obs_entrega" class="space-y-2">
          <h4 class="font-semibold">Observações de Entrega:</h4>
          <p class="text-sm info-box">{{ pedido.obs_entrega }}</p>
        </div>
      </div>
     </div>

     <!-- Signatures -->
    <div class="signature-section">
      <h3 class="section-header">Assinaturas</h3>
      <div class="grid grid-cols-2 gap-12">
        <div class="text-center">
          <div class="signature-line"></div>
          <p class="text-sm font-medium">Cliente</p>
          <p class="text-xs text-gray-600">{{ cliente?.nome_fantasia || cliente?.razao_social || pedido.razao_social }}</p>
        </div>
        <div class="text-center">
          <div class="signature-line"></div>
          <p class="text-sm font-medium">Sistema Lizzie</p>
          <p class="text-xs text-gray-600">Representante Autorizado</p>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-gray-600 mt-12 pt-6 border-t-2 border-gray-300">
      <p class="font-medium">Documento gerado em {{ new Date().toLocaleString('pt-BR') }}</p>
      <p class="mt-1">Sistema Lizzie - Gestão Empresarial | Este documento é oficial e foi gerado automaticamente pelo sistema.</p>
    </div>

    <!-- Buttons -->
    <div v-if="showButton && !preview" class="text-center mt-4 no-print space-x-4">
      <button @click="previewPDF" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium shadow-md">
        Preview PDF
      </button>
      <button @click="generatePDF" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-md">
        Imprimir PDF
      </button>
    </div>
  </div>
</template>

<style scoped>
.print-container {
  font-family: 'Arial', sans-serif;
  line-height: 1.6;
  color: #1f2937;
  background: white;
}

h1, h2, h3, h4 {
  color: #111827;
  margin-bottom: 0.5rem;
  font-weight: 700;
}

strong {
  font-weight: 600;
  color: #374151;
}

.table th {
  background-color: #f3f4f6;
  font-weight: 600;
  color: #111827;
  border-bottom: 2px solid #d1d5db;
}

.table td {
  border-bottom: 1px solid #e5e7eb;
  padding: 0.75rem;
}

.table tr:last-child td {
  border-bottom: none;
}

.signature-section {
  border-top: 1px solid #d1d5db;
  padding-top: 2rem;
  margin-top: 3rem;
}

.signature-line {
  border-bottom: 1px solid #6b7280;
  padding-bottom: 3rem;
  margin-bottom: 0.5rem;
  width: 100%;
}

.info-box {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}

.section-header {
  border-bottom: 1px solid #d1d5db;
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
  font-size: 1.125rem;
  font-weight: 700;
  color: #111827;
}

.no-print {
  display: block;
}

@media print {
  .print-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 20px;
    box-shadow: none;
    background: white;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
  }
  .no-print {
    display: none !important;
  }
  .bg-gray-50 {
    background-color: #f9fafb !important;
  }
  .border-gray-300 {
    border-color: #d1d5db !important;
  }
}
</style>
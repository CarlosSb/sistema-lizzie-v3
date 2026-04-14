<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NButton, NIcon, NSpin, NSpace, NDescriptions, NDescriptionsItem, useMessage } from 'naive-ui'
import { PrintOutline, ArrowBackOutline, QrCodeOutline } from '@vicons/ionicons5'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()
const loading = ref(true)
const pedido = ref(null)
const itens = ref([])

const pedidoId = computed(() => route.params.id)

const statusLabels = { 1: 'Aberto', 2: 'Pendente', 3: 'Cancelado', 4: 'Concluído' }
const statusColors = { 1: 'blue', 2: 'yellow', 3: 'red', 4: 'green' }

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  await loadPedido()
})

async function loadPedido() {
  loading.value = true
  try {
    const response = await api.getPedido(pedidoId.value)
    if (response.data.success) {
      pedido.value = response.data.data
      itens.value = response.data.data.itens || []
    } else {
      message.error('Pedido não encontrado')
    }
  } catch (error) {
    message.error('Erro ao carregar pedido')
  } finally {
    loading.value = false
  }
}

function formatMoney(val) {
  if (!val) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val)
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR')
}

function imprimir() {
  window.print()
}

const itensPorTamanho = computed(() => {
  const grouped = {}
  itens.value.forEach(item => {
    const key = `${item.produto || 'Produto #' + item.id_produto} (${item.referencia || '-'})`
    if (!grouped[key]) {
      grouped[key] = { nome: key, tamanhos: {}, total: 0, preco: item.valor_unt }
    }
    const tamanhos = ['tam_pp', 'tam_p', 'tam_m', 'tam_g', 'tam_u', 'tam_rn', 'tam_ida_1', 'tam_ida_2', 'tam_ida_3', 'tam_ida_4', 'tam_ida_6', 'tam_ida_8', 'tam_ida_10', 'tam_ida_12', 'tam_lisa']
    tamanhos.forEach(t => {
      const val = item[t] || 0
      if (val > 0) {
        const tm = t.replace('tam_', '').replace('ida_', 'ida ')
        grouped[key].tamanhos[tm.toUpperCase()] = val
        grouped[key].total += val
      }
    })
  })
  return Object.values(grouped)
})

const totalGeral = computed(() => {
  return itens.value.reduce((sum, item) => sum + (parseFloat(item.total_item) || 0), 0)
})

const totalItens = computed(() => {
  return itens.value.reduce((sum, item) => {
    const count = (item.tam_pp || 0) + (item.tam_p || 0) + (item.tam_m || 0) + (item.tam_g || 0) + (item.tam_u || 0) + (item.tam_rn || 0) + (item.tam_ida_1 || 0) + (item.tam_ida_2 || 0) + (item.tam_ida_3 || 0) + (item.tam_ida_4 || 0) + (item.tam_ida_6 || 0) + (item.tam_ida_8 || 0)
    return sum + count
  }, 0)
})
</script>

<template>
  <div class="print-container">
    <div class="no-print fixed top-4 right-4 z-50 flex gap-2">
      <NButton @click="router.back()" quaternary>
        <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
        Voltar
      </NButton>
      <NButton type="primary" @click="imprimir">
        <template #icon><NIcon><PrintOutline /></NIcon></template>
        Imprimir
      </NButton>
    </div>

    <NSpin :show="loading">
      <div v-if="pedido" class="print-area">
        <div class="print-header">
          <div class="logo-area">
            <h1>LIZZIE - AMOR DE MÃE</h1>
            <p>Roupas Infantis e Baby</p>
          </div>
          <div class="order-info">
            <div class="order-id">OS #{{ pedido.id_pedido }}</div>
            <div class="order-date">{{ formatDate(pedido.data_pedido) }}</div>
          </div>
        </div>

        <div class="client-section">
          <div class="client-name">{{ pedido.razao_social || 'Cliente #' + pedido.id_cliente }}</div>
          <div class="client-details">
            <span v-if="pedido.pessoa === 'J'">CNPJ: {{ pedido.cpf_cnpj }}</span>
            <span v-else>CPF: {{ pedido.cpf_cnpj }}</span>
            <span>{{ pedido.endereco }}, {{ pedido.numero }}</span>
            <span>{{ pedido.bairro }} - {{ pedido.cidade }}/{{ pedido.estado }}</span>
            <span>Fone: {{ pedido.telefone }}</span>
          </div>
        </div>

        <div class="vendedor-section">
          <strong>Vendedor:</strong> {{ pedido.nome_vendedor || 'Não identificado' }}
        </div>

        <table class="items-table">
          <thead>
            <tr>
              <th>Produto</th>
              <th>PP</th>
              <th>P</th>
              <th>M</th>
              <th>G</th>
              <th>U</th>
              <th>RN</th>
              <th>Ida</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in itensPorTamanho" :key="item.nome">
              <td class="produto-nome">{{ item.nome }}</td>
              <td>{{ item.tamanhos.PP || '-' }}</td>
              <td>{{ item.tamanhos.P || '-' }}</td>
              <td>{{ item.tamanhos.M || '-' }}</td>
              <td>{{ item.tamanhos.G || '-' }}</td>
              <td>{{ item.tamanhos.U || '-' }}</td>
              <td>{{ item.tamanhos.RN || '-' }}</td>
              <td>{{ item.tamanhos['IDA 1'] || item.tamanhos['IDA 2'] || item.tamanhos['IDA 3'] || item.tamanhos['IDA 4'] || item.tamanhos['IDA 6'] || item.tamanhos['IDA 8'] || '-' }}</td>
              <td class="total-cell">{{ item.total }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="8">TOTAL GERAL</td>
              <td class="total-cell">{{ totalItens }}</td>
            </tr>
          </tfoot>
        </table>

        <div class="observations" v-if="pedido.obs_pedido || pedido.obs_entrega">
          <div v-if="pedido.obs_pedido"><strong>Observações:</strong> {{ pedido.obs_pedido }}</div>
          <div v-if="pedido.obs_entrega"><strong>Entrega:</strong> {{ pedido.obs_entrega }}</div>
        </div>

        <div class="totals-section">
          <div class="total-row">
            <span>Subtotal:</span>
            <span>{{ formatMoney(pedido.total_pedido) }}</span>
          </div>
          <div class="total-row" v-if="pedido.desconto_valor > 0">
            <span>Desconto:</span>
            <span>-{{ formatMoney(pedido.desconto_valor) }}</span>
          </div>
          <div class="total-row total-final">
            <span>TOTAL:</span>
            <span>{{ formatMoney(pedido.total_pedido) }}</span>
          </div>
        </div>

        <div class="footer">
          <div class="barcode-area">
            <div class="barcode-placeholder">*{{ pedido.id_pedido }}*</div>
          </div>
          <p>Ordem de Serviço - Lizzie Amor de Mãe</p>
          <p>{{ new Date().toLocaleString() }}</p>
        </div>
      </div>
    </NSpin>
  </div>
</template>

<style scoped>
.print-container {
  min-height: 100vh;
  background: #fff;
  padding: 20px;
}

.print-area {
  max-width: 80mm;
  margin: 0 auto;
  font-size: 10px;
  font-family: 'Courier New', monospace;
}

.print-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 2px solid #000;
  padding-bottom: 10px;
  margin-bottom: 10px;
}

.logo-area h1 {
  font-size: 14px;
  margin: 0;
  font-weight: bold;
}

.logo-area p {
  margin: 0;
  font-size: 9px;
}

.order-info {
  text-align: right;
}

.order-id {
  font-size: 16px;
  font-weight: bold;
}

.client-section {
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px dashed #ccc;
}

.client-name {
  font-weight: bold;
  font-size: 12px;
  margin-bottom: 4px;
}

.client-details {
  font-size: 9px;
  display: flex;
  flex-direction: column;
}

.vendedor-section {
  margin-bottom: 10px;
  font-size: 10px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 10px;
}

.items-table th,
.items-table td {
  border: 1px solid #000;
  padding: 4px;
  text-align: center;
  font-size: 9px;
}

.items-table th {
  background: #f0f0f0;
  font-weight: bold;
}

.produto-nome {
  text-align: left !important;
  font-size: 8px;
}

.total-cell {
  font-weight: bold;
}

.observations {
  margin-bottom: 10px;
  padding: 8px;
  border: 1px solid #ccc;
  font-size: 9px;
}

.totals-section {
  margin-bottom: 10px;
  padding: 8px;
  border: 1px solid #000;
}

.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
}

.total-final {
  font-weight: bold;
  font-size: 12px;
  border-top: 1px solid #000;
  padding-top: 4px;
  margin-top: 4px;
}

.footer {
  text-align: center;
  font-size: 8px;
  margin-top: 20px;
  padding-top: 10px;
  border-top: 1px solid #000;
}

.barcode-area {
  margin-bottom: 10px;
}

.barcode-placeholder {
  font-family: 'Libre Barcode 39', cursive;
  font-size: 24px;
  letter-spacing: 2px;
}

@media print {
  .no-print {
    display: none !important;
  }
  
  .print-container {
    padding: 0;
    margin: 0;
  }
  
  .print-area {
    max-width: 100%;
  }
}
</style>
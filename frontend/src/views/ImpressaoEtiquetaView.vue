<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'
import { NCard, NButton, NIcon, NSpin, NSelect, useMessage } from 'naive-ui'
import { PrintOutline, ArrowBackOutline } from '@vicons/ionicons5'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const message = useMessage()
const loading = ref(true)
const etiqueta = ref(null)
const notaFiscal = ref('')
const caixaAtual = ref('')
const caixaTotal = ref('')

const pedidoId = computed(() => route.params.id)

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  await loadEtiqueta()
})

async function loadEtiqueta() {
  loading.value = true
  try {
    const response = await api.getPedidoEtiqueta(pedidoId.value)
    if (response.data.success) {
      etiqueta.value = response.data.data
    } else {
      message.error('Pedido não encontrado')
    }
  } catch (error) {
    message.error('Erro ao carregar etiqueta')
  } finally {
    loading.value = false
  }
}

function imprimir() {
  window.print()
}

function getCpfCnpjLabel(tipo) {
  return tipo == 1 ? 'CPF' : 'CNPJ'
}
</script>

<template>
  <div class="print-container">
    <div class="no-print fixed top-4 right-4 z-50 flex gap-2" style="position: fixed; top: 16px; right: 16px; z-index: 50; display: flex; gap: 8px;">
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
      <div v-if="etiqueta" class="print-area">
        <div class="etiqueta-box">
          <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 30'%3E%3Crect fill='%236366F1' width='100' height='30' rx='4'/%3E%3Ctext x='50' y='20' fill='white' font-family='Arial' font-size='14' font-weight='bold' text-anchor='middle'%3ELIZZIE%3C/text%3E%3C/svg%3E" alt="Lizzie" class="logo-img">

          <div class="etiqueta-section">
            <div class="section-title"><strong>REMETENTE:</strong></div>
            <div class="section-content">
              <p>{{ etiqueta.remetente.nome }}</p>
              <p>CNPJ: {{ etiqueta.remetente.cnpj }}</p>
              <p>{{ etiqueta.remetente.endereco }}</p>
              <p>{{ etiqueta.remetente.bairro }} - {{ etiqueta.remetente.cidade }} - {{ etiqueta.remetente.estado }}</p>
              <p>CEP: {{ etiqueta.remetente.cep }}</p>
              <p><strong>PRODUTO: {{ etiqueta.remetente.marca }}</strong></p>
            </div>
          </div>

          <div class="etiqueta-section">
            <div class="section-title"><strong>DESTINATÁRIO:</strong></div>
            <div class="section-content">
              <p><strong>RESPONSÁVEL:</strong> {{ etiqueta.destinatario.responsavel }}</p>
              <p>{{ etiqueta.destinatario.nome_fantasia }}</p>
              <p>{{ etiqueta.destinatario.razao_social }}</p>
              <p>{{ getCpfCnpjLabel(etiqueta.destinatario.pessoa) }}: {{ etiqueta.destinatario.cpf_cnpj }}</p>
              <p>{{ etiqueta.destinatario.endereco }}</p>
              <div style="display: flex; justify-content: space-between;">
                <p>{{ etiqueta.destinatario.cidade }} - {{ etiqueta.destinatario.estado }}</p>
                <p>CAIXA: {{ caixaAtual }} / {{ caixaTotal }}</p>
              </div>
              <p>CEP: {{ etiqueta.destinatario.cep }}</p>
              <div style="display: flex; justify-content: space-between;">
                <p>CONTATO: {{ etiqueta.destinatario.contato }}</p>
                <p style="color: #000; font-weight: bold;">NF: {{ notaFiscal }}</p>
              </div>
            </div>
          </div>
          
          <div class="etiqueta-footer">
            <p>Pedido #: {{ etiqueta.pedido_id }}</p>
          </div>
        </div>
      </div>
    </NSpin>

    <div class="no-print" style="padding: 20px; background: #f5f5f5; border-top: 1px solid #ddd;">
      <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
        <div>
          <label style="display: block; font-size: 12px; margin-bottom: 4px;">NF (Nota Fiscal)</label>
          <input v-model="notaFiscal" type="text" placeholder="Número NF" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 120px;">
        </div>
        <div>
          <label style="display: block; font-size: 12px; margin-bottom: 4px;">Caixa Atual</label>
          <input v-model="caixaAtual" type="text" placeholder="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 60px;">
        </div>
        <div>
          <label style="display: block; font-size: 12px; margin-bottom: 4px;">Total Caixas</label>
          <input v-model="caixaTotal" type="text" placeholder="5" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 60px;">
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.print-container {
  min-height: 100vh;
  background: white;
}

.print-area {
  width: 12cm;
  min-height: 10cm;
  padding: 16px;
  margin: 0 auto;
  background: white;
}

.etiqueta-box {
  font-size: 10pt;
  color: #666;
}

.logo-img {
  width: 90px;
  margin-bottom: 10px;
}

.etiqueta-section {
  margin-top: 10px;
}

.section-title {
  font-weight: bold;
  margin-bottom: 4px;
}

.section-content p {
  margin: 2px 0;
}

.etiqueta-footer {
  margin-top: 10px;
  text-align: right;
  font-size: 9pt;
}

@media print {
  .no-print {
    display: none !important;
  }
  
  .print-area {
    margin: 0;
    padding: 0;
  }
  
  body {
    margin: 0;
    padding: 0;
  }
}
</style>
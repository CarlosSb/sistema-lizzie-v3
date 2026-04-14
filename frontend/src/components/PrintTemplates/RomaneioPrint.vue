<script setup>
import { computed } from 'vue'
import { formatMoney, formatDate } from '../../utils/formatters'

const props = defineProps({
  pedido: {
    type: Object,
    required: true
  },
  itens: {
    type: Array,
    default: () => []
  },
  cliente: {
    type: Object,
    default: null
  }
})

// Calculate totals
const totalUnidades = computed(() => {
  return props.itens.reduce((sum, item) => {
    return sum + (item.tam_pp || 0) + (item.tam_p || 0) + (item.tam_m || 0) +
           (item.tam_g || 0) + (item.tam_u || 0) + (item.tam_rn || 0) +
           (item.ida_1 || 0) + (item.ida_2 || 0) + (item.ida_3 || 0) +
           (item.ida_4 || 0) + (item.ida_6 || 0) + (item.ida_8 || 0) +
           (item.ida_10 || 0) + (item.ida_12 || 0) + (item.lisa || 0)
  }, 0)
})

const totalValor = computed(() => {
  return props.itens.reduce((sum, item) => sum + parseFloat(item.total_item || 0), 0)
})

// Print function
const print = () => {
  window.print()
}
</script>

<template>
  <div id="romaneio-print" class="print-template">
    <!-- Header -->
    <div class="header-section">
      <div class="company-info">
        <h1 class="company-name">ROMANEIO DE ENTREGA</h1>
        <p class="company-details">Sistema Lizzie - Controle de Expedição</p>
      </div>
      <div class="document-info">
        <div class="info-row">
          <span class="label">Pedido Nº:</span>
          <span class="value">{{ pedido.id_pedido }}</span>
        </div>
        <div class="info-row">
          <span class="label">Data:</span>
          <span class="value">{{ formatDate(pedido.created_at) }}</span>
        </div>
        <div class="info-row">
          <span class="label">Entrega Prevista:</span>
          <span class="value">{{ pedido.data_entrega ? formatDate(pedido.data_entrega) : 'A combinar' }}</span>
        </div>
      </div>
    </div>

    <!-- Client Information -->
    <div class="client-section">
      <h3 class="section-title">DESTINATÁRIO</h3>
      <div class="client-details">
        <div class="client-row">
          <span class="client-label">Razão Social:</span>
          <span class="client-value">{{ cliente?.razao_social || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">Nome Fantasia:</span>
          <span class="client-value">{{ cliente?.nome_fantasia || cliente?.razao_social || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">CNPJ/CPF:</span>
          <span class="client-value">{{ cliente?.cnpj || cliente?.cpf || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">Endereço:</span>
          <span class="client-value">{{ cliente?.endereco || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">Cidade/UF:</span>
          <span class="client-value">{{ cliente?.cidade || 'N/A' }} - {{ cliente?.uf || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">CEP:</span>
          <span class="client-value">{{ cliente?.cep || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">Telefone:</span>
          <span class="client-value">{{ cliente?.telefone || 'N/A' }}</span>
        </div>
        <div class="client-row">
          <span class="client-label">Contato:</span>
          <span class="client-value">{{ cliente?.contato || 'N/A' }}</span>
        </div>
      </div>
    </div>

    <!-- Items Checklist -->
    <div class="items-section">
      <h3 class="section-title">PRODUTOS PARA ENTREGA</h3>
      <div class="checklist-header">
        <span class="check-col">☐</span>
        <span class="qty-col">Qtd</span>
        <span class="desc-col">Descrição</span>
        <span class="ref-col">Referência</span>
        <span class="obs-col">Observações</span>
      </div>

      <div
        v-for="item in itens"
        :key="item.id_item_pedido"
        class="checklist-item"
      >
        <span class="check-col">☐</span>
        <span class="qty-col">{{ totalUnidades }}</span>
        <span class="desc-col">{{ item.produto?.produto || item.produto || 'Produto não informado' }}</span>
        <span class="ref-col">{{ item.produto?.referencia || item.referencia || '-' }}</span>
        <span class="obs-col">{{ item.obs_item || '-' }}</span>
      </div>
    </div>

    <!-- Summary -->
    <div class="summary-section">
      <div class="summary-grid">
        <div class="summary-item">
          <span class="summary-label">Total de Unidades:</span>
          <span class="summary-value">{{ totalUnidades }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Total de Itens:</span>
          <span class="summary-value">{{ itens.length }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Valor Total:</span>
          <span class="summary-value">R$ {{ formatMoney(totalValor) }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Forma de Pagamento:</span>
          <span class="summary-value">{{ pedido.forma_pag || 'Não informado' }}</span>
        </div>
      </div>
    </div>

    <!-- Delivery Instructions -->
    <div class="instructions-section">
      <h3 class="section-title">INSTRUÇÕES DE ENTREGA</h3>
      <div class="instructions-content">
        <p>{{ pedido.obs_entrega || 'Nenhuma instrução específica de entrega.' }}</p>
        <div class="instruction-checklist">
          <label class="checkbox-item">
            <input type="checkbox" /> Verificar quantidade dos produtos
          </label>
          <label class="checkbox-item">
            <input type="checkbox" /> Verificar integridade da embalagem
          </label>
          <label class="checkbox-item">
            <input type="checkbox" /> Obter assinatura do recebedor
          </label>
          <label class="checkbox-item">
            <input type="checkbox" /> Coletar comprovante de entrega
          </label>
        </div>
      </div>
    </div>

    <!-- Signatures -->
    <div class="signatures-section">
      <div class="signature-block">
        <div class="signature-line">_______________________________</div>
        <div class="signature-label">Recebido por:</div>
        <div class="signature-sub">Nome e assinatura</div>
      </div>

      <div class="signature-block">
        <div class="signature-line">_______________________________</div>
        <div class="signature-label">Entregue por:</div>
        <div class="signature-sub">Nome e assinatura</div>
      </div>

      <div class="signature-block">
        <div class="signature-line">_______________________________</div>
        <div class="signature-label">Data da Entrega:</div>
        <div class="signature-sub">___/___/_____</div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer-section">
      <p class="footer-text">
        Este romaneio é parte integrante do pedido {{ pedido.id_pedido }}.
        A entrega só será considerada completa após assinatura do recebedor.
      </p>
      <p class="footer-contact">
        Sistema Lizzie - Tel: (11) 9999-9999 | www.sistemalizzie.com.br
      </p>
    </div>

    <!-- Print Button (only shown when not auto-printing) -->
    <div v-if="!$route?.query?.print" class="print-controls no-print">
      <button @click="print" class="print-button">Imprimir Romaneio</button>
    </div>
  </div>
</template>

<style scoped>
.print-template {
  font-family: 'Arial', sans-serif;
  font-size: 12px;
  line-height: 1.4;
  color: #000;
  max-width: 210mm;
  margin: 0 auto;
  padding: 15px;
  background: white;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  border-bottom: 2px solid #000;
  padding-bottom: 15px;
}

.company-info {
  flex: 1;
}

.company-name {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 5px;
}

.company-details {
  font-size: 10px;
  color: #666;
}

.document-info {
  text-align: right;
}

.info-row {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 3px;
}

.info-row .label {
  font-weight: bold;
  margin-right: 10px;
  min-width: 120px;
}

.section-title {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 10px;
  border-bottom: 1px solid #000;
  padding-bottom: 3px;
}

.client-section {
  margin-bottom: 20px;
}

.client-details {
  border: 1px solid #000;
  padding: 10px;
}

.client-row {
  display: flex;
  margin-bottom: 5px;
}

.client-label {
  font-weight: bold;
  min-width: 120px;
  margin-right: 10px;
}

.items-section {
  margin-bottom: 20px;
}

.checklist-header,
.checklist-item {
  display: grid;
  grid-template-columns: 30px 50px 2fr 100px 1fr;
  gap: 10px;
  padding: 5px 0;
  border-bottom: 1px solid #ddd;
  font-size: 11px;
}

.checklist-header {
  font-weight: bold;
  border-bottom: 2px solid #000;
  margin-bottom: 5px;
}

.check-col {
  text-align: center;
}

.qty-col {
  text-align: center;
  font-weight: bold;
}

.ref-col {
  font-family: 'Courier New', monospace;
}

.summary-section {
  margin-bottom: 20px;
  background: #f9f9f9;
  padding: 15px;
  border: 1px solid #000;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
}

.summary-label {
  font-weight: bold;
}

.summary-value {
  font-weight: bold;
  color: #0066cc;
}

.instructions-section {
  margin-bottom: 20px;
}

.instructions-content {
  border: 1px solid #000;
  padding: 10px;
  min-height: 80px;
}

.instruction-checklist {
  margin-top: 15px;
}

.checkbox-item {
  display: block;
  margin-bottom: 5px;
  font-size: 11px;
}

.checkbox-item input {
  margin-right: 8px;
}

.signatures-section {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.signature-block {
  text-align: center;
}

.signature-line {
  border-bottom: 1px solid #000;
  margin-bottom: 5px;
  height: 30px;
}

.signature-label {
  font-weight: bold;
  font-size: 11px;
}

.signature-sub {
  font-size: 10px;
  color: #666;
  margin-top: 2px;
}

.footer-section {
  border-top: 1px solid #000;
  padding-top: 15px;
  text-align: center;
}

.footer-text {
  font-size: 10px;
  margin-bottom: 5px;
  font-style: italic;
}

.footer-contact {
  font-size: 9px;
  color: #666;
}

.print-controls {
  text-align: center;
  margin-top: 20px;
}

.print-button {
  background: #dc3545;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.print-button:hover {
  background: #c82333;
}

/* Print styles */
@media print {
  .no-print {
    display: none !important;
  }

  .print-template {
    padding: 0;
    margin: 0;
    max-width: none;
  }

  body {
    margin: 0;
    padding: 0;
  }

  .checkbox-item input {
    transform: scale(1.5);
    margin-right: 10px;
  }
}
</style>
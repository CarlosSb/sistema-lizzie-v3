<script setup>
import { computed, onMounted } from 'vue'
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
const subtotal = computed(() => {
  return props.itens.reduce((sum, item) => sum + parseFloat(item.total_item || 0), 0)
})

const totalDesconto = computed(() => {
  const descontoPercentual = parseFloat(props.pedido.desconto_percentual || 0)
  const descontoValor = parseFloat(props.pedido.desconto_valor || 0)
  return (subtotal.value * descontoPercentual / 100) + descontoValor
})

const total = computed(() => {
  return subtotal.value - totalDesconto.value
})

// Print function
const print = () => {
  window.print()
}

onMounted(() => {
  // Auto-print after a short delay to ensure rendering
  setTimeout(() => {
    if (window.location.search.includes('print=1')) {
      print()
    }
  }, 500)
})
</script>

<template>
  <div id="pedido-print" class="print-template">
    <!-- Print Header -->
    <div class="print-header">
      <div class="company-info">
        <h1 class="company-name">Sistema Lizzie</h1>
        <p class="company-address">Rua Example, 123 - Centro</p>
        <p class="company-contact">Tel: (11) 9999-9999 | CNPJ: 00.000.000/0001-00</p>
      </div>
      <div class="document-info">
        <h2 class="document-title">PEDIDO DE VENDA</h2>
        <p class="document-number">Nº {{ pedido.id_pedido }}</p>
        <p class="document-date">Data: {{ formatDate(pedido.created_at) }}</p>
        <p class="document-region">Região: {{ pedido.regiao === 'norde' ? 'Nordeste' : 'Norte' }}</p>
      </div>
    </div>

    <!-- Client Information -->
    <div class="client-section">
      <h3 class="section-title">DADOS DO CLIENTE</h3>
      <div class="client-grid">
        <div class="client-field">
          <label>Razão Social:</label>
          <span>{{ cliente?.razao_social || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>Nome Fantasia:</label>
          <span>{{ cliente?.nome_fantasia || cliente?.razao_social || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>CNPJ/CPF:</label>
          <span>{{ cliente?.cnpj || cliente?.cpf || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>Inscrição:</label>
          <span>{{ cliente?.inscricao_estadual || cliente?.inscricao_municipal || '-' }}</span>
        </div>
        <div class="client-field">
          <label>Endereço:</label>
          <span>{{ cliente?.endereco || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>Cidade/UF:</label>
          <span>{{ cliente?.cidade || 'N/A' }} - {{ cliente?.uf || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>CEP:</label>
          <span>{{ cliente?.cep || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>Telefone:</label>
          <span>{{ cliente?.telefone || 'N/A' }}</span>
        </div>
        <div class="client-field">
          <label>Email:</label>
          <span>{{ cliente?.email || 'N/A' }}</span>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="items-section">
      <h3 class="section-title">ITENS DO PEDIDO</h3>
      <table class="items-table">
        <thead>
          <tr>
            <th class="item-code">Código</th>
            <th class="item-description">Descrição</th>
            <th class="item-size">Tamanhos</th>
            <th class="item-qty">Qtd</th>
            <th class="item-price">Preço Unit.</th>
            <th class="item-total">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in itens" :key="item.id_item_pedido">
            <td class="item-code">{{ item.produto?.referencia || '-' }}</td>
            <td class="item-description">{{ item.produto?.produto || item.produto || 'Produto não informado' }}</td>
            <td class="item-size">
              <div class="sizes-list">
                <span v-if="item.tam_pp" class="size-badge">PP:{{ item.tam_pp }}</span>
                <span v-if="item.tam_p" class="size-badge">P:{{ item.tam_p }}</span>
                <span v-if="item.tam_m" class="size-badge">M:{{ item.tam_m }}</span>
                <span v-if="item.tam_g" class="size-badge">G:{{ item.tam_g }}</span>
                <span v-if="item.tam_u" class="size-badge">U:{{ item.tam_u }}</span>
                <span v-if="item.tam_rn" class="size-badge">RN:{{ item.tam_rn }}</span>
                <span v-if="item.ida_1" class="size-badge">1:{{ item.ida_1 }}</span>
                <span v-if="item.ida_2" class="size-badge">2:{{ item.ida_2 }}</span>
                <span v-if="item.ida_3" class="size-badge">3:{{ item.ida_3 }}</span>
                <span v-if="item.ida_4" class="size-badge">4:{{ item.ida_4 }}</span>
                <span v-if="item.ida_6" class="size-badge">6:{{ item.ida_6 }}</span>
                <span v-if="item.ida_8" class="size-badge">8:{{ item.ida_8 }}</span>
                <span v-if="item.ida_10" class="size-badge">10:{{ item.ida_10 }}</span>
                <span v-if="item.ida_12" class="size-badge">12:{{ item.ida_12 }}</span>
                <span v-if="item.lisa" class="size-badge">Lisa:{{ item.lisa }}</span>
              </div>
            </td>
            <td class="item-qty">
              {{ (item.tam_pp || 0) + (item.tam_p || 0) + (item.tam_m || 0) + (item.tam_g || 0) +
                 (item.tam_u || 0) + (item.tam_rn || 0) + (item.ida_1 || 0) + (item.ida_2 || 0) +
                 (item.ida_3 || 0) + (item.ida_4 || 0) + (item.ida_6 || 0) + (item.ida_8 || 0) +
                 (item.ida_10 || 0) + (item.ida_12 || 0) + (item.lisa || 0) }}
            </td>
            <td class="item-price">R$ {{ formatMoney(item.preco_unit || 0) }}</td>
            <td class="item-total">R$ {{ formatMoney(item.total_item || 0) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="total-label">SUBTOTAL:</td>
            <td colspan="2" class="total-value">R$ {{ formatMoney(subtotal) }}</td>
          </tr>
          <tr v-if="totalDesconto > 0">
            <td colspan="4" class="total-label">DESCONTO:</td>
            <td colspan="2" class="total-value">- R$ {{ formatMoney(totalDesconto) }}</td>
          </tr>
          <tr>
            <td colspan="4" class="grand-total-label">TOTAL:</td>
            <td colspan="2" class="grand-total-value">R$ {{ formatMoney(total) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Footer Information -->
    <div class="footer-section">
      <div class="footer-grid">
        <div class="footer-field">
          <label>Forma de Pagamento:</label>
          <span>{{ pedido.forma_pag || 'Não informado' }}</span>
        </div>
        <div class="footer-field">
          <label>Vendedor:</label>
          <span>{{ pedido.vendedor?.nome || 'Sistema' }}</span>
        </div>
        <div class="footer-field">
          <label>Data de Entrega:</label>
          <span>{{ pedido.data_entrega ? formatDate(pedido.data_entrega) : 'A combinar' }}</span>
        </div>
      </div>

      <div class="observations">
        <h4>Observações:</h4>
        <p>{{ pedido.obs_pedido || 'Nenhuma observação' }}</p>
        <p v-if="pedido.obs_entrega"><strong>Entrega:</strong> {{ pedido.obs_entrega }}</p>
      </div>

      <div class="signatures">
        <div class="signature-line">
          <span>_______________________________</span>
          <p>Cliente</p>
        </div>
        <div class="signature-line">
          <span>_______________________________</span>
          <p>Vendedor</p>
        </div>
      </div>
    </div>

    <!-- Print Button (only shown when not auto-printing) -->
    <div v-if="!$route?.query?.print" class="print-controls no-print">
      <button @click="print" class="print-button">Imprimir Pedido</button>
    </div>
  </div>
</template>

<style scoped>
.print-template {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.4;
  color: #000;
  max-width: 210mm;
  margin: 0 auto;
  padding: 20px;
  background: white;
}

.print-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 30px;
  border-bottom: 2px solid #000;
  padding-bottom: 20px;
}

.company-info {
  flex: 1;
}

.company-name {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 5px;
}

.company-address,
.company-contact {
  margin: 2px 0;
}

.document-info {
  text-align: right;
}

.document-title {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 10px;
}

.document-number {
  font-size: 16px;
  font-weight: bold;
  margin: 5px 0;
}

.document-date,
.document-region {
  margin: 2px 0;
}

.client-section,
.items-section {
  margin-bottom: 30px;
}

.section-title {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 10px;
  border-bottom: 1px solid #000;
  padding-bottom: 5px;
}

.client-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.client-field {
  display: flex;
  margin-bottom: 5px;
}

.client-field label {
  font-weight: bold;
  min-width: 120px;
  margin-right: 10px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.items-table th,
.items-table td {
  border: 1px solid #000;
  padding: 5px;
  text-align: left;
}

.items-table th {
  background: #f0f0f0;
  font-weight: bold;
}

.item-code {
  width: 80px;
}

.item-description {
  width: 200px;
}

.item-size {
  width: 150px;
}

.item-qty {
  width: 60px;
  text-align: center;
}

.item-price,
.item-total {
  width: 80px;
  text-align: right;
}

.sizes-list {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
}

.size-badge {
  background: #e0e0e0;
  padding: 1px 3px;
  font-size: 10px;
  border-radius: 2px;
}

.items-table tfoot td {
  border-top: 2px solid #000;
  font-weight: bold;
}

.total-label,
.grand-total-label {
  text-align: right;
  padding-right: 10px;
}

.total-value,
.grand-total-value {
  text-align: right;
  padding-right: 10px;
}

.grand-total-label,
.grand-total-value {
  font-size: 14px;
}

.footer-section {
  margin-top: 40px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 15px;
  margin-bottom: 20px;
}

.footer-field {
  display: flex;
  flex-direction: column;
}

.footer-field label {
  font-weight: bold;
  margin-bottom: 5px;
}

.observations {
  margin-bottom: 30px;
}

.observations h4 {
  font-weight: bold;
  margin-bottom: 5px;
}

.signatures {
  display: flex;
  justify-content: space-between;
  margin-top: 50px;
}

.signature-line {
  text-align: center;
  flex: 1;
}

.signature-line span {
  display: block;
  margin-bottom: 5px;
}

.print-controls {
  text-align: center;
  margin-top: 30px;
}

.print-button {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.print-button:hover {
  background: #0056b3;
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
}
</style>
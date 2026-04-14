<script setup>
import { computed } from 'vue'
import { formatMoney } from '../../utils/formatters'

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

// Generate labels for each item and size
const labels = computed(() => {
  const labelList = []

  props.itens.forEach(item => {
    const sizes = [
      { key: 'tam_pp', label: 'PP' },
      { key: 'tam_p', label: 'P' },
      { key: 'tam_m', label: 'M' },
      { key: 'tam_g', label: 'G' },
      { key: 'tam_u', label: 'U' },
      { key: 'tam_rn', label: 'RN' },
      { key: 'ida_1', label: '1' },
      { key: 'ida_2', label: '2' },
      { key: 'ida_3', label: '3' },
      { key: 'ida_4', label: '4' },
      { key: 'ida_6', label: '6' },
      { key: 'ida_8', label: '8' },
      { key: 'ida_10', label: '10' },
      { key: 'ida_12', label: '12' },
      { key: 'lisa', label: 'LISA' }
    ]

    sizes.forEach(size => {
      const quantity = item[size.key] || 0
      if (quantity > 0) {
        for (let i = 0; i < quantity; i++) {
          labelList.push({
            produto: item.produto?.produto || item.produto || 'Produto',
            referencia: item.produto?.referencia || item.referencia || '-',
            tamanho: size.label,
            cliente: props.cliente?.nome_fantasia || props.cliente?.razao_social || 'Cliente',
            pedidoId: props.pedido.id_pedido,
            preco: item.preco_unit || 0
          })
        }
      }
    })
  })

  return labelList
})

// Print function
const print = () => {
  window.print()
}
</script>

<template>
  <div id="etiquetas-print" class="print-template">
    <!-- Labels Grid -->
    <div class="labels-grid">
      <div
        v-for="(label, index) in labels"
        :key="index"
        class="label-item"
      >
        <!-- Company Logo/Name -->
        <div class="label-header">
          <div class="company-name">Sistema Lizzie</div>
          <div class="label-type">ETIQUETA DE PRODUTO</div>
        </div>

        <!-- Product Info -->
        <div class="product-info">
          <div class="product-name">{{ label.produto }}</div>
          <div class="product-ref">Ref: {{ label.referencia }}</div>
        </div>

        <!-- Size and Price -->
        <div class="size-price">
          <div class="size">Tamanho: {{ label.tamanho }}</div>
          <div class="price">R$ {{ formatMoney(label.preco) }}</div>
        </div>

        <!-- Client and Order -->
        <div class="client-order">
          <div class="client">{{ label.cliente }}</div>
          <div class="order">Pedido: {{ label.pedidoId }}</div>
        </div>

        <!-- Barcode Placeholder -->
        <div class="barcode">
          <div class="barcode-lines">
            <div class="barcode-line thick"></div>
            <div class="barcode-line thin"></div>
            <div class="barcode-line thick"></div>
            <div class="barcode-line thin"></div>
            <div class="barcode-line thick"></div>
            <div class="barcode-line thin"></div>
            <div class="barcode-line thick"></div>
          </div>
          <div class="barcode-text">{{ label.referencia }}</div>
        </div>
      </div>
    </div>

    <!-- Print Button (only shown when not auto-printing) -->
    <div v-if="!$route?.query?.print" class="print-controls no-print">
      <button @click="print" class="print-button">Imprimir Etiquetas</button>
    </div>
  </div>
</template>

<style scoped>
.print-template {
  font-family: 'Arial', sans-serif;
  background: white;
}

.labels-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 20px;
}

.label-item {
  width: 70mm;
  height: 40mm;
  border: 2px solid #000;
  padding: 3mm;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  font-size: 8px;
  line-height: 1.2;
  page-break-inside: avoid;
}

.label-header {
  text-align: center;
  border-bottom: 1px solid #000;
  padding-bottom: 2px;
  margin-bottom: 2px;
}

.company-name {
  font-size: 9px;
  font-weight: bold;
}

.label-type {
  font-size: 7px;
  margin-top: 1px;
}

.product-info {
  flex: 1;
  text-align: center;
}

.product-name {
  font-size: 9px;
  font-weight: bold;
  margin-bottom: 1px;
}

.product-ref {
  font-size: 7px;
}

.size-price {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 2px 0;
}

.size, .price {
  font-size: 8px;
  font-weight: bold;
}

.client-order {
  display: flex;
  justify-content: space-between;
  font-size: 7px;
  margin-bottom: 2px;
}

.barcode {
  border-top: 1px solid #000;
  padding-top: 2px;
  text-align: center;
}

.barcode-lines {
  display: flex;
  justify-content: center;
  align-items: end;
  height: 8px;
  margin-bottom: 1px;
}

.barcode-line {
  background: #000;
  margin: 0 1px;
}

.barcode-line.thick {
  width: 2px;
  height: 6px;
}

.barcode-line.thin {
  width: 1px;
  height: 4px;
}

.barcode-text {
  font-size: 6px;
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}

.print-controls {
  text-align: center;
  margin: 20px;
}

.print-button {
  background: #28a745;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.print-button:hover {
  background: #218838;
}

/* Print styles for thermal printer */
@media print {
  .no-print {
    display: none !important;
  }

  .print-template {
    padding: 0;
    margin: 0;
  }

  body {
    margin: 0;
    padding: 0;
  }

  .labels-grid {
    padding: 0;
  }

  .label-item {
    border: 1px solid #000 !important;
    page-break-inside: avoid;
    break-inside: avoid;
  }

  @page {
    size: A4;
    margin: 5mm;
  }
}

/* Alternative layout for continuous labels */
@media print and (max-width: 80mm) {
  .labels-grid {
    grid-template-columns: 1fr;
  }

  .label-item {
    width: 70mm;
    margin-bottom: 5mm;
  }
}
</style>
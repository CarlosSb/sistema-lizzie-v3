import { ref, computed, reactive } from 'vue'

export function usePedido() {
  const pedido = reactive({
    id_cliente: '',
    id_vendedor: '',
    regiao: 'norde',
    obs_pedido: '',
    obs_entrega: '',
    data_entrega: '',
    forma_pag: '',
    desconto_percentual: 0,
    desconto_valor: 0,
    itens: []
  })

  const TAMANHOS_INFANTIS = ['pp', 'p', 'm', 'g', 'u', 'rn']
  const TAMANHOS_FEMININO = ['ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12']

  function adicionarItem(produto, quantidades = {}) {
    const item = {
      id_produto: produto.id_produto,
      produto: produto,
      regiao: pedido.regiao,
      quantidades: {
        pp: 0, p: 0, m: 0, g: 0, u: 0, rn: 0,
        ida_1: 0, ida_2: 0, ida_3: 0, ida_4: 0,
        ida_6: 0, ida_8: 0, ida_10: 0, ida_12: 0,
        lisa: 0
      },
      tem_estampa: false,
      tem_estampa_lisa: false,
      sexo: 'U',
      desconto_percentual: 0,
      desconto_valor: 0
    }

    Object.keys(quantidades).forEach(k => {
      if (item.quantidades.hasOwnProperty(k)) {
        item.quantidades[k] = quantidades[k]
      }
    })

    pedido.itens.push(item)
  }

  function removerItem(index) {
    pedido.itens.splice(index, 1)
  }

  function atualizarQuantidade(itemIndex, tamanho, valor) {
    pedido.itens[itemIndex].quantidades[tamanho] = parseInt(valor) || 0
  }

  function getPrecoUnitario(item) {
    const produto = item.produto
    return item.regiao === 'norte' ? produto.valor_unt_norte : produto.valor_unt_norde
  }

  function calcularSubtotalItem(item) {
    const preco = getPrecoUnitario(item)
    const quantidade = calcularQuantidadeTotal(item.quantidades)
    return quantidade * preco
  }

  function calcularDescontoItem(item) {
    const subtotal = calcularSubtotalItem(item)
    const descPerc = (subtotal * item.desconto_percentual) / 100
    return descPerc + item.desconto_valor
  }

  function calcularTotalItem(item) {
    return calcularSubtotalItem(item) - calcularDescontoItem(item)
  }

  function calcularQuantidadeTotal(quantidades) {
    let total = 0
    
    TAMANHOS_INFANTIS.forEach(t => {
      total += quantidades[t] || 0
    })
    
    TAMANHOS_FEMININO.forEach(t => {
      total += quantidades[t] || 0
    })
    
    total += quantidades.pp || 0
    total += quantidades.lisa || 0
    
    return total
  }

  const resumo = computed(() => {
    let subtotal = 0
    let totalDescontosItens = 0
    let quantidadeTotal = 0

    pedido.itens.forEach(item => {
      const qtd = calcularQuantidadeTotal(item.quantidades)
      const sub = qtd * getPrecoUnitario(item)
      const desc = calcularDescontoItem(item)
      
      subtotal += sub
      totalDescontosItens += desc
      quantidadeTotal += qtd
    })

    const descPedidoPerc = (subtotal * pedido.desconto_percentual) / 100
    const totalDescontoPedido = descPedidoPerc + pedido.desconto_valor
    const totalGeral = subtotal - totalDescontosItens - totalDescontoPedido

    return {
      quantidadeTotal,
      subtotalItens: subtotal,
      descontosItens: totalDescontosItens,
      descontoPedido: totalDescontoPedido,
      total: totalGeral
    }
  })

  function criarPayload() {
    return {
      id_cliente: parseInt(pedido.id_cliente),
      id_vendedor: parseInt(pedido.id_vendedor),
      regiao: pedido.regiao,
      obs_pedido: pedido.obs_pedido,
      obs_entrega: pedido.obs_entrega,
      data_entrega: pedido.data_entrega || null,
      forma_pag: pedido.forma_pag,
      desconto_percentual: pedido.desconto_percentual,
      desconto_valor: pedido.desconto_valor,
      itens: pedido.itens.map(item => ({
        id_produto: item.id_produto,
        regiao: item.regiao,
        quantidades: item.quantidades,
        tem_estampa: item.tem_estampa,
        tem_estampa_lisa: item.tem_estampa_lisa,
        sexo: item.sexo,
        desconto_percentual: item.desconto_percentual,
        desconto_valor: item.desconto_valor
      }))
    }
  }

  function reset() {
    pedido.id_cliente = ''
    pedido.id_vendedor = ''
    pedido.regiao = 'norde'
    pedido.obs_pedido = ''
    pedido.obs_entrega = ''
    pedido.data_entrega = ''
    pedido.forma_pag = ''
    pedido.desconto_percentual = 0
    pedido.desconto_valor = 0
    pedido.itens = []
  }

  return {
    pedido,
    adicionarItem,
    removerItem,
    atualizarQuantidade,
    getPrecoUnitario,
    calcularSubtotalItem,
    calcularDescontoItem,
    calcularTotalItem,
    calcularQuantidadeTotal,
    resumo,
    criarPayload,
    reset,
    TAMANHOS_INFANTIS,
    TAMANHOS_FEMININO
  }
}
import { ref } from 'vue'

export function usePedidoPrint() {
  const printing = ref(false)
  const printError = ref(null)

  const generatePedidoPrintData = (pedido, itens = []) => {
    return {
      id: pedido.id_pedido,
      data: new Date(pedido.created_at).toLocaleDateString('pt-BR'),
      cliente: {
        nome: pedido.cliente?.razao_social || 'N/A',
        fantasia: pedido.cliente?.nome_fantasia || '',
        cnpj: pedido.cliente?.cnpj || '',
        cpf: pedido.cliente?.cpf || '',
        endereco: pedido.cliente?.endereco || '',
        cidade: pedido.cliente?.cidade || '',
        uf: pedido.cliente?.uf || '',
        telefone: pedido.cliente?.telefone || '',
        email: pedido.cliente?.email || ''
      },
      vendedor: pedido.vendedor?.nome || '',
      regiao: pedido.regiao === 'norde' ? 'Nordeste' : 'Norte',
      formaPagamento: pedido.forma_pag || 'Não informado',
      obsPedido: pedido.obs_pedido || '',
      obsEntrega: pedido.obs_entrega || '',
      status: pedido.status,
      itens: itens.map(item => ({
        produto: item.produto?.produto || item.produto || '',
        referencia: item.produto?.referencia || item.referencia || '',
        tamanhos: {
          pp: item.tam_pp || 0,
          p: item.tam_p || 0,
          m: item.tam_m || 0,
          g: item.tam_g || 0,
          u: item.tam_u || 0,
          rn: item.tam_rn || 0,
          ida_1: item.ida_1 || 0,
          ida_2: item.ida_2 || 0,
          ida_3: item.ida_3 || 0,
          ida_4: item.ida_4 || 0,
          ida_6: item.ida_6 || 0,
          ida_8: item.ida_8 || 0,
          ida_10: item.ida_10 || 0,
          ida_12: item.ida_12 || 0,
          lisa: item.lisa || 0
        },
        quantidadeTotal: 
          (item.tam_pp || 0) + (item.tam_p || 0) + (item.tam_m || 0) +
          (item.tam_g || 0) + (item.tam_u || 0) + (item.tam_rn || 0) +
          (item.ida_1 || 0) + (item.ida_2 || 0) + (item.ida_3 || 0) +
          (item.ida_4 || 0) + (item.ida_6 || 0) + (item.ida_8 || 0) +
          (item.ida_10 || 0) + (item.ida_12 || 0) + (item.lisa || 0),
        valorUnitario: parseFloat(item.preco_unit || 0),
        totalItem: parseFloat(item.total_item || 0)
      })),
      subtotal: parseFloat(pedido.subtotal || 0),
      descontoPercentual: parseFloat(pedido.desconto_percentual || 0),
      descontoValor: parseFloat(pedido.desconto_valor || 0),
      total: parseFloat(pedido.total_pedido || 0)
    }
  }

  const generateEtiquetaData = (pedido, itens = []) => {
    const etiquetas = []
    
    itens.forEach(item => {
      const sizes = ['pp', 'p', 'm', 'g', 'u', 'rn', 'ida_1', 'ida_2', 'ida_3', 'ida_4', 'ida_6', 'ida_8', 'ida_10', 'ida_12']
      
      sizes.forEach(size => {
        const qty = item[`tam_${size}`] || item[size] || 0
        if (qty > 0) {
          etiquetas.push({
            produto: item.produto?.produto || item.produto || '',
            referencia: item.produto?.referencia || item.referencia || '',
            tamanho: size.toUpperCase(),
            quantidade: qty,
            cliente: pedido.cliente?.nome_fantasia || pedido.cliente?.razao_social || '',
            pedidoId: pedido.id_pedido
          })
        }
      })
    })
    
    return etiquetas
  }

  const generateRomaneioData = (pedido, itens = []) => {
    return {
      pedidoId: pedido.id_pedido,
      data: new Date(pedido.created_at).toLocaleDateString('pt-BR'),
      cliente: {
        nome: pedido.cliente?.razao_social || '',
        fantasia: pedido.cliente?.nome_fantasia || '',
        endereco: pedido.cliente?.endereco || '',
        cidade: pedido.cliente?.cidade || '',
        uf: pedido.cliente?.uf || ''
      },
      itens: itens.map(item => ({
        produto: item.produto?.produto || item.produto || '',
        referencia: item.produto?.referencia || item.referencia || '',
        quantidadeTotal: 
          (item.tam_pp || 0) + (item.tam_p || 0) + (item.tam_m || 0) +
          (item.tam_g || 0) + (item.tam_u || 0) + (item.tam_rn || 0) +
          (item.ida_1 || 0) + (item.ida_2 || 0) + (item.ida_3 || 0) +
          (item.ida_4 || 0) + (item.ida_6 || 0) + (item.ida_8 || 0) +
          (item.ida_10 || 0) + (item.ida_12 || 0) + (item.lisa || 0),
        obs: item.obs_item || ''
      })),
      totalUnidades: itens.reduce((sum, item) => sum + (
        (item.tam_pp || 0) + (item.tam_p || 0) + (item.tam_m || 0) +
        (item.tam_g || 0) + (item.tam_u || 0) + (item.tam_rn || 0) +
        (item.ida_1 || 0) + (item.ida_2 || 0) + (item.ida_3 || 0) +
        (item.ida_4 || 0) + (item.ida_6 || 0) + (item.ida_8 || 0) +
        (item.ida_10 || 0) + (item.ida_12 || 0) + (item.lisa || 0)
      ), 0),
      observacoes: pedido.obs_entrega || ''
    }
  }

  const printElement = async (elementId, options = {}) => {
    printing.value = true
    printError.value = null

    try {
      const element = document.getElementById(elementId)
      if (!element) {
        throw new Error('Element not found')
      }

      const originalDisplay = element.style.display
      element.style.display = 'block'
      
      window.print()
      
      element.style.display = originalDisplay
    } catch (error) {
      printError.value = error.message
      console.error('Print error:', error)
    } finally {
      printing.value = false
    }
  }

  const exportToCSV = (data, filename) => {
    const headers = Object.keys(data[0] || {})
    const csvContent = [
      headers.join(';'),
      ...data.map(row => headers.map(h => {
        const val = row[h]
        if (typeof val === 'string' && val.includes(';')) {
          return `"${val}"`
        }
        return val
      }).join(';'))
    ].join('\n')

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `${filename}_${new Date().toISOString().split('T')[0]}.csv`
    link.click()
    URL.revokeObjectURL(link.href)
  }

  const exportPedidosToCSV = (pedidos, filename = 'pedidos') => {
    const data = pedidos.map(p => ({
      ID: p.id_pedido,
      Data: new Date(p.created_at).toLocaleDateString('pt-BR'),
      Cliente: p.cliente?.razao_social || '',
      CNPJ: p.cliente?.cnpj || '',
      CPF: p.cliente?.cpf || '',
      Cidade: p.cliente?.cidade || '',
      UF: p.cliente?.uf || '',
      Vendedor: p.vendedor?.nome || '',
      Região: p.regiao === 'norde' ? 'Nordeste' : 'Norte',
      'Forma Pagamento': p.forma_pag || '',
      'Qtd Itens': p.itens_count || 0,
      Subtotal: p.subtotal || 0,
      'Desconto %': p.desconto_percentual || 0,
      'Desconto R$': p.desconto_valor || 0,
      Total: p.total_pedido || 0,
      Status: p.status || '',
      'Observações': p.obs_pedido || '',
      'Obs Entrega': p.obs_entrega || '',
      'Data Entrega': p.data_entrega ? new Date(p.data_entrega).toLocaleDateString('pt-BR') : '',
      'Criado em': p.created_at ? new Date(p.created_at).toLocaleString('pt-BR') : '',
      'Atualizado em': p.updated_at ? new Date(p.updated_at).toLocaleString('pt-BR') : ''
    }))

    exportToCSV(data, filename)
  }

  return {
    printing,
    printError,
    generatePedidoPrintData,
    generateEtiquetaData,
    generateRomaneioData,
    printElement,
    exportToCSV,
    exportPedidosToCSV
  }
}
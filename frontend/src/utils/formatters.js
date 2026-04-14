export function formatMoney(val) {
  return typeof val === 'number' ? val.toFixed(2) : parseFloat(val || 0).toFixed(2)
}

export function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR')
}

export function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('pt-BR')
}

export function formatCurrency(val) {
  return 'R$ ' + formatMoney(val)
}

export function formatNumber(val) {
  return typeof val === 'number' ? val.toLocaleString('pt-BR') : val
}
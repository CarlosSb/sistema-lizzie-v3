// src/utils/logger.js
const isProduction = import.meta.env.PROD

export const logger = {
  info: (message, ...args) => {
    if (!isProduction) {
      console.log(`[INFO] ${message}`, ...args)
    }
  },

  warn: (message, ...args) => {
    if (!isProduction) {
      console.warn(`[WARN] ${message}`, ...args)
    }
  },

  error: (message, ...args) => {
    if (!isProduction) {
      console.error(`[ERROR] ${message}`, ...args)
    } else {
      // Em produção, poderia enviar para um serviço de logging
      // sendToLoggingService('error', message, args)
    }
  },

  debug: (message, ...args) => {
    if (!isProduction && import.meta.env.DEV) {
      console.debug(`[DEBUG] ${message}`, ...args)
    }
  }
}

export default logger
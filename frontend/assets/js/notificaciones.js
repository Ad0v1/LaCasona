/**
 * Sistema de notificaciones para el proceso de reservas
 * Maneja notificaciones por email, WhatsApp y en pantalla
 */

class NotificationManager {
  constructor() {
    this.apiUrl = "api/notificaciones.php"
    this.queue = []
    this.isProcessing = false
  }

  /**
   * Envía una notificación por correo electrónico
   */
  async enviarEmail(tipo, datos) {
    return this.enviarNotificacion("email", tipo, datos)
  }

  /**
   * Envía una notificación por WhatsApp
   */
  async enviarWhatsApp(tipo, datos) {
    return this.enviarNotificacion("whatsapp", tipo, datos)
  }

  /**
   * Método genérico para enviar notificaciones
   */
  async enviarNotificacion(medio, tipo, datos) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          tipo: tipo,
          medio: medio,
          datos: datos,
        }),
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const result = await response.json()
      console.log(`Notificación por ${medio} enviada:`, result)
      return result
    } catch (error) {
      console.error(`Error al enviar notificación por ${medio}:`, error)
      throw error
    }
  }

  /**
   * Programa un recordatorio
   */
  async programarRecordatorio(reserva, diasAntes = 1) {
    try {
      const response = await fetch(this.apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          accion: "programar_recordatorio",
          reserva: reserva,
          diasAntes: diasAntes,
        }),
      })

      const result = await response.json()
      console.log("Recordatorio programado:", result)
      return result
    } catch (error) {
      console.error("Error al programar recordatorio:", error)
      throw error
    }
  }

  /**
   * Envía notificación de pago pendiente
   */
  async notificarPagoPendiente(reserva) {
    const mensaje = `Hola ${reserva.nombre}, te recordamos que debes pagar el 50% de tu reserva para el ${reserva.fecha}. Tienes plazo hasta el ${reserva.fechaLimite}.`

    const datos = {
      ...reserva,
      mensaje: mensaje,
      tipo: "recordatorio_pago",
    }

    const promesas = []

    if (reserva.email) {
      promesas.push(this.enviarEmail("recordatorio_pago", datos))
    }

    if (reserva.telefono) {
      promesas.push(this.enviarWhatsApp("recordatorio_pago", datos))
    }

    try {
      await Promise.all(promesas)
      return {
        success: true,
        message: "Notificación de pago pendiente enviada",
      }
    } catch (error) {
      return {
        success: false,
        message: "Error al enviar notificación de pago pendiente",
        error: error.message,
      }
    }
  }

  /**
   * Envía confirmación de pago
   */
  async confirmarPago(reserva) {
    const mensaje = `¡Gracias ${reserva.nombre}! Hemos confirmado tu pago. Tu reserva para el ${reserva.fecha} ha sido confirmada. ¡Te esperamos!`

    const datos = {
      ...reserva,
      mensaje: mensaje,
      tipo: "confirmacion_pago",
    }

    const promesas = []

    if (reserva.email) {
      promesas.push(this.enviarEmail("confirmacion_pago", datos))
    }

    if (reserva.telefono) {
      promesas.push(this.enviarWhatsApp("confirmacion_pago", datos))
    }

    try {
      await Promise.all(promesas)
      return {
        success: true,
        message: "Confirmación de pago enviada",
      }
    } catch (error) {
      return {
        success: false,
        message: "Error al enviar confirmación de pago",
        error: error.message,
      }
    }
  }

  /**
   * Envía notificación de cancelación
   */
  async notificarCancelacion(reserva, motivo = "falta de pago") {
    let mensaje

    if (motivo === "falta de pago") {
      mensaje = `Lamentamos informarte que tu reserva para el ${reserva.fecha} ha sido cancelada por no recibir el pago dentro del plazo establecido.`
    } else {
      mensaje = `Tu reserva para el ${reserva.fecha} ha sido cancelada. Motivo: ${motivo}`
    }

    const datos = {
      ...reserva,
      mensaje: mensaje,
      motivo: motivo,
      tipo: "cancelacion",
    }

    const promesas = []

    if (reserva.email) {
      promesas.push(this.enviarEmail("cancelacion", datos))
    }

    if (reserva.telefono) {
      promesas.push(this.enviarWhatsApp("cancelacion", datos))
    }

    try {
      await Promise.all(promesas)
      return {
        success: true,
        message: "Notificación de cancelación enviada",
      }
    } catch (error) {
      return {
        success: false,
        message: "Error al enviar notificación de cancelación",
        error: error.message,
      }
    }
  }

  /**
   * Envía notificación de nueva reserva al administrador
   */
  async notificarNuevaReserva(reserva) {
    const datos = {
      ...reserva,
      tipo: "nueva_reserva_admin",
      mensaje: `Nueva reserva recibida de ${reserva.nombre} para el ${reserva.fecha} a las ${reserva.hora}.`,
    }

    try {
      // Enviar al email del administrador
      await this.enviarEmail("nueva_reserva_admin", datos)

      return {
        success: true,
        message: "Administrador notificado de nueva reserva",
      }
    } catch (error) {
      return {
        success: false,
        message: "Error al notificar al administrador",
        error: error.message,
      }
    }
  }

  /**
   * Añade una notificación a la cola
   */
  addToQueue(notification) {
    this.queue.push(notification)
    this.processQueue()
  }

  /**
   * Procesa la cola de notificaciones
   */
  async processQueue() {
    if (this.isProcessing || this.queue.length === 0) {
      return
    }

    this.isProcessing = true

    while (this.queue.length > 0) {
      const notification = this.queue.shift()

      try {
        await this.enviarNotificacion(notification.medio, notification.tipo, notification.datos)

        // Esperar un poco entre notificaciones para no sobrecargar
        await new Promise((resolve) => setTimeout(resolve, 1000))
      } catch (error) {
        console.error("Error procesando notificación:", error)

        // Reintentarlo después de un tiempo si es un error temporal
        if (notification.retries < 3) {
          notification.retries = (notification.retries || 0) + 1
          setTimeout(() => {
            this.queue.unshift(notification)
            this.processQueue()
          }, 5000 * notification.retries)
        }
      }
    }

    this.isProcessing = false
  }
}

// Crear instancia global
window.NotificationManager = NotificationManager

// Exportar para uso en módulos
if (typeof module !== "undefined" && module.exports) {
  module.exports = NotificationManager
}

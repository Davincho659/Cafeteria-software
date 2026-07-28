/**
 * ============================================================================
 * BILL-WINDOW.JS - Manejo de la ventana emergente de factura
 * ============================================================================
 * Centraliza la apertura de la factura para que se comporte igual en toda la
 * aplicación (mostrador, mesas y panel administrativo).
 *
 * Comportamiento:
 *  - Solo puede haber una ventana de factura abierta a la vez.
 *  - Al hacer click en cualquier parte de la aplicación, la factura se cierra.
 *  - La ventana queda enfocada para poder imprimir de una vez (Enter / botón).
 * ============================================================================
 */
;(function () {
  "use strict"

  var billWindow = null
  var listenerAttached = false
  var attachTimer = null

  /** Cierra la factura abierta (si existe) y limpia los listeners */
  function closeBillWindow() {
    if (attachTimer) {
      clearTimeout(attachTimer)
      attachTimer = null
    }

    try {
      if (billWindow && !billWindow.closed) billWindow.close()
    } catch (e) {
      /* La ventana pudo haberse cerrado a mano: no es un error real */
    }

    billWindow = null
    detachCloseListener()
  }

  function handleAppInteraction() {
    closeBillWindow()
  }

  function attachCloseListener() {
    if (listenerAttached) return
    // Fase de captura: así se cierra aunque otro handler detenga la propagación.
    document.addEventListener("click", handleAppInteraction, true)
    listenerAttached = true
  }

  function detachCloseListener() {
    if (!listenerAttached) return
    document.removeEventListener("click", handleAppInteraction, true)
    listenerAttached = false
  }

  /**
   * Abre la factura de una venta en una ventana aparte.
   *
   * @param {number|string} saleId  ID de la venta
   * @param {Object}  [options]
   * @param {boolean} [options.autoPrint=false] Lanza el diálogo de impresión al abrir
   * @returns {Window|null} Referencia a la ventana, o null si el navegador la bloqueó
   */
  function openBillWindow(saleId, options) {
    if (!saleId) {
      console.warn("[BILL] No se puede abrir la factura: falta el ID de venta")
      return null
    }

    var opts = options || {}

    // Si ya había una factura abierta, se reemplaza.
    closeBillWindow()

    var url = "?pg=bill&id=" + encodeURIComponent(saleId)
    if (opts.autoPrint) url += "&print=1"

    billWindow = window.open(url, "facturaPOS", "width=350,height=900")

    if (!billWindow) {
      console.warn("[BILL] El navegador bloqueó la ventana emergente de la factura")
      return null
    }

    try {
      billWindow.focus()
    } catch (e) {
      /* Algunos navegadores no permiten enfocar: no es crítico */
    }

    // Se espera un momento antes de escuchar clicks para que el mismo click
    // que confirmó la venta no cierre la factura de inmediato.
    attachTimer = setTimeout(function () {
      attachTimer = null
      attachCloseListener()
    }, 400)

    return billWindow
  }

  // Al salir de la página no dejar ventanas huérfanas.
  window.addEventListener("beforeunload", closeBillWindow)

  // API pública
  window.openBillWindow = openBillWindow
  window.closeBillWindow = closeBillWindow
})()

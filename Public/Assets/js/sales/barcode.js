/**
 * ============================================================================
 * BARCODE.JS - Lector de código de barras en la vista de ventas
 * ============================================================================
 * Un lector USB se comporta como un teclado: "teclea" el código muy rápido y
 * termina con Enter. Aquí detectamos esa ráfaga rápida (más veloz que un humano)
 * y, si el código corresponde a un producto creado, lo agregamos al carrito de
 * la pestaña activa (venta o mesa). Si no existe → "Producto no encontrado".
 *
 * Este archivo SOLO se carga en la vista de ventas, así que en otras pantallas
 * no interfiere.
 *
 * Cantidad: cada escaneo suma 1 unidad (patrón estándar de POS: rápido). Si se
 * necesita más de uno del mismo, se escanea otra vez o se ajusta en el carrito.
 * ============================================================================
 */
;(function () {
  "use strict"

  // --- Parámetros de detección ---
  const MAX_GAP_MS = 50     // separación máxima entre teclas para contar como "ráfaga"
  const MIN_LARGO = 3       // largo mínimo de un código válido
  const RESET_MS = 400      // si pasa este tiempo sin Enter, se descarta el buffer

  let buffer = ""
  let primeraTecla = 0
  let ultimaTecla = 0

  // Cola para procesar escaneos uno por uno (evita choques entre fetchs)
  let cadena = Promise.resolve()

  function esCampoTexto(el) {
    if (!el) return false
    const tag = (el.tagName || "").toLowerCase()
    return tag === "input" || tag === "textarea" || el.isContentEditable
  }

  document.addEventListener(
    "keydown",
    function (e) {
      // Ignorar teclas con modificadores (atajos) y teclas no imprimibles
      if (e.ctrlKey || e.altKey || e.metaKey) return

      const ahora = Date.now()

      if (e.key === "Enter") {
        // ¿El buffer se escribió como una ráfaga (lector) y no a mano?
        const largo = buffer.length
        const intervalos = largo > 1 ? largo - 1 : 1
        const promedio = (ultimaTecla - primeraTecla) / intervalos
        const esRafaga = largo >= MIN_LARGO && promedio <= MAX_GAP_MS

        if (esRafaga) {
          const codigo = buffer
          buffer = ""

          // Evitar que el Enter dispare envíos o comportamientos de la página
          e.preventDefault()
          e.stopPropagation()

          // Si el código se coló en un campo de texto (ej. el buscador), limpiarlo
          const activo = document.activeElement
          if (esCampoTexto(activo) && typeof activo.value === "string" && activo.value.endsWith(codigo)) {
            activo.value = activo.value.slice(0, -codigo.length)
          }

          procesarCodigo(codigo)
        } else {
          buffer = ""
        }
        return
      }

      // Solo caracteres imprimibles de un carácter (dígitos/letras del código)
      if (e.key.length === 1) {
        if (ahora - ultimaTecla > RESET_MS || ahora - ultimaTecla > MAX_GAP_MS) {
          // Nueva secuencia
          buffer = e.key
          primeraTecla = ahora
        } else {
          buffer += e.key
        }
        ultimaTecla = ahora
      }
    },
    true // fase de captura: llega antes que otros handlers
  )

  function procesarCodigo(codigo) {
    cadena = cadena.then(() => buscarYAgregar(codigo)).catch((err) => {
      console.error("[BARCODE] Error procesando código:", err)
    })
  }

  async function buscarYAgregar(codigo) {
    let data
    try {
      const resp = await fetch("?pg=sales&action=findByBarcode&codigo=" + encodeURIComponent(codigo), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
      data = await resp.json()
    } catch (e) {
      toast("error", "Error de conexión al leer el código")
      return
    }

    if (!data || !data.encontrado || !data.data) {
      toast("warning", "Producto no encontrado", "Código: " + codigo)
      return
    }

    const p = data.data

    // Verificar stock si el producto lo maneja
    if (p.manejaStock && p.stockActual !== null && Number(p.stockActual) <= 0) {
      toast("warning", "Sin stock", p.nombre + " no tiene existencias")
      return
    }

    // Reusar la lógica existente: agrega +1 al carrito de la pestaña activa
    if (typeof addToCart === "function") {
      await addToCart({
        idProducto: p.idProducto,
        nombre: p.nombre,
        imagen: p.imagen,
        precioVenta: p.precioVenta,
      })
      toast("success", p.nombre, "Agregado al carrito")
    } else {
      console.warn("[BARCODE] addToCart no está disponible")
    }
  }

  function toast(icon, title, text) {
    if (typeof Swal === "undefined") return
    Swal.fire({
      toast: true,
      position: "top-end",
      icon: icon,
      title: title,
      text: text || "",
      showConfirmButton: false,
      timer: icon === "success" ? 1200 : 2200,
      timerProgressBar: true,
    })
  }
})()

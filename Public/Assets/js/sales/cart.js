// ============================================================================
// CART.JS - GESTIÓN DE CARRITO
// ============================================================================
// Este módulo gestiona el carrito de compras en memoria
// Utiliza el estado global definido en Sales.js (carts, currentCartId, etc.)

// ============================================================================
// FUNCIONES DE GESTIÓN DE CARRITO
// ============================================================================

/**
 * Obtiene el carrito especificado o el actual
 */
function getCart(cartId) {
  return carts[cartId || currentCartId] || { type: "sale", products: [], total: 0 }
}

/**
 * Obtiene información del tab actual
 */
function getCurrentTabInfo() {
  const activeTab = document.querySelector(".nav-tabs .nav-link.active")
  if (!activeTab) return { type: "sale", cartId: currentCartId }

  const href = activeTab.getAttribute("href")
  const cartId = href ? href.substring(1) : currentCartId

  // Verificar si es una mesa
  if (isTableTab(cartId)) {
    const idMesa = toInt(cartId.replace("mesa-", ""))
    return {
      type: "table",
      cartId: cartId,
      idMesa: idMesa,
    }
  }

  return {
    type: "sale",
    cartId: cartId,
  }
}

/**
 * Garantiza que el carrito tenga una venta pendiente en el servidor.
 *
 * Se usa cuando una pestaña quedó sin `idVenta` (venta anulada desde otro
 * lado, base restaurada, etc.). Sin esto la pestaña quedaba inservible: no
 * dejaba agregar, ni cobrar, ni cerrar — y había que recargar el POS.
 *
 * Devuelve el idVenta, o null si no se pudo crear.
 */
async function asegurarVentaDelCarrito(cartId) {
  const cartObj = getCart(cartId)
  if (cartObj.idVenta) return cartObj.idVenta

  try {
    const fd = new FormData()
    fd.append("productos", JSON.stringify([])) // venta pendiente vacía
    const respuesta = await fetchJson("?pg=sales&action=CreateSale", { method: "POST", body: fd })

    if (!respuesta.success || !respuesta.data?.idVenta) return null

    cartObj.idVenta = respuesta.data.idVenta

    // El id del tab se usa al cerrar/cancelar: mantenerlo sincronizado.
    const link = document.querySelector(`#ventasTabs a[href="#${cartId}"]`)
    if (link) link.setAttribute("id", String(respuesta.data.idVenta))

    console.log("[CART] ✅ Venta pendiente creada para", cartId, "→", respuesta.data.idVenta)
    return respuesta.data.idVenta
  } catch (e) {
    console.error("[CART] No se pudo crear la venta pendiente:", e)
    return null
  }
}

/**
 * Añade un producto al carrito
 * PASO 2: Sincroniza inmediatamente con BD si hay idVenta
 */
async function addToCart(product, reintento = false) {
  const currentTab = getCurrentTabInfo()

  console.log("[CART] Adding to cart:", { type: currentTab.type, cartId: currentTab.cartId, idMesa: currentTab.idMesa, product: product.nombre })

  // Si es mesa → agregar a BD
  if (currentTab.type === "table") {
    await addProductToTableSale(currentTab.idMesa, product)
    return
  }

  // Si es venta en memoria (carrito normal)
  const cartId = currentTab.cartId
  const cartObj = getCart(cartId)
  
  // Sin venta asociada: en vez de bloquear la caja, se crea una venta pendiente
  // al vuelo y se sigue trabajando con normalidad.
  if (!cartObj.idVenta) {
    console.warn("[CART] addToCart: el carrito no tiene idVenta; creando una venta pendiente…")
    const nuevoId = await asegurarVentaDelCarrito(cartId)
    if (!nuevoId) {
      alert("No se pudo iniciar la venta. Revisa que la caja esté abierta e inténtalo de nuevo.")
      return
    }
  }

  // Para NULL (monto manual), no consolidar: cada monto manual es un ítem independiente
  const isManualAmount = product.idProducto === null
  const exist = isManualAmount 
    ? null
    : cartObj.products.find((p) => toInt(p.idProducto) === toInt(product.idProducto))

  try {
    if (exist) {
      // El producto ya existe: aumentar cantidad
      console.log("[CART] Producto existe, aumentando cantidad:", product.nombre)
      
      // Sincronizar con BD: agregar +1 cantidad
      const response = await fetchJson("?pg=sales&action=addProductToSale", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idVenta: cartObj.idVenta,
          idProducto: product.idProducto,
          cantidad: 1, // Aumentar en 1
          precioUnitario: product.precioVenta,
        }),
      })

      if (!response.success) {
        throw new Error("No se pudo sincronizar con BD: " + response.error)
      }

      // Actualizar en memoria solo si BD fue exitosa
      exist.cantidad++
      exist.precioTotal = exist.cantidad * exist.precioVenta
      console.log("[CART] ✅ Producto sincronizado en BD")
      
    } else {
      // Producto nuevo: insertar en BD primero
      console.log("[CART] Producto nuevo, agregando a BD:", product.nombre)
      
      const manualId = isManualAmount ? `manual-${Date.now()}-${Math.random().toString(16).slice(2, 8)}` : null
      
      // Sincronizar con BD
      const response = await fetchJson("?pg=sales&action=addProductToSale", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idVenta: cartObj.idVenta,
          idProducto: product.idProducto,
          cantidad: 1,
          precioUnitario: product.precioVenta,
        }),
      })

      if (!response.success) {
        throw new Error("No se pudo agregar producto en BD: " + response.error)
      }

      // Agregar a carrito en memoria con idDetalleVenta guardado
      const newProduct = {
        idProducto: product.idProducto,
        nombre: product.nombre,
        imagen: product.imagen,
        precioVenta: toFloat(product.precioVenta),
        cantidad: 1,
        precioTotal: toFloat(product.precioVenta),
        isManualAmount: isManualAmount,
        manualId: manualId,
        idDetalleVenta: response.data?.idDetalle // ← Guardar para poder eliminarlo después
      }
      
      cartObj.products.push(newProduct)
      console.log("[CART] ✅ Producto agregado en BD con idDetalleVenta:", response.data?.idDetalle)
    }

    updateCart(cartId)

  } catch (error) {
    console.error("[CART] Error en addToCart:", error)

    // La venta desapareció mientras se trabajaba (anulada desde otro equipo,
    // base restaurada...): se crea una nueva y se reintenta una sola vez, para
    // que el cajero no tenga que recargar ni perder lo que ya marcó.
    if (esVentaInexistente(error.message) && !reintento) {
      const cartObj2 = getCart(cartId)
      cartObj2.idVenta = null
      const nuevoId = await asegurarVentaDelCarrito(cartId)
      if (nuevoId) {
        // Los detalles anteriores pertenecían a la venta que ya no existe.
        cartObj2.products.forEach((p) => { delete p.idDetalleVenta })
        return addToCart(product, true)
      }
    }

    alert("Error al agregar producto: " + error.message)
  }
}

  /**
   * Actualiza el carrito (total, cantidad, UI)
   */
function updateCart(cartId = null) {
  const targetCartId = cartId || currentCartId
  const cartObj = getCart(targetCartId)
  const totalEl = getById(`total-${targetCartId}`)
  const countEl = getById(`ventasCount-${targetCartId}`)

  const totalItems = cartObj.products.reduce((sum, p) => sum + p.cantidad, 0)
  if (countEl) countEl.textContent = totalItems

  const total = cartObj.products.reduce((sum, p) => sum + p.precioVenta * p.cantidad, 0)
  cartObj.total = total
  if (totalEl) totalEl.textContent = formatCurrency(total);

  showCartProducts(targetCartId)
}

/**
 * Limpia el carrito
 */
async function clearCart(cartId) {
  const targetCartId = cartId || currentCartId
  const cartObj = carts[targetCartId]
  if (!cartObj) return
  // Ventas normales: eliminar detalles en BD y mantener el mismo idVenta
  if (cartObj.idVenta) {
    const productos = cartObj.products || []
    for (const p of productos) {
      if (!p.idDetalleVenta) continue
      try {
        await fetchJson("?pg=sales&action=removeProductFromSale", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ idDetalleVenta: p.idDetalleVenta }),
        })
      } catch (error) {
        console.warn("No se pudo eliminar producto", p.idDetalleVenta, error)
      }
    }
  }
  if (cartObj.tableId) {
      await reloadTableSale(cartObj.tableId)
    }
  // Limpiar productos en memoria
  cartObj.products = []
  cartObj.total = 0
  updateCart(targetCartId)
}

/**
 * Muestra los productos del carrito en el DOM
 */
function showCartProducts(cartId) {
  const cartObj = carts[cartId] || { products: [] }
  const container = getById(`productos-carrito-${cartId}`)
  if (!container) return

  container.innerHTML = ""
  if (cartObj.products.length === 0) {
    renderEmptyState(container, "Carrito vacío")
    return
  }

  cartObj.products.forEach((p) => {
    const productIdLiteral = p.idProducto === null ? "null" : p.idProducto
    const manualLiteral = p.manualId ? `'${p.manualId}'` : "null"

    const div = document.createElement("div")
    div.className = "cart-product"
    div.innerHTML = `
      <div class="row align-items-center">
        <div class="col-auto">
          <img src="${p.imagen ? "assets/img/products/" + p.imagen : "assets/img/products/default.png"}" 
               class="product-img">
        </div>
        <div class="col">
          <div class="product-title">${p.nombre}</div>
          <div class="product-actions mt-2">
            <div class="quantity-control">
              <button onclick="decreaseQty(${productIdLiteral}, '${cartId}', ${manualLiteral})">−</button>
              <input type="text" value="${p.cantidad}" readonly>
              <button onclick="increaseQty(${productIdLiteral}, '${cartId}', ${manualLiteral})">+</button>
            </div>
            <button class="remove-btn" onclick="dropProduct(${productIdLiteral}, '${cartId}', ${manualLiteral})">
              <i class="fa-solid fa-trash-can icon-delete"></i>
            </button>
          </div>
        </div>
        <div class="col-auto">
          <div class="price">$ ${formatCurrency(p.precioTotal)}</div>
        </div>
      </div>`
    container.appendChild(div)
  })
}

/**
 * Elimina un producto del carrito
 */
async function dropProduct(idProducto, cartId, manualId = null) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[CART] dropProduct: carrito no encontrado", cartId)
    return
  }
  
  console.log("[CART] dropProduct:", { idProducto, cartId, manualId })
  
  // Encontrar el producto a eliminar
  const producto = cartObj.products.find((p) => {
    if (idProducto === null) {
      return p.idProducto === null && p.isManualAmount === true && p.manualId === manualId
    } else {
      return toInt(p.idProducto) === toInt(idProducto)
    }
  })
  
  if (!producto) {
    console.error("[CART] dropProduct: producto no encontrado", { idProducto, manualId })
    return
  }
  
  // Si la venta está en BD (tiene idVenta e idDetalleVenta), eliminar desde BD
  if (cartObj.idVenta && producto.idDetalleVenta) {
    try {
      console.log("[CART] Eliminando producto de BD con idDetalleVenta:", producto.idDetalleVenta)
      
      const response = await fetchJson("?pg=sales&action=removeProductFromSale", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ 
          idDetalleVenta: producto.idDetalleVenta 
        }),
      })
      
      if (!response.success) {
        alert("Error al eliminar producto: " + response.error)
        return
      }

      // Eliminar del carrito en memoria después de éxito en BD
      cartObj.products = cartObj.products.filter((p) => p.idDetalleVenta !== producto.idDetalleVenta)
      updateCart(cartId)
      console.log("[CART] ✅ Producto eliminado correctamente de BD y carrito")
      
    } catch (error) {
      console.error("[CART] Error al eliminar producto de BD:", error)
      alert("Error al eliminar el producto. Intenta de nuevo.")
    }
    return
  }
  
  // Si es solo en memoria (carrito local sin idVenta)
  console.log("[CART] Eliminando producto de memoria (sin BD)")
  if (idProducto === null) {
    cartObj.products = cartObj.products.filter((p) => !(p.idProducto === null && p.isManualAmount === true && p.manualId === manualId))
  } else {
    cartObj.products = cartObj.products.filter((p) => toInt(p.idProducto) !== toInt(idProducto))
  }
  
  updateCart(cartId)
  console.log("[CART] ✅ Producto eliminado de memoria")
}

/**
 * Aumenta la cantidad de un producto
 */
async function increaseQty(idProducto, cartId, manualId = null) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[CART] increaseQty: carrito no encontrado", cartId)
    return
  }
  
  const isManualAmount = idProducto === null
  const p = isManualAmount
    ? cartObj.products.find((p) => p.idProducto === null && p.isManualAmount === true && p.manualId === manualId)
    : cartObj.products.find((p) => toInt(p.idProducto) === toInt(idProducto))
  
  if (!p) {
    console.error("[CART] increaseQty: producto no encontrado")
    return
  }

  console.log("[CART] increaseQty:", { producto: p.nombre, cantidadActual: p.cantidad })

  // Si el carrito tiene idVenta en BD, sincronizar directamente
  if (cartObj.idVenta && p.idDetalleVenta) {
    try {
      const response = await fetchJson("?pg=sales&action=addProductToSale", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idVenta: cartObj.idVenta,
          idProducto: p.idProducto,
          cantidad: 1, // Aumentar en 1
          precioUnitario: p.precioVenta,
        }),
      })

      if (!response.success) {
        console.error("[CART] Error al aumentar cantidad en BD:", response.error)
        return
      }

      p.cantidad++
      p.precioTotal = p.cantidad * p.precioVenta
      updateCart(cartId)
      console.log("[CART] Cantidad aumentada en BD:", p.nombre)
    } catch (error) {
      console.error("[CART] Error sincronizando cantidad:", error)
      alert("Error al sincronizar: " + error.message)
    }
    return
  }

  // Si es solo en memoria (sin idVenta)
  p.cantidad++
  p.precioTotal = p.cantidad * p.precioVenta
  updateCart(cartId)
}

/**
 * Disminuye la cantidad de un producto
 */
async function decreaseQty(idProducto, cartId, manualId = null) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[CART] decreaseQty: carrito no encontrado", cartId)
    return
  }
  
  const isManualAmount = idProducto === null
  const p = isManualAmount
    ? cartObj.products.find((p) => p.idProducto === null && p.isManualAmount === true && p.manualId === manualId)
    : cartObj.products.find((p) => toInt(p.idProducto) === toInt(idProducto))
  
  if (!p || p.cantidad <= 1) return

  console.log("[CART] decreaseQty:", { producto: p.nombre, cantidadActual: p.cantidad })

  // Si el carrito tiene idVenta en BD, sincronizar con la cantidad ABSOLUTA.
  // (Antes se enviaba -1 a addProductToSale, pero la validación de montos
  // rechaza cantidades negativas; por eso el botón de restar dejó de servir.)
  if (cartObj.idVenta && p.idDetalleVenta) {
    try {
      const response = await fetchJson("?pg=sales&action=updateProductQuantity", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idDetalleVenta: p.idDetalleVenta,
          cantidad: p.cantidad - 1, // nueva cantidad (siempre >= 1 aquí)
        }),
      })

      if (!response.success) {
        console.error("[CART] Error al disminuir cantidad en BD:", response.error)
        return
      }

      p.cantidad--
      p.precioTotal = p.cantidad * p.precioVenta
      updateCart(cartId)
      console.log("[CART] Cantidad disminuida en BD:", p.nombre)
    } catch (error) {
      console.error("[CART] Error sincronizando cantidad:", error)
      alert("Error al sincronizar: " + error.message)
    }
    return
  }

  // Si es solo en memoria (sin idVenta)
  p.cantidad--
  p.precioTotal = p.cantidad * p.precioVenta
  updateCart(cartId)
}

// ============================================================================
// MODAL DE PAGO Y CONFIRMACIÓN
// ============================================================================

/**
 * Abre el modal de confirmación de venta
 */
function saleConfirmationModal(cartId, idMesa = null) {
  const currentTab = getCurrentTabInfo()
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return
  console.log(getCart(cartId))
  let total = 0
  
  total = getCart(cartId).total

  if (total <= 0) {
    alert("Carrito vacío. Agrega productos antes de procesar la venta.")
    return
  }
  setCashReceived(0);
  getById("saleTotalValue").textContent = `$ ${formatCurrency(total)}`
  overlay.dataset.paymentMethod = "efectivo"
  overlay.dataset.cartId = cartId
  overlay.dataset.idMesa = idMesa || ""
  overlay.dataset.cartType = currentTab.type
  overlay.dataset.total = String(total) // para calcular la devuelta

  // Método por defecto: efectivo
  overlay.querySelectorAll(".payment-btn").forEach((b) => b.classList.remove("active"))
  getById("salePaymentEfectivo").classList.add("active")

  // Preparar la caja de "efectivo recibido / devuelta"
  overlay.dataset.recibido = "0"
  const disp = getById("cashReceivedDisplay")
  if (disp) disp.textContent = "0"
  const changeVal = getById("cashChangeValue")
  if (changeVal) { changeVal.textContent = "$ 0"; changeVal.classList.remove("text-danger") }
  toggleCashBox("efectivo")

  overlay.classList.add("active")
}

/**
 * Selecciona el método de pago (solo uno)
 */
function selectPaymentMethod(btn, method) {
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return
  overlay.querySelectorAll(".payment-btn").forEach((b) => b.classList.remove("active"))
  if (btn && btn.classList) btn.classList.add("active")
  overlay.dataset.paymentMethod = method
  toggleCashBox(method)
}

/** Muestra la caja de efectivo recibido solo cuando el método es efectivo. */
function toggleCashBox(method) {
  const box = getById("cashChangeBox")
  if (box) box.style.display = method === "efectivo" ? "block" : "none"
}

/** Fija el efectivo recibido y recalcula la devuelta (solo se muestra). */
function setCashReceived(valor) {
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return
  overlay.dataset.recibido = String(valor || 0)
  const disp = getById("cashReceivedDisplay")
  if (disp) disp.textContent = formatCurrency(valor || 0)
  computeChange()
}

/** Calcula la devuelta = recibido - total (no se guarda en BD). */
function computeChange() {
  const overlay = getById("saleConfirmationOverlay")
  const out = getById("cashChangeValue")
  const changeContainer = getById("cashChangeContainer");
  if (!overlay || !out) return
  const total = parseFloat(overlay.dataset.total || "0") || 0
  const recibido = parseFloat(overlay.dataset.recibido || "0") || 0
  if (changeContainer) {
    changeContainer.style.display = recibido > 0 ? "" : "none"; 
  }
  const vuelto = recibido - total 
  const label = getById("cashChangeLabel")
  if (label) label.textContent = vuelto > 0 ? "Devuelta" : "Faltante"
  out.textContent = "$ " + formatCurrency(vuelto > 0 ? vuelto : vuelto)
  out.classList.toggle("text-danger", recibido > 0 && recibido < total)
}

// ============================================================================
// CALCULADORA TÁCTIL DE EFECTIVO RECIBIDO
// ============================================================================
let cashCalcValue = ""

function openCashCalc() {
  cashCalcValue = ""
  updateCashCalcDisplay()
  const ov = getById("cashCalcOverlay")
  if (ov) ov.classList.add("active")
}

function closeCashCalc(event) {
  if (event && event.target && event.target.id !== "cashCalcOverlay") return
  const ov = getById("cashCalcOverlay")
  if (ov) ov.classList.remove("active")
}

function addCashDigit(d) {
  // Evita ceros a la izquierda y limita a 8 dígitos
  if (cashCalcValue === "0") cashCalcValue = ""
  if (cashCalcValue.length < 8) cashCalcValue += d
  updateCashCalcDisplay()
}

function deleteCashDigit() {
  cashCalcValue = cashCalcValue.slice(0, -1)
  updateCashCalcDisplay()
}

function clearCashCalc() {
  cashCalcValue = ""
  updateCashCalcDisplay()
}

function updateCashCalcDisplay() {
  const disp = getById("cashCalcDisplay")
  const val = parseInt(cashCalcValue || "0", 10)
  if (disp) disp.textContent = "$" + formatCurrency(val)
}

function confirmCashCalc() {
  const val = parseInt(cashCalcValue || "0", 10)
  setCashReceived(val)
  const ov = getById("cashCalcOverlay")
  if (ov) ov.classList.remove("active")
}

/**
 * Cierra el modal de confirmación de venta
 */
function closeSaleConfirmation(event) {
  const el = getById("saleConfirmationOverlay")
  if (!el) return
  if (!event || event.target.id === "saleConfirmationOverlay") {
    el.classList.remove("active")
  }
}

/**
 * Confirma el pago y procesa la venta
 */
function confirmSalePayment() {
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return

  const metodo = overlay.dataset.paymentMethod || "efectivo"
  const cartId = overlay.dataset.cartId || currentCartId
  const idMesa = overlay.dataset.idMesa
  const cartType = overlay.dataset.cartType

  console.log("[CART] Confirmando pago:", { cartType, cartId, idMesa, metodo })

  if (cartType === "table" && idMesa) {
    completeTableSale(toInt(idMesa), metodo)
  } else {
    const userId = getSessionUserId()
    saleProcess(cartId, userId, metodo)
  }

  overlay.classList.remove("active")
}

/**
 * Procesa una venta siguiendo el flujo: Crear pendiente → Agregar productos → Completar
 */
/**
 * Procesa una venta usando el idVenta que ya existe desde la creación del tab
 * Paso 2: Agregar productos → Paso 3: Completar venta
 */
/**
 * ¿El servidor está diciendo que la venta ya no existe?
 *
 * Puede pasar si la venta se anuló desde otro dispositivo, si se limpió una
 * venta vacía, o tras restaurar la base de datos. Antes esto dejaba la pestaña
 * "zombie": con productos en pantalla pero imposible de cobrar o cerrar.
 */
function esVentaInexistente(mensaje) {
  const t = String(mensaje || "").toLowerCase()
  return t.includes("venta no encontrada")
      || t.includes("no encontrada o no está pendiente")
      || t.includes("no encontrada o no esta pendiente")
      // Red de seguridad: si la venta fue borrada, la base rechaza el detalle
      // con un error de llave foránea. Significa lo mismo.
      || t.includes("foreign key")
      || t.includes("1452")
}

/**
 * Recrea en el servidor la venta de un carrito cuyo idVenta se perdió,
 * reenviando los productos que siguen en memoria.
 *
 * Es lo que permite al cajero seguir cobrando sin perder lo que ya marcó.
 * Devuelve el nuevo idVenta, o null si no se pudo.
 */
async function recrearVentaDelCarrito(cartId) {
  const cartObj = getCart(cartId)
  if (!cartObj || !cartObj.products || cartObj.products.length === 0) return null

  console.warn("[CART] La venta ya no existe en el servidor. Recreándola para no perder el pedido…")

  const productos = cartObj.products.map((p) => ({
    idProducto: p.idProducto ?? null,
    cantidad: p.cantidad,
    precio: p.precioVenta,
    nombre: p.nombre,
  }))

  const fd = new FormData()
  fd.append("productos", JSON.stringify(productos))

  try {
    const respuesta = await fetchJson("?pg=sales&action=CreateSale", { method: "POST", body: fd })
    if (!respuesta.success || !respuesta.data?.idVenta) return null

    const nuevoId = respuesta.data.idVenta
    cartObj.idVenta = nuevoId

    // Los detalles viejos ya no sirven: el servidor creó líneas nuevas.
    cartObj.products.forEach((p) => { delete p.idDetalleVenta })

    // El id del tab también se usa para cerrar/cancelar: hay que actualizarlo.
    const link = document.querySelector(`#ventasTabs a[href="#${cartId}"]`)
    if (link) link.setAttribute("id", String(nuevoId))

    console.log("[CART] ✅ Venta recreada con id:", nuevoId)
    return nuevoId
  } catch (e) {
    console.error("[CART] No se pudo recrear la venta:", e)
    return null
  }
}

async function saleProcess(cartId, userId, paymentMethod = "efectivo", yaSeIntentoRecuperar = false) {
  if (!cartId || currentCartId !== cartId) cartId = currentCartId

  const cartObj = getCart(cartId)

  if (!cartObj.products || cartObj.products.length === 0) {
    alert("El carrito está vacío")
    return
  }

  // Sin venta asociada (pestaña zombie): se recrea con lo que hay en pantalla
  // en vez de dejar al cajero bloqueado.
  if (!cartObj.idVenta) {
    const recuperado = await recrearVentaDelCarrito(cartId)
    if (!recuperado) {
      alert("No se pudo preparar la venta. Vuelve a marcar los productos en una pestaña nueva.")
      return
    }
  }

  console.log("[CART] Procesando venta con idVenta existente:", { cartId, idVenta: cartObj.idVenta, productos: cartObj.products.length })

  try {
    // PASO 2: Agregar productos que aún no estén agregados en BD
    console.log("[CART] PASO 2: Validando productos en BD...")
    
    for (const producto of cartObj.products) {
      // Si ya tiene idDetalleVenta, significa que ya fue agregado a BD
      if (producto.idDetalleVenta) {
        console.log("[CART] Producto ya en BD:", producto.nombre)
        continue
      }

      const addResponse = await fetchJson("?pg=sales&action=addProductToSale", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idVenta: cartObj.idVenta,
          idProducto: producto.idProducto,
          cantidad: producto.cantidad,
          precioUnitario: producto.precioVenta,
          idUsuario: userId,
        }),
      })

      if (!addResponse.success) {
        // Si la venta desapareció justo ahora, se recrea y se reintenta el
        // cobro completo una vez, sin que el cajero pierda el pedido.
        if (esVentaInexistente(addResponse.error) && !yaSeIntentoRecuperar) {
          yaSeIntentoRecuperar = true
          const nuevoId = await recrearVentaDelCarrito(cartId)
          if (nuevoId) {
            return saleProcess(cartId, userId, paymentMethod, true)
          }
        }
        throw new Error("Error al agregar producto: " + addResponse.error)
      }

      if (addResponse.data?.idDetalle) {
        producto.idDetalleVenta = addResponse.data.idDetalle
      }

      console.log("[CART] ✅ Producto agregado:", producto.nombre)
    }

    // PASO 3: Completar la venta
    console.log("[CART] PASO 3: Completando venta...")
    const completeResponse = await fetchJson("?pg=sales&action=CompleteSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        idVenta: cartObj.idVenta,
        metodoPago: paymentMethod,
      }),
    })

    if (!completeResponse.success) {
      if (esVentaInexistente(completeResponse.error) && !yaSeIntentoRecuperar) {
        yaSeIntentoRecuperar = true
        const nuevoId = await recrearVentaDelCarrito(cartId)
        if (nuevoId) {
          return saleProcess(cartId, userId, paymentMethod, true)
        }
      }
      throw new Error( completeResponse.error)
    }

    console.log("[CART] ✅ Venta completada exitosamente")

    // Mostrar confirmación
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: "success",
        title: "¡Éxito!",
        text: "Venta registrada correctamente",
        timer: 1500,
        showConfirmButton: false,
      })
    }

    // Abrir comprobante (se cierra solo al hacer click en la aplicación)
    openBillWindow(completeResponse.saleId)

    // Limpiar carrito en memoria y en pantalla
    cartObj.products = []
    cartObj.total = 0
    cartObj.idVenta = null
    updateCart(cartId)

    if (cartId === "venta1") {
      // La pestaña principal NO se elimina: se le crea una venta pendiente
      // nueva para poder seguir cobrando de inmediato (como estaba antes).
      await reinitMainSaleTab()
    } else {
      // Las pestañas extra (Venta 2, 3, ...) sí se cierran al facturar.
      dropTab(cartId)
    }

  } catch (error) {
    console.error("[CART] Error en saleProcess:", error)
    alert(error.message)
  }
}

/**
 * Reinicia la pestaña fija "venta1" con una venta pendiente nueva, para que
 * después de facturar se pueda seguir vendiendo ahí sin recargar la página.
 */
async function reinitMainSaleTab() {
  const link = document.querySelector('#ventasTabs a[href="#venta1"]')
  if (link) link.setAttribute("id", "") // forzar que se cree una venta nueva

  carts["venta1"] = { type: "sale", products: [], total: 0, idVenta: null }

  try {
    if (typeof initializeDefaultTab === "function") {
      await initializeDefaultTab() // crea la venta pendiente y asigna idVenta
    }
  } catch (e) {
    console.error("[CART] No se pudo reiniciar venta1:", e)
  }

  updateCart("venta1")
}

console.log("✅ [CART] Módulo de carrito cargado correctamente")
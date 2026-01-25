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
 * Añade un producto al carrito
 */
async function addToCart(product) {
  const currentTab = getCurrentTabInfo()

  console.log("[CART] Adding to cart:", { type: currentTab.type, cartId: currentTab.cartId, idMesa: currentTab.idMesa, product: product.nombre })

  // Si es mesa → agregar a BD
  if (currentTab.type === "table") {
    await addProductToTableSale(currentTab.idMesa, product)
    return
  }

  // Si es venta en memoria
  const cartId = currentTab.cartId
  const cartObj = getCart(cartId)
  
  // Para NULL (monto manual), no consolidar: cada monto manual es un ítem independiente
  const isManualAmount = product.idProducto === null
  const exist = isManualAmount 
    ? null
    : cartObj.products.find((p) => toInt(p.idProducto) === toInt(product.idProducto))

  if (exist) {
    exist.cantidad++
    exist.precioTotal = exist.cantidad * exist.precioVenta
  } else {
    const manualId = isManualAmount ? `manual-${Date.now()}-${Math.random().toString(16).slice(2, 8)}` : null
    cartObj.products.push({
      idProducto: product.idProducto,
      nombre: product.nombre,
      imagen: product.imagen,
      precioVenta: toFloat(product.precioVenta),
      cantidad: 1,
      precioTotal: toFloat(product.precioVenta),
      isManualAmount: isManualAmount,
      manualId: manualId
    })
  }

  updateCart(cartId)
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

  // Mesas: limpiar en BD y refrescar
  if (cartObj.type === "table") {
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
    // Refrescar datos de la mesa desde servidor
    if (cartObj.tableId) {
      await reloadTableSale(cartObj.tableId)
    }
    return
  }

  // Ventas locales en memoria
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
function dropProduct(idProducto, cartId, manualId = null) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[CART] dropProduct: carrito no encontrado", cartId)
    return
  }
  
  // Manejar NULL (monto manual)
  if (idProducto === null) {
    cartObj.products = cartObj.products.filter((p) => !(p.idProducto === null && p.isManualAmount === true && p.manualId === manualId))
  } else {
    cartObj.products = cartObj.products.filter((p) => toInt(p.idProducto) !== toInt(idProducto))
  }
  
  // Actualizar el carrito específico
  updateCart(cartId)
}

/**
 * Aumenta la cantidad de un producto
 */
function increaseQty(idProducto, cartId, manualId = null) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[CART] increaseQty: carrito no encontrado", cartId)
    return
  }
  
  const isManualAmount = idProducto === null
  const p = isManualAmount
    ? cartObj.products.find((p) => p.idProducto === null && p.isManualAmount === true && p.manualId === manualId)
    : cartObj.products.find((p) => toInt(p.idProducto) === toInt(idProducto))
  
  if (p) {
    p.cantidad++
    p.precioTotal = p.cantidad * p.precioVenta
    updateCart(cartId)
  }
}

/**
 * Disminuye la cantidad de un producto
 */
function decreaseQty(idProducto, cartId, manualId = null) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[CART] decreaseQty: carrito no encontrado", cartId)
    return
  }
  
  const isManualAmount = idProducto === null
  const p = isManualAmount
    ? cartObj.products.find((p) => p.idProducto === null && p.isManualAmount === true && p.manualId === manualId)
    : cartObj.products.find((p) => toInt(p.idProducto) === toInt(idProducto))
  
  if (p && p.cantidad > 1) {
    p.cantidad--
    p.precioTotal = p.cantidad * p.precioVenta
    updateCart(cartId)
  }
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

  let total = 0
  if (currentTab.type === "table") {
    const totalEl = getById(`total-${cartId}`)
    total = toFloat((totalEl?.textContent || "0").replace(/[^0-9.-]+/g, ""))
  } else {
    total = getCart(cartId).total
  }

  if (total <= 0) {
    alert("Carrito vacío. Agrega productos antes de procesar la venta.")
    return
  }

  getById("saleTotalValue").textContent = `$ ${formatCurrency(total)}`
  overlay.dataset.paymentMethod = "efectivo"
  overlay.dataset.cartId = cartId
  overlay.dataset.idMesa = idMesa || ""
  overlay.dataset.cartType = currentTab.type

  getById("salePaymentEfectivo").classList.add("active")
  getById("salePaymentTransfer").classList.remove("active")

  overlay.classList.add("active")
}

/**
 * Selecciona el método de pago
 */
function selectPaymentMethod(btn, method) {
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return
  overlay.querySelectorAll(".payment-btn").forEach((b) => b.classList.remove("active"))
  if (btn && btn.classList) btn.classList.add("active")
  overlay.dataset.paymentMethod = method
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
 * Procesa una venta
 */
function saleProcess(cartId, userId, paymentMethod = "efectivo") {
  if (!cartId || currentCartId !== cartId) cartId = currentCartId

  const cartObj = getCart(cartId)

  if (!cartObj.products || cartObj.products.length === 0) {
    alert("El carrito está vacío")
    return
  }

  console.log("[CART] Procesando venta:", { cartId, productos: cartObj.products.length, total: cartObj.total })

  const payload = {
    cartId: cartId,
    tipo: cartObj.type,
    tableId: cartObj.tableId || null,
    tableNumber: cartObj.tableNumber || null,
    metodoPago: paymentMethod,
    idUsuario: userId,
    total: cartObj.total,
    productos: cartObj.products.map((p) => ({
      idProducto: p.idProducto,
      cantidad: p.cantidad,
      precioUnitario: p.precioVenta,
      precioTotal: p.precioTotal,
    })),
  }

  fetchJson("?pg=sales&action=CreateSale", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  })
    .then((data) => {
      if (data.success) {
        if (typeof Swal !== "undefined") {
          Swal.fire({
            icon: "success",
            title: "¡Éxito!",
            text: "Venta registrada correctamente",
            timer: 1500,
            showConfirmButton: false,
          })
        }

        window.open(`?pg=bill&id=${data.saleId}`, "_blank", "width=350,height=900")

        const cartObj = getCart(cartId)
        cartObj.products = []
        cartObj.total = 0
        updateCart()

        console.log("[CART] ✅ Venta procesada correctamente")
      } else {
        alert("Error: " + data.error)
      }
    })
    .catch((err) => {
      console.error("[CART] Error al procesar venta:", err)
      alert("Error al procesar la venta. Intenta de nuevo.")
    })
}

console.log("✅ [CART] Módulo de carrito cargado correctamente")
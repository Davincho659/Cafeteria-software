// ============================================================================
// TABLES.JS - GESTIÓN DE MESAS
// ============================================================================
// Este módulo gestiona las mesas con ventas activas y su persistencia en BD
// Utiliza el estado global definido en Sales.js (activeTables, carts, etc.)

// ============================================================================
// FUNCIONES DE GESTIÓN DE MESAS
// ============================================================================

/**
 * Carga las mesas activas desde el servidor
 */
async function loadActiveTables() {
  try {
    console.log("[TABLES] Cargando mesas activas...")
    const data = await fetchJson("?pg=sales&action=GetTables")

    if (data.success) {
      console.log("[TABLES] Mesas cargadas:", data.data.length)

      const mesasActivas = data.data.filter((mesa) => mesa.idVenta !== null && mesa.idVenta !== undefined)

      console.log("[TABLES] Mesas con ventas activas:", mesasActivas.length)

      mesasActivas.forEach((mesa) => {
        activeTables[mesa.idMesa] = {
          idMesa: mesa.idMesa,
          idVenta: mesa.idVenta,
          numero: mesa.numeroMesa,
          total: toFloat(mesa.total || 0),
        }

        const tabId = `mesa-${mesa.idMesa}`
        if (!getById(tabId)) {
          createTableTab(mesa.idMesa, mesa.numeroMesa, mesa.idVenta, false)
        }
      })

      console.log("[TABLES] Mesas activas cargadas:", Object.keys(activeTables).length)
    }
  } catch (error) {
    console.error("[TABLES] Error cargando mesas:", error)
  }
}

/**
 * Abre el modal de selección de mesas
 */
async function openTableSelectionModal(event) {
  if (event) event.stopPropagation()

  // "Agregar a Mesa" ahora muestra el TABLERO del salón (modo transferencia):
  // se toca una mesa libre y se le pasa el pedido actual.
  if (typeof openTablesBoard === "function") {
    openTablesBoard("transfer")
    return
  }

  // Respaldo (por si el tablero no está disponible): lista simple de mesas.
  try {
    const data = await fetchJson("?pg=sales&action=GetTables")
    if (data.success) showTableSelectionPopup(data.data)
  } catch (error) {
    console.error("[TABLES] Error al cargar mesas:", error)
    alert("Error al cargar mesas")
  }
}

/**
 * Muestra el popup de selección de mesas
 */
function showTableSelectionPopup(mesas) {
  const container = getById("tableContainer")
  if (!container) return
  container.innerHTML = ""

  mesas.forEach((mesa) => {
    const btn = document.createElement("button")
    btn.className = "m-2 table-card p-2"

    const isOccupied = mesa.idVenta !== null && mesa.idVenta !== undefined
    const numeroMesa = mesa.numeroMesa
    const statusClass = isOccupied ? "mesa-status-occupied" : "mesa-status-available"

    btn.innerHTML = `
      <h4 class="${statusClass}">Mesa #${numeroMesa}</h4>
      <img src="assets/img/mesa.jpg" class="table-img" onerror="this.src='assets/img/categories/default.png'">
      <small class="${statusClass}" style="font-weight:bold">
        ${isOccupied ? "Ocupada" : "Disponible"}
      </small>`

    if (isOccupied) {
      btn.style.cssText = "cursor:not-allowed;opacity:0.5"
      btn.onclick = () => Swal.fire({
            icon: "error",
            title: "Mesa ocupada!",
            text: "Cierre la venta actual antes de transferir productos.",
            timer: 1500,
            showConfirmButton: false,
          })
    } else {
      btn.style.cursor = "pointer"
      btn.onclick = () => openOrTransferToTable(mesa.idMesa, numeroMesa)
    }

    container.appendChild(btn)
  })

  getById("tableOverlay").classList.add("active")
}

/**
 * Abre o transfiere productos a una mesa
 */
async function openOrTransferToTable(idMesa, numeroMesa) {
  try {
    const cartId = currentCartId  // Capturar cartId al inicio
    const sourceCart = getCart(cartId)
    const userId = await getUserId()
    const hasProducts = sourceCart.products && sourceCart.products.length > 0

    if (!hasProducts) {
      // Crear nueva venta vacía en la mesa
      console.log("[TABLES] Creando nueva venta vacía en mesa:", { idMesa, numeroMesa })

      const data = await fetchJson("?pg=sales&action=transferProductsToTable", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idMesa: idMesa,
          idUsuario: userId,
          productos: [], // Sin productos iniciales
        }),
      })

      if (data.success) {
        const idVenta = data.data.idVenta 
        const numero = data.data.numeroMesa || numeroMesa

        activeTables[idMesa] = { idMesa, idVenta, numero, total: 0 }
        const tabId = createTableTab(idMesa, numero, idVenta, true)

        // Cargar productos vacíos para inicializar el carrito
        loadTableProducts(tabId, [])

        closeTable()
        console.log("[TABLES] ✅ Mesa abierta correctamente:", numero)
      } else {
        alert("Error: " + data.error)
      }
    } else {
      // Transferir productos existentes a la mesa
      console.log("[TABLES] Transfiriendo productos a mesa:", { idMesa, numeroMesa, productos: sourceCart.products.length })

      try {
        const response = await fetch("?pg=sales&action=transferProductsToTable", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            idMesa: idMesa,
            idUsuario: userId,
            productos: sourceCart.products.map((p) => ({
              idProducto: p.idProducto,
              cantidad: p.cantidad,
              precioUnitario: p.precioVenta,
            })),
          }),
        })

        // Leer respuesta como texto primero
        const responseText = await response.text()
        console.log("[TABLES] Respuesta del servidor:", responseText.substring(0, 200))

        // Intentar parsear como JSON
        let data
        try {
          data = JSON.parse(responseText)
        } catch (parseError) {
          console.error("[TABLES] Error parseando JSON:", parseError)
          console.error("[TABLES] Respuesta recibida:", responseText)
          alert("Error del servidor: Respuesta inválida. Verifica la consola para más detalles.")
          return
        }

        if (data.success) {
          const idVenta = data.data.idVenta
          const numero = data.data.numeroMesa
          const productos = data.data.productos || []

          activeTables[idMesa] = { idMesa, idVenta, numero, total: 0 }
          const tabId = createTableTab(idMesa, numero, idVenta, true)

          // Cargar productos transferidos (pueden ser vacíos o con datos)
          loadTableProducts(tabId, productos)

          // Limpiar carrito usando el cartId capturado
          sourceCart.products = []
          sourceCart.total = 0
          updateCart(cartId)
          dropTab(cartId)
          closeTable()

          console.log("[TABLES] ✅ Productos transferidos correctamente a Mesa", numero)
        } else {
          alert("Error: " + (data.error || "Error desconocido"))
        }
      } catch (fetchError) {
        console.error("[TABLES] Error en fetch:", fetchError)
        alert("Error al procesar la transferencia: " + fetchError.message)
      }
    }
  } catch (error) {
    console.error("[TABLES] Error al procesar mesa:", error)
    alert("Error al procesar la mesa")
  }
}

/**
 * Crea un nuevo tab de mesa
 */
function createTableTab(idMesa, numeroMesa, idVenta, switchTo = true, etiqueta = null) {
  const tabs = getById("ventasTabs")
  const content = getById("ventasContent")
  if (!tabs || !content) return

  const tituloCarrito = etiqueta || `Mesa ${numeroMesa}`
  const tabId = `mesa-${idMesa}`

  if (getById(tabId)) {
    if (switchTo) {
      const tab = document.querySelector(`a[href="#${tabId}"]`)
      if (tab) showTab(tab)
    }
    return tabId
  }

  const li = document.createElement("li")
  // 'mesa-tab-item' permite ocultar las pestañas de mesa de la barra: las mesas
  // se navegan desde el tablero "Ver mesas", no desde pestañas (más ágil).
  li.className = "tab-item mesa-tab-item"
  const a = document.createElement("a")
  a.className = "nav-link"
  a.setAttribute("data-bs-toggle", "tab")
  a.setAttribute("href", `#${tabId}`)
  a.setAttribute("id", idMesa)
  a.setAttribute("data-venta-id", idVenta)
    a.textContent = `Mesa ${numeroMesa} `
  
    // Crear el botón X como elemento separado
    const closeIcon = document.createElement("i")
    closeIcon.className = "fa-solid fa-circle-xmark fa-xl icon-close"
    closeIcon.title = "Eliminar"
  
    // Listener en el X
    closeIcon.addEventListener("click", (e) => {
      console.log("[TABLES] Click en X para cerrar mesa:", idMesa)
      e.stopPropagation()
      e.preventDefault()
      closeTableSale(idVenta, idMesa)
    })
  
    a.appendChild(closeIcon)
   
    // Listener en el tab link
    a.addEventListener("click", (e) => {
      if (!e.target.matches(".fa-circle-xmark")) {
        console.log("[TABLES] Click en mesa tab para cambiar a:", tabId)
        switchToTableCart(tabId, idMesa)
      }
    })
  li.appendChild(a)

  const addTabItem = getById("addTabItem")
  if (addTabItem) tabs.insertBefore(li, addTabItem)
  else tabs.appendChild(li)

  const pane = document.createElement("div")
  pane.className = "tab-pane fade"
  pane.id = tabId
  pane.innerHTML = `
    <div id="carrito-${tabId}">
      <div class="cart-header cart-header-mesa">
        <span class="ch-icon"><i class="fa-solid fa-utensils"></i></span>
        <span class="ch-title">${tituloCarrito}</span>
        <span class="ch-badge" id="ventasCount-${tabId}">0</span>
      </div>
      <div id="productos-carrito-${tabId}" class="cart-scroll"></div>
      <div class="cart-footer">
        <div id="total-carrito-${tabId}"><h4>Total: $<span id="total-${tabId}">0.00</span></h4></div>
        <button class="btn btn-primary btn-lg w-100 mb-2" onclick="saleConfirmationModal('${tabId}', ${idMesa})">
          Facturar <i class="fa-solid fa-receipt"></i>
        </button>
        <button class="btn btn-outline-danger btn-lg w-100" onclick="clearCart('${tabId}')">
          Limpiar carrito <i class="fa-solid fa-trash-can"></i>
        </button>
      </div>
    </div>`
  content.appendChild(pane)

  // Inicializar carrito en memoria para la mesa
  if (!carts[tabId]) {
    carts[tabId] = { type: "table", tableId: idMesa, tableNumber: numeroMesa, products: [], total: 0 }
    console.log("[TABLES] Nuevo carrito de mesa creado:", tabId)
  }

  if (switchTo) {
    showTab(a)
    switchToTableCart(tabId, idMesa)
  }

  return tabId
}

/**
 * Carga los productos de una mesa en el DOM
 */
function loadTableProducts(tabId, productos) {
  const container = getById(`productos-carrito-${tabId}`)
  if (!container) return

  container.innerHTML = ""
  if (!productos || productos.length === 0) {
    renderEmptyState(container, "Sin productos")
    // IMPORTANTE: dejar el total y el contador en 0. Antes se hacía return aquí
    // y el monto quedaba con el valor viejo (se podía facturar una mesa vacía).
    if (carts[tabId]) carts[tabId].total = 0
    const totalEl0 = getById(`total-${tabId}`)
    if (totalEl0) { totalEl0.textContent = formatCurrency(0); totalEl0.dataset.total = "0" }
    const countEl0 = getById(`ventasCount-${tabId}`)
    if (countEl0) countEl0.textContent = "0"
    return
  }

  let total = 0,
  totalItems = 0

  productos.forEach((p) => {
    const qty = toInt(p.cantidad || 0)
    const price = toFloat(p.precioUnitario || p.precioVenta || p.precio || 0)
    const subtotal = toFloat(p.subTotal || p.precioTotal || qty * price)

    total += subtotal
    totalItems += qty

    const nombre = p.producto_nombre || p.nombre || "Producto"
    const imgPath = p.producto_imagen 
    const img = imgPath ? `assets/img/products/${imgPath}` : "assets/img/products/default.png"
    const detalleId = p.idDetalleVenta || p.idDetalle

    const div = document.createElement("div")
    div.className = "cart-product"
    if (detalleId) div.setAttribute("data-detalle-id", detalleId)
    div.innerHTML = `
      <div class="row align-items-center">
        <div class="col-auto">
          <img src="${img}" 
               class="product-img">
        </div>
        <div class="col">
          <div class="product-title">${nombre}</div>
          <div class="product-actions mt-2">
            <div class="quantity-control">
              <button onclick="decreaseTableQty(${detalleId})">−</button>
              <input type="text" value="${qty}" readonly>
              <button onclick="increaseTableQty(${detalleId})">+</button>
            </div>
            <button class="remove-btn" onclick="removeTableProduct(${detalleId})">
              <i class="fa-solid fa-trash-can icon-delete"></i>
            </button>
          </div>
        </div>
        <div class="col-auto">
          <div class="price">$ ${formatCurrency(subtotal)}</div>
        </div>
      </div>`
    container.appendChild(div)
  })

  if (carts[tabId]) {
    carts[tabId].total = total
  }
  const totalEl = getById(`total-${tabId}`)
  if (totalEl) {
    totalEl.textContent = formatCurrency(total)
    totalEl.dataset.total = String(total)
  }
  const countEl = getById(`ventasCount-${tabId}`)
  if (countEl) countEl.textContent = totalItems
}

/**
 * Añade un producto a una venta de mesa
 */
async function addProductToTableSale(idMesa, product) {
  try {
    const tableInfo = activeTables[idMesa]
    if (!tableInfo) {
      console.error("[TABLES] Mesa no inicializada:", idMesa)
      alert("Mesa no inicializada correctamente")
      return
    }

    const userId = await getUserId()

    console.log("[TABLES] Agregando producto a mesa:", {
      idMesa,
      idVenta: tableInfo.idVenta,
      producto: product.nombre,
    })

    const data = await fetchJson("?pg=sales&action=addProductToSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        idVenta: tableInfo.idVenta,
        idProducto: product.idProducto,
        cantidad: 1,
        precioUnitario: product.precioVenta,
        idUsuario: userId,
      }),
    })

    if (data.success) {
      const tabId = `mesa-${idMesa}`
      loadTableProducts(tabId, data.data.productos)
      console.log("[TABLES] ✅ Producto agregado correctamente")
    } else {
      alert("Error: " + data.error)
    }
  } catch (error) {
    console.error("[TABLES] Error al agregar producto:", error)
    alert("Error al agregar producto a la mesa")
  }
}

/**
 * Aumenta la cantidad de un producto de mesa
 */
async function increaseTableQty(idDetalleVenta) {
  const detalle = document.querySelector(`[data-detalle-id="${idDetalleVenta}"]`)
  if (!detalle) return
  const input = detalle.querySelector("input")
  await updateTableProductQty(idDetalleVenta, toInt(input.value) + 1)
}

/**
 * Disminuye la cantidad de un producto de mesa
 */
async function decreaseTableQty(idDetalleVenta) {
  const detalle = document.querySelector(`[data-detalle-id="${idDetalleVenta}"]`)
  if (!detalle) return
  const input = detalle.querySelector("input")
  const qty = toInt(input.value)
  if (qty > 1) await updateTableProductQty(idDetalleVenta, qty - 1)
}

/**
 * Actualiza la cantidad de un producto de mesa
 */
async function updateTableProductQty(idDetalleVenta, cantidad) {
  const idMesa = mesaFromDetalle(idDetalleVenta)
  try {
    const data = await fetchJson("?pg=sales&action=updateProductQuantity", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idDetalleVenta, cantidad }),
    })
    if (data.success && idMesa != null) {
      await reloadTableSale(idMesa)
    }
  } catch (error) {
    console.error("[TABLES] Error al actualizar cantidad:", error)
  }
}

/**
 * Determina a qué mesa pertenece un detalle a partir de su fila en el DOM.
 * Más confiable que leer el tab activo después de un await.
 */
function mesaFromDetalle(idDetalleVenta) {
  const el = document.querySelector(`[data-detalle-id="${idDetalleVenta}"]`)
  const pane = el ? el.closest('[id^="productos-carrito-mesa-"]') : null
  if (pane) return toInt(pane.id.replace("productos-carrito-mesa-", ""))
  const info = getCurrentTabInfo()
  return info.type === "table" ? info.idMesa : null
}

/**
 * Elimina un producto de una venta de mesa.
 * Si la mesa queda sin productos, se libera automáticamente (se cancela la
 * venta pendiente vacía y se cierra su pestaña).
 */
async function removeTableProduct(idDetalleVenta) {
  const idMesa = mesaFromDetalle(idDetalleVenta)
  try {
    const data = await fetchJson("?pg=sales&action=removeProductFromSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idDetalleVenta }),
    })
    if (!data.success || idMesa == null) return

    await reloadTableSale(idMesa)

    // ¿La mesa quedó vacía? → liberarla
    const pane = getById(`productos-carrito-mesa-${idMesa}`)
    const quedanProductos = pane && pane.querySelector(".cart-product")
    if (!quedanProductos) {
      await freeEmptyTable(idMesa)
    }
  } catch (error) {
    console.error("[TABLES] Error al eliminar producto:", error)
  }
}

/**
 * Libera una mesa que quedó sin productos: cancela la venta pendiente vacía y
 * cierra su pestaña. Una mesa sin productos NO está ocupada.
 *
 * @param {number} idMesa
 * @param {Object} opciones
 *   volverAVenta1: si true (por defecto), cambia a la Venta 1 al liberar.
 *   silent:        si true, no muestra el aviso (para liberaciones automáticas).
 */
async function freeEmptyTable(idMesa, opciones = {}) {
  const info = activeTables[idMesa]
  if (!info) return
  try {
    await fetchJson("?pg=sales&action=CancelSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idVenta: info.idVenta }),
    })
  } catch (e) {
    console.warn("[TABLES] No se pudo cancelar la venta vacía:", e)
  }
  if (typeof removeTableTab === "function") removeTableTab(`mesa-${idMesa}`, idMesa)
  delete activeTables[idMesa]

  if (opciones.volverAVenta1 !== false) {
    const venta1 = document.querySelector('#ventasTabs a[href="#venta1"]')
    if (venta1 && typeof showTab === "function") showTab(venta1)
  }

  if (!opciones.silent && typeof Swal !== "undefined") {
    Swal.fire({ toast: true, position: "top-end", icon: "info", title: "Mesa liberada (sin productos)", showConfirmButton: false, timer: 1500 })
  }
}

/**
 * Si el carrito indicado es una MESA sin productos, la libera silenciosamente.
 * Se usa al SALIR de una mesa (cambio de pestaña): una mesa sin productos no
 * debe quedar ocupada.
 */
function liberarMesaSiVacia(cartId) {
  if (!isTableTab(cartId)) return
  const idMesa = toInt(cartId.replace("mesa-", ""))
  if (!activeTables[idMesa]) return
  const pane = getById(`productos-carrito-${cartId}`)
  const tieneProductos = pane && pane.querySelector(".cart-product")
  if (!tieneProductos) {
    // No cambiamos de vista (el usuario ya se movió a otra pestaña) ni avisamos.
    freeEmptyTable(idMesa, { volverAVenta1: false, silent: true })
  }
}

/**
 * Recarga los datos de una venta de mesa desde el servidor
 */
async function reloadTableSale(idMesa) {
  const tableInfo = activeTables[idMesa]
  if (!tableInfo) return

  try {
    console.log("[TABLES] Recargando venta de mesa:", idMesa)
    const data = await fetchJson(`?pg=sales&action=GetSale&id=${tableInfo.idVenta}`)
    if (data.success) {
      loadTableProducts(`mesa-${idMesa}`, data.data.detalles)
    }
  } catch (error) {
    console.error("[TABLES] Error al recargar venta:", error)
  }
}

/**
 * Completa una venta de mesa
 */
async function completeTableSale(idMesa, metodoPago) {
  try {
    console.log("[TABLES] Completando venta de mesa:", { idMesa, metodoPago })

    const data = await fetchJson("?pg=sales&action=completeTableSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idMesa, metodoPago }),
    })

    if (data.success) {
      if (typeof Swal !== "undefined") {
        Swal.fire({
          icon: "success",
          title: "¡Venta Completada!",
          timer: 2000,
          showConfirmButton: false,
        })
      }

      openBillWindow(data.saleId)

      removeTableTab(`mesa-${idMesa}`, idMesa)
      delete activeTables[idMesa]

      console.log("[TABLES] ✅ Venta completada correctamente")
    } else {
      alert("Error: " + data.error)
    }
  } catch (error) {
    console.error("[TABLES] Error al completar venta:", error)
    alert("Error al completar la venta")
  }
}

/**
 * Cierra/cancela una venta de mesa
 */
async function closeTableSale(idVenta, idMesa) {
  const result = await Swal.fire({
    title: "Seguro de eliminar la venta de esta mesa?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#808080",
    confirmButtonText: "Confirmar",
    cancelButtonText: "Cancelar",
  })
  
  if (!result.isConfirmed) {
    return
  }

  try {
    console.log("[TABLES] Cancelando venta de mesa:", idMesa)

    const data = await fetchJson("?pg=sales&action=CancelSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idVenta }),
    })
    
    if (data.success) {
      Swal.fire({
        title: "¡Eliminada con éxito!",
        icon: "success",
        timer: 800,
        showConfirmButton: false,
      })
      
      removeTableTab(`mesa-${idMesa}`, idMesa)
      delete activeTables[idMesa]
      console.log("[TABLES] ✅ Venta cancelada correctamente")
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.error || "No se pudo cancelar la venta"
      })
    }
  } catch (error) {
    console.error("[TABLES] Error al cancelar venta:", error)
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al cancelar la venta de la mesa"
    })
  }
}

/**
 * Cierra el modal de selección de mesas
 */
function closeTable(event) {
  const el = getById("tableOverlay")
  if (!el) return
  if (!event || event.target.id === "tableOverlay") {
    el.classList.remove("active")
  }
}

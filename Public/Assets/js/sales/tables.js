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

  const cartObj = getCart()

  try {
    const data = await fetchJson("?pg=sales&action=GetTables")

    if (data.success) {
      showTableSelectionPopup(data.data)
    }
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
function createTableTab(idMesa, numeroMesa, idVenta, switchTo = true) {
  const tabs = getById("ventasTabs")
  const content = getById("ventasContent")
  if (!tabs || !content) return

  const tabId = `mesa-${idMesa}`

  if (getById(tabId)) {
    if (switchTo) {
      const tab = document.querySelector(`a[href="#${tabId}"]`)
      if (tab) showTab(tab)
    }
    return tabId
  }

  const li = document.createElement("li")
  li.className = "tab-item"
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
      <center class="py-1rem">
        <h3>Mesa ${numeroMesa}: <div class="badge bg-warning rounded-circle" id="ventasCount-${tabId}">0</div></h3>
      </center>
      <div id="productos-carrito-${tabId}" class="cart-scroll"></div>
      <div class="py-1rem">
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

  const totalEl = getById(`total-${tabId}`)
  if (totalEl) totalEl.textContent = formatCurrency(total)
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
  try {
    const data = await fetchJson("?pg=sales&action=updateProductQuantity", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idDetalleVenta, cantidad }),
    })
    if (data.success) {
      const currentTab = getCurrentTabInfo()
      if (currentTab.type === "table") await reloadTableSale(currentTab.idMesa)
    }
  } catch (error) {
    console.error("[TABLES] Error al actualizar cantidad:", error)
  }
}

/**
 * Elimina un producto de una venta de mesa
 */
async function removeTableProduct(idDetalleVenta) {
  try {
    const data = await fetchJson("?pg=sales&action=removeProductFromSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idDetalleVenta }),
    })
    if (data.success) {
      const currentTab = getCurrentTabInfo()
      if (currentTab.type === "table") await reloadTableSale(currentTab.idMesa)
    }
  } catch (error) {
    console.error("[TABLES] Error al eliminar producto:", error)
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

      window.open(`?pg=bill&id=${data.saleId}`, "_blank", "width=350,height=900")

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

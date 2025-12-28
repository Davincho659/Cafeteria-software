// ============================================================================
// SALES.JS - SISTEMA DE VENTAS POR MESAS (VERSIÓN CORREGIDA Y COMPLETA)
// ============================================================================

let categoriasCache = []
let productosCache = []
let currentCartId = "venta1"
const carts = { venta1: { type: "sale", products: [], total: 0 } }
const activeTables = {} // Mesas con ventas activas sincronizadas con BD

// Dependencias globales expuestas por la vista
const bootstrap = window.bootstrap
const Swal = window.Swal

// ============================================================================
// UTILIDADES GENERALES
// ============================================================================

const toInt = (value) => Number.parseInt(value, 10)
const toFloat = (value) => Number.parseFloat(value || 0)
const isTableTab = (cartId = "") => String(cartId).startsWith("mesa-")
const getById = (id) => document.getElementById(id)

const renderEmptyState = (container, message) => {
  if (!container) return
  container.innerHTML = `<div class="text-center p-4"><p class="text-muted">${message}</p></div>`
}

const fetchJson = async (url, options = {}) => {
  const response = await fetch(url, options)
  return response.json()
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Sistema POS iniciado")
  startSystem()

  const nuevaBtn = getById("nuevaVenta")
  if (nuevaBtn) nuevaBtn.addEventListener("click", addNewSaleTab)
})

function startSystem() {
  loadCategories()
  loadProducts()
  loadActiveTables()
}

// ============================================================================
// OBTENER USER ID
// ============================================================================

async function getUserId() {
  const userIdElement = document.querySelector("[data-user-id]")
  if (userIdElement) return toInt(userIdElement.getAttribute("data-user-id"))

  try {
    const data = await fetchJson("?pg=login&action=checkAuth")
    return data.success && data.authenticated ? data.usuario.id : null
  } catch (error) {
    console.error("[v0] Error obteniendo userId:", error)
    return null
  }
}

function getSessionUserId() {
  const userIdElement = document.querySelector("[data-user-id]")
  return userIdElement ? toInt(userIdElement.getAttribute("data-user-id")) : null
}

// ============================================================================
// CARGAR CATEGORÍAS Y PRODUCTOS
// ============================================================================

async function loadCategories() {
  try {
    const data = await fetchJson("?pg=sales&action=getCategories")
    if (data.success) {
      categoriasCache = data.data
      showCategories(data.data)
    }
  } catch (err) {
    console.error("[v0] Error categorías:", err)
  }
}

function showCategories(categories) {
  const container = getById("categoriasNav")
  if (!container) return
  container.innerHTML = ""

  const allBtn = document.createElement("button")
  allBtn.className = "categoria-item active"
  allBtn.innerHTML = `<img src="assets/img/categories/default.png" class="categoria-icon" 
    style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
    <span class="categoria-nombre">Todos</span>`
  allBtn.onclick = () => {
    document.querySelectorAll(".categoria-item").forEach((b) => b.classList.remove("active"))
    allBtn.classList.add("active")
    loadProducts(null)
  }
  container.appendChild(allBtn)

  categories.forEach((cat) => {
    const btn = document.createElement("button")
    btn.className = "categoria-item"
    btn.innerHTML = `<img src="${cat.imagen ? "assets/img/" + cat.imagen : "assets/img/categories/default.png"}" 
      class="categoria-icon" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
      <span class="categoria-nombre">${cat.nombre}</span>`
    btn.onclick = () => {
      document.querySelectorAll(".categoria-item").forEach((b) => b.classList.remove("active"))
      btn.classList.add("active")
      loadProducts(cat.idCategoria)
    }
    container.appendChild(btn)
  })
}

async function loadProducts(idCategoria = null) {
  let url = "?pg=sales&action=getProducts"
  if (idCategoria) url += `&idCategory=${idCategoria}`

  try {
    const data = await fetchJson(url)
    if (data.success) {
      productosCache = data.data
      showProducts(productosCache)
    }
  } catch (err) {
    console.error("[v0] Error productos:", err)
  }
}

function showProducts(products) {
  const container = getById("productosContainer")
  if (!container) return

  if (products.length === 0) {
    renderEmptyState(container, "No hay productos")
    return
  }

  container.innerHTML = ""
  products.forEach((product) => {
    const img = product.imagen
      ? `assets/img/${product.imagen}`
      : product.categoria_imagen
        ? `assets/img/${product.categoria_imagen}`
        : "assets/img/products/default.jpg"

    const btn = document.createElement("button")
    btn.className = "m-2 producto-card p-2"
    btn.style.cssText = "width:200px;height:300px"
    btn.innerHTML = `
      <div class="producto-img-container">
        <img src="${img}" alt="${product.nombre}" class="producto-img">
      </div>
      <div class="d-flex flex-column align-items-left">
        <div class="producto-nombre"><b>${product.nombre}</b></div>
        <div class="producto-categoria">${product.categoria}</div>
        <p class="producto-precio"><b>$ ${formatCurrency(product.precioVenta)}</b></p>
        <span class="btn cantidad-display" id="prod-qty-${product.idProducto}" 
              onclick="event.stopPropagation(); changeQuantity(${product.idProducto})" role="button">1</span>
      </div>`
    btn.onclick = () => addToCart(product)
    container.appendChild(btn)
  })
}

// ============================================================================
// GESTIÓN DE CARRITOS EN MEMORIA (Ventas sin mesa)
// ============================================================================

function getCart(cartId) {
  return carts[cartId || currentCartId] || { type: "sale", products: [], total: 0 }
}

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

async function addToCart(product) {
  const currentTab = getCurrentTabInfo()

  console.log("[v0] Adding to cart:", { type: currentTab.type, cartId: currentTab.cartId, idMesa: currentTab.idMesa, product: product.nombre })

  // Si es mesa → agregar a BD
  if (currentTab.type === "table") {
    await addProductToTableSale(currentTab.idMesa, product)
    return
  }

  // Si es venta en memoria
  const cartId = currentTab.cartId
  const cartObj = getCart(cartId)
  const pid = toInt(product.idProducto)
  const exist = cartObj.products.find((p) => toInt(p.idProducto) === pid)

  if (exist) {
    exist.cantidad++
    exist.precioTotal = exist.cantidad * exist.precioVenta
  } else {
    cartObj.products.push({
      idProducto: product.idProducto,
      nombre: product.nombre,
      imagen: product.imagen,
      precioVenta: toFloat(product.precioVenta),
      cantidad: 1,
      precioTotal: toFloat(product.precioVenta),
    })
  }

  updateCart(cartId)
}

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
    const div = document.createElement("div")
    div.className = "cart-product"
    div.innerHTML = `
      <div class="row align-items-center">
        <div class="col-auto">
          <img src="${p.imagen ? "assets/img/" + p.imagen : "assets/img/products/default.jpg"}" 
               class="product-img" style="width:80px;height:80px;object-fit:cover;border-radius:8px">
        </div>
        <div class="col">
          <div class="product-title">${p.nombre}</div>
          <div class="product-actions mt-2">
            <div class="quantity-control">
              <button onclick="decreaseQty(${p.idProducto}, '${cartId}')">−</button>
              <input type="text" value="${p.cantidad}" readonly>
              <button onclick="increaseQty(${p.idProducto}, '${cartId}')">+</button>
            </div>
            <button class="remove-btn" onclick="dropProduct(${p.idProducto}, '${cartId}')">
              <i class="fa-solid fa-trash-can" style="color:#ff0000"></i>
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

function dropProduct(idProducto, cartId) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[v0] dropProduct: carrito no encontrado", cartId)
    return
  }
  
  cartObj.products = cartObj.products.filter((p) => toInt(p.idProducto) !== toInt(idProducto))
  
  // Actualizar el carrito específico
  updateCart(cartId)
}

function increaseQty(idProducto, cartId) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[v0] increaseQty: carrito no encontrado", cartId)
    return
  }
  
  const p = cartObj.products.find((p) => toInt(p.idProducto) === toInt(idProducto))
  if (p) {
    p.cantidad++
    p.precioTotal = p.cantidad * p.precioVenta
    updateCart(cartId)
  }
}

function decreaseQty(idProducto, cartId) {
  const cartObj = carts[cartId]
  if (!cartObj) {
    console.error("[v0] decreaseQty: carrito no encontrado", cartId)
    return
  }
  
  const p = cartObj.products.find((p) => toInt(p.idProducto) === toInt(idProducto))
  if (p && p.cantidad > 1) {
    p.cantidad--
    p.precioTotal = p.cantidad * p.precioVenta
    updateCart(cartId)
  }
}

// ============================================================================
// GESTIÓN DE MESAS (Persistencia en BD)
// ============================================================================

async function loadActiveTables() {
  try {
    console.log("[v0] Cargando mesas activas...")
    const data = await fetchJson("?pg=sales&action=GetTables")

    if (data.success) {
      console.log("[v0] Mesas cargadas:", data.data.length)

      const mesasActivas = data.data.filter((mesa) => mesa.idVenta !== null && mesa.idVenta !== undefined)

      console.log("[v0] Mesas con ventas activas:", mesasActivas.length)

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

      console.log("[v0] Mesas activas cargadas:", Object.keys(activeTables).length)
    }
  } catch (error) {
    console.error("[v0] Error cargando mesas:", error)
  }
}

async function openTableSelectionModal(event) {
  if (event) event.stopPropagation()

  const cartObj = getCart()

  try {
    const data = await fetchJson("?pg=sales&action=GetTables")

    if (data.success) {
      showTableSelectionPopup(data.data)
    }
  } catch (error) {
    console.error("[v0] Error al cargar mesas:", error)
    alert("Error al cargar mesas")
  }
}

function showTableSelectionPopup(mesas) {
  const container = getById("tableContainer")
  if (!container) return
  container.innerHTML = ""

  mesas.forEach((mesa) => {
    const btn = document.createElement("button")
    btn.className = "m-2 table-card p-2"

    const isOccupied = mesa.idVenta !== null && mesa.idVenta !== undefined
    const numeroMesa = mesa.numeroMesa

    btn.innerHTML = `
      <h4 style="color:${isOccupied ? "red" : "green"}">Mesa #${numeroMesa}</h4>
      <img src="assets/img/mesa.jpg" class="table-img" onerror="this.src='assets/img/categories/default.png'">
      <small style="color:${isOccupied ? "red" : "green"};font-weight:bold">
        ${isOccupied ? "Ocupada" : "Disponible"}
      </small>`

    if (isOccupied) {
      btn.style.cssText = "cursor:not-allowed;opacity:0.5"
      btn.onclick = () => alert("Mesa ocupada. Cierra la venta actual primero.")
    } else {
      btn.style.cursor = "pointer"
      btn.onclick = () => openOrTransferToTable(mesa.idMesa, numeroMesa)
    }

    container.appendChild(btn)
  })

  getById("tableOverlay").classList.add("active")
}

async function openOrTransferToTable(idMesa, numeroMesa) {
  try {
    const cartId = currentCartId  // Capturar cartId al inicio
    const sourceCart = getCart(cartId)
    const userId = await getUserId()
    const hasProducts = sourceCart.products && sourceCart.products.length > 0

    if (!hasProducts) {
      // Crear nueva venta vacía en la mesa
      console.log("[v0] Creando nueva venta vacía en mesa:", { idMesa, numeroMesa })

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
        const idVenta = data.data.idVenta || data.data.venta?.idVenta || data.data.venta?.id || data.data.id
        const numero = data.data.numeroMesa || numeroMesa

        activeTables[idMesa] = { idMesa, idVenta, numero, total: 0 }
        const tabId = createTableTab(idMesa, numero, idVenta, true)

        // Cargar productos vacíos para inicializar el carrito
        loadTableProducts(tabId, [])

        closeTable()
        console.log("[v0] ✅ Mesa abierta correctamente:", numero)
      } else {
        alert("Error: " + data.error)
      }
    } else {
      // Transferir productos existentes a la mesa
      console.log("[v0] Transfiriendo productos a mesa:", { idMesa, numeroMesa, productos: sourceCart.products.length })

      const data = await fetchJson("?pg=sales&action=transferProductsToTable", {
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

      if (data.success) {
        const idVenta = data.data.idVenta || data.data.venta?.idVenta || data.data.venta?.id || data.data.id
        const numero = data.data.numeroMesa || numeroMesa
        const productos = data.data.productos || []

        activeTables[idMesa] = { idMesa, idVenta, numero, total: 0 }
        const tabId = createTableTab(idMesa, numero, idVenta, true)

        if (Array.isArray(productos)) {
          loadTableProducts(tabId, productos)
        } else if (data.data.venta && Array.isArray(data.data.venta.detalles)) {
          loadTableProducts(tabId, data.data.venta.detalles)
        }

        // Limpiar carrito usando el cartId capturado
        sourceCart.products = []
        sourceCart.total = 0
        updateCart(cartId)

        closeTable()

        console.log("[v0] ✅ Productos transferidos correctamente a Mesa", numero)
      } else {
        alert("Error: " + data.error)
      }
    }
  } catch (error) {
    console.error("[v0] Error al procesar mesa:", error)
    alert("Error al procesar la mesa")
  }
}

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
  li.className = "nav-item"
  const a = document.createElement("a")
  a.className = "nav-link"
  a.setAttribute("data-bs-toggle", "tab")
  a.setAttribute("href", `#${tabId}`)
  a.setAttribute("data-table-id", idMesa)
  a.setAttribute("data-venta-id", idVenta)
    a.textContent = `Mesa ${numeroMesa} `
  
    // Crear el botón X como elemento separado
    const closeIcon = document.createElement("i")
    closeIcon.className = "fa-solid fa-circle-xmark fa-xl"
    closeIcon.style.cssText = "color:#ff0000;margin-left:8px;cursor:pointer"
    closeIcon.title = "Eliminar"
  
    // Listener en el X
    closeIcon.addEventListener("click", (e) => {
      console.log("[v0] Click en X para cerrar mesa:", idMesa)
      e.stopPropagation()
      e.preventDefault()
      closeTableSale(idMesa)
    })
  
    a.appendChild(closeIcon)
   
    // Listener en el tab link
    a.addEventListener("click", (e) => {
      if (!e.target.matches(".fa-circle-xmark")) {
        console.log("[v0] Click en mesa tab para cambiar a:", tabId)
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
      <center style="padding:1rem 0">
        <h3>Mesa ${numeroMesa}: <div class="badge bg-warning rounded-circle" id="ventasCount-${tabId}">0</div></h3>
      </center>
      <div id="productos-carrito-${tabId}" style="height:calc(85vh - 220px);overflow-y:auto"></div>
      <div style="padding:1rem 0">
        <div id="total-carrito-${tabId}"><h4>Total: $<span id="total-${tabId}">0.00</span></h4></div>
        <button class="btn btn-primary btn-lg w-100 mb-2" onclick="saleConfirmationModal('${tabId}', ${idMesa})">
          Facturar <i class="fa-solid fa-receipt"></i>
        </button>
      </div>
    </div>`
  content.appendChild(pane)

  // Inicializar carrito en memoria para la mesa
  if (!carts[tabId]) {
    carts[tabId] = { type: "table", tableId: idMesa, tableNumber: numeroMesa, products: [], total: 0 }
    console.log("[v0] Nuevo carrito de mesa creado:", tabId)
  }

  if (switchTo) {
    showTab(a)
    switchToTableCart(tabId, idMesa)
  }

  return tabId
}

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
    const img = imgPath ? `assets/img/${imgPath}` : "assets/img/products/default.jpg"
    const detalleId = p.idDetalleVenta || p.idDetalle

    const div = document.createElement("div")
    div.className = "cart-product"
    if (detalleId) div.setAttribute("data-detalle-id", detalleId)
    div.innerHTML = `
      <div class="row align-items-center">
        <div class="col-auto">
          <img src="${img}" 
               class="product-img" style="width:80px;height:80px;object-fit:cover;border-radius:8px">
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
              <i class="fa-solid fa-trash-can" style="color:#ff0000"></i>
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

async function addProductToTableSale(idMesa, product) {
  try {
    const tableInfo = activeTables[idMesa]
    if (!tableInfo) {
      console.error("[v0] Mesa no inicializada:", idMesa)
      alert("Mesa no inicializada correctamente")
      return
    }

    const userId = await getUserId()

    console.log("[v0] Agregando producto a mesa:", {
      idMesa,
      idVenta: tableInfo.idVenta,
      producto: product.nombre,
    })

    const data = await fetchJson("?pg=sales&action=addProductToTableSale", {
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
      console.log("[v0] ✅ Producto agregado correctamente")
    } else {
      alert("Error: " + data.error)
    }
  } catch (error) {
    console.error("[v0] Error al agregar producto:", error)
    alert("Error al agregar producto a la mesa")
  }
}

async function increaseTableQty(idDetalleVenta) {
  const detalle = document.querySelector(`[data-detalle-id="${idDetalleVenta}"]`)
  if (!detalle) return
  const input = detalle.querySelector("input")
  await updateTableProductQty(idDetalleVenta, toInt(input.value) + 1)
}

async function decreaseTableQty(idDetalleVenta) {
  const detalle = document.querySelector(`[data-detalle-id="${idDetalleVenta}"]`)
  if (!detalle) return
  const input = detalle.querySelector("input")
  const qty = toInt(input.value)
  if (qty > 1) await updateTableProductQty(idDetalleVenta, qty - 1)
}

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
    console.error("[v0] Error al actualizar cantidad:", error)
  }
}

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
    console.error("[v0] Error al eliminar producto:", error)
  }
}

async function reloadTableSale(idMesa) {
  const tableInfo = activeTables[idMesa]
  if (!tableInfo) return

  try {
    console.log("[v0] Recargando venta de mesa:", idMesa)
    const data = await fetchJson(`?pg=sales&action=GetSale&id=${tableInfo.idVenta}`)
    if (data.success) {
      loadTableProducts(`mesa-${idMesa}`, data.data.detalles)
    }
  } catch (error) {
    console.error("[v0] Error al recargar venta:", error)
  }
}

async function completeTableSale(idMesa, metodoPago) {
  try {
    console.log("[v0] Completando venta de mesa:", { idMesa, metodoPago })

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

      console.log("[v0] ✅ Venta completada correctamente")
    } else {
      alert("Error: " + data.error)
    }
  } catch (error) {
    console.error("[v0] Error al completar venta:", error)
    alert("Error al completar la venta")
  }
}

async function closeTableSale(idMesa) {
  if (!confirm("¿Cancelar esta venta? Se perderán todos los productos.")) return

  try {
    console.log("[v0] Cancelando venta de mesa:", idMesa)

    const data = await fetchJson("?pg=sales&action=cancelTableSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idMesa }),
    })
    if (data.success) {
      removeTableTab(`mesa-${idMesa}`, idMesa)
      delete activeTables[idMesa]
      console.log("[v0] ✅ Venta cancelada correctamente")
    }
  } catch (error) {
    console.error("[v0] Error al cancelar venta:", error)
  }
}

// ============================================================================
// GESTIÓN DE TABS
// ============================================================================

/**
 * Cambia al tab activo de forma segura, sincronizando el estado global
 */
function setActiveCart(cartId) {
  if (!cartId) {
    console.error("[v0] setActiveCart: cartId inválido")
    return false
  }
  
  currentCartId = cartId
  console.log("[v0] Tab activo cambiado a:", cartId)
  return true
}

// Helper: Mostrar tab de forma segura con o sin Bootstrap
function showTab(link) {
  try {
    const bs = window.bootstrap
    if (bs && typeof bs.Tab === "function") {
      bs.Tab.getOrCreateInstance(link).show()
      return
    }

    // Fallback manual: activar clases
    const href = link.getAttribute("href")
    const id = href ? href.substring(1) : null

    document.querySelectorAll("#ventasTabs .nav-link.active").forEach((l) => l.classList.remove("active"))
    link.classList.add("active")

    document
      .querySelectorAll("#ventasContent .tab-pane.show, #ventasContent .tab-pane.active")
      .forEach((p) => p.classList.remove("show", "active"))

    if (id) {
      const pane = document.getElementById(id)
      if (pane) pane.classList.add("show", "active")
      setActiveCart(id)
    }
  } catch (error) {
    console.error("[v0] Error en showTab:", error)
  }
}

function switchToCart(cartId) {
  if (!setActiveCart(cartId)) return
  updateCart(cartId)
  console.log("[v0] Switched to cart:", cartId)
}

function switchToTableCart(tabId, idMesa) {
  if (!setActiveCart(tabId)) return

  console.log("[v0] Switched to table cart:", { tabId, idMesa })

  // Solo recargar si la mesa tiene venta activa
  if (activeTables[idMesa] && activeTables[idMesa].idVenta) {
    reloadTableSale(idMesa)
  }
}

function addNewSaleTab() {
  const tabs = getById("ventasTabs")
  const content = getById("ventasContent")
  if (!tabs || !content) return

  // Contar solo nav-items reales (sin contar addTabItem)
  const count = tabs.querySelectorAll(".nav-item").length
  const id = `venta${count}`

  const li = document.createElement("li")
  li.className = "nav-item"
  const a = document.createElement("a")
  a.className = "nav-link"
  a.setAttribute("data-bs-toggle", "tab")
  a.setAttribute("href", `#${id}`)
    a.textContent = `Venta ${count} `
  
    // Crear el botón X como elemento separado
    const closeIcon = document.createElement("i")
    closeIcon.className = "fa-solid fa-circle-xmark fa-xl"
    closeIcon.style.cssText = "color:#ff0000;margin-left:8px;cursor:pointer"
    closeIcon.title = "Eliminar"
  
    // Listener PRIMERO en el X para detener propagación
    closeIcon.addEventListener("click", (e) => {
      console.log("[v0] Click en X para eliminar:", id)
      e.stopPropagation()
      e.preventDefault()
      dropTab(id)
    })
  
    a.appendChild(closeIcon)
  
    // Listener DESPUÉS en el tab link
    a.addEventListener("click", (e) => {
      // Solo ejecutar si NO fue click en el X
      if (!e.target.matches(".fa-circle-xmark")) {
        console.log("[v0] Click en tab para cambiar a:", id)
        switchToCart(id)
      }
    })
  li.appendChild(a)

  const addTabItem = getById("addTabItem")
  if (addTabItem) tabs.insertBefore(li, addTabItem)
  else tabs.appendChild(li)

  const pane = document.createElement("div")
  pane.className = "tab-pane fade"
  pane.id = id
  pane.innerHTML = `
    <div id="carrito-${id}">
      <center style="padding:1rem 0">
        <h3>Ventas: <div class="badge bg-primary rounded-circle" id="ventasCount-${id}">0</div></h3>
      </center>
      <div id="productos-carrito-${id}" style="height:calc(85vh - 220px);overflow-y:auto"></div>
      <div style="padding:1rem 0">
        <div id="total-carrito-${id}"><h4>Total: $<span id="total-${id}">0.00</span></h4></div>
        <button class="btn btn-primary btn-lg w-100 mb-2" onclick="saleConfirmationModal('${id}', null)">
          Procesar Venta <i class="fa-solid fa-cash-register"></i>
        </button>
        <button class="btn btn-secondary btn-lg w-100" onclick="openTableSelectionModal(event)">
          Agregar a Mesa <i class="fa-solid fa-utensils"></i>
        </button>
      </div>
    </div>`
  content.appendChild(pane)

  // Inicializar carrito
  carts[id] = { type: "sale", products: [], total: 0 }
  console.log("[v0] Nuevo carrito creado:", id)
  
  // Activar el tab de forma segura y sincronizar el carrito
  showTab(a)
  switchToCart(id)
}

function dropTab(tabId) {
  const tab = document.querySelector(`#ventasTabs a[href="#${tabId}"]`)
  const containerTab = tab?.parentElement
  const pane = getById(tabId)

    if (!tab || !containerTab || !pane) {
      console.error("[v0] dropTab: elementos no encontrados para", tabId)
      return
    }

    console.log("[v0] dropTab ejecutado para:", tabId)

  if (tab.classList.contains("active")) {
    let nextTab = containerTab.previousElementSibling

    if (!nextTab || nextTab.id === "addTabItem") {
      nextTab = containerTab.nextElementSibling
      if (nextTab && nextTab.id === "addTabItem") {
        nextTab = null
      }
    }

    if (nextTab) {
      const nextTabLink = nextTab.querySelector("a")
      if (nextTabLink) {
        const nextTabId = nextTabLink.getAttribute("href").substring(1)

        currentCartId = nextTabId

        showTab(nextTabLink)

        setTimeout(() => {
          console.log("[v0] Ejecutando switchToCart para:", nextTabId)
          switchToCart(nextTabId)

          delete carts[tabId]

          // Finalmente eliminar del DOM
          containerTab.remove()
          pane.remove()
        }, 100)

        return
      }
    }
  }

  delete carts[tabId]

  containerTab.remove()
  pane.remove()

  console.log("[v0] Tab eliminado:", tabId)
}

function removeTableTab(tabId, idMesa) {
  const tab = document.querySelector(`a[href="#${tabId}"]`)
  const li = tab?.parentElement
  const pane = getById(tabId)
  if (!tab || !li || !pane) {
    console.error("[v0] removeTableTab: elementos no encontrados para", tabId)
    return
  }

  const wasActive = tab.classList.contains("active")

  // Limpiar datos de la mesa activa
  if (activeTables[idMesa]) {
    delete activeTables[idMesa]
    console.log("[v0] Mesa eliminada de activeTables:", idMesa)
  }

  // Si era el tab activo, cambiar a otro antes de eliminar
  if (wasActive) {
    const next = li.previousElementSibling || li.nextElementSibling
    if (next && next.id !== "addTabItem") {
      const nextLink = next.querySelector("a")
      const nextHref = nextLink?.getAttribute("href")
      if (nextLink && nextHref) {
        const nextCartId = nextHref.substring(1)
        showTab(nextLink)
        setActiveCart(nextCartId)
      }
    }
  }

  // Remover elementos DOM
  li.remove()
  pane.remove()
  
  console.log("[v0] Tab de mesa eliminado:", tabId)
}

// ============================================================================
// MODAL DE PAGO Y CONFIRMACIÓN
// ============================================================================

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

function selectPaymentMethod(btn, method) {
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return
  overlay.querySelectorAll(".payment-btn").forEach((b) => b.classList.remove("active"))
  if (btn && btn.classList) btn.classList.add("active")
  overlay.dataset.paymentMethod = method
}

function closeSaleConfirmation(event) {
  const el = getById("saleConfirmationOverlay")
  if (!el) return
  if (!event || event.target.id === "saleConfirmationOverlay") {
    el.classList.remove("active")
  }
}

function confirmSalePayment() {
  const overlay = getById("saleConfirmationOverlay")
  if (!overlay) return

  const metodo = overlay.dataset.paymentMethod || "efectivo"
  const cartId = overlay.dataset.cartId || currentCartId
  const idMesa = overlay.dataset.idMesa
  const cartType = overlay.dataset.cartType

  console.log("[v0] Confirmando pago:", { cartType, cartId, idMesa, metodo })

  if (cartType === "table" && idMesa) {
    completeTableSale(toInt(idMesa), metodo)
  } else {
    const userId = getSessionUserId()
    saleProcess(cartId, userId, metodo)
  }

  overlay.classList.remove("active")
}

function saleProcess(cartId, userId, paymentMethod = "efectivo") {
  if (!cartId || currentCartId !== cartId) cartId = currentCartId

  const cartObj = getCart(cartId)

  if (!cartObj.products || cartObj.products.length === 0) {
    alert("El carrito está vacío")
    return
  }

  console.log("[v0] Procesando venta:", { cartId, productos: cartObj.products.length, total: cartObj.total })

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

        console.log("[v0] ✅ Venta procesada correctamente")
      } else {
        alert("Error: " + data.error)
      }
    })
    .catch((err) => {
      console.error("[v0] Error al procesar venta:", err)
      alert("Error al procesar la venta. Intenta de nuevo.")
    })
}

// ============================================================================
// FUNCIONES AUXILIARES Y MODALES
// ============================================================================

function closeTable(event) {
  const el = getById("tableOverlay")
  if (!el) return
  if (!event || event.target.id === "tableOverlay") {
    el.classList.remove("active")
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO').format(value);
}

const MAX = 99;
const MIN = 1;
let actualProduct = null;
let currentQuantity = '0';

function changeQuantity(idProducto) {
  openCalculator(idProducto);
}

function openCalculator(idProducto) {
  actualProduct = idProducto;
  const cartObj = getCart();
  const existingProduct = cartObj.products.find(p => parseInt(p.idProducto) === parseInt(idProducto));
  console.log("Existing product in cart:", existingProduct);
  currentQuantity = existingProduct ? existingProduct.cantidad.toString() : '0';
  const overlay = getById("calculatorOverlay");
  const display = getById("calculatorDisplay");
  if (overlay) overlay.classList.add('active');
  if (display) display.textContent = currentQuantity;
}

function addNumber(num) {
  const display = getById("calculatorDisplay");
  if (!display) return;
  const digit = String(num).replace(/\D/g, "");
  if (digit.length === 0) return;

  let next = (currentQuantity === "0" || currentQuantity === "") ? digit : (currentQuantity + digit);
  // Limitar a 2 dígitos porque MAX es 99
  if (next.length > 2) next = next.slice(0, 2);

  const val = parseInt(next, 10);
  if (!isNaN(val) && val > MAX) next = String(MAX);

  currentQuantity = next;
  display.textContent = currentQuantity;
}

function deleteLast() {
  const display = getById("calculatorDisplay");
  if (!display) return;
  if (currentQuantity && currentQuantity.length > 1) {
    currentQuantity = currentQuantity.slice(0, -1);
  } else {
    currentQuantity = '0';
  }
  display.textContent = currentQuantity;
}

function clearCalculator() {
  currentQuantity = '0';
  const display = getById("calculatorDisplay");
  if (display) display.textContent = currentQuantity;
}

function closeCalculator(event) {
  const el = getById("calculatorOverlay");
  if (!el) return;
  if (!event || event.target.id === "calculatorOverlay") {
    el.classList.remove("active");
    actualProduct = null;
    currentQuantity = '0';
  }
}

function confirmQuantity() {
  const display = getById("calculatorDisplay");
  let qty = parseInt((display ? display.textContent : currentQuantity), 10);
  if (isNaN(qty)) qty = MIN;
  if (qty < MIN) qty = MIN;
  if (qty > MAX) qty = MAX;

  if (actualProduct !== null) {
    const product = productosCache.find(p => parseInt(p.idProducto) === parseInt(actualProduct));
    if (product) {
      const cartObj = getCart();
      const existingProduct = cartObj.products.find(p => parseInt(p.idProducto) === parseInt(actualProduct));

      if (existingProduct) {
        existingProduct.cantidad = qty;
        existingProduct.precioTotal = qty * existingProduct.precioVenta;
      } else {
        cartObj.products.push({
          idProducto: product.idProducto,
          nombre: product.nombre,
          categoria: product.categoria,
          imagen: product.imagen,
          categoria_imagen: product.categoria_imagen,
          precioVenta: parseFloat(product.precioVenta),
          cantidad: qty,
          precioTotal: qty * parseFloat(product.precioVenta),
        });
      }

      // Actualizar badge de cantidad en la tarjeta del producto si existe
      const qtyBadge = getById(`prod-qty-${actualProduct}`);
      if (qtyBadge) qtyBadge.textContent = String(qty);

      updateCart();
    }
    closeCalculator();
  }
}

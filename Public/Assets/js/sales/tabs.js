// ============================================================================
// TABS.JS - GESTIÓN DE TABS Y NAVEGACIÓN
// ============================================================================
// Este módulo gestiona la creación, eliminación y navegación entre tabs
// Utiliza el estado global definido en Sales.js (carts, currentCartId, etc.)

// ============================================================================
// FUNCIONES DE GESTIÓN DE TABS
// ============================================================================

/**
 * Encuentra el primer número de venta disponible
 * Escanea tabs existentes y retorna el primer número libre desde 2 en adelante
 * (venta1 es estático y no se cuenta)
 */
function getNextAvailableTabNumber() {
  const tabs = getById("ventasTabs")
  if (!tabs) return 2
  
  // Obtener todos los tabs existentes y extraer sus números
  const existingNumbers = new Set()
  
  tabs.querySelectorAll('.nav-item a[href^="#venta"]').forEach(link => {
    const href = link.getAttribute('href')
    const match = href.match(/^#venta(\d+)$/)
    if (match) {
      existingNumbers.add(parseInt(match[1], 10))
    }
  })
  
  console.log("[TABS] Números de venta existentes:", Array.from(existingNumbers).sort((a, b) => a - b))
  
  // Encontrar el primer número disponible desde 2 en adelante
  let number = 2
  while (existingNumbers.has(number)) {
    number++
  }
  
  console.log("[TABS] Próximo número disponible:", number)
  return number
}

/**
 * Cambia al tab activo de forma segura, sincronizando el estado global
 */
function setActiveCart(cartId) {
  if (!cartId) {
    console.error("[TABS] setActiveCart: cartId inválido")
    return false
  }
  
  currentCartId = cartId
  console.log("[TABS] Tab activo cambiado a:", cartId)
  return true
}

/**
 * Helper: Mostrar tab de forma segura con o sin Bootstrap
 */
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
    console.error("[TABS] Error en showTab:", error)
  }
}

/**
 * Cambia al carrito especificado
 */
function switchToCart(cartId) {
  if (!setActiveCart(cartId)) return
  updateCart(cartId)
  console.log("[TABS] Switched to cart:", cartId)
}

/**
 * Cambia al carrito de una mesa específica
 */
function switchToTableCart(tabId, idMesa) {
  if (!setActiveCart(tabId)) return

  console.log("[TABS] Switched to table cart:", { tabId, idMesa })

  // Solo recargar si la mesa tiene venta activa
  if (activeTables[idMesa] && activeTables[idMesa].idVenta) {
    reloadTableSale(idMesa)
  }
}

/**
 * Añade un nuevo tab de venta
 * PASO 1: Crear venta pendiente en BD inmediatamente
 */
async function addNewSaleTab() {
  // Obtener el siguiente número disponible
  const number = getNextAvailableTabNumber()
  const id = `venta${number}`
  
  console.log(`[TABS] addNewSaleTab: creando nuevo tab ${id}`)

  // PASO 1: Crear venta pendiente en BD
  try {
    const userId = getSessionUserId()
    const createResponse = await fetchJson("?pg=sales&action=CreateSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        idUsuario: userId,
        productos: [], // Sin productos inicialmente
      }),
    })

    if (!createResponse.success) {
      throw new Error("No se pudo crear la venta: " + createResponse.error)
    }

    const idVenta = createResponse.data?.idVenta
    if (!idVenta) {
      throw new Error("No se obtuvo idVenta del servidor")
    }

    console.log(`[TABS] ✅ Venta pendiente creada. idVenta: ${idVenta}`)

    // Usar función genérica para crear el tab
    createSaleTab(idVenta, id, true)

  } catch (error) {
    console.error("[TABS] Error al crear venta pendiente:", error)
    alert("Error al crear la venta: " + error.message)
  }
}

/**
 * Crea un tab de venta (genérico - usado por addNewSaleTab y loadActiveSales)
 * @param {number} idVenta - ID de la venta en BD
 * @param {string} tabId - ID del tab (ej: venta2, venta3)
 * @param {boolean} switchTo - Si true, activa el tab inmediatamente
 */
function createSaleTab(idVenta, tabId, switchTo = true) {
  const tabs = getById("ventasTabs")
  const content = getById("ventasContent")
  if (!tabs || !content) {
    console.error("[TABS] createSaleTab: elementos no encontrados")
    return
  }

  // Extraer número de venta del tabId (ej: "venta2" → 2)
  const ventaNumber = tabId.replace("venta", "")

  console.log(`[TABS] createSaleTab: creando ${tabId} para idVenta ${idVenta}`)

  // Verificar si el tab ya existe
  if (getById(tabId)) {
    console.warn(`[TABS] El tab ${tabId} ya existe`)
    if (switchTo) {
      const tab = document.querySelector(`a[href="#${tabId}"]`)
      if (tab) showTab(tab)
    }
    return
  }

  // Crear el elemento del tab
  const li = document.createElement("li")
  li.className = "nav-item"
  
  const a = document.createElement("a")
  a.className = "nav-link"
  a.setAttribute("data-bs-toggle", "tab")
  a.setAttribute("href", `#${tabId}`)
  a.setAttribute("id", idVenta) // Guardar idVenta en el tab
  a.textContent = `Venta ${ventaNumber} `
  
  // Crear el botón X
  const closeIcon = document.createElement("i")
  closeIcon.className = "fa-solid fa-circle-xmark fa-xl icon-close"
  closeIcon.title = "Eliminar"

  closeIcon.addEventListener("click", (e) => {
    e.stopPropagation()
    e.preventDefault()
    dropTab(tabId,1)
  })

  a.appendChild(closeIcon)

  a.addEventListener("click", (e) => {
    if (!e.target.matches(".fa-circle-xmark")) {
      switchToCart(tabId)
    }
  })
  
  li.appendChild(a)

  // Insertar antes del botón "+" de agregar nueva venta
  const addTabItem = getById("addTabItem")
  if (addTabItem) tabs.insertBefore(li, addTabItem)
  else tabs.appendChild(li)

  // Crear el panel de contenido
  const pane = document.createElement("div")
  pane.className = "tab-pane fade"
  pane.id = tabId
  pane.innerHTML = `
    <div id="carrito-${tabId}">
      <center style="padding:1rem 0">
        <h3>Ventas: <div class="badge bg-primary rounded-circle" id="ventasCount-${tabId}">0</div></h3>
      </center>
      <div id="productos-carrito-${tabId}" style="height:calc(85vh - 280px);overflow-y:auto;overflow-x:hidden;"></div>
      <div style="padding:1rem 0">
        <div id="total-carrito-${tabId}">
          <center><h1>Total: <strong>$<span id="total-${tabId}">0.00</span></strong></h1></center>
        </div>
        <button class="btn btn-primary btn-lg w-100 mb-2" onclick="saleConfirmationModal('${tabId}', null)">
          Procesar Venta <i class="fa-solid fa-cash-register"></i>
        </button>
        <button class="btn btn-secondary btn-lg w-100" onclick="openTableSelectionModal(event)">
          Agregar a Mesa <i class="fa-solid fa-utensils"></i>
        </button>
        <button class="btn btn-outline-danger btn-lg w-100 mt-2" onclick="clearCart('${tabId}')">
          Limpiar carrito <i class="fa-solid fa-trash-can"></i>
        </button>
      </div>
    </div>`
  content.appendChild(pane)

  // Inicializar carrito en memoria
  if (!carts[tabId]) {
    carts[tabId] = { type: "sale", products: [], total: 0, idVenta: idVenta }
    console.log("[TABS] ✅ Carrito inicializado:", tabId)
  }

  // Activar el tab si se solicita
  if (switchTo) {
    showTab(a)
    switchToCart(tabId)
  }

  return tabId
}

/**
 * Elimina un tab de venta
 * Elimina la venta de BD antes de eliminar el tab del DOM
 */
async function dropTab(tabId,Confirmation = 0) {
    // Proteger el tab fijo venta1
    if (tabId === "venta1") {
      return
    }
  
  const tab = document.querySelector(`#ventasTabs a[href="#${tabId}"]`)
  const containerTab = tab?.parentElement
  const pane = getById(tabId)

  if (!tab || !containerTab || !pane) {
    console.error("[TABS] dropTab: elementos no encontrados para", tabId)
    return
  }

  console.log("[TABS] dropTab ejecutado para:", tabId)

  // Obtener idVenta del tab
  const idVenta = toInt(tab.getAttribute("id"))
  
  if (!idVenta) {
    console.warn("[TABS] No se encontró idVenta, eliminando solo del DOM")
    performTabRemoval(tab, containerTab, pane, tabId)
    return
  }

  // Confirmar eliminación
  if (Confirmation === 1) {
    const confirmed = await Swal.fire({
      title: '¿Eliminar venta?',
      text: 'Esto eliminará la venta y todos sus productos',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    })

    if (!confirmed.isConfirmed) {
      console.log("[TABS] Eliminación cancelada por el usuario")
      return
    }
  }

  // Eliminar de BD
  try {
    console.log("[TABS] Eliminando venta de BD:", idVenta)
    const response = await fetchJson("?pg=sales&action=CancelSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idVenta: idVenta }),
    })

    if (!response.success) {
      throw new Error(response.error || "Error desconocido")
    }

    console.log("[TABS] ✅ Venta eliminada de BD:", idVenta)
    
    // Después de eliminar de BD, eliminar del DOM
    performTabRemoval(tab, containerTab, pane, tabId)
  } catch (error) {
    console.error("[TABS] Error al eliminar venta de BD:", error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo eliminar la venta: ' + error.message
    })
  }
}

/**
 * Realiza la eliminación del tab del DOM
 */
function performTabRemoval(tab, containerTab, pane, tabId) {

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
          console.log("[TABS] Ejecutando switchToCart para:", nextTabId)
          switchToCart(nextTabId)

          delete carts[tabId]

          // Finalmente eliminar del DOM
          containerTab.remove()
          pane.remove()
          
          console.log(`[TABS] ✅ Tab eliminado: ${tabId}`)
        }, 100)

        return
      }
    }
  }

  delete carts[tabId]

  containerTab.remove()
  pane.remove()

  console.log(`[TABS] ✅ Tab eliminado: ${tabId}`)
  
  // Mostrar tabs restantes para debugging
  const remaining = Array.from(document.querySelectorAll('#ventasTabs .tab-item a[href^="#venta"]'))
    .map(a => a.getAttribute('href').substring(1))
  console.log("[TABS] Tabs restantes:", remaining)
}

/**
 * Elimina un tab de mesa
 */
function removeTableTab(tabId, idMesa) {
  const tab = document.querySelector(`a[href="#${tabId}"]`)
  const li = tab?.parentElement
  const pane = getById(tabId)
  if (!tab || !li || !pane) {
    console.error("[TABS] removeTableTab: elementos no encontrados para", tabId)
    return
  }

  const wasActive = tab.classList.contains("active")

  // Limpiar datos de la mesa activa
  if (activeTables[idMesa]) {
    delete activeTables[idMesa]
    console.log("[TABS] Mesa eliminada de activeTables:", idMesa)
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
  
  console.log("[TABS] Tab de mesa eliminado:", tabId)
}
// ============================================================================
// SORTABLE: Drag & Drop de Tabs
// ============================================================================

/**
 * Inicializa Sortable para permitir arrastrar y organizar tabs
 * Similar a los tabs de un navegador
 */
function initTabsDragAndDrop() {
  const tabsContainer = getById("ventasTabs")
  if (!tabsContainer || !window.Sortable) {
    console.warn("[TABS] SortableJS no está disponible o contenedor no encontrado")
    return
  }

  new Sortable(tabsContainer, {
    // Opciones de Sortable
    animation: 150,                           // Duración de la animación en ms
    handle: "a.nav-link",                    // Solo se puede arrastrar por el link
    ghostClass: "sortable-ghost",            // Clase cuando está siendo arrastrado
    dragClass: "sortable-drag",              // Clase mientras se arrastra
    filter: "#addTabItem, .ms-auto",         // NO permitir arrastrar el botón + ni el reporte
    invertSwap: true,
    swapThreshold: 0.65,
    
    // Callbacks
    onEnd: function(evt) {
      console.log("[TABS] 📍 Tab reordenado:")
      console.log("[TABS] - De posición:", evt.oldIndex)
      console.log("[TABS] - A posición:", evt.newIndex)
      console.log("[TABS] - Elemento:", evt.item.querySelector("a")?.textContent.trim())
      
      // Mostrar orden actual de tabs
      const tabs = Array.from(tabsContainer.querySelectorAll(".tab-item a[href^='#venta']"))
        .map(a => a.getAttribute("href").substring(1))
      console.log("[TABS] Orden actual:", tabs)
    },
    
    onMove: function(evt) {
      // Prevenir mover sobre addTabItem
      if (evt.related.id === "addTabItem") {
        return false
      }
    }
  })
  
  console.log("[TABS] ✅ Drag & Drop de tabs inicializado")
}


function reinitTabsDragAndDrop() {
  // SortableJS detecta automáticamente nuevos elementos
  // Esta función existe solo por si se necesita reinicios manuales en el futuro
  console.log("[TABS] Drag & Drop actualizado")
}
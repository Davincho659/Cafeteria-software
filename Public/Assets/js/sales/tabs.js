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
 */
function addNewSaleTab() {
  const tabs = getById("ventasTabs")
  const content = getById("ventasContent")
  if (!tabs || !content) {
    console.error("[TABS] No se encontraron elementos ventasTabs o ventasContent")
    return
  }

  // Obtener el siguiente número disponible de forma inteligente
  const number = getNextAvailableTabNumber()
  const id = `venta${number}`
  
  console.log(`[TABS] Creando nuevo tab: ${id}`)

  const li = document.createElement("li")
  li.className = "nav-item"
  const a = document.createElement("a")
  a.className = "nav-link"
  a.setAttribute("data-bs-toggle", "tab")
  a.setAttribute("href", `#${id}`)
  a.textContent = `Venta ${number} `
  
    // Crear el botón X como elemento separado
    const closeIcon = document.createElement("i")
    closeIcon.className = "fa-solid fa-circle-xmark fa-xl icon-close"
    closeIcon.title = "Eliminar"
  
    // Listener PRIMERO en el X para detener propagación
    closeIcon.addEventListener("click", (e) => {
      e.stopPropagation()
      e.preventDefault()
      dropTab(id)
    })
  
    a.appendChild(closeIcon)
  
    // Listener DESPUÉS en el tab link
    a.addEventListener("click", (e) => {
      // Solo ejecutar si NO fue click en el X
      if (!e.target.matches(".fa-circle-xmark")) {
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
      <div id="productos-carrito-${id}" style="height:calc(85vh - 280px);overflow-y:auto"></div>
      <div style="padding:1rem 0">
        <div id="total-carrito-${id}">
            <center><h1>Total: $<span id="total-${id}">0.00</span></h1></center>
        </div>
        <button class="btn btn-primary btn-lg w-100 mb-2" onclick="saleConfirmationModal('${id}', null)">
          Procesar Venta <i class="fa-solid fa-cash-register"></i>
        </button>
        <button class="btn btn-secondary btn-lg w-100" onclick="openTableSelectionModal(event)">
          Agregar a Mesa <i class="fa-solid fa-utensils"></i>
        </button>
        <button class="btn btn-outline-danger btn-lg w-100 mt-2" onclick="clearCart('${id}')">
          Limpiar carrito <i class="fa-solid fa-trash-can"></i>
        </button>
      </div>
    </div>`
  content.appendChild(pane)

  // Inicializar carrito
  carts[id] = { type: "sale", products: [], total: 0 }
  
  // Activar el tab de forma segura y sincronizar el carrito
  showTab(a)
  switchToCart(id)
}

/**
 * Elimina un tab de venta
 */
function dropTab(tabId) {
  const tab = document.querySelector(`#ventasTabs a[href="#${tabId}"]`)
  const containerTab = tab?.parentElement
  const pane = getById(tabId)

    if (!tab || !containerTab || !pane) {
      console.error("[TABS] dropTab: elementos no encontrados para", tabId)
      return
    }

    console.log("[TABS] dropTab ejecutado para:", tabId)

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
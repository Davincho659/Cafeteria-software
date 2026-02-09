// ============================================================================
// SALES.JS - ENTRY POINT + ESTADO GLOBAL + HELPERS
// ============================================================================

let categoriasCache = []
let productosCache = []
let currentCartId = "venta1"
let ventaCounter = 1 // Contador global para numeración de ventas (siempre incrementa)
const carts = { venta1: { type: "sale", products: [], total: 0 } }
const activeTables = {} // Mesas con ventas activas sincronizadas con BD
const activeSales = {} // Ventas locales activas (no mesa) sincronizadas con BD

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

  // Filtro de búsqueda en tiempo real
  const searchInput = getById("search")
  if (searchInput) {
    searchInput.addEventListener("input", (e) => filterProductsBySearch(e.target.value))
  }
})

async function startSystem() {
  initProducts()
 
  // Primero recuperar ventas pendientes (asignará al tab fijo si existe)
  await loadActiveSales()
   await loadActiveTables()
  // Después inicializar el tab fijo SOLO si no tiene venta asignada
  await initializeDefaultTab()
  
  // Inicializar drag & drop de tabs después de que el DOM esté listo
  setTimeout(() => {
    initTabsDragAndDrop()
  }, 100)
  
  console.log("✅ [SALES] Sistema POS iniciado correctamente")
}

/**
 * Inicializa el tab fijo "Venta 1" creando una venta en BD si no existe
 * Este tab es fijo en la vista HTML, así que debe inicializarse al cargar
 */
async function initializeDefaultTab() {
  const defaultTabLink = document.querySelector('a[href="#venta1"]')
  if (!defaultTabLink) {
    console.warn("[SALES] Tab venta1 no encontrado en el DOM")
    return
  }

  // Revisar si ya tiene idVenta asignado
  const existingIdVenta = defaultTabLink.getAttribute("id")
  
  if (existingIdVenta && existingIdVenta !== "") {
    console.log("[SALES] Tab venta1 ya tiene idVenta:", existingIdVenta)
    return
  }

  // Crear nueva venta en BD para el tab fijo
  console.log("[SALES] Inicializando tab fijo venta1...")
  
  try {
    const response = await fetchJson("?pg=sales&action=CreateSale", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ 
        productos: [] // Sin productos, solo crear venta pendiente
      }),
    })

    if (!response.success) {
      throw new Error(response.error || "Error al crear venta")
    }

    const idVenta = response.data?.idVenta
    if (!idVenta) {
      throw new Error("No se recibió idVenta en la respuesta")
    }

    // Guardar idVenta en el tab
    defaultTabLink.setAttribute("id", idVenta)
    
    // Guardar idVenta en el carrito en memoria
    carts["venta1"].idVenta = toInt(idVenta)

    console.log("[SALES] ✅ Tab venta1 inicializado con idVenta:", idVenta)
  } catch (error) {
    console.error("[SALES] Error inicializando tab venta1:", error)
    alert("Error al inicializar la venta: " + error.message)
  }
}

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

// ============================================================================
// cargar ventas activas desde la base de datos
// ============================================================================

/**
 * Paso 3: Recupera ventas pendientes y sus detalles al cargar la página
 * Para cada venta:
 * - Crea un tab dinámico
 * - Carga los productos/detalles
 * - Restaura el estado del carrito en memoria
 */
async function loadActiveSales() {
  try {
    console.log("[SALES] Cargando ventas activas...")
    const data = await fetchJson('?pg=sales&action=LoadActiveSales')

    if (!data.success || !data.data || data.data.length === 0) {
      console.log("[SALES] No hay ventas pendientes para recuperar")
      return
    }

    console.log(`[SALES] ${data.data.length} venta(s) pendiente(s) encontrada(s)`)
    // Verificar si existe el tab fijo "venta1" sin idVenta asignado
    const defaultTabLink = document.querySelector('a[href="#venta1"]')
    const hasDefaultTab = defaultTabLink !== null
    const defaultTabIdVenta = defaultTabLink ? defaultTabLink.getAttribute("id") : null
    const defaultTabNeedsAssignment = hasDefaultTab && (!defaultTabIdVenta || defaultTabIdVenta === "")
    
    let ventasToProcess = data.data
    
    // Si el tab fijo existe y necesita asignación, usar la primera venta para él
    if (defaultTabNeedsAssignment && ventasToProcess.length > 0) {
      const firstVenta = ventasToProcess[0]
      const idVenta = firstVenta.idVenta
      
      console.log(`[SALES] Asignando venta ${idVenta} al tab fijo venta1`)
      
      // Asignar idVenta al tab fijo
      defaultTabLink.setAttribute("id", idVenta)
      
      // Guardar en activeSales
      activeSales[idVenta] = firstVenta
      
      // Cargar detalles de esta venta para el tab fijo
      try {
        const saleDetailsData = await fetchJson(`?pg=sales&action=GetSale&id=${idVenta}`)
        
        if (saleDetailsData.success && saleDetailsData.data?.detalles) {
          const cartObj = carts["venta1"]
          if (cartObj) {
            // Restaurar idVenta en el carrito
            cartObj.idVenta = idVenta
            
            // Cargar productos en el carrito
            saleDetailsData.data.detalles.forEach((detalle) => {
              cartObj.products.push({
                idProducto: detalle.idProducto,
                nombre: detalle.producto_nombre || "Producto",
                imagen: detalle.producto_imagen || null,
                precioVenta: toFloat(detalle.precioUnitario),
                cantidad: toInt(detalle.cantidad),
                precioTotal: toFloat(detalle.subTotal),
                idDetalleVenta: detalle.idDetalleVenta,
                isManualAmount: detalle.idProducto === null,
              })
            })
            
            // Actualizar UI del carrito
            updateCart("venta1")
            console.log(`[SALES] ✅ Tab fijo venta1 recuperado con venta ${idVenta}`)
          }
        }
      } catch (error) {
        console.error(`[SALES] Error cargando detalles para tab fijo:`, error)
      }
      
      // Remover esta venta de la lista a procesar
      ventasToProcess = ventasToProcess.slice(1)
    }
    

    // Para cada venta pendiente
    for (const venta of ventasToProcess) {
      const idVenta = venta.idVenta
      activeSales[idVenta] = venta

      // Crear tab para esta venta
      ventaCounter++
      const tabId = `venta${ventaCounter}`
      
      console.log(`[SALES] Creando tab para venta ${idVenta}: ${tabId}`)
      createSaleTab(idVenta, tabId, false) // false = no switchear, solo crear

      // Cargar detalles de la venta
      try {
        const saleDetailsData = await fetchJson(`?pg=sales&action=GetSale&id=${idVenta}`)
        
        if (saleDetailsData.success && saleDetailsData.data?.detalles) {
          const cartObj = carts[tabId]
          if (!cartObj) {
            console.warn(`[SALES] Carrito no encontrado para ${tabId}`)
            continue
          }

          // Restaurar idVenta en el carrito
          cartObj.idVenta = idVenta

          // Cargar productos en el carrito
          saleDetailsData.data.detalles.forEach((detalle) => {
            cartObj.products.push({
              idProducto: detalle.idProducto,
              nombre: detalle.producto_nombre || "Producto",
              imagen: detalle.producto_imagen || null,
              precioVenta: toFloat(detalle.precioUnitario),
              cantidad: toInt(detalle.cantidad),
              precioTotal: toFloat(detalle.subTotal),
              idDetalleVenta: detalle.idDetalleVenta, // ← Importante para poder eliminar
              isManualAmount: detalle.idProducto === null,
            })
          })

          // Actualizar UI del carrito
          updateCart(tabId)
          console.log(`[SALES] ✅ Venta ${idVenta} recuperada con ${saleDetailsData.data.detalles.length} producto(s)`)
        }
      } catch (error) {
        console.error(`[SALES] Error cargando detalles de venta ${idVenta}:`, error)
      }
    }

    console.log(`[SALES] ✅ Todas las ventas activas cargadas correctamente`)
  } catch (error) {
    console.error("[SALES] Error en loadActiveSales:", error)
  }
}
// ============================================================================
// OVERLAY DE REPORTE DIARIO - AUTÓNOMO Y ROBUSTO
// ============================================================================

function openDailyReportModal() {
  const overlay = getById('dailyReportOverlay');
  const contentContainer = getById('dailyReportContent');
  
  if (!overlay || !contentContainer) {
    console.error('[REPORTE] Elementos del overlay no encontrados');
    return;
  }
  
  // Verificar si ya está cargado
  if (contentContainer.dataset.loaded === 'true') {
    console.log('[REPORTE] Contenido ya cargado, mostrando modal...');
    overlay.classList.add('active');
    
    // Refrescar datos si la función existe
    if (typeof window.cargarReporte === 'function') {
      console.log('[REPORTE] Refrescando datos...');
      setTimeout(() => window.cargarReporte(), 100);
    }
    return;
  }
  
  // Primera carga - limpiar y preparar
  contentContainer.innerHTML = '';
  contentContainer.dataset.loaded = 'false';
  
  // Mostrar loader
  contentContainer.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border text-info" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-3 text-muted">Cargando reporte...</p>
    </div>`;
  
  // Abrir overlay
  overlay.classList.add('active');
  
  console.log('[REPORTE] Iniciando carga del reporte...');
  
  // Cargar contenido del reporte con timeout para evitar bloqueos
  const fetchTimeout = setTimeout(() => {
    contentContainer.innerHTML = `
      <div class="alert alert-warning" role="alert">
        <i class="fa-solid fa-exclamation-triangle"></i>
        El reporte está tardando demasiado en cargar. Por favor, intente nuevamente.
      </div>`;
  }, 10000); // 10 segundos timeout
  
  fetch('?pg=reports&action=Daily&ajax=1', {
    method: 'GET',
    headers: {
      'Cache-Control': 'no-cache',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => {
      clearTimeout(fetchTimeout);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      console.log('[REPORTE] Respuesta recibida, procesando HTML...');
      return response.text();
    })
    .then(html => {
      console.log('[REPORTE] HTML recibido, longitud:', html.length);
      
      // Verificar que el HTML no esté vacío
      if (!html || html.trim().length === 0) {
        throw new Error('Respuesta vacía del servidor');
      }
      
      // Inyectar HTML
      contentContainer.innerHTML = html;
      contentContainer.dataset.loaded = 'true';
      
      console.log('[REPORTE] HTML inyectado, procesando scripts...');
      
      // Procesar scripts de forma segura
      setTimeout(() => {
        initializeReportScripts(contentContainer);
      }, 100);
    })
    .catch(error => {
      clearTimeout(fetchTimeout);
      console.error('[REPORTE] Error cargando reporte:', error);
      
      contentContainer.innerHTML = `
        <div class="alert alert-danger" role="alert">
          <h5><i class="fa-solid fa-exclamation-triangle"></i> Error al cargar el reporte</h5>
          <p class="mb-2"><strong>Detalles:</strong> ${error.message}</p>
          <button class="btn btn-sm btn-primary" onclick="reloadDailyReport()">
            <i class="fa-solid fa-rotate-right"></i> Reintentar
          </button>
        </div>`;
    });
}

/**
 * Función para forzar recarga del reporte
 */
function reloadDailyReport() {
  const contentContainer = getById('dailyReportContent');
  if (contentContainer) {
    contentContainer.dataset.loaded = 'false';
    contentContainer.innerHTML = '';
  }
  openDailyReportModal();
}

/**
 * Inicializa los scripts del reporte de forma aislada y segura
 */
function initializeReportScripts(container) {
  
  try {
    // PRIMERO: Ejecutar scripts inline (donde se define window.cargarReporte)
    const inlineScripts = container.querySelectorAll('script:not([src])');
    
    inlineScripts.forEach((oldScript, index) => {
      try {
        console.log(`[REPORTE] Ejecutando script inline ${index + 1}...`);
        // Evaluar directamente en el contexto global
        eval(oldScript.textContent);
      } catch (e) {
        console.error(`[REPORTE] Error ejecutando script inline ${index + 1}:`, e);
      }
    });
    
    // Pequeña espera para que los scripts inline terminen de ejecutarse
    setTimeout(() => {
      
      // SEGUNDO: Cargar scripts externos (si hay)
      const externalScripts = container.querySelectorAll('script[src]');
      let scriptsLoaded = 0;
      const totalScripts = externalScripts.length;
      
      if (totalScripts === 0) {
        console.log('[REPORTE] No hay scripts externos, inicializando datos...');
        initializeReportData();
        return;
      }
      
      externalScripts.forEach((oldScript) => {
        const src = oldScript.getAttribute('src');
        
        // Verificar si ya está cargado
        const existingScript = document.querySelector(`script[src="${src}"]`);
        
        if (existingScript && existingScript !== oldScript) {
          console.log('[REPORTE] Script ya cargado:', src);
          scriptsLoaded++;
          
          if (scriptsLoaded === totalScripts) {
            initializeReportData();
          }
          return;
        }
        
        // Cargar script nuevo
        const newScript = document.createElement('script');
        newScript.src = src;
        newScript.async = false;
        
        newScript.onload = () => {
          console.log('[REPORTE] Script cargado exitosamente:', src);
          scriptsLoaded++;
          
          if (scriptsLoaded === totalScripts) {
            console.log('[REPORTE] Todos los scripts externos cargados');
            initializeReportData();
          }
        };
        
        newScript.onerror = () => {
          console.error('[REPORTE] Error cargando script:', src);
          scriptsLoaded++;
          
          if (scriptsLoaded === totalScripts) {
            initializeReportData();
          }
        };
        
        document.head.appendChild(newScript);
      });
    }, 150); // Esperar 150ms para que los scripts inline terminen
    
  } catch (error) {
    setTimeout(initializeReportData, 300);
  }
}

/**
 * Inicializa los datos del reporte (llamar a cargarReporte si existe)
 */
function initializeReportData() {
  
  // Intentar varias veces con delays crecientes
  let attempts = 0;
  const maxAttempts = 5;
  
  const tryInitialize = () => {
    attempts++;
    console.log(`[REPORTE] Intento ${attempts}/${maxAttempts}...`);
    
    try {
      // Verificar si la función cargarReporte existe en el scope global
      if (typeof window.cargarReporte === 'function') {
        console.log('[REPORTE] ✅ window.cargarReporte encontrada, ejecutando...');
        window.cargarReporte();
        return true;
      } else {
        console.warn(`[REPORTE] ❌ window.cargarReporte no encontrada (intento ${attempts})`);
        
        // Si llegamos al último intento, intentar ejecutar el formulario manualmente
        if (attempts >= maxAttempts) {
          console.log('[REPORTE] Intentando ejecutar formulario manualmente como fallback...');
          const form = document.getElementById('filtrosReporte');
          if (form) {
            console.log('[REPORTE] Formulario encontrado, disparando submit...');
            const event = new Event('submit', { cancelable: true, bubbles: true });
            form.dispatchEvent(event);
          } else {
            console.error('[REPORTE] Formulario no encontrado');
          }
        } else {
          // Reintentar después de un delay
          setTimeout(tryInitialize, 200 * attempts);
        }
        return false;
      }
    } catch (error) {
      console.error('[REPORTE] Error inicializando datos:', error);
      if (attempts < maxAttempts) {
        setTimeout(tryInitialize, 200 * attempts);
      }
      return false;
    }
  };
  
  // Primera ejecución inmediata
  tryInitialize();
}

function closeDailyReport(event) {
  const el = getById('dailyReportOverlay');
  if (!el) return;
  
  if (!event || event.target.id === 'dailyReportOverlay') {
    el.classList.remove('active');
    
    // NO limpiar el contenido - mantenerlo cargado para próxima apertura
    console.log('[REPORTE] Modal cerrado, contenido preservado');
  }
}

// ============================================================================
// LIMPIEZA AUTOMÁTICA DE VENTAS VACÍAS AL SALIR
// ============================================================================

window.addEventListener('beforeunload', function() {
  // Verificar si venta1 existe, tiene un idVenta en BD y está vacía
  const cart1 = carts['venta1'];
  
  if (cart1 && cart1.idVenta && cart1.products.length === 0) {
    console.log('[CLEANUP] Eliminando venta1 vacía antes de salir:', cart1.idVenta);
    
    // Usar sendBeacon con JSON en blob para mantener el formato esperado por el backend
    const payload = JSON.stringify({ idVenta: cart1.idVenta });
    const blob = new Blob([payload], { type: 'application/json' });
    
    navigator.sendBeacon('?pg=sales&action=CancelSale', blob);
  }
});


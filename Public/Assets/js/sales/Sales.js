// ============================================================================
// SALES.JS - ENTRY POINT + ESTADO GLOBAL + HELPERS
// ============================================================================

let categoriasCache = []
let productosCache = []
let currentCartId = "venta1"
let ventaCounter = 1 // Contador global para numeración de ventas (siempre incrementa)
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

  // Filtro de búsqueda en tiempo real
  const searchInput = getById("search")
  if (searchInput) {
    searchInput.addEventListener("input", (e) => filterProductsBySearch(e.target.value))
  }
})

function startSystem() {
  initProducts()
  loadActiveTables()
  
  // Inicializar drag & drop de tabs después de que el DOM esté listo
  setTimeout(() => {
    initTabsDragAndDrop()
  }, 100)
  
  console.log("✅ [SALES] Sistema POS iniciado correctamente")
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


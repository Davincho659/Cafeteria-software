/* SalesB.js - Sistema de Ventas y Gestión de Mesas [UPDATED]
   - Múltiples pestañas de ventas con carritos separados en memoria
   - Sistema de mesas con transferencia de productos y persistencia en BD
   - Mantiene lista de productos/categorías compartida
   - Cada pestaña (venta o mesa) tiene su propio carrito con IDs únicos
*/

// Cache de datos
let categoriasCache = [];
let productosCache = [];
let mesasCache = [];

// Gestión de múltiples carritos (ventas + mesas)
let currentCartId = 'venta1';
let carts = {
  'venta1': { type: 'sale', products: [], total: 0 }
};

// Cache de mesas con estado temporal (sincroniza con BD)
let tables = {};

// Flag para evitar transferencias simultáneas
let isTransferring = false;

document.addEventListener("DOMContentLoaded", () => {
  startSystem();
  // vincular botón para crear nuevas pestañas
  const nuevaBtn = document.getElementById('nuevaVenta');
  if (nuevaBtn) nuevaBtn.addEventListener('click', addTabs);
  // asegurar que el link inicial cambie el carrito activo
  const initialTab = document.querySelector('#ventasTabs .nav-link');
  if (initialTab) initialTab.addEventListener('click', () => switchToCart('venta1'));
});

function startSystem() {
  loadCategories();
  loadProducts();
  loadTables(); // Cargar mesas al iniciar
}

function loadCategories() {
  fetch('index.php?pg=sales&action=getCategories')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        categoriasCache = data.data;
        showCategories(data.data);
      } else {
        console.error('Error al cargar categorías:', data.error);
      }
    })
    .catch(error => {
      console.error('Error al cargar categorías:', error);
    });
}

function showCategories(categories) {
  const container = document.getElementById("categoriasNav");
  if (!container) return;

  // Crear botón "Todos los Productos" al inicio
  const allButton = document.createElement("button");
  allButton.className = "categoria-item active";
  allButton.setAttribute("id", "cat-all");
  allButton.innerHTML = `
    <img src="assets/img/categories/default.png" class="categoria-icon" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
    <span class="categoria-nombre">Todos</span>`;
  allButton.addEventListener("click", function () {
    container.querySelectorAll(".categoria-item").forEach((btn) => btn.classList.remove("active"));
    allButton.classList.add("active");
    loadProducts(null);
  });
  container.appendChild(allButton);

  // Crear botones de categorías
  categories.forEach(category => {
    const catImg = category.imagen ? `assets/img/${category.imagen}` : 'assets/img/categories/default.png';
    const button = document.createElement("button");
    button.className = "categoria-item";
    button.setAttribute("id", `cat-${category.idCategoria}`);

    button.innerHTML = `
      <img src="${catImg}" class="categoria-icon" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
      <span class="categoria-nombre">${category.nombre}</span>`;

    button.addEventListener("click", function () {
      container.querySelectorAll(".categoria-item").forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");
      loadProducts(category.idCategoria);
    });

    container.appendChild(button);
  });
}

function loadProducts(idCategoria = null) {
  let url = "index.php?pg=sales&action=getProducts";
  if (idCategoria) url += `&idCategory=${idCategoria}`;

  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        productosCache = data.data.map(product => ({
          ...product,
          cantidad: 1,
          precioVenta: parseFloat(product.precioVenta)
        }));
        showProducts(productosCache);
      } else {
        console.error('Error al cargar productos:', data.error);
      }
    })
    .catch(error => console.error('Error al cargar productos:', error));
}

/**
 * Mostrar productos en la grilla
 */
function showProducts(products) {
  const container = document.getElementById("productosContainer");
  if (!container) return;

  container.innerHTML = '';

  products.forEach(product => {
    const productImg = product.imagen ? `assets/img/products/${product.imagen}` : 'assets/img/products/default.png';
    const card = document.createElement("div");
    card.className = "col-md-3 col-sm-6 mb-3 product-card";

    card.innerHTML = `
      <div class="card h-100 shadow-sm cursor-pointer" style="border: none; border-radius: 8px; overflow: hidden;">
        <img src="${productImg}" class="card-img-top" style="height: 150px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title text-truncate">${product.nombre}</h5>
          <p class="card-text text-muted text-truncate" style="font-size: 0.85rem;">${product.descripcion || 'Sin descripción'}</p>
          <h6 class="text-success mt-auto">$${parseFloat(product.precioVenta).toFixed(2)}</h6>
        </div>
      </div>`;

    card.addEventListener('click', () => addProduct(product));
    container.appendChild(card);
  });
}

function loadTables() {
  fetch('index.php?pg=sales&action=getTables')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        mesasCache = data.data;
        tables = {};
        data.data.forEach(mesa => {
          tables[mesa.idMesa] = { ...mesa };
        });
        showTables(mesasCache);
      } else {
        console.error('Error al cargar mesas:', data.error);
      }
    })
    .catch(error => console.error('Error al cargar mesas:', error));
}

function showTables(mesas) {
  const container = document.getElementById("mesasContainer");
  if (!container) return;

  container.innerHTML = '';

  mesas.forEach(mesa => {
    const card = document.createElement("div");
    card.className = "col-md-2 col-sm-4 col-6 mb-3";
    card.setAttribute('data-mesa-id', mesa.idMesa);

    const isOcupada = mesa.estado === 'ocupada';
    const bgColor = isOcupada ? '#ff6b6b' : '#e8f5e9';
    const textColor = isOcupada ? '#fff' : '#333';
    const borderColor = isOcupada ? '#cc5555' : '#4caf50';

    card.innerHTML = `
      <div class="card mesa-card" style="height: 100px; background-color: ${bgColor}; border: 2px solid ${borderColor}; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
        <div class="card-body text-center" style="color: ${textColor}; padding: 10px;">
          <h6 class="card-title mb-1" style="font-weight: bold; font-size: 1.1rem;">Mesa ${mesa.numero}</h6>
          <p class="card-text mb-0" style="font-size: 0.85rem;">${isOcupada ? 'Ocupada' : 'Libre'}</p>
        </div>
      </div>`;

    card.addEventListener('click', () => {
      if (isOcupada) {
        openMesaTab(mesa.idMesa, mesa.numero);
      } else {
        openTableSelectionModal(mesa.idMesa, mesa.numero);
      }
    });

    container.appendChild(card);
  });
}

// ============================================================================
// GESTIÓN DE CARRITOS Y PRODUCTOS
// ============================================================================

function getCart() {
  if (!carts[currentCartId]) {
    carts[currentCartId] = { type: 'sale', products: [], total: 0 };
  }
  return carts[currentCartId];
}

function switchToCart(cartId) {
  currentCartId = cartId;
  updateCart();
}

function addProduct(product) {
  const cart = getCart();
  const existingProduct = cart.products.find(p => p.idProducto === product.idProducto);

  if (existingProduct) {
    existingProduct.cantidad++;
  } else {
    cart.products.push({
      ...product,
      cantidad: 1,
      precioVenta: parseFloat(product.precioVenta)
    });
  }

  updateCart();
}

function updateCart() {
  const cart = getCart();
  const container = document.getElementById("carrito");
  if (!container) return;

  container.innerHTML = '';

  if (cart.products.length === 0) {
    container.innerHTML = '<p class="text-center text-muted mt-3">Carrito vacío</p>';
    updateTotal(0);
    return;
  }

  let subtotal = 0;
  cart.products.forEach((product, index) => {
    const itemTotal = product.cantidad * product.precioVenta;
    subtotal += itemTotal;

    const row = document.createElement("div");
    row.className = "carrito-item";
    row.innerHTML = `
      <div style="flex: 1;">
        <p class="mb-1"><strong>${product.nombre}</strong></p>
        <p class="mb-0 text-muted" style="font-size: 0.85rem;">$${product.precioVenta.toFixed(2)} x ${product.cantidad} = $${itemTotal.toFixed(2)}</p>
      </div>
      <div style="display: flex; gap: 5px; align-items: center;">
        <button class="btn btn-sm btn-outline-secondary" onclick="decrementProduct(${index})">−</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="incrementProduct(${index})">+</button>
        <button class="btn btn-sm btn-outline-danger" onclick="removeProduct(${index})">✕</button>
      </div>`;

    container.appendChild(row);
  });

  updateTotal(subtotal);

  // Sincronizar estado de mesa si es necesario
  if (cart.type === 'table') {
    syncTableState(cart.tableId, cart.products.length);
  }
}

function incrementProduct(index) {
  const cart = getCart();
  if (cart.products[index]) cart.products[index].cantidad++;
  updateCart();
}

function decrementProduct(index) {
  const cart = getCart();
  if (cart.products[index] && cart.products[index].cantidad > 1) {
    cart.products[index].cantidad--;
  }
  updateCart();
}

function removeProduct(index) {
  const cart = getCart();
  cart.products.splice(index, 1);
  updateCart();
}

function updateTotal(subtotal) {
  const totalElement = document.getElementById("totalCarrito");
  if (totalElement) {
    totalElement.textContent = subtotal.toFixed(2);
  }
}

// ============================================================================
// GESTIÓN DE PESTAÑAS Y MESAS
// ============================================================================

function addTabs(event) {
  if (event) event.preventDefault();

  const newCartId = `venta${Date.now()}`;
  carts[newCartId] = { type: 'sale', products: [], total: 0 };

  createTab(newCartId, `Venta ${Object.keys(carts).filter(k => k.startsWith('venta')).length}`, false);
  switchToCart(newCartId);
}

function createTab(cartId, tabName, isTable = false, tableNumber = null) {
  const tabsContainer = document.getElementById('ventasTabs');
  if (!tabsContainer) return;

  const tabId = `tab-${cartId}`;
  const navItem = document.createElement('li');
  navItem.className = 'nav-item';
  navItem.innerHTML = `
    <a class="nav-link active" id="${tabId}" data-bs-toggle="pill" href="#${cartId}" role="tab">
      ${tabName}
      <button class="btn btn-sm btn-close" style="width: 20px; height: 20px; padding: 0; margin-left: 5px;" onclick="dropTab('${cartId}', event)"></button>
    </a>`;

  tabsContainer.appendChild(navItem);

  const contentContainer = document.getElementById('ventasContent');
  if (contentContainer) {
    const content = document.createElement('div');
    content.className = 'tab-pane fade show active';
    content.id = cartId;
    content.innerHTML = `<div id="carrito" style="margin-top: 10px;"></div>`;
    contentContainer.appendChild(content);
  }

  const tabLink = document.getElementById(tabId);
  if (tabLink) {
    tabLink.addEventListener('click', () => switchToCart(cartId));
  }
}

function createTableTab(tableId, tableNumber) {
  const cartId = `mesa${tableId}`;
  const tabName = `Mesa ${tableNumber}`;

  if (!carts[cartId]) {
    carts[cartId] = {
      type: 'table',
      tableId: tableId,
      tableNumber: tableNumber,
      products: [],
      total: 0
    };
  }

  createTab(cartId, tabName, true, tableNumber);
  switchToCart(cartId);
}

function dropTab(cartId, event) {
  if (event) event.stopPropagation();

  const cart = carts[cartId];
  if (cart && cart.type === 'table') {
    // Si es mesa, primero liberar
    releaseTableTab(cartId, cart.tableId);
  } else {
    // Si es venta normal, solo cerrar pestaña
    const tabElement = document.getElementById(`tab-${cartId}`);
    const contentElement = document.getElementById(cartId);
    if (tabElement) tabElement.parentElement.remove();
    if (contentElement) contentElement.remove();
    delete carts[cartId];

    const remainingTabs = document.querySelectorAll('#ventasTabs .nav-link');
    if (remainingTabs.length > 0) {
      const firstTab = remainingTabs[0];
      firstTab.click();
      switchToCart(firstTab.id.replace('tab-', ''));
    }
  }
}

function releaseTableTab(cartId, tableId) {
  fetch('index.php?pg=sales&action=releaseTable', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idMesa: tableId })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success || data.message) {
        // Actualizar caché de mesas
        if (tables[tableId]) {
          tables[tableId].estado = 'libre';
        }

        // Cerrar pestaña
        const tabElement = document.getElementById(`tab-${cartId}`);
        const contentElement = document.getElementById(cartId);
        if (tabElement) tabElement.parentElement.remove();
        if (contentElement) contentElement.remove();
        delete carts[cartId];

        // Actualizar UI de mesas
        updateMesasListUI(tableId);

        // Cambiar a otra pestaña si existe
        const remainingTabs = document.querySelectorAll('#ventasTabs .nav-link');
        if (remainingTabs.length > 0) {
          remainingTabs[0].click();
          switchToCart(remainingTabs[0].id.replace('tab-', ''));
        }

        console.log('Mesa liberada correctamente');
      } else {
        console.error('Error al liberar mesa:', data.error);
      }
    })
    .catch(error => console.error('Error al liberar mesa:', error));
}

function openTableSelectionModal(tableId, tableNumber) {
  createTableTab(tableId, tableNumber);
  sendTableStateToServer(tableId, 'ocupada');
  updateMesasListUI(tableId);
}

function openMesaTab(tableId, tableNumber) {
  const cartId = `mesa${tableId}`;
  if (carts[cartId]) {
    switchToCart(cartId);
  } else {
    createTableTab(tableId, tableNumber);
  }
}

// ============================================================================
// SECCIÓN 6: GESTIÓN DE MESAS
// ============================================================================

/**
 * Abrir modal de selección de mesas para transferir productos
 */
function openTableSelectionModal(event) {
  event.stopPropagation();
  
  const cartObj = getCart();
  if (!cartObj.products || cartObj.products.length === 0) {
    alert('El carrito está vacío. Agrega productos antes de asignar una mesa.');
    return;
  }

  // Recargar mesas
  fetch("index.php?pg=sales&action=getTables")
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        mesasCache = data.data;
        showTableSelectionPopup(data.data);
      }
    })
    .catch(error => console.error('Error al cargar mesas:', error));
}

/**
 * Mostrar popup de selección de mesas
 */
function showTableSelectionPopup(mesas) {
  const container = document.getElementById("tableContainer");
  if (!container) return;

  container.innerHTML = '';

  mesas.forEach((mesa) => {
    const button = document.createElement("button");
    button.className = "m-2 table-card p-2";
    
    if (mesa.estado === "libre") {
      button.innerHTML = `
        <h4>Mesa #${mesa.numero}</h4>
        <img src="assets/img/mesa.jpg" class="table-img" onerror="this.src='assets/img/categories/default.png'">
        <small style="color: green; font-weight: bold;">Disponible</small>`;
      button.style.cursor = 'pointer';
      button.addEventListener("click", () => transferToTable(mesa.idMesa, mesa.numero));
    } else {
      button.innerHTML = `
        <h4 style="color:red;">Mesa #${mesa.numero}</h4>
        <img src="assets/img/mesa.jpg" class="table-img" onerror="this.src='assets/img/categories/default.png'">
        <small style="color: red; font-weight: bold;">Ocupada</small>`;
      button.style.cursor = 'not-allowed';
      button.style.opacity = '0.5';
    }

    container.appendChild(button);
  });

  document.getElementById("tableOverlay").classList.add('active');
}

/**
 * Transferir productos del carrito actual a una mesa
 * Crea un nuevo tab para la mesa y mueve los productos
 */
function transferToTable(tableId, tableNumber) {
  if (isTransferring) return;
  isTransferring = true;

  try {
    const sourceCartId = currentCartId;
    const sourceCart = getCart(sourceCartId);

    // Validaciones
    if (!sourceCart.products || sourceCart.products.length === 0) {
      alert('El carrito está vacío.');
      isTransferring = false;
      return;
    }

    if (sourceCart.type === 'table') {
      alert('No puedes transferir desde una mesa a otra mesa.');
      isTransferring = false;
      return;
    }

    // Crear ID de carrito para la mesa
    const tableCartId = `mesa${tableId}`;

    // Copiar productos al nuevo carrito de mesa
    const tableProducts = sourceCart.products.map(p => ({ ...p }));
    const tableTotal = sourceCart.total;

    // Crear carrito de mesa
    carts[tableCartId] = {
      type: 'table',
      tableId: tableId,
      tableNumber: tableNumber,
      tableName: `Mesa ${tableNumber}`,
      products: tableProducts,
      total: tableTotal
    };

    // Actualizar cache de mesas
    if (tables[tableId]) {
      tables[tableId].estado = 'ocupada';
      tables[tableId].cartId = tableCartId;
      tables[tableId].productCount = tableProducts.length;
    }

    // Persistir estado en BD
    sendTableStateToServer(tableId, 'ocupada');

    // Crear tab de mesa
    createTableTab(tableId, tableNumber, tableCartId);

    // Vaciar carrito de venta original (opcional: puedes mantenerlo)
    sourceCart.products = [];
    sourceCart.total = 0;

    // Actualizar UI del carrito vacío
    updateCart();

    // Cerrar popup
    closeTable();

    // Notificación
    console.log(`✓ Productos trasferidos a Mesa ${tableNumber}`);

    // Cambiar a tab de mesa
    setTimeout(() => {
      switchToCart(tableCartId);
    }, 200);

  } catch (error) {
    console.error('Error al transferir a mesa:', error);
    alert('Error al transferir a la mesa. Intenta de nuevo.');
  } finally {
    isTransferring = false;
  }
}

/**
 * Crear pestaña para una mesa
 */
function createTableTab(tableId, tableNumber, tableCartId) {
  const tabs = document.getElementById('ventasTabs');
  const content = document.getElementById('ventasContent');
  if (!tabs || !content) return;

  // Crear tab
  const li = document.createElement('li');
  li.className = 'nav-item';
  const a = document.createElement('a');
  a.className = 'nav-link';
  a.setAttribute('data-bs-toggle', 'tab');
  a.setAttribute('href', `#${tableCartId}`);
  a.setAttribute('data-table-id', tableId);
  a.innerHTML = `Mesa ${tableNumber} <i onclick="releaseTableTab('${tableCartId}', ${tableId}); event.stopPropagation();" class="fa-solid fa-circle-xmark fa-xl" style="color: #ff0000; margin-left: 8px;"></i>`;
  a.addEventListener('click', () => switchToCart(tableCartId));
  li.appendChild(a);

  const addTabItem = document.getElementById('addTabItem');
  if (addTabItem) tabs.insertBefore(li, addTabItem);
  else tabs.appendChild(li);

  // Crear pane
  const pane = document.createElement('div');
  pane.className = 'tab-pane fade';
  pane.id = tableCartId;
  pane.setAttribute('data-table-id', tableId);
  pane.innerHTML = `
    <div id="carrito-${tableCartId}">
      <center>
        <h3>
          Mesa ${tableNumber}: 
          <div class="badge bg-warning rounded-circle" id="ventasCount-${tableCartId}">0</div>
        </h3>
      </center>
      <div id="productos-carrito-${tableCartId}" style="overflow-y: scroll; height: 600px;"></div>
      <div id="total-carrito-${tableCartId}"><h4>Total: $<span id="total-${tableCartId}">0.00</span></h4></div>
      <button id="btn-procesar-venta-${tableCartId}" class="btn btn-primary btn-lg w-100 mb-2">
        Facturar Mesa <i class="fa-solid fa-receipt"></i>
      </button>
      <button id="btn-agregar-productos-${tableCartId}" class="btn btn-success btn-lg w-100" onclick="event.stopPropagation(); switchToCart('${tableCartId}')">
        Agregar Más <i class="fa-solid fa-plus"></i>
      </button>
    </div>`;
  content.appendChild(pane);

  // Activar tab
  a.click();
}

/**
 * Liberar mesa (cerrar tab y marcar como libre)
 */
function releaseTableTab(tableCartId, tableId) {
  const tab = document.querySelector(`#ventasTabs a[href="#${tableCartId}"]`);
  const containerTab = tab.parentElement;
  const pane = document.getElementById(tableCartId);

  if (!tab || !containerTab || !pane) return;

  // Cambiar a otra pestaña si esta está activa
  if (tab.classList.contains('active')) {
    let nextTab = containerTab.previousElementSibling;
    if (!nextTab || nextTab.id === 'addTabItem') {
      nextTab = containerTab.nextElementSibling;
      if (nextTab && nextTab.id === 'addTabItem') nextTab = null;
    }
    
    if (nextTab) {
      const nextTabLink = nextTab.querySelector('a');
      if (nextTabLink) {
        const nextTabId = nextTabLink.getAttribute('href').substring(1);
        currentCartId = nextTabId;
        const tab_instance = new bootstrap.Tab(nextTabLink);
        tab_instance.show();
      }
    }
  }

  // Liberar mesa en BD (opcional)
  fetch("index.php?pg=sales&action=releaseTable", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idMesa: tableId })
  }).catch(e => console.error('Error al liberar mesa:', e));

  // Actualizar cache
  if (tables[tableId]) {
    tables[tableId].estado = 'libre';
    tables[tableId].cartId = null;
    tables[tableId].productCount = 0;
  }

  // Actualizar UI de listas de mesas si existe
  updateMesasListUI(tableId);

  // Eliminar del DOM
  delete carts[tableCartId];
  containerTab.remove();
  pane.remove();

  console.log(`✓ Mesa ${tableId} liberada`);
}

/**
 * Actualizar conteo de productos en dashboard de mesa
 */
function updateTableDashboardItem(tableId, productCount) {
  if (tables[tableId]) {
    tables[tableId].productCount = productCount;
  }
}

/**
 * Enviar estado de mesa al servidor para persistencia
 * @param {number} tableId
 * @param {'libre'|'ocupada'} estado
 */
function sendTableStateToServer(tableId, estado) {
  fetch("index.php?pg=sales&action=updateTableState", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idMesa: tableId, estado: estado })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Mantener cache sincronizada con BD
        if (!tables[tableId]) tables[tableId] = { idMesa: tableId, numero: data.data.numero || tableId, estado: data.data.estado, cartId: tables[tableId] ? tables[tableId].cartId : null, productCount: tables[tableId] ? tables[tableId].productCount : 0 };
        else tables[tableId].estado = data.data.estado;
        updateMesasListUI(tableId);
      } else {
        console.error('No se pudo actualizar estado en servidor:', data.error);
      }
    })
    .catch(err => console.error('Error comunicando estado de mesa:', err));
}

/**
 * Actualizar la representación visual de mesas en la UI (vista de mesas / tarjetas)
 * Busca elementos con atributo data-mesa-id y colorea según estado.
 */
function updateMesasListUI(changedTableId) {
  try {
    const entries = document.querySelectorAll(`[data-mesa-id="${changedTableId}"]`);
    if (!entries || entries.length === 0) return;
    const estado = tables[changedTableId] ? tables[changedTableId].estado : null;
    entries.forEach(el => {
      const title = el.querySelector('.mesa-title') || el.querySelector('h4');
      if (estado === 'ocupada') {
        if (title) title.style.color = 'red';
        el.classList.add('mesa-ocupada');
      } else {
        if (title) title.style.color = '';
        el.classList.remove('mesa-ocupada');
      }
    });
  } catch (e) {
    console.warn('updateMesasListUI fallo:', e);
  }
}

/**
 * Mostrar mesas en popup (compatibilidad con código anterior)
 */
function AddToTable(mesas) {
  showTableSelectionPopup(mesas);
}

/**
 * Cerrar popup de mesas
 */
function closeTable(event) {
  if (!event || event.target.id === 'tableOverlay') {
    document.getElementById('tableOverlay').classList.remove('active');
  }
}

// ============================================================================
// SECCIÓN 7: CALCULADORA DE CANTIDADES
// ============================================================================

const MAX = 99;
const MIN = 1;
let actualProduct = null;
let currentQuantity = '0';

/**
 * Abrir calculadora para cambiar cantidad
 */
function changeQuantity(productId) {
  openCalculator(productId);
}

/**
 * Abrir calculadora
 */
function openCalculator(productId) {
  actualProduct = productId;
  const existingProduct = getCart().products.find(p => parseInt(p.idProducto) === parseInt(productId));
  currentQuantity = existingProduct ? existingProduct.cantidad.toString() : '0';
  document.getElementById("calculatorOverlay").classList.add('active');
  document.getElementById("calculatorDisplay").textContent = currentQuantity;
}

/**
 * Cerrar calculadora
 */
function closeCalculator(event) {
  if (!event || event.target.id === 'calculatorOverlay') {
    document.getElementById('calculatorOverlay').classList.remove('active');
    actualProduct = null;
    currentQuantity = '';
  }
}

/**
 * Añadir número a la calculadora
 */
function addNumber(number) {
  let newQty;
  if (currentQuantity === '0' || currentQuantity === '') newQty = number;
  else if (currentQuantity.length < 2) newQty = currentQuantity + number;
  else return;

  const numericQty = parseInt(newQty);
  if (numericQty >= MIN && numericQty <= MAX) currentQuantity = newQty;
  document.getElementById("calculatorDisplay").textContent = currentQuantity;
}

/**
 * Eliminar último dígito
 */
function deleteLast() {
  currentQuantity = currentQuantity.slice(0, -1);
  document.getElementById("calculatorDisplay").textContent = currentQuantity || '0';
}

/**
 * Limpiar calculadora
 */
function clearCalculator() {
  currentQuantity = '0';
  document.getElementById("calculatorDisplay").textContent = currentQuantity || '0';
}

/**
 * Confirmar cantidad
 */
function confirmQuantity() {
  let qty = parseInt(currentQuantity);
  if (isNaN(qty) || qty < MIN || qty > MAX) qty = 1;

  if (actualProduct !== null) {
    const product = productosCache.find(p => parseInt(p.idProducto) === parseInt(actualProduct));
    if (product) {
      const productToAdd = {
        idProducto: product.idProducto,
        nombre: product.nombre,
        categoria: product.categoria,
        imagen: product.imagen,
        categoria_imagen: product.categoria_imagen,
        precioVenta: parseFloat(product.precioVenta),
        cantidad: qty,
        precioTotal: qty * parseFloat(product.precioVenta)
      };

      const cartObj = getCart();
      const existingProduct = cartObj.products.find(p => parseInt(p.idProducto) === parseInt(actualProduct));
      
      if (existingProduct) {
        existingProduct.cantidad = qty;
        existingProduct.precioTotal = qty * existingProduct.precioVenta;
      } else {
        cartObj.products.push(productToAdd);
      }

      updateCart();
    }
    closeCalculator();
  }
}

/**
 * Abrir pestaña de mesa desde la vista de Mesas (sin transferir productos)
 * Si el carrito de la mesa no existe lo crea vacío y abre el tab para agregar productos.
 */
function openMesaTab(tableId, tableNumber) {
  const tableCartId = `mesa${tableId}`;

  // Crear carrito de mesa si no existe
  if (!carts[tableCartId]) {
    carts[tableCartId] = {
      type: 'table',
      tableId: tableId,
      tableNumber: tableNumber,
      tableName: `Mesa ${tableNumber}`,
      products: [],
      total: 0
    };
  }

  // Asegurar entrada en cache de tables
  if (!tables[tableId]) {
    tables[tableId] = { idMesa: tableId, numero: tableNumber, estado: 'libre', cartId: tableCartId, productCount: 0 };
  } else {
    tables[tableId].cartId = tableCartId;
  }

  // Si el pane ya existe, simplemente mostrarlo
  const existingPane = document.getElementById(tableCartId);
  if (existingPane) {
    const link = document.querySelector(`#ventasTabs a[href="#${tableCartId}"]`);
    if (link) {
      const tab_instance = new bootstrap.Tab(link);
      tab_instance.show();
    }
    switchToCart(tableCartId);
    return;
  }

  // Crear tab/pane para la mesa
  createTableTab(tableId, tableNumber, tableCartId);
  // No marcar como ocupada hasta que se agreguen productos (transferencia)
}

function loadTables() {
  let url = "index.php?pg=sales&action=gettables"

  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
          mesasCache = data.data;
          AddToTable(data.data);
        } else {
        console.log('No se pudieron cargar las mesas');
      }
    })
    .catch(error => {
      console.error('Error al cargar las mesas:', error);
    });
}

function AddToTable(mesas) {
  const cartId = currentCartId

  document.getElementById("tableOverlay").classList.add('active');
  const container = document.getElementById("tableContainer");
  
  mesas.forEach((mesa) => {
    const button = document.createElement("button");
    if (mesa.estado == "libre") {
      button.className = "m-2 table-card p-2";
      button.innerHTML = `<h4 >mesa #${mesa.numero}</h4>
                        <img src="assets/img/mesa.jpg"  class ="table-img">
                        `;
      button.addEventListener("click", () => busyTable(mesa));
      container.appendChild(button)
    } else {
      button.className = "m-2 table-card p-2";
      button.innerHTML = `<h4 style="color:red;">mesa #${mesa.numero}</h4>
                        <img src="assets/img/mesa.jpg"  class ="table-img">
                        `;
      container.appendChild(button)
    }
  })
}

function closeTable(event) {
  if (!event || event.target.id === 'tableOverlay') {
    document.getElementById('tableOverlay').classList.remove('active');
  }
}
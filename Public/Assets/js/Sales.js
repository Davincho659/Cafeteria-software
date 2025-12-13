
/* Sales.js - versión mejorada (carritos con persistencia de mesas en BD)
   - Maneja múltiples pestañas (ventas) con su propio carrito en memoria
   - Mantiene la lista de productos y categorías compartida
   - Cada pestaña tiene su propio contenedor de carrito con ids únicos (ventaN)
   - Sistema de mesas con sincronización en BD (ocupada/libre)
*/

// Obtener userId desde el HTML
function getSessionUserId() {
  return document.querySelector('[data-user-id]')?.getAttribute('data-user-id') || 1;
}

// Cache de datos
let categoriasCache = [];
let productosCache = [];
let mesasCache = [];

// Gestión de múltiples carritos
let currentCartId = 'venta1';
let carts = {
  'venta1': { type: 'sale', products: [], total: 0 }
};

// Cache de mesas con estado sincronizado a BD
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
}

function loadCategories() {
  fetch('index.php?pg=sales&action=getCategories')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        categoriasCache = data.data;
        showCategories(data.data);
      } else {
        console.log('No se pudieron cargar las categorías');
      }
    })
    .catch(error => {
      console.error('Error al cargar categorías:', error);
    });
}

function showCategories(categories) {
  const container = document.getElementById("categoriasNav");
  if (!container) return;

  // Crear botón "Todos los Productos" al inicio (una sola vez)
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
        console.log('No se pudieron cargar los productos');
      }
    })
    .catch(error => console.error('Error al cargar productos:', error));
}

function showProducts(products) {
  const container = document.getElementById("productosContainer");
  if (!container) return;
  if (products.length === 0) {
    container.innerHTML = '<div class="loading">No hay productos disponibles</div>';
    return;
  }

  container.innerHTML = "";
  products.forEach((product) => {
    const imgPath = product.imagen ? `assets/img/${product.imagen}` : (product.categoria_imagen ? `assets/img/${product.categoria_imagen}` : 'assets/img/products/default.jpg');
    product.cantidad = 1;

    const button = document.createElement("button");
    button.className = "m-2 producto-card p-2";
    button.style.width = "200px";
    button.style.height = "300px";

    button.innerHTML = `
      <div class="producto-img-container">
        <img src="${imgPath}" alt="${product.nombre}" class="producto-img" onerror="this.src='/placeholder.svg?height=140&width=220'">
      </div>
      <div class="d-flex flex-column align-items-left">
        <div class="producto-nombre"><b>${product.nombre}</b></div>
        <div class="producto-categoria">${product.categoria}</div>
        <p class="producto-precio"><b>$ ${new Intl.NumberFormat('es-CO').format(product.precioVenta)}</b></p>
        <span class="btn cantidad-display" id="prod-qty-${product.idProducto}" onclick="event.stopPropagation(); changeQuantity(${product.idProducto})" role="button">${product.cantidad}</span>
      </div>`;

    button.addEventListener("click", () => addToCart(product));
    container.appendChild(button);
  });
}

// --- Carritos por pestaña (funciones que operan sobre el carrito activo) ---
function getCart(cartId) {
  return carts[cartId || currentCartId] || { products: [], total: 0 };
}

function addToCart(product) {
  const cartObj = getCart();
  const productId = parseInt(product.idProducto);
  const exist = cartObj.products.find(p => parseInt(p.idProducto) === productId);
  if (exist) {
    exist.cantidad = parseInt(exist.cantidad || 0) + 1;
    exist.precioTotal = exist.cantidad * parseFloat(exist.precioVenta || 0);
  } else {
    const productCopy = {
      idProducto: product.idProducto,
      nombre: product.nombre,
      categoria: product.categoria,
      imagen: product.imagen,
      categoria_imagen: product.categoria_imagen,
      precioVenta: parseFloat(product.precioVenta || 0),
      cantidad: 1,
      precioTotal: parseFloat(product.precioVenta || 0)
    };
    cartObj.products.push(productCopy);
  }

  // actualizar botón de cantidad en listado (refleja carrito activo)
  const cantidadBtn = document.getElementById(`prod-qty-${productId}`);
  if (cantidadBtn) {
    const updatedProduct = cartObj.products.find(p => parseInt(p.idProducto) === productId);
    cantidadBtn.textContent = updatedProduct ? updatedProduct.cantidad : 1;
    
    const productCard = cantidadBtn.closest('.producto-card');
    if (productCard) {
      productCard.classList.add('disabled');
    const imgContainer = productCard.querySelector('.producto-img-container');
      if (imgContainer && !imgContainer.querySelector('.added-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'added-overlay';
        overlay.innerHTML = '<i class="fas fa-check"></i>';
        imgContainer.appendChild(overlay);
      }
    }
  }

  updateCart();
}

function updateCart() {
  const cartObj = getCart();
  const vistaTotal = document.getElementById(`total-${currentCartId}`);
  const ventasCount = document.getElementById(`ventasCount-${currentCartId}`);

  const totalItems = cartObj.products.reduce((total, p) => total + p.cantidad, 0);
  if (ventasCount) ventasCount.textContent = totalItems;

  const nuevoTotal = cartObj.products.reduce((acumul, product) => acumul + parseFloat(product.precioVenta || 0) * parseInt(product.cantidad || 1), 0);
  cartObj.total = nuevoTotal;
  if (vistaTotal) vistaTotal.textContent = new Intl.NumberFormat('es-CO').format(nuevoTotal);

  showCartProducts(currentCartId);
}

function showCartProducts(cartId) {
  const id = cartId || currentCartId;
  const cartObj = carts[id] || { products: [] };
  const container = document.getElementById(`productos-carrito-${id}`);
  if (!container) return;
  container.innerHTML = '';

  cartObj.products.forEach(product => {
    const imgPath = product.imagen ? `assets/img/${product.imagen}` : (product.categoria_imagen ? `assets/img/${product.categoria_imagen}` : 'assets/img/products/default.jpg');
    const productDiv = document.createElement('div');
    productDiv.className = 'cart-product';

    productDiv.innerHTML = `
      <div class="row align-items-center">
        <div class="col-auto"><img src="${imgPath}" alt="imagen" class="product-img"></div>
        <div class="col">
          <div class="product-info">
            <div class="product-title">${product.nombre}</div>
            <div class="product-actions">
              <div class="quantity-control">
                <button onclick="decreaseQty(${product.idProducto}, '${id}')">−</button>
                <input type="text" id="cart-qty-${product.idProducto}-${id}" value="${product.cantidad}" readonly>
                <button onclick="increaseQty(${product.idProducto}, '${id}')">+</button>
              </div>
              <button class="remove-btn" onclick="dropProduct(${product.idProducto}, '${id}')"><i class="fa-solid fa-trash-can" style="color: #ff0000;"></i></button>
            </div>
          </div>
        </div>
        <div class="col"><div class="price-section"><div class="price">$ ${new Intl.NumberFormat('es-CO').format(product.precioTotal)}</div></div></div>
      </div>`;

    container.appendChild(productDiv);
  });
}

function dropProduct(idProducto, cartId) {
  const id = cartId || currentCartId;
  const cartObj = carts[id];
  if (!cartObj) return;
  cartObj.products = cartObj.products.filter(p => parseInt(p.idProducto) !== parseInt(idProducto));

  if (id === currentCartId) {
    const cantidadBtn = document.getElementById(`prod-qty-${idProducto}`);
    if (cantidadBtn) cantidadBtn.textContent = 1;
  }

  updateCart();
}

function increaseQty(idProducto, cartId) {
  const id = cartId || currentCartId;
  const cartObj = carts[id];
  if (!cartObj) return;
  const product = cartObj.products.find(p => parseInt(p.idProducto) === parseInt(idProducto));
  if (product) {
    product.cantidad = parseInt(product.cantidad || 0) + 1;
    product.precioTotal = product.cantidad * parseFloat(product.precioVenta || 0);
    if (id === currentCartId) updateCart();
  }
}

function decreaseQty(idProducto, cartId) {
  const id = cartId || currentCartId;
  const cartObj = carts[id];
  if (!cartObj) return;
  const product = cartObj.products.find(p => parseInt(p.idProducto) === parseInt(idProducto));
  if (product && product.cantidad > 1) {
    product.cantidad = parseInt(product.cantidad) - 1;
    product.precioTotal = product.cantidad * parseFloat(product.precioVenta || 0);
    if (id === currentCartId) updateCart();
  }
}

// Calculadora de productos (mantiene compatibilidad con la UI existente)
const MAX = 99;
const MIN = 1;
let actualProduct = null;
let currentQuantity = '0';

function changeQuantity(productId) {
  openCalculator(productId);
}

function openCalculator(productId) {
  actualProduct = productId;
  const existingProduct = getCart().products.find(p => parseInt(p.idProducto) === parseInt(productId));
  currentQuantity = existingProduct ? existingProduct.cantidad.toString() : '0';
  document.getElementById("calculatorOverlay").classList.add('active');
  document.getElementById("calculatorDisplay").textContent = currentQuantity;
}

function closeCalculator(event) {
  if (!event || event.target.id === 'calculatorOverlay') {
    document.getElementById('calculatorOverlay').classList.remove('active');
    actualProduct = null;
    currentQuantity = '';
  }
}

function addNumber(number) {
  let newQty;
  if (currentQuantity === '0' || currentQuantity === '') newQty = number;
  else if (currentQuantity.length < 2) newQty = currentQuantity + number;
  else return;

  const numericQty = parseInt(newQty);
  if (numericQty >= MIN && numericQty <= MAX) currentQuantity = newQty;
  document.getElementById("calculatorDisplay").textContent = currentQuantity;
}

function deleteLast() {
  currentQuantity = currentQuantity.slice(0, -1);
  document.getElementById("calculatorDisplay").textContent = currentQuantity || '0';
}

function clearCalculator() {
  currentQuantity = '0';
  document.getElementById("calculatorDisplay").textContent = currentQuantity || '0';
}

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

// Cambia el carrito activo y refresca UI mínima
function switchToCart(cartId) {
  currentCartId = cartId;
  document.querySelectorAll('.cantidad-display').forEach(btn => {
    const pid = btn.id.replace('prod-qty-', '');
    const prod = getCart().products.find(p => parseInt(p.idProducto) === parseInt(pid));
    btn.textContent = prod ? prod.cantidad : 1;
  });
  updateCart();
}

// Crea nueva pestaña y su pane con ids únicos
function switchToCart(cartId) {
  currentCartId = cartId;
  document.querySelectorAll('.cantidad-display').forEach(btn => {
    const pid = btn.id.replace('prod-qty-', '');
    const prod = getCart().products.find(p => parseInt(p.idProducto) === parseInt(pid));
    btn.textContent = prod ? prod.cantidad : 1;
  });
  updateCart();
}

// Crea nueva pestaña y su pane con ids únicos
function addTabs() {
  const tabs = document.getElementById('ventasTabs');
  const content = document.getElementById('ventasContent');
  if (!tabs || !content) return;
  const count = tabs.querySelectorAll('.nav-item').length;
  const id = `venta${count}`;

  const li = document.createElement('li');
  li.className = 'nav-item';
  const a = document.createElement('a');
  a.className = 'nav-link';
  a.setAttribute('data-bs-toggle', 'tab');
  a.setAttribute('href', `#${id}`);
  a.innerHTML = `Venta ${count} <i onclick="dropTab('${id}')" class="fa-solid fa-circle-xmark fa-xl" style="color: #ff0000; margin-left: 8px;"></i>`;
  a.addEventListener('click', () => switchToCart(id));
  li.appendChild(a);
  const addTabItem = document.getElementById('addTabItem');
  if (addTabItem) tabs.insertBefore(li, addTabItem);
  else tabs.appendChild(li);

  const pane = document.createElement('div');
  pane.className = 'tab-pane fade';
  pane.id = id;
  pane.innerHTML = `
    <div id="carrito-${id}">
      <center style="flex-shrink: 0; padding: 1rem 0;">
        <h3>Ventas: <div class="badge bg-primary rounded-circle" id="ventasCount-${id}">0</div></h3>
      </center>
      <div id="productos-carrito-${id}" style="height: calc(85vh - 220px);overflow-y: auto;overflow-x: hidden;"></div>
      <div style="flex-shrink: 0; padding: 1rem 0;">
          <div id="total-carrito-${id}">
              <h4>Total: $<span id="total-${id}">0.00</span></h4>
          </div>
          <button id="btn-procesar-venta-${id}" class="btn btn-primary btn-lg w-100 mb-2" 
                  onclick="saleConfirmationModal('${id}', <?= $_SESSION['user_id'] ?>)" role="button">
              Procesar Venta <i class="fa-solid fa-cash-register"></i>
          </button>
          <button id="btn-agregar-mesa-${id}" class="btn btn-secondary btn-lg w-100" 
                  onclick="openTableSelectionModal(event)" role="button">
              Agregar a Mesa <i class="fa-solid fa-utensils"></i>
          </button>
      </div>
    </div>`;
  content.appendChild(pane);

  carts[id] = { products: [], total: 0 };
  a.click();
}

function dropTab(tabId) {
  const tab = document.querySelector(`#ventasTabs a[href="#${tabId}"]`);
  const containerTab = tab.parentElement;
  const pane = document.getElementById(tabId);

  if (!tab || !containerTab || !pane) return;

  if (tab.classList.contains('active')) {
    let nextTab = containerTab.previousElementSibling;
    
    if (!nextTab || nextTab.id === 'addTabItem') {
      nextTab = containerTab.nextElementSibling;
      if (nextTab && nextTab.id === 'addTabItem') {
        nextTab = null;
      }
    }
    
    if (nextTab) {
      const nextTabLink = nextTab.querySelector('a');
      if (nextTabLink) {
        const nextTabId = nextTabLink.getAttribute('href').substring(1);
        
        currentCartId = nextTabId;
        
        const tab_instance = new bootstrap.Tab(nextTabLink);
        tab_instance.show();
        
        setTimeout(() => {
          console.log('Ejecutando switchToCart para:', nextTabId);
          switchToCart(nextTabId);
          
          delete carts[tabId];
          
          // Finalmente eliminar del DOM
          containerTab.remove();
          pane.remove();
        }, 100);
        
        return;
      }
    }
  }

  delete carts[tabId];
  
  containerTab.remove();
  pane.remove();
} 

function loadTables() {
  fetch('index.php?pg=sales&action=getTables')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        mesasCache = data.data;
        // Inicializar cache de mesas
        mesasCache.forEach(mesa => {
          if (!tables[mesa.idMesa]) {
            tables[mesa.idMesa] = {
              idMesa: mesa.idMesa,
              numero: mesa.numero,
              estado: mesa.estado,
              cartId: null,
              productCount: 0
            };
          }
        });
        // Mostrar mesas en modal
        showTableSelectionPopup(mesasCache);
      } else {
        console.log('No se pudieron cargar las mesas');
      }
    })
    .catch(error => {
      console.error('Error al cargar las mesas:', error);
    });
}

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

function openTableSelectionModal(event) {
  event.stopPropagation();
  
  

  loadTables();
}

function transferToTable(tableId, tableNumber) {
  if (isTransferring) return;
  isTransferring = true;

  try {
    const sourceCartId = currentCartId;
    const sourceCart = getCart(sourceCartId);

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

    const tableCartId = `mesa${tableId}`;
    const tableProducts = sourceCart.products.map(p => ({ ...p }));
    const tableTotal = sourceCart.total;

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

    // Vaciar carrito de venta original
    sourceCart.products = [];
    sourceCart.total = 0;

    updateCart();
    closeTable();

    console.log(`✓ Productos trasferidos a Mesa ${tableNumber}`);

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

function createTableTab(tableId, tableNumber, tableCartId) {
  const tabs = document.getElementById('ventasTabs');
  const content = document.getElementById('ventasContent');
  if (!tabs || !content) return;

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
      <button id="btn-procesar-venta-${tableCartId}" class="btn btn-primary btn-lg w-100 mb-2" onclick="saleConfirmationModal('${tableCartId}', getSessionUserId())">
        Facturar Mesa <i class="fa-solid fa-receipt"></i>
      </button>
      <button id="btn-agregar-productos-${tableCartId}" class="btn btn-success btn-lg w-100" onclick="event.stopPropagation(); switchToCart('${tableCartId}')">
        Agregar Más <i class="fa-solid fa-plus"></i>
      </button>
    </div>`;
  content.appendChild(pane);

  a.click();
}

function releaseTableTab(tableCartId, tableId) {
  const tab = document.querySelector(`#ventasTabs a[href="#${tableCartId}"]`);
  const containerTab = tab.parentElement;
  const pane = document.getElementById(tableCartId);

  if (!tab || !containerTab || !pane) return;

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

  // Liberar mesa en BD
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

  updateMesasListUI(tableId);

  delete carts[tableCartId];
  containerTab.remove();
  pane.remove();

  console.log(`✓ Mesa ${tableId} liberada`);
}

// ============================================================================
// FUNCIONES DE PERSISTENCIA EN BD (SINCRONIZACIÓN DE MESAS)
// ============================================================================

function updateTableDashboardItem(tableId, productCount) {
  if (tables[tableId]) {
    tables[tableId].productCount = productCount;
  }
}

function sendTableStateToServer(tableId, estado) {
  fetch("index.php?pg=sales&action=updateTableState", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idMesa: tableId, estado: estado })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (!tables[tableId]) tables[tableId] = { idMesa: tableId, numero: data.data.numero || tableId, estado: data.data.estado, cartId: tables[tableId] ? tables[tableId].cartId : null, productCount: tables[tableId] ? tables[tableId].productCount : 0 };
        else tables[tableId].estado = data.data.estado;
        updateMesasListUI(tableId);
      } else {
        console.error('No se pudo actualizar estado en servidor:', data.error);
      }
    })
    .catch(err => console.error('Error comunicando estado de mesa:', err));
}

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
function closeTable(event) {
  if (!event || event.target.id === 'tableOverlay') {
    document.getElementById('tableOverlay').classList.remove('active');
  }
}

// Mostrar el pop-up de confirmación que está definido en la vista
function saleConfirmationModal(cartId, userId = null) {
  // Mantener compatibilidad si se llama sin parámetros
  if (!cartId || currentCartId !== cartId) cartId = currentCartId;
  const cartObj = getCart(cartId);
  if (!cartObj || !cartObj.products || cartObj.products.length === 0) {
    alert('El carrito está vacío');
    return;
  }

  const overlay = document.getElementById('saleConfirmationOverlay');

  // Rellenar total
  const totalEl = document.getElementById('saleTotalValue');
  if (totalEl) totalEl.textContent = `$ ${new Intl.NumberFormat('es-CO').format(cartObj.total)}`;

  // Valor por defecto método de pago
  overlay.dataset.paymentMethod = 'efectivo';
  overlay.dataset.cartId = cartId;
  overlay.dataset.userId = userId;

  // Marcar botón efectivo como activo visualmente si existe
  const btnE = document.getElementById('salePaymentEfectivo');
  const btnT = document.getElementById('salePaymentTransfer');
  if (btnE && btnT) {
    btnE.classList.add('active');
    btnT.classList.remove('active');
  }

  // Mostrar overlay (coincide con patrón de la calculadora y mesas)
  overlay.classList.add('active');
}

function selectPaymentMethod(btn, method) {
  const overlay = document.getElementById('saleConfirmationOverlay');
  if (!overlay) return;
  // desactivar todas
  overlay.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('active'));
  // activar la pulsada
  if (btn && btn.classList) btn.classList.add('active');
  overlay.dataset.paymentMethod = method;
}

function closeSaleConfirmation(event) {
  const el = document.getElementById('saleConfirmationOverlay');
  if (!el) return;
  if (!event || event.target.id === 'saleConfirmationOverlay') {
    el.classList.remove('active');
  }
}



function confirmSalePayment() {
  const overlay = document.getElementById('saleConfirmationOverlay');
  if (!overlay) return;
  const metodo = overlay.dataset && overlay.dataset.paymentMethod ? overlay.dataset.paymentMethod : 'efectivo';
  const cartId = overlay.dataset.cartId || currentCartId;
  const userId = overlay.dataset.userId || getSessionUserId();
  saleProcess(cartId, userId, metodo);
  overlay.classList.remove('active');
}

function saleProcess(cartId, userId, paymentMethod = 'efectivo') {
  if (!cartId || currentCartId !== cartId) cartId = currentCartId;
  
  const cartObj = getCart(cartId);
  let facturaWindow = null;
  
  // Validar carrito
  if (!cartObj.products || cartObj.products.length === 0) {
    alert('El carrito está vacío');
    return;
  }
  const payload = {
    cartId: cartId,
    tipo: cartObj.type,
    tableId: cartObj.tableId || null,
    tableNumber: cartObj.tableNumber || null,
    metodoPago: paymentMethod,
    idUsuario: userId, 
    total: cartObj.total,
    productos: cartObj.products.map(p => ({
      idProducto: p.idProducto,
      cantidad: p.cantidad,
      precioUnitario: p.precioVenta,
      precioTotal: p.precioTotal
    }))
  };
  fetch('index.php?pg=sales&action=CreateSale', {
    method: 'POST',
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Registro creado correctamente',
        timer: 1500,
        showConfirmButton: false
      });
      openInvoice(data.saleId);
      cartObj.products = [];
      cartObj.total = 0;
      updateCart();
      document.addEventListener("click", function () {
        if (facturaWindow && !facturaWindow.closed) {
                facturaWindow.close();
                facturaWindow = null;
            }
      });
    } else {
      alert('Error: ' + data.error);
    }
    function openInvoice(id) {
      facturaWindow = window.open(
        "factura.php?pg=bill&id=" + id,
        "_blank",
        "width=350,height=900"
      );
    }
  })
  .catch(err => console.error('Error al procesar venta:', err));

}

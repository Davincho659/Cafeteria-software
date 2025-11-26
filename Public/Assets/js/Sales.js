
/* Sales.js - versión reescrita (base para pestañas con carritos separados)
   - Base para manejar múltiples pestañas (ventas) con su propio carrito en memoria
   - Mantiene la lista de productos y categorías compartida
   - Cada pestaña tiene su propio contenedor de carrito con ids únicos (ventaN)
*/

// Cache de datos
let categoriasCache = [];
let productosCache = [];

// Gestión de múltiples carritos (estado mínimo)
let currentCartId = 'venta1';
let carts = {
  'venta1': { products: [], total: 0 }
};

document.addEventListener("DOMContentLoaded", () => {
  startSistem();
  // vincular botón para crear nuevas pestañas
  const nuevaBtn = document.getElementById('nuevaVenta');
  if (nuevaBtn) nuevaBtn.addEventListener('click', addTabs);
  // asegurar que el link inicial cambie el carrito activo
  const initialTab = document.querySelector('#ventasTabs .nav-link');
  if (initialTab) initialTab.addEventListener('click', () => switchToCart('venta1'));
});

function startSistem() {
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

  categories.forEach(category => {
    const catImg = category.imagen ? `assets/img/${category.imagen}` : 'assets/img/categories/default.png';
    const button = document.createElement("button");
    button.className = "categoria-item";
    button.setAttribute("id", category.idCategoria)

    button.innerHTML = `
      <img src="${catImg}" class="categoria-icon" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
      <span class="categoria-nombre">${category.nombre}</span>`;

    button.addEventListener("click", function () {
      container.querySelectorAll(".categoria-item").forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");
      loadProducts(category.idCategoria);
    });

   

    const allButton = container.querySelector(".categoria-item.active");
      allButton.addEventListener("click", function () {
        container.querySelectorAll(".categoria-item").forEach((btn) => {
          btn.classList.remove("active");
        });

      allButton.classList.add("active");
      loadProducts(null);
    });


    container.appendChild(button);
  });
}

function loadProducts(idCategoria = null) {
  let url = "index.php?pg=sales&action=getproducts";
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
      <center><h3>Ventas: <div class="badge bg-primary rounded-circle" id="ventasCount-${id}">0</div></h3></center>
      <div id="productos-carrito-${id}" style="overflow-y: scroll; height: 600px;"></div>
      <div id="total-carrito-${id}"><h4>Total: $<span id="total-${id}">0.00</span></h4></div>
      <button id="btn-procesar-venta-${id}" class="btn btn-primary btn-lg w-100 mb-2">Procesar Venta <i class="fa-solid fa-cash-register"></i></button>
      <button id="btn-agregar-mesa-${id}" class="btn btn-secondary btn-lg">Agregar Mesa <i class="fa-solid fa-utensils"></i></button>
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

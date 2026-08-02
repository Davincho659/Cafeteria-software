// ============================================================================
// PRODUCTS.JS - GESTIÓN DE PRODUCTOS Y CATEGORÍAS
// ============================================================================
// Este módulo gestiona la carga, visualización y filtrado de productos y categorías
// Utiliza el estado global definido en Sales.js (categoriasCache, productosCache, etc.)

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
    console.error("[PRODUCTS] Error obteniendo userId:", error)
    return null
  }
}

function getSessionUserId() {
  const userIdElement = document.querySelector("[data-user-id]")
  return userIdElement ? toInt(userIdElement.getAttribute("data-user-id")) : null
}

// ============================================================================
// FUNCIONES DE GESTIÓN DE PRODUCTOS
// ============================================================================

/**
 * Carga las categorías desde el servidor
 */
async function loadCategories() {
  try {
    const data = await fetchJson("?pg=sales&action=getCategories")
    if (data.success) {
      categoriasCache = data.data
      showCategories(data.data)
    }
  } catch (err) {
    console.error("[PRODUCTS] Error categorías:", err)
  }
}

/**
 * Muestra las categorías en el DOM
 */
function showCategories(categories) {
  const container = getById("categoriasNav")
  if (!container) return
  container.innerHTML = ""

  const allBtn = document.createElement("button")
  allBtn.className = "categoria-item active"
  allBtn.innerHTML = `<img src="assets/img/categories/default.png" class="categoria-icon">
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
    btn.innerHTML = `<img src="${cat.imagen ? "assets/img/categories/" + cat.imagen : "assets/img/categories/default.png"}" 
      class="categoria-icon">
      <span class="categoria-nombre">${cat.nombre}</span>`
    btn.onclick = () => {
      document.querySelectorAll(".categoria-item").forEach((b) => b.classList.remove("active"))
      btn.classList.add("active")
      loadProducts(cat.idCategoria)
    }
    container.appendChild(btn)
  })
}

/**
 * Carga los productos desde el servidor (opcionalmente filtrados por categoría)
 */
async function loadProducts(idCategoria = null) {
  let url = "?pg=sales&action=getProducts"
  if (idCategoria) url += `&idCategory=${idCategoria}`

  try {
    const data = await fetchJson(url)
    if (data.success) {
      // Filtro de seguridad: solo productos activos (estado = 1)
      productosCache = data.data.filter(p => {
        const estado = p.estado !== undefined ? Number(p.estado) : 1
        return estado === 1
      })
      showProducts(productosCache)
    }
  } catch (err) {
    console.error("[PRODUCTS] Error productos:", err)
  }
}

/**
 * Muestra los productos en el DOM
 */
function showProducts(products) {
  const container = getById("productosContainer")
  if (!container) return

  if (products.length === 0) {
    renderEmptyState(container, "No hay productos")
    return
  }

  container.innerHTML = `
      <div class="producto-card manual-card p-3" id="prod-qty-0" onclick="event.stopPropagation(); changeQuantity(0)" role="button">
        <div class="manual-icon"><i class="fa-solid fa-plus"></i></div>
        <div class="d-flex flex-column align-items-center justify-content-center">
          <div class="producto-nombre" style="font-size:18px"><b>Agregar monto manual</b></div>
          <p class="producto-precio" style="margin:6px 0 2px 0"><b>$ 0.00</b></p>
          <div class="manual-hint">Registrar valor sin producto</div>
        </div>
      </div>`
  products.forEach((product) => {
    const img = product.imagen
      ? `assets/img/products/${product.imagen}`
      : "assets/img/products/default.png";

    const btn = document.createElement("button")
    btn.className = "producto-card p-2"
    // Sin ancho fijo: la tarjeta se adapta a la columna del grid. Al fijarlo en
    // 200px por estilo en linea se imponia sobre el width:100% del CSS y, en
    // pantallas donde la columna quedaba mas angosta, las tarjetas se montaban
    // unas sobre otras.
    btn.type = "button"
    btn.innerHTML = `
      <div class="producto-img-container">
        <img src="${img}" alt="${product.nombre}" class="producto-img">
      </div>
      <div class="d-flex flex-column align-items-left">
        <div class="producto-nombre"><b>${product.nombre}</b></div>
        <div class="producto-categoria">${product.categoria}</div>
        <p class="producto-precio"><b>$ ${formatCurrency(product.precioVenta)}</b></p>
        <span class="btn cantidad-display" id="prod-qty-${product.idProducto}" 
              onclick="event.stopPropagation(); changeQuantity(${product.idProducto})" role="button"><strong style="font-size:25px;">+</strong></span>
      </div>`
    btn.onclick = () => addToCart(product)
    container.appendChild(btn)
  })
}

/**
 * Filtra los productos por búsqueda en tiempo real
 */
function filterProductsBySearch(query) {
  const products = document.querySelectorAll(".producto-card")
  const searchText = String(query).toLowerCase().trim()

  products.forEach((card) => {
    const nombreEl = card.querySelector(".producto-nombre")
    const nombre = nombreEl ? nombreEl.textContent.toLowerCase() : ""

    const matches = searchText === "" || nombre.includes(searchText)
    card.style.display = matches ? "" : "none"
  })
}

/**
 * Obtiene el ID del usuario autenticado
 */
async function getUserId() {
  const userIdElement = document.querySelector("[data-user-id]")
  if (userIdElement) return toInt(userIdElement.getAttribute("data-user-id"))

  try {
    const data = await fetchJson("?pg=login&action=checkAuth")
    return data.success && data.authenticated ? data.usuario.id : null
  } catch (error) {
    console.error("[PRODUCTS] Error obteniendo userId:", error)
    return null
  }
}

/**
 * Obtiene el ID del usuario desde la sesión
 */
function getSessionUserId() {
  const userIdElement = document.querySelector("[data-user-id]")
  return userIdElement ? toInt(userIdElement.getAttribute("data-user-id")) : null
}

// ============================================================================
// CALCULADORA DE CANTIDAD
// ============================================================================

const MAX = 200;
const MIN = 1;
let actualProduct = null;
let currentQuantity = '0';

/**
 * Abre la calculadora para cambiar cantidad de un producto
 */
function changeQuantity(idProducto) {
  if (idProducto === 0) {
    openManualAmountModal();
  } else {
    openCalculator(idProducto);
  } 
}

/**
 * Abre el modal de calculadora
 */
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

/**
 * Añade un número a la calculadora
 */
function addNumber(num) {
  const display = getById("calculatorDisplay");
  if (!display) return;
  const digit = String(num).replace(/\D/g, "");
  if (digit.length === 0) return;

  let next = (currentQuantity === "0" || currentQuantity === "") ? digit : (currentQuantity + digit);
  // Limitar a 2 dígitos porque MAX es 99
  if (next.length > 2) next = next.slice(0, 3);

  const val = parseInt(next, 10);
  if (!isNaN(val) && val > MAX) next = String(MAX);

  currentQuantity = next;
  display.textContent = currentQuantity;
}

/**
 * Elimina el último dígito de la calculadora
 */
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

/**
 * Limpia la calculadora
 */
function clearCalculator() {
  currentQuantity = '0';
  const display = getById("calculatorDisplay");
  if (display) display.textContent = currentQuantity;
}

/**
 * Cierra el modal de calculadora
 */
function closeCalculator(event) {
  const el = getById("calculatorOverlay");
  if (!el) return;
  if (!event || event.target.id === "calculatorOverlay") {
    el.classList.remove("active");
    actualProduct = null;
    currentQuantity = '0';
  }
}

/**
 * Confirma la cantidad seleccionada en la calculadora
 */
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

      

      updateCart();
    }
    closeCalculator();
  }
}

function initProducts() {
  loadCategories()
  loadProducts()
}

// ============================================================================
// CALCULADORA DE MONTO MANUAL
// ============================================================================

let currentManualAmount = '0';

/**
 * Abre modal para monto manual
 */
function openManualAmountModal() {
  currentManualAmount = '0';
  const overlay = getById("manualAmountOverlay");
  const display = getById("manualAmountDisplay");
  
  if (overlay && display) {
    display.textContent = '$0';
    overlay.classList.add("active");
  }
}

/**
 * Añade un dígito al monto manual
 */
function addManualDigit(digit) {
  const display = getById("manualAmountDisplay");
  if (!display) return;
  
  const digitStr = String(digit).replace(/\D/g, "");
  if (digitStr.length === 0) return;

  // Si es 0, reemplazar. Si no, concatenar
  if (currentManualAmount === '0') {
    currentManualAmount = digitStr;
  } else {
    currentManualAmount += digitStr;
  }
  
  // Limitar a 8 dígitos (máximo $99,999,999)
  if (currentManualAmount.length > 6) {
    currentManualAmount = currentManualAmount.slice(0, 6);
  }
  
  // Formatear y mostrar
  const amount = parseInt(currentManualAmount, 10) || 0;
  display.textContent = '$' + formatCurrency(amount);
}

/**
 * Elimina el último dígito del monto manual
 */
function deleteManualDigit() {
  const display = getById("manualAmountDisplay");
  if (!display) return;
  
  if (currentManualAmount.length > 1) {
    currentManualAmount = currentManualAmount.slice(0, -1);
  } else {
    currentManualAmount = '0';
  }
  
  const amount = parseInt(currentManualAmount, 10) || 0;
  display.textContent = '$' + formatCurrency(amount);
}

/**
 * Limpia la calculadora de monto manual
 */
function clearManualAmount() {
  currentManualAmount = '0';
  const display = getById("manualAmountDisplay");
  if (display) display.textContent = '$0';
}

/**
 * Cierra el modal de monto manual
 */
function closeManualAmount(event) {
  const el = getById("manualAmountOverlay");
  if (!el) return;
  if (!event || event.target.id === "manualAmountOverlay") {
    el.classList.remove("active");
    currentManualAmount = '0';
  }
}

/**
 * Confirma el monto manual y lo agrega al carrito
 */
function confirmManualAmount() {
  const amount = parseInt(currentManualAmount, 10) || 0;

  if (amount <= 0) {
    alert("Ingresa un monto válido");
    return;
  }
  
  // Agregar como producto especial con idProducto = 0
  let product = ({
    idProducto: null,  
    nombre: "PRODUCTO",
    imagen: null,
    categoria: null,
    precioVenta: amount,
    cantidad: 1,
    precioTotal: amount,
    isManualAmount: true  // Flag para identificar en el carrito
  });
  addToCart(product);

  updateCart();
  closeManualAmount();
}

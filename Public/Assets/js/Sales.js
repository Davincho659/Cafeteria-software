
/* const tabs = document.getElementById("ventasTabs");
const content = document.getElementById("ventasContent");



document.getElementById("nuevaVenta").addEventListener("click", () => {
  let contador = 1;
  contador++;
  const idVenta = "venta" + contador;

  // Crear pestaña
  const nuevaTab = document.createElement("li");
  nuevaTab.classList.add("nav-item");
  nuevaTab.innerHTML = `<a class="nav-link" data-bs-toggle="tab" href="#${idVenta}">Venta ${contador}</a>`;
  tabs.insertBefore(nuevaTab, tabs.lastElementChild);

  // Crear contenido
  const nuevaVenta = document.createElement("div");
  nuevaVenta.classList.add("tab-pane", "fade");
  nuevaVenta.id = idVenta;
  nuevaVenta.innerHTML = `<h5>Venta ${contador}</h5><div id="carrito${contador}"></div>`;
  content.appendChild(nuevaVenta);
}); */


// Cache de datos
let categoriasCache = [];
let productosCache = [];


document.addEventListener("DOMContentLoaded", () => {
  startSistem();
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
                    <span class="categoria-nombre">${category.nombre}</span>
                `

    button.addEventListener("click", function () {
      container.querySelectorAll(".categoria-item").forEach((btn) => {
        btn.classList.remove("active")
      })

      button.classList.add("active")
      loadProducts(category.idCategoria);
    })

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

  if (idCategoria) {
    url += `&idCategory=${idCategoria}`
  }
  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Actualizar cache de productos
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
    .catch(error => {
      console.error('Error al cargar productos:', error);
    });
}

function showProducts(products) {
  const container = document.getElementById("productosContainer");

  if (products.length === 0) {
    container.innerHTML = '<div class="loading">No hay productos disponibles</div>'
    return
  }
  
  container.innerHTML = "";
  products.forEach((product) => {
    const imgPath = product.imagen
      ? `assets/img/${product.imagen}`
      : (product.categoria_imagen ? 
      `assets/img/${product.categoria_imagen}` :
       'assets/img/products/default.jpg');
      
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
                    <div class="producto-categoria">${product.categoria} </div>
          <p class="producto-precio"><b>$ ${new Intl.NumberFormat('es-CO').format(product.precioVenta)}</b></p>
          <span class="btn cantidad-display" id="prod-qty-${product.idProducto}" onclick="event.stopPropagation(); changeQuantity(${product.idProducto})" role="button">${product.cantidad}</span>
                </div>`
  
    button.addEventListener("click", () => {
      addToCart(product);
    });
    
    container.appendChild(button);
  });
}

// Carrito de ventas

const cart = {
    products: [],
    total: 0
};

function addToCart(product) {
  let productId = parseInt(product.idProducto);
  // Usar copia para evitar mutaciones cruzadas con productosCache
  const exist = cart.products.find(p => parseInt(p.idProducto) === productId);
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
    cart.products.push(productCopy);
  }

  // Actualizar sólo la tarjeta afectada en el DOM (sin recargar todo)
  const cantidadBtn = document.getElementById(`prod-qty-${productId}`);
  if (cantidadBtn) {
    const updatedProduct = cart.products.find(p => parseInt(p.idProducto) === productId);
    // actualizar el número mostrado en el botón de cantidad
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
  const vistaTotal = document.getElementById("total");
  const ventasCount = document.getElementById("ventasCount");

  ventasCount.textContent = cart.products.reduce((total, product) => total + product.cantidad, 0);

  const nuevoTotal = cart.products.reduce((acumulador, product) => {
    
    let precioUnitario = parseFloat(product.precioVenta || 0); 
    let cantidad = parseInt(product.cantidad || 1);
    let subtotal = precioUnitario * cantidad;
    
    return acumulador + subtotal;
  }, 0);



  cart.total = new Intl.NumberFormat('es-CO').format(nuevoTotal);
  vistaTotal.textContent = cart.total;

  showCartProducts();
}

function showCartProducts() {
  const products = cart.products;

  const container = document.getElementById("productos-carrito");
  container.innerHTML = "";

  products.forEach((product) => {
    const imgPath = product.imagen
      ? `assets/img/${product.imagen}`
      : (product.categoria_imagen ? 
      `assets/img/${product.categoria_imagen}` :
       'assets/img/products/default.jpg');
    const productDiv = document.createElement("div");
    productDiv.className = "cart-product";

    productDiv.innerHTML = `
                  <div class="row align-items-center">
                      <div class="col-auto">
                          <img src="${imgPath}" alt="imagen" class="product-img">
                      </div>
                      <div class="col">
                          <div class="product-info">
                            <div class="product-title">${product.nombre}</div>
                              <div class="product-actions">
                                  <div class="quantity-control">
                                      <button onclick="decreaseQty(${product.idProducto})">−</button>
                                      <input type="text" id="cart-qty-${product.idProducto}" value="${product.cantidad}" readonly>
                                      <button onclick="increaseQty(${product.idProducto})">+</button>
                                  </div>
                                  <button class="remove-btn" onclick="dropProduct(${product.idProducto})">
                                      <i class="fa-solid fa-trash-can" style="color: #ff0000;"></i>
                                  </button>
                              </div>
                          </div>
                      </div>
                      <div class="col">
                          <div class="price-section">
                              <div class="price">$ ${new Intl.NumberFormat('es-CO').format(product.precioTotal)}</div>
                          </div>
                      </div>
                  </div>`;

    container.appendChild(productDiv);
  });
}

function dropProduct(idProducto) {
    cart.products = cart.products.filter(product => {
        return parseInt(product.idProducto) !== parseInt(idProducto);
    });

    // Reactivar el producto en la lista
  const cantidadBtn = document.getElementById(`prod-qty-${idProducto}`);
    if (cantidadBtn) {
        const productCard = cantidadBtn.closest('.producto-card');
        if (productCard) {
            productCard.classList.remove('disabled');
            const overlay = productCard.querySelector('.added-overlay');
            if (overlay) overlay.remove();
        }
    }

  updateCart();
}

function increaseQty(idProducto) {
    const product = cart.products.find(p => parseInt(p.idProducto) === parseInt(idProducto));
    if (product) {
        product.cantidad += 1;
        product.precioTotal = product.cantidad * parseFloat(product.precioVenta);
        updateCart();
    }
}

function decreaseQty(idProducto) {
  const product = cart.products.find(p =>  parseInt(p.idProducto) === parseInt(idProducto));
  if (product) {
    if (product.cantidad > 1) {
      product.cantidad -= 1;
      product.precioTotal = product.cantidad * parseInt(product.precioVenta)
      updateCart();
    }
  }
}

// Calculadora de productos

const MAX = 99;
const MIN = 1;

let actualProduct = null;
let currentQuantity = '0';

let quantities = {};


function changeQuantity(productId) {
  openCalculator(productId);
}

function openCalculator(productId) {
  actualProduct = productId;
  // Obtener la cantidad actual si existe en el carrito
  const existingProduct = cart.products.find(p => parseInt(p.idProducto) === parseInt(productId));
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
  if (currentQuantity === '0' || currentQuantity === '') {
    newQty = number;
  } else if (currentQuantity.length < 2) {
    newQty = currentQuantity + number;
  } else {
    return; // No añadir más números si ya hay 2 dígitos
  }
  
  // Validar el rango
  const numericQty = parseInt(newQty);
  if (numericQty >= MIN && numericQty <= MAX) {
    currentQuantity = newQty;
  }
  
  document.getElementById("calculatorDisplay").textContent = currentQuantity;
}

function deleteLast() {
  currentQuantity = currentQuantity.slice(0,-1)
  document.getElementById("calculatorDisplay").textContent = currentQuantity || '0';
}

function clearCalculator() {
  currentQuantity = '0';
  document.getElementById("calculatorDisplay").textContent = currentQuantity || '0';
}

function confirmQuantity() {
  let qty = parseInt(currentQuantity);
  if (isNaN(qty) || qty < MIN || qty > MAX) {
    qty = 1;
  }
  
  if (actualProduct !== null) {
    const product = productosCache.find(p => parseInt(p.idProducto) === parseInt(actualProduct));
    
    if (product) {
      // Crear una copia limpia del producto
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
      
      // Actualizar o agregar al carrito
      const existingProduct = cart.products.find(p => parseInt(p.idProducto) === parseInt(actualProduct));
      if (existingProduct) {
        existingProduct.cantidad = qty;
        existingProduct.precioTotal = qty * existingProduct.precioVenta;
      } else {
        cart.products.push(productToAdd);
      }
      
      updateCart();
    }
    closeCalculator();
  }
}

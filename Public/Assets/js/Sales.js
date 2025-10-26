
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
let categoriasCache = "";

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
    const button = document.createElement("button");
    button.className = "categoria-item";
    button.setAttribute("id", category.idCategoria)

    button.innerHTML = `
                    <span class="categoria-icon">📦</span>
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
  console.log(url)
  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showProducts(data.data);
        console.log(data)
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
  let html = "";
  container.innerHTML = "";

  products.forEach((product) => {
    html += `<div class="card m-2 producto-card" style="width: 180px; height: 265px;">
                <img src="assets/img/tinto.jpg" class="card-img-top cards-img" >
                <div class=" ps-2 pt-2 d-flex flex-wrap js-products">
                    <div class="producto-categoria">${product.categoria}</div></br>
                    <div class="producto-nombre">${product.nombre}</div>
                    <p class="card-text "><b>${product.precioVenta}</b></p>
                </div>
            </div>`
  })
  container.innerHTML = html;
}

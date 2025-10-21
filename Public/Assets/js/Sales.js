let contador = 1;
const tabs = document.getElementById("ventasTabs");
const content = document.getElementById("ventasContent");

document.getElementById("nuevaVenta").addEventListener("click", () => {
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
});


document.addEventListener("DOMContentLoaded", () => {
  iniciarSistema();
});

function iniciarSistema() {
  cargarCategorias();

}

function cargarCategorias() {
  const contenedor = document.getElementById("lista-categorias");
  contenedor.innerHTML = "<div class='loading'>Cargando categorías...</div>";

  fetch('/sales/getAllCategories')
    .then(response => response.json())
    .then(data => {
      console.log(data);
    })
    .catch(error => {
      console.error('Error al cargar categorías:', error);
    });
}

<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/sales.css">

<!-- </div> 
<div class="container">
  <ul class="nav nav-tabs" id="ventasTabs">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#venta1">Venta 1</a>    
    </li>
    <li class="nav-item">
      <button id="nuevaVenta" class="btn btn-success btn-sm ms-2">+</button>
    </li>
  </ul>
</div> -->
<div class="row d-flex" style="height: 600px;">
    <div class="col-2 border-end overflow-auto mt-3" id="categorias">
        <h2>Categorías</h2>
        <nav class="categorias-nav" id="categoriasNav">
            <button class="categoria-item active" id="" >
                <span class="categoria-icon">📦</span>
                <span class="categoria-nombre">Todos los Productos</span>
            </button>
            <!-- Las categorías se cargan dinámicamente aquí -->
        </nav>
    </div>
    <div class="col-7" style="height: 90vh; overflow-y: scroll;" id="productos">
        <div class="input-group mb-3 mt-3">
            <h4>Productos</h4>
            <input type="text" class="form-control ms-4"  placeholder="Buscar" id="search" >
            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
            <span class="input-group-text" id="basic-addon1"><i class="fa-solid fa-magnifying-glass"></i></span></button>
        </div>
        <div class="d-flex flex-wrap js-products">  <!--Contenedor de productos -->
            <div id="errorContainer"></div>

            <div id="productosContainer" class="productos-grid">
                <div class="loading">Cargando productos...</div>
            </div>
        </div>
    </div>
    <div class="col-3 border-start bg-light overflow-auto" id="carrito">
        <div><center>
            <h4>Ventas
            <div class="badge bg-primary rounded-circle">3</div></h4>
        </center></div>
        <table class="table table-hover table-striped">
            <tr>
                <th class="hola">Imagen</th>
                <th>Nombre</th>
                <th>Cantidad</th>
                <th>Precio</th>
            </tr>
            <tr>
                <td><img src="assets/img/tinto.jpg" class="img-fluid" style="width: 70px; height: 70px;"></td>
                <td>Torta de pescado</td>
                <td>
                    <div class="input-group flex-nowrap " style="width: 100px;">
                        <span class="input-group-text" id="addon-wrapping">-</span>
                        <input type="text" class="form-control" value="1">
                        <span class="input-group-text" id="addon-wrapping">+</span>
                    </div>
                </td>
                <td>$ 5.000</td>
            </tr>
        </table>
    </div>
  </div>

  <script src="assets/js/sales.js"></script>
<?php require loadView('Layouts/Footer'); ?>

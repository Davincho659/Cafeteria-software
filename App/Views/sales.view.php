<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/sales.css">


 <!--<div class="d-flex row-12">
    <div class="col-8 p-4 pt-2" style="min-height:550px; overflow-y:scroll;">
        <div class="container-fluid shadow-sm text-center p-2">
            <h4>Categorias</h4>
        </div>
    </div>
    <div class="col-4 p-4 pt-2" style="min-height:550px; overflow-y:scroll;">
        <div><center>
            <h4>Ventas
            <div class="badge bg-primary rounded-circle">3</div></h4>
        </center></div>
        <table class="table table-hover table-striped">
            <tr>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Precio</th>
            </tr>
        </table>
    </div>-->

</div> 
<div class="container">
  <ul class="nav nav-tabs" id="ventasTabs">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#venta1">Venta 1</a>    
    </li>
    <li class="nav-item">
      <button id="nuevaVenta" class="btn btn-success btn-sm ms-2">+</button>
    </li>
  </ul>
</div>
<div class="row d-flex" style="height: 600px;">
    <div class="col-1 border-end overflow-auto mt-3" id="categorias">
        <h6>Categorias</h6>
        <div id="lista-categorias">
            
        </div>
    </div>
    <div class="col-8" style="height: 90vh; overflow-y: scroll;" id="productos">
        <div class="input-group mb-3 mt-3">
            <h4>Productos</h4>
            <input type="text" class="form-control ms-4"  placeholder="Buscar" id="search" >
            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
            <span class="input-group-text" id="basic-addon1"><i class="fa-solid fa-magnifying-glass"></i></span></button>
        </div>
        <div class="d-flex flex-wrap js-products">  <!--Contenedor de productos -->
            <a href="#">
            <div class="card m-2" style="width: 180px; height: 265px;">
                <img src="assets/img/tinto.jpg" class="card-img-top cards-img" >
                <div class="card-body-bottom ps-2 pt-2">
                    <h6 class="card-tittle text-muted">Torta de pescado</h6>
                    <p class="card-text "><b>$ 5.000</b></p>
                </div>
            </div></a>
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

<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/sales.css">

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
    <div class="col-2 border-end overflow-auto mt-3" id="categorias">
        <h2>Categorías</h2>
        <nav class="categorias-nav" id="categoriasNav">
            <button class="categoria-item active" id="" >
                <img src="assets/img/categories/default.png" class="categoria-icon" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
                <span class="categoria-nombre">Todos los Productos</span>
            </button>
            <!-- Las categorías se cargan dinámicamente aquí -->
        </nav>
    </div>
    <div class="col-7" style="height: 80vh; overflow-y: scroll;" id="productos">
        <div class="input-group mb-3 mt-3">
            <h4 id="prueba">Productos</h4>
            <input type="text" class="form-control ms-4"  placeholder="Buscar" id="search" >
            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
            <span class="input-group-text" id="basic-addon1"><i class="fa-solid fa-magnifying-glass"></i></span></button>
        </div>
        <div class="d-flex "> 
            <div id="productosContainer" class="productos-grid">
                <div class="loading">Cargando productos...</div>
            </div>
        </div>
    </div>
    <div id="calculatorOverlay" class="calculator-overlay" onclick="closeCalculator(event)">
        <div class="calculator-popup" onclick="event.stopPropagation()">
            <div class="calculator-display" id="calculatorDisplay">0</div>
            <div class="calculator-grid">
                <button class="calc-btn" onclick="addNumber('1')">1</button>
                <button class="calc-btn" onclick="addNumber('2')">2</button>
                <button class="calc-btn" onclick="addNumber('3')">3</button>
                <button class="calc-btn" onclick="addNumber('4')">4</button>
                <button class="calc-btn" onclick="addNumber('5')">5</button>
                <button class="calc-btn" onclick="addNumber('6')">6</button>
                <button class="calc-btn" onclick="addNumber('7')">7</button>
                <button class="calc-btn" onclick="addNumber('8')">8</button>
                <button class="calc-btn" onclick="addNumber('9')">9</button>
                <button class="calc-btn zero" onclick="addNumber('0')">0</button>
                <button class="calc-btn" onclick="deleteLast()"><i class="fa-solid fa-left-long fa-lg"></i></button>
            </div>
            <div class="calc-actions">
                <button class="calc-action-btn borrar" onclick="clearCalculator()">Borrar</button>
                <button class="calc-action-btn cancelar" onclick="closeCalculator()">Cancelar</button>
                <button class="calc-action-btn confirmar" onclick="confirmQuantity()">OK</button>
            </div>
        </div>
    </div>
    <div class="col-3 border-start bg-light " id="carrito">
        <div id="carrito">
            <center><h3>Ventas: <div class="badge bg-primary rounded-circle" id="ventasCount">0</div></h3></center>
            <div id="productos-carrito" style="overflow-y: scroll; height: 600px;"></div>
            <div id="total-carrito">
                <h4>Total: $<span id="total">0.00</span></h4>
            </div>
            <button id="btn-procesar-venta" class="btn btn-primary btn-lg w-100 mb-2">
                Procesar Venta <i class="fa-solid fa-cash-register"></i>
            </button>
            <button id="btn-procesar-venta" class="btn btn-secondary btn-lg">
                Agregar Mesa <i class="fa-solid fa-utensils"></i>
            </button>
        </div>
    </div>
  </div>

  <script src="assets/js/sales.js"></script>
<?php require loadView('Layouts/Footer'); ?>

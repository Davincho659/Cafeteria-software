<?php require loadView('Layouts/header'); ?>
<link rel="stylesheet" href="assets/css/sales.css">

<div class="d-flex inline-block tab-container">
    <ul class="nav nav-tabs" id="ventasTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#venta1">Venta 1</a>
        </li>
        <!-- botón fijo para agregar nueva pestaña; siempre debe quedar al final -->
        <li class="nav-item" id="addTabItem">
            <i id="nuevaVenta" class="fa-solid fa-plus fa-2xl" style="color: #26c418ff; padding-top: 20px; padding-left: 15px;"></i>
        </li>
    </ul>
</div>


<div class="container-fluid px-2">
  <div class="row d-flex" style="min-height: calc(100vh - 120px);">
        <div class="col-auto border-end overflow-auto mt-3" id="categorias">
            <h2>Categorías</h2>
            <nav class="categorias-nav" id="categoriasNav">
                <button class="categoria-item active" id="0" >
                    <img src="assets/img/categories/default.png" class="categoria-icon" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:6px">
                    <span class="categoria-nombre">Todos los Productos</span>
                </button>
                <!-- Las categorías se cargan dinámicamente aquí -->
            </nav>
        </div>
        <div class="col" style="min-height: calc(100vh - 140px); overflow-y: auto;" id="productos">
            <div class="input-group mb-3 mt-3">
                <h4 id="prueba">Productos</h4>
                <input type="text" class="form-control ms-4"  placeholder="Buscar" id="search" >
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
                <span class="input-group-text" id="basic-addon1"><i class="fa-solid fa-magnifying-glass"></i></span></button>
            </div>
            <div class="d-flex "> 
                <div id="productosContainer" class="productos-grid">
                    <h5>cargando productos...</h5>
                    <!-- <button class="m-2 producto-card p-2" style="width: 200px; height: 300px;" oneclick="openCalculator()">
                        <div class="producto-img-container">
                            <img src="assets/img/products/producto.png" alt="Empanada" class="producto-img" onerror="this.src='/placeholder.svg?height=140&amp;width=220'">
                        </div>
                        <div class="d-flex flex-column align-items-left">
                            <div class="producto-nombre"><b>Producto</b></div>
                            <br>
                            <p class="producto-precio"><b>$ ?</b></p>
                            <span class="btn cantidad-display" id="prod-qty-1" onclick="event.stopPropagation(); changeQuantity(1)" role="button">1</span>
                        </div>
                    </button> -->
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
                <div class="calc-actions" >
                    <button class="calc-action-btn borrar" onclick="clearCalculator()">Borrar</button>
                    <button class="calc-action-btn cancelar" onclick="closeCalculator()">Cancelar</button>
                    <button class="calc-action-btn confirmar" onclick="confirmQuantity()">OK</button>
                </div>
            </div>
        </div>
        <div id="tableOverlay" class="table-overlay" onclick="closeTable(event)">
            <div class="table-popup" onclick="event.stopPropagation()">
                <h2>Mesas</h2>
                <i onclick="closeTable()" class="fa-solid fa-circle-xmark fa-xl" style="color: #ff0000; margin-left: 8px;"></i>
                <div class="tableContainer" id="tableContainer">

                </div>
            </div>
        </div>
        <div class="col-auto border-start bg-light ps-1" style="min-width: 320px;" >
            <!-- Carritos por pestaña -->
            <div id="ventasContent" class="tab-content">
                <div class="tab-pane fade show active" id="venta1">
                    <div id="carrito-venta1">
                        <center><h3>Ventas: <div class="badge bg-primary rounded-circle" id="ventasCount-venta1">0</div></h3></center>
                        <div id="productos-carrito-venta1" style="overflow-y: scroll; height: 650px;"></div>
                        <div id="total-carrito-venta1">
                            <h4>Total: $<span id="total-venta1">0.00</span></h4>
                        </div>
                        <button id="btn-procesar-venta-venta1" class="btn btn-primary btn-lg w-100 mb-2">
                            Procesar Venta <i class="fa-solid fa-cash-register"></i>
                        </button>
                        <button id="btn-agregar-mesa-venta1" class="btn btn-secondary btn-lg" onclick="event.stopPropagation(); loadTables()" role="button">
                            Agregar Mesa <i class="fa-solid fa-utensils"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


  <script src="assets/js/sales.js"></script>
<?php require loadView('Layouts/Footer'); ?>

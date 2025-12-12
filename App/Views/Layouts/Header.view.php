<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=esc(APP_NAME)?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme-safe.css">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <!-- Dropzone (CDN) 
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>-->
    
</head>
<body class="use-theme">
    <header>
        <?php if ($_GET['pg'] == 'home' || $_GET['pg'] == 'adminHome'): ?>
            <nav class="navbar navbar-expand-lg cafe-navbar" style="min-width:350px;">
                <div class="container-fluid">
                    <a class="navbar-brand brand-badge" href="index.php?pg=<?php echo $backPage; ?>">
                        <img src="assets/img/logo.jpg" alt="Logo" class="cafe-logo me-2">
                        <div>
                            <div class="name"><?=esc(APP_NAME)?></div>
                            <div class="tagline text-muted">La Casa del Pastel</div>
                        </div>
                    </a>
                    <?php
                        $backPage = 'home';
                        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                            $backPage = 'adminHome';
                        }
                    ?>
                    
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item me-2">
                                    <a class="nav-link cafe-navlink" href="index.php?pg=sales">Ventas</a>
                                </li>
                                <li class="nav-item me-3">
                                    <a class="nav-link cafe-navlink" href="index.php?pg=inventory">Inventario</a>
                                </li>
                                <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                                <li class="nav-item me-3">
                                    <a class="nav-link" href="index.php?pg=admin">Administración</a>
                                </li>
                                <?php endif; ?>
                                <li class="nav-item me-3">
                                    <a class="nav-link" href="index.php?pg=sales">Reportes</a>
                                </li>
                                <li class="nav-item mt-2">
                                    <span class="navbar-text me-3 ">
                                        Bienvenido, <strong><?=esc($_SESSION['username'])?></strong>
                                    </span>
                                </li>
                                <li class="nav-item me-3">
                                    <a class="btn btn-outline-cafe" href="index.php?pg=login&action=logout">
                                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="btn btn-outline-cafe" href="index.php?pg=login">
                                        <i class="fas fa-sign-in-alt"></i> Ingresar
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>
        <?php elseif($_GET['pg'] == 'sales'): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <?php
                    
                    $backPage = 'home';
                    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                        $backPage = 'adminHome';
                    }
                ?>
                <a href="index.php?pg=<?php echo $backPage; ?>"><button class="btn btn-secondary ms-2"><i class="fa-solid fa-chevron-left"></i></button></a>
                <a class="btn btn-primary ms-5 " href="index.php?pg=mesas">Ver mesas <i class="fa-solid fa-utensils"></i></a>
                <a id="openDayBillsBtn" class="btn btn-success ms-3" href="index.php?pg=reports&action=dayBills" >Ver facturas<i class="fa-solid fa-plus"></i></a>
            </nav>
            <!-- Facturas del dia -->
            <div class="container-fluid" style="min-width:350px;">
                <div id="dayBillsOverlay" class="table-overlay" onclick="closeDayBillsOverlay(event)">
                    <div class="table-popup" onclick="event.stopPropagation();" style="max-width:1000px; width:95%; height:90%; position:relative; padding:8px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <h2 style="margin:0">Facturas</h2>
                            <i onclick="closeDayBillsOverlay()" class="fa-solid fa-circle-xmark fa-xl" style="color: #ff0000; cursor:pointer"></i>
                        </div>
                        <div id="dayBillsContent" style="flex:1; height:95%; overflow:auto; background:#fff; border-radius:6px; padding:12px;">
                            <div id="dayBillsLoading" style="display:flex;align-items:center;justify-content:center;height:100%;">
                                Cargando...
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                (function(){
                    function qs(id){ return document.getElementById(id); }
                    document.addEventListener('DOMContentLoaded', function(){
                        var btn = qs('openDayBillsBtn');
                        var overlay = qs('dayBillsOverlay');
                        var content = qs('dayBillsContent');
                        var loading = qs('dayBillsLoading');

                        if(!btn || !overlay || !content) return;

                        btn.addEventListener('click', function(e){
                            e.preventDefault();
                            var src = btn.getAttribute('data-src') || btn.getAttribute('href') || 'index.php?pg=dayBills';
                            // pedir versión para inyección (sin header/footer)
                            if (src.indexOf('ajax=1') === -1) {
                                src = src + (src.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
                            }

                            overlay.classList.add('active');
                            // mostrar loader
                            if(loading) loading.style.display = 'flex';
                            content.scrollTop = 0;

                            fetch(src, { credentials: 'same-origin' })
                                .then(function(res){
                                    if(!res.ok) throw new Error('Error cargando datos');
                                    return res.text();
                                })
                                .then(function(html){
                                    if(loading) loading.style.display = 'none';
                                    content.innerHTML = html;
                                })
                                .catch(function(err){
                                    if(loading) loading.style.display = 'none';
                                    content.innerHTML = '<div class="text-center text-danger">Error cargando facturas. Intente de nuevo.</div>';
                                    console.error(err);
                                });

                            document.addEventListener('keydown', escHandler);
                        });

                        window.closeDayBillsOverlay = function(event){
                            if(!overlay) return;
                            if(!event || (event && event.target && event.target.id === 'dayBillsOverlay')){
                                overlay.classList.remove('active');
                                // limpiar contenido para liberar memoria y evitar duplicados
                                try{ content.innerHTML = '<div id="dayBillsLoading" style="display:flex;align-items:center;justify-content:center;height:100%;">Cargando...</div>'; }catch(e){}
                                document.removeEventListener('keydown', escHandler);
                            }
                        };

                        function escHandler(e){
                            if(e.key === 'Escape' || e.key === 'Esc'){
                                closeDayBillsOverlay();
                            }
                        }
                    });
                })();
                </script>
        <?php elseif($_GET['pg'] == 'mesas'): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <?php
                    
                    $backPage = 'sales';
                ?>
                <a href="index.php?pg=<?php echo $backPage; ?>"><button class="btn btn-secondary ms-2"><i class="fa-solid fa-chevron-left"></i></button></a>
            </nav>
        <?php elseif($_GET['pg'] == 'admin'): ?>
            <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <?php
                    
                    $backPage = 'home';
                    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
                        $backPage = 'adminHome';
                    }
                ?>
                <a href="index.php?pg=<?php echo $backPage; ?>"><button class="btn btn-secondary ms-2"><i class="fa-solid fa-chevron-left"></i></button></a>
            </nav>
        <?php endif; ?>
    </header>
</body>
</html>



    
        
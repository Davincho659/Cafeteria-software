<div class="glass-header">
    <nav class="navbar navbar-expand-lg bg-body-tertiary" style="min-width:350px;">
                <div class="container-fluid glass-header">
                    <img src="assets/img/coffee.jpg" alt="Bootstrap" width="40" height="34">
                    <a class="navbar-brand" href="Menu"><?=esc(APP_NAME)?></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll" aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarScroll">
                    <ul class="navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll" style="--bs-scroll-height: 100px;">
                        <li class="nav-item">
                        <a class="nav-link" href="index.php?pg=sales">Ventas</a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php?pg=inventory">Inventario</a>
                        </li>
                        <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="index.php?pg=admin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Administración
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?pg=logout">Cerrar sesión</a></li>
                            <li><a class="dropdown-item" href="index.php?pg=settings">Configuración de perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?pg=sales">Reportes</a>
                        </li>
                        
                    </ul>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?pg=login">Login</a>
                        </li>
                    </div>
                </div>
            </nav>
</div>


<?php require loadView('Layouts/header'); ?>

<div class="container-fluid border col-lg-4 col-md-6 mt-5 p-5 shadow-lg">
    <form method="POST">
        <center>
            <h4><i class="fa-solid fa-user fa-2xl"></i></h4>
            <h3>Iniciar Sesión</h3>
        </center>
        <br>
        
        <div class="mb-3">
            <label for="exampleInputEmail1" class="form-label">Usuario</label>
            <input type="text" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        </div>
        <div class="mb-3">
            <label for="exampleInputPassword1" class="form-label">Pin</label>
            <input type="password" class="form-control" id="pin" autofocus>
        </div>
        <div class="row" >
            <button type="submit" class="btn btn-primary" style=" min-width:100px;font-size:18px;">Entrar</button>
        </div>
    </form>
</div>


<?php require loadView('Layouts/Footer'); ?>
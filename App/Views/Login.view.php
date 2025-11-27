<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=esc(APP_NAME)?></title>
    <link rel="stylesheet" href="assets/css/theme-safe.css">
    <link rel="stylesheet" href="assets/css/Login.css">
    <title>Login por roles</title>
</head>

<body class="use-theme">
    <div class="wrapper">
        <div class="title">Inicia sesion</div>
            <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="index.php?pg=login&action=login" method="POST">
            <div class="field">
                <input type="text" required name="username">
                <label>Nombre de usuario</label>
            </div>
            <div class="field">
                <input type="password" required name="pin" >
                <label>Pin</label>
            </div>
            <div class="content">
                <div class="pass-link"><a href="#">Olvido su Pin?</a></div>
            </div>
            <div class="field">
                <input type="submit" value="Ingresar">
            </div>
            <div class="signup-link"><a href="Registrarse.php">Registrarse Ahora</a></div>
        </form>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Preload recursos críticos -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="assets/css/pos-theme.css" as="style">
    
    <link rel="stylesheet" href="assets/css/pos-theme.css">
    <link rel="stylesheet" href="assets/css/Login.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
</head>
<body>

<body class="use-theme">
    <div class="wrapper">
        <div class="title">Inicia sesion</div>
                    <!-- Alerta de error -->
                    <div id="alertContainer"></div>
                    
                    <form id="loginForm">
                        <div class="field">
                            <input type="text" required name="nombre" id="nombre" >
                            <label>Nombre de usuario</label>
                        </div>
                        <div class="field">
                            <input type="password" required name="pin" id="pin" maxlength="6" >
                            <label>Pin</label>
                            <small class="text-muted">Solo números (4-6 dígitos)</small>
                        </div>
                        <br><br>
                        <div class="field">
                            <input type="submit" value="Ingresar" id="btnLogin">
                        </div>
                        <div class="text-center">
                        <small class="text-muted">
                            <i class="fa-solid fa-shield-halved"></i> 
                            Acceso seguro al sistema
                        </small>
                    </div>
                    </form>
        </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btnLogin = document.getElementById('btnLogin');
    const originalText = btnLogin.innerHTML;
    console.log('Botón antes de deshabilitar:', btnLogin);
    // Deshabilitar botón y mostrar loading
    btnLogin.disabled = true;
    btnLogin.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Iniciando...';
    
    // Limpiar alertas previas
    document.getElementById('alertContainer').innerHTML = '';
    
    const formData = {
        nombre: document.getElementById('nombre').value.trim(),
        pin: document.getElementById('pin').value.trim()
    };
    
    try {
        const response = await fetch('?pg=login&action=authenticate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Éxito - mostrar mensaje y redirigir
            showAlert('Inicio de sesión exitoso. Redirigiendo...', 'success');
            
            setTimeout(() => {
                window.location.href = '?pg=home';
            }, 1000);
        } else {
            // Error
            showAlert(data.error || 'Error al iniciar sesión', 'danger');
            btnLogin.disabled = false;
            btnLogin.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Intenta nuevamente.', 'danger');
        btnLogin.disabled = false;
        btnLogin.innerHTML = originalText;
    }
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.getElementById('alertContainer').appendChild(alertDiv);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Permitir solo números en el campo PIN
document.getElementById('pin').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

</body>
</html>
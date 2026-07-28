<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Token CSRF: esta pantalla también hace peticiones que modifican datos
         (abrir la caja justo después de entrar), así que necesita el token.
         El token sobrevive a session_regenerate_id() del login. -->
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/pos-theme.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/Login.css') ?>">
    
    <title>Login - Sistema POS</title>
</head>
<body class="use-theme">
    <div class="wrapper">
        <div class="title">Inicia sesión</div>
        
        <!-- Alerta de error -->
        <div id="alertContainer"></div>
        
        <form id="loginForm">
            <div class="field">
                <input type="text" required name="nombre" id="nombre">
                <label>Nombre de usuario</label>
            </div>
            <div class="field">
                <input type="password" required name="pin" id="pin" maxlength="6">
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

    <!-- Modal para abrir caja -->
    <div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-labelledby="modalAbrirCajaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalAbrirCajaLabel">
                        <i class="fa-solid fa-cash-register"></i> Apertura de Caja
                    </h5>
                </div>
                <form id="formAbrirCaja">
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            Ingresa el monto inicial de la caja para iniciar operaciones.
                        </p>
                        <div class="mb-3">
                            <label for="montoInicial" class="form-label">Monto Inicial</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="montoInicial" 
                                       name="montoInicial" placeholder="0" 
                                       required autofocus>
                            </div>
                            <small class="form-text text-muted">Formato: 1.000.000</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="btnCancelarCaja">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnAbrirCaja">
                            <i class="fa-solid fa-lock-open"></i> Abrir Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <!-- Wrapper global de fetch: agrega el token CSRF a los POST (abrir caja). -->
    <script src="<?= asset('assets/js/auth-helper.js') ?>"></script>

    <script>
        // Variables globales
        let modalCaja = null;

        // ============ LOGIN FORM ============
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnLogin = document.getElementById('btnLogin');
            const originalText = btnLogin.innerHTML;
            
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
                    // Éxito - verificar si hay caja abierta
                    if (!data.cajaAbierta) {
                        // No hay caja abierta - mostrar modal para abrir
                        showAlert('✅ Login exitoso. Por favor, abre la caja para continuar.', 'success');
                        setTimeout(() => {
                            abrirModalCaja();
                        }, 500);
                        btnLogin.disabled = false;
                        btnLogin.innerHTML = originalText;
                    } else {
                        // Caja ya abierta - redirigir al home
                        showAlert('✅ Inicio de sesión exitoso. Redirigiendo...', 'success');
                        setTimeout(() => {
                            window.location.href = '?pg=home';
                        }, 1000);
                    }
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

        // ============ APERTURA DE CAJA ============
        document.getElementById('formAbrirCaja').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnAbrirCaja = document.getElementById('btnAbrirCaja');
            const originalText = btnAbrirCaja.innerHTML;
            const montoFormateado = document.getElementById('montoInicial').value.trim();
            
            // Convertir el monto formateado a número (eliminar puntos)
            const monto = montoFormateado.replace(/\./g, '');
            
            // Validar monto
            if (!monto || parseFloat(monto) < 0) {
                showAlert('Ingresa un monto válido', 'warning');
                return;
            }
            
            // Deshabilitar botón mientras se procesa
            btnAbrirCaja.disabled = true;
            btnAbrirCaja.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Abriendo...';
            
            try {
                const response = await fetch('?pg=cash&action=open', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        saldoInicial: parseFloat(monto),
                        notas: 'Apertura de caja en login - Saldo inicial: $' + monto
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('✅ Caja abierta correctamente con $' + monto, 'success');
                    cerrarModalCaja();
                    
                    // Redirigir al home después de 1 segundo
                    setTimeout(() => {
                        window.location.href = '?pg=home';
                    }, 1000);
                } else {
                    showAlert('❌ ' + (data.error || 'Error al abrir la caja'), 'danger');
                    btnAbrirCaja.disabled = false;
                    btnAbrirCaja.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Error:', err);
                showAlert('❌ Error en la conexión: ' + err.message, 'danger');
                btnAbrirCaja.disabled = false;
                btnAbrirCaja.innerHTML = originalText;
            }
        });

        // ============ FUNCIONES UTILIDAD ============
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

        function abrirModalCaja() {
            if (!modalCaja) {
                modalCaja = new bootstrap.Modal(document.getElementById('modalAbrirCaja'), {
                    backdrop: 'static',
                    keyboard: false
                });
            }
            modalCaja.show();
        }

        function cerrarModalCaja() {
            if (modalCaja) {
                modalCaja.hide();
            }
        }

        // Permitir solo números en el campo PIN
        document.getElementById('pin').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Formatear monto inicial con separadores de miles
        document.getElementById('montoInicial').addEventListener('input', function(e) {
            // Obtener solo números
            let value = this.value.replace(/\D/g, '');
            
            // Limitar a 10 dígitos (máximo 99.999.999)
            if (value.length > 6) {
                value = value.slice(0, 6);
            }
            
            // Si está vacío, no hacer nada
            if (value === '') {
                this.value = '';
                return;
            }
            
            // Convertir a número y formatear con separadores de miles
            let num = parseInt(value, 10);
            this.value = num.toLocaleString('es-CO');
        });

        // Botón cancelar modal
        document.getElementById('btnCancelarCaja').addEventListener('click', function() {
            // Permitir cancelar y volver a intentar login
            cerrarModalCaja();
            document.getElementById('loginForm').reset();
            document.getElementById('btnLogin').disabled = false;
            document.getElementById('btnLogin').innerHTML = 'Ingresar';
        });
    </script>
</body>
</html>
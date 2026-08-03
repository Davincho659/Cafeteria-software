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
            <!-- PIN con teclado propio.
                 readonly + inputmode="none" impiden que Windows abra su teclado
                 táctil: además de tapar media pantalla, muestra en grande la
                 tecla pulsada, y el PIN es personal de cada empleado.
                 El valor lo escribe el teclado de abajo. -->
            <div class="field">
                <input type="password" required name="pin" id="pin" maxlength="6"
                       readonly inputmode="none" autocomplete="off"
                       aria-label="PIN de acceso">
                <label>Pin</label>
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

    <!-- ================================================================
         TECLADO DEL PIN
         ================================================================
         Se abre al tocar el campo del PIN. Va en un modal y no bajo el
         campo para que el PIN se marque sobre un fondo neutro, lejos del
         resto del formulario, y se pueda cerrar al terminar.
         ================================================================ -->
    <div class="modal fade" id="modalPin" tabindex="-1" aria-labelledby="modalPinLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content pin-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPinLabel">
                        <i class="fa-solid fa-lock"></i> Ingresa tu PIN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <!-- Puntos: dicen cuántos dígitos van, nunca cuáles -->
                    <div class="pin-dots" id="pinDots" aria-hidden="true">
                        <span></span><span></span><span></span><span></span><span></span><span></span>
                    </div>

                    <div class="pin-pad" id="pinPad">
                        <button type="button" class="pin-key" data-num="1">1</button>
                        <button type="button" class="pin-key" data-num="2">2</button>
                        <button type="button" class="pin-key" data-num="3">3</button>
                        <button type="button" class="pin-key" data-num="4">4</button>
                        <button type="button" class="pin-key" data-num="5">5</button>
                        <button type="button" class="pin-key" data-num="6">6</button>
                        <button type="button" class="pin-key" data-num="7">7</button>
                        <button type="button" class="pin-key" data-num="8">8</button>
                        <button type="button" class="pin-key" data-num="9">9</button>
                        <button type="button" class="pin-key pin-key-accion" id="pinBorrarTodo" title="Borrar todo">C</button>
                        <button type="button" class="pin-key" data-num="0">0</button>
                        <button type="button" class="pin-key pin-key-accion" id="pinBorrar" title="Borrar un dígito">
                            <i class="fa-solid fa-delete-left"></i>
                        </button>
                    </div>

                    <button type="button" class="btn pin-listo w-100" id="pinListo">
                        <i class="fa-solid fa-check"></i> Listo
                    </button>

                    <label class="pin-mezclar">
                        <input type="checkbox" id="pinMezclar">
                        <span>Mezclar teclas (más discreto)</span>
                    </label>
                </div>
            </div>
        </div>
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
                    <div class="modal-footer d-flex justify-content-between">
                        <!-- Se puede entrar sin abrir caja: hay tareas que no
                             implican cobrar (cargar productos, ver reportes).
                             La caja se abre despues desde el menu principal. -->
                        <button type="button" class="btn btn-link text-secondary text-decoration-none px-0" id="btnEntrarSinCaja">
                            Entrar sin abrir caja
                        </button>
                        <div>
                            <button type="button" class="btn btn-secondary" id="btnCancelarCaja">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnAbrirCaja">
                                <i class="fa-solid fa-lock-open"></i> Abrir Caja
                            </button>
                        </div>
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

        // ====================================================================
        // TECLADO NUMERICO DEL PIN
        // ====================================================================
        // En la caja no hay teclado fisico. El de Windows tapa media pantalla y
        // muestra en grande la tecla que se pulsa, asi que cualquiera que este
        // enfrente ve el PIN. Este teclado vive dentro de la pagina: teclas
        // grandes para el dedo y sin delatar lo que se marca.
        (function () {
            const campoPin  = document.getElementById('pin');
            const pad       = document.getElementById('pinPad');
            const dots      = document.getElementById('pinDots');
            const mezclar   = document.getElementById('pinMezclar');
            if (!campoPin || !pad) return;

            const MAX = 6;

            /** Un punto por digito: dice cuantos van, no cuales. */
            function pintarPuntos() {
                if (!dots) return;
                const n = campoPin.value.length;
                [...dots.children].forEach((p, i) => p.classList.toggle('lleno', i < n));
            }

            function agregarDigito(d) {
                if (campoPin.value.length >= MAX) return;
                campoPin.value += d;
                pintarPuntos();
            }

            function borrarUno() {
                campoPin.value = campoPin.value.slice(0, -1);
                pintarPuntos();
            }

            function borrarTodo() {
                campoPin.value = '';
                pintarPuntos();
            }
            window.limpiarPin = borrarTodo;

            pad.addEventListener('click', function (e) {
                const tecla = e.target.closest('.pin-key');
                if (!tecla) return;

                if (tecla.id === 'pinBorrar')     { borrarUno();  return; }
                if (tecla.id === 'pinBorrarTodo') { borrarTodo(); return; }

                const d = tecla.getAttribute('data-num');
                if (d !== null) {
                    agregarDigito(d);
                    // Con la mezcla activa las teclas cambian de sitio en cada
                    // golpe: seguir el movimiento de los dedos deja de servir.
                    if (mezclar && mezclar.checked) reordenarTeclas();
                }
            });

            /** Reparte los digitos 0-9 en posiciones al azar. */
            function reordenarTeclas() {
                const teclas = [...pad.querySelectorAll('.pin-key[data-num]')];
                const numeros = teclas.map(t => t.getAttribute('data-num'));
                for (let i = numeros.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [numeros[i], numeros[j]] = [numeros[j], numeros[i]];
                }
                teclas.forEach((t, i) => {
                    t.setAttribute('data-num', numeros[i]);
                    t.textContent = numeros[i];
                });
            }

            /** Vuelve al orden natural 1..9, 0. */
            function ordenarTeclas() {
                const teclas = [...pad.querySelectorAll('.pin-key[data-num]')];
                const orden = ['1','2','3','4','5','6','7','8','9','0'];
                teclas.forEach((t, i) => {
                    t.setAttribute('data-num', orden[i]);
                    t.textContent = orden[i];
                });
            }

            if (mezclar) {
                mezclar.addEventListener('change', function () {
                    if (this.checked) reordenarTeclas(); else ordenarTeclas();
                });
            }

            // ---- Apertura del teclado ----
            const modalEl = document.getElementById('modalPin');
            const modalPin = modalEl ? new bootstrap.Modal(modalEl) : null;

            function abrirTeclado() {
                if (!modalPin) return;
                // Se parte de cero cada vez: no quedan digitos de un intento previo.
                borrarTodo();
                if (mezclar && mezclar.checked) reordenarTeclas();
                modalPin.show();
            }
            window.abrirTecladoPin = abrirTeclado;

            // Tocar el campo abre el teclado propio, nunca el del sistema.
            campoPin.addEventListener('focus', function () { this.blur(); abrirTeclado(); });
            campoPin.addEventListener('click', abrirTeclado);

            const btnListo = document.getElementById('pinListo');
            if (btnListo) {
                btnListo.addEventListener('click', function () {
                    modalPin.hide();
                    // Si ya hay usuario escrito, se entra directo.
                    const nombre = document.getElementById('nombre');
                    if (nombre && nombre.value.trim() && campoPin.value.length >= 4) {
                        document.getElementById('loginForm').requestSubmit();
                    }
                });
            }

            // Con el teclado abierto, las teclas fisicas escriben en el PIN.
            document.addEventListener('keydown', function (e) {
                if (!modalEl || !modalEl.classList.contains('show')) return;
                if (e.key >= '0' && e.key <= '9') { agregarDigito(e.key); }
                else if (e.key === 'Backspace')   { borrarUno(); e.preventDefault(); }
                else if (e.key === 'Enter')       { e.preventDefault(); btnListo && btnListo.click(); }
            });

            pintarPuntos();
        })();

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
                    // Error: se borra el PIN para poder reintentar de una vez
                    showAlert(data.error || 'Error al iniciar sesión', 'danger');
                    if (typeof limpiarPin === 'function') limpiarPin();
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Error de conexión. Intenta nuevamente.', 'danger');
                if (typeof limpiarPin === 'function') limpiarPin();
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

        // Entrar al sistema sin abrir la caja. La sesion ya esta iniciada en este
        // punto, asi que basta con seguir al menu principal; alli aparece el
        // boton para abrirla cuando haga falta cobrar.
        document.getElementById('btnEntrarSinCaja').addEventListener('click', function() {
            cerrarModalCaja();
            window.location.href = '?pg=home';
        });
    </script>
</body>
</html>
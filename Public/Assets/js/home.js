// ============================================================================
// HOME.JS - GESTIÓN DE CAJA Y MENÚ PRINCIPAL
// ============================================================================

let cajaActiva = null;
let modalCerrarCaja = null;
let resumenCajaActual = null;

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

document.addEventListener('DOMContentLoaded', async function() {
    await verificarEstadoCaja();
    console.log('[HOME] init done');
});

// ============================================================================
// VERIFICAR ESTADO DE CAJA
// ============================================================================

async function verificarEstadoCaja() {
    try {
        const response = await fetch('?pg=cash&action=active');
        const data = await response.json();
        console.log('[HOME] caja active response', data);
        
        if (data.success) {
            cajaActiva = data.data;
            mostrarEstadoCaja();
        } else {
            ocultarEstadoCaja();
        }
    } catch (error) {
        console.error('[HOME] Error verificando estado de caja:', error);
        ocultarEstadoCaja();
    }
}

function mostrarEstadoCaja() {
    const container = document.getElementById('cajaStatusContainer');
    if (container && cajaActiva) {
        // Calcular saldo actual aproximado
        const saldoInicial = parseFloat(cajaActiva.saldoInicial) || 0;
        const saldoEl = document.getElementById('saldoCajaActual');
        if (saldoEl) {
            saldoEl.textContent = formatCurrency(saldoInicial);
        }
        container.style.display = 'block';
        console.log('[HOME] mostrando franja de caja');
    } else {
        console.warn('[HOME] No se encontró contenedor o cajaActiva', { containerExists: !!container, cajaActiva });
    }
}

function ocultarEstadoCaja() {
    const container = document.getElementById('cajaStatusContainer');
    if (container) {
        container.style.display = 'none';
    }
}

// ============================================================================
// ABRIR MODAL CERRAR CAJA
// ============================================================================

async function abrirModalCerrarCaja() {
    if (!cajaActiva) {
        showNotification('No hay caja activa', 'warning');
        return;
    }

    try {
        // Obtener resumen detallado de la caja
        const response = await fetch(`?pg=reports&action=cashRegister&ajax=1`);
        const data = await response.json();
        const resumen = data.resumen || data.data;
        
        if (resumen) {
            resumenCajaActual = resumen;
            llenarResumenModal(resumen);
            
            // Mostrar modal
            if (!modalCerrarCaja) {
                modalCerrarCaja = new bootstrap.Modal(document.getElementById('modalCerrarCaja'));
            }
            modalCerrarCaja.show();
            
            // Focus en el input de saldo real
            setTimeout(() => {
                document.getElementById('saldoRealCierre').focus();
            }, 500);
        } else {
            showNotification('Error al obtener resumen de caja: ' + (data.error || 'respuesta vacía'), 'danger');
        }
    } catch (error) {
        console.error('[HOME] Error obteniendo resumen:', error);
        showNotification('Error de conexión al obtener resumen', 'danger');
    }
}

// ============================================================================
// LLENAR RESUMEN EN MODAL
// ============================================================================

function llenarResumenModal(resumen) {
    // Saldo inicial (acepta ambas claves)
    const saldoInicial = parseFloat(resumen.montoApertura ?? resumen.saldoInicial ?? 0) || 0;
    document.getElementById('resumenSaldoInicial').textContent = formatCurrency(saldoInicial);
    
    // Ingresos (ventas) - fallback a totalVentas
    const ingresos = parseFloat(resumen.totalIngresos ?? resumen.totalVentas ?? 0) || 0;
    document.getElementById('resumenIngresos').textContent = formatCurrency(ingresos);
    
    // Egresos (compras + gastos) - fallback a suma de campos
    const egresos = parseFloat(resumen.totalEgresos ?? resumen.totalCompras ?? 0) || 0;
    document.getElementById('resumenEgresos').textContent = formatCurrency(egresos);
    
    // Saldo calculado - fallback a saldoCalculado/efectivoActual
    const saldoCalculado = parseFloat(resumen.efectivoActual ?? resumen.saldoCalculado ?? 0) || 0;
    document.getElementById('resumenSaldoCalculado').textContent = formatCurrency(saldoCalculado);
    
    // Guardar saldo calculado para comparación
    document.getElementById('saldoRealCierre').dataset.saldoCalculado = saldoCalculado;
}

// ============================================================================
// CALCULAR DIFERENCIA AL ESCRIBIR SALDO REAL
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    const inputSaldoReal = document.getElementById('saldoRealCierre');
    
    if (inputSaldoReal) {
        inputSaldoReal.addEventListener('input', function() {
            // Formatear con separadores de miles
            let value = this.value.replace(/\D/g, '');
            
            // Limitar a 10 dígitos (máximo 9.999.999.999)
            if (value.length > 8) {
                value = value.slice(0, 8);
            }
            
            // Si está vacío, no hacer nada
            if (value === '') {
                this.value = '';
                return;
            }
            
            // Convertir a número y formatear con separadores de miles
            let num = parseInt(value);
            this.value = num.toLocaleString('es-CO');
            
            // Calcular diferencia
            const saldoReal = num;
            const saldoCalculado = parseFloat(this.dataset.saldoCalculado) || 0;
            const diferencia = saldoReal - saldoCalculado;
            
            const divDiferencia = document.getElementById('diferenciaCaja');
            const spanMonto = document.getElementById('diferenciaMonto');
            
            if (this.value && !isNaN(saldoReal)) {
                divDiferencia.style.display = 'block';
                spanMonto.textContent = formatCurrency(Math.abs(diferencia));
                
                // Colorear según diferencia
                if (diferencia > 0) {
                    divDiferencia.className = 'alert alert-success';
                    spanMonto.textContent = '+' + formatCurrency(diferencia) + ' (Sobrante)';
                } else if (diferencia < 0) {
                    divDiferencia.className = 'alert alert-danger';
                    spanMonto.textContent = formatCurrency(Math.abs(diferencia)) + ' (Faltante)';
                } else {
                    divDiferencia.className = 'alert alert-info';
                    spanMonto.textContent = formatCurrency(0) + ' (Exacto ✓)';
                }
            } else {
                divDiferencia.style.display = 'none';
            }
        });
    }
});

// ============================================================================
// CONFIRMAR CIERRE DE CAJA
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    const formCerrar = document.getElementById('formCerrarCaja');
    
    if (formCerrar) {
        formCerrar.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnConfirmar = document.getElementById('btnConfirmarCierre');
            const originalText = btnConfirmar.innerHTML;
            
            const saldoRealFormateado = document.getElementById('saldoRealCierre').value.trim();
            // Convertir el monto formateado a número (eliminar puntos)
            const saldoReal = parseFloat(saldoRealFormateado.replace(/\./g, ''));
            const notas = document.getElementById('notasCierre').value.trim();
            
            if (isNaN(saldoReal) || saldoReal < 0) {
                showNotification('Ingresa un saldo real válido', 'warning');
                return;
            }
            
            // Confirmar cierre
            if (!confirm('¿Estás seguro de cerrar la caja? Esta acción no se puede deshacer.')) {
                return;
            }
            
            // Deshabilitar botón
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cerrando...';
            
            try {
                const response = await fetch('?pg=cash&action=close', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        idCaja: cajaActiva.idCaja,
                        saldoReal: saldoReal,
                        notas: notas || 'Cierre de caja desde panel administrativo'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('✅ Caja cerrada correctamente', 'success');
                    
                    // Cerrar modal
                    if (modalCerrarCaja) {
                        modalCerrarCaja.hide();
                    }
                    
                    // Recargar página para actualizar estado
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('❌ ' + (data.error || 'Error al cerrar la caja'), 'danger');
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = originalText;
                }
            } catch (error) {
                console.error('[HOME] Error cerrando caja:', error);
                showNotification('❌ Error de conexión', 'danger');
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = originalText;
            }
        });
    }
});

// ============================================================================
// UTILIDADES
// ============================================================================

function formatCurrency(value) {
    return '$' + parseFloat(value).toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function showNotification(message, type = 'info') {
    // Usar SweetAlert2 si está disponible
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            text: message,
            icon: type === 'danger' ? 'error' : type,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        // Fallback a alert nativo
        alert(message);
    }
}

// Limpiar modal al cerrarse
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('modalCerrarCaja');
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            document.getElementById('formCerrarCaja').reset();
            document.getElementById('diferenciaCaja').style.display = 'none';
        });
    }
});

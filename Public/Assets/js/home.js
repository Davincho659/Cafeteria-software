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
    // Base con la que se abrió la caja
    const saldoInicial = parseFloat(resumen.montoApertura ?? resumen.saldoInicial ?? 0) || 0;
    document.getElementById('resumenSaldoInicial').textContent = formatCurrency(saldoInicial);

    // Ventas cobradas EN EFECTIVO: es el único dinero que entró al cajón.
    const ventasEfectivo = parseFloat(resumen.totalVentasEfectivo ?? 0) || 0;
    const elVentasEfectivo = document.getElementById('resumenVentasEfectivo');
    if (elVentasEfectivo) elVentasEfectivo.textContent = formatCurrency(ventasEfectivo);

    // Salidas de efectivo (compras y gastos pagados del cajón)
    const egresos = parseFloat(resumen.totalEgresosEfectivo ?? resumen.totalEgresos ?? 0) || 0;
    document.getElementById('resumenEgresos').textContent = formatCurrency(egresos);

    // Ventas por Nequi/Bancolombia: se informan aparte, NO se cuentan en el cajón.
    const transferencias = parseFloat(resumen.totalVentasTransferencia ?? 0) || 0;
    const bloqueTransf = document.getElementById('bloqueTransferencias');
    const elTransf = document.getElementById('resumenVentasTransferencia');
    if (bloqueTransf && elTransf) {
        if (transferencias > 0) {
            elTransf.textContent = formatCurrency(transferencias);
            bloqueTransf.style.display = 'block';
        } else {
            bloqueTransf.style.display = 'none';
        }
    }

    // Lo que DEBE haber físicamente en el cajón (base + ventas efectivo − salidas)
    const efectivoEsperado = parseFloat(resumen.efectivoEsperado ?? resumen.efectivoActual ?? 0) || 0;
    document.getElementById('resumenSaldoCalculado').textContent = formatCurrency(efectivoEsperado);

    // Guardar para comparar contra lo contado
    const input = document.getElementById('saldoRealCierre');
    input.dataset.saldoCalculado = efectivoEsperado;

    // Reiniciar el conteo cada vez que se abre el modal
    input.value = '';
    input.dataset.valor = '';
    const divDiferencia = document.getElementById('diferenciaCaja');
    if (divDiferencia) divDiferencia.style.display = 'none';
}

// ============================================================================
// ARQUEO: comparar lo contado contra lo esperado
// ============================================================================

/**
 * Pinta la diferencia entre el efectivo contado y el esperado.
 * Se llama desde la calculadora táctil (el campo es readonly a propósito:
 * la pantalla del POS no tiene teclado).
 */
function actualizarDiferenciaCaja() {
    const input = document.getElementById('saldoRealCierre');
    const divDiferencia = document.getElementById('diferenciaCaja');
    const spanMonto = document.getElementById('diferenciaMonto');
    if (!input || !divDiferencia || !spanMonto) return;

    const contado = parseFloat(input.dataset.valor || '');
    if (isNaN(contado) || input.dataset.valor === '') {
        divDiferencia.style.display = 'none';
        return;
    }

    const esperado = parseFloat(input.dataset.saldoCalculado) || 0;
    const diferencia = contado - esperado;

    divDiferencia.style.display = 'block';
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
}

// ============================================================================
// CALCULADORA TÁCTIL PARA CONTAR EL EFECTIVO
// ============================================================================

let cashCountValue = '';   // dígitos escritos
let cashCountTotal = 0;    // total acumulado con los botones de billetes

function renderCashCount() {
    const display = document.getElementById('cashCountDisplay');
    if (!display) return;
    const valor = cashCountTotal + (cashCountValue === '' ? 0 : parseInt(cashCountValue, 10));
    display.textContent = '$' + valor.toLocaleString('es-CO');
}

function openCashCountCalc() {
    const overlay = document.getElementById('cashCountOverlay');
    if (!overlay) return;
    // Parte del valor que ya estuviera puesto, para poder corregirlo.
    const actual = parseInt(document.getElementById('saldoRealCierre').dataset.valor || '0', 10);
    cashCountTotal = isNaN(actual) ? 0 : actual;
    cashCountValue = '';
    renderCashCount();
    overlay.classList.add('active');
}

function closeCashCountCalc(event) {
    // Si se hizo click en el fondo (no en el popup), también cierra.
    if (event && event.target && event.target.id !== 'cashCountOverlay') return;
    const overlay = document.getElementById('cashCountOverlay');
    if (overlay) overlay.classList.remove('active');
}

function addCashCountDigit(d) {
    // Tope de 8 dígitos: evita montos absurdos por toques repetidos.
    if (cashCountValue.length >= 8) return;
    cashCountValue = (cashCountValue === '' && d === '0') ? '' : cashCountValue + d;
    renderCashCount();
}

function deleteCashCountDigit() {
    if (cashCountValue !== '') {
        cashCountValue = cashCountValue.slice(0, -1);
    } else {
        cashCountTotal = 0; // ya no quedan dígitos: limpia el acumulado
    }
    renderCashCount();
}

/** Suma una denominación de billete (atajo para contar el cajón). */
function addCashCountAmount(monto) {
    // Lo escrito se consolida antes de sumar el billete.
    if (cashCountValue !== '') {
        cashCountTotal += parseInt(cashCountValue, 10);
        cashCountValue = '';
    }
    cashCountTotal += monto;
    renderCashCount();
}

function clearCashCountCalc() {
    cashCountValue = '';
    cashCountTotal = 0;
    renderCashCount();
}

function confirmCashCount() {
    const total = cashCountTotal + (cashCountValue === '' ? 0 : parseInt(cashCountValue, 10));
    const input = document.getElementById('saldoRealCierre');
    input.dataset.valor = String(total);
    input.value = total.toLocaleString('es-CO');
    actualizarDiferenciaCaja();
    closeCashCountCalc();
}

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
            
            const inputSaldo = document.getElementById('saldoRealCierre');
            // El monto viene de la calculadora táctil (dataset.valor); se usa el
            // texto visible solo como respaldo.
            const crudo = inputSaldo.dataset.valor !== undefined && inputSaldo.dataset.valor !== ''
                ? inputSaldo.dataset.valor
                : inputSaldo.value.trim().replace(/\./g, '');
            const saldoReal = parseFloat(crudo);
            const notas = document.getElementById('notasCierre').value.trim();

            if (isNaN(saldoReal) || saldoReal < 0) {
                showNotification('Cuenta el efectivo del cajón antes de cerrar', 'warning');
                return;
            }

            // Si hay descuadre, se avisa explícitamente antes de cerrar: el
            // cierre no se puede deshacer y la diferencia queda registrada.
            const esperado = parseFloat(inputSaldo.dataset.saldoCalculado) || 0;
            const diferencia = saldoReal - esperado;
            let mensajeConfirm = '¿Estás seguro de cerrar la caja? Esta acción no se puede deshacer.';
            if (Math.abs(diferencia) >= 1) {
                const tipo = diferencia > 0 ? 'SOBRANTE' : 'FALTANTE';
                mensajeConfirm = 'Hay un ' + tipo + ' de ' + formatCurrency(Math.abs(diferencia)) + '.\n\n'
                    + 'Esperado: ' + formatCurrency(esperado) + '\n'
                    + 'Contado: ' + formatCurrency(saldoReal) + '\n\n'
                    + '¿Cerrar la caja de todos modos? Quedará registrado.';
            }

            if (!confirm(mensajeConfirm)) {
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

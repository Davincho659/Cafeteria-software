// ============================================================================
// TIEMPO REAL - Lo que pasa en los celulares se ve en la caja
// ============================================================================
// Cuando un mesero toma un pedido desde el celular, la caja tiene que enterarse
// sin que nadie recargue nada. Se consulta al servidor cada pocos segundos y se
// actualiza solo lo que cambió.
//
// Se usa consulta periódica y no una conexión permanente porque funciona en
// cualquier servidor, incluso en hosting compartido, y para un salón de pocas
// mesas es de sobra.
//
// Cuidados que se toman, porque esto corre sobre una caja registradora:
//   - No se toca la pantalla mientras el cajero está marcando productos: si se
//     repintara justo al tocar, se le movería lo que está haciendo.
//   - Se detiene cuando la pantalla no está a la vista, para no gastar datos
//     ni batería en los celulares.
//   - Nunca reemplaza el carrito de una venta suelta: eso vive en la caja y no
//     lo modifica nadie más.
// ============================================================================

(function () {
  const CADA_MS = 4000;          // cada cuánto se pregunta al servidor
  const ESPERA_TRAS_TOCAR = 2500; // silencio tras la última acción del cajero

  let temporizador = null;
  let ultimoToque = 0;
  let consultando = false;

  // Huella de cada mesa (total + cantidad de productos) para saber si cambió
  // sin tener que comparar listas enteras.
  let huellas = {};

  /** El cajero acaba de tocar algo: no repintar encima. */
  function marcarActividad() {
    ultimoToque = Date.now();
  }

  function estaOcupadoElCajero() {
    return Date.now() - ultimoToque < ESPERA_TRAS_TOCAR;
  }

  function huellaDe(mesa) {
    return [
      mesa.idVenta || 0,
      mesa.total || 0,
      mesa.cantidadProductos || 0,
      mesa.estadoMesa || ''
    ].join('|');
  }

  /**
   * Refresca el tablero de mesas si está a la vista.
   * Solo cambia los textos: no vuelve a dibujar los botones, para no romper
   * un toque que el cajero esté haciendo en ese instante.
   */
  function refrescarTablero(mesas) {
    const contenedor = document.getElementById('tableContainer');
    if (!contenedor || !contenedor.offsetParent) return; // no está visible

    // Si cambió el número de mesas, se deja que la vista se redibuje sola.
    const botones = contenedor.querySelectorAll('.table-card');
    if (botones.length !== mesas.length) {
      if (typeof showTableSelectionPopup === 'function') {
        showTableSelectionPopup(mesas);
      }
      return;
    }

    mesas.forEach((mesa, i) => {
      const btn = botones[i];
      if (!btn) return;
      const ocupada = mesa.idVenta !== null && mesa.idVenta !== undefined;
      const etiqueta = btn.querySelector('small');
      if (etiqueta) {
        const texto = ocupada ? 'Ocupada' : 'Disponible';
        if (etiqueta.textContent.trim() !== texto) {
          etiqueta.textContent = texto;
          etiqueta.className = ocupada ? 'mesa-status-occupied' : 'mesa-status-available';
        }
      }
    });
  }

  /**
   * Si la caja tiene abierta la pestaña de una mesa que cambió, recarga sus
   * productos. Es el caso que importa: el mesero agrega algo desde el celular
   * y el cajero lo ve aparecer.
   */
  function refrescarMesasAbiertas(mesas, cambiadas) {
    if (typeof activeTables === 'undefined') return;

    cambiadas.forEach((idMesa) => {
      // Solo las que la caja tiene abiertas
      if (!activeTables[idMesa]) return;

      const mesa = mesas.find(m => String(m.idMesa) === String(idMesa));
      if (!mesa) return;

      // La mesa se cobró o se liberó desde otro lado: se quita la pestaña.
      if (!mesa.idVenta) {
        if (typeof removeTableTab === 'function') {
          removeTableTab('mesa-' + idMesa, idMesa);
        }
        delete activeTables[idMesa];
        return;
      }

      // El identificador de venta cambió (se cobró y se abrió otra cuenta).
      activeTables[idMesa].idVenta = mesa.idVenta;

      if (typeof reloadTableSale === 'function') {
        reloadTableSale(idMesa);
      }
    });
  }

  async function sincronizar() {
    if (consultando) return;
    if (document.hidden) return;          // pestaña en segundo plano
    if (estaOcupadoElCajero()) return;    // el cajero está marcando

    consultando = true;
    try {
      const r = await fetch('?pg=sales&action=GetTables', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const datos = await r.json();
      if (!datos.success || !Array.isArray(datos.data)) return;

      const mesas = datos.data;
      const cambiadas = [];
      const nuevas = {};

      mesas.forEach((mesa) => {
        const id = String(mesa.idMesa);
        const h = huellaDe(mesa);
        nuevas[id] = h;
        // La primera vuelta solo toma la foto inicial: sin esto se recargaría
        // todo nada más entrar, sin que nada hubiera cambiado.
        if (huellas[id] !== undefined && huellas[id] !== h) {
          cambiadas.push(id);
        }
      });

      const primeraVez = Object.keys(huellas).length === 0;
      huellas = nuevas;
      if (primeraVez) return;

      if (cambiadas.length) {
        console.log('[TIEMPO REAL] Mesas con cambios:', cambiadas.join(', '));
        refrescarMesasAbiertas(mesas, cambiadas);
      }

      refrescarTablero(mesas);
    } catch (e) {
      // Sin conexión: se calla y reintenta en el siguiente ciclo. No se avisa
      // en pantalla para no distraer al cajero por un corte de un segundo.
    } finally {
      consultando = false;
    }
  }

  function arrancar() {
    if (temporizador) return;
    // Solo en la vista de ventas: en el resto no hay nada que sincronizar.
    if (!document.getElementById('ventasTabs')) return;

    ['click', 'keydown', 'touchstart'].forEach((evento) => {
      document.addEventListener(evento, marcarActividad, { passive: true });
    });

    // Al volver a la pestaña conviene mirar de inmediato: puede haber pedidos
    // nuevos de mientras.
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) sincronizar();
    });

    temporizador = setInterval(sincronizar, CADA_MS);
    console.log('[TIEMPO REAL] Activo: se consultan las mesas cada', CADA_MS / 1000, 'segundos');
  }

  // Que otras partes puedan forzar una comprobación tras una acción propia.
  window.sincronizarMesasAhora = sincronizar;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', arrancar);
  } else {
    arrancar();
  }
})();

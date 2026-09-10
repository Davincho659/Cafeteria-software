// ============================================================================
// SIN CONEXIÓN - La caja sigue cobrando aunque se caiga el internet
// ============================================================================
// Una caja registradora no puede detenerse porque falle la señal. Aquí se
// guarda en el propio equipo lo indispensable para cobrar (qué se vende y a
// cuánto) y, mientras no haya internet, las ventas se apuntan en una cola que
// se envía sola al servidor cuando la conexión vuelve.
//
// Alcance, decidido a propósito:
//
//   SÍ funciona sin conexión → el cobro de mostrador
//   NO funciona sin conexión → mesas, inventario, compras y reportes
//
// La razón es el descuadre: las mesas y el stock los tocan varios dispositivos
// a la vez, y si cada uno trabajara por su cuenta sin verse, al reconectar
// habría que decidir cuál versión vale. El mostrador no tiene ese problema:
// es una venta que empieza y termina en la caja.
//
// Contra los cobros repetidos: cada venta lleva una marca única generada aquí.
// El servidor la usa para reconocerla, así que reenviar la misma venta -por un
// reintento, una recarga o un corte a mitad de envío- nunca la cobra dos veces.
// ============================================================================

const SinConexion = (function () {
  const BD_NOMBRE = 'pos-sin-conexion';
  const BD_VERSION = 1;
  const ALMACEN_CATALOGO = 'catalogo';
  const ALMACEN_COLA = 'ventasPendientes';

  let bd = null;
  let hayConexion = navigator.onLine;
  let sincronizando = false;

  // ---------------------------------------------------------------- base local
  function abrirBD() {
    return new Promise((resolve, reject) => {
      if (bd) return resolve(bd);
      const req = indexedDB.open(BD_NOMBRE, BD_VERSION);

      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains(ALMACEN_CATALOGO)) {
          db.createObjectStore(ALMACEN_CATALOGO, { keyPath: 'idProducto' });
        }
        if (!db.objectStoreNames.contains(ALMACEN_COLA)) {
          db.createObjectStore(ALMACEN_COLA, { keyPath: 'uuid' });
        }
      };

      req.onsuccess = (e) => { bd = e.target.result; resolve(bd); };
      req.onerror = () => reject(req.error);
    });
  }

  function conAlmacen(nombre, modo, operacion) {
    return abrirBD().then((db) => new Promise((resolve, reject) => {
      const tx = db.transaction(nombre, modo);
      const almacen = tx.objectStore(nombre);
      const req = operacion(almacen);
      tx.oncomplete = () => resolve(req ? req.result : undefined);
      tx.onerror = () => reject(tx.error);
    }));
  }

  // ---------------------------------------------------------------- catálogo
  /**
   * Guarda el catálogo en el equipo. Se refresca cuando hay conexión, para que
   * la copia local no quede con precios viejos.
   */
  async function guardarCatalogo() {
    if (!navigator.onLine) return;
    try {
      const r = await fetch('?pg=sales&action=catalogoParaSinConexion');
      const datos = await r.json();
      if (!datos.success || !Array.isArray(datos.data)) return;

      const db = await abrirBD();
      const tx = db.transaction(ALMACEN_CATALOGO, 'readwrite');
      const almacen = tx.objectStore(ALMACEN_CATALOGO);
      almacen.clear();
      datos.data.forEach((p) => almacen.put(p));

      localStorage.setItem('pos_catalogo_actualizado', new Date().toISOString());
      console.log('[SIN CONEXIÓN] Catálogo guardado:', datos.data.length, 'productos');
    } catch (e) {
      console.warn('[SIN CONEXIÓN] No se pudo guardar el catálogo:', e);
    }
  }

  async function leerCatalogo() {
    try {
      return await conAlmacen(ALMACEN_CATALOGO, 'readonly', (a) => a.getAll());
    } catch (e) {
      return [];
    }
  }

  // ---------------------------------------------------------------- cola
  function nuevaMarca() {
    // Identificador propio de esta venta. Es lo que impide que el mismo cobro
    // entre dos veces si el envío se repite.
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'v-' + Date.now() + '-' + Math.random().toString(16).slice(2, 10);
  }

  /** Apunta una venta cobrada sin conexión. */
  async function guardarVenta(productos, metodoPago) {
    const venta = {
      uuid: nuevaMarca(),
      fecha: new Date().toISOString(),
      metodoPago: metodoPago || 'efectivo',
      productos: productos.map((p) => ({
        idProducto: p.idProducto ?? null,
        cantidad: p.cantidad,
        precioUnitario: p.precioVenta ?? p.precioUnitario ?? 0,
        nombre: p.nombre,
      })),
      total: productos.reduce((s, p) => s + (p.cantidad * (p.precioVenta ?? 0)), 0),
    };

    await conAlmacen(ALMACEN_COLA, 'readwrite', (a) => a.put(venta));
    actualizarAviso();
    console.log('[SIN CONEXIÓN] Venta guardada en el equipo:', venta.uuid);
    return venta;
  }

  async function ventasPendientes() {
    try {
      return await conAlmacen(ALMACEN_COLA, 'readonly', (a) => a.getAll());
    } catch (e) {
      return [];
    }
  }

  async function quitarDeLaCola(uuid) {
    return conAlmacen(ALMACEN_COLA, 'readwrite', (a) => a.delete(uuid));
  }

  // ---------------------------------------------------------------- envío
  /**
   * Envía al servidor lo cobrado sin conexión.
   *
   * Solo se borra de la cola lo que el servidor confirmó. Lo que falle se queda
   * para el siguiente intento: es preferible reintentar de más que dar por
   * enviada una venta que no llegó.
   */
  async function sincronizar() {
    if (sincronizando || !navigator.onLine) return;

    const pendientes = await ventasPendientes();
    if (!pendientes.length) return;

    sincronizando = true;
    try {
      console.log('[SIN CONEXIÓN] Enviando', pendientes.length, 'venta(s)...');
      const r = await fetch('?pg=sales&action=sincronizarVentasSinConexion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ventas: pendientes }),
      });
      const datos = await r.json();
      if (!datos.success) {
        console.warn('[SIN CONEXIÓN] El servidor rechazó el envío:', datos.error);
        return;
      }

      let enviadas = 0;
      for (const res of datos.resultados || []) {
        if (res.ok) { await quitarDeLaCola(res.uuid); enviadas++; }
        else console.warn('[SIN CONEXIÓN] Quedó pendiente:', res.uuid, res.error);
      }

      actualizarAviso();
      if (enviadas > 0) {
        avisar(`Se registraron ${enviadas} venta(s) que se cobraron sin conexión.`, 'success');
      }
    } catch (e) {
      console.warn('[SIN CONEXIÓN] No se pudo enviar; se reintenta luego:', e);
    } finally {
      sincronizando = false;
    }
  }

  // ---------------------------------------------------------------- aviso
  function elementoAviso() {
    let el = document.getElementById('avisoSinConexion');
    if (!el) {
      el = document.createElement('div');
      el.id = 'avisoSinConexion';
      el.className = 'aviso-sin-conexion';
      document.body.appendChild(el);
    }
    return el;
  }

  /** Franja que dice si se está trabajando sin conexión y cuánto falta por enviar. */
  async function actualizarAviso() {
    const el = elementoAviso();
    const pendientes = await ventasPendientes();

    if (hayConexion && pendientes.length === 0) {
      el.style.display = 'none';
      return;
    }

    el.style.display = 'flex';
    if (!hayConexion) {
      el.className = 'aviso-sin-conexion sin-red';
      el.innerHTML = '<i class="fa-solid fa-plug-circle-xmark"></i>' +
        '<span><strong>Sin internet.</strong> Puedes seguir cobrando: las ventas se guardan ' +
        'y se registran solas cuando vuelva la señal.' +
        (pendientes.length ? ` <b>(${pendientes.length} por enviar)</b>` : '') + '</span>';
    } else {
      el.className = 'aviso-sin-conexion enviando';
      el.innerHTML = '<i class="fa-solid fa-arrows-rotate fa-spin"></i>' +
        `<span>Registrando ${pendientes.length} venta(s) cobradas sin conexión…</span>`;
    }
  }

  function avisar(texto, tipo) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: tipo || 'info', title: texto, timer: 2600, showConfirmButton: false });
    } else {
      console.log('[SIN CONEXIÓN]', texto);
    }
  }

  // ---------------------------------------------------------------- estado
  function marcarEstado(conectado) {
    hayConexion = conectado;
    document.body.classList.toggle('modo-sin-conexion', !conectado);
    actualizarAviso();
    if (conectado) {
      guardarCatalogo();
      sincronizar();
    }
  }

  /**
   * Comprueba de verdad si el servidor responde.
   *
   * navigator.onLine solo dice si hay cable o wifi, no si hay internet: con el
   * router encendido pero sin servicio, el navegador sigue diciendo que hay
   * conexión mientras el servidor está inalcanzable.
   */
  async function comprobarServidor() {
    if (!navigator.onLine) { marcarEstado(false); return false; }
    try {
      const control = new AbortController();
      const corte = setTimeout(() => control.abort(), 5000);
      const r = await fetch('?pg=login&action=csrfToken', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: control.signal,
      });
      clearTimeout(corte);
      marcarEstado(r.ok);
      return r.ok;
    } catch (e) {
      marcarEstado(false);
      return false;
    }
  }

  // ---------------------------------------------------------------- arranque
  function iniciar() {
    if (!document.getElementById('ventasTabs')) return; // solo en la caja

    window.addEventListener('online', () => comprobarServidor());
    window.addEventListener('offline', () => marcarEstado(false));

    guardarCatalogo();
    comprobarServidor();

    // Reintento periódico: si la señal volvió sin que el navegador avisara,
    // igual se envía lo pendiente.
    setInterval(() => { if (navigator.onLine) sincronizar(); }, 30000);

    console.log('[SIN CONEXIÓN] Listo. La caja puede cobrar sin internet.');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }

  return {
    hayConexion: () => hayConexion,
    guardarVenta,
    ventasPendientes,
    sincronizar,
    leerCatalogo,
    guardarCatalogo,
    comprobarServidor,
    actualizarAviso,
  };
})();

window.SinConexion = SinConexion;

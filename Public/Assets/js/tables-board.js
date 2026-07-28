/**
 * ============================================================================
 * TABLES-BOARD.JS - Tablero/plano del salón (reutilizable)
 * ============================================================================
 * Renderiza las mesas como fichas ubicadas por posición (posX/posY en % del
 * tablero). Dos modos:
 *   · editable:  se arrastran las fichas y se pueden leer las posiciones para
 *                guardarlas (vista Mesas).
 *   · navegador: solo se ven; al tocar una mesa se llama onTableClick (Ventas).
 *
 * API global: window.TablesBoard = { render, getPositions }
 * ============================================================================
 */
;(function () {
  "use strict"

  var money = function (n) {
    return "$" + Number(n || 0).toLocaleString("es-CO", { maximumFractionDigits: 0 })
  }
  var esc = function (s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]
    })
  }

  /**
   * Dibuja el tablero dentro de `canvas`.
   * @param {HTMLElement} canvas  Contenedor con clase .board-canvas
   * @param {Array} mesas         [{idMesa, numero|numeroMesa, estado|estadoMesa, posX, posY, total, idVenta}]
   * @param {Object} opciones
   *   editable:   boolean  → permite arrastrar
   *   onTableClick: fn(mesa) → callback al tocar una mesa (modo navegador)
   */
  function render(canvas, mesas, opciones) {
    opciones = opciones || {}
    var editable = !!opciones.editable
    var onTableClick = typeof opciones.onTableClick === "function" ? opciones.onTableClick : null

    canvas.innerHTML = ""

    if (!mesas || mesas.length === 0) {
      var vacio = document.createElement("div")
      vacio.className = "board-empty"
      vacio.textContent = "No hay mesas creadas. Créalas primero en la vista de Mesas."
      canvas.appendChild(vacio)
      return
    }

    // Reparto automático inicial para mesas sin posición (posX=posY=0)
    var sinPos = mesas.filter(function (m) {
      return (Number(m.posX) || 0) === 0 && (Number(m.posY) || 0) === 0
    })
    if (sinPos.length) {
      var cols = Math.ceil(Math.sqrt(mesas.length))
      sinPos.forEach(function (m, i) {
        var idx = mesas.indexOf(m)
        m.posX = 12 + (idx % cols) * (76 / Math.max(1, cols - 1 || 1))
        m.posY = 15 + Math.floor(idx / cols) * 22
        m.posX = Math.min(90, Math.max(8, m.posX))
        m.posY = Math.min(88, Math.max(8, m.posY))
      })
    }

    mesas.forEach(function (mesa) {
      var numero = mesa.numero != null ? mesa.numero : mesa.numeroMesa
      var estado = mesa.estado || mesa.estadoMesa || "libre"
      var ocupada = estado === "ocupada"
      var total = Number(mesa.total || 0)
      var esBarra = mesa.tipo === "barra"
      var nombre = mesa.nombre || mesa.nombreMesa || ""

      var ficha = document.createElement("div")
      ficha.className = "board-table " +
        (esBarra ? "board-barra " : "board-mesa ") +
        (ocupada ? "ocupada" : "libre") +
        (editable ? " editable" : " clickable")
      ficha.style.left = (Number(mesa.posX) || 0) + "%"
      ficha.style.top = (Number(mesa.posY) || 0) + "%"
      ficha.dataset.idMesa = mesa.idMesa
      ficha.dataset.numero = numero

      var icono = esBarra ? svgBarra() : svgMesa()
      var etiqueta = esBarra
        ? (nombre ? esc(nombre) : "Barra " + esc(numero))
        : (nombre ? esc(nombre) : "Mesa " + esc(numero))

      ficha.innerHTML =
        '<div class="bt-head">' +
          '<span class="bt-num">' + (esBarra ? "🍸" : "#") + esc(numero) + "</span>" +
          '<span class="bt-name">' + etiqueta + "</span>" +
        "</div>" +
        '<div class="bt-icon">' + icono + "</div>" +
        '<div class="bt-foot">' +
          (ocupada
            ? '<span class="bt-total">' + money(total) + "</span>"
            : '<span class="bt-libre">Libre</span>') +
        "</div>"

      canvas.appendChild(ficha)

      if (editable) {
        habilitarArrastre(ficha, canvas)
      } else if (onTableClick) {
        ficha.addEventListener("click", function () {
          onTableClick(mesa)
        })
      }
    })
  }

  /** Ícono de mesa (vista superior: mesa con 4 sillas). */
  function svgMesa() {
    return (
      '<svg viewBox="0 0 64 64" class="bt-svg" aria-hidden="true">' +
        '<rect x="25" y="3"  width="14" height="9"  rx="3" fill="rgba(255,255,255,.55)"/>' +
        '<rect x="25" y="52" width="14" height="9"  rx="3" fill="rgba(255,255,255,.55)"/>' +
        '<rect x="3"  y="25" width="9"  height="14" rx="3" fill="rgba(255,255,255,.55)"/>' +
        '<rect x="52" y="25" width="9"  height="14" rx="3" fill="rgba(255,255,255,.55)"/>' +
        '<rect x="15" y="15" width="34" height="34" rx="7" fill="#fff"/>' +
      "</svg>"
    )
  }

  /** Ícono de barra (mostrador con banquetas). */
  function svgBarra() {
    return (
      '<svg viewBox="0 0 112 48" class="bt-svg bt-svg-barra" aria-hidden="true">' +
        '<rect x="6" y="10" width="100" height="18" rx="6" fill="#fff"/>' +
        '<circle cx="22" cy="40" r="5.5" fill="rgba(255,255,255,.55)"/>' +
        '<circle cx="46" cy="40" r="5.5" fill="rgba(255,255,255,.55)"/>' +
        '<circle cx="70" cy="40" r="5.5" fill="rgba(255,255,255,.55)"/>' +
        '<circle cx="92" cy="40" r="5.5" fill="rgba(255,255,255,.55)"/>' +
      "</svg>"
    )
  }

  /** Arrastre de una ficha dentro del tablero (con pointer events). */
  function habilitarArrastre(ficha, canvas) {
    var arrastrando = false
    var movio = false

    ficha.addEventListener("pointerdown", function (e) {
      arrastrando = true
      movio = false
      ficha.classList.add("dragging")
      ficha.setPointerCapture(e.pointerId)
      e.preventDefault()
    })

    ficha.addEventListener("pointermove", function (e) {
      if (!arrastrando) return
      movio = true
      var rect = canvas.getBoundingClientRect()
      var x = ((e.clientX - rect.left) / rect.width) * 100
      var y = ((e.clientY - rect.top) / rect.height) * 100
      // Mantener la ficha dentro del tablero (con margen)
      x = Math.min(96, Math.max(4, x))
      y = Math.min(96, Math.max(4, y))
      ficha.style.left = x + "%"
      ficha.style.top = y + "%"
    })

    var soltar = function (e) {
      if (!arrastrando) return
      arrastrando = false
      ficha.classList.remove("dragging")
      try { ficha.releasePointerCapture(e.pointerId) } catch (err) {}
    }
    ficha.addEventListener("pointerup", soltar)
    ficha.addEventListener("pointercancel", soltar)
  }

  /** Lee las posiciones actuales de las fichas del tablero. */
  function getPositions(canvas) {
    var fichas = canvas.querySelectorAll(".board-table")
    var out = []
    fichas.forEach(function (f) {
      out.push({
        idMesa: parseInt(f.dataset.idMesa, 10),
        posX: parseFloat(f.style.left) || 0,
        posY: parseFloat(f.style.top) || 0,
      })
    })
    return out
  }

  window.TablesBoard = { render: render, getPositions: getPositions }
})()

// ============================================================================
// UNIDADES.JS - Adaptar los campos de cantidad a la unidad del producto
// ============================================================================
// Un bulto de maíz se mide en kilos y puede tener 50,5 kg; una gaseosa se mide
// en unidades y no existe media gaseosa. Sin esto, los formularios pedían
// "Cantidad" a secas y solo aceptaban enteros, así que era imposible registrar
// medio kilo de merma o ajustar un inventario con decimales.
//
// Uso:
//   aplicarUnidadAlCampo(input, producto, etiqueta)
// ============================================================================

/**
 * Devuelve cómo debe comportarse el campo de cantidad para un producto.
 *
 * @param {object} producto - debe traer unidadTipo y/o unidadAbreviatura
 * @returns {{decimales:boolean, paso:string, minimo:string, abreviatura:string, nombre:string}}
 */
function reglaDeUnidad(producto) {
    if (!producto) {
        return { decimales: false, paso: '1', minimo: '1', abreviatura: 'u', nombre: 'unidades' };
    }

    // Si el servidor no mandó el tipo, se deduce por la abreviatura.
    let tipo = producto.unidadTipo;
    if (!tipo) {
        const abrev = String(producto.unidadAbreviatura || 'u').toLowerCase();
        if (['kg', 'g', 'lb', 'lib'].includes(abrev)) tipo = 'peso';
        else if (['l', 'ml', 'lt'].includes(abrev)) tipo = 'volumen';
        else tipo = 'unidad';
    }

    const abreviatura = producto.unidadAbreviatura || (tipo === 'peso' ? 'kg' : tipo === 'volumen' ? 'L' : 'u');
    const nombre = producto.unidadNombre || (tipo === 'peso' ? 'kilogramos' : tipo === 'volumen' ? 'litros' : 'unidades');

    if (tipo === 'unidad') {
        // Cosas contables: enteros. No existe media empanada en el inventario.
        return { decimales: false, paso: '1', minimo: '1', abreviatura, nombre };
    }

    // Peso y volumen: se admiten decimales (50,5 kg / 1,75 L).
    return { decimales: true, paso: '0.001', minimo: '0.001', abreviatura, nombre };
}

/**
 * Ajusta un <input type="number"> y su etiqueta según la unidad del producto.
 *
 * @param {HTMLInputElement} input
 * @param {object} producto
 * @param {HTMLElement} [etiqueta] - label al que se le agrega la unidad
 * @param {string} [textoBase='Cantidad'] - texto de la etiqueta sin la unidad
 */
function aplicarUnidadAlCampo(input, producto, etiqueta, textoBase = 'Cantidad') {
    if (!input) return null;

    const regla = reglaDeUnidad(producto);

    input.step = regla.paso;
    // El mínimo solo se fuerza cuando el campo no admite el cero (mermas, compras).
    if (input.dataset.permiteCero !== 'true') {
        input.min = regla.minimo;
    } else {
        input.min = '0';
    }
    input.placeholder = regla.decimales ? `Ej: 50.5 ${regla.abreviatura}` : `Ej: 12 ${regla.abreviatura}`;

    // Si ya había un valor con decimales y ahora el producto es contable, se
    // redondea para que el navegador no bloquee el envío del formulario.
    if (!regla.decimales && input.value !== '') {
        const n = parseFloat(input.value);
        if (!isNaN(n) && n % 1 !== 0) input.value = String(Math.round(n));
    }

    if (etiqueta) {
        etiqueta.textContent = `${textoBase} (${regla.abreviatura}) *`;
    }

    return regla;
}

/**
 * Da formato a una cantidad con su unidad, sin decimales inútiles:
 * 3 → "3 u"    ·    50.5 → "50.5 kg"    ·    2.000 → "2 kg"
 */
function formatearCantidadUnidad(cantidad, producto) {
    const regla = reglaDeUnidad(producto);
    const n = parseFloat(cantidad) || 0;
    const texto = regla.decimales
        ? String(parseFloat(n.toFixed(3)))   // quita ceros sobrantes
        : String(Math.round(n));
    return `${texto} ${regla.abreviatura}`;
}

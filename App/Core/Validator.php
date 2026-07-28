<?php
/**
 * ============================================================================
 * VALIDATOR - Validación de cantidades y montos
 * ============================================================================
 * Punto único para validar números que afectan plata e inventario. Se valida
 * en el SERVIDOR (el navegador se puede saltar). Lanza InvalidArgumentException
 * con un mensaje claro cuando el valor no sirve.
 *
 * Los topes son GENEROSOS a propósito: permiten pedidos y compras grandes
 * (muy por encima de 100 unidades) y solo cortan valores absurdos/errores de
 * digitación con ceros de más.
 * ============================================================================
 */
class Validator {
    /** Máximo de unidades por línea (pedidos grandes permitidos). */
    const MAX_CANTIDAD = 100000;

    /** Máximo por precio unitario / monto de una línea. */
    const MAX_PRECIO = 800000;      // 800 mil

    /** Máximo para un total o monto acumulado. */
    const MAX_MONTO = 4000000;      // 4 millones

    /**
     * Valida una cantidad. Debe ser numérica y mayor a 0.
     * @param bool $enteros  Si true, exige entero (unidades); si false, admite decimales (kg, L).
     * @return float cantidad normalizada
     */
    public static function cantidad($valor, $enteros = false, $etiqueta = 'La cantidad') {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            throw new InvalidArgumentException("$etiqueta no es un número válido");
        }
        $n = (float) $valor;
        if ($n <= 0) {
            throw new InvalidArgumentException("$etiqueta debe ser mayor a 0");
        }
        if ($n > self::MAX_CANTIDAD) {
            throw new InvalidArgumentException("$etiqueta supera el máximo permitido (" . self::MAX_CANTIDAD . ")");
        }
        if ($enteros && floor($n) != $n) {
            throw new InvalidArgumentException("$etiqueta debe ser un número entero");
        }
        return $n;
    }

    /**
     * Valida un precio unitario. Numérico, dentro de [ $min , MAX_PRECIO ].
     * Por defecto exige > 0; usar $min = 0 para permitir precio cero (ej. insumos).
     * @return float precio normalizado
     */
    public static function precio($valor, $min = 0.01, $etiqueta = 'El precio') {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            throw new InvalidArgumentException("$etiqueta no es un número válido");
        }
        $n = (float) $valor;
        if ($n < $min) {
            throw new InvalidArgumentException("$etiqueta no puede ser menor a $min");
        }
        if ($n > self::MAX_PRECIO) {
            throw new InvalidArgumentException("$etiqueta supera el máximo permitido (" . self::MAX_PRECIO . ")");
        }
        return round($n, 2);
    }

    /**
     * Valida un monto (gasto, total, etc.). Numérico y > 0.
     * @return float monto normalizado
     */
    public static function monto($valor, $etiqueta = 'El monto') {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            throw new InvalidArgumentException("$etiqueta no es un número válido");
        }
        $n = (float) $valor;
        if ($n <= 0) {
            throw new InvalidArgumentException("$etiqueta debe ser mayor a 0");
        }
        if ($n > self::MAX_MONTO) {
            throw new InvalidArgumentException("$etiqueta supera el máximo permitido (" . self::MAX_MONTO . ")");
        }
        return round($n, 2);
    }
}

<?php
/**
 * Archivo de LABORATORIO para probar el loop autofix.
 * Contiene un bug real de clase segura (Unsupported operand types).
 * No se incluye en ningún sitio de producción.
 */

function demo_calcular_descuento($precio) {
    // Bug: $margen es string, se resta a int -> TypeError en PHP 8
    $margen = "diez";
    $resultado = $precio - $margen;
    return $resultado;
}

<?php
/**
 * Script para debuggear detalladamente el problema de fechas
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Debug Detallado de Fechas</h1>";

// Obtener algunos códigos para analizar las fechas
$array_filtro = array("estado" => 0);
$array_opciones = array(
    "limit" => 10,
    "sort" => array('fecha_publicacion' => -1)
);

$lista_codigos_pre = get_all_listado_codigos_array($array_filtro, $array_opciones);
$lista_codigos = isset($lista_codigos_pre["results"]) ? $lista_codigos_pre["results"] : [];

echo "<h2>📊 Análisis de fechas encontradas:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th>Marca</th>";
echo "<th>Fecha Publicación</th>";
echo "<th>Timestamp</th>";
echo "<th>Días desde hoy</th>";
echo "<th>Estado</th>";
echo "</tr>";

$fecha_actual = time();

foreach ($lista_codigos as $codigo) {
    $fecha_str = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;

    if ($fecha_str !== 'SIN FECHA') {
        $timestamp = strtotime($fecha_str);
        $dias_desde_hoy = floor(($fecha_actual - $timestamp) / (24 * 60 * 60));

        $estado_fecha = '';
        $dias_desde_hoy_corregido = diasDesdeFechaCorregida($fecha_str);

        if ($dias_desde_hoy < 0) {
            $estado_fecha = "<span style='color: red;'>FUTURO</span>";
        } elseif ($dias_desde_hoy_corregido <= 7) {
            $estado_fecha = "<span style='color: green;'>RECIENTE</span>";
        } else {
            $estado_fecha = "<span style='color: orange;'>ANTIGUO</span>";
        }

        echo "<tr>";
        echo "<td>$marca</td>";
        echo "<td>$fecha_str</td>";
        echo "<td>$timestamp</td>";
        echo "<td>$dias_desde_hoy días (corr: $dias_desde_hoy_corregido)</td>";
        echo "<td>$estado_fecha</td>";
        echo "</tr>";
    }
}

echo "</table>";

// También verificar códigos destacados que tenían fechas extrañas
echo "<hr><h2>🔍 Códigos destacados con fechas sospechosas:</h2>";
$array_filtro_destacados = array("estado" => 0);
$array_opciones_destacados = array(
    "limit" => 20,
    "sort" => array('destacado_social' => -1, 'destacado' => -1, 'fecha_publicacion' => -1)
);

$lista_destacados_pre = get_all_listado_codigos_array($array_filtro_destacados, $array_opciones_destacados);
$lista_destacados = isset($lista_destacados_pre["results"]) ? $lista_destacados_pre["results"] : [];

foreach ($lista_destacados as $codigo) {
    $fecha_str = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
    $marca = $codigo['marca'] ?? 'SIN MARCA';
    $destacado = isset($codigo['destacado']) ? $codigo['destacado'] : 0;
    $destacado_social = isset($codigo['destacado_social']) ? $codigo['destacado_social'] : 0;

    if ($fecha_str !== 'SIN FECHA') {
        $timestamp = strtotime($fecha_str);
        $dias_desde_hoy = floor(($fecha_actual - $timestamp) / (24 * 60 * 60));

        if ($dias_desde_hoy < -30 || $dias_desde_hoy > 365 || $dias_desde_hoy_corregido < -30 || $dias_desde_hoy_corregido > 365) { // Fechas sospechosas
            echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0; border-left: 3px solid #ffc107;'>";
            echo "⚠️ $marca | Fecha: $fecha_str | Timestamp: $timestamp | Días: $dias_desde_hoy | Destacado: $destacado | Social: $destacado_social";
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Fecha actual del servidor:</strong> " . fechaCorregida('Y-m-d H:i:s', $fecha_actual) . " (Corr: " . date('Y-m-d H:i:s', time()) . ")</p>";
echo "<p><strong>Timestamp corregido:</strong> $fecha_actual</p>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Volver al inicio</a></p>";
?>

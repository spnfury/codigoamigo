<?php
/**
 * Script para verificar si hay códigos recientes disponibles
 */
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
include_once __DIR__ . '/inc/includes.php';
include_once __DIR__ . '/myphp/funciones.php';

echo "<h1>🔍 Verificación de Códigos Recientes</h1>";

// Verificar códigos recientes (últimos 7 días)
$fecha_actual = getFechaActualCorregida();
$fecha_7_dias_atras = $fecha_actual - (7 * 24 * 60 * 60);

echo "<h2>📅 Fecha actual: " . fechaCorregida('Y-m-d H:i:s', $fecha_actual) . "</h2>";
echo "<h2>📅 Fecha 7 días atrás: " . fechaCorregida('Y-m-d H:i:s', $fecha_7_dias_atras) . "</h2>";
echo "<p><small>Fecha original del servidor: " . date('Y-m-d H:i:s', time()) . "</small></p>";
echo "<hr>";

// Consulta para códigos de los últimos 7 días
$array_filtro_recientes = array(
    "estado" => 0,
    "destacado" => 0,
    "fecha_publicacion" => array(
        '$gte' => date('Y-m-d H:i:s', $fecha_7_dias_atras)
    )
);

$array_opciones_recientes = array(
    "limit" => 20,
    "sort" => array('fecha_publicacion' => -1)
);

$lista_recientes_pre = get_all_listado_codigos_array($array_filtro_recientes, $array_opciones_recientes);
$lista_recientes = isset($lista_recientes_pre["results"]) ? $lista_recientes_pre["results"] : [];

echo "<h2>📋 Códigos de los últimos 7 días (" . count($lista_recientes) . "):</h2>";

if (!empty($lista_recientes)) {
    foreach ($lista_recientes as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $titulo = $codigo['titulo'] ?? 'SIN TITULO';
        $descripcion = $codigo['descripcion'] ?? 'SIN DESCRIPCION';

        echo "<div style='margin: 10px 0; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 8px;'>";
        echo "<strong>#" . ($index + 1) . " - $marca</strong> | ";
        echo "Fecha: <strong>$fecha</strong>";
        echo "<br><em>" . htmlspecialchars(substr($descripcion, 0, 100)) . "</em>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ No hay códigos recientes (últimos 7 días)";
    echo "</div>";
}

// Consulta general para comparar
echo "<hr><h2>🔍 Consulta general (sin filtro de fecha):</h2>";
$array_filtro_general = array("estado" => 0, "destacado" => 0);
$array_opciones_general = array(
    "limit" => 20,
    "sort" => array('fecha_publicacion' => -1)
);

$lista_general_pre = get_all_listado_codigos_array($array_filtro_general, $array_opciones_general);
$lista_general = isset($lista_general_pre["results"]) ? $lista_general_pre["results"] : [];

echo "<h3>Total códigos encontrados: " . count($lista_general) . "</h3>";

if (!empty($lista_general)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>";
    foreach ($lista_general as $index => $codigo) {
        $fecha = isset($codigo['fecha_publicacion']) ? $codigo['fecha_publicacion'] : 'SIN FECHA';
        $marca = $codigo['marca'] ?? 'SIN MARCA';
        $timestamp = strtotime($fecha);

        $dias_desde_publicacion = diasDesdeFechaCorregida($fecha);
        $es_reciente = $dias_desde_publicacion <= 7;
        $color = $es_reciente ? '#d4edda' : '#f8d7da';
        $icono = $es_reciente ? '🟢' : '🔴';

        echo "<div style='margin: 5px 0; padding: 8px; background: $color; border-left: 3px solid " . ($es_reciente ? '#28a745' : '#dc3545') . "'>";
        echo "$icono #$index - $marca | $fecha | " . ($es_reciente ? 'RECIENTE' : 'ANTIGUO') . " ($dias_desde_publicacion días)";
        echo "</div>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='debug_orden_codigos.php' target='_blank'>🔍 Ver orden completo de códigos</a></p>";
echo "<p><a href='/' style='color: #E30613; text-decoration: none; font-weight: bold;'>← Volver al inicio</a></p>";
?>
